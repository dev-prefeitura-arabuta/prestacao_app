<?php

namespace App\Http\Controllers;

use App\Models\Accountability;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class ProfileController extends Controller
{
    private array $allowedAvatarExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    public function index()
    {
        $userId = (int) session('user_id');
        $role   = session('user_role') ?? session('role') ?? 'user';

        $user         = null;
        $allowedItems = [];

        try {
            $userObj = User::find($userId);
            if ($userObj !== null) {
                $user = $userObj->toArray();
                session(['user_avatar' => $user['avatar'] ?? null]);
            }

            if ($role === 'admin') {
                $allowedItems = array_keys(Accountability::getItems());
            } else {
                $allowedItems = UserAccountabilityPermission::getAllowedItemsForUser($userId);
            }
        } catch (Throwable) {
            $user = null;
        }

        if ($user === null) {
            $user = [
                'id'         => $userId,
                'name'       => (string) session('user_name'),
                'email'      => (string) session('user_email'),
                'role'       => $role,
                'avatar'     => session('user_avatar'),
                'created_at' => null,
            ];
        }

        return view('profile.index', [
            'userName'            => session('user_name'),
            'username'            => session('username'),
            'userEmail'           => session('user_email'),
            'userRole'            => $role,
            'user'                => $user,
            'accountabilityItems' => Accountability::getItems(),
            'allowedItems'        => $allowedItems,
        ]);
    }

    public function update(Request $request)
    {
        $userId = (int) session('user_id');

        $validator = Validator::make($request->all(), [
            'name'             => 'required|min:3|max:100',
            'email'            => "required|email|max:100|unique:users,email,{$userId}",
            'new_password'     => 'nullable|min:6',
            'confirm_password' => 'same:new_password',
        ], [
            'name.required'         => 'O nome é obrigatório.',
            'name.min'              => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required'        => 'O e-mail é obrigatório.',
            'email.email'           => 'Informe um e-mail válido.',
            'email.unique'          => 'Este e-mail já está sendo utilizado por outro usuário.',
            'new_password.min'      => 'A nova senha deve ter pelo menos 6 caracteres.',
            'confirm_password.same' => 'A confirmação de senha não confere com a nova senha.',
        ]);

        if ($validator->fails()) {
            return redirect('/profile')->withInput()->with('errors_list', $validator->errors()->all());
        }

        try {
            $existing    = User::find($userId);
            $name        = trim((string) $request->input('name'));
            $email       = trim((string) $request->input('email'));
            $newPassword = (string) $request->input('new_password');
            $avatarPath  = $existing?->avatar ?? session('user_avatar');

            if ($request->hasFile('avatar')) {
                $avatarFile = $request->file('avatar');
                if (! $avatarFile->isValid()) {
                    return redirect('/profile')->withInput()->with('error', 'Não foi possível processar a imagem enviada.');
                }

                $ext = strtolower((string) $avatarFile->getClientOriginalExtension());
                if (! in_array($ext, $this->allowedAvatarExtensions, true)) {
                    return redirect('/profile')->withInput()->with('error', 'Formato de imagem inválido. Utilize JPG, JPEG, PNG ou WEBP.');
                }

                if ($avatarFile->getSize() > 5 * 1024 * 1024) {
                    return redirect('/profile')->withInput()->with('error', 'A foto de perfil deve ter no máximo 5 MB.');
                }

                $targetDir = storage_path('app/uploads/avatars');
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                if (! empty($avatarPath)) {
                    $oldFullPath = storage_path('app/' . $avatarPath);
                    if (is_file($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }

                $newName = 'user_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $avatarFile->move($targetDir, $newName);
                $avatarPath = 'uploads/avatars/' . $newName;
            }

            if ($existing !== null) {
                $data = [
                    'name'   => $name,
                    'email'  => $email,
                    'avatar' => $avatarPath,
                ];

                if ($newPassword !== '') {
                    $data['password_hashed'] = password_hash($newPassword, PASSWORD_DEFAULT);
                }

                $existing->update($data);
            }

            session([
                'user_name'   => $name,
                'user_email'  => $email,
                'user_avatar' => $avatarPath,
            ]);

            return redirect('/profile')->with('success', 'Seu perfil foi atualizado com sucesso!');
        } catch (Throwable $e) {
            return redirect('/profile')->withInput()->with('error', 'Erro ao atualizar perfil: ' . $e->getMessage());
        }
    }

    public function removeAvatar()
    {
        $userId = (int) session('user_id');

        try {
            $existing = User::find($userId);
            $oldPath  = $existing?->avatar ?? session('user_avatar');

            if (! empty($oldPath)) {
                $fullPath = storage_path('app/' . $oldPath);
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            if ($existing !== null) {
                $existing->update(['avatar' => null]);
            }

            session(['user_avatar' => null]);

            return redirect('/profile')->with('success', 'Foto de perfil removida com sucesso!');
        } catch (Throwable $e) {
            return redirect('/profile')->with('error', 'Erro ao remover foto de perfil: ' . $e->getMessage());
        }
    }

    public function avatar(int $id)
    {
        $avatarRel = null;

        try {
            $user      = User::find($id);
            $avatarRel = $user?->avatar;
        } catch (Throwable) {
            $avatarRel = null;
        }

        if (empty($avatarRel) && (int) session('user_id') === $id) {
            $avatarRel = session('user_avatar');
        }

        if (empty($avatarRel)) {
            abort(404);
        }

        $fullPath = storage_path('app/' . $avatarRel);
        if (! is_file($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath, [
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
