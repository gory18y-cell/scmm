<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak | SCM Otomotif</title>
    <style>
        :root {
            --primary: #0F2C59;
            --secondary: #1E56A0;
            --danger: #DC2626;
            --bg-canvas: #F8FAFC;
            --card-surface: #FFFFFF;
            --text-dark: #1E293B;
            --text-muted: #64748B;
            --border-color: #E2E8F0;
        }
        body {
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, Roboto, sans-serif;
            background-color: var(--bg-canvas);
            color: var(--text-dark);
            margin: 0;
            padding: 30px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 85vh;
        }
        .error-card {
            background: var(--card-surface);
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(15, 44, 89, 0.1);
            border: 1px solid var(--border-color);
            border-top: 5px solid var(--danger);
            max-width: 520px;
            width: 100%;
            padding: 40px 30px;
            text-align: center;
        }
        .error-icon {
            font-size: 56px;
            margin-bottom: 12px;
        }
        .error-code {
            font-size: 64px;
            font-weight: 800;
            color: var(--danger);
            line-height: 1;
            margin: 0 0 10px 0;
        }
        .error-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            margin: 0 0 12px 0;
        }
        .error-desc {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .role-badge-box {
            background: #FEF2F2;
            border: 1px solid #FECACA;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 24px;
            font-size: 13px;
            text-align: left;
            color: #991B1B;
        }
        .role-badge-box strong {
            color: #7F1D1D;
        }
        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--primary);
            color: #FFFFFF;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s;
        }
        .btn-action:hover {
            background: var(--secondary);
        }
        .btn-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #F1F5F9;
            color: #475569;
            border: 1px solid #CBD5E1;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            margin-left: 8px;
        }
        .btn-secondary:hover {
            background: #E2E8F0;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">🚫</div>
        <div class="error-code">403</div>
        <h1 class="error-title">Akses Ditolak (Forbidden)</h1>
        <p class="error-desc">
            Maaf, Anda tidak memiliki hak akses (wewenang) untuk menjalankan operasi atau membuka halaman ini. Pembatasan ini diterapkan berdasarkan kebijakan Role-Based Access Control (RBAC) industri SCM.
        </p>

        <div class="role-badge-box">
            <div>• Role Akun Anda: <strong><?= htmlspecialchars($currentRole ?? 'Tamu / Belum Login', ENT_QUOTES, 'UTF-8') ?></strong></div>
            <?php if (!empty($requiredRoles)): ?>
                <div style="margin-top: 4px;">• Wewenang yang Dibutuhkan: <strong><?= htmlspecialchars(implode(' atau ', $requiredRoles), ENT_QUOTES, 'UTF-8') ?></strong></div>
            <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: center; flex-wrap: wrap; gap: 10px;">
            <a href="/scm/" class="btn-action">
                <span>🏠</span> Kembali ke Dashboard
            </a>
            <a href="javascript:history.back()" class="btn-secondary">
                <span>↩️</span> Halaman Sebelumnya
            </a>
        </div>
    </div>
</body>
</html>

