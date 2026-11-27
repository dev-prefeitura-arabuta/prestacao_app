<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * API mobile: a conta de desenvolvedor não usa a área de negócio (prestações, anexos,
 * avaliação, usuários…). No app ela só acessa o próprio perfil. Usar depois de 'auth.mobile'.
 */
class MobileBusinessAccess
{
    public const MESSAGE = 'Conta de desenvolvedor: use o painel web para logs e configurações.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $user->isDeveloper()) {
            return response()->json(['success' => false, 'error' => self::MESSAGE, 'developer' => true], 403);
        }

        return $next($request);
    }
}
