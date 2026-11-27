<?php

namespace App\Http\Controllers;

use App\Exceptions\ActionException;
use App\Models\Accountability;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use App\Services\ProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProfileController extends Controller
{
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

    public function update(Request $request, ProfileService $profiles)
    {
        $user = User::find((int) session('user_id'));
        if ($user === null) {
            return redirect('/login')->with('error', 'Usuário não encontrado. Faça login novamente.');
        }

        try {
            $user = $profiles->update($user, $request->only(['name', 'email', 'whatsapp', 'new_password', 'confirm_password']), $request->file('avatar'));
        } catch (ValidationException $e) {
            return redirect('/profile')->withInput()->with('errors_list', $e->validator->errors()->all());
        } catch (ActionException $e) {
            return redirect('/profile')->withInput()->with('error', $e->getMessage());
        } catch (Throwable $e) {
            report($e);
            return redirect('/profile')->withInput()->with('error', 'Erro ao atualizar perfil. Tente novamente.');
        }

        session([
            'user_name'   => $user->name,
            'user_email'  => $user->email,
            'user_avatar' => $user->avatar,
        ]);

        return redirect('/profile')->with('success', 'Seu perfil foi atualizado com sucesso!');
    }

    public function removeAvatar(ProfileService $profiles)
    {
        $user = User::find((int) session('user_id'));
        if ($user !== null) {
            try {
                $profiles->removeAvatar($user);
            } catch (Throwable $e) {
                report($e);
                return redirect('/profile')->with('error', 'Erro ao remover foto de perfil.');
            }
        }

        session(['user_avatar' => null]);

        return redirect('/profile')->with('success', 'Foto de perfil removida com sucesso!');
    }

    public function avatar(int $id, ProfileService $profiles)
    {
        try {
            return $profiles->avatarResponse($id);
        } catch (ActionException) {
            abort(404);
        }
    }
}
