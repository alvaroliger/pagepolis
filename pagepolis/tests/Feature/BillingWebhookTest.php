<?php

namespace Tests\Feature;

use App\Listeners\HandleStripeWebhook;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Events\WebhookReceived;
use Tests\TestCase;

class BillingWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_webhook_endpoint_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/stripe/webhook', ['type' => 'ping'], [
            'Stripe-Signature' => 'invalid',
        ]);

        // El endpoint existe y Cashier rechaza la firma inválida.
        // Cashier puede devolver un Symfony Response directo (sin envolver en TestResponse),
        // por lo que usamos getStatusCode() en lugar de status().
        $this->assertContains($response->getStatusCode(), [400, 403]);
    }

    public function test_grace_period_set_on_subscription_deleted(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test123']);

        (new HandleStripeWebhook())->handle(new WebhookReceived([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['customer' => 'cus_test123']],
        ]));

        $this->assertNotNull($user->fresh()->grace_period_ends_at);
    }

    public function test_reactivates_on_payment_succeeded(): void
    {
        $user = User::factory()->create([
            'stripe_id'            => 'cus_test123',
            'grace_period_ends_at' => now()->addDays(10),
        ]);

        (new HandleStripeWebhook())->handle(new WebhookReceived([
            'type' => 'invoice.payment_succeeded',
            'data' => ['object' => ['customer' => 'cus_test123']],
        ]));

        $this->assertNull($user->fresh()->grace_period_ends_at);
    }
}
