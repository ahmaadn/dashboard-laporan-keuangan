@props(['icon' => 'dashboard'])

@php
    $paths = [
        'dashboard' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'products' => '<path d="M20.5 7.27 12 3 3.5 7.27 12 11.54z"/><path d="M3.5 7.27v9.46L12 21l8.5-4.27V7.27"/><path d="M12 11.54v9.46"/>',
        'income' => '<path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/>',
        'expenses' => '<path d="M3 7l6 6 4-4 8 8"/><path d="M14 17h7v-7"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'reports' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h2v5H8z"/><path d="M14 11h2v7h-2z"/>',
    ];
@endphp

<svg class="ld-nav-icon {{ $attributes->get('class', '') }}" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    {!! $paths[$icon] ?? $paths['dashboard'] !!}
</svg>
