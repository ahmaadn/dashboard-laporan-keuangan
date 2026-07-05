@props([
    'variant' => 'app',
    'size' => null,
    'href' => null,
    'type' => 'button',
    'dismiss' => null,
])

@php
    $variantClass = [
        'app' => 'btn-app',
        'brand' => 'btn-brand',
        'secondary' => 'btn-app-secondary',
        'ghost' => 'btn-app-ghost',
        'danger' => 'btn-danger',
        'pill-primary' => 'btn-pill-primary',
        'pill-brand' => 'btn-pill-brand',
        'pill-secondary' => 'btn-pill-secondary',
    ][$variant] ?? 'btn-app';

    $sizeClass = $size === 'sm' ? 'btn-sm' : '';
    $baseClass = trim('btn '.$variantClass.' '.$sizeClass);

    $attrs = $attributes->merge(['class' => $baseClass]);
    if ($dismiss) {
        $attrs = $attrs->merge(['data-bs-dismiss' => $dismiss]);
    }
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attrs }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attrs }}>{{ $slot }}</button>
@endif
