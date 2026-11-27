<?php

namespace Tests\Feature;

use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityItemStatus;
use App\Models\AdminNotification;
use App\Models\User;
use App\Models\UserAccountabilityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    private function statusOf(Accountability $acc, int $item): ?string
    {
        return AccountabilityItemStatus::where('accountability_id', $acc->id)
            ->where('item_number', $item)
            ->value('status');
    }

    // ---------- Públicas e autenticação ----------

    public function test_ping(): void
    {
        $this->getJson('/api/mobile/ping')->assertOk()->assertJson(['ok' => true]);
    }

    public function test_login_rapido_desativado_nao_expoe_usuarios(): void
    {
        config(['app.quick_login' => false]);
        $this->makeUser('admin');

        $this->getJson('/api/mobile/quick-users')->assertNotFound()->assertJsonMissing(['role' => 'admin']);
    }

    public function test_login_emite_token_que_autentica(): void
    {
        $user = $this->makeUser('user', [], ['whatsapp' => '(11) 98765-4321']);

        $token = $this->postJson('/api/mobile/login', ['email' => strtoupper($user->email), 'password' => 'segredo123'])
            ->assertOk()
            ->assertJsonPath('user.id', (int) $user->id)
            ->json('token');

        $this->assertNotEmpty($token);

        $this->getJson('/api/mobile/me', ['Authorization' => 'Bearer ' . $token])
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_login_invalido(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'errada'])->assertUnauthorized();
        $this->postJson('/api/mobile/login', [])->assertStatus(422);
    }

    public function test_rotas_protegidas_exigem_token(): void
    {
        $this->getJson('/api/mobile/me')->assertUnauthorized();
        $this->getJson('/api/mobile/me', ['Authorization' => 'Bearer token-falso'])->assertUnauthorized();
        $this->getJson('/api/mobile/accountabilities')->assertUnauthorized();
    }

    public function test_logout_revoga_o_token(): void
    {
        $headers = $this->mobileHeaders($this->makeUser());

        $this->postJson('/api/mobile/logout', [], $headers)->assertOk();
        $this->getJson('/api/mobile/me', $headers)->assertUnauthorized();
    }

    public function test_usuario_comum_nao_acessa_rotas_de_admin(): void
    {
        $headers = $this->mobileHeaders($this->makeUser());

        $this->getJson('/api/mobile/users', $headers)->assertForbidden();
        $this->postJson('/api/mobile/accountabilities', ['title' => 'Hack'], $headers)->assertForbidden();
        $this->getJson('/api/mobile/settings-summary', $headers)->assertForbidden();
        $this->getJson('/api/mobile/notifications', $headers)->assertForbidden();
        $this->assertSame(0, Accountability::count());
    }

    // ---------- Perfil ----------

    public function test_atualiza_perfil_e_valida_whatsapp(): void
    {
        $user    = $this->makeUser();
        $headers = $this->mobileHeaders($user);

        $this->postJson('/api/mobile/profile', ['name' => 'Novo Nome', 'email' => $user->email, 'whatsapp' => '123'], $headers)
            ->assertStatus(422);

        $this->postJson('/api/mobile/profile', ['name' => 'Novo Nome', 'email' => $user->email, 'whatsapp' => '11987654321'], $headers)
            ->assertOk()
            ->assertJsonPath('user.name', 'Novo Nome');

        $this->assertSame('Novo Nome', $user->fresh()->name);
    }

    // ---------- Prestações (usuário) ----------

    public function test_usuario_lista_e_abre_suas_prestacoes(): void
    {
        $mine  = $this->makeAccountability([1, 2], ['title' => 'Minha']);
        $other = $this->makeAccountability([7], ['title' => 'Outra']);
        $user  = $this->makeUser('user', [1]);
        $headers = $this->mobileHeaders($user);

        $this->getJson('/api/mobile/accountabilities', $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Minha')
            ->assertJsonPath('data.0.total_items', 1);

        $this->getJson('/api/mobile/accountabilities/' . $mine->id, $headers)
            ->assertOk()
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.item_number', 1)
            ->assertJsonPath('data.can_submit', false);

        $this->getJson('/api/mobile/accountabilities/' . $other->id, $headers)->assertForbidden();
        $this->getJson('/api/mobile/accountabilities/999', $headers)->assertNotFound();
    }

    public function test_fluxo_mobile_anexar_visualizar_e_enviar(): void
    {
        $this->useTempStorage();
        $acc     = $this->makeAccountability([1, 2]);
        $admin   = $this->makeUser('admin', [], ['whatsapp' => '(11) 98765-4321']);
        $user    = $this->makeUser('user', [1, 2]);
        $headers = $this->mobileHeaders($user);

        // Envio bloqueado enquanto faltar anexo em algum item
        $attId = $this->post('/api/mobile/accountabilities/' . $acc->id . '/upload/1', [
            'file' => UploadedFile::fake()->create('nota1.pdf', 10, 'application/pdf'),
        ], $headers)->assertOk()->json('attachment.id');

        $this->postJson('/api/mobile/accountabilities/' . $acc->id . '/submit', [], $headers)->assertStatus(422);

        $this->post('/api/mobile/accountabilities/' . $acc->id . '/upload/2', [
            'file' => UploadedFile::fake()->create('nota2.pdf', 10, 'application/pdf'),
        ], $headers)->assertOk();

        // Preview aceita token via query (imagens carregadas pelo app)
        $token = substr($headers['Authorization'], 7);
        $this->get('/api/mobile/attachments/' . $attId . '/preview?token=' . $token)->assertOk();

        $this->getJson('/api/mobile/accountabilities/' . $acc->id, $headers)->assertJsonPath('data.can_submit', true);

        $this->postJson('/api/mobile/accountabilities/' . $acc->id . '/submit', ['notify_admin' => false], $headers)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('all_accountability_filled', true)
            ->assertJsonCount(1, 'admin_wa_links');

        $this->assertSame('enviado', $this->statusOf($acc, 1));
        $this->assertSame('enviado', $this->statusOf($acc, 2));
        $this->assertGreaterThan(0, AdminNotification::where('user_id', $admin->id)->count());

        // Depois de enviada, some da lista do usuário
        $this->getJson('/api/mobile/accountabilities', $headers)->assertJsonCount(0, 'data');
    }

    public function test_upload_mobile_sem_arquivo_ou_sem_permissao(): void
    {
        $this->useTempStorage();
        $acc     = $this->makeAccountability([1, 2]);
        $headers = $this->mobileHeaders($this->makeUser('user', [1]));

        $this->postJson('/api/mobile/accountabilities/' . $acc->id . '/upload/1', [], $headers)->assertStatus(422);

        $this->post('/api/mobile/accountabilities/' . $acc->id . '/upload/2', [
            'file' => UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'),
        ], $headers)->assertForbidden();

        $this->assertSame(0, AccountabilityAttachment::count());
    }

    // ---------- Fila offline: reenvio com o mesmo identificador ----------

    public function test_reenvio_da_fila_offline_nao_duplica_anexo(): void
    {
        $this->useTempStorage();
        $acc     = $this->makeAccountability([1]);
        $headers = $this->mobileHeaders($this->makeUser('user', [1]));
        $url     = '/api/mobile/accountabilities/' . $acc->id . '/upload/1';
        $payload = fn () => [
            'file'             => UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf'),
            'client_upload_id' => 'fila-abc12345',
        ];

        $first = $this->post($url, $payload(), $headers)->assertOk()->assertJsonPath('duplicate', false);
        $this->post($url, $payload(), $headers)
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('attachment.id', $first->json('attachment.id'));

        $this->assertSame(1, AccountabilityAttachment::count());
        $this->assertSame('fila-abc12345', AccountabilityAttachment::firstOrFail()->client_upload_id);
    }

    public function test_reenvio_aceito_mesmo_se_item_foi_confirmado_depois(): void
    {
        $this->useTempStorage();
        $acc     = $this->makeAccountability([1]);
        $headers = $this->mobileHeaders($this->makeUser('user', [1]));
        $url     = '/api/mobile/accountabilities/' . $acc->id . '/upload/1';

        $this->post($url, ['file' => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'), 'client_upload_id' => 'fila-confirmado1'], $headers)
            ->assertOk();
        $this->setItemStatus($acc, 1, 'confirmado');

        // A resposta do primeiro envio se perdeu: o app reenvia e recebe sucesso, sem 403
        $this->post($url, ['file' => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'), 'client_upload_id' => 'fila-confirmado1'], $headers)
            ->assertOk()
            ->assertJsonPath('duplicate', true);
    }

    public function test_identificador_de_outro_usuario_nao_e_reaproveitado(): void
    {
        $this->useTempStorage();
        $acc = $this->makeAccountability([1]);
        $url = '/api/mobile/accountabilities/' . $acc->id . '/upload/1';

        $this->post($url, ['file' => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'), 'client_upload_id' => 'fila-compart1'], $this->mobileHeaders($this->makeUser('user', [1])))
            ->assertOk();

        $res = $this->post($url, ['client_upload_id' => 'fila-compart1'], $this->mobileHeaders($this->makeUser('user', [1])));
        $res->assertStatus(422);
        $this->assertSame(1, AccountabilityAttachment::count());
    }

    public function test_identificador_invalido_e_recusado(): void
    {
        $acc = $this->makeAccountability([1]);

        $this->post('/api/mobile/accountabilities/' . $acc->id . '/upload/1', [
            'file'             => UploadedFile::fake()->create('a.pdf', 5, 'application/pdf'),
            'client_upload_id' => "x'; drop",
        ], $this->mobileHeaders($this->makeUser('user', [1])))->assertStatus(422);

        $this->assertSame(0, AccountabilityAttachment::count());
    }

    public function test_usuario_nao_apaga_anexo_de_outro(): void
    {
        $acc   = $this->makeAccountability([1]);
        $owner = $this->makeUser('user', [1]);
        $att   = $this->makeAttachment($acc, 1, $owner);

        $this->deleteJson('/api/mobile/attachments/' . $att->id, [], $this->mobileHeaders($this->makeUser('user', [1])))
            ->assertForbidden();
        $this->assertNotNull(AccountabilityAttachment::find($att->id));
    }

    // ---------- Admin ----------

    public function test_admin_cria_prestacao_pelo_app(): void
    {
        $headers = $this->mobileHeaders($this->makeUser('admin'));

        $this->postJson('/api/mobile/accountabilities', ['title' => 'Via App', 'deadline' => '31/02/2027'], $headers)
            ->assertStatus(422);
        $this->postJson('/api/mobile/accountabilities', ['title' => 'ab'], $headers)->assertStatus(422);

        $id = $this->postJson('/api/mobile/accountabilities', [
            'title' => 'Via App', 'deadline' => '15/03/2027', 'enabled_items' => [2, 4],
        ], $headers)->assertCreated()->json('data.id');

        $acc = Accountability::findOrFail($id);
        $this->assertSame('2027-03-15', substr((string) $acc->deadline, 0, 10));
        $this->assertSame([2, 4], json_decode((string) $acc->enabled_items, true));

        // Sem itens informados, habilita todos
        $id2 = $this->postJson('/api/mobile/accountabilities', ['title' => 'Todos'], $headers)->json('data.id');
        $this->assertCount(24, json_decode((string) Accountability::findOrFail($id2)->enabled_items, true));
    }

    public function test_admin_gerencia_usuarios_pelo_app(): void
    {
        $headers = $this->mobileHeaders($this->makeUser('admin'));
        $user    = $this->makeUser('user', [1]);

        $this->getJson('/api/mobile/users', $headers)->assertOk()->assertJsonCount(2, 'data');
        $this->getJson('/api/mobile/users/' . $user->id, $headers)
            ->assertOk()
            ->assertJsonPath('data.allowed_items', [1])
            ->assertJsonCount(24, 'data.all_items');
        $this->getJson('/api/mobile/users/999', $headers)->assertNotFound();

        $this->postJson('/api/mobile/users/' . $user->id, [
            'name' => 'Editado App', 'email' => $user->email, 'role' => 'user', 'whatsapp' => 'abc',
        ], $headers)->assertStatus(422);

        $this->postJson('/api/mobile/users/' . $user->id, [
            'name' => 'Editado App', 'email' => $user->email, 'role' => 'user', 'allowed_items' => [3, 4],
        ], $headers)->assertOk();

        $this->assertSame('Editado App', $user->fresh()->name);
        $this->assertEqualsCanonicalizing([3, 4], UserAccountabilityPermission::getAllowedItemsForUser((int) $user->id));
    }

    public function test_cobranca_whatsapp_gera_link_quando_api_inativa(): void
    {
        $acc     = $this->makeAccountability([1], ['title' => 'Cobranca']);
        $headers = $this->mobileHeaders($this->makeUser('admin'));
        $semZap  = $this->makeUser('user', [1]);
        $comZap  = $this->makeUser('user', [1], ['whatsapp' => '(11) 98765-4321']);

        $this->postJson('/api/mobile/accountabilities/' . $acc->id . '/remind-whatsapp', ['user_id' => $semZap->id], $headers)
            ->assertStatus(422);
        $this->postJson('/api/mobile/accountabilities/999/remind-whatsapp', ['user_id' => $comZap->id], $headers)
            ->assertNotFound();

        $res = $this->postJson('/api/mobile/accountabilities/' . $acc->id . '/remind-whatsapp', [
            'user_id' => $comZap->id, 'item_number' => 1,
        ], $headers)->assertOk()->assertJsonPath('sent_via_api', false);

        $this->assertStringStartsWith('https://wa.me/5511987654321?text=', (string) $res->json('wa_url'));
        $this->assertStringContainsString('Item 01', (string) $res->json('text'));
    }

    public function test_admin_notificacoes_e_resumos(): void
    {
        $admin   = $this->makeUser('admin');
        $headers = $this->mobileHeaders($admin);
        AdminNotification::notifyAdmins('Teste', 'Mensagem', null, 'Fulano');

        $this->getJson('/api/mobile/notifications', $headers)->assertOk()->assertJsonPath('unread_count', 1);
        $this->postJson('/api/mobile/notifications/read-all', [], $headers)->assertOk();
        $this->getJson('/api/mobile/notifications', $headers)->assertJsonPath('unread_count', 0);

        $this->postJson('/api/mobile/notifications/toggle', ['enabled' => false], $headers)->assertJsonPath('enabled', false);
        $this->assertFalse((bool) User::find($admin->id)->notifications_enabled);

        $this->getJson('/api/mobile/settings-summary', $headers)->assertOk()->assertJsonPath('data.smtp_active', false);
        $this->getJson('/api/mobile/annual-history', $headers)->assertOk();
        $this->getJson('/api/mobile/audit-history', $headers)->assertOk();
    }
}
