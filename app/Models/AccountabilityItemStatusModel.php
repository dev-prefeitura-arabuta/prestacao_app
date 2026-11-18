<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountabilityItemStatusModel extends Model
{
    protected $table            = 'public_accountability_item_statuses';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'accountability_id',
        'item_number',
        'status',
        'rejection_reason',
        'updated_by',
        'updated_at',
    ];

    public static function getValidStatuses(): array
    {
        return [
            'pendente'      => 'Pendente',
            'enviado'       => 'Enviado',
            'confirmado'    => 'Confirmado',
            'rejeitado'     => 'Rejeitado',
            'prazo_vencido' => 'Prazo vencido',
        ];
    }

    public function setStatus(
        int $accountabilityId,
        int $itemNumber,
        string $status,
        ?int $userId = null,
        ?string $rejectionReason = null
    ): void {
        $existing = $this->where('accountability_id', $accountabilityId)
            ->where('item_number', $itemNumber)
            ->first();

        $now = date('Y-m-d H:i:s');

        $data = [
            'status'           => $status,
            'rejection_reason' => $status === 'rejeitado' ? $rejectionReason : null,
            'updated_by'       => $userId,
            'updated_at'       => $now,
        ];

        if ($existing !== null) {
            $this->update($existing['id'], $data);
        } else {
            $data['accountability_id'] = $accountabilityId;
            $data['item_number']       = $itemNumber;
            $this->insert($data);
        }
    }
}
