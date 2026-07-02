<?php

namespace Tests\Feature;

use App\Console\Commands\SuspendExpiredSubscriptions;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifica que admin-suspend usa admin_suspended_at (no grace_period_ends_at),
 * evitando que SuspendExpiredSubscriptions elimine datos de forma no deseada.
 */
class AdminSuspendTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'email_verified_at' => now()]);
    }

    private function regularUser(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $attrs));
    }

    // -------------------------------------------------------------------------
    // suspendUser
    // -------------------------------------------------------------------------

    public function test_suspend_sets_admin_suspended_at_not_grace_period(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser();

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/suspender")
            ->assertOk();

        $target->refresh();
        $this->assertNotNull($target->admin_suspended_at, 'admin_suspended_at must be set');
        $this->assertNull($target->grace_period_ends_at, 'grace_period_ends_at must NOT be touched');
    }

    public function test_suspend_marks_domains_as_suspended(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser();
        $domain = Domain::create([
            'user_id'    => $target->id,
            'project_id' => Project::create(['user_id' => $target->id, 'name' => 'X', 'status' => 'published'])->id,
            'domain'     => 'test.com',
            'type'       => 'custom',
            'status'     => 'active',
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/suspender")
            ->assertOk();

        $this->assertSame('suspended', $domain->fresh()->status);
    }

    public function test_isAdminSuspended_returns_true_after_suspend(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser();

        $this->actingAs($admin)->postJson("/admin/usuarios/{$target->id}/suspender");

        $this->assertTrue($target->fresh()->isAdminSuspended());
    }

    // -------------------------------------------------------------------------
    // reactivateUser
    // -------------------------------------------------------------------------

    public function test_reactivate_clears_admin_suspended_at_and_grace_period(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser([
            'admin_suspended_at'   => now(),
            'grace_period_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/reactivar")
            ->assertOk();

        $target->refresh();
        $this->assertNull($target->admin_suspended_at);
        $this->assertNull($target->grace_period_ends_at);
    }

    public function test_reactivate_restores_suspended_domains(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser();
        $domain = Domain::create([
            'user_id'    => $target->id,
            'project_id' => Project::create(['user_id' => $target->id, 'name' => 'X', 'status' => 'published'])->id,
            'domain'     => 'test.com',
            'type'       => 'custom',
            'status'     => 'suspended',
        ]);

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/reactivar")
            ->assertOk();

        $this->assertSame('active', $domain->fresh()->status);
    }

    // -------------------------------------------------------------------------
    // SuspendExpiredSubscriptions: must skip admin-suspended users
    // -------------------------------------------------------------------------

    public function test_expire_command_skips_admin_suspended_users(): void
    {
        // User was admin-suspended AND has a past grace_period_ends_at — the command
        // must NOT delete their data.
        $target = $this->regularUser([
            'grace_period_ends_at' => now()->subDay(),
            'admin_suspended_at'   => now()->subDay(),
        ]);
        Project::create(['user_id' => $target->id, 'name' => 'Mi web', 'status' => 'draft']);

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
        $this->assertDatabaseCount('projects', 1);
    }

    public function test_expire_command_still_processes_billing_expired_users(): void
    {
        // A user with an expired grace period but NOT admin-suspended must be processed.
        $target = $this->regularUser([
            'grace_period_ends_at' => now()->subDay(),
            'admin_suspended_at'   => null,
        ]);

        $this->artisan('pagepolis:suspend-expired')->assertSuccessful();

        $this->assertSoftDeleted('users', ['id' => $target->id]);
    }

    // -------------------------------------------------------------------------
    // DomainController: admin-suspended users cannot reserve new domains
    // -------------------------------------------------------------------------

    public function test_suspended_user_cannot_reserve_domain(): void
    {
        $target  = $this->regularUser(['admin_suspended_at' => now()]);
        $project = Project::create(['user_id' => $target->id, 'name' => 'X', 'status' => 'draft']);

        $this->actingAs($target)->postJson('/dominios/reservar', [
            'domain'     => 'mi-dominio.com',
            'type'       => 'custom',
            'project_id' => $project->id,
        ])->assertStatus(403);
    }

    public function test_non_suspended_user_can_reach_reserve_endpoint(): void
    {
        $target  = $this->regularUser();
        $project = Project::create(['user_id' => $target->id, 'name' => 'X', 'status' => 'draft']);

        // Non-subscribed user can reserve (returns needs_payment), not 403.
        $res = $this->actingAs($target)->postJson('/dominios/reservar', [
            'domain'     => 'mi-dominio.com',
            'type'       => 'custom',
            'project_id' => $project->id,
        ]);

        $this->assertNotEquals(403, $res->status());
    }
}
