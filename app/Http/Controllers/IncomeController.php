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
use RuntimeException;

class IncomeController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request)
    {
        $incomes = Income::with('salesReturns')->orderBy('created_at', 'desc')->get();
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
        if ($request->isMultiItem()) {
            return $this->storeMulti($request);
        }

        return $this->storeSingle($request);
    }

    private function storeSingle(IncomeRequest $request): JsonResponse
    {
        $mapped = $request->mapped();
        $hargaManual = (bool) ($mapped['harga_manual'] ?? false);
        unset($mapped['harga_manual']);

        $product = null;
        if ($mapped['product_id']) {
            $product = Product::findOrFail($mapped['product_id']);
            if ((int) $mapped['jumlah'] > (int) $product->stok) {
                return $this->stockInsufficientResponse((int) $product->stok);
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
            $nomor = Income::generateNomorTransaksi();

            $income = Income::create([
                'nomor_transaksi' => $nomor,
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
                    'Penjualan '.$nomor,
                    $request->user()->id,
                    $mapped['tanggal_transaksi'],
                );
            }

            return $income;
        });

        if ($income === null) {
            $sisa = $product ? (int) $product->fresh()->stok : 0;

            return $this->stockInsufficientResponse($sisa);
        }

        return response()->json([
            'success' => true,
            'resource' => IncomeResource::make($income->fresh()->load('salesReturns'))->resolve(),
        ], 201);
    }

    private function storeMulti(IncomeRequest $request): JsonResponse
    {
        $mapped = $request->mapped();
        /** @var list<array{product_id: ?int, jumlah: int, harga_satuan: float, total: float, harga_manual: bool}> $items */
        $items = $mapped['items'];

        $qtyByProduct = [];
        foreach ($items as $index => $item) {
            if ($item['product_id'] === null) {
                continue;
            }
            $qtyByProduct[$item['product_id']] = ($qtyByProduct[$item['product_id']] ?? 0) + $item['jumlah'];
        }

        foreach ($qtyByProduct as $productId => $qty) {
            $product = Product::find($productId);
            if (! $product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.',
                    'errors' => ['items' => ['Produk tidak ditemukan.']],
                ], 422);
            }
            if ($qty > (int) $product->stok) {
                return response()->json([
                    'success' => false,
                    'message' => "Stok {$product->nama} tidak mencukupi, sisa ".(int) $product->stok.'.',
                    'errors' => [
                        'items' => ["Stok {$product->nama} tidak mencukupi, sisa ".(int) $product->stok.'.'],
                    ],
                ], 422);
            }
        }

        try {
            $created = DB::transaction(function () use ($request, $mapped, $items, $qtyByProduct) {
                $locked = [];
                foreach (array_keys($qtyByProduct) as $productId) {
                    $product = Product::lockForUpdate()->findOrFail($productId);
                    $needed = $qtyByProduct[$productId];
                    if ($needed > (int) $product->stok) {
                        throw new RuntimeException('STOCK:'.$product->nama.':'.(int) $product->stok);
                    }
                    $locked[$productId] = $product;
                }

                $nomor = Income::generateNomorTransaksi();
                $incomes = [];

                foreach ($items as $item) {
                    $product = $item['product_id'] ? $locked[$item['product_id']] : null;
                    $pricing = $this->resolvePricing(
                        $product,
                        $mapped['jenis_transaksi'],
                        $item['jumlah'],
                        $item['harga_satuan'],
                        $item['harga_manual'],
                    );

                    $income = Income::create([
                        'nomor_transaksi' => $nomor,
                        'product_id' => $item['product_id'],
                        'user_id' => $request->user()->id,
                        'tanggal_transaksi' => $mapped['tanggal_transaksi'],
                        'jenis_transaksi' => $mapped['jenis_transaksi'],
                        'jumlah' => $item['jumlah'],
                        'harga_satuan' => $pricing['harga'],
                        'hpp_satuan' => $product ? (float) $product->harga_modal : 0,
                        'harga_tipe' => $pricing['tipe'],
                        'total' => $item['jumlah'] * $pricing['harga'],
                        'keterangan' => $mapped['keterangan'],
                    ]);

                    if ($product) {
                        $this->stock->catatKeluar(
                            $product,
                            $item['jumlah'],
                            'penjualan',
                            $income->id,
                            'Penjualan '.$nomor,
                            $request->user()->id,
                            $mapped['tanggal_transaksi'],
                        );
                        $product->refresh();
                        $locked[$product->id] = $product;
                    }

                    $incomes[] = $income->fresh()->load('salesReturns');
                }

                return $incomes;
            });
        } catch (RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'STOCK:')) {
                $parts = explode(':', $e->getMessage(), 3);
                $nama = $parts[1] ?? 'Produk';
                $sisa = (int) ($parts[2] ?? 0);

                return response()->json([
                    'success' => false,
                    'message' => "Stok {$nama} tidak mencukupi, sisa {$sisa}.",
                    'errors' => [
                        'items' => ["Stok {$nama} tidak mencukupi, sisa {$sisa}."],
                    ],
                ], 422);
            }

            throw $e;
        }

        $resources = collect($created)
            ->map(fn (Income $income) => IncomeResource::make($income)->resolve())
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'nomor_transaksi' => $resources[0]['nomor_transaksi'] ?? null,
            'resources' => $resources,
            'resource' => $resources[0] ?? null,
        ], 201);
    }

    public function update(IncomeRequest $request, Income $income): JsonResponse
    {
        $this->authorize('update', $income);

        if ($income->trashed()) {
            return response()->json(['success' => false, 'message' => 'Transaksi yang sudah dihapus tidak dapat diubah.'], 422);
        }

        if ($request->isMultiItem()) {
            return response()->json([
                'success' => false,
                'message' => 'Ubah transaksi hanya untuk satu baris item.',
            ], 422);
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
                        throw new RuntimeException('STOCK:'.(int) $product->stok);
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
                        'Penjualan '.($income->nomor_transaksi ?? '#'.$income->id),
                        $request->user()->id,
                        $mapped['tanggal_transaksi'],
                    );
                }

                return $income;
            });
        } catch (RuntimeException $e) {
            if (str_starts_with($e->getMessage(), 'STOCK:')) {
                $sisa = (int) substr($e->getMessage(), 6);

                return $this->stockInsufficientResponse($sisa);
            }

            throw $e;
        }

        return response()->json([
            'success' => true,
            'resource' => IncomeResource::make($income->fresh()->load('salesReturns'))->resolve(),
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

    private function stockInsufficientResponse(int $sisa): JsonResponse
    {
        return response()->json([
            'message' => 'Stok tidak mencukupi, sisa '.$sisa.'.',
            'errors' => [
                'jumlah' => ['Stok tidak mencukupi, sisa '.$sisa.'.'],
            ],
        ], 422);
    }
}
