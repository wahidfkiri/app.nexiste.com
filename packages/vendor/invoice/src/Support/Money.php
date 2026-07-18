<?php

namespace Vendor\Invoice\Support;

/**
 * Formatage monétaire multi-devise basé sur config('invoice.currencies').
 *
 * Chaque devise définit : symbol, position (before|after), decimals,
 * thousands (séparateur de milliers), decimal_sep (séparateur décimal).
 */
class Money
{
    /**
     * Formate un montant dans la devise donnée (ex: 1 250,00 € / $1,250.00).
     */
    public static function format(float|int $amount, string $code, ?int $forceDecimals = null): string
    {
        $code = strtoupper($code);
        $cfg = config("invoice.currencies.{$code}");

        $symbol      = $cfg['symbol']      ?? $code;
        $position    = $cfg['position']    ?? 'after';
        $decimals    = $forceDecimals ?? ($cfg['decimals'] ?? 2);
        $thousands   = $cfg['thousands']   ?? ' ';
        $decimalSep  = $cfg['decimal_sep'] ?? ',';

        $number = number_format((float) $amount, $decimals, $decimalSep, $thousands);

        return $position === 'before'
            ? $symbol . $number
            : $number . ' ' . $symbol;
    }

    /**
     * Symbole d'une devise (repli sur le code si inconnue).
     */
    public static function symbol(string $code): string
    {
        $code = strtoupper($code);

        return (string) config("invoice.currencies.{$code}.symbol", $code);
    }
}
