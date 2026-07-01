<?php

namespace Tests\Feature;

use App\Jobs\GenerateWebsiteJob;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Test de humo: recorre todas las páginas y los flujos clave para asegurar que
 * NADA devuelve un error de servidor (5xx). Es la red de seguridad "100% funcional".
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $attrs));
    }

    private function project(User $user, array $attrs = []): Project
    {
        return Project::create(array_merge([
            'user_id' => $user->id,
            'name'    => 'Mi web',
            'html'    => '<h1>Hola</h1><a href="#x">x</a>',
            'css'     => 'h1{color:red}',
            'js'      => '',
            'status'  => 'draft',
        ], $attrs));
    }

    public function test_public_pages_load(): void
    {
        foreach (['/', '/terminos', '/privacidad', '/sitemap.xml', '/robots.txt',
                  '/login', '/register', '/forgot-password'] as $url) {
            $this->get($url)->assertStatus(200);
        }
    }

    public function test_authenticated_pages_load(): void
    {
        $user = $this->user();

        foreach (['/dashboard', '/plantillas', '/crear', '/analytics', '/publicar', '/perfil'] as $url) {
            $this->actingAs($user)->get($url)->assertStatus(200);
        }
    }

    public function test_editor_and_project_pages_load(): void
    {
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)->get("/editor/{$project->id}")->assertStatus(200);
        $this->actingAs($user)->get("/proyectos/{$project->id}/preview")->assertStatus(200);
        $this->actingAs($user)->get("/proyectos/{$project->id}/zip")->assertStatus(200);
        $this->actingAs($user)->get("/ai/estado/{$project->id}")->assertStatus(200)->assertJson(['status' => 'ready']);
    }

    public function test_preview_injects_guard_against_iframe_navigation(): void
    {
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)->get("/proyectos/{$project->id}/preview")
            ->assertSee('addEventListener', false);   // el guard de la preview
    }

    public function test_published_site_loads_with_badge_for_free_user(): void
    {
        $user    = $this->user();
        $project = $this->project($user, ['status' => 'published', 'slug' => 'mi-web-test']);

        $res = $this->get('/s/mi-web-test');
        $res->assertStatus(200)->assertSee('Hecho con Pagepolis');
    }

    public function test_admin_panel_loads_for_admin(): void
    {
        $admin = $this->user(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin')->assertStatus(200);
    }

    public function test_wizard_creates_project_and_queues_generation(): void
    {
        Queue::fake();
        $user = $this->user();

        $res = $this->actingAs($user)->postJson('/crear-con-ia', [
            'business_name' => 'Panadería La Espiga',
            'description'   => 'Pan artesano y bollería en Sevilla.',
            'sells'         => true,
            'whatsapp'      => '+34 600 123 456',
            'style'         => 'Moderno',
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('projects', ['name' => 'Panadería La Espiga', 'ai_status' => 'generating']);
        Queue::assertPushed(GenerateWebsiteJob::class);
    }

    public function test_generate_endpoint_queues_job(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)->postJson('/ai/generar', [
            'prompt'     => 'Una web para mi cafetería',
            'project_id' => $project->id,
        ])->assertOk()->assertJson(['status' => 'generating']);

        Queue::assertPushed(GenerateWebsiteJob::class);
    }

    public function test_update_with_image_stores_file_and_queues_job(): void
    {
        Queue::fake();
        Storage::fake('local');
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)->post('/ai/actualizar', [
            'instruction' => 'Añade la carta de esta foto',
            'project_id'  => $project->id,
            'images'      => [UploadedFile::fake()->create('carta.jpg', 200, 'image/jpeg')],
        ])->assertOk()->assertJson(['status' => 'generating']);

        Queue::assertPushed(GenerateWebsiteJob::class, function ($job) {
            return count($job->imagePaths) === 1;
        });
    }

    public function test_local_edit_is_instant_and_free(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user, [
            'css' => "/* Base compartida de las plantillas */\n:root{--bg:#ffffff;--brand:#7c3aed;--text:#111}body{color:var(--text)}",
            'js'  => "const WHATSAPP_NUMERO='34600000000';",
        ]);

        $res = $this->actingAs($user)->postJson('/ai/actualizar', [
            'instruction' => 'pon el fondo azul',
            'project_id'  => $project->id,
        ]);

        $res->assertOk()->assertJson(['status' => 'done', 'instant' => true]);
        $this->assertStringContainsString('--bg:#2563eb', $res->json('css'));
        Queue::assertNothingPushed();                         // no usó la IA
        $this->assertSame(0, (int) $user->fresh()->ai_calls_today); // no gastó cuota
    }

    public function test_generate_blocked_while_already_generating(): void
    {
        Queue::fake();
        $user    = $this->user();
        $project = $this->project($user, ['ai_status' => 'generating']);

        $this->actingAs($user)->postJson('/ai/generar', [
            'prompt'     => 'otra web',
            'project_id' => $project->id,
        ])->assertStatus(409);

        Queue::assertNothingPushed();
    }

    public function test_editor_save_persists_content(): void
    {
        $user    = $this->user();
        $project = $this->project($user);

        $this->actingAs($user)->postJson("/editor/{$project->id}/guardar", [
            'name' => 'Web actualizada',
            'html' => '<h1>Nueva</h1>',
            'css'  => 'h1{color:blue}',
            'js'   => 'console.log("ok")',
        ])->assertOk()->assertJson(['success' => true]);

        $fresh = $project->fresh();
        $this->assertSame('Web actualizada', $fresh->name);
        $this->assertSame('<h1>Nueva</h1>', $fresh->html);
        $this->assertSame('h1{color:blue}', $fresh->css);
        $this->assertSame('console.log("ok")', $fresh->js);
    }

    public function test_editor_save_requires_ownership(): void
    {
        $owner   = $this->user();
        $other   = $this->user();
        $project = $this->project($owner);

        $this->actingAs($other)->postJson("/editor/{$project->id}/guardar", [
            'html' => '<h1>Hackeado</h1>',
        ])->assertStatus(403);

        $this->assertSame('<h1>Hola</h1><a href="#x">x</a>', $project->fresh()->html);
    }
}
