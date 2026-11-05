<?php
/**
 * ==============================================================================
 * VIEW: MASTER KATALOG BAHAN BAKU LOGAM OTOMOTIF - CRUD MATERIAL
 * ==============================================================================
 */
$userRole = $_SESSION['user']['role'] ?? ($authUser['role'] ?? null);
$canManage = in_array($userRole, ['Admin', 'Purchasing'], true);
$isAdmin = ($userRole === 'Admin');

// Hitung rekap statistik kategori
$kategoriCounts = [
    'Plat Baja' => 0,
    'Pipa Baja' => 0,
    'Aluminium Alloy' => 0,
    'Besi Tempa' => 0
];
foreach ($materials as $m) {
    if (isset($kategoriCounts[$m['kategori']])) {
        $kategoriCounts[$m['kategori']]++;
    }
}
?>

<!-- Notifikasi Flash Message -->
<?php if (!empty($flashSuccess)): ?>
    <div class="alert-box alert-success" style="margin-bottom: 20px;">
        <span style="font-size: 18px;">✅</span>
        <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="alert-box"
        style="background-color: var(--danger-bg); border-left: 4px solid #DC2626; color: var(--danger-text); margin-bottom: 20px;">
        <span style="font-size: 18px;">⚠️</span>
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<!-- Ringkasan Statistik Logam Gudang -->
<div
    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 22px;">
    <div class="card-panel"
        style="padding: 16px 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 0;">
        <div
            style="width: 48px; height: 48px; border-radius: 10px; background: #E0F2FE; display: flex; align-items: center; justify-content: center; font-size: 24px;">
            🔩
        </div>
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Jenis
                Material</div>
            <div style="font-size: 22px; font-weight: 800; color: #0F2C59; line-height: 1.2;"><?= count($materials) ?>
                <span style="font-size: 13px; font-weight: 600; color: #64748B;">Item</span></div>
        </div>
    </div>

    <div class="card-panel"
        style="padding: 16px 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 0;">
        <div
            style="width: 48px; height: 48px; border-radius: 10px; background: #DEF7EC; display: flex; align-items: center; justify-content: center; font-size: 24px;">
            ⚖️
        </div>
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Fisik
                Gudang</div>
            <div style="font-size: 22px; font-weight: 800; color: #03543F; line-height: 1.2;">
                <?= number_format($totalStok ?? 0, 2) ?> <span
                    style="font-size: 13px; font-weight: 600; color: #64748B;">Unit/Kg</span></div>
        </div>
    </div>

    <div class="card-panel"
        style="padding: 16px 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 0;">
        <div
            style="width: 48px; height: 48px; border-radius: 10px; background: #FEF3C7; display: flex; align-items: center; justify-content: center; font-size: 24px;">
            🏭
        </div>
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Kategori Baja &
                Plat</div>
            <div style="font-size: 22px; font-weight: 800; color: #92400E; line-height: 1.2;">
                <?= ($kategoriCounts['Plat Baja'] + $kategoriCounts['Pipa Baja']) ?> <span
                    style="font-size: 13px; font-weight: 600; color: #64748B;">Varian</span></div>
        </div>
    </div>

    <div class="card-panel"
        style="padding: 16px 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 0;">
        <div
            style="width: 48px; height: 48px; border-radius: 10px; background: #F3E8FF; display: flex; align-items: center; justify-content: center; font-size: 24px;">
            ✨
        </div>
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #64748B; text-transform: uppercase;">Aluminium & Tempa
            </div>
            <div style="font-size: 22px; font-weight: 800; color: #6B21A8; line-height: 1.2;">
                <?= ($kategoriCounts['Aluminium Alloy'] + $kategoriCounts['Besi Tempa']) ?> <span
                    style="font-size: 13px; font-weight: 600; color: #64748B;">Varian</span></div>
        </div>
    </div>
</div>

<!-- Panel Utama Tabel Material -->
<div class="card-panel">
    <!-- Toolbar Atas: Tombol Tambah & Live Search Filter -->
    <div
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; flex-wrap: wrap; gap: 15px;">
        <div>
            <div class="card-title" style="margin-bottom: 2px;">
                <span>🔩</span> Master Bahan Baku Logam Otomotif
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Katalog spesifikasi JIS/ASTM, kategori pelat/pipa, volume stok fisik gudang, dan pemetaan ke rekanan
                vendor.
            </div>
        </div>

        <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <!-- Live Search Input (Vanilla JS) -->
            <div style="position: relative;">
                <input type="text" id="materialSearch" placeholder="🔍 Cari material, kode, kategori..."
                    style="padding: 9px 14px 9px 36px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; width: 260px; outline: none; background: #FFFFFF;"
                    autocomplete="off">
                <span style="position: absolute; left: 12px; top: 10px; color: #94A3B8; font-size: 14px;">🔍</span>
            </div>

            <?php if ($canManage): ?>
                <a href="/scm/material/create"
                    style="background: var(--primary); color: #FFFFFF; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm);">
                    <span>➕</span> Tambah Material Baru
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel Data Material -->
    <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
        <table id="materialTable" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
            <thead>
                <tr style="background-color: #0F2C59; color: #FFFFFF; font-weight: 600;">
                    <th style="padding: 12px 14px; width: 45px;">No</th>
                    <th style="padding: 12px 14px; width: 140px;">Kode Material</th>
                    <th style="padding: 12px 14px;">Nama Bahan Baku & Kategori</th>
                    <th style="padding: 12px 14px;">Spesifikasi Teknis</th>
                    <th style="padding: 12px 14px; text-align: right; width: 150px;">Stok Gudang</th>
                    <th style="padding: 12px 14px; text-align: center; width: 130px;">Mitra Vendor</th>
                    <th style="padding: 12px 14px; text-align: center; width: 150px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="materialTableBody">
                <?php if (empty($materials)): ?>
                    <tr id="emptyRow">
                        <td colspan="7" style="padding: 40px 20px; text-align: center; color: #64748B;">
                            <div style="font-size: 32px; margin-bottom: 8px;">📦</div>
                            <strong>Belum Ada Data Bahan Baku Material</strong>
                            <p style="margin: 4px 0 15px 0; font-size: 13px;">Silakan daftarkan bahan baku logam pertama
                                melalui tombol di atas.</p>
                            <?php if ($canManage): ?>
                                <a href="/scm/material/create" style="color: var(--primary-light); font-weight: 600;">+ Tambah
                                    Material Sekarang</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php
                    $no = 1;
                    foreach ($materials as $m):
                        $kategoriDisplay = !empty($m['kategori']) ? $m['kategori'] : 'Plat Baja';

                        // Style badge kategori
                        switch ($kategoriDisplay) {
                            case 'Plat Baja':
                                $katBadgeStyle = 'background: #E0F2FE; color: #0369A1;';
                                break;
                            case 'Pipa Baja':
                                $katBadgeStyle = 'background: #FEF3C7; color: #92400E;';
                                break;
                            case 'Aluminium Alloy':
                                $katBadgeStyle = 'background: #F3E8FF; color: #6B21A8;';
                                break;
                            case 'Besi Tempa':
                                $katBadgeStyle = 'background: #DEF7EC; color: #03543F;';
                                break;
                            default:
                                $katBadgeStyle = 'background: #F1F5F9; color: #475569;';
                                break;
                        }

                        $vendorCount = (int) ($m['vendor_count'] ?? 0);
                        ?>
                        <tr class="material-row"
                            style="border-bottom: 1px solid var(--border-color); transition: background-color 0.15s;">
                            <td style="padding: 12px 14px; color: #64748B; font-weight: 600;"><?= $no++ ?></td>

                            <!-- Kode Material -->
                            <td style="padding: 12px 14px;">
                                <span
                                    style="display: inline-block; font-weight: 700; color: #0F2C59; font-family: monospace; font-size: 13px; background: #F1F5F9; padding: 3px 8px; border-radius: 4px; border: 1px solid #E2E8F0;">
                                    <?= htmlspecialchars($m['kode_material'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <!-- Nama Material & Kategori -->
                            <td style="padding: 12px 14px;">
                                <div style="font-weight: 700; color: #1E293B; font-size: 14px; margin-bottom: 4px;">
                                    <?= htmlspecialchars($m['nama_material'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <span
                                    style="display: inline-block; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; <?= $katBadgeStyle ?>">
                                    <?= htmlspecialchars($kategoriDisplay, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <!-- Spesifikasi Teknis -->
                            <td style="padding: 12px 14px; color: #475569; line-height: 1.5; font-size: 12.5px;">
                                <?php if (!empty($m['spesifikasi_teknis'])): ?>
                                    <?= nl2br(htmlspecialchars($m['spesifikasi_teknis'], ENT_QUOTES, 'UTF-8')) ?>
                                <?php else: ?>
                                    <span style="color: #94A3B8; font-style: italic;">Tidak ada catatan spesifikasi</span>
                                <?php endif; ?>
                            </td>

                            <!-- Stok Gudang & Satuan -->
                            <td style="padding: 12px 14px; text-align: right;">
                                <div style="font-weight: 800; font-size: 14.5px; color: #0F2C59;">
                                    <?= number_format((float) $m['stok_gudang'], 2) ?>
                                </div>
                                <div style="font-size: 11.5px; font-weight: 600; color: #64748B;">
                                    <?= htmlspecialchars($m['satuan'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>

                            <!-- Relasi Rekanan Vendor -->
                            <td style="padding: 12px 14px; text-align: center;">
                                <?php if ($vendorCount > 0): ?>
                                    <span
                                        style="display: inline-flex; align-items: center; gap: 4px; background: #DEF7EC; color: #03543F; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; font-weight: 700;"
                                        title="<?= $vendorCount ?> rekanan vendor menyediakan material ini">
                                        🏢 <?= $vendorCount ?> Rekanan
                                    </span>
                                <?php else: ?>
                                    <span
                                        style="display: inline-block; background: #F1F5F9; color: #94A3B8; padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">
                                        Belum Terpetakan
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Tombol Aksi: Edit & Hapus Berdasarkan RBAC -->
                            <td style="padding: 12px 14px; text-align: center;">
                                <div style="display: flex; gap: 6px; justify-content: center; align-items: center;">
                                    <?php if ($canManage): ?>
                                        <!-- Tombol Edit -->
                                        <a href="/scm/material/edit/<?= (int) $m['id'] ?>"
                                            style="background: #1E56A0; color: #FFFFFF; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;"
                                            title="Ubah Spesifikasi & Stok">
                                            ✏️ Edit
                                        </a>

                                        <!-- Tombol Hapus dengan Konfirmasi -->
                                        <form method="POST" action="/scm/material/delete/<?= (int) $m['id'] ?>"
                                            style="display: inline;"
                                            onsubmit="return confirm('Apakah Anda yakin ingin menghapus material \'<?= htmlspecialchars(addslashes($m['nama_material']), ENT_QUOTES, 'UTF-8') ?>\' (<?= htmlspecialchars($m['kode_material'], ENT_QUOTES, 'UTF-8') ?>)?\n\nCatatan: Jika material ini sedang terhubung ke data vendor atau pesanan PO, penghapusan akan otomatis dicegah untuk menjaga integritas sistem.');">
                                            <input type="hidden" name="csrf_token"
                                                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit"
                                                style="background: #DC2626; color: #FFFFFF; border: none; padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer;"
                                                title="Hapus Material">
                                                🗑️
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: #94A3B8; font-style: italic;">Mode Lihat</span>
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
    <div id="noMatchMessage"
        style="display: none; padding: 25px; text-align: center; color: #64748B; background: #F8FAFC; border: 1px dashed var(--border-color); border-radius: 6px; margin-top: 15px;">
        🔍 Tidak ditemukan data bahan baku material yang sesuai dengan kata kunci pencarian.
    </div>
</div>

<!-- ==============================================================================
     INTERAKTIVITAS VANILLA JAVASCRIPT: LIVE FILTER PENCARIAN MATERIAL
     ============================================================================== -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('materialSearch');
        const tableRows = document.querySelectorAll('.material-row');
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