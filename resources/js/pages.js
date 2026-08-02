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
    rows: (rows || []).map((r) => ({
        ...r,
        jumlah_diretur: r.jumlah_diretur ?? 0,
        sisa_retur: r.sisa_retur ?? r.jumlah,
        status: r.status ?? (r.dihapus_pada ? 'gagal' : 'berhasil'),
        status_label: r.status_label ?? (r.dihapus_pada ? 'Gagal' : 'Berhasil'),
        retur_history: r.retur_history ?? [],
    })),
    produkAktif,
    produkById,
    penggunaById,
    currentUserId,
    search: '',
    modalOpen: false,
    editingId: null,
    form: {},
    cart: [],
    cartDraft: {},
    errors: {},
    deleteTarget: null,
    toast: '',
    saving: false,
    expandedId: null,
    returModalOpen: false,
    returTarget: null,
    returForm: {},
    returErrors: {},
    returSaving: false,

    get visibleRows() {
        if (!this.search.trim()) {
            return this.rows;
        }
        const q = this.search.toLowerCase();

        return this.rows.filter((r) =>
            `${this.produkNama(r.id_produk)} ${r.nomor_transaksi || ''} ${r.tanggal_transaksi} ${r.jenis_transaksi || ''} ${r.status_label || ''}`.toLowerCase().includes(q),
        );
    },

    get returMax() {
        if (!this.returTarget) return 0;
        return Math.max(0, Number(this.returTarget.sisa_retur ?? this.returTarget.jumlah) || 0);
    },

    get returNominalPreview() {
        if (!this.returTarget) return 0;
        return (Number(this.returForm.jumlah) || 0) * (Number(this.returTarget.harga_satuan) || 0);
    },

    get cartTotal() {
        return this.cart.reduce((sum, line) => sum + (Number(line.jumlah) || 0) * (Number(line.harga_satuan) || 0), 0);
    },

    get computedTotal() {
        if (this.editingId) {
            return (Number(this.form.jumlah) || 0) * (Number(this.form.harga_satuan) || 0);
        }
        return this.cartTotal;
    },

    get draftHargaTipe() {
        if (this.cartDraft.harga_manual) return 'manual';
        const p = this.draftProduct();
        if (!p) return 'manual';
        const qty = Number(this.cartDraft.jumlah) || 0;
        const min = Number(p.min_qty_grosir || 3);
        if (this.form.jenis_transaksi === 'offline' && qty >= min && p.harga_grosir) {
            return 'grosir';
        }
        return 'eceran';
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

    produkNama(id) {
        return this.produkById[id]?.nama ?? 'Tanpa produk';
    },

    pencatatNama(id) {
        return this.penggunaById[id]?.nama ?? '—';
    },

    statusLabel(row) {
        if (row.dihapus_pada) return 'Gagal';
        return row.status_label || 'Berhasil';
    },

    statusBadgeClass(row) {
        const status = row.dihapus_pada ? 'gagal' : row.status;
        if (status === 'gagal') return 'badge-soft-delete';
        if (status === 'semua_diretur') return 'badge-info-soft';
        if (status === 'retur_sebagian') return 'badge-warning-soft';
        return 'badge-success-soft';
    },

    toggleExpand(row) {
        if (!(row.retur_history?.length > 0)) return;
        this.expandedId = this.expandedId === row.id ? null : row.id;
    },

    selectedProduct() {
        return this.produkAktif.find((x) => x.id === Number(this.form.id_produk)) || null;
    },

    draftProduct() {
        return this.produkAktif.find((x) => x.id === Number(this.cartDraft.id_produk)) || null;
    },

    cartLineProduct(line) {
        return this.produkById[line.id_produk] || this.produkAktif.find((x) => x.id === Number(line.id_produk)) || null;
    },

    cartLineNama(line) {
        if (!line.id_produk) return 'Tanpa produk (lain-lain)';
        return this.produkNama(line.id_produk);
    },

    cartLineSubtotal(line) {
        return (Number(line.jumlah) || 0) * (Number(line.harga_satuan) || 0);
    },

    emptyCartDraft() {
        return {
            id_produk: '',
            jumlah: 1,
            harga_satuan: 0,
            harga_manual: false,
        };
    },

    applyAutoPriceTo(target) {
        if (target.harga_manual) return;
        const p = this.produkAktif.find((x) => x.id === Number(target.id_produk));
        if (!p) {
            if (!target.harga_manual) target.harga_satuan = 0;
            return;
        }
        const qty = Number(target.jumlah) || 0;
        const min = Number(p.min_qty_grosir || 3);
        if (this.form.jenis_transaksi === 'offline' && qty >= min && p.harga_grosir) {
            target.harga_satuan = p.harga_grosir;
        } else {
            target.harga_satuan = p.harga;
        }
    },

    applyAutoPrice() {
        this.applyAutoPriceTo(this.form);
    },

    onProductChange() {
        this.applyAutoPrice();
    },

    onPricingInputsChange() {
        this.applyAutoPrice();
        if (!this.editingId) {
            this.cart.forEach((line) => this.applyAutoPriceTo(line));
            this.applyAutoPriceTo(this.cartDraft);
        }
    },

    onDraftProductChange() {
        this.applyAutoPriceTo(this.cartDraft);
    },

    onDraftPricingChange() {
        this.applyAutoPriceTo(this.cartDraft);
    },

    onCartLinePricingChange(index) {
        this.applyAutoPriceTo(this.cart[index]);
    },

    availableProductsForDraft() {
        const used = new Set(this.cart.map((l) => Number(l.id_produk)).filter(Boolean));
        return this.produkAktif.filter((p) => !used.has(Number(p.id)) || Number(this.cartDraft.id_produk) === Number(p.id));
    },

    addCartLine() {
        const idProduk = this.cartDraft.id_produk ? Number(this.cartDraft.id_produk) : null;
        const jumlah = Number(this.cartDraft.jumlah) || 0;
        const harga = Number(this.cartDraft.harga_satuan) || 0;
        const hargaManual = !!this.cartDraft.harga_manual;

        if (jumlah < 1) {
            this.errors = { ...this.errors, cart: 'Jumlah minimal 1.' };
            return;
        }
        if (!idProduk && !hargaManual) {
            this.errors = { ...this.errors, cart: 'Pilih produk atau aktifkan harga manual.' };
            return;
        }
        if (!idProduk && harga <= 0) {
            this.errors = { ...this.errors, cart: 'Isi harga untuk item tanpa produk.' };
            return;
        }

        if (idProduk) {
            const existing = this.cart.findIndex((l) => Number(l.id_produk) === idProduk);
            if (existing >= 0) {
                const line = this.cart[existing];
                line.jumlah = Number(line.jumlah) + jumlah;
                if (hargaManual) {
                    line.harga_manual = true;
                    line.harga_satuan = harga;
                } else {
                    this.applyAutoPriceTo(line);
                }
                this.cartDraft = this.emptyCartDraft();
                delete this.errors.cart;
                return;
            }
        }

        this.cart.push({
            id_produk: idProduk || '',
            jumlah,
            harga_satuan: harga,
            harga_manual: hargaManual || !idProduk,
        });
        this.cartDraft = this.emptyCartDraft();
        delete this.errors.cart;
    },

    removeCartLine(index) {
        this.cart.splice(index, 1);
    },

    rupiah(n) {
        return rupiah(n);
    },

    openAdd() {
        this.editingId = null;
        this.form = {
            tanggal_transaksi: todayStr(),
            jenis_transaksi: 'offline',
            keterangan: '',
        };
        this.cart = [];
        this.cartDraft = this.emptyCartDraft();
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
        this.cart = [];
        this.cartDraft = this.emptyCartDraft();
        this.errors = {};
        this.modalOpen = true;
    },

    openRetur(row) {
        const sisa = Math.max(0, Number(row.sisa_retur ?? row.jumlah) || 0);
        if (sisa < 1) {
            this.toast = 'Tidak ada sisa yang bisa diretur.';
            this.dismissToast();
            return;
        }
        this.returTarget = row;
        this.returForm = {
            tanggal: todayStr(),
            jumlah: sisa,
            alasan: '',
        };
        this.returErrors = {};
        this.returModalOpen = true;
    },

    async saveRetur() {
        if (!this.returTarget) return;
        this.returErrors = {};
        this.returSaving = true;
        const qty = Number(this.returForm.jumlah);
        if (qty < 1 || qty > this.returMax) {
            this.returErrors = { jumlah: `Jumlah retur harus antara 1 dan ${this.returMax}.` };
            this.returSaving = false;
            return;
        }

        const body = JSON.stringify({
            id_penjualan: this.returTarget.id,
            tanggal: this.returForm.tanggal,
            jumlah: qty,
            alasan: this.returForm.alasan,
        });
        const res = await apiFetch('/sales-returns', { method: 'POST', body });
        this.returSaving = false;

        if (!res.success) {
            if (res.errors) {
                this.returErrors = Object.fromEntries(Object.entries(res.errors).map(([k, v]) => [k, Array.isArray(v) ? v[0] : v]));
            } else {
                this.returErrors = { jumlah: res.message || 'Gagal menyimpan retur.' };
            }
            return;
        }

        const idx = this.rows.findIndex((r) => r.id === this.returTarget.id);
        if (idx >= 0) {
            const row = this.rows[idx];
            const history = [...(row.retur_history || [])];
            if (res.resource) {
                history.unshift({
                    id: res.resource.id,
                    tanggal: res.resource.tanggal,
                    jumlah: res.resource.jumlah,
                    nominal_retur: res.resource.nominal_retur,
                    alasan: res.resource.alasan,
                    dibuat_pada: res.resource.dibuat_pada,
                });
            }
            const jumlahDiretur = res.income?.jumlah_diretur ?? (Number(row.jumlah_diretur || 0) + qty);
            const sisaRetur = res.income?.sisa_retur ?? Math.max(0, Number(row.jumlah) - jumlahDiretur);
            Object.assign(row, {
                jumlah_diretur: jumlahDiretur,
                sisa_retur: sisaRetur,
                status: res.income?.status ?? (sisaRetur <= 0 ? 'semua_diretur' : 'retur_sebagian'),
                status_label: res.income?.status_label ?? (sisaRetur <= 0 ? 'Semua di retur' : 'Retur sebagian'),
                retur_history: history,
            });
            if (row.id_produk && this.produkById[row.id_produk]) {
                this.produkById[row.id_produk].stok = (this.produkById[row.id_produk].stok || 0) + qty;
            }
        }

        this.returModalOpen = false;
        this.returTarget = null;
        this.toast = 'Retur dicatat.';
        this.dismissToast();
    },

    normalizeRow(resource) {
        return {
            ...resource,
            jumlah_diretur: resource.jumlah_diretur ?? 0,
            sisa_retur: resource.sisa_retur ?? resource.jumlah,
            status: resource.status ?? 'berhasil',
            status_label: resource.status_label ?? 'Berhasil',
            retur_history: resource.retur_history ?? [],
        };
    },

    applyStockDelta(resource, sign) {
        if (resource?.id_produk && this.produkById[resource.id_produk]) {
            const p = this.produkById[resource.id_produk];
            p.stok = Math.max(0, (p.stok || 0) + sign * Number(resource.jumlah || 0));
        }
        const aktif = this.produkAktif.find((x) => x.id === resource?.id_produk);
        if (aktif) {
            aktif.stok = Math.max(0, (aktif.stok || 0) + sign * Number(resource.jumlah || 0));
        }
    },

    async save() {
        this.errors = {};
        this.saving = true;

        let body;
        let url;
        let method;

        if (this.editingId) {
            url = `/income/${this.editingId}`;
            method = 'PUT';
            body = JSON.stringify({
                id_produk: this.form.id_produk || null,
                tanggal_transaksi: this.form.tanggal_transaksi,
                jenis_transaksi: this.form.jenis_transaksi || 'offline',
                jumlah: Number(this.form.jumlah),
                harga_satuan: Number(this.form.harga_satuan),
                harga_manual: !!this.form.harga_manual,
                keterangan: this.form.keterangan,
            });
        } else {
            if (this.cart.length === 0) {
                this.errors = { cart: 'Tambahkan minimal satu produk ke keranjang.' };
                this.saving = false;
                return;
            }
            url = '/income';
            method = 'POST';
            body = JSON.stringify({
                tanggal_transaksi: this.form.tanggal_transaksi,
                jenis_transaksi: this.form.jenis_transaksi || 'offline',
                keterangan: this.form.keterangan,
                items: this.cart.map((line) => ({
                    id_produk: line.id_produk || null,
                    jumlah: Number(line.jumlah),
                    harga_satuan: Number(line.harga_satuan),
                    harga_manual: !!line.harga_manual,
                })),
            });
        }

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
            const list = res.resources || (res.resource ? [res.resource] : []);
            list
                .slice()
                .reverse()
                .forEach((resource) => {
                    this.rows.unshift(this.normalizeRow(resource));
                    this.applyStockDelta(resource, -1);
                });
            const n = list.length;
            this.toast = n > 1 ? `Transaksi ${res.nomor_transaksi || ''} · ${n} item ditambahkan.` : 'Transaksi pemasukan ditambahkan.';
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
        this.deleteTarget.status = 'gagal';
        this.deleteTarget.status_label = 'Gagal';
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

const capital = (rows, penggunaById, currentUser) => ({
    rows,
    penggunaById,
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
        return id ? (this.penggunaById?.[id]?.nama ?? `Pencatat #${id}`) : '—';
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

    init() {
        const params = new URLSearchParams(window.location.search);
        const incomeId = params.get('income_id');
        if (incomeId) {
            this.openAdd(incomeId);
        }
    },

    get visibleRows() {
        if (!this.search.trim()) {
            return this.rows;
        }
        const q = this.search.toLowerCase();

        return this.rows.filter((r) => `${r.tanggal || ''} ${r.id_penjualan} ${r.nama_produk || ''} ${r.alasan || ''}`.toLowerCase().includes(q));
    },

    openAdd(incomeId = '') {
        this.form = {
            id_penjualan: incomeId ? String(incomeId) : '',
            tanggal: todayStr(),
            jumlah: 1,
            alasan: '',
        };
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
        this.toast = 'Retur dihapus; stok dikurangi kembali.';
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
