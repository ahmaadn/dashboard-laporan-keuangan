<?php

namespace App\Http\Resources;

use App\Models\Debt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Debt */
class DebtResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'id_pengguna' => $this->user_id,
            'tanggal' => $this->tanggal?->format('Y-m-d'),
            'nominal' => (int) $this->nominal,
            'terbayar' => (int) $this->paidAmount(),
            'sisa' => (int) $this->remainingAmount(),
            'keterangan' => $this->keterangan,
            'pembayaran' => $this->whenLoaded('payments', fn () => $this->payments->map(fn ($payment) => [
                'id' => $payment->id,
                'tanggal' => $payment->tanggal?->format('Y-m-d'),
                'nominal' => (int) $payment->nominal,
                'sumber' => $payment->sumber,
                'keterangan' => $payment->keterangan,
            ])->values()->all()),
        ];
    }
}
