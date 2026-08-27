<?php

namespace App\Http\Requests;

use App\Models\Debt;
use App\Services\CashBalanceService;
use App\Support\AppTimezone;
use App\Support\Format;
use Illuminate\Contracts\Validation\Validator;

class DebtPaymentRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'id_hutang' => ['required', 'integer', 'exists:debts,id'],
            'tanggal' => ['required', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.AppTimezone::todayDateString()],
            'nominal' => ['required', 'numeric', 'gt:0'],
            'sumber' => ['required', 'in:kas_usaha,dana_pribadi'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $debt = Debt::find($this->input('id_hutang'));
            $nominal = (float) $this->input('nominal');

            if ($debt && $nominal > $debt->remainingAmount()) {
                $validator->errors()->add('nominal', 'Pembayaran melebihi sisa hutang sebesar Rp '.number_format($debt->remainingAmount(), 0, ',', '.').'.');
            }

            if ($debt && (string) $this->input('tanggal') < $debt->tanggal?->toDateString()) {
                $validator->errors()->add('tanggal', 'Tanggal pembayaran tidak boleh sebelum tanggal pencatatan hutang.');
            }

            if ($this->input('sumber') === 'kas_usaha') {
                $cashBalance = app(CashBalanceService::class)->saldo((string) $this->input('tanggal'));

                if ($nominal > $cashBalance) {
                    $validator->errors()->add('nominal', 'Saldo kas tidak mencukupi. Saldo tersedia '.Format::rupiah($cashBalance).'.');
                }
            }
        });
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        return [
            'tanggal' => $this->input('tanggal'),
            'nominal' => (float) $this->input('nominal'),
            'sumber' => $this->input('sumber'),
            'keterangan' => $this->input('keterangan'),
        ];
    }
}
