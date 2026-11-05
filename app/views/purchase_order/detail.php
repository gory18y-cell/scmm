<?php
/**
 * ==============================================================================
 * VIEW: DETAIL DOKUMEN PURCHASE ORDER, PENERIMAAN GUDANG & RIWAYAT STOK - MODUL 2
 * ==============================================================================
 */
$userRole    = $_SESSION['user']['role'] ?? ($authUser['role'] ?? null);
$canManagePo = in_array($userRole, ['Admin', 'Purchasing'], true);

// Penentuan Badge Status PO
switch ($po['status_po'] ?? '') {
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
        $badgeStyle = 'background: #F3F4F6; color: #374151; border: 1px solid #E5E7EB;';
        $badgeIcon  = '•';
        break;
}

// Perhitungan metrik kuantitas pesanan dan penerimaan
$calcTotalPesanan   = 0.0;
$calcTotalDiterima  = 0.0;
$calcTotalSisa      = 0.0;
if (!empty($items)) {
    foreach ($items as $it) {
        $calcTotalPesanan  += (float)$it['jumlah_pesanan'];
        $calcTotalDiterima += (float)$it['jumlah_diterima'];
        $calcTotalSisa     += (float)$it['sisa_pesanan'];
    }
}
$isReceivingActive = in_array($po['status_po'], ['Dalam Pengiriman', 'Sebagian Diterima'], true) && $calcTotalSisa > 0.0001;
?>

<!-- Notifikasi Flash Message -->
<?php if (!empty($flashSuccess)): ?>
    <div class="alert-box alert-success no-print" style="margin-bottom: 20px;">
        <span style="font-size: 18px;">✅</span>
        <div><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<?php if (!empty($flashError)): ?>
    <div class="alert-box no-print" style="background-color: var(--danger-bg); border-left: 4px solid #DC2626; color: var(--danger-text); margin-bottom: 20px;">
        <span style="font-size: 18px;">⚠️</span>
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<!-- Dokumen Faktur Purchase Order -->
<div class="card-panel" id="poPrintArea" style="max-width: 1100px; margin: 0 auto;">
    <!-- Toolbar Atas & Navigasi -->
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 22px; flex-wrap: wrap; gap: 10px;">
        <div>
            <div class="card-title" style="margin-bottom: 2px;">
                <span>📑</span> Dokumen Purchase Order: <?= htmlspecialchars($po['no_po'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Modul 2 (Latifah Noor Azizah - 43250555): Lembar Faktur Resmi Pengadaan Bahan Logam & Penerimaan Gudang
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="/scm/purchase-order" 
               style="background: #E2E8F0; color: #334155; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; text-decoration: none;">
                ← Kembali ke Daftar PO
            </a>
            <button onclick="window.print()" 
                    style="background: var(--primary); color: #FFFFFF; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span>🖨️</span> Cetak Lembar PO
            </button>
        </div>
    </div>

    <!-- Header Surat Resmi PO (Kop Surat) -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0F2C59; padding-bottom: 16px; margin-bottom: 20px;">
        <div>
            <div style="font-size: 22px; font-weight: 800; color: #0F2C59; letter-spacing: 0.5px;">
                PURCHASE ORDER (PO)
            </div>
            <div style="color: #475569; font-size: 13px; margin-top: 2px;">
                Sistem Supply Chain Management Industri Manufaktur Otomotif
            </div>
            <div style="margin-top: 10px; font-size: 14px;">
                Nomor Dokumen: <strong style="color: #0F2C59; font-size: 16px; font-family: monospace;"><?= htmlspecialchars($po['no_po'], ENT_QUOTES, 'UTF-8') ?></strong>
            </div>
        </div>

        <div style="text-align: right;">
            <div style="margin-bottom: 8px;">
                <span style="display: inline-flex; align-items: center; gap: 5px; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 700; <?= $badgeStyle ?>">
                    <span><?= $badgeIcon ?></span> <?= htmlspecialchars($po['status_po'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
            <div style="font-size: 12.5px; color: #64748B;">
                Tanggal Diterbitkan: <strong><?= date('d/m/Y', strtotime($po['tanggal_po'])) ?></strong>
            </div>
            <div style="font-size: 11.5px; color: #94A3B8; margin-top: 2px;">
                Timestamp: <?= date('d/m/Y H:i', strtotime($po['created_at'])) ?> WIB
            </div>
        </div>
    </div>

    <!-- Ringkasan Status Progres Pemenuhan Logistik (Metrik Fisik) -->
    <div class="no-print" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 22px;">
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11.5px; font-weight: 700; color: #64748B; text-transform: uppercase;">Total Pesanan</div>
            <div style="font-size: 20px; font-weight: 800; color: #0F2C59; line-height: 1.2;">
                <?= number_format($calcTotalPesanan, 2, ',', '.') ?> <span style="font-size: 12px; font-weight: 600; color: #64748B;">Unit/Kg</span>
            </div>
        </div>

        <div style="background: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11.5px; font-weight: 700; color: #166534; text-transform: uppercase;">Diterima Gudang</div>
            <div style="font-size: 20px; font-weight: 800; color: #15803D; line-height: 1.2;">
                <?= number_format($calcTotalDiterima, 2, ',', '.') ?> <span style="font-size: 12px; font-weight: 600; color: #166534;">Unit/Kg</span>
            </div>
        </div>

        <div style="background: <?= ($calcTotalSisa > 0.0001) ? '#FFFBEB' : '#F1F5F9' ?>; border: 1px solid <?= ($calcTotalSisa > 0.0001) ? '#FDE68A' : '#E2E8F0' ?>; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11.5px; font-weight: 700; color: <?= ($calcTotalSisa > 0.0001) ? '#92400E' : '#64748B' ?>; text-transform: uppercase;">Sisa Menunggu Fisik</div>
            <div style="font-size: 20px; font-weight: 800; color: <?= ($calcTotalSisa > 0.0001) ? '#B45309' : '#64748B' ?>; line-height: 1.2;">
                <?= number_format($calcTotalSisa, 2, ',', '.') ?> <span style="font-size: 12px; font-weight: 600; color: #64748B;">Unit/Kg</span>
            </div>
        </div>

        <div style="background: #EFF6FF; border: 1px solid #BFDBFE; border-radius: 8px; padding: 12px 16px;">
            <div style="font-size: 11.5px; font-weight: 700; color: #1E40AF; text-transform: uppercase;">Total Nilai PO</div>
            <div style="font-size: 20px; font-weight: 800; color: #1D4ED8; line-height: 1.2;">
                Rp <?= number_format((float)$po['total_nilai'], 2, ',', '.') ?>
            </div>
        </div>
    </div>

    <!-- Informasi 3 Kolom: Header PO, Rekanan Vendor & Logistik -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; margin-bottom: 22px;">
        <!-- Kolom 1: Rekanan Vendor -->
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px;">
            <div style="font-weight: 700; color: #0F2C59; font-size: 13px; margin-bottom: 8px; text-transform: uppercase; border-bottom: 1px dashed #CBD5E1; padding-bottom: 6px;">
                🏢 Rekanan Pemasok (Vendor)
            </div>
            <div style="font-weight: 700; font-size: 14px; color: #0F2C59;">
                <?= htmlspecialchars($po['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div style="font-size: 12px; color: #64748B; margin-top: 2px;">
                Kode: <strong><?= htmlspecialchars($po['kode_vendor'], ENT_QUOTES, 'UTF-8') ?></strong> • Spesialisasi: <?= htmlspecialchars($po['spesialisasi_logam'], ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div style="font-size: 12px; color: #475569; margin-top: 6px;">
                PIC: <?= htmlspecialchars($po['kontak_person'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($po['vendor_telepon'], ENT_QUOTES, 'UTF-8') ?>)
            </div>
            <?php if (!empty($po['vendor_email'])): ?>
                <div style="font-size: 12px; color: #475569;">
                    Email: <?= htmlspecialchars($po['vendor_email'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>
            <div style="font-size: 12px; color: #64748B; margin-top: 4px; line-height: 1.4;">
                <?= nl2br(htmlspecialchars($po['vendor_alamat'], ENT_QUOTES, 'UTF-8')) ?>
            </div>
            <div style="margin-top: 8px;">
                <span style="background: #EFF6FF; color: #1D4ED8; padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; border: 1px solid #BFDBFE;">
                    Akreditasi: <?= htmlspecialchars($po['jenis_akreditasi'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>

        <!-- Kolom 2: Petugas & Jadwal Logistik -->
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px;">
            <div style="font-weight: 700; color: #0F2C59; font-size: 13px; margin-bottom: 8px; text-transform: uppercase; border-bottom: 1px dashed #CBD5E1; padding-bottom: 6px;">
                🚚 Jadwal Logistik & Kedatangan
            </div>
            <table style="width: 100%; font-size: 12.5px; border-collapse: collapse;">
                <tr>
                    <td style="padding: 4px 0; color: #64748B; width: 130px;">Tanggal PO:</td>
                    <td style="padding: 4px 0; font-weight: 600; color: #0F2C59;"><?= date('d F Y', strtotime($po['tanggal_po'])) ?></td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #64748B;">Lead Time Maksimal:</td>
                    <td style="padding: 4px 0; font-weight: 700; color: #0F2C59;"><?= (int)$po['lead_time_hari'] ?> hari kerja</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #64748B;">Estimasi Tiba (ETA):</td>
                    <td style="padding: 4px 0; font-weight: 800; color: #0F2C59;">
                        <?= date('d F Y', strtotime($po['estimasi_kedatangan'])) ?>
                        <small style="display: block; color: #16A34A; font-weight: 600;">(Docking Gudang Pabrik)</small>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #64748B;">Petugas Pembuat:</td>
                    <td style="padding: 4px 0; font-weight: 600; color: #0F2C59;">
                        <?= htmlspecialchars($po['nama_user'], ENT_QUOTES, 'UTF-8') ?> 
                        <span style="font-size: 11px; color: #64748B;">(<?= htmlspecialchars($po['role_user'], ENT_QUOTES, 'UTF-8') ?>)</span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Kolom 3: Catatan Pengiriman -->
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 14px;">
            <div style="font-weight: 700; color: #0F2C59; font-size: 13px; margin-bottom: 8px; text-transform: uppercase; border-bottom: 1px dashed #CBD5E1; padding-bottom: 6px;">
                📦 Instruksi & Catatan Khusus
            </div>
            <?php if (!empty($po['catatan_pengiriman'])): ?>
                <div style="font-size: 12.5px; color: #334155; line-height: 1.5; white-space: pre-wrap;"><?= htmlspecialchars($po['catatan_pengiriman'], ENT_QUOTES, 'UTF-8') ?></div>
            <?php else: ?>
                <div style="font-size: 12.5px; color: #94A3B8; font-style: italic;">
                    Tidak ada catatan pengiriman khusus. Pengiriman mengikuti standar protokol logistik manufaktur otomotif.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabel Rincian Material Pesanan -->
    <div style="margin-bottom: 25px;">
        <div style="font-weight: 700; color: #0F2C59; font-size: 14px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span>🔩</span> Rincian Material Bahan Baku Pesanan
            </div>
            <span style="font-size: 12px; color: #64748B; font-weight: normal;">
                Terhubung langsung ke katalog master <strong>Bahan Baku</strong>
            </span>
        </div>

        <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; font-size: 13px; text-align: left;">
                <thead>
                    <tr style="background-color: #0F2C59; color: #FFFFFF; font-weight: 600;">
                        <th style="padding: 10px 12px; width: 40px; text-align: center;">No</th>
                        <th style="padding: 10px 12px;">Bahan Baku Logam</th>
                        <th style="padding: 10px 12px; width: 90px; text-align: center;">Satuan</th>
                        <th style="padding: 10px 12px; text-align: right; width: 95px;">Dipesan</th>
                        <th style="padding: 10px 12px; text-align: right; width: 95px;">Diterima</th>
                        <th style="padding: 10px 12px; text-align: right; width: 95px;">Sisa</th>
                        <th style="padding: 10px 12px; text-align: center; width: 120px;">Status Item</th>
                        <th style="padding: 10px 12px; text-align: right; width: 110px;">Stok Gudang</th>
                        <th style="padding: 10px 12px; text-align: right; width: 130px;">Harga Satuan</th>
                        <th style="padding: 10px 12px; text-align: right; width: 140px;">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 1;
                    if (empty($items)): 
                    ?>
                        <tr>
                            <td colspan="10" style="padding: 20px; text-align: center; color: #64748B;">
                                Tidak ada rincian material pada dokumen PO ini.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        foreach ($items as $item): 
                            $ordered  = (float)$item['jumlah_pesanan'];
                            $received = (float)$item['jumlah_diterima'];
                            $rem      = (float)$item['sisa_pesanan'];
                            
                            if ($rem <= 0.0001) {
                                $itemBadge = '<span style="background: #DEF7EC; color: #03543F; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">✓ Lengkap</span>';
                            } elseif ($received > 0.0001) {
                                $itemBadge = '<span style="background: #FFEDD5; color: #9A3412; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 700;">⏳ Parsial (' . number_format($received, 2) . ')</span>';
                            } else {
                                $itemBadge = '<span style="background: #F1F5F9; color: #64748B; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">Belum Tiba</span>';
                            }
                        ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 10px 12px; text-align: center; color: #64748B;"><?= $no++ ?></td>
                                <td style="padding: 10px 12px;">
                                    <strong style="color: #0F2C59;"><?= htmlspecialchars($item['nama_material'], ENT_QUOTES, 'UTF-8') ?></strong>
                                    <div style="font-size: 11.5px; color: #64748B;">
                                        Kode: <span style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($item['kode_material'], ENT_QUOTES, 'UTF-8') ?></span> • <?= htmlspecialchars($item['kategori'], ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td style="padding: 10px 12px; text-align: center; color: #475569;">
                                    <?= htmlspecialchars($item['satuan'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #0F2C59;">
                                    <?= number_format($ordered, 2, ',', '.') ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #16A34A;">
                                    <?= number_format($received, 2, ',', '.') ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: <?= ($rem > 0.0001) ? '#D97706' : '#64748B' ?>;">
                                    <?= number_format($rem, 2, ',', '.') ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <?= $itemBadge ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; color: #0369A1; font-weight: 700; background: #F0F9FF;">
                                    <?= number_format((float)($item['stok_gudang'] ?? 0), 2, ',', '.') ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; color: #334155;">
                                    Rp <?= number_format((float)$item['harga_satuan'], 2, ',', '.') ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #0F2C59;">
                                    Rp <?= number_format((float)$item['subtotal'], 2, ',', '.') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #F8FAFC; font-weight: 700; border-top: 2px solid #CBD5E1;">
                        <td colspan="3" style="padding: 12px 14px; text-align: right; color: #0F2C59; font-size: 13px;">
                            TOTAL KUANTITAS:
                        </td>
                        <td style="padding: 12px 14px; text-align: right; color: #0F2C59; font-size: 13px;">
                            <?= number_format($calcTotalPesanan, 2, ',', '.') ?>
                        </td>
                        <td style="padding: 12px 14px; text-align: right; color: #16A34A; font-size: 13px;">
                            <?= number_format($calcTotalDiterima, 2, ',', '.') ?>
                        </td>
                        <td style="padding: 12px 14px; text-align: right; color: #D97706; font-size: 13px;">
                            <?= number_format($calcTotalSisa, 2, ',', '.') ?>
                        </td>
                        <td colspan="3" style="padding: 12px 14px; text-align: right; color: #0F2C59; font-size: 13px;">
                            GRAND TOTAL NILAI PO:
                        </td>
                        <td style="padding: 12px 14px; text-align: right; color: #0F2C59; font-size: 15px; font-weight: 800;">
                            Rp <?= number_format((float)$po['total_nilai'], 2, ',', '.') ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- ==============================================================================
         FORMULIR PENERIMAAN FISIK BARANG GUDANG (DOCKING LOGISTIK)
         Aktif ketika PO berstatus 'Dalam Pengiriman' atau 'Sebagian Diterima'
         ============================================================================== -->
    <?php if ($canManagePo && $isReceivingActive): ?>
        <div class="no-print" style="background: #F0FDF4; border: 2px solid #86EFAC; border-radius: 8px; padding: 20px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div style="font-weight: 800; color: #166534; font-size: 15px; display: flex; align-items: center; gap: 8px;">
                        <span>📥</span> Catat Penerimaan Fisik Barang Gudang (Docking)
                    </div>
                    <div style="font-size: 12.5px; color: #15803D; margin-top: 2px;">
                        Petugas gudang memeriksa barang fisik yang tiba. Konfirmasi penerimaan akan langsung <strong>menambah stok pada master Bahan Baku</strong> dan mencatat nomor tanda terima resmi.
                    </div>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button type="button" onclick="fillAllRemaining()" 
                            style="background: #15803D; color: #FFFFFF; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer;">
                        ✓ Terima Penuh Semua Sisa
                    </button>
                    <button type="button" onclick="clearAllInputs()" 
                            style="background: #E2E8F0; color: #334155; border: none; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer;">
                        Kosongkan
                    </button>
                </div>
            </div>

            <form method="POST" action="/scm/purchase-order/receive/<?= (int)$po['id'] ?>" id="formReceivePo" 
                  onsubmit="return confirm('Apakah Anda yakin ingin memproses penerimaan barang ini?\n\nKuantitas yang diterima akan otomatis ditambahkan ke stok fisik master bahan baku.');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 16px; background: #FFFFFF; padding: 14px; border-radius: 6px; border: 1px solid #BBF7D0;">
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 12.5px; color: #166534; margin-bottom: 5px;">
                            Tanggal Kedatangan Fisik <span style="color: #DC2626;">*</span>
                        </label>
                        <input type="date" name="tanggal_terima" value="<?= date('Y-m-d') ?>" required
                               style="width: 100%; padding: 8px 12px; border: 1px solid #86EFAC; border-radius: 5px; font-size: 13px;">
                    </div>
                    <div>
                        <label style="display: block; font-weight: 700; font-size: 12.5px; color: #166534; margin-bottom: 5px;">
                            No. Surat Jalan Vendor / Catatan Docking
                        </label>
                        <input type="text" name="catatan_penerimaan" placeholder="Contoh: Surat Jalan SJ-09823 / Truk Plat B-1234-XYZ" 
                               style="width: 100%; padding: 8px 12px; border: 1px solid #86EFAC; border-radius: 5px; font-size: 13px;">
                    </div>
                </div>

                <!-- Daftar Input Jumlah Diterima Per Item -->
                <div style="background: #FFFFFF; border: 1px solid #BBF7D0; border-radius: 6px; overflow: hidden; margin-bottom: 16px;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                        <thead>
                            <tr style="background: #DCFCE7; color: #166534; font-weight: 700;">
                                <th style="padding: 9px 12px; text-align: left;">Bahan Baku Logam</th>
                                <th style="padding: 9px 12px; text-align: right; width: 110px;">Dipesan</th>
                                <th style="padding: 9px 12px; text-align: right; width: 110px;">Sudah Ada</th>
                                <th style="padding: 9px 12px; text-align: right; width: 110px;">Sisa Ditunggu</th>
                                <th style="padding: 9px 12px; text-align: right; width: 180px;">Jumlah Diterima Saat Ini</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $it): 
                                $sisa = (float)$it['sisa_pesanan'];
                                if ($sisa <= 0.0001) continue;
                            ?>
                                <tr style="border-bottom: 1px solid #E2E8F0;">
                                    <td style="padding: 10px 12px;">
                                        <strong><?= htmlspecialchars($it['nama_material'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <div style="font-size: 11.5px; color: #64748B;">
                                            Kode: <?= htmlspecialchars($it['kode_material'], ENT_QUOTES, 'UTF-8') ?> (Satuan: <?= htmlspecialchars($it['satuan'], ENT_QUOTES, 'UTF-8') ?>)
                                        </div>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; color: #64748B;">
                                        <?= number_format((float)$it['jumlah_pesanan'], 2, ',', '.') ?>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; color: #16A34A; font-weight: 600;">
                                        <?= number_format((float)$it['jumlah_diterima'], 2, ',', '.') ?>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right; color: #D97706; font-weight: 700;">
                                        <?= number_format($sisa, 2, ',', '.') ?> <?= htmlspecialchars($it['satuan'], ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: right;">
                                        <div style="display: flex; align-items: center; justify-content: flex-end; gap: 6px;">
                                            <input type="number" 
                                                   name="items[<?= (int)$it['id'] ?>]" 
                                                   class="receive-qty-input"
                                                   step="0.01" 
                                                   min="0" 
                                                   max="<?= $sisa ?>" 
                                                   data-max="<?= $sisa ?>"
                                                   value="<?= $sisa ?>" 
                                                   required
                                                   style="width: 110px; padding: 7px 10px; border: 1px solid #86EFAC; border-radius: 4px; font-weight: 700; text-align: right; font-size: 13.5px; color: #0F2C59;">
                                            <span style="font-size: 12px; color: #475569; font-weight: 600; width: 45px; text-align: left;">
                                                <?= htmlspecialchars($it['satuan'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div style="font-size: 12px; color: #166534;">
                        * Jika barang baru datang sebagian, ubah angka di kolom penerimaan sesuai jumlah fisik yang nyata diterima.
                    </div>
                    <button type="submit" 
                            style="background: #16A34A; color: #FFFFFF; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 700; font-size: 13.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: var(--shadow-sm);">
                        <span>📥</span> Konfirmasi Penerimaan Gudang & Tambah Stok
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- ==============================================================================
         RIWAYAT PENERIMAAN FISIK GUDANG (RECEIPTS HISTORY)
         ============================================================================== -->
    <div style="margin-bottom: 25px;">
        <div style="font-weight: 700; color: #0F2C59; font-size: 14px; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
            <span>📋</span> Riwayat Penerimaan Gudang (Goods Receipt)
        </div>

        <?php if (empty($receipts)): ?>
            <div style="background: #F8FAFC; border: 1px dashed var(--border-color); border-radius: 8px; padding: 18px; text-align: center; color: #64748B; font-size: 13px;">
                Belum ada riwayat penerimaan barang masuk untuk Purchase Order ini.
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <?php foreach ($receipts as $rcpt): ?>
                    <div style="background: #FFFFFF; border: 1px solid var(--border-color); border-left: 4px solid #16A34A; border-radius: 6px; padding: 14px 16px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px; flex-wrap: wrap; gap: 8px;">
                            <div>
                                <span style="font-weight: 800; color: #0F2C59; font-family: monospace; font-size: 14px;">
                                    <?= htmlspecialchars($rcpt['no_penerimaan'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span style="margin-left: 10px; font-size: 12px; color: #64748B;">
                                    Tanggal Tiba: <strong><?= date('d/m/Y', strtotime($rcpt['tanggal_terima'])) ?></strong>
                                </span>
                                <span style="margin-left: 10px; font-size: 12px; color: #64748B;">
                                    Diterima oleh: <strong><?= htmlspecialchars($rcpt['nama_penerima'] ?? $rcpt['username_penerima'] ?? 'Petugas Gudang', ENT_QUOTES, 'UTF-8') ?></strong>
                                </span>
                            </div>
                            <span style="background: #DEF7EC; color: #03543F; padding: 2px 10px; border-radius: 12px; font-size: 12px; font-weight: 700;">
                                +<?= number_format((float)($rcpt['total_qty_diterima'] ?? 0), 2, ',', '.') ?> Masuk Stok
                            </span>
                        </div>

                        <?php if (!empty($rcpt['catatan'])): ?>
                            <div style="font-size: 12.5px; color: #475569; margin-bottom: 10px; background: #F8FAFC; padding: 6px 10px; border-radius: 4px;">
                                📝 <em><?= htmlspecialchars($rcpt['catatan'], ENT_QUOTES, 'UTF-8') ?></em>
                            </div>
                        <?php endif; ?>

                        <!-- Sub-table Item yang Diterima -->
                        <?php if (!empty($rcpt['items'])): ?>
                            <div style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse; font-size: 12px; background: #FAFAFA;">
                                    <thead>
                                        <tr style="background: #F1F5F9; color: #475569; text-align: left;">
                                            <th style="padding: 6px 10px;">Kode & Nama Material</th>
                                            <th style="padding: 6px 10px;">Kategori</th>
                                            <th style="padding: 6px 10px; text-align: right; width: 140px;">Jumlah Diterima</th>
                                            <th style="padding: 6px 10px; text-align: center; width: 100px;">Satuan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($rcpt['items'] as $rItem): ?>
                                            <tr style="border-bottom: 1px solid #E2E8F0;">
                                                <td style="padding: 6px 10px;">
                                                    <strong><?= htmlspecialchars($rItem['nama_material'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                    <span style="font-family: monospace; color: #64748B; margin-left: 6px;">(<?= htmlspecialchars($rItem['kode_material'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                                </td>
                                                <td style="padding: 6px 10px; color: #64748B;"><?= htmlspecialchars($rItem['kategori'], ENT_QUOTES, 'UTF-8') ?></td>
                                                <td style="padding: 6px 10px; text-align: right; font-weight: 700; color: #16A34A;">
                                                    +<?= number_format((float)$rItem['jumlah_diterima'], 2, ',', '.') ?>
                                                </td>
                                                <td style="padding: 6px 10px; text-align: center; color: #64748B;"><?= htmlspecialchars($rItem['satuan'], ENT_QUOTES, 'UTF-8') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tanda Tangan Resmi Dokumen (Khusus Mode Cetak) -->
    <div style="display: none;" class="print-only">
        <div style="display: flex; justify-content: space-between; margin-top: 50px; text-align: center; font-size: 13px;">
            <div style="width: 200px;">
                <div>Diterbitkan Oleh,</div>
                <div style="height: 60px;"></div>
                <div style="font-weight: 700; text-decoration: underline;"><?= htmlspecialchars($po['nama_user'], ENT_QUOTES, 'UTF-8') ?></div>
                <div style="color: #64748B;">Divisi Purchasing & Pengadaan</div>
            </div>
            <div style="width: 200px;">
                <div>Disetujui Oleh,</div>
                <div style="height: 60px;"></div>
                <div style="font-weight: 700; text-decoration: underline;">Manajer SCM & Logistik</div>
                <div style="color: #64748B;">PT Manufaktur Otomotif</div>
            </div>
            <div style="width: 200px;">
                <div>Konfirmasi Rekanan,</div>
                <div style="height: 60px;"></div>
                <div style="font-weight: 700; text-decoration: underline;"><?= htmlspecialchars($po['kontak_person'], ENT_QUOTES, 'UTF-8') ?></div>
                <div style="color: #64748B;"><?= htmlspecialchars($po['nama_vendor'], ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>
    </div>

    <!-- Panel Alur Transisi Status & Pembatalan (No Print) -->
    <div class="no-print" style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 20px; margin-top: 25px;">
        <div style="font-weight: 700; color: #0F2C59; font-size: 14px; margin-bottom: 6px; display: flex; align-items: center; gap: 8px;">
            <span>🔄</span> Alur Transisi Status Dokumen Purchase Order
        </div>
        <p style="color: #64748B; font-size: 12.5px; margin-bottom: 16px;">
            Pengendalian status mengikuti aturan pengadaan manufaktur (Draft → Disetujui → Dalam Pengiriman → Sebagian Diterima → Selesai). Dokumen Selesai atau Dibatalkan bersifat final.
        </p>

        <?php if ($canManagePo): ?>
            <?php if ($po['status_po'] === 'Draft'): ?>
                <!-- Aksi untuk Status Draft -->
                <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                    <!-- Setujui PO -->
                    <form method="POST" action="/scm/purchase-order/update-status/<?= (int)$po['id'] ?>" onsubmit="return confirm('Apakah Anda yakin ingin MENYETUJUI dokumen Purchase Order ini?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="status_po" value="Disetujui">
                        <button type="submit" 
                                style="background: #1E40AF; color: #FFFFFF; border: none; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm);">
                            <span>✓</span> Setujui Purchase Order (Disetujui)
                        </button>
                    </form>

                    <!-- Batalkan PO -->
                    <form method="POST" action="/scm/purchase-order/cancel/<?= (int)$po['id'] ?>" style="display: inline-flex; gap: 8px; align-items: center;" onsubmit="return confirm('Apakah Anda yakin ingin MEMBATALKAN Purchase Order ini? Dokumen yang dibatalkan bersifat final.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="alasan_pembatalan" placeholder="Alasan pembatalan (opsional)..." 
                               style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; width: 250px;">
                        <button type="submit" 
                                style="background: #DC2626; color: #FFFFFF; border: none; padding: 9px 16px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                            <span>⛔</span> Batalkan PO
                        </button>
                    </form>
                </div>

            <?php elseif ($po['status_po'] === 'Disetujui'): ?>
                <!-- Aksi untuk Status Disetujui -->
                <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                    <!-- Kirim Barang -->
                    <form method="POST" action="/scm/purchase-order/update-status/<?= (int)$po['id'] ?>" onsubmit="return confirm('Konfirmasi bahwa barang saat ini sedang DALAM PENGIRIMAN oleh rekanan vendor?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="status_po" value="Dalam Pengiriman">
                        <button type="submit" 
                                style="background: #D97706; color: #FFFFFF; border: none; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm);">
                            <span>🚚</span> Proses Pengiriman Logistik (Dalam Pengiriman)
                        </button>
                    </form>

                    <!-- Batalkan PO -->
                    <form method="POST" action="/scm/purchase-order/cancel/<?= (int)$po['id'] ?>" style="display: inline-flex; gap: 8px; align-items: center;" onsubmit="return confirm('Apakah Anda yakin ingin MEMBATALKAN Purchase Order ini?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="alasan_pembatalan" placeholder="Alasan pembatalan..." 
                               style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; width: 250px;">
                        <button type="submit" 
                                style="background: #DC2626; color: #FFFFFF; border: none; padding: 9px 16px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer;">
                            <span>⛔</span> Batalkan PO
                        </button>
                    </form>
                </div>

            <?php elseif (in_array($po['status_po'], ['Dalam Pengiriman', 'Sebagian Diterima'], true)): ?>
                <!-- Petunjuk Alur Saat Barang Dalam Pengiriman / Sebagian Diterima -->
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                    <div style="font-size: 13px; color: #0F2C59; line-height: 1.5;">
                        ℹ️ <strong>Status Logistik:</strong> Barang sedang dalam alur penerimaan fisik docking gudang. Silakan gunakan <strong>Formulir Penerimaan Barang</strong> di atas untuk mencatat fisik barang yang masuk. Status PO akan otomatis menjadi <strong>Selesai</strong> jika seluruh kuantitas pesanan telah diterima penuh.
                    </div>

                    <!-- Batalkan PO (jika ada kendala fatal sebelum selesai) -->
                    <form method="POST" action="/scm/purchase-order/cancel/<?= (int)$po['id'] ?>" style="display: inline-flex; gap: 8px; align-items: center;" onsubmit="return confirm('Apakah Anda yakin ingin MEMBATALKAN sisa pesanan Purchase Order ini?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="text" name="alasan_pembatalan" placeholder="Alasan pembatalan sisa..." 
                               style="padding: 8px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13px; width: 220px;">
                        <button type="submit" 
                                style="background: #DC2626; color: #FFFFFF; border: none; padding: 9px 14px; border-radius: 6px; font-weight: 700; font-size: 12.5px; cursor: pointer;">
                            <span>⛔</span> Batalkan Sisa PO
                        </button>
                    </form>
                </div>

            <?php elseif ($po['status_po'] === 'Selesai'): ?>
                <div style="background: #DEF7EC; border: 1px solid #BCF0DA; border-radius: 6px; padding: 12px 16px; color: #03543F; font-size: 13px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">✅</span>
                    <div>
                        <strong>Dokumen Purchase Order Telah SELESAI (Final).</strong> Seluruh bahan baku telah diterima lengkap di docking gudang pabrik dan transaksi terkunci permanen.
                    </div>
                </div>

            <?php elseif ($po['status_po'] === 'Dibatalkan'): ?>
                <div style="background: #FEE2E2; border: 1px solid #FECACA; border-radius: 6px; padding: 12px 16px; color: #991B1B; font-size: 13px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 20px;">⛔</span>
                    <div>
                        <strong>Dokumen Purchase Order Telah DIBATALKAN (Final).</strong> Dokumen pengadaan ini tidak lagi aktif dan terkunci permanen.
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="background: #F1F5F9; border: 1px solid #CBD5E1; border-radius: 6px; padding: 12px 16px; font-size: 13px; color: #475569; display: flex; align-items: center; gap: 10px;">
                <span style="font-size: 18px;">🔒</span>
                <div>
                    <strong>Mode Lihat Rincian Dokumen:</strong> Akun Anda (Role: <strong><?= htmlspecialchars($userRole ?? 'Tamu / Belum Login', ENT_QUOTES, 'UTF-8') ?></strong>) tidak memiliki wewenang mengelola status atau penerimaan Purchase Order (Hanya diizinkan untuk Admin dan Purchasing).
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript Bantuan Form Penerimaan Gudang -->
<script>
function fillAllRemaining() {
    const inputs = document.querySelectorAll('.receive-qty-input');
    inputs.forEach(input => {
        const maxVal = input.getAttribute('data-max');
        if (maxVal) {
            input.value = maxVal;
        }
    });
}

function clearAllInputs() {
    const inputs = document.querySelectorAll('.receive-qty-input');
    inputs.forEach(input => {
        input.value = '0';
    });
}
</script>

<!-- Gaya Khusus Pencetakan Dokumen (Media Print) -->
<style>
@media print {
    body {
        background: #FFFFFF !important;
        color: #000000 !important;
    }
    .no-print, .sidebar, .top-header, .sidebar-toggle-btn {
        display: none !important;
    }
    .main-wrapper {
        margin-left: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    .content-area {
        padding: 0 !important;
    }
    .card-panel {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        max-width: 100% !important;
    }
    .print-only {
        display: block !important;
    }
}
</style>
