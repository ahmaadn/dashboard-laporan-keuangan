<?php

use App\Models\HppAdjustment;
use App\Models\User;

describe('hpp adjustments', function () {
    it('allows admin to create adjustment', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/reports/hpp-adjustments', [
            'tanggal' => today()->toDateString(),
            'nominal' => -15000,
            'keterangan' => 'Koreksi stok rusak',
        ])->assertCreated();

        expect(HppAdjustment::count())->toBe(1);
        expect((float) HppAdjustment::first()->nominal)->toBe(-15000.0);
    });

    it('blocks pegawai from creating adjustment', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/reports/hpp-adjustments', [
            'tanggal' => today()->toDateString(),
            'nominal' => 1000,
        ])->assertForbidden();
    });

    it('soft deletes adjustment', function () {
        $admin = User::factory()->admin()->create();
        $adj = HppAdjustment::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->deleteJson("/reports/hpp-adjustments/{$adj->id}")
            ->assertOk();

        expect(HppAdjustment::find($adj->id))->toBeNull();
        expect(HppAdjustment::withTrashed()->find($adj->id)->trashed())->toBeTrue();
    });
});
