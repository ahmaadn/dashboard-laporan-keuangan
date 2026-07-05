@extends('layouts.app')

@section('title', 'Data Produk')
@section('topbar-title', 'Data Produk')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
<div x-data="products(@js($produk), @js($kategoriProduk), @js($currentUser['peran'] === 'admin'))">

    <x-page-header eyebrow="Master Data" title="Data Produk">
        <x-slot:actions>
            <button type="button" class="btn btn-app" x-show="isAdmin" @click="openAdd()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Produk
            </button>
        </x-slot:actions>
    </x-page-header>

    <x-app-card>
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <input type="search" class="form-control" style="max-width: 320px" placeholder="Cari produk, SKU, kategori…" x-model="search">
            <span class="ld-mono-caps" x-text="visibleRows.length + ' produk'"></span>
        </div>

        <x-data-table>
            <table class="ld-data-table">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>SKU</th>
                        <th class="text-end">Harga</th>
                        <th>Status</th>
                        <th x-show="isAdmin" class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in visibleRows" :key="row.id">
                        <tr :class="row.dihapus_pada ? 'ld-row-deleted' : ''">
                            <td class="fw-medium" x-text="row.nama"></td>
                            <td x-text="kategoriNama(row.id_kategori)"></td>
                            <td class="ld-mono-caps" x-text="row.sku || '—'"></td>
                            <td class="text-end tnum" x-text="rupiah(row.harga)"></td>
                            <td>
                                <span class="badge-soft-delete" x-show="row.dihapus_pada" x-cloak>Terhapus</span>
                                <span class="badge-success-soft" x-show="!row.dihapus_pada && row.aktif" x-cloak>Aktif</span>
                                <span class="badge-neutral" x-show="!row.dihapus_pada && !row.aktif" x-cloak>Nonaktif</span>
                            </td>
                            <td x-show="isAdmin" class="text-end" x-cloak>
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
            <x-empty-state icon="○" text="Tidak ada produk yang cocok." />
        </template>
    </x-app-card>

    {{-- Add/Edit modal --}}
    <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false" @click.self="modalOpen = false" x-transition.opacity>
        <div class="ld-modal__dialog" x-transition>
            <div class="ld-modal__header">
                <h5 class="ld-modal__title" x-text="editingId ? 'Ubah Produk' : 'Tambah Produk'"></h5>
                <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
            </div>
            <div class="ld-modal__body">
                <div class="ld-form-grid">
                    <div class="full">
                        <label class="form-label">Nama Produk <span class="req">*</span></label>
                        <input type="text" class="form-control" :class="errors.nama ? 'ld-input-invalid' : ''" x-model="form.nama">
                        <div class="ld-field-error" x-show="errors.nama" x-text="errors.nama"></div>
                    </div>
                    <div>
                        <label class="form-label">Kategori</label>
                        <select class="form-select" x-model="form.id_kategori">
                            <option value="">— Pilih kategori —</option>
                            <template x-for="k in Object.values(kategoriMap)" :key="k.id">
                                <option :value="k.id" x-text="k.nama"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">SKU</label>
                        <input type="text" class="form-control" :class="errors.sku ? 'ld-input-invalid' : ''" x-model="form.sku" placeholder="mis. DPL-001">
                        <div class="ld-field-error" x-show="errors.sku" x-text="errors.sku"></div>
                    </div>
                    <div>
                        <label class="form-label">Harga <span class="req">*</span></label>
                        <input type="number" min="0" step="1000" class="form-control" :class="errors.harga ? 'ld-input-invalid' : ''" x-model="form.harga">
                        <div class="ld-field-error" x-show="errors.harga" x-text="errors.harga"></div>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <div class="form-check form-switch pt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="prodAktif" x-model="form.aktif">
                            <label class="form-check-label" for="prodAktif" x-text="form.aktif ? 'Aktif' : 'Nonaktif'"></label>
                        </div>
                    </div>
                    <div class="full">
                        <label class="form-label">Deskripsi</label>
                        <textarea class="form-control" rows="2" x-model="form.deskripsi"></textarea>
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
            <div class="ld-modal__header"><h5 class="ld-modal__title">Hapus Produk?</h5></div>
            <div class="ld-modal__body">
                <p class="mb-0">Produk <strong x-text="deleteTarget?.nama"></strong> akan dihapus secara soft delete. Data transaksi lama yang mereferensikan produk ini tidak akan terpengaruh.</p>
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
