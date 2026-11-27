<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

class AdminNotification extends Model
{
    protected $table = 'admin_notifications';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'accountability_id',
        'actor_name',
        'title',
        'message',
        'is_read',
        'created_at',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public static function notifyAdmins(
        string $title,
        string $message,
        ?int $accountabilityId = null,
        ?string $actorName = null
    ): void {
        try {
            $admins = User::where('role', 'admin')->get();
            $now    = now()->format('Y-m-d H:i:s');

            foreach ($admins as $adm) {
                // Só notifica administradores que mantiveram as notificações ativas
                if ($adm->notifications_enabled !== null && ! (bool) $adm->notifications_enabled) {
                    continue;
                }

                self::create([
                    'user_id'           => (int) $adm->id,
                    'accountability_id' => $accountabilityId,
                    'actor_name'        => $actorName,
                    'title'             => $title,
                    'message'           => $message,
                    'is_read'           => false,
                    'created_at'        => $now,
                ]);
            }
        } catch (Throwable) {
            // Não interrompe o fluxo principal se a tabela ainda não existir
        }
    }
}
