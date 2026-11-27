<?php

namespace App\Http\Controllers;

use App\Models\Accountability;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use App\Rules\BrazilianPhone;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class UsersController extends Controller
{
    public const PRIMARY_ADMIN_ROLE_MSG = 'O administrador principal sempre será administrador e não pode virar usuário comum.';

    public function index()
    {
        $role            = session('user_role') ?? session('role') ?? 'admin';
        $users           = [];
        $userPermissions = [];

        try {
            $users    = User::visibleTo($this->viewerIsDeveloper())->orderBy('id', 'asc')->get()->toArray();
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
            'primaryAdminId'  => User::primaryAdminId(),
        ]);
    }

    public function create()
    {
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
        $validator = Validator::make($request->all(), [
            'name'     => 'required|min:3|max:100',
            'email'    => 'required|email|max:100|unique:users,email',
            'whatsapp' => ['nullable', 'max:30', new BrazilianPhone()],
            'password' => 'required|min:6',
            'role'     => 'required|in:admin,user',
        ], [
            'name.required'     => 'O nome é obrigatório.',
            'name.min'          => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required'    => 'O e-mail é obrigatório.',
            'email.email'       => 'Informe um e-mail válido.',
            'email.unique'      => 'Este e-mail já está cadastrado.',
            'whatsapp.max'      => 'O número de WhatsApp deve ter no máximo 30 caracteres.',
            'password.required' => 'A senha é obrigatória.',
            'password.min'      => 'A senha deve ter pelo menos 6 caracteres.',
            'role.required'     => 'Selecione a permissão do usuário.',
            'role.in'           => 'Permissão inválida.',
        ]);

        if ($validator->fails()) {
            return back()->withInput()->with('errors_list', $validator->errors()->all());
        }

        try {
            $whatsapp = trim((string) $request->input('whatsapp', ''));

            $user = User::create([
                'name'            => trim((string) $request->input('name')),
                'email'           => strtolower(trim((string) $request->input('email'))),
                'whatsapp'        => Phone::format($whatsapp),
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
        try {
            $user = $this->findManageable($id);
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
            'isPrimaryAdmin'      => $user->isPrimaryAdmin(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|min:3|max:100',
            'email'    => "required|email|max:100|unique:users,email,{$id}",
            'whatsapp' => ['nullable', 'max:30', new BrazilianPhone()],
            'role'     => 'required|in:admin,user',
            'password' => 'nullable|min:6',
        ], [
            'name.required'  => 'O nome é obrigatório.',
            'name.min'       => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email'    => 'Informe um e-mail válido.',
            'email.unique'   => 'Este e-mail já está em uso por outro usuário.',
            'whatsapp.max'   => 'O número de WhatsApp deve ter no máximo 30 caracteres.',
            'role.required'  => 'Selecione a permissão do usuário.',
            'password.min'   => 'A nova senha deve ter pelo menos 6 caracteres.',
        ]);

        if ($validator->fails()) {
            return back()->withInput()->with('errors_list', $validator->errors()->all());
        }

        try {
            $user = $this->findManageable($id);
            if ($user === null) {
                return redirect('/users')->with('error', 'Usuário não encontrado.');
            }

            $whatsapp = trim((string) $request->input('whatsapp', ''));

            $data = [
                'name'     => trim((string) $request->input('name')),
                'email'    => strtolower(trim((string) $request->input('email'))),
                'whatsapp' => Phone::format($whatsapp),
                'role'     => (string) $request->input('role'),
            ];

            if ($user->isPrimaryAdmin() && $data['role'] !== 'admin') {
                return back()->withInput()->with('error', self::PRIMARY_ADMIN_ROLE_MSG);
            }

            // Conta de desenvolvedor é sempre administrador
            if ($user->isDeveloper()) {
                $data['role'] = 'admin';
            }

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
        if ((int) session('user_id') === $id) {
            return redirect('/users')->with('error', 'Você não pode excluir sua própria conta enquanto estiver logado.');
        }

        try {
            $user = $this->findManageable($id);
            if ($user === null) {
                return redirect('/users')->with('error', 'Usuário não encontrado.');
            }

            if ($user->isPrimaryAdmin()) {
                return redirect('/users')->with('error', 'O administrador principal não pode ser excluído.');
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
        $newRole = (string) $request->input('role');
        if (! in_array($newRole, ['admin', 'user'], true)) {
            return back()->with('error', 'Permissão inválida.');
        }

        try {
            $user = $this->findManageable($id);
            if ($user === null) {
                return back()->with('error', 'Usuário não encontrado.');
            }

            if ($user->isDeveloper() && $newRole !== 'admin') {
                return back()->with('error', 'A conta de desenvolvedor precisa ser administrador.');
            }

            if ($user->isPrimaryAdmin() && $newRole !== 'admin') {
                return back()->with('error', self::PRIMARY_ADMIN_ROLE_MSG);
            }

            $user->update(['role' => $newRole]);

            return redirect('/users')->with('success', 'Permissão de "' . $user->name . '" atualizada com sucesso!');
        } catch (Throwable) {
            return back()->with('error', 'Não foi possível atualizar a permissão no banco de dados.');
        }
    }

    private function viewerIsDeveloper(): bool
    {
        return (bool) session('is_developer');
    }

    /**
     * Busca um usuário que o administrador logado pode gerenciar.
     * Contas de desenvolvedor só são visíveis/editáveis por outro desenvolvedor.
     */
    private function findManageable(int $id): ?User
    {
        return User::visibleTo($this->viewerIsDeveloper())->find($id);
    }
}
