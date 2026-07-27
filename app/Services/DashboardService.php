<?php

namespace App\Services;

use App\Http\Resources\ExpenseResource;
use App\Http\Resources\IncomeResource;
use App\Models\Expense;
use App\Models\Income;
use App\Models\Product;
use App\Models\SalesReturn;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Agregasi dashboard untuk periode aktif.
 *
 * Model keuangan (lihat REVISI_KONSEP_KEUANGAN.md Bagian 3 & ReportService):
 * - Pendapatan Bersih  = penjualan - retur
 * - Laba Kotor         = Pendapatan Bersih - HPP
 * - Laba Bersih        = Laba Kotor - Beban Operasional
 * - Arus Kas Bersih    = (penjualan + modal) - seluruh kas keluar
 */
final class DashboardService
{
    private const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const PERIOD_HINTS = [
        'hari_ini' => 'Hari Ini = tanggal hari ini; grafik memakai jam pencatatan.',
        'minggu_ini' => 'Minggu Ini = Senin–Minggu kalender; grafik memakai tanggal transaksi.',
        'bulan_ini' => 'Bulan Ini = tanggal 1–akhir bulan; grafik memakai tanggal transaksi.',
        'tahun_ini' => 'Tahun Ini = 1 Jan–31 Des; grafik memakai bulan transaksi.',
        'rentang' => 'Rentang kustom; grafik memakai tanggal transaksi.',
    ];

    public function __construct(
        private readonly PeriodResolver $periods,
        private readonly ReportService $reports,
    ) {}

    /** @return array<string, mixed> */
    public function data(string $period, ?string $start = null, ?string $end = null): array
    {
        $range = $this->periods->resolve($period, $start, $end);

        $startStr = $range['start']->toDateString();
        $endStr = $range['end']->toDateString();

        $incomes = Income::whereBetween('tanggal_transaksi', [$startStr, $endStr])->orderBy('created_at', 'desc')->get();
        $expenses = Expense::with('category')->whereBetween('tanggal_transaksi', [$startStr, $endStr])->orderBy('created_at', 'desc')->get();

        $metrics = $this->reports->metricsForRange($startStr, $endStr);

        $buckets = $this->buildBuckets($range['start'], $range['end'], $range['granularity']);
        $trend = $this->computeTrend($incomes, $expenses, $buckets, $range['granularity']);
        $series = $this->reports->trendForRange($startStr, $endStr, $buckets, $range['granularity']);

        $categoryBreakdown = $this->computeCategoryBreakdown($expenses);
        $productAggregates = $this->computeProductAggregates($incomes);
        $topProducts = $productAggregates->take(5)->values()->all();
        $productTrend = $this->computeProductTrend($incomes, $buckets, $range['granularity'], $topProducts);

        return [
            'range' => [
                'start' => $startStr,
                'end' => $endStr,
                'label' => $this->periodLabel($period),
                'granularity' => $range['granularity'],
                'hint' => self::PERIOD_HINTS[$period] ?? self::PERIOD_HINTS['bulan_ini'],
            ],
            'summary' => [
                'income' => $metrics['pendapatanBersih'],
                'expense' => $metrics['pengeluaranKas'],
                'profit' => $metrics['labaBersih'],
                'penjualan' => $metrics['penjualan'],
                'returTotal' => $metrics['returTotal'],
                'pendapatanBersih' => $metrics['pendapatanBersih'],
                'pengeluaranKas' => $metrics['pengeluaranKas'],
                'hpp' => $metrics['hpp'],
                'labaKotor' => $metrics['labaKotor'],
                'biayaOperasional' => $metrics['biayaOperasional'],
                'pembelianBahanBaku' => $metrics['pembelianBahanBaku'],
                'labaBersih' => $metrics['labaBersih'],
                'modalTotal' => $metrics['modalTotal'],
                'arusKasMasuk' => $metrics['arusKasMasuk'],
                'arusKasKeluar' => $metrics['arusKasKeluar'],
                'arusKasBersih' => $metrics['arusKasBersih'],
                'hasData' => $metrics['penjualan'] > 0 || $metrics['pengeluaranKas'] > 0
                    || $metrics['returTotal'] > 0 || $metrics['modalTotal'] > 0,
            ],
            'incomeByChannel' => $this->incomeByChannel($startStr, $endStr),
            'lowStock' => $this->lowStockProducts(),
            'trend' => [
                'labels' => array_column($buckets, 'label'),
                'income' => $trend['income'],
                'expense' => $trend['expense'],
                'pendapatanBersih' => $series['pendapatanBersih'],
                'penjualan' => $series['penjualan'],
                'retur' => $series['retur'],
                'kasKeluar' => $series['kasKeluar'],
                'buckets' => $buckets,
                'granularity' => $range['granularity'],
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'productAggregates' => $productAggregates->values()->all(),
            'topProducts' => $topProducts,
            'productTrend' => $productTrend,
            'income' => IncomeResource::collection($incomes)->resolve(),
            'expense' => ExpenseResource::collection($expenses)->resolve(),
            'recentTransactions' => $this->recentTransactions(),
        ];
    }

    /**
     * @return array{a: array<string, mixed>, b: array<string, mixed>}
     */
    public function compare(string $aPreset, string $bPreset, ?string $aStart = null, ?string $aEnd = null, ?string $bStart = null, ?string $bEnd = null): array
    {
        $rangeA = $this->periods->resolvePreset($aPreset, $aStart, $aEnd);
        $rangeB = $this->periods->resolvePreset($bPreset, $bStart, $bEnd);

        return [
            'a' => array_merge(
                $this->summaryForRange($rangeA['start'], $rangeA['end']),
                ['label' => $this->presetLabel($aPreset)],
            ),
            'b' => array_merge(
                $this->summaryForRange($rangeB['start'], $rangeB['end']),
                ['label' => $this->presetLabel($bPreset)],
            ),
        ];
    }

    /** @return array<int, mixed> */
    private function recentTransactions(): array
    {
        $recentIncomes = Income::orderBy('created_at', 'desc')->limit(5)->get()
            ->map(fn ($r) => array_merge(
                IncomeResource::make($r)->resolve(),
                ['type' => 'pemasukan', 'amount' => (int) $r->total, 'date' => $r->created_at?->format('Y-m-d H:i:s')],
            ));

        $recentExpenses = Expense::orderBy('created_at', 'desc')->limit(5)->get()
            ->map(fn ($r) => array_merge(
                ExpenseResource::make($r)->resolve(),
                ['type' => 'pengeluaran', 'amount' => (int) $r->nominal, 'date' => $r->created_at?->format('Y-m-d H:i:s')],
            ));

        return $recentIncomes->concat($recentExpenses)
            ->sortByDesc('date')
            ->take(10)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBuckets(CarbonInterface $start, CarbonInterface $end, string $granularity): array
    {
        $buckets = [];
        $spanYears = $start->year !== $end->year;

        if ($granularity === 'hour') {
            for ($h = 0; $h < 24; $h++) {
                $key = 'h'.$h;
                $label = str_pad((string) $h, 2, '0', STR_PAD_LEFT).'.00';
                $buckets[] = ['key' => $key, 'label' => $label, 'hour' => $h];
            }
        } elseif ($granularity === 'day') {
            $cur = CarbonImmutable::parse($start)->startOfDay();
            $endImmutable = CarbonImmutable::parse($end)->endOfDay();
            while ($cur <= $endImmutable) {
                $key = $cur->toDateString();
                $label = $cur->day.' '.self::MONTHS_SHORT[$cur->month - 1];
                $buckets[] = ['key' => $key, 'label' => $label];
                $cur = $cur->addDay();
            }
        } else {
            $cur = CarbonImmutable::parse($start)->startOfMonth();
            $endMonth = CarbonImmutable::parse($end)->startOfMonth();
            while ($cur <= $endMonth) {
                $key = $cur->format('Y-m');
                $label = $spanYears
                    ? self::MONTHS_SHORT[$cur->month - 1].' '.$cur->year
                    : self::MONTHS_SHORT[$cur->month - 1];
                $buckets[] = ['key' => $key, 'label' => $label];
                $cur = $cur->addMonth();
            }
        }

        return $buckets;
    }

    /**
     * @param  array<int, array<string, mixed>>  $buckets
     * @return array{income: array<int, float>, expense: array<int, float>}
     */
    private function computeTrend($incomes, $expenses, array $buckets, string $granularity): array
    {
        $incomeData = array_fill(0, count($buckets), 0.0);
        $expenseData = array_fill(0, count($buckets), 0.0);

        foreach ($incomes as $r) {
            $key = $this->bucketKey($r, $granularity);
            $idx = $this->bucketIndex($buckets, $key);
            if ($idx !== null) {
                $incomeData[$idx] += (float) $r->total;
            }
        }

        foreach ($expenses as $r) {
            $key = $this->bucketKey($r, $granularity);
            $idx = $this->bucketIndex($buckets, $key);
            if ($idx !== null) {
                $expenseData[$idx] += (float) $r->nominal;
            }
        }

        return ['income' => $incomeData, 'expense' => $expenseData];
    }

    /** @return array<int, array<string, mixed>> */
    private function computeCategoryBreakdown($expenses): array
    {
        $byCat = [];
        $totalAll = 0.0;
        foreach ($expenses as $r) {
            $cid = $r->category_id;
            if (! isset($byCat[$cid])) {
                $byCat[$cid] = [
                    'id' => $cid,
                    'label' => $r->category?->nama ?? 'Lainnya',
                    'value' => 0,
                    'is_bahan_baku' => (bool) ($r->category?->is_bahan_baku),
                ];
            }
            $byCat[$cid]['value'] += (float) $r->nominal;
            $totalAll += (float) $r->nominal;
        }

        return collect($byCat)->map(function (array $row) use ($totalAll) {
            $row['percent'] = $totalAll > 0 ? round(($row['value'] / $totalAll) * 100, 1) : 0;

            return $row;
        })->sortByDesc('value')->values()->all();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function computeProductAggregates($incomes)
    {
        $byProduct = [];
        foreach ($incomes as $r) {
            if (! $r->product_id) {
                continue;
            }
            $pid = $r->product_id;
            if (! isset($byProduct[$pid])) {
                $byProduct[$pid] = [
                    'id' => $pid,
                    'nama' => $r->product?->nama ?? 'Tanpa produk',
                    'stok' => (int) ($r->product?->stok ?? 0),
                    'stok_rendah' => (bool) ($r->product?->isStokRendah() ?? false),
                    'qty' => 0,
                    'total' => 0,
                ];
            }
            $byProduct[$pid]['qty'] += (int) $r->jumlah;
            $byProduct[$pid]['total'] += (int) $r->total;
        }

        return collect($byProduct)->sortByDesc('qty')->values();
    }

    /**
     * @param  array<int, array<string, mixed>>  $buckets
     * @param  array<int, array<string, mixed>>  $topProducts
     * @return array{labels: array<int, string>, datasets: array<int, array<string, mixed>>}
     */
    private function computeProductTrend($incomes, array $buckets, string $granularity, array $topProducts): array
    {
        $labels = array_column($buckets, 'label');
        $datasets = [];

        foreach ($topProducts as $i => $p) {
            $data = array_fill(0, count($buckets), 0);
            foreach ($incomes as $r) {
                if ($r->product_id !== $p['id']) {
                    continue;
                }
                $key = $this->bucketKey($r, $granularity);
                $idx = $this->bucketIndex($buckets, $key);
                if ($idx !== null) {
                    $data[$idx] += (int) $r->total;
                }
            }
            $datasets[] = [
                'label' => $p['nama'].($p['stok_rendah'] ? ' · rendah' : ''),
                'productId' => $p['id'],
                'data' => $data,
            ];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
    }

    /** @return array{online: array<string, int>, offline: array<string, int>} */
    private function incomeByChannel(string $startStr, string $endStr): array
    {
        $channels = [
            'online' => ['count' => 0, 'qty' => 0, 'total' => 0, 'retur' => 0, 'net_total' => 0],
            'offline' => ['count' => 0, 'qty' => 0, 'total' => 0, 'retur' => 0, 'net_total' => 0],
        ];

        $incomeRows = Income::query()
            ->whereBetween('tanggal_transaksi', [$startStr, $endStr])
            ->selectRaw('jenis_transaksi, COUNT(*) as count, SUM(jumlah) as qty, SUM(total) as total')
            ->groupBy('jenis_transaksi')
            ->get();

        foreach ($incomeRows as $row) {
            $key = is_object($row->jenis_transaksi) ? $row->jenis_transaksi->value : (string) $row->jenis_transaksi;
            if (! isset($channels[$key])) {
                continue;
            }
            $channels[$key]['count'] = (int) $row->count;
            $channels[$key]['qty'] = (int) $row->qty;
            $channels[$key]['total'] = (int) $row->total;
            $channels[$key]['net_total'] = (int) $row->total;
        }

        $returRows = SalesReturn::query()
            ->whereBetween('sales_returns.tanggal', [$startStr, $endStr])
            ->join('incomes', 'incomes.id', '=', 'sales_returns.income_id')
            ->selectRaw('incomes.jenis_transaksi, SUM(sales_returns.nominal_retur) as retur_nominal')
            ->groupBy('incomes.jenis_transaksi')
            ->pluck('retur_nominal', 'incomes.jenis_transaksi');

        foreach ($returRows as $key => $nominal) {
            $keyStr = is_object($key) ? $key->value : (string) $key;
            if (! isset($channels[$keyStr])) {
                continue;
            }
            $channels[$keyStr]['retur'] = (int) $nominal;
            $channels[$keyStr]['net_total'] = $channels[$keyStr]['total'] - (int) $nominal;
        }

        return $channels;
    }

    /** @return array<int, array{id: int, nama: string, stok: int, stok_minimum: int}> */
    private function lowStockProducts(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->whereColumn('stok', '<=', 'stok_minimum')
            ->orderBy('stok')
            ->limit(10)
            ->get(['id', 'nama', 'stok', 'stok_minimum'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'nama' => $p->nama,
                'stok' => (int) $p->stok,
                'stok_minimum' => (int) $p->stok_minimum,
            ])
            ->all();
    }

    private function bucketKey($row, string $granularity): string
    {
        if ($granularity === 'hour') {
            return 'h'.$row->created_at->hour;
        }
        if ($granularity === 'month') {
            return substr($row->tanggal_transaksi->format('Y-m-d'), 0, 7);
        }

        return $row->tanggal_transaksi->format('Y-m-d');
    }

    /**
     * @param  array<int, array<string, mixed>>  $buckets
     */
    private function bucketIndex(array $buckets, string $key): ?int
    {
        foreach ($buckets as $i => $b) {
            if ($b['key'] === $key) {
                return $i;
            }
        }

        return null;
    }

    /** @return array<string, float> */
    private function summaryForRange(CarbonInterface $start, CarbonInterface $end): array
    {
        return $this->reports->summaryForRange($start, $end);
    }

    private function periodLabel(string $period): string
    {
        return PeriodResolver::OPTIONS[$period] ?? 'Bulan Ini';
    }

    private function presetLabel(string $preset): string
    {
        return [
            'hari_ini' => 'Hari Ini',
            'minggu_ini' => 'Minggu Ini',
            'bulan_ini' => 'Bulan Ini',
            'tahun_ini' => 'Tahun Ini',
            'bulan_lalu' => 'Bulan Lalu',
            'tahun_lalu' => 'Tahun Lalu',
            'rentang' => 'Rentang Kustom',
        ][$preset] ?? $preset;
    }
}
