<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    private function configureRateLimiting(): void
    {
        $tooMany = static function (Request $request, array $headers) {
            $retry   = (int) ($headers['Retry-After'] ?? 60);
            $message = "Muitas tentativas. Aguarde {$retry} segundo(s) e tente novamente.";

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['success' => false, 'error' => $message], 429, $headers);
            }

            return back()->withInput($request->except(['password', 'confirm_password', 'code']))->with('error', $message);
        };

        $emailKey = static fn (Request $request): string => strtolower(trim((string) ($request->input('login') ?? $request->input('email') ?? '')));

        // Login (web e mobile): 5 tentativas/min por e-mail+IP e 20/min por IP.
        RateLimiter::for('login', static fn (Request $request) => [
            Limit::perMinute(5)->by('login:' . $emailKey($request) . '|' . $request->ip())->response($tooMany),
            Limit::perMinute(20)->by('login-ip:' . $request->ip())->response($tooMany),
        ]);

        // Recuperação de senha (envio, reenvio, validação do código e redefinição).
        RateLimiter::for('password-reset', static fn (Request $request) => [
            Limit::perMinute(5)->by('pwreset:' . $emailKey($request) . '|' . $request->ip())->response($tooMany),
            Limit::perHour(30)->by('pwreset-ip:' . $request->ip())->response($tooMany),
        ]);
    }
}
