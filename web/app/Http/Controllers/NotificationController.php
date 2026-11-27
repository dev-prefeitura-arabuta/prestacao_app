<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Http\Request;
use Throwable;

class NotificationController extends Controller
{
    public function index()
    {
        $role   = session('user_role') ?? session('role') ?? 'user';
        $userId = (int) session('user_id');

        if ($role !== 'admin' || $userId <= 0) {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

        try {
            $user    = User::find($userId);
            $enabled = $user === null || $user->notifications_enabled === null
                ? true
                : (bool) $user->notifications_enabled;

            $rows = AdminNotification::where('user_id', $userId)
                ->orderBy('id', 'desc')
                ->limit(30)
                ->get()
                ->map(static fn ($n) => [
                    'id'                => (int) $n->id,
                    'accountability_id' => $n->accountability_id ? (int) $n->accountability_id : null,
                    'actor_name'        => (string) ($n->actor_name ?? ''),
                    'title'             => (string) $n->title,
                    'message'           => (string) $n->message,
                    'is_read'           => (bool) $n->is_read,
                    'created_at'        => $n->created_at ? date('d/m/Y H:i', strtotime((string) $n->created_at)) : '',
                ])
                ->all();

            $unread = AdminNotification::where('user_id', $userId)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success'      => true,
                'enabled'      => $enabled,
                'unread_count' => $unread,
                'items'        => $rows,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success'      => true,
                'enabled'      => true,
                'unread_count' => 0,
                'items'        => [],
            ]);
        }
    }

    public function toggle(Request $request)
    {
        $role   = session('user_role') ?? session('role') ?? 'user';
        $userId = (int) session('user_id');

        if ($role !== 'admin' || $userId <= 0) {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

        $user = User::find($userId);
        if (! $user) {
            return response()->json(['success' => false, 'error' => 'Usuário não encontrado.'], 404);
        }

        $newValue = $request->has('enabled')
            ? (bool) $request->input('enabled')
            : ! ((bool) ($user->notifications_enabled ?? true));

        $user->update(['notifications_enabled' => $newValue]);

        return response()->json([
            'success' => true,
            'enabled' => $newValue,
            'message' => $newValue ? 'Notificações ativadas.' : 'Notificações pausadas.',
        ]);
    }

    public function markRead(int $id)
    {
        $userId = (int) session('user_id');
        AdminNotification::where('id', $id)->where('user_id', $userId)->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function markAllRead()
    {
        $userId = (int) session('user_id');
        AdminNotification::where('user_id', $userId)->where('is_read', false)->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
