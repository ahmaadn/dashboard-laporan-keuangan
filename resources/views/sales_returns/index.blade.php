@extends('layouts.app')

@section('title', 'Retur Penjualan')
@section('topbar-title', 'Retur Penjualan')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
    <div x-data="salesReturns(@js($retur), @js($currentUser))">

        <x-page-header eyebrow="Transaksi" title="Retur Penjualan">
            <x-slot:actions>
                <x-button variant="brand" icon="plus" @click="openAdd()">Catat Retur</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="alert alert-info mb-4" role="status">
            Retur penjualan otomatis dicatat sebagai <strong>pengurang pendapatan</strong> (Pendapatan Bersih = Penjualan −
            Retur).
            Stok barang yang diretur akan dikembalikan.
        </div>

        <x-app-card class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                <input type="search" class="form-control" style="max-width: 320px"
                    placeholder="Cari tanggal, penjualan, alasan…" x-model="search">
                <span class="ld-mono-caps" x-text="visibleRows.length + ' retur'"></span>
            </div>

            <x-data-table>
                <table class="ld-data-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Penjualan Asal</th>
                            <th>Produk</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Nominal Retur</th>
                            <th>Alasan</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in visibleRows" :key="row.id">
                            <tr :class="row.dihapus_pada ? 'ld-row-deleted' : ''">
                                <td class="tnum" x-text="row.tanggal?.split('-').reverse().join('/')"></td>
                                <td class="ld-mono-caps" x-text="'#' + row.id_penjualan"></td>
                                <td x-text="row.nama_produk || (row.id_produk ? ('Produk #' + row.id_produk) : '—')"></td>
                                <td class="text-end tnum" x-text="row.jumlah"></td>
                                <td class="text-end tnum fw-medium text-danger" x-text="rupiah(row.nominal_retur)"></td>
                                <td x-text="row.alasan || '—'"></td>
                                <td>
                                    <span class="badge-soft-delete" x-show="row.dihapus_pada" x-cloak>Terhapus</span>
                                    <span class="badge-success-soft" x-show="!row.dihapus_pada" x-cloak>Aktif</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="ld-action-link ld-action-link--danger"
                                        x-show="!row.dihapus_pada && isAdmin" @click="confirmDelete(row)">Hapus</button>
                                    <span x-show="row.dihapus_pada || !isAdmin" class="ld-mono-caps">—</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </x-data-table>
            <template x-if="visibleRows.length === 0">
                <x-empty-state icon="○" text="Belum ada retur penjualan." />
            </template>
        </x-app-card>

        {{-- Add modal --}}
        <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
            @click.self="modalOpen = false" x-transition.opacity>
            <div class="ld-modal__dialog" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title">Catat Retur Penjualan</h5>
                    <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
                </div>
                <div class="ld-modal__body">
                    <div class="ld-form-grid">
                        <div class="full">
                            <label class="form-label">Penjualan Asal <span class="req">*</span></label>
                            <input type="number" min="1" class="form-control"
                                :class="errors.id_penjualan ? 'ld-input-invalid' : ''" x-model="form.id_penjualan"
                                placeholder="Masukkan ID penjualan (mis. 12)">
                            <div class="ld-field-error" x-show="errors.id_penjualan" x-text="errors.id_penjualan"></div>
                            <p class="ld-caption mt-1 mb-0">Lihat ID penjualan di halaman Pemasukan.</p>
                        </div>
                        <div>
                            <label class="form-label">Tanggal Retur <span class="req">*</span></label>
                            <input type="date" class="form-control" :max="today" :class="errors.tanggal ? 'ld-input-invalid' : ''"
                                x-model="form.tanggal">
                            <div class="ld-field-error" x-show="errors.tanggal" x-text="errors.tanggal"></div>
                        </div>
                        <div>
                            <label class="form-label">Jumlah Diretur <span class="req">*</span></label>
                            <input type="number" min="1" step="1" class="form-control"
                                :class="errors.jumlah ? 'ld-input-invalid' : ''" x-model="form.jumlah">
                            <div class="ld-field-error" x-show="errors.jumlah" x-text="errors.jumlah"></div>
                        </div>
                        <div class="full">
                            <label class="form-label">Alasan</label>
                            <textarea class="form-control" rows="2" x-model="form.alasan"
                                placeholder="mis. Barang cacat, Ukuran tidak sesuai"></textarea>
                        </div>
                    </div>
                    <p class="ld-caption mt-2 mb-0">
                        Nilai retur otomatis dihitung di server: <code>jumlah × harga_satuan</code> dari penjualan asal.
                        Stok akan ditambah balik ke produk.
                    </p>
                </div>
                <div class="ld-modal__footer">
                    <x-button variant="secondary" icon="close" @click="modalOpen = false">Batal</x-button>
                    <x-button variant="app" icon="check" ::disabled="saving" ::class="saving ? 'is-loading' : ''" @click="save()">Simpan</x-button>
                </div>
            </div>
        </div>

        {{-- Delete confirm --}}
        <div class="ld-modal" x-show="deleteTarget" x-cloak @keydown.escape.window="deleteTarget = null"
            @click.self="deleteTarget = null" x-transition.opacity>
            <div class="ld-modal__dialog" style="max-width: 420px" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title">Hapus Retur?</h5>
                </div>
                <div class="ld-modal__body">
                    <p class="mb-0">Retur penjualan <strong>#<span x-text="deleteTarget?.id_penjualan"></span></strong>
                        sebesar <strong x-text="deleteTarget ? rupiah(deleteTarget.nominal_retur) : ''"></strong> akan
                        dihapus. Stok yang sebelumnya dikembalikan dari retur akan dikurangi kembali agar tidak
                        double-count.</p>
                </div>
                <div class="ld-modal__footer">
                    <x-button variant="secondary" icon="close" @click="deleteTarget = null">Batal</x-button>
                    <x-button variant="danger" icon="trash" @click="doDelete()">Hapus</x-button>
                </div>
            </div>
        </div>

        <div class="ld-toast" x-show="toast" x-cloak x-transition x-text="toast"></div>
    </div>
@endsection