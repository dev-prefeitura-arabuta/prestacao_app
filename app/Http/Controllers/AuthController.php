<?php

namespace App\Http\Controllers;

use App\Models\GeneralSettings;
use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\SmtpMailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    private array $defaultUsers = [
        [
            'id'       => 1,
            'name'     => 'Administrador Geral',
            'username' => 'admin',
            'email'    => 'admin@admin.com',
            'password' => '123456',
            'role'     => 'admin',
        ],
        [
            'id'       => 2,
            'name'     => 'Carlos Eduardo (Controladoria)',
            'username' => 'controladoria',
            'email'    => 'controladoria@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'admin',
        ],
        [
            'id'       => 3,
            'name'     => 'Fernanda Souza (Contabilidade)',
            'username' => 'contabilidade',
            'email'    => 'contabilidade@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
        [
            'id'       => 4,
            'name'     => 'Marcos Oliveira (Sec. Educação)',
            'username' => 'educacao',
            'email'    => 'educacao@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
        [
            'id'       => 5,
            'name'     => 'Juliana Lima (Sec. Saúde)',
            'username' => 'saude',
            'email'    => 'saude@arabuta.sc.gov.br',
            'password' => '123456',
            'role'     => 'user',
        ],
    ];

    public function login()
    {
        if (session('isLoggedIn')) {
            return redirect('/accountability');
        }

        $users = $this->getAvailableUsers();

        return view('auth.login', [
            'users' => $users,
        ]);
    }

    public function attemptLogin(Request $request)
    {
        $request->validate([
            'login'    => 'required',
            'password' => 'required',
        ], [
            'login.required'    => 'Selecione um usuário.',
            'password.required' => 'Informe a senha.',
        ]);

        $login    = trim((string) $request->input('login'));
        $password = (string) $request->input('password');

        $authenticatedUser = $this->verifyCredentials($login, $password);

        if ($authenticatedUser === null) {
            return back()->withInput()->with('error', 'Credenciais inválidas.');
        }

        $request->session()->regenerate();
        session([
            'user_id'     => $authenticatedUser['id'],
            'user_name'   => $authenticatedUser['name'],
            'username'    => $authenticatedUser['username'] ?? $authenticatedUser['name'],
            'user_email'  => $authenticatedUser['email'],
            'user_role'   => $authenticatedUser['role'] ?? 'user',
            'role'        => $authenticatedUser['role'] ?? 'user',
            'user_avatar' => $authenticatedUser['avatar'] ?? null,
            'isLoggedIn'  => true,
        ]);

        return redirect('/accountability');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showForgotPassword()
    {
        if (session('isLoggedIn')) {
            return redirect('/accountability');
        }

        session()->forget(['reset_verified_token_id', 'reset_verified_email', 'reset_verified_at']);

        return view('auth.forgot_password');
    }

    public function sendResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'Informe o seu e-mail cadastrado.',
            'email.email'    => 'Informe um endereço de e-mail válido.',
        ]);

        if ($validator->fails()) {
            return back()->withInput()->with('error', $validator->errors()->first());
        }

        $email = strtolower(trim((string) $request->input('email')));

        return $this->issueAndSendResetCode($email, '/forgot-password');
    }

    public function resendResetCode(Request $request)
    {
        $email = strtolower(trim((string) ($request->input('email') ?: session('reset_email', ''))));
        if ($email === '') {
            return redirect('/forgot-password')->with('error', 'Informe o seu e-mail para solicitar um novo código.');
        }

        $expiresAtTs = (int) session('reset_code_expires_at', 0);
        if ($expiresAtTs > time()) {
            $remaining = $expiresAtTs - time();
            return redirect('/reset-password')->with('error', "Aguarde {$remaining} segundo(s) para solicitar um novo código.");
        }

        return $this->issueAndSendResetCode($email, '/reset-password');
    }

    private function issueAndSendResetCode(string $email, string $errorRedirect)
    {
        $user = User::where('email', $email)->first();
        if ($user === null) {
            return redirect($errorRedirect)->withInput()->with('error', 'Não encontramos nenhuma conta cadastrada com este e-mail.');
        }

        try {
            // Gera código numérico seguro de 6 dígitos
            $code      = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = now()->addSeconds(90);

            // Invalida códigos anteriores ainda não utilizados deste usuário
            PasswordResetToken::where('user_id', $user->id)
                ->whereNull('used_at')
                ->update(['used_at' => now()->format('Y-m-d H:i:s')]);

            PasswordResetToken::create([
                'user_id'    => (int) $user->id,
                'email'      => $user->email,
                'token_hash' => hash('sha256', $code),
                'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
                'used_at'    => null,
                'created_at' => now()->format('Y-m-d H:i:s'),
            ]);

            $genSettings = GeneralSettings::getSettings();
            $appName     = $genSettings['app_name'] ?? 'Sistema Patrimonial';
            $companyName = $genSettings['company_name'] ?? 'Município de Arabutã';

            $htmlBody = '
                <div style="font-family: Arial, Helvetica, sans-serif; max-width: 520px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                    <div style=" padding: 20px 24px; color: #ffffff;">
                        <h2 style="margin: 0; font-size: 18px;">' . e($appName) . '</h2>
                        <p style="margin: 4px 0 0; font-size: 13px; color: #94a3b8;">' . e($companyName) . '</p>
                    </div>
                    <div style="padding: 24px; color: #1e293b;">
                        <p style="margin: 0 0 12px;">Olá, <strong>' . e($user->name) . '</strong>!</p>
                        <p style="margin: 0 0 18px; line-height: 1.5; font-size: 14px; color: #475569;">
                            Recebemos uma solicitação para redefinir a senha da sua conta. Utilize o código de 6 dígitos abaixo:
                        </p>
                        <div style="text-align: center; margin: 22px 0;">
                            <span style="display: inline-block; font-size: 32px; font-weight: 800; letter-spacing: 8px; color: #dd4814; background: #fff7ed; border: 1px solid #fed7aa; padding: 14px 26px; border-radius: 8px;">'
                . $code .
                '</span>
                        </div>
                        <p style="margin: 16px 0 0; font-size: 13px; color: #64748b; line-height: 1.5;">
                            Este código é válido por <strong>1 minuto e 30 segundos</strong>. Caso você não tenha solicitado a alteração de senha, ignore este e-mail.
                        </p>
                    </div>
                </div>';

            $mailer = new SmtpMailService();
            $sent   = $mailer->send(
                $user->email,
                (string) $user->name,
                "Código de Recuperação de Senha ({$code}) - {$appName}",
                $htmlBody,
                "Olá {$user->name}, seu código de 6 dígitos para redefinir a senha no {$appName} é: {$code} (válido por 1 minuto e 30 segundos)."
            );

            if (! $sent['success']) {
                return redirect($errorRedirect)->withInput()->with('error', $sent['error'] ?: 'Não foi possível enviar o e-mail de recuperação.');
            }

            session()->forget(['reset_verified_token_id', 'reset_verified_email', 'reset_verified_at']);
            session([
                'reset_email'           => $user->email,
                'reset_code_expires_at' => $expiresAt->timestamp,
            ]);

            return redirect('/reset-password')->with('success', 'Código de 6 dígitos enviado para ' . $user->email . '! Você tem 1 minuto e 30 segundos para validá-lo.');
        } catch (Throwable $e) {
            return redirect($errorRedirect)->withInput()->with('error', 'Erro ao processar recuperação de senha: ' . $e->getMessage());
        }
    }

    public function showResetPassword(Request $request)
    {
        if (session('isLoggedIn')) {
            return redirect('/accountability');
        }

        $email = strtolower(trim((string) ($request->query('email') ?: session('reset_email') ?: old('email', ''))));
        if ($email === '') {
            return redirect('/forgot-password')->with('error', 'Informe primeiro o seu e-mail para receber o código de verificação.');
        }

        // Verifica se a Etapa 2 (código) já foi validada nesta sessão
        $verifiedTokenId = (int) session('reset_verified_token_id', 0);
        $verifiedEmail   = strtolower((string) session('reset_verified_email', ''));
        $verifiedAt      = (int) session('reset_verified_at', 0);
        $step            = 2;

        if ($verifiedTokenId > 0 && $verifiedEmail === $email && (time() - $verifiedAt) <= 600) {
            $tokenStillUnused = PasswordResetToken::where('id', $verifiedTokenId)
                ->where('email', $email)
                ->whereNull('used_at')
                ->exists();

            if ($tokenStillUnused) {
                $step = 3;
            } else {
                session()->forget(['reset_verified_token_id', 'reset_verified_email', 'reset_verified_at']);
            }
        }

        // Calcula o tempo restante real do contador de 90 segundos (1 minuto e meio)
        $expiresAtTs = (int) session('reset_code_expires_at', 0);
        if ($expiresAtTs <= 0) {
            $latestToken = PasswordResetToken::where('email', $email)
                ->whereNull('used_at')
                ->orderBy('id', 'desc')
                ->first();
            if ($latestToken !== null && ! empty($latestToken->expires_at)) {
                $expiresAtTs = strtotime((string) $latestToken->expires_at) ?: 0;
            }
        }

        $remainingSeconds = max(0, $expiresAtTs - time());

        return view('auth.reset_password', [
            'email'            => $email,
            'step'             => $step,
            'remainingSeconds' => $remainingSeconds,
            'totalSeconds'     => 90,
        ]);
    }

    public function verifyResetCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'code'  => 'required|digits:6',
        ], [
            'email.required' => 'Informe o seu e-mail.',
            'email.email'    => 'Informe um e-mail válido.',
            'code.required'  => 'Informe o código de 6 dígitos enviado ao seu e-mail.',
            'code.digits'    => 'O código de verificação deve conter exatamente 6 números.',
        ]);

        if ($validator->fails()) {
            return redirect('/reset-password')->withInput()->with('error', $validator->errors()->first());
        }

        $email = strtolower(trim((string) $request->input('email')));
        $code  = trim((string) $request->input('code'));

        $user = User::where('email', $email)->first();
        if ($user === null) {
            return redirect('/reset-password')->withInput()->with('error', 'Usuário não encontrado.');
        }

        $tokenHash = hash('sha256', $code);
        $nowStr    = now()->format('Y-m-d H:i:s');

        $tokenRow = PasswordResetToken::where('user_id', $user->id)
            ->where('email', $user->email)
            ->where('token_hash', $tokenHash)
            ->whereNull('used_at')
            ->where('expires_at', '>=', $nowStr)
            ->orderBy('id', 'desc')
            ->first();

        if ($tokenRow === null) {
            return redirect('/reset-password')->withInput()->with('error', 'Código inválido ou expirado. Verifique os 6 dígitos ou solicite um novo código.');
        }

        session([
            'reset_email'             => $user->email,
            'reset_verified_token_id' => (int) $tokenRow->id,
            'reset_verified_email'    => $user->email,
            'reset_verified_at'       => time(),
        ]);

        return redirect('/reset-password')->with('success', 'Código validado com sucesso! Agora defina sua nova senha.');
    }

    public function resetPassword(Request $request)
    {
        $verifiedTokenId = (int) session('reset_verified_token_id', 0);
        $verifiedEmail   = strtolower((string) session('reset_verified_email', ''));
        $verifiedAt      = (int) session('reset_verified_at', 0);

        if ($verifiedTokenId <= 0 || $verifiedEmail === '' || (time() - $verifiedAt) > 600) {
            session()->forget(['reset_verified_token_id', 'reset_verified_email', 'reset_verified_at']);
            return redirect('/reset-password')->with('error', 'Valide o código de 6 dígitos primeiro antes de redefinir sua senha.');
        }

        $validator = Validator::make($request->all(), [
            'password'         => 'required|min:6',
            'confirm_password' => 'required|same:password',
        ], [
            'password.required'         => 'Informe a nova senha.',
            'password.min'              => 'A nova senha deve ter pelo menos 6 caracteres.',
            'confirm_password.required' => 'Confirme a nova senha.',
            'confirm_password.same'     => 'As senhas informadas não coincidem.',
        ]);

        if ($validator->fails()) {
            return redirect('/reset-password')->with('error', $validator->errors()->first());
        }

        $user = User::where('email', $verifiedEmail)->first();
        if ($user === null) {
            return redirect('/forgot-password')->with('error', 'Usuário não encontrado.');
        }

        $tokenRow = PasswordResetToken::where('id', $verifiedTokenId)
            ->where('user_id', $user->id)
            ->where('email', $user->email)
            ->whereNull('used_at')
            ->first();

        if ($tokenRow === null) {
            session()->forget(['reset_verified_token_id', 'reset_verified_email', 'reset_verified_at']);
            return redirect('/forgot-password')->with('error', 'Este código já foi utilizado ou invalidado. Solicite um novo código.');
        }

        try {
            $user->update([
                'password_hashed' => password_hash((string) $request->input('password'), PASSWORD_DEFAULT),
            ]);

            $tokenRow->update([
                'used_at' => now()->format('Y-m-d H:i:s'),
            ]);

            session()->forget([
                'reset_email',
                'reset_code_expires_at',
                'reset_verified_token_id',
                'reset_verified_email',
                'reset_verified_at',
            ]);

            return redirect('/login')->with('success', 'Senha redefinida com sucesso! Faça login com sua nova senha.');
        } catch (Throwable $e) {
            return redirect('/reset-password')->with('error', 'Erro ao atualizar a senha: ' . $e->getMessage());
        }
    }

    private function getAvailableUsers(): array
    {
        try {
            $dbUsers = User::orderBy('role', 'asc')->orderBy('name', 'asc')->get()->toArray();
            if (! empty($dbUsers)) {
                return $dbUsers;
            }
        } catch (Throwable) {
            // Fallback
        }

        return $this->defaultUsers;
    }

    private function verifyCredentials(string $login, string $password): ?array
    {
        try {
            $dbUser = User::where('email', $login)
                ->orWhere('name', $login)
                ->orWhere('id', is_numeric($login) ? (int) $login : -1)
                ->first();

            if ($dbUser !== null) {
                $arr = $dbUser->toArray();
                if ($password !== '' && isset($arr['password_hashed']) && password_verify($password, $arr['password_hashed'])) {
                    return [
                        'id'       => $arr['id'],
                        'name'     => $arr['name'],
                        'username' => strtolower((string) $arr['name']),
                        'email'    => $arr['email'],
                        'role'     => $arr['role'] ?? 'user',
                        'avatar'   => $arr['avatar'] ?? null,
                    ];
                }

                return null;
            }
        } catch (Throwable) {
            // Fallback
        }

        foreach ($this->defaultUsers as $user) {
            $matchesLogin = strcasecmp($user['username'], $login) === 0
                || strcasecmp($user['email'], $login) === 0
                || strcasecmp($user['name'], $login) === 0
                || (string) $user['id'] === $login;

            if ($matchesLogin && $password !== '' && hash_equals($user['password'], $password)) {
                return $user;
            }
        }

        return null;
    }
}
