<?php

use App\Models\Income;
use App\Models\Product;
use App\Models\User;

describe('income store', function () {
    it('creates an income with computed total', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['harga' => 100000, 'harga_grosir' => 90000, 'stok' => 50, 'harga_modal' => 40000]);

        $response = $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 2,
            'harga_satuan' => 100000,
            'keterangan' => 'Pelanggan tetap',
        ]);

        $response->assertCreated();
        $income = Income::first();
        expect((float) $income->total)->toBe(200000.0);
        expect($income->user_id)->toBe($pegawai->id);
        expect((float) $income->hpp_satuan)->toBe(40000.0);
        expect($product->fresh()->stok)->toBe(48);
    });

    it('applies grosir price for offline qty >= min', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create([
            'harga' => 100000,
            'harga_grosir' => 90000,
            'min_qty_grosir' => 3,
            'stok' => 20,
            'harga_modal' => 40000,
        ]);

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 3,
            'harga_satuan' => 1,
        ])->assertCreated();

        $income = Income::first();
        expect((float) $income->harga_satuan)->toBe(90000.0);
        expect($income->harga_tipe)->toBe('grosir');
        expect((float) $income->total)->toBe(270000.0);
    });

    it('keeps eceran for online even with large qty', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create([
            'harga' => 100000,
            'harga_grosir' => 90000,
            'min_qty_grosir' => 3,
            'stok' => 20,
        ]);

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'online',
            'jumlah' => 5,
            'harga_satuan' => 1,
        ])->assertCreated();

        $income = Income::first();
        expect((float) $income->harga_satuan)->toBe(100000.0);
        expect($income->harga_tipe)->toBe('eceran');
    });

    it('respects manual price override', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['harga' => 100000, 'stok' => 10]);

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 1,
            'harga_satuan' => 75000,
            'harga_manual' => true,
        ])->assertCreated();

        expect((float) Income::first()->harga_satuan)->toBe(75000.0);
        expect(Income::first()->harga_tipe)->toBe('manual');
    });

    it('rejects qty above stock', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 2]);

        $response = $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 5,
            'harga_satuan' => 100000,
        ]);

        expect($response->status())->toBe(422);
        expect($response->json('errors.jumlah'))->not->toBeEmpty();
    });

    it('restores stock on delete', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 10, 'harga' => 50000]);

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => $product->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 3,
            'harga_satuan' => 50000,
        ])->assertCreated();

        $income = Income::first();
        expect($product->fresh()->stok)->toBe(7);

        $this->actingAs($pegawai)->deleteJson("/income/{$income->id}")->assertOk();
        expect($product->fresh()->stok)->toBe(10);
    });

    it('allows income without product', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'id_produk' => null,
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 1,
            'harga_satuan' => 50000,
            'harga_manual' => true,
        ])->assertCreated();
    });

    it('blocks future dates', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => now()->addDay()->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 1,
            'harga_satuan' => 100000,
        ])->assertStatus(422);
    });

    it('validates jumlah minimum', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
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
            'jenis_transaksi' => 'offline',
            'jumlah' => 5,
            'harga_satuan' => 200000,
            'harga_manual' => true,
        ])->assertOk();
    });

    it('blocks pegawai from updating others transaction', function () {
        $pegawai = User::factory()->pegawai()->create();
        $other = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $other->id]);

        $this->actingAs($pegawai)->putJson("/income/{$income->id}", [
            'tanggal_transaksi' => $income->tanggal_transaksi->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 5,
            'harga_satuan' => 200000,
            'harga_manual' => true,
        ])->assertForbidden();
    });

    it('allows admin to update any transaction', function () {
        $admin = User::factory()->admin()->create();
        $pegawai = User::factory()->pegawai()->create();
        $income = Income::factory()->create(['user_id' => $pegawai->id]);

        $this->actingAs($admin)->putJson("/income/{$income->id}", [
            'tanggal_transaksi' => $income->tanggal_transaksi->toDateString(),
            'jenis_transaksi' => 'offline',
            'jumlah' => 2,
            'harga_satuan' => 50000,
            'harga_manual' => true,
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
