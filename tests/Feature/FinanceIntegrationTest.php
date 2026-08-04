<?php

use App\Models\CapitalInjection;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;
use App\Support\AppTimezone;

describe('pendapatan bersih — retur as income reducer', function () {
    it('retur reduces pendapatan bersih and HPP for net sold units', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['harga' => 100000, 'harga_modal' => 40000]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 3,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 300000,
        ]);

        SalesReturn::create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 1,
            'nominal_retur' => 100000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        // Pendapatan Bersih = 300000 - 100000 = 200000
        // HPP net = (3 - 1) * 40000 = 80000
        // Laba Kotor = 200000 - 80000 = 120000
        expect($response->json('summary.penjualan'))->toBe(300000);
        expect($response->json('summary.returTotal'))->toBe(100000);
        expect($response->json('summary.pendapatanBersih'))->toBe(200000);
        expect($response->json('summary.hpp'))->toBe(80000);
        expect($response->json('summary.labaKotor'))->toBe(120000);
    });

    it('reduces HPP proportionally for partial multi-unit returns', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['harga' => 200000, 'harga_modal' => 120000]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 5,
            'harga_satuan' => 200000,
            'hpp_satuan' => 120000,
            'total' => 1000000,
        ]);

        SalesReturn::create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 3,
            'nominal_retur' => 600000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        // Net qty 2 → pendapatan 400000, HPP 240000, laba kotor 160000
        expect($response->json('summary.penjualan'))->toBe(1000000);
        expect($response->json('summary.returTotal'))->toBe(600000);
        expect($response->json('summary.pendapatanBersih'))->toBe(400000);
        expect($response->json('summary.hpp'))->toBe(240000);
        expect($response->json('summary.labaKotor'))->toBe(160000);
    });
});

describe('arus kas bersih — modal injection increases kas', function () {
    it('modal increases arus kas masuk but not pendapatanBersih or labaBersih', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['harga' => 100000, 'harga_modal' => 40000]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 1,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 100000,
        ]);

        // Retur 20000 (uang dikembalikan ke pelanggan) — ikut arus kas keluar.
        SalesReturn::create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 1,
            'nominal_retur' => 20000,
        ]);

        CapitalInjection::create([
            'user_id' => $admin->id,
            'tanggal' => AppTimezone::todayDateString(),
            'nominal' => 1000000,
            'keterangan' => 'Setoran awal',
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        expect($response->json('summary.modalTotal'))->toBe(1000000);
        expect($response->json('summary.arusKasMasuk'))->toBe(1100000); // 100k jualan + 1jt modal
        // Arus kas keluar = retur 20k (tidak ada beban/bahan baku di test ini)
        expect($response->json('summary.arusKasKeluar'))->toBe(20000);
        expect($response->json('summary.arusKasBersih'))->toBe(1080000);
        // Pendapatan Bersih = 100k - 20k = 80k (retur tetap pengurang pendapatan)
        expect($response->json('summary.pendapatanBersih'))->toBe(80000);
        // HPP net = 1 * 40000 - 1 * 40000 = 0; Laba Kotor = 80000; Laba Bersih = 80000
        expect($response->json('summary.labaBersih'))->toBe(80000);
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
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 5,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 500000,
        ]);
        $retur = SalesReturn::create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => AppTimezone::todayDateString(),
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
            'tanggal' => AppTimezone::todayDateString(),
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
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 2,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 200000,
        ]);
        Expense::factory()->create([
            'category_id' => $bahanBaku->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'nominal' => 50000,
        ]);
        Expense::factory()->create([
            'category_id' => $ops->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
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
        // Arus Kas Bersih = arusKasMasuk - arusKasKeluar
        //   arusKasMasuk = 200000 (jualan, tidak ada modal di test ini)
        //   arusKasKeluar = 50000 (bahan) + 30000 (operasional) = 80000
        expect($report['arusKasMasuk'])->toBe(200000.0);
        expect($report['arusKasKeluar'])->toBe(80000.0);
        expect($report['arusKasBersih'])->toBe(120000.0);
    });
});

describe('arus kas bersih — retur uang keluar', function () {
    it('retur penjualan mengurangi arus kas bersih (uang kembali ke pelanggan)', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['harga' => 100000, 'harga_modal' => 40000]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 3,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 300000,
        ]);

        SalesReturn::create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 1,
            'nominal_retur' => 100000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        // Arus kas masuk = penjualan + modal (tidak ada modal di sini) = 300000
        expect($response->json('summary.arusKasMasuk'))->toBe(300000);
        // Arus kas keluar = pengeluaranKas (0) + retur 100000
        expect($response->json('summary.arusKasKeluar'))->toBe(100000);
        expect($response->json('summary.arusKasBersih'))->toBe(200000);
        expect($response->json('summary.returKeluar'))->toBe(100000);
    });
});

describe('retur dari income yang di-soft-delete diabaikan', function () {
    it('tidak ikut menjumlahkan returTotal saat penjualan sumbernya dihapus', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 5,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 500000,
        ]);
        SalesReturn::create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => AppTimezone::todayDateString(),
            'jumlah' => 1,
            'nominal_retur' => 100000,
        ]);

        // Income dihapus (soft delete) tanpa menghapus retur.
        $income->delete();

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        // Penjualan ikut hilang karena income di-trash; retur juga harus hilang.
        expect($response->json('summary.penjualan'))->toBe(0);
        expect($response->json('summary.returTotal'))->toBe(0);
        expect($response->json('summary.pendapatanBersih'))->toBe(0);
        expect($response->json('summary.arusKasKeluar'))->toBe(0);
    });
});
