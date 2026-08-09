@extends('layouts.app')

@section('title', 'Kelola Stok')
@section('topbar-title', 'Kelola Stok')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
    <div x-data="stocks(@js($produk), @js($produkById), @js($currentUser))">

        <x-page-header eyebrow="Inventori" title="Kelola Stok">
            <x-slot:actions>
                <x-button variant="brand" icon="plus" @click="openAdd()">Produksi / Restok</x-button>
            </x-slot:actions>
        </x-page-header>

        <x-app-card class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                <input type="search" class="form-control" style="max-width: 320px" placeholder="Cari produk…"
                    x-model="search">
                <span class="ld-mono-caps" x-text="visibleProducts.length + ' produk'"></span>
            </div>

            <x-data-table>
                <table class="ld-data-table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>SKU</th>
                            <th class="text-end">Stok Saat Ini</th>
                            <th class="text-end">Stok Minimum</th>
                            <th class="text-end">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in visibleProducts" :key="row.id">
                            <tr :class="row.stok_rendah ? 'table-warning' : ''">
                                <td class="fw-medium" x-text="row.nama"></td>
                                <td class="ld-mono-caps" x-text="row.sku || '—'"></td>
                                <td class="text-end tnum" :class="row.stok_rendah ? 'text-danger fw-medium' : ''"
                                    x-text="row.stok"></td>
                                <td class="text-end tnum ld-mono-caps" x-text="row.stok_minimum"></td>
                                <td class="text-end">
                                    <span class="badge-error-soft" x-show="row.stok_rendah" x-cloak>Stok rendah</span>
                                    <span class="badge-success-soft" x-show="!row.stok_rendah" x-cloak>Cukup</span>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </x-data-table>

            <template x-if="visibleProducts.length === 0">
                <x-empty-state icon="○" text="Tidak ada produk." />
            </template>
        </x-app-card>

        <x-app-card eyebrow="Riwayat" title="Mutasi Stok Terbaru">
            <x-data-table>
                <table class="ld-data-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Produk</th>
                            <th>Jenis</th>
                            <th class="text-end">Jumlah</th>
                            <th>Sumber</th>
                            <th>Pencatat</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="m in mutasi" :key="m.id">
                            <tr>
                                <td class="tnum" x-text="m.tanggal"></td>
                                <td class="fw-medium" x-text="produkMap[m.id_produk] || '—'"></td>
                                <td>
                                    <span class="badge-success-soft" x-show="m.jumlah > 0" x-cloak>Masuk</span>
                                    <span class="badge-error-soft" x-show="m.jumlah < 0" x-cloak>Keluar</span>
                                </td>
                                <td class="text-end tnum" :class="m.jumlah < 0 ? 'text-danger' : 'text-success'"
                                    x-text="(m.jumlah > 0 ? '+' : '') + m.jumlah"></td>
                                <td><span class="badge-neutral" x-text="m.sumber"></span></td>
                                <td x-text="m.pencatat || '—'"></td>
                                <td x-text="m.keterangan || '—'"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </x-data-table>
            <template x-if="mutasi.length === 0">
                <x-empty-state icon="○" text="Belum ada mutasi stok." />
            </template>
        </x-app-card>

        {{-- Add restock modal --}}
        <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
            @click.self="modalOpen = false" x-transition.opacity>
            <div class="ld-modal__dialog" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title">Produksi / Restok</h5>
                    <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
                </div>
                <div class="ld-modal__body">
                    <div class="ld-form-grid">
                        <div class="full">
                            <label class="form-label">Produk <span class="req">*</span></label>
                            <select class="form-select" :class="errors.id_produk ? 'ld-input-invalid' : ''"
                                x-model="form.id_produk">
                                <option value="">— Pilih produk —</option>
                                <template x-for="p in produk" :key="p.id">
                                    <option :value="p.id" x-text="p.nama + ' · stok saat ini ' + p.stok"></option>
                                </template>
                            </select>
                            <div class="ld-field-error" x-show="errors.id_produk" x-text="errors.id_produk"></div>
                        </div>
                        <div>
                            <label class="form-label">Tanggal <span class="req">*</span></label>
                            <input type="date" class="form-control" :max="today" :class="errors.tanggal ? 'ld-input-invalid' : ''"
                                x-model="form.tanggal">
                            <div class="ld-field-error" x-show="errors.tanggal" x-text="errors.tanggal"></div>
                        </div>
                        <div>
                            <label class="form-label">Jumlah Masuk <span class="req">*</span></label>
                            <input type="number" min="1" step="1" class="form-control"
                                :class="errors.jumlah ? 'ld-input-invalid' : ''" x-model="form.jumlah">
                            <div class="ld-field-error" x-show="errors.jumlah" x-text="errors.jumlah"></div>
                        </div>
                        <div class="full">
                            <label class="form-label">Keterangan</label>
                            <input type="text" class="form-control" x-model="form.keterangan"
                                placeholder="mis. Produksi 3 Mei, Restok vendor">
                        </div>
                    </div>
                </div>
                <div class="ld-modal__footer">
                    <x-button variant="secondary" icon="close" @click="modalOpen = false">Batal</x-button>
                    <x-button variant="app" icon="check" ::disabled="saving" ::class="saving ? 'is-loading' : ''" @click="save()">Simpan</x-button>
                </div>
            </div>
        </div>

        <div class="ld-toast" x-show="toast" x-cloak x-transition x-text="toast"></div>
    </div>
@endsection