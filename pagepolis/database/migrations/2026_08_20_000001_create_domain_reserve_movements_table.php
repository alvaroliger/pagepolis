<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico AUDITABLE del fondo de reserva de dominios (append-only).
 *
 * Cada fila es un MOVIMIENTO (no un saldo): entrada al apartar la porción
 * prorrateada de un cobro, salida al pagar la compra/renovación al registrador.
 * El saldo de un dominio es la suma de sus movimientos; se guarda `balance_after`
 * en cada fila para poder auditar el saldo en cualquier punto del tiempo y
 * detectar descuadres sin recalcular todo.
 *
 * NUNCA se actualiza ni se borra una fila: las correcciones se hacen con un
 * movimiento nuevo (p. ej. un ajuste). Así el rastro de dinero es inmutable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_reserve_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained()->cascadeOnDelete();

            // 'accrual'  = entrada: porción prorrateada apartada de un cobro.
            // 'purchase' = salida: pago de la compra inicial al registrador.
            // 'renewal'  = salida: pago de una renovación anual al registrador.
            // 'refund'   = entrada: devolución del registrador (p. ej. compra fallida).
            // 'adjust'   = corrección manual auditada (entrada o salida).
            $table->string('type', 20);

            // En céntimos. Positivo = entra al fondo; negativo = sale del fondo.
            $table->bigInteger('amount_cents');

            // Saldo del fondo de ESTE dominio justo después de aplicar el movimiento.
            $table->bigInteger('balance_after_cents');

            // Referencia externa idempotente (id de invoice de Stripe, id de la
            // renovación, etc.). Con el índice único evita contar dos veces el
            // mismo cobro si el webhook se entrega repetido.
            $table->string('reference')->nullable();

            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'created_at']);
            // Idempotencia: un mismo (dominio, tipo, referencia) no se aplica 2 veces.
            $table->unique(['domain_id', 'type', 'reference'], 'domain_reserve_idem');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_reserve_movements');
    }
};
