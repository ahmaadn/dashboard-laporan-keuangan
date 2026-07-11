<?php

namespace App\Models;

use Database\Factories\IncomeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    /** @use HasFactory<IncomeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'user_id',
        'tanggal_transaksi',
        'jumlah',
        'harga_satuan',
        'total',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'harga_satuan' => 'decimal:2',
            'total' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
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
