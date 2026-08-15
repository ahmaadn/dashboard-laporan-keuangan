@extends('layouts.auth')

@section('title', 'Atur Ulang Kata Sandi')

@section('content')
<div class="ld-auth-grid">
    <section class="ld-auth-hero">
        <div class="ld-auth-hero__top">
            <img src="{{ asset('logo-t.png') }}" alt="{{ config('app.name', 'BM Leather') }}" class="ld-brand-logo ld-brand-logo--lg" width="44" height="44">
            <span class="ld-auth-wordmark">{{ config('app.name', 'BM Leather') }}</span>
        </div>
        <div>
            <span class="ld-auth-eyebrow d-block mb-3">Keamanan Akun</span>
            <h1 class="ld-auth-display">Buat sandi baru,<br>lanjut bekerja.</h1>
            <p class="ld-auth-subtitle mt-4">Gunakan kata sandi baru minimal delapan karakter dan pastikan konfirmasinya sama.</p>
        </div>
        <p class="ld-auth-eyebrow m-0">Jangan gunakan ulang kata sandi lama</p>
    </section>

    <section class="ld-auth-form-panel">
        <div class="ld-auth-card">
            <h2 class="ld-auth-card__title">Atur ulang kata sandi</h2>
            <p class="ld-auth-eyebrow mt-1 mb-4">Masukkan kredensial baru</p>

            <form method="POST" action="{{ route('password.update') }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                @if ($errors->any())
                    <div class="ld-auth-error mb-3">{{ $errors->first() }}</div>
                @endif

                <div class="mb-3">
                    <label class="ld-auth-label" for="email">Email</label>
                    <input id="email" type="email" name="email" class="ld-auth-input" value="{{ old('email', $email) }}" autocomplete="email" required>
                </div>
                <div class="mb-3">
                    <label class="ld-auth-label" for="password">Kata Sandi Baru</label>
                    <input id="password" type="password" name="password" class="ld-auth-input" autocomplete="new-password" required>
                </div>
                <div class="mb-4">
                    <label class="ld-auth-label" for="password_confirmation">Konfirmasi Kata Sandi</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="ld-auth-input" autocomplete="new-password" required>
                </div>

                <button type="submit" class="btn btn-pill-brand w-100">Simpan Kata Sandi Baru</button>
            </form>
        </div>
    </section>
</div>
@endsection
