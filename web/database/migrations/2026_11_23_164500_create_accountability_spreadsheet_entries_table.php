<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('accountability_spreadsheet_entries')) {
            Schema::create('accountability_spreadsheet_entries', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('accountability_id')->index();
                $table->unsignedInteger('item_number')->nullable();
                $table->string('entry_date', 20)->nullable();
                $table->string('document_number', 100)->nullable();
                $table->string('supplier_name', 200)->nullable();
                $table->string('cnpj_cpf', 30)->nullable();
                $table->string('description', 500)->nullable();
                $table->string('entry_type', 20)->default('despesa'); // receita | despesa
                $table->decimal('amount', 15, 2)->default(0);
                $table->string('notes', 500)->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accountability_spreadsheet_entries');
    }
};
