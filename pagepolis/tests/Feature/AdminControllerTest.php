<?php

namespace Tests\Feature;

use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function regularUser(): User
    {
        return User::factory()->create();
    }

    private function domainFor(User $user, string $status = 'active'): Domain
    {
        $project = Project::create([
            'user_id' => $user->id,
            'name'    => 'Test site',
            'html'    => '<h1>Hi</h1>',
            'css'     => '',
            'js'      => '',
            'status'  => 'published',
        ]);

        return Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'example.com',
            'type'       => 'custom',
            'status'     => $status,
        ]);
    }

    // ── Access control ────────────────────────────────────────────────────────

    public function test_guest_cannot_access_admin_panel(): void
    {
        // Auth middleware redirects unauthenticated users to login (302).
        $this->get('/admin')->assertRedirect();
    }

    public function test_regular_user_cannot_access_admin_panel(): void
    {
        $this->actingAs($this->regularUser())->get('/admin')->assertStatus(403);
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }

    public function test_regular_user_cannot_suspend_other_user(): void
    {
        $target = $this->regularUser();
        $this->actingAs($this->regularUser())
            ->postJson("/admin/usuarios/{$target->id}/suspender")
            ->assertStatus(403);
    }

    // ── suspendUser ───────────────────────────────────────────────────────────

    public function test_admin_can_suspend_user(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser();

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/suspender")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull($target->fresh()->grace_period_ends_at);
    }

    public function test_suspending_user_suspends_their_domains(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser();
        $domain = $this->domainFor($target, 'active');

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/suspender")
            ->assertOk();

        $this->assertSame('suspended', $domain->fresh()->status);
        $this->assertNotNull($domain->fresh()->suspended_at);
    }

    // ── extendGrace ───────────────────────────────────────────────────────────

    public function test_admin_can_extend_grace_period(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser();

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/gracia", ['days' => 14])
            ->assertOk()
            ->assertJson(['success' => true]);

        $fresh = $target->fresh();
        $this->assertNotNull($fresh->grace_period_ends_at);
        // Should be roughly 14 days from now (within a 5-minute window).
        $this->assertEqualsWithDelta(
            now()->addDays(14)->timestamp,
            $fresh->grace_period_ends_at->timestamp,
            300
        );
    }

    public function test_extend_grace_requires_days_field(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser();

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/gracia", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['days']);
    }

    public function test_extend_grace_rejects_days_out_of_range(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser();

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/gracia", ['days' => 0])
            ->assertStatus(422);

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/gracia", ['days' => 91])
            ->assertStatus(422);
    }

    // ── reactivateUser ────────────────────────────────────────────────────────

    public function test_admin_can_reactivate_suspended_user(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser(['grace_period_ends_at' => now()->addDays(10)]);

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/reactivar")
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull($target->fresh()->grace_period_ends_at);
    }

    public function test_reactivating_user_restores_their_suspended_domains(): void
    {
        $admin  = $this->admin();
        $target = $this->regularUser(['grace_period_ends_at' => now()->addDays(10)]);
        $domain = $this->domainFor($target, 'suspended');
        $domain->update(['suspended_at' => now()->subDay(), 'grace_period_ends_at' => now()->addDays(10)]);

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/reactivar")
            ->assertOk();

        $freshDomain = $domain->fresh();
        $this->assertSame('active', $freshDomain->status);
        $this->assertNull($freshDomain->suspended_at);
        $this->assertNull($freshDomain->grace_period_ends_at);
    }

    public function test_reactivating_user_does_not_touch_already_active_domains(): void
    {
        $admin      = $this->admin();
        $target     = $this->regularUser(['grace_period_ends_at' => now()->addDays(10)]);
        $activeDomain = $this->domainFor($target, 'active');

        $this->actingAs($admin)
            ->postJson("/admin/usuarios/{$target->id}/reactivar")
            ->assertOk();

        // Active domains must remain active; their timestamps must not be changed.
        $this->assertSame('active', $activeDomain->fresh()->status);
    }
}
