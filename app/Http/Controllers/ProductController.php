<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
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
        $data = $request->mapped();
        $data['created_by'] = $request->user()->id;

        $product = Product::create($data);

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
}
