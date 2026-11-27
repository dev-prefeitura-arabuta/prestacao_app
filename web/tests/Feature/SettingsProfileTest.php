<?php

namespace Tests\Feature;

use App\Models\AdminNotification;
use App\Models\GeneralSettings;
use App\Models\SmtpConfig;
use App\Models\WhatsappConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SettingsProfileTest extends TestCase
{
    use RefreshDatabase;

    // ---------- Configurações (admin) ----------

    public function test_abas_de_configuracao_abrem(): void
    {
        $this->loginAs($this->makeDeveloper());

        foreach (['smtp', 'general', 'pdf_cover'] as $tab) {
            $this->get('/settings?tab=' . $tab)->assertOk();
        }
    }

    public function test_salva_e_alterna_smtp_preservando_senha(): void
    {
        $this->loginAs($this->makeDeveloper());

        $payload = [
            'smtp_host'       => 'smtp.gmail.com',
            'smtp_port'       => '587',
            'smtp_encryption' => 'STARTTLS',
            'smtp_username'   => 'conta@gmail.com',
            'smtp_password'   => 'senha-app',
            'email_address'   => 'conta@gmail.com',
            'email_name'      => 'Prestacao',
            'smtp_auth'       => '1',
        ];

        $this->post('/settings/smtp', $payload)->assertRedirect('/settings?tab=smtp')->assertSessionHas('success');
        $cfg = SmtpConfig::firstOrFail();
        $this->assertSame('smtp.gmail.com', $cfg->smtp_host);
        $this->assertSame(0, (int) $cfg->status);

        // Salvar de novo sem senha mantém a senha anterior
        $this->post('/settings/smtp', array_merge($payload, ['smtp_password' => '', 'smtp_host' => 'smtp2.gmail.com']));
        $cfg->refresh();
        $this->assertSame('smtp2.gmail.com', $cfg->smtp_host);
        $this->assertSame('senha-app', (string) $cfg->smtp_password);

        $this->post('/settings/smtp/toggle');
        $this->assertSame(1, (int) $cfg->fresh()->status);
    }

    public function test_smtp_invalido_e_recusado(): void
    {
        $this->loginAs($this->makeDeveloper());

        $this->post('/settings/smtp', ['smtp_host' => 'x', 'smtp_port' => '99999', 'smtp_encryption' => 'XYZ'])
            ->assertRedirect('/settings?tab=smtp')
            ->assertSessionHas('errors_list');
        $this->assertSame(0, SmtpConfig::count());

        $this->post('/settings/smtp/toggle')->assertSessionHas('error');
    }

    public function test_salva_whatsapp_e_valida_ddi(): void
    {
        $this->loginAs($this->makeDeveloper());

        $this->post('/settings/whatsapp', [
            'api_url' => 'http://localhost:2785', 'session_name' => 'prestacao',
            'engine_type' => 'baileys', 'default_country_code' => '9999',
        ])->assertSessionHas('errors_list');
        $this->assertSame(0, WhatsappConfig::count());

        $this->post('/settings/whatsapp', [
            'api_url' => 'http://localhost:2785/', 'session_name' => 'prestacao',
            'engine_type' => 'baileys', 'default_country_code' => '+55', 'api_key' => 'chave',
        ])->assertSessionHas('success');

        $cfg = WhatsappConfig::firstOrFail();
        $this->assertSame('http://localhost:2785', $cfg->api_url);
        $this->assertSame('55', (string) $cfg->default_country_code);

        $this->post('/settings/whatsapp/toggle');
        $this->assertSame(1, (int) $cfg->fresh()->status);
    }

    public function test_salva_configuracoes_gerais_com_cnpj(): void
    {
        $this->useTempStorage();
        $this->loginAs($this->makeUser('admin'));

        $this->post('/settings/general', ['app_name' => 'App', 'company_name' => 'Empresa', 'cnpj' => '11.111.111/1111-11'])
            ->assertSessionHas('errors_list');

        $this->post('/settings/general', [
            'app_name'     => 'Meu App',
            'company_name' => 'Minha Empresa',
            'cnpj'         => '11222333000181',
            'app_logo'     => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect('/settings?tab=general')->assertSessionHas('success');

        $settings = GeneralSettings::firstOrFail();
        $this->assertSame('Meu App', $settings->app_name);
        $this->assertSame('11.222.333/0001-81', $settings->cnpj);
        $this->assertFileExists(storage_path('app/' . $settings->app_logo));

        $this->get('/app-logo')->assertOk();

        $this->post('/settings/general/remove-logo')->assertSessionHas('success');
        $this->assertEmpty(GeneralSettings::firstOrFail()->app_logo);
    }

    public function test_logo_com_formato_invalido_e_recusada(): void
    {
        $this->useTempStorage();
        $this->loginAs($this->makeUser('admin'));

        $this->post('/settings/general', [
            'app_name'     => 'Meu App',
            'company_name' => 'Minha Empresa',
            'app_logo'     => UploadedFile::fake()->create('logo.exe', 1),
        ])->assertSessionHas('error');
    }

    // ---------- Perfil ----------

    public function test_usuario_ve_e_atualiza_o_proprio_perfil(): void
    {
        $user = $this->makeUser('user', [1]);
        $this->loginAs($user);

        $this->get('/profile')->assertOk()->assertSee($user->email);

        $this->post('/profile/update', [
            'name'     => 'Nome Atualizado',
            'email'    => $user->email,
            'whatsapp' => '11987654321',
        ])->assertRedirect('/profile')->assertSessionHas('success')->assertSessionHas('user_name', 'Nome Atualizado');

        $this->assertSame('Nome Atualizado', $user->fresh()->name);
    }

    public function test_perfil_recusa_email_de_outro_usuario(): void
    {
        $other = $this->makeUser();
        $user  = $this->makeUser();
        $this->loginAs($user);

        $this->post('/profile/update', ['name' => $user->name, 'email' => $other->email])
            ->assertRedirect('/profile')
            ->assertSessionHas('errors_list');

        $this->assertNotSame($other->email, $user->fresh()->email);
    }

    public function test_perfil_troca_senha_e_avatar(): void
    {
        $this->useTempStorage();
        $user = $this->makeUser();
        $this->loginAs($user);

        $this->post('/profile/update', [
            'name'             => $user->name,
            'email'            => $user->email,
            'new_password'     => 'outraSenha9',
            'confirm_password' => 'outraSenha9',
            'avatar'           => UploadedFile::fake()->image('eu.jpg'),
        ])->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(password_verify('outraSenha9', (string) $user->password_hashed));
        $this->assertNotEmpty($user->avatar);

        $this->get('/profile/avatar/' . $user->id)->assertOk();

        $this->post('/profile/avatar/remove')->assertSessionHas('success');
        $this->assertEmpty($user->fresh()->avatar);
        $this->get('/profile/avatar/' . $user->id)->assertNotFound();
    }

    // ---------- Notificações ----------

    public function test_admin_le_e_marca_notificacoes(): void
    {
        $admin = $this->makeUser('admin');
        $acc   = $this->makeAccountability([1]);
        AdminNotification::notifyAdmins('Item 01 enviado', 'Fulano enviou o item', (int) $acc->id, 'Fulano');
        AdminNotification::notifyAdmins('Item 02 enviado', 'Fulano enviou o item', (int) $acc->id, 'Fulano');
        $this->loginAs($admin);

        $this->get('/notifications')->assertOk();

        $first = AdminNotification::where('user_id', $admin->id)->orderBy('id')->firstOrFail();
        $this->post('/notifications/' . $first->id . '/read');
        $this->assertTrue((bool) $first->fresh()->is_read);

        $this->post('/notifications/read-all');
        $this->assertSame(0, AdminNotification::where('user_id', $admin->id)->where('is_read', false)->count());
    }

    // ---------- Histórico ----------

    public function test_historicos_abrem_para_admin(): void
    {
        $this->makeAccountability([1], ['deadline' => date('Y') . '-06-30']);
        $this->loginAs($this->makeUser('admin'));

        $this->get('/history')->assertOk();
        $this->get('/historico-anual')->assertOk();
        $this->get('/historico-anual/' . date('Y'))->assertOk();
    }
}
