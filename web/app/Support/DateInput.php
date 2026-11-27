<?php

namespace App\Support;

/**
 * Datas digitadas pelo usuário (Web envia AAAA-MM-DD; o app pode enviar DD/MM/AAAA).
 */
final class DateInput
{
    public const MIN_YEAR = 2000;
    public const MAX_YEAR = 2100;

    /**
     * Converte para AAAA-MM-DD. Retorna null se vazio; false se inválido
     * (data inexistente como 31/02 ou fora do intervalo MIN_YEAR..MAX_YEAR).
     */
    public static function parse(?string $input): string|false|null
    {
        $raw = trim((string) $input);
        if ($raw === '') {
            return null;
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $raw, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return false;
        }

        if (! checkdate($mo, $d, $y) || $y < self::MIN_YEAR || $y > self::MAX_YEAR) {
            return false;
        }

        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }
}
