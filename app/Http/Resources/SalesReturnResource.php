<?php

namespace App\Http\Resources;

use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SalesReturn */
class SalesReturnResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_penjualan' => $this->income_id,
            'id_produk' => $this->product_id,
            'id_pengguna' => $this->user_id,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'jumlah' => (int) $this->jumlah,
            'nominal_retur' => (int) $this->nominal_retur,
            'alasan' => $this->alasan,
            'dibuat_pada' => $this->created_at?->format('Y-m-d H:i:s'),
            'dihapus_pada' => $this->when($this->trashed(), fn () => $this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
