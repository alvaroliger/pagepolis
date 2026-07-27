<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingCheckoutTest extends TestCase
{
    use RefreshDatabase;

    // ── Auth guard ──────────────────────────────────────────────────────────

    public function test_guest_cannot_reach_checkout(): void
    {
        $response = $this->postJson('/facturacion/checkout', ['plan' => 'monthly']);

        $response->assertUnauthorized();
    }

    public function test_guest_is_redirected_from_billing_portal(): void
    {
        $response = $this->get('/facturacion/portal');

        $response->assertRedirect('/login');
    }

    public function test_billing_portal_redirects_to_plans_when_user_has_no_subscription(): void
    {
        // El botón "Suscripción" (menú y dashboard) lo ve también quien está en el
        // plan gratis: sin cliente en Stripe el portal reventaba con un 500.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/facturacion/portal');

        $response->assertRedirect(route('publish.index'));
        $this->assertNull($user->fresh()->stripe_id);
    }

    // ── Input validation ────────────────────────────────────────────────────

    public function test_checkout_requires_plan_parameter(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/facturacion/checkout', []);

        $response->assertUnprocessable()->assertJsonValidationErrors(['plan']);
    }

    public function test_checkout_rejects_unknown_plan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/facturacion/checkout', ['plan' => 'enterprise']);

        $response->assertUnprocessable()->assertJsonValidationErrors(['plan']);
    }

    // ── Error handling ──────────────────────────────────────────────────────

    public function test_checkout_returns_error_json_when_stripe_is_not_configured(): void
    {
        // In the test environment Stripe is unconfigured, so the SDK throws an
        // exception. BillingController catches it and returns a JSON error body
        // rather than crashing — this test verifies that contract.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/facturacion/checkout', ['plan' => 'monthly']);

        $response->assertStatus(500)->assertJsonStructure(['error']);
    }
}
