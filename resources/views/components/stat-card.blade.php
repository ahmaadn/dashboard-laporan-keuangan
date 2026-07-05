@props([
    'label' => null,
    'value' => null,
    'variant' => 'neutral',
    'hint' => null,
    'offcanvas' => null,
    'eyebrow' => null,
])

@php
    $class = 'stat-card stat-card--'.$variant;
@endphp

@if($offcanvas)
    <button type="button" class="{{ $class }}" data-bs-toggle="offcanvas" data-bs-target="#{{ $offcanvas }}">
        @if($eyebrow)<span class="stat-card__label">{{ $eyebrow }}</span>@endif
        @if($label)<span class="stat-card__label">{{ $label }}</span>@endif
        <span class="stat-card__value tnum">@if($slot->isNotEmpty()){{ $slot }}@else{{ $value }}@endif</span>
        @if($hint)<span class="stat-card__hint">{{ $hint }}</span>@endif
    </button>
@else
    <div class="{{ $class }}">
        @if($eyebrow)<span class="stat-card__label">{{ $eyebrow }}</span>@endif
        @if($label)<span class="stat-card__label">{{ $label }}</span>@endif
        <span class="stat-card__value tnum">@if($slot->isNotEmpty()){{ $slot }}@else{{ $value }}@endif</span>
        @if($hint)<span class="stat-card__hint">{{ $hint }}</span>@endif
    </div>
@endif
