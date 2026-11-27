<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Conta de desenvolvedor: administrador com acesso às configurações técnicas
 * (SMTP, WhatsApp/OpenWA, IA). Definida apenas via `php artisan app:developer`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'is_developer')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_developer')->default(false)->after('role');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_developer')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_developer');
            });
        }
    }
};
