<?php

namespace Tests\Unit\Models;

use App\Models\Accountability;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AccountabilityItemsTest extends TestCase
{
    public function test_existem_24_itens_com_versao_curta_correspondente(): void
    {
        $this->assertCount(24, Accountability::getItems());
        $this->assertSame(array_keys(Accountability::getItems()), array_keys(Accountability::getShortItems()));
    }

    #[DataProvider('enabledItemsSemFiltro')]
    public function test_sem_filtro_valido_todos_os_itens_ficam_habilitados(mixed $raw): void
    {
        $enabled = Accountability::getEnabledItemsForAccountability(['enabled_items' => $raw]);

        $this->assertCount(24, $enabled);
    }

    public static function enabledItemsSemFiltro(): array
    {
        return [
            'nulo'              => [null],
            'string vazia'      => [''],
            'json invalido'     => ['nao-e-json'],
            'array vazio json'  => ['[]'],
            'so itens inexistentes' => ['[99, 100]'],
        ];
    }

    public function test_filtra_itens_habilitados_por_json(): void
    {
        $enabled = Accountability::getEnabledItemsForAccountability(['enabled_items' => '[3, "5", 24]']);

        $this->assertSame([3, 5, 24], array_keys($enabled));
    }

    public function test_aceita_array_ja_decodificado(): void
    {
        $enabled = Accountability::getEnabledItemsForAccountability(['enabled_items' => [1, 2]]);

        $this->assertSame([1, 2], array_keys($enabled));
    }

    #[DataProvider('statusFinalizados')]
    public function test_status_explicito_finaliza_a_prestacao(string $status): void
    {
        $this->assertTrue(Accountability::isFinalized(['status' => $status]));
    }

    public static function statusFinalizados(): array
    {
        return [['completed'], ['finalizado'], ['Finalizada'], [' concluido '], ['concluida']];
    }

    public function test_finalizada_quando_todos_os_itens_habilitados_estao_confirmados(): void
    {
        $acc = ['status' => 'aberta', 'enabled_items' => '[1, 2]'];

        $this->assertTrue(Accountability::isFinalized($acc, [1 => 'confirmado', 2 => 'confirmado']));
        $this->assertFalse(Accountability::isFinalized($acc, [1 => 'confirmado', 2 => 'enviado']));
        $this->assertFalse(Accountability::isFinalized($acc, [1 => 'confirmado']));
    }

    public function test_aberta_sem_status_de_itens_nao_esta_finalizada(): void
    {
        $this->assertFalse(Accountability::isFinalized(['status' => 'aberta']));
    }
}
