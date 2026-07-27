<?php

namespace Tests\Feature;

use App\Jobs\GenerateWebsiteJob;
use App\Models\Project;
use App\Models\User;
use App\Services\AnthropicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Deshacer el último cambio de la IA": un cliente que no le gusta el
 * resultado de una edición debe poder volver atrás sin perder el resto de
 * su web. Cubre el snapshot (tanto en el camino local instantáneo como en
 * el job de IA en segundo plano) y el endpoint que lo restaura.
 */
class AiUndoTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['email_verified_at' => now()]);
    }

    private function templateProject(User $u): Project
    {
        return Project::create([
            'user_id' => $u->id, 'name' => 'Web', 'status' => 'draft',
            'html' => '<header><a class="logo">X</a></header><main></main>',
            'css'  => "/* Base compartida de las plantillas */\n:root{--bg:#fff;--brand:#7c3aed;--text:#111;--radius:14px}",
            'js'   => "const WHATSAPP_NUMERO='34600000000';",
        ]);
    }

    public function test_local_instant_edit_creates_undo_snapshot(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);

        $this->actingAs($u)
            ->postJson('/ai/actualizar', ['instruction' => 'pon el fondo azul', 'project_id' => $p->id])
            ->assertOk();

        $p->refresh();
        $this->assertTrue($p->canUndoAiChange());
        $this->assertSame('<header><a class="logo">X</a></header><main></main>', $p->previous_html);
    }

    public function test_a_no_op_local_reply_does_not_touch_existing_snapshot(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);
        $p->update(['previous_html' => 'antiguo', 'previous_css' => 'antiguo', 'previous_js' => 'antiguo']);

        $this->actingAs($u)
            ->postJson('/ai/actualizar', ['instruction' => 'hola, ¿qué puedes hacer?', 'project_id' => $p->id])
            ->assertOk();

        $this->assertSame('antiguo', $p->fresh()->previous_html);
    }

    public function test_undo_restores_previous_content_and_is_single_level(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);
        $p->update([
            'html' => '<p>nuevo</p>', 'css' => 'nuevo{}', 'js' => 'nuevo();',
            'previous_html' => '<p>viejo</p>', 'previous_css' => 'viejo{}', 'previous_js' => 'viejo();',
        ]);

        $this->actingAs($u)
            ->postJson('/ai/deshacer', ['project_id' => $p->id])
            ->assertOk()
            ->assertJson(['success' => true, 'html' => '<p>viejo</p>', 'css' => 'viejo{}', 'js' => 'viejo();']);

        $p->refresh();
        $this->assertSame('<p>viejo</p>', $p->html);
        $this->assertFalse($p->canUndoAiChange());
    }

    public function test_editor_page_exposes_can_undo_flag(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);

        $this->actingAs($u)->get("/editor/{$p->id}")
            ->assertInertia(fn ($page) => $page->where('project.can_undo', false));

        $p->update(['previous_html' => 'x', 'previous_css' => 'x', 'previous_js' => 'x']);

        $this->actingAs($u)->get("/editor/{$p->id}")
            ->assertInertia(fn ($page) => $page->where('project.can_undo', true));
    }

    public function test_undo_fails_when_nothing_to_undo(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);

        $this->actingAs($u)
            ->postJson('/ai/deshacer', ['project_id' => $p->id])
            ->assertStatus(422);
    }

    public function test_cannot_undo_another_users_project(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $p = $this->templateProject($owner);
        $p->update(['previous_html' => 'x', 'previous_css' => 'x', 'previous_js' => 'x']);

        $this->actingAs($other)
            ->postJson('/ai/deshacer', ['project_id' => $p->id])
            ->assertStatus(404);
    }

    public function test_unauthenticated_cannot_undo(): void
    {
        $this->postJson('/ai/deshacer', ['project_id' => 1])->assertStatus(401);
    }

    public function test_undo_blocked_while_generating(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);
        $p->update(['previous_html' => 'x', 'previous_css' => 'x', 'previous_js' => 'x', 'ai_status' => 'generating']);

        $this->actingAs($u)
            ->postJson('/ai/deshacer', ['project_id' => $p->id])
            ->assertStatus(409);
    }

    public function test_ai_job_update_creates_undo_snapshot_but_full_generate_clears_it(): void
    {
        $u = $this->user();
        $p = $this->templateProject($u);
        $p->update(['previous_html' => 'huella-vieja', 'previous_css' => 'huella-vieja', 'previous_js' => 'huella-vieja']);

        $this->mock(AnthropicService::class, function ($mock) {
            $mock->shouldReceive('forUser')->andReturnSelf();
            $mock->shouldReceive('updateWebsite')->andReturn([
                'html' => '<p>actualizado por IA</p>', 'css' => '', 'js' => '', 'description' => 'Listo', 'changed' => ['color'],
            ]);
        });

        $updateJob = new GenerateWebsiteJob($p->id, $u->id, 'update', 'pon el fondo verde');
        app()->call([$updateJob, 'handle']);

        $p->refresh();
        $this->assertSame('<p>actualizado por IA</p>', $p->html);
        $this->assertTrue($p->canUndoAiChange());
        $this->assertSame('<header><a class="logo">X</a></header><main></main>', $p->previous_html);

        $this->mock(AnthropicService::class, function ($mock) {
            $mock->shouldReceive('forUser')->andReturnSelf();
            $mock->shouldReceive('generateWebsite')->andReturn([
                'html' => '<p>regenerado</p>', 'css' => '', 'js' => '', 'description' => 'Listo',
            ]);
        });

        $generateJob = new GenerateWebsiteJob($p->id, $u->id, 'generate', 'hazme una web nueva');
        app()->call([$generateJob, 'handle']);

        $p->refresh();
        $this->assertSame('<p>regenerado</p>', $p->html);
        $this->assertFalse($p->canUndoAiChange());
    }
}
