<?php

use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;

describe('sales return creation', function () {
    it('creates a retur and restocks the product', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 10, 'harga' => 100000, 'harga_modal' => 40000]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'jumlah' => 5,
            'harga_satuan' => 100000,
            'total' => 500000,
            'jenis_transaksi' => 'offline',
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => today()->toDateString(),
            'jumlah' => 2,
            'alasan' => 'Barang cacat',
        ])->assertCreated();

        $retur = SalesReturn::first();
        expect($retur->nominal_retur)->toEqual(200000.0); // 2 × 100000
        expect((int) $product->fresh()->stok)->toBe(12); // 10 + 2
    });

    it('rejects retur exceeding remaining income quantity', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 5]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'jumlah' => 3,
            'harga_satuan' => 100000,
            'total' => 300000,
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => today()->toDateString(),
            'jumlah' => 4,
        ])->assertStatus(422);
    });

    it('accepts partial returs across multiple entries but blocks overshoot cumulatively', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 5]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'jumlah' => 3,
            'harga_satuan' => 100000,
            'total' => 300000,
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => today()->toDateString(),
            'jumlah' => 2,
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => today()->toDateString(),
            'jumlah' => 2, // 2 + 2 = 4 > 3 (income.jumlah)
        ])->assertStatus(422);
    });

    it('allows pegawai to record retur', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 5]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $pegawai->id,
            'tanggal_transaksi' => today(),
            'jumlah' => 2,
            'harga_satuan' => 50000,
            'total' => 100000,
        ]);

        $this->actingAs($pegawai)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => today()->toDateString(),
            'jumlah' => 1,
        ])->assertCreated();
    });

    it('validates penjualan exists', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => 99999,
            'tanggal' => today()->toDateString(),
            'jumlah' => 1,
        ])->assertStatus(422);
    });
});

describe('sales return destroy', function () {
    it('only admin can soft delete retur', function () {
        $pegawai = User::factory()->pegawai()->create();
        $admin = User::factory()->admin()->create();
        $retur = SalesReturn::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($pegawai)->deleteJson("/sales-returns/{$retur->id}")->assertForbidden();
        $this->actingAs($admin)->deleteJson("/sales-returns/{$retur->id}")->assertOk();
        expect(SalesReturn::withTrashed()->find($retur->id)->trashed())->toBeTrue();
    });
});

describe('sales return page access', function () {
    it('allows any authenticated user', function () {
        $pegawai = User::factory()->pegawai()->create();
        $this->actingAs($pegawai)->get('/sales-returns')->assertOk();
    });
});
