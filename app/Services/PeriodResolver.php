<?php

namespace App\Services;

use App\Support\AppTimezone;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Resolves period presets (and custom ranges) to [start, end, granularity].
 *
 * Bounds are calendar dates in the display timezone (ISO 8601 wall-clock).
 * DATE columns (tanggal_transaksi) are filtered with those Y-m-d strings.
 * Storage timestamps remain UTC; no manual hour arithmetic.
 *
 * Granularity: "hour" for today, "day" for week/month, "month" for year/long ranges.
 */
final class PeriodResolver
{
    /** @var array<string, string> */
    public const OPTIONS = [
        'bulan_ini' => 'Bulan Ini',
        'hari_ini' => 'Hari Ini',
        'minggu_ini' => 'Minggu Ini',
        'tahun_ini' => 'Tahun Ini',
        'rentang' => 'Rentang Kustom',
    ];

    /**
     * Batas maksimum rentang tanggal kustom (inklusif), dalam hari.
     * 731 hari = 2 tahun termasuk kemungkinan dua tahun kabisat.
     */
    public const MAX_RANGE_DAYS = 731;

    /**
     * @return array{start: CarbonInterface, end: CarbonInterface, granularity: string, timezone: string, start_date: string, end_date: string, start_sql: string, end_sql: string}
     */
    public function resolve(string $period, ?string $start = null, ?string $end = null, ?string $timezone = null): array
    {
        $tz = $timezone ?: AppTimezone::name();
        $today = CarbonImmutable::now($tz)->startOfDay();

        $range = match ($period) {
            'hari_ini' => ['start' => $today->startOfDay(), 'end' => $today->endOfDay(), 'granularity' => 'hour'],
            'minggu_ini' => ['start' => $today->startOfWeek(), 'end' => $today->endOfWeek(), 'granularity' => 'day'],
            'tahun_ini' => ['start' => $today->startOfYear(), 'end' => $today->endOfYear(), 'granularity' => 'month'],
            'rentang' => [
                'start' => CarbonImmutable::parse($start ?: $today->startOfMonth()->toDateString(), $tz)->startOfDay(),
                'end' => CarbonImmutable::parse($end ?: $today->toDateString(), $tz)->endOfDay(),
                'granularity' => $this->customGranularity(
                    $start ?: $today->startOfMonth()->toDateString(),
                    $end ?: $today->toDateString(),
                    $tz,
                ),
            ],
            default => ['start' => $today->startOfMonth(), 'end' => $today->endOfMonth(), 'granularity' => 'day'],
        };

        return $this->withSqlBounds($range, $tz);
    }

    /**
     * Resolve any preset key (including comparison presets) to [start, end, granularity].
     *
     * @return array{start: CarbonInterface, end: CarbonInterface, granularity: string, timezone: string, start_date: string, end_date: string, start_sql: string, end_sql: string}
     */
    public function resolvePreset(string $preset, ?string $start = null, ?string $end = null, ?string $timezone = null): array
    {
        $tz = $timezone ?: AppTimezone::name();
        $today = CarbonImmutable::now($tz)->startOfDay();

        $range = match ($preset) {
            'hari_ini' => ['start' => $today->startOfDay(), 'end' => $today->endOfDay(), 'granularity' => 'hour'],
            'minggu_ini' => ['start' => $today->startOfWeek(), 'end' => $today->endOfWeek(), 'granularity' => 'day'],
            'tahun_ini' => ['start' => $today->startOfYear(), 'end' => $today->endOfYear(), 'granularity' => 'month'],
            'bulan_lalu' => [
                'start' => $today->subMonthNoOverflow()->startOfMonth(),
                'end' => $today->subMonthNoOverflow()->endOfMonth(),
                'granularity' => 'day',
            ],
            'tahun_lalu' => [
                'start' => $today->subYear()->startOfYear(),
                'end' => $today->subYear()->endOfYear(),
                'granularity' => 'month',
            ],
            'rentang' => [
                'start' => CarbonImmutable::parse($start ?: $today->startOfMonth()->toDateString(), $tz)->startOfDay(),
                'end' => CarbonImmutable::parse($end ?: $today->toDateString(), $tz)->endOfDay(),
                'granularity' => $this->customGranularity(
                    $start ?: $today->startOfMonth()->toDateString(),
                    $end ?: $today->toDateString(),
                    $tz,
                ),
            ],
            default => ['start' => $today->startOfMonth(), 'end' => $today->endOfMonth(), 'granularity' => 'day'],
        };

        return $this->withSqlBounds($range, $tz);
    }

    /**
     * @param  array{start: CarbonInterface, end: CarbonInterface, granularity: string}  $range
     * @return array{start: CarbonInterface, end: CarbonInterface, granularity: string, timezone: string, start_date: string, end_date: string, start_sql: string, end_sql: string}
     */
    private function withSqlBounds(array $range, string $tz): array
    {
        $start = CarbonImmutable::parse($range['start']->toDateTimeString(), $tz)->startOfDay();
        $end = CarbonImmutable::parse($range['end']->toDateTimeString(), $tz)->endOfDay();

        // Inclusive SQL bounds: DATE columns may be stored as "Y-m-d 00:00:00".
        // Comparing <= "Y-m-d" fails on SQLite for that datetime form — use end-of-day.
        $range['start'] = $start;
        $range['end'] = $end;
        $range['timezone'] = $tz;
        $range['start_date'] = $start->toDateString();
        $range['end_date'] = $end->toDateString();
        $range['start_sql'] = $start->format('Y-m-d H:i:s');
        $range['end_sql'] = $end->format('Y-m-d H:i:s');

        return $range;
    }

    private function customGranularity(string $start, string $end, string $tz): string
    {
        $days = CarbonImmutable::parse($start, $tz)->startOfDay()
            ->diffInDays(CarbonImmutable::parse($end, $tz)->endOfDay());

        return $days > 31 ? 'month' : 'day';
    }
}
