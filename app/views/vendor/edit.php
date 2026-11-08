<?php
/**
 * ==============================================================================
 * VIEW: UBAH DATA UMUM REKANAN VENDOR LOGAM - MASTER DATA
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
                <span>✏️</span> Perbarui Data Umum Rekanan: <?= htmlspecialchars($vendor['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Kode Rekanan: <strong><?= htmlspecialchars($vendor['kode_vendor'], ENT_QUOTES, 'UTF-8') ?></strong> (ID #<?= (int)$vendor['id'] ?>)
            </div>
        </div>
        <a href="/scm/vendor" style="color: var(--text-muted); font-size: 13.5px; font-weight: 600; text-decoration: none;">
            ← Kembali ke Daftar Vendor
        </a>
    </div>

    <!-- Status Verifikasi Kemitraan (Hanya Info & Tombol Menuju Jalur Verifikasi Khusus) -->
    <div style="background: #F1F5F9; border: 1px solid var(--border-color); border-radius: 8px; padding: 15px 18px; margin-bottom: 22px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Status Verifikasi Kemitraan Saat Ini:</div>
            <div style="margin-top: 4px;">
                <?php
                $statusColor = 'background: #FEF08A; color: #854D0E;';
                if ($vendor['status_verifikasi'] === 'Terverifikasi') {
                    $statusColor = 'background: #DEF7EC; color: #03543F;';
                } elseif ($vendor['status_verifikasi'] === 'Blacklist') {
                    $statusColor = 'background: #FEE2E2; color: #991B1B;';
                }
                ?>
                <span style="font-size: 13.5px; font-weight: 700; padding: 4px 12px; border-radius: 20px; <?= $statusColor ?>">
                    ● <?= htmlspecialchars($vendor['status_verifikasi'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <span style="color: #64748B; font-size: 12px; margin-left: 8px;">
                    (Form edit umum ini tidak dapat mengubah status verifikasi).
                </span>
            </div>
        </div>
        <div>
            <a href="/scm/vendor/verify/<?= (int)$vendor['id'] ?>" 
               style="background: #0F2C59; color: #FFFFFF; padding: 8px 16px; border-radius: 6px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span>🛡️</span> Buka Form Verifikasi Khusus →
            </a>
        </div>
    </div>

    <!-- Formulir Edit Data Umum -->
    <form method="POST" action="/scm/vendor/update/<?= (int)$vendor['id'] ?>" id="formVendorEdit">
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
                    <input type="text" name="kode_vendor" value="<?= htmlspecialchars($vendor['kode_vendor'], ENT_QUOTES, 'UTF-8') ?>" 
                           required maxlength="20"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; text-transform: uppercase;">
                </div>

                <!-- Nama Vendor -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Nama Perusahaan / PT <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="nama_vendor" value="<?= htmlspecialchars($vendor['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>" 
                           required maxlength="120"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Spesialisasi Logam -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Spesialisasi Bahan Logam <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="spesialisasi_logam" value="<?= htmlspecialchars($vendor['spesialisasi_logam'], ENT_QUOTES, 'UTF-8') ?>" 
                           required maxlength="100"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Nama Kontak Person (PIC) -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Kontak Person (PIC) <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="kontak_person" value="<?= htmlspecialchars($vendor['kontak_person'], ENT_QUOTES, 'UTF-8') ?>" 
                           required maxlength="80"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Email Resmi -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Email Perusahaan
                    </label>
                    <input type="email" name="email" value="<?= htmlspecialchars($vendor['email'], ENT_QUOTES, 'UTF-8') ?>" 
                           maxlength="100"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Nomor Telepon -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Telepon / WhatsApp <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="telepon" value="<?= htmlspecialchars($vendor['telepon'], ENT_QUOTES, 'UTF-8') ?>" 
                           required maxlength="25"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>
            </div>

            <!-- Alamat Pabrik / Gudang -->
            <div style="margin-top: 16px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                    Alamat Fasilitas / Pabrik <span style="color: #DC2626;">*</span>
                </label>
                <textarea name="alamat" rows="2" required
                          style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;"><?= htmlspecialchars($vendor['alamat'], ENT_QUOTES, 'UTF-8') ?></textarea>
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
                    <select name="jenis_akreditasi" id="jenisAkreditasiSelect" required
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; background: #FFFFFF; font-weight: 600;">
                        <option value="IATF 16949" <?= ($vendor['jenis_akreditasi'] === 'IATF 16949') ? 'selected' : '' ?>>IATF 16949 (Standar Mutu Otomotif Global)</option>
                        <option value="ISO 9001" <?= ($vendor['jenis_akreditasi'] === 'ISO 9001') ? 'selected' : '' ?>>ISO 9001 (Sistem Manajemen Mutu)</option>
                        <option value="ISO 14001" <?= ($vendor['jenis_akreditasi'] === 'ISO 14001') ? 'selected' : '' ?>>ISO 14001 (Manajemen Lingkungan Hidup)</option>
                        <option value="Non-ISO" <?= ($vendor['jenis_akreditasi'] === 'Non-ISO') ? 'selected' : '' ?>>Non-ISO (Belum Memiliki Sertifikasi Resmi)</option>
                    </select>
                </div>

                <!-- Nomor Sertifikat -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Nomor Registrasi Sertifikat <span id="noSertifikatReq" style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="no_sertifikat" id="inputNoSertifikat" value="<?= htmlspecialchars($vendor['no_sertifikat'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           maxlength="80"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Badan Sertifikasi Penerbit -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Badan Audit Sertifikasi Penerbit
                    </label>
                    <input type="text" name="badan_sertifikasi" id="inputBadanSertifikasi" value="<?= htmlspecialchars($vendor['badan_sertifikasi'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           maxlength="100"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                </div>

                <!-- Masa Berlaku Sertifikat ISO -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Masa Berlaku Sertifikat ISO <span id="masaBerlakuReq" style="color: #DC2626;">*</span>
                    </label>
                    <input type="date" name="masa_berlaku_iso" id="inputMasaBerlaku" value="<?= htmlspecialchars($vendor['masa_berlaku_iso'] ?? '', ENT_QUOTES, 'UTF-8') ?>" 
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; background: #FFFFFF;">
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Form -->
        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" 
                    style="background: #1E56A0; color: #FFFFFF; border: none; padding: 11px 24px; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <span>💾</span> Simpan Perubahan Data Umum
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
