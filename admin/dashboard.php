<?php
// ============================================================
//  admin/dashboard.php — Dashboard principale
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
requireLogin();

$db = db();

// ---- Statistiche ----
$stats = $db->query("
    SELECT
        COUNT(*)                            AS totale,
        SUM(stato_scadenza = 'valida')      AS valide,
        SUM(stato_scadenza = 'in_scadenza') AS in_scadenza,
        SUM(stato_scadenza = 'scaduta')     AS scadute,
        SUM(taratura_id IS NULL)            AS senza_taratura
    FROM ( " . sql_ultima_taratura() . "
")->fetch();

// ---- Tarature in scadenza (lista compatta) ----
$allerta = $db->query("
    SELECT macchinario_nome, macchinario_id, reparto, data_scadenza,
           giorni_alla_scadenza AS giorni_rimanenti
    FROM ( " . sql_ultima_taratura() . "
    WHERE stato_scadenza = 'in_scadenza'
    ORDER BY giorni_alla_scadenza ASC
    LIMIT 8
")->fetchAll();

// ---- Macchinari senza taratura ----
$senza = $db->query("
    SELECT macchinario_id, macchinario_nome, reparto
    FROM ( " . sql_ultima_taratura() . "
    WHERE taratura_id IS NULL
    ORDER BY macchinario_nome
    LIMIT 5
")->fetchAll();

// ---- Tutte le scadenze per il calendario (nessun limite di date) ----
$scadenze_cal = $db->query("
    SELECT data_scadenza, macchinario_nome, stato_scadenza
    FROM ( " . sql_ultima_taratura() . "
    WHERE data_scadenza IS NOT NULL
    ORDER BY data_scadenza ASC
")->fetchAll();

// Raggruppa per data
$scadenze_per_giorno = [];
foreach ($scadenze_cal as $s) {
    $scadenze_per_giorno[$s['data_scadenza']][] = $s;
}

// ---- Calendario: mese da navigare ----
$cal_year = (int) ($_GET['cal_y'] ?? date('Y'));
$cal_month = (int) ($_GET['cal_m'] ?? date('n'));
if ($cal_month < 1) {
    $cal_month = 12;
    $cal_year--;
}
if ($cal_month > 12) {
    $cal_month = 1;
    $cal_year++;
}

$first_day = mktime(0, 0, 0, $cal_month, 1, $cal_year);
$days_in_month = (int) date('t', $first_day);
$start_dow = (int) date('N', $first_day); // 1=lun 7=dom
$today = date('Y-m-d');

$prev_m = $cal_month - 1;
$prev_y = $cal_year;
if ($prev_m < 1) {
    $prev_m = 12;
    $prev_y--;
}
$next_m = $cal_month + 1;
$next_y = $cal_year;
if ($next_m > 12) {
    $next_m = 1;
    $next_y++;
}

// Nomi mesi italiani — niente strftime (deprecato PHP 8.1+)
$mesi_it = [
    1 => 'Gennaio',
    2 => 'Febbraio',
    3 => 'Marzo',
    4 => 'Aprile',
    5 => 'Maggio',
    6 => 'Giugno',
    7 => 'Luglio',
    8 => 'Agosto',
    9 => 'Settembre',
    10 => 'Ottobre',
    11 => 'Novembre',
    12 => 'Dicembre',
];

$page_title = 'Dashboard';
$active_nav = 'dashboard';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<style>
    /* ---- Stat cards ---- */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }

    @media (min-width: 640px) {
        .stats-grid {
            grid-template-columns: repeat(4, 1fr);
        }
    }

    .stat-card-v2 {
        background: #fff;
        padding: 18px 16px;
        border-radius: 2px;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .08);
        border-top: 4px solid var(--border);
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .stat-card-v2.ok {
        border-top-color: #2e7d32;
    }

    .stat-card-v2.warn {
        border-top-color: #f57f17;
    }

    .stat-card-v2.err {
        border-top-color: #FF000F;
    }

    .stat-num {
        font-size: 2.4rem;
        font-weight: 900;
        line-height: 1;
        font-family: 'Barlow Condensed', sans-serif;
    }

    .stat-card-v2.ok .stat-num {
        color: #2e7d32;
    }

    .stat-card-v2.warn .stat-num {
        color: #f57f17;
    }

    .stat-card-v2.err .stat-num {
        color: #FF000F;
    }

    .stat-lbl-v2 {
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #888;
    }

    /* ---- Dashboard grid ---- */
    .dash-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
    }

    @media (min-width: 900px) {
        .dash-grid {
            grid-template-columns: 1fr 1fr;
        }

        .dash-full {
            grid-column: span 2;
        }
    }

    /* ---- Azioni rapide ---- */
    .quick-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    .qa-btn {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 20px 12px;
        background: #fafafa;
        border: 1.5px solid var(--border);
        text-decoration: none;
        color: var(--dark);
        font-weight: 700;
        font-size: .85rem;
        text-align: center;
        transition: border-color .15s, background .15s;
        border-radius: 2px;
    }

    .qa-btn:hover {
        border-color: var(--red);
        background: #fff5f5;
        color: var(--red);
    }

    .qa-btn .fa {
        font-size: 1.6rem;
        color: var(--red);
    }

    /* ---- Lista scadenze ---- */
    .scad-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f0f0f0;
        gap: 10px;
    }

    .scad-item:last-child {
        border-bottom: none;
    }

    .scad-giorni {
        font-size: 1.1rem;
        font-weight: 900;
        min-width: 44px;
        text-align: right;
        flex-shrink: 0;
        font-family: 'Barlow Condensed', sans-serif;
    }

    /* ---- Calendario ---- */
    .cal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .cal-header h3 {
        margin: 0;
        font-size: 1rem;
        font-weight: 800;
    }

    .cal-nav {
        background: none;
        border: 1.5px solid var(--border);
        padding: 5px 12px;
        font-size: .9rem;
        color: var(--dark);
        text-decoration: none;
        border-radius: 2px;
    }

    .cal-nav:hover {
        border-color: var(--dark);
    }

    .cal-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 3px;
    }

    .cal-dow {
        text-align: center;
        font-size: .68rem;
        font-weight: 700;
        text-transform: uppercase;
        color: #aaa;
        padding: 4px 0 6px;
    }

    .cal-day {
        position: relative;
        aspect-ratio: 1 / 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        font-weight: 600;
        border-radius: 2px;
        min-height: 32px;
    }

    .cal-day.empty {
        background: transparent;
    }

    .cal-day.today {
        background: var(--dark);
        color: #fff;
        font-weight: 800;
    }

    .cal-day.has-scadenza {
        cursor: pointer;
    }

    .cal-day.scad-valida {
        background: #e8f5e9;
        color: #1b5e20;
    }

    .cal-day.scad-in-scadenza {
        background: #fff3cd;
        color: #6d4c00;
    }

    .cal-day.scad-scaduta {
        background: #ffebee;
        color: #b71c1c;
    }

    .cal-day.today.has-scadenza {
        background: var(--red);
        color: #fff;
    }

    .cal-dot {
        position: absolute;
        bottom: 3px;
        width: 5px;
        height: 5px;
        border-radius: 50%;
    }

    .scad-valida .cal-dot {
        background: #2e7d32;
    }

    .scad-in-scadenza .cal-dot {
        background: #f57f17;
    }

    .scad-scaduta .cal-dot {
        background: #FF000F;
    }

    .today.has-scadenza .cal-dot {
        background: rgba(255, 255, 255, .7);
    }

    .cal-tooltip {
        display: none;
        position: absolute;
        bottom: calc(100% + 6px);
        left: 50%;
        transform: translateX(-50%);
        background: var(--dark);
        color: #fff;
        font-size: .68rem;
        font-weight: 600;
        white-space: nowrap;
        padding: 4px 8px;
        border-radius: 2px;
        z-index: 20;
        pointer-events: none;
    }

    .cal-tooltip::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: var(--dark);
    }

    .cal-day:hover .cal-tooltip {
        display: block;
    }

    .cal-legend {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 12px;
        font-size: .72rem;
        font-weight: 600;
        color: #666;
        align-items: center;
    }

    .cal-legend span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .leg-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }

    @media (max-width: 600px) {
        .dash-full > div {
            grid-template-columns: 1fr !important;
        }

        .dash-full > div > div:last-child {
            border-left: none !important;
            padding-left: 0 !important;
            border-top: 1px solid #f0f0f0;
            padding-top: 16px;
        }
    }
</style>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card-v2">
        <div class="stat-num"><?= (int) $stats['totale'] ?></div>
        <div class="stat-lbl-v2"><i class="fa fa-gears"></i> Macchinari</div>
    </div>
    <div class="stat-card-v2 ok">
        <div class="stat-num"><?= (int) ($stats['valide'] ?? 0) ?></div>
        <div class="stat-lbl-v2"><i class="fa fa-circle-check"></i> Valide</div>
    </div>
    <div class="stat-card-v2 warn">
        <div class="stat-num"><?= (int) ($stats['in_scadenza'] ?? 0) ?></div>
        <div class="stat-lbl-v2"><i class="fa fa-clock"></i> In scadenza</div>
    </div>
    <div class="stat-card-v2 err">
        <div class="stat-num"><?= (int) ($stats['scadute'] ?? 0) + (int) ($stats['senza_taratura'] ?? 0) ?></div>
        <div class="stat-lbl-v2"><i class="fa fa-circle-xmark"></i> Scadute / N/D</div>
    </div>
</div>

<div class="dash-grid">

    <!-- Azioni rapide + senza taratura -->
    <div class="page-card">
        <div class="page-header" style="margin-bottom:14px;">
            <h2>Azioni rapide</h2>
        </div>
        <div class="quick-actions">
            <a href="<?= BASE_URL ?>/admin/taratura_edit.php" class="qa-btn">
                <i class="fa fa-plus-circle"></i>
                Nuova taratura
            </a>
            <a href="<?= BASE_URL ?>/admin/macchinario_edit.php" class="qa-btn">
                <i class="fa fa-gears"></i>
                Nuovo macchinario
            </a>
            <a href="<?= BASE_URL ?>/admin/tarature.php?filter=scadute" class="qa-btn">
                <i class="fa fa-circle-xmark"></i>
                Vedi scadute
            </a>
            <a href="<?= BASE_URL ?>/admin/tarature.php?filter=scadenza" class="qa-btn">
                <i class="fa fa-clock"></i>
                In scadenza
            </a>
        </div>

        <?php if (!empty($senza)): ?>
            <div style="margin-top:16px; padding-top:16px; border-top:1px solid #f0f0f0;">
                <div style="font-size:.75rem; font-weight:700; text-transform:uppercase;
                            letter-spacing:.5px; color:#888; margin-bottom:10px;">
                    <i class="fa fa-question-circle"></i> Senza taratura
                </div>
                <?php foreach ($senza as $s): ?>
                    <div style="display:flex; align-items:center; justify-content:space-between;
                                padding:8px 0; border-bottom:1px solid #f8f8f8; gap:10px;">
                        <div style="min-width:0;">
                            <div style="font-weight:700; font-size:.88rem; white-space:nowrap;
                                        overflow:hidden; text-overflow:ellipsis;">
                                <?= e($s['macchinario_nome']) ?>
                            </div>
                            <div class="text-muted" style="font-size:.75rem;"><?= e($s['reparto']) ?></div>
                        </div>
                        <a href="<?= BASE_URL ?>/admin/taratura_edit.php?macchinario_id=<?= $s['macchinario_id'] ?>"
                            class="btn btn-primary btn-sm" style="white-space:nowrap; flex-shrink:0;">
                            <i class="fa fa-plus"></i> Aggiungi
                        </a>
                    </div>
                <?php endforeach; ?>
                <?php if ((int) $stats['senza_taratura'] > 5): ?>
                    <div style="margin-top:8px; font-size:.78rem; color:#aaa; text-align:center;">
                        + altri <?= (int) $stats['senza_taratura'] - 5 ?> senza taratura
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Tarature in scadenza -->
    <div class="page-card">
        <div class="page-header" style="margin-bottom:14px;">
            <h2>&#9888; In scadenza</h2>
            <a href="<?= BASE_URL ?>/admin/tarature.php?filter=scadenza" class="btn btn-ghost btn-sm">Tutte</a>
        </div>
        <?php if (empty($allerta)): ?>
            <div class="empty-state" style="padding:20px 0;">
                <i class="fa fa-circle-check" style="color:#2e7d32; font-size:2rem;
                   display:block; margin-bottom:8px;"></i>
                <p style="margin:0; color:#aaa; font-size:.88rem;">
                    Nessuna scadenza nei prossimi 30 giorni.
                </p>
            </div>
        <?php else: ?>
            <?php foreach ($allerta as $a): ?>
                <div class="scad-item">
                    <div style="flex:1; min-width:0;">
                        <div style="font-weight:700; font-size:.9rem; white-space:nowrap;
                                    overflow:hidden; text-overflow:ellipsis;">
                            <?= e($a['macchinario_nome']) ?>
                        </div>
                        <div class="text-muted" style="font-size:.75rem;">
                            <?= e($a['reparto']) ?> &mdash; <?= fmtDate($a['data_scadenza']) ?>
                        </div>
                    </div>
                    <div class="scad-giorni" style="color:<?= (int) $a['giorni_rimanenti'] <= 7 ? '#FF000F' : '#f57f17' ?>;">
                        <?= (int) $a['giorni_rimanenti'] ?>gg
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Calendario scadenze -->
    <div class="page-card dash-full">
        <div class="page-header" style="margin-bottom:16px;">
            <h2><i class="fa fa-calendar-days" style="color:var(--red)"></i> Calendario scadenze</h2>
        </div>

        <div style="display:grid; grid-template-columns: auto 1fr; gap:24px; align-items:start;">

            <!-- Calendario piccolo -->
            <div style="min-width:220px; max-width:240px;">
                <div class="cal-header" style="margin-bottom:8px;">
                    <a href="?cal_y=<?= $prev_y ?>&cal_m=<?= $prev_m ?>" class="cal-nav">&#8249;</a>
                    <h3 style="margin:0; font-size:.88rem; font-weight:800;">
                        <?= $mesi_it[$cal_month] . ' ' . $cal_year ?>
                    </h3>
                    <a href="?cal_y=<?= $next_y ?>&cal_m=<?= $next_m ?>" class="cal-nav">&#8250;</a>
                </div>

                <div style="display:grid; grid-template-columns:repeat(7,1fr); gap:1px;">
                    <?php foreach (['L', 'M', 'M', 'G', 'V', 'S', 'D'] as $dow): ?>
                        <div style="text-align:center; font-size:.6rem; font-weight:700;
                                color:#bbb; padding:3px 0;"><?= $dow ?></div>
                    <?php endforeach; ?>

                    <?php for ($i = 1; $i < $start_dow; $i++): ?>
                        <div></div>
                    <?php endfor; ?>

                    <?php for ($d = 1; $d <= $days_in_month; $d++):
                        $date_str = sprintf('%04d-%02d-%02d', $cal_year, $cal_month, $d);
                        $is_today = ($date_str === $today);
                        $scad = $scadenze_per_giorno[$date_str] ?? [];
                        $has = !empty($scad);

                        $dot_color = '';
                        if ($has) {
                            $stati = array_column($scad, 'stato_scadenza');
                            if (in_array('scaduta', $stati))
                                $dot_color = '#FF000F';
                            elseif (in_array('in_scadenza', $stati))
                                $dot_color = '#f57f17';
                            else
                                $dot_color = '#2e7d32';
                        }
                        ?>
                        <div style="
                        display:flex; flex-direction:column; align-items:center;
                        padding:2px 1px; border-radius:2px;
                        <?= $is_today ? 'background:var(--dark);' : '' ?>
                    ">
                            <span style="
                            font-size:.72rem; font-weight:<?= $is_today ? '800' : '500' ?>;
                            color:<?= $is_today ? '#fff' : '#444' ?>;
                            line-height:1.6;
                        "><?= $d ?></span>
                            <?php if ($has): ?>
                                <span style="width:4px; height:4px; border-radius:50%;
                                         background:<?= $dot_color ?>;
                                         margin-top:1px; display:block;
                                         <?= $is_today ? 'opacity:.7;' : '' ?>"></span>
                            <?php else: ?>
                                <span style="width:4px; height:4px; display:block;"></span>
                            <?php endif; ?>
                        </div>
                    <?php endfor; ?>
                </div>

                <!-- Legenda -->
                <div style="display:flex; flex-direction:column; gap:4px; margin-top:10px;">
                    <span
                        style="font-size:.65rem; font-weight:600; color:#888; display:flex; align-items:center; gap:5px;">
                        <span
                            style="width:6px;height:6px;border-radius:50%;background:#2e7d32;display:inline-block;"></span>
                        Valida
                    </span>
                    <span
                        style="font-size:.65rem; font-weight:600; color:#888; display:flex; align-items:center; gap:5px;">
                        <span
                            style="width:6px;height:6px;border-radius:50%;background:#f57f17;display:inline-block;"></span>
                        In scadenza
                    </span>
                    <span
                        style="font-size:.65rem; font-weight:600; color:#888; display:flex; align-items:center; gap:5px;">
                        <span
                            style="width:6px;height:6px;border-radius:50%;background:#FF000F;display:inline-block;"></span>
                        Scaduta
                    </span>
                </div>
            </div>

            <!-- Lista scadenze del mese -->
            <div style="border-left:1px solid #f0f0f0; padding-left:24px;">
                <div style="font-size:.72rem; font-weight:700; text-transform:uppercase;
                        letter-spacing:.5px; color:#888; margin-bottom:10px;">
                    Scadenze di <?= $mesi_it[$cal_month] ?>
                </div>
                <?php
                $scad_mese = array_filter(
                    $scadenze_per_giorno,
                    fn($date) => (int) explode('-', $date)[0] === $cal_year
                    && (int) explode('-', $date)[1] === $cal_month,
                    ARRAY_FILTER_USE_KEY
                );
                ksort($scad_mese);
                ?>
                <?php if (!empty($scad_mese)): ?>
                    <?php foreach ($scad_mese as $date => $items):
                        $giorno = (int) date('d', strtotime($date));
                        foreach ($items as $item):
                            $si = statoInfo($item['stato_scadenza']);
                            ?>
                            <div style="display:flex; align-items:center; gap:10px;
                                padding:7px 0; border-bottom:1px solid #f8f8f8;">
                                <div style="min-width:28px; text-align:center; font-size:1rem;
                                    font-weight:900; color:#bbb;
                                    font-family:'Barlow Condensed',sans-serif;">
                                    <?= $giorno ?>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:700; font-size:.87rem; white-space:nowrap;
                                        overflow:hidden; text-overflow:ellipsis;">
                                        <?= e($item['macchinario_nome']) ?>
                                    </div>
                                </div>
                                <span class="stato-pill <?= $si['class'] ?>" style="font-size:.68rem; flex-shrink:0;">
                                    <?= $si['icon'] ?>             <?= $si['label'] ?>
                                </span>
                            </div>
                        <?php endforeach; endforeach; ?>
                <?php else: ?>
                    <p style="font-size:.82rem; color:#bbb; margin:0;">
                        Nessuna scadenza in <?= $mesi_it[$cal_month] ?>.
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<?php require dirname(__DIR__) . '/includes/footer_admin.php'; ?>