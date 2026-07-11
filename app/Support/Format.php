<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Formatting helpers shared across Blade directives, resources, and exports.
 */
final class Format
{
    /** Format an integer/float as Rupiah ("Rp 1.250.000"). */
    public static function rupiah(int|float|string $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    /** Short Indonesian table date ("03/07/2026"). */
    public static function tanggal(?string $date): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    /** Long Indonesian date ("3 Juli 2026"). */
    public static function tanggalLengkap(?string $date): string
    {
        if (! $date) {
            return '-';
        }

        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $d = Carbon::parse($date);

        return $d->day.' '.$months[$d->month - 1].' '.$d->year;
    }
}
