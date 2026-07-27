@extends('layouts.app')

@section('title', 'Modal')
@section('topbar-title', 'Modal / Setoran Pemilik')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
<div x-data="capital(@js($modal), @js($currentUser))">

    <x-page-header eyebrow="Pembiayaan" title="Modal / Setoran Pemilik">
        <x-slot:actions>
            <button type="button" class="btn btn-brand" @click="openAdd()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y1="12"/></svg>
                Catat Setoran
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="alert alert-info mb-4" role="status">
        Setoran modal adalah suntikan dana pemilik — <strong>bukan</strong> pendapatan usaha dan tidak dihitung sebagai penjualan atau Pendapatan Bersih.
        Tercatat di Arus Kas Bersih (kas masuk) tetapi tidak mengubah Laba Kotor maupun Laba Bersih.
    </div>

    <x-app-card class="mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <input type="search" class="form-control" style="max-width: 320px" placeholder="Cari tanggal atau keterangan…" x-model="search">
            <span class="ld-mono-caps" x-text="visibleRows.length + ' catatan'"></span>
        </div>

        <x-data-table>
            <table class="ld-data-table">
                <thead>
                    <tr>
                        <th>Tanggal</th>
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
                            <td class="tnum" x-text="row.tanggal?.split('-').reverse().join('/')"></td>
                            <td class="text-end tnum fw-medium" x-text="rupiah(row.nominal)"></td>
                            <td x-text="row.keterangan || '—'"></td>
                            <td x-text="pencatatNama(row.id_pengguna)"></td>
                            <td>
                                <span class="badge-soft-delete" x-show="row.dihapus_pada" x-cloak>Terhapus</span>
                                <span class="badge-success-soft" x-show="!row.dihapus_pada" x-cloak>Aktif</span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="ld-action-link ld-action-link--danger" x-show="!row.dihapus_pada" @click="confirmDelete(row)">Hapus</button>
                                <span x-show="row.dihapus_pada" class="ld-mono-caps">—</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </x-data-table>
        <template x-if="visibleRows.length === 0">
            <x-empty-state icon="○" text="Belum ada catatan setoran modal." />
        </template>
    </x-app-card>

    {{-- Add modal --}}
    <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false" @click.self="modalOpen = false" x-transition.opacity>
        <div class="ld-modal__dialog" x-transition>
            <div class="ld-modal__header">
                <h5 class="ld-modal__title">Catat Setoran Modal</h5>
                <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
            </div>
            <div class="ld-modal__body">
                <div class="ld-form-grid">
                    <div>
                        <label class="form-label">Tanggal <span class="req">*</span></label>
                        <input type="date" class="form-control" :class="errors.tanggal ? 'ld-input-invalid' : ''" x-model="form.tanggal">
                        <div class="ld-field-error" x-show="errors.tanggal" x-text="errors.tanggal"></div>
                    </div>
                    <div>
                        <label class="form-label">Nominal (Rp) <span class="req">*</span></label>
                        <input type="number" min="1" step="1000" class="form-control" :class="errors.nominal ? 'ld-input-invalid' : ''" x-model="form.nominal">
                        <div class="ld-field-error" x-show="errors.nominal" x-text="errors.nominal"></div>
                    </div>
                    <div class="full">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" rows="2" x-model="form.keterangan" placeholder="mis. Setoran awal Mei 2026, Tambahan modal beli bahan"></textarea>
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
            <div class="ld-modal__header"><h5 class="ld-modal__title">Hapus Catatan Modal?</h5></div>
            <div class="ld-modal__body">
                <p class="mb-0">Setoran <strong x-text="deleteTarget ? rupiah(deleteTarget.nominal) : ''"></strong> pada <strong x-text="deleteTarget?.tanggal"></strong> akan dihapus (soft delete).</p>
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