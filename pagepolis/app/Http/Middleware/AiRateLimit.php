<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // Reserve a slot atomically BEFORE processing. This closes the TOCTOU race
        // where two concurrent requests both pass an exceeded() check simultaneously.
        if (!self::reserve($user)) {
            $limit = self::dailyLimit($user);
            $user->refresh();
            return response()->json([
                'error'   => "Has alcanzado el límite de {$limit} generaciones con IA hoy. Se restablece " . now()->endOfDay()->diffForHumans() . '.',
                'limit'   => $limit,
                'used'    => self::used($user),
                'tier'    => self::tier($user),
                'upgrade' => self::tier($user) === 'trial',
            ], 429);
        }

        $response = $next($request);

        // Release the reserved slot if the endpoint returned an error (e.g. 409).
        if ($response->getStatusCode() !== 200) {
            DB::table('users')->where('id', $user->id)->decrement('ai_calls_today');
        }

        return $response;
    }

    /** Resetea el contador si es un nuevo día y devuelve los usos de hoy. */
    public static function used(User $user): int
    {
        $today = now()->toDateString();
        // OJO: ai_calls_reset_date es un cast 'date' (Carbon); hay que comparar por
        // toDateString(), no por (string), o el contador se resetearía SIEMPRE y el
        // límite diario de IA no se aplicaría nunca (riesgo de coste descontrolado).
        if ($user->ai_calls_reset_date?->toDateString() !== $today) {
            $user->update(['ai_calls_today' => 0, 'ai_calls_reset_date' => $today]);
        }
        return (int) $user->ai_calls_today;
    }

    public static function dailyLimit(User $user): int
    {
        return self::LIMITS[self::tier($user)] ?? 5;
    }

    public static function exceeded(User $user): bool
    {
        return self::used($user) >= self::dailyLimit($user);
    }

    /**
     * Atomically reserve one AI quota slot if the user is still under the daily limit.
     * Returns true when the slot was reserved, false when the quota was already full.
     */
    public static function reserve(User $user): bool
    {
        self::used($user); // ensure daily reset before the atomic increment
        return (bool) DB::table('users')
            ->where('id', $user->id)
            ->where('ai_calls_today', '<', self::dailyLimit($user))
            ->increment('ai_calls_today');
    }

    /** Suma una llamada de IA al contador diario. */
    public static function register(User $user): void
    {
        self::reserve($user);
    }

    public static function tier(User $user): string
    {
        if ($user->role === 'admin') return 'admin';
        if (!$user->isSubscribed()) return 'trial';
        return 'basic';
    }
}
