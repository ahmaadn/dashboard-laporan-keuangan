<?php

namespace App\Services;

use App\Models\CapitalInjection;
use App\Models\Expense;
use App\Models\Income;
use App\Models\SalesReturn;

/**
 * Saldo kas kumulatif sejak awal pencatatan (bukan per periode).
 *
 * Definisi:
 * - kasMasuk  = SUM(incomes.total) + SUM(capital_injections.nominal > 0)
 * - kasKeluar = SUM(expenses.nominal) + SUM(sales_returns.nominal_retur) + ABS(SUM(capital_injections.nominal < 0))
 * - saldo     = kasMasuk - kasKeluar
 *
 * Baris soft-deleted dikecualikan otomatis oleh global scope SoftDeletes.
 * Retur yang penjualan asalnya sudah di-soft-delete ikut gugur, sejalan
 * dengan {@see ReportService::metricsForRange()}.
 */
final class CashBalanceService
{
    /** Saldo kas kumulatif hingga tanggal tertentu (inklusif). */
    public function saldo(?string $sampaiTanggal = null): float
    {
        return $this->kasMasuk($sampaiTanggal) - $this->kasKeluar($sampaiTanggal);
    }

    public function kasMasuk(?string $sampaiTanggal = null): float
    {
        $penjualan = (float) $this->applyDateBound(Income::query(), 'tanggal_transaksi', $sampaiTanggal)->sum('total');
        $modal = (float) $this->applyDateBound(CapitalInjection::query(), 'tanggal', $sampaiTanggal)
            ->where('nominal', '>', 0)
            ->sum('nominal');

        return $penjualan + $modal;
    }

    public function kasKeluar(?string $sampaiTanggal = null): float
    {
        $pengeluaran = (float) $this->applyDateBound(Expense::query(), 'tanggal_transaksi', $sampaiTanggal)->sum('nominal');

        $returQuery = SalesReturn::query()
            ->join('incomes', 'incomes.id', '=', 'sales_returns.income_id')
            ->whereNull('incomes.deleted_at');
        $retur = (float) $this->applyDateBound($returQuery, 'sales_returns.tanggal', $sampaiTanggal)
            ->sum('sales_returns.nominal_retur');

        $hutangPiutang = (float) $this->applyDateBound(CapitalInjection::query(), 'tanggal', $sampaiTanggal)
            ->where('nominal', '<', 0)
            ->sum('nominal');

        return $pengeluaran + $retur + abs($hutangPiutang);
    }

    /**
     * Rincian saldo untuk ditampilkan di UI.
     *
     * @return array{kasMasuk: int, kasKeluar: int, saldo: int}
     */
    public function ringkasan(?string $sampaiTanggal = null): array
    {
        $masuk = $this->kasMasuk($sampaiTanggal);
        $keluar = $this->kasKeluar($sampaiTanggal);

        return [
            'kasMasuk' => (int) $masuk,
            'kasKeluar' => (int) $keluar,
            'saldo' => (int) ($masuk - $keluar),
        ];
    }

    /**
     * Saldo kas yang tersedia untuk sebuah transaksi pengeluaran, mengabaikan
     * kontribusi pengeluaran yang sedang diubah agar edit tidak menghitung
     * nominal lamanya dua kali.
     */
    public function saldoTersedia(?string $sampaiTanggal = null, ?Expense $kecuali = null): float
    {
        $saldo = $this->saldo($sampaiTanggal);

        if ($kecuali && ! $kecuali->trashed() && $this->withinBound($kecuali->tanggal_transaksi?->toDateString(), $sampaiTanggal)) {
            $saldo += (float) $kecuali->nominal;
        }

        return $saldo;
    }

    private function withinBound(?string $tanggal, ?string $sampaiTanggal): bool
    {
        if ($tanggal === null) {
            return false;
        }

        return $sampaiTanggal === null || $tanggal <= $sampaiTanggal;
    }

    /**
     * @template TQuery of \Illuminate\Database\Eloquent\Builder
     *
     * @param  TQuery  $query
     * @return TQuery
     */
    private function applyDateBound($query, string $column, ?string $sampaiTanggal)
    {
        if ($sampaiTanggal === null) {
            return $query;
        }

        return $query->where($column, '<=', $sampaiTanggal.' 23:59:59');
    }
}
