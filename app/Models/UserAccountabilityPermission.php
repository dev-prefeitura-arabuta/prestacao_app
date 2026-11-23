<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserAccountabilityPermission extends Model
{
    protected $table = 'user_accountability_permissions';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'item_number',
        'created_at',
    ];

    public static function getAllowedItemsForUser(int $userId): array
    {
        return self::where('user_id', $userId)
            ->orderBy('item_number', 'asc')
            ->pluck('item_number')
            ->map(static fn ($v) => (int) $v)
            ->unique()
            ->values()
            ->all();
    }

    public static function syncUserPermissions(int $userId, array $itemNumbers): void
    {
        self::where('user_id', $userId)->delete();

        $now   = now()->format('Y-m-d H:i:s');
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
            self::insert(array_values($valid));
        }
    }
}
