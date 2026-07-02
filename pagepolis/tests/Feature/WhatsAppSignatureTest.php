<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Verifica que el webhook de WhatsApp falla cerrado cuando la firma no puede
 * validarse: ya sea porque falta el secreto (config incompleta) o porque la
 * firma no coincide. Esto evita que cualquier cliente externo pueda disparar
 * el procesamiento de mensajes sin credenciales válidas de Meta.
 */
class WhatsAppSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['https://graph.facebook.com/*' => Http::response([], 200)]);
    }

    public function test_webhook_denied_when_app_secret_not_configured(): void
    {
        config(['services.whatsapp.app_secret' => null]);

        $this->postJson('/webhook/whatsapp', ['entry' => []])
            ->assertUnauthorized();
    }

    public function test_webhook_denied_with_invalid_signature_when_secret_is_set(): void
    {
        config(['services.whatsapp.app_secret' => 'real-secret']);

        $this->postJson('/webhook/whatsapp', ['entry' => []], [
            'X-Hub-Signature-256' => 'sha256=badhash',
        ])->assertUnauthorized();
    }

    public function test_webhook_accepted_with_valid_hmac_signature(): void
    {
        $secret  = 'test-secret';
        $payload = ['entry' => []];
        $body    = json_encode($payload);
        $sig     = 'sha256=' . hash_hmac('sha256', $body, $secret);

        config(['services.whatsapp.app_secret' => $secret]);

        $this->postJson('/webhook/whatsapp', $payload, [
            'X-Hub-Signature-256' => $sig,
        ])->assertOk();
    }
}
