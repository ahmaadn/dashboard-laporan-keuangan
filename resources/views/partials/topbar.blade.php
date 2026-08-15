@php
    $namaParts = preg_split('/\s+/', trim($currentUser['nama']));
    $initials = strtoupper(implode('', array_map(fn ($part) => mb_substr($part, 0, 1), array_slice($namaParts, 0, 2))));
    $roleLabel = $currentUser['peran'] === 'admin' ? 'Admin' : 'Pegawai';
@endphp

<header class="ld-topbar">
    <button type="button" class="ld-hamburger d-lg-none" @click="open()" aria-label="Buka menu navigasi">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
            <line x1="3" y1="6" x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>

    <h1 class="ld-topbar__title">@yield('topbar-title', 'Dashboard')</h1>

    <div class="dropdown ms-auto ld-account-dropdown" @click.outside="closeAccountMenu()" @keydown.escape.window="closeAccountMenu()">
        <button class="ld-account-trigger dropdown-toggle" type="button" @click="toggleAccountMenu()" :aria-expanded="accountMenuOpen.toString()" aria-haspopup="true">
            <span class="ld-avatar">{{ $initials }}</span>
            <span class="ld-account-trigger__identity d-none d-sm-flex">
                <span class="ld-account-trigger__name">{{ $currentUser['nama'] }}</span>
                <span class="ld-mono-micro">{{ $roleLabel }}</span>
            </span>
        </button>

        <div class="dropdown-menu dropdown-menu-end ld-account-menu" x-show="accountMenuOpen" x-cloak x-transition.origin.top.right>
            <div class="ld-account-menu__header">
                <span class="ld-avatar ld-avatar--lg">{{ $initials }}</span>
                <div class="overflow-hidden">
                    <div class="ld-account-menu__name text-truncate">{{ $currentUser['nama'] }}</div>
                    <div class="ld-account-menu__email text-truncate">{{ $currentUser['email'] }}</div>
                    <span class="ld-account-menu__role">{{ $roleLabel }}</span>
                </div>
            </div>

            <div class="dropdown-divider"></div>

            <a class="dropdown-item ld-account-menu__item" href="{{ route('profile.edit') }}">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"/>
                    <path d="M4 21a8 8 0 0 1 16 0"/>
                </svg>
                <span>Profil</span>
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item ld-account-menu__item ld-account-menu__item--danger">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </div>
</header>
