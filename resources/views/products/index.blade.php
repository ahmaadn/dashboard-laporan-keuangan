@extends('layouts.app')

@section('title', 'Data Produk')
@section('topbar-title', 'Data Produk')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
    <div x-data="products(
        @js($produk),
        @js($kategoriProduk),
        @js($currentUser['peran'] === 'admin'),
        @js(auth()->user()->can('manage', App\Models\Product::class))
    )">

        <x-page-header eyebrow="Master Data" title="Data Produk">
            <x-slot:actions>
                @can('create', App\Models\Product::class)
                    <x-button variant="success" icon="plus" @click="openAdd()">Tambah Produk</x-button>
                @endcan
            </x-slot:actions>
        </x-page-header>

        <x-app-card>
            <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
                <input type="search" class="form-control" style="max-width: 320px" placeholder="Cari produk, SKU, kategori…"
                    x-model="search">
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
                            <th class="text-end">Stok</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="row in visibleRows" :key="row.id">
                            <tr :class="row.dihapus_pada ? 'ld-row-deleted' : ''">
                                <td class="fw-medium" x-text="row.nama"></td>
                                <td x-text="kategoriNama(row.id_kategori)"></td>
                                <td class="ld-mono-caps" x-text="row.sku || '—'"></td>
                                <td class="text-end tnum" x-text="rupiah(row.harga)"></td>
                                <td class="text-end">
                                    <span class="tnum" :class="row.stok_rendah ? 'text-danger fw-medium' : ''"
                                        x-text="row.stok ?? 0"></span>
                                    <span class="badge-error-soft ms-1" x-show="row.stok_rendah && !row.dihapus_pada"
                                        x-cloak>Rendah</span>
                                </td>
                                <td>
                                    <span class="badge-soft-delete" x-show="row.dihapus_pada" x-cloak>Terhapus</span>
                                    <span class="badge-success-soft" x-show="!row.dihapus_pada && row.aktif"
                                        x-cloak>Aktif</span>
                                    <span class="badge-neutral" x-show="!row.dihapus_pada && !row.aktif"
                                        x-cloak>Nonaktif</span>
                                </td>
                                <td class="text-end" x-cloak>
                                    {{-- TIdak Aktikan dulu fitur riwayat
                                    <!-- <button type="button" class="ld-action-link ld-action-link--neutral" x-show="!row.dihapus_pada"
                                            @click="openMovements(row)">Riwayat</button> -->
                                    --}}
                                    <template x-if="canManageProducts && !row.dihapus_pada">
                                        <span>
                                            <button type="button" class="ld-action-link ld-action-link--success"
                                                @click="openStock(row, 'restok')">+
                                                Stok</button>
                                            <button type="button" class="ld-action-link ld-action-link--neutral"
                                                @click="openStock(row, 'koreksi')">Sesuaikan</button>
                                            <button type="button" class="ld-action-link ld-action-link--primary"
                                                @click="openEdit(row)">Ubah</button>
                                            <button type="button" class="ld-action-link ld-action-link--danger"
                                                @click="confirmDelete(row)">Hapus</button>
                                        </span>
                                    </template>
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
        <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false"
            @click.self="modalOpen = false" x-transition.opacity>
            <div class="ld-modal__dialog" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title" x-text="editingId ? 'Ubah Produk' : 'Tambah Produk'"></h5>
                    <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
                </div>
                <div class="ld-modal__body">
                    <div class="ld-form-grid">
                        <div class="full">
                            <label class="form-label">Nama Produk <span class="req">*</span></label>
                            <input type="text" class="form-control" :class="errors.nama ? 'ld-input-invalid' : ''"
                                x-model="form.nama">
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
                            <input type="text" class="form-control" :class="errors.sku ? 'ld-input-invalid' : ''"
                                x-model="form.sku" placeholder="mis. DPL-001">
                            <div class="ld-field-error" x-show="errors.sku" x-text="errors.sku"></div>
                        </div>
                        <div>
                            <label class="form-label">Harga Eceran <span class="req">*</span></label>
                            <input type="text" inputmode="numeric" class="form-control tnum"
                                :class="errors.harga ? 'ld-input-invalid' : ''" :value="formatRupiahInput(form.harga)"
                                @input="form.harga = updateRupiahInput($event)">
                            <div class="ld-field-error" x-show="errors.harga" x-text="errors.harga"></div>
                        </div>
                        <div>
                            <label class="form-label">Harga Modal (HPP)</label>
                            <input type="text" inputmode="numeric" class="form-control tnum"
                                :class="errors.harga_modal ? 'ld-input-invalid' : ''"
                                :value="formatRupiahInput(form.harga_modal)"
                                @input="form.harga_modal = updateRupiahInput($event)">
                            <div class="ld-field-error" x-show="errors.harga_modal" x-text="errors.harga_modal"></div>
                        </div>
                        <div>
                            <label class="form-label">Harga Grosir</label>
                            <input type="text" inputmode="numeric" class="form-control tnum"
                                :class="errors.harga_grosir ? 'ld-input-invalid' : ''"
                                :value="formatRupiahInput(form.harga_grosir)"
                                @input="form.harga_grosir = updateRupiahInput($event)" placeholder="Opsional">
                            <div class="ld-field-error" x-show="errors.harga_grosir" x-text="errors.harga_grosir"></div>
                        </div>
                        <div>
                            <label class="form-label">Min. Qty Grosir</label>
                            <input type="number" min="1" step="1" class="form-control" x-model="form.min_qty_grosir">
                        </div>
                        <div x-show="!editingId">
                            <label class="form-label">Stok Awal</label>
                            <input type="number" min="0" step="1" class="form-control" x-model="form.stok">
                        </div>
                        <div>
                            <label class="form-label">Stok Minimum</label>
                            <input type="number" min="0" step="1" class="form-control" x-model="form.stok_minimum">
                        </div>
                        <div x-show="editingId" x-cloak>
                            <label class="form-label">Status</label>
                            <div class="form-check form-switch pt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="prodAktif"
                                    x-model="form.aktif">
                                <label class="form-check-label" for="prodAktif"
                                    x-text="form.aktif ? 'Aktif' : 'Nonaktif'"></label>
                            </div>
                        </div>
                        <div class="full">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" rows="2" x-model="form.deskripsi"></textarea>
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

        {{-- Stock modal --}}
        <div class="ld-modal" x-show="stockModal" x-cloak @keydown.escape.window="stockModal = null"
            @click.self="stockModal = null" x-transition.opacity>
            <div class="ld-modal__dialog" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title" x-text="stockForm.aksi === 'restok' ? 'Tambah Stok' : 'Sesuaikan Stok'">
                    </h5>
                    <button type="button" class="btn-close" @click="stockModal = null" aria-label="Tutup"></button>
                </div>
                <div class="ld-modal__body">
                    <p class="ld-body-sm mb-3" x-text="stockModal?.nama"></p>
                    <div class="ld-form-grid">
                        <div x-show="stockForm.aksi === 'restok'">
                            <label class="form-label">Jumlah Masuk <span class="req">*</span></label>
                            <input type="number" min="1" class="form-control" x-model="stockForm.jumlah">
                            <div class="ld-field-error" x-show="stockErrors.jumlah" x-text="stockErrors.jumlah"></div>
                        </div>
                        <div x-show="stockForm.aksi === 'koreksi'">
                            <label class="form-label">Stok Baru <span class="req">*</span></label>
                            <input type="number" min="0" class="form-control" x-model="stockForm.stok_baru">
                            <div class="ld-field-error" x-show="stockErrors.stok_baru" x-text="stockErrors.stok_baru"></div>
                        </div>
                        <div class="full">
                            <label class="form-label">Keterangan</label>
                            <input type="text" class="form-control" x-model="stockForm.keterangan">
                        </div>
                    </div>
                </div>
                <div class="ld-modal__footer">
                    <x-button variant="secondary" icon="close" @click="stockModal = null">Batal</x-button>
                    <x-button variant="app" icon="check" @click="saveStock()">Simpan</x-button>
                </div>
            </div>
        </div>

        {{-- Movements offcanvas --}}
        <div class="ld-modal" x-show="movementsOpen" x-cloak @keydown.escape.window="movementsOpen = false"
            @click.self="movementsOpen = false" x-transition.opacity>
            <div class="ld-modal__dialog ld-modal__dialog--wide" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title" x-text="'Riwayat Stok · ' + (movementsProduct?.nama || '')"></h5>
                    <button type="button" class="btn-close" @click="movementsOpen = false" aria-label="Tutup"></button>
                </div>
                <div class="ld-modal__body">
                    <template x-if="movements.length === 0">
                        <p class="ld-caption mb-0">Belum ada mutasi stok.</p>
                    </template>
                    <x-data-table x-show="movements.length > 0">
                        <table class="ld-data-table">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jenis</th>
                                    <th class="text-end">Jumlah</th>
                                    <th>Sumber</th>
                                    <th>Keterangan</th>
                                    <th>Pencatat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="m in movements" :key="m.id">
                                    <tr>
                                        <td class="tnum" x-text="m.tanggal"></td>
                                        <td x-text="m.jenis"></td>
                                        <td class="text-end tnum" x-text="m.jumlah"></td>
                                        <td x-text="m.sumber"></td>
                                        <td x-text="m.keterangan || '—'"></td>
                                        <td x-text="m.pencatat || '—'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </x-data-table>
                </div>
            </div>
        </div>

        {{-- Delete confirm --}}
        <div class="ld-modal" x-show="deleteTarget" x-cloak @keydown.escape.window="deleteTarget = null"
            @click.self="deleteTarget = null" x-transition.opacity>
            <div class="ld-modal__dialog" style="max-width: 420px" x-transition>
                <div class="ld-modal__header">
                    <h5 class="ld-modal__title">Hapus Produk?</h5>
                </div>
                <div class="ld-modal__body">
                    <p class="mb-0">Produk <strong x-text="deleteTarget?.nama"></strong> akan dihapus secara soft delete.
                        Data transaksi lama yang mereferensikan produk ini tidak akan terpengaruh.</p>
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
