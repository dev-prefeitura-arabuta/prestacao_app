<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PasswordResetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    public function __construct(private readonly PasswordResetService $passwordReset)
    {
    }

    public function login()
    {
        if (session('isLoggedIn')) {
            return redirect('/accountability');
        }

        $quickLogin = (bool) config('app.quick_login');

        return view('auth.login', [
            'quickLogin' => $quickLogin,
            'users'      => $quickLogin ? $this->getQuickLoginUsers() : [],
        ]);
    }

    public function attemptLogin(Request $request)
    {
        $request->validate([
            'login'    => 'required',
            'password' => 'required',
        ], [
            'login.required'    => 'Informe o e-mail.',
            'password.required' => 'Informe a senha.',
        ]);

        $login    = PasswordResetService::normalizeEmail((string) $request->input('login'));
        $password = (string) $request->input('password');

        $user = User::where('email', $login)->first();

        if ($user === null || ! password_verify($password, (string) $user->password_hashed)) {
            return back()->withInput($request->only('login'))->with('error', 'Credenciais inválidas.');
        }

        $request->session()->regenerate();
        session([
            'user_id'     => (int) $user->id,
            'user_name'   => (string) $user->name,
            'username'    => strtolower((string) $user->name),
            'user_email'  => (string) $user->email,
            'user_role'   => (string) ($user->role ?? 'user'),
            'role'        => (string) ($user->role ?? 'user'),
            'user_avatar' => $user->avatar ?? null,
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

        return $this->issueAndSendResetCode((string) $request->input('email'), '/forgot-password');
    }

    public function resendResetCode(Request $request)
    {
        $email = PasswordResetService::normalizeEmail((string) ($request->input('email') ?: session('reset_email', '')));
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
        $email  = PasswordResetService::normalizeEmail($email);
        $result = $this->passwordReset->issueCode($email);

        if (! $result['success']) {
            return redirect($errorRedirect)->withInput()->with('error', $result['error']);
        }

        session()->forget(['reset_verified_token_id', 'reset_verified_email', 'reset_verified_at']);
        session([
            'reset_email'           => $email,
            'reset_code_expires_at' => $result['expires_at']->timestamp,
        ]);

        return redirect('/reset-password')->with('success', PasswordResetService::GENERIC_SENT_MESSAGE . ' Você tem 1 minuto e 30 segundos para validá-lo.');
    }

    public function showResetPassword(Request $request)
    {
        if (session('isLoggedIn')) {
            return redirect('/accountability');
        }

        $email = PasswordResetService::normalizeEmail((string) ($request->query('email') ?: session('reset_email') ?: old('email', '')));
        if ($email === '') {
            return redirect('/forgot-password')->with('error', 'Informe primeiro o seu e-mail para receber o código de verificação.');
        }

        // Verifica se a Etapa 2 (código) já foi validada nesta sessão
        $verifiedTokenId = (int) session('reset_verified_token_id', 0);
        $verifiedEmail   = strtolower((string) session('reset_verified_email', ''));
        $verifiedAt      = (int) session('reset_verified_at', 0);
        $step            = 2;

        if ($verifiedTokenId > 0 && $verifiedEmail === $email && (time() - $verifiedAt) <= PasswordResetService::VERIFIED_TTL_SECONDS) {
            if ($this->passwordReset->findUnusedToken($verifiedTokenId, $email) !== null) {
                $step = 3;
            } else {
                session()->forget(['reset_verified_token_id', 'reset_verified_email', 'reset_verified_at']);
            }
        }

        // Tempo restante real do contador de 90 segundos
        $expiresAtTs = (int) session('reset_code_expires_at', 0);
        if ($expiresAtTs <= 0) {
            $expiresAtTs = $this->passwordReset->latestExpiry($email);
        }

        return view('auth.reset_password', [
            'email'            => $email,
            'step'             => $step,
            'remainingSeconds' => max(0, $expiresAtTs - time()),
            'totalSeconds'     => PasswordResetService::CODE_TTL_SECONDS,
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

        $result = $this->passwordReset->verifyCode((string) $request->input('email'), (string) $request->input('code'));
        $token  = $result['token'];

        if ($token === null) {
            return redirect('/reset-password')->withInput()->with('error', $result['error']);
        }

        session([
            'reset_email'             => (string) $token->email,
            'reset_verified_token_id' => (int) $token->id,
            'reset_verified_email'    => (string) $token->email,
            'reset_verified_at'       => time(),
        ]);

        return redirect('/reset-password')->with('success', 'Código validado com sucesso! Agora defina sua nova senha.');
    }

    public function resetPassword(Request $request)
    {
        $verifiedTokenId = (int) session('reset_verified_token_id', 0);
        $verifiedEmail   = strtolower((string) session('reset_verified_email', ''));
        $verifiedAt      = (int) session('reset_verified_at', 0);

        if ($verifiedTokenId <= 0 || $verifiedEmail === '' || (time() - $verifiedAt) > PasswordResetService::VERIFIED_TTL_SECONDS) {
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

        $token = $this->passwordReset->findUnusedToken($verifiedTokenId, $verifiedEmail);
        if ($token === null) {
            session()->forget(['reset_verified_token_id', 'reset_verified_email', 'reset_verified_at']);
            return redirect('/forgot-password')->with('error', 'Este código já foi utilizado ou invalidado. Solicite um novo código.');
        }

        try {
            if (! $this->passwordReset->resetPassword($token, (string) $request->input('password'))) {
                return redirect('/forgot-password')->with('error', 'Não foi possível redefinir a senha. Solicite um novo código.');
            }
        } catch (Throwable $e) {
            report($e);
            return redirect('/reset-password')->with('error', 'Erro ao atualizar a senha. Tente novamente.');
        }

        session()->forget([
            'reset_email',
            'reset_code_expires_at',
            'reset_verified_token_id',
            'reset_verified_email',
            'reset_verified_at',
        ]);

        return redirect('/login')->with('success', 'Senha redefinida com sucesso! Faça login com sua nova senha.');
    }

    /**
     * Lista de usuários para o modo de login rápido (somente quando QUICK_LOGIN=true).
     */
    private function getQuickLoginUsers(): array
    {
        try {
            return User::orderBy('role', 'asc')->orderBy('name', 'asc')->get(['id', 'name', 'email', 'role'])->toArray();
        } catch (Throwable) {
            return [];
        }
    }
}
