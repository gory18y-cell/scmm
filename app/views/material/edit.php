<?php
/**
 * ==============================================================================
 * VIEW: PERUBAHAN DATA BAHAN BAKU MATERIAL - MODUL MATERIAL
 * ==============================================================================
 */
$vendorCount = (int)($material['vendor_count'] ?? 0);
?>

<!-- Notifikasi Galat Validasi -->
<?php if (!empty($flashError)): ?>
    <div class="alert-box" style="background-color: var(--danger-bg); border-left: 4px solid #DC2626; color: var(--danger-text); margin-bottom: 20px;">
        <span style="font-size: 18px;">⚠️</span>
        <div><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<div class="card-panel" style="max-width: 850px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 22px; flex-wrap: wrap; gap: 10px;">
        <div>
            <div class="card-title" style="margin-bottom: 2px;">
                <span>✏️</span> Ubah Data Bahan Baku Logam
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Perbarui rincian spesifikasi teknis JIS/ASTM, kategori, atau penyesuaian volume stok fisik gudang.
            </div>
        </div>
        <a href="/scm/material" style="color: var(--text-muted); font-size: 13.5px; font-weight: 600; text-decoration: none;">
            ← Kembali ke Katalog Bahan Baku
        </a>
    </div>

    <?php if ($vendorCount > 0): ?>
        <!-- Banner Peringatan Keterhubungan Rekanan Vendor -->
        <div class="alert-box alert-info" style="margin-bottom: 22px; background: #F0FDF4; border-left: 4px solid #16A34A; color: #166534;">
            <span style="font-size: 20px;">🏢</span>
            <div>
                <strong>Material Terhubung ke <?= $vendorCount ?> Rekanan Vendor Terdaftar:</strong>
                <div style="margin-top: 4px; font-size: 12.5px; color: #14532D; line-height: 1.5;">
                    Material ini sudah dipetakan ke katalog pemasok rekanan. Pastikan perubahan kode material atau nama tetap selaras dengan kontrak pengadaan yang berlaku.
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Formulir Ubah Material -->
    <form method="POST" action="/scm/material/update/<?= (int)$material['id'] ?>" id="formMaterialEdit">
        <!-- Token Proteksi CSRF -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <!-- Bagian 1: Identitas & Klasifikasi Material -->
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin-bottom: 22px;">
            <div style="font-weight: 700; color: #0F2C59; margin-bottom: 15px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span>🏷️</span> Identitas Bahan Baku & Klasifikasi
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <!-- Kode Material -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Kode Material <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="kode_material" value="<?= htmlspecialchars($material['kode_material'], ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: MAT-SPCC-05" required maxlength="25"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; text-transform: uppercase; font-family: monospace; font-weight: 600;">
                    <small style="color: #64748B; font-size: 11.5px;">Kode unik master material (maks. 25 karakter).</small>
                </div>

                <!-- Nama Material -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Nama Bahan Baku <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="nama_material" value="<?= htmlspecialchars($material['nama_material'], ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: Pelat Baja SPCC 1.2mm JIS G3141" required maxlength="120"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                    <small style="color: #64748B; font-size: 11.5px;">Nama deskriptif material logam (maks. 120 karakter).</small>
                </div>

                <!-- Kategori -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Kategori Logam <span style="color: #DC2626;">*</span>
                    </label>
                    <?php $selectedKat = $material['kategori'] ?? 'Plat Baja'; ?>
                    <select name="kategori" required
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; background: #FFFFFF; font-weight: 600;">
                        <option value="Plat Baja" <?= ($selectedKat === 'Plat Baja') ? 'selected' : '' ?>>Plat Baja (Cold/Hot Rolled Sheet)</option>
                        <option value="Pipa Baja" <?= ($selectedKat === 'Pipa Baja') ? 'selected' : '' ?>>Pipa Baja (Seamless / Welded Tube)</option>
                        <option value="Aluminium Alloy" <?= ($selectedKat === 'Aluminium Alloy') ? 'selected' : '' ?>>Aluminium Alloy (Extrusion / Ingot)</option>
                        <option value="Besi Tempa" <?= ($selectedKat === 'Besi Tempa') ? 'selected' : '' ?>>Besi Tempa (Forged Steel Rod / Billet)</option>
                    </select>
                </div>

                <!-- Satuan Pengukuran -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Satuan Pengukuran <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="text" name="satuan" value="<?= htmlspecialchars($material['satuan'] ?? 'Kg', ENT_QUOTES, 'UTF-8') ?>" 
                           placeholder="Contoh: Kg, Batang, Lembar" required maxlength="20"
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
                    <small style="color: #64748B; font-size: 11.5px;">Satuan berat/unit dalam persediaan.</small>
                </div>

                <!-- Stok Gudang -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Stok Fisik Gudang Saat Ini
                    </label>
                    <input type="number" name="stok_gudang" step="0.01" min="0" value="<?= htmlspecialchars($material['stok_gudang'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>" 
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; font-weight: 700;">
                    <small style="color: #64748B; font-size: 11.5px;">Jumlah saldo persediaan fisik di gudang penerimaan.</small>
                </div>
            </div>
        </div>

        <!-- Bagian 2: Spesifikasi Teknis & Standarisasi Mutu -->
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin-bottom: 25px;">
            <div style="font-weight: 700; color: #0F2C59; margin-bottom: 15px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span>🔬</span> Spesifikasi Teknis Manufaktur
            </div>

            <div>
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                    Deskripsi Spesifikasi Teknis (JIS / ASTM / Dimensi / Metalurgi)
                </label>
                <textarea name="spesifikasi_teknis" rows="4" placeholder="Detail teknis standar JIS/ASTM atau parameter ukuran..."
                          style="width: 100%; padding: 10px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; line-height: 1.5; font-family: inherit;"><?= htmlspecialchars($material['spesifikasi_teknis'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                <small style="color: #64748B; font-size: 11.5px;">Detail teknis parameter kimia, toleransi ukuran, atau standar sertifikasi bahan baku.</small>
            </div>
        </div>

        <!-- Tombol Aksi Formulir -->
        <div style="display: flex; gap: 12px; justify-content: flex-end; align-items: center;">
            <a href="/scm/material" 
               style="padding: 10px 20px; border: 1px solid var(--border-color); border-radius: 6px; color: #475569; font-weight: 600; text-decoration: none; font-size: 13.5px; background: #FFFFFF;">
                Batal
            </a>
            <button type="submit" 
                    style="background: var(--primary); color: #FFFFFF; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 700; font-size: 13.5px; cursor: pointer; box-shadow: var(--shadow-sm); display: inline-flex; align-items: center; gap: 6px;">
                <span>💾</span> Simpan Perubahan
            </button>
        </div>
    </form>
</div>

