<?php

use App\Services\PeriodResolver;
use App\Support\AppTimezone;
use Carbon\CarbonImmutable;

it('resolves hari_ini to hour granularity in display timezone', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolve('hari_ini');

    expect($range['granularity'])->toBe('hour');
    expect($range['start']->isSameDay($range['end']))->toBeTrue();
    expect($range['timezone'])->toBe(AppTimezone::name());
    expect($range['start']->toDateString())->toBe(AppTimezone::todayDateString());
});

it('resolves minggu_ini to day granularity', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolve('minggu_ini');

    expect($range['granularity'])->toBe('day');
});

it('resolves bulan_ini to day granularity by default', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolve('bulan_ini');

    expect($range['granularity'])->toBe('day');
    expect($range['start']->isStartOfMonth())->toBeTrue();
    expect($range['end']->isEndOfMonth())->toBeTrue();
});

it('resolves tahun_ini to month granularity', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolve('tahun_ini');

    expect($range['granularity'])->toBe('month');
    expect($range['start']->isStartOfYear())->toBeTrue();
});

it('resolves rentang with custom dates', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolve('rentang', '2026-01-01', '2026-01-15');

    expect($range['granularity'])->toBe('day');
    expect($range['start']->toDateString())->toBe('2026-01-01');
    expect($range['end']->toDateString())->toBe('2026-01-15');
});

it('uses month granularity for long custom ranges', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolve('rentang', '2025-01-01', '2026-06-30');

    expect($range['granularity'])->toBe('month');
});

it('resolves comparison preset bulan_lalu', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolvePreset('bulan_lalu');
    $local = AppTimezone::now();

    expect($range['start']->month)->toBe($local->subMonthNoOverflow()->month);
    expect($range['granularity'])->toBe('day');
});

it('resolves comparison preset tahun_lalu', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolvePreset('tahun_lalu');

    expect($range['start']->year)->toBe(AppTimezone::now()->subYear()->year);
    expect($range['granularity'])->toBe('month');
});

it('provides option labels', function () {
    expect(PeriodResolver::OPTIONS)->toHaveKey('bulan_ini');
    expect(PeriodResolver::OPTIONS['bulan_ini'])->toBe('Bulan Ini');
    expect(PeriodResolver::OPTIONS)->toHaveCount(5);
});

it('uses display timezone for hari_ini near UTC midnight boundary', function () {
    // 2026-07-31 20:00 UTC = 2026-08-01 03:00 Asia/Jakarta — local "today" is Aug 1
    try {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-07-31 20:00:00', 'UTC'));

        $resolver = new PeriodResolver;
        $range = $resolver->resolve('hari_ini');

        expect($range['start']->toDateString())->toBe('2026-08-01');
        expect($range['end']->toDateString())->toBe('2026-08-01');
    } finally {
        CarbonImmutable::setTestNow();
    }
});
