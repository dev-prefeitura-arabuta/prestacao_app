<?php

namespace Tests\Unit\Support;

use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    #[DataProvider('validos')]
    public function test_aceita_e_formata_numeros_validos(string $input, string $expected): void
    {
        $this->assertTrue(Phone::isValid($input));
        $this->assertSame($expected, Phone::format($input));
    }

    public static function validos(): array
    {
        return [
            'celular com mascara'      => ['(49) 99999-9999', '(49) 99999-9999'],
            'celular so digitos'       => ['49998110169', '(49) 99811-0169'],
            'celular com DDI e +'      => ['+55 49 99811-0169', '(49) 99811-0169'],
            'celular com DDI colado'   => ['5549998110169', '(49) 99811-0169'],
            'celular com zero do DDD'  => ['049 99811-0169', '(49) 99811-0169'],
            'fixo'                     => ['(49) 3333-4444', '(49) 3333-4444'],
            'fixo com DDI'             => ['554933334444', '(49) 3333-4444'],
            'DDD 55 nao vira DDI'      => ['(55) 99123-4567', '(55) 99123-4567'],
            'DDD 11 sao paulo'         => ['11 91234-5678', '(11) 91234-5678'],
        ];
    }

    #[DataProvider('invalidos')]
    public function test_recusa_numeros_invalidos(string $input): void
    {
        $this->assertFalse(Phone::isValid($input));
        $this->assertNull(Phone::format($input));
    }

    public static function invalidos(): array
    {
        return [
            'vazio'                         => [''],
            'texto'                         => ['nao tenho'],
            'sem DDD'                       => ['99999-9999'],
            'DDD inexistente (20)'          => ['(20) 99999-9999'],
            'DDD inexistente (10)'          => ['(10) 99999-9999'],
            'celular antigo sem o 9'        => ['49 8829-6525'],
            'celular 11 digitos sem 9'      => ['(49) 89999-9999'],
            'fixo comecando com 1'          => ['(49) 1333-4444'],
            'curto'                         => ['4999'],
            'longo demais'                  => ['+55 49 99999-9999 123'],
        ];
    }

    public function test_is_mobile(): void
    {
        $this->assertTrue(Phone::isMobile('(49) 99999-9999'));
        $this->assertFalse(Phone::isMobile('(49) 3333-4444'));
        $this->assertFalse(Phone::isMobile('lixo'));
    }

    public function test_to_international(): void
    {
        $this->assertSame('5549998110169', Phone::toInternational('(49) 99811-0169'));
        $this->assertSame('5555991234567', Phone::toInternational('(55) 99123-4567'), 'DDD 55 deve receber o DDI 55');
        $this->assertSame('554933334444', Phone::toInternational('49 3333-4444'));
        $this->assertSame('149998110169', Phone::toInternational('(49) 99811-0169', '1'));
        $this->assertNull(Phone::toInternational(''));
        $this->assertNull(Phone::toInternational('12345'));
    }

    public function test_to_international_mantem_numeros_antigos_fora_do_padrao(): void
    {
        // Cadastros anteriores à validação (ex: celular sem o 9) continuam sendo enviados como antes
        $this->assertSame('554988296525', Phone::toInternational('49 8829-6525'));
        $this->assertSame('5549882965251', Phone::toInternational('5549882965251'));
    }
}
