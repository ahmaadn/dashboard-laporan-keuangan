<?php

namespace App\Http\Requests;

class ExpenseRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_kategori' => ['required', 'exists:expense_categories,id'],
            'tanggal_transaksi' => ['required', 'date', 'before_or_equal:today'],
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
            'tanggal_transaksi.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'nominal.required' => 'Nominal wajib diisi.',
            'nominal.min' => 'Nominal harus lebih besar dari 0.',
        ];
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
}
