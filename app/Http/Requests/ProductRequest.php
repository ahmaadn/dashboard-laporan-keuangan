<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class ProductRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        return [
            'nama' => ['required', 'string', 'max:150', Rule::unique('products', 'nama')->ignore($this->route('product'))],
            'id_kategori' => ['nullable', 'exists:product_categories,id'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'harga' => ['required', 'numeric', 'min:0'],
            'harga_modal' => ['nullable', 'numeric', 'min:0'],
            'harga_grosir' => ['nullable', 'numeric', 'min:0'],
            'min_qty_grosir' => ['nullable', 'integer', 'min:1'],
            'stok' => [$isCreate ? 'nullable' : 'prohibited', 'integer', 'min:0'],
            'stok_minimum' => ['nullable', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama produk wajib diisi.',
            'nama.max' => 'Nama produk maksimal 150 karakter.',
            'nama.unique' => 'Nama produk sudah digunakan, gunakan nama lain.',
            'sku.unique' => 'SKU sudah digunakan, gunakan nilai lain.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.min' => 'Harga tidak boleh negatif.',
            'harga_modal.min' => 'Harga modal tidak boleh negatif.',
            'harga_grosir.min' => 'Harga grosir tidak boleh negatif.',
            'min_qty_grosir.min' => 'Minimal qty grosir minimal 1.',
            'stok.min' => 'Stok tidak boleh negatif.',
            'stok.prohibited' => 'Stok tidak diubah lewat form produk; gunakan restok atau koreksi.',
            'stok_minimum.min' => 'Stok minimum tidak boleh negatif.',
        ];
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        $data = [
            'nama' => $this->string('nama')->trim(),
            'category_id' => $this->filled('id_kategori') ? (int) $this->input('id_kategori') : null,
            'sku' => $this->filled('sku') ? $this->string('sku')->trim() : null,
            'harga' => (float) $this->input('harga'),
            'harga_modal' => (float) $this->input('harga_modal', 0),
            'harga_grosir' => $this->filled('harga_grosir') ? (float) $this->input('harga_grosir') : null,
            'min_qty_grosir' => (int) $this->input('min_qty_grosir', 3),
            'stok_minimum' => (int) $this->input('stok_minimum', 5),
            'deskripsi' => $this->input('deskripsi'),
            'is_active' => (bool) $this->input('aktif', true),
        ];

        if ($this->isMethod('POST') && $this->has('stok')) {
            $data['stok'] = (int) $this->input('stok', 0);
        }

        return $data;
    }
}
