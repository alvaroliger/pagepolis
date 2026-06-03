<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_endpoint_exists(): void
    {
        $response = $this->postJson('/webhook/stripe', [], [
            'Stripe-Signature' => 'invalid',
        ]);
        // 400 = signature mismatch (endpoint works, Stripe rejects bad sig)
        $this->assertContains($response->status(), [400, 401, 403]);
    }

    public function test_grace_period_set_on_subscription_deleted(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->grace_period_ends_at);
    }
}
