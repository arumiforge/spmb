<?php

declare(strict_types=1);

namespace Modules\Statistik\Libraries;

/**
 * How counts are written on the Statistik page and in its CSV files: a group
 * of 1 or 2 applicants is shown as "< 3" (and gets no percentage), so nobody
 * can be recognised from a small number (D-111).
 */
final class Angka
{
    /**
     * Counts below this (and above 0) are hidden.
     */
    public const HIDE_BELOW = 3;

    public static function isHidden(int $count): bool
    {
        return $count > 0 && $count < self::HIDE_BELOW;
    }

    /**
     * "0", "< 3" or the number with Indonesian thousands dots.
     */
    public static function show(int $count): string
    {
        return self::isHidden($count) ? lang('Statistik.small') : number_format($count, 0, ',', '.');
    }

    /**
     * Share of $total as "12,5%" (one decimal under 10%), or '' when the count is hidden or the total is 0.
     */
    public static function percent(int $count, int $total): string
    {
        if ($total <= 0 || self::isHidden($count)) {
            return '';
        }

        $pct = $count / $total * 100;

        return number_format($pct, $pct > 0 && $pct < 10 ? 1 : 0, ',', '.') . '%';
    }
}
