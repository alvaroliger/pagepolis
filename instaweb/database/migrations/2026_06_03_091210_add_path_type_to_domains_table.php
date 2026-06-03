<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite no soporta MODIFY COLUMN ni ENUMs — solo MySQL lo necesita
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE domains MODIFY COLUMN type ENUM('custom','subdomain','path') DEFAULT 'subdomain'");
        }
        // En SQLite el campo ya es TEXT y acepta cualquier valor — no hace falta migrar
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE domains MODIFY COLUMN type ENUM('custom','subdomain') DEFAULT 'subdomain'");
        }
    }
};
