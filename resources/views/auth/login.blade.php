@extends('layouts.auth')

@section('title', 'Masuk')

@section('content')
<div x-data="login(@js($profiles))">
    <div class="ld-auth-grid">
        {{-- Marketing hero --}}
        <section class="ld-auth-hero">
            <div class="ld-auth-hero__top">
                <span class="ld-brand-dot"></span>
                <span class="ld-auth-wordmark">LeatherDash</span>
            </div>

            <div>
                <span class="ld-auth-eyebrow d-block mb-3">Keuangan UMKM Kerajinan Kulit</span>
                <h1 class="ld-auth-display">Catat transaksi,<br>pahami usaha.</h1>
                <p class="ld-auth-subtitle mt-4">Pencatatan pemasukan dan pengeluaran yang terstruktur, dashboard interaktif, dan laporan periode — semua dalam satu tempat.</p>
            </div>

            <p class="ld-auth-eyebrow m-0">Akun demo tersedia di bawah</p>
        </section>

        {{-- Login form panel --}}
        <section class="ld-auth-form-panel">
            <div class="ld-auth-card">
                <h2 class="ld-auth-card__title">Masuk ke akun</h2>
                <p class="ld-auth-eyebrow mt-1 mb-4">Gunakan akun demo atau pilih peran di bawah</p>

                <form method="POST" action="/login">
                    @csrf
                    @if ($errors->any())
                        <div class="ld-auth-error mb-3">{{ $errors->first() }}</div>
                    @endif

                    <div class="mb-3">
                        <label class="ld-auth-label" for="namaPengguna">Nama Pengguna / Email</label>
                        <input id="namaPengguna" type="text" name="login" class="ld-auth-input" x-model="namaPengguna" placeholder="mis. busari" autocomplete="username" value="{{ old('login') }}">
                    </div>

                    <div class="mb-4">
                        <label class="ld-auth-label" for="kataSandi">Kata Sandi</label>
                        <div class="position-relative">
                            <input id="kataSandi" name="password" :type="showPassword ? 'text' : 'password'" class="ld-auth-input pe-5" x-model="kataSandi" placeholder="••••••••" autocomplete="current-password">
                            <button type="button" @click="showPassword = !showPassword" class="ld-auth-toggle" :aria-label="showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                                <span x-text="showPassword ? 'Sembunyikan' : 'Lihat'"></span>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn-pill-brand w-100">Masuk</button>
                </form>

                <hr class="ld-auth-divider">

                <span class="ld-auth-eyebrow d-block mb-3">Isi otomatis sebagai</span>
                <div class="d-flex flex-column gap-2">
                    @foreach ($profiles as $profile)
                        <button type="button" class="ld-auth-quickfill" @click="quickFill(@js($profile))">
                            <span class="ld-brand-dot"></span>
                            <span class="d-flex flex-column">
                                <span class="ld-auth-quickfill__nama">{{ $profile['nama'] }}</span>
                                <span class="ld-auth-quickfill__peran">
                                    {{ $profile['peran'] === 'admin' ? 'Admin' : 'Pegawai' }}{{ $profile['peran'] === 'pegawai' ? ($profile['dapat_melihat_dashboard'] ? ' · akses dashboard' : ' · tanpa dashboard') : '' }}
                                </span>
                            </span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>
    </div>
</div>
@endsection
