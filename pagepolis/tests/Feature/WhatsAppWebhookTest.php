<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\AnthropicService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * CAJA NEGRA — endpoints del webhook WhatsApp Business: verificación GET,
 * validación de firma HMAC, comandos de texto y aplicación de cambios con IA.
 */
class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.whatsapp.verify_token' => 'verify-tok',
            'services.whatsapp.app_secret'   => null,      // sin secreto → bypass firma (modo test)
            'services.whatsapp.token'        => 'wa-token',
            'services.whatsapp.phone_id'     => 'ph123',
        ]);
        Http::fake(['https://graph.facebook.com/*' => Http::response([], 200)]);
    }

    private function user(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['email_verified_at' => now()], $attrs));
    }

    private function project(User $user, array $attrs = []): Project
    {
        return Project::create(array_merge([
            'user_id' => $user->id,
            'name'    => 'Mi web',
            'html'    => '<h1>Hola</h1>',
            'css'     => '',
            'js'      => '',
            'status'  => 'draft',
        ], $attrs));
    }

    /** Construye el payload de un mensaje de texto tal como lo envía Meta. */
    private function textPayload(string $from, string $text, string $msgId = 'wamid.test'): array
    {
        return ['entry' => [['changes' => [['field' => 'messages', 'value' => [
            'contacts' => [['wa_id' => $from]],
            'messages' => [[
                'id' => $msgId, 'from' => $from, 'type' => 'text',
                'text' => ['body' => $text],
            ]],
        ]]]]]]];
    }

    /** Payload de un mensaje NO textual (imagen, audio, etc.). */
    private function nonTextPayload(string $from, string $type = 'image'): array
    {
        return ['entry' => [['changes' => [['field' => 'messages', 'value' => [
            'contacts' => [['wa_id' => $from]],
            'messages' => [['id' => 'wamid.img', 'from' => $from, 'type' => $type]],
        ]]]]]]];
    }

    /**
     * Extrae el cuerpo del mensaje de respuesta enviado al usuario vía WhatsApp.
     * Lo diferencia del markRead (que lleva 'status' en lugar de 'type').
     */
    private function sentMessage(): ?string
    {
        foreach (Http::recorded() as [$req]) {
            $data = json_decode($req->body(), true);
            if (($data['type'] ?? '') === 'text') {
                return $data['text']['body'] ?? null;
            }
        }
        return null;
    }

    // ── Verificación GET (handshake Meta) ────────────────────────────────

    public function test_verify_returns_challenge_with_correct_token(): void
    {
        $this->get('/webhook/whatsapp?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'verify-tok',
            'hub_challenge'    => 'abc123',
        ]))->assertOk()->assertSee('abc123');
    }

    public function test_verify_returns_forbidden_with_wrong_token(): void
    {
        $this->get('/webhook/whatsapp?' . http_build_query([
            'hub_mode'         => 'subscribe',
            'hub_verify_token' => 'bad-token',
            'hub_challenge'    => 'abc123',
        ]))->assertForbidden();
    }

    // ── Validación de firma HMAC ──────────────────────────────────────────

    public function test_webhook_rejects_invalid_signature_when_secret_is_set(): void
    {
        config(['services.whatsapp.app_secret' => 'my-secret']);

        $this->postJson('/webhook/whatsapp', $this->textPayload('34600000001', 'hola'), [
            'X-Hub-Signature-256' => 'sha256=badhash',
        ])->assertUnauthorized();
    }

    public function test_webhook_accepts_valid_hmac_signature(): void
    {
        $secret  = 'my-secret';
        config(['services.whatsapp.app_secret' => $secret]);
        $payload = $this->textPayload('34600000099', 'ayuda');
        $body    = json_encode($payload);
        $sig     = 'sha256=' . hash_hmac('sha256', $body, $secret);

        $this->postJson('/webhook/whatsapp', $payload, ['X-Hub-Signature-256' => $sig])
            ->assertOk();
    }

    // ── Número desconocido ───────────────────────────────────────────────

    public function test_unknown_phone_gets_registration_message(): void
    {
        $this->postJson('/webhook/whatsapp', $this->textPayload('34699999999', 'hola'))
            ->assertOk();

        $this->assertStringContainsString('vincular', $this->sentMessage());
    }

    // ── Comandos de ayuda ────────────────────────────────────────────────

    public function test_help_command_returns_help_message(): void
    {
        $user = $this->user(['whatsapp_phone' => '34600111111']);

        $this->postJson('/webhook/whatsapp', $this->textPayload('34600111111', 'ayuda'))
            ->assertOk();

        $this->assertStringContainsString('asistente de Pagepolis', $this->sentMessage());
    }

    // ── Listado de proyectos ─────────────────────────────────────────────

    public function test_list_projects_shows_user_projects(): void
    {
        $user = $this->user(['whatsapp_phone' => '34600222222']);
        $this->project($user, ['name' => 'Panadería Espiga', 'status' => 'published']);

        $this->postJson('/webhook/whatsapp', $this->textPayload('34600222222', 'mis webs'))
            ->assertOk();

        $this->assertStringContainsString('Panadería Espiga', $this->sentMessage());
    }

    public function test_list_projects_when_none_exist(): void
    {
        $user = $this->user(['whatsapp_phone' => '34600333333']);

        $this->postJson('/webhook/whatsapp', $this->textPayload('34600333333', 'proyectos'))
            ->assertOk();

        $this->assertStringContainsString('No tienes ninguna', $this->sentMessage());
    }

    // ── Selección de proyecto ────────────────────────────────────────────

    public function test_web_command_sets_active_project(): void
    {
        $user    = $this->user(['whatsapp_phone' => '34600444444']);
        $project = $this->project($user);

        $this->postJson('/webhook/whatsapp', $this->textPayload('34600444444', 'web 1'))
            ->assertOk();

        $this->assertSame($project->id, $user->fresh()->whatsapp_active_project);
    }

    public function test_web_command_with_invalid_number_returns_error(): void
    {
        $user = $this->user(['whatsapp_phone' => '34600555555']);

        $this->postJson('/webhook/whatsapp', $this->textPayload('34600555555', 'web 99'))
            ->assertOk();

        $this->assertStringContainsString('No encontré', $this->sentMessage());
    }

    // ── Aplicar cambio con IA ────────────────────────────────────────────

    public function test_applying_change_updates_project_and_increments_counter(): void
    {
        $user    = $this->user(['whatsapp_phone' => '34600666666']);
        $project = $this->project($user);
        $user->update(['whatsapp_active_project' => $project->id]);

        $this->mock(AnthropicService::class, function ($mock) {
            $mock->shouldReceive('forUser')->andReturnSelf();
            $mock->shouldReceive('updateWebsite')->andReturn([
                'html' => '<h1>Nuevo</h1>', 'css' => 'h1{}', 'js' => '',
                'description' => 'He cambiado el título.',
            ]);
        });

        $this->postJson('/webhook/whatsapp', $this->textPayload('34600666666', 'cambia el título'))
            ->assertOk();

        $this->assertSame(1, $user->fresh()->ai_calls_today);
        $this->assertSame('<h1>Nuevo</h1>', $project->fresh()->html);
    }

    public function test_remaining_count_is_accurate_after_change(): void
    {
        // Free user: limit = 5; 2 calls used → this is call #3 → 2 remain.
        // Before the fix, the off-by-1 bug reported only 1 remaining.
        $user    = $this->user(['whatsapp_phone' => '34600747474']);
        $project = $this->project($user);
        $user->update([
            'whatsapp_active_project' => $project->id,
            'ai_calls_today'          => 2,
            'ai_calls_reset_date'     => now()->toDateString(),
        ]);

        $this->mock(AnthropicService::class, function ($mock) {
            $mock->shouldReceive('forUser')->andReturnSelf();
            $mock->shouldReceive('updateWebsite')->andReturn([
                'html' => '', 'css' => '', 'js' => '', 'description' => 'OK',
            ]);
        });

        $this->postJson('/webhook/whatsapp', $this->textPayload('34600747474', 'cambia algo'))
            ->assertOk();

        $msg = $this->sentMessage();
        $this->assertNotNull($msg);
        $this->assertStringContainsString('quedan 2', $msg);
    }

    // ── Límite diario ────────────────────────────────────────────────────

    public function test_daily_limit_blocks_ai_calls_and_does_not_increment_counter(): void
    {
        $user    = $this->user(['whatsapp_phone' => '34600888888']);
        $project = $this->project($user);
        $user->update([
            'whatsapp_active_project' => $project->id,
            'ai_calls_today'          => 5,
            'ai_calls_reset_date'     => now()->toDateString(),
        ]);

        $this->postJson('/webhook/whatsapp', $this->textPayload('34600888888', 'cambia algo'))
            ->assertOk();

        $this->assertStringContainsString('límite', $this->sentMessage());
        $this->assertSame(5, $user->fresh()->ai_calls_today);
    }

    // ── Mensajes no textuales ────────────────────────────────────────────

    public function test_non_text_message_returns_help(): void
    {
        $user = $this->user(['whatsapp_phone' => '34600999999']);

        $this->postJson('/webhook/whatsapp', $this->nonTextPayload('34600999999'))
            ->assertOk();

        $this->assertStringContainsString('asistente', $this->sentMessage());
    }

    // ── Sin proyecto activo ──────────────────────────────────────────────

    public function test_change_without_active_project_prompts_selection(): void
    {
        $user = $this->user(['whatsapp_phone' => '34601000000']);

        $this->postJson('/webhook/whatsapp', $this->textPayload('34601000000', 'cambia el título'))
            ->assertOk();

        $this->assertStringContainsString('selecciona', $this->sentMessage());
    }
}
