@extends('layouts.auth')

@section('title', 'Lupa Kata Sandi')

@section('content')
<div class="ld-auth-grid">
    <section class="ld-auth-hero">
        <div class="ld-auth-hero__top">
            <img src="{{ asset('logo-t.png') }}" alt="{{ config('app.name', 'BM Leather') }}" class="ld-brand-logo ld-brand-logo--lg" width="44" height="44">
            <span class="ld-auth-wordmark">{{ config('app.name', 'BM Leather') }}</span>
        </div>
        <div>
            <span class="ld-auth-eyebrow d-block mb-3">Pemulihan Akun</span>
            <h1 class="ld-auth-display">Akses akun,<br>kembali aman.</h1>
            <p class="ld-auth-subtitle mt-4">Masukkan email akun Anda. Tautan untuk membuat kata sandi baru akan dikirim melalui email.</p>
        </div>
        <p class="ld-auth-eyebrow m-0">Tautan berlaku selama 60 menit</p>
    </section>

    <section class="ld-auth-form-panel">
        <div class="ld-auth-card">
            <h2 class="ld-auth-card__title">Lupa kata sandi</h2>
            <p class="ld-auth-eyebrow mt-1 mb-4">Kirim tautan pemulihan akun</p>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                @if (session('status'))
                    <div class="ld-auth-success mb-3">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="ld-auth-error mb-3">{{ $errors->first() }}</div>
                @endif

                <div class="mb-4">
                    <label class="ld-auth-label" for="email">Email</label>
                    <input id="email" type="email" name="email" class="ld-auth-input" value="{{ old('email') }}" autocomplete="email" autofocus required>
                </div>

                <button type="submit" class="btn btn-pill-brand w-100">Kirim Tautan Reset</button>
            </form>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="ld-auth-link">Kembali ke halaman masuk</a>
            </div>
        </div>
    </section>
</div>
@endsection
