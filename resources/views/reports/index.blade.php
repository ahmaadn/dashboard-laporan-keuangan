@extends('layouts.app')

@section('title', 'Laporan Keuangan')
@section('topbar-title', 'Laporan Keuangan')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
    <div x-data="reports()">

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
                <div class="d-flex justify-content-between text-danger">
                    <span class="ld-body-sm">− Beban Operasional</span>
                    <span class="tnum fw-medium">@rupiah($report['biayaOperasional'])</span>
                </div>
                <hr class="my-1">
                <div class="d-flex justify-content-between">
                    <span class="fw-medium">= Laba Bersih</span>
                    <span class="tnum fw-bold"
                        :class="$report['labaBersih'] >= 0 ? 'text-success' : 'text-danger'">@rupiah($report['labaBersih'])</span>
                </div>
            </div>
        </x-offcanvas-detail>

        <x-page-header eyebrow="Reporting" title="Laporan Laba Rugi">
            <x-slot:actions>
                <button type="button" class="btn btn-app-secondary btn-sm" @click="doExport('PDF')">Ekspor PDF</button>
                <button type="button" class="btn btn-app-secondary btn-sm" @click="doExport('Excel')">Ekspor Excel</button>
            </x-slot:actions>
        </x-page-header>

        <div class="ld-filter-bar">
            <span class="ld-eyebrow d-none d-sm-inline">Periode</span>
            <div class="ld-segmented">
                @foreach ($periodOptions as $value => $label)
                    <a href="?period={{ $value }}"
                        class="{{ $report['period'] === $value ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
            @if ($report['period'] === 'rentang')
                <form class="d-flex align-items-center gap-2 ms-2" method="GET" action="/reports">
                    <input type="hidden" name="period" value="rentang">
                    <input type="date" name="start" value="{{ $report['start'] }}" class="form-control form-control-sm"
                        style="max-width: 150px">
                    <span class="ld-mono-caps">s/d</span>
                    <input type="date" name="end" value="{{ $report['end'] }}" class="form-control form-control-sm"
                        style="max-width: 150px">
                    <button type="submit" class="btn btn-app-secondary btn-sm">Terapkan</button>
                </form>
            @endif
            <span class="ld-mono-caps ms-auto">{{ $report['rangeLabel'] }}</span>
        </div>

        {{-- Tangga laba rugi bertingkat sesuai REVISI_KONSEP_KEUANGAN.md Bagian 3.3 --}}
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
                <div class="stat-card stat-card--cashflow w-100" aria-label="Arus Kas Bersih (bukan laba)"
                    title="Total uang masuk (penjualan + modal) − seluruh kas keluar. Bukan laba.">
                    <span class="stat-card__label">Arus Kas Bersih</span>
                    <span class="stat-card__value tnum">@rupiah($report['arusKasBersih'])</span>
                    <span class="stat-card__hint">Kas masuk (penjualan + modal) − kas keluar</span>
                </div>
            </div>
        </div>

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
                <div class="d-flex justify-content-between text-danger"><span>− Beban Operasional</span><span
                        class="tnum">@rupiah($report['biayaOperasional'])</span></div>
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
                <p class="ld-caption mb-0">Bahan Baku keluar tercatat di kas, tetapi masuk ke beban laba rugi lewat HPP saat
                    produk terjual. Modal bukan pendapatan — hanya menambah kas masuk.</p>
            </div>
        </x-app-card>

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
                                    <th class="text-end">HPP</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end">Laba Kotor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['incomeByProduct'] as $row)
                                    <tr>
                                        <td class="fw-medium">{{ $row['nama'] }}</td>
                                        <td class="text-end tnum">{{ $row['qty'] }}</td>
                                        <td class="text-end tnum">@rupiah($row['hpp'])</td>
                                        <td class="text-end tnum fw-medium">@rupiah($row['total'])</td>
                                        <td class="text-end tnum">@rupiah($row['laba_kotor'])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </x-data-table>
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
                        <div class="col-6">
                            <div class="ld-body-sm text-muted">Online</div>
                            <div class="tnum fw-medium">@rupiah($report['incomeByChannel']['online']['total'])</div>
                            <div class="ld-mono-caps">{{ $report['incomeByChannel']['online']['count'] }} trx ·
                                {{ $report['incomeByChannel']['online']['qty'] }} unit</div>
                        </div>
                        <div class="col-6">
                            <div class="ld-body-sm text-muted">Offline</div>
                            <div class="tnum fw-medium">@rupiah($report['incomeByChannel']['offline']['total'])</div>
                            <div class="ld-mono-caps">{{ $report['incomeByChannel']['offline']['count'] }} trx ·
                                {{ $report['incomeByChannel']['offline']['qty'] }} unit</div>
                        </div>
                    </div>
                </x-app-card>

                <x-app-card eyebrow="Admin" title="Penyesuaian HPP">
                    <form method="POST" action="/reports/hpp-adjustments" class="ld-form-grid mb-3"
                        onsubmit="return submitHpp(event)">
                        @csrf
                        <div>
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm"
                                value="{{ today()->toDateString() }}" required>
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
                            <button type="submit" class="btn btn-app btn-sm">Tambah Koreksi</button>
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