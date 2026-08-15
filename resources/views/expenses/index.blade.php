@extends('layouts.app')

@section('title', 'Pengeluaran')
@section('topbar-title', 'Pengeluaran')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
<div x-data="expenses(@js($pengeluaran), @js($kategoriPengeluaran), @js($penggunaById), @js($currentUser['id']), @js($saldoKas))">

    <x-page-header eyebrow="Transaksi" title="Pengeluaran">
        <x-slot:actions>
            <x-button variant="success" icon="plus" @click="openAdd()">Tambah Transaksi</x-button>
        </x-slot:actions>
    </x-page-header>

    {{-- Saldo kas: pengeluaran tidak dapat melebihi nilai ini --}}
    <div class="ld-cash-balance" :class="saldoKas.saldo <= 0 ? 'ld-cash-balance--empty' : ''">
        <div>
            <span class="ld-mono-caps">Saldo Kas Tersedia</span>
            <div class="ld-cash-balance__value tnum" x-text="rupiah(saldoKas.saldo)"></div>
            <p class="ld-caption mb-0" x-show="saldoKas.saldo <= 0" x-cloak>
                Saldo kas habis. Catat setoran modal atau penjualan terlebih dahulu sebelum mencatat pengeluaran.
            </p>
        </div>
        <div class="ld-cash-balance__meta">
            <div class="ld-summary-stat">
                <span class="ld-summary-stat__label">Kas Masuk</span>
                <span class="tnum" x-text="rupiah(saldoKas.kasMasuk)"></span>
            </div>
            <div class="ld-summary-stat">
                <span class="ld-summary-stat__label">Kas Keluar</span>
                <span class="tnum" x-text="rupiah(saldoKas.kasKeluar)"></span>
            </div>
        </div>
    </div>

    <x-app-card>
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <input type="search" class="form-control" style="max-width: 320px" placeholder="Cari kategori, tanggal, keterangan…" x-model="search">
            <span class="ld-mono-caps" x-text="visibleRows.length + ' transaksi'"></span>
        </div>

        <x-data-table>
            <table class="ld-data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th class="text-end">Nominal</th>
                        <th>Keterangan</th>
                        <th>Pencatat</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in visibleRows" :key="row.id">
                        <tr :class="row.dihapus_pada ? 'ld-row-deleted' : ''">
                            <td class="tnum" x-text="row.tanggal_transaksi.split('-').reverse().join('/')"></td>
                            <td x-text="kategoriNama(row.id_kategori)"></td>
                            <td class="text-end tnum fw-medium" x-text="rupiah(row.nominal)"></td>
                            <td x-text="row.keterangan || '—'"></td>
                            <td x-text="pencatatNama(row.id_pengguna)"></td>
                            <td>
                                <span class="badge-soft-delete" x-show="row.dihapus_pada" x-cloak>Terhapus</span>
                                <span class="badge-success-soft" x-show="!row.dihapus_pada" x-cloak>Aktif</span>
                            </td>
                            <td class="text-end">
                                    <button type="button" class="ld-action-link ld-action-link--primary" x-show="!row.dihapus_pada" @click="openEdit(row)">Ubah</button>
                                <button type="button" class="ld-action-link ld-action-link--danger" x-show="!row.dihapus_pada" @click="confirmDelete(row)">Hapus</button>
                                <span x-show="row.dihapus_pada" class="ld-mono-caps">—</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </x-data-table>

        <template x-if="visibleRows.length === 0">
            <x-empty-state icon="○" text="Belum ada transaksi pengeluaran." />
        </template>
    </x-app-card>

    {{-- Add/Edit modal --}}
    <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false" @click.self="modalOpen = false" x-transition.opacity>
        <div class="ld-modal__dialog" x-transition>
            <div class="ld-modal__header">
                <h5 class="ld-modal__title" x-text="editingId ? 'Ubah Transaksi Pengeluaran' : 'Tambah Transaksi Pengeluaran'"></h5>
                <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
            </div>
            <div class="ld-modal__body">
                <div class="ld-form-grid">
                    <div>
                        <label class="form-label">Kategori <span class="req">*</span></label>
                        <select class="form-select" :class="errors.id_kategori ? 'ld-input-invalid' : ''" x-model="form.id_kategori">
                            <option value="">— Pilih kategori —</option>
                            <template x-for="k in kategoriPengeluaran" :key="k.id">
                                <option :value="k.id" x-text="k.nama"></option>
                            </template>
                        </select>
                        <div class="ld-field-error" x-show="errors.id_kategori" x-text="errors.id_kategori"></div>
                    </div>
                    <div>
                        <label class="form-label">Tanggal Transaksi <span class="req">*</span></label>
                        <input type="date" class="form-control" :max="today" :class="errors.tanggal_transaksi ? 'ld-input-invalid' : ''" x-model="form.tanggal_transaksi">
                        <div class="ld-field-error" x-show="errors.tanggal_transaksi" x-text="errors.tanggal_transaksi"></div>
                    </div>
                    <div class="full">
                        <label class="form-label">Nominal <span class="req">*</span></label>
                        <input type="text" inputmode="numeric" class="form-control tnum" :class="errors.nominal ? 'ld-input-invalid' : ''"
                            :value="formatRupiahInput(form.nominal)" @input="form.nominal = updateRupiahInput($event)">
                        <div class="ld-field-error" x-show="errors.nominal" x-text="errors.nominal"></div>
                        <p class="ld-caption mt-1 mb-0">
                            Saldo kas tersedia: <span class="tnum fw-medium" x-text="rupiah(saldoTersedia)"></span>
                        </p>
                        <div class="ld-field-error" x-show="melebihiSaldo" x-cloak>
                            Nominal melebihi saldo kas tersedia.
                        </div>
                    </div>
                    <div class="full">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" rows="2" x-model="form.keterangan" placeholder="mis. Pembelian rutin, restok bulanan"></textarea>
                    </div>
                </div>
            </div>
            <div class="ld-modal__footer">
                <x-button variant="secondary" icon="close" @click="modalOpen = false">Batal</x-button>
                <x-button variant="app" icon="check" ::disabled="saving || melebihiSaldo" ::class="saving ? 'is-loading' : ''" @click="save()">Simpan</x-button>
            </div>
        </div>
    </div>

    {{-- Delete confirm --}}
    <div class="ld-modal" x-show="deleteTarget" x-cloak @keydown.escape.window="deleteTarget = null" @click.self="deleteTarget = null" x-transition.opacity>
        <div class="ld-modal__dialog" style="max-width: 420px" x-transition>
            <div class="ld-modal__header"><h5 class="ld-modal__title">Hapus Transaksi?</h5></div>
            <div class="ld-modal__body">
                <p class="mb-0">Pengeluaran <strong x-text="deleteTarget ? kategoriNama(deleteTarget.id_kategori) : ''"></strong> sebesar <strong x-text="deleteTarget ? rupiah(deleteTarget.nominal) : ''"></strong> akan dihapus (soft delete).</p>
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
