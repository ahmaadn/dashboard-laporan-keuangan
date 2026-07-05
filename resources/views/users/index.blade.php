@extends('layouts.app')

@section('title', 'Data Pengguna')
@section('topbar-title', 'Data Pengguna')

@push('scripts')
    @vite(['resources/js/pages.js'])
@endpush

@section('content')
<div x-data="users(@js($pengguna), @js($currentUser))">

    <x-page-header eyebrow="Administrasi" title="Data Pengguna">
        <x-slot:actions>
            <button type="button" class="btn btn-app" @click="openAdd()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Pengguna
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="alert-app mb-4" x-show="guardMessage" x-cloak x-transition x-text="guardMessage"></div>

    <x-app-card>
        <div class="d-flex justify-content-between align-items-center mb-3 gap-2">
            <input type="search" class="form-control" style="max-width: 320px" placeholder="Cari nama, username, email…" x-model="search">
            <span class="ld-mono-caps" x-text="visibleRows.length + ' pengguna'"></span>
        </div>

        <x-data-table>
            <table class="ld-data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Nama Pengguna</th>
                        <th>Email</th>
                        <th>Peran</th>
                        <th>Akses Dashboard</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="row in visibleRows" :key="row.id">
                        <tr :class="row.dihapus_pada ? 'ld-row-deleted' : ''">
                            <td class="fw-medium" x-text="row.nama"></td>
                            <td class="ld-mono-caps" x-text="row.nama_pengguna"></td>
                            <td x-text="row.email"></td>
                            <td>
                                <span class="badge-filled" x-show="row.peran === 'admin'">Admin</span>
                                <span class="badge-neutral" x-show="row.peran !== 'admin'" x-cloak>Pegawai</span>
                            </td>
                            <td>
                                <span x-show="row.peran === 'pegawai' && row.dapat_melihat_dashboard" class="badge-success-soft">Ya</span>
                                <span x-show="row.peran === 'pegawai' && !row.dapat_melihat_dashboard" class="badge-neutral" x-cloak>Tidak</span>
                                <span x-show="row.peran === 'admin'" class="ld-mono-caps" x-cloak>Otomatis</span>
                            </td>
                            <td>
                                <span class="badge-soft-delete" x-show="row.dihapus_pada" x-cloak>Terhapus</span>
                                <span class="badge-success-soft" x-show="!row.dihapus_pada && row.aktif" x-cloak>Aktif</span>
                                <span class="badge-neutral" x-show="!row.dihapus_pada && !row.aktif" x-cloak>Nonaktif</span>
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
            <x-empty-state icon="○" text="Tidak ada pengguna yang cocok." />
        </template>
    </x-app-card>

    {{-- Add/Edit modal --}}
    <div class="ld-modal" x-show="modalOpen" x-cloak @keydown.escape.window="modalOpen = false" @click.self="modalOpen = false" x-transition.opacity>
        <div class="ld-modal__dialog" x-transition>
            <div class="ld-modal__header">
                <h5 class="ld-modal__title" x-text="editingId ? 'Ubah Pengguna' : 'Tambah Pengguna'"></h5>
                <button type="button" class="btn-close" @click="modalOpen = false" aria-label="Tutup"></button>
            </div>
            <div class="ld-modal__body">
                <div class="ld-form-grid">
                    <div class="full">
                        <label class="form-label">Nama <span class="req">*</span></label>
                        <input type="text" class="form-control" :class="errors.nama ? 'ld-input-invalid' : ''" x-model="form.nama">
                        <div class="ld-field-error" x-show="errors.nama" x-text="errors.nama"></div>
                    </div>
                    <div>
                        <label class="form-label">Nama Pengguna <span class="req">*</span></label>
                        <input type="text" class="form-control" :class="errors.nama_pengguna ? 'ld-input-invalid' : ''" x-model="form.nama_pengguna">
                        <div class="ld-field-error" x-show="errors.nama_pengguna" x-text="errors.nama_pengguna"></div>
                    </div>
                    <div>
                        <label class="form-label">Email <span class="req">*</span></label>
                        <input type="email" class="form-control" :class="errors.email ? 'ld-input-invalid' : ''" x-model="form.email">
                        <div class="ld-field-error" x-show="errors.email" x-text="errors.email"></div>
                    </div>
                    <div>
                        <label class="form-label">Kata Sandi <span class="req" x-show="!editingId">*</span></label>
                        <input type="password" class="form-control" :class="errors.kata_sandi ? 'ld-input-invalid' : ''" x-model="form.kata_sandi" :placeholder="editingId ? 'Kosongkan jika tidak diubah' : ''">
                        <div class="ld-field-error" x-show="errors.kata_sandi" x-text="errors.kata_sandi"></div>
                    </div>
                    <div>
                        <label class="form-label">Peran <span class="req">*</span></label>
                        <select class="form-select" x-model="form.peran" @change="onPeranChange()">
                            <option value="admin">Admin</option>
                            <option value="pegawai">Pegawai</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label">Akses Dashboard</label>
                        <div class="form-check form-switch pt-2" x-show="form.peran === 'pegawai'" x-cloak>
                            <input class="form-check-input" type="checkbox" role="switch" id="usrDash" x-model="form.dapat_melihat_dashboard">
                            <label class="form-check-label" for="usrDash">Izinkan melihat dashboard</label>
                        </div>
                        <span x-show="form.peran === 'admin'" class="ld-mono-caps" x-cloak>Otomatis untuk Admin</span>
                    </div>
                    <div class="full">
                        <label class="form-label">Status Akun</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="usrAktif" x-model="form.aktif">
                            <label class="form-check-label" for="usrAktif" x-text="form.aktif ? 'Aktif' : 'Nonaktif'"></label>
                        </div>
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
            <div class="ld-modal__header"><h5 class="ld-modal__title">Hapus Pengguna?</h5></div>
            <div class="ld-modal__body">
                <p class="mb-0">Akun <strong x-text="deleteTarget?.nama"></strong> akan dihapus (soft delete). Riwayat transaksi yang pernah dicatat tetap utuh.</p>
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
