<?php

use App\Models\CapitalInjection;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;

describe('pendapatan bersih — retur as income reducer', function () {
    it('retur reduces pendapatan bersih without reducing laba kotor base', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['harga' => 100000, 'harga_modal' => 40000]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'jumlah' => 3,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 300000,
        ]);

        SalesReturn::create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => today(),
            'jumlah' => 1,
            'nominal_retur' => 100000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        // Pendapatan Bersih = 300000 - 100000 = 200000
        // HPP = 3 * 40000 = 120000
        // Laba Kotor = 200000 - 120000 = 80000
        expect($response->json('summary.penjualan'))->toBe(300000);
        expect($response->json('summary.returTotal'))->toBe(100000);
        expect($response->json('summary.pendapatanBersih'))->toBe(200000);
        expect($response->json('summary.labaKotor'))->toBe(80000);
    });
});

describe('arus kas bersih — modal injection increases kas', function () {
    it('modal increases arus kas masuk but not pendapatanBersih or labaBersih', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['harga' => 100000, 'harga_modal' => 40000]);
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'jumlah' => 1,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 100000,
        ]);

        CapitalInjection::create([
            'user_id' => $admin->id,
            'tanggal' => today(),
            'nominal' => 1000000,
            'keterangan' => 'Setoran awal',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        expect($response->json('summary.modalTotal'))->toBe(1000000);
        expect($response->json('summary.arusKasMasuk'))->toBe(1100000);
        expect($response->json('summary.arusKasBersih'))->toBe(1100000);
        // Pendapatan Bersih unchanged from sales only
        expect($response->json('summary.pendapatanBersih'))->toBe(100000);
        // Laba Bersih = Laba Kotor - Beban Ops
        // HPP = 40000; Laba Kotor = 60000; Laba Bersih = 60000
        expect($response->json('summary.labaBersih'))->toBe(60000);
    });
});

describe('periode kalender boundary', function () {
    it('filters by tanggal_transaksi not created_at', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => now()->subMonths(2),
            'total' => 100000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');
        expect($response->json('summary.penjualan'))->toBe(0);
    });

    it('minggu_ini uses Senin-Minggu calendar week', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        // Seed something far in the past and assert tidak masuk minggu ini
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => now()->subMonths(3),
            'total' => 100000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=minggu_ini');
        expect($response->json('summary.penjualan'))->toBe(0);
    });
});

describe('soft-delete exclusion', function () {
    it('excludes soft-deleted sales returns from retur total', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'jumlah' => 5,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 500000,
        ]);
        $retur = SalesReturn::create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => today(),
            'jumlah' => 1,
            'nominal_retur' => 100000,
        ]);
        $retur->delete();

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');
        expect($response->json('summary.returTotal'))->toBe(0);
        expect($response->json('summary.pendapatanBersih'))->toBe(500000);
    });

    it('excludes soft-deleted capital from modalTotal', function () {
        $admin = User::factory()->admin()->create();
        $entry = CapitalInjection::create([
            'user_id' => $admin->id,
            'tanggal' => today(),
            'nominal' => 500000,
        ]);
        $entry->delete();

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');
        expect($response->json('summary.modalTotal'))->toBe(0);
    });
});

describe('laporan tiered breakdown', function () {
    it('exposes the full chain in laporan view', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['harga' => 100000, 'harga_modal' => 40000]);
        $bahanBaku = ExpenseCategory::factory()->bahanBaku()->create();
        $ops = ExpenseCategory::factory()->create(['is_bahan_baku' => false]);

        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'jumlah' => 2,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 200000,
        ]);
        Expense::factory()->create([
            'category_id' => $bahanBaku->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'nominal' => 50000,
        ]);
        Expense::factory()->create([
            'category_id' => $ops->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'nominal' => 30000,
        ]);

        $response = $this->actingAs($admin)->get('/reports?period=bulan_ini');
        $report = $response->viewData('report');

        expect($report['penjualan'])->toBe(200000.0);
        expect($report['pendapatanBersih'])->toBe(200000.0);
        expect($report['hpp'])->toBe(80000.0);
        expect($report['labaKotor'])->toBe(120000.0);
        expect($report['biayaOperasional'])->toBe(30000.0);
        expect($report['pembelianBahanBaku'])->toBe(50000.0);
        expect($report['labaBersih'])->toBe(90000.0);
        expect($report['arusKasBersih'])->toBe(200000.0 - 80000.0); // 120000
    });
});
