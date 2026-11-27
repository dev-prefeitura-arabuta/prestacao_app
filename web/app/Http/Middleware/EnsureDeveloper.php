<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe as configurações técnicas (SMTP, WhatsApp, IA) à conta de desenvolvedor.
 * Usar depois de 'auth.session' e 'admin'.
 */
class EnsureDeveloper
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('is_developer')) {
            $message = 'Estas configurações técnicas são gerenciadas pelo desenvolvedor do sistema.';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'error' => $message], 403);
            }

            return redirect('/settings?tab=general')->with('error', $message);
        }

        return $next($request);
    }
}
