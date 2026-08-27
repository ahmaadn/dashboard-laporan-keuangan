<?php

namespace App\Models;

use Database\Factories\DebtPaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class DebtPayment extends Model
{
    /** @use HasFactory<DebtPaymentFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['debt_id', 'user_id', 'tanggal', 'nominal', 'sumber', 'keterangan'];

    protected function casts(): array
    {
        return ['tanggal' => 'date', 'nominal' => 'decimal:2', 'deleted_at' => 'datetime'];
    }

    /** @return BelongsTo<Debt, $this> */
    public function debt(): BelongsTo
    {
        return $this->belongsTo(Debt::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }
}
