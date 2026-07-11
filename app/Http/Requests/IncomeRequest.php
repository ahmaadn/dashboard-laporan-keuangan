<?php

namespace App\Http\Requests;

class IncomeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_produk' => ['nullable', 'exists:products,id'],
            'tanggal_transaksi' => ['required', 'date', 'before_or_equal:today'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_transaksi.required' => 'Tanggal transaksi wajib diisi.',
            'tanggal_transaksi.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'harga_satuan.required' => 'Harga satuan wajib diisi.',
            'harga_satuan.min' => 'Harga satuan tidak boleh negatif.',
        ];
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        $jumlah = (int) $this->input('jumlah');
        $hargaSatuan = (float) $this->input('harga_satuan');

        return [
            'product_id' => $this->filled('id_produk') ? (int) $this->input('id_produk') : null,
            'tanggal_transaksi' => $this->input('tanggal_transaksi'),
            'jumlah' => $jumlah,
            'harga_satuan' => $hargaSatuan,
            'total' => $jumlah * $hargaSatuan,
            'keterangan' => $this->input('keterangan'),
        ];
    }
}
