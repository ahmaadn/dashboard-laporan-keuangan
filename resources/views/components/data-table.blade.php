@props([
    'scroll' => true,
])

<div class="ld-table-wrap">
    <div class="{{ $scroll ? 'ld-table-wrap__scroll ld-scroll' : '' }}">
        {{ $slot }}
    </div>
</div>
