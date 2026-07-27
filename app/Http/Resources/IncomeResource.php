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

        return [
            'id' => $this->id,
            'id_produk' => $this->product_id,
            'tanggal_transaksi' => $this->tanggal_transaksi?->format('Y-m-d'),
            'jenis_transaksi' => $jenis?->value ?? 'offline',
            'jenis_transaksi_label' => $jenis?->label() ?? 'Offline',
            'dibuat_pada' => $this->created_at?->format('Y-m-d H:i:s'),
            'jumlah' => $this->jumlah,
            'harga_satuan' => (int) $this->harga_satuan,
            'hpp_satuan' => (int) $this->hpp_satuan,
            'harga_tipe' => $this->harga_tipe ?? 'manual',
            'total' => (int) $this->total,
            'keterangan' => $this->keterangan,
            'id_pengguna' => $this->user_id,
            'dihapus_pada' => $this->when($this->trashed(), fn () => $this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
