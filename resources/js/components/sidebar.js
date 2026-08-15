import { Offcanvas } from 'bootstrap';

export default () => ({
    offcanvas: null,
    activeUrl: null,
    accountMenuOpen: false,

    init() {
        this.activeUrl = window.location.pathname;
        const el = this.$refs.canvas;
        if (el) {
            this.offcanvas = new Offcanvas(el);
            el.addEventListener('click', (event) => {
                if (event.target.closest('[data-nav-link]')) {
                    this.offcanvas.hide();
                }
            });
        }
    },

    open() {
        this.offcanvas?.show();
    },

    close() {
        this.offcanvas?.hide();
    },

    toggleAccountMenu() {
        this.accountMenuOpen = !this.accountMenuOpen;
    },

    closeAccountMenu() {
        this.accountMenuOpen = false;
    },

    isActive(url) {
        if (url === '/dashboard') {
            return this.activeUrl === '/dashboard';
        }
        return this.activeUrl.startsWith(url);
    },
});
