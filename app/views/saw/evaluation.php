<?php
/**
 * ==============================================================================
 * VIEW: INPUT MATRIKS EVALUASI SPK SAW - MODUL 3 (M. HADI PRATAMA)
 * ==============================================================================
 * Matriks penilaian alternatif vendor terverifikasi per kriteria keputusan.
 * Hanya vendor dengan status 'Terverifikasi' yang diizinkan dinilai.
 * ==============================================================================
 */

$userRole = $authUser['role'] ?? 'Guest';
$canEdit = in_array($userRole, ['Admin', 'QC_Auditor'], true);
?>

<!-- Flash Messages -->
<?php if (!empty($flashSuccess)): ?>
    <div class="alert-box alert-success" style="margin-bottom: 20px;">
        <span style="font-size: 18px;">✅</span>
        <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="alert-box" style="background-color: var(--danger-bg); border-left: 4px solid #DC2626; color: var(--danger-text); margin-bottom: 20px;">
        <span style="font-size: 18px;">⚠️</span>
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<!-- Header & Filter Periode -->
<div class="card-panel" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <div class="card-title" style="margin-bottom: 3px;">
                <span>📝</span> <?= htmlspecialchars($pageTitle ?? 'Input Evaluasi Alternatif Supplier', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Penanggung Jawab: <strong>M. Hadi Pratama</strong> | Matriks Penilaian Rekanan Terverifikasi per Periode
            </div>
        </div>

        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <!-- Form Pilih Periode -->
            <form method="GET" action="/scm/saw-evaluation" style="display: flex; align-items: center; gap: 8px; margin: 0;">
                <label style="font-size: 13px; font-weight: 600; color: #475569;">Periode:</label>
                <select name="periode" onchange="this.form.submit()" style="padding: 7px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; font-weight: 600; background: #FFFFFF; color: #0F172A;">
                    <?php if (empty($periods)): ?>
                        <option value="2026-Q4">2026-Q4</option>
                    <?php else: ?>
                        <?php foreach ($periods as $p): ?>
                            <option value="<?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>" <?= ($selectedPeriod === $p) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (!in_array($selectedPeriod, $periods, true)): ?>
                            <option value="<?= htmlspecialchars($selectedPeriod, ENT_QUOTES, 'UTF-8') ?>" selected>
                                <?= htmlspecialchars($selectedPeriod, ENT_QUOTES, 'UTF-8') ?> (Baru)
                            </option>
                        <?php endif; ?>
                    <?php endif; ?>
                </select>
            </form>

            <!-- Tombol Tambah Periode Baru -->
            <button type="button" onclick="promptNewPeriod()" style="background: #F1F5F9; color: #334155; border: 1px solid #CBD5E1; padding: 7px 12px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">
                📅 + Periode Baru
            </button>

            <!-- Tautan Cepat ke Perangkingan -->
            <a href="/scm/saw-ranking?periode=<?= urlencode($selectedPeriod) ?>" style="background: var(--primary); color: #FFFFFF; padding: 7px 14px; border-radius: 6px; font-weight: 700; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm);">
                <span>🏆</span> Lihat Ranking SAW
            </a>
        </div>
    </div>
</div>

<!-- Informasi Aturan Verifikasi Mutu & Kriteria Aktif -->
<div class="alert-box alert-info" style="margin-bottom: 20px;">
    <span style="font-size: 20px;">🛡️</span>
    <div style="font-size: 13px; line-height: 1.5;">
        <strong>Ketentuan Evaluasi Pemasok Industri Komponen Otomotif:</strong>
        <div style="margin-top: 4px; color: #1E3A8A;">
            Hanya rekanan dengan status <strong>"Terverifikasi"</strong> yang lolos audit mutu ISO yang dapat dinilai dan diproses dalam SPK SAW. 
            Rekanan dengan status <em>Audit Pending</em> secara otomatis diproteksi dan dikecualikan hingga sertifikasi dan kelayakan teknis disetujui auditor.
        </div>
    </div>
</div>

<?php if (empty($criteriaList)): ?>
    <div class="card-panel" style="text-align: center; padding: 50px 20px;">
        <div style="font-size: 40px; margin-bottom: 10px;">⚠️</div>
        <h3 style="margin: 0 0 8px 0; color: #0F172A;">Belum Ada Kriteria SAW yang Aktif</h3>
        <p style="color: #64748B; font-size: 14px; max-width: 500px; margin: 0 auto 20px auto;">
            Sebelum menginputkan nilai evaluasi alternatif rekanan, silakan aktifkan dan konfigurasi kriteria keputusan terlebih dahulu.
        </p>
        <a href="/scm/saw-criteria" style="background: var(--primary); color: #FFFFFF; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 13.5px; text-decoration: none; display: inline-block;">
            ⚙️ Kelola Kriteria SAW
        </a>
    </div>
<?php elseif (empty($verifiedVendors)): ?>
    <div class="card-panel" style="text-align: center; padding: 50px 20px;">
        <div style="font-size: 40px; margin-bottom: 10px;">🏢</div>
        <h3 style="margin: 0 0 8px 0; color: #0F172A;">Tidak Ada Vendor Terverifikasi</h3>
        <p style="color: #64748B; font-size: 14px; max-width: 500px; margin: 0 auto 20px auto;">
            Belum ada rekanan vendor yang berstatus 'Terverifikasi'. Pastikan vendor telah diaudit dan disetujui di Modul 1.
        </p>
        <a href="/scm/vendor" style="background: var(--primary); color: #FFFFFF; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 13.5px; text-decoration: none; display: inline-block;">
            🔍 Buka Master Rekanan
        </a>
    </div>
<?php else: ?>
    <!-- Form Matriks Penilaian -->
    <form method="POST" action="/scm/saw-evaluation/store">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="periode_evaluasi" value="<?= htmlspecialchars($selectedPeriod, ENT_QUOTES, 'UTF-8') ?>">

        <div class="card-panel">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div class="card-title" style="font-size: 16px; margin: 0;">
                        <span>📊</span> Matriks Keputusan Alternatif (\(X_{ij}\)) — Periode: <strong style="color: var(--primary);"><?= htmlspecialchars($selectedPeriod, ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <div style="font-size: 12.5px; color: #64748B; margin-top: 2px;">
                        Isikan skala nilai kinerja rekanan (1.00 – 100.00) untuk setiap kriteria evaluasi.
                    </div>
                </div>

                <div style="font-size: 12px; color: #64748B;">
                    <span>Alternatif: <strong><?= count($verifiedVendors) ?></strong> Rekanan</span> | 
                    <span>Kriteria: <strong><?= count($criteriaList) ?></strong> Atribut</span>
                </div>
            </div>

            <!-- Tabel Matriks Penilaian Interaktif -->
            <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;">
                    <thead>
                        <tr style="background-color: #0F2C59; color: #FFFFFF;">
                            <th style="padding: 12px 14px; width: 40px; text-align: center;">No</th>
                            <th style="padding: 12px 14px; min-width: 220px;">Alternatif Rekanan (Vendor)</th>
                            <?php foreach ($criteriaList as $c): ?>
                                <th style="padding: 12px 14px; text-align: center; min-width: 130px;">
                                    <div style="font-weight: 700; font-family: monospace; font-size: 13.5px;"><?= htmlspecialchars($c['kode_kriteria'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div style="font-size: 11.5px; font-weight: 500; opacity: 0.9; margin: 2px 0;"><?= htmlspecialchars($c['nama_kriteria'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div style="display: flex; justify-content: center; gap: 4px; align-items: center; margin-top: 4px;">
                                        <span style="font-size: 10.5px; padding: 1px 6px; border-radius: 3px; background: rgba(255,255,255,0.2);">
                                            <?= number_format((float)$c['bobot'], 1) ?>%
                                        </span>
                                        <span style="font-size: 10.5px; padding: 1px 6px; border-radius: 3px; background: <?= ($c['tipe'] === 'Benefit') ? '#059669' : '#D97706' ?>;">
                                            <?= htmlspecialchars($c['tipe'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $vNo = 1; foreach ($verifiedVendors as $v): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 12px 14px; text-align: center; font-weight: 600; color: #64748B;">
                                    <?= $vNo++ ?>
                                </td>
                                <td style="padding: 12px 14px;">
                                    <div style="font-weight: 700; color: #0F172A; font-size: 13.5px;">
                                        <?= htmlspecialchars($v['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div style="display: flex; gap: 6px; align-items: center; margin-top: 4px; flex-wrap: wrap;">
                                        <span style="font-family: monospace; font-size: 11px; background: #EEF2F6; color: #334155; padding: 2px 6px; border-radius: 4px;">
                                            <?= htmlspecialchars($v['kode_vendor'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span style="font-size: 11px; color: #64748B;">
                                            <?= htmlspecialchars($v['spesialisasi_logam'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <span style="font-size: 10.5px; font-weight: 700; color: #0284C7; background: #E0F2FE; padding: 1px 5px; border-radius: 3px;">
                                            <?= htmlspecialchars($v['jenis_akreditasi'] ?? 'ISO', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    </div>
                                </td>

                                <?php foreach ($criteriaList as $c): ?>
                                    <?php 
                                    $val = $currentMatrix[$v['id']]['scores'][$c['id']] ?? '';
                                    if ($val !== '') {
                                        $val = number_format((float)$val, 2, '.', '');
                                    }
                                    ?>
                                    <td style="padding: 8px 10px; text-align: center;">
                                        <input type="number" 
                                               name="scores[<?= (int)$v['id'] ?>][<?= (int)$c['id'] ?>]" 
                                               value="<?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?>"
                                               step="0.01" 
                                               min="0.01" 
                                               max="100" 
                                               placeholder="1.00-100" 
                                               required
                                               <?= !$canEdit ? 'disabled' : '' ?>
                                               style="width: 100px; padding: 7px 8px; text-align: center; border: 1px solid var(--border-color); border-radius: 6px; font-family: monospace; font-weight: 700; font-size: 13px; color: #0F172A; background: <?= ($val === '') ? '#FFFBEB' : '#FFFFFF' ?>;">
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Footer Action Toolbar -->
            <div style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                <div style="font-size: 12.5px; color: #64748B;">
                    💡 <em>Petunjuk: Nilai berupa desimal 1.00 s/d 100.00. Kolom berlatar kuning menandakan nilai belum disimpan.</em>
                </div>

                <div style="display: flex; gap: 10px; align-items: center;">
                    <?php if ($canEdit): ?>
                        <button type="submit" style="background: #059669; color: #FFFFFF; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: var(--shadow-sm);">
                            <span>💾</span> Simpan Seluruh Matriks Evaluasi
                        </button>
                    <?php else: ?>
                        <span style="font-size: 12.5px; color: #64748B; background: #F1F5F9; padding: 8px 14px; border-radius: 6px; font-weight: 600;">
                            🔒 Anda masuk sebagai role <strong><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></strong> (Hanya Admin & QC Auditor yang dapat mengubah nilai evaluasi).
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>

<!-- Panduan Skala Penilaian Standar Industri -->
<div class="card-panel" style="margin-top: 20px;">
    <div class="card-title" style="font-size: 14.5px; margin-bottom: 12px;">
        <span>📐</span> Standar Rentang Penilaian Vendor Industri Otomotif (Quality & Cost Index)
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px; font-size: 12.5px;">
        <div style="padding: 12px; border-radius: 6px; background: #DEF7EC; border: 1px solid #BCF0DA;">
            <div style="font-weight: 700; color: #03543F; font-size: 13px;">Skor 85.00 – 100.00</div>
            <div style="color: #046C4E; margin-top: 3px;">Kategori: <strong>Sangat Baik (Grade A)</strong></div>
            <div style="font-size: 11.5px; color: #4B5563; margin-top: 4px;">Kualitas presisi tinggi, defect rate &lt; 0.05%, pengiriman 100% on-time.</div>
        </div>
        <div style="padding: 12px; border-radius: 6px; background: #E1EFFE; border: 1px solid #B4C6FC;">
            <div style="font-weight: 700; color: #1E429F; font-size: 13px;">Skor 70.00 – 84.99</div>
            <div style="color: #233876; margin-top: 3px;">Kategori: <strong>Baik (Grade B)</strong></div>
            <div style="font-size: 11.5px; color: #4B5563; margin-top: 4px;">Memenuhi spesifikasi OEM standar, penyimpangan toleransi minor teratasi.</div>
        </div>
        <div style="padding: 12px; border-radius: 6px; background: #FEF3C7; border: 1px solid #FCD34D;">
            <div style="font-weight: 700; color: #92400E; font-size: 13px;">Skor 55.00 – 69.99</div>
            <div style="color: #B45309; margin-top: 3px;">Kategori: <strong>Cukup (Grade C)</strong></div>
            <div style="font-size: 11.5px; color: #4B5563; margin-top: 4px;">Perlu supervisi QC intensif, terdapat keterlambatan pengiriman sesekali.</div>
        </div>
        <div style="padding: 12px; border-radius: 6px; background: #FEE2E2; border: 1px solid #FCA5A5;">
            <div style="font-weight: 700; color: #991B1B; font-size: 13px;">Skor &lt; 55.00</div>
            <div style="color: #B91C1C; margin-top: 3px;">Kategori: <strong>Kurang (Grade D)</strong></div>
            <div style="font-size: 11.5px; color: #4B5563; margin-top: 4px;">Tidak direkomendasikan untuk kuota pengadaan utama (Hold Purchase).</div>
        </div>
    </div>
</div>

<script>
function promptNewPeriod() {
    var p = prompt("Masukkan kode periode evaluasi baru (contoh: 2027-Q1 atau 2026-Semester2):", "");
    if (p && p.trim() !== "") {
        window.location.href = "/scm/saw-evaluation?periode=" + encodeURIComponent(p.trim());
    }
}
</script>
