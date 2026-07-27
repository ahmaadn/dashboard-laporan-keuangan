<?php

namespace Database\Seeders;

use App\Enums\JenisTransaksi;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
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
        $rows = [
            ['Bahan Baku', true],
            ['Operasional', false],
            ['Pengiriman', false],
            ['Gaji', false],
        ];

        foreach ($rows as [$nama, $isBahanBaku]) {
            ExpenseCategory::updateOrCreate(
                ['nama' => $nama],
                ['is_bahan_baku' => $isBahanBaku],
            );
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

        // nama, kategori, sku, harga eceran, harga modal, harga grosir, stok
        $rows = [
            ['Dompet Kulit Asli', 'Dompet', 'DPL-001', 185000, 85000, 165000, 40],
            ['Dompet Lipat Minimalis', 'Dompet', 'DPL-002', 145000, 65000, 130000, 35],
            ['Tas Selempang Kulit', 'Tas', 'TSL-001', 420000, 210000, 380000, 25],
            ['Tas Ransel Kulit', 'Tas', 'TSL-002', 580000, 290000, 520000, 18],
            ['Sabuk Kulit Pria', 'Sabuk', 'SBK-001', 135000, 60000, 120000, 50],
            ['Sabuk Kulit Wanita', 'Sabuk', 'SBK-002', 125000, 55000, 110000, 45],
            ['Gelang Kulit Braided', 'Aksesoris', 'AKS-001', 65000, 25000, 55000, 80],
            ['Card Holder Kulit', 'Dompet', 'DPL-003', 95000, 40000, 85000, 60],
            ['Gantungan Kunci Kulit', 'Aksesoris', 'AKS-003', 45000, 15000, 38000, 100],
            ['Passport Cover Kulit', 'Aksesoris', 'AKS-002', 110000, 45000, 95000, 4],
        ];

        $products = [];
        foreach ($rows as $i => $row) {
            [$nama, $katNama, $sku, $harga, $modal, $grosir, $stok] = $row;

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $byNama[$katNama]?->id,
                    'nama' => $nama,
                    'harga' => $harga,
                    'harga_modal' => $modal,
                    'harga_grosir' => $grosir,
                    'min_qty_grosir' => 3,
                    'stok' => 0,
                    'stok_minimum' => 5,
                    'deskripsi' => $nama.' — kerajinan kulit handmade.',
                    'is_active' => true,
                    'created_by' => $admin->id,
                ],
            );

            if ($stok > 0 && ! StockMovement::where('product_id', $product->id)->where('sumber', 'restok')->exists()) {
                $product->stok = $stok;
                $product->save();
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $admin->id,
                    'tanggal' => now()->subMonths(6)->toDateString(),
                    'jenis' => 'masuk',
                    'jumlah' => $stok,
                    'sumber' => 'restok',
                    'keterangan' => 'Stok awal seeder',
                ]);
            }

            if ($i === count($rows) - 1 && ! $product->trashed()) {
                $product->delete();
            }

            $products[] = $product->fresh();
        }

        return $products;
    }

    /**
     * @param  array<int, Product>  $products
     * @param  array<string, User>  $users
     */
    private function seedIncomes(array $products, array $users): void
    {
        if (Income::query()->exists()) {
            return;
        }

        $activeProducts = array_values(array_filter($products, fn ($p) => ! $p->trashed()));
        $recorders = [$users['admin'], $users['dimas']];
        $keteranganPool = ['Pelanggan tetap', 'Penjualan tunai', 'Transfer marketplace', 'Diskon pameran', 'Penjualan grosir toko', '', '', 'Bukti transfer diterima'];

        $today = now()->startOfDay();
        $rand = $this->rng(20260703);
        $todayRows = [
            [9, 0, 2, 0, 'offline'],
            [13, 4, 1, 1, 'online'],
            [16, 2, 4, 1, 'offline'],
        ];

        foreach ($todayRows as [$hour, $produkIdx, $jumlah, $recorderIdx, $jenis]) {
            $this->createIncome($activeProducts[$produkIdx], $jumlah, $today->copy()->setHour($hour), $recorders[$recorderIdx], $keteranganPool, $rand, $jenis);
        }

        for ($monthsBack = 0; $monthsBack <= 5; $monthsBack++) {
            $monthStart = $today->copy()->subMonths($monthsBack)->startOfMonth();
            $count = $monthsBack === 0 ? 8 : 6;

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
                $jenis = $rand() < 0.4 ? 'online' : 'offline';

                $this->createIncome($product, $jumlah, $date, $recorder, $keteranganPool, $rand, $jenis);
            }
        }
    }

    /**
     * @param  array<string, User>  $users
     */
    private function seedExpenses(array $users): void
    {
        if (Expense::query()->exists()) {
            return;
        }

        $categories = ExpenseCategory::all();
        $recorders = [$users['admin'], $users['dimas']];
        $ranges = [
            'Bahan Baku' => [200000, 1500000],
            'Operasional' => [50000, 400000],
            'Pengiriman' => [30000, 150000],
            'Gaji' => [1500000, 3500000],
        ];
        $keteranganPool = ['Pembelian rutin', 'Restok bulanan', 'Pembayaran vendor', 'Biaya kirim pesanan', 'Keperluan toko', 'Gaji bulanan', ''];

        $today = now()->startOfDay();
        $rand = $this->rng(31415);

        $this->createExpense($categories->firstWhere('nama', 'Bahan Baku'), $ranges['Bahan Baku'], $today->copy()->setHour(10), $recorders[0], $keteranganPool, $rand);

        for ($monthsBack = 0; $monthsBack <= 5; $monthsBack++) {
            $monthStart = $today->copy()->subMonths($monthsBack)->startOfMonth();
            $count = $monthsBack === 0 ? 5 : 4;

            for ($i = 0; $i < $count; $i++) {
                $day = (int) ($rand() * 27) + 1;
                $hour = (int) ($rand() * 10) + 8;
                $date = $monthStart->copy()->setDay(min($day, $monthStart->daysInMonth))->setHour($hour);
                if ($date->isFuture() && $monthsBack === 0) {
                    $date = $today->copy()->subDay()->setHour($hour);
                }
                $kat = $categories[(int) ($rand() * count($categories))];
                $recorder = $recorders[(int) ($rand() * count($recorders))];
                $range = $ranges[$kat->nama] ?? [50000, 300000];

                $this->createExpense($kat, $range, $date, $recorder, $keteranganPool, $rand);
            }
        }
    }

    private function createIncome(Product $product, int $jumlah, $date, User $recorder, array $keteranganPool, callable $rand, string $jenis): void
    {
        $product->refresh();
        $jenisEnum = JenisTransaksi::from($jenis);
        $pricing = $product->hargaUntuk($jenisEnum, $jumlah);
        $hargaSatuan = $pricing['harga'];
        $tipe = $pricing['tipe'];

        // Jangan kurangi stok di bawah 0 di seeder
        if ((int) $product->stok < $jumlah) {
            $product->stok = (int) $product->stok + $jumlah + 5;
            $product->save();
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $recorder->id,
                'tanggal' => $date->toDateString(),
                'jenis' => 'masuk',
                'jumlah' => $jumlah + 5,
                'sumber' => 'restok',
                'keterangan' => 'Restok otomatis seeder',
            ]);
        }

        $income = Income::create([
            'product_id' => $product->id,
            'user_id' => $recorder->id,
            'tanggal_transaksi' => $date->toDateString(),
            'jenis_transaksi' => $jenisEnum,
            'jumlah' => $jumlah,
            'harga_satuan' => $hargaSatuan,
            'hpp_satuan' => (float) $product->harga_modal,
            'harga_tipe' => $tipe,
            'total' => $jumlah * $hargaSatuan,
            'keterangan' => $keteranganPool[(int) ($rand() * count($keteranganPool))],
        ]);

        $product->stok = (int) $product->stok - $jumlah;
        $product->save();

        StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $recorder->id,
            'tanggal' => $date->toDateString(),
            'jenis' => 'keluar',
            'jumlah' => -$jumlah,
            'sumber' => 'penjualan',
            'ref_id' => $income->id,
            'keterangan' => 'Penjualan #'.$income->id,
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
