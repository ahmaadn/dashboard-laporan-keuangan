<header class="ld-topbar">
    <button type="button" class="ld-hamburger d-lg-none" @click="open()" aria-label="Buka menu navigasi">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <h1 class="ld-topbar__title">@yield('topbar-title', 'Dashboard')</h1>

    <div class="ms-auto" x-data="roleSwitcher(@js($currentUser), @js($profiles))">
        <div class="ld-role-switch">
            <button type="button" class="btn-app-ghost btn-sm" @click="toggle()" :aria-expanded="open">
                <span class="d-none d-sm-inline">Lihat sebagai</span>
                <span class="d-sm-none">Peran</span>
                <span class="ld-mono-micro ms-1" x-text="label"></span>
            </button>
            <div class="ld-role-menu" x-show="open" x-cloak @click.outside="close()" @keydown.escape.window="close()">
                <span class="ld-sidebar__section-label px-2 pt-1">Ganti peran (demo)</span>
                @foreach ($profiles as $profile)
                    <button type="button" class="ld-role-option" @click="choose(@js($profile))">
                        <span class="ld-role-option__nama">{{ $profile['nama'] }}</span>
                        <span class="ld-role-option__peran">
                            {{ $profile['peran'] === 'admin' ? 'Admin' : 'Pegawai' }}{{ $profile['peran'] === 'pegawai' ? ($profile['dapat_melihat_dashboard'] ? ' · akses dashboard' : ' · tanpa dashboard') : '' }}
                        </span>
                    </button>
                @endforeach
                <hr class="my-1">
                <button type="button" class="ld-role-option" @click="logout()">
                    <span class="ld-role-option__nama">Keluar</span>
                    <span class="ld-role-option__peran">Akhiri sesi demo</span>
                </button>
            </div>
        </div>
    </div>
</header>
