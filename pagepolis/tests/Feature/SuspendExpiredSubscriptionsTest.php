<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use App\Services\DinahostingService;
use App\Services\NginxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SuspendExpiredSubscriptionsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithExpiredGrace(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'grace_period_ends_at' => now()->subHour(),
        ], $attrs));
    }

    private function domainFor(User $user, array $attrs = []): Domain
    {
        $project = Project::create([
            'user_id' => $user->id,
            'name'    => 'Test site',
            'html'    => '<h1>hi</h1>',
            'css'     => '',
            'js'      => '',
            'status'  => 'published',
        ]);

        return Domain::create(array_merge([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'test-' . $user->id . '.example.com',
            'type'       => 'path',
            'status'     => 'active',
        ], $attrs));
    }

    private function mockExternalServices(): void
    {
        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('release')->andReturn(null);
        });
        $this->mock(NginxService::class, function (MockInterface $mock) {
            $mock->shouldReceive('removeVHost')->andReturn(null);
        });
    }

    public function test_user_with_expired_grace_is_soft_deleted(): void
    {
        $this->mockExternalServices();
        $user = $this->userWithExpiredGrace();

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();

        $this->assertSoftDeleted(User::class, ['id' => $user->id]);
    }

    public function test_user_with_future_grace_period_is_not_touched(): void
    {
        $this->mockExternalServices();
        $user = User::factory()->create(['grace_period_ends_at' => now()->addDays(5)]);

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();

        $this->assertNotNull(User::find($user->id));
    }

    public function test_user_with_no_grace_period_is_not_touched(): void
    {
        $this->mockExternalServices();
        $user = User::factory()->create(['grace_period_ends_at' => null]);

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();

        $this->assertNotNull(User::find($user->id));
    }

    public function test_projects_are_soft_deleted(): void
    {
        $this->mockExternalServices();
        $user    = $this->userWithExpiredGrace();
        $project = Project::create([
            'user_id' => $user->id,
            'name'    => 'My site',
            'html'    => '',
            'css'     => '',
            'js'      => '',
            'status'  => 'published',
        ]);

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();

        $this->assertSoftDeleted(Project::class, ['id' => $project->id]);
    }

    public function test_dinahosting_release_called_only_for_custom_domains_with_registrar_id(): void
    {
        $user = $this->userWithExpiredGrace();
        $this->domainFor($user, ['type' => 'custom', 'domain' => 'custom.example.com', 'registrar_id' => 'REG123']);
        $this->domainFor($user, ['type' => 'path',   'domain' => 'pagepolis-slug']);

        $dinahosting = $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('release')->with('custom.example.com')->once();
            $mock->shouldNotReceive('release')->with('pagepolis-slug');
        });
        $this->mock(NginxService::class, function (MockInterface $mock) {
            $mock->shouldReceive('removeVHost')->twice();
        });

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();
    }

    public function test_dinahosting_not_called_for_custom_domain_without_registrar_id(): void
    {
        $user = $this->userWithExpiredGrace();
        $this->domainFor($user, ['type' => 'custom', 'domain' => 'custom.example.com', 'registrar_id' => null]);

        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('release');
        });
        $this->mock(NginxService::class, function (MockInterface $mock) {
            $mock->shouldReceive('removeVHost')->once();
        });

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();
    }

    public function test_nginx_remove_vhost_called_for_all_domains(): void
    {
        $user = $this->userWithExpiredGrace();
        $this->domainFor($user, ['type' => 'path',   'domain' => 'path-domain']);
        $this->domainFor($user, ['type' => 'custom', 'domain' => 'custom-domain', 'registrar_id' => null]);

        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('release')->never();
        });
        $this->mock(NginxService::class, function (MockInterface $mock) {
            $mock->shouldReceive('removeVHost')->twice();
        });

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();
    }

    public function test_domain_status_set_to_released(): void
    {
        $this->mockExternalServices();
        $user   = $this->userWithExpiredGrace();
        $domain = $this->domainFor($user);

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();

        $this->assertSame('released', $domain->fresh()->status);
    }

    public function test_grace_period_preserved_when_external_service_fails(): void
    {
        $user = $this->userWithExpiredGrace();
        $this->domainFor($user);

        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('release')->never();
        });
        $this->mock(NginxService::class, function (MockInterface $mock) {
            $mock->shouldReceive('removeVHost')->andThrow(new \RuntimeException('nginx unavailable'));
        });

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();

        // User must NOT be deleted — grace period preserved so next run retries.
        $fresh = User::withTrashed()->find($user->id);
        $this->assertNotNull($fresh);
        $this->assertNull($fresh->deleted_at);
        $this->assertNotNull($fresh->grace_period_ends_at);
    }

    public function test_failure_for_one_user_does_not_prevent_processing_others(): void
    {
        $badUser  = $this->userWithExpiredGrace();
        $goodUser = $this->userWithExpiredGrace();
        $this->domainFor($badUser);

        $calls = 0;
        $this->mock(DinahostingService::class, function (MockInterface $mock) {
            $mock->shouldReceive('release')->never();
        });
        $this->mock(NginxService::class, function (MockInterface $mock) use (&$calls, $badUser) {
            $mock->shouldReceive('removeVHost')->andReturnUsing(function () use (&$calls, $badUser) {
                $calls++;
                // Fail only for the first user's domain
                if ($calls === 1) {
                    throw new \RuntimeException('nginx error');
                }
            });
        });

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();

        // good user had no domains, so no nginx call → should be deleted
        $this->assertSoftDeleted(User::class, ['id' => $goodUser->id]);
        // bad user had a domain that failed → should NOT be deleted
        $this->assertNotSoftDeleted(User::class, ['id' => $badUser->id]);
    }
}
