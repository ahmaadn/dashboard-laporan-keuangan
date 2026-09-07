@extends('layouts.app')

@section('title', 'Modal')
@section('topbar-title', 'Modal dan Hutang')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
    <div x-data="capital(@js($modal), @js($hutang), @js($penggunaById), @js($currentUser), @js($totalDebt))">

        <x-page-header eyebrow="Pembiayaan" title="Modal dan Hutang">
            <x-slot:actions>
                <x-button variant="success" icon="plus" @click="openAdd()">Catat Modal</x-button>
                <x-button variant="app" icon="cash" @click="openPayDebt()">Bayar Hutang</x-button>
            </x-slot:actions>
        </x-page-header>

        <div class="alert alert-info mb-4" role="status">
            Nilai positif dicatat sebagai setoran modal dan kas masuk. Nilai negatif membuat modal positif dan hutang dengan
            nominal yang sama, lalu ditampilkan sebagai satu baris yang dapat dibuka untuk melihat rincian hutang. Hutang
            tidak
            dihitung sebagai tambahan kas kedua kali. Pembayaran hanya mengurangi kas jika sumbernya kas usaha.
        </div>

        <div class="ld-cash-balance mb-4">
            <div>
                <span class="ld-mono-caps">Total Hutang Berjalan</span>
                <div class="ld-cash-balance__value tnum text-danger" x-text="rupiah(totalDebt)"></div>
                <p class="ld-caption mb-0">Kewajiban yang belum dilunasi. Pembayaran dana pribadi tidak mengubah saldo kas
                    usaha.</p>
            </div>
            <div class="ld-cash-balance__meta">
                <div class="ld-summary-stat">
                    <span class="ld-summary-stat__label">Hutang Aktif</span>
                    <span class="tnum" x-text="debts.filter((debt) => debt.sisa > 0).length"></span>
                </div>
            </div>
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
                            <th style="width: 2rem"></th>
                            <th>Tanggal</th>
                            <th class="text-end">Nominal</th>
                            <th>Keterangan</th>
                            <th>Pencatat</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <template x-for="row in visibleRows" :key="row.id">
                        <tbody>
                            <tr :class="[
                                                        row.dihapus_pada ? 'ld-row-deleted' : '',
                                                        row.is_hutang ? 'ld-row-expandable' : '',
                                                        expandedId === row.id ? 'ld-row-expanded' : '',
                                                    ].filter(Boolean).join(' ')" @click="toggleExpand(row)">
                                <td class="text-center"><span class="ld-mono-caps" x-show="row.is_hutang"
                                        x-text="expandedId === row.id ? '▾' : '▸'" x-cloak></span></td>
                                <td class="tnum" x-text="row.tanggal?.split('-').reverse().join('/')"></td>
                                <td class="text-end tnum fw-medium" x-text="rupiah(row.nominal)"></td>
                                <td x-text="row.keterangan || '—'"></td>
                                <td x-text="pencatatNama(row.id_pengguna)"></td>
                                <td>
                                    <span class="badge-soft-delete" x-show="row.dihapus_pada" x-cloak>Terhapus</span>
                                    <span class="badge-error-soft" x-show="!row.dihapus_pada && row.is_hutang" x-cloak>Modal
                                        dari Hutang</span>
                                    <span class="badge-success-soft" x-show="!row.dihapus_pada && !row.is_hutang"
                                        x-cloak>Modal</span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="ld-action-link ld-action-link--danger"
                                        x-show="!row.dihapus_pada" @click.stop="confirmDelete(row)">Hapus</button>
                                    <span x-show="row.dihapus_pada" class="ld-mono-caps">—</span>
                                </td>
                            </tr>
                            <tr class="ld-expand-detail" x-show="expandedId === row.id" x-cloak>
                                <td colspan="7">
                                    <div x-show="row.hutang" x-cloak>
                                        <p class="ld-mono-caps mb-2">Rincian Hutang</p>
                                        <div class="d-flex flex-wrap gap-4 mb-2">
                                            <span>Total <strong class="tnum"
                                                    x-text="rupiah(row.hutang?.nominal)"></strong></span>
                                            <span>Terbayar <strong class="tnum text-success"
                                                    x-text="rupiah(row.hutang?.terbayar)"></strong></span>
                                            <span>Sisa <strong class="tnum text-danger"
                                                    x-text="rupiah(row.hutang?.sisa)"></strong></span>
                                        </div>
                                        <table>
                                            <thead>
                                                <tr>
                                                    <th>Tanggal</th>
                                                    <th class="text-end">Nominal</th>
                                                    <th>Sumber</th>
                                                    <th>Keterangan</th>
                                                </tr>
                                            </thead>
                                            <tbody><template x-for="payment in (row.hutang?.pembayaran || [])"
                                                    :key="payment.id">
                                                    <tr>
                                                        <td class="tnum"
                                                            x-text="payment.tanggal?.split('-').reverse().join('/')"></td>
                                                        <td class="text-end tnum text-success"
                                                            x-text="rupiah(payment.nominal)"></td>
                                                        <td
                                                            x-text="payment.sumber === 'kas_usaha' ? 'Kas usaha' : 'Dana pribadi'">
                                                        </td>
                                                        <td x-text="payment.keterangan || '—'"></td>
                                                    </tr>
                                                </template></tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </template>
                </table>
            </x-data-table>
            <template x-if="visibleRows.length === 0">
                <x-empty-state icon="○" text="Belum ada catatan modal atau hutang." />
            </template>
        </x-app-card>

        <div class="ld-modal" x-show="payModalOpen" x-cloak @keydown.escape.window="payModalOpen = false"
            @click.self="payModalOpen = false" x-transition.opacity>
            <div class="ld-modal__dialog" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title">Bayar Hutang</h5><button type="button" class="btn-close"
                        @click="payModalOpen = false" aria-label="Tutup"></button>
                </div>
                <div class="ld-modal__body">
                    <div class="ld-form-grid">
                        <div class="full"><label class="form-label">Hutang <span class="req">*</span></label><select
                                class="form-select" x-model="payForm.id_hutang">
                                <option value="">— Pilih hutang —</option><template
                                    x-for="debt in debts.filter((item) => item.sisa > 0)" :key="debt.id">
                                    <option :value="debt.id" x-text="`${debt.tanggal} · sisa ${rupiah(debt.sisa)}`">
                                    </option>
                                </template>
                            </select></div>
                        <div><label class="form-label">Tanggal <span class="req">*</span></label><input type="date"
                                class="form-control" :max="today" x-model="payForm.tanggal"></div>
                        <div><label class="form-label">Nominal <span class="req">*</span></label><input type="text"
                                inputmode="numeric" class="form-control tnum" :value="formatRupiahInput(payForm.nominal)"
                                @input="payForm.nominal = updateRupiahInput($event)">
                            <div class="ld-field-error" x-show="errors.nominal" x-text="errors.nominal"></div>
                        </div>
                        <div class="full"><label class="form-label">Sumber Pembayaran <span
                                    class="req">*</span></label><select class="form-select" x-model="payForm.sumber">
                                <option value="kas_usaha">Kas usaha</option>
                                <option value="dana_pribadi">Dana pribadi</option>
                            </select>
                            <p class="ld-caption mt-1 mb-0">Kas usaha mengurangi saldo kas; dana pribadi hanya mengurangi
                                saldo hutang.</p>
                        </div>
                        <div class="full"><label class="form-label">Keterangan</label><textarea class="form-control"
                                rows="2" x-model="payForm.keterangan"></textarea></div>
                    </div>
                </div>
                <div class="ld-modal__footer"><x-button variant="secondary" icon="close"
                        @click="payModalOpen = false">Batal</x-button><x-button variant="app" icon="check"
                        ::disabled="saving" @click="payDebt()">Simpan Pembayaran</x-button></div>
            </div>
        </div>

        {{-- Add modal --}}
        <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
            @click.self="modalOpen = false" x-transition.opacity>
            <div class="ld-modal__dialog" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title">Catat Modal / Hutang</h5>
                    <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
                </div>
                <div class="ld-modal__body">
                    <div class="ld-form-grid">
                        <div>
                            <label class="form-label">Tanggal <span class="req">*</span></label>
                            <input type="date" class="form-control" :max="today"
                                :class="errors.tanggal ? 'ld-input-invalid' : ''" x-model="form.tanggal">
                            <div class="ld-field-error" x-show="errors.tanggal" x-text="errors.tanggal"></div>
                        </div>
                        <div>
                            <label class="form-label">Nominal (Rp) <span class="req">*</span></label>
                            <input type="text" inputmode="numeric" class="form-control tnum"
                                :class="errors.nominal ? 'ld-input-invalid' : ''" :value="formatRupiahInput(form.nominal)"
                                @keydown="form.nominal = updateRupiahSign($event, form.nominal)"
                                @input="form.nominal = updateRupiahInput($event)">
                            <div class="ld-field-error" x-show="errors.nominal" x-text="errors.nominal"></div>
                            <p class="ld-caption mt-1 mb-0">Positif untuk modal; negatif untuk hutang. Nilai 0 tidak
                                diperbolehkan.</p>
                        </div>
                        <div class="full">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" rows="2" x-model="form.keterangan"
                                placeholder="mis. Setoran modal, Pembayaran hutang."></textarea>
                        </div>
                    </div>
                </div>
                <div class="ld-modal__footer">
                    <x-button variant="secondary" icon="close" @click="modalOpen = false">Batal</x-button>
                    <x-button variant="app" icon="check" ::disabled="saving" ::class="saving ? 'is-loading' : ''"
                        @click="save()">Simpan</x-button>
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
                        <strong x-text="deleteTarget?.tanggal"></strong> akan dihapus (soft delete).
                    </p>
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