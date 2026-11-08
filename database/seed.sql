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

