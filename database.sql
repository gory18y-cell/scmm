-- ==============================================================================
-- SISTEM SUPPLY CHAIN MANAGEMENT (SCM) & PENILAIAN SUPPLIER LOGAM OTOMOTIF
-- SKEMA BASIS DATA UTAMA (MYSQL / MARIADB XAMPP COMPATIBLE)
-- ==============================================================================
-- Mata Kuliah : Pemrograman Web Dasar (PHP Native MVC, OOP, PDO Security)
-- Institusi   : STMIK IKMI Cirebon
-- Kelompok    : 1 (Gori, Latifah Noor Azizah, M. Hadi Pratama)
-- Target DB   : db_scm_otomotif
-- Engine      : InnoDB
-- Collation   : utf8mb4_unicode_ci
-- ==============================================================================

-- 1. Inisialisasi Database
CREATE DATABASE IF NOT EXISTS `db_scm_otomotif`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `db_scm_otomotif`;

-- Nonaktifkan pemeriksaan foreign key sementara untuk memastikan urutan pembuatan aman
SET FOREIGN_KEY_CHECKS = 0;

-- ==============================================================================
-- 2. Penghapusan Tabel Terlebih Dahulu (Jika Sudah Ada)
-- ==============================================================================
DROP TABLE IF EXISTS `saw_evaluations`;
DROP TABLE IF EXISTS `saw_criteria`;
DROP TABLE IF EXISTS `purchase_order_details`;
DROP TABLE IF EXISTS `purchase_orders`;
DROP TABLE IF EXISTS `vendor_materials`;
DROP TABLE IF EXISTS `materials`;
DROP TABLE IF EXISTS `vendors`;
DROP TABLE IF EXISTS `users`;

-- Aktifkan kembali pemeriksaan foreign key
SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- 3. Tabel users (Fitur Pendukung - Autentikasi & Akun Operator Sistem)
-- ==============================================================================
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `nama_lengkap` VARCHAR(100) NOT NULL,
    `role` ENUM('Admin', 'Purchasing', 'QC_Auditor') NOT NULL DEFAULT 'Purchasing',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 4. Tabel vendors (Fitur Wajib - Modul 1: Gori - Master Rekanan & Mutu ISO)
-- ==============================================================================
CREATE TABLE `vendors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_vendor` VARCHAR(20) NOT NULL UNIQUE,
    `nama_vendor` VARCHAR(120) NOT NULL,
    `spesialisasi_logam` VARCHAR(100) NOT NULL,
    `kontak_person` VARCHAR(80) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `telepon` VARCHAR(25) NOT NULL,
    `alamat` TEXT NOT NULL,
    `jenis_akreditasi` ENUM('IATF 16949', 'ISO 9001', 'ISO 14001', 'Non-ISO') NOT NULL DEFAULT 'Non-ISO',
    `no_sertifikat` VARCHAR(80) DEFAULT NULL,
    `badan_sertifikasi` VARCHAR(100) DEFAULT NULL,
    `masa_berlaku_iso` DATE DEFAULT NULL,
    `status_verifikasi` ENUM('Terverifikasi', 'Audit Pending', 'Blacklist') NOT NULL DEFAULT 'Audit Pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 5. Tabel materials (Fitur Pendukung - Master Katalog Bahan Baku Logam Otomotif)
-- ==============================================================================
CREATE TABLE `materials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_material` VARCHAR(25) NOT NULL UNIQUE,
    `nama_material` VARCHAR(120) NOT NULL,
    `kategori` ENUM('Plat Baja', 'Pipa Baja', 'Aluminium Alloy', 'Besi Tempa') NOT NULL DEFAULT 'Plat Baja',
    `satuan` VARCHAR(20) NOT NULL DEFAULT 'Kg',
    `spesifikasi_teknis` TEXT DEFAULT NULL,
    `stok_gudang` DECIMAL(12,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 6. Tabel vendor_materials (Fitur Pendukung - Pemetaan Pasokan Logam per Rekanan)
-- ==============================================================================
CREATE TABLE `vendor_materials` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vendor_id` INT NOT NULL,
    `material_id` INT NOT NULL,
    `harga_penawaran` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `standar_lead_time` INT NOT NULL DEFAULT 7,
    `status_pasokan` ENUM('Tersedia', 'Habis', 'Discontinue') NOT NULL DEFAULT 'Tersedia',
    
    -- Menghindari duplikasi kombinasi material yang sama pada satu vendor
    UNIQUE KEY `uq_vendor_material` (`vendor_id`, `material_id`),
    -- Kunci gabungan untuk mendukung Composite Foreign Key ke purchase_order_details
    UNIQUE KEY `uq_vm_vendor` (`id`, `vendor_id`),
    
    -- Integritas referensi
    CONSTRAINT `fk_vm_vendor` FOREIGN KEY (`vendor_id`) 
        REFERENCES `vendors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vm_material` FOREIGN KEY (`material_id`) 
        REFERENCES `materials` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 7. Tabel purchase_orders (Fitur Wajib - Modul 2: Latifah - Header PO & Jadwal Kirim)
-- ==============================================================================
CREATE TABLE `purchase_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_po` VARCHAR(30) NOT NULL UNIQUE,
    `vendor_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `tanggal_po` DATE NOT NULL,
    `lead_time_hari` INT NOT NULL DEFAULT 7,
    `estimasi_kedatangan` DATE NOT NULL,
    `total_nilai` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    `status_po` ENUM('Draft', 'Disetujui', 'Dalam Pengiriman', 'Sebagian Diterima', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Draft',
    `catatan_pengiriman` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Kunci gabungan untuk mendukung Composite Foreign Key ke purchase_order_details
    UNIQUE KEY `uq_po_vendor` (`id`, `vendor_id`),
    
    -- Integritas referensi
    CONSTRAINT `fk_po_vendor` FOREIGN KEY (`vendor_id`) 
        REFERENCES `vendors` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_po_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 8. Tabel purchase_order_details (Fitur Wajib - Modul 2: Latifah - Rincian Item PO)
-- ==============================================================================
CREATE TABLE `purchase_order_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_id` INT NOT NULL,
    `vendor_id` INT NOT NULL,
    `vendor_material_id` INT NOT NULL,
    `material_id` INT DEFAULT NULL,
    `jumlah_pesanan` DECIMAL(12,2) NOT NULL DEFAULT 1.00,
    `jumlah_diterima` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `harga_satuan` DECIMAL(14,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    
    -- Indeks relasi gabungan
    KEY `idx_pod_po_vendor` (`po_id`, `vendor_id`),
    KEY `idx_pod_vm_vendor` (`vendor_material_id`, `vendor_id`),
    KEY `idx_pod_material` (`material_id`),
    
    -- COMPOSITE FOREIGN KEY GANDA:
    -- Mengunci secara fisik bahwa item material pesanan WAJIB berasal dari vendor yang sama dengan Header PO
    CONSTRAINT `fk_pod_po_vendor` FOREIGN KEY (`po_id`, `vendor_id`) 
        REFERENCES `purchase_orders` (`id`, `vendor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_pod_vm_vendor` FOREIGN KEY (`vendor_material_id`, `vendor_id`) 
        REFERENCES `vendor_materials` (`id`, `vendor_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_pod_material` FOREIGN KEY (`material_id`) 
        REFERENCES `materials` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 8a. Tabel po_receipts (Penerimaan Fisik Gudang & Surat Jalan)
-- ==============================================================================
CREATE TABLE `po_receipts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `po_id` INT NOT NULL,
    `no_penerimaan` VARCHAR(35) NOT NULL UNIQUE,
    `tanggal_terima` DATE NOT NULL,
    `user_id` INT NOT NULL,
    `catatan` TEXT DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_rcpt_po` (`po_id`),
    KEY `idx_rcpt_user` (`user_id`),
    CONSTRAINT `fk_rcpt_po` FOREIGN KEY (`po_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rcpt_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 8b. Tabel po_receipt_details (Rincian Item Penerimaan Fisik Bahan Baku)
-- ==============================================================================
CREATE TABLE `po_receipt_details` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `receipt_id` INT NOT NULL,
    `po_detail_id` INT NOT NULL,
    `material_id` INT NOT NULL,
    `jumlah_diterima` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_rcpt_dtl_receipt` (`receipt_id`),
    KEY `idx_rcpt_dtl_pod` (`po_detail_id`),
    KEY `idx_rcpt_dtl_mat` (`material_id`),
    CONSTRAINT `fk_rcpt_dtl_receipt` FOREIGN KEY (`receipt_id`) REFERENCES `po_receipts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rcpt_dtl_pod` FOREIGN KEY (`po_detail_id`) REFERENCES `purchase_order_details` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_rcpt_dtl_mat` FOREIGN KEY (`material_id`) REFERENCES `materials` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 9. Tabel saw_criteria (Fitur Wajib - Modul 3: Hadi - Kriteria & Bobot Dinamis)
-- ==============================================================================
CREATE TABLE `saw_criteria` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_kriteria` VARCHAR(10) NOT NULL UNIQUE,
    `nama_kriteria` VARCHAR(100) NOT NULL,
    `bobot` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `tipe` ENUM('Benefit', 'Cost') NOT NULL DEFAULT 'Benefit',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    
    -- Batas rentang nilai persentase bobot kriteria (0.00 s/d 100.00)
    CONSTRAINT `chk_saw_bobot` CHECK (`bobot` >= 0.00 AND `bobot` <= 100.00)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==============================================================================
-- 10. Tabel saw_evaluations (Fitur Wajib - Modul 3: Hadi - Matriks Skor Alternatif)
-- ==============================================================================
CREATE TABLE `saw_evaluations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vendor_id` INT NOT NULL,
    `criteria_id` INT NOT NULL,
    `nilai` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `periode_evaluasi` VARCHAR(20) NOT NULL,
    
    -- Mencegah duplikasi skor untuk vendor dan kriteria yang sama pada satu periode evaluasi
    UNIQUE KEY `uq_eval_vendor_kriteria` (`vendor_id`, `criteria_id`, `periode_evaluasi`),
    
    -- Integritas referensi
    CONSTRAINT `fk_eval_vendor` FOREIGN KEY (`vendor_id`) 
        REFERENCES `vendors` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_eval_criteria` FOREIGN KEY (`criteria_id`) 
        REFERENCES `saw_criteria` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

