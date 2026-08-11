<?php

use App\Models\CapitalInjection;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;
use App\Services\Reports\ExportRenderer;
use App\Support\AppTimezone;

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

    it('renders the detailed sections in the pdf view', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['nama' => 'Dompet Kulit Sapi']);
        $category = ExpenseCategory::factory()->create(['nama' => 'Operasional', 'is_bahan_baku' => false]);
        $hariIni = AppTimezone::todayDateString();

        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'jenis_transaksi' => 'online',
            'jumlah' => 10,
            'harga_satuan' => 10000,
            'hpp_satuan' => 4000,
            'total' => 100000,
        ]);
        SalesReturn::factory()->create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => $hariIni,
            'jumlah' => 3,
            'nominal_retur' => 30000,
        ]);
        Expense::factory()->create([
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'nominal' => 20000,
        ]);

        $report = app(ExportRenderer::class)->report('bulan_ini', null, null);
        $html = view('reports.export.pdf', ['report' => $report])->render();

        // Laba rugi bertingkat, kanal, dan jurnal kas harus ikut tercetak.
        expect($html)
            ->toContain('Struktur Laba Rugi')
            ->toContain('Kanal Penjualan')
            ->toContain('Pemasukan per Produk')
            ->toContain('Jurnal Arus Kas Bersih')
            ->toContain('Dompet Kulit Sapi');

        // Qty bersih 7 dari 10, dan nilai bersih Rp 70.000 (bukan bruto 100.000).
        expect($html)->toContain('7 (dari 10)');
        expect($html)->toContain('Rp 70.000');
    });
});

describe('jurnal arus kas', function () {
    it('lists every cash movement with a running balance', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $category = ExpenseCategory::factory()->create(['is_bahan_baku' => false]);
        $hariIni = AppTimezone::todayDateString();

        CapitalInjection::factory()->create([
            'user_id' => $admin->id,
            'tanggal' => $hariIni,
            'nominal' => 500000,
        ]);
        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'jumlah' => 2,
            'harga_satuan' => 100000,
            'total' => 200000,
        ]);
        SalesReturn::factory()->create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => $hariIni,
            'jumlah' => 1,
            'nominal_retur' => 100000,
        ]);
        Expense::factory()->create([
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'nominal' => 50000,
        ]);

        $journal = $this->actingAs($admin)
            ->get('/reports?period=bulan_ini')
            ->viewData('report')['cashJournal'];

        expect($journal['totalMasuk'])->toBe(700000);   // 500.000 modal + 200.000 penjualan
        expect($journal['totalKeluar'])->toBe(150000);  // 50.000 beban + 100.000 retur
        expect(count($journal['entries']))->toBe(4);

        // Saldo akhir = saldo awal + mutasi, dan sama dengan arus kas bersih periode.
        expect($journal['saldoAkhir'])->toBe($journal['saldoAwal'] + 700000 - 150000);
        expect($journal['saldoAkhir'] - $journal['saldoAwal'])->toBe(550000);

        // Saldo baris terakhir harus sama dengan saldo akhir.
        expect(end($journal['entries'])['saldo'])->toBe($journal['saldoAkhir']);
    });

    it('carries the opening balance from movements before the period', function () {
        $admin = User::factory()->admin()->create();
        $hariIni = AppTimezone::today();

        CapitalInjection::factory()->create([
            'user_id' => $admin->id,
            'tanggal' => $hariIni->subMonthNoOverflow()->startOfMonth()->toDateString(),
            'nominal' => 400000,
        ]);

        $journal = $this->actingAs($admin)
            ->get('/reports?period=bulan_ini')
            ->viewData('report')['cashJournal'];

        // Setoran bulan lalu tidak muncul sebagai baris, tetapi menjadi saldo awal.
        expect($journal['saldoAwal'])->toBe(400000);
        expect($journal['entries'])->toBe([]);
        expect($journal['saldoAkhir'])->toBe(400000);
    });

    it('renders the journal card on the report page', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'total' => 250000,
        ]);

        $this->actingAs($admin)->get('/reports?period=bulan_ini')
            ->assertOk()
            ->assertSee('Jurnal Arus Kas Bersih')
            ->assertSee('Saldo Awal')
            ->assertSee('Saldo Akhir');
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
            ->assertSee('Kanal Penjualan', false)
            ->assertSee('Pengeluaran per Kategori', false);
    });

    it('includes the cash journal and net product columns', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $hariIni = AppTimezone::todayDateString();

        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'jumlah' => 10,
            'harga_satuan' => 10000,
            'total' => 100000,
        ]);
        SalesReturn::factory()->create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => $hariIni,
            'jumlah' => 3,
            'nominal_retur' => 30000,
        ]);

        $this->actingAs($admin)->get('/reports/export/excel?period=bulan_ini')
            ->assertSuccessful()
            ->assertSee('Jurnal Arus Kas Bersih', false)
            ->assertSee('Qty Bersih', false)
            ->assertSee('Total Bersih', false)
            // Qty bersih 7 dan nilai bersih 70000 harus ada di spreadsheet.
            ->assertSee('>7<', false)
            ->assertSee('>70000<', false);
    });

    it('excel filename reflects period', function () {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/reports/export/excel?period=tahun_ini');

        expect($response->headers->get('Content-Disposition'))
            ->toStartWith('attachment; filename="laporan-keuangan-')
            ->toEndWith('.xls"');
    });
});
