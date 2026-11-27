<?php

namespace Tests\Feature;

use App\Models\PasswordResetToken;
use App\Services\SmtpMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array<string, mixed>> */
    private array $sentMails = [];

    private function fakeMailer(): void
    {
        $test = $this;
        $this->app->instance(SmtpMailService::class, new class($test) extends SmtpMailService {
            public function __construct(private $test)
            {
            }

            public function send(...$args): array
            {
                return $this->test->recordMail($args);
            }
        });
    }

    /** @internal usado pelo mailer falso */
    public function recordMail(array $args): array
    {
        $this->sentMails[] = ['to' => $args[0], 'subject' => $args[2]];

        return ['success' => true, 'error' => null];
    }

    private function sentCode(): string
    {
        $this->assertNotEmpty($this->sentMails, 'Nenhum e-mail foi enviado');
        preg_match('/\((\d{6})\)/', (string) end($this->sentMails)['subject'], $m);

        return $m[1];
    }

    // ---------- Login / logout ----------

    public function test_pagina_de_login_abre(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_login_valido_grava_sessao_e_redireciona(): void
    {
        $user = $this->makeUser('admin');

        $this->post('/login', ['login' => '  ' . strtoupper($user->email) . ' ', 'password' => 'segredo123'])
            ->assertRedirect('/accountability')
            ->assertSessionHas('isLoggedIn', true)
            ->assertSessionHas('user_id', (int) $user->id)
            ->assertSessionHas('user_role', 'admin');
    }

    public function test_senha_errada_nao_loga(): void
    {
        $user = $this->makeUser();

        $this->from('/login')
            ->post('/login', ['login' => $user->email, 'password' => 'errada'])
            ->assertRedirect('/login')
            ->assertSessionHas('error')
            ->assertSessionMissing('isLoggedIn');
    }

    public function test_login_exige_campos(): void
    {
        $this->from('/login')->post('/login', [])->assertRedirect('/login')->assertSessionHasErrors(['login', 'password']);
    }

    public function test_usuario_logado_e_redirecionado_da_tela_de_login(): void
    {
        $this->loginAs($this->makeUser())->get('/login')->assertRedirect('/accountability');
    }

    public function test_logout_encerra_a_sessao(): void
    {
        $this->loginAs($this->makeUser())->post('/logout')->assertRedirect('/login');
        $this->get('/accountability')->assertRedirect('/login');
    }

    // ---------- Proteção de rotas ----------

    public function test_visitante_e_redirecionado_para_login(): void
    {
        foreach (['/accountability', '/profile', '/users', '/settings', '/notifications'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_usuario_comum_nao_acessa_rotas_de_admin(): void
    {
        $this->loginAs($this->makeUser());

        foreach (['/users', '/users/create', '/settings', '/history', '/historico-anual'] as $url) {
            $this->get($url)->assertRedirect('/accountability');
        }

        $this->post('/settings/smtp', [])->assertRedirect('/accountability');
        $this->postJson('/users', [])->assertForbidden();
    }

    // ---------- Recuperação de senha (3 etapas) ----------

    public function test_fluxo_completo_de_recuperacao_de_senha(): void
    {
        $this->fakeMailer();
        $user = $this->makeUser();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect('/reset-password')
            ->assertSessionHas('reset_email', $user->email);
        $this->assertCount(1, $this->sentMails);

        $this->get('/reset-password')->assertOk();

        $this->post('/reset-password/verify', ['email' => $user->email, 'code' => $this->sentCode()])
            ->assertRedirect('/reset-password')
            ->assertSessionHas('reset_verified_token_id');

        $this->post('/reset-password', ['password' => 'novaSenha1', 'confirm_password' => 'novaSenha1'])
            ->assertRedirect('/login')
            ->assertSessionHas('success');

        $this->assertTrue(password_verify('novaSenha1', (string) $user->fresh()->password_hashed));

        $this->post('/login', ['login' => $user->email, 'password' => 'novaSenha1'])
            ->assertRedirect('/accountability');
    }

    public function test_redefinir_senha_sem_validar_codigo_e_recusado(): void
    {
        $user = $this->makeUser();

        $this->post('/reset-password', ['password' => 'novaSenha1', 'confirm_password' => 'novaSenha1'])
            ->assertRedirect('/reset-password')
            ->assertSessionHas('error');

        $this->assertTrue(password_verify('segredo123', (string) $user->fresh()->password_hashed));
    }

    public function test_codigo_errado_nao_valida(): void
    {
        $this->fakeMailer();
        $user = $this->makeUser();
        $this->post('/forgot-password', ['email' => $user->email]);

        $wrong = $this->sentCode() === '000000' ? '111111' : '000000';

        $this->post('/reset-password/verify', ['email' => $user->email, 'code' => $wrong])
            ->assertRedirect('/reset-password')
            ->assertSessionHas('error')
            ->assertSessionMissing('reset_verified_token_id');
    }

    public function test_email_inexistente_nao_revela_cadastro(): void
    {
        $this->fakeMailer();

        $this->post('/forgot-password', ['email' => 'ninguem@teste.local'])
            ->assertRedirect('/reset-password')
            ->assertSessionHas('success');

        $this->assertSame([], $this->sentMails);
        $this->assertSame(0, PasswordResetToken::count());
    }

    public function test_reset_password_sem_email_volta_para_etapa_1(): void
    {
        $this->get('/reset-password')->assertRedirect('/forgot-password');
    }
}
