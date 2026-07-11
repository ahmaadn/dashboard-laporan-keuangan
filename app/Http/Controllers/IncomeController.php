<?php

namespace App\Http\Controllers;

use App\Http\Requests\IncomeRequest;
use App\Http\Resources\IncomeResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\UserResource;
use App\Models\Income;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IncomeController extends Controller
{
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
        $data = $request->mapped();
        $data['user_id'] = $request->user()->id;

        $income = Income::create($data);

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

        $income->update($request->mapped());

        return response()->json([
            'success' => true,
            'resource' => IncomeResource::make($income->fresh())->resolve(),
        ]);
    }

    public function destroy(Request $request, Income $income): JsonResponse
    {
        $this->authorize('delete', $income);

        $income->delete();

        return response()->json(['success' => true]);
    }
}
