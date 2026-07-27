# Penjelasan Istilah & Rumus di Aplikasi
## Untuk Pemilik BM Leather Shop — Tanpa Perlu Latar Belakang Akuntansi

Dokumen ini menjelaskan istilah-istilah yang akan Anda temui di dashboard dan laporan aplikasi, dengan bahasa sederhana dan contoh dari usaha kerajinan kulit Anda sendiri. Tidak perlu paham akuntansi untuk mengerti ini — anggap saja seperti seseorang menjelaskan cara kerja aplikasi ke Anda secara langsung.

---

## 1. Pendapatan (Uang Hasil Jualan)

**Artinya:** Total uang yang masuk dari hasil jualan produk — baik dari toko online, offline, maupun pesanan custom (misalnya "Pesanan Bu Hapsah").

**Yang TIDAK termasuk pendapatan:** Uang modal yang Anda setor sendiri ke usaha. Kenapa? Karena itu bukan hasil jualan, itu uang Anda sendiri yang dipindahkan ke kas usaha. Kalau ikut dihitung sebagai pendapatan, nanti kelihatannya usaha untung besar padahal itu cuma uang Anda sendiri yang dipindah-pindah.

> **Contoh:** Bulan ini Anda jual online Rp15.000.000 dan offline Rp10.000.000. Pendapatan = Rp25.000.000. Kalau bulan ini Anda juga nambah modal Rp5.000.000 ke kas usaha, itu **tidak** ikut dihitung di angka Rp25.000.000 tadi.

---

## 2. Retur (Barang Dikembalikan Pelanggan)

**Artinya:** Ketika pelanggan mengembalikan barang dan uangnya dikembalikan (misal karena cacat produksi atau salah ukuran).

**Kenapa penting dipisahkan:** Retur itu **bukan biaya/pengeluaran**, tapi **pengurang hasil jualan**. Bayangkan begini: kalau bulan ini Anda jual Rp25.000.000 tapi ada Rp500.000 barang yang dikembalikan, artinya uang yang benar-benar "nyantol" ke usaha cuma Rp24.500.000 — bukan berarti Anda "keluar biaya" Rp500.000.

---

## 3. Pendapatan Bersih

**Artinya:** Hasil jualan setelah dikurangi retur. Ini angka yang lebih jujur menggambarkan berapa uang jualan yang benar-benar Anda terima.

**Cara hitungnya:**
```
Pendapatan Bersih = Total Hasil Jualan − Retur
```

> **Contoh:** Rp25.000.000 (jualan) − Rp500.000 (retur) = **Rp24.500.000** (Pendapatan Bersih)

---

## 4. HPP — Harga Pokok Penjualan

**Ini istilah yang paling sering bikin bingung, jadi pelan-pelan ya.**

**Artinya:** Biaya bahan yang benar-benar terpakai untuk membuat produk yang **sudah terjual** bulan ini — bukan semua bahan yang Anda beli.

Kenapa harus dibedakan? Karena kadang Anda beli bahan kulit banyak sekaligus (misal buat stok 3 bulan ke depan), tapi belum semuanya jadi produk dan belum semuanya laku. HPP itu khusus menghitung bahan yang "menempel" di produk yang sudah benar-benar terjual.

**Cara hitungnya (pakai data stok):**
```
HPP = Stok Bahan Awal Bulan + Bahan yang Dibeli Bulan Ini − Stok Bahan Akhir Bulan
```

> **Contoh sederhana:** Awal bulan Anda punya stok kulit senilai Rp5.000.000. Bulan ini beli lagi Rp10.000.000. Di akhir bulan, sisa stok kulit yang belum dipakai senilai Rp3.000.000.
> HPP = Rp5.000.000 + Rp10.000.000 − Rp3.000.000 = **Rp12.000.000**
>
> Artinya: bulan ini, bahan senilai Rp12.000.000 benar-benar terpakai jadi produk yang terjual (bukan Rp10.000.000 yang dibeli, karena sebagian stok lama juga ikut kepakai, dan sebagian pembelian baru belum kepakai).

Karena inilah aplikasi Anda sekarang punya **menu Kelola Stok** — supaya angka HPP ini bisa dihitung otomatis oleh sistem, tidak perlu Anda hitung manual satu-satu.

---

## 5. Laba Kotor

**Artinya:** Untung dari jualan produk saja, sebelum dikurangi biaya-biaya menjalankan usaha sehari-hari (packing, marketing, dll).

**Cara hitungnya:**
```
Laba Kotor = Pendapatan Bersih − HPP
```

> **Contoh (lanjutan dari atas):** Rp24.500.000 (Pendapatan Bersih) − Rp12.000.000 (HPP) = **Rp12.500.000** (Laba Kotor)

Angka ini menjawab pertanyaan: *"Kalau cuma dilihat dari harga jual vs harga bahan, apakah produk saya untung?"* Belum termasuk biaya lain seperti packing dan marketing.

---

## 6. Beban Operasional

**Artinya:** Biaya-biaya menjalankan usaha yang **tidak** langsung berhubungan dengan bahan baku produk, seperti:
- **Packing** — biaya bungkus/kemasan
- **Pemasaran** — bayar jasa endorse/iklan/admin marketing
- **Pengiriman** — biaya ongkir kalau ditanggung toko

> **Contoh:** Bulan ini packing Rp800.000, marketing Rp3.500.000, pengiriman Rp400.000.
> Beban Operasional = Rp800.000 + Rp3.500.000 + Rp400.000 = **Rp4.700.000**

---

## 7. Laba Bersih

**Artinya:** Untung usaha yang sesungguhnya — setelah dikurangi HPP **dan** biaya operasional. Ini angka yang paling penting untuk tahu "usaha saya benar-benar untung berapa bulan ini?"

**Cara hitungnya:**
```
Laba Bersih = Laba Kotor − Beban Operasional
```

> **Contoh (lanjutan):** Rp12.500.000 (Laba Kotor) − Rp4.700.000 (Beban Operasional) = **Rp7.800.000** (Laba Bersih)

Inilah angka yang paling jujur menggambarkan hasil usaha Anda bulan ini.

---

## 8. Arus Kas Bersih (Bukan Laba!)

**Artinya:** Selisih semua uang masuk dan semua uang keluar dari kas usaha — **termasuk modal**.

**Kenapa ini beda dari Laba Bersih?** Karena Arus Kas menghitung *pergerakan uang di rekening/kas*, sedangkan Laba menghitung *untung dari jualan*. Contohnya, kalau Anda setor modal Rp10.000.000 ke usaha, kas Anda jadi lebih banyak — tapi itu bukan berarti usaha Anda untung Rp10.000.000, karena uang itu memang uang Anda sendiri.

```
Arus Kas Bersih = Semua Uang Masuk (termasuk modal) − Semua Uang Keluar
```

**Kapan angka ini berguna?** Untuk tahu "apakah uang di kas usaha saya cukup untuk belanja bahan bulan depan?" — bukan untuk menilai untung-rugi usaha. Makanya di dashboard, angka ini ditampilkan terpisah dan diberi warna berbeda dari Laba, supaya tidak tertukar.

---

## 9. Modal

**Artinya:** Uang yang Anda setor sendiri ke usaha (bukan dari hasil jualan). Dicatat terpisah supaya tidak tercampur dengan hasil penjualan, dan tidak membuat Laba kelihatan lebih besar dari yang sebenarnya.

---

## 10. Stok & Mutasi Stok

**Stok:** Jumlah bahan atau produk jadi yang masih tersedia, belum terjual/terpakai.

**Mutasi Stok:** Catatan keluar-masuknya stok — misalnya "masuk" saat Anda beli bahan baru atau selesai produksi, dan "keluar" otomatis saat ada produk yang terjual.

> Contoh: Anda produksi 20 tas kulit → stok tas bertambah 20 (mutasi masuk). Lalu terjual 5 tas → stok otomatis berkurang jadi 15 (mutasi keluar, tercatat otomatis oleh sistem tiap ada transaksi penjualan).

---

## 11. Harga Eceran vs Harga Grosir

**Harga Eceran:** Harga jual normal per satuan produk.

**Harga Grosir:** Harga khusus yang lebih murah per satuan, kalau pembeli beli dalam jumlah banyak sekaligus (misal minimal 3 pcs).

**Aturan yang berlaku di sistem:**
- Kalau transaksi **Offline** dan beli ≥ 3 pcs → otomatis pakai **Harga Grosir**.
- Kalau transaksi **Online**, berapa pun jumlahnya → tetap pakai **Harga Eceran** (sesuai kebijakan toko Anda).

Sistem yang akan menghitung otomatis mana yang berlaku, jadi Anda atau pegawai tidak perlu hitung manual saat mencatat transaksi.

---

## 12. Jenis Transaksi: Online vs Offline

Setiap transaksi penjualan sekarang wajib ditandai asalnya dari mana — **Online** (marketplace, chat WhatsApp, dll) atau **Offline** (dibeli langsung di tempat/pameran). Ini membantu Anda melihat di dashboard: kanal mana yang lebih banyak mendatangkan uang, Online atau Offline.

---

## 13. Kategori Pengeluaran

Semua pengeluaran usaha dikelompokkan jadi 4 kelompok, supaya Anda bisa lihat mana yang paling "makan biaya":

| Kategori | Contoh |
|---|---|
| **Bahan Baku** | Beli kulit, aksesoris (resleting, kancing, lem), bahan tas |
| **Operasional** | Packing/kemasan |
| **Pemasaran** | Bayar jasa marketing/iklan |
| **Pengiriman** | Ongkos kirim (jika ditanggung toko) |

---

## 14. Filter Periode: Minggu Ini, Bulan Ini, dst.

Saat Anda pilih "Bulan Ini" di dashboard, sistem menghitung dari **tanggal 1 sampai akhir bulan berjalan** (bukan 30 hari ke belakang dari hari ini). Begitu juga "Minggu Ini" dihitung dari **Senin sampai Minggu** minggu ini (bukan 7 hari ke belakang). Ini supaya kalau Anda bandingkan bulan ini vs bulan lalu, perbandingannya adil (sama-sama dihitung dari tanggal 1 sampai akhir bulan).

---

## Ringkasan Alur Perhitungan (Dari Atas ke Bawah)

```
Total Hasil Jualan (Online + Offline + Custom)
   − Retur
= Pendapatan Bersih
   − HPP (bahan yang terpakai untuk produk yang terjual)
= Laba Kotor
   − Beban Operasional (Packing + Pemasaran + Pengiriman)
= Laba Bersih  ← ini "untung usaha yang sesungguhnya"
```

Dan terpisah, tidak masuk ke hitungan Laba:
```
Semua Uang Masuk (termasuk Modal) − Semua Uang Keluar = Arus Kas Bersih
```
← ini "apakah kas usaha cukup", bukan "apakah usaha untung"

---

## Contoh Lengkap dalam Satu Bulan

| Item | Nominal |
|---|---|
| Hasil Jualan Online | Rp15.000.000 |
| Hasil Jualan Offline | Rp10.000.000 |
| **Total Hasil Jualan** | **Rp25.000.000** |
| Retur | − Rp500.000 |
| **Pendapatan Bersih** | **Rp24.500.000** |
| HPP (bahan terpakai) | − Rp12.000.000 |
| **Laba Kotor** | **Rp12.500.000** |
| Packing | − Rp800.000 |
| Pemasaran | − Rp3.500.000 |
| Pengiriman | − Rp400.000 |
| **Laba Bersih** | **Rp7.800.000** |

Ditambah, terpisah dari hitungan di atas:
| Item | Nominal |
|---|---|
| Modal disetor bulan ini | Rp5.000.000 |
| **Arus Kas Bersih** (semua masuk − semua keluar, termasuk modal) | Rp13.300.000 |

*(Rp25.000.000 jualan + Rp5.000.000 modal − Rp12.000.000 bahan (harga beli, bukan HPP) − Rp4.700.000 operasional − Rp500.000 retur ≈ Rp13.300.000 — angka arus kas bisa berbeda dari Laba Bersih karena menghitung uang riil keluar-masuk, bukan biaya yang "menempel" ke produk terjual)*

Kalau ada bagian yang masih terasa membingungkan, kabari saja bagian mananya — saya bisa jelaskan ulang dengan contoh dari transaksi Anda sendiri.
