<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreUserRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:100'],
            'nama_pengguna' => ['required', 'string', 'max:50', Rule::unique('users', 'username')],
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')],
            'kata_sandi' => ['required', 'string', 'min:8'],
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
            'kata_sandi.required' => 'Kata sandi wajib diisi.',
            'kata_sandi.min' => 'Kata sandi minimal 8 karakter.',
            'peran.required' => 'Peran wajib dipilih.',
        ];
    }

    /** @return array<string, mixed> */
    public function mapped(): array
    {
        $peran = $this->input('peran');

        return [
            'nama' => $this->string('nama')->trim(),
            'username' => $this->string('nama_pengguna')->trim(),
            'email' => $this->string('email')->trim(),
            'password' => $this->input('kata_sandi'),
            'peran' => $peran,
            'dapat_melihat_dashboard' => $peran === 'admin' ? true : (bool) $this->input('dapat_melihat_dashboard'),
            'is_active' => (bool) $this->input('aktif', true),
        ];
    }
}
