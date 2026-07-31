<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Display / aggregation timezone (ISO 8601). Storage stays UTC (app.timezone).
 * Never add/subtract hours manually — always convert via Carbon.
 */
final class AppTimezone
{
    public static function name(): string
    {
        return (string) config('app.display_timezone', 'Asia/Jakarta');
    }

    public static function now(): CarbonImmutable
    {
        return CarbonImmutable::now(self::name());
    }

    public static function today(): CarbonImmutable
    {
        return self::now()->startOfDay();
    }

    /** Local calendar date as Y-m-d (for DATE columns / form defaults). */
    public static function todayDateString(): string
    {
        return self::today()->toDateString();
    }

    public static function toLocal(CarbonInterface|string|null $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $dt = $value instanceof CarbonInterface
            ? CarbonImmutable::parse($value->toIso8601String(), 'UTC')
            : CarbonImmutable::parse($value, 'UTC');

        return $dt->setTimezone(self::name());
    }

    /** Format UTC datetime for display in user timezone (ISO date parts). */
    public static function formatLocal(CarbonInterface|string|null $value, string $format = 'Y-m-d H:i:s'): ?string
    {
        $local = self::toLocal($value);

        return $local?->format($format);
    }
}
