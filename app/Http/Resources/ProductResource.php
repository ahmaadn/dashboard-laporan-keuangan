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
            'harga_modal' => (int) $this->harga_modal,
            'harga_grosir' => $this->harga_grosir !== null ? (int) $this->harga_grosir : null,
            'min_qty_grosir' => (int) ($this->min_qty_grosir ?: 3),
            'stok' => (int) $this->stok,
            'stok_minimum' => (int) ($this->stok_minimum ?: 5),
            'stok_rendah' => $this->isStokRendah(),
            'deskripsi' => $this->deskripsi,
            'aktif' => $this->is_active,
            'dibuat_oleh' => $this->created_by,
            'dibuat_pada' => $this->created_at?->format('Y-m-d H:i:s'),
            'diperbarui_pada' => $this->updated_at?->format('Y-m-d H:i:s'),
            'dihapus_pada' => $this->when($this->trashed(), fn () => $this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
