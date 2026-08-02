<?php

namespace Database\Seeders;

use App\Enums\JenisTransaksi;
use App\Models\CapitalInjection;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SalesReturn;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Opt-in seeder data demo untuk BM Leather Shop.
 *
 * CARA PAKAI:
 *   php artisan migrate:fresh --seed              # HANYA seed minimum (DatabaseSeeder)
 *   php artisan db:seed --class=DemoDataSeeder     # tambahkan data demo di atas
 *
 * Data ini merepresentasikan contoh toko kerajinan kulit:
 *  - 10 produk dengan harga pokok, harga grosir, dan stok
 *  - 6 bulan transaksi (penjualan online/offline, 4 kategori pengeluaran)
 *  - Suntikan modal awal pemilik
 *  - Beberapa retur penjualan (untuk demo konsep pengurang pendapatan)
 *  - Riwayat mutasi stok
 *
 * TIDAK dipanggil dari DatabaseSeeder — murni opt-in.
 */
class DemoDataSeeder extends Seeder
{
    /** @var array<string, ExpenseCategory> */
    private array $kategoriPengeluaran = [];

    /** @var array<int, Product> */
    private array $produk = [];

    /** @var array<int, User> */
    private array $users = [];

    public function run(): void
    {
        DB::transaction(function () {
            $this->loadExisting();
            $this->seedProducts();
            $this->seedModal();
            $this->seedIncomes();
            $this->seedExpenses();
            $this->seedSalesReturns();
        });

        $this->command->info('DemoDataSeeder selesai.');
    }

    private function loadExisting(): void
    {
        $this->kategoriPengeluaran = ExpenseCategory::all()->keyBy('nama')->all();

        $this->users = [
            'admin' => User::where('username', 'busari')->firstOrFail(),
            'dimas' => User::where('username', 'dimas')->firstOrFail(),
            'rina' => User::where('username', 'rina')->firstOrFail(),
        ];
    }

    /**
     * Buat 10 produk kerajinan kulit (idempotent by SKU).
     * Setiap produk di-seed stok awal via StockMovement (ledger append-only).
     */
    private function seedProducts(): void
    {
        $kategori = ProductCategory::all()->keyBy('nama');

        // [nama, kategori, sku, harga eceran, harga modal, harga grosir, stok awal]
        $rows = [
            ['Dompet Kulit Asli', 'Dompet', 'DPL-001', 185_000, 85_000, 165_000, 40],
            ['Dompet Lipat Minimalis', 'Dompet', 'DPL-002', 145_000, 65_000, 130_000, 35],
            ['Tas Selempang Kulit', 'Tas', 'TSL-001', 420_000, 210_000, 380_000, 25],
            ['Tas Ransel Kulit', 'Tas', 'TSL-002', 580_000, 290_000, 520_000, 18],
            ['Sabuk Kulit Pria', 'Sabuk', 'SBK-001', 135_000, 60_000, 120_000, 50],
            ['Sabuk Kulit Wanita', 'Sabuk', 'SBK-002', 125_000, 55_000, 110_000, 45],
            ['Gelang Kulit Braided', 'Aksesoris', 'AKS-001', 65_000, 25_000, 55_000, 80],
            ['Card Holder Kulit', 'Dompet', 'DPL-003', 95_000, 40_000, 85_000, 60],
            ['Gantungan Kunci Kulit', 'Aksesoris', 'AKS-003', 45_000, 15_000, 38_000, 100],
            ['Passport Cover Kulit', 'Aksesoris', 'AKS-002', 110_000, 45_000, 95_000, 12],
        ];

        $admin = $this->users['admin'];
        $tanggalAwal = now()->subMonths(6)->startOfMonth()->toDateString();

        foreach ($rows as $row) {
            [$nama, $katNama, $sku, $harga, $modal, $grosir, $stok] = $row;

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'category_id' => $kategori[$katNama]?->id,
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

            $hasRestok = StockMovement::where('product_id', $product->id)
                ->where('sumber', 'restok')
                ->exists();

            if (! $hasRestok) {
                $product->stok = $stok;
                $product->save();

                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $admin->id,
                    'tanggal' => $tanggalAwal,
                    'jenis' => 'masuk',
                    'jumlah' => $stok,
                    'sumber' => 'restok',
                    'keterangan' => 'Stok awal demo',
                ]);
            }
        }

        $this->produk = Product::orderBy('id')->get()->all();
    }

    private function seedModal(): void
    {
        if (CapitalInjection::exists()) {
            return;
        }

        $admin = $this->users['admin'];

        CapitalInjection::create([
            'user_id' => $admin->id,
            'tanggal' => now()->subMonths(6)->startOfMonth()->toDateString(),
            'nominal' => 30000000,
            'keterangan' => 'Modal awal usaha BM Leather Shop',
        ]);

        CapitalInjection::create([
            'user_id' => $admin->id,
            'tanggal' => now()->subMonths(2)->startOfMonth()->toDateString(),
            'nominal' => 5000000,
            'keterangan' => 'Tambahan modal beli bahan baku',
        ]);
    }

    private function seedIncomes(): void
    {
        if (Income::exists()) {
            return;
        }

        $activeProducts = array_values(array_filter($this->produk, fn ($p) => ! $p->trashed()));
        if (empty($activeProducts)) {
            return;
        }

        $recorders = [$this->users['admin'], $this->users['dimas']];
        $today = now()->startOfDay();
        $rand = $this->seededRand(20260703);

        for ($monthsBack = 0; $monthsBack <= 5; $monthsBack++) {
            $monthStart = $today->copy()->subMonths($monthsBack)->startOfMonth();
            $daysInMonth = (int) $monthStart->daysInMonth;
            $count = match (true) {
                $monthsBack === 0 => 14,
                $monthsBack === 1 => 18,
                default => 12 + (int) ($rand() * 5),
            };

            for ($i = 0; $i < $count; $i++) {
                $day = (int) ($rand() * $daysInMonth) + 1;
                $hour = 8 + (int) ($rand() * 12);
                $date = $monthStart->copy()->setDay($day)->setHour($hour);
                if ($date->isFuture()) {
                    $date = $today->copy()->subDays((int) ($rand() * 3))->setHour($hour);
                }

                $product = $activeProducts[(int) ($rand() * count($activeProducts))];
                $jumlah = 1 + (int) ($rand() * 5);
                $recorder = $recorders[(int) ($rand() * count($recorders))];
                $jenis = $rand() < 0.45 ? JenisTransaksi::Online : JenisTransaksi::Offline;

                $this->recordIncome($product, $jumlah, $date, $recorder, $jenis);
            }
        }
    }

    private function recordIncome(Product $product, int $jumlah, $date, User $recorder, JenisTransaksi $jenis): void
    {
        $pricing = $product->hargaUntuk($jenis, $jumlah);
        $hargaSatuan = (float) $pricing['harga'];

        if ((int) $product->stok < $jumlah) {
            $topUp = $jumlah + 5;
            $product->stok = (int) $product->stok + $topUp;
            $product->save();
            StockMovement::create([
                'product_id' => $product->id,
                'user_id' => $recorder->id,
                'tanggal' => $date->copy()->subDay()->toDateString(),
                'jenis' => 'masuk',
                'jumlah' => $topUp,
                'sumber' => 'restok',
                'keterangan' => 'Restok otomatis (stok tidak cukup)',
            ]);
        }

        $income = Income::create([
            'nomor_transaksi' => Income::generateNomorTransaksi(),
            'product_id' => $product->id,
            'user_id' => $recorder->id,
            'tanggal_transaksi' => $date->toDateString(),
            'jenis_transaksi' => $jenis,
            'jumlah' => $jumlah,
            'harga_satuan' => $hargaSatuan,
            'hpp_satuan' => (float) $product->harga_modal,
            'harga_tipe' => $pricing['tipe'],
            'total' => $jumlah * $hargaSatuan,
            'keterangan' => null,
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
            'keterangan' => 'Penjualan '.$income->nomor_transaksi,
        ]);
    }

    private function seedExpenses(): void
    {
        if (Expense::exists()) {
            return;
        }

        $recorders = [$this->users['admin'], $this->users['dimas']];
        $ranges = [
            'Bahan Baku' => [400_000, 1_800_000],
            'Operasional' => [75_000, 450_000],
            'Pemasaran' => [350_000, 1_200_000],
            'Pengiriman' => [50_000, 200_000],
        ];
        $keteranganMap = [
            'Bahan Baku' => ['Beli kulit sapi', 'Beli kulit domba', 'Beli benang & resleting', 'Beli lem kulit', 'Restok bahan pendukung'],
            'Operasional' => ['Packing & labelling', 'Listrik & air', 'Alat tulis & ATK', 'Sewa workshop', 'Pemeliharaan alat'],
            'Pemasaran' => ['Iklan Instagram', 'Bayar marketing bulanan', 'Foto produk studio', 'Promo marketplace', 'Endorse selebgram'],
            'Pengiriman' => ['Ongkir pesanan offline', 'Ongkir reseller', 'Kirim sample ke toko'],
        ];

        $today = now()->startOfDay();
        $rand = $this->seededRand(31415);

        for ($monthsBack = 0; $monthsBack <= 5; $monthsBack++) {
            $monthStart = $today->copy()->subMonths($monthsBack)->startOfMonth();
            $daysInMonth = (int) $monthStart->daysInMonth;

            foreach ($ranges as $nama => $range) {
                $count = match ($nama) {
                    'Bahan Baku', 'Pemasaran' => 4 + (int) ($rand() * 3),
                    default => 2 + (int) ($rand() * 3),
                };

                for ($i = 0; $i < $count; $i++) {
                    $day = (int) ($rand() * $daysInMonth) + 1;
                    $hour = 8 + (int) ($rand() * 10);
                    $date = $monthStart->copy()->setDay($day)->setHour($hour);
                    if ($date->isFuture()) {
                        $date = $today->copy()->subDays((int) ($rand() * 3))->setHour($hour);
                    }

                    $nominal = (int) (round(($range[0] + $rand() * ($range[1] - $range[0])) / 1000) * 1000);
                    $recorder = $recorders[(int) ($rand() * count($recorders))];
                    $ket = $keteranganMap[$nama][(int) ($rand() * count($keteranganMap[$nama]))];

                    Expense::create([
                        'category_id' => $this->kategoriPengeluaran[$nama]->id,
                        'user_id' => $recorder->id,
                        'tanggal_transaksi' => $date->toDateString(),
                        'nominal' => $nominal,
                        'keterangan' => $ket,
                    ]);
                }
            }
        }
    }

    private function seedSalesReturns(): void
    {
        if (SalesReturn::exists()) {
            return;
        }

        $rand = $this->seededRand(91203);

        $recentIncomes = Income::query()
            ->where('tanggal_transaksi', '>=', now()->subMonths(3))
            ->whereNotNull('product_id')
            ->inRandomOrder()
            ->limit(3)
            ->get();

        foreach ($recentIncomes as $income) {
            $maxJumlah = max(1, (int) ($income->jumlah / 2));
            $jumlah = 1 + (int) ($rand() * $maxJumlah);
            $tanggal = $income->tanggal_transaksi->copy()->addDays(2 + (int) ($rand() * 5));

            SalesReturn::create([
                'income_id' => $income->id,
                'product_id' => $income->product_id,
                'user_id' => $this->users['admin']->id,
                'tanggal' => $tanggal->toDateString(),
                'jumlah' => $jumlah,
                'nominal_retur' => $jumlah * (float) $income->harga_satuan,
                'alasan' => ['Barang cacat produksi', 'Ukuran tidak sesuai', 'Warna tidak cocok'][(int) ($rand() * 3)],
            ]);

            $product = Product::find($income->product_id);
            if ($product) {
                $product->stok = (int) $product->stok + $jumlah;
                $product->save();
                StockMovement::create([
                    'product_id' => $product->id,
                    'user_id' => $this->users['admin']->id,
                    'tanggal' => $tanggal->toDateString(),
                    'jenis' => 'masuk',
                    'jumlah' => $jumlah,
                    'sumber' => 'retur',
                    'ref_id' => $income->id,
                    'keterangan' => 'Retur penjualan #'.$income->id,
                ]);
            }
        }
    }

    /** Seedable PRNG untuk output stabil antar-run. */
    private function seededRand(int $seed): \Closure
    {
        $state = $seed;

        return function () use (&$state): float {
            $state = (1103515245 * $state + 12345) & 0x7FFFFFFF;

            return $state / 0x7FFFFFFF;
        };
    }
}
