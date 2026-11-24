<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('public_accountability', 'is_paused')) {
            Schema::table('public_accountability', function (Blueprint $table) {
                $table->boolean('is_paused')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('public_accountability', 'is_paused')) {
            Schema::table('public_accountability', function (Blueprint $table) {
                $table->dropColumn('is_paused');
            });
        }
    }
};
