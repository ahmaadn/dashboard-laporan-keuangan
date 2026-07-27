<?php

use App\Models\CapitalInjection;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Artisan;

describe('DemoDataSeeder (opt-in)', function () {
    it('DatabaseSeeder alone does NOT create demo products or transactions', function () {
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);

        expect(Product::count())->toBe(0);
        expect(Income::count())->toBe(0);
        expect(Expense::count())->toBe(0);
        expect(CapitalInjection::count())->toBe(0);
        expect(SalesReturn::count())->toBe(0);
    });

    it('creates the expected demo dataset when invoked explicitly', function () {
        // DatabaseSeeder first to seed users + categories
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class]);

        expect(Product::count())->toBeGreaterThanOrEqual(10);
        expect(Income::count())->toBeGreaterThan(50);
        expect(Expense::count())->toBeGreaterThan(50);
        expect(StockMovement::count())->toBeGreaterThan(50);
        expect(CapitalInjection::count())->toBeGreaterThanOrEqual(2);
        expect(SalesReturn::count())->toBeGreaterThanOrEqual(1);

        // Persis 4 kategori pengeluaran final (Gaji harus sudah tidak ada)
        expect(ExpenseCategory::count())->toBe(4);
        expect(ExpenseCategory::pluck('nama')->all())
            ->toEqualCanonicalizing(['Bahan Baku', 'Operasional', 'Pemasaran', 'Pengiriman']);
        expect(ExpenseCategory::where('nama', 'Gaji')->exists())->toBeFalse();
    });

    it('is idempotent — running twice does not double any row', function () {
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class]);

        $first = [
            'products' => Product::count(),
            'incomes' => Income::count(),
            'expenses' => Expense::count(),
            'capital' => CapitalInjection::count(),
            'returns' => SalesReturn::count(),
            'movements' => StockMovement::count(),
        ];

        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class]);

        expect(Product::count())->toBe($first['products']);
        expect(Income::count())->toBe($first['incomes']);
        expect(Expense::count())->toBe($first['expenses']);
        expect(CapitalInjection::count())->toBe($first['capital']);
        expect(SalesReturn::count())->toBe($first['returns']);
        expect(StockMovement::count())->toBe($first['movements']);
    });

    it('produces non-trivial dashboard inputs (pricing tiers, stok, kanal online+offline)', function () {
        Artisan::call('db:seed', ['--class' => DatabaseSeeder::class]);
        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class]);

        $produkGrosir = Product::whereNotNull('harga_grosir')
            ->where('min_qty_grosir', '>=', 3)
            ->first();
        expect($produkGrosir)->not->toBeNull();
        expect((float) $produkGrosir->harga_grosir)->toBeLessThan((float) $produkGrosir->harga);

        expect(Product::where('stok', '>', 0)->exists())->toBeTrue();
        expect(Income::where('jenis_transaksi', 'online')->exists())->toBeTrue();
        expect(Income::where('jenis_transaksi', 'offline')->exists())->toBeTrue();
    });
});
