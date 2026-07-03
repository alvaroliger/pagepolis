<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    use RefreshDatabase;

    private function publishedProject(User $user, string $slug): Project
    {
        return Project::create([
            'user_id'      => $user->id,
            'name'         => 'Test',
            'html'         => '<h1>Hola</h1>',
            'css'          => '',
            'js'           => '',
            'slug'         => $slug,
            'status'       => 'published',
            'published_at' => now(),
        ]);
    }

    public function test_sitemap_includes_static_pages(): void
    {
        $res = $this->get('/sitemap.xml');

        $res->assertOk()
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee(url('/'), false)
            ->assertSee(url('/register'), false)
            ->assertSee(url('/plantillas'), false);
    }

    public function test_sitemap_includes_free_path_sites(): void
    {
        $user    = User::factory()->create();
        $project = $this->publishedProject($user, 'mi-negocio-test');

        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'mi-negocio-test',
            'type'       => 'path',
            'status'     => 'active',
        ]);

        $res = $this->get('/sitemap.xml');

        $res->assertOk()->assertSee('/s/mi-negocio-test', false);
    }

    public function test_sitemap_includes_subdomain_sites(): void
    {
        $user    = User::factory()->create();
        $project = $this->publishedProject($user, 'mi-negocio-sub');

        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'minegocio.pagepolis.com',
            'type'       => 'subdomain',
            'status'     => 'active',
        ]);

        $res = $this->get('/sitemap.xml');

        $res->assertOk()->assertSee('https://minegocio.pagepolis.com', false);
    }

    public function test_sitemap_omits_custom_domain_sites(): void
    {
        $user    = User::factory()->create();
        $project = $this->publishedProject($user, 'mi-negocio-custom');

        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'minegocio.com',
            'type'       => 'custom',
            'status'     => 'active',
        ]);

        $res = $this->get('/sitemap.xml');

        $res->assertOk()->assertDontSee('minegocio.com', false);
    }

    public function test_sitemap_omits_pending_and_draft_sites(): void
    {
        $user    = User::factory()->create();
        $project = $this->publishedProject($user, 'mi-negocio-draft');

        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'mi-negocio-draft',
            'type'       => 'path',
            'status'     => 'pending',
        ]);

        $res = $this->get('/sitemap.xml');

        $res->assertOk()->assertDontSee('/s/mi-negocio-draft', false);
    }
}
