<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de gasto de IA por DÍA. Permite al guardián de presupuesto
 * (AiBudgetGuard) cortar las llamadas a la API cuando se alcanza el tope
 * diario o mensual, garantizando que nadie (bots o a mano) pueda disparar
 * el coste de la API key. El mes en curso se calcula sumando los días.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_spend', function (Blueprint $table) {
            $table->id();
            $table->date('day')->unique(); // p.ej. 2026-06-09
            $table->unsignedBigInteger('tokens_in')->default(0);
            $table->unsignedBigInteger('tokens_out')->default(0);
            $table->decimal('est_cost_usd', 12, 6)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_spend');
    }
};
