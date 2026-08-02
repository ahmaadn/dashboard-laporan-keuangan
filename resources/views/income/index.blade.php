@extends('layouts.app')

@section('title', 'Pemasukan')
@section('topbar-title', 'Pemasukan')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
<div x-data="income(@js($pemasukan), @js($produkAktif), @js($produkById), @js($penggunaById), @js($currentUser['id']))">

    <x-page-header eyebrow="Transaksi" title="Pemasukan">
        <x-slot:actions>
            <button type="button" class="btn btn-brand" @click="openAdd()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Transaksi
            </button>
        </x-slot:actions>
    </x-page-header>

    <x-app-card>
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <input type="search" class="form-control" style="max-width: 320px" placeholder="Cari produk atau tanggal…" x-model="search">
            <span class="ld-mono-caps" x-text="visibleRows.length + ' transaksi'"></span>
        </div>

        <x-data-table>
            <table class="ld-data-table ld-data-table--scroll-x">
                <thead>
                    <tr>
                        <th style="width: 2rem"></th>
                        <th>No. Transaksi</th>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Produk</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-end">Harga Satuan</th>
                        <th class="text-end">Total</th>
                        <th>Pencatat</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <template x-for="row in visibleRows" :key="row.id">
                    <tbody>
                        <tr
                            :class="[
                                row.dihapus_pada ? 'ld-row-deleted' : '',
                                expandedId === row.id ? 'ld-row-expanded' : '',
                                (row.retur_history?.length || 0) > 0 ? 'ld-row-expandable' : '',
                            ].filter(Boolean).join(' ')"
                            @click="toggleExpand(row)"
                        >
                            <td class="text-center" style="width: 2rem">
                                <span
                                    class="ld-mono-caps"
                                    x-show="(row.retur_history?.length || 0) > 0"
                                    x-text="expandedId === row.id ? '▾' : '▸'"
                                    x-cloak
                                ></span>
                            </td>
                            <td class="tnum ld-mono-caps" x-text="row.nomor_transaksi || '—'"></td>
                            <td class="tnum" x-text="row.tanggal_transaksi.split('-').reverse().join('/')"></td>
                            <td>
                                <span class="badge-neutral" x-text="row.jenis_transaksi_label || (row.jenis_transaksi === 'online' ? 'Online' : 'Offline')"></span>
                            </td>
                            <td x-text="produkNama(row.id_produk)"></td>
                            <td class="text-end tnum" x-text="row.jumlah"></td>
                            <td class="text-end tnum" x-text="rupiah(row.harga_satuan)"></td>
                            <td class="text-end tnum fw-medium" x-text="rupiah(row.total)"></td>
                            <td x-text="pencatatNama(row.id_pengguna)"></td>
                            <td>
                                <span :class="statusBadgeClass(row)" x-text="statusLabel(row)" x-cloak></span>
                            </td>
                            <td class="text-end" @click.stop>
                                <button type="button" class="ld-action-link" x-show="!row.dihapus_pada" @click="openEdit(row)">Ubah</button>
                                <button type="button" class="ld-action-link ld-action-link--danger" x-show="!row.dihapus_pada" @click="confirmDelete(row)">Hapus</button>
                                <button
                                    type="button"
                                    class="ld-action-link"
                                    x-show="!row.dihapus_pada && (row.sisa_retur ?? row.jumlah) > 0"
                                    @click="openRetur(row)"
                                >Retur</button>
                                <span x-show="row.dihapus_pada" class="ld-mono-caps">—</span>
                            </td>
                        </tr>
                        <tr class="ld-expand-detail" x-show="expandedId === row.id" x-cloak>
                            <td colspan="11">
                                <p class="ld-caption mb-0" x-show="!(row.retur_history?.length > 0)">Belum ada riwayat retur.</p>
                                <div x-show="row.retur_history?.length > 0">
                                    <p class="ld-mono-caps mb-2">Riwayat Retur · sisa <span class="tnum" x-text="row.sisa_retur ?? 0"></span> dari <span class="tnum" x-text="row.jumlah"></span></p>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th class="text-end">Jumlah</th>
                                                <th class="text-end">Nominal</th>
                                                <th>Alasan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="h in (row.retur_history || [])" :key="h.id">
                                                <tr>
                                                    <td class="tnum" x-text="h.tanggal?.split('-').reverse().join('/')"></td>
                                                    <td class="text-end tnum" x-text="h.jumlah"></td>
                                                    <td class="text-end tnum text-danger" x-text="rupiah(h.nominal_retur)"></td>
                                                    <td x-text="h.alasan || '—'"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </template>
            </table>
        </x-data-table>

        <template x-if="visibleRows.length === 0">
            <x-empty-state icon="○" text="Belum ada transaksi pemasukan." />
        </template>
    </x-app-card>

    {{-- Add (kasir multi-item) / Edit (single line) modal --}}
    <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false" @click.self="modalOpen = false" x-transition.opacity>
        <div class="ld-modal__dialog" :style="editingId ? '' : 'max-width: 720px'" x-transition>
            <div class="ld-modal__header">
                <h5 class="ld-modal__title" x-text="editingId ? 'Ubah Item Pemasukan' : 'Transaksi Kasir'"></h5>
                <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
            </div>
            <div class="ld-modal__body">
                <div class="ld-form-grid">
                    <div>
                        <label class="form-label">Jenis Transaksi <span class="req">*</span></label>
                        <select class="form-select" :class="errors.jenis_transaksi ? 'ld-input-invalid' : ''" x-model="form.jenis_transaksi" @change="onPricingInputsChange()">
                            <option value="offline">Offline</option>
                            <option value="online">Online</option>
                        </select>
                        <div class="ld-field-error" x-show="errors.jenis_transaksi" x-text="errors.jenis_transaksi"></div>
                        <p class="ld-caption mt-1 mb-0">Harga grosir otomatis hanya untuk Offline ≥ jumlah minimum grosir; Online selalu eceran.</p>
                    </div>
                    <div>
                        <label class="form-label">Tanggal Transaksi <span class="req">*</span></label>
                        <input type="date" class="form-control" :class="errors.tanggal_transaksi ? 'ld-input-invalid' : ''" x-model="form.tanggal_transaksi">
                        <div class="ld-field-error" x-show="errors.tanggal_transaksi" x-text="errors.tanggal_transaksi"></div>
                    </div>

                    {{-- Edit: single product line --}}
                    <template x-if="editingId">
                        <div class="full">
                            <div class="ld-form-grid">
                                <div class="full">
                                    <label class="form-label">Produk</label>
                                    <select class="form-select" x-model="form.id_produk" @change="onProductChange()">
                                        <option value="">— Tanpa produk (lain-lain) —</option>
                                        <template x-for="p in produkAktif" :key="p.id">
                                            <option :value="p.id" x-text="p.nama + ' · ' + rupiah(p.harga) + ' · stok ' + (p.stok ?? 0)"></option>
                                        </template>
                                    </select>
                                    <p class="ld-caption mt-1 mb-0" x-show="selectedProduct()" x-cloak>
                                        Sisa stok: <span class="tnum" x-text="selectedProduct()?.stok ?? 0"></span>
                                    </p>
                                </div>
                                <div>
                                    <label class="form-label">Jumlah <span class="req">*</span></label>
                                    <input type="number" min="1" step="1" class="form-control" :class="errors.jumlah ? 'ld-input-invalid' : ''" x-model="form.jumlah" @input="onPricingInputsChange()">
                                    <div class="ld-field-error" x-show="errors.jumlah" x-text="errors.jumlah"></div>
                                </div>
                                <div>
                                    <label class="form-label">
                                        Harga Satuan <span class="req">*</span>
                                        <span class="badge-success-soft ms-1" x-show="hargaTipePreview === 'grosir'" x-cloak>Grosir</span>
                                    </label>
                                    <input type="number" min="0" step="1000" class="form-control" :class="errors.harga_satuan ? 'ld-input-invalid' : ''" x-model="form.harga_satuan" :readonly="!form.harga_manual">
                                    <div class="ld-field-error" x-show="errors.harga_satuan" x-text="errors.harga_satuan"></div>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="hargaManualEdit" x-model="form.harga_manual" @change="onPricingInputsChange()">
                                        <label class="form-check-label" for="hargaManualEdit">Ubah harga manual</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    {{-- Add: multi-product cart --}}
                    <template x-if="!editingId">
                        <div class="full">
                            <label class="form-label">Keranjang Produk <span class="req">*</span></label>
                            <div class="ld-field-error mb-2" x-show="errors.cart || errors.items" x-text="errors.cart || errors.items"></div>

                            <div class="table-responsive mb-3" x-show="cart.length > 0" x-cloak>
                                <table class="ld-data-table">
                                    <thead>
                                        <tr>
                                            <th>Produk</th>
                                            <th class="text-end" style="width: 5rem">Qty</th>
                                            <th class="text-end" style="width: 8rem">Harga</th>
                                            <th class="text-end" style="width: 8rem">Subtotal</th>
                                            <th style="width: 3rem"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(line, idx) in cart" :key="idx">
                                            <tr>
                                                <td>
                                                    <span x-text="cartLineNama(line)"></span>
                                                    <span class="badge-success-soft ms-1" x-show="!line.harga_manual && form.jenis_transaksi === 'offline' && cartLineProduct(line)?.harga_grosir && Number(line.jumlah) >= Number(cartLineProduct(line)?.min_qty_grosir || 3)" x-cloak>Grosir</span>
                                                </td>
                                                <td>
                                                    <input type="number" min="1" step="1" class="form-control form-control-sm text-end" x-model="line.jumlah" @input="onCartLinePricingChange(idx)">
                                                </td>
                                                <td>
                                                    <input type="number" min="0" step="1000" class="form-control form-control-sm text-end" x-model="line.harga_satuan" :readonly="!line.harga_manual">
                                                    <div class="form-check form-switch mt-1">
                                                        <input class="form-check-input" type="checkbox" :id="'hm'+idx" x-model="line.harga_manual" @change="onCartLinePricingChange(idx)">
                                                        <label class="form-check-label ld-caption" :for="'hm'+idx">Manual</label>
                                                    </div>
                                                </td>
                                                <td class="text-end tnum fw-medium" x-text="rupiah(cartLineSubtotal(line))"></td>
                                                <td class="text-end">
                                                    <button type="button" class="ld-action-link ld-action-link--danger" @click="removeCartLine(idx)">×</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="border rounded p-3 mb-2" style="border-color: var(--ld-hairline) !important">
                                <p class="ld-mono-caps mb-2">Tambah item</p>
                                <div class="ld-form-grid">
                                    <div class="full">
                                        <select class="form-select" x-model="cartDraft.id_produk" @change="onDraftProductChange()">
                                            <option value="">— Tanpa produk (lain-lain) —</option>
                                            <template x-for="p in availableProductsForDraft()" :key="p.id">
                                                <option :value="p.id" x-text="p.nama + ' · ' + rupiah(p.harga) + ' · stok ' + (p.stok ?? 0)"></option>
                                            </template>
                                        </select>
                                        <p class="ld-caption mt-1 mb-0" x-show="draftProduct()" x-cloak>
                                            Sisa stok: <span class="tnum" x-text="draftProduct()?.stok ?? 0"></span>
                                        </p>
                                    </div>
                                    <div>
                                        <label class="form-label">Jumlah</label>
                                        <input type="number" min="1" step="1" class="form-control" x-model="cartDraft.jumlah" @input="onDraftPricingChange()">
                                    </div>
                                    <div>
                                        <label class="form-label">
                                            Harga
                                            <span class="badge-success-soft ms-1" x-show="draftHargaTipe === 'grosir'" x-cloak>Grosir</span>
                                        </label>
                                        <input type="number" min="0" step="1000" class="form-control" x-model="cartDraft.harga_satuan" :readonly="!cartDraft.harga_manual">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="hargaManualDraft" x-model="cartDraft.harga_manual" @change="onDraftPricingChange()">
                                            <label class="form-check-label" for="hargaManualDraft">Harga manual</label>
                                        </div>
                                    </div>
                                    <div class="full">
                                        <button type="button" class="btn btn-app-secondary btn-sm" @click="addCartLine()">+ Tambah ke keranjang</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div class="full">
                        <label class="form-label" x-text="editingId ? 'Total (otomatis)' : 'Total keranjang'"></label>
                        <input type="text" class="form-control" :value="rupiah(computedTotal)" readonly>
                    </div>
                    <div class="full">
                        <label class="form-label">Keterangan</label>
                        <textarea class="form-control" rows="2" x-model="form.keterangan" placeholder="mis. Pelanggan tetap, diskon pameran"></textarea>
                    </div>
                </div>
            </div>
            <div class="ld-modal__footer">
                <button type="button" class="btn btn-app-secondary" @click="modalOpen = false">Batal</button>
                <button type="button" class="btn btn-app" :disabled="saving" @click="save()" x-text="editingId ? 'Simpan' : 'Simpan Transaksi'"></button>
            </div>
        </div>
    </div>

    {{-- Retur modal --}}
    <div class="ld-modal" x-show="returModalOpen" x-cloak @keydown.escape.window="returModalOpen = false" @click.self="returModalOpen = false" x-transition.opacity>
        <div class="ld-modal__dialog" x-transition>
            <div class="ld-modal__header">
                <h5 class="ld-modal__title">Catat Retur</h5>
                <button type="button" class="btn-close" @click="returModalOpen = false" aria-label="Tutup"></button>
            </div>
            <div class="ld-modal__body">
                <div class="mb-3" x-show="returTarget" x-cloak>
                    <p class="ld-caption mb-1">Penjualan #<span class="tnum" x-text="returTarget?.id"></span> · <span x-text="returTarget ? produkNama(returTarget.id_produk) : ''"></span></p>
                    <p class="mb-0">
                        Qty terjual: <strong class="tnum" x-text="returTarget?.jumlah"></strong>
                        · Sudah diretur: <strong class="tnum" x-text="returTarget?.jumlah_diretur ?? 0"></strong>
                        · Sisa: <strong class="tnum" x-text="returMax"></strong>
                    </p>
                </div>
                <div class="ld-form-grid">
                    <div>
                        <label class="form-label">Tanggal Retur <span class="req">*</span></label>
                        <input type="date" class="form-control" :class="returErrors.tanggal ? 'ld-input-invalid' : ''" x-model="returForm.tanggal">
                        <div class="ld-field-error" x-show="returErrors.tanggal" x-text="returErrors.tanggal"></div>
                    </div>
                    <div>
                        <label class="form-label">Jumlah Diretur <span class="req">*</span></label>
                        <input
                            type="number"
                            min="1"
                            :max="returMax"
                            step="1"
                            class="form-control"
                            :class="returErrors.jumlah ? 'ld-input-invalid' : ''"
                            x-model="returForm.jumlah"
                        >
                        <div class="ld-field-error" x-show="returErrors.jumlah" x-text="returErrors.jumlah"></div>
                        <p class="ld-caption mt-1 mb-0">Min 1 · Max <span class="tnum" x-text="returMax"></span></p>
                    </div>
                    <div class="full">
                        <label class="form-label">Alasan</label>
                        <textarea class="form-control" rows="2" x-model="returForm.alasan" placeholder="mis. Barang cacat, Ukuran tidak sesuai"></textarea>
                    </div>
                    <div class="full">
                        <label class="form-label">Perkiraan nominal retur</label>
                        <input type="text" class="form-control" :value="rupiah(returNominalPreview)" readonly>
                    </div>
                </div>
            </div>
            <div class="ld-modal__footer">
                <button type="button" class="btn btn-app-secondary" @click="returModalOpen = false">Batal</button>
                <button type="button" class="btn btn-app" :disabled="returSaving || returMax < 1" @click="saveRetur()">Simpan Retur</button>
            </div>
        </div>
    </div>

    {{-- Delete confirm --}}
    <div class="ld-modal" x-show="deleteTarget" x-cloak @keydown.escape.window="deleteTarget = null" @click.self="deleteTarget = null" x-transition.opacity>
        <div class="ld-modal__dialog" style="max-width: 420px" x-transition>
            <div class="ld-modal__header"><h5 class="ld-modal__title">Hapus Transaksi?</h5></div>
            <div class="ld-modal__body">
                <p class="mb-0">Transaksi <strong x-text="deleteTarget ? produkNama(deleteTarget.id_produk) : ''"></strong> sebesar <strong x-text="deleteTarget ? rupiah(deleteTarget.total) : ''"></strong> akan dihapus (soft delete).</p>
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
