@extends('layouts.app')

@section('title', 'Laporan Keuangan')
@section('topbar-title', 'Laporan Keuangan')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
<div x-data="reports()">

    <x-page-header eyebrow="Reporting" title="Laporan Keuangan">
        <x-slot:actions>
            <button type="button" class="btn btn-app-secondary btn-sm" @click="doExport('PDF')">Ekspor PDF</button>
            <button type="button" class="btn btn-app-secondary btn-sm" @click="doExport('Excel')">Ekspor Excel</button>
        </x-slot:actions>
    </x-page-header>

    {{-- Period filter (server-driven via query params) --}}
    <div class="ld-filter-bar">
        <span class="ld-eyebrow d-none d-sm-inline">Periode</span>
        <div class="ld-segmented">
            @foreach ($periodOptions as $value => $label)
                <a href="?period={{ $value }}" class="{{ $report['period'] === $value ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
        @if ($report['period'] === 'rentang')
            <form class="d-flex align-items-center gap-2 ms-2" method="GET" action="/reports">
                @csrf
                <input type="hidden" name="period" value="rentang">
                <input type="date" name="start" value="{{ $report['start'] }}" class="form-control form-control-sm" style="max-width: 150px">
                <span class="ld-mono-caps">s/d</span>
                <input type="date" name="end" value="{{ $report['end'] }}" class="form-control form-control-sm" style="max-width: 150px">
                <button type="submit" class="btn btn-app-secondary btn-sm">Terapkan</button>
            </form>
        @endif
        <span class="ld-mono-caps ms-auto">{{ $report['rangeLabel'] }}</span>
    </div>

    {{-- Summary totals --}}
    <div class="row g-3 mb-4 align-items-stretch">
        <div class="col-md-4 d-flex">
            <div class="stat-card stat-card--income w-100">
                <span class="stat-card__label">Total Pemasukan</span>
                <span class="stat-card__value tnum">@rupiah($report['totalIncome'])</span>
            </div>
        </div>
        <div class="col-md-4 d-flex">
            <div class="stat-card stat-card--expense w-100">
                <span class="stat-card__label">Total Pengeluaran</span>
                <span class="stat-card__value tnum">@rupiah($report['totalExpense'])</span>
            </div>
        </div>
        <div class="col-md-4 d-flex">
            <div class="stat-card w-100 {{ $report['profit'] >= 0 ? 'stat-card--profit' : 'stat-card--loss' }}">
                <span class="stat-card__label">Laba / Rugi</span>
                <span class="stat-card__value tnum">@rupiah($report['profit'])</span>
                <span class="stat-card__hint">{{ $report['profit'] >= 0 ? 'Surplus' : 'Defisit' }}</span>
            </div>
        </div>
    </div>

    @if (! $report['hasData'])
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
                                <th class="text-end">Jumlah Terjual</th>
                                <th class="text-end">Transaksi</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($report['incomeByProduct'] as $row)
                                <tr>
                                    <td class="fw-medium">{{ $row['nama'] }}</td>
                                    <td class="text-end tnum">{{ $row['qty'] }}</td>
                                    <td class="text-end tnum">{{ $row['count'] }}</td>
                                    <td class="text-end tnum fw-medium">@rupiah($row['total'])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-data-table>
            </x-app-card>

            <x-app-card eyebrow="Rincian" title="Pengeluaran per Kategori">
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
                                    <td class="fw-medium">{{ $row['nama'] }}</td>
                                    <td class="text-end tnum">{{ $row['count'] }}</td>
                                    <td class="text-end tnum fw-medium">@rupiah($row['total'])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </x-data-table>
            </x-app-card>
        </div>
    @endif

    <div class="ld-toast" x-show="exportToast" x-cloak x-transition x-text="exportToast"></div>
</div>
@endsection
