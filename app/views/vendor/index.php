<?php
/**
 * ==============================================================================
 * VIEW: DAFTAR MASTER REKANAN VENDOR LOGAM - MODUL 1 (GORI)
 * ==============================================================================
 */
$userRole  = $_SESSION['user']['role'] ?? ($authUser['role'] ?? null);
$isAdmin   = ($userRole === 'Admin');
$canVerify = in_array($userRole, ['Admin', 'QC_Auditor'], true);
?>

<!-- Notifikasi Flash Message -->
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

<!-- Panel Utama Tabel Vendor -->
<div class="card-panel">
    <!-- Toolbar Atas: Tombol Tambah & Live Search Filter -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div class="card-title" style="margin-bottom: 2px;">
                <span>🏢</span> Rekanan Vendor Logam & Akreditasi Mutu ISO
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Kelola rekanan pabrik pelat baja, pipa, dan aluminium komponen otomotif.
            </div>
        </div>

        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <!-- Live Search Input (Vanilla JS) -->
            <div style="position: relative;">
                <input type="text" id="vendorSearch" placeholder="🔍 Cari vendor, kode, logam..." 
                       style="padding: 9px 14px 9px 36px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; width: 250px; outline: none; background: #FFFFFF;"
                       autocomplete="off">
                <span style="position: absolute; left: 12px; top: 10px; color: #94A3B8; font-size: 14px;">🔍</span>
            </div>

            <?php if ($isAdmin): ?>
                <a href="/scm/vendor/create" 
                   style="background: var(--primary); color: #FFFFFF; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm);">
                    <span>➕</span> Tambah Rekanan Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel Data Rekanan Vendor -->
    <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
        <table id="vendorTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
            <thead>
                <tr style="background-color: #0F2C59; color: #FFFFFF; font-weight: 600;">
                    <th style="padding: 12px 14px; width: 45px;">No</th>
                    <th style="padding: 12px 14px;">Kode & Nama Rekanan</th>
                    <th style="padding: 12px 14px;">Spesialisasi Logam</th>
                    <th style="padding: 12px 14px;">Kontak & Telepon</th>
                    <th style="padding: 12px 14px;">Akreditasi Mutu</th>
                    <th style="padding: 12px 14px;">Masa Berlaku ISO</th>
                    <th style="padding: 12px 14px;">Status Verifikasi</th>
                    <th style="padding: 12px 14px; text-align: center; width: 200px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="vendorTableBody">
                <?php if (empty($vendors)): ?>
                    <tr id="emptyRow">
                        <td colspan="8" style="padding: 40px 20px; text-align: center; color: #64748B;">
                            <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                            <strong>Belum Ada Data Rekanan Vendor</strong>
                            <p style="margin: 4px 0 15px 0; font-size: 13px;">Silakan daftarkan rekanan pemasok logam pertama melalui tombol di atas.</p>
                            <a href="/scm/vendor/create" style="color: var(--primary-light); font-weight: 600;">+ Daftarkan Vendor Sekarang</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    $today = date('Y-m-d');
                    foreach ($vendors as $v): 
                        // ==========================================================
                        // LOGIKA PERBAIKAN BADGE MASA BERLAKU SERTIFIKAT ISO
                        // ==========================================================
                        if ($v['jenis_akreditasi'] === 'Non-ISO') {
                            $isoBadgeClass = 'background: #F1F5F9; color: #475569;';
                            $isoStatusText = 'Non-ISO';
                            $isoDateText = '-';
                        } else {
                            if (empty($v['masa_berlaku_iso'])) {
                                $isoBadgeClass = 'background: #FEF3C7; color: #92400E;';
                                $isoStatusText = 'Belum Ada Tanggal';
                                $isoDateText = 'Tanggal belum diisi';
                            } else {
                                $diffDays = (strtotime($v['masa_berlaku_iso']) - strtotime($today)) / 86400;
                                $isoDateText = 's/d ' . date('d/m/Y', strtotime($v['masa_berlaku_iso']));
                                if ($diffDays < 0) {
                                    $isoBadgeClass = 'background: #FEE2E2; color: #991B1B;';
                                    $isoStatusText = 'Kedaluwarsa';
                                } elseif ($diffDays <= 30) {
                                    $isoBadgeClass = 'background: #FFEDD5; color: #9A3412;';
                                    $isoStatusText = 'Segera Berakhir';
                                } else {
                                    $isoBadgeClass = 'background: #DEF7EC; color: #03543F;';
                                    $isoStatusText = 'Berlaku Aktif';
                                }
                            }
                        }

                        // Badge Status Verifikasi Vendor
                        $statusBadge = 'background: #FEF08A; color: #854D0E;'; // Audit Pending
                        if ($v['status_verifikasi'] === 'Terverifikasi') {
                            $statusBadge = 'background: #DEF7EC; color: #03543F;';
                        } elseif ($v['status_verifikasi'] === 'Blacklist') {
                            $statusBadge = 'background: #FEE2E2; color: #991B1B;';
                        }
                    ?>
                    <tr class="vendor-row" style="border-bottom: 1px solid var(--border-color); transition: background-color 0.15s;">
                        <td style="padding: 12px 14px; color: #64748B; font-weight: 600;"><?= $no++ ?></td>
                        
                        <!-- Kode & Nama Vendor -->
                        <td style="padding: 12px 14px;">
                            <span style="display: inline-block; font-weight: 700; color: #0F2C59; font-size: 13.5px;">
                                <?= htmlspecialchars($v['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <div style="font-size: 12px; color: #1E56A0; font-weight: 600; font-family: monospace;">
                                <?= htmlspecialchars($v['kode_vendor'], ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </td>

                        <!-- Spesialisasi Logam -->
                        <td style="padding: 12px 14px;">
                            <span style="display: inline-block; background: #F1F5F9; color: #1E293B; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">
                                <?= htmlspecialchars($v['spesialisasi_logam'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>

                        <!-- Kontak & Telepon -->
                        <td style="padding: 12px 14px;">
                            <div style="font-weight: 600; color: #334155;"><?= htmlspecialchars($v['kontak_person'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div style="font-size: 12px; color: #64748B;">📞 <?= htmlspecialchars($v['telepon'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php if (!empty($v['email'])): ?>
                                <div style="font-size: 11.5px; color: #64748B;">✉️ <?= htmlspecialchars($v['email'], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </td>

                        <!-- Akreditasi Mutu ISO -->
                        <td style="padding: 12px 14px;">
                            <span style="display: inline-block; font-size: 11.5px; font-weight: 700; padding: 4px 8px; border-radius: 4px; <?= ($v['jenis_akreditasi'] === 'IATF 16949') ? 'background: #E0E7FF; color: #3730A3;' : (($v['jenis_akreditasi'] === 'Non-ISO') ? 'background: #F1F5F9; color: #64748B;' : 'background: #E0F2FE; color: #0369A1;') ?>">
                                <?= htmlspecialchars($v['jenis_akreditasi'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if (!empty($v['no_sertifikat'])): ?>
                                <div style="font-size: 11px; color: #64748B; margin-top: 3px;">
                                    No: <?= htmlspecialchars($v['no_sertifikat'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Status Masa Berlaku ISO Terpisah -->
                        <td style="padding: 12px 14px;">
                            <span style="display: inline-block; font-size: 11.5px; font-weight: 700; padding: 3px 8px; border-radius: 4px; <?= $isoBadgeClass ?>">
                                <?= $isoStatusText ?>
                            </span>
                            <div style="font-size: 11px; color: #64748B; margin-top: 3px;">
                                <?= $isoDateText ?>
                            </div>
                        </td>

                        <!-- Status Verifikasi Kemitraan Terpisah -->
                        <td style="padding: 12px 14px;">
                            <span style="display: inline-block; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 20px; <?= $statusBadge ?>">
                                <?= htmlspecialchars($v['status_verifikasi'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>

                        <!-- Tombol Aksi: Verifikasi Khusus, Edit, dan Hapus Berdasarkan RBAC -->
                        <td style="padding: 12px 14px; text-align: center;">
                            <div style="display: flex; gap: 5px; justify-content: center; flex-wrap: wrap;">
                                <?php if ($canVerify): ?>
                                    <!-- Tombol Verifikasi Khusus (Admin & QC Auditor) -->
                                    <a href="/scm/vendor/verify/<?= (int)$v['id'] ?>" 
                                       style="background: #0F2C59; color: #FFFFFF; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 700; text-decoration: none;"
                                       title="Audit & Verifikasi Mutu">
                                        🛡️ Verifikasi
                                    </a>
                                <?php endif; ?>

                                <?php if ($isAdmin): ?>
                                    <!-- Tombol Edit Umum (Admin Saja) -->
                                    <a href="/scm/vendor/edit/<?= (int)$v['id'] ?>" 
                                       style="background: #1E56A0; color: #FFFFFF; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 600; text-decoration: none;"
                                       title="Ubah Data Umum Vendor">
                                        ✏️ Edit
                                    </a>

                                    <!-- Tombol Hapus (Admin Saja) -->
                                    <form method="POST" action="/scm/vendor/delete/<?= (int)$v['id'] ?>" style="display: inline;" 
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus data rekanan vendor <?= htmlspecialchars($v['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>? Tindakan ini tidak dapat dibatalkan.');">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <button type="submit" 
                                                style="background: #DC2626; color: #FFFFFF; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11.5px; font-weight: 600; cursor: pointer;"
                                                title="Hapus Vendor">
                                            🗑️
                                        </button>
                                    </form>
                                <?php endif; ?>

                                <?php if (!$canVerify && !$isAdmin): ?>
                                    <span style="font-size: 11.5px; color: #94A3B8; font-style: italic;">Mode Lihat</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Feedback Pencarian Kosong -->
    <div id="noMatchMessage" style="display: none; padding: 25px; text-align: center; color: #64748B; background: #F8FAFC; border: 1px dashed var(--border-color); border-radius: 6px; margin-top: 15px;">
        🔍 Tidak ditemukan data rekanan vendor yang sesuai dengan kata kunci pencarian.
    </div>
</div>

<!-- ==============================================================================
     INTERAKTIVITAS VANILLA JAVASCRIPT: LIVE FILTER PENCARIAN REKANAN
     ============================================================================== -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('vendorSearch');
    const tableRows = document.querySelectorAll('.vendor-row');
    const noMatchMessage = document.getElementById('noMatchMessage');

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            let visibleCount = 0;

            tableRows.forEach(function (row) {
                const text = row.textContent.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noMatchMessage) {
                if (visibleCount === 0 && tableRows.length > 0) {
                    noMatchMessage.style.display = 'block';
                } else {
                    noMatchMessage.style.display = 'none';
                }
            }
        });
    }
});
</script>
