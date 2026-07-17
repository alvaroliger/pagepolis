<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->longText('previous_html')->nullable()->after('js');
            $table->longText('previous_css')->nullable()->after('previous_html');
            $table->longText('previous_js')->nullable()->after('previous_css');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['previous_html', 'previous_css', 'previous_js']);
        });
    }
};
