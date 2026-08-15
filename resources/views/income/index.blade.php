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
            <x-button variant="success" icon="plus" @click="openAdd()">Tambah Transaksi</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-app-card>
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <input type="search" class="form-control" style="max-width: 320px" placeholder="Cari produk atau tanggal…" x-model="search">
            <span class="ld-mono-caps" x-text="visibleRows.length + ' transaksi'"></span>
        </div>

        <x-data-table :scroll="false">
            <table class="ld-data-table ld-income-table">
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
                                <span
                                    :class="'ld-badge-channel ld-badge-channel--' + (row.jenis_transaksi === 'online' ? 'online' : 'offline')"
                                    x-text="row.jenis_transaksi_label || (row.jenis_transaksi === 'online' ? 'Online' : 'Offline')"></span>
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
                                <button
                                    type="button"
                                    class="ld-action-link ld-action-link--neutral"
                                    x-show="row.nomor_transaksi"
                                    @click="openNota(row)"
                                    title="Lihat nota transaksi"
                                >Nota</button>
                                <button type="button" class="ld-action-link ld-action-link--primary" x-show="!row.dihapus_pada" @click="openEdit(row)">Ubah</button>
                                <button type="button" class="ld-action-link ld-action-link--danger" x-show="!row.dihapus_pada" @click="confirmDelete(row)">Hapus</button>
                                <button
                                    type="button"
                                    class="ld-action-link ld-action-link--success"
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
                        <input type="date" class="form-control" :max="today" :class="errors.tanggal_transaksi ? 'ld-input-invalid' : ''" x-model="form.tanggal_transaksi">
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
                                    <input type="text" inputmode="numeric" class="form-control tnum" :class="errors.harga_satuan ? 'ld-input-invalid' : ''"
                                        :value="formatRupiahInput(form.harga_satuan)" @input="form.harga_satuan = updateRupiahInput($event)" :readonly="!form.harga_manual">
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
                                                    <input type="text" inputmode="numeric" class="form-control form-control-sm text-end tnum"
                                                        :value="formatRupiahInput(line.harga_satuan)" @input="line.harga_satuan = updateRupiahInput($event)" :readonly="!line.harga_manual">
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
                                        <input type="text" inputmode="numeric" class="form-control tnum"
                                            :value="formatRupiahInput(cartDraft.harga_satuan)" @input="cartDraft.harga_satuan = updateRupiahInput($event)" :readonly="!cartDraft.harga_manual">
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" id="hargaManualDraft" x-model="cartDraft.harga_manual" @change="onDraftPricingChange()">
                                            <label class="form-check-label" for="hargaManualDraft">Harga manual</label>
                                        </div>
                                    </div>
                                    <div class="full">
                                        <x-button variant="secondary" size="sm" icon="plus" @click="addCartLine()">Tambah ke keranjang</x-button>
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
                <x-button variant="secondary" icon="close" @click="modalOpen = false">Batal</x-button>
                <x-button variant="app" icon="check" ::disabled="saving" ::class="saving ? 'is-loading' : ''" @click="save()">
                    <span x-text="editingId ? 'Simpan' : 'Simpan Transaksi'"></span>
                </x-button>
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
                        <input type="date" class="form-control" :max="today" :class="returErrors.tanggal ? 'ld-input-invalid' : ''" x-model="returForm.tanggal">
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
                <x-button variant="secondary" icon="close" @click="returModalOpen = false">Batal</x-button>
                <x-button variant="app" icon="undo" ::disabled="returSaving || returMax < 1" ::class="returSaving ? 'is-loading' : ''" @click="saveRetur()">Simpan Retur</x-button>
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
                <x-button variant="secondary" icon="close" @click="deleteTarget = null">Batal</x-button>
                <x-button variant="danger" icon="trash" @click="doDelete()">Hapus</x-button>
            </div>
        </div>
    </div>

    {{-- Nota penjualan (bukti transaksi) --}}
    <div class="ld-modal" x-show="notaOpen" x-cloak @keydown.escape.window="notaOpen = false" @click.self="notaOpen = false" x-transition.opacity>
        <div class="ld-modal__dialog" style="max-width: 480px" x-transition>
            <div class="ld-modal__header">
                <h5 class="ld-modal__title">Nota Penjualan</h5>
                <button type="button" class="btn-close" @click="notaOpen = false" aria-label="Tutup"></button>
            </div>
            <div class="ld-modal__body">
                <p class="ld-caption" x-show="notaLoading" x-cloak>Memuat nota…</p>
                <div class="ld-field-error" x-show="notaError" x-text="notaError" x-cloak></div>

                <div class="ld-nota" id="notaPrintArea" x-show="nota && !notaLoading" x-cloak>
                    <div class="ld-nota__head">
                        <img class="ld-nota__logo" :src="nota?.usaha?.logo" :alt="nota?.usaha?.nama">
                        <div class="ld-nota__brand" x-text="nota?.usaha?.nama"></div>
                        <div class="ld-nota__subtitle">Kerajinan Kulit</div>
                        <div class="ld-nota__title">Nota Penjualan</div>
                    </div>

                    <dl class="ld-nota__meta">
                        <div><dt>No. Transaksi</dt><dd class="tnum" x-text="nota?.nomor_transaksi"></dd></div>
                        <div><dt>Tanggal</dt><dd x-text="nota?.tanggal_label"></dd></div>
                        <div><dt>Jenis</dt><dd x-text="nota?.jenis_transaksi_label"></dd></div>
                        <div><dt>Kasir</dt><dd x-text="nota?.kasir"></dd></div>
                    </dl>

                    <table class="ld-nota__items">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in (nota?.items || [])" :key="item.id">
                                <tr>
                                    <td>
                                        <span x-text="item.nama_produk"></span>
                                        <span class="ld-nota__flag" x-show="item.harga_tipe === 'grosir'" x-cloak>grosir</span>
                                        <div class="ld-nota__flag ld-nota__flag--retur" x-show="item.jumlah_diretur > 0" x-cloak>
                                            Diretur <span class="tnum" x-text="item.jumlah_diretur"></span> pcs
                                        </div>
                                    </td>
                                    <td class="text-end tnum" x-text="item.jumlah"></td>
                                    <td class="text-end tnum" x-text="rupiah(item.harga_satuan)"></td>
                                    <td class="text-end tnum" x-text="rupiah(item.subtotal)"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>

                    <div class="ld-nota__totals">
                        <div><span>Total Item</span><span class="tnum" x-text="(nota?.total_qty ?? 0) + ' pcs'"></span></div>
                        <div><span>Subtotal</span><span class="tnum" x-text="rupiah(nota?.subtotal)"></span></div>
                        <div class="ld-nota__retur" x-show="(nota?.total_retur ?? 0) > 0" x-cloak>
                            <span>Retur</span><span class="tnum" x-text="'− ' + rupiah(nota?.total_retur)"></span>
                        </div>
                        <div class="ld-nota__grand"><span>TOTAL</span><span class="tnum" x-text="rupiah(nota?.total)"></span></div>
                    </div>

                    <p class="ld-nota__foot">
                        Terima kasih atas pembelian Anda.<br>
                        Simpan nota ini sebagai bukti transaksi untuk keperluan retur atau komplain.
                    </p>
                </div>
            </div>
            <div class="ld-modal__footer">
                <x-button variant="secondary" icon="close" @click="notaOpen = false">Tutup</x-button>
                <x-button variant="secondary" icon="print" ::disabled="!nota" @click="printNota()">Cetak</x-button>
                <x-button variant="app" icon="download" ::disabled="!nota" @click="downloadNota()">Unduh PDF</x-button>
            </div>
        </div>
    </div>

    <div class="ld-toast" x-show="toast" x-cloak x-transition x-text="toast"></div>
</div>
@endsection
