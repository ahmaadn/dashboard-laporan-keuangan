<header class="ld-topbar">
    <button type="button" class="ld-hamburger d-lg-none" @click="open()" aria-label="Buka menu navigasi">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <h1 class="ld-topbar__title">@yield('topbar-title', 'Dashboard')</h1>

    <div class="ms-auto d-flex align-items-center gap-2">
        <span class="ld-mono-caps d-none d-sm-inline">{{ $currentUser['peran'] === 'admin' ? 'Admin' : 'Pegawai' }}</span>
        <form method="POST" action="/logout">
            @csrf
            <button type="submit" class="btn-app-ghost btn-sm" aria-label="Keluar">Keluar</button>
        </form>
    </div>
</header>
