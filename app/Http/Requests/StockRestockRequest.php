<?php

namespace App\Http\Requests;

use App\Support\AppTimezone;
use Illuminate\Validation\Rule;

class StockRestockRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_produk' => ['required', 'integer', Rule::exists('products', 'id')],
            'tanggal' => ['required', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.AppTimezone::todayDateString()],
            'jumlah' => ['required', 'integer', 'min:1'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_produk.required' => 'Produk wajib dipilih.',
            'id_produk.exists' => 'Produk tidak ditemukan.',
            'tanggal.required' => 'Tanggal produksi/restok wajib diisi.',
            'tanggal.after_or_equal' => 'Tanggal produksi/restok tidak boleh sebelum '.AppTimezone::TANGGAL_MULAI_USAHA.' (usaha mulai beroperasi 2018).',
            'tanggal.before_or_equal' => 'Tanggal produksi/restok tidak boleh melebihi hari ini.',
            'jumlah.required' => 'Jumlah produksi/restok wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
        ];
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        return [
            'product_id' => (int) $this->input('id_produk'),
            'tanggal' => $this->input('tanggal'),
            'jumlah' => (int) $this->input('jumlah'),
            'keterangan' => $this->input('keterangan') ?: 'Produksi/Restok',
        ];
    }
}
