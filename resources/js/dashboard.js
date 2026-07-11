import Chart from 'chart.js/auto';
import { Offcanvas, Modal } from 'bootstrap';
import Alpine from 'alpinejs';

const MONTHS_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

const CATEGORY_COLORS = ['#1c1108', '#f36458', '#8a7a6a', '#c4b8aa', '#3d2a1a'];
const PRODUCT_COLORS = ['#f36458', '#1c1108', '#37cd84', '#8a7a6a', '#0052ef'];

function pad(n) {
    return String(n).padStart(2, '0');
}

function dateStr(d) {
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

function startOfMonth(d) {
    return new Date(d.getFullYear(), d.getMonth(), 1);
}

function endOfMonth(d) {
    return new Date(d.getFullYear(), d.getMonth() + 1, 0, 23, 59, 59, 999);
}

function addMonths(d, n) {
    return new Date(d.getFullYear(), d.getMonth() + n, 1);
}

function rupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

const dashboard = (produk, kategoriProduk, kategoriPengeluaran, pengguna) => {
    const _charts = { trend: null, category: null, product: null };

    return {
    produkMap: {},
    kategoriProdukMap: {},
    kategoriPengeluaranMap: {},
    penggunaMap: {},

    serverData: null,

    period: 'bulan_ini',
    rangeStart: '',
    rangeEnd: '',

    cmpA: 'bulan_lalu',
    cmpB: 'bulan_ini',
    cmpCustomA: { start: '', end: '' },
    cmpCustomB: { start: '', end: '' },
    cmpData: null,

    detail: { title: '', eyebrow: '', columns: [], rows: [], emptyText: 'Belum ada transaksi pada periode ini.' },
    offcanvasInst: null,

    cashflowInstances: { offIncome: null, offExpense: null, offProfit: null },

    modalDetail: { title: '', fields: [] },
    modalInst: null,

    init() {
        this.produkMap = Object.fromEntries(produk.map((p) => [p.id, p]));
        this.kategoriProdukMap = Object.fromEntries(kategoriProduk.map((k) => [k.id, k]));
        this.kategoriPengeluaranMap = Object.fromEntries(kategoriPengeluaran.map((k) => [k.id, k]));
        this.penggunaMap = Object.fromEntries(pengguna.map((u) => [u.id, u]));

        const today = new Date();
        this.rangeStart = dateStr(startOfMonth(today));
        this.rangeEnd = dateStr(today);
        this.cmpCustomA = { start: dateStr(startOfMonth(addMonths(today, -1))), end: dateStr(endOfMonth(addMonths(today, -1))) };
        this.cmpCustomB = { start: dateStr(startOfMonth(today)), end: dateStr(today) };

        this.fetchData();
        this.fetchCompare();

        this.$watch('period', () => { this.syncCustomRange(); this.fetchData(); });
        this.$watch('rangeStart', () => { if (this.period === 'rentang') this.fetchData(); });
        this.$watch('rangeEnd', () => { if (this.period === 'rentang') this.fetchData(); });
        this.$watch('cmpA', () => this.fetchCompare());
        this.$watch('cmpB', () => this.fetchCompare());
        this.$watch('cmpCustomA', () => { if (this.cmpA === 'rentang') this.fetchCompare(); }, { deep: true });
        this.$watch('cmpCustomB', () => { if (this.cmpB === 'rentang') this.fetchCompare(); }, { deep: true });
    },

    async fetchData() {
        const params = new URLSearchParams();
        params.set('period', this.period);
        if (this.period === 'rentang') {
            params.set('start', this.rangeStart);
            params.set('end', this.rangeEnd);
        }
        try {
            const res = await fetch(`/api/dashboard?${params}`);
            this.serverData = await res.json();
        } catch (e) {
            this.serverData = null;
        }

        this.$nextTick(() => {
            if (!_charts.trend) {
                this.initCharts();
            }
            this.renderCharts();
        });
    },

    async fetchCompare() {
        const params = new URLSearchParams();
        params.set('a', this.cmpA);
        params.set('b', this.cmpB);
        if (this.cmpA === 'rentang') {
            params.set('a_start', this.cmpCustomA.start);
            params.set('a_end', this.cmpCustomA.end);
        }
        if (this.cmpB === 'rentang') {
            params.set('b_start', this.cmpCustomB.start);
            params.set('b_end', this.cmpCustomB.end);
        }
        try {
            const res = await fetch(`/api/dashboard/compare?${params}`);
            this.cmpData = await res.json();
        } catch (e) {
            this.cmpData = null;
        }
    },

    syncCustomRange() {
        if (this.period === 'rentang' && !this.rangeStart) {
            const today = new Date();
            this.rangeStart = dateStr(startOfMonth(today));
            this.rangeEnd = dateStr(today);
        }
    },

    get periodLabel() {
        return this.serverData?.range?.label ?? 'Bulan Ini';
    },

    get summary() {
        return this.serverData?.summary ?? { income: 0, expense: 0, profit: 0, hasData: false };
    },

    get categoryBreakdown() {
        return this.serverData?.categoryBreakdown ?? [];
    },

    get topProducts() {
        return this.serverData?.topProducts ?? [];
    },

    get productTrendSeries() {
        return this.serverData?.productTrend ?? { labels: [], datasets: [] };
    },

    get recentTransactions() {
        return this.serverData?.recentTransactions ?? [];
    },

    periodIncome() {
        return this.serverData?.income ?? [];
    },

    periodExpense() {
        return this.serverData?.expense ?? [];
    },

    get cmpSummaryA() {
        return this.cmpData?.a ?? { income: 0, expense: 0, profit: 0 };
    },

    get cmpSummaryB() {
        return this.cmpData?.b ?? { income: 0, expense: 0, profit: 0 };
    },

    cmpDelta(field) {
        const a = this.cmpSummaryA[field];
        const b = this.cmpSummaryB[field];
        if (a === 0) {
            return 'N/A';
        }
        const pct = ((b - a) / a) * 100;

        return `${pct >= 0 ? '+' : ''}${pct.toFixed(1)}%`;
    },

    cmpDeltaClass(field) {
        const a = this.cmpSummaryA[field];
        const b = this.cmpSummaryB[field];
        if (a === 0) {
            return '';
        }

        return ((b - a) / a) >= 0 ? 'delta-up' : 'delta-down';
    },

    initCharts() {
        const baseOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { labels: { font: { family: "'IBM Plex Mono', monospace", size: 11 }, color: '#8a7a6a' } } },
        };

        if (this.$refs.trendChart) {
            _charts.trend = new Chart(this.$refs.trendChart, {
                type: 'bar',
                data: { labels: [], datasets: [
                    { label: 'Pemasukan', data: [], backgroundColor: '#f36458', borderRadius: 3 },
                    { label: 'Pengeluaran', data: [], backgroundColor: '#3d2a1a', borderRadius: 3 },
                ] },
                options: {
                    ...baseOptions,
                    onClick: (evt, elements) => this.onTrendClick(evt, elements),
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { family: "'IBM Plex Mono', monospace", size: 10 }, color: '#8a7a6a' } },
                        y: { grid: { color: '#ededed' }, ticks: { font: { family: "'IBM Plex Mono', monospace", size: 10 }, color: '#8a7a6a', callback: (v) => 'Rp ' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v) } },
                    },
                },
            });
        }

        if (this.$refs.categoryChart) {
            _charts.category = new Chart(this.$refs.categoryChart, {
                type: 'doughnut',
                data: { labels: [], datasets: [{ data: [], backgroundColor: CATEGORY_COLORS, borderColor: '#fff', borderWidth: 2 }] },
                options: {
                    ...baseOptions,
                    cutout: '62%',
                    plugins: { legend: { position: 'right', labels: { font: { family: "'IBM Plex Mono', monospace", size: 11 }, color: '#3d2a1a' } } },
                    onClick: (evt, elements) => this.onCategoryClick(evt, elements),
                },
            });
        }

        if (this.$refs.productChart) {
            _charts.product = new Chart(this.$refs.productChart, {
                type: 'line',
                data: { labels: [], datasets: [] },
                options: {
                    ...baseOptions,
                    onClick: (evt, elements) => this.onProductClick(evt, elements),
                    plugins: {
                        legend: {
                            labels: { font: { family: "'IBM Plex Mono', monospace", size: 11 }, color: '#3d2a1a', boxWidth: 12 },
                            onClick: (e, item) => this.showProductDetail(this.productTrendSeries.datasets[item.datasetIndex].productId),
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { font: { family: "'IBM Plex Mono', monospace", size: 10 }, color: '#8a7a6a' } },
                        y: { grid: { color: '#ededed' }, ticks: { font: { family: "'IBM Plex Mono', monospace", size: 10 }, color: '#8a7a6a', callback: (v) => 'Rp ' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v) } },
                    },
                },
            });
        }
    },

    renderCharts() {
        if (_charts.trend && this.serverData) {
            const trend = this.serverData.trend;
            _charts.trend.data.labels = trend.labels;
            _charts.trend.data.datasets[0].data = trend.income;
            _charts.trend.data.datasets[1].data = trend.expense;
            _charts.trend.update();
        }

        if (_charts.category) {
            const cats = this.categoryBreakdown;
            _charts.category.data.labels = cats.map((c) => c.label);
            _charts.category.data.datasets[0].data = cats.map((c) => c.value);
            _charts.category.update();
        }

        if (_charts.product) {
            const pt = this.productTrendSeries;
            _charts.product.data.labels = pt.labels;
            _charts.product.data.datasets = pt.datasets.map((d, i) => ({
                ...d,
                borderColor: PRODUCT_COLORS[i % PRODUCT_COLORS.length],
                backgroundColor: PRODUCT_COLORS[i % PRODUCT_COLORS.length],
                tension: 0.3,
                fill: false,
            }));
            _charts.product.update();
        }
    },

    bucketKeyOf(row, granularity) {
        if (granularity === 'hour') {
            return `h${new Date(row.dibuat_pada).getHours()}`;
        }
        if (granularity === 'month') {
            return row.tanggal_transaksi.slice(0, 7);
        }

        return row.tanggal_transaksi;
    },

    onTrendClick(evt, elements) {
        if (!elements.length || !this.serverData) {
            return;
        }
        const { index } = elements[0];
        const trend = this.serverData.trend;
        const bucket = trend.buckets[index];
        const granularity = trend.granularity;
        const rows = [...this.periodIncome(), ...this.periodExpense()].filter((r) => this.bucketKeyOf(r, granularity) === bucket.key);
        const items = rows.map((r) => this.transactionRow(r));
        this.showDetail(`Tren · ${bucket.label}`, this.periodLabel, ['Tanggal', 'Jenis', ' Produk/Kategori', 'Jumlah', 'Pencatat'], items, 'Belum ada transaksi pada titik ini.');
    },

    onCategoryClick(evt, elements) {
        if (!elements.length) {
            return;
        }
        const { index } = elements[0];
        const cats = this.categoryBreakdown;
        const cat = cats[index];
        if (!cat) {
            return;
        }
        const rows = this.periodExpense().filter((r) => r.id_kategori === cat.id);
        const items = rows.map((r) => this.expenseRow(r));
        this.showDetail(`Pengeluaran · ${cat.label}`, this.periodLabel, ['Tanggal', 'Nominal', 'Keterangan', 'Pencatat'], items, 'Belum ada data pengeluaran untuk kategori ini.');
    },

    showProductDetail(productId) {
        const product = this.produkMap[productId];
        const rows = this.periodIncome().filter((r) => r.id_produk === productId);
        const items = rows.map((r) => this.incomeRow(r));
        this.showDetail(`Penjualan · ${product?.nama ?? 'Produk'}`, this.periodLabel, ['Tanggal', 'Jumlah', 'Harga Satuan', 'Total', 'Pencatat'], items, 'Belum ada penjualan produk ini pada periode ini.');
    },

    transactionRow(r) {
        return r.type === 'pemasukan' || r.id_produk !== undefined ? this.incomeRow(r) : this.expenseRow(r);
    },

    incomeRow(r) {
        const product = this.produkMap[r.id_produk];

        return [
            r.tanggal_transaksi.split('-').reverse().join('/'),
            'Pemasukan',
            product?.nama ?? '—',
            `${r.jumlah} × ${rupiah(r.harga_satuan)}`,
            rupiah(r.total),
            this.penggunaMap[r.id_pengguna]?.nama ?? '—',
        ];
    },

    expenseRow(r) {
        const cat = this.kategoriPengeluaranMap[r.id_kategori];

        return [
            r.tanggal_transaksi.split('-').reverse().join('/'),
            'Pengeluaran',
            cat?.nama ?? '—',
            rupiah(r.nominal),
            r.keterangan || '—',
            this.penggunaMap[r.id_pengguna]?.nama ?? '—',
        ];
    },

    showDetail(title, eyebrow, columns, rows, emptyText) {
        this.detail = { title, eyebrow, columns, rows, emptyText };
        this.offcanvasInst ??= new Offcanvas(this.$refs.offDetail);
        this.offcanvasInst.show();
    },

    openCashflow(id) {
        this.cashflowInstances[id] ??= new Offcanvas(document.getElementById(id));
        this.cashflowInstances[id].show();
    },

    showTransaction(row) {
        const isIncome = row.type === 'pemasukan';
        const product = this.produkMap[row.id_produk];
        const cat = this.kategoriPengeluaranMap[row.id_kategori];
        const recorder = this.penggunaMap[row.id_pengguna]?.nama ?? '—';
        const fields = [
            ['Jenis', isIncome ? 'Pemasukan' : 'Pengeluaran'],
            ['Tanggal', row.tanggal_transaksi.split('-').reverse().join('/')],
            ['Nominal', rupiah(isIncome ? row.total : row.nominal)],
        ];
        if (isIncome) {
            fields.push(['Produk', product?.nama ?? '—']);
            fields.push(['Jumlah', String(row.jumlah)]);
            fields.push(['Harga Satuan', rupiah(row.harga_satuan)]);
            fields.push(['Total', rupiah(row.total)]);
        } else {
            fields.push(['Kategori', cat?.nama ?? '—']);
            fields.push(['Nominal', rupiah(row.nominal)]);
        }
        fields.push(['Keterangan', row.keterangan || '—']);
        fields.push(['Pencatat', recorder]);

        this.modalDetail = { title: isIncome ? 'Detail Pemasukan' : 'Detail Pengeluaran', fields };
        this.modalInst ??= new Modal(this.$refs.trxModal);
        this.modalInst.show();
    },

    fmt(n) {
        return rupiah(n);
    },
    };
};

Alpine.data('dashboard', dashboard);

export default dashboard;
