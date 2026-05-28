<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BillingController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        $request->validate(['plan' => 'required|in:monthly,yearly']);

        $priceId = $request->plan === 'yearly'
            ? config('services.stripe.price_yearly')
            : config('services.stripe.price_monthly');

        try {
            $checkout = auth()->user()
                ->newSubscription('default', $priceId)
                ->trialDays(7)
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => route('publish.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url'  => route('publish.index'),
                ]);

            return response()->json(['url' => $checkout->url]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function portal(): RedirectResponse
    {
        return auth()->user()->redirectToBillingPortal(route('dashboard'));
    }

    public function webhook(Request $request): Response
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response('Invalid signature', 400);
        }

        match ($event->type) {
            'customer.subscription.deleted' => $this->handleCancellation($event),
            'invoice.payment_failed'        => $this->handleCancellation($event),
            'invoice.payment_succeeded'     => $this->handlePaymentSucceeded($event),
            'checkout.session.completed'    => $this->handleCheckoutComplete($event),
            default                         => null,
        };

        return response('OK', 200);
    }

    private function handleCancellation(object $event): void
    {
        $user = User::where('stripe_id', $event->data->object->customer)->first();
        if (!$user) return;

        $user->update(['grace_period_ends_at' => now()->addDays(30)]);
        $user->domains()->update([
            'status'               => 'suspended',
            'suspended_at'         => now(),
            'grace_period_ends_at' => now()->addDays(30),
        ]);
    }

    private function handlePaymentSucceeded(object $event): void
    {
        $user = User::where('stripe_id', $event->data->object->customer)->first();
        if (!$user) return;

        $user->update(['grace_period_ends_at' => null]);
        $user->domains()->where('status', 'suspended')->update([
            'status'               => 'active',
            'suspended_at'         => null,
            'grace_period_ends_at' => null,
        ]);
    }

    private function handleCheckoutComplete(object $event): void
    {
        $session = $event->data->object;
        $user    = User::where('stripe_id', $session->customer)->first();
        if (!$user) return;

        $domain = $user->domains()->where('status', 'pending')->first();
        if ($domain) {
            \App\Jobs\PurchaseDomain::dispatch($domain);
        }
    }
}
