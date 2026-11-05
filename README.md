# Sistem Supply Chain Management (SCM) Industri Manufaktur Otomotif
### Penilaian Kelayakan Supplier Bahan Logam Berbasis SPK SAW

> **Mata Kuliah:** Pemrograman Web Dasar (PHP Native MVC, OOP & Keamanan PDO)  
> **Institusi:** STMIK IKMI Cirebon — Program Studi S1 Sistem Informasi  
> **Dosen Pengampu:** Rosidin, S. Kom., M. Kom.  
> **Tahun Akademik:** 2026/2027 (Semester Ganjil)  

---

## 👥 Tim Pengembang (Kelompok 1)

| No | NIM | Nama Mahasiswa | Modul Tanggung Jawab Utama |
| :---: | :---: | :--- | :--- |
| 1 | **43250583** | **GORI** | **Modul 1:** CRUD Master Rekanan Vendor Logam & Akreditasi Mutu ISO (*Form verifikasi, live filter JS, badge status mutu*) |
| 2 | **43250555** | **LATIFAH NOOR AZIZAH** | **Modul 2:** CRUD Pengadaan Bahan Baku (Purchase Order) & Jadwal Kirim (*Form transaksi dinamis JS, hitung subtotal otomatis*) |
| 3 | **43250562** | **M. HADI PRATAMA** | **Modul 3:** CRUD Kriteria Pembobotan & Perhitungan SPK SAW Supplier Terbaik (*Matriks normalisasi, ranking bar visual CSS*) |

---

## 📁 Struktur Direktori Proyek

```text
scm/
├── app/
│   ├── controllers/      <- Controller penanganan request & alur MVC
│   ├── models/           <- Model pengolahan data & query PDO berparameter
│   ├── views/            <- Tampilan antarmuka pengguna (HTML5 semantik)
│   └── core/             <- Kelas inti (Base Controller, Session Guard)
├── config/
│   └── database.php      <- Kelas koneksi PDO Strict Mode (Anti SQL Injection)
├── public/
│   ├── assets/
│   │   ├── css/          <- Lembar gaya modern (Industrial Automotive Style)
│   │   ├── js/           <- Skrip interaktivitas Vanilla JS
│   │   └── images/       <- Aset visual, logo, dan ikon
│   └── index.php         <- Pintu masuk aplikasi (Front Controller)
├── database.sql          <- Skema basis data MySQL (8 Tabel relasional)
└── README.md             <- Dokumentasi proyek
```

---

## ⚙️ Persyaratan Lingkungan Pengembangan

1. **Web Server & Database:** XAMPP / Laragon (Apache 2.4+, MySQL 8.0 / MariaDB 10.4+)
2. **Bahasa Pemrograman:** PHP 7.4+ atau PHP 8.x
3. **Ekstensi PHP Wajib:** `pdo`, `pdo_mysql`

---

## 🚀 Panduan Instalasi & Menjalankan

1. **Persiapan Database:**
   * Buka **XAMPP Control Panel** dan aktifkan layanan **Apache** serta **MySQL**.
   * Buka phpMyAdmin di browser: `http://localhost/phpmyadmin`.
   * Klik menu **Import**, pilih berkas `database.sql`, lalu klik **Kirim** / **Import**.
   * Basis data `db_scm_otomotif` dengan 8 tabel relasional akan otomatis dibuat.

2. **Konfigurasi Database:**
   * Konfigurasi koneksi tersimpan di `config/database.php`:
     * Host: `127.0.0.1`
     * Database: `db_scm_otomotif`
     * Username: `root`
     * Password: *(kosong)*
     * Mode: `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`, `PDO::ATTR_EMULATE_PREPARES => false`.

3. **Menjalankan Aplikasi:**
   * Melalui server built-in PHP dari root proyek:
     ```bash
     php -S localhost:8000 -t public
     ```
   * Buka browser dan akses: `http://localhost:8000`.

