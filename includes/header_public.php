<?php
// ============================================================
//  includes/header_public.php — Header pagine pubbliche
//  Variabili attese:  $page_title  (stringa)
// ============================================================
$page_title = $page_title ?? 'ABB Calibration';
?>
<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> — ABB Calibration</title>
    <link rel="icon" type="image/x-icon" href="https://images.seeklogo.com/logo-png/0/1/abb-logo-png_seeklogo-1844.png">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700;800&family=Barlow+Condensed:wght@600;700;800&display=swap">
    <style>
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

        :root {
            --red: #FF000F;
            --red-dark: #CC000C;
            --dark: #1A1A1A;
            --white: #FFFFFF;
            --border: #DCDCDC;
        }

        /* Topbar pubblica */
        .pub-topbar {
            background: var(--dark);
            border-bottom: 3px solid var(--red);
            padding: 14px 20px;
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .pub-logo-box {
            background: var(--red);
            color: #fff;
            font-family: 'Barlow Condensed', sans-serif;
            font-weight: 800;
            font-size: 1.5rem;
            letter-spacing: -1px;
            padding: 2px 9px 1px;
        }

        .pub-logo-sub {
            color: #888;
            font-size: .72rem;
            letter-spacing: .4px;
        }

        /* Container */
        .pub-container {
            max-width: 680px;
            margin: 0 auto;
            padding: 20px 16px 40px;
        }

        /* Machine header card */
        .machine-header {
            background: var(--white);
            padding: 24px 20px 18px;
            border-top: 4px solid var(--red);
            margin-bottom: 16px;
        }

        .machine-tag {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin-bottom: 6px;
        }

        .machine-name {
            font-family: 'Barlow Condensed', sans-serif;
            font-size: 2rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1.1;
            margin: 0 0 4px;
        }

        .machine-serial {
            color: #888;
            font-size: .88rem;
            font-weight: 500;
        }

        /* Status badge grande */
        .stato-banner {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            margin-bottom: 16px;
            font-weight: 700;
            font-size: 1rem;
        }

        .stato-banner .stato-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            font-weight: 900;
            flex-shrink: 0;
        }

        .stato-banner.valida {
            background: #e8f5e9;
        }

        .stato-banner.in-scadenza {
            background: #fff8e1;
        }

        .stato-banner.scaduta {
            background: #ffebee;
        }

        .stato-banner.valida .stato-icon {
            background: #2e7d32;
            color: #fff;
        }

        .stato-banner.in-scadenza .stato-icon {
            background: #f57f17;
            color: #fff;
        }

        .stato-banner.scaduta .stato-icon {
            background: var(--red);
            color: #fff;
        }

        .stato-banner .stato-text {
            line-height: 1.3;
        }

        .stato-banner .stato-label {
            font-size: 1.1rem;
            font-weight: 800;
        }

        .stato-banner .stato-sub {
            font-size: .82rem;
            font-weight: 500;
            color: #666;
        }

        /* Info grid */
        .info-card {
            background: var(--white);
            padding: 20px;
            margin-bottom: 16px;
        }

        .info-card h3 {
            font-size: .78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #888;
            margin: 0 0 14px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .info-item .info-lbl {
            font-size: .72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #aaa;
            margin-bottom: 2px;
        }

        .info-item .info-val {
            font-size: .95rem;
            font-weight: 600;
            color: var(--dark);
        }

        /* PDF button */
        .btn-pdf {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 16px;
            background: var(--red);
            color: #fff;
            font-family: 'Barlow', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            text-decoration: none;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: .5px;
            transition: background .15s;
        }

        .btn-pdf:hover {
            background: var(--red-dark);
        }

        .btn-pdf .fa {
            font-size: 1.2rem;
        }

        /* Storico link */
        .storico-link {
            display: block;
            text-align: center;
            padding: 14px;
            background: var(--white);
            color: var(--dark);
            text-decoration: none;
            font-weight: 600;
            font-size: .9rem;
            border: 1.5px solid var(--border);
            transition: border-color .15s;
            margin-bottom: 16px;
        }

        .storico-link:hover {
            border-color: var(--dark);
        }

        /* Footer pubblica */
        .pub-footer {
            text-align: center;
            color: #bbb;
            font-size: .75rem;
            padding: 20px;
            border-top: 1px solid var(--border);
            margin-top: 20px;
        }

        /* Esito pill */
        .esito-pill {
            display: inline-block;
            padding: 2px 10px;
            font-size: .75rem;
            font-weight: 700;
            border-radius: 20px;
            text-transform: uppercase;
        }

        .esito-conforme {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .esito-non-conforme {
            background: #ffebee;
            color: #c62828;
        }
    </style>
</head>

<body>
    <div class="pub-topbar">
        <div class="pub-logo-box">ABB</div>
        <div>
            <div style="color:#fff; font-weight:700; font-size:.95rem;">Calibration Manager</div>
            <div class="pub-logo-sub">ABB S.p.A. — Dalmine</div>
        </div>
    </div>