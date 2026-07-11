<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Income;
use App\Support\Format;
use Carbon\CarbonInterface;

/**
 * Builds the financial report structure for the Reports page and exports.
 */
final class ReportService
{
    public function __construct(private readonly PeriodResolver $periods) {}

    /** @return array<string, mixed> */
    public function summary(string $period, ?string $start = null, ?string $end = null): array
    {
        $range = $this->periods->resolve($period, $start, $end);

        $incomes = Income::whereBetween('tanggal_transaksi', [$range['start']->toDateString(), $range['end']->toDateString()])
            ->selectRaw('product_id, SUM(jumlah) as qty, SUM(total) as total, COUNT(*) as count')
            ->groupBy('product_id')
            ->get();

        $expenses = Expense::whereBetween('tanggal_transaksi', [$range['start']->toDateString(), $range['end']->toDateString()])
            ->selectRaw('category_id, SUM(nominal) as total, COUNT(*) as count')
            ->groupBy('category_id')
            ->get();

        $totalIncome = (float) Income::whereBetween('tanggal_transaksi', [$range['start']->toDateString(), $range['end']->toDateString()])->sum('total');
        $totalExpense = (float) Expense::whereBetween('tanggal_transaksi', [$range['start']->toDateString(), $range['end']->toDateString()])->sum('nominal');

        $incomeByProduct = $incomes->map(fn ($r) => [
            'id' => $r->product_id,
            'nama' => $r->product?->nama ?? 'Tanpa produk',
            'qty' => (int) $r->qty,
            'count' => (int) $r->count,
            'total' => (int) $r->total,
        ])->sortByDesc('total')->values()->all();

        $expenseByCategory = $expenses->map(fn ($r) => [
            'id' => $r->category_id,
            'nama' => $r->category?->nama ?? 'Lainnya',
            'count' => (int) $r->count,
            'total' => (int) $r->total,
        ])->sortByDesc('total')->values()->all();

        return [
            'period' => $period,
            'start' => $range['start']->toDateString(),
            'end' => $range['end']->toDateString(),
            'rangeLabel' => Format::tanggalLengkap($range['start']->toDateString()).' — '.Format::tanggalLengkap($range['end']->toDateString()),
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'profit' => $totalIncome - $totalExpense,
            'incomeByProduct' => $incomeByProduct,
            'expenseByCategory' => $expenseByCategory,
            'hasData' => $totalIncome > 0 || $totalExpense > 0,
        ];
    }

    /**
     * Compute a simple summary for a preset period (used by comparison).
     *
     * @return array{income: float, expense: float, profit: float}
     */
    public function summaryForRange(CarbonInterface $start, CarbonInterface $end): array
    {
        $startStr = $start->toDateString();
        $endStr = $end->toDateString();

        $income = (float) Income::whereBetween('tanggal_transaksi', [$startStr, $endStr])->sum('total');
        $expense = (float) Expense::whereBetween('tanggal_transaksi', [$startStr, $endStr])->sum('nominal');

        return ['income' => $income, 'expense' => $expense, 'profit' => $income - $expense];
    }
}
