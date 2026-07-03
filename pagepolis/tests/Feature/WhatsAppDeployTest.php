<?php

namespace Tests\Feature;

use App\Jobs\DeploySite;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use App\Services\AnthropicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Verifica que un cambio vía WhatsApp re-despliega la web en internet cuando
 * el proyecto tiene un dominio activo, igual que lo hacen el editor y la IA.
 */
class WhatsAppDeployTest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function webhookPayload(string $from, string $text): array
    {
        return [
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [['wa_id' => $from, 'profile' => ['name' => 'Test']]],
                        'messages' => [[
                            'id'   => 'wamid.test123',
                            'from' => $from,
                            'type' => 'text',
                            'text' => ['body' => $text],
                        ]],
                    ],
                ]],
            ]],
        ];
    }

    private function userWithProject(): array
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'whatsapp_phone'    => '34600000001',
        ]);
        $project = Project::create([
            'user_id' => $user->id,
            'name'    => 'Mi Web',
            'html'    => '<h1>Hola</h1>',
            'css'     => 'h1 { color: red; }',
            'js'      => '',
            'status'  => 'published',
        ]);
        $user->update(['whatsapp_active_project' => $project->id]);

        return [$user, $project];
    }

    private function mockAi(): void
    {
        $mock = $this->mock(AnthropicService::class);
        $mock->allows('forUser')->andReturnSelf();
        $mock->allows('updateWebsite')->andReturn([
            'html'        => '<h1>Nuevo</h1>',
            'css'         => 'h1 { color: blue; }',
            'js'          => '',
            'description' => 'Cambio aplicado.',
        ]);
    }

    // ── Tests ─────────────────────────────────────────────────────────────────

    public function test_whatsapp_edit_deploys_to_active_custom_domain(): void
    {
        Queue::fake();
        Http::fake();
        $this->mockAi();

        [$user, $project] = $this->userWithProject();
        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'minegocio.com',
            'type'       => 'custom',
            'status'     => 'active',
        ]);

        $this->postJson('/webhook/whatsapp', $this->webhookPayload('34600000001', 'pon el fondo azul'))
            ->assertOk();

        Queue::assertPushed(DeploySite::class);
    }

    public function test_whatsapp_edit_deploys_to_active_subdomain(): void
    {
        Queue::fake();
        Http::fake();
        $this->mockAi();

        [$user, $project] = $this->userWithProject();
        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'minegocio.pagepolis.com',
            'type'       => 'subdomain',
            'status'     => 'active',
        ]);

        $this->postJson('/webhook/whatsapp', $this->webhookPayload('34600000001', 'pon el fondo azul'))
            ->assertOk();

        Queue::assertPushed(DeploySite::class);
    }

    public function test_whatsapp_edit_without_domain_does_not_deploy(): void
    {
        Queue::fake();
        Http::fake();
        $this->mockAi();

        $this->userWithProject();

        $this->postJson('/webhook/whatsapp', $this->webhookPayload('34600000001', 'pon el fondo azul'))
            ->assertOk();

        Queue::assertNotPushed(DeploySite::class);
    }

    public function test_whatsapp_edit_with_path_domain_does_not_deploy(): void
    {
        Queue::fake();
        Http::fake();
        $this->mockAi();

        [$user, $project] = $this->userWithProject();
        Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'test-slug',
            'type'       => 'path',
            'status'     => 'active',
        ]);

        $this->postJson('/webhook/whatsapp', $this->webhookPayload('34600000001', 'pon el fondo azul'))
            ->assertOk();

        Queue::assertNotPushed(DeploySite::class);
    }

    public function test_whatsapp_edit_saves_content_to_database(): void
    {
        Queue::fake();
        Http::fake();
        $this->mockAi();

        [$user, $project] = $this->userWithProject();

        $this->postJson('/webhook/whatsapp', $this->webhookPayload('34600000001', 'pon el fondo azul'))
            ->assertOk();

        $this->assertDatabaseHas('projects', [
            'id'  => $project->id,
            'html' => '<h1>Nuevo</h1>',
        ]);
    }

    public function test_whatsapp_edit_increments_ai_quota(): void
    {
        Queue::fake();
        Http::fake();
        $this->mockAi();

        [$user] = $this->userWithProject();

        $this->postJson('/webhook/whatsapp', $this->webhookPayload('34600000001', 'pon el fondo azul'))
            ->assertOk();

        $this->assertSame(1, (int) $user->fresh()->ai_calls_today);
    }
}
