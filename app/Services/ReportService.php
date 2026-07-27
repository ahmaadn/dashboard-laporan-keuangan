<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\HppAdjustment;
use App\Models\Income;
use App\Models\Product;
use App\Support\Format;
use Carbon\CarbonInterface;

/**
 * Laporan laba rugi bertingkat.
 *
 * Definisi:
 * - Penjualan (Pemasukan) = SUM(incomes.total)
 * - HPP = SUM(incomes.jumlah × hpp_satuan) + SUM(hpp_adjustments.nominal) periode
 * - Laba Kotor = Penjualan − HPP
 * - Biaya Operasional = SUM(pengeluaran) kategori SELAIN is_bahan_baku
 * - Laba Bersih = Laba Kotor − Biaya Operasional
 * - Surplus Kas = Pemasukan − Semua Pengeluaran (termasuk Bahan Baku)
 * - Pembelian Bahan Baku = kas keluar bahan baku (bukan beban laba rugi langsung)
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

        $incomeByProduct = $incomeRows->map(function ($r) use ($productNames) {
            $total = (float) $r->total;
            $hpp = (float) $r->hpp;

            return [
                'id' => $r->product_id,
                'nama' => $r->product_id ? ($productNames[$r->product_id] ?? 'Tanpa produk') : 'Tanpa produk',
                'qty' => (int) $r->qty,
                'count' => (int) $r->count,
                'total' => (int) $total,
                'hpp' => (int) $hpp,
                'laba_kotor' => (int) ($total - $hpp),
            ];
        })->sortByDesc('total')->values()->all();

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
            'online' => ['count' => 0, 'qty' => 0, 'total' => 0],
            'offline' => ['count' => 0, 'qty' => 0, 'total' => 0],
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
            ];
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

        return array_merge($metrics, [
            'period' => $period,
            'start' => $startStr,
            'end' => $endStr,
            'rangeLabel' => Format::tanggalLengkap($startStr).' — '.Format::tanggalLengkap($endStr),
            'incomeByProduct' => $incomeByProduct,
            'expenseByCategory' => $expenseByCategory,
            'incomeByChannel' => $incomeByChannel,
            'hppPenyesuaian' => $hppAdjustments,
            'hasData' => $metrics['penjualan'] > 0 || $metrics['pengeluaranKas'] > 0 || abs($metrics['hppPenyesuaianTotal']) > 0,
            // Backward-compatible keys used by older UI/tests/export
            'totalIncome' => $metrics['penjualan'],
            'totalExpense' => $metrics['pengeluaranKas'],
            'profit' => $metrics['labaBersih'],
        ]);
    }

    /**
     * @return array{
     *   penjualan: float,
     *   hppPenjualan: float,
     *   hppPenyesuaianTotal: float,
     *   hpp: float,
     *   labaKotor: float,
     *   biayaOperasional: float,
     *   pembelianBahanBaku: float,
     *   pengeluaranKas: float,
     *   labaBersih: float,
     *   surplusKas: float,
     *   selisihKasVsLaba: float
     * }
     */
    public function metricsForRange(string $startStr, string $endStr): array
    {
        $penjualan = (float) Income::whereBetween('tanggal_transaksi', [$startStr, $endStr])->sum('total');
        $hppPenjualan = (float) Income::whereBetween('tanggal_transaksi', [$startStr, $endStr])
            ->selectRaw('COALESCE(SUM(jumlah * hpp_satuan), 0) as hpp')
            ->value('hpp');
        $hppPenyesuaianTotal = (float) HppAdjustment::whereBetween('tanggal', [$startStr, $endStr])->sum('nominal');
        $hpp = $hppPenjualan + $hppPenyesuaianTotal;
        $labaKotor = $penjualan - $hpp;

        $pengeluaranKas = (float) Expense::whereBetween('tanggal_transaksi', [$startStr, $endStr])->sum('nominal');

        $pembelianBahanBaku = (float) Expense::query()
            ->whereBetween('tanggal_transaksi', [$startStr, $endStr])
            ->whereHas('category', fn ($q) => $q->where('is_bahan_baku', true))
            ->sum('nominal');

        $biayaOperasional = $pengeluaranKas - $pembelianBahanBaku;
        $labaBersih = $labaKotor - $biayaOperasional;
        $surplusKas = $penjualan - $pengeluaranKas;
        // Selisih kas vs laba ≈ perubahan nilai persediaan (bahan dibeli − HPP terpakai)
        $selisihKasVsLaba = $surplusKas - $labaBersih;

        return [
            'penjualan' => $penjualan,
            'hppPenjualan' => $hppPenjualan,
            'hppPenyesuaianTotal' => $hppPenyesuaianTotal,
            'hpp' => $hpp,
            'labaKotor' => $labaKotor,
            'biayaOperasional' => $biayaOperasional,
            'pembelianBahanBaku' => $pembelianBahanBaku,
            'pengeluaranKas' => $pengeluaranKas,
            'labaBersih' => $labaBersih,
            'surplusKas' => $surplusKas,
            'selisihKasVsLaba' => $selisihKasVsLaba,
        ];
    }

    /**
     * @return array{income: float, expense: float, profit: float, labaKotor: float, labaBersih: float, hpp: float}
     */
    public function summaryForRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $m = $this->metricsForRange($start->toDateString(), $end->toDateString());

        return [
            'income' => $m['penjualan'],
            'expense' => $m['pengeluaranKas'],
            'profit' => $m['labaBersih'],
            'labaKotor' => $m['labaKotor'],
            'labaBersih' => $m['labaBersih'],
            'hpp' => $m['hpp'],
        ];
    }
}
