<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(private readonly StockService $stock) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isAdmin();

        $query = Product::query();
        if ($isAdmin) {
            $query->withTrashed();
        }
        $products = $query->orderBy('created_at', 'desc')->get();

        $categories = ProductCategory::orderBy('nama')->get();

        return view('products.index', [
            'produk' => ProductResource::collection($products)->resolve(),
            'kategoriProduk' => ProductCategoryResource::collection($categories)->resolve(),
            'kategoriProdukById' => collect(ProductCategoryResource::collection($categories)->resolve())->keyBy('id')->all(),
            'currentUser' => $request->user() ? UserResource::make($request->user())->resolve() : null,
        ]);
    }

    public function store(ProductRequest $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $data = $request->mapped();
        $data['created_by'] = $request->user()->id;
        $stokAwal = (int) ($data['stok'] ?? 0);
        $data['stok'] = 0;

        $product = DB::transaction(function () use ($data, $stokAwal, $request) {
            $product = Product::create($data);

            if ($stokAwal > 0) {
                $this->stock->catatMasuk(
                    $product,
                    $stokAwal,
                    'restok',
                    null,
                    'Stok awal',
                    $request->user()->id,
                );
            }

            return $product;
        });

        return response()->json([
            'success' => true,
            'resource' => ProductResource::make($product->fresh())->resolve(),
        ], 201);
    }

    public function update(ProductRequest $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        if ($product->trashed()) {
            return response()->json(['success' => false, 'message' => 'Produk yang sudah dihapus tidak dapat diubah.'], 422);
        }

        $product->update($request->mapped());

        return response()->json([
            'success' => true,
            'resource' => ProductResource::make($product->fresh())->resolve(),
        ]);
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->authorize('delete', $product);

        $product->delete();

        return response()->json(['success' => true]);
    }

    public function adjustStock(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        if ($product->trashed()) {
            return response()->json(['success' => false, 'message' => 'Produk yang sudah dihapus tidak dapat diubah stoknya.'], 422);
        }

        $validated = $request->validate([
            'aksi' => ['required', Rule::in(['restok', 'koreksi'])],
            'jumlah' => ['required_if:aksi,restok', 'nullable', 'integer', 'min:1'],
            'stok_baru' => ['required_if:aksi,koreksi', 'nullable', 'integer', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ], [
            'aksi.required' => 'Aksi stok wajib dipilih.',
            'jumlah.required_if' => 'Jumlah restok wajib diisi.',
            'jumlah.min' => 'Jumlah restok minimal 1.',
            'stok_baru.required_if' => 'Stok baru wajib diisi.',
            'stok_baru.min' => 'Stok baru tidak boleh negatif.',
        ]);

        DB::transaction(function () use ($request, $product, $validated) {
            if ($validated['aksi'] === 'restok') {
                $this->stock->catatMasuk(
                    $product,
                    (int) $validated['jumlah'],
                    'restok',
                    null,
                    $validated['keterangan'] ?? null,
                    $request->user()->id,
                );
            } else {
                $this->stock->koreksi(
                    $product,
                    (int) $validated['stok_baru'],
                    $validated['keterangan'] ?? null,
                    $request->user()->id,
                );
            }
        });

        return response()->json([
            'success' => true,
            'resource' => ProductResource::make($product->fresh())->resolve(),
        ]);
    }

    public function movements(Request $request, Product $product): JsonResponse
    {
        $movements = StockMovement::query()
            ->where('product_id', $product->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (StockMovement $m) => [
                'id' => $m->id,
                'tanggal' => $m->tanggal?->format('Y-m-d'),
                'jenis' => $m->jenis,
                'jumlah' => $m->jumlah,
                'sumber' => $m->sumber,
                'ref_id' => $m->ref_id,
                'keterangan' => $m->keterangan,
                'pencatat' => $m->user?->nama,
                'dibuat_pada' => $m->created_at?->format('Y-m-d H:i:s'),
            ]);

        return response()->json([
            'success' => true,
            'movements' => $movements,
            'stok' => (int) $product->stok,
        ]);
    }
}
