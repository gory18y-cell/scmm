<?php
/**
 * ==============================================================================
 * LAYOUT: GLOBAL HEADER & TOP NAVIGATION
 * ==============================================================================
 * @var string $pageTitle Judul halaman
 * @var string $pageSubTitle Deskripsi singkat atau breadcrumb
 */
$pageTitle = $pageTitle ?? 'SCM Manufaktur Otomotif';
$pageSubTitle = $pageSubTitle ?? 'Sistem Pengendalian Rantai Pasok Bahan Logam & SPK SAW';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | SCM Otomotif</title>
    <!-- Stylesheet Global -->
    <link rel="stylesheet" href="/scm/assets/css/style.css">
</head>
<body>
    <div class="app-wrapper">
        <!-- Komponen Sidebar Navigasi -->
        <?php require_once __DIR__ . '/sidebar.php'; ?>

        <!-- Area Wrapper Konten Utama -->
        <div class="main-wrapper">
            <!-- Header Atas (Top Bar) -->
            <header class="top-header">
                <div class="header-left">
                    <!-- Tombol Toggle Sidebar Mobile -->
                    <button class="sidebar-toggle-btn" id="sidebarToggle" aria-label="Buka Menu">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                    </button>

                    <div class="header-title-block">
                        <h1><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></h1>
                        <p><?= htmlspecialchars($pageSubTitle, ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>

                <div class="header-right">
                    <!-- Indikator Waktu Sistem -->
                    <div class="system-badge">
                        <span>📅 <?= date('d M Y') ?></span>
                    </div>

                    <!-- Informasi Pengguna Aktif & Tombol Autentikasi -->
                    <?php 
                    $sessionUser = $_SESSION['user'] ?? ($authUser ?? null);
                    if ($sessionUser && is_array($sessionUser) && !empty($sessionUser['id'])): 
                        $initials = strtoupper(substr($sessionUser['username'] ?? 'U', 0, 3));
                        $userName = $sessionUser['nama_lengkap'] ?? $sessionUser['username'];
                        $userRole = $sessionUser['role'] ?? 'Operator';
                    ?>
                        <div class="user-profile-pill" style="display: flex; align-items: center; gap: 10px;">
                            <div class="user-avatar" title="<?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="user-meta">
                                <div class="user-name"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="user-role"><?= htmlspecialchars($userRole, ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <a href="/scm/logout" class="btn-logout" title="Keluar dari sistem" 
                               style="background: #FEE2E2; color: #991B1B; border: 1px solid #FECACA; padding: 6px 12px; border-radius: 6px; font-size: 12.5px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; transition: background 0.2s;"
                               onmouseover="this.style.background='#FCA5A5'" onmouseout="this.style.background='#FEE2E2'">
                                <span>🚪</span> Logout
                            </a>
                        </div>
                    <?php else: ?>
                        <a href="/scm/login" 
                           style="background: var(--primary); color: #FFFFFF; padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: var(--shadow-sm); transition: background 0.2s;"
                           onmouseover="this.style.background='var(--secondary)'" onmouseout="this.style.background='var(--primary)'">
                            <span>🔑</span> Masuk (Login)
                        </a>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Area Konten Dinamis -->
            <main class="content-area">

