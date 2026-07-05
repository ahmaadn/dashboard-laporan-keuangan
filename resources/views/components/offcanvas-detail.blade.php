@props([
    'id',
    'title' => null,
    'eyebrow' => null,
])

<div class="offcanvas offcanvas-end ld-offcanvas" tabindex="-1" id="{{ $id }}" aria-labelledby="{{ $id }}Label">
    <div class="offcanvas-header border-bottom">
        <div>
            @if($eyebrow)<span class="ld-eyebrow d-block">{{ $eyebrow }}</span>@endif
            @if($title)<h5 class="offcanvas-title" id="{{ $id }}Label">{{ $title }}</h5>@endif
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
        {{ $slot }}
    </div>
</div>
