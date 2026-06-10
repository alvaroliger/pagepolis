<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `domains.type` se creó como enum('custom','subdomain'), lo que en SQLite genera
 * un CHECK constraint que rechaza el valor 'path' (usado por la publicación gratis).
 * Lo convertimos a string en todos los motores para aceptar custom/subdomain/path
 * sin romper la publicación. Igual con `status` por robustez.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('type')->default('subdomain')->change();
            $table->string('status')->default('pending')->change();
        });
    }

    public function down(): void
    {
        // No revertimos a enum: string es un superconjunto seguro.
    }
};
