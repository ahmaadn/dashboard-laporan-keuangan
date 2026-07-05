<?php

namespace App\Services\Mock;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Central accessor for all mock datasets used by the LeatherDash frontend.
 *
 * Pure in-memory arrays shaped from the PRD field tables (FR-2…FR-5).
 * No database, no Eloquent. Active reads exclude `dihapus_pada` (BR-3);
 * the full collections retain 1–2 soft-deleted rows to demo the badge.
 */
class MockData
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /**
     * Build (once) the full dataset cache.
     *
     * @return array<string, mixed>
     */
    private static function all(): array
    {
        if (self::$cache === null) {
            self::$cache = self::build();
        }

        return self::$cache;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function pengguna(): array
    {
        return self::all()['pengguna'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function kategoriProduk(): array
    {
        return self::all()['kategori_produk'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function kategoriPengeluaran(): array
    {
        return self::all()['kategori_pengeluaran'];
    }

    /**
     * Full product collection (includes soft-deleted, for the Admin table).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function produk(): array
    {
        return self::all()['produk'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function produkAktif(): array
    {
        return array_values(array_filter(self::produk(), fn ($p) => $p['dihapus_pada'] === null && $p['aktif']));
    }

    /**
     * Full income collection (includes soft-deleted, to demo the badge).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pemasukan(): array
    {
        return self::all()['pemasukan'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function pemasukanAktif(): array
    {
        return array_values(array_filter(self::pemasukan(), fn ($p) => $p['dihapus_pada'] === null));
    }

    /**
     * Full expense collection (includes soft-deleted).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function pengeluaran(): array
    {
        return self::all()['pengeluaran'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function pengeluaranAktif(): array
    {
        return array_values(array_filter(self::pengeluaran(), fn ($p) => $p['dihapus_pada'] === null));
    }

    /**
     * The three demo login profiles (Bu Sari / Dimas / Rina) for quick-fill
     * and the "Lihat sebagai…" role switcher.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function profiles(): array
    {
        $byId = [];

        foreach (self::pengguna() as $u) {
            if ($u['dihapus_pada'] === null && $u['aktif'] && in_array($u['id'], [1, 2, 3], true)) {
                $byId[$u['id']] = $u;
            }
        }

        return array_values($byId);
    }

    /**
     * Resolve the current user from the mock cookie, defaulting to the Admin.
     *
     * @return array<string, mixed>
     */
    public static function currentUser(?Request $request = null): array
    {
        $request ??= request();
        $cookie = $request?->cookie('ld_profile');

        if (is_string($cookie) && $cookie !== '') {
            $decoded = json_decode(urldecode($cookie), true);
            if (is_array($decoded) && isset($decoded['id'])) {
                foreach (self::pengguna() as $u) {
                    if ($u['id'] === $decoded['id'] && $u['dihapus_pada'] === null && $u['aktif']) {
                        return $u;
                    }
                }
            }
        }

        return self::pengguna()[0];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function produkById(): array
    {
        return array_column(self::produk(), null, 'id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function penggunaById(): array
    {
        return array_column(self::pengguna(), null, 'id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function kategoriProdukById(): array
    {
        return array_column(self::kategoriProduk(), null, 'id');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function kategoriPengeluaranById(): array
    {
        return array_column(self::kategoriPengeluaran(), null, 'id');
    }

    /**
     * Format an integer/float as Rupiah ("Rp 1.250.000").
     */
    public static function rupiah(int|float $value): string
    {
        return 'Rp '.number_format((float) $value, 0, ',', '.');
    }

    /**
     * Short Indonesian table date ("03/07/2026").
     */
    public static function tanggal(?string $date): string
    {
        if (! $date) {
            return '-';
        }

        return Carbon::parse($date)->format('d/m/Y');
    }

    /**
     * Long Indonesian date ("3 Juli 2026").
     */
    public static function tanggalLengkap(?string $date): string
    {
        if (! $date) {
            return '-';
        }

        $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $d = Carbon::parse($date);

        return $d->day.' '.$months[$d->month - 1].' '.$d->year;
    }

    /**
     * Resolve a period preset (or custom range) to a [start, end] pair of
     * 'Y-m-d' date strings inclusive.
     *
     * @return array{0:string,1:string}
     */
    public static function periodRange(string $period, ?string $start = null, ?string $end = null): array
    {
        $t = today();

        return match ($period) {
            'hari_ini' => [$t->toDateString(), $t->toDateString()],
            'minggu_ini' => [(clone $t)->startOfWeek()->toDateString(), (clone $t)->endOfWeek()->toDateString()],
            'tahun_ini' => [(clone $t)->startOfYear()->toDateString(), (clone $t)->endOfYear()->toDateString()],
            'rentang' => [
                $start ?: (clone $t)->startOfMonth()->toDateString(),
                $end ?: $t->toDateString(),
            ],
            default => [(clone $t)->startOfMonth()->toDateString(), (clone $t)->endOfMonth()->toDateString()],
        };
    }

    /**
     * Period preset labels with their option values.
     *
     * @return array<string, string>
     */
    public static function periodOptions(): array
    {
        return [
            'bulan_ini' => 'Bulan Ini',
            'hari_ini' => 'Hari Ini',
            'minggu_ini' => 'Minggu Ini',
            'tahun_ini' => 'Tahun Ini',
            'rentang' => 'Rentang Kustom',
        ];
    }

    /**
     * Compute a full report structure for a period (used by the Reports page).
     *
     * @return array<string, mixed>
     */
    public static function reportSummary(string $period, ?string $start = null, ?string $end = null): array
    {
        [$rangeStart, $rangeEnd] = self::periodRange($period, $start, $end);
        $inRange = fn ($d) => $d >= $rangeStart && $d <= $rangeEnd;

        $pemasukan = array_values(array_filter(self::pemasukanAktif(), fn ($r) => $inRange($r['tanggal_transaksi'])));
        $pengeluaran = array_values(array_filter(self::pengeluaranAktif(), fn ($r) => $inRange($r['tanggal_transaksi'])));

        $totalIncome = array_sum(array_column($pemasukan, 'total'));
        $totalExpense = array_sum(array_column($pengeluaran, 'nominal'));

        $produkById = self::produkById();
        $incomeByProduct = [];
        foreach ($pemasukan as $r) {
            $pid = $r['id_produk'];
            if (! isset($incomeByProduct[$pid])) {
                $incomeByProduct[$pid] = ['id' => $pid, 'nama' => $produkById[$pid]['nama'] ?? 'Tanpa produk', 'qty' => 0, 'total' => 0, 'count' => 0];
            }
            $incomeByProduct[$pid]['qty'] += (int) $r['jumlah'];
            $incomeByProduct[$pid]['total'] += (int) $r['total'];
            $incomeByProduct[$pid]['count']++;
        }
        $incomeByProduct = array_values($incomeByProduct);
        usort($incomeByProduct, fn ($a, $b) => $b['total'] <=> $a['total']);

        $kategoriById = self::kategoriPengeluaranById();
        $expenseByCategory = [];
        foreach ($pengeluaran as $r) {
            $cid = $r['id_kategori'];
            if (! isset($expenseByCategory[$cid])) {
                $expenseByCategory[$cid] = ['id' => $cid, 'nama' => $kategoriById[$cid]['nama'] ?? 'Lainnya', 'total' => 0, 'count' => 0];
            }
            $expenseByCategory[$cid]['total'] += (int) $r['nominal'];
            $expenseByCategory[$cid]['count']++;
        }
        $expenseByCategory = array_values($expenseByCategory);
        usort($expenseByCategory, fn ($a, $b) => $b['total'] <=> $a['total']);

        return [
            'period' => $period,
            'start' => $rangeStart,
            'end' => $rangeEnd,
            'rangeLabel' => self::tanggalLengkap($rangeStart).' — '.self::tanggalLengkap($rangeEnd),
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'profit' => $totalIncome - $totalExpense,
            'incomeByProduct' => $incomeByProduct,
            'expenseByCategory' => $expenseByCategory,
            'hasData' => count($pemasukan) > 0 || count($pengeluaran) > 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function build(): array
    {
        $pengguna = self::seedPengguna();
        $kategoriProduk = self::seedKategoriProduk();
        $kategoriPengeluaran = self::seedKategoriPengeluaran();
        $produk = self::seedProduk();
        $pemasukan = self::seedPemasukan($produk, $pengguna);
        $pengeluaran = self::seedPengeluaran($kategoriPengeluaran, $pengguna);

        return [
            'pengguna' => $pengguna,
            'kategori_produk' => $kategoriProduk,
            'kategori_pengeluaran' => $kategoriPengeluaran,
            'produk' => $produk,
            'pemasukan' => $pemasukan,
            'pengeluaran' => $pengeluaran,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function seedPengguna(): array
    {
        return [
            ['id' => 1, 'nama' => 'Bu Sari', 'nama_pengguna' => 'busari', 'email' => 'busari@leatherdash.id', 'peran' => 'admin', 'dapat_melihat_dashboard' => true, 'aktif' => true, 'dihapus_pada' => null],
            ['id' => 2, 'nama' => 'Dimas Pratama', 'nama_pengguna' => 'dimas', 'email' => 'dimas@leatherdash.id', 'peran' => 'pegawai', 'dapat_melihat_dashboard' => true, 'aktif' => true, 'dihapus_pada' => null],
            ['id' => 3, 'nama' => 'Rina Wati', 'nama_pengguna' => 'rina', 'email' => 'rina@leatherdash.id', 'peran' => 'pegawai', 'dapat_melihat_dashboard' => false, 'aktif' => true, 'dihapus_pada' => null],
            ['id' => 4, 'nama' => 'Eko Saputra', 'nama_pengguna' => 'eko', 'email' => 'eko@leatherdash.id', 'peran' => 'pegawai', 'dapat_melihat_dashboard' => false, 'aktif' => true, 'dihapus_pada' => '2026-04-12 09:30:00'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function seedKategoriProduk(): array
    {
        return [
            ['id' => 1, 'nama' => 'Dompet'],
            ['id' => 2, 'nama' => 'Tas'],
            ['id' => 3, 'nama' => 'Sabuk'],
            ['id' => 4, 'nama' => 'Aksesoris'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function seedKategoriPengeluaran(): array
    {
        return [
            ['id' => 1, 'nama' => 'Bahan Baku'],
            ['id' => 2, 'nama' => 'Operasional'],
            ['id' => 3, 'nama' => 'Pengiriman'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function seedProduk(): array
    {
        $now = '2026-01-15 08:00:00';

        return [
            ['id' => 1, 'nama' => 'Dompet Kulit Asli', 'id_kategori' => 1, 'sku' => 'DPL-001', 'harga' => 185000, 'deskripsi' => 'Dompet pria 2 lipat, kulit sapi asli.', 'aktif' => true, 'dibuat_oleh' => 1, 'dibuat_pada' => $now, 'diperbarui_pada' => $now, 'dihapus_pada' => null],
            ['id' => 2, 'nama' => 'Dompet Lipat Minimalis', 'id_kategori' => 1, 'sku' => 'DPL-002', 'harga' => 145000, 'deskripsi' => 'Desain tipis, muat 6 kartu.', 'aktif' => true, 'dibuat_oleh' => 1, 'dibuat_pada' => $now, 'diperbarui_pada' => $now, 'dihapus_pada' => null],
            ['id' => 3, 'nama' => 'Tas Selempang Kulit', 'id_kategori' => 2, 'sku' => 'TSL-001', 'harga' => 420000, 'deskripsi' => 'Tas selempang crossbody, tali adjustable.', 'aktif' => true, 'dibuat_oleh' => 1, 'dibuat_pada' => $now, 'diperbarui_pada' => $now, 'dihapus_pada' => null],
            ['id' => 4, 'nama' => 'Tas Ransel Kulit', 'id_kategori' => 2, 'sku' => 'TSL-002', 'harga' => 580000, 'deskripsi' => 'Ransel harian kulit premium.', 'aktif' => true, 'dibuat_oleh' => 1, 'dibuat_pada' => $now, 'diperbarui_pada' => $now, 'dihapus_pada' => null],
            ['id' => 5, 'nama' => 'Sabuk Kulit Pria', 'id_kategori' => 3, 'sku' => 'SBK-001', 'harga' => 135000, 'deskripsi' => 'Sabuk kulit sapi, gesper stainless.', 'aktif' => true, 'dibuat_oleh' => 1, 'dibuat_pada' => $now, 'diperbarui_pada' => $now, 'dihapus_pada' => null],
            ['id' => 6, 'nama' => 'Sabuk Kulit Wanita', 'id_kategori' => 3, 'sku' => 'SBK-002', 'harga' => 125000, 'deskripsi' => 'Sabuk elegan, motif minimalis.', 'aktif' => true, 'dibuat_oleh' => 1, 'dibuat_pada' => $now, 'diperbarui_pada' => $now, 'dihapus_pada' => null],
            ['id' => 7, 'nama' => 'Gelang Kulit Braided', 'id_kategori' => 4, 'sku' => 'AKS-001', 'harga' => 65000, 'deskripsi' => 'Gelang rajut kulit, magnetic clasp.', 'aktif' => true, 'dibuat_oleh' => 1, 'dibuat_pada' => $now, 'diperbarui_pada' => $now, 'dihapus_pada' => null],
            ['id' => 8, 'nama' => 'Card Holder Kulit', 'id_kategori' => 1, 'sku' => 'DPL-003', 'harga' => 95000, 'deskripsi' => 'Card holder tipis, slot ganda.', 'aktif' => true, 'dibuat_oleh' => 1, 'dibuat_pada' => $now, 'diperbarui_pada' => $now, 'dihapus_pada' => null],
            ['id' => 9, 'nama' => 'Passport Cover Kulit', 'id_kategori' => 4, 'sku' => 'AKS-002', 'harga' => 110000, 'deskripsi' => 'Sampul paspor kulit, emboss custom.', 'aktif' => true, 'dibuat_oleh' => 1, 'dibuat_pada' => $now, 'diperbarui_pada' => $now, 'dihapus_pada' => '2026-05-02 14:10:00'],
        ];
    }

    /**
     * Deterministic seeded RNG (LCG) so the mock dataset is stable across requests.
     *
     * @return callable(): float
     */
    private static function rng(int $seed): callable
    {
        $state = $seed;

        return function () use (&$state): float {
            $state = (1103515245 * $state + 12345) & 0x7FFFFFFF;

            return $state / 2147483647;
        };
    }

    /**
     * @param  array<int, array<string, mixed>>  $produk
     * @param  array<int, array<string, mixed>>  $pengguna
     * @return array<int, array<string, mixed>>
     */
    private static function seedPemasukan(array $produk, array $pengguna): array
    {
        $rand = self::rng(20260703);
        $rows = [];
        $id = 1;
        $activeProduk = array_values(array_filter($produk, fn ($p) => $p['dihapus_pada'] === null));
        $recorders = array_values(array_filter($pengguna, fn ($u) => $u['dihapus_pada'] === null));
        $keteranganPool = ['Pelanggan tetap', 'Penjualan tunai', 'Pesanan online', 'Diskon pameran', 'Penjualan grosir', '', '', 'Bukti transfer diterima'];

        $today = now()->startOfDay();
        $todayIncome = [
            ['hour' => 9, 'produkIdx' => 0, 'jumlah' => 2, 'recorder' => 1],
            ['hour' => 13, 'produkIdx' => 4, 'jumlah' => 1, 'recorder' => 2],
            ['hour' => 16, 'produkIdx' => 2, 'jumlah' => 3, 'recorder' => 2],
        ];
        foreach ($todayIncome as $t) {
            $p = $activeProduk[$t['produkIdx']];
            $rows[] = self::makePemasukan($id++, $p, $t['jumlah'], $today->copy()->setHour($t['hour'])->setMinute((int) ($rand() * 50)), $recorders[$t['recorder'] - 1] ?? $recorders[0], $rand, $keteranganPool);
        }

        for ($monthsBack = 0; $monthsBack <= 11; $monthsBack++) {
            $monthStart = $today->copy()->subMonths($monthsBack)->startOfMonth();
            $count = $monthsBack === 0 ? 6 : 5;

            for ($i = 0; $i < $count; $i++) {
                $day = (int) ($rand() * 27) + 1;
                $hour = (int) ($rand() * 12) + 8;
                $date = $monthStart->copy()->setDay(min($day, $monthStart->daysInMonth))->setHour($hour)->setMinute((int) ($rand() * 59));
                if ($date->isFuture() && $monthsBack === 0) {
                    $date = $today->copy()->setHour($hour)->setMinute((int) ($rand() * 59));
                }
                $p = $activeProduk[(int) ($rand() * count($activeProduk))];
                $jumlah = (int) ($rand() * 6) + 1;
                $recorder = $recorders[(int) ($rand() * count($recorders))];
                $rows[] = self::makePemasukan($id++, $p, $jumlah, $date, $recorder, $rand, $keteranganPool);
            }
        }

        // Soft-delete two rows to demo the badge (excluded from active lists/aggregates).
        $rows[count($rows) - 1]['dihapus_pada'] = '2026-06-20 10:00:00';
        $rows[count($rows) - 3]['dihapus_pada'] = '2026-06-21 11:00:00';

        usort($rows, fn ($a, $b) => strcmp($b['dibuat_pada'], $a['dibuat_pada']));

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $produk
     * @param  array<string, mixed>  $recorder
     * @param  callable(): float  $rand
     * @param  array<int, string>  $keteranganPool
     * @return array<string, mixed>
     */
    private static function makePemasukan(int $id, array $produk, int $jumlah, $date, array $recorder, callable $rand, array $keteranganPool): array
    {
        $hargaSatuan = (int) $produk['harga'];
        // Occasional small discount.
        if ($rand() < 0.18) {
            $hargaSatuan = (int) round($hargaSatuan * 0.9);
        }
        $total = $jumlah * $hargaSatuan;
        $keterangan = $keteranganPool[(int) ($rand() * count($keteranganPool))];

        return [
            'id' => $id,
            'id_produk' => $produk['id'],
            'tanggal_transaksi' => $date->format('Y-m-d'),
            'dibuat_pada' => $date->format('Y-m-d H:i:s'),
            'jumlah' => $jumlah,
            'harga_satuan' => $hargaSatuan,
            'total' => $total,
            'keterangan' => $keterangan,
            'id_pengguna' => $recorder['id'],
            'dihapus_pada' => null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $kategori
     * @param  array<int, array<string, mixed>>  $pengguna
     * @return array<int, array<string, mixed>>
     */
    private static function seedPengeluaran(array $kategori, array $pengguna): array
    {
        $rand = self::rng(31415);
        $rows = [];
        $id = 1;
        $recorders = array_values(array_filter($pengguna, fn ($u) => $u['dihapus_pada'] === null));
        $ranges = [1 => [200000, 1500000], 2 => [50000, 400000], 3 => [30000, 150000]];
        $keteranganPool = ['Pembelian rutin', 'Restok bulanan', 'Pembayaran vendor', 'Biaya kirim pesanan', 'Keperluan toko', ''];

        $today = now()->startOfDay();
        $rows[] = self::makePengeluaran($id++, $kategori[0], $ranges[1], $today->copy()->setHour(10)->setMinute(15), $recorders[0], $rand, $keteranganPool);

        for ($monthsBack = 0; $monthsBack <= 11; $monthsBack++) {
            $monthStart = $today->copy()->subMonths($monthsBack)->startOfMonth();
            $count = $monthsBack === 0 ? 4 : 3;

            for ($i = 0; $i < $count; $i++) {
                $day = (int) ($rand() * 27) + 1;
                $hour = (int) ($rand() * 10) + 8;
                $date = $monthStart->copy()->setDay(min($day, $monthStart->daysInMonth))->setHour($hour)->setMinute((int) ($rand() * 59));
                if ($date->isFuture() && $monthsBack === 0) {
                    $date = $today->copy()->subDay()->setHour($hour)->setMinute((int) ($rand() * 59));
                }
                $kat = $kategori[(int) ($rand() * count($kategori))];
                $recorder = $recorders[(int) ($rand() * count($recorders))];
                $rows[] = self::makePengeluaran($id++, $kat, $ranges[$kat['id']], $date, $recorder, $rand, $keteranganPool);
            }
        }

        $rows[count($rows) - 2]['dihapus_pada'] = '2026-06-18 09:00:00';

        usort($rows, fn ($a, $b) => strcmp($b['dibuat_pada'], $a['dibuat_pada']));

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $kategori
     * @param  array<string, mixed>  $recorder
     * @param  array{0:int,1:int}  $range
     * @param  callable(): float  $rand
     * @param  array<int, string>  $keteranganPool
     * @return array<string, mixed>
     */
    private static function makePengeluaran(int $id, array $kategori, array $range, $date, array $recorder, callable $rand, array $keteranganPool): array
    {
        $nominal = (int) round(($range[0] + $rand() * ($range[1] - $range[0])) / 1000) * 1000;
        $keterangan = $keteranganPool[(int) ($rand() * count($keteranganPool))];

        return [
            'id' => $id,
            'id_kategori' => $kategori['id'],
            'tanggal_transaksi' => $date->format('Y-m-d'),
            'dibuat_pada' => $date->format('Y-m-d H:i:s'),
            'nominal' => $nominal,
            'keterangan' => $keterangan,
            'id_pengguna' => $recorder['id'],
            'dihapus_pada' => null,
        ];
    }
}
