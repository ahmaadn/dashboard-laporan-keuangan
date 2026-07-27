<?php

namespace App\Models;

use Database\Factories\SalesReturnFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Retur penjualan = pengurang pendapatan, bukan beban.
 * Baris ini terkait dengan satu transaksi penjualan (income) yang diretur sebagian
 * atau seluruhnya (lihat REVISI_KONSEP_KEUANGAN.md Bagian 2.4 / 4).
 */
class SalesReturn extends Model
{
    /** @use HasFactory<SalesReturnFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'income_id',
        'product_id',
        'user_id',
        'tanggal',
        'jumlah',
        'nominal_retur',
        'alasan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal_retur' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Income, $this> */
    public function income(): BelongsTo
    {
        return $this->belongsTo(Income::class, 'income_id')->withTrashed();
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id')->withTrashed();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}
