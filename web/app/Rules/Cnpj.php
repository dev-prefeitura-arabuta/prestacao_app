<?php

namespace App\Rules;

use App\Support\BrazilianDocument;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! BrazilianDocument::isValidCnpj(is_scalar($value) ? (string) $value : '')) {
            $fail('Informe um CNPJ válido.');
        }
    }
}
