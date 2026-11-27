<?php

namespace Tests\Unit\Services;

use App\Services\AccountabilityListingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountabilityListingServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, array<string, mixed>> indexado pelo id da prestação */
    private function listFor($user): array
    {
        $rows = (new AccountabilityListingService())->activeFor($this->actorFor($user));

        $byId = [];
        foreach ($rows as $row) {
            $byId[(int) $row['accountability']['id']] = $row;
        }

        return $byId;
    }

    public function test_finalizadas_nao_aparecem_para_ninguem(): void
    {
        $porStatus = $this->makeAccountability([1], ['status' => 'finalizado']);
        $porItens  = $this->makeAccountability([1]);
        $this->setItemStatus($porItens, 1, 'confirmado');
        $aberta = $this->makeAccountability([1]);

        $ids = array_keys($this->listFor($this->makeUser('admin')));

        $this->assertSame([(int) $aberta->id], $ids);
    }

    public function test_pausada_aparece_so_para_admin(): void
    {
        $acc  = $this->makeAccountability([1], ['is_paused' => true]);
        $user = $this->makeUser('user', [1]);

        $this->assertArrayHasKey($acc->id, $this->listFor($this->makeUser('admin')));
        $this->assertArrayNotHasKey($acc->id, $this->listFor($user));
    }

    public function test_usuario_nao_ve_prestacao_sem_itens_liberados_para_ele(): void
    {
        $acc = $this->makeAccountability([1, 2]);

        $this->assertArrayNotHasKey($acc->id, $this->listFor($this->makeUser('user', [7])));
        $this->assertArrayHasKey($acc->id, $this->listFor($this->makeUser('user', [2])));
    }

    public function test_prestacao_ja_enviada_some_para_o_usuario(): void
    {
        $acc  = $this->makeAccountability([1, 2]);
        $user = $this->makeUser('user', [1]);
        $this->makeAttachment($acc, 1, $user);
        $this->setItemStatus($acc, 1, 'enviado');

        $this->assertArrayNotHasKey($acc->id, $this->listFor($user));
        $this->assertArrayHasKey($acc->id, $this->listFor($this->makeUser('admin')));
    }

    public function test_enviada_volta_a_aparecer_se_houver_item_rejeitado(): void
    {
        $acc  = $this->makeAccountability([1, 2]);
        $user = $this->makeUser('user', [1, 2]);
        $this->setItemStatus($acc, 1, 'enviado');
        $this->setItemStatus($acc, 2, 'rejeitado');

        $rows = $this->listFor($user);

        $this->assertArrayHasKey($acc->id, $rows);
        $this->assertSame(1, $rows[$acc->id]['rejected_count']);
    }

    public function test_enviada_volta_a_aparecer_se_houver_anexo_nao_enviado(): void
    {
        $acc  = $this->makeAccountability([1, 2]);
        $user = $this->makeUser('user', [1, 2]);
        $this->setItemStatus($acc, 1, 'enviado');
        $this->makeAttachment($acc, 2, $user); // item 2 ainda pendente, com arquivo

        $this->assertArrayHasKey($acc->id, $this->listFor($user));
    }

    public function test_progresso_e_contadores_consideram_so_itens_visiveis(): void
    {
        $acc   = $this->makeAccountability([1, 2, 3, 4]);
        $user  = $this->makeUser('user', [1, 2]);
        $outro = $this->makeUser('user', [3]);
        $this->makeAttachment($acc, 1, $user, 'a.pdf');
        $this->makeAttachment($acc, 1, $user, 'b.pdf');
        $this->makeAttachment($acc, 3, $outro);

        $row = $this->listFor($user)[$acc->id];

        $this->assertSame(2, $row['attachments_count']); // anexo do item 3 não conta
        $this->assertSame(1, $row['filled_items']);
        $this->assertSame(2, $row['visible_total']);
        $this->assertSame(4, $row['enabled_total']);
        $this->assertSame(50, $row['progress']);

        $admin = $this->listFor($this->makeUser('admin'))[$acc->id];
        $this->assertSame(3, $admin['attachments_count']);
        $this->assertSame(50, $admin['progress']); // 2 de 4 itens com arquivo
    }

    public function test_ordena_da_mais_recente_para_a_mais_antiga(): void
    {
        $a = $this->makeAccountability([1]);
        $b = $this->makeAccountability([1]);

        $this->assertSame([(int) $b->id, (int) $a->id], array_keys($this->listFor($this->makeUser('admin'))));
    }
}
