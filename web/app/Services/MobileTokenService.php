<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Tokens opacos da API mobile. Apenas o hash SHA-256 do token é armazenado (no cache).
 */
class MobileTokenService
{
    private const TTL_DAYS = 7;

    public function issue(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        Cache::put($this->key($token), (int) $user->id, now()->addDays(self::TTL_DAYS));

        return $token;
    }

    public function resolve(string $token): ?User
    {
        if ($token === '') {
            return null;
        }

        $userId = Cache::get($this->key($token));

        return $userId ? User::find((int) $userId) : null;
    }

    public function revoke(string $token): void
    {
        if ($token !== '') {
            Cache::forget($this->key($token));
        }
    }

    /**
     * Lê o token do cabeçalho Authorization. O parâmetro ?token= só é aceito quando
     * $allowQuery = true (rotas de imagem carregadas diretamente pelo <Image> do app).
     */
    public static function extractToken(Request $request, bool $allowQuery = false): string
    {
        $bearer = (string) $request->bearerToken();
        if ($bearer !== '') {
            return trim($bearer);
        }

        return $allowQuery ? trim((string) $request->query('token', '')) : '';
    }

    private function key(string $token): string
    {
        return 'mobile_token_' . hash('sha256', $token);
    }
}
