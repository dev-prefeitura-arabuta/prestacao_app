<?php

namespace App\Models;

use CodeIgniter\Model;

class UserAccountabilityPermissionModel extends Model
{
    protected $table            = 'user_accountability_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'item_number',
        'created_at',
    ];

    /**
     * Retorna a lista de números de itens (1..24) permitidos para um usuário.
     *
     * @return list<int>
     */
    public function getAllowedItemsForUser(int $userId): array
    {
        $rows = $this->where('user_id', $userId)
            ->orderBy('item_number', 'ASC')
            ->findAll();

        return array_values(array_unique(array_map(static fn ($r) => (int) $r['item_number'], $rows)));
    }

    /**
     * Atualiza os itens (1..24) permitidos para um usuário.
     *
     * @param list<int|string> $itemNumbers
     */
    public function syncUserPermissions(int $userId, array $itemNumbers): void
    {
        $this->where('user_id', $userId)->delete();

        $now   = date('Y-m-d H:i:s');
        $valid = [];

        foreach ($itemNumbers as $num) {
            $n = (int) $num;
            if ($n >= 1 && $n <= 24) {
                $valid[$n] = [
                    'user_id'     => $userId,
                    'item_number' => $n,
                    'created_at'  => $now,
                ];
            }
        }

        if (! empty($valid)) {
            $this->insertBatch(array_values($valid));
        }
    }
}
