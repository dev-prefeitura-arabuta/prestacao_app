<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\SmtpConfig;
use App\Models\User;
use App\Models\WhatsappConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Configurações técnicas (SMTP, WhatsApp, IA) pertencem à conta de desenvolvedor;
 * o administrador cuida do restante e não enxerga nem altera a conta do desenvolvedor.
 */
class DeveloperAccessTest extends TestCase
{
    use RefreshDatabase;

    private function smtpPayload(): array
    {
        return [
            'smtp_host' => 'smtp.gmail.com', 'smtp_port' => '587', 'smtp_encryption' => 'STARTTLS',
            'smtp_username' => 'conta@gmail.com', 'smtp_password' => 'x', 'email_address' => 'conta@gmail.com',
            'email_name' => 'Prestacao',
        ];
    }

    // ---------- Login ----------

    public function test_login_grava_flag_de_desenvolvedor_na_sessao(): void
    {
        $dev   = $this->makeDeveloper();
        $admin = $this->makeUser('admin');

        $this->post('/login', ['login' => $dev->email, 'password' => 'segredo123'])->assertSessionHas('is_developer', true);
        $this->post('/logout');
        $this->post('/login', ['login' => $admin->email, 'password' => 'segredo123'])->assertSessionHas('is_developer', false);
    }

    // ---------- Tela de configurações ----------

    public function test_admin_nao_ve_abas_tecnicas(): void
    {
        $this->loginAs($this->makeUser('admin'));

        foreach (['', '?tab=smtp', '?tab=whatsapp', '?tab=ollama'] as $query) {
            $this->get('/settings' . $query)
                ->assertOk()
                ->assertViewHas('activeTab', 'general')
                ->assertDontSee('settings/smtp', false)
                ->assertDontSee('settings/whatsapp', false);
        }
    }

    public function test_desenvolvedor_ve_abas_tecnicas(): void
    {
        $this->loginAs($this->makeDeveloper());

        $this->get('/settings')
            ->assertOk()
            ->assertViewHas('activeTab', 'smtp')
            ->assertSee('settings/smtp', false)
            ->assertSee('settings/whatsapp', false);
    }

    public function test_admin_nao_altera_configuracoes_tecnicas(): void
    {
        $this->loginAs($this->makeUser('admin'));

        $posts = [
            '/settings/smtp'                    => $this->smtpPayload(),
            '/settings/smtp/toggle'             => [],
            '/settings/whatsapp'                => ['api_url' => 'http://localhost:2785', 'session_name' => 's', 'engine_type' => 'baileys', 'default_country_code' => '55'],
            '/settings/whatsapp/toggle'         => [],
            '/settings/whatsapp/test'           => ['test_phone' => '11987654321'],
            '/settings/whatsapp/notify-overdue' => [],
            '/settings/whatsapp/session/start'  => [],
            '/settings/ollama'                  => ['provider' => 'ollama'],
        ];

        foreach ($posts as $url => $data) {
            $this->post($url, $data)->assertRedirect('/settings?tab=general')->assertSessionHas('error');
        }

        $this->getJson('/settings/whatsapp/session/qr')->assertForbidden();

        $this->assertSame(0, SmtpConfig::count());
        $this->assertSame(0, WhatsappConfig::count());
    }

    public function test_admin_continua_gerenciando_dados_do_negocio(): void
    {
        $this->loginAs($this->makeUser('admin'));

        $this->post('/settings/general', ['app_name' => 'App Admin', 'company_name' => 'Prefeitura'])
            ->assertSessionHas('success');
        $this->post('/settings/pdf-cover', [])->assertRedirect('/settings?tab=pdf_cover');
    }

    public function test_desenvolvedor_salva_smtp(): void
    {
        $this->loginAs($this->makeDeveloper());

        $this->post('/settings/smtp', $this->smtpPayload())->assertSessionHas('success');
        $this->assertSame(1, SmtpConfig::count());
    }

    // ---------- Conta do desenvolvedor protegida do admin ----------

    public function test_admin_nao_ve_nem_altera_conta_de_desenvolvedor(): void
    {
        $dev = $this->makeDeveloper(['name' => 'Conta Dev Oculta']);
        $this->loginAs($this->makeUser('admin'));

        $this->get('/users')->assertOk()->assertDontSee('Conta Dev Oculta');
        $this->get('/users/' . $dev->id . '/edit')->assertRedirect('/users')->assertSessionHas('error');

        $this->post('/users/' . $dev->id . '/update', [
            'name' => 'Hackeado', 'email' => $dev->email, 'role' => 'user', 'password' => 'novaSenha1',
        ])->assertSessionHas('error');
        $this->post('/users/' . $dev->id . '/role', ['role' => 'user'])->assertSessionHas('error');
        $this->post('/users/' . $dev->id . '/delete')->assertSessionHas('error');

        $dev->refresh();
        $this->assertSame('Conta Dev Oculta', $dev->name);
        $this->assertSame('admin', $dev->role);
        $this->assertTrue($dev->isDeveloper());
        $this->assertTrue(password_verify('segredo123', (string) $dev->password_hashed));
    }

    public function test_desenvolvedor_ve_a_propria_conta_e_ela_continua_admin(): void
    {
        $dev = $this->makeDeveloper(['name' => 'Conta Dev']);
        $this->loginAs($dev);

        $this->get('/users')->assertOk()->assertSee('Conta Dev');

        $this->post('/users/' . $dev->id . '/update', ['name' => 'Conta Dev', 'email' => $dev->email, 'role' => 'user']);
        $this->assertSame('admin', $dev->fresh()->role);
    }

    public function test_flag_de_desenvolvedor_nao_pode_ser_definida_pelo_formulario(): void
    {
        $this->loginAs($this->makeUser('admin'));

        $this->post('/users', [
            'name' => 'Espertinho', 'email' => 'esperto@teste.local', 'password' => 'segredo123',
            'role' => 'admin', 'is_developer' => '1',
        ])->assertRedirect('/users');

        $this->assertFalse(User::where('email', 'esperto@teste.local')->firstOrFail()->isDeveloper());
    }

    public function test_api_mobile_esconde_conta_de_desenvolvedor(): void
    {
        $dev     = $this->makeDeveloper();
        $headers = $this->mobileHeaders($this->makeUser('admin'));

        $ids = array_column($this->getJson('/api/mobile/users', $headers)->assertOk()->json('data'), 'id');
        $this->assertNotContains((int) $dev->id, $ids);

        $this->getJson('/api/mobile/users/' . $dev->id, $headers)->assertNotFound();
        $this->postJson('/api/mobile/users/' . $dev->id, ['name' => 'Hack', 'email' => $dev->email, 'role' => 'user'], $headers)
            ->assertNotFound();
        $this->assertSame('admin', $dev->fresh()->role);
    }

    public function test_app_mobile_libera_ao_desenvolvedor_so_o_perfil(): void
    {
        $dev     = $this->makeDeveloper();
        $acc     = $this->makeAccountability([1]);
        $headers = $this->mobileHeaders($dev);

        $this->postJson('/api/mobile/login', ['email' => $dev->email, 'password' => 'segredo123'])
            ->assertOk()
            ->assertJsonPath('user.is_developer', true);
        $this->getJson('/api/mobile/me', $headers)->assertOk()->assertJsonPath('user.is_developer', true);
        $this->postJson('/api/mobile/profile', ['name' => 'Dev Renomeado', 'email' => $dev->email], $headers)->assertOk();

        $blocked = [
            ['GET', '/api/mobile/accountabilities'],
            ['GET', '/api/mobile/accountabilities/' . $acc->id],
            ['POST', '/api/mobile/accountabilities/' . $acc->id . '/submit'],
            ['POST', '/api/mobile/accountabilities'],
            ['GET', '/api/mobile/review-queue'],
            ['GET', '/api/mobile/pending-by-user'],
            ['GET', '/api/mobile/users'],
            ['GET', '/api/mobile/notifications'],
            ['GET', '/api/mobile/settings-summary'],
        ];
        foreach ($blocked as [$method, $url]) {
            $this->json($method, $url, ['title' => 'Via dev'], $headers)
                ->assertForbidden()
                ->assertJsonPath('developer', true);
        }
        $this->assertSame(1, \App\Models\Accountability::count());

        // Admin comum segue normal e recebe is_developer = false
        $adminHeaders = $this->mobileHeaders($this->makeUser('admin'));
        $this->getJson('/api/mobile/me', $adminHeaders)->assertJsonPath('user.is_developer', false);
        $this->getJson('/api/mobile/accountabilities', $adminHeaders)->assertOk();
    }

    // ---------- Desenvolvedor fora do fluxo do negócio ----------

    public function test_desenvolvedor_nao_recebe_notificacoes_nem_aparece_como_contato(): void
    {
        $dev   = $this->makeDeveloper(['whatsapp' => '(11) 91111-1111']);
        $admin = $this->makeUser('admin', [], ['whatsapp' => '(11) 98765-4321']);

        AdminNotification::notifyAdmins('Item 01 enviado', 'msg');
        $this->assertSame(0, AdminNotification::where('user_id', $dev->id)->count());
        $this->assertSame(1, AdminNotification::where('user_id', $admin->id)->count());

        $acc = $this->makeAccountability([1]);
        $this->getJson('/api/mobile/accountabilities/' . $acc->id, $this->mobileHeaders($this->makeUser('user', [1])))
            ->assertOk()
            ->assertJsonCount(1, 'data.admin_contacts')
            ->assertJsonPath('data.admin_contacts.0.id', (int) $admin->id);
    }

    // ---------- Área do desenvolvedor: sem menu de negócio, com Logs ----------

    public function test_login_do_desenvolvedor_vai_para_os_logs(): void
    {
        $dev = $this->makeDeveloper();

        $this->post('/login', ['login' => $dev->email, 'password' => 'segredo123'])->assertRedirect('/logs');
    }

    public function test_desenvolvedor_nao_acessa_area_de_negocio(): void
    {
        $acc = $this->makeAccountability([1]);
        $this->loginAs($this->makeDeveloper());

        foreach (['/accountability', '/accountability/' . $acc->id, '/review', '/pendencias', '/history', '/historico-anual', '/notifications'] as $url) {
            $this->get($url)->assertRedirect('/logs');
        }
        $this->postJson('/accountability/' . $acc->id . '/delete')->assertForbidden();
        $this->assertNotNull(\App\Models\Accountability::find($acc->id));

        // Continua com usuários, configurações e perfil
        $this->get('/users')->assertOk();
        $this->get('/settings')->assertOk();
        $this->get('/profile')->assertOk();
    }

    public function test_menu_do_desenvolvedor_mostra_logs_e_esconde_o_negocio(): void
    {
        $this->useTempStorage();
        $this->loginAs($this->makeDeveloper());

        $this->get('/logs')
            ->assertOk()
            ->assertSee('Logs do Sistema')
            ->assertSee('Desenvolvedor')
            ->assertDontSee('Para Avaliar')
            ->assertDontSee('Pendências por Pessoa')
            ->assertDontSee('adminNotifBellBtn', false);
    }

    public function test_admin_e_usuario_nao_acessam_logs(): void
    {
        $this->loginAs($this->makeUser('admin'));
        $this->get('/logs')->assertRedirect('/settings?tab=general');
        $this->get('/logs/download/laravel.log')->assertRedirect('/settings?tab=general');

        $this->loginAs($this->makeUser('user'));
        $this->get('/logs')->assertRedirect('/accountability');
    }

    private function writeLog(string $name, string $content): void
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir . DIRECTORY_SEPARATOR . $name, $content);
    }

    public function test_tela_de_logs_lista_dias_niveis_e_filtra(): void
    {
        $this->useTempStorage();
        $this->writeLog('laravel-2026-11-27.log', "[2026-11-27 09:00:00] local.INFO: Login (web) {\"user_id\":1}\n");
        $this->writeLog('laravel-2026-11-28.log', implode("\n", [
            '[2026-11-28 08:00:00] local.INFO: Login (web) {"user_id":3,"email":"a@b.com"}',
            '[2026-11-28 08:05:00] local.WARNING: Falha de login (web) {"email":"x@y.com"}',
            '[2026-11-28 08:10:00] local.ERROR: SQLSTATE[HY000] conexão recusada {"exception":"[object] (PDOException(code: 2002))',
            '[stacktrace]',
            '#0 /app/vendor/laravel/framework/src/Database/Connection.php(100): connect()',
            '"}',
            '',
        ]));
        $this->writeLog('outro-arquivo.txt', 'não deve aparecer');
        $this->loginAs($this->makeDeveloper());

        $res = $this->get('/logs')->assertOk();
        $res->assertViewHas('file', 'laravel-2026-11-28.log')
            ->assertSee('28/11/2026')
            ->assertSee('27/11/2026')
            ->assertDontSee('outro-arquivo')
            ->assertSee('SQLSTATE[HY000] conexão recusada')
            ->assertSee('Connection.php(100)')
            ->assertSee('Erros (1)')
            ->assertSee('Avisos (1)')
            ->assertSee('Informações (1)');

        $this->get('/logs?file=laravel-2026-11-28.log&level=error')
            ->assertSee('SQLSTATE')
            ->assertDontSee('Falha de login (web)');

        $this->get('/logs?file=laravel-2026-11-28.log&q=x@y.com')
            ->assertSee('Falha de login (web)')
            ->assertDontSee('SQLSTATE[HY000] conexão');

        $this->get('/logs?file=laravel-2026-11-27.log')->assertViewHas('file', 'laravel-2026-11-27.log');
    }

    public function test_preview_detalhado_vem_na_pagina_sem_permitir_html_do_log(): void
    {
        $this->useTempStorage();
        $this->writeLog('laravel-2026-11-28.log', implode("\n", [
            '[2026-11-28 08:00:00] local.INFO: Login (web) {"user_id":3,"ip":"10.0.0.1"}',
            '[2026-11-28 08:01:00] local.WARNING: </script><script>alert(1)</script><img src=x onerror=alert(2)>',
            '',
        ]));
        $this->loginAs($this->makeDeveloper());

        $html = $this->get('/logs')->assertOk()->getContent();

        $this->assertStringContainsString('id="lvDrawer"', $html);
        $this->assertStringContainsString('id="lvData"', $html);
        $this->assertStringContainsString('user_id: 3', $html, 'Campos do contexto aparecem na lista');
        // O conteúdo do log nunca entra como HTML/JS executável
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringContainsString('u003Cscript', $html, 'Dados do preview escapados no JSON');

        preg_match('#<script type="application/json" id="lvData">(.*?)</script>#s', $html, $m);
        $data = json_decode($m[1], true);
        $this->assertSame('</script><script>alert(1)</script><img src=x onerror=alert(2)>', $data[0]['clean_message']);
        $this->assertSame(['user_id' => 3, 'ip' => '10.0.0.1'], $data[1]['context']);
    }

    public function test_logs_nao_permitem_ler_outros_arquivos(): void
    {
        $this->useTempStorage();
        $this->writeLog('laravel.log', "[2026-11-28 08:00:00] local.INFO: ok\n");
        file_put_contents(storage_path('segredo.txt'), 'APP_KEY=base64:segredo');
        $this->loginAs($this->makeDeveloper());

        $this->get('/logs?file=../segredo.txt')->assertOk()->assertViewHas('file', 'laravel.log')->assertDontSee('APP_KEY');
        $this->get('/logs/download/segredo.txt')->assertNotFound();
        $this->get('/logs/download/..%2Fsegredo.txt')->assertNotFound();
        $this->get('/logs/download/laravel.log')->assertOk()->assertDownload('laravel.log');
    }

    public function test_tentativas_de_login_ficam_no_log(): void
    {
        \Illuminate\Support\Facades\Log::spy();
        $user = $this->makeUser();

        $this->post('/login', ['login' => $user->email, 'password' => 'errada']);
        $this->post('/login', ['login' => $user->email, 'password' => 'segredo123']);

        \Illuminate\Support\Facades\Log::shouldHaveReceived('warning')->with('Falha de login (web)', \Mockery::type('array'))->once();
        \Illuminate\Support\Facades\Log::shouldHaveReceived('info')->with('Login (web)', \Mockery::type('array'))->once();
    }

    // ---------- Comando app:developer ----------

    public function test_comando_concede_e_remove_acesso_de_desenvolvedor(): void
    {
        $user = $this->makeUser('user');

        $this->artisan('app:developer', ['email' => strtoupper($user->email)])->assertSuccessful();
        $user->refresh();
        $this->assertTrue($user->isDeveloper());
        $this->assertSame('admin', $user->role);

        $this->artisan('app:developer', ['email' => $user->email, '--remove' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->isDeveloper());

        $this->artisan('app:developer', ['email' => 'ninguem@teste.local'])->assertFailed();
    }
}
