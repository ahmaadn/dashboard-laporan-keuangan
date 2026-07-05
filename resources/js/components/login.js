export default (profiles) => ({
    namaPengguna: '',
    kataSandi: '',
    error: '',
    showPassword: false,

    init() {
        this.profiles = profiles;
    },

    setProfile(profile) {
        const value = encodeURIComponent(JSON.stringify(profile));
        document.cookie = `ld_profile=${value};path=/;max-age=86400;SameSite=Lax`;
    },

    targetFor(profile) {
        return profile.peran === 'pegawai' && !profile.dapat_melihat_dashboard
            ? '/income'
            : '/dashboard';
    },

    quickFill(profile) {
        this.namaPengguna = profile.nama_pengguna;
        this.kataSandi = 'demo1234';
        this.setProfile(profile);
        window.location.href = this.targetFor(profile);
    },

    submit() {
        if (!this.namaPengguna || !this.kataSandi) {
            this.error = 'Nama pengguna dan kata sandi wajib diisi.';
            return;
        }
        const nama = this.namaPengguna.trim().toLowerCase();
        const matched = this.profiles.find((p) => p.nama_pengguna === nama);
        const profile = matched ?? this.profiles.find((p) => p.peran === 'admin') ?? this.profiles[0];
        this.setProfile(profile);
        window.location.href = this.targetFor(profile);
    },
});
