<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            // Estado de la generación en segundo plano: null/ready = libre,
            // 'generating' = en curso, 'error' = falló.
            $table->string('ai_status')->nullable()->after('ai_history');
            // Mensaje legible del paso actual ("Diseñando la estructura…") o el error.
            $table->string('ai_progress')->nullable()->after('ai_status');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['ai_status', 'ai_progress']);
        });
    }
};
