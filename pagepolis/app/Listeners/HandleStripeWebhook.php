<?php

namespace App\Listeners;

use App\Jobs\PurchaseDomain;
use App\Models\User;
use Laravel\Cashier\Events\WebhookReceived;

/**
 * Lógica de negocio propia sobre los webhooks de Stripe, ejecutada en el MISMO
 * endpoint que usa Cashier (POST /stripe/webhook). Cashier mantiene sincronizada
 * la tabla de suscripciones; aquí gestionamos el periodo de gracia, la
 * reactivación y el aprovisionamiento de dominios tras el pago.
 */
class HandleStripeWebhook
{
    public function handle(WebhookReceived $event): void
    {
        $payload  = $event->payload;
        $type     = $payload['type'] ?? '';
        $object   = $payload['data']['object'] ?? [];
        $customer = $object['customer'] ?? null;

        $user = $customer ? User::where('stripe_id', $customer)->first() : null;
        if (!$user) {
            return;
        }

        match ($type) {
            'customer.subscription.deleted',
            'invoice.payment_failed'     => $this->startGracePeriod($user),
            'invoice.payment_succeeded'  => $this->reactivate($user),
            'checkout.session.completed' => $this->provisionPendingDomain($user),
            default                      => null,
        };
    }

    private function startGracePeriod(User $user): void
    {
        $user->update(['grace_period_ends_at' => now()->addDays(30)]);
        $user->domains()->update([
            'status'               => 'suspended',
            'suspended_at'         => now(),
            'grace_period_ends_at' => now()->addDays(30),
        ]);
    }

    private function reactivate(User $user): void
    {
        $user->update(['grace_period_ends_at' => null]);
        $user->domains()->where('status', 'suspended')->update([
            'status'               => 'active',
            'suspended_at'         => null,
            'grace_period_ends_at' => null,
        ]);
    }

    private function provisionPendingDomain(User $user): void
    {
        $domain = $user->domains()->where('status', 'pending')->first();
        if ($domain) {
            PurchaseDomain::dispatch($domain);
        }
    }
}
