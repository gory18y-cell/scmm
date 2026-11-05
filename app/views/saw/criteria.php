<?php
/**
 * ==============================================================================
 * VIEW: KRITERIA PEMBOBOTAN SPK SAW - MODUL 3 (M. HADI PRATAMA)
 * ==============================================================================
 * Mengelola kriteria keputusan, bobot persentase, atribut Benefit/Cost,
 * serta validasi akumulasi bobot 100.00% untuk komputasi SAW.
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

<!-- Header & Penanggung Jawab Modul -->
<div class="card-panel" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div>
            <div class="card-title" style="margin-bottom: 3px;">
                <span>⚖️</span> <?= htmlspecialchars($pageTitle ?? 'Kriteria Pembobotan SPK SAW', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Penanggung Jawab: <strong>M. Hadi Pratama</strong> | Atribut Benefit/Cost & Pengawasan Konsistensi Bobot
            </div>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="/scm/saw-evaluation" class="btn-secondary" style="padding: 8px 14px; text-decoration: none; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; color: var(--text-color); background: #FFFFFF; display: inline-flex; align-items: center; gap: 6px;">
                <span>📝</span> Matriks Evaluasi
            </a>
            <a href="/scm/saw-ranking" class="btn-secondary" style="padding: 8px 14px; text-decoration: none; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; color: var(--text-color); background: #FFFFFF; display: inline-flex; align-items: center; gap: 6px;">
                <span>🏆</span> Hasil Perangkingan
            </a>
            <?php if ($canEdit): ?>
                <button type="button" onclick="openAddModal()" style="background: var(--primary); color: #FFFFFF; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm);">
                    <span>➕</span> Tambah Kriteria
                </button>
            <?php else: ?>
                <span style="font-size: 12px; background: #F1F5F9; color: #64748B; padding: 6px 12px; border-radius: 6px; font-weight: 600;">
                    🔒 Mode Lihat (Role: <?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>)
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Status Validasi Akumulasi Bobot 100% -->
<div class="card-panel" style="margin-bottom: 20px; border-left: 5px solid <?= $isExact100 ? '#10B981' : '#F59E0B' ?>;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div style="flex: 1; min-width: 250px;">
            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                <span style="font-size: 20px;"><?= $isExact100 ? '🎯' : '⚠️' ?></span>
                <strong style="font-size: 15px; color: <?= $isExact100 ? '#065F46' : '#92400E' ?>;">
                    <?= $isExact100 ? 'Akumulasi Bobot Kriteria Tepat 100.00% (Valid untuk SPK SAW)' : 'Akumulasi Bobot Belum Tepat 100.00%' ?>
                </strong>
            </div>
            <p style="margin: 0; font-size: 13px; color: #475569; line-height: 1.5;">
                Metode Simple Additive Weighting (SAW) mensyaratkan total seluruh bobot kriteria yang aktif (\(\sum W_j\)) bernilai <strong>tepat 100.00%</strong> (atau 1.00) agar nilai preferensi \(V_i\) dapat dihitung secara valid dan proporsional.
            </p>
        </div>

        <div style="text-align: right; min-width: 180px;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748B; margin-bottom: 4px;">Total Bobot Aktif</div>
            <div style="font-size: 28px; font-weight: 800; font-family: monospace; color: <?= $isExact100 ? '#10B981' : '#D97706' ?>;">
                <?= number_format((float)$totalWeight, 2, '.', '') ?>%
            </div>
            <span style="display: inline-block; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 4px; background: <?= $isExact100 ? '#DEF7EC' : '#FEF3C7' ?>; color: <?= $isExact100 ? '#03543F' : '#92400E' ?>;">
                <?= $isExact100 ? 'Lengkap & Konsisten' : ($totalWeight < 100 ? 'Kurang ' . number_format(100 - $totalWeight, 2, '.', '') . '%' : 'Kelebihan ' . number_format($totalWeight - 100, 2, '.', '') . '%') ?>
            </span>
        </div>
    </div>

    <!-- Progress Bar Akumulasi -->
    <div style="width: 100%; height: 10px; background: #E2E8F0; border-radius: 5px; margin-top: 15px; overflow: hidden;">
        <div style="width: <?= min(100, max(0, (float)$totalWeight)) ?>%; height: 100%; background: <?= $isExact100 ? '#10B981' : ((float)$totalWeight > 100 ? '#EF4444' : '#F59E0B') ?>; transition: width 0.4s ease;"></div>
    </div>
</div>

<!-- Tabel Kriteria SAW -->
<div class="card-panel">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div class="card-title" style="font-size: 16px; margin: 0;">
            <span>📋</span> Daftar Kriteria & Klasifikasi Atribut
        </div>
        <span style="font-size: 12.5px; color: #64748B;">
            Total: <strong><?= count($criteriaList) ?></strong> Kriteria Terdaftar
        </span>
    </div>

    <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
            <thead>
                <tr style="background-color: #0F2C59; color: #FFFFFF; font-weight: 600;">
                    <th style="padding: 12px 14px; width: 45px; text-align: center;">No</th>
                    <th style="padding: 12px 14px; width: 110px;">Kode</th>
                    <th style="padding: 12px 14px;">Nama Kriteria Keputusan</th>
                    <th style="padding: 12px 14px; text-align: right; width: 120px;">Bobot (%)</th>
                    <th style="padding: 12px 14px; text-align: center; width: 120px;">Tipe Atribut</th>
                    <th style="padding: 12px 14px; text-align: center; width: 110px;">Status</th>
                    <?php if ($canEdit): ?>
                        <th style="padding: 12px 14px; text-align: center; width: 180px;">Aksi</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($criteriaList)): ?>
                    <tr>
                        <td colspan="<?= $canEdit ? '7' : '6' ?>" style="padding: 40px 20px; text-align: center; color: #64748B;">
                            <div style="font-size: 32px; margin-bottom: 8px;">📭</div>
                            <strong>Belum Ada Kriteria SAW</strong>
                            <p style="margin: 4px 0 0 0; font-size: 13px;">Gunakan tombol Tambah Kriteria untuk membuat kriteria evaluasi rekanan.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($criteriaList as $c): ?>
                        <tr style="border-bottom: 1px solid var(--border-color); <?= empty($c['is_active']) ? 'background: #F8FAFC; opacity: 0.75;' : '' ?>">
                            <td style="padding: 12px 14px; text-align: center; font-weight: 600; color: #64748B;"><?= $no++ ?></td>
                            <td style="padding: 12px 14px;">
                                <span style="font-family: monospace; font-weight: 700; font-size: 13px; background: #EEF2F6; color: #1E293B; padding: 3px 8px; border-radius: 4px;">
                                    <?= htmlspecialchars($c['kode_kriteria'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 600; color: #0F172A;">
                                <?= htmlspecialchars($c['nama_kriteria'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: right; font-family: monospace; font-weight: 700; color: #0284C7; font-size: 14px;">
                                <?= number_format((float)$c['bobot'], 2, '.', '') ?>%
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <?php if ($c['tipe'] === 'Benefit'): ?>
                                    <span style="background: #DEF7EC; color: #03543F; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                        📈 Benefit
                                    </span>
                                <?php else: ?>
                                    <span style="background: #FEF3C7; color: #92400E; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                        📉 Cost
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <?php if (!empty($c['is_active'])): ?>
                                    <span style="background: #DEF7EC; color: #03543F; font-size: 12px; font-weight: 700; padding: 3px 8px; border-radius: 4px;">
                                        Aktif
                                    </span>
                                <?php else: ?>
                                    <span style="background: #F1F5F9; color: #64748B; font-size: 12px; font-weight: 700; padding: 3px 8px; border-radius: 4px;">
                                        Non-Aktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <?php if ($canEdit): ?>
                                <td style="padding: 12px 14px; text-align: center;">
                                    <div style="display: inline-flex; gap: 6px; align-items: center; justify-content: center;">
                                        <!-- Edit Button -->
                                        <button type="button" 
                                                onclick='openEditModal(<?= json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'
                                                title="Ubah Kriteria"
                                                style="background: #E0F2FE; color: #0369A1; border: 1px solid #BAE6FD; padding: 5px 9px; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                            ✏️ Edit
                                        </button>

                                        <!-- Toggle Status Form -->
                                        <form method="POST" action="/scm/saw-criteria/toggle/<?= (int)$c['id'] ?>" style="display: inline; margin: 0;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" 
                                                    title="<?= !empty($c['is_active']) ? 'Non-aktifkan Kriteria' : 'Aktifkan Kriteria' ?>"
                                                    style="background: #F1F5F9; color: #334155; border: 1px solid #CBD5E1; padding: 5px 9px; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                                <?= !empty($c['is_active']) ? '⏸️' : '▶️' ?>
                                            </button>
                                        </form>

                                        <!-- Delete Form -->
                                        <form method="POST" action="/scm/saw-criteria/delete/<?= (int)$c['id'] ?>" style="display: inline; margin: 0;" onsubmit="return confirm('Apakah Anda yakin ingin menghapus kriteria <?= htmlspecialchars($c['kode_kriteria'], ENT_QUOTES, 'UTF-8') ?>?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <button type="submit" 
                                                    title="Hapus Kriteria"
                                                    style="background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; padding: 5px 9px; border-radius: 5px; font-size: 12px; font-weight: 600; cursor: pointer;">
                                                🗑️
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Catatan Penjelasan Teori SAW -->
    <div style="margin-top: 20px; padding: 14px 18px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; font-size: 12.5px; color: #475569; line-height: 1.6;">
        <strong style="color: #0F172A;">📖 Keterangan Tipe Atribut SAW:</strong>
        <ul style="margin: 6px 0 0 18px; padding: 0;">
            <li><strong>Benefit (Keuntungan):</strong> Kriteria yang nilainya semakin tinggi semakin baik (contoh: Kualitas Logam, Ketepatan Waktu, Layanan Purna Jual). Normalisasi: \(R_{ij} = \frac{x_{ij}}{\max(x_j)}\).</li>
            <li><strong>Cost (Biaya):</strong> Kriteria yang nilainya semakin rendah semakin menguntungkan (contoh: Harga Penawaran, Tingkat Cacat / Defect Rate). Normalisasi: \(R_{ij} = \frac{\min(x_j)}{x_{ij}}\).</li>
        </ul>
    </div>
</div>

<!-- MODAL TAMBAH KRITERIA -->
<div id="addModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.55); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div style="background: #FFFFFF; border-radius: 10px; width: 100%; max-width: 520px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; margin: 20px;">
        <div style="padding: 18px 24px; background: #0F2C59; color: #FFFFFF; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">➕ Tambah Kriteria SAW Baru</h3>
            <button type="button" onclick="closeAddModal()" style="background: none; border: none; color: #FFFFFF; font-size: 20px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form method="POST" action="/scm/saw-criteria/store" style="padding: 24px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Kode Kriteria *</label>
                <input type="text" name="kode_kriteria" placeholder="Contoh: C6" required maxlength="10" 
                       style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; font-family: monospace;">
                <small style="color: #64748B; font-size: 11.5px;">Kode unik kriteria (misal: C1, C2, C3, dst.)</small>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Nama Kriteria Keputusan *</label>
                <input type="text" name="nama_kriteria" placeholder="Contoh: Fleksibilitas Minimum Order" required maxlength="100" 
                       style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Bobot (%) *</label>
                    <input type="number" name="bobot" step="0.01" min="0.01" max="100" placeholder="Contoh: 15.00" required 
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; font-family: monospace;">
                    <small style="color: #64748B; font-size: 11.5px;">Persentase bobot (0.01 - 100)</small>
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Tipe Atribut *</label>
                    <select name="tipe" required style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; background: #FFFFFF;">
                        <option value="Benefit">📈 Benefit (Makin besar makin baik)</option>
                        <option value="Cost">📉 Cost (Makin kecil makin baik)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13px; color: #334155; cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" checked style="width: 16px; height: 16px;">
                    Aktifkan kriteria ini langsung dalam komputasi SPK
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeAddModal()" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Batal</button>
                <button type="submit" style="background: var(--primary); color: #FFFFFF; border: none; padding: 9px 20px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer;">Simpan Kriteria</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL EDIT KRITERIA -->
<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.55); z-index: 9999; align-items: center; justify-content: center; backdrop-filter: blur(2px);">
    <div style="background: #FFFFFF; border-radius: 10px; width: 100%; max-width: 520px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); overflow: hidden; margin: 20px;">
        <div style="padding: 18px 24px; background: #0F2C59; color: #FFFFFF; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 700;">✏️ Ubah Kriteria SAW</h3>
            <button type="button" onclick="closeEditModal()" style="background: none; border: none; color: #FFFFFF; font-size: 20px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <form id="editForm" method="POST" action="" style="padding: 24px;">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Kode Kriteria *</label>
                <input type="text" id="edit_kode_kriteria" name="kode_kriteria" required maxlength="10" 
                       style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; font-family: monospace;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Nama Kriteria Keputusan *</label>
                <input type="text" id="edit_nama_kriteria" name="nama_kriteria" required maxlength="100" 
                       style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Bobot (%) *</label>
                    <input type="number" id="edit_bobot" name="bobot" step="0.01" min="0.01" max="100" required 
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; font-family: monospace;">
                </div>
                <div>
                    <label style="display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px;">Tipe Atribut *</label>
                    <select id="edit_tipe" name="tipe" required style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; background: #FFFFFF;">
                        <option value="Benefit">📈 Benefit</option>
                        <option value="Cost">📉 Cost</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 24px;">
                <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; font-size: 13px; color: #334155; cursor: pointer;">
                    <input type="checkbox" id="edit_is_active" name="is_active" value="1" style="width: 16px; height: 16px;">
                    Aktifkan kriteria ini dalam perhitungan SPK
                </label>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeEditModal()" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; padding: 9px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;">Batal</button>
                <button type="submit" style="background: var(--primary); color: #FFFFFF; border: none; padding: 9px 20px; border-radius: 6px; font-size: 13px; font-weight: 700; cursor: pointer;">Perbarui Kriteria</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'flex';
}
function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}
function openEditModal(c) {
    document.getElementById('editForm').action = '/scm/saw-criteria/update/' + c.id;
    document.getElementById('edit_kode_kriteria').value = c.kode_kriteria;
    document.getElementById('edit_nama_kriteria').value = c.nama_kriteria;
    document.getElementById('edit_bobot').value = parseFloat(c.bobot).toFixed(2);
    document.getElementById('edit_tipe').value = c.tipe;
    document.getElementById('edit_is_active').checked = parseInt(c.is_active) === 1;
    document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
window.onclick = function(event) {
    var addModal = document.getElementById('addModal');
    var editModal = document.getElementById('editModal');
    if (event.target === addModal) addModal.style.display = 'none';
    if (event.target === editModal) editModal.style.display = 'none';
};
</script>
