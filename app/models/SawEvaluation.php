<?php
/**
 * ==============================================================================
 * MODEL: SAW_EVALUATION (MATRIKS NILAI ALTERNATIF & KALKULASI ALGORITMA SAW)
 * ==============================================================================
 * Mengelola penilaian supplier dan eksekusi komputasi Simple Additive Weighting
 * Target DB : db_scm_otomotif (Tabel `saw_evaluations`, `vendors`, `saw_criteria`)
 * Modul 3   : M. Hadi Pratama
 * ==============================================================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/SawCriteria.php';

class SawEvaluation {
    private $db;
    private $criteriaModel;

    public function __construct(?PDO $pdo = null) {
        $this->db            = $pdo ?? Database::connect();
        $this->criteriaModel = new SawCriteria($this->db);
    }

    /**
     * Mengambil daftar periode evaluasi unik yang tersimpan di sistem
     *
     * @return array
     */
    public function getPeriods() {
        $sql = "SELECT DISTINCT `periode_evaluasi` 
                FROM `saw_evaluations` 
                ORDER BY `periode_evaluasi` DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Mengambil matriks evaluasi nilai untuk periode tertentu
     * Format output: [vendor_id => [criteria_id => nilai, ...], ...]
     *
     * @param string $periode
     * @return array
     */
    public function getMatrixByPeriod($periode) {
        $sql = "SELECT 
                    e.`vendor_id`,
                    e.`criteria_id`,
                    e.`nilai`,
                    v.`kode_vendor`,
                    v.`nama_vendor`,
                    v.`status_verifikasi`,
                    c.`kode_kriteria`,
                    c.`nama_kriteria`,
                    c.`bobot`,
                    c.`tipe`
                FROM `saw_evaluations` e
                INNER JOIN `vendors` v ON e.`vendor_id` = v.`id`
                INNER JOIN `saw_criteria` c ON e.`criteria_id` = c.`id`
                WHERE e.`periode_evaluasi` = :periode
                ORDER BY v.`nama_vendor` ASC, c.`kode_kriteria` ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':periode' => trim($periode)]);
        $rows = $stmt->fetchAll();

        $matrix = [];
        foreach ($rows as $row) {
            $vid = (int)$row['vendor_id'];
            $cid = (int)$row['criteria_id'];

            if (!isset($matrix[$vid])) {
                $matrix[$vid] = [
                    'vendor_id'         => $vid,
                    'kode_vendor'       => $row['kode_vendor'],
                    'nama_vendor'       => $row['nama_vendor'],
                    'status_verifikasi' => $row['status_verifikasi'],
                    'scores'            => []
                ];
            }
            $matrix[$vid]['scores'][$cid] = (float)$row['nilai'];
        }

        return $matrix;
    }

    /**
     * Menyimpan satu nilai evaluasi (Insert / Update)
     *
     * @param int $vendorId
     * @param int $criteriaId
     * @param float $nilai
     * @param string $periode
     * @return bool
     * @throws InvalidArgumentException
     */
    public function saveScore($vendorId, $criteriaId, $nilai, $periode) {
        $vendorId   = (int)$vendorId;
        $criteriaId = (int)$criteriaId;
        $nilai      = (float)$nilai;
        $periode    = trim($periode);

        if ($vendorId <= 0 || $criteriaId <= 0 || empty($periode)) {
            throw new InvalidArgumentException("Data evaluasi tidak lengkap.");
        }

        if ($nilai <= 0 || $nilai > 100.0) {
            throw new InvalidArgumentException("Nilai evaluasi harus berada dalam rentang lebih besar dari 0 sampai 100.00 (mencegah pembagian dengan nol).");
        }

        $sql = "INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`) 
                VALUES (:vid, :cid, :nilai, :periode)
                ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`)";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':vid'     => $vendorId,
            ':cid'     => $criteriaId,
            ':nilai'   => number_format($nilai, 2, '.', ''),
            ':periode' => $periode
        ]);
    }

    /**
     * Menyimpan batch nilai matriks evaluasi untuk satu periode dalam satu transaksi
     *
     * @param string $periode
     * @param array $scoresArray [vendor_id => [criteria_id => nilai, ...], ...]
     * @return bool
     * @throws Exception
     */
    public function saveBatchMatrix($periode, array $scoresArray) {
        $periode = trim($periode);
        if (empty($periode)) {
            throw new InvalidArgumentException("Periode evaluasi wajib diisi.");
        }

        $this->db->beginTransaction();

        try {
            $sql = "INSERT INTO `saw_evaluations` (`vendor_id`, `criteria_id`, `nilai`, `periode_evaluasi`) 
                    VALUES (:vid, :cid, :nilai, :periode)
                    ON DUPLICATE KEY UPDATE `nilai` = VALUES(`nilai`)";
            $stmt = $this->db->prepare($sql);

            foreach ($scoresArray as $vendorId => $criteriaScores) {
                $vid = (int)$vendorId;
                if ($vid <= 0 || !is_array($criteriaScores)) continue;

                foreach ($criteriaScores as $criteriaId => $nilaiRaw) {
                    $cid = (int)$criteriaId;
                    if ($cid <= 0 || $nilaiRaw === '' || $nilaiRaw === null) continue;

                    $nilai = (float)$nilaiRaw;
                    if ($nilai <= 0 || $nilai > 100.0) {
                        throw new InvalidArgumentException("Nilai evaluasi untuk vendor ID {$vid} dan kriteria ID {$cid} harus antara 0.01 - 100.00 (diterima: {$nilaiRaw}).");
                    }

                    $stmt->execute([
                        ':vid'     => $vid,
                        ':cid'     => $cid,
                        ':nilai'   => number_format($nilai, 2, '.', ''),
                        ':periode' => $periode
                    ]);
                }
            }

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Menghapus seluruh data evaluasi pada periode tertentu
     *
     * @param string $periode
     * @return bool
     */
    public function deletePeriod($periode) {
        $sql = "DELETE FROM `saw_evaluations` WHERE `periode_evaluasi` = :periode";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':periode' => trim($periode)]);
    }

    /**
     * KALKULASI UTAMA METODE SIMPLE ADDITIVE WEIGHTING (SAW)
     *
     * ATURAN BISNIS & TAHAPAN PERHITUNGAN:
     * 1. Hanya menyertakan vendor yang berstatus 'Terverifikasi'.
     *    Vendor 'Audit Pending' dan 'Blacklist' otomatis disaring/diabaikan dari pemeringkatan.
     * 2. Kriteria yang digunakan adalah kriteria yang aktif (is_active = 1).
     * 3. Matrix Completeness Guard: Memastikan seluruh sel alternatif x kriteria telah terisi.
     * 4. Normalisasi Matriks Keputusan (R):
     *    - Kriteria Benefit : R_ij = X_ij / Max_i(X_ij)
     *    - Kriteria Cost    : R_ij = Min_i(X_ij) / X_ij
     * 5. Perhitungan Nilai Preferensi (V):
     *    - V_i = SUM( w_j * R_ij ), di mana w_j = bobot_j / 100
     * 6. Perangkingan (Ranking):
     *    - Mengurutkan V_i secara descending (nilai terbesar = Peringkat 1 / Rekomendasi Terbaik).
     *
     * @param string $periode
     * @return array [
     *    'is_complete'      => bool,
     *    'message'          => string,
     *    'criteria'         => array,
     *    'total_weight'     => float,
     *    'vendors'          => array,
     *    'raw_matrix'       => array,
     *    'min_max'          => array,
     *    'norm_matrix'      => array,
     *    'rankings'         => array,
     *    'best_vendor'      => array|null
     * ]
     */
    public function calculateSaw($periode) {
        $periode = trim($periode);

        // 1. Ambil kriteria aktif
        $criteriaList = $this->criteriaModel->getAll(true);
        $totalWeight  = $this->criteriaModel->getTotalWeight();

        // 2. Ambil vendor yang berstatus HANYA 'Terverifikasi'
        $sqlV = "SELECT `id`, `kode_vendor`, `nama_vendor`, `spesialisasi_logam`, `jenis_akreditasi`, `status_verifikasi` 
                 FROM `vendors` 
                 WHERE `status_verifikasi` = 'Terverifikasi' 
                 ORDER BY `nama_vendor` ASC";
        $stmtV = $this->db->query($sqlV);
        $verifiedVendors = $stmtV->fetchAll();

        // Struktur respon dasar
        $result = [
            'is_complete'   => false,
            'message'       => '',
            'criteria'      => $criteriaList,
            'total_weight'  => $totalWeight,
            'vendors'       => $verifiedVendors,
            'raw_matrix'    => [],
            'min_max'       => [],
            'norm_matrix'   => [],
            'rankings'      => [],
            'best_vendor'   => null
        ];

        // Validasi kriteria
        if (empty($criteriaList)) {
            $result['message'] = "Belum ada kriteria evaluasi yang aktif. Silakan tambahkan atau aktifkan kriteria pada menu Kriteria SAW.";
            return $result;
        }

        // 2. Validasi total bobot kriteria harus tepat 100.00%
        // Ranking TIDAK BOLEH dihitung jika total bobot kurang atau lebih dari 100.00%
        if (abs((float)$totalWeight - 100.0) >= 0.001) {
            $result['is_complete'] = false;
            $result['rankings']    = [];
            $result['best_vendor'] = null;
            $result['message']     = "Perhitungan ranking SAW dibatalkan: Total bobot kriteria aktif saat ini adalah " . number_format((float)$totalWeight, 2) . "%. Sesuai aturan SPK SAW, akumulasi bobot wajib tepat 100.00%. Silakan sesuaikan bobot di menu Kriteria SAW.";
            return $result;
        }

        // Validasi keberadaan vendor terverifikasi
        if (empty($verifiedVendors)) {
            $result['message'] = "Tidak ditemukan rekanan vendor dengan status 'Terverifikasi' untuk dievaluasi.";
            return $result;
        }

        // 3. Ambil nilai evaluasi periode dari database
        $matrixData = $this->getMatrixByPeriod($periode);

        // 4. Matrix Completeness Guard: Susun matriks mentah X_ij dan periksa kelengkapan sel
        $rawMatrix    = [];
        $missingCells = [];

        foreach ($verifiedVendors as $v) {
            $vid = (int)$v['id'];
            $rawMatrix[$vid] = [
                'vendor' => $v,
                'scores' => []
            ];

            foreach ($criteriaList as $c) {
                $cid = (int)$c['id'];
                if (isset($matrixData[$vid]['scores'][$cid])) {
                    $rawMatrix[$vid]['scores'][$cid] = $matrixData[$vid]['scores'][$cid];
                } else {
                    $missingCells[] = "{$v['nama_vendor']} ({$c['nama_kriteria']})";
                }
            }
        }

        if (!empty($missingCells)) {
            $countMissing = count($missingCells);
            $preview = implode(', ', array_slice($missingCells, 0, 3));
            if ($countMissing > 3) $preview .= ", dan " . ($countMissing - 3) . " sel lainnya";
            $result['raw_matrix'] = $rawMatrix;
            $result['message'] = "Matriks evaluasi belum lengkap ({$countMissing} sel belum dinilai: {$preview}). Lengkapi nilai di menu Evaluasi Supplier.";
            return $result;
        }

        // 5. Cari Nilai Ekstrem (Max dan Min) untuk setiap kriteria
        // Benefit: butuh Max(X_ij)
        // Cost   : butuh Min(X_ij)
        $minMax = [];
        foreach ($criteriaList as $c) {
            $cid = (int)$c['id'];
            $colValues = [];
            foreach ($rawMatrix as $row) {
                $colValues[] = $row['scores'][$cid];
            }

            $maxVal = max($colValues);
            $minVal = min($colValues);

            $minMax[$cid] = [
                'max'  => $maxVal,
                'min'  => $minVal,
                'tipe' => $c['tipe']
            ];
        }

        // 6. Normalisasi Matriks (R_ij)
        $normMatrix = [];
        foreach ($rawMatrix as $vid => $row) {
            $normMatrix[$vid] = [
                'vendor'      => $row['vendor'],
                'norm_scores' => []
            ];

            foreach ($criteriaList as $c) {
                $cid   = (int)$c['id'];
                $x_ij  = $row['scores'][$cid];
                $tipe  = $c['tipe'];
                $r_ij  = 0.0;

                if ($tipe === 'Benefit') {
                    $max = (float)($minMax[$cid]['max'] ?? 0.0);
                    // Cegah pembagian dengan nol jika nilai maksimum kolom <= 0
                    $r_ij = ($max > 0.0) ? ($x_ij / $max) : 0.0;
                } else {
                    // Cost
                    $min = (float)($minMax[$cid]['min'] ?? 0.0);
                    // Cegah pembagian dengan nol jika nilai alternatif x_ij <= 0
                    if ($x_ij > 0.0) {
                        $r_ij = $min / $x_ij;
                    } else {
                        $r_ij = 0.0;
                    }
                }

                // Batasi nilai normalisasi dalam interval logis [0, 1]
                $r_ij = max(0.0, min(1.0, (float)$r_ij));
                $normMatrix[$vid]['norm_scores'][$cid] = round($r_ij, 4);
            }
        }

        // 7. Perhitungan Nilai Preferensi (V_i) = SUM( w_j * R_ij )
        $rankings = [];
        foreach ($normMatrix as $vid => $row) {
            $v_i = 0.0;

            foreach ($criteriaList as $c) {
                $cid  = (int)$c['id'];
                $w_j  = (float)$c['bobot'] / 100.0; // Bobot ternormalisasi
                $r_ij = $row['norm_scores'][$cid];

                $v_i += ($w_j * $r_ij);
            }

            $rankings[] = [
                'vendor_id'         => $vid,
                'kode_vendor'       => $row['vendor']['kode_vendor'],
                'nama_vendor'       => $row['vendor']['nama_vendor'],
                'spesialisasi_logam'=> $row['vendor']['spesialisasi_logam'],
                'jenis_akreditasi'  => $row['vendor']['jenis_akreditasi'],
                'preferensi'        => round($v_i, 4),
                'percent'           => round($v_i * 100, 2),
                'scores_raw'        => $rawMatrix[$vid]['scores'],
                'scores_norm'       => $row['norm_scores']
            ];
        }

        // 8. Urutkan berdasarkan Nilai Preferensi Terbesar (Descending)
        usort($rankings, function ($a, $b) {
            if ($a['preferensi'] == $b['preferensi']) return 0;
            return ($a['preferensi'] > $b['preferensi']) ? -1 : 1;
        });

        // Tetapkan peringkat (Rank 1, 2, 3...)
        foreach ($rankings as $idx => &$r) {
            $r['rank'] = $idx + 1;
        }
        unset($r);

        $result['is_complete'] = true;
        $result['raw_matrix']  = $rawMatrix;
        $result['min_max']     = $minMax;
        $result['norm_matrix'] = $normMatrix;
        $result['rankings']    = $rankings;
        $result['best_vendor'] = $rankings[0] ?? null;
        $result['message']     = "Perhitungan SAW periode '{$periode}' berhasil diproses.";

        return $result;
    }
}

