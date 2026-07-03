<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function project(User $user, array $attrs = []): Project
    {
        return Project::create(array_merge([
            'user_id' => $user->id,
            'name'    => 'Test web',
            'html'    => '<h1>Hola</h1>',
            'css'     => '',
            'js'      => '',
            'status'  => 'draft',
        ], $attrs));
    }

    private function onboarding(User $user): array
    {
        return $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->inertiaProps('onboarding');
    }

    public function test_onboarding_all_false_for_draft_only_user(): void
    {
        $user = $this->user();
        $this->project($user);

        $ob = $this->onboarding($user);

        $this->assertFalse($ob['published']);
        $this->assertFalse($ob['domain']);
    }

    public function test_onboarding_published_true_when_any_project_is_published(): void
    {
        $user = $this->user();
        $this->project($user, ['status' => 'published']);

        $ob = $this->onboarding($user);

        $this->assertTrue($ob['published']);
        $this->assertFalse($ob['domain']);
    }

    public function test_onboarding_domain_true_when_custom_domain_is_active(): void
    {
        $user    = $this->user();
        $project = $this->project($user, ['status' => 'published']);

        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'miweb.com',
            'type'       => 'custom',
            'status'     => 'active',
        ]);

        $ob = $this->onboarding($user);

        $this->assertTrue($ob['published']);
        $this->assertTrue($ob['domain']);
    }

    public function test_onboarding_domain_true_for_subdomain_type(): void
    {
        $user    = $this->user();
        $project = $this->project($user, ['status' => 'published']);

        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'miweb.pagepolis.com',
            'type'       => 'subdomain',
            'status'     => 'active',
        ]);

        $ob = $this->onboarding($user);

        $this->assertTrue($ob['domain']);
    }

    public function test_onboarding_domain_false_for_free_path_domain(): void
    {
        $user    = $this->user();
        $project = $this->project($user, ['status' => 'published']);

        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => $project->slug,
            'type'       => 'path',
            'status'     => 'active',
        ]);

        $ob = $this->onboarding($user);

        $this->assertTrue($ob['published']);
        $this->assertFalse($ob['domain'], 'Free /s/slug domains must not satisfy the domain step');
    }

    public function test_onboarding_domain_false_for_inactive_custom_domain(): void
    {
        $user    = $this->user();
        $project = $this->project($user, ['status' => 'published']);

        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'miweb.com',
            'type'       => 'custom',
            'status'     => 'pending',
        ]);

        $ob = $this->onboarding($user);

        $this->assertFalse($ob['domain'], 'Pending domains must not satisfy the domain step');
    }

    public function test_dashboard_includes_onboarding_key_for_new_user_with_no_projects(): void
    {
        $user = $this->user();
        $ob   = $this->onboarding($user);

        $this->assertArrayHasKey('published', $ob);
        $this->assertArrayHasKey('domain', $ob);
        $this->assertFalse($ob['published']);
        $this->assertFalse($ob['domain']);
    }
}
