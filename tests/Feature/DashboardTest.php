<?php

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\User;

describe('dashboard data endpoint', function () {
    it('returns summary and aggregations', function () {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();
        Income::factory()->create([
            'product_id' => $product->id,
            'user_id' => $admin->id,
            'tanggal_transaksi' => today(),
            'total' => 150000,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/dashboard?period=bulan_ini');

        $response->assertOk()
            ->assertJsonStructure([
                'range' => ['start', 'end', 'label', 'granularity'],
                'summary' => ['income', 'expense', 'profit', 'hasData'],
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
    });

    it('excludes soft-deleted transactions from aggregations', function () {
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
            'tanggal_transaksi' => today(),
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
            ->assertJsonStructure(['a' => ['income', 'expense', 'profit', 'label'], 'b' => ['income', 'expense', 'profit', 'label']]);

        expect($response->json('b.income'))->toBe(200000);
        expect($response->json('a.income'))->toBe(0);
    });
});
