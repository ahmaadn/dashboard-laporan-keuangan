<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\User;
use App\Support\AppTimezone;

describe('dashboard data endpoint', function () {
    it('returns summary and aggregations', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'total' => 150000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        $response->assertOk()
            ->assertJsonStructure([
                'range' => ['start', 'end', 'label', 'granularity', 'hint'],
                'summary' => ['income', 'expense', 'profit', 'hasData', 'labaKotor', 'labaBersih', 'hpp'],
                'incomeByChannel',
                'lowStock',
                'trend' => ['labels', 'income', 'expense', 'buckets', 'granularity'],
                'categoryBreakdown',
                'productAggregates',
                'topProducts',
                'productTrend',
                'income',
                'expense',
                'recentTransactions',
            ]);

        expect($response->json('summary.income'))->toBe(150000);
        expect($response->json('summary.hasData'))->toBeTrue();
        expect($response->json('range.label'))->toMatch('/^\d{1,2} \w+ \d{4} — \d{1,2} \w+ \d{4}$/');
        expect($response->json('range.label'))->not->toBe('Bulan Ini');
    });

    it('computes laba kotor and excludes bahan baku from biaya operasional', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create(['harga_modal' => 40000]);
        $bahanBaku = ExpenseCategory::factory()->bahanBaku()->create();
        $ops = ExpenseCategory::factory()->create(['nama' => 'Operasional', 'is_bahan_baku' => false]);

        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'jumlah' => 2,
            'harga_satuan' => 100000,
            'hpp_satuan' => 40000,
            'total' => 200000,
            'jenis_transaksi' => 'online',
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

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        // HPP = 2 * 40000 = 80000; Laba kotor = 200000 - 80000 = 120000
        // Biaya operasional = 30000 (bahan baku excluded); Laba bersih = 90000
        expect($response->json('summary.hpp'))->toBe(80000);
        expect($response->json('summary.labaKotor'))->toBe(120000);
        expect($response->json('summary.biayaOperasional'))->toBe(30000);
        expect($response->json('summary.labaBersih'))->toBe(90000);
        expect($response->json('incomeByChannel.online.total'))->toBe(200000);
    });

    it('excludes soft-deleted transactions from aggregations', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'total' => 100000,
        ]);
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
            'total' => 50000,
        ])->delete();

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        expect($response->json('summary.income'))->toBe(100000);
    });

    it('blocks pegawai without dashboard access', function () {
        $pegawai = User::factory()->pegawai()->withoutDashboard()->create();

        $this->actingAs($pegawai)->getJson('/api/dashboard')->assertForbidden();
    });

    it('returns recent transactions across all time', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        $category = ExpenseCategory::factory()->create();

        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => now()->subYear(),
        ]);
        Expense::factory()->create([
            'category_id' => $category->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => AppTimezone::todayDateString(),
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        // The recent transactions should include the expense from today even though
        // the income from a year ago is outside the period.
        expect(count($response->json('recentTransactions')))->toBeGreaterThan(0);
    });
});

describe('dashboard compare endpoint', function () {
    it('returns comparison between two periods', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => now()->startOfMonth(),
            'total' => 200000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard/compare?a=bulan_lalu&b=bulan_ini');

        $response->assertOk()
            ->assertJsonStructure([
                'a' => ['income', 'expense', 'profit', 'labaKotor', 'labaBersih', 'label'],
                'b' => ['income', 'expense', 'profit', 'labaKotor', 'labaBersih', 'label'],
            ]);

        expect($response->json('b.income'))->toBe(200000);
        expect($response->json('a.income'))->toBe(0);
    });
});
