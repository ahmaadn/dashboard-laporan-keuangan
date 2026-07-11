<?php

use App\Services\PeriodResolver;

it('resolves hari_ini to hour granularity', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolve('hari_ini');

    expect($range['granularity'])->toBe('hour');
    expect($range['start']->isSameDay($range['end']))->toBeTrue();
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

    expect($range['start']->month)->toBe(now()->subMonth()->month);
    expect($range['granularity'])->toBe('day');
});

it('resolves comparison preset tahun_lalu', function () {
    $resolver = new PeriodResolver;
    $range = $resolver->resolvePreset('tahun_lalu');

    expect($range['start']->year)->toBe(now()->subYear()->year);
    expect($range['granularity'])->toBe('month');
});

it('provides option labels', function () {
    expect(PeriodResolver::OPTIONS)->toHaveKey('bulan_ini');
    expect(PeriodResolver::OPTIONS['bulan_ini'])->toBe('Bulan Ini');
    expect(PeriodResolver::OPTIONS)->toHaveCount(5);
});
