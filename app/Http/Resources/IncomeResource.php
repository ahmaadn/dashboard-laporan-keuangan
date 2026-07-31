<?php

namespace App\Http\Resources;

use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Income */
class IncomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $jenis = $this->jenis_transaksi;
        $jumlahDiretur = $this->jumlahDiretur();
        $sisaRetur = max(0, (int) $this->jumlah - $jumlahDiretur);

        $returHistory = [];
        if ($this->relationLoaded('salesReturns')) {
            $returHistory = $this->salesReturns
                ->sortByDesc('tanggal')
                ->values()
                ->map(fn ($r) => [
                    'id' => $r->id,
                    'tanggal' => $r->tanggal?->format('Y-m-d'),
                    'jumlah' => (int) $r->jumlah,
                    'nominal_retur' => (int) $r->nominal_retur,
                    'alasan' => $r->alasan,
                    'dibuat_pada' => $r->created_at?->format('Y-m-d\TH:i:s\Z'),
                ])
                ->all();
        }

        return [
            'id' => $this->id,
            'id_produk' => $this->product_id,
            'tanggal_transaksi' => $this->tanggal_transaksi?->format('Y-m-d'),
            'jenis_transaksi' => $jenis?->value ?? 'offline',
            'jenis_transaksi_label' => $jenis?->label() ?? 'Offline',
            'dibuat_pada' => $this->created_at?->format('Y-m-d\TH:i:s\Z'),
            'jumlah' => $this->jumlah,
            'jumlah_diretur' => $jumlahDiretur,
            'sisa_retur' => $sisaRetur,
            'harga_satuan' => (int) $this->harga_satuan,
            'hpp_satuan' => (int) $this->hpp_satuan,
            'harga_tipe' => $this->harga_tipe ?? 'manual',
            'total' => (int) $this->total,
            'keterangan' => $this->keterangan,
            'id_pengguna' => $this->user_id,
            'status' => $this->statusTransaksi(),
            'status_label' => $this->statusTransaksiLabel(),
            'retur_history' => $returHistory,
            'dihapus_pada' => $this->when($this->trashed(), fn () => $this->deleted_at?->format('Y-m-d\TH:i:s\Z')),
        ];
    }
}
