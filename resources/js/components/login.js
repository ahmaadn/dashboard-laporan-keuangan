export default (profiles) => ({
    namaPengguna: '',
    kataSandi: '',
    showPassword: false,

    init() {
        this.profiles = profiles;
    },

    quickFill(profile) {
        this.namaPengguna = profile.nama_pengguna;
        this.kataSandi = 'demo1234';
    },
});
