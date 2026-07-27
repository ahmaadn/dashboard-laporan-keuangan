<?php

namespace App\Models;

use App\Enums\JenisTransaksi;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'nama',
        'sku',
        'harga',
        'harga_modal',
        'harga_grosir',
        'min_qty_grosir',
        'stok',
        'stok_minimum',
        'deskripsi',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
            'harga_modal' => 'decimal:2',
            'harga_grosir' => 'decimal:2',
            'min_qty_grosir' => 'integer',
            'stok' => 'integer',
            'stok_minimum' => 'integer',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Harga jual otomatis berdasarkan kanal & qty.
     * Offline + qty ≥ min_qty_grosir + harga_grosir terisi → grosir; selain itu eceran.
     *
     * @return array{harga: float, tipe: string}
     */
    public function hargaUntuk(JenisTransaksi $jenis, int $qty): array
    {
        $hargaGrosir = $this->harga_grosir;
        $minQty = (int) ($this->min_qty_grosir ?: 3);

        if (
            $jenis === JenisTransaksi::Offline
            && $qty >= $minQty
            && $hargaGrosir !== null
            && (float) $hargaGrosir > 0
        ) {
            return ['harga' => (float) $hargaGrosir, 'tipe' => 'grosir'];
        }

        return ['harga' => (float) $this->harga, 'tipe' => 'eceran'];
    }

    public function isStokRendah(): bool
    {
        return (int) $this->stok <= (int) $this->stok_minimum;
    }

    /** @return BelongsTo<ProductCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<Income, $this> */
    public function incomes(): HasMany
    {
        return $this->hasMany(Income::class, 'product_id');
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }
}
