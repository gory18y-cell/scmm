<?php
/**
 * ==============================================================================
 * LAYOUT: SIDEBAR NAVIGASI SISTEM SCM OTOMOTIF
 * ==============================================================================
 * @var string $activeMenu Penanda menu aktif ('dashboard', 'vendor', dll.)
 */
$activeMenu = $activeMenu ?? 'dashboard';
$currentRole = $_SESSION['user']['role'] ?? ($authUser['role'] ?? null);
?>
<aside class="sidebar" id="sidebar">
    <!-- Header Brand Logo -->
    <div class="sidebar-header">
        <div class="brand-icon">⚙</div>
        <div class="brand-text">
            <h2>SCM OTOMOTIF</h2>
            <span><?= htmlspecialchars($currentRole ? "Role: {$currentRole}" : 'Manufaktur Komponen', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <!-- Navigasi Menu Utama -->
    <nav class="sidebar-nav">
        <div class="nav-section-title">Menu Utama</div>
        <ul class="nav-list">
            <!-- 1. Dashboard -->
            <li class="nav-item">
                <a href="/scm/" class="nav-link <?= ($activeMenu === 'dashboard') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                    <span>Dashboard</span>
                </a>
            </li>

            <!-- 2. Master Vendor (Modul 1: Gori) -->
            <li class="nav-item">
                <a href="/scm/vendor" class="nav-link <?= ($activeMenu === 'vendor') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    <span>Master Vendor</span>
                    <span class="nav-tag"><?= ($currentRole === 'QC_Auditor') ? 'Audit ISO' : (($currentRole === 'Purchasing') ? 'Katalog' : 'Modul 1') ?></span>
                </a>
            </li>

            <!-- 3. Bahan Baku (Katalog Material) -->
            <li class="nav-item">
                <a href="/scm/material" class="nav-link <?= ($activeMenu === 'material') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                    <span>Bahan Baku</span>
                </a>
            </li>

            <!-- 4. Purchase Order (Modul 2: Latifah) -->
            <?php if ($currentRole !== 'QC_Auditor'): ?>
                <li class="nav-item">
                    <a href="/scm/purchase-order" class="nav-link <?= ($activeMenu === 'purchase-order') ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>Purchase Order</span>
                        <span class="nav-tag"><?= ($currentRole === 'Purchasing') ? 'Pengadaan' : 'Modul 2' ?></span>
                    </a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a href="/scm/purchase-order" class="nav-link <?= ($activeMenu === 'purchase-order') ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                        <span>Purchase Order</span>
                        <span class="nav-tag" style="background: #F1F5F9; color: #64748B;">Lihat</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>

        <div class="nav-section-title">SPK Metode SAW</div>
        <ul class="nav-list">
            <!-- 5. Kriteria SAW (Modul 3: Hadi) -->
            <li class="nav-item">
                <a href="/scm/saw-criteria" class="nav-link <?= ($activeMenu === 'saw-criteria') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24"><line x1="4" y1="21" x2="4" y2="14"></line><line x1="4" y1="10" x2="4" y2="3"></line><line x1="12" y1="21" x2="12" y2="12"></line><line x1="12" y1="8" x2="12" y2="3"></line><line x1="20" y1="21" x2="20" y2="16"></line><line x1="20" y1="12" x2="20" y2="3"></line><line x1="1" y1="14" x2="7" y2="14"></line><line x1="9" y1="8" x2="15" y2="8"></line><line x1="17" y1="16" x2="23" y2="16"></line></svg>
                    <span>Kriteria SAW</span>
                    <span class="nav-tag"><?= ($currentRole === 'QC_Auditor') ? 'Audit Bobot' : (($currentRole === 'Purchasing') ? 'Lihat' : 'Modul 3') ?></span>
                </a>
            </li>

            <!-- 6. Evaluasi Supplier (Modul 3: Hadi) -->
            <li class="nav-item">
                <a href="/scm/saw-evaluation" class="nav-link <?= ($activeMenu === 'saw-evaluation') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><line x1="3" y1="9" x2="21" y2="9"></line><line x1="9" y1="21" x2="9" y2="9"></line></svg>
                    <span>Evaluasi Supplier</span>
                    <span class="nav-tag"><?= ($currentRole === 'QC_Auditor') ? 'Input Mutu' : (($currentRole === 'Purchasing') ? 'Lihat' : 'Modul 3') ?></span>
                </a>
            </li>

            <!-- 7. Hasil Perangkingan (Modul 3: Hadi) -->
            <li class="nav-item">
                <a href="/scm/saw-ranking" class="nav-link <?= ($activeMenu === 'saw-ranking') ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                    <span>Hasil Perangkingan</span>
                    <span class="nav-tag" style="background: #DEF7EC; color: #03543F;">Prioritas</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Footer Sidebar -->
    <div class="sidebar-footer">
        <div>
            <span class="status-dot"></span>
            <span>Server Online</span>
        </div>
        <span>v1.0-MVC</span>
    </div>
</aside>

