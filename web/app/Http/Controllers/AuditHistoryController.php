<?php

namespace App\Http\Controllers;

use App\Models\Accountability;
use App\Models\AccountabilityHistory;
use App\Models\User;
use Throwable;

class AuditHistoryController extends Controller
{
    public function index()
    {
        $role = session('user_role') ?? session('role') ?? 'user';
        $logs = [];

        try {
            $rawLogs = AccountabilityHistory::orderBy('id', 'desc')->limit(100)->get()->toArray();

            $usersMap = [];
            foreach (User::all() as $u) {
                $usersMap[(int) $u->id] = $u->name;
            }

            $accMap = [];
            foreach (Accountability::withTrashed()->get() as $a) {
                $accMap[(int) $a->id] = $a->title;
            }

            foreach ($rawLogs as $row) {
                $accId                       = (int) $row['accountability_id'];
                $uid                         = (int) $row['user_id'];
                $row['accountability_title'] = $accMap[$accId] ?? ('Prestação #' . $accId);
                $row['user_name']            = $usersMap[$uid] ?? ('Usuário #' . $uid);
                $logs[]                      = $row;
            }
        } catch (Throwable) {
            $logs = [];
        }

        return view('audit_history.index', [
            'userName'  => session('user_name'),
            'username'  => session('username'),
            'userEmail' => session('user_email'),
            'userRole'  => $role,
            'logs'      => $logs,
        ]);
    }
}
