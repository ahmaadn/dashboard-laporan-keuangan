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
        return [
            'nama' => ['required', 'string', 'max:150'],
            'id_kategori' => ['nullable', 'exists:product_categories,id'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'harga' => ['required', 'numeric', 'min:0'],
            'deskripsi' => ['nullable', 'string'],
            'aktif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama produk wajib diisi.',
            'nama.max' => 'Nama produk maksimal 150 karakter.',
            'sku.unique' => 'SKU sudah digunakan, gunakan nilai lain.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.min' => 'Harga tidak boleh negatif.',
        ];
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        return [
            'nama' => $this->string('nama')->trim(),
            'category_id' => $this->filled('id_kategori') ? (int) $this->input('id_kategori') : null,
            'sku' => $this->filled('sku') ? $this->string('sku')->trim() : null,
            'harga' => (float) $this->input('harga'),
            'deskripsi' => $this->input('deskripsi'),
            'is_active' => (bool) $this->input('aktif', true),
        ];
    }
}
