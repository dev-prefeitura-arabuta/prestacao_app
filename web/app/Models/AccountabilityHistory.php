<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Throwable;

class AccountabilityHistory extends Model
{
    use SoftDeletes;

    protected $table = 'public_accountability_history';

    protected $fillable = [
        'accountability_id',
        'user_id',
        'status',
    ];

    /**
     * Registra uma ação no histórico de auditoria. Nunca lança exceção (falha de log não bloqueia a ação).
     */
    public static function log(int $accountabilityId, int $userId, string $statusText): void
    {
        try {
            self::create([
                'accountability_id' => $accountabilityId,
                'user_id'           => $userId,
                'status'            => mb_substr($statusText, 0, 250),
            ]);
        } catch (Throwable) {
            // Ignora falha de log
        }
    }
}
