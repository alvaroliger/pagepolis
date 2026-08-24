<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\DomainReserveMovement;
use Illuminate\Support\Facades\DB;

/**
 * Fondo de reserva de dominios.
 *
 * PROBLEMA que resuelve: el registrador cobra el dominio ANUAL por adelantado,
 * pero al cliente le cobramos por ciclos (mensual/anual). Para no quedarnos
 * cortos en la renovación, en CADA cobro apartamos una porción prorrateada del
 * coste anual del dominio, con un margen de seguridad. Cuando llega la renovación
 * se paga DESDE el fondo acumulado.
 *
 * INVARIANTE (matemática del margen): con cobro mensual y margen m>=1, la porción
 * mensual es ceil(coste_anual * m / 12). Sumadas 12 porciones:
 *     12 * ceil(coste_anual * m / 12) >= coste_anual * m >= coste_anual
 * es decir, al cabo de un año SIEMPRE hay al menos el coste anual (más el colchón
 * del margen para subidas de precio/divisa). Lo que apartamos por ciclo es SIEMPRE
 * menor que lo que el cliente paga por ese ciclo (el dominio es solo una parte de
 * su cuota), así que nunca se come el margen de la plataforma.
 *
 * Todo en CÉNTIMOS. Append-only + idempotente por `reference` (un webhook repetido
 * de Stripe no cuenta dos veces el mismo cobro).
 */
class DomainReserveFund
{
    /** Porción a apartar por un ciclo de facturación, en céntimos. */
    public function accrualForCycle(string $interval = 'month'): int
    {
        $annual = (int) config('pagepolis.domain.annual_cost_cents');
        $margin = (float) config('pagepolis.domain.reserve_margin');
        $target = $annual * $margin;               // objetivo anual con colchón

        return match ($interval) {
            'year'  => (int) ceil($target),        // cobro anual: se aparta todo de una
            default => (int) ceil($target / 12),   // mensual: 1/12 (redondeo al alza)
        };
    }

    /** Saldo actual del fondo de un dominio (céntimos). */
    public function balance(Domain $domain): int
    {
        return (int) $domain->reserveMovements()->sum('amount_cents');
    }

    /**
     * Aparta la porción prorrateada de un cobro confirmado del cliente.
     * Idempotente: si ya se registró un accrual con esta misma `reference`
     * (p. ej. el id de la invoice de Stripe), no hace nada y devuelve null.
     * Se llama SOLO desde eventos de pago YA CONFIRMADO (no reversible).
     */
    public function accrue(Domain $domain, string $interval, string $reference, ?string $note = null): ?DomainReserveMovement
    {
        $amount = $this->accrualForCycle($interval);

        return $this->record($domain, 'accrual', $amount, $reference, $note);
    }

    /**
     * Registra el pago de la compra inicial o de una renovación DESDE el fondo.
     * `amountCents` es el precio REAL que cobró el registrador (consultado en vivo
     * antes de pagar), no un coste asumido. Devuelve el movimiento, o null si ya
     * estaba registrado con esa referencia (idempotencia ante reintentos del job).
     *
     * Nota: el fondo puede quedar temporalmente en negativo en la PRIMERA compra
     * (aún no se ha acumulado un año) — es esperado y visible en el histórico; el
     * margen lo recupera en los ciclos siguientes.
     */
    public function debitForRegistrar(Domain $domain, string $type, int $amountCents, string $reference, ?string $note = null): ?DomainReserveMovement
    {
        if (!in_array($type, ['purchase', 'renewal'], true)) {
            throw new \InvalidArgumentException("Tipo de débito no válido: {$type}");
        }

        return $this->record($domain, $type, -abs($amountCents), $reference, $note);
    }

    /** Devolución al fondo (p. ej. el registrador reembolsa una compra fallida). */
    public function refund(Domain $domain, int $amountCents, string $reference, ?string $note = null): ?DomainReserveMovement
    {
        return $this->record($domain, 'refund', abs($amountCents), $reference, $note);
    }

    /** Ajuste manual auditado (positivo entra, negativo sale). */
    public function adjust(Domain $domain, int $amountCents, string $reference, string $note): ?DomainReserveMovement
    {
        return $this->record($domain, 'adjust', $amountCents, $reference, $note);
    }

    /**
     * ¿Hay saldo suficiente en el fondo para pagar `priceCents` de renovación?
     * Se consulta ANTES de renovar; si es false hay que alertar (no fallar en
     * silencio) — puede ser un dominio premium o una subida de precio.
     */
    public function canCover(Domain $domain, int $priceCents): bool
    {
        return $this->balance($domain) >= $priceCents;
    }

    /**
     * Crea un movimiento de forma atómica y calcula balance_after. La unicidad
     * (domain_id, type, reference) en BD garantiza la idempotencia aunque dos
     * procesos lo intenten a la vez: el segundo choca con la constraint y se
     * resuelve como "ya aplicado".
     */
    private function record(Domain $domain, string $type, int $amountCents, string $reference, ?string $note): ?DomainReserveMovement
    {
        return DB::transaction(function () use ($domain, $type, $amountCents, $reference, $note) {
            $exists = DomainReserveMovement::where('domain_id', $domain->id)
                ->where('type', $type)
                ->where('reference', $reference)
                ->lockForUpdate()
                ->first();

            if ($exists) {
                return null; // ya aplicado con esta referencia → idempotente
            }

            $current = (int) DomainReserveMovement::where('domain_id', $domain->id)
                ->sum('amount_cents');

            return DomainReserveMovement::create([
                'domain_id'           => $domain->id,
                'type'                => $type,
                'amount_cents'        => $amountCents,
                'balance_after_cents' => $current + $amountCents,
                'reference'           => $reference,
                'note'                => $note,
            ]);
        });
    }
}
