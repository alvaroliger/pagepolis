<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Authorization and lifecycle tests for the destructive project operations:
 * permanent delete (force-destroy), restore from trash, and free publish.
 *
 * These boundaries matter because:
 *  - forceDestroy is irreversible — a regression that drops the auth check
 *    would let any user wipe another user's project permanently.
 *  - restore operates on soft-deleted rows that are otherwise invisible in
 *    normal queries, making the authorization guard easy to accidentally skip.
 *  - publishFree changes a project's public visibility; another user triggering
 *    it would publish content they don't own under the owner's account.
 */
class ProjectLifecycleTest extends TestCase
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
            'name'    => 'Mi web',
            'html'    => '<h1>Test</h1>',
            'css'     => '',
            'js'      => '',
            'status'  => 'draft',
        ], $attrs));
    }

    private function domain(Project $project): Domain
    {
        return Domain::create([
            'user_id'    => $project->user_id,
            'project_id' => $project->id,
            'domain'     => $project->slug,
            'type'       => 'path',
            'status'     => 'active',
        ]);
    }

    // ── force-destroy (permanent deletion) ──────────────────────────────

    public function test_owner_can_force_destroy_trashed_project(): void
    {
        $user    = $this->user();
        $project = $this->project($user);
        $this->domain($project);

        $project->delete(); // soft-delete first, as the UI flow requires

        $this->actingAs($user)
            ->delete("/proyectos/{$project->id}/eliminar-definitivo")
            ->assertRedirect('/dashboard');

        // Row must be gone entirely, not just soft-deleted.
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
        // Domain should be gone too (cascaded by the DB foreign key).
        $this->assertDatabaseMissing('domains', ['project_id' => $project->id]);
    }

    public function test_other_user_cannot_force_destroy_project(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $project = $this->project($owner);
        $project->delete();

        $this->actingAs($other)
            ->delete("/proyectos/{$project->id}/eliminar-definitivo")
            ->assertForbidden();

        // Project must still exist (soft-deleted, not purged).
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    public function test_force_destroy_requires_authentication(): void
    {
        $owner   = $this->user();
        $project = $this->project($owner);
        $project->delete();

        $this->delete("/proyectos/{$project->id}/eliminar-definitivo")
            ->assertRedirect('/login');

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    // ── restore from trash ──────────────────────────────────────────────

    public function test_other_user_cannot_restore_trashed_project(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $project = $this->project($owner);
        $project->delete();

        $this->actingAs($other)
            ->post("/proyectos/{$project->id}/restaurar")
            ->assertForbidden();

        // Must still be soft-deleted.
        $this->assertSoftDeleted('projects', ['id' => $project->id]);
    }

    // ── duplicate ────────────────────────────────────────────────────────

    public function test_owner_can_duplicate_project(): void
    {
        $user    = $this->user();
        $project = $this->project($user, ['html' => '<h1>Original</h1>', 'css' => 'h1{color:blue}']);

        $this->actingAs($user)
            ->post("/proyectos/{$project->id}/duplicar")
            ->assertRedirect();

        $this->assertSame(2, $user->projects()->count());

        $copy = $user->projects()->where('id', '!=', $project->id)->firstOrFail();
        $this->assertSame('Mi web (copia)', $copy->name);
        $this->assertSame('<h1>Original</h1>', $copy->html);
        $this->assertSame('h1{color:blue}', $copy->css);
        $this->assertSame('draft', $copy->status);
        $this->assertNotSame($project->slug, $copy->slug);
    }

    public function test_other_user_cannot_duplicate_project(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $project = $this->project($owner);

        $this->actingAs($other)
            ->post("/proyectos/{$project->id}/duplicar")
            ->assertForbidden();

        $this->assertSame(1, $owner->projects()->count());
    }

    public function test_duplicate_requires_authentication(): void
    {
        $owner   = $this->user();
        $project = $this->project($owner);

        $this->post("/proyectos/{$project->id}/duplicar")
            ->assertRedirect('/login');

        $this->assertSame(1, $owner->projects()->count());
    }

    // ── free publish ────────────────────────────────────────────────────

    public function test_other_user_cannot_publish_free_on_another_users_project(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $project = $this->project($owner);

        $this->actingAs($other)
            ->postJson('/publicar/gratis', ['project_id' => $project->id])
            ->assertNotFound();

        $this->assertSame('draft', $project->fresh()->status);
    }

    public function test_publish_free_requires_authentication(): void
    {
        $owner   = $this->user();
        $project = $this->project($owner);

        $this->postJson('/publicar/gratis', ['project_id' => $project->id])
            ->assertUnauthorized();

        $this->assertSame('draft', $project->fresh()->status);
    }
}
