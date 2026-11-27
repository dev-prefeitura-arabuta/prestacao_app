<?php

namespace App\Services;

use App\Exceptions\ActionException;
use App\Models\User;
use App\Rules\BrazilianPhone;
use App\Support\Phone;
use App\Support\UploadedFiles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Edição do próprio perfil e foto (avatar) — compartilhado entre Web e API mobile.
 */
class ProfileService
{
    public const AVATAR_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    public const AVATAR_MAX_BYTES  = 5 * 1024 * 1024;

    /**
     * @param array<string, mixed> $input name, email, whatsapp, new_password, confirm_password
     *
     * @throws ValidationException
     * @throws ActionException
     */
    public function update(User $user, array $input, ?UploadedFile $avatar = null): User
    {
        $userId = (int) $user->id;

        Validator::make($input, [
            'name'             => 'required|min:3|max:100',
            'email'            => "required|email|max:100|unique:users,email,{$userId}",
            'whatsapp'         => ['nullable', 'max:30', new BrazilianPhone()],
            'new_password'     => 'nullable|min:6',
            'confirm_password' => 'same:new_password',
        ], [
            'name.required'         => 'O nome é obrigatório.',
            'name.min'              => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required'        => 'O e-mail é obrigatório.',
            'email.email'           => 'Informe um e-mail válido.',
            'email.unique'          => 'Este e-mail já está sendo utilizado por outro usuário.',
            'whatsapp.max'          => 'O número de WhatsApp deve ter no máximo 30 caracteres.',
            'new_password.min'      => 'A nova senha deve ter pelo menos 6 caracteres.',
            'confirm_password.same' => 'A confirmação de senha não confere com a nova senha.',
        ])->validate();

        $avatarPath = $user->avatar;
        if ($avatar !== null) {
            $avatarPath = $this->storeAvatar($user, $avatar);
        }

        $whatsapp = trim((string) ($input['whatsapp'] ?? ''));
        $data     = [
            'name'     => trim((string) $input['name']),
            'email'    => strtolower(trim((string) $input['email'])),
            'whatsapp' => Phone::format($whatsapp),
            'avatar'   => $avatarPath,
        ];

        $newPassword = (string) ($input['new_password'] ?? '');
        if ($newPassword !== '') {
            $data['password_hashed'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        $user->update($data);

        return $user->refresh();
    }

    public function removeAvatar(User $user): User
    {
        UploadedFiles::deleteStored($user->avatar);
        $user->update(['avatar' => null]);

        return $user->refresh();
    }

    /**
     * @throws ActionException
     */
    public function avatarResponse(int $userId): BinaryFileResponse
    {
        $user = User::find($userId);
        if ($user === null || empty($user->avatar)) {
            throw ActionException::notFound('Foto não encontrada.');
        }

        return UploadedFiles::inlineResponse((string) $user->avatar, 'avatar_' . $userId);
    }

    /**
     * @throws ActionException
     */
    private function storeAvatar(User $user, UploadedFile $file): string
    {
        $ext = UploadedFiles::validate(
            $file,
            self::AVATAR_EXTENSIONS,
            self::AVATAR_MAX_BYTES,
            'Formato de imagem inválido. Utilize JPG, JPEG, PNG ou WEBP.'
        );

        $relDir = 'uploads/avatars';
        UploadedFiles::ensureDirectory(storage_path('app/' . $relDir));

        $newName = 'user_' . (int) $user->id . '_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $file->move(storage_path('app/' . $relDir), $newName);

        // Só remove a foto antiga depois que a nova foi gravada com sucesso
        UploadedFiles::deleteStored($user->avatar);

        return $relDir . '/' . $newName;
    }
}
