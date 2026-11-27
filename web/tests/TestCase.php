<?php

namespace Tests;

use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityItemStatus;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use App\Support\Actor;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    /** Diretório de storage temporário (uploads de teste não tocam storage/ real). */
    protected ?string $tempStorage = null;

    protected function tearDown(): void
    {
        if ($this->tempStorage !== null && is_dir($this->tempStorage)) {
            File::deleteDirectory($this->tempStorage);
        }

        parent::tearDown();
    }

    protected function useTempStorage(): string
    {
        $this->tempStorage = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'prestacao_tests_' . bin2hex(random_bytes(6));
        mkdir($this->tempStorage . '/app', 0775, true);
        $this->app->useStoragePath($this->tempStorage);

        return $this->tempStorage;
    }

    /**
     * @param array<int, int> $items Itens (1..24) liberados para o usuário
     */
    protected function makeUser(string $role = 'user', array $items = [], array $attrs = []): User
    {
        static $seq = 0;
        $seq++;

        $user = User::create(array_merge([
            'name'            => ($role === 'admin' ? 'Admin ' : 'Usuario ') . $seq,
            'email'           => "{$role}{$seq}_" . bin2hex(random_bytes(3)) . '@teste.local',
            'password_hashed' => password_hash('segredo123', PASSWORD_DEFAULT),
            'role'            => $role,
        ], $attrs));

        if (! empty($items)) {
            UserAccountabilityPermission::syncUserPermissions((int) $user->id, $items);
        }

        return $user;
    }

    /**
     * Simula o login Web (mesmas chaves de sessão gravadas por AuthController::attemptLogin).
     */
    protected function loginAs(User $user): static
    {
        return $this->withSession([
            'user_id'     => (int) $user->id,
            'user_name'   => (string) $user->name,
            'username'    => strtolower((string) $user->name),
            'user_email'  => (string) $user->email,
            'user_role'   => (string) ($user->role ?? 'user'),
            'role'        => (string) ($user->role ?? 'user'),
            'user_avatar' => $user->avatar ?? null,
            'is_developer' => $user->isDeveloper(),
            'isLoggedIn'  => true,
        ]);
    }

    /** Conta de desenvolvedor (administrador com acesso às configurações técnicas). */
    protected function makeDeveloper(array $attrs = []): User
    {
        $user = $this->makeUser('admin', [], $attrs);
        $user->forceFill(['is_developer' => true])->save();

        return $user->fresh();
    }

    /**
     * Cabeçalho Authorization com um token mobile válido para o usuário.
     *
     * @return array<string, string>
     */
    protected function mobileHeaders(User $user): array
    {
        $token = app(\App\Services\MobileTokenService::class)->issue($user);

        return ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];
    }

    protected function actorFor(User $user): Actor
    {
        return Actor::fromUser($user);
    }

    /**
     * @param array<int, int>|null $enabledItems null = todos os 24 itens
     */
    protected function makeAccountability(?array $enabledItems = null, array $attrs = []): Accountability
    {
        return Accountability::create(array_merge([
            'title'         => 'Prestação de teste',
            'status'        => 'aberta',
            'is_paused'     => false,
            'enabled_items' => $enabledItems === null ? null : json_encode($enabledItems),
        ], $attrs));
    }

    protected function makeAttachment(Accountability $acc, int $item, User $owner, string $name = 'doc.pdf'): AccountabilityAttachment
    {
        return AccountabilityAttachment::create([
            'accountability_id' => (int) $acc->id,
            'item_number'       => $item,
            'user_id'           => (int) $owner->id,
            'original_name'     => $name,
            'file_name'         => $name,
            'file_path'         => 'uploads/accountability/' . $acc->id . '/item_' . $item . '/' . $name,
            'file_ext'          => pathinfo($name, PATHINFO_EXTENSION),
            'file_size'         => 10,
            'created_at'        => now()->format('Y-m-d H:i:s'),
        ]);
    }

    protected function setItemStatus(Accountability $acc, int $item, string $status): void
    {
        AccountabilityItemStatus::setStatus((int) $acc->id, $item, $status, null, $status === 'rejeitado' ? 'motivo' : null);
    }
}
