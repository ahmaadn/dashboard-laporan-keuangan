<?php

use App\Models\CapitalInjection;
use App\Models\Debt;
use App\Models\DebtPayment;
use App\Models\User;
use App\Services\CashBalanceService;
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

    it('records negative capital as linked capital and debt records', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/capital', [
            'tanggal' => today()->toDateString(),
            'nominal' => -750000,
            'keterangan' => 'Piutang pemilik',
        ])->assertCreated()->assertJsonPath('resource.is_hutang', true);

        $capital = CapitalInjection::first();
        $debt = Debt::first();

        expect(CapitalInjection::count())->toBe(1)
            ->and((float) $capital->nominal)->toBe(750000.0)
            ->and($capital->debt_id)->toBeNull()
            ->and($debt->capital_injection_id)->toBe($capital->id)
            ->and((float) $debt->nominal)->toBe(750000.0)
            ->and(app(CashBalanceService::class)->saldo())->toBe(750000.0);
    });

    it('rejects a zero nominal', function () {
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

    it('groups the capital and debt records into one expandable page row', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/capital', [
            'tanggal' => today()->toDateString(),
            'nominal' => -1000000,
            'keterangan' => 'Pinjaman usaha',
        ])->assertCreated();

        $rows = $this->actingAs($admin)->get('/capital')->viewData('modal');

        expect($rows)->toHaveCount(1)
            ->and($rows[0]['is_hutang'])->toBeTrue()
            ->and($rows[0]['nominal'])->toBe(1000000)
            ->and($rows[0]['hutang']['nominal'])->toBe(1000000)
            ->and($rows[0]['hutang']['sisa'])->toBe(1000000);

        $this->actingAs($admin)->get('/capital')
            ->assertOk()
            ->assertSee('<template x-for="row in visibleRows" :key="row.id">', false)
            ->assertSee('<tbody>', false)
            ->assertSee('@click="toggleExpand(row)"', false)
            ->assertSee('x-show="expandedId === row.id"', false);
    });
});

describe('debt payment', function () {
    it('reduces debt and cash when paid from business cash', function () {
        $admin = User::factory()->admin()->create();
        CapitalInjection::factory()->create(['user_id' => $admin->id, 'nominal' => 1000000]);

        $this->actingAs($admin)->postJson('/capital', [
            'tanggal' => today()->toDateString(),
            'nominal' => -750000,
            'keterangan' => 'Pinjaman pemilik',
        ])->assertCreated();

        $debt = Debt::first();
        $this->actingAs($admin)->postJson('/capital/debt-payments', [
            'id_hutang' => $debt->id,
            'tanggal' => today()->toDateString(),
            'nominal' => 250000,
            'sumber' => 'kas_usaha',
        ])->assertCreated()->assertJsonPath('total_hutang', 500000);

        expect(DebtPayment::count())->toBe(1)
            ->and($debt->fresh()->remainingAmount())->toBe(500000.0)
            ->and(app(CashBalanceService::class)->saldo())->toBe(1500000.0);
    });

    it('reduces debt without changing cash when paid from personal funds', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson('/capital', [
            'tanggal' => today()->toDateString(),
            'nominal' => -750000,
        ])->assertCreated();

        $debt = Debt::first();
        $this->actingAs($admin)->postJson('/capital/debt-payments', [
            'id_hutang' => $debt->id,
            'tanggal' => today()->toDateString(),
            'nominal' => 250000,
            'sumber' => 'dana_pribadi',
        ])->assertCreated();

        expect($debt->fresh()->remainingAmount())->toBe(500000.0)
            ->and(app(CashBalanceService::class)->saldo())->toBe(750000.0);
    });

    it('rejects payments above the outstanding debt', function () {
        $admin = User::factory()->admin()->create();
        $debt = Debt::factory()->create(['user_id' => $admin->id, 'nominal' => 100000]);

        $this->actingAs($admin)->postJson('/capital/debt-payments', [
            'id_hutang' => $debt->id,
            'tanggal' => today()->toDateString(),
            'nominal' => 100001,
            'sumber' => 'dana_pribadi',
        ])->assertUnprocessable()->assertJsonValidationErrors('nominal');
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

    it('deletes grouped capital, debt memorandum, debt, and payments together', function () {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->postJson('/capital', [
            'tanggal' => today()->toDateString(),
            'nominal' => -1000000,
        ])->assertCreated();

        $capital = CapitalInjection::whereNull('debt_id')->first();
        $debt = Debt::first();
        DebtPayment::factory()->create([
            'debt_id' => $debt->id,
            'user_id' => $admin->id,
            'nominal' => 100000,
            'sumber' => 'dana_pribadi',
        ]);

        $this->actingAs($admin)->deleteJson("/capital/{$capital->id}")->assertOk();

        expect(CapitalInjection::count())->toBe(0)
            ->and(Debt::count())->toBe(0)
            ->and(DebtPayment::count())->toBe(0);
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
