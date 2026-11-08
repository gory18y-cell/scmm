<?php
/**
 * ==============================================================================
 * VIEW: PENDAFTARAN REKANAN VENDOR BARU - MASTER DATA
 * ==============================================================================
 */
?>

<!-- Notifikasi Galat Validasi -->
<?php if (!empty($flashError)): ?>
    <div class="alert-box" style="background-color: var(--danger-bg); border-left: 4px solid #DC2626; color: var(--danger-text); margin-bottom: 20px;">
        <span style="font-size: 18px;">⚠️</span>
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="card-panel" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 22px;">
        <div>
            <div class="card-title" style="margin-bottom: 2px;">
                <span>➕</span> Formulir Pendaftaran Rekanan Vendor Logam
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Lengkapi identitas badan usaha, spesialisasi material, dan verifikasi sertifikasi mutu ISO.
            </div>
        </div>
        <a href="/scm/vendor" style="color: var(--text-muted); font-size: 13.5px; font-weight: 600; text-decoration: none;">
            ← Kembali ke Daftar Vendor
        </a>
    </div>

    <!-- Banner Ketetapan Status Registrasi -->
    <div class="alert-box alert-info" style="margin-bottom: 22px;">
        <span style="font-size: 20px;">ℹ️</span>
        <div>
            <strong>Status Awal Registrasi: Terkunci pada <span style="background: #FEF08A; color: #854D0E; padding: 2px 8px; border-radius: 4px; font-weight: 700;">⏳ Audit Pending</span></strong>
            <div style="margin-top: 4px; font-size: 12.5px; color: #0C4A6E;">
                Sesuai aturan bisnis, seluruh rekanan baru yang didaftarkan wajib berstatus <em>Audit Pending</em>. Penetapan status <em>Terverifikasi</em> hanya dapat dilakukan melalui menu <strong>Verifikasi Khusus</strong> setelah berkas audit diperiksa.
            </div>
        </div>
    </div>

    <!-- Formulir Pendaftaran -->
    <form method="POST" action="/scm/vendor/store" id="formVendorCreate">
        <!-- Token Proteksi CSRF -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <!-- Bagian 1: Identitas Rekanan & Kontak Perusahaan -->
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin-bottom: 22px;">
            <div style="font-weight: 700; color: #0F2C59; margin-bottom: 15px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span>🏭</span> Bagian 1: Identitas & Kontak Perusahaan
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                <!-- Kode Vendor -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Kode Vendor <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="kode_vendor" value="<?= htmlspecialchars($old['kode_vendor'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: VND-001" required maxlength="20"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; text-transform: uppercase;">
                    <small style="color: #64748B; font-size: 11.5px;">Kode unik identifikasi rekanan (maks. 20 karakter).</small>
                </div>

                <!-- Nama Vendor -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Nama Perusahaan / PT <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="nama_vendor" value="<?= htmlspecialchars($old['nama_vendor'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: PT Krakatau Steel Perkasa" required maxlength="120"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Spesialisasi Logam -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Spesialisasi Bahan Logam <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="spesialisasi_logam" value="<?= htmlspecialchars($old['spesialisasi_logam'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: Plat Baja SPCC & Pipa Seamless" required maxlength="100"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Nama Kontak Person (PIC) -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Kontak Person (PIC) <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="kontak_person" value="<?= htmlspecialchars($old['kontak_person'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: Bambang Sutedjo (Sales Manager)" required maxlength="80"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Email Resmi -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Email Perusahaan
                    </label>
                    <input type="email" name="email" value="<?= htmlspecialchars($old['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: sales@krakatausteel.co.id" maxlength="100"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Nomor Telepon -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Telepon / WhatsApp <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="telepon" value="<?= htmlspecialchars($old['telepon'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: 021-8976543 / 08123456789" required maxlength="25"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>
            </div>

            <!-- Alamat Pabrik / Gudang -->
            <div style="margin-top: 16px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                    Alamat Fasilitas / Pabrik <span style="color: #DC2626;">*</span>
                </label>
                <textarea name="alamat" rows="2" placeholder="Alamat lengkap fasilitas manufaktur atau kantor operasional rekanan..." required
                          style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;"><?= htmlspecialchars($old['alamat'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>

        <!-- Bagian 2: Akreditasi Mutu ISO -->
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin-bottom: 25px;">
            <div style="font-weight: 700; color: #0F2C59; margin-bottom: 15px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span>🛡️</span> Bagian 2: Standarisasi Mutu ISO
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
                <!-- Jenis Akreditasi -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Jenis Akreditasi Mutu <span style="color: #DC2626;">*</span>
                    </label>
                    <?php $selectedAkred = $old['jenis_akreditasi'] ?? 'IATF 16949'; ?>
                    <select name="jenis_akreditasi" id="jenisAkreditasiSelect" required
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; background: #FFFFFF; font-weight: 600;">
                        <option value="IATF 16949" <?= ($selectedAkred === 'IATF 16949') ? 'selected' : '' ?>>IATF 16949 (Standar Mutu Otomotif Global)</option>
                        <option value="ISO 9001" <?= ($selectedAkred === 'ISO 9001') ? 'selected' : '' ?>>ISO 9001 (Sistem Manajemen Mutu)</option>
                        <option value="ISO 14001" <?= ($selectedAkred === 'ISO 14001') ? 'selected' : '' ?>>ISO 14001 (Manajemen Lingkungan Hidup)</option>
                        <option value="Non-ISO" <?= ($selectedAkred === 'Non-ISO') ? 'selected' : '' ?>>Non-ISO (Belum Memiliki Sertifikasi Resmi)</option>
                    </select>
                </div>

                <!-- Nomor Sertifikat -->
                <div id="noSertifikatWrapper">
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Nomor Registrasi Sertifikat <span id="noSertifikatReq" style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="no_sertifikat" id="inputNoSertifikat" value="<?= htmlspecialchars($old['no_sertifikat'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: 01 111 2026/IATF-0482" maxlength="80"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                    <small id="noSertifikatHint" style="color: #64748B; font-size: 11.5px;">Wajib diisi untuk vendor berakreditasi ISO/IATF.</small>
                </div>

                <!-- Badan Sertifikasi Penerbit -->
                <div id="badanSertifikasiWrapper">
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Badan Audit Sertifikasi Penerbit
                    </label>
                    <input type="text" name="badan_sertifikasi" id="inputBadanSertifikasi" value="<?= htmlspecialchars($old['badan_sertifikasi'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: TÜV Rheinland / Sucofindo / BSI" maxlength="100"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Masa Berlaku Sertifikat ISO -->
                <div id="masaBerlakuWrapper">
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Masa Berlaku Sertifikat ISO <span id="masaBerlakuReq" style="color: #DC2626;">*</span>
                    </label>
                    <input type="date" name="masa_berlaku_iso" id="inputMasaBerlaku" value="<?= htmlspecialchars($old['masa_berlaku_iso'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; background: #FFFFFF;">
                    <small id="masaBerlakuHint" style="color: #64748B; font-size: 11.5px;">Wajib diisi untuk vendor berakreditasi ISO/IATF.</small>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Form -->
        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" 
                    style="background: var(--primary); color: #FFFFFF; border: none; padding: 11px 24px; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <span>💾</span> Simpan Pendaftaran Vendor
            </button>
            <a href="/scm/vendor" 
               style="color: var(--text-muted); font-size: 13.5px; text-decoration: none; padding: 10px 18px; border: 1px solid var(--border-color); border-radius: 6px;">
                Batal
            </a>
        </div>
    </form>
</div>

<!-- Interaktivitas Input Form ISO vs Non-ISO -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const akredSelect = document.getElementById('jenisAkreditasiSelect');
    const inputNoSertifikat = document.getElementById('inputNoSertifikat');
    const inputBadanSertifikasi = document.getElementById('inputBadanSertifikasi');
    const inputMasaBerlaku = document.getElementById('inputMasaBerlaku');
    const noSertifikatReq = document.getElementById('noSertifikatReq');
    const masaBerlakuReq = document.getElementById('masaBerlakuReq');

    function toggleIsoFields() {
        if (akredSelect.value === 'Non-ISO') {
            inputNoSertifikat.value = '';
            inputBadanSertifikasi.value = '';
            inputMasaBerlaku.value = '';
            inputNoSertifikat.disabled = true;
            inputBadanSertifikasi.disabled = true;
            inputMasaBerlaku.disabled = true;
            noSertifikatReq.style.display = 'none';
            masaBerlakuReq.style.display = 'none';
        } else {
            inputNoSertifikat.disabled = false;
            inputBadanSertifikasi.disabled = false;
            inputMasaBerlaku.disabled = false;
            noSertifikatReq.style.display = 'inline';
            masaBerlakuReq.style.display = 'inline';
        }
    }

    akredSelect.addEventListener('change', toggleIsoFields);
    toggleIsoFields();
});
</script>
