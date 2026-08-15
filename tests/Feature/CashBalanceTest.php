<?php

use App\Models\CapitalInjection;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\User;
use App\Services\CashBalanceService;

describe('cash balance guard', function () {
    it('blocks an expense when cash balance is zero', function () {
        $pegawai = User::factory()->pegawai()->create();
        $category = ExpenseCategory::factory()->create();

        $response = $this->actingAs($pegawai)->postJson('/expenses', [
            'id_kategori' => $category->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 50000,
        ]);

        $response->assertStatus(422);
        expect($response->json('errors.nominal.0'))->toContain('Saldo kas tidak mencukupi');
        expect(Expense::count())->toBe(0);
    });

    it('blocks an expense that exceeds the available balance', function () {
        $pegawai = User::factory()->pegawai()->create();
        $category = ExpenseCategory::factory()->create();
        CapitalInjection::factory()->create([
            'tanggal' => today()->toDateString(),
            'nominal' => 100000,
        ]);

        $this->actingAs($pegawai)->postJson('/expenses', [
            'id_kategori' => $category->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 100001,
        ])->assertStatus(422);

        expect(Expense::count())->toBe(0);
    });

    it('allows an expense exactly equal to the available balance', function () {
        $pegawai = User::factory()->pegawai()->create();
        $category = ExpenseCategory::factory()->create();
        CapitalInjection::factory()->create([
            'tanggal' => today()->toDateString(),
            'nominal' => 100000,
        ]);

        $this->actingAs($pegawai)->postJson('/expenses', [
            'id_kategori' => $category->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 100000,
        ])->assertCreated();

        expect(Expense::count())->toBe(1);
    });

    it('counts sales as cash in', function () {
        $pegawai = User::factory()->pegawai()->create();
        $category = ExpenseCategory::factory()->create();
        $product = Product::factory()->create(['stok' => 10]);

        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $pegawai->id,
            'tanggal_transaksi' => today()->toDateString(),
            'jumlah' => 1,
            'harga_satuan' => 250000,
            'total' => 250000,
        ]);

        $this->actingAs($pegawai)->postJson('/expenses', [
            'id_kategori' => $category->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 250000,
        ])->assertCreated();
    });

    it('treats returns as cash out when computing the balance', function () {
        $pegawai = User::factory()->pegawai()->create();
        $product = Product::factory()->create(['stok' => 10]);

        $income = Income::factory()->create([
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

        expect(app(CashBalanceService::class)->saldo())->toBe(100000.0);
    });

    it('treats negative capital as cash out', function () {
        CapitalInjection::factory()->create([
            'tanggal' => today()->toDateString(),
            'nominal' => 300000,
        ]);
        CapitalInjection::factory()->create([
            'tanggal' => today()->toDateString(),
            'nominal' => -125000,
        ]);

        expect(app(CashBalanceService::class)->saldo())->toBe(175000.0);
    });

    it('excludes soft deleted expenses from the balance', function () {
        $pegawai = User::factory()->pegawai()->create();
        CapitalInjection::factory()->create([
            'tanggal' => today()->toDateString(),
            'nominal' => 300000,
        ]);
        $expense = Expense::factory()->create([
            'user_id' => $pegawai->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 200000,
        ]);

        expect(app(CashBalanceService::class)->saldo())->toBe(100000.0);

        $expense->delete();

        expect(app(CashBalanceService::class)->saldo())->toBe(300000.0);
    });

    it('does not double count the edited expense own nominal', function () {
        $pegawai = User::factory()->pegawai()->create();
        $category = ExpenseCategory::factory()->create();
        CapitalInjection::factory()->create([
            'tanggal' => today()->toDateString(),
            'nominal' => 100000,
        ]);
        $expense = Expense::factory()->create([
            'category_id' => $category->id,
            'user_id' => $pegawai->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 100000,
        ]);

        // Saldo sekarang 0, tetapi mengubah nominal ke 100000 tetap sah
        // karena nominal lama dikembalikan lebih dulu.
        $this->actingAs($pegawai)->putJson("/expenses/{$expense->id}", [
            'id_kategori' => $category->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 100000,
        ])->assertOk();

        $this->actingAs($pegawai)->putJson("/expenses/{$expense->id}", [
            'id_kategori' => $category->id,
            'tanggal_transaksi' => today()->toDateString(),
            'nominal' => 150000,
        ])->assertStatus(422);
    });

    it('exposes the cash balance on the expenses page', function () {
        $pegawai = User::factory()->pegawai()->create();
        CapitalInjection::factory()->create([
            'tanggal' => today()->toDateString(),
            'nominal' => 400000,
        ]);

        $this->actingAs($pegawai)
            ->get('/expenses')
            ->assertOk()
            ->assertViewHas('saldoKas', fn (array $saldo) => $saldo['saldo'] === 400000);
    });
});
