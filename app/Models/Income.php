<?php

namespace App\Models;

use App\Enums\JenisTransaksi;
use Database\Factories\IncomeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    /** @use HasFactory<IncomeFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nomor_transaksi',
        'product_id',
        'user_id',
        'tanggal_transaksi',
        'jenis_transaksi',
        'jumlah',
        'harga_satuan',
        'hpp_satuan',
        'harga_tipe',
        'total',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'jenis_transaksi' => JenisTransaksi::class,
            'harga_satuan' => 'decimal:2',
            'hpp_satuan' => 'decimal:2',
            'total' => 'decimal:2',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Generate nomor struk harian (TRX-YYYYMMDD-0001).
     * Harus dipanggil di dalam DB::transaction() agar lock aman.
     */
    public static function generateNomorTransaksi(): string
    {
        $prefix = 'TRX-'.now()->format('Ymd').'-';

        $last = static::withTrashed()
            ->where('nomor_transaksi', 'like', $prefix.'%')
            ->lockForUpdate()
            ->orderByDesc('nomor_transaksi')
            ->value('nomor_transaksi');

        $seq = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
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

    /** @return HasMany<SalesReturn, $this> */
    public function salesReturns(): HasMany
    {
        return $this->hasMany(SalesReturn::class, 'income_id');
    }

    public function jumlahDiretur(): int
    {
        if ($this->relationLoaded('salesReturns')) {
            return (int) $this->salesReturns->sum('jumlah');
        }

        return (int) $this->salesReturns()->sum('jumlah');
    }

    public function sisaRetur(): int
    {
        return max(0, (int) $this->jumlah - $this->jumlahDiretur());
    }

    /**
     * Status penjualan: gagal (soft-deleted), berhasil, retur_sebagian, semua_diretur.
     */
    public function statusTransaksi(): string
    {
        if ($this->trashed()) {
            return 'gagal';
        }

        $returned = $this->jumlahDiretur();
        $qty = (int) $this->jumlah;

        if ($returned <= 0) {
            return 'berhasil';
        }

        if ($returned >= $qty) {
            return 'semua_diretur';
        }

        return 'retur_sebagian';
    }

    public function statusTransaksiLabel(): string
    {
        return match ($this->statusTransaksi()) {
            'gagal' => 'Gagal',
            'semua_diretur' => 'Semua di retur',
            'retur_sebagian' => 'Retur sebagian',
            default => 'Berhasil',
        };
    }
}
