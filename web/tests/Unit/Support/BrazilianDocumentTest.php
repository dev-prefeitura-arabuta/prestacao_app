<?php

namespace Tests\Unit\Support;

use App\Support\BrazilianDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BrazilianDocumentTest extends TestCase
{
    #[DataProvider('cnpjs')]
    public function test_valida_cnpj(string $cnpj, bool $valid): void
    {
        $this->assertSame($valid, BrazilianDocument::isValidCnpj($cnpj));
    }

    public static function cnpjs(): array
    {
        return [
            'valido com mascara'   => ['11.222.333/0001-81', true],
            'valido so digitos'    => ['11222333000181', true],
            'digito errado'        => ['11.222.333/0001-82', false],
            'todos iguais'         => ['11.111.111/1111-11', false],
            'curto'                => ['11.222.333/0001', false],
            'vazio'                => ['', false],
            'cpf no lugar do cnpj' => ['529.982.247-25', false],
        ];
    }

    #[DataProvider('cpfs')]
    public function test_valida_cpf(string $cpf, bool $valid): void
    {
        $this->assertSame($valid, BrazilianDocument::isValidCpf($cpf));
    }

    public static function cpfs(): array
    {
        return [
            'valido com mascara' => ['529.982.247-25', true],
            'valido so digitos'  => ['52998224725', true],
            'digito errado'      => ['529.982.247-26', false],
            'todos iguais'       => ['111.111.111-11', false],
            'curto'              => ['529.982.247', false],
        ];
    }

    public function test_cpf_ou_cnpj(): void
    {
        $this->assertTrue(BrazilianDocument::isValidCpfOrCnpj('529.982.247-25'));
        $this->assertTrue(BrazilianDocument::isValidCpfOrCnpj('11.222.333/0001-81'));
        $this->assertFalse(BrazilianDocument::isValidCpfOrCnpj('123'));
    }

    public function test_formata(): void
    {
        $this->assertSame('11.222.333/0001-81', BrazilianDocument::format('11222333000181'));
        $this->assertSame('529.982.247-25', BrazilianDocument::format(' 52998224725 '));
        $this->assertSame('', BrazilianDocument::format(null));
    }
}
