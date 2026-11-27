<?php

namespace App\Http\Middleware;

use App\Services\MobileTokenService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autenticação da API mobile por token Bearer.
 *
 * Uso: ->middleware('auth.mobile') ou ->middleware('auth.mobile:admin').
 * O usuário autenticado fica disponível em $request->user().
 */
class MobileTokenAuth
{
    /** Rotas (por nome) que podem receber o token via ?token= (imagens carregadas pelo app). */
    private const QUERY_TOKEN_ROUTES = ['mobile.attachments.preview'];

    public function __construct(private readonly MobileTokenService $tokens)
    {
    }

    public function handle(Request $request, Closure $next, ?string $role = null): Response
    {
        $allowQuery = $request->isMethod('GET') && $request->routeIs(...self::QUERY_TOKEN_ROUTES);
        $user       = $this->tokens->resolve(MobileTokenService::extractToken($request, $allowQuery));

        if ($user === null) {
            return response()->json(['success' => false, 'error' => 'Não autenticado.'], 401);
        }

        if ($role === 'admin' && ($user->role ?? 'user') !== 'admin') {
            return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
        }

        $request->setUserResolver(static fn () => $user);

        return $next($request);
    }
}
