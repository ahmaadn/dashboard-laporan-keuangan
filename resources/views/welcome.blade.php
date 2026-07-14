<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'BM Leather') }}</title>

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="bg-light min-vh-100 d-flex align-items-center">
        <main class="container py-5">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4 p-md-5 text-center">
                            <h1 class="h3 mb-2 fw-semibold">{{ config('app.name', 'BM Leather') }}</h1>
                            <p class="text-muted mb-4">Aplikasi pengelolaan keuangan UMKM kerajinan kulit dengan dashboard interaktif.</p>

                            <div class="d-grid gap-2 d-sm-flex justify-content-center">
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="btn btn-primary px-4">Dashboard</a>
                                @else
                                    @if (Route::has('login'))
                                        <a href="{{ route('login') }}" class="btn btn-primary px-4">Masuk</a>
                                    @endif
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="btn btn-outline-primary px-4">Daftar</a>
                                    @endif
                                @endauth
                            </div>

                            <hr class="my-4">

                            <div class="d-flex flex-column flex-sm-row justify-content-center gap-2 text-muted small">
                                <a href="https://laravel.com/docs" target="_blank" class="link-secondary text-decoration-none">Dokumentasi</a>
                                <span class="d-none d-sm-inline">·</span>
                                <a href="https://laracasts.com" target="_blank" class="link-secondary text-decoration-none">Laracasts</a>
                                <span class="d-none d-sm-inline">·</span>
                                <a href="https://cloud.laravel.com" target="_blank" class="link-secondary text-decoration-none">Deploy</a>
                            </div>

                            <p class="text-muted small mt-4 mb-0">v{{ app()->version() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </body>
</html>
