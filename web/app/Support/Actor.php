<?php

namespace App\Support;

use App\Models\Accountability;
use App\Models\User;
use App\Models\UserAccountabilityPermission;

/**
 * Quem está executando a ação — independente do canal (sessão Web ou token da API mobile).
 */
final class Actor
{
    /** @var array<int, int>|null */
    private ?array $allowedItems = null;

    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly bool $isAdmin,
    ) {
    }

    public static function fromSession(): self
    {
        $role = (string) (session('user_role') ?? session('role') ?? 'user');

        return new self((int) session('user_id'), (string) (session('user_name') ?? 'Usuário'), $role === 'admin');
    }

    public static function fromUser(User $user): self
    {
        return new self((int) $user->id, (string) $user->name, ($user->role ?? 'user') === 'admin');
    }

    /**
     * Itens (1..24) em que o usuário pode atuar. Administradores podem atuar em todos.
     *
     * @return array<int, int>
     */
    public function allowedItems(): array
    {
        return $this->allowedItems ??= $this->isAdmin
            ? array_map('intval', array_keys(Accountability::getItems()))
            : UserAccountabilityPermission::getAllowedItemsForUser($this->id);
    }

    public function canAccessItem(int $itemNumber): bool
    {
        return $this->isAdmin || in_array($itemNumber, $this->allowedItems(), true);
    }
}
