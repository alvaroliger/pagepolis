<?php

namespace Tests\Feature;

use App\Jobs\GenerateWebsiteJob;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * CAJA NEGRA — comportamiento observable de los endpoints de IA desde fuera, sin
 * conocer la implementación: auth, autorización, validación, cuota (IA vs local),
 * concurrencia (409), estado y recuperación.
 */
class AiBlackBoxTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $a = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $a));
    }

    /** Proyecto basado en plantilla (motor base) -> editable en local. */
    private function templateProject(User $u): Project
    {
        return Project::create([
            'user_id' => $u->id, 'name' => 'Web', 'status' => 'draft',
            'html' => '<header><a class="logo">X</a></header><main></main>',
            'css'  => "/* Base compartida de las plantillas */\n:root{--bg:#fff;--brand:#7c3aed;--text:#111;--radius:14px}",
            'js'   => "const WHATSAPP_NUMERO='34600000000';",
        ]);
    }

    private function overQuota(User $u): void
    {
        $u->update(['ai_calls_today' => 5, 'ai_calls_reset_date' => now()->toDateString()]);
    }

    // ── Auth / autorización ──────────────────────────────────────────────
    public function test_unauthenticated_is_blocked(): void
    {
        $this->postJson('/ai/actualizar', ['instruction' => 'x', 'project_id' => 1])->assertStatus(401);
    }

    public function test_cannot_edit_another_users_project(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $project = $this->templateProject($owner);

        $this->actingAs($other)
            ->postJson('/ai/actualizar', ['instruction' => 'pon el fondo azul', 'project_id' => $project->id])
            ->assertStatus(404);
    }

    public function test_status_forbidden_for_other_user(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $project = $this->templateProject($owner);

        $this->actingAs($other)->getJson("/ai/estado/{$project->id}")->assertStatus(403);
    }

    // ── Validación ───────────────────────────────────────────────────────
    public function test_update_requires_instruction(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);
        $this->actingAs($u)->postJson('/ai/actualizar', ['project_id' => $p->id])->assertStatus(422);
    }

    public function test_wizard_requires_business_name(): void
    {
        $u = $this->user();
        $this->actingAs($u)->postJson('/crear-con-ia', ['description' => 'algo'])->assertStatus(422);
    }

    // ── Cuota: local es GRATIS, IA cuenta ────────────────────────────────
    public function test_local_edit_is_free_even_over_quota(): void
    {
        Queue::fake();
        $u = $this->user();
        $this->overQuota($u);
        $p = $this->templateProject($u);

        $this->actingAs($u)
            ->postJson('/ai/actualizar', ['instruction' => 'pon el fondo azul', 'project_id' => $p->id])
            ->assertOk()->assertJson(['status' => 'done']);

        Queue::assertNothingPushed();
        $this->assertSame(5, (int) $u->fresh()->ai_calls_today); // sigue igual: fue gratis
    }

    public function test_help_message_is_free_local(): void
    {
        Queue::fake();
        $u = $this->user();
        $p = $this->templateProject($u);

        // Un saludo / petición a medias se responde GRATIS (sin IA, sin cuota).
        $res = $this->actingAs($u)
            ->postJson('/ai/actualizar', ['instruction' => 'hola, ¿qué puedes hacer?', 'project_id' => $p->id]);

        $res->assertOk()->assertJson(['status' => 'done']);
        $this->assertSame([], $res->json('changed'));
        Queue::assertNothingPushed();
        $this->assertSame(0, (int) $u->fresh()->ai_calls_today);
    }

    public function test_incomplete_request_asks_back_for_free(): void
    {
        Queue::fake();
        $u = $this->user();
        $p = $this->templateProject($u);

        $res = $this->actingAs($u)
            ->postJson('/ai/actualizar', ['instruction' => 'cambia el fondo', 'project_id' => $p->id]);

        $res->assertOk()->assertJson(['status' => 'done']);
        $this->assertStringContainsStringIgnoringCase('color', $res->json('description'));
        Queue::assertNothingPushed();
    }

    public function test_ai_edit_blocked_when_over_quota(): void
    {
        Queue::fake();
        $u = $this->user();
        $this->overQuota($u);
        $p = $this->templateProject($u);

        // Instrucción NO programada -> requiere IA -> bloqueada por cuota.
        $this->actingAs($u)
            ->postJson('/ai/actualizar', ['instruction' => 'añádeme un blog con tres artículos', 'project_id' => $p->id])
            ->assertStatus(429);

        Queue::assertNothingPushed();
    }

    public function test_ai_edit_counts_quota_and_queues(): void
    {
        Queue::fake();
        $u = $this->user();
        $p = $this->templateProject($u);

        $this->actingAs($u)
            ->postJson('/ai/actualizar', ['instruction' => 'añádeme un mapa interactivo', 'project_id' => $p->id])
            ->assertOk()->assertJson(['status' => 'generating']);

        Queue::assertPushed(GenerateWebsiteJob::class);
        $this->assertSame(1, (int) $u->fresh()->ai_calls_today);
    }

    public function test_generate_blocked_when_over_quota(): void
    {
        Queue::fake();
        $u = $this->user();
        $this->overQuota($u);
        $p = $this->templateProject($u);

        $this->actingAs($u)
            ->postJson('/ai/generar', ['prompt' => 'una web nueva', 'project_id' => $p->id])
            ->assertStatus(429);
    }

    // ── Concurrencia ─────────────────────────────────────────────────────
    public function test_blocked_while_already_generating(): void
    {
        Queue::fake();
        $u = $this->user();
        $p = $this->templateProject($u);
        $p->update(['ai_status' => 'generating']);

        $this->actingAs($u)
            ->postJson('/ai/actualizar', ['instruction' => 'añádeme un blog', 'project_id' => $p->id])
            ->assertStatus(409);
    }

    // ── Estado / recuperación ────────────────────────────────────────────
    public function test_status_ready_returns_content(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);
        $this->actingAs($u)->getJson("/ai/estado/{$p->id}")
            ->assertOk()->assertJson(['status' => 'ready']);
    }

    public function test_stale_generating_recovers_as_error(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);
        $p->update(['ai_status' => 'generating']);
        // Simula un job perdido: sin actualizar desde hace 20 min.
        DB::table('projects')->where('id', $p->id)->update(['updated_at' => now()->subMinutes(20)]);

        $this->actingAs($u)->getJson("/ai/estado/{$p->id}")
            ->assertOk()->assertJson(['status' => 'error']);
    }
}
