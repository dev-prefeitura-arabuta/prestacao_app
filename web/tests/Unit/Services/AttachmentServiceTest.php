<?php

namespace Tests\Unit\Services;

use App\Exceptions\ActionException;
use App\Models\AccountabilityAttachment;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AccountabilitySpreadsheetEntry;
use App\Services\AttachmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AttachmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AttachmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AttachmentService();
    }

    private function assertActionFails(callable $fn, int $status, ?string $message = null): void
    {
        try {
            $fn();
            $this->fail('Era esperada uma ActionException');
        } catch (ActionException $e) {
            $this->assertSame($status, $e->status(), $e->getMessage());
            if ($message !== null) {
                $this->assertStringContainsString($message, $e->getMessage());
            }
        }
    }

    // ---------- assertCanUpload ----------

    public function test_prestacao_inexistente_retorna_404(): void
    {
        $actor = $this->actorFor($this->makeUser('admin'));

        $this->assertActionFails(fn () => $this->service->assertCanUpload($actor, 999, 1), 404);
    }

    public function test_item_nao_habilitado_na_prestacao_e_recusado(): void
    {
        $acc   = $this->makeAccountability([1, 2]);
        $actor = $this->actorFor($this->makeUser('admin'));

        $this->assertActionFails(fn () => $this->service->assertCanUpload($actor, $acc->id, 5), 422, 'Campo de anexo inválido');
        $this->assertActionFails(fn () => $this->service->assertCanUpload($actor, $acc->id, 99), 422);
    }

    public function test_usuario_sem_permissao_no_item_e_recusado(): void
    {
        $acc   = $this->makeAccountability();
        $actor = $this->actorFor($this->makeUser('user', [1]));

        $this->assertActionFails(fn () => $this->service->assertCanUpload($actor, $acc->id, 2), 403, 'Item 2');
    }

    public function test_usuario_nao_envia_em_item_confirmado_mas_admin_sim(): void
    {
        $acc = $this->makeAccountability();
        $this->setItemStatus($acc, 1, 'confirmado');

        $user  = $this->actorFor($this->makeUser('user', [1]));
        $admin = $this->actorFor($this->makeUser('admin'));

        $this->assertActionFails(fn () => $this->service->assertCanUpload($user, $acc->id, 1), 403, 'confirmado');
        $this->assertSame((int) $acc->id, (int) $this->service->assertCanUpload($admin, $acc->id, 1)->id);
    }

    public function test_usuario_com_permissao_pode_enviar(): void
    {
        $acc   = $this->makeAccountability();
        $actor = $this->actorFor($this->makeUser('user', [3]));

        $this->assertSame((int) $acc->id, (int) $this->service->assertCanUpload($actor, $acc->id, 3)->id);
    }

    // ---------- store ----------

    public function test_store_grava_arquivo_e_registro(): void
    {
        $root  = $this->useTempStorage();
        $acc   = $this->makeAccountability();
        $user  = $this->makeUser('user', [4]);
        $file  = UploadedFile::fake()->create('nota fiscal.pdf', 20, 'application/pdf');

        $att = $this->service->store($this->actorFor($user), $acc->id, 4, $file);

        $this->assertSame('nota fiscal.pdf', $att->original_name);
        $this->assertSame('pdf', $att->file_ext);
        $this->assertSame((int) $user->id, (int) $att->user_id);
        $this->assertStringStartsWith("uploads/accountability/{$acc->id}/item_4/", $att->file_path);
        $this->assertFileExists($root . '/app/' . $att->file_path);
        // O nome gravado no disco é aleatório (não usa o nome enviado pelo cliente)
        $this->assertStringNotContainsString('nota', $att->file_name);
    }

    public function test_store_recusa_tipo_invalido_sem_gravar(): void
    {
        $this->useTempStorage();
        $acc  = $this->makeAccountability();
        $user = $this->makeUser('user', [4]);
        $file = UploadedFile::fake()->create('virus.exe', 1, 'application/x-msdownload');

        $this->assertActionFails(fn () => $this->service->store($this->actorFor($user), $acc->id, 4, $file), 422, 'Formato inválido');
        $this->assertSame(0, AccountabilityAttachment::count());
    }

    // ---------- findViewable ----------

    public function test_find_viewable_respeita_permissao_do_item(): void
    {
        $acc   = $this->makeAccountability();
        $owner = $this->makeUser('user', [1]);
        $att   = $this->makeAttachment($acc, 1, $owner);

        $colega   = $this->actorFor($this->makeUser('user', [1]));
        $estranho = $this->actorFor($this->makeUser('user', [2]));

        $this->assertSame((int) $att->id, (int) $this->service->findViewable($colega, $att->id)->id);
        $this->assertActionFails(fn () => $this->service->findViewable($estranho, $att->id), 403);
        $this->assertActionFails(fn () => $this->service->findViewable($colega, 9999), 404);
    }

    // ---------- delete ----------

    public function test_usuario_nao_exclui_anexo_de_outro_usuario(): void
    {
        $acc   = $this->makeAccountability();
        $owner = $this->makeUser('user', [1]);
        $att   = $this->makeAttachment($acc, 1, $owner);
        $outro = $this->actorFor($this->makeUser('user', [1]));

        $this->assertActionFails(fn () => $this->service->delete($outro, $att->id), 403, 'enviados por você');
        $this->assertNotNull(AccountabilityAttachment::find($att->id));
    }

    public function test_usuario_nao_exclui_anexo_de_item_confirmado(): void
    {
        $acc   = $this->makeAccountability();
        $owner = $this->makeUser('user', [1]);
        $att   = $this->makeAttachment($acc, 1, $owner);
        $this->setItemStatus($acc, 1, 'confirmado');

        $this->assertActionFails(fn () => $this->service->delete($this->actorFor($owner), $att->id), 403, 'confirmado');
    }

    public function test_dono_exclui_anexo_arquivo_planilha_e_status_volta_a_pendente(): void
    {
        $root  = $this->useTempStorage();
        $acc   = $this->makeAccountability();
        $owner = $this->makeUser('user', [1]);
        $att   = $this->makeAttachment($acc, 1, $owner, 'recibo.pdf');
        $this->setItemStatus($acc, 1, 'rejeitado');

        mkdir(dirname($root . '/app/' . $att->file_path), 0775, true);
        file_put_contents($root . '/app/' . $att->file_path, 'x');

        $base = ['accountability_id' => $acc->id, 'item_number' => 1, 'amount' => 10];
        AccountabilitySpreadsheetEntry::create($base + ['attachment_id' => $att->id]);
        AccountabilitySpreadsheetEntry::create($base + ['notes' => 'Extraído de recibo.pdf']);
        $outra = AccountabilitySpreadsheetEntry::create($base + ['notes' => 'lançamento manual']);

        $this->service->delete($this->actorFor($owner), $att->id);

        $this->assertNull(AccountabilityAttachment::find($att->id));
        $this->assertFileDoesNotExist($root . '/app/' . $att->file_path);
        $this->assertSame([(int) $outra->id], AccountabilitySpreadsheetEntry::pluck('id')->map(fn ($v) => (int) $v)->all());
        $this->assertSame('pendente', AccountabilityItemStatus::where('accountability_id', $acc->id)->where('item_number', 1)->value('status'));
        $this->assertStringContainsString("Removeu anexo 'recibo.pdf'", AccountabilityHistory::latest('id')->value('status'));
    }

    public function test_status_nao_muda_se_ainda_restam_anexos_no_item(): void
    {
        $this->useTempStorage();
        $acc   = $this->makeAccountability();
        $owner = $this->makeUser('user', [1]);
        $att   = $this->makeAttachment($acc, 1, $owner, 'a.pdf');
        $this->makeAttachment($acc, 1, $owner, 'b.pdf');
        $this->setItemStatus($acc, 1, 'enviado');

        $this->service->delete($this->actorFor($owner), $att->id);

        $this->assertSame('enviado', AccountabilityItemStatus::where('accountability_id', $acc->id)->where('item_number', 1)->value('status'));
    }

    public function test_admin_exclui_anexo_de_qualquer_usuario_mesmo_confirmado(): void
    {
        $this->useTempStorage();
        $acc   = $this->makeAccountability();
        $owner = $this->makeUser('user', [1]);
        $att   = $this->makeAttachment($acc, 1, $owner);
        $this->setItemStatus($acc, 1, 'confirmado');

        $this->service->delete($this->actorFor($this->makeUser('admin')), $att->id);

        $this->assertNull(AccountabilityAttachment::find($att->id));
    }
}
