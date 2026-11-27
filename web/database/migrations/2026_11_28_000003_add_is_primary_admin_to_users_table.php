<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Administrador principal: o primeiro administrador do sistema, que nunca pode
 * deixar de ser administrador nem ser excluído.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || Schema::hasColumn('users', 'is_primary_admin')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_primary_admin')->default(false)->after('role');
        });

        // Marca o administrador mais antigo (que não seja a conta de desenvolvedor)
        $query = DB::table('users')->where('role', 'admin');
        if (Schema::hasColumn('users', 'is_developer')) {
            $query->where('is_developer', false);
        }
        $firstAdminId = $query->orderBy('id')->value('id');

        if ($firstAdminId !== null) {
            DB::table('users')->where('id', $firstAdminId)->update(['is_primary_admin' => true]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'is_primary_admin')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_primary_admin');
            });
        }
    }
};
