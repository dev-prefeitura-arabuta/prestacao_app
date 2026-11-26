<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_accountability')) {
            Schema::create('public_accountability', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 100);
                $table->text('description')->nullable();
                $table->string('status', 255)->default('aberta');
                $table->boolean('is_paused')->default(false);
                $table->date('deadline')->nullable();
                $table->text('enabled_items')->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
            });
        }

        if (! Schema::hasTable('public_accountability_attachments')) {
            Schema::create('public_accountability_attachments', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('accountability_id');
                $table->unsignedInteger('item_number');
                $table->unsignedInteger('user_id');
                $table->string('original_name', 255);
                $table->string('file_name', 255);
                $table->string('file_path', 500);
                $table->string('file_ext', 20);
                $table->unsignedInteger('file_size')->default(0);
                $table->dateTime('created_at')->nullable();
                $table->index(['accountability_id', 'item_number'], 'accountability_id_item_number');
            });
        }

        if (! Schema::hasTable('public_accountability_history')) {
            Schema::create('public_accountability_history', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('accountability_id');
                $table->unsignedInteger('user_id');
                $table->string('status', 255)->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->dateTime('deleted_at')->nullable();
            });
        }

        if (! Schema::hasTable('public_accountability_item_statuses')) {
            Schema::create('public_accountability_item_statuses', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('accountability_id');
                $table->unsignedInteger('item_number');
                $table->string('status', 30)->default('pendente');
                $table->text('rejection_reason')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->dateTime('updated_at')->nullable();
                $table->index(['accountability_id', 'item_number'], 'accountability_id_item_number');
            });
        }

        if (! Schema::hasTable('user_accountability_permissions')) {
            Schema::create('user_accountability_permissions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('item_number');
                $table->dateTime('created_at')->nullable();
                $table->index(['user_id', 'item_number'], 'user_id_item_number');
            });
        }

        if (! Schema::hasTable('accountability_attachment_ai_extractions')) {
            Schema::create('accountability_attachment_ai_extractions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('accountability_id')->index();
                $table->unsignedInteger('item_number');
                $table->unsignedInteger('attachment_id')->index();
                $table->string('document_type', 120)->nullable();
                $table->string('document_number', 120)->nullable();
                $table->string('emitter_name', 180)->nullable();
                $table->string('cnpj_cpf', 60)->nullable();
                $table->string('issue_date', 60)->nullable();
                $table->decimal('total_value', 15, 2)->nullable()->default(0.00);
                $table->text('summary')->nullable();
                $table->string('compatibility', 60)->nullable();
                $table->text('raw_response')->nullable();
                $table->string('model_used', 80)->nullable();
                $table->dateTime('analyzed_at')->nullable();
            });
        }

        if (! Schema::hasTable('config_smtp')) {
            Schema::create('config_smtp', function (Blueprint $table) {
                $table->increments('id');
                $table->string('smtp_host', 255);
                $table->string('smtp_port', 10)->default('587');
                $table->string('smtp_encryption', 20)->default('STARTTLS');
                $table->string('smtp_username', 255);
                $table->string('smtp_password', 255);
                $table->string('email_address', 255);
                $table->string('email_name', 255);
                $table->string('reply_to_email', 255)->nullable();
                $table->integer('smtp_auth')->default(1);
                $table->integer('smtp_timeout')->default(30);
                $table->integer('smtp_verify_ssl')->default(1);
                $table->integer('status')->default(1);
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }

        if (! Schema::hasTable('general_settings')) {
            Schema::create('general_settings', function (Blueprint $table) {
                $table->increments('id');
                $table->string('app_name', 150)->default('Sistema de Prestação de Contas');
                $table->string('company_name', 200)->default('Município de Arabutã');
                $table->string('cnpj', 30)->nullable();
                $table->string('app_logo', 255)->nullable();
                $table->boolean('pdf_cover_enabled')->nullable()->default(true);
                $table->boolean('pdf_cover_index_enabled')->nullable()->default(true);
                $table->boolean('pdf_cover_show_logo')->nullable()->default(true);
                $table->boolean('pdf_cover_show_border')->nullable()->default(true);
                $table->string('pdf_cover_label', 120)->nullable()->default('PRESTAÇÃO DE CONTAS');
                $table->string('pdf_cover_subtitle', 255)->nullable()->default('DOSSIÊ DOCUMENTAL CONSOLIDADO');
                $table->string('pdf_cover_footer', 255)->nullable();
                $table->string('pdf_cover_primary_color', 20)->nullable()->default('#0f172a');
                $table->string('pdf_cover_accent_color', 20)->nullable()->default('#dd4814');
                $table->string('pdf_cover_art', 255)->nullable();
                $table->dateTime('created_at')->nullable();
                $table->dateTime('updated_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('general_settings');
        Schema::dropIfExists('config_smtp');
        Schema::dropIfExists('accountability_attachment_ai_extractions');
        Schema::dropIfExists('user_accountability_permissions');
        Schema::dropIfExists('public_accountability_item_statuses');
        Schema::dropIfExists('public_accountability_history');
        Schema::dropIfExists('public_accountability_attachments');
        Schema::dropIfExists('public_accountability');
    }
};
