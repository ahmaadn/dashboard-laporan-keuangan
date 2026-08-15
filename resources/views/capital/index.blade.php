@extends('layouts.app')

@section('title', 'Modal')
@section('topbar-title', 'Modal / Hutang Piutang')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
    <div x-data="capital(@js($modal), @js($penggunaById), @js($currentUser))">

        <x-page-header eyebrow="Pembiayaan" title="Modal / Hutang Piutang">
            <x-slot:actions>
                <x-button variant="success" icon="plus" @click="openAdd()">Catat Modal</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="alert alert-info mb-4" role="status">
            Nilai positif dicatat sebagai setoran modal dan kas masuk. Nilai negatif dicatat sebagai hutang/piutang dan kas keluar.
            Keduanya bukan pendapatan usaha serta tidak mengubah Laba Kotor maupun Laba Bersih.
        </div>

        <x-app-card class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                <input type="search" class="form-control" style="max-width: 320px"
                    placeholder="Cari tanggal atau keterangan…" x-model="search">
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
                                    <span class="badge-error-soft" x-show="!row.dihapus_pada && Number(row.nominal) < 0" x-cloak>Hutang/Piutang</span>
                                    <span class="badge-success-soft" x-show="!row.dihapus_pada && Number(row.nominal) > 0" x-cloak>Modal</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="ld-action-link ld-action-link--danger"
                                        x-show="!row.dihapus_pada" @click="confirmDelete(row)">Hapus</button>
                                    <span x-show="row.dihapus_pada" class="ld-mono-caps">—</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </x-data-table>
            <template x-if="visibleRows.length === 0">
                <x-empty-state icon="○" text="Belum ada catatan modal atau hutang/piutang." />
            </template>
        </x-app-card>

        {{-- Add modal --}}
        <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
            @click.self="modalOpen = false" x-transition.opacity>
            <div class="ld-modal__dialog" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title">Catat Modal / Hutang Piutang</h5>
                    <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
                </div>
                <div class="ld-modal__body">
                    <div class="ld-form-grid">
                        <div>
                            <label class="form-label">Tanggal <span class="req">*</span></label>
                            <input type="date" class="form-control" :max="today" :class="errors.tanggal ? 'ld-input-invalid' : ''"
                                x-model="form.tanggal">
                            <div class="ld-field-error" x-show="errors.tanggal" x-text="errors.tanggal"></div>
                        </div>
                        <div>
                            <label class="form-label">Nominal (Rp) <span class="req">*</span></label>
                            <input type="text" inputmode="numeric" class="form-control tnum"
                                :class="errors.nominal ? 'ld-input-invalid' : ''" :value="formatRupiahInput(form.nominal)"
                                @keydown="form.nominal = updateRupiahSign($event, form.nominal)"
                                @input="form.nominal = updateRupiahInput($event)">
                            <div class="ld-field-error" x-show="errors.nominal" x-text="errors.nominal"></div>
                            <p class="ld-caption mt-1 mb-0">Positif untuk modal; negatif untuk hutang/piutang. Nilai 0 tidak diperbolehkan.</p>
                        </div>
                        <div class="full">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" rows="2" x-model="form.keterangan"
                                placeholder="mis. Setoran modal, Pembayaran hutang, Piutang pemilik"></textarea>
                        </div>
                    </div>
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
                    <h5 class="ld-modal__title">Hapus Catatan Modal?</h5>
                </div>
                <div class="ld-modal__body">
                    <p class="mb-0">Setoran <strong x-text="deleteTarget ? rupiah(deleteTarget.nominal) : ''"></strong> pada
                        <strong x-text="deleteTarget?.tanggal"></strong> akan dihapus (soft delete).</p>
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
