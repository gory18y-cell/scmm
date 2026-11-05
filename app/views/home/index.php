<!-- ==============================================================================
     KONTEN UTAMA: DASHBOARD SISTEM SCM OTOMOTIF
     ============================================================================== -->

<!-- Alert Informasi Sistem -->
<div class="alert-box alert-info">
    <div style="font-size: 20px;">ℹ️</div>
    <div>
        <strong>Arsitektur MVC & Global Layout Aktif:</strong>
        <div>Sistem SCM Industri Otomotif siap untuk tahap implementasi modul fungsional. Seluruh navigasi sidebar terhubung ke Router berorientasi objek dengan keamanan koneksi PDO Strict Mode.</div>
    </div>
</div>

<!-- Kartu Informasi Studi Kasus & Integrasi -->
<div class="card-panel">
    <div class="card-title">
        <span>🏭</span> Gambaran Umum Sistem Rantai Pasok Logam Otomotif
    </div>
    <div class="card-subtitle">
        Integrasi alur pengadaan material baja/logam dari akreditasi mutu supplier hingga kalkulasi analitik SPK SAW dan penerbitan Purchase Order.
    </div>

    <!-- Grid Distribusi Modul Kelompok 1 -->
    <div class="grid-cols-3">
        <!-- Modul 1: Gori -->
        <div class="metric-card" style="border-left-color: #0F2C59;">
            <div class="metric-header">
                <div class="metric-title">Modul 1: Master Rekanan</div>
                <span class="metric-badge" style="background: #DEF7EC; color: #03543F;">Gori (43250583)</span>
            </div>
            <div class="metric-value" style="font-size: 16px;">Vendor & Mutu ISO</div>
            <div class="metric-desc">
                Pengelolaan rekanan pabrik logam, verifikasi sertifikat mutu (IATF 16949 / ISO 9001), live filter Vanilla JS, dan badge status ganda.
            </div>
            <div style="margin-top: 15px;">
                <a href="/scm/vendor" style="font-weight: 700; font-size: 13px;">Buka Master Vendor →</a>
            </div>
        </div>

        <!-- Modul 2: Latifah -->
        <div class="metric-card" style="border-left-color: #1E56A0;">
            <div class="metric-header">
                <div class="metric-title">Modul 2: Pengadaan Logam</div>
                <span class="metric-badge" style="background: #E0F2FE; color: #0369A1;">Latifah (43250555)</span>
            </div>
            <div class="metric-value" style="font-size: 16px;">Purchase Order & Jadwal</div>
            <div class="metric-desc">
                Transaksi pengadaan bahan baku, form dinamis multi-item JS, kalkulasi subtotal otomatis, dan estimasi waktu tiba material (ETA).
            </div>
            <div style="margin-top: 15px;">
                <a href="/scm/purchase-order" style="font-weight: 700; font-size: 13px;">Buka Purchase Order →</a>
            </div>
        </div>

        <!-- Modul 3: Hadi -->
        <div class="metric-card" style="border-left-color: #3B82F6;">
            <div class="metric-header">
                <div class="metric-title">Modul 3: Keputusan SAW</div>
                <span class="metric-badge" style="background: #FEF08A; color: #854D0E;">Hadi (43250562)</span>
            </div>
            <div class="metric-value" style="font-size: 16px;">SPK Supplier Terbaik</div>
            <div class="metric-desc">
                CRUD Kriteria & bobot preferensi (0–100%), matriks normalisasi (R), skor preferensi (V), dan visualisasi ranking bar rekomendasi supplier.
            </div>
            <div style="margin-top: 15px;">
                <a href="/scm/saw-ranking" style="font-weight: 700; font-size: 13px;">Buka Perangkingan →</a>
            </div>
        </div>
    </div>
</div>

<!-- Panel Status Arsitektur Database & Relasi -->
<div class="card-panel">
    <div class="card-title">
        <span>🛡️</span> Status Arsitektur & Kepatuhan Keamanan
    </div>
    <div class="card-subtitle">
        Standar rekayasa perangkat lunak yang diterapkan pada backend dan basis data.
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 14px;">
            <div style="font-weight: 700; color: #0F2C59; margin-bottom: 4px;">Basis Data Aktif</div>
            <div style="font-size: 12.5px; color: #64748B;">MySQL / MariaDB: <code>db_scm_otomotif</code> (8 Tabel InnoDB siap relasi).</div>
        </div>

        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 14px;">
            <div style="font-weight: 700; color: #0F2C59; margin-bottom: 4px;">Keamanan Query</div>
            <div style="font-size: 12.5px; color: #64748B;">PDO Strict Mode dengan <code>EMULATE_PREPARES = false</code> untuk pencegahan SQL Injection mutlak.</div>
        </div>

        <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 14px;">
            <div style="font-weight: 700; color: #0F2C59; margin-bottom: 4px;">Integritas Relasi PO</div>
            <div style="font-size: 12.5px; color: #64748B;">Composite Foreign Key ganda mengunci material agar wajib disuplai oleh vendor penerima PO.</div>
        </div>
    </div>
</div>
