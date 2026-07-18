<?php

namespace Tests\Feature;

use App\Http\Middleware\AiRateLimit;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Unit + integration tests for the atomic AiRateLimit::reserve() method and the
 * middleware's slot-release-on-failure behaviour.
 */
class AiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $attrs));
    }

    private function project(User $u): Project
    {
        return Project::create([
            'user_id' => $u->id, 'name' => 'Test', 'status' => 'draft',
            'html'    => '<header></header><main></main>',
            'css'     => '',
            'js'      => '',
        ]);
    }

    // ── reserve() ────────────────────────────────────────────────────────

    public function test_reserve_increments_when_under_limit(): void
    {
        $u = $this->user();
        $u->update(['ai_calls_today' => 3, 'ai_calls_reset_date' => now()->toDateString()]);

        $this->assertTrue(AiRateLimit::reserve($u));
        $this->assertSame(4, (int) $u->fresh()->ai_calls_today);
    }

    public function test_reserve_returns_false_and_does_not_over_increment_at_limit(): void
    {
        $u = $this->user();
        $u->update(['ai_calls_today' => 5, 'ai_calls_reset_date' => now()->toDateString()]);

        $this->assertFalse(AiRateLimit::reserve($u));
        $this->assertSame(5, (int) $u->fresh()->ai_calls_today); // must not exceed 5
    }

    public function test_reserve_resets_counter_on_new_day_then_increments(): void
    {
        $u = $this->user();
        // User exhausted quota yesterday
        $u->update(['ai_calls_today' => 5, 'ai_calls_reset_date' => now()->subDay()->toDateString()]);

        $this->assertTrue(AiRateLimit::reserve($u));
        $this->assertSame(1, (int) $u->fresh()->ai_calls_today); // reset → 0, then +1
    }

    // ── Middleware: slot released on non-200 response ────────────────────

    public function test_quota_slot_released_when_generate_endpoint_conflicts(): void
    {
        Queue::fake();
        $u = $this->user();
        $p = $this->project($u);
        $p->update(['ai_status' => 'generating']); // forces a 409 from AiController::generate

        $this->actingAs($u)
            ->postJson('/ai/generar', ['prompt' => 'un blog', 'project_id' => $p->id])
            ->assertStatus(409);

        // Slot was pre-reserved then released on the 409 — net counter must be 0
        $this->assertSame(0, (int) $u->fresh()->ai_calls_today);
    }

    // ── Wizard: aviso de cuota agotada ────────────────────────────────────

    public function test_creation_wizard_exposes_ai_usage_so_client_sees_quota_before_submitting(): void
    {
        $u = $this->user();
        $u->update(['ai_calls_today' => 5, 'ai_calls_reset_date' => now()->toDateString()]); // límite trial = 5

        $usage = $this->actingAs($u)
            ->get('/crear')
            ->assertOk()
            ->inertiaProps('aiUsage');

        $this->assertSame(5, $usage['used']);
        $this->assertSame(5, $usage['limit']);
        $this->assertTrue($usage['used'] >= $usage['limit']);
    }

    public function test_creation_wizard_ai_call_returns_upgrade_hint_when_quota_exhausted(): void
    {
        $u = $this->user();
        $u->update(['ai_calls_today' => 5, 'ai_calls_reset_date' => now()->toDateString()]);

        $response = $this->actingAs($u)->postJson('/crear-con-ia', [
            'business_name' => 'Panadería La Espiga',
            'description'   => 'Panadería artesana de barrio',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('limit', 5)
            ->assertJsonPath('upgrade', true);
    }
}
