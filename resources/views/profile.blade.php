@extends('layouts.app')

@section('title', 'Profil Saya')
@section('topbar-title', 'Profil Saya')

@section('content')
    <x-page-header eyebrow="Akun" title="Profil Saya" />

    @if (session('status') === 'profile-updated')
        <div class="alert-app mb-4">Profil berhasil diperbarui.</div>
    @elseif (session('status') === 'password-updated')
        <div class="alert-app mb-4">Kata sandi berhasil diperbarui.</div>
    @endif

    <div class="ld-grid-2 ld-profile-grid">
        <x-app-card eyebrow="Informasi akun" title="Data diri">
            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PATCH')

                <div class="d-flex align-items-center gap-3 mb-4">
                    @php
                        $namaParts = preg_split('/\s+/', trim($profile['nama']));
                        $initials = strtoupper(implode('', array_map(fn ($part) => mb_substr($part, 0, 1), array_slice($namaParts, 0, 2))));
                    @endphp
                    <span class="ld-avatar ld-avatar--lg">{{ $initials }}</span>
                    <div>
                        <div class="fw-medium">{{ $profile['nama'] }}</div>
                        <div class="ld-mono-caps">{{ $profile['peran'] === 'admin' ? 'Admin' : 'Pegawai' }}</div>
                    </div>
                </div>

                <div class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label" for="nama">Nama <span class="req">*</span></label>
                        <input id="nama" name="nama" type="text" class="form-control @error('nama') ld-input-invalid @enderror" value="{{ old('nama', $profile['nama']) }}" required autocomplete="name">
                        @error('nama')<div class="ld-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label" for="email">Email <span class="req">*</span></label>
                        <input id="email" name="email" type="email" class="form-control @error('email') ld-input-invalid @enderror" value="{{ old('email', $profile['email']) }}" required autocomplete="email">
                        @error('email')<div class="ld-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label" for="username">Nama Pengguna</label>
                        <input id="username" type="text" class="form-control" value="{{ $profile['nama_pengguna'] }}" disabled>
                        <div class="form-text">Nama pengguna dikelola oleh administrator.</div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <x-button variant="app" type="submit" icon="check">Simpan Profil</x-button>
                </div>
            </form>
        </x-app-card>

        <x-app-card eyebrow="Keamanan" title="Ganti kata sandi">
            <form method="POST" action="{{ route('profile.password.update') }}">
                @csrf
                @method('PATCH')

                <div class="d-flex flex-column gap-3">
                    <div>
                        <label class="form-label" for="current_password">Kata Sandi Saat Ini <span class="req">*</span></label>
                        <input id="current_password" name="current_password" type="password" class="form-control @error('current_password') ld-input-invalid @enderror" required autocomplete="current-password">
                        @error('current_password')<div class="ld-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label" for="password">Kata Sandi Baru <span class="req">*</span></label>
                        <input id="password" name="password" type="password" class="form-control @error('password') ld-input-invalid @enderror" required autocomplete="new-password">
                        @error('password')<div class="ld-field-error">{{ $message }}</div>@enderror
                    </div>

                    <div>
                        <label class="form-label" for="password_confirmation">Konfirmasi Kata Sandi Baru <span class="req">*</span></label>
                        <input id="password_confirmation" name="password_confirmation" type="password" class="form-control" required autocomplete="new-password">
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <x-button variant="app" type="submit" icon="check">Simpan Kata Sandi</x-button>
                </div>
            </form>
        </x-app-card>
    </div>
@endsection
