import Alpine from 'alpinejs';

function rupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function nowStr() {
    const d = new Date();

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

function todayStr() {
    const d = new Date();

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function pad(n) {
    return String(n).padStart(2, '0');
}

function isEmail(v) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
}

const products = (rows, kategoriMap, isAdmin) => ({
    rows,
    kategoriMap,
    isAdmin,
    search: '',
    modalOpen: false,
    editingId: null,
    form: {},
    errors: {},
    deleteTarget: null,
    toast: '',

    get visibleRows() {
        const base = this.isAdmin ? this.rows : this.rows.filter((r) => !r.dihapus_pada);
        if (!this.search.trim()) {
            return base;
        }
        const q = this.search.toLowerCase();

        return base.filter((r) => `${r.nama} ${r.sku} ${this.kategoriNama(r.id_kategori)}`.toLowerCase().includes(q));
    },

    kategoriNama(id) {
        return this.kategoriMap[id]?.nama ?? '—';
    },

    rupiah(n) {
        return rupiah(n);
    },

    openAdd() {
        this.editingId = null;
        this.form = { nama: '', id_kategori: '', sku: '', harga: '', deskripsi: '', aktif: true };
        this.errors = {};
        this.modalOpen = true;
    },

    openEdit(row) {
        this.editingId = row.id;
        this.form = { ...row };
        this.errors = {};
        this.modalOpen = true;
    },

    save() {
        this.errors = {};
        if (!this.form.nama?.trim()) {
            this.errors.nama = 'Nama produk wajib diisi.';
        }
        if (this.form.harga === '' || Number(this.form.harga) < 0) {
            this.errors.harga = 'Harga tidak boleh negatif.';
        }
        if (this.form.sku && this.rows.some((r) => r.sku === this.form.sku && r.id !== this.editingId)) {
            this.errors.sku = 'SKU sudah digunakan, gunakan nilai lain.';
        }
        if (Object.keys(this.errors).length) {
            return;
        }
        if (this.editingId) {
            const row = this.rows.find((r) => r.id === this.editingId);
            Object.assign(row, this.form, { diperbarui_pada: nowStr() });
            this.toast = 'Produk diperbarui.';
        } else {
            const id = this.rows.reduce((m, r) => Math.max(m, r.id), 0) + 1;
            this.rows.unshift({ id, ...this.form, harga: Number(this.form.harga), dibuat_oleh: 1, dibuat_pada: nowStr(), diperbarui_pada: nowStr(), dihapus_pada: null });
            this.toast = 'Produk ditambahkan.';
        }
        this.modalOpen = false;
        this.dismissToast();
    },

    confirmDelete(row) {
        this.deleteTarget = row;
    },

    doDelete() {
        if (!this.deleteTarget) {
            return;
        }
        this.deleteTarget.dihapus_pada = nowStr();
        this.toast = 'Produk dihapus (soft delete).';
        this.deleteTarget = null;
        this.dismissToast();
    },

    dismissToast() {
        setTimeout(() => (this.toast = ''), 2800);
    },
});

const income = (rows, produkAktif, produkById, penggunaById, currentUserId) => ({
    rows,
    produkAktif,
    produkById,
    penggunaById,
    currentUserId,
    search: '',
    modalOpen: false,
    editingId: null,
    form: {},
    errors: {},
    deleteTarget: null,
    toast: '',

    get visibleRows() {
        if (!this.search.trim()) {
            return this.rows;
        }
        const q = this.search.toLowerCase();

        return this.rows.filter((r) => `${this.produkNama(r.id_produk)} ${r.tanggal_transaksi}`.toLowerCase().includes(q));
    },

    produkNama(id) {
        return this.produkById[id]?.nama ?? 'Tanpa produk';
    },

    pencatatNama(id) {
        return this.penggunaById[id]?.nama ?? '—';
    },

    get computedTotal() {
        return (Number(this.form.jumlah) || 0) * (Number(this.form.harga_satuan) || 0);
    },

    onProductChange() {
        const p = this.produkAktif.find((x) => x.id === Number(this.form.id_produk));
        if (p) {
            this.form.harga_satuan = p.harga;
        }
    },

    rupiah(n) {
        return rupiah(n);
    },

    openAdd() {
        this.editingId = null;
        this.form = { id_produk: '', tanggal_transaksi: todayStr(), jumlah: 1, harga_satuan: 0, keterangan: '' };
        this.errors = {};
        this.modalOpen = true;
    },

    openEdit(row) {
        this.editingId = row.id;
        this.form = { ...row, jumlah: String(row.jumlah), harga_satuan: String(row.harga_satuan) };
        this.errors = {};
        this.modalOpen = true;
    },

    save() {
        this.errors = {};
        if (!this.form.tanggal_transaksi) {
            this.errors.tanggal_transaksi = 'Tanggal transaksi wajib diisi.';
        }
        if (!this.form.jumlah || Number(this.form.jumlah) < 1) {
            this.errors.jumlah = 'Jumlah minimal 1.';
        }
        if (Number(this.form.harga_satuan) < 0) {
            this.errors.harga_satuan = 'Harga satuan tidak boleh negatif.';
        }
        if (Object.keys(this.errors).length) {
            return;
        }
        const total = this.computedTotal;
        if (this.editingId) {
            const row = this.rows.find((r) => r.id === this.editingId);
            Object.assign(row, {
                id_produk: this.form.id_produk ? Number(this.form.id_produk) : null,
                tanggal_transaksi: this.form.tanggal_transaksi,
                jumlah: Number(this.form.jumlah),
                harga_satuan: Number(this.form.harga_satuan),
                total,
                keterangan: this.form.keterangan,
            });
            this.toast = 'Transaksi pemasukan diperbarui.';
        } else {
            const id = this.rows.reduce((m, r) => Math.max(m, r.id), 0) + 1;
            this.rows.unshift({
                id,
                id_produk: this.form.id_produk ? Number(this.form.id_produk) : null,
                tanggal_transaksi: this.form.tanggal_transaksi,
                dibuat_pada: `${this.form.tanggal_transaksi} ${pad(new Date().getHours())}:${pad(new Date().getMinutes())}:00`,
                jumlah: Number(this.form.jumlah),
                harga_satuan: Number(this.form.harga_satuan),
                total,
                keterangan: this.form.keterangan,
                id_pengguna: this.currentUserId,
                dihapus_pada: null,
            });
            this.toast = 'Transaksi pemasukan ditambahkan.';
        }
        this.modalOpen = false;
        this.dismissToast();
    },

    confirmDelete(row) {
        this.deleteTarget = row;
    },

    doDelete() {
        if (!this.deleteTarget) {
            return;
        }
        this.deleteTarget.dihapus_pada = nowStr();
        this.toast = 'Transaksi dihapus (soft delete).';
        this.deleteTarget = null;
        this.dismissToast();
    },

    dismissToast() {
        setTimeout(() => (this.toast = ''), 2800);
    },
});

const expenses = (rows, kategoriPengeluaran, penggunaById, currentUserId) => ({
    rows,
    kategoriPengeluaran,
    penggunaById,
    currentUserId,
    search: '',
    modalOpen: false,
    editingId: null,
    form: {},
    errors: {},
    deleteTarget: null,
    toast: '',

    get visibleRows() {
        if (!this.search.trim()) {
            return this.rows;
        }
        const q = this.search.toLowerCase();

        return this.rows.filter((r) => `${this.kategoriNama(r.id_kategori)} ${r.tanggal_transaksi} ${r.keterangan}`.toLowerCase().includes(q));
    },

    kategoriNama(id) {
        return this.kategoriPengeluaran.find((k) => k.id === id)?.nama ?? '—';
    },

    pencatatNama(id) {
        return this.penggunaById[id]?.nama ?? '—';
    },

    rupiah(n) {
        return rupiah(n);
    },

    openAdd() {
        this.editingId = null;
        this.form = { id_kategori: '', tanggal_transaksi: todayStr(), nominal: '', keterangan: '' };
        this.errors = {};
        this.modalOpen = true;
    },

    openEdit(row) {
        this.editingId = row.id;
        this.form = { ...row, nominal: String(row.nominal) };
        this.errors = {};
        this.modalOpen = true;
    },

    save() {
        this.errors = {};
        if (!this.form.id_kategori) {
            this.errors.id_kategori = 'Kategori wajib dipilih.';
        }
        if (!this.form.tanggal_transaksi) {
            this.errors.tanggal_transaksi = 'Tanggal transaksi wajib diisi.';
        }
        if (!this.form.nominal || Number(this.form.nominal) <= 0) {
            this.errors.nominal = 'Nominal harus lebih besar dari 0.';
        }
        if (Object.keys(this.errors).length) {
            return;
        }
        if (this.editingId) {
            const row = this.rows.find((r) => r.id === this.editingId);
            Object.assign(row, {
                id_kategori: Number(this.form.id_kategori),
                tanggal_transaksi: this.form.tanggal_transaksi,
                nominal: Number(this.form.nominal),
                keterangan: this.form.keterangan,
            });
            this.toast = 'Transaksi pengeluaran diperbarui.';
        } else {
            const id = this.rows.reduce((m, r) => Math.max(m, r.id), 0) + 1;
            this.rows.unshift({
                id,
                id_kategori: Number(this.form.id_kategori),
                tanggal_transaksi: this.form.tanggal_transaksi,
                dibuat_pada: `${this.form.tanggal_transaksi} ${pad(new Date().getHours())}:${pad(new Date().getMinutes())}:00`,
                nominal: Number(this.form.nominal),
                keterangan: this.form.keterangan,
                id_pengguna: this.currentUserId,
                dihapus_pada: null,
            });
            this.toast = 'Transaksi pengeluaran ditambahkan.';
        }
        this.modalOpen = false;
        this.dismissToast();
    },

    confirmDelete(row) {
        this.deleteTarget = row;
    },

    doDelete() {
        if (!this.deleteTarget) {
            return;
        }
        this.deleteTarget.dihapus_pada = nowStr();
        this.toast = 'Transaksi dihapus (soft delete).';
        this.deleteTarget = null;
        this.dismissToast();
    },

    dismissToast() {
        setTimeout(() => (this.toast = ''), 2800);
    },
});

const users = (rows, currentUser) => ({
    rows,
    currentUser,
    search: '',
    modalOpen: false,
    editingId: null,
    form: {},
    errors: {},
    deleteTarget: null,
    toast: '',
    guardMessage: '',

    get visibleRows() {
        if (!this.search.trim()) {
            return this.rows;
        }
        const q = this.search.toLowerCase();

        return this.rows.filter((r) => `${r.nama} ${r.nama_pengguna} ${r.email} ${r.peran}`.toLowerCase().includes(q));
    },

    activeAdminCount(exceptId) {
        return this.rows.filter((r) => r.peran === 'admin' && r.aktif && !r.dihapus_pada && r.id !== exceptId).length;
    },

    isLastAdmin(row) {
        return row.peran === 'admin' && row.aktif && !row.dihapus_pada && this.activeAdminCount(row.id) === 0;
    },

    openAdd() {
        this.editingId = null;
        this.form = { nama: '', nama_pengguna: '', email: '', kata_sandi: '', peran: 'pegawai', dapat_melihat_dashboard: false, aktif: true };
        this.errors = {};
        this.modalOpen = true;
    },

    openEdit(row) {
        this.editingId = row.id;
        this.form = { ...row, kata_sandi: '' };
        this.errors = {};
        this.modalOpen = true;
    },

    onPeranChange() {
        if (this.form.peran === 'admin') {
            this.form.dapat_melihat_dashboard = true;
        }
    },

    save() {
        this.errors = {};
        if (!this.form.nama?.trim()) {
            this.errors.nama = 'Nama wajib diisi.';
        }
        if (!this.form.nama_pengguna?.trim()) {
            this.errors.nama_pengguna = 'Nama pengguna wajib diisi.';
        } else if (this.rows.some((r) => r.nama_pengguna === this.form.nama_pengguna && r.id !== this.editingId)) {
            this.errors.nama_pengguna = 'Nama pengguna sudah digunakan.';
        }
        if (!isEmail(this.form.email || '')) {
            this.errors.email = 'Format email tidak valid.';
        } else if (this.rows.some((r) => r.email === this.form.email && r.id !== this.editingId)) {
            this.errors.email = 'Email sudah digunakan.';
        }
        if (!this.editingId && (!this.form.kata_sandi || this.form.kata_sandi.length < 8)) {
            this.errors.kata_sandi = 'Kata sandi minimal 8 karakter.';
        }
        if (Object.keys(this.errors).length) {
            return;
        }
        if (this.editingId) {
            const row = this.rows.find((r) => r.id === this.editingId);
            Object.assign(row, {
                nama: this.form.nama,
                nama_pengguna: this.form.nama_pengguna,
                email: this.form.email,
                peran: this.form.peran,
                dapat_melihat_dashboard: this.form.peran === 'admin' ? true : !!this.form.dapat_melihat_dashboard,
                aktif: !!this.form.aktif,
            });
            this.toast = 'Data pengguna diperbarui.';
        } else {
            const id = this.rows.reduce((m, r) => Math.max(m, r.id), 0) + 1;
            this.rows.unshift({
                id,
                nama: this.form.nama,
                nama_pengguna: this.form.nama_pengguna,
                email: this.form.email,
                peran: this.form.peran,
                dapat_melihat_dashboard: this.form.peran === 'admin' ? true : !!this.form.dapat_melihat_dashboard,
                aktif: !!this.form.aktif,
                dihapus_pada: null,
            });
            this.toast = 'Pengguna ditambahkan.';
        }
        this.modalOpen = false;
        this.dismissToast();
    },

    confirmDelete(row) {
        if (row.id === this.currentUser.id) {
            this.guardMessage = 'Tidak dapat menghapus akun Anda sendiri.';
            setTimeout(() => (this.guardMessage = ''), 3000);

            return;
        }
        if (this.isLastAdmin(row)) {
            this.guardMessage = 'Tidak dapat menonaktifkan Admin terakhir.';
            setTimeout(() => (this.guardMessage = ''), 3000);

            return;
        }
        this.deleteTarget = row;
    },

    doDelete() {
        if (!this.deleteTarget) {
            return;
        }
        this.deleteTarget.dihapus_pada = nowStr();
        this.toast = 'Pengguna dihapus (soft delete).';
        this.deleteTarget = null;
        this.dismissToast();
    },

    dismissToast() {
        setTimeout(() => (this.toast = ''), 2800);
    },
});

const reports = () => ({
    exportToast: '',
    doExport(kind) {
        const params = new URLSearchParams(window.location.search);
        const url = `/reports/export/${kind.toLowerCase()}?${params.toString()}`;
        window.location.href = url;
        this.exportToast = `Mengekspor ${kind}, mohon tunggu...`;
        setTimeout(() => (this.exportToast = ''), 2800);
    },
});

Alpine.data('products', products);
Alpine.data('income', income);
Alpine.data('expenses', expenses);
Alpine.data('users', users);
Alpine.data('reports', reports);

export { products, income, expenses, users, reports };
