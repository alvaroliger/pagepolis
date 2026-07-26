<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Contrato de `GET /publicar` del que depende el front (Dashboard/Index.tsx,
 * Publish/Index.tsx): sin `project_id` el paso de dominio se salta en
 * silencio y un usuario puede acabar pagando sin que se reserve ningún
 * dominio (el webhook de Stripe no encuentra ningún Domain "pending" que
 * provisionar). Estos tests fijan que `project` solo se rellena con un
 * `project_id` válido y del propio usuario.
 */
class PublishControllerTest extends TestCase
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

    public function test_publish_page_has_no_project_without_project_id(): void
    {
        $user = $this->user();
        $this->project($user);

        $project = $this->actingAs($user)
            ->get('/publicar')
            ->assertOk()
            ->inertiaProps('project');

        $this->assertNull($project, 'Sin project_id, el front no debe poder continuar el flujo de dominio.');
    }

    public function test_publish_page_resolves_project_from_project_id(): void
    {
        $user    = $this->user();
        $project = $this->project($user, ['name' => 'Mi negocio']);

        $props = $this->actingAs($user)
            ->get("/publicar?project_id={$project->id}")
            ->assertOk()
            ->inertiaProps('project');

        $this->assertSame($project->id, $props['id']);
        $this->assertSame('Mi negocio', $props['name']);
    }

    public function test_publish_page_ignores_project_id_owned_by_another_user(): void
    {
        $owner        = $this->user();
        $otherUser    = $this->user();
        $otherProject = $this->project($otherUser);

        $project = $this->actingAs($owner)
            ->get("/publicar?project_id={$otherProject->id}")
            ->assertOk()
            ->inertiaProps('project');

        $this->assertNull($project, 'Un project_id de otro usuario nunca debe resolverse.');
    }
}
