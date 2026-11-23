<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountabilityAttachmentAi extends Model
{
    protected $table = 'accountability_attachment_ai_extractions';

    public $timestamps = false;

    protected $fillable = [
        'accountability_id',
        'item_number',
        'attachment_id',
        'document_type',
        'document_number',
        'emitter_name',
        'cnpj_cpf',
        'issue_date',
        'total_value',
        'summary',
        'compatibility',
        'raw_response',
        'model_used',
        'analyzed_at',
    ];

    public static function upsertByAttachment(int $attachmentId, array $data): int
    {
        $record = self::updateOrCreate(
            ['attachment_id' => $attachmentId],
            $data
        );

        return (int) $record->id;
    }
}
