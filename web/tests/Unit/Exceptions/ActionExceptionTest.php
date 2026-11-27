<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\ActionException;
use PHPUnit\Framework\TestCase;

class ActionExceptionTest extends TestCase
{
    public function test_status_padrao_e_422(): void
    {
        $e = new ActionException('Erro');

        $this->assertSame(422, $e->status());
        $this->assertSame('Erro', $e->getMessage());
    }

    public function test_construtores_nomeados_definem_o_status_http(): void
    {
        $this->assertSame(403, ActionException::forbidden('x')->status());
        $this->assertSame(404, ActionException::notFound('x')->status());
        $this->assertSame(413, ActionException::tooLarge('x')->status());
    }
}
