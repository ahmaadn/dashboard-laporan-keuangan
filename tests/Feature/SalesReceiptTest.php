<?php

use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;

describe('sales receipt', function () {
    it('returns the receipt grouped by nomor transaksi', function () {
        $pegawai = User::factory()->pegawai()->create(['nama' => 'Dimas']);
        $productA = Product::factory()->create(['nama' => 'Dompet Kulit', 'stok' => 10]);
        $productB = Product::factory()->create(['nama' => 'Ikat Pinggang', 'stok' => 10]);

        $nomor = 'TRX-20260808-0001';
        Income::factory()->create([
            'nomor_transaksi' => $nomor,
            'product_id' => $productA->id,
            'user_id' => $pegawai->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jumlah' => 2,
            'harga_satuan' => 150000,
            'total' => 300000,
        ]);
        Income::factory()->create([
            'nomor_transaksi' => $nomor,
            'product_id' => $productB->id,
            'user_id' => $pegawai->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jumlah' => 1,
            'harga_satuan' => 90000,
            'total' => 90000,
        ]);

        $response = $this->actingAs($pegawai)->getJson("/income/nota/{$nomor}");

        $response->assertOk();
        expect($response->json('nota.nomor_transaksi'))->toBe($nomor);
        expect($response->json('nota.items'))->toHaveCount(2);
        expect($response->json('nota.total_qty'))->toBe(3);
        expect($response->json('nota.subtotal'))->toBe(390000);
        expect($response->json('nota.total'))->toBe(390000);
        expect($response->json('nota.kasir'))->toBe('Dimas');
    });

    it('subtracts returns from the receipt total', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 10]);
        $nomor = 'TRX-20260808-0002';

        $income = Income::factory()->create([
            'nomor_transaksi' => $nomor,
            'product_id' => $product->id,
            'user_id' => $pegawai->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jumlah' => 2,
            'harga_satuan' => 100000,
            'total' => 200000,
        ]);

        SalesReturn::factory()->create([
            'income_id' => $income->id,
            'product_id' => $product->id,
            'user_id' => $pegawai->id,
            'tanggal' => today()->toDateString(),
            'jumlah' => 1,
            'nominal_retur' => 100000,
        ]);

        $response = $this->actingAs($pegawai)->getJson("/income/nota/{$nomor}");

        $response->assertOk();
        expect($response->json('nota.subtotal'))->toBe(200000);
        expect($response->json('nota.total_retur'))->toBe(100000);
        expect($response->json('nota.total'))->toBe(100000);
        expect($response->json('nota.items.0.jumlah_diretur'))->toBe(1);
    });

    it('returns 404 for an unknown nomor transaksi', function () {
        $pegawai = User::factory()->pegawai()->create();

        $this->actingAs($pegawai)->getJson('/income/nota/TRX-TIDAK-ADA')->assertNotFound();
    });

    it('requires authentication', function () {
        $this->get('/income/nota/TRX-20260808-0001')->assertRedirect('/login');
    });

    it('downloads the receipt as pdf', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 10]);
        $nomor = 'TRX-20260808-0003';

        Income::factory()->create([
            'nomor_transaksi' => $nomor,
            'product_id' => $product->id,
            'user_id' => $pegawai->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jumlah' => 1,
            'harga_satuan' => 75000,
            'total' => 75000,
        ]);

        $response = $this->actingAs($pegawai)->get("/income/nota/{$nomor}/pdf");

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('application/pdf');
    });

    it('generates a nomor transaksi for every new sale', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 10, 'harga' => 50000]);

        $response = $this->actingAs($pegawai)->postJson('/income', [
            'tanggal_transaksi' => today()->toDateString(),
            'jenis_transaksi' => 'offline',
            'items' => [
                ['id_produk' => $product->id, 'jumlah' => 1, 'harga_satuan' => 50000],
            ],
        ]);

        $response->assertCreated();
        $nomor = $response->json('nomor_transaksi');
        expect($nomor)->not->toBeNull();

        $this->actingAs($pegawai)->getJson("/income/nota/{$nomor}")->assertOk();
    });
});
