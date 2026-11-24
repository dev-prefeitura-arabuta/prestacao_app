<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whatsapp_overdue_notifications')) {
            return;
        }

        Schema::create('whatsapp_overdue_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('accountability_id');
            $table->unsignedSmallInteger('item_number');
            $table->unsignedBigInteger('user_id');
            $table->string('phone', 40);
            $table->text('message');
            $table->date('notified_on');
            $table->string('status', 20)->default('sent');
            $table->text('error_message')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['accountability_id', 'item_number', 'user_id', 'notified_on'], 'idx_wa_overdue_dedup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_overdue_notifications');
    }
};
