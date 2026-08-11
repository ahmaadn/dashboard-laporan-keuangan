<?php

namespace App\Http\Requests;

use App\Models\Expense;
use App\Services\CashBalanceService;
use App\Support\AppTimezone;
use App\Support\Format;
use Illuminate\Contracts\Validation\Validator;

class ExpenseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        $expense = $this->existingExpense();

        // Otorisasi didahulukan agar akses terlarang menghasilkan 403, bukan 422
        // dari aturan saldo kas di bawah.
        return $expense === null || $this->user()?->can('update', $expense) === true;
    }

    public function rules(): array
    {
        return [
            'id_kategori' => ['required', 'exists:expense_categories,id'],
            'tanggal_transaksi' => ['required', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.AppTimezone::todayDateString()],
            'nominal' => ['required', 'numeric', 'min:0.01'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_kategori.required' => 'Kategori wajib dipilih.',
            'id_kategori.exists' => 'Kategori tidak valid.',
            'tanggal_transaksi.required' => 'Tanggal transaksi wajib diisi.',
            'tanggal_transaksi.after_or_equal' => 'Tanggal transaksi tidak boleh sebelum '.AppTimezone::TANGGAL_MULAI_USAHA.' (usaha mulai beroperasi 2018).',
            'tanggal_transaksi.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'nominal.required' => 'Nominal wajib diisi.',
            'nominal.min' => 'Nominal harus lebih besar dari 0.',
        ];
    }

    /**
     * Pengeluaran tidak boleh melebihi saldo kas yang tersedia (modal + penjualan
     * − retur − pengeluaran lain). Fitur modal jadi punya fungsi nyata: tanpa
     * saldo, pengeluaran ditolak.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $nominal = (float) $this->input('nominal');
            $tanggal = (string) $this->input('tanggal_transaksi');
            $saldo = app(CashBalanceService::class)->saldoTersedia($tanggal, $this->existingExpense());

            if ($nominal > $saldo) {
                $validator->errors()->add(
                    'nominal',
                    'Saldo kas tidak mencukupi. Saldo tersedia '.Format::rupiah($saldo).'.',
                );
            }
        });
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        return [
            'category_id' => (int) $this->input('id_kategori'),
            'tanggal_transaksi' => $this->input('tanggal_transaksi'),
            'nominal' => (float) $this->input('nominal'),
            'keterangan' => $this->input('keterangan'),
        ];
    }

    private function existingExpense(): ?Expense
    {
        $expense = $this->route('expense');

        return $expense instanceof Expense ? $expense : null;
    }
}
