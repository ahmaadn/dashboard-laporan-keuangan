export default (current, profiles) => ({
    open: false,
    current: null,
    profiles: [],

    init() {
        this.current = current;
        this.profiles = profiles;
    },

    get label() {
        if (!this.current) {
            return '—';
        }
        return this.current.peran === 'admin'
            ? `Admin · ${this.current.nama}`
            : `Pegawai · ${this.current.nama}`;
    },

    toggle() {
        this.open = !this.open;
    },

    choose(profile) {
        const value = encodeURIComponent(JSON.stringify(profile));
        document.cookie = `ld_profile=${value};path=/;max-age=86400;SameSite=Lax`;
        window.location.reload();
    },

    logout() {
        window.location.href = '/logout';
    },
});
