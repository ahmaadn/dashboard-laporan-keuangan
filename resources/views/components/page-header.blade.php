@props([
    'eyebrow' => null,
    'title' => null,
])

<div class="ld-page-header">
    <div class="ld-page-header__titles">
        @if($eyebrow)<span class="ld-eyebrow">{{ $eyebrow }}</span>@endif
        @if($title)<h1 class="ld-page-header__title">{{ $title }}</h1>@endif
    </div>
    @if(isset($actions))<div class="ld-page-header__actions">{{ $actions }}</div>@endif
</div>
