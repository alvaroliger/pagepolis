<?php

namespace Tests\Feature;

use App\Jobs\DeploySite;
use App\Jobs\PurchaseDomain;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use App\Services\CloudflareService;
use App\Services\DinahostingService;
use App\Services\NginxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * CAJA NEGRA — qué le pasa al Domain (y por tanto al Dashboard) cuando el
 * aprovisionamiento falla. Antes esto se registraba en el log y ahí quedaba: el
 * cliente había pagado y su proyecto se quedaba en "Borrador" para siempre, sin
 * ningún aviso de qué había pasado.
 */
class PurchaseDomainJobTest extends TestCase
{
    use RefreshDatabase;

    private function domain(string $type = 'subdomain'): Domain
    {
        config(['services.server.public_ip' => '203.0.113.10']);

        $u = User::factory()->create(['email_verified_at' => now()]);
        $p = Project::create([
            'user_id' => $u->id, 'name' => 'Web', 'status' => 'draft',
            'html' => '<h1>Hola</h1>', 'css' => '', 'js' => '',
        ]);

        return Domain::create([
            'user_id' => $u->id, 'project_id' => $p->id,
            'domain' => 'minegocio.pagepolis.com', 'type' => $type, 'status' => 'pending',
        ]);
    }

    public function test_domain_marked_failed_when_cloudflare_errors(): void
    {
        Queue::fake();
        $domain = $this->domain();

        $this->mock(CloudflareService::class, function ($mock) {
            $mock->shouldReceive('addSubdomainRecord')->andThrow(new \Exception('Cloudflare API down'));
        });

        (new PurchaseDomain($domain))->handle(
            app(DinahostingService::class),
            app(CloudflareService::class),
            app(NginxService::class),
        );

        $this->assertSame('failed', $domain->fresh()->status);
        Queue::assertNotPushed(DeploySite::class);
    }

    public function test_domain_marked_failed_when_nginx_errors(): void
    {
        Queue::fake();
        $domain = $this->domain();

        $this->mock(CloudflareService::class, function ($mock) {
            $mock->shouldReceive('addSubdomainRecord')->andReturn('cf-record-123');
        });
        $this->mock(NginxService::class, function ($mock) {
            $mock->shouldReceive('createVHost')->andThrow(new \Exception('nginx reload failed'));
        });

        (new PurchaseDomain($domain))->handle(
            app(DinahostingService::class),
            app(CloudflareService::class),
            app(NginxService::class),
        );

        $this->assertSame('failed', $domain->fresh()->status);
        Queue::assertNotPushed(DeploySite::class);
    }

    public function test_domain_marked_active_and_deploys_when_provisioning_succeeds(): void
    {
        Queue::fake();
        $domain = $this->domain();

        $this->mock(CloudflareService::class, function ($mock) {
            $mock->shouldReceive('addSubdomainRecord')->andReturn('cf-record-123');
        });
        $this->mock(NginxService::class, function ($mock) {
            $mock->shouldReceive('createVHost')->andReturn(null);
        });

        (new PurchaseDomain($domain))->handle(
            app(DinahostingService::class),
            app(CloudflareService::class),
            app(NginxService::class),
        );

        $this->assertSame('active', $domain->fresh()->status);
        Queue::assertPushed(DeploySite::class);
    }

    public function test_dashboard_reports_failed_domain_status(): void
    {
        $domain = $this->domain();
        $domain->update(['status' => 'failed']);
        $project = $domain->project;

        $response = $this->actingAs($domain->user)->get('/dashboard');

        $response->assertInertia(fn ($page) => $page
            ->where('projects.0.domain_status', 'failed')
            ->where('projects.0.domain', $domain->domain)
        );
    }
}
