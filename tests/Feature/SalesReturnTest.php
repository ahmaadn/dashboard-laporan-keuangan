<?php

use App\Http\Resources\SalesReturnResource;
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

describe('sales return resource', function () {
    it('exposes nomor_transaksi of the origin income', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'nomor_transaksi' => 'TRX-TEST-0001',
        ]);
        $retur = SalesReturn::factory()->create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
        ]);

        $payload = SalesReturnResource::make($retur->load('income'))->resolve();

        expect($payload['nomor_transaksi'])->toBe('TRX-TEST-0001');
    });
});

describe('sales return origin search', function () {
    it('lists only income lines that still have remaining retur', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['nama' => 'Dompet Kulit']);
        $partial = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'nomor_transaksi' => 'TRX-20260101-0001',
            'jumlah' => 5,
            'harga_satuan' => 100000,
        ]);
        $full = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'nomor_transaksi' => 'TRX-20260101-0002',
            'jumlah' => 3,
            'harga_satuan' => 100000,
        ]);

        SalesReturn::factory()->create(['income_id' => $partial->id, 'product_id' => $product->id, 'user_id' => $admin->id, 'jumlah' => 2]);
        SalesReturn::factory()->create(['income_id' => $full->id, 'product_id' => $product->id, 'user_id' => $admin->id, 'jumlah' => 3]);

        $options = $this->actingAs($admin)->getJson('/sales-returns/search')->assertOk()->json('options');
        $ids = array_column($options, 'id');

        expect($ids)->toContain($partial->id);
        expect($ids)->not->toContain($full->id);

        $row = collect($options)->firstWhere('id', $partial->id);
        expect($row['nomor_transaksi'])->toBe('TRX-20260101-0001');
        expect($row['sisa_retur'])->toBe(3);
        expect($row['nama_produk'])->toBe('Dompet Kulit');
    });

    it('excludes soft-deleted incomes and ignores soft-deleted returs', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $deleted = Income::factory()->softDeleted()->create(['product_id' => $product->id, 'user_id' => $admin->id]);
        $income = Income::factory()->create(['product_id' => $product->id, 'user_id' => $admin->id, 'jumlah' => 4]);

        $retur = SalesReturn::factory()->create(['income_id' => $income->id, 'product_id' => $product->id, 'user_id' => $admin->id, 'jumlah' => 4]);
        $retur->delete();

        $options = $this->actingAs($admin)->getJson('/sales-returns/search')->assertOk()->json('options');
        $ids = array_column($options, 'id');

        expect($ids)->toContain($income->id);
        expect($ids)->not->toContain($deleted->id);
    });

    it('filters by nomor transaksi and product name', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['nama' => 'Sabuk Premium']);
        $matching = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'nomor_transaksi' => 'TRX-20260315-0007',
        ]);
        $other = Income::factory()->create([
            'product_id' => Product::factory()->create(['nama' => 'Ikat Pinggang']),
            'user_id' => $admin->id,
            'nomor_transaksi' => 'TRX-20260315-0008',
        ]);

        $byNomor = $this->actingAs($admin)->getJson('/sales-returns/search?q=20260315-0007')->assertOk()->json('options');
        expect(array_column($byNomor, 'id'))->toBe([$matching->id]);
        expect(array_column($byNomor, 'id'))->not->toContain($other->id);

        $byProduct = $this->actingAs($admin)->getJson('/sales-returns/search?q=Sabuk')->assertOk()->json('options');
        expect(array_column($byProduct, 'id'))->toBe([$matching->id]);
    });

    it('pins the requested income_id into the options', function () {
        $admin = User::factory()->admin()->create();
        $pinned = Income::factory()->create([
            'user_id' => $admin->id,
            'nomor_transaksi' => 'TRX-20190101-0001',
        ]);

        $options = $this->actingAs($admin)
            ->getJson("/sales-returns/search?income_id={$pinned->id}")
            ->assertOk()
            ->json('options');

        expect(array_column($options, 'id'))->toContain($pinned->id);
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
