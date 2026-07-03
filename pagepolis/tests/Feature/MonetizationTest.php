<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonetizationTest extends TestCase
{
    use RefreshDatabase;

    private function project(User $user, array $attrs = []): Project
    {
        return Project::create(array_merge([
            'user_id' => $user->id,
            'name'    => 'Mi web',
            'html'    => '<h1>Hola</h1>',
            'css'     => 'h1{color:red}',
            'js'      => '',
            'status'  => 'draft',
        ], $attrs));
    }

    public function test_free_user_can_publish_to_path(): void
    {
        $user    = User::factory()->create();
        $project = $this->project($user);

        $res = $this->actingAs($user)->postJson('/publicar/gratis', ['project_id' => $project->id]);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertSame('published', $project->fresh()->status);
        $this->assertDatabaseHas('domains', ['project_id' => $project->id, 'type' => 'path', 'status' => 'active']);
    }

    public function test_free_user_blocked_after_publish_limit(): void
    {
        config(['pagepolis.limits.free_max_published' => 1]);
        $user = User::factory()->create();
        $p1   = $this->project($user);
        $p2   = $this->project($user);

        $this->actingAs($user)->postJson('/publicar/gratis', ['project_id' => $p1->id])->assertOk();

        $res = $this->actingAs($user)->postJson('/publicar/gratis', ['project_id' => $p2->id]);
        $res->assertStatus(402)->assertJson(['upgrade' => true]);
        $this->assertSame('draft', $p2->fresh()->status);
    }

    public function test_publish_free_never_exceeds_limit_even_at_exact_boundary(): void
    {
        config(['pagepolis.limits.free_max_published' => 2]);
        $user = User::factory()->create();
        $p1   = $this->project($user);
        $p2   = $this->project($user);
        $p3   = $this->project($user);

        $this->actingAs($user)->postJson('/publicar/gratis', ['project_id' => $p1->id])->assertOk();
        $this->actingAs($user)->postJson('/publicar/gratis', ['project_id' => $p2->id])->assertOk();
        $this->actingAs($user)->postJson('/publicar/gratis', ['project_id' => $p3->id])->assertStatus(402);

        $this->assertSame(2, $user->projects()->where('status', 'published')->count());
    }

    public function test_publish_free_ignores_soft_deleted_projects_in_limit_count(): void
    {
        config(['pagepolis.limits.free_max_published' => 1]);
        $user = User::factory()->create();
        $old  = $this->project($user, ['status' => 'published', 'published_at' => now()]);
        $old->delete();
        $new = $this->project($user);

        $res = $this->actingAs($user)->postJson('/publicar/gratis', ['project_id' => $new->id]);

        $res->assertOk();
        $this->assertSame('published', $new->fresh()->status);
    }

    public function test_free_user_blocked_after_project_limit(): void
    {
        config(['pagepolis.limits.free_max_projects' => 1]);
        $user = User::factory()->create();
        $this->project($user); // ya tiene 1

        $this->actingAs($user)->from('/plantillas')->post('/proyectos', ['name' => 'Otra web'])
            ->assertRedirect('/plantillas')
            ->assertSessionHas('error');

        $this->assertSame(1, $user->projects()->count());
    }

    public function test_published_free_site_shows_badge(): void
    {
        $user    = User::factory()->create();
        $project = $this->project($user, ['status' => 'published', 'published_at' => now()]);

        $res = $this->get('/s/' . $project->slug);

        $res->assertOk()->assertSee('Hecho con Pagepolis');
    }

    public function test_gdpr_export_returns_user_data(): void
    {
        $user    = User::factory()->create(['email' => 'export@test.com']);
        $project = $this->project($user, ['status' => 'published', 'slug' => 'test-export']);
        $project->leads()->create([
            'name'    => 'Cliente Uno',
            'email'   => 'cliente@example.com',
            'phone'   => '600111222',
            'message' => 'Quiero información',
            'payload' => ['nombre' => 'Cliente Uno'],
        ]);

        $res = $this->actingAs($user)->get('/perfil/exportar');

        $res->assertOk()
            ->assertJsonPath('usuario.email', 'export@test.com')
            ->assertJsonCount(1, 'proyectos')
            ->assertJsonCount(1, 'proyectos.0.leads')
            ->assertJsonPath('proyectos.0.leads.0.email', 'cliente@example.com')
            ->assertJsonPath('proyectos.0.leads.0.mensaje', 'Quiero información');
    }

    public function test_zip_export_only_for_owner(): void
    {
        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $project = $this->project($owner);

        $this->actingAs($owner)->get("/proyectos/{$project->id}/zip")
            ->assertOk()->assertHeader('Content-Type', 'application/zip');

        $this->actingAs($other)->get("/proyectos/{$project->id}/zip")->assertForbidden();
    }

    public function test_project_can_be_restored_from_trash(): void
    {
        $user    = User::factory()->create();
        $project = $this->project($user);
        $project->delete();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);

        $this->actingAs($user)->post("/proyectos/{$project->id}/restaurar")->assertRedirect('/dashboard');

        $this->assertDatabaseHas('projects', ['id' => $project->id, 'deleted_at' => null]);
    }

    public function test_legal_pages_are_public(): void
    {
        $this->get('/terminos')->assertOk();
        $this->get('/privacidad')->assertOk();
    }
}
