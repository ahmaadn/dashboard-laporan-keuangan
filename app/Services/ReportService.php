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
use Carbon\CarbonInterface;

/**
 * Laporan laba rugi bertingkat + arus kas (lihat REVISI_KONSEP_KEUANGAN.md Bagian 3.3).
 *
 * Definisi:
 * - penjualan            = SUM(incomes.total)                   [pendapatan kotor]
 * - returTotal           = SUM(sales_returns.nominal_retur)     [pengurang pendapatan]
 * - pendapatanBersih     = penjualan - returTotal               [Pendapatan Bersih]
 * - hppPenjualan         = SUM(incomes.jumlah * incomes.hpp_satuan)
 * - hppPenyesuaianTotal  = SUM(hpp_adjustments.nominal)
 * - hpp                  = hppPenjualan + hppPenyesuaianTotal
 * - labaKotor            = pendapatanBersih - hpp
 * - biayaOperasional     = SUM(expenses) WHERE category.is_bahan_baku = false
 * - labaBersih           = labaKotor - biayaOperasional
 * - pembelianBahanBaku   = SUM(expenses) WHERE category.is_bahan_baku = true
 * - pengeluaranKas       = pembelianBahanBaku + biayaOperasional
 * - modalTotal           = SUM(capital_injections.nominal)      [pembiayaan, bukan pendapatan]
 * - arusKasMasuk         = penjualan + modalTotal
 * - arusKasKeluar        = pengeluaranKas
 * - arusKasBersih        = arusKasMasuk - arusKasKeluar
 */
final class ReportService
{
    public function __construct(private readonly PeriodResolver $periods) {}

    /** @return array<string, mixed> */
    public function summary(string $period, ?string $start = null, ?string $end = null): array
    {
        $range = $this->periods->resolve($period, $start, $end);
        $startStr = $range['start']->toDateString();
        $endStr = $range['end']->toDateString();

        $metrics = $this->metricsForRange($startStr, $endStr);

        $productNames = Product::withTrashed()->pluck('nama', 'id');
        $categoryMeta = ExpenseCategory::withTrashed()->get()->keyBy('id');

        $incomeRows = Income::query()
            ->whereBetween('tanggal_transaksi', [$startStr, $endStr])
            ->selectRaw('product_id, SUM(jumlah) as qty, SUM(total) as total, SUM(jumlah * hpp_satuan) as hpp, COUNT(*) as count')
            ->groupBy('product_id')
            ->get();

        $returByProduct = SalesReturn::query()
            ->whereBetween('tanggal', [$startStr, $endStr])
            ->selectRaw('product_id, SUM(nominal_retur) as retur_nominal')
            ->groupBy('product_id')
            ->pluck('retur_nominal', 'product_id');

        $incomeByProduct = $incomeRows->map(function ($r) use ($productNames, $returByProduct) {
            $total = (float) $r->total;
            $hpp = (float) $r->hpp;
            $retur = (float) ($returByProduct[$r->product_id] ?? 0);

            return [
                'id' => $r->product_id,
                'nama' => $r->product_id ? ($productNames[$r->product_id] ?? 'Tanpa produk') : 'Tanpa produk',
                'qty' => (int) $r->qty,
                'count' => (int) $r->count,
                'total' => (int) $total,
                'retur' => (int) $retur,
                'net_total' => (int) ($total - $retur),
                'hpp' => (int) $hpp,
                'laba_kotor' => (int) ($total - $retur - $hpp),
            ];
        })->sortByDesc('net_total')->values()->all();

        $expenseRows = Expense::query()
            ->whereBetween('tanggal_transaksi', [$startStr, $endStr])
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
            ->whereBetween('tanggal_transaksi', [$startStr, $endStr])
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
            ->whereBetween('sales_returns.tanggal', [$startStr, $endStr])
            ->join('incomes', 'incomes.id', '=', 'sales_returns.income_id')
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
            ->whereBetween('tanggal', [$startStr, $endStr])
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
            ->whereBetween('tanggal', [$startStr, $endStr])
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
     *   hppPenjualan: float,
     *   hppPenyesuaianTotal: float,
     *   hpp: float,
     *   labaKotor: float,
     *   biayaOperasional: float,
     *   pembelianBahanBaku: float,
     *   pengeluaranKas: float,
     *   labaBersih: float,
     *   modalTotal: float,
     *   arusKasMasuk: float,
     *   arusKasKeluar: float,
     *   arusKasBersih: float
     * }
     */
    public function metricsForRange(string $startStr, string $endStr): array
    {
        $penjualan = (float) Income::whereBetween('tanggal_transaksi', [$startStr, $endStr])->sum('total');

        $returTotal = (float) SalesReturn::whereBetween('tanggal', [$startStr, $endStr])->sum('nominal_retur');

        $pendapatanBersih = $penjualan - $returTotal;

        $hppPenjualan = (float) Income::whereBetween('tanggal_transaksi', [$startStr, $endStr])
            ->selectRaw('COALESCE(SUM(jumlah * hpp_satuan), 0) as hpp')
            ->value('hpp');
        $hppPenyesuaianTotal = (float) HppAdjustment::whereBetween('tanggal', [$startStr, $endStr])->sum('nominal');
        $hpp = $hppPenjualan + $hppPenyesuaianTotal;
        $labaKotor = $pendapatanBersih - $hpp;

        $pembelianBahanBaku = (float) Expense::query()
            ->whereBetween('tanggal_transaksi', [$startStr, $endStr])
            ->whereHas('category', fn ($q) => $q->where('is_bahan_baku', true))
            ->sum('nominal');

        $biayaOperasional = (float) Expense::query()
            ->whereBetween('tanggal_transaksi', [$startStr, $endStr])
            ->whereHas('category', fn ($q) => $q->where('is_bahan_baku', false))
            ->sum('nominal');

        $pengeluaranKas = $pembelianBahanBaku + $biayaOperasional;
        $labaBersih = $labaKotor - $biayaOperasional;

        $modalTotal = (float) CapitalInjection::whereBetween('tanggal', [$startStr, $endStr])->sum('nominal');
        $arusKasMasuk = $penjualan + $modalTotal;
        $arusKasKeluar = $pengeluaranKas;
        $arusKasBersih = $arusKasMasuk - $arusKasKeluar;

        return [
            'penjualan' => $penjualan,
            'returTotal' => $returTotal,
            'pendapatanBersih' => $pendapatanBersih,
            'hppPenjualan' => $hppPenjualan,
            'hppPenyesuaianTotal' => $hppPenyesuaianTotal,
            'hpp' => $hpp,
            'labaKotor' => $labaKotor,
            'biayaOperasional' => $biayaOperasional,
            'pembelianBahanBaku' => $pembelianBahanBaku,
            'pengeluaranKas' => $pengeluaranKas,
            'labaBersih' => $labaBersih,
            'modalTotal' => $modalTotal,
            'arusKasMasuk' => $arusKasMasuk,
            'arusKasKeluar' => $arusKasKeluar,
            'arusKasBersih' => $arusKasBersih,
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

        $incomeRows = Income::query()
            ->whereBetween('tanggal_transaksi', [$startStr, $endStr])
            ->selectRaw('tanggal_transaksi, total')
            ->get();
        foreach ($incomeRows as $row) {
            $idx = $this->bucketIndex($buckets, $row->tanggal_transaksi->format('Y-m-d'), $granularity);
            if ($idx !== null) {
                $penjualan[$idx] += (float) $row->total;
            }
        }

        $returRows = SalesReturn::query()
            ->whereBetween('tanggal', [$startStr, $endStr])
            ->select('tanggal', 'nominal_retur')
            ->get();
        foreach ($returRows as $row) {
            $idx = $this->bucketIndex($buckets, $row->tanggal->format('Y-m-d'), $granularity);
            if ($idx !== null) {
                $retur[$idx] += (float) $row->nominal_retur;
            }
        }

        $expenseRows = Expense::query()
            ->whereBetween('tanggal_transaksi', [$startStr, $endStr])
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
}
