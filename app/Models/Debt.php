<?php

namespace App\Models;

use Database\Factories\DebtFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Debt extends Model
{
    /** @use HasFactory<DebtFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['user_id', 'capital_injection_id', 'tanggal', 'nominal', 'keterangan'];

    protected function casts(): array
    {
        return ['capital_injection_id' => 'integer', 'tanggal' => 'date', 'nominal' => 'decimal:2', 'deleted_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /** @return BelongsTo<CapitalInjection, $this> */
    public function capitalInjection(): BelongsTo
    {
        return $this->belongsTo(CapitalInjection::class);
    }

    /** @return HasMany<DebtPayment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function paidAmount(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments->sum('nominal');
        }

        return (float) $this->payments()->sum('nominal');
    }

    public function remainingAmount(): float
    {
        return max(0, (float) $this->nominal - $this->paidAmount());
    }
}
