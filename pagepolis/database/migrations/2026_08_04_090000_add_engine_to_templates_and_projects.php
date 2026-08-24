<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deja de duplicar el motor compartido dentro de cada plantilla y proyecto.
 *
 * Antes, cada fila guardaba una copia completa de kinetic.css/js (~110 KB).
 * Con 170 plantillas eran 17,6 MB en base de datos, de los que ~14 eran el
 * mismo código repetido, y la galería llegaba a agotar el tiempo de ejecución
 * solo con serializarlo. Ahora la fila guarda su CSS/JS propio y este campo
 * dice qué motor compartido necesita, que se sirve como archivo cacheable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->string('engine', 20)->nullable()->after('js');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->string('engine', 20)->nullable()->after('js');
        });
    }

    public function down(): void
    {
        Schema::table('templates', fn (Blueprint $t) => $t->dropColumn('engine'));
        Schema::table('projects', fn (Blueprint $t) => $t->dropColumn('engine'));
    }
};
