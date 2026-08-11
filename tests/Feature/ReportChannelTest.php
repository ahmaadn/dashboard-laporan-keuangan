<?php

use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;
use App\Support\AppTimezone;

/**
 * Kartu "Kanal Online vs Offline" harus memakai nilai bersih (penjualan − retur),
 * konsisten dengan kartu Pendapatan Bersih dan komponen yang sama di dashboard.
 */
describe('laporan kanal online vs offline', function () {
    it('reports net value per channel after returns', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $hariIni = AppTimezone::todayDateString();

        $online = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'jenis_transaksi' => 'online',
            'jumlah' => 2,
            'harga_satuan' => 100000,
            'total' => 200000,
        ]);
        SalesReturn::factory()->create([
            'income_id' => $online->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => $hariIni,
            'jumlah' => 1,
            'nominal_retur' => 50000,
        ]);

        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'jenis_transaksi' => 'offline',
            'jumlah' => 1,
            'harga_satuan' => 80000,
            'total' => 80000,
        ]);

        $response = $this->actingAs($admin)->get('/reports?period=bulan_ini');

        $response->assertOk();
        $channel = $response->viewData('report')['incomeByChannel'];

        expect($channel['online']['total'])->toBe(200000);
        expect($channel['online']['retur'])->toBe(50000);
        expect($channel['online']['net_total'])->toBe(150000);
        expect($channel['offline']['net_total'])->toBe(80000);
        expect($channel['offline']['retur'])->toBe(0);
    });

    it('ignores returns whose originating sale was soft-deleted', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $hariIni = AppTimezone::todayDateString();

        $deleted = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'jenis_transaksi' => 'online',
            'jumlah' => 1,
            'harga_satuan' => 90000,
            'total' => 90000,
        ]);
        SalesReturn::factory()->create([
            'income_id' => $deleted->id,
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal' => $hariIni,
            'jumlah' => 1,
            'nominal_retur' => 90000,
        ]);
        $deleted->delete();

        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'jenis_transaksi' => 'online',
            'jumlah' => 1,
            'harga_satuan' => 120000,
            'total' => 120000,
        ]);

        $response = $this->actingAs($admin)->get('/reports?period=bulan_ini');
        $channel = $response->viewData('report')['incomeByChannel'];

        // Retur dari penjualan terhapus tidak boleh mengurangi nilai kanal.
        expect($channel['online']['total'])->toBe(120000);
        expect($channel['online']['retur'])->toBe(0);
        expect($channel['online']['net_total'])->toBe(120000);
    });

    it('renders the net channel value on the report page', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $hariIni = AppTimezone::todayDateString();

        $income = Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => $hariIni,
            'jenis_transaksi' => 'online',
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
            'nominal_retur' => 50000,
        ]);

        $this->actingAs($admin)->get('/reports?period=bulan_ini')
            ->assertOk()
            ->assertSee('Rp 150.000')
            ->assertSee('Online vs Offline');
    });
});
