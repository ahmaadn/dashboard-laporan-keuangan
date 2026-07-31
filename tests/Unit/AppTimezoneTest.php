<?php

use App\Support\AppTimezone;
use Carbon\CarbonImmutable;

it('keeps app storage timezone as UTC', function () {
    expect(config('app.timezone'))->toBe('UTC');
});

it('defaults display timezone to Asia/Jakarta', function () {
    expect(AppTimezone::name())->toBe('Asia/Jakarta');
});

it('converts utc datetime to local without manual hour math', function () {
    $utc = CarbonImmutable::parse('2026-07-31 17:00:00', 'UTC');
    $local = AppTimezone::toLocal($utc);

    expect($local->toDateString())->toBe('2026-08-01');
    expect($local->format('H:i'))->toBe('00:00');
    expect($local->timezoneName)->toBe('Asia/Jakarta');
});

it('formats local display as ISO-friendly datetime string', function () {
    $formatted = AppTimezone::formatLocal('2026-07-31 17:30:00');

    expect($formatted)->toBe('2026-08-01 00:30:00');
});
