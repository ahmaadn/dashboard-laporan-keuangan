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
    stockModal: null,
    stockForm: {},
    stockErrors: {},
    movementsOpen: false,
    movements: [],
    movementsProduct: null,

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
        this.form = {
            nama: '', id_kategori: '', sku: '', harga: '', harga_modal: 0, harga_grosir: '',
            min_qty_grosir: 3, stok: 0, stok_minimum: 5, deskripsi: '', aktif: true,
        };
        this.errors = {};
        this.modalOpen = true;
    },

    openEdit(row) {
        this.editingId = row.id;
        this.form = {
            ...row,
            harga: String(row.harga ?? ''),
            harga_modal: String(row.harga_modal ?? 0),
            harga_grosir: row.harga_grosir != null ? String(row.harga_grosir) : '',
            min_qty_grosir: row.min_qty_grosir ?? 3,
            stok_minimum: row.stok_minimum ?? 5,
        };
        this.errors = {};
        this.modalOpen = true;
    },

    async save() {
        this.errors = {};
        this.saving = true;
        const url = this.editingId ? `/products/${this.editingId}` : '/products';
        const method = this.editingId ? 'PUT' : 'POST';
        const payload = {
            nama: this.form.nama,
            id_kategori: this.form.id_kategori || null,
            sku: this.form.sku || null,
            harga: Number(this.form.harga),
            harga_modal: Number(this.form.harga_modal || 0),
            harga_grosir: this.form.harga_grosir === '' || this.form.harga_grosir == null ? null : Number(this.form.harga_grosir),
            min_qty_grosir: Number(this.form.min_qty_grosir || 3),
            stok_minimum: Number(this.form.stok_minimum || 5),
            deskripsi: this.form.deskripsi,
            aktif: !!this.form.aktif,
        };
        if (!this.editingId) {
            payload.stok = Number(this.form.stok || 0);
        }
        const body = JSON.stringify(payload);

        const res = await apiFetch(url, { method, body });
        this.saving = false;

        if (!res.success) {
            if (res.errors) {
                this.errors = Object.fromEntries(Object.entries(res.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
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

    openStock(row, aksi) {
        this.stockModal = row;
        this.stockForm = { aksi, jumlah: 1, stok_baru: row.stok, keterangan: '' };
        this.stockErrors = {};
    },

    async saveStock() {
        if (!this.stockModal) return;
        this.stockErrors = {};
        const body = {
            aksi: this.stockForm.aksi,
            keterangan: this.stockForm.keterangan || null,
        };
        if (this.stockForm.aksi === 'restok') {
            body.jumlah = Number(this.stockForm.jumlah);
        } else {
            body.stok_baru = Number(this.stockForm.stok_baru);
        }
        const res = await apiFetch(`/products/${this.stockModal.id}/stock`, {
            method: 'POST',
            body: JSON.stringify(body),
        });
        if (!res.success) {
            if (res.errors) {
                this.stockErrors = Object.fromEntries(Object.entries(res.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
            } else {
                this.toast = res.message || 'Gagal mengubah stok.';
                this.dismissToast();
            }
            return;
        }
        const idx = this.rows.findIndex((r) => r.id === this.stockModal.id);
        if (idx >= 0) Object.assign(this.rows[idx], res.resource);
        this.stockModal = null;
        this.toast = 'Stok diperbarui.';
        this.dismissToast();
    },

    async openMovements(row) {
        this.movementsProduct = row;
        this.movements = [];
        this.movementsOpen = true;
        const res = await apiFetch(`/products/${row.id}/movements`);
        if (res.success) {
            this.movements = res.movements || [];
        }
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

        return this.rows.filter((r) => `${this.produkNama(r.id_produk)} ${r.tanggal_transaksi} ${r.jenis_transaksi || ''}`.toLowerCase().includes(q));
    },

    produkNama(id) {
        return this.produkById[id]?.nama ?? 'Tanpa produk';
    },

    pencatatNama(id) {
        return this.penggunaById[id]?.nama ?? '—';
    },

    selectedProduct() {
        return this.produkAktif.find((x) => x.id === Number(this.form.id_produk)) || null;
    },

    get hargaTipePreview() {
        if (this.form.harga_manual) return 'manual';
        const p = this.selectedProduct();
        if (!p) return 'manual';
        const qty = Number(this.form.jumlah) || 0;
        const min = Number(p.min_qty_grosir || 3);
        if (this.form.jenis_transaksi === 'offline' && qty >= min && p.harga_grosir) {
            return 'grosir';
        }
        return 'eceran';
    },

    get computedTotal() {
        return (Number(this.form.jumlah) || 0) * (Number(this.form.harga_satuan) || 0);
    },

    applyAutoPrice() {
        if (this.form.harga_manual) return;
        const p = this.selectedProduct();
        if (!p) return;
        const qty = Number(this.form.jumlah) || 0;
        const min = Number(p.min_qty_grosir || 3);
        if (this.form.jenis_transaksi === 'offline' && qty >= min && p.harga_grosir) {
            this.form.harga_satuan = p.harga_grosir;
        } else {
            this.form.harga_satuan = p.harga;
        }
    },

    onProductChange() {
        this.applyAutoPrice();
    },

    onPricingInputsChange() {
        this.applyAutoPrice();
    },

    rupiah(n) {
        return rupiah(n);
    },

    openAdd() {
        this.editingId = null;
        this.form = {
            id_produk: '',
            tanggal_transaksi: todayStr(),
            jenis_transaksi: 'offline',
            jumlah: 1,
            harga_satuan: 0,
            harga_manual: false,
            keterangan: '',
        };
        this.errors = {};
        this.modalOpen = true;
    },

    openEdit(row) {
        this.editingId = row.id;
        this.form = {
            ...row,
            jumlah: String(row.jumlah),
            harga_satuan: String(row.harga_satuan),
            jenis_transaksi: row.jenis_transaksi || 'offline',
            harga_manual: row.harga_tipe === 'manual',
        };
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
            jenis_transaksi: this.form.jenis_transaksi || 'offline',
            jumlah: Number(this.form.jumlah),
            harga_satuan: Number(this.form.harga_satuan),
            harga_manual: !!this.form.harga_manual,
            keterangan: this.form.keterangan,
        });

        const res = await apiFetch(url, { method, body });
        this.saving = false;

        if (!res.success) {
            if (res.errors) {
                this.errors = Object.fromEntries(Object.entries(res.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
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
            // refresh stok di map produk client-side
            if (res.resource?.id_produk && this.produkById[res.resource.id_produk]) {
                const p = this.produkById[res.resource.id_produk];
                p.stok = Math.max(0, (p.stok || 0) - Number(res.resource.jumlah || 0));
            }
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

const stocks = (produk, produkById, currentUser) => ({
    produk,
    produkById,
    produkMap: Object.fromEntries(Object.entries(produkById).map(([k, v]) => [k, v.nama])),
    mutasi: [],
    search: '',
    modalOpen: false,
    form: {},
    errors: {},
    saving: false,
    toast: '',

    async init() {
        const res = await apiFetch('/stocks/movements');
        if (res.success) {
            this.mutasi = res.movements || [];
        }
    },

    get visibleProducts() {
        if (!this.search.trim()) {
            return this.produk;
        }
        const q = this.search.toLowerCase();

        return this.produk.filter((p) => `${p.nama} ${p.sku || ''}`.toLowerCase().includes(q));
    },

    openAdd() {
        this.form = { id_produk: '', tanggal: todayStr(), jumlah: 1, keterangan: '' };
        this.errors = {};
        this.modalOpen = true;
    },

    async save() {
        this.errors = {};
        this.saving = true;
        const body = JSON.stringify({
            id_produk: Number(this.form.id_produk) || null,
            tanggal: this.form.tanggal,
            jumlah: Number(this.form.jumlah),
            keterangan: this.form.keterangan,
        });
        const res = await apiFetch('/stocks', { method: 'POST', body });
        this.saving = false;
        if (!res.success) {
            if (res.errors) {
                this.errors = Object.fromEntries(Object.entries(res.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
            } else {
                this.toast = res.message || 'Gagal menyimpan.';
                this.dismissToast();
            }

            return;
        }

        // refresh movements and produk state
        const idx = this.produk.findIndex((p) => p.id === Number(this.form.id_produk));
        if (idx >= 0) {
            this.produk[idx] = { ...this.produk[idx], stok: res.stok ?? (this.produk[idx].stok + Number(this.form.jumlah)) };
            this.produkMap = Object.fromEntries(Object.entries(this.produkById).length
                ? Object.entries(this.produkById).map(([k, v]) => [k, v.nama])
                : []);
            this.produkById[this.form.id_produk] = this.produk[idx];
            this.produkMap[this.form.id_produk] = this.produk[idx].nama;
        }
        const mov = await apiFetch('/stocks/movements');
        if (mov.success) {
            this.mutasi = mov.movements || [];
        }
        this.modalOpen = false;
        this.toast = 'Stok ditambahkan.';
        this.dismissToast();
    },

    rupiah(n) {
        return rupiah(n);
    },

    dismissToast() {
        setTimeout(() => (this.toast = ''), 2800);
    },
});

const capital = (rows, currentUser) => ({
    rows,
    currentUser,
    isAdmin: currentUser?.peran === 'admin',
    search: '',
    modalOpen: false,
    form: {},
    errors: {},
    deleteTarget: null,
    toast: '',

    get visibleRows() {
        if (!this.search.trim()) {
            return this.rows;
        }
        const q = this.search.toLowerCase();

        return this.rows.filter((r) => `${r.tanggal || ''} ${r.keterangan || ''}`.toLowerCase().includes(q));
    },

    openAdd() {
        this.form = { tanggal: todayStr(), nominal: '', keterangan: '' };
        this.errors = {};
        this.modalOpen = true;
    },

    async save() {
        this.errors = {};
        const body = JSON.stringify({
            tanggal: this.form.tanggal,
            nominal: Number(this.form.nominal),
            keterangan: this.form.keterangan,
        });
        const res = await apiFetch('/capital', { method: 'POST', body });
        if (!res.success) {
            if (res.errors) {
                this.errors = Object.fromEntries(Object.entries(res.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
            } else {
                this.toast = res.message || 'Gagal menyimpan.';
                this.dismissToast();
            }

            return;
        }
        this.rows.unshift(res.resource);
        this.modalOpen = false;
        this.toast = 'Setoran modal dicatat.';
        this.dismissToast();
    },

    confirmDelete(row) {
        this.deleteTarget = row;
    },

    async doDelete() {
        if (!this.deleteTarget) {
            return;
        }
        const res = await apiFetch(`/capital/${this.deleteTarget.id}`, { method: 'DELETE' });
        if (!res.success) {
            this.toast = res.message || 'Gagal menghapus.';
            this.deleteTarget = null;
            this.dismissToast();

            return;
        }
        this.deleteTarget.dihapus_pada = nowStr();
        this.toast = 'Setoran dihapus (soft delete).';
        this.deleteTarget = null;
        this.dismissToast();
    },

    rupiah(n) {
        return rupiah(n);
    },

    pencatatNama(id) {
        return id ? ('Pencatat #' + id) : '—';
    },

    dismissToast() {
        setTimeout(() => (this.toast = ''), 2800);
    },
});

const salesReturns = (rows, currentUser) => ({
    rows,
    currentUser,
    isAdmin: currentUser?.peran === 'admin',
    search: '',
    modalOpen: false,
    form: {},
    errors: {},
    deleteTarget: null,
    toast: '',

    get visibleRows() {
        if (!this.search.trim()) {
            return this.rows;
        }
        const q = this.search.toLowerCase();

        return this.rows.filter((r) => `${r.tanggal || ''} ${r.id_penjualan} ${r.alasan || ''}`.toLowerCase().includes(q));
    },

    openAdd() {
        this.form = { id_penjualan: '', tanggal: todayStr(), jumlah: 1, alasan: '' };
        this.errors = {};
        this.modalOpen = true;
    },

    async save() {
        this.errors = {};
        const body = JSON.stringify({
            id_penjualan: Number(this.form.id_penjualan),
            tanggal: this.form.tanggal,
            jumlah: Number(this.form.jumlah),
            alasan: this.form.alasan,
        });
        const res = await apiFetch('/sales-returns', { method: 'POST', body });
        if (!res.success) {
            if (res.errors) {
                this.errors = Object.fromEntries(Object.entries(res.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
            } else {
                this.errors = { id_penjualan: res.message || 'Gagal menyimpan.' };
            }

            return;
        }
        this.rows.unshift(res.resource);
        this.modalOpen = false;
        this.toast = 'Retur dicatat.';
        this.dismissToast();
    },

    confirmDelete(row) {
        this.deleteTarget = row;
    },

    async doDelete() {
        if (!this.deleteTarget) {
            return;
        }
        const res = await apiFetch(`/sales-returns/${this.deleteTarget.id}`, { method: 'DELETE' });
        if (!res.success) {
            this.toast = res.message || 'Gagal menghapus.';
            this.deleteTarget = null;
            this.dismissToast();

            return;
        }
        this.deleteTarget.dihapus_pada = nowStr();
        this.toast = 'Retur dihapus.';
        this.deleteTarget = null;
        this.dismissToast();
    },

    rupiah(n) {
        return rupiah(n);
    },

    dismissToast() {
        setTimeout(() => (this.toast = ''), 2800);
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
Alpine.data('stocks', stocks);
Alpine.data('capital', capital);
Alpine.data('salesReturns', salesReturns);

export { products, income, expenses, users, reports, stocks, capital, salesReturns };
