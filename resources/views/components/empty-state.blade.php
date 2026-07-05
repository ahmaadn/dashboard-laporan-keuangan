@props([
    'icon' => null,
    'text' => null,
])

<div class="ld-empty" {{ $attributes->merge([]) }}>
    @if($icon)<span class="ld-empty__icon" aria-hidden="true">{{ $icon }}</span>@endif
    @if($text)<span class="ld-empty__text">{{ $text }}</span>@endif
    @if(isset($action))<div class="mt-2">{{ $action }}</div>@endif
</div>
