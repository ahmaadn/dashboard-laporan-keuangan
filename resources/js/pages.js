import Alpine from 'alpinejs';

function rupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function nowStr() {
    const d = new Date();

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
}

function pad(n) {
    return String(n).padStart(2, '0');
}

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

async function apiFetch(url, options = {}) {
    const res = await fetch(url, {
        ...options,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'Accept': 'application/json',
            ...(options.headers || {}),
        },
    });

    if (res.status === 204 || res.headers.get('content-length') === '0') {
        return { success: true };
    }

    const data = await res.json().catch(() => ({}));

    if (!res.ok) {
        return { success: false, status: res.status, ...data };
    }

    return { success: true, ...data };
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
    saving: false,

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

    async save() {
        this.errors = {};
        this.saving = true;
        const url = this.editingId ? `/products/${this.editingId}` : '/products';
        const method = this.editingId ? 'PUT' : 'POST';
        const body = JSON.stringify(this.form);

        const res = await apiFetch(url, { method, body });
        this.saving = false;

        if (!res.success) {
            if (res.errors) {
                this.errors = res.errors;
            } else {
                this.errors = { nama: res.message || 'Terjadi kesalahan.' };
            }
            return;
        }

        if (this.editingId) {
            const idx = this.rows.findIndex((r) => r.id === this.editingId);
            if (idx >= 0) Object.assign(this.rows[idx], res.resource);
            this.toast = 'Produk diperbarui.';
        } else {
            this.rows.unshift(res.resource);
            this.toast = 'Produk ditambahkan.';
        }
        this.modalOpen = false;
        this.dismissToast();
    },

    confirmDelete(row) {
        this.deleteTarget = row;
    },

    async doDelete() {
        if (!this.deleteTarget) return;
        const res = await apiFetch(`/products/${this.deleteTarget.id}`, { method: 'DELETE' });
        if (!res.success) {
            this.toast = res.message || 'Gagal menghapus.';
            this.deleteTarget = null;
            this.dismissToast();
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
    saving: false,

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

    async save() {
        this.errors = {};
        this.saving = true;
        const url = this.editingId ? `/income/${this.editingId}` : '/income';
        const method = this.editingId ? 'PUT' : 'POST';
        const body = JSON.stringify({
            id_produk: this.form.id_produk || null,
            tanggal_transaksi: this.form.tanggal_transaksi,
            jumlah: Number(this.form.jumlah),
            harga_satuan: Number(this.form.harga_satuan),
            keterangan: this.form.keterangan,
        });

        const res = await apiFetch(url, { method, body });
        this.saving = false;

        if (!res.success) {
            if (res.errors) {
                this.errors = res.errors;
            } else {
                this.errors = { tanggal_transaksi: res.message || 'Terjadi kesalahan.' };
            }
            return;
        }

        if (this.editingId) {
            const idx = this.rows.findIndex((r) => r.id === this.editingId);
            if (idx >= 0) Object.assign(this.rows[idx], res.resource);
            this.toast = 'Transaksi pemasukan diperbarui.';
        } else {
            this.rows.unshift(res.resource);
            this.toast = 'Transaksi pemasukan ditambahkan.';
        }
        this.modalOpen = false;
        this.dismissToast();
    },

    confirmDelete(row) {
        this.deleteTarget = row;
    },

    async doDelete() {
        if (!this.deleteTarget) return;
        const res = await apiFetch(`/income/${this.deleteTarget.id}`, { method: 'DELETE' });
        if (!res.success) {
            this.toast = res.message || 'Gagal menghapus.';
            this.deleteTarget = null;
            this.dismissToast();
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
    saving: false,

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

    async save() {
        this.errors = {};
        this.saving = true;
        const url = this.editingId ? `/expenses/${this.editingId}` : '/expenses';
        const method = this.editingId ? 'PUT' : 'POST';
        const body = JSON.stringify({
            id_kategori: this.form.id_kategori,
            tanggal_transaksi: this.form.tanggal_transaksi,
            nominal: Number(this.form.nominal),
            keterangan: this.form.keterangan,
        });

        const res = await apiFetch(url, { method, body });
        this.saving = false;

        if (!res.success) {
            if (res.errors) {
                this.errors = res.errors;
            } else {
                this.errors = { nominal: res.message || 'Terjadi kesalahan.' };
            }
            return;
        }

        if (this.editingId) {
            const idx = this.rows.findIndex((r) => r.id === this.editingId);
            if (idx >= 0) Object.assign(this.rows[idx], res.resource);
            this.toast = 'Transaksi pengeluaran diperbarui.';
        } else {
            this.rows.unshift(res.resource);
            this.toast = 'Transaksi pengeluaran ditambahkan.';
        }
        this.modalOpen = false;
        this.dismissToast();
    },

    confirmDelete(row) {
        this.deleteTarget = row;
    },

    async doDelete() {
        if (!this.deleteTarget) return;
        const res = await apiFetch(`/expenses/${this.deleteTarget.id}`, { method: 'DELETE' });
        if (!res.success) {
            this.toast = res.message || 'Gagal menghapus.';
            this.deleteTarget = null;
            this.dismissToast();
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
    saving: false,

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

    async save() {
        this.errors = {};
        this.saving = true;
        const url = this.editingId ? `/users/${this.editingId}` : '/users';
        const method = this.editingId ? 'PUT' : 'POST';
        const body = JSON.stringify(this.form);

        const res = await apiFetch(url, { method, body });
        this.saving = false;

        if (!res.success) {
            if (res.errors) {
                this.errors = res.errors;
            } else {
                this.errors = { nama: res.message || 'Terjadi kesalahan.' };
            }
            return;
        }

        if (this.editingId) {
            const idx = this.rows.findIndex((r) => r.id === this.editingId);
            if (idx >= 0) Object.assign(this.rows[idx], res.resource);
            this.toast = 'Data pengguna diperbarui.';
        } else {
            this.rows.unshift(res.resource);
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

    async doDelete() {
        if (!this.deleteTarget) return;
        const res = await apiFetch(`/users/${this.deleteTarget.id}`, { method: 'DELETE' });
        if (!res.success) {
            this.guardMessage = res.message || 'Gagal menghapus.';
            this.deleteTarget = null;
            setTimeout(() => (this.guardMessage = ''), 3000);
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

function todayStr() {
    const d = new Date();

    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

Alpine.data('products', products);
Alpine.data('income', income);
Alpine.data('expenses', expenses);
Alpine.data('users', users);
Alpine.data('reports', reports);

export { products, income, expenses, users, reports };
