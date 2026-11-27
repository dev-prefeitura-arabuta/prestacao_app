<?php

namespace Tests\Feature;

use App\Models\Accountability;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AccountabilityWebTest extends TestCase
{
    use RefreshDatabase;

    private function statusOf(Accountability $acc, int $item): ?string
    {
        return AccountabilityItemStatus::where('accountability_id', $acc->id)
            ->where('item_number', $item)
            ->value('status');
    }

    // ---------- Listagem e detalhe ----------

    public function test_admin_ve_listagem_e_detalhe(): void
    {
        $acc = $this->makeAccountability([1, 2], ['title' => 'Prestacao Janeiro']);
        $this->loginAs($this->makeUser('admin'));

        $this->get('/accountability')->assertOk()->assertSee('Prestacao Janeiro');
        $this->get('/accountability/' . $acc->id)->assertOk()->assertSee('Prestacao Janeiro');
    }

    public function test_usuario_so_ve_prestacao_com_itens_liberados(): void
    {
        $this->makeAccountability([1], ['title' => 'Visivel Para Mim']);
        $this->makeAccountability([5], ['title' => 'Nao E Minha']);
        $this->loginAs($this->makeUser('user', [1]));

        $this->get('/accountability')
            ->assertOk()
            ->assertSee('Visivel Para Mim')
            ->assertDontSee('Nao E Minha');
    }

    public function test_usuario_nao_abre_prestacao_pausada(): void
    {
        $acc = $this->makeAccountability([1], ['is_paused' => true]);
        $this->loginAs($this->makeUser('user', [1]));

        $this->get('/accountability/' . $acc->id)->assertRedirect('/accountability')->assertSessionHas('error');
    }

    public function test_prestacao_inexistente_volta_para_listagem(): void
    {
        $this->loginAs($this->makeUser('admin'));

        $this->get('/accountability/999')->assertRedirect('/accountability')->assertSessionHas('error');
    }

    public function test_relatorio_e_pdf_abrem_para_admin(): void
    {
        $acc = $this->makeAccountability([1]);
        $this->loginAs($this->makeUser('admin'));

        $this->get('/accountability/' . $acc->id . '/report')->assertOk();
        $this->get('/accountability/' . $acc->id . '/print-pdf')->assertOk();
    }

    // ---------- Criar / editar / pausar / excluir (admin) ----------

    public function test_admin_cria_prestacao(): void
    {
        $this->loginAs($this->makeUser('admin'));
        $this->get('/accountability/create')->assertOk();

        $response = $this->post('/accountability', [
            'title'         => '  Prestacao Nova  ',
            'description'   => 'Descricao',
            'deadline'      => '2027-03-31',
            'status'        => 'aberta',
            'enabled_items' => ['3', '1', '99', '3'],
        ]);

        $acc = Accountability::firstOrFail();
        $response->assertRedirect('/accountability/' . $acc->id);
        $this->assertSame('Prestacao Nova', $acc->title);
        $this->assertSame([3, 1], json_decode((string) $acc->enabled_items, true));
        $this->assertSame(1, AccountabilityHistory::where('accountability_id', $acc->id)->count());
    }

    public function test_criacao_exige_ao_menos_um_item_e_dados_validos(): void
    {
        $this->loginAs($this->makeUser('admin'));

        $this->from('/accountability/create')
            ->post('/accountability', ['title' => 'Sem itens', 'status' => 'aberta', 'enabled_items' => ['30']])
            ->assertRedirect('/accountability/create')
            ->assertSessionHas('error');

        $this->from('/accountability/create')
            ->post('/accountability', ['title' => 'ab', 'status' => 'aberta', 'deadline' => '31/02/2027', 'enabled_items' => ['1']])
            ->assertRedirect('/accountability/create')
            ->assertSessionHas('errors_list', fn ($errors) => count($errors) === 2);

        $this->assertSame(0, Accountability::count());
    }

    public function test_usuario_comum_nao_cria_edita_pausa_nem_exclui(): void
    {
        $acc = $this->makeAccountability([1], ['title' => 'Original']);
        $this->loginAs($this->makeUser('user', [1]));

        $this->get('/accountability/create')->assertRedirect('/accountability');
        $this->post('/accountability', ['title' => 'Hack', 'status' => 'aberta', 'enabled_items' => [1]])->assertRedirect('/accountability');
        $this->get('/accountability/' . $acc->id . '/edit')->assertRedirect('/accountability');
        $this->post('/accountability/' . $acc->id . '/update', ['title' => 'Hack', 'status' => 'aberta', 'enabled_items' => [1]])->assertRedirect('/accountability');
        $this->post('/accountability/' . $acc->id . '/toggle-pause')->assertRedirect('/accountability');
        $this->post('/accountability/' . $acc->id . '/delete')->assertRedirect('/accountability');

        $acc->refresh();
        $this->assertSame(1, Accountability::count());
        $this->assertSame('Original', $acc->title);
        $this->assertFalse((bool) $acc->is_paused);
    }

    public function test_admin_edita_pausa_e_exclui(): void
    {
        $acc = $this->makeAccountability([1]);
        $this->loginAs($this->makeUser('admin'));

        $this->get('/accountability/' . $acc->id . '/edit')->assertOk();

        $this->post('/accountability/' . $acc->id . '/update', [
            'title' => 'Titulo Editado', 'status' => 'em_analise', 'enabled_items' => [2, 4],
        ])->assertRedirect('/accountability');
        $acc->refresh();
        $this->assertSame('Titulo Editado', $acc->title);
        $this->assertSame([2, 4], json_decode((string) $acc->enabled_items, true));

        $this->post('/accountability/' . $acc->id . '/toggle-pause');
        $this->assertTrue((bool) $acc->fresh()->is_paused);
        $this->post('/accountability/' . $acc->id . '/toggle-pause');
        $this->assertFalse((bool) $acc->fresh()->is_paused);

        $this->post('/accountability/' . $acc->id . '/delete')->assertRedirect('/accountability');
        $this->assertNull(Accountability::find($acc->id));
    }

    // ---------- Upload, visualização e exclusão de anexos ----------

    public function test_usuario_anexa_visualiza_baixa_e_remove_arquivo(): void
    {
        $this->useTempStorage();
        $acc  = $this->makeAccountability([1]);
        $user = $this->makeUser('user', [1]);
        $this->loginAs($user);

        $this->post('/accountability/' . $acc->id . '/upload/1', [
            'attachments' => [UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')],
        ])->assertRedirect('/accountability/' . $acc->id . '#modal-item-1')->assertSessionHas('success');

        $att = AccountabilityAttachment::firstOrFail();
        $this->assertSame((int) $user->id, (int) $att->user_id);
        $this->assertFileExists(storage_path('app/' . $att->file_path));

        $this->get('/accountability/attachment/' . $att->id . '/preview')->assertOk();
        $this->get('/accountability/attachment/' . $att->id . '/download')->assertOk()->assertDownload('nota.pdf');

        $this->post('/accountability/attachment/' . $att->id . '/delete')->assertSessionHas('success');
        $this->assertNull(AccountabilityAttachment::find($att->id));
    }

    public function test_upload_recusa_tipo_invalido_e_item_sem_permissao(): void
    {
        $this->useTempStorage();
        $acc = $this->makeAccountability([1, 2]);
        $this->loginAs($this->makeUser('user', [1]));

        $this->post('/accountability/' . $acc->id . '/upload/1', [
            'attachments' => [UploadedFile::fake()->create('virus.exe', 1, 'application/x-msdownload')],
        ])->assertSessionHas('error');

        $this->post('/accountability/' . $acc->id . '/upload/2', [
            'attachments' => [UploadedFile::fake()->create('nota.pdf', 10, 'application/pdf')],
        ])->assertRedirect('/accountability/' . $acc->id)->assertSessionHas('error');

        $this->post('/accountability/' . $acc->id . '/upload/1', [])->assertSessionHas('error');

        $this->assertSame(0, AccountabilityAttachment::count());
    }

    public function test_usuario_nao_ve_anexo_de_item_sem_permissao(): void
    {
        $acc   = $this->makeAccountability([1, 2]);
        $owner = $this->makeUser('user', [2]);
        $att   = $this->makeAttachment($acc, 2, $owner);
        $this->loginAs($this->makeUser('user', [1]));

        $this->get('/accountability/attachment/' . $att->id . '/preview')->assertForbidden();
    }

    // ---------- Envio de itens e da prestação ----------

    public function test_enviar_item_sem_anexo_e_recusado(): void
    {
        $acc = $this->makeAccountability([1]);
        $this->loginAs($this->makeUser('user', [1]));

        $this->post('/accountability/' . $acc->id . '/item/1/submit')->assertSessionHas('error');
        $this->assertNull($this->statusOf($acc, 1));
    }

    public function test_usuario_envia_item_e_admins_sao_notificados(): void
    {
        $acc   = $this->makeAccountability([1]);
        $admin = $this->makeUser('admin');
        $user  = $this->makeUser('user', [1]);
        $this->makeAttachment($acc, 1, $user);
        $this->loginAs($user);

        $this->post('/accountability/' . $acc->id . '/item/1/submit')->assertRedirect('/accountability')->assertSessionHas('success');

        $this->assertSame('enviado', $this->statusOf($acc, 1));
        $this->assertSame(1, AdminNotification::where('user_id', $admin->id)->count());
    }

    public function test_usuario_nao_envia_item_sem_permissao(): void
    {
        $acc   = $this->makeAccountability([1, 2]);
        $other = $this->makeUser('user', [2]);
        $this->makeAttachment($acc, 2, $other);
        $this->loginAs($this->makeUser('user', [1]));

        $this->post('/accountability/' . $acc->id . '/item/2/submit')->assertSessionHas('error');
        $this->assertNull($this->statusOf($acc, 2));
    }

    public function test_usuario_envia_prestacao_completa(): void
    {
        $acc  = $this->makeAccountability([1, 2]);
        $user = $this->makeUser('user', [1, 2]);
        $this->makeAttachment($acc, 1, $user);
        $this->loginAs($user);

        $this->post('/accountability/' . $acc->id . '/submit')->assertRedirect('/accountability')->assertSessionHas('success');

        $this->assertSame('enviado', $this->statusOf($acc, 1));
        $this->assertNull($this->statusOf($acc, 2));
    }

    public function test_enviar_prestacao_sem_anexos_e_recusado(): void
    {
        $acc = $this->makeAccountability([1]);
        $this->loginAs($this->makeUser('user', [1]));

        $this->post('/accountability/' . $acc->id . '/submit')
            ->assertRedirect('/accountability/' . $acc->id)
            ->assertSessionHas('error');
    }

    // ---------- Avaliação do admin ----------

    public function test_admin_confirma_e_rejeita_item_via_ajax(): void
    {
        $acc  = $this->makeAccountability([1]);
        $user = $this->makeUser('user', [1]);
        $this->makeAttachment($acc, 1, $user);
        $this->loginAs($this->makeUser('admin'));

        $url = '/accountability/' . $acc->id . '/item/1/status';

        $this->postJson($url, ['status' => 'rejeitado'], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertStatus(422);

        $this->postJson($url, ['status' => 'rejeitado', 'rejection_reason' => 'Nota ilegivel'], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'rejeitado', 'rejection_reason' => 'Nota ilegivel']);
        $this->assertSame('rejeitado', $this->statusOf($acc, 1));

        $this->postJson($url, ['status' => 'confirmado'], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJson(['status' => 'confirmado']);
        $this->assertSame('confirmado', $this->statusOf($acc, 1));

        $this->postJson($url, ['status' => 'inventado'], ['X-Requested-With' => 'XMLHttpRequest'])->assertStatus(422);
    }

    public function test_admin_nao_confirma_item_sem_anexo(): void
    {
        $acc = $this->makeAccountability([1]);
        $this->loginAs($this->makeUser('admin'));

        $this->post('/accountability/' . $acc->id . '/item/1/status', ['status' => 'confirmado'])->assertSessionHas('error');
        $this->assertNull($this->statusOf($acc, 1));
    }

    public function test_usuario_comum_nao_altera_status(): void
    {
        $acc  = $this->makeAccountability([1]);
        $user = $this->makeUser('user', [1]);
        $this->makeAttachment($acc, 1, $user);
        $this->loginAs($user);

        $this->postJson('/accountability/' . $acc->id . '/item/1/status', ['status' => 'confirmado'], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertForbidden();
        $this->assertNull($this->statusOf($acc, 1));
    }

    // ---------- E-mail, assistente e IA ----------

    public function test_envio_por_email_valida_permissao_e_destinatario(): void
    {
        $acc = $this->makeAccountability([1]);

        $this->loginAs($this->makeUser('user', [1]));
        $this->post('/accountability/' . $acc->id . '/send-email', ['to_email' => 'a@b.com'])->assertSessionHas('error');

        $this->loginAs($this->makeUser('admin'));
        $this->post('/accountability/' . $acc->id . '/send-email', ['to_email' => ''])->assertSessionHas('error');
        $this->post('/accountability/' . $acc->id . '/send-email', ['to_email' => 'ok@teste.com; invalido'])->assertSessionHas('error');
    }

    public function test_assistente_local_responde_json(): void
    {
        $acc = $this->makeAccountability([1]);
        $this->loginAs($this->makeUser('admin'));

        $this->postJson('/accountability/' . $acc->id . '/assistant', ['question' => 'resumo geral'])
            ->assertOk()
            ->assertJsonStructure(['csrf_hash']);

        $this->postJson('/accountability/999/assistant')->assertNotFound();
    }

    public function test_analise_por_ia_esta_desativada(): void
    {
        $acc  = $this->makeAccountability([1]);
        $user = $this->makeUser('user', [1]);
        $att  = $this->makeAttachment($acc, 1, $user);
        $this->loginAs($this->makeUser('admin'));

        $this->postJson('/accountability/attachment/' . $att->id . '/analyze')->assertStatus(503);
        $this->postJson('/accountability/' . $acc->id . '/analyze-all')->assertStatus(503);
    }
}
