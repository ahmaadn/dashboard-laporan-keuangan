<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Http\Resources\ExpenseCategoryResource;
use App\Http\Resources\ExpenseResource;
use App\Http\Resources\UserResource;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::orderBy('created_at', 'desc')->get();
        $categories = ExpenseCategory::orderBy('nama')->get();
        $allUsers = User::withTrashed()->get();

        return view('expenses.index', [
            'pengeluaran' => ExpenseResource::collection($expenses)->resolve(),
            'kategoriPengeluaran' => ExpenseCategoryResource::collection($categories)->resolve(),
            'penggunaById' => collect(UserResource::collection($allUsers)->resolve())->keyBy('id')->all(),
            'currentUser' => $request->user() ? UserResource::make($request->user())->resolve() : null,
        ]);
    }

    public function store(ExpenseRequest $request): JsonResponse
    {
        $data = $request->mapped();
        $data['user_id'] = $request->user()->id;

        $expense = Expense::create($data);

        return response()->json([
            'success' => true,
            'resource' => ExpenseResource::make($expense->fresh())->resolve(),
        ], 201);
    }

    public function update(ExpenseRequest $request, Expense $expense): JsonResponse
    {
        $this->authorize('update', $expense);

        if ($expense->trashed()) {
            return response()->json(['success' => false, 'message' => 'Transaksi yang sudah dihapus tidak dapat diubah.'], 422);
        }

        $expense->update($request->mapped());

        return response()->json([
            'success' => true,
            'resource' => ExpenseResource::make($expense->fresh())->resolve(),
        ]);
    }

    public function destroy(Request $request, Expense $expense): JsonResponse
    {
        $this->authorize('delete', $expense);

        $expense->delete();

        return response()->json(['success' => true]);
    }
}
