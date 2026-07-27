<?php

namespace App\Http\Resources;

use App\Models\CapitalInjection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CapitalInjection */
class CapitalInjectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_pengguna' => $this->user_id,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'nominal' => (int) $this->nominal,
            'keterangan' => $this->keterangan,
            'dibuat_pada' => $this->created_at?->format('Y-m-d H:i:s'),
            'dihapus_pada' => $this->when($this->trashed(), fn () => $this->deleted_at?->format('Y-m-d H:i:s')),
        ];
    }
}
