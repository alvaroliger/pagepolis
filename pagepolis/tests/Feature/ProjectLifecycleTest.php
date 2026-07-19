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

    // ── unpublish ────────────────────────────────────────────────────────

    public function test_owner_can_unpublish_a_published_project(): void
    {
        $user    = $this->user();
        $project = $this->project($user, ['status' => 'published', 'published_at' => now()]);

        $this->actingAs($user)
            ->postJson('/publicar/despublicar', ['project_id' => $project->id])
            ->assertOk()
            ->assertJson(['success' => true]);

        $project->refresh();
        $this->assertSame('draft', $project->status);
        $this->assertNull($project->published_at);
    }

    public function test_unpublish_frees_a_slot_under_the_free_publish_limit(): void
    {
        config(['pagepolis.limits.free_max_published' => 1]);
        $user = $this->user();
        $published = $this->project($user, ['status' => 'published', 'published_at' => now()]);
        $draft = $this->project($user);

        // Limit already reached: a second project can't be published yet.
        $this->actingAs($user)
            ->postJson('/publicar/gratis', ['project_id' => $draft->id])
            ->assertStatus(402);

        $this->actingAs($user)
            ->postJson('/publicar/despublicar', ['project_id' => $published->id])
            ->assertOk();

        // Slot freed: the other project can now be published.
        $this->actingAs($user)
            ->postJson('/publicar/gratis', ['project_id' => $draft->id])
            ->assertOk();
        $this->assertSame('published', $draft->fresh()->status);
    }

    public function test_other_user_cannot_unpublish_another_users_project(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $project = $this->project($owner, ['status' => 'published', 'published_at' => now()]);

        $this->actingAs($other)
            ->postJson('/publicar/despublicar', ['project_id' => $project->id])
            ->assertNotFound();

        $this->assertSame('published', $project->fresh()->status);
    }

    public function test_unpublish_requires_authentication(): void
    {
        $owner   = $this->user();
        $project = $this->project($owner, ['status' => 'published', 'published_at' => now()]);

        $this->postJson('/publicar/despublicar', ['project_id' => $project->id])
            ->assertUnauthorized();

        $this->assertSame('published', $project->fresh()->status);
    }
}
