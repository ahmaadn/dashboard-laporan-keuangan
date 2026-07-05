@extends('layouts.app')

@section('title', 'Dashboard')
@section('topbar-title', 'Dashboard')

@push('scripts')
    @vite(['resources/js/dashboard.js'])
@endpush

@section('content')
<div x-data="dashboard(@js($pemasukan), @js($pengeluaran), @js($produk), @js($kategoriProduk), @js($kategoriPengeluaran), @js($pengguna))">

    {{-- 8.7 Filter Periode --}}
    <div class="ld-filter-bar">
        <span class="ld-eyebrow d-none d-sm-inline">Periode</span>
        <div class="ld-segmented">
            <button type="button" :class="period === 'hari_ini' ? 'is-active' : ''" @click="period = 'hari_ini'">Hari Ini</button>
            <button type="button" :class="period === 'minggu_ini' ? 'is-active' : ''" @click="period = 'minggu_ini'">Minggu Ini</button>
            <button type="button" :class="period === 'bulan_ini' ? 'is-active' : ''" @click="period = 'bulan_ini'">Bulan Ini</button>
            <button type="button" :class="period === 'tahun_ini' ? 'is-active' : ''" @click="period = 'tahun_ini'">Tahun Ini</button>
            <button type="button" :class="period === 'rentang' ? 'is-active' : ''" @click="period = 'rentang'">Rentang</button>
        </div>
        <template x-if="period === 'rentang'">
            <div class="d-flex align-items-center gap-2 ms-2">
                <input type="date" class="form-control form-control-sm" style="max-width: 150px" x-model="rangeStart">
                <span class="ld-mono-caps">s/d</span>
                <input type="date" class="form-control form-control-sm" style="max-width: 150px" x-model="rangeEnd">
            </div>
        </template>
        <span class="ld-mono-caps ms-auto" x-text="periodLabel"></span>
    </div>

    {{-- 8.1 Ringkasan Keuangan --}}
    <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-md-4 d-flex">
            <button type="button" class="stat-card stat-card--income w-100" @click="openCashflow('offIncome')">
                <span class="stat-card__label">Total Pemasukan</span>
                <span class="stat-card__value tnum" x-text="fmt(summary.income)"></span>
                <span class="stat-card__hint">Klik untuk rincian transaksi</span>
            </button>
        </div>
        <div class="col-md-4 d-flex">
            <button type="button" class="stat-card stat-card--expense w-100" @click="openCashflow('offExpense')">
                <span class="stat-card__label">Total Pengeluaran</span>
                <span class="stat-card__value tnum" x-text="fmt(summary.expense)"></span>
                <span class="stat-card__hint">Klik untuk rincian transaksi</span>
            </button>
        </div>
        <div class="col-md-4 d-flex">
            <button type="button" class="stat-card w-100" :class="'stat-card--' + (summary.profit >= 0 ? 'profit' : 'loss')" @click="openCashflow('offProfit')">
                <span class="stat-card__label">Laba / Rugi</span>
                <span class="stat-card__value tnum" x-text="fmt(summary.profit)"></span>
                <span class="stat-card__hint" x-text="summary.profit >= 0 ? 'Surplus periode ini' : 'Defisit periode ini'"></span>
            </button>
        </div>
    </div>

    {{-- 8.2 Grafik Tren + 8.3 Analisis Pengeluaran per Kategori --}}
    <div class="ld-grid-2 mb-4">
        <x-app-card eyebrow="Tren" title="Pemasukan vs Pengeluaran">
            <div class="ld-chart-wrap" style="height: 300px">
                <canvas x-ref="trendChart"></canvas>
            </div>
            <p class="ld-caption mt-2 mb-0">Klik batang untuk melihat rincian transaksi pada titik tersebut.</p>
        </x-app-card>

        <x-app-card eyebrow="Pengeluaran" title="Analisis per Kategori">
            <div class="ld-chart-wrap ld-chart-with-empty" style="height: 300px">
                <canvas x-ref="categoryChart"></canvas>
                <div class="ld-chart-empty" x-show="categoryBreakdown.length === 0" x-cloak>
                    <span class="ld-empty__icon" aria-hidden="true">○</span>
                    <span class="ld-empty__text">Belum ada data pengeluaran pada periode ini.</span>
                </div>
            </div>
            <div class="d-flex flex-column gap-1 mt-3" x-show="categoryBreakdown.length > 0" x-cloak>
                <template x-for="c in categoryBreakdown" :key="c.id">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="ld-body-sm"><span x-text="c.label"></span></span>
                        <span class="ld-mono-caps tnum" x-text="fmt(c.value)"></span>
                    </div>
                </template>
            </div>
        </x-app-card>
    </div>

    {{-- 8.4 Tren Penjualan Produk + 8.5 Produk Terlaris --}}
    <div class="ld-grid-2 mb-4">
        <x-app-card eyebrow="Penjualan" title="Tren Penjualan Produk">
            <div class="ld-chart-wrap ld-chart-with-empty" style="height: 300px">
                <canvas x-ref="productChart"></canvas>
                <div class="ld-chart-empty" x-show="productTrendSeries.datasets.length === 0" x-cloak>
                    <span class="ld-empty__icon" aria-hidden="true">○</span>
                    <span class="ld-empty__text">Belum ada penjualan produk pada periode ini.</span>
                </div>
            </div>
            <p class="ld-caption mt-2 mb-0">Klik produk pada legenda untuk riwayat penjualan.</p>
        </x-app-card>

        <x-app-card eyebrow="Ranking" title="Produk Terlaris">
            <template x-if="topProducts.length === 0">
                <x-empty-state icon="○" text="Belum ada penjualan pada periode ini." />
            </template>
            <template x-if="topProducts.length > 0">
                <div class="d-flex flex-column gap-2">
                    <template x-for="(p, i) in topProducts" :key="p.id">
                        <button type="button" class="ld-rank-item" @click="showProductDetail(p.id)">
                            <span class="ld-rank-item__no tnum" x-text="(i + 1)"></span>
                            <span class="flex-grow-1 text-start">
                                <span class="d-block fw-medium" x-text="p.nama"></span>
                                <span class="ld-mono-caps" x-text="p.qty + ' unit terjual'"></span>
                            </span>
                            <span class="tnum fw-medium" x-text="fmt(p.total)"></span>
                        </button>
                    </template>
                </div>
            </template>
        </x-app-card>
    </div>

    {{-- 8.6 Daftar Transaksi Terkini + 8.8 Perbandingan Periode --}}
    <div class="d-flex flex-column gap-3 mb-4">
        <x-app-card eyebrow="Evaluasi" title="Perbandingan Periode">
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label">Periode A</label>
                    <select class="form-select form-select-sm" x-model="cmpA">
                        <option value="bulan_lalu">Bulan Lalu</option>
                        <option value="bulan_ini">Bulan Ini</option>
                        <option value="tahun_lalu">Tahun Lalu</option>
                        <option value="tahun_ini">Tahun Ini</option>
                        <option value="rentang">Rentang Kustom</option>
                    </select>
                    <template x-if="cmpA === 'rentang'">
                        <div class="d-flex gap-1 mt-1">
                            <input type="date" class="form-control form-control-sm" x-model="cmpCustomA.start">
                            <input type="date" class="form-control form-control-sm" x-model="cmpCustomA.end">
                        </div>
                    </template>
                </div>
                <div class="col-6">
                    <label class="form-label">Periode B</label>
                    <select class="form-select form-select-sm" x-model="cmpB">
                        <option value="bulan_ini">Bulan Ini</option>
                        <option value="bulan_lalu">Bulan Lalu</option>
                        <option value="tahun_ini">Tahun Ini</option>
                        <option value="tahun_lalu">Tahun Lalu</option>
                        <option value="rentang">Rentang Kustom</option>
                    </select>
                    <template x-if="cmpB === 'rentang'">
                        <div class="d-flex gap-1 mt-1">
                            <input type="date" class="form-control form-control-sm" x-model="cmpCustomB.start">
                            <input type="date" class="form-control form-control-sm" x-model="cmpCustomB.end">
                        </div>
                    </template>
                </div>
            </div>
            <x-data-table>
                <table class="ld-compare">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th class="text-end">Periode A</th>
                            <th class="text-end">Periode B</th>
                            <th class="text-end">Δ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Pemasukan</td>
                            <td class="text-end tnum" x-text="fmt(cmpSummaryA.income)"></td>
                            <td class="text-end tnum" x-text="fmt(cmpSummaryB.income)"></td>
                            <td class="text-end tnum" :class="cmpDeltaClass('income')" x-text="cmpDelta('income')"></td>
                        </tr>
                        <tr>
                            <td>Pengeluaran</td>
                            <td class="text-end tnum" x-text="fmt(cmpSummaryA.expense)"></td>
                            <td class="text-end tnum" x-text="fmt(cmpSummaryB.expense)"></td>
                            <td class="text-end tnum" :class="cmpDeltaClass('expense')" x-text="cmpDelta('expense')"></td>
                        </tr>
                        <tr>
                            <td>Laba/Rugi</td>
                            <td class="text-end tnum" x-text="fmt(cmpSummaryA.profit)"></td>
                            <td class="text-end tnum" x-text="fmt(cmpSummaryB.profit)"></td>
                            <td class="text-end tnum" :class="cmpDeltaClass('profit')" x-text="cmpDelta('profit')"></td>
                        </tr>
                    </tbody>
                </table>
            </x-data-table>
            <p class="ld-caption mt-2 mb-0">Δ = perubahan B terhadap A. "N/A" saat periode A bernilai 0.</p>
        </x-app-card>

        <x-app-card eyebrow="Aktivitas" title="Transaksi Terkini">
            <template x-if="recentTransactions.length === 0">
                <x-empty-state icon="○" text="Belum ada transaksi." />
            </template>
            <template x-if="recentTransactions.length > 0">
                <div class="d-flex flex-column gap-1">
                    <template x-for="r in recentTransactions" :key="r.type + '-' + r.id">
                        <button type="button" class="ld-recent-item" @click="showTransaction(r)">
                            <span class="ld-recent-item__type">
                                <span class="badge" :class="r.type === 'pemasukan' ? 'badge-success-soft' : 'badge-error-soft'" x-text="r.type === 'pemasukan' ? 'Masuk' : 'Keluar'"></span>
                            </span>
                            <span class="flex-grow-1 text-start">
                                <span class="d-block fw-medium tnum" x-text="fmt(r.amount)"></span>
                                <span class="ld-mono-caps" x-text="new Date(r.date).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) + ' · ' + (penggunaMap[r.id_pengguna]?.nama || '—')"></span>
                            </span>
                            <span class="ld-recent-item__chevron" aria-hidden="true">›</span>
                        </button>
                    </template>
                </div>
            </template>
        </x-app-card>
    </div>

    {{-- Offcanvas: rincian pemasukan (8.1) --}}
    <x-offcanvas-detail id="offIncome" eyebrow="Rincian" title="Transaksi Pemasukan">
        <template x-if="filteredIncome(range).length === 0">
            <x-empty-state icon="○" text="Belum ada transaksi pada periode ini." />
        </template>
        <template x-if="filteredIncome(range).length > 0">
            <x-data-table>
                <table class="ld-data-table">
                    <thead><tr><th>Tanggal</th><th>Produk</th><th class="text-end">Jml</th><th class="text-end">Harga</th><th class="text-end">Total</th><th>Pencatat</th></tr></thead>
                    <tbody>
                        <template x-for="r in filteredIncome(range)" :key="r.id">
                            <tr>
                                <td x-text="r.tanggal_transaksi.split('-').reverse().join('/')"></td>
                                <td x-text="produkMap[r.id_produk]?.nama || '—'"></td>
                                <td class="text-end tnum" x-text="r.jumlah"></td>
                                <td class="text-end tnum" x-text="fmt(r.harga_satuan)"></td>
                                <td class="text-end tnum" x-text="fmt(r.total)"></td>
                                <td x-text="penggunaMap[r.id_pengguna]?.nama || '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </x-data-table>
        </template>
    </x-offcanvas-detail>

    {{-- Offcanvas: rincian pengeluaran (8.1) --}}
    <x-offcanvas-detail id="offExpense" eyebrow="Rincian" title="Transaksi Pengeluaran">
        <template x-if="filteredExpense(range).length === 0">
            <x-empty-state icon="○" text="Belum ada transaksi pada periode ini." />
        </template>
        <template x-if="filteredExpense(range).length > 0">
            <x-data-table>
                <table class="ld-data-table">
                    <thead><tr><th>Tanggal</th><th>Kategori</th><th class="text-end">Nominal</th><th>Keterangan</th><th>Pencatat</th></tr></thead>
                    <tbody>
                        <template x-for="r in filteredExpense(range)" :key="r.id">
                            <tr>
                                <td x-text="r.tanggal_transaksi.split('-').reverse().join('/')"></td>
                                <td x-text="kategoriPengeluaranMap[r.id_kategori]?.nama || '—'"></td>
                                <td class="text-end tnum" x-text="fmt(r.nominal)"></td>
                                <td x-text="r.keterangan || '—'"></td>
                                <td x-text="penggunaMap[r.id_pengguna]?.nama || '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </x-data-table>
        </template>
    </x-offcanvas-detail>

    {{-- Offcanvas: rincian laba/rugi (8.1) --}}
    <x-offcanvas-detail id="offProfit" eyebrow="Rincian" title="Perhitungan Laba/Rugi">
        <div class="d-flex flex-column gap-3">
            <div class="d-flex justify-content-between">
                <span class="ld-body-sm">Total Pemasukan</span>
                <span class="tnum fw-medium" x-text="fmt(summary.income)"></span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="ld-body-sm">Total Pengeluaran</span>
                <span class="tnum fw-medium" x-text="fmt(summary.expense)"></span>
            </div>
            <hr class="my-1">
            <div class="d-flex justify-content-between">
                <span class="fw-medium">Laba/Rugi</span>
                <span class="tnum fw-bold" :class="summary.profit >= 0 ? 'text-success' : 'text-danger'" x-text="fmt(summary.profit)"></span>
            </div>
        </div>
    </x-offcanvas-detail>

    {{-- Dynamic offcanvas detail (chart click-throughs) --}}
    <div class="offcanvas offcanvas-end ld-offcanvas" tabindex="-1" x-ref="offDetail" aria-labelledby="offDetailLabel">
        <div class="offcanvas-header border-bottom">
            <div>
                <span class="ld-eyebrow d-block" x-text="detail.eyebrow"></span>
                <h5 class="offcanvas-title" id="offDetailLabel" x-text="detail.title"></h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body">
            <template x-if="detail.rows.length === 0">
                <div class="ld-empty">
                    <span class="ld-empty__icon" aria-hidden="true">○</span>
                    <span class="ld-empty__text" x-text="detail.emptyText"></span>
                </div>
            </template>
            <template x-if="detail.rows.length > 0">
                <x-data-table>
                    <table class="ld-data-table">
                        <thead>
                            <tr>
                                <template x-for="(col, i) in detail.columns" :key="i">
                                    <th x-text="col"></th>
                                </template>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, ri) in detail.rows" :key="ri">
                                <tr>
                                    <template x-for="(cell, ci) in row" :key="ci">
                                        <td x-text="cell"></td>
                                    </template>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </x-data-table>
            </template>
        </div>
    </div>

    {{-- Modal: detail transaksi terkini (8.6) --}}
    <div class="modal fade" tabindex="-1" x-ref="trxModal" aria-labelledby="trxModalLabel">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="trxModalLabel" x-text="modalDetail.title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <template x-for="(f, i) in modalDetail.fields" :key="i">
                        <div class="d-flex justify-content-between py-2 border-bottom">
                            <span class="ld-body-sm text-muted"><span x-text="f[0]"></span></span>
                            <span class="fw-medium text-end tnum" x-text="f[1]"></span>
                        </div>
                    </template>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-app-secondary btn" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
