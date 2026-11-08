-- ==============================================================================
-- SISTEM SUPPLY CHAIN MANAGEMENT (SCM) & PENILAIAN SUPPLIER LOGAM OTOMOTIF
-- SKEMA BASIS DATA UTAMA (MYSQL / MARIADB)
-- ==============================================================================
-- Target DB   : db_scm_otomotif (atau nama database hosting Anda di InfinityFree)
-- Engine      : InnoDB
-- Collation   : utf8mb4_unicode_ci
-- ==============================================================================

-- 1. Inisialisasi Database
-- CATATAN HOSTING (InfinityFree/cPanel):
-- Jangan gunakan CREATE DATABASE / USE di hosting karena dibatasi oleh sistem hosting.
-- Di phpMyAdmin hosting: Klik nama database Anda di panel kiri terlebih dahulu, lalu Import.
-- Hapus tanda komentar (uncomment) baris di bawah hanya jika impor di localhost XAMPP baru:
-- CREATE DATABASE IF NOT EXISTS `db_scm_otomotif` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE `db_scm_otomotif`;

-- Nonaktifkan pemeriksaan foreign key sementara untuk memastikan urutan pembuatan aman
SET FOREIGN_KEY_CHECKS = 0;

-- ==============================================================================
-- 2. Penghapusan Tabel Terlebih Dahulu (Jika Sudah Ada)
-- ==============================================================================
DROP TABLE IF EXISTS `saw_evaluations`;
DROP TABLE IF EXISTS `saw_criteria`;
DROP TABLE IF EXISTS `po_receipt_details`;
DROP TABLE IF EXISTS `po_receipts`;
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

-- ==============================================================================
-- SISTEM SUPPLY CHAIN MANAGEMENT (SCM) INDUSTRI MANUFAKTUR OTOMOTIF
-- SEED DATA AWAL UNTUK PENGUJIAN DAN DEMO SISTEM
-- ==============================================================================
-- File: database/seed.sql
-- Catatan: Script ini AMAN dijalankan berulang kali (Idempoten).
-- Script ini TIDAK menghapus tabel atau data yang sudah ada sebelumnya.
-- ==============================================================================

-- USE `db_scm_otomotif`;

-- 1. Akun Pengguna (Users)
-- Password default untuk semua akun: password123
-- Hash Bcrypt valid teruji: $2y$10$4Mx/SCNBTCGa.lBNv0Of7.U8TPPnuyyQEOTgKWRBI0TJKqZMa5jyi
INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES
('admin', '$2y$10$4Mx/SCNBTCGa.lBNv0Of7.U8TPPnuyyQEOTgKWRBI0TJKqZMa5jyi', 'Administrator SCM', 'Admin'),
('purchasing', '$2y$10$4Mx/SCNBTCGa.lBNv0Of7.U8TPPnuyyQEOTgKWRBI0TJKqZMa5jyi', 'Staff Purchasing Logam', 'Purchasing'),
('qc_auditor', '$2y$10$4Mx/SCNBTCGa.lBNv0Of7.U8TPPnuyyQEOTgKWRBI0TJKqZMa5jyi', 'QC & Auditor Mutu', 'QC_Auditor')
ON DUPLICATE KEY UPDATE 
    `nama_lengkap` = VALUES(`nama_lengkap`),
    `role` = VALUES(`role`),
    `password` = VALUES(`password`);

-- 2. Master Rekanan Vendor Logam (Vendors)
-- Memuat vendor berstatus 'Terverifikasi' dan 'Audit Pending'
INSERT INTO `vendors` (
    `kode_vendor`, `nama_vendor`, `spesialisasi_logam`, `kontak_person`, 
    `email`, `telepon`, `alamat`, `jenis_akreditasi`, `no_sertifikat`, 
    `badan_sertifikasi`, `masa_berlaku_iso`, `status_verifikasi`
) VALUES
(
    'VND-001', 'PT Krakatau Steel Perkasa', 'Plat Baja SPCC & Coil Baja', 'Bambang Sutedjo', 
    'sales@krakatausteel.co.id', '021-8976543', 'Kawasan Industri Cilegon Kav. 12, Banten', 
    'IATF 16949', 'IATF-16949-ID-2024', 'TUV Rheinland', '2028-12-31', 'Terverifikasi'
),
(
    'VND-002', 'PT Toyota Tsusho Metals Indonesia', 'Pipa Baja Seamless & Rod Bar', 'Irwan Gunawan', 
    'info@ttmetals.co.id', '021-8934567', 'Kawasan Industri MM2100 Blok C-3, Cikarang Barat', 
    'ISO 9001', 'ISO-9001-ID-2023', 'SGS Indonesia', '2027-08-15', 'Terverifikasi'
),
(
    'VND-003', 'PT Alumindo Light Metal Industry', 'Aluminium Sheet & Extrusion', 'Dewi Sartika', 
    'contact@alumindo.com', '031-7890123', 'Kawasan Industri Rungkut Industri III No. 45, Surabaya', 
    'ISO 14001', 'ISO-14001-ID-2024', 'BSI Group', '2027-11-20', 'Terverifikasi'
),
(
    'VND-004', 'PT Cipta Logam Presisi', 'Baja Karbon Tinggi & Plat Bordes', 'Hendra Wijaya', 
    'sales@ciptalogam.co.id', '021-5432100', 'Kawasan Industri Jababeka Tahap II Blok J-5, Cikarang', 
    'Non-ISO', NULL, NULL, NULL, 'Terverifikasi'
),
(
    'VND-005', 'PT Sentosa Baja Nusantara', 'Pipa Welded & Baja Hollow', 'Agus Santoso', 
    'agus@sentosabaja.com', '022-7654321', 'Jl. Raya Industri Leuwigajah No. 88, Cimahi Bandung', 
    'ISO 9001', 'ISO-9001-BDG-2024', 'Mutu Agung', '2026-11-30', 'Audit Pending'
),
(
    'VND-006', 'CV Logam Anugerah Abadi', 'Aluminium Scrap & Profil Standar', 'Rudi Hermawan', 
    'rudi@logamanugerah.com', '024-8765432', 'Kawasan Industri Candi Blok A-10, Semarang', 
    'Non-ISO', NULL, NULL, NULL, 'Audit Pending'
)
ON DUPLICATE KEY UPDATE 
    `nama_vendor` = VALUES(`nama_vendor`),
    `spesialisasi_logam` = VALUES(`spesialisasi_logam`),
    `status_verifikasi` = VALUES(`status_verifikasi`);

-- 3. Master Katalog Bahan Baku Logam (Materials)
INSERT INTO `materials` (`kode_material`, `nama_material`, `kategori`, `satuan`, `spesifikasi_teknis`, `stok_gudang`) VALUES
('MAT-SPCC-01', 'Plat Baja SPCC JIS G3141', 'Plat Baja', 'Lembar', 'Tebal 1.2mm x Lebar 1200mm x Panjang 2400mm (Komponen Sasis)', 150.00),
('MAT-SEAM-02', 'Pipa Baja Seamless ASTM A53', 'Pipa Baja', 'Batang', 'Diameter 2 inch, Sch 40, Panjang 6 meter (Sistem Knalpot/Exhaust)', 80.00),
('MAT-ALUM-03', 'Aluminium Sheet 5052-H32', 'Aluminium Alloy', 'Lembar', 'Tebal 2.0mm x Lebar 1220mm x Panjang 2440mm (Panel Pintu/Body)', 65.00),
('MAT-ROD-04', 'Baja Batangan Round Bar S45C', 'Besi Tempa', 'Batang', 'Diameter 25mm x Panjang 6 meter, Carbon Steel JIS G4051 (Poros/Axle)', 120.00)
ON DUPLICATE KEY UPDATE 
    `nama_material` = VALUES(`nama_material`),
    `spesifikasi_teknis` = VALUES(`spesifikasi_teknis`);

-- 4. Pemetaan Pasokan Vendor dan Harga Penawaran (Vendor Materials)
-- Menghubungkan vendor terverifikasi dengan material yang disuplai
INSERT INTO `vendor_materials` (`vendor_id`, `material_id`, `harga_penawaran`, `standar_lead_time`, `status_pasokan`)
SELECT v.`id`, m.`id`, 350000.00, 4, 'Tersedia'
FROM `vendors` v, `materials` m
WHERE v.`kode_vendor` = 'VND-001' AND m.`kode_material` = 'MAT-SPCC-01'
ON DUPLICATE KEY UPDATE `harga_penawaran` = VALUES(`harga_penawaran`), `standar_lead_time` = VALUES(`standar_lead_time`), `status_pasokan` = VALUES(`status_pasokan`);

INSERT INTO `vendor_materials` (`vendor_id`, `material_id`, `harga_penawaran`, `standar_lead_time`, `status_pasokan`)
SELECT v.`id`, m.`id`, 185000.00, 6, 'Tersedia'
FROM `vendors` v, `materials` m
WHERE v.`kode_vendor` = 'VND-001' AND m.`kode_material` = 'MAT-ROD-04'
ON DUPLICATE KEY UPDATE `harga_penawaran` = VALUES(`harga_penawaran`), `standar_lead_time` = VALUES(`standar_lead_time`), `status_pasokan` = VALUES(`status_pasokan`);

INSERT INTO `vendor_materials` (`vendor_id`, `material_id`, `harga_penawaran`, `standar_lead_time`, `status_pasokan`)
SELECT v.`id`, m.`id`, 275000.00, 5, 'Tersedia'
FROM `vendors` v, `materials` m
WHERE v.`kode_vendor` = 'VND-002' AND m.`kode_material` = 'MAT-SEAM-02'
ON DUPLICATE KEY UPDATE `harga_penawaran` = VALUES(`harga_penawaran`), `standar_lead_time` = VALUES(`standar_lead_time`), `status_pasokan` = VALUES(`status_pasokan`);

INSERT INTO `vendor_materials` (`vendor_id`, `material_id`, `harga_penawaran`, `standar_lead_time`, `status_pasokan`)
SELECT v.`id`, m.`id`, 190000.00, 5, 'Tersedia'
FROM `vendors` v, `materials` m
WHERE v.`kode_vendor` = 'VND-002' AND m.`kode_material` = 'MAT-ROD-04'
ON DUPLICATE KEY UPDATE `harga_penawaran` = VALUES(`harga_penawaran`), `standar_lead_time` = VALUES(`standar_lead_time`), `status_pasokan` = VALUES(`status_pasokan`);

INSERT INTO `vendor_materials` (`vendor_id`, `material_id`, `harga_penawaran`, `standar_lead_time`, `status_pasokan`)
SELECT v.`id`, m.`id`, 420000.00, 7, 'Tersedia'
FROM `vendors` v, `materials` m
WHERE v.`kode_vendor` = 'VND-003' AND m.`kode_material` = 'MAT-ALUM-03'
ON DUPLICATE KEY UPDATE `harga_penawaran` = VALUES(`harga_penawaran`), `standar_lead_time` = VALUES(`standar_lead_time`), `status_pasokan` = VALUES(`status_pasokan`);

INSERT INTO `vendor_materials` (`vendor_id`, `material_id`, `harga_penawaran`, `standar_lead_time`, `status_pasokan`)
SELECT v.`id`, m.`id`, 340000.00, 5, 'Tersedia'
FROM `vendors` v, `materials` m
WHERE v.`kode_vendor` = 'VND-004' AND m.`kode_material` = 'MAT-SPCC-01'
ON DUPLICATE KEY UPDATE `harga_penawaran` = VALUES(`harga_penawaran`), `standar_lead_time` = VALUES(`standar_lead_time`), `status_pasokan` = VALUES(`status_pasokan`);

INSERT INTO `vendor_materials` (`vendor_id`, `material_id`, `harga_penawaran`, `standar_lead_time`, `status_pasokan`)
SELECT v.`id`, m.`id`, 280000.00, 6, 'Tersedia'
FROM `vendors` v, `materials` m
WHERE v.`kode_vendor` = 'VND-004' AND m.`kode_material` = 'MAT-SEAM-02'
ON DUPLICATE KEY UPDATE `harga_penawaran` = VALUES(`harga_penawaran`), `standar_lead_time` = VALUES(`standar_lead_time`), `status_pasokan` = VALUES(`status_pasokan`);

-- 5. Kriteria Pembobotan SPK SAW (saw_criteria)
-- Total bobot kriteria aktif tepat 100.00% (25 + 25 + 20 + 15 + 15 = 100%)
INSERT INTO `saw_criteria` (`kode_kriteria`, `nama_kriteria`, `bobot`, `tipe`, `is_active`) VALUES
('C1', 'Kualitas & Presisi Bahan Logam', 25.00, 'Benefit', 1),
('C2', 'Daya Saing Harga Penawaran', 25.00, 'Cost', 1),
('C3', 'Ketepatan Waktu & Lead Time Logistik', 20.00, 'Cost', 1),
('C4', 'Kapasitas Pasokan & Fleksibilitas', 15.00, 'Benefit', 1),
('C5', 'Sertifikasi Sistem Mutu ISO/IATF', 15.00, 'Benefit', 1)
ON DUPLICATE KEY UPDATE 
    `nama_kriteria` = VALUES(`nama_kriteria`),
    `bobot` = VALUES(`bobot`),
    `tipe` = VALUES(`tipe`),
    `is_active` = VALUES(`is_active`);

-- 6. Nilai Matriks Evaluasi Alternatif Vendor (saw_evaluations)
-- Periode Evaluasi: '2026-Q4' (Skala Penilaian Standar Industri Otomotif: 1 - 100)
-- Vendor VND-001 (Krakatau Steel)
INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 92.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-001' AND c.`kode_kriteria` = 'C1'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 85.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-001' AND c.`kode_kriteria` = 'C2'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 75.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-001' AND c.`kode_kriteria` = 'C3'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 90.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-001' AND c.`kode_kriteria` = 'C4'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 95.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-001' AND c.`kode_kriteria` = 'C5'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

-- Vendor VND-002 (Toyota Tsusho Metals)
INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 90.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-002' AND c.`kode_kriteria` = 'C1'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 80.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-002' AND c.`kode_kriteria` = 'C2'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 70.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-002' AND c.`kode_kriteria` = 'C3'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 85.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-002' AND c.`kode_kriteria` = 'C4'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 90.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-002' AND c.`kode_kriteria` = 'C5'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

-- Vendor VND-003 (Alumindo Light Metal)
INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 88.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-003' AND c.`kode_kriteria` = 'C1'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 78.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-003' AND c.`kode_kriteria` = 'C2'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 80.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-003' AND c.`kode_kriteria` = 'C3'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 80.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-003' AND c.`kode_kriteria` = 'C4'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 85.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-003' AND c.`kode_kriteria` = 'C5'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

-- Vendor VND-004 (Cipta Logam Presisi)
INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 80.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-004' AND c.`kode_kriteria` = 'C1'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 70.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-004' AND c.`kode_kriteria` = 'C2'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 85.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-004' AND c.`kode_kriteria` = 'C3'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 75.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-004' AND c.`kode_kriteria` = 'C4'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`)
SELECT v.`id`, c.`id`, 60.00, '2026-Q4' FROM `vendors` v, `saw_criteria` c WHERE v.`kode_vendor` = 'VND-004' AND c.`kode_kriteria` = 'C5'
ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`);

