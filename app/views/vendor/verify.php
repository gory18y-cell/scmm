<?php
/**
 * ==============================================================================
 * VIEW: VERIFIKASI REKANAN VENDOR & AUDIT MUTU ISO - MODUL 1 (GORI)
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

<div class="card-panel" style="max-width: 850px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 22px;">
        <div>
            <div class="card-title" style="margin-bottom: 2px;">
                <span>🛡️</span> Formulir Audit & Verifikasi Kemitraan Rekanan
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Penetapan kelayakan vendor logam untuk pengadaan bahan baku dan penilaian SPK SAW.
            </div>
        </div>
        <a href="/scm/vendor" style="color: var(--text-muted); font-size: 13.5px; font-weight: 600; text-decoration: none;">
            ← Kembali ke Daftar Vendor
        </a>
    </div>

    <!-- Panel Profil Rekanan Yang Sedang Diaudit -->
    <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 16px;">
            <div>
                <span style="font-size: 12px; font-weight: 700; color: #1E56A0; font-family: monospace;">
                    <?= htmlspecialchars($vendor['kode_vendor'], ENT_QUOTES, 'UTF-8') ?>
                </span>
                <h2 style="font-size: 18px; color: #0F2C59; margin: 2px 0 6px 0;">
                    <?= htmlspecialchars($vendor['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
                </h2>
                <div style="font-size: 13px; color: #64748B;">
                    Spesialisasi: <strong><?= htmlspecialchars($vendor['spesialisasi_logam'], ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
            </div>

            <div style="text-align: right;">
                <div style="font-size: 11.5px; color: #64748B; margin-bottom: 4px;">Status Saat Ini:</div>
                <?php
                $statusColor = 'background: #FEF08A; color: #854D0E;';
                if ($vendor['status_verifikasi'] === 'Terverifikasi') {
                    $statusColor = 'background: #DEF7EC; color: #03543F;';
                } elseif ($vendor['status_verifikasi'] === 'Blacklist') {
                    $statusColor = 'background: #FEE2E2; color: #991B1B;';
                }
                ?>
                <span style="font-size: 13px; font-weight: 700; padding: 4px 12px; border-radius: 20px; <?= $statusColor ?>">
                    ● <?= htmlspecialchars($vendor['status_verifikasi'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>

        <!-- Detail Dokumen Legalitas & Mutu ISO -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; border-top: 1px solid #E2E8F0; padding-top: 15px; font-size: 13px;">
            <div>
                <span style="color: #64748B; display: block; font-size: 11.5px;">Akreditasi Mutu:</span>
                <strong style="color: #0F2C59;"><?= htmlspecialchars($vendor['jenis_akreditasi'], ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
            <div>
                <span style="color: #64748B; display: block; font-size: 11.5px;">Nomor Sertifikat:</span>
                <strong><?= !empty($vendor['no_sertifikat']) ? htmlspecialchars($vendor['no_sertifikat'], ENT_QUOTES, 'UTF-8') : '<em style="color:#94A3B8;">Tidak Ada</em>' ?></strong>
            </div>
            <div>
                <span style="color: #64748B; display: block; font-size: 11.5px;">Badan Audit Penerbit:</span>
                <strong><?= !empty($vendor['badan_sertifikasi']) ? htmlspecialchars($vendor['badan_sertifikasi'], ENT_QUOTES, 'UTF-8') : '<em style="color:#94A3B8;">Tidak Ada</em>' ?></strong>
            </div>
            <div>
                <span style="color: #64748B; display: block; font-size: 11.5px;">Masa Berlaku Sertifikat:</span>
                <strong>
                    <?php if (!empty($vendor['masa_berlaku_iso'])): ?>
                        <?= date('d M Y', strtotime($vendor['masa_berlaku_iso'])) ?>
                    <?php else: ?>
                        <em style="color:#94A3B8;">Belum Ditentukan</em>
                    <?php endif; ?>
                </strong>
            </div>
        </div>
    </div>

    <!-- Formulir Eksekusi Perubahan Status Verifikasi -->
    <form method="POST" action="/scm/vendor/process-verify/<?= (int)$vendor['id'] ?>">
        <!-- Token Proteksi CSRF -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <div style="background: #FFFFFF; border: 2px solid #1E56A0; border-radius: 8px; padding: 22px; margin-bottom: 25px;">
            <div style="font-weight: 700; color: #0F2C59; font-size: 14.5px; margin-bottom: 8px;">
                ⚖️ Keputusan Hasil Audit Kemitraan:
            </div>
            <p style="font-size: 13px; color: #64748B; margin-bottom: 16px;">
                Pilih keputusan status resmi berdasarkan kelengkapan berkas dan kepatuhan standar mutu otomotif:
            </p>

            <div style="margin-bottom: 15px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                    Ubah Status Verifikasi Menjadi: <span style="color: #DC2626;">*</span>
                </label>
                <select name="status_verifikasi" required
                        style="width: 100%; max-width: 480px; padding: 11px 14px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 14px; background: #FFFFFF; font-weight: 700;">
                    <option value="Terverifikasi" <?= ($vendor['status_verifikasi'] === 'Terverifikasi') ? 'selected' : '' ?>>
                        ✅ Terverifikasi — Lolos audit legalitas & mutu (Berhak menerima PO & ikut evaluasi SAW)
                    </option>
                    <option value="Audit Pending" <?= ($vendor['status_verifikasi'] === 'Audit Pending') ? 'selected' : '' ?>>
                        ⏳ Audit Pending — Berkas audit masih ditinjau / ditangguhkan sementara
                    </option>
                    <option value="Blacklist" <?= ($vendor['status_verifikasi'] === 'Blacklist') ? 'selected' : '' ?>>
                        ⛔ Blacklist — Rekanan bermasalah / dibekukan dari seluruh pengadaan pabrik
                    </option>
                </select>
            </div>

            <div class="alert-box" style="background: #FFFBEB; border-left: 4px solid #F59E0B; color: #92400E; margin-bottom: 0; font-size: 12.5px;">
                <span>📌</span>
                <div>
                    <strong>Konsekuensi Perubahan Status:</strong>
                    <ul style="margin: 4px 0 0 16px; line-height: 1.5;">
                        <li><strong>Terverifikasi:</strong> Rekanan akan muncul dalam dropdown penerbitan Purchase Order dan masuk ke dalam himpunan evaluasi matriks SPK SAW.</li>
                        <li><strong>Audit Pending / Blacklist:</strong> Rekanan diblokir dari transaksi PO dan dikecualikan dari perhitungan pemeringkatan supplier.</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Tombol Simpan Verifikasi -->
        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" 
                    style="background: #0F2C59; color: #FFFFFF; border: none; padding: 11px 24px; border-radius: 6px; font-weight: 700; font-size: 14px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <span>🛡️</span> Simpan Keputusan Verifikasi
            </button>
            <a href="/scm/vendor" 
               style="color: var(--text-muted); font-size: 13.5px; text-decoration: none; padding: 10px 18px; border: 1px solid var(--border-color); border-radius: 6px;">
                Batal
            </a>
        </div>
    </form>
</div>

