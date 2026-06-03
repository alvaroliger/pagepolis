<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AiRateLimit
{
    // Límites diarios por tipo de cuenta
    private const LIMITS = [
        'trial'   => 5,    // Prueba gratis
        'basic'   => 30,   // Plan Básico pagado
        'pro'     => 100,  // Plan Pro pagado
        'admin'   => 9999,
    ];

    public function handle(Request $request, Closure $next): mixed
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'No autenticado.'], 401);
        }

        // Resetear contador si es un nuevo día
        $today = now()->toDateString();
        if ($user->ai_calls_reset_date !== $today) {
            $user->update(['ai_calls_today' => 0, 'ai_calls_reset_date' => $today]);
        }

        $tier  = $this->getUserTier($user);
        $limit = self::LIMITS[$tier];

        if ($user->ai_calls_today >= $limit) {
            $resetAt = now()->endOfDay()->diffForHumans();
            return response()->json([
                'error'    => "Has alcanzado el límite de {$limit} generaciones con IA hoy. Se restablece {$resetAt}.",
                'limit'    => $limit,
                'used'     => $user->ai_calls_today,
                'tier'     => $tier,
                'upgrade'  => $tier === 'trial',
            ], 429);
        }

        $response = $next($request);

        // Solo contar si la llamada fue exitosa
        if ($response->getStatusCode() === 200) {
            $user->increment('ai_calls_today');
        }

        return $response;
    }

    private function getUserTier($user): string
    {
        if ($user->role === 'admin') return 'admin';
        if (!$user->isSubscribed()) return 'trial';

        // Distinguir básico vs pro por el precio de la suscripción activa
        $priceMonthlyId = config('services.stripe.price_monthly');
        $priceYearlyId  = config('services.stripe.price_yearly');

        $subscription = $user->subscription('default');
        if (!$subscription) return 'trial';

        // Si el precio es el "pro" (más caro), es pro. Si no, básico.
        // Por simplicidad: si tiene suscripción activa = básico mínimo
        // Aquí se puede refinar si se añaden price IDs distintos por plan
        return 'basic';
    }
}
