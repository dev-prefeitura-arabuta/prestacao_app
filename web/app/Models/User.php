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

    // 'is_primary_admin' também fica fora de $fillable: só a migração o define.
    protected $casts = [
        'is_developer'     => 'boolean',
        'is_primary_admin' => 'boolean',
    ];

    public function isDeveloper(): bool
    {
        return (bool) ($this->is_developer ?? false);
    }

    /**
     * Id do administrador principal: o marcado no banco. Numa instalação nova (ninguém marcado),
     * marca o administrador mais antigo que não seja desenvolvedor, para ele ficar fixo dali em diante
     * (promover depois um usuário mais antigo não muda quem é o principal).
     */
    public static function primaryAdminId(): ?int
    {
        $flagged = static::where('is_primary_admin', true)->orderBy('id')->value('id');
        if ($flagged !== null) {
            return (int) $flagged;
        }

        $oldest = static::where('role', 'admin')->where('is_developer', false)->orderBy('id')->value('id');
        if ($oldest === null) {
            return null;
        }

        static::whereKey($oldest)->update(['is_primary_admin' => true]);

        return (int) $oldest;
    }

    /** O administrador principal nunca deixa de ser administrador nem pode ser excluído. */
    public function isPrimaryAdmin(): bool
    {
        return $this->id !== null && (int) $this->id === static::primaryAdminId();
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
