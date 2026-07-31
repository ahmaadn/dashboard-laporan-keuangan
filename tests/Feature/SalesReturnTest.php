<?php

use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use App\Models\User;
use App\Support\AppTimezone;

describe('sales return creation', function () {
    it('creates a retur and restocks the product', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 10, 'harga' => 100000, 'harga_modal' => 40000]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 5,
            'harga_satuan' => 100000,
            'total' => 500000,
            'jenis_transaksi' => 'offline',
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 2,
            'alasan' => 'Barang cacat',
        ])->assertCreated();

        $retur = SalesReturn::first();
        expect($retur->nominal_retur)->toEqual(200000.0);
        expect((int) $product->fresh()->stok)->toBe(12);
        expect($income->fresh()->statusTransaksi())->toBe('retur_sebagian');
    });

    it('rejects retur exceeding remaining income quantity', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 5]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 3,
            'harga_satuan' => 100000,
            'total' => 300000,
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 4,
        ])->assertStatus(422);
    });

    it('accepts partial returs across multiple entries but blocks overshoot cumulatively', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 5]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 3,
            'harga_satuan' => 100000,
            'total' => 300000,
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 2,
        ])->assertCreated();

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 2,
        ])->assertStatus(422);
    });

    it('marks status semua_diretur when fully returned', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 5]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 5,
            'harga_satuan' => 100000,
            'total' => 500000,
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 5,
        ])->assertCreated();

        expect($income->fresh()->statusTransaksi())->toBe('semua_diretur');
        expect($income->fresh()->sisaRetur())->toBe(0);
    });

    it('allows pegawai to record retur', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 5]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $pegawai->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 2,
            'harga_satuan' => 50000,
            'total' => 100000,
        ]);

        $this->actingAs($pegawai)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 1,
        ])->assertCreated();
    });

    it('validates penjualan exists', function () {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => 99999,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 1,
        ])->assertStatus(422);
    });
});

describe('sales return destroy', function () {
    it('only admin can soft delete retur and reverses stock', function () {
        $pegawai = User::factory()->pegawai()->create();
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 10]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'jumlah' => 5,
            'harga_satuan' => 100000,
            'total' => 500000,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 5,
        ])->assertCreated();

        expect((int) $product->fresh()->stok)->toBe(15);

        $retur = SalesReturn::first();

        $this->actingAs($pegawai)->deleteJson("/sales-returns/{$retur->id}")->assertForbidden();
        $this->actingAs($admin)->deleteJson("/sales-returns/{$retur->id}")->assertOk();

        expect(SalesReturn::withTrashed()->find($retur->id)->trashed())->toBeTrue();
        expect((int) $product->fresh()->stok)->toBe(10);
        expect(StockMovement::where('sumber', 'retur')->where('ref_id', $retur->id)->where('jenis', 'keluar')->exists())->toBeTrue();
    });

    it('prevents double stock when re-returning after delete', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 10]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'jumlah' => 5,
            'harga_satuan' => 100000,
            'total' => 500000,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 5,
        ])->assertCreated();

        $retur = SalesReturn::first();
        $this->actingAs($admin)->deleteJson("/sales-returns/{$retur->id}")->assertOk();

        expect((int) $product->fresh()->stok)->toBe(10);
        expect($income->fresh()->sisaRetur())->toBe(5);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 5,
        ])->assertCreated();

        expect((int) $product->fresh()->stok)->toBe(15);
        expect(SalesReturn::where('income_id', $income->id)->count())->toBe(1);
    });
});

describe('sales return page access', function () {
    it('requires authentication', function () {
        $this->get('/sales-returns')->assertRedirect('/login');
    });

    it('allows authenticated users', function () {
        $user = User::factory()->pegawai()->create();
        $this->actingAs($user)->get('/sales-returns')->assertOk();
    });
});

describe('income status and retur history payload', function () {
    it('includes status and retur history on income page', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['stok' => 10]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'jumlah' => 5,
            'harga_satuan' => 100000,
            'total' => 500000,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
        ]);

        $this->actingAs($admin)->postJson('/sales-returns', [
            'id_penjualan' => $income->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 2,
        ])->assertCreated();

        $response = $this->actingAs($admin)->get('/income');
        $response->assertOk();
        $html = $response->getContent();
        expect(str_contains($html, 'retur_sebagian') || str_contains($html, 'Retur sebagian'))->toBeTrue();
    });
});
