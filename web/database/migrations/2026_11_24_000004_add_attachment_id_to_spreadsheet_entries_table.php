<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accountability_spreadsheet_entries') || Schema::hasColumn('accountability_spreadsheet_entries', 'attachment_id')) {
            return;
        }

        Schema::table('accountability_spreadsheet_entries', function (Blueprint $table) {
            $table->unsignedBigInteger('attachment_id')->nullable()->after('item_number')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('accountability_spreadsheet_entries') && Schema::hasColumn('accountability_spreadsheet_entries', 'attachment_id')) {
            Schema::table('accountability_spreadsheet_entries', function (Blueprint $table) {
                $table->dropColumn('attachment_id');
            });
        }
    }
};
