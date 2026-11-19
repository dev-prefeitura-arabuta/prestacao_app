<?php

namespace App\Controllers;

use App\Models\UserModel;
use Throwable;

class Auth extends BaseController
{
    /**
     * Lista padrão de 10 usuários caso o banco ainda não esteja populado.
     */
    private array $defaultUsers = [
        [
            'id'       => 1,
            'name'     => 'Administrador Geral',
            'username' => 'admin',
            'email'    => 'admin@admin.com',
            'password' => '123456',
            'role'     => 'admin',
        ],
        [
            'id'       => 2,
            'name'     => 'Carlos Eduardo (Controladoria)',
            'username' => 'controladoria',
            'email'    => 'controladoria@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'admin',
        ],
        [
            'id'       => 3,
            'name'     => 'Fernanda Souza (Contabilidade)',
            'username' => 'contabilidade',
            'email'    => 'contabilidade@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
        [
            'id'       => 4,
            'name'     => 'Marcos Oliveira (Sec. Educação)',
            'username' => 'educacao',
            'email'    => 'educacao@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
        [
            'id'       => 5,
            'name'     => 'Juliana Lima (Sec. Saúde)',
            'username' => 'saude',
            'email'    => 'saude@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
        [
            'id'       => 6,
            'name'     => 'Roberto Santos (Sec. Obras)',
            'username' => 'obras',
            'email'    => 'obras@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
        [
            'id'       => 7,
            'name'     => 'Patrícia Costa (Sec. Agricultura)',
            'username' => 'agricultura',
            'email'    => 'agricultura@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
        [
            'id'       => 8,
            'name'     => 'Lucas Almeida (Licitações)',
            'username' => 'licitacoes',
            'email'    => 'licitacoes@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
        [
            'id'       => 9,
            'name'     => 'Camila Rocha (Assistência Social)',
            'username' => 'social',
            'email'    => 'social@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
        [
            'id'       => 10,
            'name'     => 'Rafael Mendes (Administração)',
            'username' => 'administracao',
            'email'    => 'administracao@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
    ];

    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/accountability');
        }

        $users = $this->getAvailableUsers();

        return view('auth/login', [
            'users' => $users,
        ]);
    }

    public function attemptLogin()
    {
        $rules = [
            'login'    => 'required',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Credenciais inválidas.');
        }

        $login    = trim((string) $this->request->getPost('login'));
        $password = (string) $this->request->getPost('password');

        $authenticatedUser = $this->verifyCredentials($login, $password);

        if ($authenticatedUser === null) {
            return redirect()->back()->withInput()->with('error', 'Credenciais inválidas.');
        }

        $session = session();
        $session->regenerate();
        $session->set([
            'user_id'     => $authenticatedUser['id'],
            'user_name'   => $authenticatedUser['name'],
            'username'    => $authenticatedUser['username'] ?? $authenticatedUser['name'],
            'user_email'  => $authenticatedUser['email'],
            'user_role'   => $authenticatedUser['role'] ?? 'user',
            'role'        => $authenticatedUser['role'] ?? 'user',
            'user_avatar' => $authenticatedUser['avatar'] ?? null,
            'isLoggedIn'  => true,
        ]);

        return redirect()->to('/accountability');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/login');
    }

    private function getAvailableUsers(): array
    {
        try {
            $userModel = new UserModel();
            $dbUsers   = $userModel->orderBy('role', 'ASC')->orderBy('name', 'ASC')->findAll();

            if (! empty($dbUsers)) {
                return $dbUsers;
            }
        } catch (Throwable) {
            // Usa a lista padrão caso o banco não esteja disponível
        }

        return $this->defaultUsers;
    }

    private function verifyCredentials(string $login, string $password): ?array
    {
        // Tenta autenticar no banco de dados (tabela `users`)
        try {
            $userModel = new UserModel();
            $dbUser    = $userModel
                ->groupStart()
                    ->where('email', $login)
                    ->orWhere('name', $login)
                    ->orWhere('id', is_numeric($login) ? (int) $login : -1)
                ->groupEnd()
                ->first();

            if ($dbUser !== null) {
                if ($password !== '' && isset($dbUser['password_hashed']) && password_verify($password, $dbUser['password_hashed'])) {
                    return [
                        'id'       => $dbUser['id'],
                        'name'     => $dbUser['name'],
                        'username' => strtolower((string) $dbUser['name']),
                        'email'    => $dbUser['email'],
                        'role'     => $dbUser['role'] ?? 'user',
                        'avatar'   => $dbUser['avatar'] ?? null,
                    ];
                }

                return null;
            }
        } catch (Throwable) {
            // Caso o banco não esteja disponível, utiliza o fallback abaixo
        }

        foreach ($this->defaultUsers as $user) {
            $matchesLogin = strcasecmp($user['username'], $login) === 0
                || strcasecmp($user['email'], $login) === 0
                || strcasecmp($user['name'], $login) === 0
                || (string) $user['id'] === $login;

            if ($matchesLogin && $password !== '' && hash_equals($user['password'], $password)) {
                return $user;
            }
        }

        return null;
    }
}
