@props([
    'title' => null,
    'eyebrow' => null,
    'flush' => false,
])

<div {{ $attributes->merge(['class' => 'app-card'.($flush ? ' app-card--flush' : '')]) }}>
    @if($title || $eyebrow || isset($actions))
        <div class="app-card__header">
            <div>
                @if($eyebrow)<span class="ld-eyebrow d-block">{{ $eyebrow }}</span>@endif
                @if($title)<h3 class="app-card__title">{{ $title }}</h3>@endif
            </div>
            @if(isset($actions))<div class="app-card__actions">{{ $actions }}</div>@endif
        </div>
    @endif

    {{ $slot }}
</div>
