<?php

use App\Models\CapitalInjection;
use App\Models\User;
use App\Support\AppTimezone;

describe('capital injection store', function () {
    it('allows admin to record capital injection', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/capital', [
            'tanggal' => today()->toDateString(),
            'nominal' => 5000000,
            'keterangan' => 'Setoran awal Mei',
        ])->assertCreated();

        expect(CapitalInjection::count())->toBe(1);
        expect((float) CapitalInjection::first()->nominal)->toBe(5000000.0);
        expect(CapitalInjection::first()->user_id)->toBe($admin->id);
    });

    it('blocks pegawai from recording capital injection', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/capital', [
            'tanggal' => today()->toDateString(),
            'nominal' => 1000000,
        ])->assertForbidden();

        expect(CapitalInjection::count())->toBe(0);
    });

    it('validates nominal greater than zero', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/capital', [
            'tanggal' => today()->toDateString(),
            'nominal' => 0,
        ])->assertStatus(422);
    });

    it('blocks future dates', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/capital', [
            'tanggal' => AppTimezone::today()->addDays(2)->toDateString(),
            'nominal' => 100000,
        ])->assertStatus(422);
    });
});

describe('capital injection destroy', function () {
    it('allows admin to soft delete', function () {
        $admin = User::factory()->admin()->create();
        $entry = CapitalInjection::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)->deleteJson("/capital/{$entry->id}")->assertOk();

        expect(CapitalInjection::find($entry->id))->toBeNull();
        expect(CapitalInjection::withTrashed()->find($entry->id)->trashed())->toBeTrue();
    });

    it('blocks pegawai from destroy', function () {
        $pegawai = User::factory()->pegawai()->create();
        $entry = CapitalInjection::factory()->create();

        $this->actingAs($pegawai)->deleteJson("/capital/{$entry->id}")->assertForbidden();
    });
});

describe('capital injection page access', function () {
    it('allows admin', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/capital')->assertOk();
    });

    it('blocks pegawai', function () {
        $pegawai = User::factory()->pegawai()->create();
        $this->actingAs($pegawai)->get('/capital')->assertForbidden();
    });
});
