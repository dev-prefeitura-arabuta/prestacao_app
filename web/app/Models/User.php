<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $table = 'users';

    // 'is_developer' fica fora de $fillable de propósito: só é alterado pelo comando app:developer.
    protected $fillable = [
        'name',
        'email',
        'whatsapp',
        'password_hashed',
        'role',
        'avatar',
        'notifications_enabled',
    ];

    protected $casts = [
        'is_developer' => 'boolean',
    ];

    public function isDeveloper(): bool
    {
        return (bool) ($this->is_developer ?? false);
    }

    /** Administradores do negócio (exclui contas de desenvolvedor). */
    public function scopeBusinessAdmins(Builder $query): Builder
    {
        return $query->where('role', 'admin')->where('is_developer', false);
    }

    /** Esconde contas de desenvolvedor de quem não é desenvolvedor. */
    public function scopeVisibleTo(Builder $query, bool $viewerIsDeveloper): Builder
    {
        return $viewerIsDeveloper ? $query : $query->where('is_developer', false);
    }
}
