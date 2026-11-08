<?php
/**
 * ==============================================================================
 * VIEW: FORMULIR PENERBITAN PURCHASE ORDER BARU - PENGADAAN BAHAN BAKU
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

<div class="card-panel" style="max-width: 1050px; margin: 0 auto;">
    <!-- Header Formulir -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 22px;">
        <div>
            <div class="card-title" style="margin-bottom: 2px;">
                <span>➕</span> Formulir Penerbitan Purchase Order Baru
            </div>
            <div class="card-subtitle" style="margin-bottom: 0;">
                Input Transaksi Multi-Item Pengadaan Logam, Harga Resmi & Jadwal Pengiriman
            </div>
        </div>
        <a href="/scm/purchase-order" style="color: var(--text-muted); font-size: 13.5px; font-weight: 600; text-decoration: none;">
            ← Kembali ke Daftar PO
        </a>
    </div>

    <!-- Banner Ketetapan Status & Otorisasi Petugas -->
    <div class="alert-box alert-info" style="margin-bottom: 22px;">
        <span style="font-size: 20px;">ℹ️</span>
        <div>
            <strong>Status Awal Dokumen: Terkunci Otomatis pada <span style="background: #E2E8F0; color: #334155; padding: 2px 8px; border-radius: 4px; font-weight: 700;">📝 Draft</span></strong>
            <div style="margin-top: 4px; font-size: 12.5px; color: #0C4A6E;">
                Petugas Pembuat: <strong><?= htmlspecialchars($authUser['nama_lengkap'] ?? 'Petugas SCM', ENT_QUOTES, 'UTF-8') ?></strong> 
                (Role: <span style="font-weight: 700;"><?= htmlspecialchars($authUser['role'] ?? 'Purchasing', ENT_QUOTES, 'UTF-8') ?></span>).
                Nomor PO otomatis dibuat berurutan dengan format <code>PO-YYYYMM-XXXX</code> saat transaksi disimpan.
            </div>
        </div>
    </div>

    <!-- Formulir Pembuatan PO -->
    <form method="POST" action="/scm/purchase-order/store" id="formPoCreate">
        <!-- Token Proteksi CSRF (Wajib) -->
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

        <!-- Bagian 1: Header Dokumen Pengadaan & Rekanan Vendor -->
        <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin-bottom: 22px;">
            <div style="font-weight: 700; color: #0F2C59; margin-bottom: 15px; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                <span>🏢</span> Bagian 1: Informasi Header & Rekanan Terverifikasi
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px;">
                <!-- Pemilihan Rekanan Vendor (HANYA TERVERIFIKASI) -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Rekanan Vendor Logam <span style="color: #DC2626;">*</span>
                    </label>
                    <select name="vendor_id" id="vendorSelect" required
                            style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; background: #FFFFFF;">
                        <option value="">-- Pilih Vendor Terverifikasi --</option>
                        <?php foreach ($verifiedVendors as $v): ?>
                            <option value="<?= (int)$v['id'] ?>" <?= ((int)($old['vendor_id'] ?? 0) === (int)$v['id']) ? 'selected' : '' ?>>
                                [<?= htmlspecialchars($v['kode_vendor'], ENT_QUOTES, 'UTF-8') ?>] <?= htmlspecialchars($v['nama_vendor'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($v['spesialisasi_logam'], ENT_QUOTES, 'UTF-8') ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color: #64748B; font-size: 11.5px;">Hanya vendor berstatus Terverifikasi yang dapat dipilih.</small>
                </div>

                <!-- Tanggal Dokumen PO -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Tanggal Purchase Order <span style="color: #DC2626;">*</span>
                    </label>
                    <input type="date" name="tanggal_po" id="tanggalPo" 
                           value="<?= htmlspecialchars($old['tanggal_po'] ?? date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>" required
                           style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; background: #FFFFFF;">
                    <small style="color: #64748B; font-size: 11.5px;">Tanggal acuan penerbitan dan perhitungan lead time kedatangan.</small>
                </div>

                <!-- Petugas Pembuat (Display Sahabat Sesi) -->
                <div>
                    <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                        Petugas Pengadaan (Session)
                    </label>
                    <input type="text" readonly disabled
                           value="<?= htmlspecialchars(($authUser['nama_lengkap'] ?? 'Petugas') . ' (' . ($authUser['role'] ?? 'Purchasing') . ')', ENT_QUOTES, 'UTF-8') ?>"
                           style="width: 100%; padding: 9px 12px; border: 1px solid #CBD5E1; border-radius: 6px; font-size: 13.5px; background: #E2E8F0; color: #475569; font-weight: 600;">
                    <small style="color: #64748B; font-size: 11.5px;">Otentikasi terikat otomatis ke sesi aktif (anti-spoofing user_id).</small>
                </div>
            </div>

            <!-- Catatan Pengiriman Logistik -->
            <div style="margin-top: 14px;">
                <label style="display: block; font-weight: 700; font-size: 13px; margin-bottom: 6px; color: #334155;">
                    Catatan Pengiriman / Instruksi Docking Logistik (Opsional)
                </label>
                <textarea name="catatan_pengiriman" rows="2" placeholder="Contoh: Pengiriman via pintu gerbang barat docking gudang pabrik 2..."
                          style="width: 100%; padding: 9px 12px; border: 1px solid var(--border-color); border-radius: 6px; font-size: 13.5px; font-family: inherit; resize: vertical;"><?= htmlspecialchars($old['catatan_pengiriman'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>

        <!-- Bagian 2: Rincian Item Bahan Baku Logam (Daftar Dinamis) -->
        <div style="background: #FFFFFF; border: 1px solid var(--border-color); border-radius: 8px; padding: 18px; margin-bottom: 22px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <div style="font-weight: 700; color: #0F2C59; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                        <span>📦</span> Bagian 2: Rincian Bahan Baku Logam yang Dipesan
                    </div>
                    <div style="color: #64748B; font-size: 12.5px; margin-top: 2px;">
                        Harga satuan resmi diambil langsung dari basis data penawaran rekanan terpilih.
                    </div>
                </div>

                <button type="button" id="btnAddRow" 
                        style="background: #0F2C59; color: #FFFFFF; border: none; padding: 8px 14px; border-radius: 6px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <span>➕</span> Tambah Material
                </button>
            </div>

            <!-- Tabel Dinamis Item Material -->
            <div style="overflow-x: auto; border: 1px solid var(--border-color); border-radius: 6px;">
                <table style="width: 100%; border-collapse: collapse; font-size: 13px;" id="itemsTable">
                    <thead>
                        <tr style="background: #F1F5F9; color: #334155; font-weight: 700; border-bottom: 2px solid #CBD5E1;">
                            <th style="padding: 10px 12px; width: 40px; text-align: center;">No</th>
                            <th style="padding: 10px 12px; min-width: 280px;">Material Bahan Baku</th>
                            <th style="padding: 10px 12px; width: 90px; text-align: center;">Satuan</th>
                            <th style="padding: 10px 12px; width: 140px; text-align: right;">Harga Satuan</th>
                            <th style="padding: 10px 12px; width: 90px; text-align: center;">Lead Time</th>
                            <th style="padding: 10px 12px; width: 130px; text-align: right;">Kuantitas</th>
                            <th style="padding: 10px 12px; width: 160px; text-align: right;">Subtotal (Rp)</th>
                            <th style="padding: 10px 12px; width: 50px; text-align: center;">Hapus</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <!-- Baris material akan dimuat oleh JavaScript secara dinamis -->
                    </tbody>
                </table>
            </div>

            <!-- Pesan bila vendor belum dipilih -->
            <div id="noVendorNotice" style="padding: 30px; text-align: center; color: #64748B; background: #F8FAFC; border: 1px dashed #CBD5E1; border-radius: 6px; margin-top: 10px;">
                <span style="font-size: 26px;">👈</span>
                <div style="margin-top: 6px; font-weight: 600;">Pilih Rekanan Vendor di atas terlebih dahulu untuk memuat katalog bahan baku logam.</div>
            </div>

            <!-- Ringkasan Logistik & Biaya Pengadaan (Live Calculation) -->
            <div style="background: #F8FAFC; border: 1px solid var(--border-color); border-radius: 6px; padding: 16px; margin-top: 18px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
                    <div>
                        <div style="color: #64748B; font-size: 11.5px; text-transform: uppercase; font-weight: 700;">Total Item Pesanan</div>
                        <div id="summaryItemCount" style="font-size: 18px; font-weight: 800; color: #0F2C59;">0 item</div>
                    </div>
                    <div>
                        <div style="color: #64748B; font-size: 11.5px; text-transform: uppercase; font-weight: 700;">Lead Time Logistik</div>
                        <div id="summaryLeadTime" style="font-size: 18px; font-weight: 800; color: #0F2C59;">0 hari</div>
                    </div>
                    <div>
                        <div style="color: #64748B; font-size: 11.5px; text-transform: uppercase; font-weight: 700;">Estimasi Kedatangan (ETA)</div>
                        <div id="summaryEta" style="font-size: 18px; font-weight: 800; color: #0F2C59;">-</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="color: #64748B; font-size: 11.5px; text-transform: uppercase; font-weight: 700;">Grand Total Nilai PO</div>
                        <div id="summaryTotalNilai" style="font-size: 20px; font-weight: 800; color: #0F2C59;">Rp 0,00</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tombol Aksi Form -->
        <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 25px;">
            <a href="/scm/purchase-order" 
               style="background: #E2E8F0; color: #334155; padding: 10px 20px; border-radius: 6px; font-weight: 700; font-size: 13.5px; text-decoration: none;">
                Batal
            </a>
            <button type="submit" id="btnSubmitPo"
                    style="background: var(--primary); color: #FFFFFF; border: none; padding: 10px 24px; border-radius: 6px; font-weight: 700; font-size: 13.5px; cursor: pointer; box-shadow: var(--shadow-sm); display: inline-flex; align-items: center; gap: 8px;">
                <span>💾</span> Terbitkan Purchase Order
            </button>
        </div>
    </form>
</div>

<!-- Logika Dinamis Client-Side (Vanilla JS) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const vendorSelect       = document.getElementById('vendorSelect');
    const tanggalPoInput     = document.getElementById('tanggalPo');
    const itemsTableBody     = document.getElementById('itemsTableBody');
    const noVendorNotice     = document.getElementById('noVendorNotice');
    const btnAddRow          = document.getElementById('btnAddRow');
    const summaryItemCount   = document.getElementById('summaryItemCount');
    const summaryLeadTime    = document.getElementById('summaryLeadTime');
    const summaryEta         = document.getElementById('summaryEta');
    const summaryTotalNilai  = document.getElementById('summaryTotalNilai');
    const formPoCreate       = document.getElementById('formPoCreate');

    let vendorMaterials = [];
    let rowCounter = 0;

    // Format nominal uang ke format Rupiah standar
    function formatRupiah(amount) {
        return 'Rp ' + Number(amount).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Format tanggal ke DD/MM/YYYY
    function formatDateIndo(dateStr) {
        if (!dateStr) return '-';
        const parts = dateStr.split('-');
        if (parts.length !== 3) return dateStr;
        return `${parts[2]}/${parts[1]}/${parts[0]}`;
    }

    // Mengambil katalog bahan baku vendor via Fetch API
    function fetchVendorMaterials(vendorId) {
        if (!vendorId) {
            vendorMaterials = [];
            itemsTableBody.innerHTML = '';
            noVendorNotice.style.display = 'block';
            updateSummary();
            return;
        }

        fetch('/scm/purchase-order/materials/' + vendorId)
            .then(response => response.json())
            .then(result => {
                if (result.success && Array.isArray(result.data)) {
                    vendorMaterials = result.data;
                    itemsTableBody.innerHTML = '';

                    if (vendorMaterials.length === 0) {
                        noVendorNotice.innerHTML = '⚠️ Rekanan vendor ini belum memiliki katalog material yang berstatus <strong>Tersedia</strong>.';
                        noVendorNotice.style.display = 'block';
                    } else {
                        noVendorNotice.style.display = 'none';
                        // Tambahkan baris pertama secara otomatis
                        addMaterialRow();
                    }
                    updateSummary();
                } else {
                    alert('Gagal mengambil katalog material rekanan vendor.');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Terjadi kesalahan jaringan saat memuat material vendor.');
            });
    }

    // Menambahkan baris material baru ke tabel
    function addMaterialRow() {
        if (vendorMaterials.length === 0) {
            alert('Pilih vendor dengan material tersedia terlebih dahulu.');
            return;
        }

        rowCounter++;
        const rowIndex = rowCounter;

        const tr = document.createElement('tr');
        tr.id = 'row-' + rowIndex;
        tr.style.borderBottom = '1px solid var(--border-color)';

        // Susun opsi select material
        let optionsHtml = '<option value="">-- Pilih Bahan Baku Logam --</option>';
        vendorMaterials.forEach(m => {
            optionsHtml += `<option value="${m.vendor_material_id}" 
                                   data-satuan="${m.satuan}" 
                                   data-harga="${m.harga_penawaran}" 
                                   data-lead="${m.standar_lead_time}" 
                                   data-spesifikasi="${m.spesifikasi_teknis || ''}">
                                [${m.kode_material}] ${m.nama_material}
                            </option>`;
        });

        tr.innerHTML = `
            <td style="padding: 10px 12px; text-align: center; color: #64748B;" class="row-number">1</td>
            <td style="padding: 10px 12px;">
                <select name="items[${rowIndex}][vendor_material_id]" class="material-select" required
                        style="width: 100%; padding: 7px 10px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 13px;">
                    ${optionsHtml}
                </select>
                <div class="material-spec" style="font-size: 11px; color: #64748B; margin-top: 3px;"></div>
            </td>
            <td style="padding: 10px 12px; text-align: center; color: #475569;" class="col-satuan">-</td>
            <td style="padding: 10px 12px; text-align: right; font-weight: 600; color: #0F2C59;" class="col-harga">Rp 0,00</td>
            <td style="padding: 10px 12px; text-align: center; font-weight: 600; color: #475569;" class="col-lead">-</td>
            <td style="padding: 10px 12px; text-align: right;">
                <input type="number" step="0.01" min="0.01" name="items[${rowIndex}][jumlah_pesanan]" value="1.00" class="qty-input" required
                       style="width: 100%; text-align: right; padding: 7px 10px; border: 1px solid var(--border-color); border-radius: 5px; font-size: 13px;">
            </td>
            <td style="padding: 10px 12px; text-align: right; font-weight: 700; color: #0F2C59;" class="col-subtotal">Rp 0,00</td>
            <td style="padding: 10px 12px; text-align: center;">
                <button type="button" class="btn-remove-row" title="Hapus baris"
                        style="background: #FEE2E2; color: #DC2626; border: 1px solid #FECACA; border-radius: 5px; width: 28px; height: 28px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; font-size: 12px;">
                    ✕
                </button>
            </td>
        `;

        itemsTableBody.appendChild(tr);

        // Bind event listeners pada baris baru
        const selectEl = tr.querySelector('.material-select');
        const qtyEl    = tr.querySelector('.qty-input');
        const btnDel   = tr.querySelector('.btn-remove-row');

        selectEl.addEventListener('change', function() {
            onMaterialChanged(tr);
        });

        qtyEl.addEventListener('input', function() {
            recalcRowSubtotal(tr);
            updateSummary();
        });

        btnDel.addEventListener('click', function() {
            if (itemsTableBody.querySelectorAll('tr').length <= 1) {
                alert('Dokumen Purchase Order minimal harus memiliki 1 item pesanan.');
                return;
            }
            tr.remove();
            renumberRows();
            updateSummary();
        });

        renumberRows();
        updateSummary();
    }

    // Handler saat pilihan material berubah
    function onMaterialChanged(tr) {
        const select = tr.querySelector('.material-select');
        const opt    = select.options[select.selectedIndex];

        const satuanEl = tr.querySelector('.col-satuan');
        const hargaEl  = tr.querySelector('.col-harga');
        const leadEl   = tr.querySelector('.col-lead');
        const specEl   = tr.querySelector('.material-spec');

        if (opt && opt.value) {
            const satuan = opt.getAttribute('data-satuan') || '-';
            const harga  = parseFloat(opt.getAttribute('data-harga') || 0);
            const lead   = parseInt(opt.getAttribute('data-lead') || 0);
            const spec   = opt.getAttribute('data-spesifikasi') || '';

            satuanEl.textContent = satuan;
            hargaEl.textContent  = formatRupiah(harga);
            hargaEl.setAttribute('data-raw-harga', harga);
            leadEl.textContent   = lead + ' hari';
            leadEl.setAttribute('data-raw-lead', lead);
            specEl.textContent   = spec ? 'Spesifikasi: ' + spec : '';
        } else {
            satuanEl.textContent = '-';
            hargaEl.textContent  = 'Rp 0,00';
            hargaEl.removeAttribute('data-raw-harga');
            leadEl.textContent   = '-';
            leadEl.removeAttribute('data-raw-lead');
            specEl.textContent   = '';
        }

        recalcRowSubtotal(tr);
        updateSummary();
    }

    // Kalkulasi subtotal baris
    function recalcRowSubtotal(tr) {
        const hargaEl    = tr.querySelector('.col-harga');
        const qtyEl      = tr.querySelector('.qty-input');
        const subtotalEl = tr.querySelector('.col-subtotal');

        const harga = parseFloat(hargaEl.getAttribute('data-raw-harga') || 0);
        const qty   = parseFloat(qtyEl.value || 0);

        const subtotal = (harga * qty);
        subtotalEl.textContent = formatRupiah(subtotal);
        subtotalEl.setAttribute('data-raw-subtotal', subtotal);
    }

    // Penomoran ulang baris
    function renumberRows() {
        const rows = itemsTableBody.querySelectorAll('tr');
        rows.forEach((r, idx) => {
            const numEl = r.querySelector('.row-number');
            if (numEl) numEl.textContent = (idx + 1);
        });
    }

    // Perbarui panel ringkasan total biaya & logistik
    function updateSummary() {
        const rows = itemsTableBody.querySelectorAll('tr');
        let grandTotal  = 0;
        let maxLeadTime = 0;
        let validRows   = 0;

        rows.forEach(r => {
            const selectEl   = r.querySelector('.material-select');
            const subtotalEl = r.querySelector('.col-subtotal');
            const leadEl     = r.querySelector('.col-lead');

            if (selectEl && selectEl.value) {
                validRows++;
                const sub = parseFloat(subtotalEl.getAttribute('data-raw-subtotal') || 0);
                const lead = parseInt(leadEl.getAttribute('data-raw-lead') || 0);

                grandTotal += sub;
                if (lead > maxLeadTime) {
                    maxLeadTime = lead;
                }
            }
        });

        summaryItemCount.textContent  = validRows + ' material';
        summaryLeadTime.textContent   = maxLeadTime + ' hari';
        summaryTotalNilai.textContent = formatRupiah(grandTotal);

        // Hitung estimasi tanggal tiba (ETA)
        const tglPoVal = tanggalPoInput.value;
        if (tglPoVal && maxLeadTime >= 0) {
            const d = new Date(tglPoVal);
            d.setDate(d.getDate() + maxLeadTime);
            const yyyy = d.getFullYear();
            const mm   = String(d.getMonth() + 1).padStart(2, '0');
            const dd   = String(d.getDate()).padStart(2, '0');
            summaryEta.textContent = formatDateIndo(`${yyyy}-${mm}-${dd}`);
        } else {
            summaryEta.textContent = '-';
        }
    }

    // Event listener vendor dropdown
    vendorSelect.addEventListener('change', function() {
        fetchVendorMaterials(this.value);
    });

    // Event listener tanggal PO
    tanggalPoInput.addEventListener('change', function() {
        updateSummary();
    });

    // Event listener tombol tambah baris
    btnAddRow.addEventListener('click', function() {
        addMaterialRow();
    });

    // Validasi pencegahan duplikasi saat submit form
    formPoCreate.addEventListener('submit', function(e) {
        const selects = itemsTableBody.querySelectorAll('.material-select');
        const chosen  = [];

        if (selects.length === 0) {
            e.preventDefault();
            alert('Minimal harus ada 1 item bahan baku dalam pesanan.');
            return;
        }

        for (let s of selects) {
            if (!s.value) {
                e.preventDefault();
                alert('Terdapat baris material yang belum dipilih.');
                s.focus();
                return;
            }
            if (chosen.includes(s.value)) {
                e.preventDefault();
                alert('Terdapat material yang dipilih lebih dari sekali. Gabungkan kuantitas dalam satu baris.');
                s.focus();
                return;
            }
            chosen.push(s.value);
        }
    });

    // Jika ada vendor yang sudah terpilih sebelumnya (old input)
    if (vendorSelect.value) {
        fetchVendorMaterials(vendorSelect.value);
    }
});
</script>

