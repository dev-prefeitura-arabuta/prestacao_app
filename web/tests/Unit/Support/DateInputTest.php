<?php

namespace Tests\Unit\Support;

use App\Support\DateInput;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DateInputTest extends TestCase
{
    public function test_vazio_retorna_null(): void
    {
        $this->assertNull(DateInput::parse(''));
        $this->assertNull(DateInput::parse('   '));
        $this->assertNull(DateInput::parse(null));
    }

    public function test_converte_formatos_aceitos(): void
    {
        $this->assertSame('2026-12-31', DateInput::parse('31/12/2026'));
        $this->assertSame('2026-12-31', DateInput::parse('2026-12-31'));
        $this->assertSame('2028-02-29', DateInput::parse('29/02/2028'));
    }

    #[DataProvider('invalidas')]
    public function test_recusa_datas_invalidas(string $input): void
    {
        $this->assertFalse(DateInput::parse($input));
    }

    public static function invalidas(): array
    {
        return [
            '31 de fevereiro (BR)'  => ['31/02/2026'],
            '31 de fevereiro (ISO)' => ['2026-02-31'],
            '29/02 ano nao bissexto' => ['29/02/2027'],
            'mes 13'                => ['01/13/2026'],
            'ano muito antigo'      => ['01/01/1999'],
            'ano muito distante'    => ['01/01/2101'],
            'formato americano'     => ['12-31-2026'],
            'texto'                 => ['amanhã'],
        ];
    }
}
