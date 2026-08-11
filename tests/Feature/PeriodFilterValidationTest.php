<?php

use App\Models\User;
use App\Services\PeriodResolver;
use App\Support\AppTimezone;
use Carbon\CarbonImmutable;

/**
 * Batas "hari ini" mengikuti timezone tampilan (Asia/Jakarta), bukan UTC.
 * Memakai `today()` (UTC) membuat test rapuh saat UTC dan Jakarta beda tanggal.
 */
beforeEach(function () {
    $this->hariIni = AppTimezone::today();
});

describe('dashboard period filter validation', function () {
    it('rejects a start date in the future', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/dashboard?period=rentang&start='.$this->hariIni->addDay()->toDateString().'&end='.$this->hariIni->toDateString())
            ->assertStatus(422)
            ->assertJsonValidationErrors('start');
    });

    it('rejects an end date in the future', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/dashboard?period=rentang&start='.$this->hariIni->toDateString().'&end='.$this->hariIni->addMonth()->toDateString())
            ->assertStatus(422)
            ->assertJsonValidationErrors('end');
    });

    it('rejects a start date greater than the end date', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson(
            '/api/dashboard?period=rentang&start='.$this->hariIni->toDateString().'&end='.$this->hariIni->subMonth()->toDateString(),
        );

        $response->assertStatus(422)->assertJsonValidationErrors('start');
        expect($response->json('errors.start.0'))->toContain('tidak boleh melebihi tanggal akhir');
    });

    it('rejects a range longer than the maximum', function () {
        $admin = User::factory()->admin()->create();
        $end = $this->hariIni;
        $start = $end->subDays(PeriodResolver::MAX_RANGE_DAYS);

        $response = $this->actingAs($admin)->getJson(
            '/api/dashboard?period=rentang&start='.$start->toDateString().'&end='.$end->toDateString(),
        );

        $response->assertStatus(422)->assertJsonValidationErrors('end');
        expect($response->json('errors.end.0'))->toContain('maksimal '.PeriodResolver::MAX_RANGE_DAYS.' hari');
    });

    it('accepts a range exactly at the maximum length', function () {
        $admin = User::factory()->admin()->create();
        $end = $this->hariIni;
        $start = $end->subDays(PeriodResolver::MAX_RANGE_DAYS - 1);

        $this->actingAs($admin)->getJson(
            '/api/dashboard?period=rentang&start='.$start->toDateString().'&end='.$end->toDateString(),
        )->assertOk();
    });

    it('accepts a valid range', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson(
            '/api/dashboard?period=rentang&start='.$this->hariIni->subDays(7)->toDateString().'&end='.$this->hariIni->toDateString(),
        )->assertOk();
    });

    it('rejects an unknown period preset', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/dashboard?period=abad_ini')
            ->assertStatus(422)
            ->assertJsonValidationErrors('period');
    });

    it('rejects a start date before the business start year', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->getJson(
            '/api/dashboard?period=rentang&start=2017-12-31&end='.$this->hariIni->toDateString(),
        );

        $response->assertStatus(422)->assertJsonValidationErrors('start');
        expect($response->json('errors.start.0'))->toContain(AppTimezone::TANGGAL_MULAI_USAHA);
    });

    it('rejects an end date before the business start year', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/dashboard?period=rentang&start=2017-01-01&end=2017-06-30')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start', 'end']);
    });

    it('accepts the business start date itself as the lower bound', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson(
            '/api/dashboard?period=rentang&start='.AppTimezone::TANGGAL_MULAI_USAHA
            .'&end='.CarbonImmutable::parse(AppTimezone::TANGGAL_MULAI_USAHA)->addDays(10)->toDateString(),
        )->assertOk();
    });
});

describe('dashboard comparison validation', function () {
    it('rejects a future date on side A', function () {
        $admin = User::factory()->admin()->create();

        // Awal dan akhir sama-sama di masa depan; keduanya harus ditolak.
        $this->actingAs($admin)->getJson(
            '/api/dashboard/compare?a=rentang&b=bulan_ini&a_start='.$this->hariIni->addDay()->toDateString().'&a_end='.$this->hariIni->addDays(2)->toDateString(),
        )->assertStatus(422)->assertJsonValidationErrors(['a_start', 'a_end']);
    });

    it('rejects an inverted range on side B', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson(
            '/api/dashboard/compare?a=bulan_ini&b=rentang&b_start='.$this->hariIni->toDateString().'&b_end='.$this->hariIni->subMonth()->toDateString(),
        )->assertStatus(422)->assertJsonValidationErrors('b_start');
    });

    it('rejects an over-long range on side B', function () {
        $admin = User::factory()->admin()->create();
        $end = $this->hariIni;
        $start = $end->subDays(PeriodResolver::MAX_RANGE_DAYS);

        $this->actingAs($admin)->getJson(
            '/api/dashboard/compare?a=bulan_ini&b=rentang&b_start='.$start->toDateString().'&b_end='.$end->toDateString(),
        )->assertStatus(422)->assertJsonValidationErrors('b_end');
    });

    it('accepts valid comparison presets', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson('/api/dashboard/compare?a=bulan_lalu&b=bulan_ini')->assertOk();
    });

    it('rejects a pre-2018 date on either comparison side', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson(
            '/api/dashboard/compare?a=rentang&b=bulan_ini&a_start=2017-05-01&a_end=2017-05-31',
        )->assertStatus(422)->assertJsonValidationErrors(['a_start', 'a_end']);

        $this->actingAs($admin)->getJson(
            '/api/dashboard/compare?a=bulan_ini&b=rentang&b_start=2017-05-01&b_end=2017-05-31',
        )->assertStatus(422)->assertJsonValidationErrors(['b_start', 'b_end']);
    });
});

describe('report period filter validation', function () {
    it('shows a validation message and no report data for an inverted range', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(
            '/reports?period=rentang&start='.$this->hariIni->toDateString().'&end='.$this->hariIni->subMonth()->toDateString(),
        );

        $response->assertOk()->assertViewHas('report', null);
        expect($response->viewData('filterError'))->toContain('tidak boleh melebihi tanggal akhir');
    });

    it('shows a validation message for a future date', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(
            '/reports?period=rentang&start='.$this->hariIni->toDateString().'&end='.$this->hariIni->addDay()->toDateString(),
        );

        $response->assertOk()->assertViewHas('report', null);
        expect($response->viewData('filterError'))->toContain('melebihi hari ini');
    });

    it('shows a validation message for an over-long range', function () {
        $admin = User::factory()->admin()->create();
        $end = $this->hariIni;
        $start = $end->subDays(PeriodResolver::MAX_RANGE_DAYS);

        $response = $this->actingAs($admin)->get(
            '/reports?period=rentang&start='.$start->toDateString().'&end='.$end->toDateString(),
        );

        $response->assertOk()->assertViewHas('report', null);
        expect($response->viewData('filterError'))->toContain('maksimal '.PeriodResolver::MAX_RANGE_DAYS.' hari');
    });

    it('renders the report for a valid range', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(
            '/reports?period=rentang&start='.$this->hariIni->subDays(7)->toDateString().'&end='.$this->hariIni->toDateString(),
        );

        $response->assertOk();
        expect($response->viewData('filterError'))->toBeNull();
        expect($response->viewData('report'))->toBeArray();
    });

    it('shows a validation message for a date before the business start year', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(
            '/reports?period=rentang&start=2017-01-01&end='.$this->hariIni->toDateString(),
        );

        $response->assertOk()->assertViewHas('report', null);
        expect($response->viewData('filterError'))->toContain(AppTimezone::TANGGAL_MULAI_USAHA);
    });

    it('exposes the minimum date to the view for the date inputs', function () {
        $admin = User::factory()->admin()->create();

        // Input tanggal hanya dirender pada mode rentang kustom.
        $response = $this->actingAs($admin)->get(
            '/reports?period=rentang&start='.$this->hariIni->subDays(7)->toDateString().'&end='.$this->hariIni->toDateString(),
        );

        $response->assertOk()->assertViewHas('tanggalMulaiUsaha', AppTimezone::TANGGAL_MULAI_USAHA);
        $response->assertSee('min="'.AppTimezone::TANGGAL_MULAI_USAHA.'"', false);
    });

    it('offers a clear action that returns to the default period', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(
            '/reports?period=rentang&start='.$this->hariIni->subDays(7)->toDateString().'&end='.$this->hariIni->toDateString(),
        );

        $response->assertOk()->assertSee('Bersihkan');
        $response->assertSee('?period=bulan_ini', false);
    });
});

describe('dashboard period filter view', function () {
    it('passes the business start date into the alpine component', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/dashboard');

        $response->assertOk()->assertViewHas('tanggalMulaiUsaha', AppTimezone::TANGGAL_MULAI_USAHA);
        $response->assertSee(AppTimezone::TANGGAL_MULAI_USAHA, false);
    });

    it('renders the clear range controls', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('clearRange()', false)
            ->assertSee('clearCompareA()', false)
            ->assertSee('clearCompareB()', false);
    });
});
