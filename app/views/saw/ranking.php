<?php
/**
 * ==============================================================================
 * VIEW: HASIL PERANGKINGAN SPK METODE SAW - MODUL 3 (M. HADI PRATAMA)
 * ==============================================================================
 * Menampilkan hasil pemeringkatan alternatif vendor terbaik, rincian
 * matriks keputusan (X), normalisasi (R), dan preferensi (V).
 * ==============================================================================
 */

$isComplete = !empty($sawResult['is_complete']);
$rankings   = $sawResult['rankings'] ?? [];
$bestVendor = $sawResult['best_vendor'] ?? null;
$criteria   = $sawResult['criteria'] ?? [];
$rawMatrix  = $sawResult['raw_matrix'] ?? [];
$minMax     = $sawResult['min_max'] ?? [];
$normMatrix = $sawResult['norm_matrix'] ?? [];
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

<!-- Header & Toolbar -->
<div class="card-panel no-print" style="margin-bottom: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <div class="card-title" style="margin-bottom: 3px;">
                <span>🏆</span> <?= htmlspecialchars($pageTitle ?? 'Hasil Perangkingan Supplier (Metode SAW)', ENT_QUOTES, 'UTF-8') ?>
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Penanggung Jawab: <strong>M. Hadi Pratama</strong> | Rekomendasi Pemasok Komponen Logam Otomotif Berbasis SPK SAW
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- Filter Periode Form -->
            <form method="GET" action="/scm/saw-ranking" style="display: flex; align-items: center; gap: 8px; margin: 0;">
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
                    <?php endif; ?>
                </select>
            </form>

            <a href="/scm/saw-evaluation?periode=<?= urlencode($selectedPeriod) ?>" class="btn-secondary" style="padding: 7px 14px; text-decoration: none; border: 1px solid var(--border-color); border-radius: 6px; font-weight: 600; font-size: 13px; color: var(--text-color); background: #FFFFFF; display: inline-flex; align-items: center; gap: 6px;">
                <span>📝</span> Edit Matriks Nilai
            </a>

            <?php if ($isComplete): ?>
                <button type="button" onclick="window.print()" style="background: #0284C7; color: #FFFFFF; border: none; padding: 7px 14px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm);">
                    <span>🖨️</span> Cetak Laporan SPK
                </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!$isComplete): ?>
    <!-- Kondisi Belum Lengkap / Error Perhitungan -->
    <div class="card-panel" style="text-align: center; padding: 50px 20px; border-left: 5px solid #F59E0B;">
        <div style="font-size: 44px; margin-bottom: 12px;">⚠️</div>
        <h3 style="margin: 0 0 10px 0; color: #0F172A; font-size: 18px;">Perhitungan SPK SAW Belum Dapat Diproses</h3>
        <p style="color: #64748B; font-size: 14px; max-width: 620px; margin: 0 auto 20px auto; line-height: 1.6;">
            <?= htmlspecialchars($sawResult['message'] ?? 'Data kriteria atau nilai evaluasi vendor belum memenuhi prasyarat SAW.', ENT_QUOTES, 'UTF-8') ?>
        </p>
        <div style="display: flex; justify-content: center; gap: 12px; flex-wrap: wrap;">
            <a href="/scm/saw-criteria" style="background: var(--primary); color: #FFFFFF; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span>⚖️</span> Periksa Kriteria & Bobot
            </a>
            <a href="/scm/saw-evaluation?periode=<?= urlencode($selectedPeriod) ?>" style="background: #059669; color: #FFFFFF; padding: 9px 18px; border-radius: 6px; font-weight: 700; font-size: 13.5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span>📝</span> Lengkapi Nilai Evaluasi
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- =========================================================================
         1. HIGHLIGHT CARD: SUPPLIER TERBAIK (RANK 1)
         ========================================================================= -->
    <?php if ($bestVendor): ?>
        <div class="card-panel" style="background: linear-gradient(135deg, #0F2C59 0%, #1E3A8A 100%); color: #FFFFFF; margin-bottom: 24px; box-shadow: 0 10px 20px rgba(15, 44, 89, 0.15); border: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px;">
                <div style="flex: 1; min-width: 280px;">
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(254, 240, 138, 0.2); border: 1px solid rgba(254, 240, 138, 0.4); padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 700; color: #FEF08A; margin-bottom: 12px;">
                        <span>🥇</span> REKOMENDASI PEMASOK UTAMA — PERIODE <?= htmlspecialchars($selectedPeriod, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <h2 style="margin: 0 0 6px 0; font-size: 24px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.5px;">
                        <?= htmlspecialchars($bestVendor['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
                    </h2>
                    <div style="display: flex; gap: 12px; align-items: center; font-size: 13px; opacity: 0.9; flex-wrap: wrap;">
                        <span>Kode: <strong style="font-family: monospace;"><?= htmlspecialchars($bestVendor['kode_vendor'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <span>•</span>
                        <span>Spesialisasi: <strong><?= htmlspecialchars($bestVendor['spesialisasi_logam'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                        <span>•</span>
                        <span>Akreditasi: <strong><?= htmlspecialchars($bestVendor['jenis_akreditasi'], ENT_QUOTES, 'UTF-8') ?></strong></span>
                    </div>
                    <p style="margin: 14px 0 0 0; font-size: 13px; color: #E2E8F0; line-height: 1.5; max-width: 650px;">
                        Berdasarkan kalkulasi metode Simple Additive Weighting (SAW) dengan seluruh pembobotan kriteria terverifikasi, rekanan ini memperoleh skor preferensi tertinggi (\(V_1 = <?= number_format($bestVendor['preferensi'], 4) ?>\)). Direkomendasikan sebagai <strong>Primary Source Supplier</strong> untuk pengadaan material otomotif utama.
                    </p>
                </div>

                <div style="text-align: center; background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(4px); padding: 20px 28px; border-radius: 12px; border: 1px solid rgba(255, 255, 255, 0.2); min-width: 180px;">
                    <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; opacity: 0.85; margin-bottom: 4px;">Nilai Preferensi (V)</div>
                    <div style="font-size: 38px; font-weight: 800; font-family: monospace; color: #FDE047; line-height: 1;">
                        <?= number_format($bestVendor['preferensi'], 4) ?>
                    </div>
                    <div style="font-size: 13px; font-weight: 700; color: #FFFFFF; margin-top: 6px;">
                        Skor: <?= number_format($bestVendor['percent'], 2) ?>%
                    </div>
                    <span style="display: inline-block; margin-top: 8px; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 4px; background: #DEF7EC; color: #03543F;">
                        Peringkat Ke-1
                    </span>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- =========================================================================
         2. TABEL PERANGKINGAN KESELURUHAN ALTERNATIF SUPPLIER
         ========================================================================= -->
    <div class="card-panel" style="margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <div>
                <div class="card-title" style="font-size: 16px; margin: 0;">
                    <span>📊</span> Urutan Peringkat Alternatif Supplier
                </div>
                <div style="font-size: 12.5px; color: #64748B; margin-top: 2px;">
                    Daftar diurutkan berdasarkan nilai preferensi tertinggi (\(V_i\)) hasil sintesis bobot kriteria.
                </div>
            </div>
            <div style="font-size: 12px; color: #64748B;">
                Total Alternatif Dinilai: <strong><?= count($rankings) ?></strong> Rekanan Terverifikasi
            </div>
        </div>

        <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13.5px;">
                <thead>
                    <tr style="background-color: #0F2C59; color: #FFFFFF; font-weight: 600;">
                        <th style="padding: 12px 14px; width: 60px; text-align: center;">Rank</th>
                        <th style="padding: 12px 14px;">Kode & Nama Rekanan</th>
                        <th style="padding: 12px 14px;">Spesialisasi Logam</th>
                        <th style="padding: 12px 14px;">Akreditasi Mutu</th>
                        <th style="padding: 12px 14px; text-align: center; width: 140px;">Nilai Preferensi (\(V_i\))</th>
                        <th style="padding: 12px 14px; text-align: center; width: 150px;">Skor Persentase</th>
                        <th style="padding: 12px 14px; text-align: center; width: 200px;">Status Rekomendasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rankings as $r): ?>
                        <?php 
                        $rank = (int)$r['rank'];
                        $isTop1 = ($rank === 1);
                        $isTop2 = ($rank === 2);
                        $rowBg = $isTop1 ? 'background-color: #F0FDF4;' : '';
                        ?>
                        <tr style="border-bottom: 1px solid var(--border-color); <?= $rowBg ?>">
                            <td style="padding: 12px 14px; text-align: center;">
                                <?php if ($isTop1): ?>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 9999px; background: #FDE047; color: #854D0E; font-weight: 800; font-size: 13px; box-shadow: var(--shadow-sm);">
                                        1
                                    </span>
                                <?php elseif ($isTop2): ?>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 9999px; background: #E2E8F0; color: #334155; font-weight: 800; font-size: 13px;">
                                        2
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; border-radius: 9999px; background: #F1F5F9; color: #64748B; font-weight: 700; font-size: 13px;">
                                        <?= $rank ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td style="padding: 12px 14px;">
                                <div style="font-weight: 700; color: #0F172A; font-size: 13.5px;">
                                    <?= htmlspecialchars($r['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div style="font-family: monospace; font-size: 11.5px; color: #64748B; margin-top: 2px;">
                                    <?= htmlspecialchars($r['kode_vendor'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>

                            <td style="padding: 12px 14px; color: #334155;">
                                <?= htmlspecialchars($r['spesialisasi_logam'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td style="padding: 12px 14px;">
                                <span style="font-size: 11.5px; font-weight: 700; color: #0284C7; background: #E0F2FE; padding: 2px 7px; border-radius: 4px;">
                                    <?= htmlspecialchars($r['jenis_akreditasi'] ?? 'ISO', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>

                            <td style="padding: 12px 14px; text-align: center; font-family: monospace; font-weight: 800; font-size: 15px; color: <?= $isTop1 ? '#047857' : '#0F2C59' ?>;">
                                <?= number_format($r['preferensi'], 4) ?>
                            </td>

                            <td style="padding: 12px 14px; text-align: center;">
                                <div style="font-weight: 700; font-size: 13px; color: #0F172A; margin-bottom: 4px;">
                                    <?= number_format($r['percent'], 2) ?>%
                                </div>
                                <div style="width: 100%; height: 6px; background: #E2E8F0; border-radius: 3px; overflow: hidden;">
                                    <div style="width: <?= min(100, max(0, $r['percent'])) ?>%; height: 100%; background: <?= $isTop1 ? '#10B981' : '#0284C7' ?>;"></div>
                                </div>
                            </td>

                            <td style="padding: 12px 14px; text-align: center;">
                                <?php if ($isTop1): ?>
                                    <span style="background: #DEF7EC; color: #03543F; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                        ⭐ Supplier Utama (Quota 60%)
                                    </span>
                                <?php elseif ($isTop2): ?>
                                    <span style="background: #E1EFFE; color: #1E429F; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; display: inline-flex; align-items: center; gap: 4px;">
                                        🔄 Supplier Cadangan (Quota 40%)
                                    </span>
                                <?php else: ?>
                                    <span style="background: #F1F5F9; color: #475569; font-size: 11.5px; font-weight: 600; padding: 4px 10px; border-radius: 9999px;">
                                        Pemasok Pendukung
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- =========================================================================
         3. RINCIAN TRANSPARAN KOMPUTASI LANGKAH DEMI LANGKAH (SAW BREAKDOWN)
         ========================================================================= -->
    <div class="card-panel" style="margin-bottom: 24px;">
        <div class="card-title" style="font-size: 16px; margin-bottom: 6px;">
            <span>🔬</span> Rincian Langkah Komputasi Ilmiah Metode SAW
        </div>
        <div class="card-subtitle" style="margin-bottom: 20px;">
            Transparansi penuh pengolahan data dari Matriks Keputusan (\(X\)), Matriks Normalisasi (\(R\)), hingga Perkalian Vektor Bobot (\(W\)).
        </div>

        <!-- 3A. Matriks Keputusan Awal (X) -->
        <div style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap;">
                <h4 style="margin: 0; font-size: 14px; color: #0F172A; font-weight: 700;">
                    1. Matriks Keputusan Awal (\(X_{ij}\)) & Penentuan Nilai Ekstrem
                </h4>
                <span style="font-size: 11.5px; color: #64748B;">
                    Benefit: \(\max(X_j)\) | Cost: \(\min(X_j)\)
                </span>
            </div>

            <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: center;">
                    <thead>
                        <tr style="background-color: #F8FAFC; color: #334155; border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 10px 12px; text-align: left;">Alternatif Rekanan</th>
                            <?php foreach ($criteria as $c): ?>
                                <th style="padding: 10px 12px;">
                                    <div style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars($c['kode_kriteria'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div style="font-size: 10.5px; color: <?= ($c['tipe'] === 'Benefit') ? '#059669' : '#D97706' ?>; font-weight: 600;">
                                        (<?= htmlspecialchars($c['tipe'], ENT_QUOTES, 'UTF-8') ?>)
                                    </div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rawMatrix as $vid => $row): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 10px 12px; text-align: left; font-weight: 600; color: #0F172A;">
                                    <?= htmlspecialchars($row['vendor']['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
                                    <span style="font-family: monospace; font-size: 11px; color: #64748B;">(<?= htmlspecialchars($row['vendor']['kode_vendor'], ENT_QUOTES, 'UTF-8') ?>)</span>
                                </td>
                                <?php foreach ($criteria as $c): ?>
                                    <td style="padding: 10px 12px; font-family: monospace;">
                                        <?= number_format((float)($row['scores'][$c['id']] ?? 0), 2) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Baris Nilai Ekstrem (Max / Min) -->
                        <tr style="background: #F1F5F9; font-weight: 700; border-top: 2px solid #CBD5E1;">
                            <td style="padding: 10px 12px; text-align: left; color: #0F2C59;">
                                Nilai Rujukan Ekstrem (\(\max/\min\))
                            </td>
                            <?php foreach ($criteria as $c): ?>
                                <?php 
                                $cid = (int)$c['id'];
                                $isBen = ($c['tipe'] === 'Benefit');
                                $extVal = $isBen ? $minMax[$cid]['max'] : $minMax[$cid]['min'];
                                ?>
                                <td style="padding: 10px 12px; font-family: monospace; color: <?= $isBen ? '#059669' : '#D97706' ?>;">
                                    <?= number_format((float)$extVal, 2) ?>
                                    <div style="font-size: 10px; font-weight: 600;">(<?= $isBen ? 'Max' : 'Min' ?>)</div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3B. Matriks Normalisasi (R) -->
        <div style="margin-bottom: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap;">
                <h4 style="margin: 0; font-size: 14px; color: #0F172A; font-weight: 700;">
                    2. Matriks Normalisasi Ternormalisasi (\(R_{ij}\))
                </h4>
                <span style="font-size: 11.5px; color: #64748B;">
                    Rentang Nilai: 0.0000 – 1.0000
                </span>
            </div>

            <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 8px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; text-align: center;">
                    <thead>
                        <tr style="background-color: #F8FAFC; color: #334155; border-bottom: 2px solid var(--border-color);">
                            <th style="padding: 10px 12px; text-align: left;">Alternatif Rekanan</th>
                            <?php foreach ($criteria as $c): ?>
                                <th style="padding: 10px 12px;">
                                    <div style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars($c['kode_kriteria'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div style="font-size: 10.5px; color: #64748B;">Bobot: <?= number_format((float)$c['bobot'], 1) ?>%</div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($normMatrix as $vid => $row): ?>
                            <tr style="border-bottom: 1px solid var(--border-color);">
                                <td style="padding: 10px 12px; text-align: left; font-weight: 600; color: #0F172A;">
                                    <?= htmlspecialchars($row['vendor']['nama_vendor'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <?php foreach ($criteria as $c): ?>
                                    <td style="padding: 10px 12px; font-family: monospace; font-weight: 600; color: #0F2C59;">
                                        <?= number_format((float)($row['norm_scores'][$c['id']] ?? 0), 4) ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 3C. Vektor Bobot (W) & Rumus Preferensi (V) -->
        <div>
            <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #0F172A; font-weight: 700;">
                3. Vektor Bobot Kriteria (\(W_j\)) & Rumus Preferensi Akhir (\(V_i\))
            </h4>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 16px;">
                <?php foreach ($criteria as $c): ?>
                    <div style="padding: 10px 12px; background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 6px;">
                        <div style="display: flex; justify-content: space-between; font-size: 12px; font-weight: 700; margin-bottom: 4px;">
                            <span style="font-family: monospace;"><?= htmlspecialchars($c['kode_kriteria'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span style="color: #0284C7; font-family: monospace;"><?= number_format((float)$c['bobot'] / 100, 2) ?></span>
                        </div>
                        <div style="font-size: 11px; color: #64748B; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            <?= htmlspecialchars($c['nama_kriteria'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="padding: 12px 16px; background: #EEF2F6; border-radius: 6px; font-size: 12.5px; color: #334155; line-height: 1.6;">
                <strong>Formulasi SAW:</strong>
                <div>
                    \(V_i = \sum_{j=1}^n \left( w_j \times R_{ij} \right)\) di mana:
                </div>
                <div style="font-size: 11.5px; color: #64748B; margin-top: 4px;">
                    \(w_j\) = Nilai bobot kriteria yang dinormalisasi (\(\sum w_j = 1.00\)), dan \(R_{ij}\) = Matriks nilai kinerja alternatif ternormalisasi.
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Styling Khusus Cetak Laporan PDF/Kertas -->
<style>
@media print {
    .no-print, .sidebar, .top-header, .sidebar-footer {
        display: none !important;
    }
    body, .main-content {
        margin: 0 !important;
        padding: 0 !important;
        background: #FFFFFF !important;
    }
    .card-panel {
        box-shadow: none !important;
        border: 1px solid #CCCCCC !important;
        page-break-inside: avoid;
    }
}
</style>
