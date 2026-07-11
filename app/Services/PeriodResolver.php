<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Resolves period presets (and custom ranges) to [start, end, granularity].
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
     * @return array{start: CarbonInterface, end: CarbonInterface, granularity: string}
     */
    public function resolve(string $period, ?string $start = null, ?string $end = null): array
    {
        $today = CarbonImmutable::today();

        return match ($period) {
            'hari_ini' => ['start' => $today->startOfDay(), 'end' => $today->endOfDay(), 'granularity' => 'hour'],
            'minggu_ini' => ['start' => $today->startOfWeek(), 'end' => $today->endOfWeek(), 'granularity' => 'day'],
            'tahun_ini' => ['start' => $today->startOfYear(), 'end' => $today->endOfYear(), 'granularity' => 'month'],
            'rentang' => [
                'start' => CarbonImmutable::parse($start ?: $today->startOfMonth())->startOfDay(),
                'end' => CarbonImmutable::parse($end ?: $today)->endOfDay(),
                'granularity' => $this->customGranularity($start ?: $today->startOfMonth()->toDateString(), $end ?: $today->toDateString()),
            ],
            default => ['start' => $today->startOfMonth(), 'end' => $today->endOfMonth(), 'granularity' => 'day'],
        };
    }

    /**
     * Resolve any preset key (including comparison presets) to [start, end, granularity].
     *
     * @return array{start: CarbonInterface, end: CarbonInterface, granularity: string}
     */
    public function resolvePreset(string $preset, ?string $start = null, ?string $end = null): array
    {
        $today = CarbonImmutable::today();

        return match ($preset) {
            'hari_ini' => ['start' => $today->startOfDay(), 'end' => $today->endOfDay(), 'granularity' => 'hour'],
            'minggu_ini' => ['start' => $today->startOfWeek(), 'end' => $today->endOfWeek(), 'granularity' => 'day'],
            'tahun_ini' => ['start' => $today->startOfYear(), 'end' => $today->endOfYear(), 'granularity' => 'month'],
            'bulan_lalu' => ['start' => $today->subMonth()->startOfMonth(), 'end' => $today->subMonth()->endOfMonth(), 'granularity' => 'day'],
            'tahun_lalu' => ['start' => $today->subYear()->startOfYear(), 'end' => $today->subYear()->endOfYear(), 'granularity' => 'month'],
            'rentang' => [
                'start' => CarbonImmutable::parse($start ?: $today->startOfMonth())->startOfDay(),
                'end' => CarbonImmutable::parse($end ?: $today)->endOfDay(),
                'granularity' => $this->customGranularity($start ?: $today->startOfMonth()->toDateString(), $end ?: $today->toDateString()),
            ],
            default => ['start' => $today->startOfMonth(), 'end' => $today->endOfMonth(), 'granularity' => 'day'],
        };
    }

    private function customGranularity(string $start, string $end): string
    {
        $days = CarbonImmutable::parse($start)->startOfDay()->diffInDays(CarbonImmutable::parse($end)->endOfDay());

        return $days > 31 ? 'month' : 'day';
    }
}
