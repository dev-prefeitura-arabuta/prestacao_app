<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountabilityAttachmentAiModel extends Model
{
    protected $table            = 'accountability_attachment_ai_extractions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
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

    public function upsertByAttachment(int $attachmentId, array $data): int
    {
        $existing = $this->where('attachment_id', $attachmentId)->first();
        if ($existing !== null) {
            $this->update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        $data['attachment_id'] = $attachmentId;
        return (int) $this->insert($data, true);
    }
}
