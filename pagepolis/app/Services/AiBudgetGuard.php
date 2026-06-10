<?php

namespace App\Services;

use App\Models\AiSpend;
use Illuminate\Support\Facades\DB;

/**
 * Guardián de presupuesto de IA.
 *
 * Acumula el gasto estimado de las llamadas a la API de Anthropic y permite
 * cortar nuevas llamadas cuando se alcanza el tope DIARIO o MENSUAL configurado.
 * Es la protección principal contra el abuso: aunque mil bots intenten generar
 * webs, el gasto total nunca supera estos topes, así nadie puede arruinarte
 * con tu API key. Subir los topes es una acción manual (= con tu permiso).
 */
class AiBudgetGuard
{
    /** ¿Quedan créditos dentro de los topes diario y mensual? */
    public function isWithinBudget(): bool
    {
        $monthly = $this->monthlyBudgetUsd();
        if ($monthly > 0 && $this->monthToDateUsd() >= $monthly) {
            return false;
        }

        $daily = $this->dailyBudgetUsd();
        if ($daily > 0 && $this->todayUsd() >= $daily) {
            return false;
        }

        return true;
    }

    /** Motivo del bloqueo (para logs/avisos): 'mensual', 'diario' o null. */
    public function blockedReason(): ?string
    {
        $monthly = $this->monthlyBudgetUsd();
        if ($monthly > 0 && $this->monthToDateUsd() >= $monthly) {
            return 'mensual';
        }

        $daily = $this->dailyBudgetUsd();
        if ($daily > 0 && $this->todayUsd() >= $daily) {
            return 'diario';
        }

        return null;
    }

    /** Gasto estimado acumulado este mes (USD). */
    public function monthToDateUsd(): float
    {
        return (float) DB::table('ai_spend')
            ->where('day', '>=', now()->startOfMonth()->toDateString())
            ->sum('est_cost_usd');
    }

    /** Gasto estimado de hoy (USD). */
    public function todayUsd(): float
    {
        return (float) (DB::table('ai_spend')->where('day', now()->toDateString())->value('est_cost_usd') ?? 0);
    }

    public function monthlyBudgetUsd(): float
    {
        return (float) config('services.anthropic.monthly_budget_usd', 0);
    }

    /**
     * Tope diario (USD). Si no se configura uno explícito, se deriva del mensual
     * (mensual / 10) para suavizar picos de abuso, sin dejar de permitir ráfagas
     * legítimas. 0 desactiva el tope diario.
     */
    public function dailyBudgetUsd(): float
    {
        $explicit = config('services.anthropic.daily_budget_usd');
        if ($explicit !== null && $explicit !== '') {
            return (float) $explicit;
        }

        $monthly = $this->monthlyBudgetUsd();
        return $monthly > 0 ? round($monthly / 10, 2) : 0.0;
    }

    /** Presupuesto mensual restante (USD). INF si no hay tope. */
    public function remainingUsd(): float
    {
        $budget = $this->monthlyBudgetUsd();
        if ($budget <= 0) {
            return INF;
        }

        return max(0.0, $budget - $this->monthToDateUsd());
    }

    /** Porcentaje del presupuesto mensual consumido (0-100+). 0 si no hay tope. */
    public function usedPercent(): float
    {
        $budget = $this->monthlyBudgetUsd();
        if ($budget <= 0) {
            return 0.0;
        }

        return round(($this->monthToDateUsd() / $budget) * 100, 1);
    }

    /** Registra el consumo de una llamada a la API en el día de hoy. */
    public function record(string $model, int $tokensIn, int $tokensOut): void
    {
        $cost = $this->estimateCost($model, $tokensIn, $tokensOut);

        $row = AiSpend::firstOrCreate(['day' => now()->toDateString()]);
        $row->increment('tokens_in', $tokensIn);
        $row->increment('tokens_out', $tokensOut);
        $row->increment('est_cost_usd', $cost);
    }

    /** Estima el coste en USD según la tarifa por modelo configurada. */
    public function estimateCost(string $model, int $tokensIn, int $tokensOut): float
    {
        $pricing = config('services.anthropic.pricing', []);
        $rate    = $pricing['default'] ?? ['in' => 15.0, 'out' => 75.0];

        foreach ($pricing as $prefix => $p) {
            if ($prefix !== 'default' && str_starts_with($model, $prefix)) {
                $rate = $p;
                break;
            }
        }

        return ($tokensIn / 1_000_000) * $rate['in']
             + ($tokensOut / 1_000_000) * $rate['out'];
    }
}
