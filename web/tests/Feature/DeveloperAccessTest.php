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
