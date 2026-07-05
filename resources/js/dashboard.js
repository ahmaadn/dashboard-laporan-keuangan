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

function parseDate(s) {
    if (!s) return null;
    const [y, m, d] = s.split('-').map(Number);

    return new Date(y, m - 1, d);
}

function startOfDay(d) {
    const x = new Date(d);
    x.setHours(0, 0, 0, 0);

    return x;
}

function endOfDay(d) {
    const x = new Date(d);
    x.setHours(23, 59, 59, 999);

    return x;
}

function startOfWeek(d) {
    const x = startOfDay(d);
    const day = (x.getDay() + 6) % 7; // Monday = 0
    x.setDate(x.getDate() - day);

    return x;
}

function startOfMonth(d) {
    return new Date(d.getFullYear(), d.getMonth(), 1);
}

function endOfMonth(d) {
    return new Date(d.getFullYear(), d.getMonth() + 1, 0, 23, 59, 59, 999);
}

function startOfYear(d) {
    return new Date(d.getFullYear(), 0, 1);
}

function endOfYear(d) {
    return new Date(d.getFullYear(), 11, 31, 23, 59, 59, 999);
}

function addMonths(d, n) {
    return new Date(d.getFullYear(), d.getMonth() + n, 1);
}

function rupiah(n) {
    return 'Rp ' + Number(n || 0).toLocaleString('id-ID');
}

function dayLabel(d) {
    return `${d.getDate()} ${MONTHS_SHORT[d.getMonth()]}`;
}

function monthLabel(d, spanYears) {
    return spanYears ? `${MONTHS_SHORT[d.getMonth()]} ${d.getFullYear()}` : MONTHS_SHORT[d.getMonth()];
}

const dashboard = (pemasukan, pengeluaran, produk, kategoriProduk, kategoriPengeluaran, pengguna) => {
    // Chart.js instances must live outside Alpine reactivity to avoid
    // "Maximum call stack size exceeded" from Chart internal circular refs.
    const _charts = { trend: null, category: null, product: null };

    return {
    pemasukan,
    pengeluaran,
    produkMap: {},
    kategoriProdukMap: {},
    kategoriPengeluaranMap: {},
    penggunaMap: {},

    period: 'bulan_ini',
    rangeStart: '',
    rangeEnd: '',

    cmpA: 'bulan_lalu',
    cmpB: 'bulan_ini',
    cmpCustomA: { start: '', end: '' },
    cmpCustomB: { start: '', end: '' },

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

        this.$nextTick(() => {
            this.initCharts();
            this.renderCharts();
        });

        this.$watch('period', () => {
            this.syncCustomRange();
            this.renderCharts();
        });
        this.$watch('rangeStart', () => this.renderCharts());
        this.$watch('rangeEnd', () => this.renderCharts());
    },

    get periodLabel() {
        return {
            hari_ini: 'Hari Ini',
            minggu_ini: 'Minggu Ini',
            bulan_ini: 'Bulan Ini',
            tahun_ini: 'Tahun Ini',
            rentang: 'Rentang Kustom',
        }[this.period] ?? 'Bulan Ini';
    },

    syncCustomRange() {
        if (this.period === 'rentang' && !this.rangeStart) {
            const today = new Date();
            this.rangeStart = dateStr(startOfMonth(today));
            this.rangeEnd = dateStr(today);
        }
    },

    computeRange(presetKey, customStart, customEnd) {
        const today = new Date();
        switch (presetKey) {
            case 'hari_ini':
                return { start: startOfDay(today), end: endOfDay(today), granularity: 'hour' };
            case 'minggu_ini':
                return { start: startOfWeek(today), end: endOfDay(addDays(startOfWeek(today), 6)), granularity: 'day' };
            case 'bulan_ini':
                return { start: startOfMonth(today), end: endOfMonth(today), granularity: 'day' };
            case 'tahun_ini':
                return { start: startOfYear(today), end: endOfYear(today), granularity: 'month' };
            case 'bulan_lalu': {
                const m = addMonths(today, -1);

                return { start: startOfMonth(m), end: endOfMonth(m), granularity: 'day' };
            }
            case 'tahun_lalu': {
                const y = new Date(today.getFullYear() - 1, 0, 1);

                return { start: startOfYear(y), end: endOfYear(y), granularity: 'month' };
            }
            case 'rentang': {
                const s = parseDate(customStart) ?? startOfMonth(today);
                const e = parseDate(customEnd) ?? today;
                const days = Math.round((endOfDay(e) - startOfDay(s)) / 86400000);

                return { start: startOfDay(s), end: endOfDay(e), granularity: days > 31 ? 'month' : 'day' };
            }
            default:
                return { start: startOfMonth(today), end: endOfMonth(today), granularity: 'day' };
        }
    },

    get range() {
        return this.computeRange(this.period, this.rangeStart, this.rangeEnd);
    },

    inRange(rowDate, range) {
        const s = dateStr(range.start);
        const e = dateStr(range.end);

        return rowDate >= s && rowDate <= e;
    },

    filteredIncome(range) {
        return this.pemasukan.filter((r) => r.dihapus_pada === null && this.inRange(r.tanggal_transaksi, range));
    },

    filteredExpense(range) {
        return this.pengeluaran.filter((r) => r.dihapus_pada === null && this.inRange(r.tanggal_transaksi, range));
    },

    get summary() {
        const range = this.range;
        const income = this.filteredIncome(range).reduce((s, r) => s + Number(r.total), 0);
        const expense = this.filteredExpense(range).reduce((s, r) => s + Number(r.nominal), 0);

        return { income, expense, profit: income - expense, hasData: income > 0 || expense > 0 };
    },

    buildBuckets(range) {
        const buckets = [];
        const spanYears = range.start.getFullYear() !== range.end.getFullYear();

        if (range.granularity === 'hour') {
            for (let h = 0; h < 24; h++) {
                buckets.push({ key: `h${h}`, label: `${pad(h)}.00`, hour: h });
            }
        } else if (range.granularity === 'day') {
            const cur = startOfDay(range.start);
            while (cur <= range.end) {
                buckets.push({ key: dateStr(cur), label: dayLabel(cur) });
                cur.setDate(cur.getDate() + 1);
            }
        } else {
            const cur = new Date(range.start.getFullYear(), range.start.getMonth(), 1);
            const end = new Date(range.end.getFullYear(), range.end.getMonth(), 1);
            while (cur <= end) {
                buckets.push({ key: `${cur.getFullYear()}-${pad(cur.getMonth() + 1)}`, label: monthLabel(cur, spanYears) });
                cur.setMonth(cur.getMonth() + 1);
            }
        }

        return buckets;
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

    get trendSeries() {
        const range = this.range;
        const buckets = this.buildBuckets(range);
        const income = this.filteredIncome(range);
        const expense = this.filteredExpense(range);
        const incData = buckets.map((b) => income.filter((r) => this.bucketKeyOf(r, range.granularity) === b.key).reduce((s, r) => s + Number(r.total), 0));
        const expData = buckets.map((b) => expense.filter((r) => this.bucketKeyOf(r, range.granularity) === b.key).reduce((s, r) => s + Number(r.nominal), 0));

        return { labels: buckets.map((b) => b.label), buckets, income: incData, expense: expData, granularity: range.granularity };
    },

    get categoryBreakdown() {
        const range = this.range;
        const expense = this.filteredExpense(range);
        const byCat = {};
        for (const r of expense) {
            byCat[r.id_kategori] = (byCat[r.id_kategori] ?? 0) + Number(r.nominal);
        }

        return Object.entries(byCat).map(([id, value]) => ({
            id: Number(id),
            label: this.kategoriPengeluaranMap[id]?.nama ?? 'Lainnya',
            value,
        })).sort((a, b) => b.value - a.value);
    },

    get productAggregates() {
        const range = this.range;
        const income = this.filteredIncome(range);
        const byProduct = {};
        for (const r of income) {
            if (!r.id_produk) {
                continue;
            }
            const agg = byProduct[r.id_produk] ?? { id: r.id_produk, nama: this.produkMap[r.id_produk]?.nama ?? 'Tanpa produk', qty: 0, total: 0 };
            agg.qty += Number(r.jumlah);
            agg.total += Number(r.total);
            byProduct[r.id_produk] = agg;
        }

        return Object.values(byProduct).sort((a, b) => b.qty - a.qty);
    },

    get topProducts() {
        return this.productAggregates.slice(0, 5);
    },

    get productTrendSeries() {
        const range = this.range;
        const buckets = this.buildBuckets(range);
        const labels = buckets.map((b) => b.label);
        const top = this.productAggregates.slice(0, 5);
        const income = this.filteredIncome(range);
        const datasets = top.map((p, i) => ({
            label: p.nama,
            productId: p.id,
            data: buckets.map((b) => income.filter((r) => r.id_produk === p.id && this.bucketKeyOf(r, range.granularity) === b.key).reduce((s, r) => s + Number(r.total), 0)),
            borderColor: PRODUCT_COLORS[i % PRODUCT_COLORS.length],
            backgroundColor: PRODUCT_COLORS[i % PRODUCT_COLORS.length],
            tension: 0.3,
            fill: false,
        }));

        return { labels, datasets };
    },

    get recentTransactions() {
        const all = [
            ...this.pemasukan.map((r) => ({ ...r, type: 'pemasukan', amount: Number(r.total), date: r.dibuat_pada })),
            ...this.pengeluaran.map((r) => ({ ...r, type: 'pengeluaran', amount: Number(r.nominal), date: r.dibuat_pada })),
        ].filter((r) => r.dihapus_pada === null);

        return all.sort((a, b) => (a.date < b.date ? 1 : -1)).slice(0, 10);
    },

    get cmpSummaryA() {
        return this.cmpSummary(this.cmpA, this.cmpCustomA);
    },

    get cmpSummaryB() {
        return this.cmpSummary(this.cmpB, this.cmpCustomB);
    },

    cmpSummary(preset, custom) {
        const range = this.computeRange(preset, custom.start, custom.end);
        const income = this.filteredIncome(range).reduce((s, r) => s + Number(r.total), 0);
        const expense = this.filteredExpense(range).reduce((s, r) => s + Number(r.nominal), 0);

        return { income, expense, profit: income - expense };
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

    cmpPresetLabel(preset) {
        return { hari_ini: 'Hari Ini', minggu_ini: 'Minggu Ini', bulan_ini: 'Bulan Ini', tahun_ini: 'Tahun Ini', bulan_lalu: 'Bulan Lalu', tahun_lalu: 'Tahun Lalu', rentang: 'Rentang Kustom' }[preset] ?? preset;
    },

    // --- Chart initialization ---
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
        if (_charts.trend) {
            const trend = this.trendSeries;
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
            _charts.product.data.datasets = pt.datasets;
            _charts.product.update();
        }
    },

    onTrendClick(evt, elements) {
        if (!elements.length) {
            return;
        }
        const { index } = elements[0];
        const trend = this.trendSeries;
        const bucket = trend.buckets[index];
        const range = this.range;
        const rows = this.filteredIncome(range).concat(this.filteredExpense(range)).filter((r) => this.bucketKeyOf(r, range.granularity) === bucket.key);
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
        const range = this.range;
        const rows = this.filteredExpense(range).filter((r) => r.id_kategori === cat.id);
        const items = rows.map((r) => this.expenseRow(r));
        this.showDetail(`Pengeluaran · ${cat.label}`, this.periodLabel, ['Tanggal', 'Nominal', 'Keterangan', 'Pencatat'], items, 'Belum ada data pengeluaran untuk kategori ini.');
    },

    showProductDetail(productId) {
        const product = this.produkMap[productId];
        const range = this.range;
        const rows = this.filteredIncome(range).filter((r) => r.id_produk === productId);
        const items = rows.map((r) => this.incomeRow(r));
        this.showDetail(`Penjualan · ${product?.nama ?? 'Produk'}`, this.periodLabel, ['Tanggal', 'Jumlah', 'Harga Satuan', 'Total', 'Pencatat'], items, 'Belum ada penjualan produk ini pada periode ini.');
    },

    transactionRow(r) {
        return r.type === 'pemasukan' ? this.incomeRow(r) : this.expenseRow(r);
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

function addDays(d, n) {
    const x = new Date(d);
    x.setDate(x.getDate() + n);

    return x;
}

Alpine.data('dashboard', dashboard);

export default dashboard;
