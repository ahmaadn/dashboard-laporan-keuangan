<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use App\Support\AppTimezone;
use Illuminate\Support\Facades\Auth;

/**
 * Mutasi stok append-only: update products.stok + insert stock_movements.
 * Caller must wrap multi-step flows in DB::transaction.
 */
final class StockService
{
    public function catatMasuk(
        Product $product,
        int $jumlah,
        string $sumber,
        ?int $refId = null,
        ?string $keterangan = null,
        ?int $userId = null,
        ?string $tanggal = null,
    ): StockMovement {
        if ($jumlah <= 0) {
            throw new \InvalidArgumentException('Jumlah masuk harus positif.');
        }

        return $this->record($product, $jumlah, 'masuk', $sumber, $refId, $keterangan, $userId, $tanggal);
    }

    public function catatKeluar(
        Product $product,
        int $jumlah,
        string $sumber,
        ?int $refId = null,
        ?string $keterangan = null,
        ?int $userId = null,
        ?string $tanggal = null,
    ): StockMovement {
        if ($jumlah <= 0) {
            throw new \InvalidArgumentException('Jumlah keluar harus positif.');
        }

        return $this->record($product, -$jumlah, 'keluar', $sumber, $refId, $keterangan, $userId, $tanggal);
    }

    /**
     * Sesuaikan stok ke nilai absolut baru (selisih dicatat sebagai koreksi).
     */
    public function koreksi(
        Product $product,
        int $stokBaru,
        ?string $keterangan = null,
        ?int $userId = null,
        ?string $tanggal = null,
    ): ?StockMovement {
        $product->refresh();
        $delta = $stokBaru - (int) $product->stok;

        if ($delta === 0) {
            return null;
        }

        return $this->record(
            $product,
            $delta,
            'koreksi',
            'koreksi',
            null,
            $keterangan,
            $userId,
            $tanggal,
        );
    }

    private function record(
        Product $product,
        int $signedJumlah,
        string $jenis,
        string $sumber,
        ?int $refId,
        ?string $keterangan,
        ?int $userId,
        ?string $tanggal,
    ): StockMovement {
        $product->refresh();
        $product->stok = (int) $product->stok + $signedJumlah;
        $product->save();

        return StockMovement::create([
            'product_id' => $product->id,
            'user_id' => $userId ?? Auth::id(),
            'tanggal' => $tanggal ?? AppTimezone::todayDateString(),
            'jenis' => $jenis,
            'jumlah' => $signedJumlah,
            'sumber' => $sumber,
            'ref_id' => $refId,
            'keterangan' => $keterangan,
        ]);
    }
}
