<?php

namespace App\Support;

/**
 * Validação e formatação de CPF e CNPJ (dígitos verificadores).
 */
final class BrazilianDocument
{
    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    public static function isValidCpf(?string $value): bool
    {
        $cpf = self::digits($value);
        if (strlen($cpf) !== 11 || preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($t = 9; $t < 11; $t++) {
            $sum = 0;
            for ($i = 0; $i < $t; $i++) {
                $sum += (int) $cpf[$i] * (($t + 1) - $i);
            }
            $digit = ((10 * $sum) % 11) % 10;
            if ((int) $cpf[$t] !== $digit) {
                return false;
            }
        }

        return true;
    }

    public static function isValidCnpj(?string $value): bool
    {
        $cnpj = self::digits($value);
        if (strlen($cnpj) !== 14 || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        $weights = [[5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2], [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]];
        foreach ($weights as $pos => $w) {
            $sum = 0;
            foreach ($w as $i => $weight) {
                $sum += (int) $cnpj[$i] * $weight;
            }
            $rest  = $sum % 11;
            $digit = $rest < 2 ? 0 : 11 - $rest;
            if ((int) $cnpj[12 + $pos] !== $digit) {
                return false;
            }
        }

        return true;
    }

    /** CPF ou CNPJ, conforme a quantidade de dígitos. */
    public static function isValidCpfOrCnpj(?string $value): bool
    {
        return strlen(self::digits($value)) === 11 ? self::isValidCpf($value) : self::isValidCnpj($value);
    }

    /** "00.000.000/0000-00" ou "000.000.000-00"; retorna a entrada original se inválida. */
    public static function format(?string $value): string
    {
        $d = self::digits($value);

        if (strlen($d) === 14) {
            return sprintf('%s.%s.%s/%s-%s', substr($d, 0, 2), substr($d, 2, 3), substr($d, 5, 3), substr($d, 8, 4), substr($d, 12));
        }
        if (strlen($d) === 11) {
            return sprintf('%s.%s.%s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3), substr($d, 9));
        }

        return trim((string) $value);
    }
}
