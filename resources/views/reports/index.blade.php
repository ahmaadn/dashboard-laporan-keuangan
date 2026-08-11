@extends('layouts.app')

@section('title', 'Laporan Keuangan')
@section('topbar-title', 'Laporan Keuangan')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
    <div x-data="reports()">

        @if ($report)
        {{-- Offcanvas: rincian Pendapatan Bersih --}}
        <x-offcanvas-detail id="offPendapatanBersih" eyebrow="Rincian" title="Pendapatan Bersih">
            <div class="d-flex flex-column gap-3">
                <div class="d-flex justify-content-between">
                    <span class="ld-body-sm">Penjualan (kotor)</span>
                    <span class="tnum fw-medium">@rupiah($report['penjualan'])</span>
                </div>
                <div class="d-flex justify-content-between text-danger">
                    <span class="ld-body-sm">− Retur Penjualan</span>
                    <span class="tnum fw-medium">@rupiah($report['returTotal'])</span>
                </div>
                <hr class="my-1">
                <div class="d-flex justify-content-between">
                    <span class="fw-medium">= Pendapatan Bersih</span>
                    <span class="tnum fw-bold">@rupiah($report['pendapatanBersih'])</span>
                </div>
                <p class="ld-caption mb-0">
                    Retur penjualan adalah pengurang pendapatan (bukan beban).
                    Lihat Bagian 2.4 dokumen acuan.
                </p>
            </div>
        </x-offcanvas-detail>

        {{-- Offcanvas: rincian Laba Kotor --}}
        <x-offcanvas-detail id="offLabaKotor" eyebrow="Rincian" title="Laba Kotor">
            <div class="d-flex flex-column gap-3">
                <div class="d-flex justify-content-between">
                    <span class="ld-body-sm">Pendapatan Bersih</span>
                    <span class="tnum fw-medium">@rupiah($report['pendapatanBersih'])</span>
                </div>
                <div class="d-flex justify-content-between text-danger">
                    <span class="ld-body-sm">− HPP</span>
                    <span class="tnum fw-medium">@rupiah($report['hppPenjualan'])</span>
                </div>
                <div class="d-flex justify-content-between text-danger">
                    <span class="ld-body-sm">−/+ Penyesuaian HPP</span>
                    <span class="tnum fw-medium">@rupiah($report['hppPenyesuaianTotal'])</span>
                </div>
                <hr class="my-1">
                <div class="d-flex justify-content-between">
                    <span class="fw-medium">= Laba Kotor</span>
                    <span class="tnum fw-bold">@rupiah($report['labaKotor'])</span>
                </div>
            </div>
        </x-offcanvas-detail>

        {{-- Offcanvas: rincian Laba Bersih --}}
        <x-offcanvas-detail id="offLabaBersih" eyebrow="Rincian" title="Laba Bersih">
            <div class="d-flex flex-column gap-3">
                <div class="d-flex justify-content-between">
                    <span class="ld-body-sm">Laba Kotor</span>
                    <span class="tnum fw-medium">@rupiah($report['labaKotor'])</span>
                </div>
                @php
                    $operasionalCategories = collect($report['expenseByCategory'])->where('is_bahan_baku', false)->sortByDesc('total')->values();
                    $biayaOperasionalTotal = $operasionalCategories->sum('total');
                @endphp
                @foreach ($operasionalCategories as $cat)
                    <div class="d-flex justify-content-between text-danger"><span class="ld-body-sm">−
                            {{ $cat['nama'] }}</span><span class="tnum fw-medium">@rupiah($cat['total'])</span></div>
                @endforeach
                <div class="d-flex justify-content-between text-danger fw-medium"><span>− Total Beban
                        Operasional</span><span class="tnum">@rupiah($biayaOperasionalTotal)</span></div>
                <hr class="my-1">
                <div class="d-flex justify-content-between">
                    <span class="fw-medium">= Laba Bersih</span>
                    <span class="tnum fw-bold"
                        :class="$report['labaBersih'] >= 0 ? 'text-success' : 'text-danger'">@rupiah($report['labaBersih'])</span>
                </div>
            </div>
        </x-offcanvas-detail>

        {{-- Offcanvas: rincian Arus Kas Bersih --}}
        <x-offcanvas-detail id="offArusKas" eyebrow="Rincian" title="Arus Kas Bersih">
            <div class="d-flex flex-column gap-3">
                <div class="d-flex justify-content-between">
                    <span class="ld-body-sm">Penjualan (kotor)</span>
                    <span class="tnum fw-medium">@rupiah($report['penjualan'])</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="ld-body-sm">+ Modal / Setoran Pemilik</span>
                    <span class="tnum fw-medium">@rupiah($report['modalTotal'])</span>
                </div>
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-medium">
                    <span>= Total Kas Masuk</span>
                    <span class="tnum">@rupiah($report['arusKasMasuk'])</span>
                </div>
                <div class="d-flex justify-content-between text-danger">
                    <span class="ld-body-sm">− Retur (uang dikembalikan ke pelanggan)</span>
                    <span class="tnum fw-medium">@rupiah($report['returTotal'])</span>
                </div>
                <div class="d-flex justify-content-between text-danger">
                    <span class="ld-body-sm">− Pembelian Bahan Baku</span>
                    <span class="tnum fw-medium">@rupiah($report['pembelianBahanBaku'])</span>
                </div>
                <div class="d-flex justify-content-between text-danger">
                    <span class="ld-body-sm">− Beban Operasional</span>
                    <span class="tnum fw-medium">@rupiah($report['biayaOperasional'])</span>
                </div>
                <hr class="my-1">
                <div class="d-flex justify-content-between fw-bold">
                    <span>= Arus Kas Bersih</span>
                    <span class="tnum"
                        :class="$report['arusKasBersih'] >= 0 ? 'text-success' : 'text-danger'">@rupiah($report['arusKasBersih'])</span>
                </div>
                <p class="ld-caption mb-0">
                    Arus kas menghitung uang riil keluar-masuk (termasuk modal). Bukan laba. Retur tetap dihitung sebagai kas keluar karena uang dikembalikan ke pelanggan.
                </p>
            </div>
        </x-offcanvas-detail>
        @endif

        <x-page-header eyebrow="Reporting" title="Laporan Keuangan">
            <x-slot:actions>
                <x-button variant="secondary" size="sm" icon="download" :disabled="! $report" @click="doExport('PDF')">Ekspor PDF</x-button>
                <x-button variant="secondary" size="sm" icon="download" :disabled="! $report" @click="doExport('Excel')">Ekspor Excel</x-button>
            </x-slot:actions>
        </x-page-header>

        @php
            $activePeriod = $report['period'] ?? request()->query('period', 'bulan_ini');
            $filterStart = $report['start'] ?? request()->query('start');
            $filterEnd = $report['end'] ?? request()->query('end');
        @endphp

        <div class="ld-filter-bar">
            <span class="ld-eyebrow d-none d-sm-inline">Periode</span>
            <div class="ld-segmented">
                @foreach ($periodOptions as $value => $label)
                    <a href="?period={{ $value }}"
                        class="{{ $activePeriod === $value ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            @if ($activePeriod === 'rentang')
                <form class="d-flex align-items-center gap-2 ms-2" method="GET" action="/reports">
                    <input type="hidden" name="period" value="rentang">
                    <input type="date" name="start" value="{{ $filterStart }}" min="{{ $tanggalMulaiUsaha }}"
                        max="{{ $tanggalHariIni }}" class="form-control form-control-sm" style="max-width: 150px">
                    <span class="ld-mono-caps">s/d</span>
                    <input type="date" name="end" value="{{ $filterEnd }}" min="{{ $tanggalMulaiUsaha }}"
                        max="{{ $tanggalHariIni }}" class="form-control form-control-sm" style="max-width: 150px">
                    <x-button variant="secondary" size="sm" type="submit" icon="filter">Terapkan</x-button>
                    <x-button variant="ghost" size="sm" icon="close" href="?period=bulan_ini"
                        title="Bersihkan rentang dan kembali ke Bulan Ini">Bersihkan</x-button>
                </form>
            @endif
            @if ($report)
                <span class="ld-mono-caps ms-auto">{{ $report['rangeLabel'] }}</span>
            @endif
        </div>

        @if ($filterError)
            <x-app-card class="mb-4">
                <div class="ld-validation-notice" role="alert">
                    <span class="ld-validation-notice__icon" aria-hidden="true">!</span>
                    <div>
                        <p class="ld-validation-notice__title mb-1">Rentang tanggal tidak valid</p>
                        <p class="mb-1">{{ $filterError }}</p>
                        <p class="ld-caption mb-0">
                            Perbaiki rentang tanggal terlebih dahulu. Maksimal {{ $maxRentangHari }} hari dan tidak boleh melebihi hari ini.
                        </p>
                    </div>
                </div>
            </x-app-card>
        @endif

        @if ($report)
        {{-- KPI Cards --}}
        <div class="row g-3 mb-4 align-items-stretch">
            <div class="col-md-6 col-xl-3 d-flex">
                <button type="button" class="stat-card stat-card--income w-100" @click="openCashflow('offPendapatanBersih')"
                    title="Penjualan − retur penjualan">
                    <span class="stat-card__label">Pendapatan Bersih</span>
                    <span class="stat-card__value tnum">@rupiah($report['pendapatanBersih'])</span>
                    <span class="stat-card__hint">Penjualan − retur · klik rincian</span>
                </button>
            </div>
            <div class="col-md-6 col-xl-3 d-flex">
                <button type="button"
                    class="stat-card w-100 {{ $report['labaKotor'] >= 0 ? 'stat-card--profit' : 'stat-card--loss' }}"
                    @click="openCashflow('offLabaKotor')" title="Pendapatan Bersih − HPP">
                    <span class="stat-card__label">Laba Kotor</span>
                    <span class="stat-card__value tnum">@rupiah($report['labaKotor'])</span>
                    <span class="stat-card__hint">Pendapatan Bersih − HPP · klik rincian</span>
                </button>
            </div>
            <div class="col-md-6 col-xl-3 d-flex">
                <button type="button"
                    class="stat-card w-100 {{ $report['labaBersih'] >= 0 ? 'stat-card--profit' : 'stat-card--loss' }}"
                    @click="openCashflow('offLabaBersih')" title="Laba Kotor − Beban Operasional">
                    <span class="stat-card__label">Laba Bersih</span>
                    <span class="stat-card__value tnum">@rupiah($report['labaBersih'])</span>
                    <span class="stat-card__hint">Setelah beban operasional · klik rincian</span>
                </button>
            </div>
            <div class="col-md-6 col-xl-3 d-flex">
                <button type="button" class="stat-card stat-card--cashflow w-100" @click="openCashflow('offArusKas')"
                    aria-label="Arus Kas Bersih (bukan laba)"
                    title="Total uang masuk (penjualan + modal) − seluruh kas keluar. Bukan laba. Klik untuk rincian.">
                    <span class="stat-card__label">Arus Kas Bersih <span class="stat-card__not-laba">◔</span></span>
                    <span class="stat-card__value tnum">@rupiah($report['arusKasBersih'])</span>
                    <span class="stat-card__hint">Kas masuk (penjualan + modal) − kas keluar · klik rincian</span>
                </button>
            </div>
        </div>
    </div>

    @php
        $operasionalCategories = collect($report['expenseByCategory'])->where('is_bahan_baku', false)->sortByDesc('total')->values();
        $biayaOperasionalTotal = $operasionalCategories->sum('total');
    @endphp
    <x-app-card class="mb-4" eyebrow="Ringkasan" title="Ringkasan Laporan Keuangan">
        <div class="d-flex flex-column gap-3">
            {{-- Pendapatan --}}
            <div class="p-3 bg-light rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-medium fs-5">Uang Masuk (Hasil Jualan)</span>
                    <span class="tnum fw-bold text-success fs-5">@rupiah($report['pendapatanBersih'])</span>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>Penjualan kotor</span>
                    <span>@rupiah($report['penjualan'])</span>
                </div>
                <div class="d-flex justify-content-between small text-danger">
                    <span>− Retur (barang dikembalikan)</span>
                    <span>@rupiah($report['returTotal'])</span>
                </div>
                <p class="ld-caption mb-0 text-muted">
                    <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i>Ini uang yang benar-benar
                    masuk ke kantong usaha dari
                    menjual produk.
                </p>
            </div>

            {{-- HPP --}}
            <div class="p-3 bg-light rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-medium fs-5">Modal Bahan Produk Terjual (HPP)</span>
                    <span class="tnum fw-bold text-danger fs-5">@rupiah($report['hpp'])</span>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>HPP produk terjual</span>
                    <span>@rupiah($report['hppPenjualan'])</span>
                </div>
                @if ($report['hppPenyesuaianTotal'] != 0)
                    <div class="d-flex justify-content-between small text-warning">
                        <span>+/− Koreksi HPP</span>
                        <span>@rupiah($report['hppPenyesuaianTotal'])</span>
                    </div>
                @endif
                <p class="ld-caption mb-0 text-muted">
                    <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i>Nilai bahan baku yang terpakai
                    untuk produk yang sudah
                    terjual (bukan semua bahan yang dibeli).
                </p>
            </div>

            {{-- Laba Kotor --}}
            <div class="p-3 bg-light rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-medium fs-5">Untung dari Jualan (Laba Kotor)</span>
                    <span class="tnum fw-bold fs-5"
                        :class="$report['labaKotor'] >= 0 ? 'text-success' : 'text-danger'">@rupiah($report['labaKotor'])</span>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>Hasil jualan bersih</span>
                    <span>@rupiah($report['pendapatanBersih'])</span>
                </div>
                <div class="d-flex justify-content-between small text-danger">
                    <span>− Modal bahan (HPP)</span>
                    <span>@rupiah($report['hpp'])</span>
                </div>
                <p class="ld-caption mb-0 text-muted">
                    <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i>Untung dari menjual produk
                    saja, belum dikurangi biaya
                    operasional (packing, marketing, kirim).
                </p>
            </div>

            {{-- Beban Operasional --}}
            <div class="p-3 bg-light rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-medium fs-5">Biaya Menjalankan Usaha</span>
                    <span class="tnum fw-bold text-danger fs-5">@rupiah($biayaOperasionalTotal)</span>
                </div>
                @foreach ($operasionalCategories as $cat)
                    <div class="d-flex justify-content-between small">
                        <span class="text-danger">− {{ $cat['nama'] }}</span>
                        <span>@rupiah($cat['total'])</span>
                    </div>
                @endforeach
                <p class="ld-caption mb-0 text-muted">
                    <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i>Biaya rutin yang termasuk
                    packing, iklan/marketing, ongkir (jika
                    ditanggung toko).
                </p>
            </div>

            {{-- Laba Bersih --}}
            <div class="p-3 rounded"
                :class="$report['labaBersih'] >= 0 ? 'bg-success-subtle border border-success' : 'bg-danger-subtle border border-danger'">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-medium fs-5">Untung Bersih Usaha (Laba Bersih)</span>
                    <span class="tnum fw-bold fs-5"
                        :class="$report['labaBersih'] >= 0 ? 'text-success' : 'text-danger'">@rupiah($report['labaBersih'])</span>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Untung dari jualan (Laba Kotor)</span>
                    <span>@rupiah($report['labaKotor'])</span>
                </div>
                <div class="d-flex justify-content-between small text-danger">
                    <span>− Biaya menjalankan usaha</span>
                    <span>@rupiah($biayaOperasionalTotal)</span>
                </div>
                <p class="ld-caption mb-0">
                    <i class="bi bi-check-circle me-1"><strong>Keterangan:</strong></i> untung usaha
                    pada periode ini.
                </p>
            </div>

            {{-- Arus Kas --}}
            <div class="p-3  border border-primary rounded">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-medium fs-5">Uang Kas Tersedia (Arus Kas Bersih)</span>
                    <span class="tnum fw-bold fs-5">@rupiah($report['arusKasBersih'])</span>
                </div>
                <div class="d-flex justify-content-between small text-muted">
                    <span>Semua uang masuk (jualan + modal)</span>
                    <span>@rupiah($report['arusKasMasuk'])</span>
                </div>
                <div class="d-flex justify-content-between small text-danger">
                    <span>− Semua uang keluar (belanja + operasional + retur)</span>
                    <span>@rupiah($report['arusKasKeluar'])</span>
                </div>
                <p class="ld-caption mb-0 text-muted">
                    <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i><strong>Beda dengan Laba
                        Bersih!</strong> Ini cek kas
                    riil. Termasuk modal masuk, belanja bahan (bukan HPP), dan retur (uang yang dikembalikan ke pelanggan).
                </p>
            </div>
        </div>
    </x-app-card>
    {{-- TAMPILAN RINGKAS (untuk pemula/awam) --}}
    <!-- <template x-if="viewMode === 'simple'">
                @php
                    $operasionalCategories = collect($report['expenseByCategory'])->where('is_bahan_baku', false)->sortByDesc('total')->values();
                    $biayaOperasionalTotal = $operasionalCategories->sum('total');
                @endphp
                <x-app-card class="mb-4" eyebrow="Ringkasan" title="Ringkasan Laporan Keuangan">
                    <div class="d-flex flex-column gap-3">
                        {{-- Pendapatan --}}
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium fs-5">Uang Masuk (Hasil Jualan)</span>
                                <span class="tnum fw-bold text-success fs-5">@rupiah($report['pendapatanBersih'])</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Penjualan kotor</span>
                                <span>@rupiah($report['penjualan'])</span>
                            </div>
                            <div class="d-flex justify-content-between small text-danger">
                                <span>− Retur (barang dikembalikan)</span>
                                <span>@rupiah($report['returTotal'])</span>
                            </div>
                            <p class="ld-caption mb-0 text-muted">
                                <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i>Ini uang yang benar-benar
                                masuk ke kantong usaha dari
                                menjual produk.
                            </p>
                        </div>

                        {{-- HPP --}}
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium fs-5">Modal Bahan Produk Terjual (HPP)</span>
                                <span class="tnum fw-bold text-danger fs-5">@rupiah($report['hpp'])</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>HPP produk terjual</span>
                                <span>@rupiah($report['hppPenjualan'])</span>
                            </div>
                            @if ($report['hppPenyesuaianTotal'] != 0)
                                <div class="d-flex justify-content-between small text-warning">
                                    <span>+/− Koreksi HPP</span>
                                    <span>@rupiah($report['hppPenyesuaianTotal'])</span>
                                </div>
                            @endif
                            <p class="ld-caption mb-0 text-muted">
                                <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i>Nilai bahan baku yang terpakai
                                untuk produk yang sudah
                                terjual (bukan semua bahan yang dibeli).
                            </p>
                        </div>

                        {{-- Laba Kotor --}}
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium fs-5">Untung dari Jualan (Laba Kotor)</span>
                                <span class="tnum fw-bold fs-5"
                                    :class="$report['labaKotor'] >= 0 ? 'text-success' : 'text-danger'">@rupiah($report['labaKotor'])</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Hasil jualan bersih</span>
                                <span>@rupiah($report['pendapatanBersih'])</span>
                            </div>
                            <div class="d-flex justify-content-between small text-danger">
                                <span>− Modal bahan (HPP)</span>
                                <span>@rupiah($report['hpp'])</span>
                            </div>
                            <p class="ld-caption mb-0 text-muted">
                                <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i>Untung dari menjual produk
                                saja, belum dikurangi biaya
                                operasional (packing, marketing, kirim).
                            </p>
                        </div>

                        {{-- Beban Operasional --}}
                        <div class="p-3 bg-light rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium fs-5">Biaya Menjalankan Usaha</span>
                                <span class="tnum fw-bold text-danger fs-5">@rupiah($biayaOperasionalTotal)</span>
                            </div>
                            @foreach ($operasionalCategories as $cat)
                                <div class="d-flex justify-content-between small">
                                    <span class="text-danger">− {{ $cat['nama'] }}</span>
                                    <span>@rupiah($cat['total'])</span>
                                </div>
                            @endforeach
                            <p class="ld-caption mb-0 text-muted">
                                <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i>Biaya rutin yang termasuk
                                packing, iklan/marketing, ongkir (jika
                                ditanggung toko).
                            </p>
                        </div>

                        {{-- Laba Bersih --}}
                        <div class="p-3 rounded"
                            :class="$report['labaBersih'] >= 0 ? 'bg-success-subtle border border-success' : 'bg-danger-subtle border border-danger'">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium fs-5">Untung Bersih Usaha (Laba Bersih)</span>
                                <span class="tnum fw-bold fs-5"
                                    :class="$report['labaBersih'] >= 0 ? 'text-success' : 'text-danger'">@rupiah($report['labaBersih'])</span>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>Untung dari jualan (Laba Kotor)</span>
                                <span>@rupiah($report['labaKotor'])</span>
                            </div>
                            <div class="d-flex justify-content-between small text-danger">
                                <span>− Biaya menjalankan usaha</span>
                                <span>@rupiah($biayaOperasionalTotal)</span>
                            </div>
                            <p class="ld-caption mb-0">
                                <i class="bi bi-check-circle me-1"><strong>Keterangan:</strong></i> untung usaha
                                pada periode ini.
                            </p>
                        </div>

                        {{-- Arus Kas --}}
                        <div class="p-3  border border-primary rounded">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-medium fs-5">Uang Kas Tersedia (Arus Kas Bersih)</span>
                                <span class="tnum fw-bold fs-5">@rupiah($report['arusKasBersih'])</span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Semua uang masuk (jualan + modal)</span>
                                <span>@rupiah($report['arusKasMasuk'])</span>
                            </div>
                            <div class="d-flex justify-content-between small text-danger">
                                <span>− Semua uang keluar (belanja + operasional)</span>
                                <span>@rupiah($report['arusKasKeluar'])</span>
                            </div>
                            <p class="ld-caption mb-0 text-muted">
                                <i class="bi bi-info-circle me-1"><strong>Keterangan:</strong></i><strong>Beda dengan Laba
                                    Bersih!</strong> Ini cek kas
                                riil. Termasuk modal masuk, belanja bahan (bukan HPP).
                            </p>
                        </div>
                    </div>
                </x-app-card>
            </template> -->

    {{-- TAMPILAN RINCI (accounting style) --}}
    <!-- <template x-if="viewMode === 'detail'">
            <x-app-card class="mb-4" eyebrow="Rincian" title="Struktur Laba Rugi (Bertingkat)">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between"><span>Penjualan (kotor)</span><span
                            class="tnum">@rupiah($report['penjualan'])</span></div>
                    <div class="d-flex justify-content-between text-danger"><span>− Retur Penjualan</span><span
                            class="tnum">@rupiah($report['returTotal'])</span></div>
                    <div class="d-flex justify-content-between fw-medium"><span>= Pendapatan Bersih</span><span
                            class="tnum">@rupiah($report['pendapatanBersih'])</span></div>
                    <div class="d-flex justify-content-between text-danger"><span>− HPP (terjual)</span><span
                            class="tnum">@rupiah($report['hppPenjualan'])</span></div>
                    <div class="d-flex justify-content-between text-danger"><span>−/+ Penyesuaian HPP</span><span
                            class="tnum">@rupiah($report['hppPenyesuaianTotal'])</span></div>
                    <div class="d-flex justify-content-between fw-medium"><span>= Laba Kotor</span><span
                            class="tnum">@rupiah($report['labaKotor'])</span></div>
                    {{-- Breakdown Beban Operasional (non bahan baku) --}}
                    @php
                        $operasionalCategories = collect($report['expenseByCategory'])->where('is_bahan_baku', false)->sortByDesc('total')->values();
                        $biayaOperasionalTotal = $operasionalCategories->sum('total');
                    @endphp
                    @foreach ($operasionalCategories as $cat)
                        <div class="d-flex justify-content-between text-danger ps-4"><span>− {{ $cat['nama'] }}</span><span
                                class="tnum">@rupiah($cat['total'])</span></div>
                    @endforeach
                    <div class="d-flex justify-content-between text-danger fw-medium"><span>− Total Beban
                            Operasional</span><span class="tnum">@rupiah($biayaOperasionalTotal)</span></div>
                    <div class="d-flex justify-content-between fw-bold"><span>= Laba Bersih</span><span
                            class="tnum">@rupiah($report['labaBersih'])</span></div>
                    <hr>
                    <div class="d-flex justify-content-between"><span>Pengeluaran Kas (semua)</span><span
                            class="tnum">@rupiah($report['pengeluaranKas'])</span></div>
                    <div class="d-flex justify-content-between"><span>· Pembelian Bahan Baku</span><span
                            class="tnum text-muted">@rupiah($report['pembelianBahanBaku'])</span></div>
                    <div class="d-flex justify-content-between"><span>· Beban Operasional</span><span
                            class="tnum text-muted">@rupiah($report['biayaOperasional'])</span></div>
                    <hr>
                    <div class="d-flex justify-content-between"><span>Modal / Setoran Pemilik (kas masuk)</span><span
                            class="tnum">@rupiah($report['modalTotal'])</span></div>
                    <div class="d-flex justify-content-between"><span>Kas masuk total</span><span
                            class="tnum">@rupiah($report['arusKasMasuk'])</span></div>
                    <div class="d-flex justify-content-between fw-medium"><span>= Arus Kas Bersih <span
                                class="ld-mono-caps text-muted">(bukan laba)</span></span><span
                            class="tnum">@rupiah($report['arusKasBersih'])</span></div>
                    <p class="ld-caption mb-0">Bahan Baku keluar tercatat di kas, tetapi masuk ke beban laba rugi lewat HPP
                        saat
                        produk terjual. Modal bukan pendapatan — hanya menambah kas masuk.</p>
                </div>
            </x-app-card>
        </template> -->

    @if (!$report['hasData'])
        <x-app-card>
            <x-empty-state icon="○" text="Belum ada transaksi pada periode ini." />
        </x-app-card>
    @else
        <div class="ld-grid-2 mb-4">
            <x-app-card eyebrow="Rincian" title="Pemasukan per Produk">
                <x-data-table>
                    <table class="ld-data-table">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Retur</th>
                                <th class="text-end">HPP</th>
                                <th class="text-end">Total Bersih</th>
                                <th class="text-end">Laba Kotor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['incomeByProduct'] as $row)
                                <tr>
                                    <td class="fw-medium">{{ $row['nama'] }}</td>
                                    <td class="text-end tnum">
                                        {{ $row['net_qty'] ?? $row['qty'] }}
                                        @if (($row['retur_qty'] ?? 0) > 0)
                                            <span class="ld-caption text-muted d-block">dari {{ $row['qty'] }}</span>
                                        @endif
                                    </td>
                                    <td class="text-end tnum {{ ($row['retur'] ?? 0) > 0 ? 'text-danger' : 'text-muted' }}">
                                        @if (($row['retur'] ?? 0) > 0)
                                            −@rupiah($row['retur'])
                                            <span class="ld-caption d-block">{{ $row['retur_qty'] ?? 0 }} unit</span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="text-end tnum">@rupiah($row['hpp'])</td>
                                    <td class="text-end tnum fw-medium">@rupiah($row['net_total'] ?? $row['total'])</td>
                                    <td class="text-end tnum">@rupiah($row['laba_kotor'])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-data-table>
                <p class="ld-caption mt-2 mb-0">Qty dan total sudah dikurangi retur pada periode ini.</p>
            </x-app-card>

            <x-app-card eyebrow="Rincian" title="Biaya Operasional per Kategori">
                <x-data-table>
                    <table class="ld-data-table">
                        <thead>
                            <tr>
                                <th>Kategori</th>
                                <th class="text-end">Transaksi</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['expenseByCategory'] as $row)
                                <tr>
                                    <td class="fw-medium">
                                        {{ $row['nama'] }}
                                        @if (!empty($row['is_bahan_baku']))
                                            <span class="badge-neutral">Bahan Baku (kas)</span>
                                        @endif
                                    </td>
                                    <td class="text-end tnum">{{ $row['count'] }}</td>
                                    <td class="text-end tnum fw-medium">@rupiah($row['total'])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-data-table>
            </x-app-card>
        </div>

        <div class="ld-grid-2 mb-4">
            <x-app-card eyebrow="Kanal" title="Online vs Offline">
                <div class="row g-2">
                    @foreach (['online' => 'Online', 'offline' => 'Offline'] as $channelKey => $channelLabel)
                        @php $channel = $report['incomeByChannel'][$channelKey]; @endphp
                        <div class="col-6">
                            <div class="ld-badge-channel ld-badge-channel--{{ $channelKey }}">{{ $channelLabel }}</div>
                            <div class="tnum fw-medium mt-1">@rupiah($channel['net_total'])</div>
                            <div class="ld-mono-caps">{{ $channel['count'] }} trx ·
                                {{ $channel['qty'] }} unit
                            </div>
                            @if ($channel['retur'] > 0)
                                <div class="ld-caption text-danger">
                                    Bruto @rupiah($channel['total']) · retur @rupiah($channel['retur'])
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <p class="ld-caption mt-2 mb-0">Nilai bersih = penjualan − retur pada periode ini.</p>
            </x-app-card>

            <x-app-card eyebrow="Admin" title="Penyesuaian HPP">
                <form method="POST" action="/reports/hpp-adjustments" class="ld-form-grid mb-3"
                    onsubmit="return submitHpp(event)">
                    @csrf
                    <div>
                        <label class="form-label">Tanggal</label>
                        <input type="date" name="tanggal" class="form-control form-control-sm"
                            value="{{ today()->toDateString() }}" max="{{ $tanggalHariIni }}" required>
                    </div>
                    <div>
                        <label class="form-label">Nominal (+/-)</label>
                        <input type="number" name="nominal" class="form-control form-control-sm" step="1000" required>
                    </div>
                    <div class="full">
                        <label class="form-label">Keterangan</label>
                        <input type="text" name="keterangan" class="form-control form-control-sm">
                    </div>
                    <div class="full">
                            <x-button variant="success" size="sm" type="submit" icon="plus">Tambah Koreksi</x-button>
                    </div>
                </form>
                @if (count($report['hppPenyesuaian']) === 0)
                    <p class="ld-caption mb-0">Belum ada koreksi HPP pada periode ini.</p>
                @else
                    <x-data-table>
                        <table class="ld-data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th class="text-end">Nominal</th>
                                    <th>Keterangan</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['hppPenyesuaian'] as $adj)
                                    <tr>
                                        <td class="tnum">{{ $adj['tanggal'] }}</td>
                                        <td class="text-end tnum">@rupiah($adj['nominal'])</td>
                                        <td>{{ $adj['keterangan'] ?: '—' }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="/reports/hpp-adjustments/{{ $adj['id'] }}"
                                                onsubmit="return deleteHpp(event, this)">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ld-action-link ld-action-link--danger">Hapus</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-data-table>
                @endif
            </x-app-card>
        </div>

        {{-- Jurnal Arus Kas: seluruh mutasi kas periode ini dengan saldo berjalan --}}
        @php $journal = $report['cashJournal']; @endphp
        <x-app-card class="mb-4" eyebrow="Kas" title="Jurnal Arus Kas Bersih">
            <div class="row g-2 mb-3">
                <div class="col-6 col-lg-3">
                    <div class="ld-summary-stat">
                        <span class="ld-summary-stat__label">Saldo Awal</span>
                        <span class="ld-summary-stat__value tnum">@rupiah($journal['saldoAwal'])</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="ld-summary-stat">
                        <span class="ld-summary-stat__label">Kas Masuk</span>
                        <span class="ld-summary-stat__value tnum text-success">@rupiah($journal['totalMasuk'])</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="ld-summary-stat">
                        <span class="ld-summary-stat__label">Kas Keluar</span>
                        <span class="ld-summary-stat__value tnum text-danger">@rupiah($journal['totalKeluar'])</span>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="ld-summary-stat">
                        <span class="ld-summary-stat__label">Saldo Akhir</span>
                        <span
                            class="ld-summary-stat__value tnum {{ $journal['saldoAkhir'] >= 0 ? 'text-success' : 'text-danger' }}">@rupiah($journal['saldoAkhir'])</span>
                    </div>
                </div>
            </div>

            @if (count($journal['entries']) === 0)
                <x-empty-state icon="○" text="Belum ada mutasi kas pada periode ini." />
            @else
                <x-data-table>
                    <table class="ld-data-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Keterangan</th>
                                <th class="text-end">Masuk</th>
                                <th class="text-end">Keluar</th>
                                <th class="text-end">Saldo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($journal['entries'] as $entry)
                                <tr>
                                    <td class="tnum">{{ \App\Support\Format::tanggal($entry['tanggal']) }}</td>
                                    <td>
                                        <span
                                            class="{{ $entry['jenis'] === 'masuk' ? 'badge-success-soft' : 'badge-error-soft' }}">{{ $entry['kategori'] }}</span>
                                    </td>
                                    <td>{{ $entry['keterangan'] }}</td>
                                    <td class="text-end tnum text-success">
                                        {{ $entry['masuk'] > 0 ? \App\Support\Format::rupiah($entry['masuk']) : '—' }}
                                    </td>
                                    <td class="text-end tnum text-danger">
                                        {{ $entry['keluar'] > 0 ? '−'.\App\Support\Format::rupiah($entry['keluar']) : '—' }}
                                    </td>
                                    <td class="text-end tnum fw-medium">@rupiah($entry['saldo'])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-data-table>
                <p class="ld-caption mt-2 mb-0">
                    Saldo berjalan dihitung dari saldo kumulatif sebelum periode. Kas masuk = penjualan + modal;
                    kas keluar = pengeluaran + retur. Ini arus kas riil, bukan laba.
                </p>
            @endif
        </x-app-card>
    @endif
    @endif

    <div class="ld-toast" x-show="exportToast" x-cloak x-transition x-text="exportToast"></div>
    </div>

    <script>
        async function submitHpp(e) {
            e.preventDefault();
            const form = e.target;
            const fd = new FormData(form);
            const body = Object.fromEntries(fd.entries());
            body.nominal = Number(body.nominal);
            const res = await fetch('/reports/hpp-adjustments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify(body),
            });
            if (res.ok) {
                window.location.reload();
            } else {
                const data = await res.json().catch(() => ({}));
                alert(data.message || 'Gagal menyimpan koreksi HPP.');
            }
            return false;
        }
        async function deleteHpp(e, form) {
            e.preventDefault();
            if (!confirm('Hapus koreksi HPP ini?')) return false;
            const res = await fetch(form.action, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
            });
            if (res.ok) window.location.reload();
            return false;
        }
    </script>
@endsection