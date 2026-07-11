<?php

use App\Models\Income;
use App\Models\Product;
use App\Models\User;

describe('income store', function () {
    it('creates an income with computed total', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['harga' => 100000]);

        $response = $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jumlah' => 3,
            'harga_satuan' => 100000,
            'keterangan' => 'Pelanggan tetap',
        ]);

        $response->assertCreated();
        $income = Income::first();
        expect((float) $income->total)->toBe(300000.0);
        expect($income->user_id)->toBe($pegawai->id);
    });

    it('allows income without product', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => null,
            'tanggal_transaksi' => today()->toDateString(),
            'jumlah' => 1,
            'harga_satuan' => 50000,
        ])->assertCreated();
    });

    it('blocks future dates', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => now()->addDay()->toDateString(),
            'jumlah' => 1,
            'harga_satuan' => 100000,
        ])->assertStatus(422);
    });

    it('validates jumlah minimum', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jumlah' => 0,
            'harga_satuan' => 100000,
        ])->assertStatus(422);
    });
});

describe('income ownership', function () {
    it('allows owner pegawai to update own transaction', function () {
        $pegawai = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $pegawai->id]);

        $this->actingAs($pegawai)->putJson("/income/{$income->id}", [
            'tanggal_transaksi' => $income->tanggal_transaksi->toDateString(),
            'jumlah' => 5,
            'harga_satuan' => 200000,
        ])->assertOk();
    });

    it('blocks pegawai from updating others transaction', function () {
        $pegawai = User::factory()->pegawai()->create();
        $other = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $other->id]);

        $this->actingAs($pegawai)->putJson("/income/{$income->id}", [
            'tanggal_transaksi' => $income->tanggal_transaksi->toDateString(),
            'jumlah' => 5,
            'harga_satuan' => 200000,
        ])->assertForbidden();
    });

    it('allows admin to update any transaction', function () {
        $admin = User::factory()->admin()->create();
        $pegawai = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $pegawai->id]);

        $this->actingAs($admin)->putJson("/income/{$income->id}", [
            'tanggal_transaksi' => $income->tanggal_transaksi->toDateString(),
            'jumlah' => 2,
            'harga_satuan' => 50000,
        ])->assertOk();
    });

    it('allows owner to delete own transaction', function () {
        $pegawai = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $pegawai->id]);

        $this->actingAs($pegawai)->deleteJson("/income/{$income->id}")->assertOk();
        expect(Income::find($income->id))->toBeNull();
        expect(Income::withTrashed()->find($income->id)->trashed())->toBeTrue();
    });

    it('blocks pegawai from deleting others transaction', function () {
        $pegawai = User::factory()->pegawai()->create();
        $other = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $other->id]);

        $this->actingAs($pegawai)->deleteJson("/income/{$income->id}")->assertForbidden();
    });
});
