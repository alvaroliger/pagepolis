<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Miniatura EN VÍDEO por plantilla (`/previews/t{id}-{fp}.mp4`), la genera
 * `php artisan templates:previews`. La rejilla la reproduce en bucle y en
 * silencio para enseñar el movimiento real de la plantilla sin montar iframes
 * (el vídeo se descodifica fuera del hilo principal -> muchas a la vez y fluido).
 * El `thumbnail` estático sigue existiendo: es el `poster` del vídeo, así nunca
 * se ve negro mientras carga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('preview_video')->nullable()->after('thumbnail');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn('preview_video');
        });
    }
};
