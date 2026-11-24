<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthSessionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('isLoggedIn')) {
            return redirect('/login')->with('error', 'Por favor, faça login para acessar esta página.');
        }

        return $next($request);
    }
}
