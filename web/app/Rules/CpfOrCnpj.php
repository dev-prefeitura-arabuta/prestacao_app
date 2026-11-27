<?php

namespace App\Rules;

use App\Support\BrazilianDocument;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** CPF (11 dígitos) ou CNPJ (14 dígitos) válido. Útil para fornecedores na planilha. */
class CpfOrCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! BrazilianDocument::isValidCpfOrCnpj(is_scalar($value) ? (string) $value : '')) {
            $fail('Informe um CPF ou CNPJ válido.');
        }
    }
}
