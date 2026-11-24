<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('config_whatsapp')) {
            return;
        }

        Schema::create('config_whatsapp', function (Blueprint $table) {
            $table->id();
            $table->string('api_url', 255)->default('http://localhost:2785');
            $table->text('api_key')->nullable();
            $table->string('session_id', 120)->nullable();
            $table->string('session_name', 120)->default('prestacao-contas');
            $table->string('engine_type', 40)->default('whatsapp-web.js');
            $table->string('default_country_code', 10)->default('55');
            $table->integer('timeout')->default(15);
            $table->string('webhook_url', 255)->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('config_whatsapp');
    }
};
