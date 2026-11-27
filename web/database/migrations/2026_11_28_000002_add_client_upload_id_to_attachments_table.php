<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Identificador gerado pelo app mobile para cada anexo da fila offline.
 * Se a conexão cair depois que o servidor recebeu o arquivo, o app reenvia com o mesmo
 * identificador e o servidor devolve o anexo já gravado em vez de duplicá-lo.
 */
return new class extends Migration
{
    private const TABLE = 'public_accountability_attachments';

    public function up(): void
    {
        if (Schema::hasTable(self::TABLE) && ! Schema::hasColumn(self::TABLE, 'client_upload_id')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->string('client_upload_id', 64)->nullable()->after('user_id');
                $table->unique('client_upload_id', 'attachments_client_upload_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable(self::TABLE) && Schema::hasColumn(self::TABLE, 'client_upload_id')) {
            Schema::table(self::TABLE, function (Blueprint $table) {
                $table->dropUnique('attachments_client_upload_id_unique');
                $table->dropColumn('client_upload_id');
            });
        }
    }
};
