<?php

namespace App\Models;

use Database\Factories\ExpenseCategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    /** @use HasFactory<ExpenseCategoryFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = ['nama', 'is_bahan_baku'];

    protected function casts(): array
    {
        return [
            'is_bahan_baku' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function isBahanBaku(): bool
    {
        return (bool) $this->is_bahan_baku;
    }

    /** @param  Builder<static>  $query */
    public function scopeBahanBaku(Builder $query): Builder
    {
        return $query->where('is_bahan_baku', true);
    }

    /** @param  Builder<static>  $query */
    public function scopeOperasional(Builder $query): Builder
    {
        return $query->where('is_bahan_baku', false);
    }

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
