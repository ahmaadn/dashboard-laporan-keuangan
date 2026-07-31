<?php

namespace App\Http\Requests;

use App\Enums\JenisTransaksi;
use App\Support\AppTimezone;
use Illuminate\Validation\Rule;

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
            'tanggal_transaksi' => ['required', 'date', 'before_or_equal:'.AppTimezone::todayDateString()],
            'jenis_transaksi' => ['required', Rule::in(['online', 'offline'])],
            'jumlah' => ['required', 'integer', 'min:1'],
            'harga_satuan' => ['required', 'numeric', 'min:0'],
            'harga_manual' => ['sometimes', 'boolean'],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_transaksi.required' => 'Tanggal transaksi wajib diisi.',
            'tanggal_transaksi.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'jenis_transaksi.required' => 'Jenis transaksi wajib dipilih.',
            'jenis_transaksi.in' => 'Jenis transaksi harus Online atau Offline.',
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
        $jenis = JenisTransaksi::from($this->input('jenis_transaksi', 'offline'));

        return [
            'product_id' => $this->filled('id_produk') ? (int) $this->input('id_produk') : null,
            'tanggal_transaksi' => $this->input('tanggal_transaksi'),
            'jenis_transaksi' => $jenis,
            'jumlah' => $jumlah,
            'harga_satuan' => $hargaSatuan,
            'total' => $jumlah * $hargaSatuan,
            'keterangan' => $this->input('keterangan'),
            'harga_manual' => $this->boolean('harga_manual'),
        ];
    }
}
