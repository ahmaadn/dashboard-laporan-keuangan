<?php

namespace App\Models;

use Database\Factories\CapitalInjectionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Suntikan modal / setoran pemilik ke dalam usaha.
 * Dicatat terpisah dari transaksi penjualan karena bersifat pembiayaan,
 * bukan pendapatan operasional (lihat REVISI_KONSEP_KEUANGAN.md Bagian 2.1).
 */
class CapitalInjection extends Model
{
    /** @use HasFactory<CapitalInjectionFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'tanggal',
        'nominal',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
