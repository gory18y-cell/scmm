<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($data['pageTitle'] ?? 'Login SCM Otomotif', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/scm/assets/css/style.css">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #0A192F 0%, #0F2C59 50%, #1E3A8A 100%);
            margin: 0;
            padding: 20px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .login-box {
            background: #FFFFFF;
            border-radius: 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 440px;
            overflow: hidden;
        }
        .login-header {
            background: #0F2C59;
            color: #FFFFFF;
            padding: 28px 24px;
            text-align: center;
        }
        .login-header h2 {
            margin: 0 0 6px 0;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        .login-header p {
            margin: 0;
            font-size: 13px;
            color: #CBD5E1;
        }
        .login-body {
            padding: 28px 24px;
        }
        .form-group {
            margin-bottom: 18px;
        }
        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #CBD5E1;
            border-radius: 6px;
            font-size: 14px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            border-color: #0284C7;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }
        .btn-login {
            width: 100%;
            padding: 11px;
            background: #0F2C59;
            color: #FFFFFF;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #1E3A8A;
        }
        .demo-accounts {
            margin-top: 24px;
            padding: 14px;
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            font-size: 12px;
            color: #475569;
        }
        .demo-accounts table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            font-size: 11.5px;
        }
        .demo-accounts th, .demo-accounts td {
            padding: 4px 6px;
            text-align: left;
        }
        .demo-accounts code {
            background: #EEF2F6;
            padding: 2px 5px;
            border-radius: 3px;
            font-weight: 700;
            color: #0F172A;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <div style="font-size: 32px; margin-bottom: 8px;">⚙️</div>
            <h2>SCM OTOMOTIF</h2>
            <p>Sistem Pengendalian Rantai Pasok & SPK SAW</p>
        </div>

        <div class="login-body">
            <?php if (!empty($data['flashSuccess'])): ?>
                <div style="padding: 10px 14px; background: #DEF7EC; border-left: 4px solid #10B981; color: #03543F; font-size: 13px; border-radius: 4px; margin-bottom: 18px;">
                    <?= htmlspecialchars($data['flashSuccess'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($data['flashError'])): ?>
                <div style="padding: 10px 14px; background: #FEE2E2; border-left: 4px solid #EF4444; color: #991B1B; font-size: 13px; border-radius: 4px; margin-bottom: 18px;">
                    <?= htmlspecialchars($data['flashError'], ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/scm/login">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($data['csrfToken'], ENT_QUOTES, 'UTF-8') ?>">

                <div class="form-group">
                    <label for="username">Username Akun</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="admin / purchasing / qc_auditor" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="btn-login">Masuk ke Sistem</button>
            </form>

            <div class="demo-accounts">
                <strong>🔑 Akun Uji Sistem (Seed Data):</strong>
                <table>
                    <thead>
                        <tr style="border-bottom: 1px solid #CBD5E1;">
                            <th>Username</th>
                            <th>Password</th>
                            <th>Role & Hak Akses</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>admin</code></td>
                            <td><code>password123</code></td>
                            <td>Admin (Akses Penuh)</td>
                        </tr>
                        <tr>
                            <td><code>qc_auditor</code></td>
                            <td><code>password123</code></td>
                            <td>QC Auditor (SPK SAW & Verifikasi)</td>
                        </tr>
                        <tr>
                            <td><code>purchasing</code></td>
                            <td><code>password123</code></td>
                            <td>Purchasing (Purchase Order)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>

