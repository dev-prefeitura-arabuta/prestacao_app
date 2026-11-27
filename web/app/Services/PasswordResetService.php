<?php

namespace App\Services;

use App\Models\GeneralSettings;
use App\Models\PasswordResetToken;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Fluxo de recuperação de senha por código de 6 dígitos (compartilhado entre Web e API mobile).
 *
 * - Nunca revela se o e-mail existe (o chamador deve responder com a mesma mensagem genérica).
 * - Códigos expiram em CODE_TTL_SECONDS.
 * - Após MAX_FAILED_ATTEMPTS erros para o mesmo e-mail, todos os códigos ativos são invalidados.
 */
class PasswordResetService
{
    public const CODE_TTL_SECONDS     = 90;
    public const MAX_FAILED_ATTEMPTS  = 5;
    public const VERIFIED_TTL_SECONDS = 600;

    public const GENERIC_SENT_MESSAGE = 'Se o e-mail informado estiver cadastrado, você receberá um código de 6 dígitos em instantes.';

    public function __construct(private readonly SmtpMailService $mailer)
    {
    }

    public static function normalizeEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    /**
     * Gera e envia um novo código. Retorna sucesso também quando o e-mail não existe
     * (para não permitir enumeração de contas).
     *
     * @return array{success: bool, error: ?string, expires_at: Carbon}
     */
    public function issueCode(string $email): array
    {
        $email     = self::normalizeEmail($email);
        $expiresAt = now()->addSeconds(self::CODE_TTL_SECONDS);

        $user = User::where('email', $email)->first();
        if ($user === null) {
            return ['success' => true, 'error' => null, 'expires_at' => $expiresAt];
        }

        try {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

            $this->invalidateActiveCodes((int) $user->id);

            PasswordResetToken::create([
                'user_id'    => (int) $user->id,
                'email'      => $user->email,
                'token_hash' => hash('sha256', $code),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'used_at'    => null,
                'created_at' => now()->format('Y-m-d H:i:s'),
            ]);

            RateLimiter::clear($this->attemptsKey($email));

            $settings = GeneralSettings::getSettings();
            $appName  = (string) ($settings['app_name'] ?? 'Sistema de Prestação de Contas');

            $html = view('emails.password_reset_code', [
                'appName'     => $appName,
                'companyName' => (string) ($settings['company_name'] ?? ''),
                'userName'    => (string) $user->name,
                'code'        => $code,
            ])->render();

            $sent = $this->mailer->send(
                (string) $user->email,
                (string) $user->name,
                "Código de Recuperação de Senha ({$code}) - {$appName}",
                $html,
                "Olá {$user->name}, seu código de 6 dígitos para redefinir a senha no {$appName} é: {$code} (válido por 1 minuto e 30 segundos)."
            );

            if (! $sent['success']) {
                Log::warning('Falha ao enviar código de recuperação de senha', ['error' => $sent['error']]);

                return ['success' => false, 'error' => 'Não foi possível enviar o e-mail de recuperação agora. Tente novamente mais tarde.', 'expires_at' => $expiresAt];
            }

            return ['success' => true, 'error' => null, 'expires_at' => $expiresAt];
        } catch (Throwable $e) {
            Log::error('Erro ao gerar código de recuperação de senha', ['exception' => $e]);

            return ['success' => false, 'error' => 'Erro ao processar a recuperação de senha. Tente novamente mais tarde.', 'expires_at' => $expiresAt];
        }
    }

    /**
     * Valida um código (não expirado e não utilizado). Conta tentativas inválidas por e-mail.
     *
     * @return array{token: ?PasswordResetToken, error: ?string}
     */
    public function verifyCode(string $email, string $code): array
    {
        $email = self::normalizeEmail($email);
        $code  = preg_replace('/\D/', '', $code) ?? '';
        $key   = $this->attemptsKey($email);

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_ATTEMPTS)) {
            return ['token' => null, 'error' => 'Muitas tentativas inválidas. Solicite um novo código.'];
        }

        $token = null;
        if ($email !== '' && strlen($code) === 6) {
            $token = PasswordResetToken::where('email', $email)
                ->where('token_hash', hash('sha256', $code))
                ->whereNull('used_at')
                ->where('expires_at', '>=', now()->format('Y-m-d H:i:s'))
                ->orderBy('id', 'desc')
                ->first();
        }

        if ($token === null) {
            RateLimiter::hit($key, 3600);

            if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_ATTEMPTS)) {
                $user = User::where('email', $email)->first();
                if ($user !== null) {
                    $this->invalidateActiveCodes((int) $user->id);
                }

                return ['token' => null, 'error' => 'Muitas tentativas inválidas. Solicite um novo código.'];
            }

            return ['token' => null, 'error' => 'Código inválido ou expirado. Verifique os 6 dígitos ou solicite um novo código.'];
        }

        return ['token' => $token, 'error' => null];
    }

    /**
     * Busca um token já validado (fluxo web em etapas) que ainda não foi utilizado.
     */
    public function findUnusedToken(int $tokenId, string $email): ?PasswordResetToken
    {
        return PasswordResetToken::where('id', $tokenId)
            ->where('email', self::normalizeEmail($email))
            ->whereNull('used_at')
            ->first();
    }

    /**
     * Redefine a senha e consome o token.
     */
    public function resetPassword(PasswordResetToken $token, string $newPassword): bool
    {
        $user = User::find((int) $token->user_id);
        if ($user === null) {
            return false;
        }

        $user->update(['password_hashed' => password_hash($newPassword, PASSWORD_DEFAULT)]);
        $token->update(['used_at' => now()->format('Y-m-d H:i:s')]);
        $this->invalidateActiveCodes((int) $user->id);
        RateLimiter::clear($this->attemptsKey((string) $user->email));

        return true;
    }

    public function latestExpiry(string $email): int
    {
        $latest = PasswordResetToken::where('email', self::normalizeEmail($email))
            ->whereNull('used_at')
            ->orderBy('id', 'desc')
            ->first();

        return ($latest !== null && ! empty($latest->expires_at)) ? (strtotime((string) $latest->expires_at) ?: 0) : 0;
    }

    private function invalidateActiveCodes(int $userId): void
    {
        PasswordResetToken::where('user_id', $userId)
            ->whereNull('used_at')
            ->update(['used_at' => now()->format('Y-m-d H:i:s')]);
    }

    private function attemptsKey(string $email): string
    {
        return 'password-reset-attempts:' . sha1(self::normalizeEmail($email));
    }
}
