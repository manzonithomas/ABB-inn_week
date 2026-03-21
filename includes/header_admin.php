<?php
// ============================================================
//  includes/header_admin.php
//  Variabili attese:  $page_title  (stringa)
//                     $active_nav  ('dashboard'|'macchinari'|'tarature'|'impostazioni')
// ============================================================
$page_title = $page_title ?? 'Admin';
$active_nav = $active_nav ?? '';

// Recupera tarature in scadenza per il badge
$badge_count = 0;
try {
    $badge_count = (int) db()->query(
        "SELECT COUNT(*) FROM v_tarature_in_scadenza"
    )->fetchColumn();
} catch (Throwable) {
}
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> — ABB Calibration Manager</title>
    <link rel="icon" type="image/x-icon" href="https://images.seeklogo.com/logo-png/0/1/abb-logo-png_seeklogo-1844.png">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ---- Reset & base ---- */
        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        body {
            font-family: 'Barlow', sans-serif;
            background: #F0F0F0;
            margin: 0;
            color: #1A1A1A;
        }

        /* ---- ABB tokens ---- */
        :root {
            --red: #FF000F;
            --red-dark: #CC000C;
            --dark: #1A1A1A;
            --mid: #3A3A3A;
            --light: #F0F0F0;
            --white: #FFFFFF;
            --border: #DCDCDC;
            --sidebar-w: 230px;
        }

        /* ---- Sidebar ---- */
        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: var(--sidebar-w);
            background: var(--dark);
            z-index: 200;
            display: flex;
            flex-direction: column;
            transition: transform .28s cubic-bezier(.4, 0, .2, 1);
            overflow-y: auto;
        }

        .sb-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 22px 20px 18px;
            border-bottom: 1px solid #2f2f2f;
        }

        .sb-logo-box {
            background: var(--red);
            color: #fff;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 800;
            font-size: 1.6rem;
            letter-spacing: -1px;
            padding: 3px 10px 2px;
            line-height: 1.2;
        }

        .sb-logo-sub {
            color: #888;
            font-size: .72rem;
            letter-spacing: .5px;
            line-height: 1.3;
        }

        .sb-section {
            color: #555;
            font-size: .68rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            padding: 18px 20px 6px;
        }

        .sb-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 11px 20px;
            color: #aaa;
            text-decoration: none;
            font-size: .9rem;
            font-weight: 500;
            transition: background .15s, color .15s;
            position: relative;
        }

        .sb-link:hover {
            background: #2a2a2a;
            color: #fff;
        }

        .sb-link.active {
            background: var(--red);
            color: #fff;
        }

        .sb-link .fa {
            width: 18px;
            font-size: .9rem;
        }

        .sb-badge {
            margin-left: auto;
            background: var(--red);
            color: #fff;
            font-size: .65rem;
            font-weight: 700;
            border-radius: 10px;
            padding: 1px 7px;
            min-width: 20px;
            text-align: center;
        }

        .sb-link.active .sb-badge {
            background: rgba(255, 255, 255, .3);
        }

        .sb-spacer {
            flex: 1;
        }

        .sb-logout {
            border-top: 1px solid #2f2f2f;
            padding: 4px 0 8px;
        }

        /* ---- Topbar ---- */
        #topbar {
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-w);
            height: 56px;
            background: var(--white);
            border-bottom: 3px solid var(--red);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            z-index: 100;
            transition: left .28s cubic-bezier(.4, 0, .2, 1);
        }

        #topbar .tb-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        #topbar h1 {
            font-size: 1rem;
            font-weight: 700;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        #hamburger {
            display: none;
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
            color: var(--dark);
            font-size: 1.1rem;
        }

        /* ---- Main content ---- */
        #main {
            margin-left: var(--sidebar-w);
            margin-top: 56px;
            padding: 24px;
            min-height: calc(100vh - 56px);
            transition: margin-left .28s cubic-bezier(.4, 0, .2, 1);
        }

        /* ---- Cards / panels ---- */
        .page-card {
            background: var(--white);
            border-radius: 2px;
            padding: 24px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
        }

        .stat-card {
            background: var(--white);
            border-radius: 2px;
            padding: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
            border-left: 4px solid var(--red);
        }

        .stat-card .stat-val {
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
        }

        .stat-card .stat-lbl {
            font-size: .78rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #888;
            margin-top: 4px;
        }

        .stat-card.ok {
            border-left-color: #28a745;
        }

        .stat-card.warn {
            border-left-color: #f0a500;
        }

        .stat-card.err {
            border-left-color: #FF000F;
        }

        /* ---- Table ---- */
        .abb-table {
            width: 100%;
            border-collapse: collapse;
        }

        .abb-table th {
            background: var(--dark);
            color: #fff;
            padding: 10px 14px;
            text-align: left;
            font-size: .8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .abb-table td {
            padding: 11px 14px;
            border-bottom: 1px solid var(--border);
            font-size: .9rem;
        }

        .abb-table tr:hover td {
            background: #fafafa;
        }

        .abb-table .actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        /* ---- Buttons ---- */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            font-family: 'Barlow', sans-serif;
            font-size: .87rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            text-decoration: none;
            border-radius: 1px;
            transition: background .15s, opacity .15s;
        }

        .btn-primary {
            background: var(--red);
            color: #fff;
        }

        .btn-primary:hover {
            background: var(--red-dark);
        }

        .btn-secondary {
            background: var(--dark);
            color: #fff;
        }

        .btn-secondary:hover {
            background: var(--mid);
        }

        .btn-ghost {
            background: transparent;
            border: 1.5px solid var(--border);
            color: var(--mid);
        }

        .btn-ghost:hover {
            border-color: var(--mid);
        }

        .btn-danger {
            background: transparent;
            border: 1.5px solid #FF000F;
            color: #FF000F;
        }

        .btn-danger:hover {
            background: #FF000F;
            color: #fff;
        }

        .btn-sm {
            padding: 5px 12px;
            font-size: .8rem;
        }

        .btn-icon {
            padding: 6px 10px;
        }

        /* ---- Form ---- */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            font-size: .82rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #555;
            margin-bottom: 6px;
        }

        .form-control {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid var(--border);
            font-family: 'Barlow', sans-serif;
            font-size: .95rem;
            border-radius: 1px;
            background: #fff;
            transition: border-color .15s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--red);
        }

        select.form-control {
            cursor: pointer;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 640px) {

            .form-row,
            .form-row-3 {
                grid-template-columns: 1fr;
            }
        }

        .form-hint {
            font-size: .78rem;
            color: #888;
            margin-top: 4px;
        }

        /* ---- Stati taratura ---- */
        .stato-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            font-size: .77rem;
            font-weight: 700;
            border-radius: 30px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }

        .stato-valida {
            background: #e6f9ee;
            color: #1a7a3a;
        }

        .stato-in-scadenza {
            background: #fff3cd;
            color: #856404;
        }

        .stato-scaduta {
            background: #fde8e8;
            color: #a00;
        }

        .stato-nd {
            background: #eee;
            color: #666;
        }

        /* ---- Flash messages ---- */
        .flash-msg {
            padding: 12px 18px;
            margin-bottom: 20px;
            font-weight: 600;
            font-size: .9rem;
            border-left: 4px solid;
        }

        .flash-success {
            background: #e8f5e9;
            border-color: #2e7d32;
            color: #1b5e20;
        }

        .flash-error {
            background: #ffebee;
            border-color: #c62828;
            color: #b71c1c;
        }

        /* ---- Overlay mobile ---- */
        #sb-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .5);
            z-index: 199;
        }

        /* ---- Responsive ---- */
        @media (max-width: 900px) {
            #sidebar {
                transform: translateX(-100%);
            }

            #sidebar.open {
                transform: translateX(0);
            }

            #sb-overlay.open {
                display: block;
            }

            #topbar {
                left: 0;
            }

            #main {
                margin-left: 0;
            }

            #hamburger {
                display: block;
            }
        }

        /* ---- Misc ---- */
        .page-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 20px;
        }

        .page-header h2 {
            margin: 0;
            font-size: 1.3rem;
            font-weight: 800;
        }

        .grid-4 {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
        }

        @media (max-width: 900px) {
            .grid-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 500px) {
            .grid-4 {
                grid-template-columns: 1fr;
            }
        }

        .text-muted {
            color: #888;
            font-size: .85rem;
        }

        .divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: 20px 0;
        }

        .table-scroll {
            overflow-x: auto;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #aaa;
        }

        .empty-state .fa {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: #ddd;
        }

        .search-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 16px;
        }

        .search-bar .form-control {
            max-width: 280px;
        }
    </style>
</head>

<body>

    <!-- Sidebar -->
    <div id="sidebar">
        <div class="sb-logo">
            <div class="sb-logo-box">ABB</div>
            <div class="sb-logo-sub">Calibration<br>Manager</div>
        </div>
        <nav>
            <div class="sb-section">Generale</div>
            <a href="<?= BASE_URL ?>/admin/dashboard.php"
                class="sb-link <?= $active_nav === 'dashboard' ? 'active' : '' ?>">
                <i class="fa fa-gauge"></i> Dashboard
                <?php if ($badge_count > 0): ?>
                    <span class="sb-badge"><?= $badge_count ?></span>
                <?php endif; ?>
            </a>

            <div class="sb-section">Gestione</div>

            <a href="<?= BASE_URL ?>/admin/tarature.php"
                class="sb-link <?= $active_nav === 'tarature' ? 'active' : '' ?>">
                <i class="fa fa-clipboard-check"></i> Tarature
            </a>
            <a href="<?= BASE_URL ?>/admin/macchinari.php"
                class="sb-link <?= $active_nav === 'macchinari' ? 'active' : '' ?>">
                <i class="fa fa-gears"></i> Macchinari
            </a>
            <a href="<?= BASE_URL ?>/admin/reparti.php"
                class="sb-link <?= $active_nav === 'reparti' ? 'active' : '' ?>">
                <i class="fa fa-building"></i> Reparti
            </a>
            <a href="<?= BASE_URL ?>/admin/qr_sheet.php"
                class="sb-link <?= $active_nav === 'Genera QR Code' ? 'active' : '' ?>">
                <i class="fa fa-qrcode"></i> Genera QR Code
            </a>

            <div class="sb-spacer"></div>
            <div class="sb-logout">
                <div class="sb-section">Account</div>
                <a href="<?= BASE_URL ?>/admin/impostazioni.php"
                    class="sb-link <?= $active_nav === 'impostazioni' ? 'active' : '' ?>">
                    <i class="fa fa-sliders"></i> Impostazioni
                </a>
                <a href="<?= BASE_URL ?>/logout.php" class="sb-link">
                    <i class="fa fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </nav>
    </div>
    <div id="sb-overlay" onclick="closeSidebar()"></div>

    <!-- Topbar -->
    <div id="topbar">
        <div class="tb-left">
            <button id="hamburger" onclick="toggleSidebar()" title="Menu">
                <i class="fa fa-bars"></i>
            </button>
            <h1><?= e($page_title) ?></h1>
        </div>
        <div style="display:flex; align-items:center; gap:10px;">
            <?php if ($badge_count > 0): ?>
                <a href="<?= BASE_URL ?>/admin/tarature.php?filter=scadenza"
                    style="color:var(--red); font-size:.82rem; font-weight:700; text-decoration:none;">
                    <i class="fa fa-bell"></i> <?= $badge_count ?> in scadenza
                </a>
            <?php endif; ?>
            <i class="fa fa-circle-user" style="color:#ccc; font-size:1.4rem;"></i>
        </div>
    </div>

    <!-- Main -->
    <div id="main">
        <?php flashRender(); ?>