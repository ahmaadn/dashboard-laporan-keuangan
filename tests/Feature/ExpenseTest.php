<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;

describe('expense store', function () {
    it('creates an expense', function () {
        $pegawai = User::factory()->pegawai()->create();
        $category = ExpenseCategory::factory()->create();

        $response = $this->actingAs($pegawai)->postJson('/expenses', [
            'id_kategori' => $category->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 150000,
            'keterangan' => 'Restok bulanan',
        ]);

        $response->assertCreated();
        $expense = Expense::first();
        expect($expense->user_id)->toBe($pegawai->id);
        expect((float) $expense->nominal)->toBe(150000.0);
    });

    it('validates category required', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/expenses', [
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 100000,
        ])->assertStatus(422);
    });

    it('validates nominal greater than zero', function () {
        $pegawai = User::factory()->pegawai()->create();
        $category = ExpenseCategory::factory()->create();

        $this->actingAs($pegawai)->postJson('/expenses', [
            'id_kategori' => $category->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 0,
        ])->assertStatus(422);
    });

    it('blocks future dates', function () {
        $pegawai = User::factory()->pegawai()->create();
        $category = ExpenseCategory::factory()->create();

        $this->actingAs($pegawai)->postJson('/expenses', [
            'id_kategori' => $category->id,
            'tanggal_transaksi' => now()->addDay()->toDateString(),
            'nominal' => 100000,
        ])->assertStatus(422);
    });
});

describe('expense ownership', function () {
    it('blocks pegawai from updating others expense', function () {
        $pegawai = User::factory()->pegawai()->create();
        $other = User::factory()->pegawai()->create();
        $expense = Expense::factory()->create(['user_id' => $other->id]);

        $this->actingAs($pegawai)->putJson("/expenses/{$expense->id}", [
            'id_kategori' => $expense->category_id,
            'tanggal_transaksi' => $expense->tanggal_transaksi->toDateString(),
            'nominal' => 50000,
        ])->assertForbidden();
    });

    it('allows owner to delete own expense', function () {
        $pegawai = User::factory()->pegawai()->create();
        $expense = Expense::factory()->create(['user_id' => $pegawai->id]);

        $this->actingAs($pegawai)->deleteJson("/expenses/{$expense->id}")->assertOk();
        expect(Expense::find($expense->id))->toBeNull();
    });

    it('allows admin to delete any expense', function () {
        $admin = User::factory()->admin()->create();
        $pegawai = User::factory()->pegawai()->create();
        $expense = Expense::factory()->create(['user_id' => $pegawai->id]);

        $this->actingAs($admin)->deleteJson("/expenses/{$expense->id}")->assertOk();
    });
});
