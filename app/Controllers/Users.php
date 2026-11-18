<?php

namespace App\Controllers;

use App\Models\AccountabilityModel;
use App\Models\UserAccountabilityPermissionModel;
use App\Models\UserModel;
use Throwable;

class Users extends BaseController
{
    private function ensureAdmin()
    {
        $role = session()->get('user_role') ?? session()->get('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Acesso restrito a administradores.');
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $role            = session()->get('user_role') ?? session()->get('role') ?? 'admin';
        $users           = [];
        $userPermissions = [];

        try {
            $userModel = new UserModel();
            $users     = $userModel->orderBy('id', 'ASC')->findAll();

            $permModel = new UserAccountabilityPermissionModel();
            $allPerms  = $permModel->orderBy('item_number', 'ASC')->findAll();
            foreach ($allPerms as $p) {
                $uid                     = (int) $p['user_id'];
                $userPermissions[$uid][] = (int) $p['item_number'];
            }
        } catch (Throwable) {
            $users = [
                [
                    'id'         => 1,
                    'name'       => 'Admin',
                    'email'      => 'admin@admin.com',
                    'role'       => 'admin',
                    'created_at' => date('Y-m-d H:i:s'),
                ],
            ];
        }

        return view('users/index', [
            'userName'        => session()->get('user_name'),
            'username'        => session()->get('username'),
            'userEmail'       => session()->get('user_email'),
            'userRole'        => $role,
            'users'           => $users,
            'userPermissions' => $userPermissions,
        ]);
    }

    public function create()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        return view('users/form', [
            'userName'            => session()->get('user_name'),
            'username'            => session()->get('username'),
            'userEmail'           => session()->get('user_email'),
            'userRole'            => session()->get('user_role') ?? 'admin',
            'isEdit'              => false,
            'user'                => null,
            'accountabilityItems' => AccountabilityModel::getItems(),
            'allowedItems'        => [],
        ]);
    }

    public function store()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $rules = [
            'name'     => 'required|min_length[3]|max_length[100]',
            'email'    => 'required|valid_email|max_length[100]|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'role'     => 'required|in_list[admin,user]',
        ];

        $messages = [
            'name' => [
                'required'   => 'O nome é obrigatório.',
                'min_length' => 'O nome deve ter pelo menos 3 caracteres.',
            ],
            'email' => [
                'required'    => 'O e-mail é obrigatório.',
                'valid_email' => 'Informe um e-mail válido.',
                'is_unique'   => 'Este e-mail já está cadastrado.',
            ],
            'password' => [
                'required'   => 'A senha é obrigatória.',
                'min_length' => 'A senha deve ter pelo menos 6 caracteres.',
            ],
            'role' => [
                'required' => 'Selecione a permissão do usuário.',
                'in_list'  => 'Permissão inválida.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $userModel = new UserModel();
            $newUserId = $userModel->insert([
                'name'            => trim((string) $this->request->getPost('name')),
                'email'           => trim((string) $this->request->getPost('email')),
                'password_hashed' => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
                'role'            => (string) $this->request->getPost('role'),
            ], true);

            $allowedItems = (array) ($this->request->getPost('allowed_items') ?? []);
            $permModel    = new UserAccountabilityPermissionModel();
            $permModel->syncUserPermissions((int) $newUserId, $allowedItems);

            return redirect()->to('/users')->with('success', 'Usuário e permissões de anexos cadastrados com sucesso!');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Erro ao cadastrar usuário: ' . $e->getMessage());
        }
    }

    public function edit(int $id)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $allowedItems = [];

        try {
            $userModel = new UserModel();
            $user      = $userModel->find($id);

            if ($user === null) {
                return redirect()->to('/users')->with('error', 'Usuário não encontrado.');
            }

            $permModel    = new UserAccountabilityPermissionModel();
            $allowedItems = $permModel->getAllowedItemsForUser($id);
        } catch (Throwable) {
            return redirect()->to('/users')->with('error', 'Não foi possível carregar o usuário no banco de dados.');
        }

        return view('users/form', [
            'userName'            => session()->get('user_name'),
            'username'            => session()->get('username'),
            'userEmail'           => session()->get('user_email'),
            'userRole'            => session()->get('user_role') ?? 'admin',
            'isEdit'              => true,
            'user'                => $user,
            'accountabilityItems' => AccountabilityModel::getItems(),
            'allowedItems'        => $allowedItems,
        ]);
    }

    public function update(int $id)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $rules = [
            'name'     => 'required|min_length[3]|max_length[100]',
            'email'    => "required|valid_email|max_length[100]|is_unique[users.email,id,{$id}]",
            'role'     => 'required|in_list[admin,user]',
            'password' => 'permit_empty|min_length[6]',
        ];

        $messages = [
            'name' => [
                'required'   => 'O nome é obrigatório.',
                'min_length' => 'O nome deve ter pelo menos 3 caracteres.',
            ],
            'email' => [
                'required'    => 'O e-mail é obrigatório.',
                'valid_email' => 'Informe um e-mail válido.',
                'is_unique'   => 'Este e-mail já está em uso por outro usuário.',
            ],
            'role' => [
                'required' => 'Selecione a permissão do usuário.',
                'in_list'  => 'Permissão inválida.',
            ],
            'password' => [
                'min_length' => 'A nova senha deve ter pelo menos 6 caracteres.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $userModel = new UserModel();
            $user      = $userModel->find($id);

            if ($user === null) {
                return redirect()->to('/users')->with('error', 'Usuário não encontrado.');
            }

            $data = [
                'name'  => trim((string) $this->request->getPost('name')),
                'email' => trim((string) $this->request->getPost('email')),
                'role'  => (string) $this->request->getPost('role'),
            ];

            $newPassword = (string) $this->request->getPost('password');
            if ($newPassword !== '') {
                $data['password_hashed'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $userModel->update($id, $data);

            $allowedItems = (array) ($this->request->getPost('allowed_items') ?? []);
            $permModel    = new UserAccountabilityPermissionModel();
            $permModel->syncUserPermissions($id, $allowedItems);

            if ((int) session()->get('user_id') === $id) {
                session()->set([
                    'user_name'  => $data['name'],
                    'user_email' => $data['email'],
                    'user_role'  => $data['role'],
                    'role'       => $data['role'],
                ]);
            }

            return redirect()->to('/users')->with('success', 'Usuário e permissões de campos atualizados com sucesso!');
        } catch (Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
        }
    }

    public function delete(int $id)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if ((int) session()->get('user_id') === $id) {
            return redirect()->to('/users')->with('error', 'Você não pode excluir sua própria conta enquanto estiver logado.');
        }

        try {
            $userModel = new UserModel();
            $user      = $userModel->find($id);

            if ($user === null) {
                return redirect()->to('/users')->with('error', 'Usuário não encontrado.');
            }

            $permModel = new UserAccountabilityPermissionModel();
            $permModel->where('user_id', $id)->delete();

            $userModel->delete($id);

            return redirect()->to('/users')->with('success', 'Usuário "' . $user['name'] . '" excluído com sucesso!');
        } catch (Throwable $e) {
            return redirect()->to('/users')->with('error', 'Erro ao excluir usuário: ' . $e->getMessage());
        }
    }

    public function updateRole(int $id)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $newRole = (string) $this->request->getPost('role');
        if (! in_array($newRole, ['admin', 'user'], true)) {
            return redirect()->back()->with('error', 'Permissão inválida.');
        }

        try {
            $userModel = new UserModel();
            $user      = $userModel->find($id);

            if ($user === null) {
                return redirect()->back()->with('error', 'Usuário não encontrado.');
            }

            $userModel->update($id, ['role' => $newRole]);

            return redirect()->to('/users')->with('success', 'Permissão de "' . $user['name'] . '" atualizada com sucesso!');
        } catch (Throwable) {
            return redirect()->back()->with('error', 'Não foi possível atualizar a permissão no banco de dados.');
        }
    }
}
