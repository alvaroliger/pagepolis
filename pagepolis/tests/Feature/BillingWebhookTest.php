<?php

namespace Tests\Feature;

use App\Jobs\PurchaseDomain;
use App\Listeners\HandleStripeWebhook;
use App\Models\Domain;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Cashier\Events\WebhookReceived;
use Tests\TestCase;

class BillingWebhookTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function fire(string $type, string $customerId = 'cus_test123'): void
    {
        (new HandleStripeWebhook())->handle(new WebhookReceived([
            'type' => $type,
            'data' => ['object' => ['customer' => $customerId]],
        ]));
    }

    private function userWithStripe(array $extra = []): User
    {
        return User::factory()->create(array_merge(['stripe_id' => 'cus_test123'], $extra));
    }

    private function pendingDomainFor(User $user): Domain
    {
        $project = Project::create([
            'user_id' => $user->id,
            'name'    => 'Test Site',
            'status'  => 'draft',
            'html'    => '<h1>Test</h1>',
            'css'     => '',
            'js'      => '',
        ]);

        return Domain::create([
            'user_id'    => $user->id,
            'project_id' => $project->id,
            'domain'     => 'miempresa.com',
            'type'       => 'custom',
            'status'     => 'pending',
        ]);
    }

    // ---------------------------------------------------------------------------
    // HTTP endpoint
    // ---------------------------------------------------------------------------

    public function test_cashier_webhook_endpoint_rejects_invalid_signature(): void
    {
        // Set a secret so VerifyWebhookSignature middleware is applied.
        config(['cashier.webhook.secret' => 'whsec_test_secret_for_testing']);

        $response = $this->postJson('/stripe/webhook', ['type' => 'ping'], [
            'Stripe-Signature' => 'invalid',
        ]);

        $this->assertContains($response->getStatusCode(), [400, 403]);
    }

    // ---------------------------------------------------------------------------
    // customer.subscription.deleted / invoice.payment_failed → grace period
    // ---------------------------------------------------------------------------

    public function test_grace_period_set_on_subscription_deleted(): void
    {
        $user = User::factory()->create(['stripe_id' => 'cus_test123']);

        (new HandleStripeWebhook())->handle(new WebhookReceived([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['customer' => 'cus_test123']],
        ]));

        $this->assertNotNull($user->fresh()->grace_period_ends_at);
    }

    public function test_invoice_payment_failed_starts_grace_period(): void
    {
        $user = $this->userWithStripe();

        $this->fire('invoice.payment_failed');

        $this->assertNotNull($user->fresh()->grace_period_ends_at);
    }

    public function test_grace_period_also_suspends_user_domains(): void
    {
        $user   = $this->userWithStripe();
        $domain = $this->pendingDomainFor($user);
        $domain->update(['status' => 'active']);

        $this->fire('customer.subscription.deleted');

        $this->assertEquals('suspended', $domain->fresh()->status);
        $this->assertNotNull($domain->fresh()->suspended_at);
        $this->assertNotNull($domain->fresh()->grace_period_ends_at);
    }

    // ---------------------------------------------------------------------------
    // invoice.payment_succeeded → reactivate
    // ---------------------------------------------------------------------------

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

    public function test_reactivation_restores_only_suspended_domains(): void
    {
        $user = $this->userWithStripe(['grace_period_ends_at' => now()->addDays(5)]);

        $project1  = Project::create([
            'user_id' => $user->id, 'name' => 'A', 'status' => 'draft',
            'html' => '<h1>A</h1>', 'css' => '', 'js' => '',
        ]);
        $suspended = Domain::create([
            'user_id' => $user->id, 'project_id' => $project1->id,
            'domain'  => 'suspended-site.com', 'type' => 'custom',
            'status'  => 'suspended', 'suspended_at' => now(),
        ]);

        $project2 = Project::create([
            'user_id' => $user->id, 'name' => 'B', 'status' => 'draft',
            'html' => '<h1>B</h1>', 'css' => '', 'js' => '',
        ]);
        $active   = Domain::create([
            'user_id' => $user->id, 'project_id' => $project2->id,
            'domain'  => 'active-site.com', 'type' => 'custom',
            'status'  => 'active',
        ]);

        $this->fire('invoice.payment_succeeded');

        $this->assertEquals('active', $suspended->fresh()->status);
        $this->assertNull($suspended->fresh()->suspended_at);
        $this->assertEquals('active', $active->fresh()->status);
    }

    // ---------------------------------------------------------------------------
    // checkout.session.completed → provision pending domain
    // ---------------------------------------------------------------------------

    public function test_checkout_completed_dispatches_purchase_domain_for_pending_domain(): void
    {
        Queue::fake();
        $user = $this->userWithStripe();
        $this->pendingDomainFor($user);

        $this->fire('checkout.session.completed');

        Queue::assertPushed(PurchaseDomain::class);
    }

    public function test_checkout_completed_skips_dispatch_when_no_pending_domain(): void
    {
        Queue::fake();
        $user = $this->userWithStripe();

        $this->fire('checkout.session.completed');

        Queue::assertNothingPushed();
    }

    // ---------------------------------------------------------------------------
    // Edge cases
    // ---------------------------------------------------------------------------

    public function test_unknown_customer_is_silently_ignored(): void
    {
        $this->userWithStripe();

        // No exception: unknown customer returns early with no side effects.
        (new HandleStripeWebhook())->handle(new WebhookReceived([
            'type' => 'customer.subscription.deleted',
            'data' => ['object' => ['customer' => 'cus_unknown_xyz']],
        ]));

        $this->assertTrue(true);
    }
}
