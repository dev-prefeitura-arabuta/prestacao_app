<?php

namespace Tests\Unit\Services;

use App\Models\PasswordResetToken;
use App\Models\User;
use App\Services\PasswordResetService;
use App\Services\SmtpMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array<string, string>> E-mails "enviados" pelo mailer falso */
    private array $sent = [];

    private bool $mailFails = false;

    private function service(): PasswordResetService
    {
        $test   = $this;
        $mailer = new class($test) extends SmtpMailService {
            public function __construct(private $test)
            {
            }

            public function send(...$args): array
            {
                return $this->test->recordMail($args);
            }
        };

        return new PasswordResetService($mailer);
    }

    /** @internal usado pelo mailer falso */
    public function recordMail(array $args): array
    {
        $this->sent[] = ['to' => $args[0], 'subject' => $args[2], 'html' => $args[3], 'text' => $args[4] ?? ''];

        return $this->mailFails
            ? ['success' => false, 'error' => 'SMTP fora do ar']
            : ['success' => true, 'error' => null];
    }

    private function sentCode(): string
    {
        $this->assertNotEmpty($this->sent, 'Nenhum e-mail foi enviado');
        preg_match('/\((\d{6})\)/', end($this->sent)['subject'], $m);

        return $m[1];
    }

    public function test_email_inexistente_retorna_sucesso_sem_enviar_nada(): void
    {
        $result = $this->service()->issueCode('ninguem@teste.local');

        $this->assertTrue($result['success']);
        $this->assertSame([], $this->sent);
        $this->assertSame(0, PasswordResetToken::count());
    }

    public function test_envia_codigo_e_armazena_somente_o_hash(): void
    {
        $user   = $this->makeUser();
        $result = $this->service()->issueCode('  ' . strtoupper($user->email) . ' ');

        $this->assertTrue($result['success']);
        $code  = $this->sentCode();
        $token = PasswordResetToken::first();

        $this->assertSame($user->email, end($this->sent)['to']);
        $this->assertSame(hash('sha256', $code), $token->token_hash);
        $this->assertStringContainsString($code, end($this->sent)['html']);
    }

    public function test_novo_codigo_invalida_o_anterior(): void
    {
        $user    = $this->makeUser();
        $service = $this->service();

        $service->issueCode($user->email);
        $first = $this->sentCode();
        $service->issueCode($user->email);

        $this->assertNull($service->verifyCode($user->email, $first)['token']);
        $this->assertNotNull($service->verifyCode($user->email, $this->sentCode())['token']);
    }

    public function test_falha_no_smtp_retorna_erro(): void
    {
        $this->mailFails = true;
        $user = $this->makeUser();

        $result = $this->service()->issueCode($user->email);

        $this->assertFalse($result['success']);
        $this->assertNotEmpty($result['error']);
    }

    public function test_codigo_correto_e_valido(): void
    {
        $user    = $this->makeUser();
        $service = $this->service();
        $service->issueCode($user->email);

        $result = $service->verifyCode($user->email, $this->sentCode());

        $this->assertNull($result['error']);
        $this->assertSame((int) $user->id, (int) $result['token']->user_id);
    }

    public function test_codigo_expirado_e_recusado(): void
    {
        $user    = $this->makeUser();
        $service = $this->service();
        $service->issueCode($user->email);
        $code = $this->sentCode();

        $this->travel(PasswordResetService::CODE_TTL_SECONDS + 5)->seconds();

        $result = $service->verifyCode($user->email, $code);
        $this->assertNull($result['token']);
        $this->assertStringContainsString('inválido ou expirado', $result['error']);
    }

    public function test_cinco_erros_invalidam_o_codigo_mesmo_se_depois_acertar(): void
    {
        $user    = $this->makeUser();
        $service = $this->service();
        $service->issueCode($user->email);
        $code  = $this->sentCode();
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < PasswordResetService::MAX_FAILED_ATTEMPTS; $i++) {
            $last = $service->verifyCode($user->email, $wrong);
        }

        $this->assertStringContainsString('Muitas tentativas', $last['error']);
        $this->assertNull($service->verifyCode($user->email, $code)['token']);
        $this->assertSame(0, PasswordResetToken::whereNull('used_at')->count());
    }

    public function test_redefine_a_senha_e_consome_o_token(): void
    {
        $user    = $this->makeUser();
        $service = $this->service();
        $service->issueCode($user->email);
        $token = $service->verifyCode($user->email, $this->sentCode())['token'];

        $this->assertTrue($service->resetPassword($token, 'novaSenha!9'));

        $this->assertTrue(password_verify('novaSenha!9', User::find($user->id)->password_hashed));
        $this->assertNotNull($token->fresh()->used_at);
        $this->assertNull($service->findUnusedToken((int) $token->id, $user->email));
    }

    public function test_find_unused_token_exige_o_mesmo_email(): void
    {
        $user    = $this->makeUser();
        $other   = $this->makeUser();
        $service = $this->service();
        $service->issueCode($user->email);
        $token = $service->verifyCode($user->email, $this->sentCode())['token'];

        $this->assertNotNull($service->findUnusedToken((int) $token->id, $user->email));
        $this->assertNull($service->findUnusedToken((int) $token->id, $other->email));
    }
}
