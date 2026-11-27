<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe rotas Web (sessão) a administradores. Usar depois de 'auth.session'.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $role = (string) (session('user_role') ?? session('role') ?? 'user');

        if ($role !== 'admin') {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Acesso restrito a administradores.'], 403);
            }

            return redirect('/accountability')->with('error', 'Acesso restrito a administradores.');
        }

        return $next($request);
    }
}
