<?php

namespace App\Models;

use Database\Factories\HppAdjustmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Koreksi HPP manual per tanggal (dicatat Admin di halaman Laporan).
 * Nominal bertanda: positif menambah HPP periode, negatif mengurangi.
 */
class HppAdjustment extends Model
{
    /** @use HasFactory<HppAdjustmentFactory> */
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
