<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExpenseCategoryResource;
use App\Http\Resources\ProductCategoryResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\PeriodResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(Request $request)
    {
        $produk = Product::withTrashed()->get();
        $kategoriProduk = ProductCategory::all();
        $kategoriPengeluaran = ExpenseCategory::all();
        $pengguna = User::withTrashed()->get();

        return view('dashboard.index', [
            'produk' => ProductResource::collection($produk)->resolve(),
            'kategoriProduk' => ProductCategoryResource::collection($kategoriProduk)->resolve(),
            'kategoriPengeluaran' => ExpenseCategoryResource::collection($kategoriPengeluaran)->resolve(),
            'pengguna' => UserResource::collection($pengguna)->resolve(),
            'currentUser' => $request->user() ? UserResource::make($request->user())->resolve() : null,
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['string', 'in:'.implode(',', array_keys(PeriodResolver::OPTIONS))],
            'start' => ['nullable', 'date'],
            'end' => ['nullable', 'date'],
        ]);

        return response()->json(
            $this->dashboardService->data(
                $validated['period'] ?? 'bulan_ini',
                $validated['start'] ?? null,
                $validated['end'] ?? null,
            ),
        );
    }

    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'a' => ['required', 'string'],
            'b' => ['required', 'string'],
            'a_start' => ['nullable', 'date'],
            'a_end' => ['nullable', 'date'],
            'b_start' => ['nullable', 'date'],
            'b_end' => ['nullable', 'date'],
        ]);

        return response()->json(
            $this->dashboardService->compare(
                $validated['a'],
                $validated['b'],
                $validated['a_start'] ?? null,
                $validated['a_end'] ?? null,
                $validated['b_start'] ?? null,
                $validated['b_end'] ?? null,
            ),
        );
    }
}
