<?php

namespace App\Http\Resources;

use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin StockMovement */
class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_produk' => $this->product_id,
            'id_pengguna' => $this->user_id,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'jenis' => $this->jenis,
            'jumlah' => (int) $this->jumlah,
            'sumber' => $this->sumber,
            'id_referensi' => $this->ref_id,
            'keterangan' => $this->keterangan,
            'pencatat' => $this->whenLoaded('user', fn () => $this->user?->nama),
            'dibuat_pada' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
