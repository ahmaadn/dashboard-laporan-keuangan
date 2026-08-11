<?php

namespace App\Services;

use App\Models\CapitalInjection;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\HppAdjustment;
use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Support\Format;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Laporan laba rugi bertingkat + arus kas (lihat REVISI_KONSEP_KEUANGAN.md Bagian 3.3).
 *
 * Definisi:
 * - penjualan            = SUM(incomes.total)                   [pendapatan kotor]
 * - returTotal           = SUM(sales_returns.nominal_retur)     [pengurang pendapatan]
 * - pendapatanBersih     = penjualan - returTotal               [Pendapatan Bersih]
 * - hppKotor             = SUM(incomes.jumlah * incomes.hpp_satuan)
 * - hppRetur             = SUM(sales_returns.jumlah * incomes.hpp_satuan)  [COGS dibalik]
 * - hppPenjualan         = hppKotor - hppRetur                  [HPP produk net terjual]
 * - hppPenyesuaianTotal  = SUM(hpp_adjustments.nominal)
 * - hpp                  = hppPenjualan + hppPenyesuaianTotal
 * - labaKotor            = pendapatanBersih - hpp
 * - biayaOperasional     = SUM(expenses) WHERE category.is_bahan_baku = false
 * - labaBersih           = labaKotor - biayaOperasional
 * - pembelianBahanBaku   = SUM(expenses) WHERE category.is_bahan_baku = true
 * - pengeluaranKas       = pembelianBahanBaku + biayaOperasional
 * - modalTotal           = SUM(capital_injections.nominal)      [pembiayaan, bukan pendapatan]
 * - arusKasMasuk         = penjualan + modalTotal
 * - returKeluar          = returTotal                             [uang dikembalikan ke pelanggan]
 * - arusKasKeluar        = pengeluaranKas + returKeluar
 * - arusKasBersih        = arusKasMasuk - arusKasKeluar
 *
 * {@see self::cashJournal()} memecah arusKasBersih menjadi jurnal per transaksi
 * dengan saldo berjalan, memakai definisi kas yang sama.
 */
final class ReportService
{
    public function __construct(
        private readonly PeriodResolver $periods,
        private readonly CashBalanceService $cashBalance,
    ) {}

    /** @return array<string, mixed> */
    public function summary(string $period, ?string $start = null, ?string $end = null): array
    {
        $range = $this->periods->resolve($period, $start, $end);
        $startStr = $range['start_date'];
        $endStr = $range['end_date'];
        $startSql = $range['start_sql'];
        $endSql = $range['end_sql'];

        $metrics = $this->metricsForRange($startStr, $endStr);

        $productNames = Product::withTrashed()->pluck('nama', 'id');
        $categoryMeta = ExpenseCategory::withTrashed()->get()->keyBy('id');

        $incomeRows = Income::query()
            ->whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->selectRaw('product_id, SUM(jumlah) as qty, SUM(total) as total, SUM(jumlah * hpp_satuan) as hpp, COUNT(*) as count')
            ->groupBy('product_id')
            ->get();

        $returByProduct = SalesReturn::query()
            ->whereBetween('sales_returns.tanggal', [$startSql, $endSql])
            ->join('incomes', 'incomes.id', '=', 'sales_returns.income_id')
            ->whereNull('incomes.deleted_at')
            ->selectRaw('COALESCE(sales_returns.product_id, incomes.product_id) as product_id, SUM(sales_returns.nominal_retur) as retur_nominal, SUM(sales_returns.jumlah) as retur_qty')
            ->groupByRaw('COALESCE(sales_returns.product_id, incomes.product_id)')
            ->get()
            ->keyBy('product_id');

        $hppReturByProduct = SalesReturn::query()
            ->whereBetween('sales_returns.tanggal', [$startSql, $endSql])
            ->join('incomes', 'incomes.id', '=', 'sales_returns.income_id')
            ->whereNull('incomes.deleted_at')
            ->selectRaw('COALESCE(sales_returns.product_id, incomes.product_id) as product_id, SUM(sales_returns.jumlah * incomes.hpp_satuan) as hpp_retur')
            ->groupByRaw('COALESCE(sales_returns.product_id, incomes.product_id)')
            ->pluck('hpp_retur', 'product_id');

        $incomeByProduct = $incomeRows->map(function ($r) use ($productNames, $returByProduct, $hppReturByProduct) {
            $total = (float) $r->total;
            $hppKotor = (float) $r->hpp;
            $hppRetur = (float) ($hppReturByProduct[$r->product_id] ?? 0);
            $hpp = max(0, $hppKotor - $hppRetur);
            $returRow = $returByProduct[$r->product_id] ?? null;
            $retur = (float) ($returRow->retur_nominal ?? 0);
            $returQty = (int) ($returRow->retur_qty ?? 0);
            $qty = (int) $r->qty;

            return [
                'id' => $r->product_id,
                'nama' => $r->product_id ? ($productNames[$r->product_id] ?? 'Tanpa produk') : 'Tanpa produk',
                'qty' => $qty,
                'retur_qty' => $returQty,
                'net_qty' => max(0, $qty - $returQty),
                'count' => (int) $r->count,
                'total' => (int) $total,
                'retur' => (int) $retur,
                'net_total' => (int) ($total - $retur),
                'hpp' => (int) $hpp,
                'laba_kotor' => (int) ($total - $retur - $hpp),
            ];
        })->sortByDesc('net_total')->values()->all();

        $expenseRows = Expense::query()
            ->whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->selectRaw('category_id, SUM(nominal) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->get();

        $expenseByCategory = $expenseRows->map(function ($r) use ($categoryMeta) {
            $cat = $categoryMeta[$r->category_id] ?? null;

            return [
                'id' => $r->category_id,
                'nama' => $cat?->nama ?? 'Lainnya',
                'is_bahan_baku' => (bool) ($cat?->is_bahan_baku),
                'count' => (int) $r->count,
                'total' => (int) $r->total,
            ];
        })->sortByDesc('total')->values()->all();

        $channelRows = Income::query()
            ->whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->selectRaw('jenis_transaksi, COUNT(*) as count, SUM(jumlah) as qty, SUM(total) as total')
            ->groupBy('jenis_transaksi')
            ->get();

        $incomeByChannel = [
            'online' => ['count' => 0, 'qty' => 0, 'total' => 0, 'retur' => 0, 'net_total' => 0],
            'offline' => ['count' => 0, 'qty' => 0, 'total' => 0, 'retur' => 0, 'net_total' => 0],
        ];
        foreach ($channelRows as $row) {
            $key = is_object($row->jenis_transaksi) ? $row->jenis_transaksi->value : (string) $row->jenis_transaksi;
            if (! isset($incomeByChannel[$key])) {
                continue;
            }
            $incomeByChannel[$key] = [
                'count' => (int) $row->count,
                'qty' => (int) $row->qty,
                'total' => (int) $row->total,
                'retur' => 0,
                'net_total' => (int) $row->total,
            ];
        }

        $returChannelRows = SalesReturn::query()
            ->whereBetween('sales_returns.tanggal', [$startSql, $endSql])
            ->join('incomes', 'incomes.id', '=', 'sales_returns.income_id')
            ->whereNull('incomes.deleted_at')
            ->selectRaw('incomes.jenis_transaksi, SUM(sales_returns.nominal_retur) as retur_nominal')
            ->groupBy('incomes.jenis_transaksi')
            ->pluck('retur_nominal', 'incomes.jenis_transaksi');

        foreach ($returChannelRows as $key => $nominal) {
            $keyStr = is_object($key) ? $key->value : (string) $key;
            if (! isset($incomeByChannel[$keyStr])) {
                continue;
            }
            $incomeByChannel[$keyStr]['retur'] = (int) $nominal;
            $incomeByChannel[$keyStr]['net_total'] = $incomeByChannel[$keyStr]['total'] - (int) $nominal;
        }

        $hppAdjustments = HppAdjustment::query()
            ->whereBetween('tanggal', [$startSql, $endSql])
            ->orderByDesc('tanggal')
            ->get()
            ->map(fn (HppAdjustment $a) => [
                'id' => $a->id,
                'tanggal' => $a->tanggal?->format('Y-m-d'),
                'nominal' => (int) $a->nominal,
                'keterangan' => $a->keterangan,
            ])
            ->all();

        $capitalInjections = CapitalInjection::query()
            ->whereBetween('tanggal', [$startSql, $endSql])
            ->orderByDesc('tanggal')
            ->get()
            ->map(fn (CapitalInjection $c) => [
                'id' => $c->id,
                'tanggal' => $c->tanggal?->format('Y-m-d'),
                'nominal' => (int) $c->nominal,
                'keterangan' => $c->keterangan,
            ])
            ->all();

        return array_merge($metrics, [
            'period' => $period,
            'start' => $startStr,
            'end' => $endStr,
            'rangeLabel' => Format::tanggalLengkap($startStr).' — '.Format::tanggalLengkap($endStr),
            'incomeByProduct' => $incomeByProduct,
            'expenseByCategory' => $expenseByCategory,
            'incomeByChannel' => $incomeByChannel,
            'hppPenyesuaian' => $hppAdjustments,
            'capitalInjections' => $capitalInjections,
            'cashJournal' => $this->cashJournal($startStr, $endStr),
            'hasData' => $metrics['penjualan'] > 0 || $metrics['pengeluaranKas'] > 0
                || abs($metrics['hppPenyesuaianTotal']) > 0 || $metrics['returTotal'] > 0
                || $metrics['modalTotal'] > 0,
            // Backward-compatible alias untuk UI/export lama.
            'totalIncome' => $metrics['pendapatanBersih'],
            'totalExpense' => $metrics['pengeluaranKas'],
            'profit' => $metrics['labaBersih'],
        ]);
    }

    /**
     * @return array{
     *   penjualan: float,
     *   returTotal: float,
     *   pendapatanBersih: float,
     *   hppKotor: float,
     *   hppRetur: float,
     *   hppPenjualan: float,
     *   hppPenyesuaianTotal: float,
     *   hpp: float,
     *   labaKotor: float,
     *   biayaOperasional: float,
     *   pembelianBahanBaku: float,
     *   pengeluaranKas: float,
     *   labaBersih: float,
     *   modalTotal: float,
     *   returKeluar: float,
     *   arusKasMasuk: float,
     *   arusKasKeluar: float,
     *   arusKasBersih: float
     * }
     */
    public function metricsForRange(string $startStr, string $endStr): array
    {
        [$startSql, $endSql] = $this->sqlDateBounds($startStr, $endStr);

        $penjualan = (float) Income::whereBetween('tanggal_transaksi', [$startSql, $endSql])->sum('total');

        // Retur penjualan adalah pengurang pendapatan; jika income sumbernya di-soft-delete,
        // retur ikut gugur (sejalan dengan filter incomes.deleted_at di query HPP retur di bawah).
        $returTotal = (float) SalesReturn::query()
            ->whereBetween('sales_returns.tanggal', [$startSql, $endSql])
            ->join('incomes', 'incomes.id', '=', 'sales_returns.income_id')
            ->whereNull('incomes.deleted_at')
            ->sum('sales_returns.nominal_retur');

        $pendapatanBersih = $penjualan - $returTotal;

        $hppKotor = (float) Income::whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->selectRaw('COALESCE(SUM(jumlah * hpp_satuan), 0) as hpp')
            ->value('hpp');

        // HPP dibalik sebanding qty retur × hpp_satuan penjualan asal (produk net terjual).
        $hppRetur = (float) SalesReturn::query()
            ->whereBetween('sales_returns.tanggal', [$startSql, $endSql])
            ->join('incomes', 'incomes.id', '=', 'sales_returns.income_id')
            ->whereNull('incomes.deleted_at')
            ->selectRaw('COALESCE(SUM(sales_returns.jumlah * incomes.hpp_satuan), 0) as hpp_retur')
            ->value('hpp_retur');

        $hppPenjualan = max(0, $hppKotor - $hppRetur);
        $hppPenyesuaianTotal = (float) HppAdjustment::whereBetween('tanggal', [$startSql, $endSql])->sum('nominal');
        $hpp = $hppPenjualan + $hppPenyesuaianTotal;
        $labaKotor = $pendapatanBersih - $hpp;

        $pembelianBahanBaku = (float) Expense::query()
            ->whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->whereHas('category', fn ($q) => $q->withTrashed()->where('is_bahan_baku', true))
            ->sum('nominal');

        $biayaOperasional = (float) Expense::query()
            ->whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->whereHas('category', fn ($q) => $q->withTrashed()->where('is_bahan_baku', false))
            ->sum('nominal');

        $pengeluaranKas = $pembelianBahanBaku + $biayaOperasional;
        $labaBersih = $labaKotor - $biayaOperasional;

        $modalTotal = (float) CapitalInjection::whereBetween('tanggal', [$startSql, $endSql])->sum('nominal');
        $arusKasMasuk = $penjualan + $modalTotal;
        $returKeluar = $returTotal;
        $arusKasKeluar = $pengeluaranKas + $returKeluar;
        $arusKasBersih = $arusKasMasuk - $arusKasKeluar;

        return [
            'penjualan' => $penjualan,
            'returTotal' => $returTotal,
            'pendapatanBersih' => $pendapatanBersih,
            'hppKotor' => $hppKotor,
            'hppRetur' => $hppRetur,
            'hppPenjualan' => $hppPenjualan,
            'hppPenyesuaianTotal' => $hppPenyesuaianTotal,
            'hpp' => $hpp,
            'labaKotor' => $labaKotor,
            'biayaOperasional' => $biayaOperasional,
            'pembelianBahanBaku' => $pembelianBahanBaku,
            'pengeluaranKas' => $pengeluaranKas,
            'labaBersih' => $labaBersih,
            'modalTotal' => $modalTotal,
            'returKeluar' => $returKeluar,
            'arusKasMasuk' => $arusKasMasuk,
            'arusKasKeluar' => $arusKasKeluar,
            'arusKasBersih' => $arusKasBersih,
        ];
    }

    /**
     * Jurnal arus kas: seluruh mutasi kas pada periode, terurut tanggal, dengan
     * saldo berjalan. Baris jurnal mengikuti definisi arus kas di kelas ini —
     * kas masuk = penjualan + modal; kas keluar = pengeluaran + retur.
     *
     * Saldo awal dihitung kumulatif sebelum tanggal mulai, sehingga saldo akhir
     * jurnal sama dengan saldo kas kumulatif pada akhir periode.
     *
     * @return array{
     *   saldoAwal: int,
     *   saldoAkhir: int,
     *   totalMasuk: int,
     *   totalKeluar: int,
     *   entries: array<int, array<string, mixed>>
     * }
     */
    public function cashJournal(string $startStr, string $endStr): array
    {
        [$startSql, $endSql] = $this->sqlDateBounds($startStr, $endStr);

        $entries = [];

        foreach (Income::query()
            ->with('product')
            ->whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get() as $row) {
            $entries[] = [
                'tanggal' => $row->tanggal_transaksi?->format('Y-m-d'),
                'jenis' => 'masuk',
                'sumber' => 'penjualan',
                'kategori' => 'Penjualan',
                'keterangan' => trim(($row->nomor_transaksi ? $row->nomor_transaksi.' · ' : '')
                    .($row->product?->nama ?? 'Tanpa produk')),
                'masuk' => (int) $row->total,
                'keluar' => 0,
            ];
        }

        foreach (CapitalInjection::query()
            ->whereBetween('tanggal', [$startSql, $endSql])
            ->orderBy('tanggal')
            ->orderBy('id')
            ->get() as $row) {
            $entries[] = [
                'tanggal' => $row->tanggal?->format('Y-m-d'),
                'jenis' => 'masuk',
                'sumber' => 'modal',
                'kategori' => 'Modal / Setoran Pemilik',
                'keterangan' => $row->keterangan ?: 'Setoran modal',
                'masuk' => (int) $row->nominal,
                'keluar' => 0,
            ];
        }

        foreach (Expense::query()
            ->with('category')
            ->whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->orderBy('tanggal_transaksi')
            ->orderBy('id')
            ->get() as $row) {
            $entries[] = [
                'tanggal' => $row->tanggal_transaksi?->format('Y-m-d'),
                'jenis' => 'keluar',
                'sumber' => $row->category?->is_bahan_baku ? 'bahan_baku' : 'operasional',
                'kategori' => $row->category?->nama ?? 'Lainnya',
                'keterangan' => $row->keterangan ?: '—',
                'masuk' => 0,
                'keluar' => (int) $row->nominal,
            ];
        }

        foreach (SalesReturn::query()
            ->join('incomes', 'incomes.id', '=', 'sales_returns.income_id')
            ->whereNull('incomes.deleted_at')
            ->whereBetween('sales_returns.tanggal', [$startSql, $endSql])
            ->orderBy('sales_returns.tanggal')
            ->orderBy('sales_returns.id')
            ->select('sales_returns.*')
            ->with('product')
            ->get() as $row) {
            $entries[] = [
                'tanggal' => $row->tanggal?->format('Y-m-d'),
                'jenis' => 'keluar',
                'sumber' => 'retur',
                'kategori' => 'Retur Penjualan',
                'keterangan' => trim(($row->product?->nama ?? 'Tanpa produk')
                    .' · '.$row->jumlah.' unit'
                    .($row->alasan ? ' · '.$row->alasan : '')),
                'masuk' => 0,
                'keluar' => (int) $row->nominal_retur,
            ];
        }

        usort($entries, fn (array $a, array $b) => [$a['tanggal'], $a['jenis']] <=> [$b['tanggal'], $b['jenis']]);

        $saldoAwal = (int) $this->cashBalance->saldo(
            CarbonImmutable::parse($startStr)->subDay()->toDateString(),
        );

        $saldo = $saldoAwal;
        $totalMasuk = 0;
        $totalKeluar = 0;

        foreach ($entries as $i => $entry) {
            $saldo += $entry['masuk'] - $entry['keluar'];
            $totalMasuk += $entry['masuk'];
            $totalKeluar += $entry['keluar'];
            $entries[$i]['saldo'] = $saldo;
        }

        return [
            'saldoAwal' => $saldoAwal,
            'saldoAkhir' => $saldo,
            'totalMasuk' => $totalMasuk,
            'totalKeluar' => $totalKeluar,
            'entries' => $entries,
        ];
    }

    /**
     * @return array{income: float, expense: float, profit: float, labaKotor: float, labaBersih: float, hpp: float, pendapatanBersih: float, arusKasBersih: float}
     */
    public function summaryForRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $m = $this->metricsForRange($start->toDateString(), $end->toDateString());

        return [
            'income' => $m['pendapatanBersih'],
            'expense' => $m['pengeluaranKas'],
            'profit' => $m['labaBersih'],
            'labaKotor' => $m['labaKotor'],
            'labaBersih' => $m['labaBersih'],
            'hpp' => $m['hpp'],
            'pendapatanBersih' => $m['pendapatanBersih'],
            'penjualan' => $m['penjualan'],
            'returTotal' => $m['returTotal'],
            'modalTotal' => $m['modalTotal'],
            'arusKasBersih' => $m['arusKasBersih'],
        ];
    }

    /**
     * Per-bucket trend series untuk dashboard: penjualan, retur, pendapatan_bersih, kas_keluar.
     *
     * @return array{penjualan: array<int, float>, retur: array<int, float>, pendapatanBersih: array<int, float>, kasKeluar: array<int, float>}
     */
    public function trendForRange(string $startStr, string $endStr, array $buckets, string $granularity): array
    {
        $penjualan = array_fill(0, count($buckets), 0.0);
        $retur = array_fill(0, count($buckets), 0.0);
        $pendapatanBersih = array_fill(0, count($buckets), 0.0);
        $kasKeluar = array_fill(0, count($buckets), 0.0);

        [$startSql, $endSql] = $this->sqlDateBounds($startStr, $endStr);

        $incomeRows = Income::query()
            ->whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->selectRaw('tanggal_transaksi, total')
            ->get();
        foreach ($incomeRows as $row) {
            $idx = $this->bucketIndex($buckets, $row->tanggal_transaksi->format('Y-m-d'), $granularity);
            if ($idx !== null) {
                $penjualan[$idx] += (float) $row->total;
            }
        }

        $returRows = SalesReturn::query()
            ->whereBetween('tanggal', [$startSql, $endSql])
            ->select('tanggal', 'nominal_retur')
            ->get();
        foreach ($returRows as $row) {
            $idx = $this->bucketIndex($buckets, $row->tanggal->format('Y-m-d'), $granularity);
            if ($idx !== null) {
                $retur[$idx] += (float) $row->nominal_retur;
            }
        }

        $expenseRows = Expense::query()
            ->whereBetween('tanggal_transaksi', [$startSql, $endSql])
            ->select('tanggal_transaksi', 'nominal')
            ->get();
        foreach ($expenseRows as $row) {
            $idx = $this->bucketIndex($buckets, $row->tanggal_transaksi->format('Y-m-d'), $granularity);
            if ($idx !== null) {
                $kasKeluar[$idx] += (float) $row->nominal;
            }
        }

        foreach ($buckets as $i => $_) {
            $pendapatanBersih[$i] = $penjualan[$i] - $retur[$i];
        }

        return [
            'penjualan' => $penjualan,
            'retur' => $retur,
            'pendapatanBersih' => $pendapatanBersih,
            'kasKeluar' => $kasKeluar,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $buckets
     */
    private function bucketIndex(array $buckets, string $dateStr, string $granularity): ?int
    {
        foreach ($buckets as $i => $b) {
            if ($granularity === 'month') {
                if (substr((string) $dateStr, 0, 7) === $b['key']) {
                    return $i;
                }
            } elseif ($b['key'] === $dateStr) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Inclusive SQL bounds for DATE columns stored as datetime ("Y-m-d 00:00:00").
     *
     * @return array{0: string, 1: string}
     */
    private function sqlDateBounds(string $startStr, string $endStr): array
    {
        $start = strlen($startStr) <= 10 ? $startStr.' 00:00:00' : $startStr;
        $end = strlen($endStr) <= 10 ? $endStr.' 23:59:59' : $endStr;

        return [$start, $end];
    }
}
