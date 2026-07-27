<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\User;

describe('report page', function () {
    it('shows report for admin', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'total' => 250000,
        ]);

        $response = $this->actingAs($admin)->get('/reports?period=bulan_ini');

        $response->assertOk()->assertSee('250.000')->assertSee('Laba Kotor')->assertSee('Laba Bersih');
    });

    it('excludes soft-deleted from report', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'total' => 100000,
        ]);
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'total' => 50000,
        ])->delete();

        $response = $this->actingAs($admin)->get('/reports?period=bulan_ini');

        $report = $response->viewData('report');
        expect($report['totalIncome'])->toBe(100000.0);
        expect($report['penjualan'])->toBe(100000.0);
    });

    it('includes hpp adjustment in period', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'jumlah' => 1,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 100000,
        ]);

        $this->actingAs($admin)->postJson('/reports/hpp-adjustments', [
            'tanggal' => today()->toDateString(),
            'nominal' => 10000,
            'keterangan' => 'Koreksi',
        ])->assertCreated();

        $response = $this->actingAs($admin)->get('/reports?period=bulan_ini');
        $report = $response->viewData('report');

        expect($report['hpp'])->toBe(50000.0);
        expect($report['labaKotor'])->toBe(50000.0);
    });
});

describe('pdf export', function () {
    it('downloads as pdf', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/reports/export/pdf?period=bulan_ini');

        $response->assertSuccessful()->assertHeader('Content-Type', 'application/pdf');
        $disposition = (string) $response->headers->get('Content-Disposition');
        expect($disposition)->toContain('laporan-keuangan-')->toContain('.pdf');
    });
});

describe('excel export', function () {
    it('downloads as excel', function () {
        $admin = User::factory()->admin()->create();
        $category = ExpenseCategory::factory()->create();
        Expense::factory()->create([
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
        ]);

        $response = $this->actingAs($admin)->get('/reports/export/excel?period=bulan_ini');

        $response->assertSuccessful()
            ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->assertSee('<Workbook', false)
            ->assertSee('Pemasukan per Produk', false)
            ->assertSee('Pengeluaran per Kategori', false);
    });

    it('excel filename reflects period', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/reports/export/excel?period=tahun_ini');

        expect($response->headers->get('Content-Disposition'))
            ->toStartWith('attachment; filename="laporan-keuangan-')
            ->toEndWith('.xls"');
    });
});
