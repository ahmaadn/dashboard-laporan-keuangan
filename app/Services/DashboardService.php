<?php

namespace App\Services;

use App\Http\Resources\ExpenseResource;
use App\Http\Resources\IncomeResource;
use App\Models\Expense;
use App\Models\Income;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Computes all dashboard aggregations server-side for a given period.
 */
final class DashboardService
{
    private const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    private const CATEGORY_COLORS = ['#1c1108', '#f36458', '#8a7a6a', '#c4b8aa', '#3d2a1a'];

    private const PRODUCT_COLORS = ['#f36458', '#1c1108', '#37cd84', '#8a7a6a', '#0052ef'];

    public function __construct(private readonly PeriodResolver $periods) {}

    /** @return array<string, mixed> */
    public function data(string $period, ?string $start = null, ?string $end = null): array
    {
        $range = $this->periods->resolve($period, $start, $end);

        $startStr = $range['start']->toDateString();
        $endStr = $range['end']->toDateString();

        $incomes = Income::whereBetween('tanggal_transaksi', [$startStr, $endStr])->orderBy('created_at', 'desc')->get();
        $expenses = Expense::whereBetween('tanggal_transaksi', [$startStr, $endStr])->orderBy('created_at', 'desc')->get();

        $totalIncome = (float) $incomes->sum('total');
        $totalExpense = (float) $expenses->sum('nominal');

        $buckets = $this->buildBuckets($range['start'], $range['end'], $range['granularity']);
        $trend = $this->computeTrend($incomes, $expenses, $buckets, $range['granularity']);

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
            ],
            'summary' => [
                'income' => $totalIncome,
                'expense' => $totalExpense,
                'profit' => $totalIncome - $totalExpense,
                'hasData' => $totalIncome > 0 || $totalExpense > 0,
            ],
            'trend' => [
                'labels' => array_column($buckets, 'label'),
                'income' => $trend['income'],
                'expense' => $trend['expense'],
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
     * @return array{a: array{income: float, expense: float, profit: float, label: string}, b: array{...}}
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
     * @param  CarbonInterface  $start
     * @param  CarbonInterface  $end
     * @return array<int, array<string, mixed>>
     */
    private function buildBuckets($start, $end, string $granularity): array
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
        foreach ($expenses as $r) {
            $cid = $r->category_id;
            if (! isset($byCat[$cid])) {
                $byCat[$cid] = ['id' => $cid, 'label' => $r->category?->nama ?? 'Lainnya', 'value' => 0];
            }
            $byCat[$cid]['value'] += (float) $r->nominal;
        }

        return collect($byCat)->sortByDesc('value')->values()->all();
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
                $byProduct[$pid] = ['id' => $pid, 'nama' => $r->product?->nama ?? 'Tanpa produk', 'qty' => 0, 'total' => 0];
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
                'label' => $p['nama'],
                'productId' => $p['id'],
                'data' => $data,
            ];
        }

        return ['labels' => $labels, 'datasets' => $datasets];
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

    /** @return array{income: float, expense: float, profit: float} */
    private function summaryForRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $income = (float) Income::whereBetween('tanggal_transaksi', [$startStr, $endStr])->sum('total');
        $expense = (float) Expense::whereBetween('tanggal_transaksi', [$startStr, $endStr])->sum('nominal');

        return ['income' => $income, 'expense' => $expense, 'profit' => $income - $expense];
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
