<?php

namespace App\Http\Controllers;

use App\Models\Accountability;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UsersController extends Controller
{
    private function ensureAdmin()
    {
        $role = session('user_role') ?? session('role') ?? 'user';

        if ($role !== 'admin') {
            return redirect('/accountability')->with('error', 'Acesso restrito a administradores.');
        }

        return null;
    }

    public function index()
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $role            = session('user_role') ?? session('role') ?? 'admin';
        $users           = [];
        $userPermissions = [];

        try {
            $users    = User::orderBy('id', 'asc')->get()->toArray();
            $allPerms = UserAccountabilityPermission::orderBy('item_number', 'asc')->get()->toArray();
            foreach ($allPerms as $p) {
                $uid                     = (int) $p['user_id'];
                $userPermissions[$uid][] = (int) $p['item_number'];
            }
        } catch (Throwable) {
            $users = [];
        }

        return view('users.index', [
            'userName'        => session('user_name'),
            'username'        => session('username'),
            'userEmail'       => session('user_email'),
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

        return view('users.form', [
            'userName'            => session('user_name'),
            'username'            => session('username'),
            'userEmail'           => session('user_email'),
            'userRole'            => session('user_role') ?? 'admin',
            'isEdit'              => false,
            'user'                => null,
            'accountabilityItems' => Accountability::getItems(),
            'allowedItems'        => [],
        ]);
    }

    public function store(Request $request)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:100|unique:users,email',
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,user',
        ], [
            'name.required'     => 'O nome é obrigatório.',
            'name.min'          => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required'    => 'O e-mail é obrigatório.',
            'email.email'       => 'Informe um e-mail válido.',
            'email.unique'      => 'Este e-mail já está cadastrado.',
            'password.required' => 'A senha é obrigatória.',
            'password.min'      => 'A senha deve ter pelo menos 6 caracteres.',
            'role.required'     => 'Selecione a permissão do usuário.',
            'role.in'           => 'Permissão inválida.',
        ]);

        if ($validator->fails()) {
            return back()->withInput()->with('errors_list', $validator->errors()->all());
        }

        try {
            $user = User::create([
                'name'            => trim((string) $request->input('name')),
                'email'           => trim((string) $request->input('email')),
                'password_hashed' => password_hash((string) $request->input('password'), PASSWORD_DEFAULT),
                'role'            => (string) $request->input('role'),
            ]);

            $allowedItems = (array) ($request->input('allowed_items') ?? []);
            UserAccountabilityPermission::syncUserPermissions((int) $user->id, $allowedItems);

            return redirect('/users')->with('success', 'Usuário e permissões de anexos cadastrados com sucesso!');
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Erro ao cadastrar usuário: ' . $e->getMessage());
        }
    }

    public function edit(int $id)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        try {
            $user = User::find($id);
            if ($user === null) {
                return redirect('/users')->with('error', 'Usuário não encontrado.');
            }
            $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($id);
        } catch (Throwable) {
            return redirect('/users')->with('error', 'Não foi possível carregar o usuário no banco de dados.');
        }

        return view('users.form', [
            'userName'            => session('user_name'),
            'username'            => session('username'),
            'userEmail'           => session('user_email'),
            'userRole'            => session('user_role') ?? 'admin',
            'isEdit'              => true,
            'user'                => $user->toArray(),
            'accountabilityItems' => Accountability::getItems(),
            'allowedItems'        => $allowedItems,
        ]);
    }

    public function update(Request $request, int $id)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $validator = Validator::make($request->all(), [
            'name'     => 'required|min:3|max:100',
            'email'    => "required|email|max:100|unique:users,email,{$id}",
            'role'     => 'required|in:admin,user',
            'password' => 'nullable|min:6',
        ], [
            'name.required'  => 'O nome é obrigatório.',
            'name.min'       => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email'    => 'Informe um e-mail válido.',
            'email.unique'   => 'Este e-mail já está em uso por outro usuário.',
            'role.required'  => 'Selecione a permissão do usuário.',
            'password.min'   => 'A nova senha deve ter pelo menos 6 caracteres.',
        ]);

        if ($validator->fails()) {
            return back()->withInput()->with('errors_list', $validator->errors()->all());
        }

        try {
            $user = User::find($id);
            if ($user === null) {
                return redirect('/users')->with('error', 'Usuário não encontrado.');
            }

            $data = [
                'name'  => trim((string) $request->input('name')),
                'email' => trim((string) $request->input('email')),
                'role'  => (string) $request->input('role'),
            ];

            $newPassword = (string) $request->input('password');
            if ($newPassword !== '') {
                $data['password_hashed'] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $user->update($data);

            $allowedItems = (array) ($request->input('allowed_items') ?? []);
            UserAccountabilityPermission::syncUserPermissions($id, $allowedItems);

            if ((int) session('user_id') === $id) {
                session([
                    'user_name'  => $data['name'],
                    'user_email' => $data['email'],
                    'user_role'  => $data['role'],
                    'role'       => $data['role'],
                ]);
            }

            return redirect('/users')->with('success', 'Usuário e permissões de campos atualizados com sucesso!');
        } catch (Throwable $e) {
            return back()->withInput()->with('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
        }
    }

    public function delete(int $id)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        if ((int) session('user_id') === $id) {
            return redirect('/users')->with('error', 'Você não pode excluir sua própria conta enquanto estiver logado.');
        }

        try {
            $user = User::find($id);
            if ($user === null) {
                return redirect('/users')->with('error', 'Usuário não encontrado.');
            }

            UserAccountabilityPermission::where('user_id', $id)->delete();
            $name = $user->name;
            $user->delete();

            return redirect('/users')->with('success', 'Usuário "' . $name . '" excluído com sucesso!');
        } catch (Throwable $e) {
            return redirect('/users')->with('error', 'Erro ao excluir usuário: ' . $e->getMessage());
        }
    }

    public function updateRole(Request $request, int $id)
    {
        if ($redirect = $this->ensureAdmin()) {
            return $redirect;
        }

        $newRole = (string) $request->input('role');
        if (! in_array($newRole, ['admin', 'user'], true)) {
            return back()->with('error', 'Permissão inválida.');
        }

        try {
            $user = User::find($id);
            if ($user === null) {
                return back()->with('error', 'Usuário não encontrado.');
            }

            $user->update(['role' => $newRole]);

            return redirect('/users')->with('success', 'Permissão de "' . $user->name . '" atualizada com sucesso!');
        } catch (Throwable) {
            return back()->with('error', 'Não foi possível atualizar a permissão no banco de dados.');
        }
    }
}
