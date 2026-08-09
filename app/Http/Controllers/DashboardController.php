<?php

namespace App\Http\Controllers;

use App\Http\Requests\PeriodComparisonRequest;
use App\Http\Requests\PeriodFilterRequest;
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
use App\Support\AppTimezone;
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
            'tanggalHariIni' => AppTimezone::todayDateString(),
            'maxRentangHari' => PeriodResolver::MAX_RANGE_DAYS,
        ]);
    }

    public function data(PeriodFilterRequest $request): JsonResponse
    {
        return response()->json(
            $this->dashboardService->data(
                $request->periodKey(),
                $request->startDate(),
                $request->endDate(),
            ),
        );
    }

    public function compare(PeriodComparisonRequest $request): JsonResponse
    {
        $validated = $request->validated();

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
