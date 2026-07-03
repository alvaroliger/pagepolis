<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function portal(): RedirectResponse
    {
        return auth()->user()->redirectToBillingPortal(route('dashboard'));
    }

    // El webhook de Stripe lo gestiona Cashier en POST /stripe/webhook.
    // La lógica de negocio (gracia, reactivación, dominios) vive en
    // App\Listeners\HandleStripeWebhook (evento WebhookReceived de Cashier).
}
