@php
    $namaParts = explode(' ', $currentUser['nama']);
    $initials = strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($namaParts, 0, 2))));
    $roleLabel = $currentUser['peran'] === 'admin' ? 'Admin' : 'Pegawai';
    $dashNote = $currentUser['peran'] === 'pegawai'
        ? ($currentUser['dapat_melihat_dashboard'] ? ' · akses dashboard' : ' · tanpa dashboard')
        : '';
@endphp

<a class="ld-sidebar__brand ld-brand-mark" href="/dashboard">
    <span class="ld-brand-dot"></span>
    <span class="ld-brand-wordmark">LeatherDash</span>
</a>

<nav class="ld-sidebar__nav" aria-label="Navigasi utama">
    <div class="ld-sidebar__section-label">Menu</div>
    @foreach ($menus as $menu)
        <a href="{{ $menu['url'] }}" class="ld-nav-link" :class="isActive('{{ $menu['url'] }}') ? 'is-active' : ''" data-nav-link>
            <x-nav-icon :icon="$menu['icon']" />
            <span>{{ $menu['label'] }}</span>
        </a>
    @endforeach
</nav>

<div class="ld-sidebar__footer">
    <div class="d-flex align-items-center gap-2">
        <span class="ld-avatar">{{ $initials }}</span>
        <div class="overflow-hidden">
            <div class="small fw-medium text-truncate">{{ $currentUser['nama'] }}</div>
            <div class="ld-mono-micro">{{ $roleLabel }}{{ $dashNote }}</div>
        </div>
    </div>
</div>
