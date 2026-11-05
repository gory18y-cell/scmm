<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan | SCM Otomotif</title>
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
            box-shadow: 0 4px 16px rgba(15, 44, 89, 0.08);
            border: 1px solid var(--border-color);
            border-top: 5px solid var(--danger);
            max-width: 520px;
            width: 100%;
            padding: 40px 30px;
            text-align: center;
        }
        .error-code {
            font-size: 72px;
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
            margin-bottom: 25px;
        }
        .path-badge {
            display: inline-block;
            background: #FEE2E2;
            color: #991B1B;
            font-family: monospace;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 13px;
            word-break: break-all;
            margin-bottom: 25px;
        }
        .btn-home {
            display: inline-block;
            background-color: var(--primary);
            color: #FFFFFF;
            text-decoration: none;
            padding: 11px 24px;
            border-radius: 6px;
            font-size: 13.5px;
            font-weight: 600;
            transition: background-color 0.2s;
        }
        .btn-home:hover {
            background-color: var(--secondary);
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-code">404</div>
        <div class="error-title">Halaman Tidak Ditemukan</div>
        <div class="error-desc">
            Rute alamat yang Anda tuju tidak terdaftar di dalam sistem routing SCM Otomotif.
        </div>
        <?php if (!empty($requestedPath)): ?>
            <div class="path-badge"><?= htmlspecialchars($requestedPath, ENT_QUOTES, 'UTF-8') ?></div>
            <br>
        <?php endif; ?>
        <a href="/scm/" class="btn-home">← Kembali ke Halaman Utama</a>
    </div>
</body>
</html>

