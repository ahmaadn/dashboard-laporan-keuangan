<?php

namespace App\Http\Controllers;

use App\Enums\JenisTransaksi;
use App\Http\Requests\IncomeRequest;
use App\Http\Resources\IncomeResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\Income;
use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request)
    {
        $incomes = Income::orderBy('created_at', 'desc')->get();
        $produkAktif = Product::where('is_active', true)->orderBy('nama')->get();
        $allProducts = Product::withTrashed()->get();
        $allUsers = User::withTrashed()->get();

        return view('income.index', [
            'pemasukan' => IncomeResource::collection($incomes)->resolve(),
            'produkAktif' => ProductResource::collection($produkAktif)->resolve(),
            'produkById' => collect(ProductResource::collection($allProducts)->resolve())->keyBy('id')->all(),
            'penggunaById' => collect(UserResource::collection($allUsers)->resolve())->keyBy('id')->all(),
            'currentUser' => $request->user() ? UserResource::make($request->user())->resolve() : null,
        ]);
    }

    public function store(IncomeRequest $request): JsonResponse
    {
        $mapped = $request->mapped();
        $hargaManual = (bool) ($mapped['harga_manual'] ?? false);
        unset($mapped['harga_manual']);

        $product = null;
        if ($mapped['product_id']) {
            $product = Product::findOrFail($mapped['product_id']);
            if ((int) $mapped['jumlah'] > (int) $product->stok) {
                return response()->json([
                    'message' => 'Stok tidak mencukupi, sisa '.(int) $product->stok.'.',
                    'errors' => [
                        'jumlah' => ['Stok tidak mencukupi, sisa '.(int) $product->stok.'.'],
                    ],
                ], 422);
            }
        }

        $income = DB::transaction(function () use ($request, $mapped, $hargaManual, $product) {
            if ($product) {
                $product = Product::lockForUpdate()->findOrFail($product->id);
                if ((int) $mapped['jumlah'] > (int) $product->stok) {
                    return null;
                }
            }

            $pricing = $this->resolvePricing($product, $mapped['jenis_transaksi'], (int) $mapped['jumlah'], (float) $mapped['harga_satuan'], $hargaManual);

            $income = Income::create([
                'product_id' => $mapped['product_id'],
                'user_id' => $request->user()->id,
                'tanggal_transaksi' => $mapped['tanggal_transaksi'],
                'jenis_transaksi' => $mapped['jenis_transaksi'],
                'jumlah' => $mapped['jumlah'],
                'harga_satuan' => $pricing['harga'],
                'hpp_satuan' => $product ? (float) $product->harga_modal : 0,
                'harga_tipe' => $pricing['tipe'],
                'total' => (int) $mapped['jumlah'] * $pricing['harga'],
                'keterangan' => $mapped['keterangan'],
            ]);

            if ($product) {
                $this->stock->catatKeluar(
                    $product,
                    (int) $mapped['jumlah'],
                    'penjualan',
                    $income->id,
                    'Penjualan #'.$income->id,
                    $request->user()->id,
                    $mapped['tanggal_transaksi'],
                );
            }

            return $income;
        });

        if ($income === null) {
            $sisa = $product ? (int) $product->fresh()->stok : 0;

            return response()->json([
                'message' => 'Stok tidak mencukupi, sisa '.$sisa.'.',
                'errors' => [
                    'jumlah' => ['Stok tidak mencukupi, sisa '.$sisa.'.'],
                ],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'resource' => IncomeResource::make($income->fresh())->resolve(),
        ], 201);
    }

    public function update(IncomeRequest $request, Income $income): JsonResponse
    {
        $this->authorize('update', $income);

        if ($income->trashed()) {
            return response()->json(['success' => false, 'message' => 'Transaksi yang sudah dihapus tidak dapat diubah.'], 422);
        }

        $mapped = $request->mapped();
        $hargaManual = (bool) ($mapped['harga_manual'] ?? false);
        unset($mapped['harga_manual']);

        try {
            $income = DB::transaction(function () use ($request, $income, $mapped, $hargaManual) {
                $oldProductId = $income->product_id;
                $oldQty = (int) $income->jumlah;

                if ($oldProductId) {
                    $oldProduct = Product::lockForUpdate()->find($oldProductId);
                    if ($oldProduct) {
                        $this->stock->catatMasuk(
                            $oldProduct,
                            $oldQty,
                            'penjualan',
                            $income->id,
                            'Revisi penjualan #'.$income->id.' (kembalikan stok)',
                            $request->user()->id,
                            $mapped['tanggal_transaksi'],
                        );
                    }
                }

                $product = null;
                if ($mapped['product_id']) {
                    $product = Product::lockForUpdate()->findOrFail($mapped['product_id']);
                    if ((int) $mapped['jumlah'] > (int) $product->stok) {
                        throw new \RuntimeException('STOCK:'.(int) $product->stok);
                    }
                }

                $pricing = $this->resolvePricing($product, $mapped['jenis_transaksi'], (int) $mapped['jumlah'], (float) $mapped['harga_satuan'], $hargaManual);

                $income->update([
                    'product_id' => $mapped['product_id'],
                    'tanggal_transaksi' => $mapped['tanggal_transaksi'],
                    'jenis_transaksi' => $mapped['jenis_transaksi'],
                    'jumlah' => $mapped['jumlah'],
                    'harga_satuan' => $pricing['harga'],
                    'hpp_satuan' => $product ? (float) $product->harga_modal : 0,
                    'harga_tipe' => $pricing['tipe'],
                    'total' => (int) $mapped['jumlah'] * $pricing['harga'],
                    'keterangan' => $mapped['keterangan'],
                ]);

                if ($product) {
                    $this->stock->catatKeluar(
                        $product,
                        (int) $mapped['jumlah'],
                        'penjualan',
                        $income->id,
                        'Penjualan #'.$income->id,
                        $request->user()->id,
                        $mapped['tanggal_transaksi'],
                    );
                }

                return $income;
            });
        } catch (\RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'STOCK:')) {
                $sisa = (int) substr($e->getMessage(), 6);

                return response()->json([
                    'message' => 'Stok tidak mencukupi, sisa '.$sisa.'.',
                    'errors' => ['jumlah' => ['Stok tidak mencukupi, sisa '.$sisa.'.']],
                ], 422);
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'resource' => IncomeResource::make($income->fresh())->resolve(),
        ]);
    }

    public function destroy(Request $request, Income $income): JsonResponse
    {
        $this->authorize('delete', $income);

        DB::transaction(function () use ($request, $income) {
            if ($income->product_id) {
                $product = Product::lockForUpdate()->find($income->product_id);
                if ($product) {
                    $this->stock->catatMasuk(
                        $product,
                        (int) $income->jumlah,
                        'penjualan',
                        $income->id,
                        'Hapus penjualan #'.$income->id.' (stok kembali)',
                        $request->user()->id,
                        $income->tanggal_transaksi?->toDateString(),
                    );
                }
            }

            $income->delete();
        });

        return response()->json(['success' => true]);
    }

    /**
     * @return array{harga: float, tipe: string}
     */
    private function resolvePricing(?Product $product, JenisTransaksi $jenis, int $qty, float $inputHarga, bool $hargaManual): array
    {
        if ($hargaManual || ! $product) {
            return ['harga' => $inputHarga, 'tipe' => 'manual'];
        }

        return $product->hargaUntuk($jenis, $qty);
    }
}
