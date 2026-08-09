@props([
    'variant' => 'app',
    'size' => null,
    'href' => null,
    'type' => 'button',
    'dismiss' => null,
    'icon' => null,
    'iconOnly' => false,
    'loading' => false,
])

@php
    $variantClass = [
        'app' => 'btn-app',
        'brand' => 'btn-brand',
        'secondary' => 'btn-app-secondary',
        'ghost' => 'btn-app-ghost',
        'danger' => 'btn-danger',
        'success' => 'btn-app-success',
        'pill-primary' => 'btn-pill-primary',
        'pill-brand' => 'btn-pill-brand',
        'pill-secondary' => 'btn-pill-secondary',
    ][$variant] ?? 'btn-app';

    $sizeClass = match ($size) {
        'sm' => 'btn-sm',
        'lg' => 'btn-lg',
        default => '',
    };

    $baseClass = trim(implode(' ', array_filter([
        'btn',
        'btn-icon-label',
        $variantClass,
        $sizeClass,
        $iconOnly ? 'btn-icon-only' : '',
        $loading ? 'is-loading' : '',
    ])));

    // Stroke icon set (24x24 grid) — konsisten dengan ikon navigasi sidebar.
    $icons = [
        'plus' => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        'trash' => '<polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>',
        'check' => '<polyline points="20 6 9 17 4 12"/>',
        'close' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>',
        'print' => '<polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>',
        'receipt' => '<path d="M4 2v20l2.5-1.5L9 22l2.5-1.5L14 22l2.5-1.5L19 22V2l-2.5 1.5L14 2l-2.5 1.5L9 2 6.5 3.5Z"/><line x1="8" y1="8" x2="15" y2="8"/><line x1="8" y1="12" x2="15" y2="12"/>',
        'filter' => '<polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>',
        'undo' => '<polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>',
        'box' => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
    ];
    $iconPath = $icon ? ($icons[$icon] ?? null) : null;

    $attrs = $attributes->merge(['class' => $baseClass]);
    if ($dismiss) {
        $attrs = $attrs->merge(['data-bs-dismiss' => $dismiss]);
    }
@endphp

@php
    $inner = ($iconPath
        ? '<svg class="btn-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$iconPath.'</svg>'
        : '');
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attrs }}>
        {!! $inner !!}
        @unless($iconOnly)<span class="btn-label">{{ $slot }}</span>@endunless
    </a>
@else
    <button type="{{ $type }}" {{ $attrs }}>
        {!! $inner !!}
        @unless($iconOnly)<span class="btn-label">{{ $slot }}</span>@endunless
    </button>
@endif
