<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'nama' => ['required', 'string', 'max:100'],
            'nama_pengguna' => ['required', 'string', 'max:50', Rule::unique('users', 'username')->ignore($userId)],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($userId)],
            'kata_sandi' => ['nullable', 'string', 'min:8'],
            'peran' => ['required', 'in:admin,pegawai'],
            'dapat_melihat_dashboard' => ['boolean'],
            'aktif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama.required' => 'Nama wajib diisi.',
            'nama_pengguna.required' => 'Nama pengguna wajib diisi.',
            'nama_pengguna.unique' => 'Nama pengguna sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'kata_sandi.min' => 'Kata sandi minimal 8 karakter.',
            'peran.required' => 'Peran wajib dipilih.',
        ];
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        $peran = $this->input('peran');
        $data = [
            'nama' => $this->string('nama')->trim(),
            'username' => $this->string('nama_pengguna')->trim(),
            'email' => $this->string('email')->trim(),
            'peran' => $peran,
            'dapat_melihat_dashboard' => $peran === 'admin' ? true : (bool) $this->input('dapat_melihat_dashboard'),
            'is_active' => (bool) $this->input('aktif', true),
        ];

        if ($this->filled('kata_sandi')) {
            $data['password'] = $this->input('kata_sandi');
        }

        return $data;
    }
}
