<?php

namespace Tests\Unit\Services;

use App\Services\OpenWaService;
use PHPUnit\Framework\TestCase;

class OpenWaChatIdTest extends TestCase
{
    public function test_monta_chat_id_com_ddi(): void
    {
        $wa = new OpenWaService();

        $this->assertSame('5549998110169@c.us', $wa->formatChatId('(49) 99811-0169'));
        $this->assertSame('5549998110169@c.us', $wa->formatChatId('5549998110169'));
    }

    public function test_ddd_55_recebe_o_ddi(): void
    {
        // Regressão: antes virava "55991234567@c.us" (sem DDI) e a mensagem não era entregue
        $this->assertSame('5555991234567@c.us', (new OpenWaService())->formatChatId('(55) 99123-4567'));
    }

    public function test_mantem_chat_id_pronto_e_recusa_numero_curto(): void
    {
        $wa = new OpenWaService();

        $this->assertSame('120363@g.us', $wa->formatChatId('120363@g.us'));
        $this->assertSame('', $wa->formatChatId(''));
        $this->assertSame('', $wa->formatChatId('12345'));
    }
}
