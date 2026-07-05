@extends('layouts.app')

@section('title', 'Pengeluaran')
@section('topbar-title', 'Pengeluaran')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
<div x-data="expenses(@js($pengeluaran), @js($kategoriPengeluaran), @js($penggunaById), @js($currentUser['id']))">

    <x-page-header eyebrow="Transaksi" title="Pengeluaran">
        <x-slot:actions>
            <button type="button" class="btn btn-brand" @click="openAdd()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Transaksi
            </button>
        </x-slot:actions>
    </x-page-header>

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
                                <button type="button" class="ld-action-link" x-show="!row.dihapus_pada" @click="openEdit(row)">Ubah</button>
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
                        <input type="date" class="form-control" :class="errors.tanggal_transaksi ? 'ld-input-invalid' : ''" x-model="form.tanggal_transaksi">
                        <div class="ld-field-error" x-show="errors.tanggal_transaksi" x-text="errors.tanggal_transaksi"></div>
                    </div>
                    <div class="full">
                        <label class="form-label">Nominal <span class="req">*</span></label>
                        <input type="number" min="1" step="1000" class="form-control" :class="errors.nominal ? 'ld-input-invalid' : ''" x-model="form.nominal">
                        <div class="ld-field-error" x-show="errors.nominal" x-text="errors.nominal"></div>
                    </div>
                    <div class="full">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" rows="2" x-model="form.keterangan" placeholder="mis. Pembelian rutin, restok bulanan"></textarea>
                    </div>
                </div>
            </div>
            <div class="ld-modal__footer">
                <button type="button" class="btn btn-app-secondary" @click="modalOpen = false">Batal</button>
                <button type="button" class="btn btn-app" @click="save()">Simpan</button>
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
                <button type="button" class="btn btn-app-secondary" @click="deleteTarget = null">Batal</button>
                <button type="button" class="btn btn-danger" @click="doDelete()">Hapus</button>
            </div>
        </div>
    </div>

    <div class="ld-toast" x-show="toast" x-cloak x-transition x-text="toast"></div>
</div>
@endsection
