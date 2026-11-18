<?php

namespace App\Controllers;

use App\Models\AccountabilityHistoryModel;
use App\Models\AccountabilityModel;
use App\Models\UserModel;
use Throwable;

class AuditHistory extends BaseController
{
    public function index(): string
    {
        $role = session()->get('user_role') ?? session()->get('role') ?? 'user';

        $logs = [];

        try {
            $historyModel = new AccountabilityHistoryModel();
            $rawLogs      = $historyModel->orderBy('id', 'DESC')->findAll(100);

            $userModel = new UserModel();
            $usersMap  = [];
            foreach ($userModel->findAll() as $u) {
                $usersMap[(int) $u['id']] = $u['name'];
            }

            $accModel = new AccountabilityModel();
            $accMap   = [];
            foreach ($accModel->withDeleted()->findAll() as $a) {
                $accMap[(int) $a['id']] = $a['title'];
            }

            foreach ($rawLogs as $row) {
                $accId                     = (int) $row['accountability_id'];
                $uid                       = (int) $row['user_id'];
                $row['accountability_title'] = $accMap[$accId] ?? ('Prestação #' . $accId);
                $row['user_name']            = $usersMap[$uid] ?? ('Usuário #' . $uid);
                $logs[]                    = $row;
            }
        } catch (Throwable) {
            $logs = [];
        }

        return view('audit_history/index', [
            'userName'  => session()->get('user_name'),
            'username'  => session()->get('username'),
            'userEmail' => session()->get('user_email'),
            'userRole'  => $role,
            'logs'      => $logs,
        ]);
    }
}
