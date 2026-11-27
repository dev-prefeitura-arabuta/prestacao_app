<?php

namespace App\Rules;

use App\Support\Phone;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Telefone brasileiro válido (DDD existente; celular com 9 dígitos ou fixo com 8).
 * Use com 'nullable' quando o campo for opcional.
 */
class BrazilianPhone implements ValidationRule
{
    public function __construct(private readonly bool $mobileOnly = false)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = is_scalar($value) ? (string) $value : '';

        if (! Phone::isValid($value)) {
            $fail('Informe um telefone válido com DDD, ex: (49) 99999-9999.');
            return;
        }

        if ($this->mobileOnly && ! Phone::isMobile($value)) {
            $fail('Informe um número de celular (com o 9 na frente), ex: (49) 99999-9999.');
        }
    }
}
