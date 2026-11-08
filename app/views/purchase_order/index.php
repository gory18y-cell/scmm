<?php
/**
 * ==============================================================================
 * VIEW: DAFTAR PURCHASE ORDER (PO) & JADWAL KIRIM - PENGADAAN BAHAN BAKU
 * ==============================================================================
 */
$userRole    = $_SESSION['user']['role'] ?? ($authUser['role'] ?? null);
$canCreatePo = in_array($userRole, ['Admin', 'Purchasing'], true);
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

<!-- Panel Utama Tabel Purchase Order -->
<div class="card-panel">
    <!-- Toolbar Atas: Tombol Tambah & Live Search Filter -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div class="card-title" style="margin-bottom: 2px;">
                <span>📑</span> Daftar Dokumen Purchase Order & Jadwal Kirim
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Manajemen Pengadaan Bahan Logam, Penerbitan PO & Estimasi Jadwal Kedatangan (ETA).
            </div>
        </div>

        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <!-- Live Search Input (Vanilla JS) -->
            <div style="position: relative;">
                <input type="text" id="poSearch" placeholder="🔍 Cari No PO, Vendor, Status..." 
                       style="padding: 9px 14px 9px 36px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; width: 260px; outline: none; background: #FFFFFF;"
                       autocomplete="off">
                <span style="position: absolute; left: 12px; top: 10px; color: #94A3B8; font-size: 14px;">🔍</span>
            </div>

            <?php if ($canCreatePo): ?>
                <a href="/scm/purchase-order/create" 
                   style="background: var(--primary); color: #FFFFFF; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm);">
                    <span>➕</span> Buat Purchase Order Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel Data Purchase Order -->
    <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
        <table id="poTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
            <thead>
                <tr style="background-color: #0F2C59; color: #FFFFFF; font-weight: 600;">
                    <th style="padding: 12px 14px; width: 45px;">No</th>
                    <th style="padding: 12px 14px;">Nomor PO</th>
                    <th style="padding: 12px 14px;">Tanggal PO</th>
                    <th style="padding: 12px 14px;">Rekanan Vendor</th>
                    <th style="padding: 12px 14px;">Petugas</th>
                    <th style="padding: 12px 14px; text-align: center;">Lead Time</th>
                    <th style="padding: 12px 14px;">Estimasi Tiba (ETA)</th>
                    <th style="padding: 12px 14px; text-align: right;">Total Nilai</th>
                    <th style="padding: 12px 14px; text-align: center;">Status PO</th>
                    <th style="padding: 12px 14px; text-align: center; width: 120px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="poTableBody">
                <?php if (empty($orders)): ?>
                    <tr id="emptyRow">
                        <td colspan="10" style="padding: 40px 20px; text-align: center; color: #64748B;">
                            <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                            <strong>Belum Ada Dokumen Purchase Order</strong>
                            <p style="margin: 4px 0 15px 0; font-size: 13px;">Silakan buat dokumen pengadaan bahan baku pertama Anda.</p>
                            <a href="/scm/purchase-order/create" style="color: var(--primary-light); font-weight: 600;">+ Terbitkan PO Baru Sekarang</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php 
                    $no = 1;
                    foreach ($orders as $row): 
                        // Penentuan Badge Status PO
                        switch ($row['status_po']) {
                            case 'Draft':
                                $badgeStyle = 'background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;';
                                $badgeIcon  = '📝';
                                break;
                            case 'Disetujui':
                                $badgeStyle = 'background: #DBEAFE; color: #1E40AF; border: 1px solid #BFDBFE;';
                                $badgeIcon  = '✓';
                                break;
                            case 'Dalam Pengiriman':
                                $badgeStyle = 'background: #FEF3C7; color: #92400E; border: 1px solid #FDE68A;';
                                $badgeIcon  = '🚚';
                                break;
                            case 'Sebagian Diterima':
                                $badgeStyle = 'background: #FFEDD5; color: #C2410C; border: 1px solid #FED7AA;';
                                $badgeIcon  = '⏳';
                                break;
                            case 'Selesai':
                                $badgeStyle = 'background: #DEF7EC; color: #03543F; border: 1px solid #BCF0DA;';
                                $badgeIcon  = '✅';
                                break;
                            case 'Dibatalkan':
                                $badgeStyle = 'background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA;';
                                $badgeIcon  = '⛔';
                                break;
                            default:
                                $badgeStyle = 'background: #F3F4F6; color: #374151;';
                                $badgeIcon  = '•';
                        }
                    ?>
                        <tr style="border-bottom: 1px solid var(--border-color); transition: background-color 0.15s;" onmouseover="this.style.backgroundColor='#F8FAFC'" onmouseout="this.style.backgroundColor=''">
                            <td style="padding: 12px 14px; text-align: center; color: #64748B;"><?= $no++ ?></td>
                            <td style="padding: 12px 14px;">
                                <a href="/scm/purchase-order/detail/<?= (int)$row['id'] ?>" style="font-weight: 700; color: #0F2C59; text-decoration: none;">
                                    <?= htmlspecialchars($row['no_po'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td style="padding: 12px 14px; color: #334155;">
                                <?= date('d/m/Y', strtotime($row['tanggal_po'])) ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <div style="font-weight: 600; color: #0F2C59;">
                                    <?= htmlspecialchars($row['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <small style="color: #64748B; font-size: 11.5px;">
                                    <?= htmlspecialchars($row['kode_vendor'], ENT_QUOTES, 'UTF-8') ?> • <?= htmlspecialchars($row['jenis_akreditasi'], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </td>
                            <td style="padding: 12px 14px; color: #475569;">
                                <div><?= htmlspecialchars($row['nama_user'], ENT_QUOTES, 'UTF-8') ?></div>
                                <small style="color: #94A3B8; font-size: 11px;"><?= htmlspecialchars($row['role_user'], ENT_QUOTES, 'UTF-8') ?></small>
                            </td>
                            <td style="padding: 12px 14px; text-align: center; font-weight: 600; color: #0F2C59;">
                                <?= (int)$row['lead_time_hari'] ?> hari
                            </td>
                            <td style="padding: 12px 14px; color: #334155;">
                                <div><?= date('d/m/Y', strtotime($row['estimasi_kedatangan'])) ?></div>
                                <small style="color: #64748B; font-size: 11.5px;">Docking Gudang</small>
                            </td>
                            <td style="padding: 12px 14px; text-align: right; font-weight: 700; color: #0F2C59; white-space: nowrap;">
                                Rp <?= number_format((float)$row['total_nilai'], 2, ',', '.') ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; <?= $badgeStyle ?>">
                                    <span><?= $badgeIcon ?></span> <?= htmlspecialchars($row['status_po'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <a href="/scm/purchase-order/detail/<?= (int)$row['id'] ?>" 
                                   style="background: #0F2C59; color: #FFFFFF; padding: 6px 12px; border-radius: 5px; font-size: 12.5px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                    <span>👁️</span> Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Script Live Search Filter Sisi Klien -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('poSearch');
    const tableBody   = document.getElementById('poTableBody');

    if (searchInput && tableBody) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const rows  = tableBody.querySelectorAll('tr');

            rows.forEach(function(row) {
                if (row.id === 'emptyRow') return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
});
</script>
