<?php
// ============================================================
//  login.php — Accesso area admin
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Se già loggato → dashboard
if (isLoggedIn()) redirect(BASE_URL . '/admin/dashboard.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    if (empty($password)) {
        $error = 'Inserisci la password.';
    } elseif (adminLogin($password)) {
        redirect(BASE_URL . '/admin/dashboard.php');
    } else {
        $error = 'Password errata. Riprova.';
        // Piccolo ritardo per prevenire brute-force
        sleep(1);
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accesso — ABB Calibration Manager</title>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@600;700;800&display=swap">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        :root { --red: #FF000F; --dark: #1A1A1A; }
        body {
            font-family: 'Barlow', sans-serif;
            background: var(--dark);
            margin: 0; min-height: 100vh;
            display: flex; flex-direction: column;
        }

        /* Background pattern industriale */
        body::before {
            content: '';
            position: fixed; inset: 0;
            background-image:
                linear-gradient(rgba(255,0,15,.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,0,15,.03) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
        }

        .login-wrap {
            flex: 1; display: flex;
            align-items: center; justify-content: center;
            padding: 20px;
        }
        .login-box {
            background: #fff;
            width: 100%; max-width: 400px;
            border-top: 5px solid var(--red);
        }
        .login-header {
            background: var(--dark);
            padding: 28px 32px 22px;
            border-bottom: 1px solid #2a2a2a;
        }
        .logo-box {
            background: var(--red); color: #fff;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 800; font-size: 2rem;
            letter-spacing: -1px; padding: 3px 12px 2px;
            display: inline-block; margin-bottom: 14px;
        }
        .login-title {
            color: #fff; font-size: 1rem;
            font-weight: 700; margin: 0;
            text-transform: uppercase; letter-spacing: .5px;
        }
        .login-sub { color: #666; font-size: .82rem; margin-top: 3px; }
        .login-body { padding: 28px 32px 32px; }
        .form-label {
            display: block; font-size: .78rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
            color: #555; margin-bottom: 7px;
        }
        .pw-wrap { position: relative; }
        .pw-input {
            width: 100%; padding: 11px 42px 11px 14px;
            border: 1.5px solid #ddd; font-family: 'Barlow', sans-serif;
            font-size: 1rem; background: #fafafa;
            transition: border-color .15s;
        }
        .pw-input:focus { outline: none; border-color: var(--red); background: #fff; }
        .pw-toggle {
            position: absolute; right: 12px; top: 50%;
            transform: translateY(-50%);
            background: none; border: none;
            cursor: pointer; color: #aaa; font-size: .9rem;
            padding: 4px;
        }
        .pw-toggle:hover { color: var(--dark); }
        .btn-login {
            width: 100%; padding: 13px;
            background: var(--red); color: #fff;
            border: none; font-family: 'Barlow', sans-serif;
            font-size: 1rem; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
            cursor: pointer; margin-top: 22px;
            transition: background .15s;
        }
        .btn-login:hover { background: #cc000c; }
        .error-msg {
            background: #ffebee; border-left: 4px solid var(--red);
            color: #a00; padding: 10px 14px;
            font-size: .88rem; font-weight: 600;
            margin-bottom: 18px;
        }
        .login-footer {
            text-align: center; color: #888;
            font-size: .72rem; padding: 12px 32px 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-box">
        <div class="login-header">
            <div class="logo-box">ABB</div>
            <div class="login-title">Calibration Manager</div>
            <div class="login-sub">Area amministrativa — ABB S.p.A. Dalmine</div>
        </div>
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error-msg">&#9888; <?= e($error) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <label class="form-label" for="password">Password</label>
                <div class="pw-wrap">
                    <input type="password" id="password" name="password"
                           class="pw-input" placeholder="Inserisci password"
                           autofocus autocomplete="current-password">
                    <button type="button" class="pw-toggle" onclick="togglePw()" id="pw-toggle-btn"
                            title="Mostra/nascondi password">
                        <span id="pw-icon">&#128065;</span>
                    </button>
                </div>
                <button type="submit" class="btn-login">Accedi &rarr;</button>
            </form>
        </div>
        <div class="login-footer">
            Accesso riservato al personale autorizzato ABB
        </div>
    </div>
</div>

<script>
function togglePw() {
    const inp = document.getElementById('password');
    const ico = document.getElementById('pw-icon');
    if (inp.type === 'password') {
        inp.type = 'text';
        ico.textContent = '🔒';
    } else {
        inp.type = 'password';
        ico.textContent = '👁';
    }
}
</script>
</body>
</html>
