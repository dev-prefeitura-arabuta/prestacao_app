<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index(): string
    {
        $role = session()->get('user_role') ?? session()->get('role');

        // Caso a sessão tenha sido criada antes de adicionarmos 'user_role', infere pelo e-mail/usuário
        if ($role === null) {
            $email    = (string) session()->get('user_email');
            $username = (string) session()->get('username');
            $role     = ($email === 'admin@admin.com' || strtolower($username) === 'admin') ? 'admin' : 'user';
            session()->set([
                'user_role' => $role,
                'role'      => $role,
            ]);
        }

        return view('dashboard/index', [
            'userName'  => session()->get('user_name'),
            'username'  => session()->get('username'),
            'userEmail' => session()->get('user_email'),
            'userRole'  => $role,
        ]);
    }
}
