<?php

namespace Tests\Unit\Models;

use App\Models\AccountabilityHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountabilityHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_log_registra_e_limita_o_texto_a_250_caracteres(): void
    {
        AccountabilityHistory::log(1, 2, str_repeat('á', 300));

        $row = AccountabilityHistory::first();
        $this->assertSame(1, (int) $row->accountability_id);
        $this->assertSame(2, (int) $row->user_id);
        $this->assertSame(250, mb_strlen($row->status));
    }

    public function test_log_nunca_lanca_excecao(): void
    {
        Schema::drop('public_accountability_history');

        AccountabilityHistory::log(1, 2, 'sem tabela');

        $this->assertTrue(true);
    }
}
