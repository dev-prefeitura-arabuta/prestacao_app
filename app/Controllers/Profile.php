<?php

namespace App\Controllers;

use App\Models\AccountabilityModel;
use App\Models\UserAccountabilityPermissionModel;
use App\Models\UserModel;
use Throwable;

class Profile extends BaseController
{
    private array $allowedAvatarExtensions = ['jpg', 'jpeg', 'png', 'webp'];

    public function index()
    {
        $userId = (int) session()->get('user_id');
        $role   = session()->get('user_role') ?? session()->get('role') ?? 'user';

        $user         = null;
        $allowedItems = [];

        try {
            $userModel = new UserModel();
            $user      = $userModel->find($userId);

            if ($user !== null) {
                session()->set('user_avatar', $user['avatar'] ?? null);
            }

            if ($role === 'admin') {
                $allowedItems = array_keys(AccountabilityModel::getItems());
            } else {
                $permModel    = new UserAccountabilityPermissionModel();
                $allowedItems = $permModel->getAllowedItemsForUser($userId);
            }
        } catch (Throwable) {
            $user = null;
        }

        if ($user === null) {
            $user = [
                'id'         => $userId,
                'name'       => (string) session()->get('user_name'),
                'email'      => (string) session()->get('user_email'),
                'role'       => $role,
                'avatar'     => session()->get('user_avatar'),
                'created_at' => null,
            ];
        }

        return view('profile/index', [
            'userName'            => session()->get('user_name'),
            'username'            => session()->get('username'),
            'userEmail'           => session()->get('user_email'),
            'userRole'            => $role,
            'user'                => $user,
            'accountabilityItems' => AccountabilityModel::getItems(),
            'allowedItems'        => $allowedItems,
        ]);
    }

    public function update()
    {
        $userId = (int) session()->get('user_id');

        $rules = [
            'name'             => 'required|min_length[3]|max_length[100]',
            'email'            => "required|valid_email|max_length[100]|is_unique[users.email,id,{$userId}]",
            'new_password'     => 'permit_empty|min_length[6]',
            'confirm_password' => 'matches[new_password]',
        ];

        $messages = [
            'name' => [
                'required'   => 'O nome é obrigatório.',
                'min_length' => 'O nome deve ter pelo menos 3 caracteres.',
            ],
            'email' => [
                'required'    => 'O e-mail é obrigatório.',
                'valid_email' => 'Informe um e-mail válido.',
                'is_unique'   => 'Este e-mail já está sendo utilizado por outro usuário.',
            ],
            'new_password' => [
                'min_length' => 'A nova senha deve ter pelo menos 6 caracteres.',
            ],
            'confirm_password' => [
                'matches' => 'A confirmação de senha não confere com a nova senha.',
            ],
        ];

        if (! $this->validate($rules, $messages)) {
            return redirect()->to('/profile')->withInput()->with('errors', $this->validator->getErrors());
        }

        try {
            $userModel = new UserModel();
            $existing  = $userModel->find($userId);

            $name        = trim((string) $this->request->getPost('name'));
            $email       = trim((string) $this->request->getPost('email'));
            $newPassword = (string) $this->request->getPost('new_password');
            $avatarPath  = $existing['avatar'] ?? session()->get('user_avatar');

            $avatarFile = $this->request->getFile('avatar');
            if ($avatarFile !== null && $avatarFile->getError() !== UPLOAD_ERR_NO_FILE) {
                if (! $avatarFile->isValid() || $avatarFile->hasMoved()) {
                    return redirect()->to('/profile')->withInput()->with('error', 'Não foi possível processar a imagem enviada.');
                }

                $ext = strtolower((string) $avatarFile->getClientExtension());
                if (! in_array($ext, $this->allowedAvatarExtensions, true)) {
                    return redirect()->to('/profile')->withInput()->with('error', 'Formato de imagem inválido. Utilize JPG, JPEG, PNG ou WEBP.');
                }

                if ((int) $avatarFile->getSize() > 5 * 1024 * 1024) {
                    return redirect()->to('/profile')->withInput()->with('error', 'A foto de perfil deve ter no máximo 5 MB.');
                }

                $targetDir = WRITEPATH . 'uploads/avatars';
                if (! is_dir($targetDir)) {
                    mkdir($targetDir, 0775, true);
                }

                // Remove foto antiga se existir
                if (! empty($avatarPath)) {
                    $oldFullPath = WRITEPATH . $avatarPath;
                    if (is_file($oldFullPath)) {
                        @unlink($oldFullPath);
                    }
                }

                $newName = 'user_' . $userId . '_' . $avatarFile->getRandomName();
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

                $userModel->update($userId, $data);
            }

            session()->set([
                'user_name'   => $name,
                'user_email'  => $email,
                'user_avatar' => $avatarPath,
            ]);

            return redirect()->to('/profile')->with('success', 'Seu perfil foi atualizado com sucesso!');
        } catch (Throwable $e) {
            return redirect()->to('/profile')->withInput()->with('error', 'Erro ao atualizar perfil: ' . $e->getMessage());
        }
    }

    public function removeAvatar()
    {
        $userId = (int) session()->get('user_id');

        try {
            $userModel = new UserModel();
            $existing  = $userModel->find($userId);
            $oldPath   = $existing['avatar'] ?? session()->get('user_avatar');

            if (! empty($oldPath)) {
                $fullPath = WRITEPATH . $oldPath;
                if (is_file($fullPath)) {
                    @unlink($fullPath);
                }
            }

            if ($existing !== null) {
                $userModel->update($userId, ['avatar' => null]);
            }

            session()->set('user_avatar', null);

            return redirect()->to('/profile')->with('success', 'Foto de perfil removida com sucesso!');
        } catch (Throwable $e) {
            return redirect()->to('/profile')->with('error', 'Erro ao remover foto de perfil: ' . $e->getMessage());
        }
    }

    public function avatar(int $id)
    {
        $avatarRel = null;

        try {
            $userModel = new UserModel();
            $user      = $userModel->find($id);
            $avatarRel = $user['avatar'] ?? null;
        } catch (Throwable) {
            $avatarRel = null;
        }

        if (empty($avatarRel) && (int) session()->get('user_id') === $id) {
            $avatarRel = session()->get('user_avatar');
        }

        if (empty($avatarRel)) {
            return $this->response->setStatusCode(404);
        }

        $fullPath = WRITEPATH . $avatarRel;
        if (! is_file($fullPath)) {
            return $this->response->setStatusCode(404);
        }

        $ext     = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        $mimeMap = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'webp' => 'image/webp',
        ];
        $mimeType = $mimeMap[$ext] ?? (mime_content_type($fullPath) ?: 'image/jpeg');

        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Cache-Control', 'no-cache, must-revalidate')
            ->setBody((string) file_get_contents($fullPath));
    }
}
