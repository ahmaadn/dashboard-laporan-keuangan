{{-- Desktop sidebar (visible ≥ lg) --}}
<aside class="ld-sidebar d-none d-lg-flex" aria-label="Navigasi utama">
    <div class="ld-sidebar-inner">
        @include('partials.sidebar-nav')
    </div>
</aside>

{{-- Mobile offcanvas drawer --}}
<div class="offcanvas offcanvas-start ld-sidebar-offcanvas" tabindex="-1" id="mobileSidebar" x-ref="canvas" aria-label="Navigasi mobile">
    <div class="offcanvas-body">
        <div class="ld-sidebar-inner">
            @include('partials.sidebar-nav')
        </div>
    </div>
</div>
