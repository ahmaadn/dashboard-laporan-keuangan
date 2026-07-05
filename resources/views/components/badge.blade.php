@props([
    'variant' => 'neutral',
])

@php
    $class = [
        'neutral' => 'badge-neutral',
        'filled' => 'badge-filled',
        'brand' => 'badge-brand',
        'success' => 'badge-success-soft',
        'error' => 'badge-error-soft',
        'soft-delete' => 'badge-soft-delete',
    ][$variant] ?? 'badge-neutral';
@endphp

<span class="{{ $class }}" {{ $attributes->merge([]) }}>{{ $slot }}</span>
