<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'notifications_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('notifications_enabled')->default(true)->after('avatar');
            });
        }

        if (! Schema::hasTable('admin_notifications')) {
            Schema::create('admin_notifications', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->index();
                $table->unsignedInteger('accountability_id')->nullable()->index();
                $table->string('actor_name', 120)->nullable();
                $table->string('title', 160);
                $table->text('message');
                $table->boolean('is_read')->default(false)->index();
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notifications');
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'notifications_enabled')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('notifications_enabled');
            });
        }
    }
};
