<?php

namespace App\Services;

use App\Models\CapitalInjection;
use App\Models\Debt;
use App\Models\DebtPayment;
use Illuminate\Support\Facades\DB;

final class DebtService
{
    /** @return array{entry: CapitalInjection, debt: ?Debt} */
    public function recordFromCapital(array $data, int $userId): array
    {
        return DB::transaction(function () use ($data, $userId): array {
            $nominal = (float) $data['nominal'];

            if ($nominal >= 0) {
                return [
                    'entry' => CapitalInjection::create([...$data, 'user_id' => $userId]),
                    'debt' => null,
                ];
            }

            $capital = CapitalInjection::create([
                ...$data,
                'user_id' => $userId,
                'nominal' => abs($nominal),
            ]);

            $debt = Debt::create([
                'user_id' => $userId,
                'capital_injection_id' => $capital->id,
                'tanggal' => $data['tanggal'],
                'nominal' => abs($nominal),
                'keterangan' => $data['keterangan'] ?? null,
            ]);

            return ['entry' => $capital, 'debt' => $debt];
        });
    }

    public function pay(Debt $debt, array $data, int $userId): DebtPayment
    {
        return DB::transaction(function () use ($debt, $data, $userId): DebtPayment {
            $lockedDebt = Debt::query()->lockForUpdate()->findOrFail($debt->id);

            if ((float) $data['nominal'] > $lockedDebt->remainingAmount()) {
                abort(422, 'Pembayaran melebihi sisa hutang.');
            }

            return $lockedDebt->payments()->create([...$data, 'user_id' => $userId]);
        });
    }

    public function totalOutstanding(): int
    {
        return (int) Debt::query()->with('payments')->get()->sum(fn (Debt $debt): float => $debt->remainingAmount());
    }

    public function deleteWithCapital(CapitalInjection $capital): void
    {
        DB::transaction(function () use ($capital): void {
            if ($capital->debtForDisplay) {
                $debt = $capital->debtForDisplay;
                $debtMemo = CapitalInjection::query()->where('debt_id', $debt->id)->first();

                $debt->payments()->delete();
                $debtMemo?->delete();
                $debt->delete();
            }

            $capital->delete();
        });
    }
}
