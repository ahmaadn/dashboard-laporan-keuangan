@props([
    'scroll' => true,
])

<div class="ld-table-wrap">
    <div class="table-responsive">
        {{ $slot }}
    </div>
</div>
