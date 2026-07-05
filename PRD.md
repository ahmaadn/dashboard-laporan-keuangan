# Product Requirements Document (PRD)
## Aplikasi Pengelolaan Keuangan UMKM Kerajinan Kulit Berbasis Web dengan Dashboard Interaktif

**Versi Dokumen:** 2.0 (Detail)
**Tanggal:** 3 Juli 2026
**Status:** Draft
**Pemilik Dokumen:** Product Owner / Peneliti
**Basis Data Rujukan:** `db_keuangan_umkm` (MySQL 8.0+, soft delete)

---

## Daftar Isi

1. Ringkasan Eksekutif
2. Latar Belakang & Rumusan Masalah
3. Tujuan & Sasaran Produk
4. Lingkup Produk
5. Persona Pengguna
6. Peran & Hak Akses (RBAC)
7. Kebutuhan Fungsional (Detail per Fitur)
8. Spesifikasi Dashboard Interaktif (Detail per Komponen)
9. Aturan Bisnis (Business Rules)
10. Kebutuhan Data & Keterkaitan dengan Skema Database
11. Kebutuhan Non-Fungsional
12. Rancangan Antarmuka (Deskripsi Wireframe)
13. Penanganan Error & Validasi
14. Asumsi, Ketergantungan, dan Batasan
15. Analisis Risiko
16. Rencana Rilis / Fase Pengembangan
17. Kriteria Penerimaan & Definition of Done
18. Metrik Keberhasilan
19. Glosarium
20. Pertanyaan Terbuka
21. Lampiran

---

## 1. Ringkasan Eksekutif

Aplikasi Pengelolaan Keuangan UMKM Kerajinan Kulit adalah sistem berbasis web yang dirancang untuk menggantikan pencatatan keuangan manual (buku kas/spreadsheet) dengan sistem terpusat, terstruktur, dan dilengkapi visualisasi data real-time melalui Dashboard Interaktif. Sistem ini melayani dua peran pengguna utama — **Admin** (pemilik usaha) dan **Pegawai** (staf operasional) — dengan kontrol akses berbasis peran (Role-Based Access Control/RBAC).

Nilai utama yang ditawarkan:
- Pencatatan transaksi pemasukan dan pengeluaran yang konsisten dan terstruktur.
- Visibilitas instan atas kondisi keuangan usaha (laba/rugi, tren, kategori pengeluaran).
- Kemampuan analisis produk terlaris dan tren penjualan untuk mendukung keputusan bisnis (misalnya restok bahan baku atau penyesuaian harga).
- Riwayat data yang aman melalui mekanisme *soft delete*, sehingga data yang "dihapus" tetap dapat dipulihkan/ditelusuri.

---

## 2. Latar Belakang & Rumusan Masalah

### 2.1 Kondisi Saat Ini
UMKM kerajinan kulit pada umumnya:
- Mencatat transaksi secara manual di buku kas atau spreadsheet lepas (tidak terstandarisasi antar pencatat).
- Tidak memiliki pemisahan akses antara pemilik dan pegawai — siapa pun yang memegang buku kas dapat mengubah seluruh catatan.
- Kesulitan menyusun laporan laba/rugi periodik karena data tersebar dan harus direkap manual.
- Tidak memiliki visibilitas terhadap tren penjualan produk atau kategori pengeluaran mana yang paling membebani biaya operasional.

### 2.2 Rumusan Masalah
1. Bagaimana merancang sistem pencatatan keuangan yang terstruktur namun tetap mudah digunakan oleh pengguna dengan literasi digital dasar?
2. Bagaimana menyediakan visualisasi kondisi keuangan yang informatif dan interaktif tanpa membebani pengguna dengan proses analisis manual?
3. Bagaimana menerapkan kontrol akses yang membedakan kewenangan Admin dan Pegawai agar integritas data tetap terjaga?
4. Bagaimana memastikan data transaksi tidak hilang secara permanen akibat kesalahan input atau penghapusan yang tidak disengaja?

### 2.3 Dampak Jika Masalah Tidak Diselesaikan
- Potensi kehilangan data akibat pencatatan manual (buku hilang/rusak, file spreadsheet tertimpa).
- Keputusan bisnis (restok, promosi produk, efisiensi biaya) diambil tanpa data yang akurat dan real-time.
- Tidak ada jejak akuntabilitas atas siapa yang mencatat/mengubah transaksi tertentu.

---

## 3. Tujuan & Sasaran Produk

### 3.1 Tujuan Umum
Membangun aplikasi web pengelolaan keuangan yang membantu UMKM kerajinan kulit mencatat transaksi secara terstruktur dan memantau kondisi usaha melalui dashboard interaktif.

### 3.2 Tujuan Khusus (Objectives)
| Kode | Tujuan Khusus |
|---|---|
| O-1 | Menyediakan sistem CRUD data produk, pemasukan, pengeluaran, dan pengguna dengan validasi data yang konsisten |
| O-2 | Menyediakan dashboard yang menampilkan ringkasan keuangan (pemasukan, pengeluaran, laba/rugi) secara real-time |
| O-3 | Menyediakan visualisasi tren (grafik) penjualan dan pengeluaran yang dapat difilter berdasarkan periode |
| O-4 | Menerapkan kontrol akses berbasis peran (Admin/Pegawai) di seluruh fitur sistem |
| O-5 | Menjamin keamanan data melalui mekanisme *soft delete*, sehingga tidak ada penghapusan data secara permanen dari sisi pengguna |
| O-6 | Menyediakan fitur perbandingan antar-periode untuk mendukung evaluasi performa usaha |

### 3.3 Sasaran Terukur (Measurable Goals)
- Seluruh transaksi pemasukan/pengeluaran tercatat dalam sistem dalam waktu < 1 menit per entri.
- Dashboard menampilkan data ter-update tanpa reload halaman penuh (async update) dalam < 2 detik setelah filter periode diubah.
- 100% aksi hapus data tercermin sebagai soft delete (data tetap ada di database, hanya disembunyikan dari tampilan aktif).

---

## 4. Lingkup Produk

### 4.1 Dalam Lingkup (In-Scope)
| No | Item |
|---|---|
| 1 | Autentikasi & manajemen sesi pengguna (Login/Logout) |
| 2 | CRUD Data Produk (khusus Admin), lihat data produk (Pegawai) |
| 3 | CRUD Data Pemasukan (Admin & Pegawai) |
| 4 | CRUD Data Pengeluaran (Admin & Pegawai) |
| 5 | CRUD Data Pengguna & pengaturan hak akses dashboard (khusus Admin) |
| 6 | Laporan Keuangan periodik (khusus Admin) |
| 7 | Dashboard Interaktif — 8 komponen (lihat Bagian 8) |
| 8 | Soft delete di seluruh entitas data utama |

### 4.2 Di Luar Lingkup (Out-of-Scope) untuk Versi Ini
| No | Item | Alasan |
|---|---|---|
| 1 | Integrasi payment gateway | Fokus versi ini adalah pencatatan, bukan transaksi pembayaran online |
| 2 | Manajemen stok/inventori mendalam (kartu stok, mutasi gudang) | Di luar cakupan pengelolaan keuangan; berpotensi jadi modul terpisah |
| 3 | Aplikasi mobile native (Android/iOS) | Versi ini berbasis web responsif |
| 4 | Multi-cabang/multi-outlet | Skala UMKM tunggal pada versi ini |
| 5 | Fitur restore data dari soft delete melalui UI (Trash/Recycle Bin) | Berpotensi untuk versi lanjutan; versi ini soft delete bersifat penyimpanan histori, bukan restore aktif — **perlu dikonfirmasi** |

---

## 5. Persona Pengguna

### 5.1 Persona 1 — "Bu Sari", Pemilik UMKM (Admin)
- **Usia:** 42 tahun
- **Latar belakang:** Pemilik usaha kerajinan kulit dengan 3 pegawai, literasi digital menengah (terbiasa WhatsApp, Excel dasar).
- **Tujuan:** Ingin mengetahui kondisi keuangan usahanya kapan saja tanpa harus merekap manual; ingin tahu produk mana yang paling laku.
- **Frustrasi saat ini:** Harus menunggu laporan dari pegawai di akhir bulan; sering menemukan selisih pencatatan.
- **Kebutuhan utama:** Dashboard ringkas, laporan yang bisa langsung dilihat, kontrol atas siapa yang boleh mengubah data.

### 5.2 Persona 2 — "Dimas", Pegawai Operasional (Pegawai)
- **Usia:** 24 tahun
- **Latar belakang:** Staf yang mencatat transaksi harian (penjualan di toko/pameran, pembelian bahan baku).
- **Tujuan:** Ingin mencatat transaksi dengan cepat tanpa proses rumit, terutama saat sedang melayani pembeli.
- **Frustrasi saat ini:** Pencatatan manual sering tertunda dan berujung lupa.
- **Kebutuhan utama:** Form input yang sederhana dan cepat, tidak perlu akses ke data sensitif lain.

---

## 6. Peran & Hak Akses (RBAC)

### 6.1 Matriks Hak Akses Detail

| Modul | Aksi | Admin | Pegawai |
|---|---|:---:|:---:|
| Login | Masuk ke sistem | ✅ | ✅ |
| Data Produk | Tambah | ✅ | ❌ |
| Data Produk | Ubah | ✅ | ❌ |
| Data Produk | Hapus (soft delete) | ✅ | ❌ |
| Data Produk | Lihat daftar/detail | ✅ | ✅ |
| Data Pemasukan | Tambah | ✅ | ✅ |
| Data Pemasukan | Ubah | ✅ | ✅ (hanya milik sendiri — **perlu dikonfirmasi**) |
| Data Pemasukan | Hapus (soft delete) | ✅ | ✅ (hanya milik sendiri — **perlu dikonfirmasi**) |
| Data Pengeluaran | Tambah | ✅ | ✅ |
| Data Pengeluaran | Ubah | ✅ | ✅ (hanya milik sendiri — **perlu dikonfirmasi**) |
| Data Pengeluaran | Hapus (soft delete) | ✅ | ✅ (hanya milik sendiri — **perlu dikonfirmasi**) |
| Data Pengguna | Tambah/Ubah/Nonaktifkan | ✅ | ❌ |
| Data Pengguna | Atur `dapat_melihat_dashboard` | ✅ | ❌ |
| Laporan Keuangan | Lihat & unduh | ✅ | ❌ |
| Dashboard Interaktif | Lihat | ✅ | ⚠️ Kondisional (`dapat_melihat_dashboard = TRUE`) |

> **Catatan Desain:** Berdasarkan skema database, kolom `id_pengguna` pada tabel `pemasukan` dan `pengeluaran` mencatat siapa pencatat transaksi. Ini membuka opsi kebijakan "Pegawai hanya dapat mengubah/menghapus transaksi yang ia catat sendiri" — perlu keputusan bisnis final dari pemilik produk.

### 6.2 Aturan Sesi & Keamanan Akses
- Setiap request ke endpoint API/halaman divalidasi terhadap peran pengguna yang sedang login (middleware otorisasi).
- Percobaan akses ke fitur di luar hak akses menghasilkan respons **403 Forbidden** dengan pesan yang jelas.
- Sesi otomatis berakhir setelah periode tidak aktif tertentu (nilai default: 30 menit — **dapat disesuaikan**).

---

## 7. Kebutuhan Fungsional (Detail per Fitur)

Setiap fitur dirinci dengan: Deskripsi, Aktor, Prakondisi, Alur Utama, Alur Alternatif/Error, Field Terkait, dan Kriteria Penerimaan.

### FR-1 — Login

**Deskripsi:** Mekanisme autentikasi menggunakan `nama_pengguna`/`email` dan `kata_sandi`.

**Aktor:** Admin, Pegawai

**Prakondisi:** Akun sudah terdaftar dan berstatus `aktif = TRUE` serta `dihapus_pada IS NULL`.

**Alur Utama:**
1. Pengguna membuka halaman login.
2. Pengguna memasukkan nama pengguna/email dan kata sandi.
3. Sistem memvalidasi kredensial terhadap tabel `pengguna`.
4. Jika valid, sistem membuat sesi dan mengarahkan ke halaman sesuai peran:
   - Admin → Dashboard (default) atau halaman terakhir yang diakses.
   - Pegawai → Halaman Input Transaksi (default), atau Dashboard jika `dapat_melihat_dashboard = TRUE`.

**Alur Alternatif/Error:**
- E-1.1: Kredensial salah → tampilkan pesan generik "Nama pengguna atau kata sandi salah" (tidak membedakan mana yang salah, demi keamanan).
- E-1.2: Akun tidak aktif (`aktif = FALSE`) atau sudah di-*soft delete* → tampilkan pesan "Akun tidak aktif, hubungi administrator".
- E-1.3: Percobaan login gagal berulang (misal 5x) → terapkan *rate limiting*/lockout sementara (rekomendasi keamanan).

**Field Terkait:** `nama_pengguna`, `email`, `kata_sandi`, `peran`, `aktif`, `dihapus_pada`.

**Kriteria Penerimaan:**
- [ ] Login berhasil mengarahkan pengguna sesuai peran.
- [ ] Login gagal menampilkan pesan error yang sesuai tanpa membocorkan detail sistem.
- [ ] Kata sandi tidak pernah ditampilkan/dikirim dalam bentuk plain text di response API.

---

### FR-2 — Kelola Data Produk

**Deskripsi:** Modul CRUD untuk data master produk kerajinan kulit yang menjadi referensi transaksi pemasukan.

**Aktor:** Admin (penuh), Pegawai (lihat saja)

**Field Input (mengacu tabel `produk`):**

| Field | Tipe | Wajib | Validasi |
|---|---|:---:|---|
| `nama` | Teks (150) | ✅ | Tidak boleh kosong, maks 150 karakter |
| `id_kategori` | Pilihan (dropdown dari `kategori_produk`) | ❌ | Harus ID kategori valid jika diisi |
| `sku` | Teks (50) | ❌ | Unik jika diisi |
| `harga` | Angka desimal | ✅ | ≥ 0 |
| `deskripsi` | Teks panjang | ❌ | — |
| `aktif` | Boolean | ✅ (default TRUE) | — |

**Alur Utama — Tambah Produk:**
1. Admin membuka menu Data Produk → klik "Tambah Produk".
2. Mengisi form sesuai field di atas.
3. Sistem memvalidasi input (lihat Bagian 13).
4. Sistem menyimpan data baru, mengisi `dibuat_oleh` dengan ID Admin yang login.
5. Sistem menampilkan notifikasi sukses dan produk baru muncul di daftar.

**Alur Utama — Ubah/Hapus Produk:**
- Ubah: sama seperti tambah, namun field terisi data eksisting; `diperbarui_pada` ter-update otomatis.
- Hapus: sistem melakukan **soft delete** — mengisi `dihapus_pada = NOW()`, bukan menghapus baris secara fisik. Produk yang sudah di-soft-delete tidak muncul lagi di dropdown pemilihan produk saat mencatat pemasukan baru, namun riwayat transaksi lama yang mereferensikan produk tersebut tetap utuh.

**Alur Alternatif/Error:**
- E-2.1: SKU duplikat → tampilkan pesan "SKU sudah digunakan".
- E-2.2: Admin mencoba menghapus produk yang masih direferensikan transaksi pemasukan aktif → soft delete tetap diizinkan (karena FK `ON DELETE SET NULL`/histori tetap aman), namun sistem menampilkan peringatan informatif "Produk ini memiliki riwayat transaksi, data transaksi lama tidak akan terpengaruh".
- E-2.3: Pegawai mencoba mengakses form tambah/ubah/hapus → sistem menolak akses (403).

**Kriteria Penerimaan:**
- [ ] Admin dapat melakukan CRUD penuh terhadap data produk.
- [ ] Pegawai hanya dapat melihat daftar produk (read-only), tombol aksi tidak tersedia di antarmuka Pegawai.
- [ ] Produk yang di-soft-delete tidak muncul di form pencatatan pemasukan baru.
- [ ] Riwayat transaksi tetap menampilkan nama produk meski produk sudah di-soft-delete.

---

### FR-3 — Kelola Data Pemasukan

**Deskripsi:** Modul pencatatan transaksi penjualan produk.

**Aktor:** Admin, Pegawai

**Field Input (mengacu tabel `pemasukan`):**

| Field | Tipe | Wajib | Validasi |
|---|---|:---:|---|
| `id_produk` | Pilihan (dropdown produk aktif) | ❌ | Harus produk aktif jika diisi |
| `tanggal_transaksi` | Tanggal | ✅ | Tidak boleh tanggal di masa depan (**aturan bisnis, perlu dikonfirmasi**) |
| `jumlah` | Angka bulat | ✅ | ≥ 1 |
| `harga_satuan` | Angka desimal | ✅ | ≥ 0; default terisi otomatis dari `produk.harga` namun dapat diubah manual (misal untuk diskon) |
| `total` | Angka desimal (otomatis) | ✅ | Dihitung sistem = `jumlah × harga_satuan`, read-only bagi pengguna |
| `keterangan` | Teks (255) | ❌ | — |

**Alur Utama:**
1. Pengguna (Admin/Pegawai) membuka menu Pemasukan → "Tambah Transaksi".
2. Memilih produk (opsional — bisa juga mencatat pemasukan tanpa produk spesifik, misalnya pemasukan lain-lain).
3. Sistem otomatis mengisi `harga_satuan` dari data produk, pengguna dapat menyesuaikan.
4. Pengguna mengisi jumlah dan tanggal transaksi.
5. Sistem menghitung `total` secara otomatis dan menampilkannya sebelum disimpan.
6. Sistem menyimpan transaksi dengan `id_pengguna` = pengguna yang login.
7. Dashboard dan laporan ter-update mencerminkan transaksi baru.

**Alur Alternatif/Error:**
- E-3.1: Jumlah atau harga bernilai negatif/nol → validasi gagal, tampilkan pesan kesalahan pada field terkait.
- E-3.2: Tanggal transaksi di masa depan → tolak dengan pesan "Tanggal transaksi tidak boleh melebihi hari ini" (jika aturan ini disepakati).
- E-3.3: Pengguna mencoba mengubah/menghapus transaksi milik pengguna lain (jika kebijakan "hanya milik sendiri" diterapkan) → 403 Forbidden.

**Kriteria Penerimaan:**
- [ ] Total dihitung otomatis dan konsisten dengan jumlah × harga satuan.
- [ ] Transaksi baru langsung tercermin di komponen dashboard terkait (ringkasan, grafik tren, produk terlaris).
- [ ] Hapus transaksi menggunakan soft delete, transaksi tidak lagi dihitung dalam ringkasan namun tetap tersimpan di database.

---

### FR-4 — Kelola Data Pengeluaran

**Deskripsi:** Modul pencatatan transaksi pengeluaran usaha.

**Aktor:** Admin, Pegawai

**Field Input (mengacu tabel `pengeluaran`):**

| Field | Tipe | Wajib | Validasi |
|---|---|:---:|---|
| `id_kategori` | Pilihan (Bahan Baku/Operasional/Pengiriman/dll.) | ✅ | Harus kategori valid |
| `tanggal_transaksi` | Tanggal | ✅ | Tidak boleh di masa depan |
| `nominal` | Angka desimal | ✅ | > 0 |
| `keterangan` | Teks (255) | ❌ | Disarankan diisi untuk konteks pengeluaran |

**Alur Utama:**
1. Pengguna membuka menu Pengeluaran → "Tambah Transaksi".
2. Memilih kategori pengeluaran dari daftar (`kategori_pengeluaran`).
3. Mengisi nominal, tanggal, dan keterangan.
4. Sistem menyimpan data dengan `id_pengguna` pencatat.
5. Dashboard (khususnya Analisis Pengeluaran per Kategori) ter-update otomatis.

**Alur Alternatif/Error:**
- E-4.1: Nominal ≤ 0 → validasi gagal.
- E-4.2: Kategori tidak dipilih → validasi gagal, field wajib.

**Kriteria Penerimaan:**
- [ ] Setiap pengeluaran wajib memiliki kategori yang valid.
- [ ] Total pengeluaran per kategori terhitung akurat di dashboard.
- [ ] Soft delete diterapkan konsisten seperti pada modul pemasukan.

---

### FR-5 — Kelola Data Pengguna

**Deskripsi:** Modul administrasi akun pengguna sistem, termasuk pengaturan hak akses dashboard untuk Pegawai.

**Aktor:** Admin

**Field Input (mengacu tabel `pengguna`):**

| Field | Tipe | Wajib | Validasi |
|---|---|:---:|---|
| `nama` | Teks | ✅ | — |
| `nama_pengguna` | Teks | ✅ | Unik |
| `email` | Teks | ✅ | Format email valid, unik |
| `kata_sandi` | Teks (di-hash) | ✅ (saat buat baru) | Minimal kompleksitas tertentu (misal 8 karakter, kombinasi huruf & angka) |
| `peran` | Pilihan (admin/pegawai) | ✅ | — |
| `dapat_melihat_dashboard` | Boolean | ✅ (default FALSE untuk Pegawai) | Hanya relevan untuk peran pegawai |
| `aktif` | Boolean | ✅ | — |

**Alur Utama — Tambah Pengguna:**
1. Admin membuka menu Data Pengguna → "Tambah Pengguna".
2. Mengisi data pengguna dan menentukan peran.
3. Jika peran = Pegawai, Admin dapat mengaktifkan/menonaktifkan toggle "Izinkan Melihat Dashboard".
4. Sistem membuat akun baru dengan kata sandi ter-hash.

**Alur Utama — Nonaktifkan/Hapus Pengguna:**
- Sistem menerapkan soft delete (`dihapus_pada`) dan/atau menonaktifkan (`aktif = FALSE`) — akun yang di-soft-delete/nonaktif tidak dapat login, namun riwayat transaksi yang pernah dicatatnya tetap tersimpan dan tertaut (`id_pengguna` pada `pemasukan`/`pengeluaran` tidak berubah).

**Alur Alternatif/Error:**
- E-5.1: Admin mencoba menonaktifkan akunnya sendiri sebagai satu-satunya Admin aktif → sistem menolak dengan pesan "Tidak dapat menonaktifkan Admin terakhir".
- E-5.2: Email/nama pengguna duplikat → validasi gagal.

**Kriteria Penerimaan:**
- [ ] Admin dapat mengatur izin dashboard per akun Pegawai secara individual.
- [ ] Akun yang dinonaktifkan/di-soft-delete tidak dapat login namun histori transaksinya tetap utuh.
- [ ] Minimal harus ada 1 akun Admin aktif di sistem pada waktu mana pun.

---

### FR-6 — Laporan Keuangan

**Deskripsi:** Menyediakan laporan pemasukan, pengeluaran, dan laba/rugi terstruktur berdasarkan periode.

**Aktor:** Admin

**Alur Utama:**
1. Admin membuka menu Laporan Keuangan.
2. Memilih periode laporan (Hari Ini/Minggu Ini/Bulan Ini/Tahun Ini/Rentang Kustom) — menggunakan komponen filter yang sama dengan Dashboard (lihat FR-7.7).
3. Sistem menampilkan ringkasan: Total Pemasukan, Total Pengeluaran, Laba/Rugi, serta rincian transaksi per kategori/produk pada periode tersebut.
4. Admin dapat mengekspor laporan (format: **perlu dikonfirmasi** — PDF dan/atau Excel).

**Field/Data yang Ditampilkan:**
- Ringkasan total (pemasukan, pengeluaran, laba/rugi).
- Rincian transaksi pemasukan per produk.
- Rincian transaksi pengeluaran per kategori.

**Kriteria Penerimaan:**
- [ ] Laporan hanya menghitung transaksi aktif (`dihapus_pada IS NULL`).
- [ ] Laporan dapat difilter dengan seluruh opsi periode yang tersedia di Dashboard.
- [ ] (Jika disepakati) Laporan dapat diekspor ke format yang ditentukan.

---

## 8. Spesifikasi Dashboard Interaktif (Detail per Komponen)

Seluruh komponen dashboard mengacu pada satu **state filter periode global** (lihat FR-7.7) dan diperbarui secara asinkron (tanpa reload halaman) ketika filter berubah. Sumber data seluruh komponen **wajib** mengecualikan baris dengan `dihapus_pada IS NOT NULL`.

### 8.1 Ringkasan Keuangan
- **Sumber Data:** Agregasi `SUM(total)` dari `pemasukan` dan `SUM(nominal)` dari `pengeluaran` pada rentang periode aktif; Laba/Rugi = Total Pemasukan − Total Pengeluaran.
- **Tampilan:** 3 kartu (Total Pemasukan, Total Pengeluaran, Laba/Rugi) dengan indikator warna (misal hijau untuk laba, merah untuk rugi).
- **Interaksi:**
  - Klik kartu "Total Pemasukan" → membuka panel/modal daftar seluruh transaksi pemasukan pada periode terpilih (kolom: tanggal, produk, jumlah, harga satuan, total, pencatat).
  - Klik kartu "Total Pengeluaran" → membuka panel/modal daftar transaksi pengeluaran (kolom: tanggal, kategori, nominal, keterangan, pencatat).
  - Klik kartu "Laba/Rugi" → menampilkan rincian perhitungan (Total Pemasukan − Total Pengeluaran = Laba/Rugi) beserta breakdown ringkas.
- **Kondisi Kosong:** Jika tidak ada transaksi pada periode terpilih, tampilkan nilai Rp 0 dengan pesan "Belum ada transaksi pada periode ini".

### 8.2 Grafik Tren Pemasukan dan Pengeluaran
- **Sumber Data:** Agregasi harian/mingguan/bulanan (granularitas menyesuaikan rentang periode terpilih) dari `pemasukan` dan `pengeluaran`.
- **Tampilan:** Grafik garis atau batang ganda (dua seri: pemasukan vs pengeluaran) sepanjang sumbu waktu.
- **Interaksi:**
  - Klik titik/batang pada bulan/tanggal tertentu → menampilkan rincian transaksi periode tersebut (modal/panel).
  - Grafik otomatis re-render saat filter periode global berubah.
- **Granularitas Otomatis (rekomendasi):**
  - Filter "Hari Ini" → granularitas per jam.
  - Filter "Minggu Ini"/"Bulan Ini" → granularitas per hari.
  - Filter "Tahun Ini" → granularitas per bulan.
  - Filter "Rentang Kustom" → granularitas menyesuaikan panjang rentang (perlu aturan ambang batas — **detail teknis, dapat ditentukan saat desain**).

### 8.3 Analisis Pengeluaran per Kategori
- **Sumber Data:** `SUM(nominal)` dari `pengeluaran` dikelompokkan per `id_kategori`, pada periode aktif.
- **Tampilan:** Diagram pie/donut dengan legenda kategori (Bahan Baku, Operasional, Pengiriman, dan kategori tambahan lain jika ada).
- **Interaksi:** Klik segmen kategori → menampilkan daftar rincian transaksi pengeluaran kategori tersebut pada periode aktif.
- **Kondisi Kosong:** Jika tidak ada pengeluaran, tampilkan pesan "Belum ada data pengeluaran pada periode ini" alih-alih diagram kosong.

### 8.4 Tren Penjualan Produk
- **Sumber Data:** Agregasi `SUM(jumlah)` dan/atau `SUM(total)` dari `pemasukan` dikelompokkan per `id_produk` sepanjang waktu dalam periode aktif.
- **Tampilan:** Grafik garis multi-seri (satu garis per produk, atau dapat dipilih produk mana yang ditampilkan jika jumlah produk banyak — **rekomendasi UX: batasi maksimal 5–10 produk teratas secara default, dengan opsi "lihat semua"**).
- **Interaksi:**
  - Klik nama produk (di legenda atau daftar) → menampilkan riwayat penjualan detail produk tersebut.
  - Grafik menyesuaikan otomatis dengan filter periode global.

### 8.5 Produk Terlaris
- **Sumber Data:** `SUM(jumlah)` dari `pemasukan` dikelompokkan per `id_produk`, diurutkan menurun, pada periode aktif.
- **Tampilan:** Daftar/ranking (misal Top 5 atau Top 10) dengan nama produk, jumlah terjual, dan total nilai penjualan.
- **Interaksi:** Klik produk → menampilkan jumlah penjualan total dan riwayat transaksi produk tersebut pada periode aktif.

### 8.6 Daftar Transaksi Terkini
- **Sumber Data:** Gabungan (`UNION`) transaksi terbaru dari `pemasukan` dan `pengeluaran`, diurutkan berdasarkan `dibuat_pada` atau `tanggal_transaksi` menurun.
- **Tampilan:** Daftar ringkas (misal 10 transaksi terbaru) dengan indikator jenis (Pemasukan/Pengeluaran), tanggal, nominal, dan pencatat.
- **Interaksi:** Klik transaksi → menampilkan detail lengkap (seluruh field transaksi terkait).
- **Catatan:** Komponen ini idealnya menampilkan transaksi terbaru **secara keseluruhan** (tidak dibatasi filter periode global) atau memiliki toggle tersendiri — **perlu dikonfirmasi**, karena tujuannya adalah *aktivitas terbaru*, bukan ringkasan periode.

### 8.7 Filter Periode Interaktif
- **Opsi:** Hari Ini, Minggu Ini, Bulan Ini, Tahun Ini, Rentang Tanggal Kustom (date picker awal–akhir).
- **Perilaku:** Perubahan filter memicu pembaruan seluruh komponen dashboard (8.1–8.6, 8.8) secara asinkron tanpa reload halaman penuh (implementasi teknis: AJAX/fetch API atau pendekatan SPA/reactive).
- **Validasi Rentang Kustom:** Tanggal akhir tidak boleh lebih awal dari tanggal mulai; rentang maksimal (jika ada batasan performa) — **perlu ditentukan saat desain teknis**.
- **Default:** Filter default saat dashboard pertama dibuka disarankan "Bulan Ini".

### 8.8 Perbandingan Periode (Period Comparison)
- **Sumber Data:** Dua kali agregasi (seperti pada 8.1) untuk dua rentang periode yang dipilih pengguna secara independen dari filter global.
- **Tampilan:** Tampilan berdampingan (side-by-side) atau tabel perbandingan: Total Pemasukan, Total Pengeluaran, Laba/Rugi untuk Periode A vs Periode B, dilengkapi persentase kenaikan/penurunan: `((Nilai_B − Nilai_A) / Nilai_A) × 100%`.
- **Penanganan Edge Case:** Jika `Nilai_A = 0` (pembagi nol), tampilkan indikator "N/A" atau "Baru" alih-alih error/infinity.
- **Interaksi:**
  - Pengguna bebas memilih dua periode pembanding (termasuk kombinasi non-standar, misal Bulan Ini vs periode sama tahun lalu).
  - Klik nilai pada salah satu sisi perbandingan → menampilkan rincian transaksi periode terkait (sama seperti interaksi pada 8.1).

---

## 9. Aturan Bisnis (Business Rules)

| Kode | Aturan |
|---|---|
| BR-1 | `total` pada tabel `pemasukan` selalu dihitung sistem (`jumlah × harga_satuan`), tidak dapat diinput manual oleh pengguna. |
| BR-2 | Seluruh operasi hapus di aplikasi adalah **soft delete** — tidak ada `DELETE` fisik dari sisi antarmuka pengguna. |
| BR-3 | Data yang di-soft-delete (`dihapus_pada IS NOT NULL`) tidak ditampilkan di daftar aktif, dropdown pemilihan, maupun perhitungan agregat dashboard/laporan. |
| BR-4 | Minimal harus ada satu akun Admin aktif (`aktif = TRUE`, `dihapus_pada IS NULL`) di sistem pada waktu mana pun. |
| BR-5 | Pegawai hanya dapat melihat Dashboard jika `dapat_melihat_dashboard = TRUE` pada akunnya. |
| BR-6 | Laba/Rugi = Total Pemasukan − Total Pengeluaran pada periode yang sama (definisi ini **perlu dikonfirmasi** apakah turut memperhitungkan Harga Pokok Penjualan/HPP secara terpisah). |
| BR-7 | Kategori pengeluaran default (Bahan Baku, Operasional, Pengiriman) tidak dapat dihapus jika masih memiliki transaksi terkait aktif — hanya dapat di-soft-delete jika tidak digunakan, atau dinonaktifkan dari pilihan baru namun tetap tampil di data historis. |

---

## 10. Kebutuhan Data & Keterkaitan dengan Skema Database

Dokumen ini selaras dengan skema database `db_keuangan_umkm` yang telah disusun sebelumnya, mencakup tabel: `pengguna`, `kategori_produk`, `produk`, `kategori_pengeluaran`, `pemasukan`, `pengeluaran`, beserta view `v_ringkasan_harian`.

### 10.1 Prinsip Kunci
- Seluruh tabel memiliki kolom `dihapus_pada` untuk mendukung soft delete (lihat Bagian 9, BR-2 & BR-3).
- Seluruh query pembacaan data (listing, dashboard, laporan) **wajib** menyertakan kondisi `WHERE dihapus_pada IS NULL`.
- Relasi `id_pengguna` pada `pemasukan` dan `pengeluaran` mendukung fitur akuntabilitas ("siapa mencatat transaksi ini") tanpa memerlukan tabel log terpisah.

### 10.2 Ringkasan Entitas
| Entitas | Digunakan oleh Fitur |
|---|---|
| `pengguna` | FR-1 (Login), FR-5 (Kelola Pengguna), seluruh fitur (kepemilikan transaksi) |
| `produk`, `kategori_produk` | FR-2 (Kelola Produk), FR-3 (Pemasukan), Dashboard 8.4 & 8.5 |
| `kategori_pengeluaran` | FR-4 (Pengeluaran), Dashboard 8.3 |
| `pemasukan` | FR-3, Dashboard 8.1, 8.2, 8.4, 8.5, 8.6, 8.8, FR-6 (Laporan) |
| `pengeluaran` | FR-4, Dashboard 8.1, 8.2, 8.3, 8.6, 8.8, FR-6 (Laporan) |

---

## 11. Kebutuhan Non-Fungsional

| Kategori | Kebutuhan Detail |
|---|---|
| **Usability** | Antarmuka harus dapat dipahami pengguna dengan literasi digital dasar (persona Bu Sari & Dimas); form input maksimal 5–7 field terlihat sekaligus; gunakan label berbahasa Indonesia yang familiar bagi pelaku UMKM. |
| **Performance** | Update komponen dashboard saat filter berubah < 2 detik; query agregasi harus memanfaatkan index pada `tanggal_transaksi` dan `dihapus_pada` (sudah tersedia di skema). |
| **Security** | Password di-hash (bcrypt/argon2); validasi otorisasi di setiap endpoint (bukan hanya di sisi antarmuka); proteksi terhadap SQL Injection (gunakan prepared statement/ORM); proteksi CSRF pada form. |
| **Reliability** | Soft delete menjamin tidak ada kehilangan data akibat penghapusan; disarankan backup database berkala (harian). |
| **Compatibility** | Web responsif — dapat diakses dari desktop dan perangkat mobile (viewport menyesuaikan); mendukung browser modern (Chrome, Firefox, Edge, Safari versi terbaru). |
| **Maintainability** | Struktur kode modular (terpisah per modul: auth, produk, pemasukan, pengeluaran, pengguna, dashboard); penamaan konsisten mengikuti skema database berbahasa Indonesia. |
| **Scalability** | Sistem harus tetap responsif hingga estimasi volume data [X transaksi/bulan — **perlu ditentukan berdasarkan skala UMKM target**]; index database sudah disiapkan pada kolom filter utama. |
| **Auditability** | Setiap transaksi tercatat dengan `id_pengguna` (pencatat), `dibuat_pada`, dan `diperbarui_pada` sebagai jejak dasar tanpa memerlukan tabel log terpisah. |

---

## 12. Rancangan Antarmuka (Deskripsi Wireframe)

> Catatan: Bagian ini bersifat deskriptif tekstual sebagai acuan awal desain UI, bukan mockup visual final.

### 12.1 Halaman Login
- Logo/nama usaha di bagian atas.
- Form 2 field: Nama Pengguna/Email, Kata Sandi.
- Tombol "Masuk".
- Pesan error tampil di atas form jika login gagal.

### 12.2 Layout Utama (Setelah Login)
- **Sidebar/menu navigasi** kiri: Dashboard, Data Produk, Pemasukan, Pengeluaran, Data Pengguna (khusus Admin), Laporan Keuangan (khusus Admin).
- **Header atas:** nama pengguna yang login, peran, tombol logout.
- Menu yang tidak sesuai hak akses tidak ditampilkan sama sekali (bukan hanya di-disable), untuk kejelasan antarmuka.

### 12.3 Halaman Dashboard
- Baris filter periode di bagian paling atas (selalu terlihat/sticky saat scroll).
- Baris kartu Ringkasan Keuangan (3 kartu) tepat di bawah filter.
- Grid 2 kolom untuk Grafik Tren dan Analisis Kategori Pengeluaran.
- Grid 2 kolom untuk Tren Penjualan Produk dan Produk Terlaris.
- Bagian Perbandingan Periode dan Daftar Transaksi Terkini di bagian bawah.
- Modal/panel geser (slide-over) digunakan konsisten untuk seluruh interaksi "klik untuk detail", agar pengguna tidak kehilangan konteks dashboard utama.

### 12.4 Halaman Form Transaksi (Pemasukan/Pengeluaran)
- Form singkat dalam modal atau halaman terpisah minimal, dioptimalkan untuk input cepat oleh Pegawai.
- Field wajib ditandai jelas (misal tanda bintang merah).
- Tombol "Simpan" dan "Batal" selalu terlihat tanpa perlu scroll (untuk form pendek ini seharusnya tidak masalah).

---

## 13. Penanganan Error & Validasi

### 13.1 Prinsip Umum
- Validasi dilakukan di dua lapis: sisi klien (untuk UX cepat) dan sisi server (wajib, sebagai lapis keamanan utama — tidak boleh mengandalkan validasi klien saja).
- Pesan error menggunakan Bahasa Indonesia yang jelas dan actionable (memberi tahu pengguna apa yang harus diperbaiki).

### 13.2 Tabel Validasi Umum

| Situasi | Perilaku Sistem |
|---|---|
| Field wajib kosong | Tandai field, tampilkan "Kolom ini wajib diisi" |
| Format email tidak valid | "Format email tidak valid" |
| Nilai numerik negatif pada field yang harus ≥ 0 | "Nilai tidak boleh negatif" |
| Duplikasi data unik (username, email, SKU) | "Data sudah digunakan, gunakan nilai lain" |
| Akses tanpa otorisasi | Respons 403, redirect ke halaman yang sesuai hak akses |
| Sesi kedaluwarsa | Redirect ke halaman login dengan pesan "Sesi Anda telah berakhir, silakan masuk kembali" |
| Kegagalan koneksi/server saat submit form | "Terjadi kesalahan, silakan coba lagi" + opsi retry, data form tidak hilang dari layar |
| Pembagi nol pada perhitungan persentase (Perbandingan Periode) | Tampilkan "N/A" alih-alih error atau nilai tak terhingga |

---

## 14. Asumsi, Ketergantungan, dan Batasan

### 14.1 Asumsi
- Aplikasi digunakan oleh satu entitas usaha (bukan multi-tenant) pada versi ini.
- Pengguna memiliki koneksi internet yang stabil (aplikasi berbasis web, bukan offline-first).
- Kategori pengeluaran dapat diperluas oleh Admin di luar tiga kategori default.

### 14.2 Ketergantungan
- Ketersediaan server hosting dan database MySQL 8.0+.
- Library/framework grafik pihak ketiga untuk visualisasi dashboard (perlu dipilih saat tahap desain teknis).

### 14.3 Batasan
- Belum mendukung multi-cabang/multi-outlet.
- Belum ada fitur restore eksplisit dari soft delete melalui antarmuka pengguna pada versi ini (data secara teknis dapat dipulihkan langsung dari database oleh developer/DBA bila diperlukan).
- Definisi Laba/Rugi belum memperhitungkan HPP secara terpisah (lihat BR-6).

---

## 15. Analisis Risiko

| Risiko | Dampak | Kemungkinan | Mitigasi |
|---|---|---|---|
| Pegawai salah input nominal transaksi | Data laporan tidak akurat | Sedang | Validasi input, konfirmasi sebelum simpan, riwayat perubahan via `diperbarui_pada` |
| Volume data tumbuh besar dan memperlambat query dashboard | Performa menurun | Rendah–Sedang (tergantung skala) | Index sudah disiapkan; pertimbangkan agregasi berkala/materialized view jika volume tinggi |
| Kesalahpahaman definisi Laba/Rugi (dengan/tanpa HPP) | Keputusan bisnis keliru | Sedang | Konfirmasi definisi di awal proyek dengan pemilik usaha (lihat Bagian 20) |
| Pegawai mengubah/menghapus transaksi milik pegawai lain tanpa batasan | Kehilangan akuntabilitas | Sedang | Tentukan kebijakan "hanya milik sendiri" (lihat Bagian 6.1) |
| Lupa kata sandi tanpa fitur reset | Pengguna terkunci dari sistem | Sedang | Tambahkan fitur "Lupa Kata Sandi" (belum tercakup di dokumen ini — **perlu ditambahkan**) |

---

## 16. Rencana Rilis / Fase Pengembangan (Usulan)

| Fase | Cakupan | Fokus |
|---|---|---|
| **Fase 1 — Fondasi** | FR-1 (Login), FR-5 (Kelola Pengguna), struktur database, RBAC dasar | Membangun kerangka autentikasi & otorisasi |
| **Fase 2 — Pencatatan Data** | FR-2 (Produk), FR-3 (Pemasukan), FR-4 (Pengeluaran) beserta soft delete | Modul transaksi inti |
| **Fase 3 — Dashboard Dasar** | 8.1 (Ringkasan), 8.2 (Grafik Tren), 8.7 (Filter Periode) | Visibilitas kondisi keuangan dasar |
| **Fase 4 — Dashboard Lanjutan** | 8.3 (Kategori Pengeluaran), 8.4 (Tren Produk), 8.5 (Produk Terlaris), 8.6 (Transaksi Terkini), 8.8 (Perbandingan Periode) | Analisis mendalam |
| **Fase 5 — Laporan & Penyempurnaan** | FR-6 (Laporan Keuangan), ekspor data, pengujian menyeluruh, penyempurnaan UX | Kesiapan rilis |

---

## 17. Kriteria Penerimaan & Definition of Done

Sebuah fitur dianggap **selesai (Done)** jika:
1. Seluruh Kriteria Penerimaan pada tiap FR (Bagian 7) terpenuhi dan sudah diuji.
2. Validasi input berfungsi baik di sisi klien maupun server.
3. Kontrol akses (RBAC) telah diverifikasi tidak dapat dilewati (misal via akses URL langsung).
4. Soft delete diverifikasi tidak menghapus data secara fisik dan data yang di-soft-delete tidak muncul di tampilan/perhitungan aktif.
5. Komponen dashboard terkait sudah diuji dengan data kosong (empty state) dan data besar (untuk memastikan performa).
6. Sudah lolos pengujian oleh minimal satu pengguna dari masing-masing peran (Admin & Pegawai).

---

## 18. Metrik Keberhasilan

- Waktu rata-rata pencatatan satu transaksi < 1 menit.
- Waktu pembaruan dashboard setelah perubahan filter < 2 detik.
- Tidak ada laporan kehilangan data transaksi selama masa uji coba (berkat soft delete).
- Admin dapat menjawab pertanyaan "berapa laba bulan ini?" hanya dengan membuka dashboard, tanpa rekap manual.
- Tingkat adopsi fitur Perbandingan Periode dan Produk Terlaris oleh Admin sebagai indikator nilai tambah analitik.

---

## 19. Glosarium

| Istilah | Definisi |
|---|---|
| **Soft Delete** | Mekanisme "hapus" yang tidak menghapus data secara fisik dari database, melainkan menandainya (kolom `dihapus_pada`) agar tidak muncul di tampilan/perhitungan aktif. |
| **RBAC** | Role-Based Access Control — kontrol akses berdasarkan peran pengguna (Admin/Pegawai). |
| **Laba/Rugi** | Selisih antara Total Pemasukan dan Total Pengeluaran pada periode tertentu. |
| **Periode Aktif** | Rentang tanggal yang sedang dipilih pengguna melalui Filter Periode Interaktif, digunakan sebagai acuan seluruh komponen dashboard. |
| **HPP** | Harga Pokok Penjualan — biaya langsung produksi suatu produk (belum tentu tercakup dalam perhitungan Laba/Rugi versi ini, lihat BR-6). |

---

## 20. Pertanyaan Terbuka (Perlu Konfirmasi Sebelum/Selama Pengembangan)

1. Apakah Pegawai hanya boleh mengubah/menghapus transaksi yang ia catat sendiri, atau boleh mengelola seluruh transaksi tanpa batasan kepemilikan?
2. Apakah Laba/Rugi perlu memperhitungkan HPP secara terpisah, atau cukup selisih Total Pemasukan − Total Pengeluaran?
3. Apakah laporan keuangan perlu fitur ekspor (PDF/Excel)? Jika ya, format mana yang diprioritaskan?
4. Apakah dibutuhkan fitur "Lupa Kata Sandi" / reset password mandiri oleh pengguna?
5. Apakah data yang di-soft-delete perlu fitur "Restore" melalui antarmuka (semacam Recycle Bin), atau cukup dapat dipulihkan manual oleh developer/DBA saat dibutuhkan?
6. Apakah komponen "Daftar Transaksi Terkini" mengikuti filter periode global, atau selalu menampilkan aktivitas terbaru secara independen dari filter?
7. Apakah ada batasan tanggal transaksi (misalnya tidak boleh mencatat transaksi untuk tanggal di masa depan)?
8. Berapa estimasi volume transaksi per bulan yang perlu diakomodasi sistem, untuk keperluan perencanaan performa?

---

## 21. Lampiran

### 21.1 Ringkasan Fitur

| No | Fitur | Modul | Referensi |
|---|---|---|---|
| 1 | Login | Autentikasi | FR-1 |
| 2 | Kelola Data Produk | Master Data | FR-2 |
| 3 | Kelola Data Pemasukan | Transaksi | FR-3 |
| 4 | Kelola Data Pengeluaran | Transaksi | FR-4 |
| 5 | Kelola Data Pengguna | Administrasi | FR-5 |
| 6 | Laporan Keuangan | Reporting | FR-6 |
| 7 | Dashboard Interaktif (8 komponen) | Analytics | Bagian 8 |

### 21.2 Referensi Skema Database
Lihat file terpisah: `skema_database_umkm_kerajinan_kulit.sql` — mencakup definisi lengkap tabel `pengguna`, `kategori_produk`, `produk`, `kategori_pengeluaran`, `pemasukan`, `pengeluaran`, serta view `v_ringkasan_harian`, dengan dukungan soft delete di seluruh entitas.