<?php

namespace App\Support;

/**
 * Telefones brasileiros (celular e fixo) para cadastro e envio de WhatsApp.
 *
 * Aceita entradas como "(49) 99999-9999", "49999999999", "+55 49 99999-9999" ou "5549999999999".
 * Formato armazenado: "(49) 99999-9999" (celular) ou "(49) 3333-4444" (fixo).
 */
final class Phone
{
    /** DDDs válidos no Brasil (Anatel). */
    private const DDDS = [
        11, 12, 13, 14, 15, 16, 17, 18, 19,
        21, 22, 24, 27, 28,
        31, 32, 33, 34, 35, 37, 38,
        41, 42, 43, 44, 45, 46, 47, 48, 49,
        51, 53, 54, 55,
        61, 62, 63, 64, 65, 66, 67, 68, 69,
        71, 73, 74, 75, 77, 79,
        81, 82, 83, 84, 85, 86, 87, 88, 89,
        91, 92, 93, 94, 95, 96, 97, 98, 99,
    ];

    /**
     * Número nacional só com dígitos (DDD + número, 10 ou 11 dígitos) ou null se inválido.
     */
    public static function normalize(?string $input): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $input) ?? '';

        // Remove o DDI 55 quando informado (12 ou 13 dígitos)
        if (in_array(strlen($digits), [12, 13], true) && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }
        // Remove o zero de discagem interurbana (ex: 049 99999-9999)
        if (in_array(strlen($digits), [11, 12], true) && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        $len = strlen($digits);
        if ($len !== 10 && $len !== 11) {
            return null;
        }

        if (! in_array((int) substr($digits, 0, 2), self::DDDS, true)) {
            return null;
        }

        $first = $digits[2];
        if ($len === 11) {
            // Celular: 9 + 8 dígitos
            return $first === '9' ? $digits : null;
        }

        // Fixo: começa com 2 a 5
        return in_array($first, ['2', '3', '4', '5'], true) ? $digits : null;
    }

    public static function isValid(?string $input): bool
    {
        return self::normalize($input) !== null;
    }

    public static function isMobile(?string $input): bool
    {
        $n = self::normalize($input);

        return $n !== null && strlen($n) === 11;
    }

    /**
     * Formato de exibição/armazenamento: "(49) 99999-9999" ou "(49) 3333-4444".
     * Retorna null para entradas vazias ou inválidas.
     */
    public static function format(?string $input): ?string
    {
        $n = self::normalize($input);
        if ($n === null) {
            return null;
        }

        $ddd  = substr($n, 0, 2);
        $rest = substr($n, 2);

        return strlen($rest) === 9
            ? sprintf('(%s) %s-%s', $ddd, substr($rest, 0, 5), substr($rest, 5))
            : sprintf('(%s) %s-%s', $ddd, substr($rest, 0, 4), substr($rest, 4));
    }

    /**
     * Número internacional só com dígitos (ex: 5549999999999) para wa.me / OpenWA.
     *
     * Números já cadastrados fora do padrão continuam funcionando: se não for possível validar,
     * usa os dígitos informados e só acrescenta o DDI quando o número tem 10 ou 11 dígitos.
     * Retorna null se não houver dígitos suficientes.
     */
    public static function toInternational(?string $input, string $countryCode = '55'): ?string
    {
        $cc = preg_replace('/\D/', '', $countryCode) ?: '55';

        $n = self::normalize($input);
        if ($n !== null) {
            return $cc . $n;
        }

        $digits = preg_replace('/\D/', '', (string) $input) ?? '';
        if (strlen($digits) < 10) {
            return null;
        }

        return strlen($digits) <= 11 ? $cc . $digits : $digits;
    }
}
