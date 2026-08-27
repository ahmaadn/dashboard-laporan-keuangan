<?php

namespace App\Http\Controllers;

use App\Http\Requests\CapitalInjectionRequest;
use App\Http\Requests\DebtPaymentRequest;
use App\Http\Resources\CapitalInjectionResource;
use App\Http\Resources\DebtResource;
use App\Http\Resources\UserResource;
use App\Models\CapitalInjection;
use App\Models\Debt;
use App\Models\User;
use App\Services\DebtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CapitalController extends Controller
{
    public function __construct(private readonly DebtService $debts) {}

    public function index(Request $request)
    {
        $capital = CapitalInjection::with('debtForDisplay.payments')
            ->whereNull('debt_id')
            ->orderByDesc('tanggal')
            ->get();
        $debts = Debt::with('payments')->orderByDesc('tanggal')->get();
        $allUsers = User::withTrashed()->get();

        return view('capital.index', [
            'modal' => CapitalInjectionResource::collection($capital)->resolve(),
            'penggunaById' => collect(UserResource::collection($allUsers)->resolve())->keyBy('id')->all(),
            'currentUser' => $request->user() ? UserResource::make($request->user())->resolve() : null,
            'totalDebt' => $this->debts->totalOutstanding(),
            'hutang' => DebtResource::collection($debts)->resolve(),
        ]);
    }

    public function store(CapitalInjectionRequest $request): JsonResponse
    {
        $data = $request->mapped();
        $data['user_id'] = $request->user()->id;

        $result = $this->debts->recordFromCapital($data, $request->user()->id);
        $entry = $result['entry']->load('debtForDisplay.payments');

        return response()->json([
            'success' => true,
            'resource' => CapitalInjectionResource::make($entry)->resolve(),
        ], 201);
    }

    public function pay(DebtPaymentRequest $request): JsonResponse
    {
        $debt = Debt::findOrFail($request->validated()['id_hutang']);
        $payment = $this->debts->pay($debt, $request->mapped(), $request->user()->id);

        return response()->json([
            'success' => true,
            'resource' => CapitalInjectionResource::make(
                $payment->load('debt.capitalInjection.debtForDisplay.payments')->debt->capitalInjection,
            )->resolve(),
            'total_hutang' => $this->debts->totalOutstanding(),
        ], 201);
    }

    public function destroy(CapitalInjection $capitalInjection): JsonResponse
    {
        $this->debts->deleteWithCapital($capitalInjection->load('debtForDisplay.payments'));

        return response()->json(['success' => true]);
    }
}
