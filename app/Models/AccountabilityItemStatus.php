<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountabilityItemStatus extends Model
{
    protected $table = 'public_accountability_item_statuses';

    public $timestamps = false;

    protected $fillable = [
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

    public static function setStatus(
        int $accountabilityId,
        int $itemNumber,
        string $status,
        ?int $userId = null,
        ?string $rejectionReason = null
    ): void {
        self::updateOrCreate(
            [
                'accountability_id' => $accountabilityId,
                'item_number'       => $itemNumber,
            ],
            [
                'status'           => $status,
                'rejection_reason' => $status === 'rejeitado' ? $rejectionReason : null,
                'updated_by'       => $userId,
                'updated_at'       => now()->format('Y-m-d H:i:s'),
            ]
        );
    }
}
