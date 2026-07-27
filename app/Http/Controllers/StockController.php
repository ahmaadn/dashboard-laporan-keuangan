<?php

namespace App\Http\Controllers;

use App\Http\Requests\StockRestockRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\StockMovementResource;
use App\Http\Resources\UserResource;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request)
    {
        $products = Product::orderBy('nama')->get();

        $movements = StockMovement::with(['product', 'user'])
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('stocks.index', [
            'produk' => ProductResource::collection($products)->resolve(),
            'produkById' => collect(ProductResource::collection($products)->resolve())->keyBy('id')->all(),
            'mutasi' => StockMovementResource::collection($movements)->resolve(),
            'currentUser' => $request->user() ? UserResource::make($request->user())->resolve() : null,
        ]);
    }

    public function store(StockRestockRequest $request): JsonResponse
    {
        $data = $request->mapped();

        $movement = DB::transaction(function () use ($request, $data) {
            $product = Product::lockForUpdate()->findOrFail($data['product_id']);

            return $this->stock->catatMasuk(
                $product,
                $data['jumlah'],
                'restok',
                null,
                $data['keterangan'],
                $request->user()->id,
                $data['tanggal'],
            );
        });

        return response()->json([
            'success' => true,
            'resource' => StockMovementResource::make($movement)->resolve(),
            'stok' => (int) Product::find($data['product_id'])->stok,
        ], 201);
    }

    public function movements(Request $request): JsonResponse
    {
        $movements = StockMovement::with('user')
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'success' => true,
            'movements' => StockMovementResource::collection($movements)->resolve(),
        ]);
    }
}
