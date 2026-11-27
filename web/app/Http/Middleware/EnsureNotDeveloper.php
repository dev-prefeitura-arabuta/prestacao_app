<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Área de negócio (prestações, avaliação, históricos): a conta de desenvolvedor
 * não participa do fluxo e é levada aos Logs do Sistema.
 */
class EnsureNotDeveloper
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session('is_developer')) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => 'Área exclusiva da administração do sistema.'], 403);
            }

            return redirect('/logs');
        }

        return $next($request);
    }
}
