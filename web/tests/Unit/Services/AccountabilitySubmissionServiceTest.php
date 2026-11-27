<?php

namespace Tests\Unit\Services;

use App\Exceptions\ActionException;
use App\Models\AccountabilityHistory;
use App\Models\AccountabilityItemStatus;
use App\Models\AdminNotification;
use App\Services\AccountabilitySubmissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountabilitySubmissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private AccountabilitySubmissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AccountabilitySubmissionService();
    }

    private function statusOf(int $accId, int $item): ?string
    {
        return AccountabilityItemStatus::where('accountability_id', $accId)->where('item_number', $item)->value('status');
    }

    // ---------- context ----------

    public function test_admin_nao_usa_envio_interno(): void
    {
        $acc = $this->makeAccountability();

        $this->expectException(ActionException::class);
        $this->service->context($this->actorFor($this->makeUser('admin')), $acc->id);
    }

    public function test_prestacao_inexistente(): void
    {
        try {
            $this->service->context($this->actorFor($this->makeUser('user', [1])), 999);
            $this->fail('Esperava 404');
        } catch (ActionException $e) {
            $this->assertSame(404, $e->status());
        }
    }

    public function test_usuario_sem_itens_liberados_na_prestacao(): void
    {
        $acc = $this->makeAccountability([1, 2]);

        try {
            $this->service->context($this->actorFor($this->makeUser('user', [5])), $acc->id);
            $this->fail('Esperava 403');
        } catch (ActionException $e) {
            $this->assertSame(403, $e->status());
        }
    }

    public function test_contexto_cruza_itens_habilitados_permitidos_e_com_arquivo(): void
    {
        $acc  = $this->makeAccountability([1, 2, 3]);
        $user = $this->makeUser('user', [2, 3, 9]);
        $this->makeAttachment($acc, 3, $user);
        $this->makeAttachment($acc, 3, $user, 'outro.pdf');
        $this->makeAttachment($acc, 1, $this->makeUser('user', [1])); // item de outro usuário

        $ctx = $this->service->context($this->actorFor($user), $acc->id);

        $this->assertSame([1, 2, 3], $ctx['enabled']);
        $this->assertSame([2, 3], $ctx['allowed']);
        $this->assertSame([3], $ctx['with_files']);
    }

    // ---------- submit ----------

    public function test_envia_apenas_itens_em_status_reenviavel(): void
    {
        $acc  = $this->makeAccountability([1, 2, 3, 4, 5]);
        $user = $this->makeUser('user', [1, 2, 3, 4, 5]);
        $this->setItemStatus($acc, 2, 'rejeitado');
        $this->setItemStatus($acc, 3, 'prazo_vencido');
        $this->setItemStatus($acc, 4, 'confirmado');
        $this->setItemStatus($acc, 5, 'enviado');

        $result = $this->service->submit($this->actorFor($user), $acc, [1, 2, 3, 4, 5], [1, 2, 3, 4, 5]);

        $this->assertSame(3, $result['sent_count']);
        $this->assertSame('enviado', $this->statusOf($acc->id, 1));
        $this->assertSame('enviado', $this->statusOf($acc->id, 2));
        $this->assertSame('enviado', $this->statusOf($acc->id, 3));
        $this->assertSame('confirmado', $this->statusOf($acc->id, 4));
        $this->assertStringContainsString('(3 item(ns))', AccountabilityHistory::latest('id')->value('status'));
    }

    public function test_all_filled_considera_anexos_de_todos_os_usuarios(): void
    {
        $acc   = $this->makeAccountability([1, 2]);
        $user  = $this->makeUser('user', [1]);
        $outro = $this->makeUser('user', [2]);
        $this->makeAttachment($acc, 1, $user);

        $this->assertFalse($this->service->submit($this->actorFor($user), $acc, [1], [1, 2])['all_filled']);

        $this->makeAttachment($acc, 2, $outro);
        $this->assertTrue($this->service->submit($this->actorFor($user), $acc, [1], [1, 2])['all_filled']);
    }

    public function test_notifica_somente_admins_com_notificacao_ativa(): void
    {
        $acc        = $this->makeAccountability([1]);
        $user       = $this->makeUser('user', [1], ['name' => 'Carlos']);
        $adminAtivo = $this->makeUser('admin');
        $adminMudo  = $this->makeUser('admin', [], ['notifications_enabled' => false]);

        $this->service->submit($this->actorFor($user), $acc, [1], [1]);

        $this->assertSame(1, AdminNotification::where('user_id', $adminAtivo->id)->count());
        $this->assertSame(0, AdminNotification::where('user_id', $adminMudo->id)->count());
        $this->assertSame(0, AdminNotification::where('user_id', $user->id)->count());
        $this->assertStringContainsString('Carlos', AdminNotification::first()->message);
    }
}
