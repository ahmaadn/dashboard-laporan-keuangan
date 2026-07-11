<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'id_kategori' => $this->category_id,
            'sku' => $this->sku,
            'harga' => (int) $this->harga,
            'deskripsi' => $this->deskripsi,
            'aktif' => $this->is_active,
            'dibuat_oleh' => $this->created_by,
            'dibuat_pada' => $this->created_at?->format('Y-m-d H:i:s'),
            'diperbarui_pada' => $this->updated_at?->format('Y-m-d H:i:s'),
            'dihapus_pada' => $this->when($this->trashed(), fn () => $this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
