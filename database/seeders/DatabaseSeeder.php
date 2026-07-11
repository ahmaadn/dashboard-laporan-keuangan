<?php

namespace Database\Seeders;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedExpenseCategories();
        $this->seedProductCategories();
        $users = $this->seedUsers();
        $products = $this->seedProducts($users['admin']);
        $this->seedIncomes($products, $users);
        $this->seedExpenses($users);
    }

    private function seedExpenseCategories(): void
    {
        foreach (['Bahan Baku', 'Operasional', 'Pengiriman'] as $nama) {
            ExpenseCategory::firstOrCreate(['nama' => $nama]);
        }
    }

    private function seedProductCategories(): void
    {
        foreach (['Dompet', 'Tas', 'Sabuk', 'Aksesoris'] as $nama) {
            ProductCategory::firstOrCreate(['nama' => $nama]);
        }
    }

    /** @return array<string, User> */
    private function seedUsers(): array
    {
        $admin = User::firstOrCreate(
            ['username' => 'busari'],
            [
                'nama' => 'Bu Sari',
                'email' => 'busari@leatherdash.id',
                'password' => Hash::make('demo1234'),
                'peran' => 'admin',
                'dapat_melihat_dashboard' => true,
                'is_active' => true,
            ],
        );

        $dimas = User::firstOrCreate(
            ['username' => 'dimas'],
            [
                'nama' => 'Dimas Pratama',
                'email' => 'dimas@leatherdash.id',
                'password' => Hash::make('demo1234'),
                'peran' => 'pegawai',
                'dapat_melihat_dashboard' => true,
                'is_active' => true,
            ],
        );

        $rina = User::firstOrCreate(
            ['username' => 'rina'],
            [
                'nama' => 'Rina Wati',
                'email' => 'rina@leatherdash.id',
                'password' => Hash::make('demo1234'),
                'peran' => 'pegawai',
                'dapat_melihat_dashboard' => false,
                'is_active' => true,
            ],
        );

        return ['admin' => $admin, 'dimas' => $dimas, 'rina' => $rina];
    }

    /** @return array<int, Product> */
    private function seedProducts(User $admin): array
    {
        $byNama = ProductCategory::all()->keyBy('nama');

        $rows = [
            ['Dompet Kulit Asli', 'Dompet', 'DPL-001', 185000, 'Dompet pria 2 lipat, kulit sapi asli.'],
            ['Dompet Lipat Minimalis', 'Dompet', 'DPL-002', 145000, 'Desain tipis, muat 6 kartu.'],
            ['Tas Selempang Kulit', 'Tas', 'TSL-001', 420000, 'Tas selempang crossbody, tali adjustable.'],
            ['Tas Ransel Kulit', 'Tas', 'TSL-002', 580000, 'Ransel harian kulit premium.'],
            ['Sabuk Kulit Pria', 'Sabuk', 'SBK-001', 135000, 'Sabuk kulit sapi, gesper stainless.'],
            ['Sabuk Kulit Wanita', 'Sabuk', 'SBK-002', 125000, 'Sabuk elegan, motif minimalis.'],
            ['Gelang Kulit Braided', 'Aksesoris', 'AKS-001', 65000, 'Gelang rajut kulit, magnetic clasp.'],
            ['Card Holder Kulit', 'Dompet', 'DPL-003', 95000, 'Card holder tipis, slot ganda.'],
            ['Passport Cover Kulit', 'Aksesoris', 'AKS-002', 110000, 'Sampul paspor kulit, emboss custom.'],
        ];

        $products = [];
        foreach ($rows as $i => $row) {
            [$nama, $katNama, $sku, $harga, $deskripsi] = $row;

            $product = Product::firstOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $byNama[$katNama]?->id,
                    'nama' => $nama,
                    'harga' => $harga,
                    'deskripsi' => $deskripsi,
                    'is_active' => true,
                    'created_by' => $admin->id,
                ],
            );

            // Soft-delete the last product to demo the badge.
            if ($i === count($rows) - 1 && ! $product->trashed()) {
                $product->delete();
            }

            $products[] = $product;
        }

        return $products;
    }

    /**
     * @param  array<int, Product>  $products
     * @param  array<string, User>  $users
     */
    private function seedIncomes(array $products, array $users): void
    {
        $activeProducts = array_values(array_filter($products, fn ($p) => ! $p->trashed()));
        $recorders = [$users['admin'], $users['dimas']];
        $keteranganPool = ['Pelanggan tetap', 'Penjualan tunai', 'Pesanan online', 'Diskon pameran', 'Penjualan grosir', '', '', 'Bukti transfer diterima'];

        $today = now()->startOfDay();
        $rand = $this->rng(20260703);
        $todayRows = [
            [9, 0, 2, 0],
            [13, 4, 1, 1],
            [16, 2, 3, 1],
        ];

        foreach ($todayRows as [$hour, $produkIdx, $jumlah, $recorderIdx]) {
            $this->createIncome($activeProducts[$produkIdx], $jumlah, $today->copy()->setHour($hour), $recorders[$recorderIdx], $keteranganPool, $rand);
        }

        for ($monthsBack = 0; $monthsBack <= 11; $monthsBack++) {
            $monthStart = $today->copy()->subMonths($monthsBack)->startOfMonth();
            $count = $monthsBack === 0 ? 6 : 5;

            for ($i = 0; $i < $count; $i++) {
                $day = (int) ($rand() * 27) + 1;
                $hour = (int) ($rand() * 12) + 8;
                $date = $monthStart->copy()->setDay(min($day, $monthStart->daysInMonth))->setHour($hour);
                if ($date->isFuture() && $monthsBack === 0) {
                    $date = $today->copy()->setHour($hour);
                }
                $product = $activeProducts[(int) ($rand() * count($activeProducts))];
                $jumlah = (int) ($rand() * 6) + 1;
                $recorder = $recorders[(int) ($rand() * count($recorders))];

                $this->createIncome($product, $jumlah, $date, $recorder, $keteranganPool, $rand);
            }
        }
    }

    /**
     * @param  array<string, User>  $users
     */
    private function seedExpenses(array $users): void
    {
        $categories = ExpenseCategory::all();
        $recorders = [$users['admin'], $users['dimas']];
        $ranges = [1 => [200000, 1500000], 2 => [50000, 400000], 3 => [30000, 150000]];
        $keteranganPool = ['Pembelian rutin', 'Restok bulanan', 'Pembayaran vendor', 'Biaya kirim pesanan', 'Keperluan toko', ''];

        $today = now()->startOfDay();
        $rand = $this->rng(31415);

        $this->createExpense($categories[0], $ranges[1], $today->copy()->setHour(10), $recorders[0], $keteranganPool, $rand);

        for ($monthsBack = 0; $monthsBack <= 11; $monthsBack++) {
            $monthStart = $today->copy()->subMonths($monthsBack)->startOfMonth();
            $count = $monthsBack === 0 ? 4 : 3;

            for ($i = 0; $i < $count; $i++) {
                $day = (int) ($rand() * 27) + 1;
                $hour = (int) ($rand() * 10) + 8;
                $date = $monthStart->copy()->setDay(min($day, $monthStart->daysInMonth))->setHour($hour);
                if ($date->isFuture() && $monthsBack === 0) {
                    $date = $today->copy()->subDay()->setHour($hour);
                }
                $kat = $categories[(int) ($rand() * count($categories))];
                $recorder = $recorders[(int) ($rand() * count($recorders))];

                $this->createExpense($kat, $ranges[$kat->id], $date, $recorder, $keteranganPool, $rand);
            }
        }
    }

    private function createIncome(Product $product, int $jumlah, $date, User $recorder, array $keteranganPool, callable $rand): void
    {
        $hargaSatuan = (int) $product->harga;
        if ($rand() < 0.18) {
            $hargaSatuan = (int) round($hargaSatuan * 0.9);
        }
        $total = $jumlah * $hargaSatuan;

        Income::create([
            'product_id' => $product->id,
            'user_id' => $recorder->id,
            'tanggal_transaksi' => $date->toDateString(),
            'jumlah' => $jumlah,
            'harga_satuan' => $hargaSatuan,
            'total' => $total,
            'keterangan' => $keteranganPool[(int) ($rand() * count($keteranganPool))],
        ]);
    }

    private function createExpense($category, array $range, $date, User $recorder, array $keteranganPool, callable $rand): void
    {
        $nominal = (int) round(($range[0] + $rand() * ($range[1] - $range[0])) / 1000) * 1000;

        Expense::create([
            'category_id' => $category->id,
            'user_id' => $recorder->id,
            'tanggal_transaksi' => $date->toDateString(),
            'nominal' => $nominal,
            'keterangan' => $keteranganPool[(int) ($rand() * count($keteranganPool))],
        ]);
    }

    /**
     * Deterministic seeded RNG (LCG) so the seed dataset is stable.
     *
     * @return callable(): float
     */
    private function rng(int $seed): callable
    {
        $state = $seed;

        return function () use (&$state): float {
            $state = (1103515245 * $state + 12345) & 0x7FFFFFFF;

            return $state / 2147483647;
        };
    }
}
