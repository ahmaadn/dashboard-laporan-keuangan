<?php

namespace App\Http\Resources;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Expense */
class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_kategori' => $this->category_id,
            'tanggal_transaksi' => $this->tanggal_transaksi?->format('Y-m-d'),
            'dibuat_pada' => $this->created_at?->format('Y-m-d H:i:s'),
            'nominal' => (int) $this->nominal,
            'keterangan' => $this->keterangan,
            'id_pengguna' => $this->user_id,
            'dihapus_pada' => $this->when($this->trashed(), fn () => $this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
