<?php

namespace App\Services;

use App\Models\Income;
use App\Support\Format;
use Illuminate\Support\Collection;

/**
 * Membangun data nota penjualan (struk) dari sekumpulan baris `incomes`
 * yang berbagi `nomor_transaksi` yang sama.
 *
 * Nota berfungsi sebagai bukti pembelian, dokumentasi transaksi, dan dasar
 * apabila terjadi retur atau komplain pelanggan.
 */
final class SalesReceiptService
{
    /**
     * @return array<string, mixed>|null
     */
    public function byNomorTransaksi(string $nomorTransaksi): ?array
    {
        $rows = Income::with(['product', 'user', 'salesReturns'])
            ->where('nomor_transaksi', $nomorTransaksi)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return null;
        }

        return $this->build($nomorTransaksi, $rows);
    }

    /**
     * Nota untuk satu baris penjualan; menarik seluruh baris satu nomor transaksi.
     *
     * @return array<string, mixed>
     */
    public function byIncome(Income $income): array
    {
        if ($income->nomor_transaksi) {
            $receipt = $this->byNomorTransaksi($income->nomor_transaksi);

            if ($receipt !== null) {
                return $receipt;
            }
        }

        $income->loadMissing(['product', 'user', 'salesReturns']);

        return $this->build('#'.$income->id, collect([$income]));
    }

    /**
     * @param  Collection<int, Income>  $rows
     * @return array<string, mixed>
     */
    private function build(string $nomorTransaksi, $rows): array
    {
        $first = $rows->first();

        $items = $rows->map(function (Income $row): array {
            $diretur = $row->jumlahDiretur();

            return [
                'id' => $row->id,
                'nama_produk' => $row->product?->nama ?? 'Tanpa produk (lain-lain)',
                'sku' => $row->product?->sku,
                'jumlah' => (int) $row->jumlah,
                'jumlah_diretur' => $diretur,
                'harga_satuan' => (int) $row->harga_satuan,
                'harga_tipe' => $row->harga_tipe ?? 'manual',
                'subtotal' => (int) $row->total,
                'status' => $row->statusTransaksi(),
                'status_label' => $row->statusTransaksiLabel(),
            ];
        })->values()->all();

        $totalRetur = (int) $rows->sum(fn (Income $row) => $row->salesReturns
            ->sum('nominal_retur'));

        $subtotal = (int) $rows->sum('total');

        return [
            'nomor_transaksi' => $nomorTransaksi,
            'tanggal_transaksi' => $first->tanggal_transaksi?->format('Y-m-d'),
            'tanggal_label' => Format::tanggalLengkap($first->tanggal_transaksi?->format('Y-m-d')),
            'jenis_transaksi' => $first->jenis_transaksi?->value ?? 'offline',
            'jenis_transaksi_label' => $first->jenis_transaksi?->label() ?? 'Offline',
            'keterangan' => $first->keterangan,
            'kasir' => $first->user?->nama ?? '—',
            'items' => $items,
            'total_qty' => (int) $rows->sum('jumlah'),
            'subtotal' => $subtotal,
            'total_retur' => $totalRetur,
            'total' => $subtotal - $totalRetur,
            'usaha' => [
                'nama' => (string) config('app.name', 'BM Leather'),
            ],
        ];
    }
}
