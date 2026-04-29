<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Portal Dev — LumiClinic AI Billing</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f4ff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(17,24,39,.12);
            padding: 40px 36px;
            width: 100%;
            max-width: 380px;
        }
        .logo {
            text-align: center;
            margin-bottom: 28px;
        }
        .logo-icon {
            font-size: 40px;
            display: block;
            margin-bottom: 8px;
        }
        .logo-title {
            font-size: 18px;
            font-weight: 800;
            color: #1f2937;
        }
        .logo-sub {
            font-size: 12px;
            color: #6b7280;
            margin-top: 2px;
        }
        .field { margin-bottom: 16px; }
        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 6px;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #d1d5db;
            border-radius: 8px;
            font-size: 14px;
            color: #1f2937;
            outline: none;
            transition: border-color .15s;
        }
        input[type="password"]:focus { border-color: #6366f1; }
        .btn {
            width: 100%;
            padding: 11px;
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s;
        }
        .btn:hover { background: #4f46e5; }
        .error {
            background: #fef2f2;
            border: 1px solid #fca5a5;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            color: #dc2626;
            margin-bottom: 16px;
        }
        .notice {
            font-size: 11px;
            color: #9ca3af;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <span class="logo-icon">🤖</span>
            <div class="logo-title">AI Billing Portal</div>
            <div class="logo-sub">LumiClinic — Acesso restrito ao desenvolvedor</div>
        </div>

        <?php if (isset($error) && $error !== null && $error !== ''): ?>
        <div class="error"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="/dev/ai-billing/login">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>" />
            <div class="field">
                <label for="password">Senha</label>
                <input type="password" id="password" name="password" autofocus placeholder="••••••••" />
            </div>
            <button class="btn" type="submit">Entrar</button>
        </form>

        <div class="notice">Este portal não aparece em nenhum menu do sistema.</div>
    </div>
</body>
</html>
