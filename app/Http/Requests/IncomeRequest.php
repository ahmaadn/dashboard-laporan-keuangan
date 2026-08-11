<?php

namespace App\Http\Requests;

use App\Enums\JenisTransaksi;
use App\Support\AppTimezone;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class IncomeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function isMultiItem(): bool
    {
        return $this->has('items') && is_array($this->input('items'));
    }

    public function rules(): array
    {
        if ($this->isMultiItem()) {
            return [
                'tanggal_transaksi' => ['required', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.AppTimezone::todayDateString()],
                'jenis_transaksi' => ['required', Rule::in(['online', 'offline'])],
                'keterangan' => ['nullable', 'string', 'max:255'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.id_produk' => ['nullable', 'exists:products,id'],
                'items.*.jumlah' => ['required', 'integer', 'min:1'],
                'items.*.harga_satuan' => ['required', 'numeric', 'min:0'],
                'items.*.harga_manual' => ['sometimes', 'boolean'],
            ];
        }

        return [
            'id_produk' => ['nullable', 'exists:products,id'],
            'tanggal_transaksi' => ['required', 'date', 'after_or_equal:'.AppTimezone::TANGGAL_MULAI_USAHA, 'before_or_equal:'.AppTimezone::todayDateString()],
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
            'tanggal_transaksi.after_or_equal' => 'Tanggal transaksi tidak boleh sebelum '.AppTimezone::TANGGAL_MULAI_USAHA.' (usaha mulai beroperasi 2018).',
            'tanggal_transaksi.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'jenis_transaksi.required' => 'Jenis transaksi wajib dipilih.',
            'jenis_transaksi.in' => 'Jenis transaksi harus Online atau Offline.',
            'jumlah.required' => 'Jumlah wajib diisi.',
            'jumlah.min' => 'Jumlah minimal 1.',
            'harga_satuan.required' => 'Harga satuan wajib diisi.',
            'harga_satuan.min' => 'Harga satuan tidak boleh negatif.',
            'items.required' => 'Minimal satu produk harus ditambahkan.',
            'items.min' => 'Minimal satu produk harus ditambahkan.',
            'items.*.jumlah.required' => 'Jumlah item wajib diisi.',
            'items.*.jumlah.min' => 'Jumlah item minimal 1.',
            'items.*.harga_satuan.required' => 'Harga satuan item wajib diisi.',
            'items.*.harga_satuan.min' => 'Harga satuan item tidak boleh negatif.',
            'items.*.id_produk.exists' => 'Produk tidak ditemukan.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->isMultiItem()) {
                return;
            }

            $items = $this->input('items', []);
            $hasAnyLine = false;

            foreach ($items as $index => $item) {
                $hasProduct = filled($item['id_produk'] ?? null);
                $hasManual = filter_var($item['harga_manual'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $harga = (float) ($item['harga_satuan'] ?? 0);

                if ($hasProduct || $harga > 0 || $hasManual) {
                    $hasAnyLine = true;
                }

                if (! $hasProduct && ! $hasManual && $harga <= 0) {
                    $validator->errors()->add(
                        "items.{$index}.id_produk",
                        'Pilih produk atau aktifkan harga manual untuk item tanpa produk.'
                    );
                }
            }

            if (! $hasAnyLine && count($items) === 0) {
                $validator->errors()->add('items', 'Minimal satu produk harus ditambahkan.');
            }
        });
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        $jenis = JenisTransaksi::from($this->input('jenis_transaksi', 'offline'));

        if ($this->isMultiItem()) {
            $items = [];
            foreach ($this->input('items', []) as $item) {
                $jumlah = (int) ($item['jumlah'] ?? 0);
                $hargaSatuan = (float) ($item['harga_satuan'] ?? 0);
                $items[] = [
                    'product_id' => filled($item['id_produk'] ?? null) ? (int) $item['id_produk'] : null,
                    'jumlah' => $jumlah,
                    'harga_satuan' => $hargaSatuan,
                    'total' => $jumlah * $hargaSatuan,
                    'harga_manual' => filter_var($item['harga_manual'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];
            }

            return [
                'tanggal_transaksi' => $this->input('tanggal_transaksi'),
                'jenis_transaksi' => $jenis,
                'keterangan' => $this->input('keterangan'),
                'items' => $items,
            ];
        }

        $jumlah = (int) $this->input('jumlah');
        $hargaSatuan = (float) $this->input('harga_satuan');

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
