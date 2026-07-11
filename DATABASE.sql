-- =====================================================================
-- SKEMA DATABASE
-- Aplikasi Pengelolaan Keuangan UMKM Kerajinan Kulit
-- DBMS: MySQL 8.0+
--
-- KONVENSI PENAMAAN (mixed convention):
-- - Nama tabel & kolom TEKNIS (id, foreign key, flag boolean, timestamp)
--   tetap menggunakan Bahasa Inggris: id, created_at, updated_at,
--   deleted_at, is_active, username, password, dst.
-- - Nama kolom yang merepresentasikan ISTILAH BISNIS/DOMAIN tetap
--   menggunakan Bahasa Indonesia agar sesuai konteks skripsi/UMKM:
--   nama, harga, jumlah, kategori, peran, tanggal_transaksi, dst.
-- - Seluruh proses hapus menggunakan SOFT DELETE (kolom deleted_at
--   diisi timestamp, bukan DELETE fisik).
-- =====================================================================

CREATE DATABASE IF NOT EXISTS db_keuangan_umkm
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_keuangan_umkm;

-- =====================================================================
-- 1. TABEL: USERS
-- Menyimpan akun pengguna beserta peran (role) dan hak akses dashboard
-- =====================================================================
CREATE TABLE users (
    id                      BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama                    VARCHAR(100)    NOT NULL,
    username                VARCHAR(50)     NOT NULL,
    email                   VARCHAR(100)    NOT NULL,
    password                VARCHAR(255)    NOT NULL COMMENT 'Password ter-hash (bcrypt/argon2)',
    peran                   ENUM('admin', 'pegawai') NOT NULL DEFAULT 'pegawai',
    dapat_melihat_dashboard BOOLEAN         NOT NULL DEFAULT FALSE
        COMMENT 'Khusus peran pegawai; admin selalu TRUE secara logika aplikasi',
    is_active               BOOLEAN         NOT NULL DEFAULT TRUE,
    created_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    deleted_at              TIMESTAMP       NULL DEFAULT NULL COMMENT 'Soft delete; NULL = data aktif',
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email UNIQUE (email),
    INDEX idx_users_deleted_at (deleted_at)
) ENGINE=InnoDB;


-- =====================================================================
-- 2. TABEL: PRODUCT_CATEGORIES
-- Kategori produk kerajinan kulit
-- =====================================================================
CREATE TABLE product_categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100)    NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP       NULL DEFAULT NULL COMMENT 'Soft delete; NULL = data aktif',
    CONSTRAINT uq_product_categories_nama UNIQUE (nama),
    INDEX idx_product_categories_deleted_at (deleted_at)
) ENGINE=InnoDB;


-- =====================================================================
-- 3. TABEL: PRODUCTS
-- Data master produk yang dijual
-- =====================================================================
CREATE TABLE products (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id     BIGINT UNSIGNED     NULL,
    nama            VARCHAR(150)        NOT NULL,
    sku             VARCHAR(50)         NULL,
    harga           DECIMAL(15,2)       NOT NULL DEFAULT 0,
    deskripsi       TEXT                NULL,
    is_active       BOOLEAN             NOT NULL DEFAULT TRUE,
    created_by      BIGINT UNSIGNED     NULL COMMENT 'FK ke users.id (admin pembuat)',
    created_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP           NULL DEFAULT NULL COMMENT 'Soft delete; NULL = data aktif',
    CONSTRAINT uq_products_sku UNIQUE (sku),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES product_categories(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_products_created_by
        FOREIGN KEY (created_by) REFERENCES users(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_products_deleted_at (deleted_at)
) ENGINE=InnoDB;


-- =====================================================================
-- 4. TABEL: EXPENSE_CATEGORIES
-- Kategori pengeluaran: Bahan Baku, Operasional, Pengiriman, dst.
-- =====================================================================
CREATE TABLE expense_categories (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nama        VARCHAR(100)    NOT NULL,
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
                                ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP       NULL DEFAULT NULL COMMENT 'Soft delete; NULL = data aktif',
    CONSTRAINT uq_expense_categories_nama UNIQUE (nama),
    INDEX idx_expense_categories_deleted_at (deleted_at)
) ENGINE=InnoDB;

INSERT INTO expense_categories (nama) VALUES
    ('Bahan Baku'),
    ('Operasional'),
    ('Pengiriman');


-- =====================================================================
-- 5. TABEL: INCOMES (PEMASUKAN)
-- Transaksi penjualan produk. jumlah x harga_satuan disimpan sebagai
-- harga_satuan untuk menjaga histori harga meskipun harga produk berubah.
-- =====================================================================
CREATE TABLE incomes (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id          BIGINT UNSIGNED     NULL,
    user_id             BIGINT UNSIGNED     NOT NULL COMMENT 'Pencatat transaksi (admin/pegawai)',
    tanggal_transaksi   DATE                NOT NULL,
    jumlah              INT UNSIGNED        NOT NULL DEFAULT 1 COMMENT 'Kuantitas produk terjual',
    harga_satuan        DECIMAL(15,2)       NOT NULL,
    total               DECIMAL(15,2)       NOT NULL COMMENT 'jumlah * harga_satuan',
    keterangan          VARCHAR(255)        NULL,
    created_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP           NULL DEFAULT NULL COMMENT 'Soft delete; NULL = data aktif',
    CONSTRAINT fk_incomes_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_incomes_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_incomes_tanggal_transaksi (tanggal_transaksi),
    INDEX idx_incomes_product (product_id),
    INDEX idx_incomes_deleted_at (deleted_at)
) ENGINE=InnoDB;


-- =====================================================================
-- 6. TABEL: EXPENSES (PENGELUARAN)
-- =====================================================================
CREATE TABLE expenses (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id         BIGINT UNSIGNED     NOT NULL,
    user_id             BIGINT UNSIGNED     NOT NULL COMMENT 'Pencatat transaksi',
    tanggal_transaksi   DATE                NOT NULL,
    nominal             DECIMAL(15,2)       NOT NULL,
    keterangan          VARCHAR(255)        NULL,
    created_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP
                                            ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP           NULL DEFAULT NULL COMMENT 'Soft delete; NULL = data aktif',
    CONSTRAINT fk_expenses_category
        FOREIGN KEY (category_id) REFERENCES expense_categories(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_expenses_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_expenses_tanggal_transaksi (tanggal_transaksi),
    INDEX idx_expenses_category (category_id),
    INDEX idx_expenses_deleted_at (deleted_at)
) ENGINE=InnoDB;


-- =====================================================================
-- VIEW BANTUAN (opsional): Ringkasan Laba/Rugi Harian
-- Mempermudah query dashboard "Ringkasan Keuangan" & "Grafik Tren"
-- Hanya menghitung transaksi yang belum di-soft-delete
-- =====================================================================
CREATE OR REPLACE VIEW v_ringkasan_harian AS
SELECT
    d.tanggal,
    COALESCE(i.total_pemasukan, 0)  AS total_pemasukan,
    COALESCE(e.total_pengeluaran, 0) AS total_pengeluaran,
    COALESCE(i.total_pemasukan, 0) - COALESCE(e.total_pengeluaran, 0) AS laba_rugi
FROM (
    SELECT tanggal_transaksi AS tanggal FROM incomes WHERE deleted_at IS NULL
    UNION
    SELECT tanggal_transaksi AS tanggal FROM expenses WHERE deleted_at IS NULL
) d
LEFT JOIN (
    SELECT tanggal_transaksi, SUM(total) AS total_pemasukan
    FROM incomes
    WHERE deleted_at IS NULL
    GROUP BY tanggal_transaksi
) i ON i.tanggal_transaksi = d.tanggal
LEFT JOIN (
    SELECT tanggal_transaksi, SUM(nominal) AS total_pengeluaran
    FROM expenses
    WHERE deleted_at IS NULL
    GROUP BY tanggal_transaksi
) e ON e.tanggal_transaksi = d.tanggal;