<?php
// ============================================================
//  admin/dashboard.php — Dashboard principale
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db = db();

// ---- Statistiche ----
$stats = $db->query("
    SELECT
        COUNT(*) AS totale,
        SUM(stato_scadenza = 'valida')      AS valide,
        SUM(stato_scadenza = 'in_scadenza') AS in_scadenza,
        SUM(stato_scadenza = 'scaduta')     AS scadute
    FROM v_ultima_taratura
")->fetch();

// Macchinari senza tarature
$senza_tar = (int) $db->query("
    SELECT COUNT(*) FROM macchinari m
    WHERE m.attivo = 1
      AND NOT EXISTS (SELECT 1 FROM tarature t WHERE t.macchinario_id = m.id)
")->fetchColumn();

// Tarature in scadenza (per la tabella alert) — indipendente da notifica_inviata
$allerta = $db->query("
    SELECT macchinario_nome, reparto, data_scadenza,
           giorni_alla_scadenza AS giorni_rimanenti
    FROM v_ultima_taratura
    WHERE stato_scadenza = 'in_scadenza'
    ORDER BY giorni_alla_scadenza ASC
    LIMIT 10
")->fetchAll();

// Ultime 8 tarature inserite
$recenti = $db->query("
    SELECT t.*, m.nome AS macchinario_nome, r.nome AS reparto
    FROM tarature t
    JOIN macchinari m ON m.id = t.macchinario_id
    JOIN reparti r    ON r.id = m.reparto_id
    ORDER BY t.created_at DESC
    LIMIT 8
")->fetchAll();

// Statistiche per reparto
$per_reparto = $db->query("
    SELECT r.nome AS reparto,
           COUNT(*) AS totale,
           SUM(vt.stato_scadenza = 'scaduta')     AS scadute,
           SUM(vt.stato_scadenza = 'in_scadenza') AS in_scadenza
    FROM v_ultima_taratura vt
    JOIN reparti r ON r.nome = vt.reparto
    GROUP BY r.nome
    ORDER BY scadute DESC, in_scadenza DESC
")->fetchAll();

$page_title = 'Dashboard';
$active_nav = 'dashboard';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<!-- Stats Cards -->
<div class="grid-4" style="margin-bottom:20px;">
    <div class="stat-card">
        <div class="stat-val"><?= $stats['totale'] ?></div>
        <div class="stat-lbl"><i class="fa fa-gears" style="color:#aaa"></i> Macchinari totali</div>
    </div>
    <div class="stat-card ok">
        <div class="stat-val" style="color:#2e7d32;"><?= $stats['valide'] ?? 0 ?></div>
        <div class="stat-lbl"><i class="fa fa-circle-check" style="color:#2e7d32"></i> Tarature valide</div>
    </div>
    <div class="stat-card warn">
        <div class="stat-val" style="color:#f57f17;"><?= $stats['in_scadenza'] ?? 0 ?></div>
        <div class="stat-lbl"><i class="fa fa-clock" style="color:#f57f17"></i> In scadenza (≤30 gg)</div>
    </div>
    <div class="stat-card err">
        <div class="stat-val" style="color:#FF000F;"><?= ($stats['scadute'] ?? 0) + $senza_tar ?></div>
        <div class="stat-lbl"><i class="fa fa-circle-xmark" style="color:#FF000F"></i> Scadute / senza taratura</div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;" class="dash-grid">

    <!-- Alert tarature in scadenza -->
    <div class="page-card" style="grid-column: span 1;">
        <div class="page-header">
            <h2>&#9888; Tarature in scadenza</h2>
            <a href="<?= BASE_URL ?>/admin/tarature.php?filter=scadenza" class="btn btn-ghost btn-sm">Vedi tutte</a>
        </div>
        <?php if (empty($allerta)): ?>
            <div class="empty-state">
                <div class="fa fa-circle-check" style="color:#2e7d32; font-size:2rem;"></div>
                <p>Nessuna taratura in scadenza nei prossimi 30 giorni.</p>
            </div>
        <?php else: ?>
            <?php foreach ($allerta as $a): ?>
                <div
                    style="display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid #f0f0f0;">
                    <div>
                        <div style="font-weight:700; font-size:.9rem;"><?= e($a['macchinario_nome']) ?></div>
                        <div class="text-muted"><?= e($a['reparto']) ?> &mdash; scade il <?= fmtDate($a['data_scadenza']) ?>
                        </div>
                    </div>
                    <span style="font-weight:800; font-size:1.1rem;
                        color: <?= $a['giorni_rimanenti'] <= 7 ? '#FF000F' : '#f57f17' ?>;">
                        <?= $a['giorni_rimanenti'] ?>gg
                    </span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Tarature per reparto -->
    <div class="page-card" style="grid-column: span 1;">
        <div class="page-header">
            <h2>Stato per reparto</h2>
        </div>
        <?php if (empty($per_reparto)): ?>
            <div class="empty-state">
                <p>Nessun dato disponibile.</p>
            </div>
        <?php else: ?>
            <div class="table-scroll">
                <table class="abb-table">
                    <thead>
                        <tr>
                            <th>Reparto</th>
                            <th>Totale</th>
                            <th>Scadute</th>
                            <th>In scadenza</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($per_reparto as $r): ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_URL ?>/public/reparto.php?reparto=<?= urlencode($r['reparto']) ?>"
                                        style="color:inherit; font-weight:600;" target="_blank">
                                        <?= e($r['reparto']) ?>
                                        <i class="fa fa-arrow-up-right-from-square"
                                            style="font-size:.7rem; color:#aaa; margin-left:4px;"></i>
                                    </a>
                                </td>
                                <td><?= $r['totale'] ?></td>
                                <td><?php if ($r['scadute'] > 0): ?>
                                        <span style="color:#FF000F; font-weight:700;"><?= $r['scadute'] ?></span>
                                    <?php else:
                                    echo '0'; endif; ?>
                                </td>
                                <td><?php if ($r['in_scadenza'] > 0): ?>
                                        <span style="color:#f57f17; font-weight:700;"><?= $r['in_scadenza'] ?></span>
                                    <?php else:
                                    echo '0'; endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Ultime tarature inserite -->
    <div class="page-card" style="grid-column: span 2;">
        <div class="page-header">
            <h2>Ultime tarature inserite</h2>
            <a href="<?= BASE_URL ?>/admin/tarature.php" class="btn btn-ghost btn-sm">Tutte le tarature</a>
        </div>
        <?php if (empty($recenti)): ?>
            <div class="empty-state">
                <i class="fa fa-clipboard-list"></i>
                <p>Nessuna taratura inserita.</p>
            </div>
        <?php else: ?>
            <div class="table-scroll">
                <table class="abb-table">
                    <thead>
                        <tr>
                            <th>Macchinario</th>
                            <th>Reparto</th>
                            <th>Data inserimento</th>
                            <th>Scadenza</th>
                            <th>Tecnico</th>
                            <th>Esito</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recenti as $t): ?>
                            <tr>
                                <td style="font-weight:600;"><?= e($t['macchinario_nome']) ?></td>
                                <td><?= e($t['reparto']) ?></td>
                                <td><?= fmtDate($t['data_inserimento']) ?></td>
                                <td><?= fmtDate($t['data_scadenza']) ?></td>
                                <td><?= e($t['tecnico']) ?></td>
                                <td>
                                    <span class="esito-pill <?= $t['esito'] === 'conforme' ? 'esito-conforme' : 'esito-nc' ?>">
                                        <?= $t['esito'] === 'conforme' ? 'Conforme' : 'Non conf.' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<style>
    .esito-pill {
        display: inline-block;
        padding: 2px 9px;
        font-size: .75rem;
        font-weight: 700;
        border-radius: 20px;
        text-transform: uppercase;
    }

    .esito-conforme {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .esito-nc {
        background: #ffebee;
        color: #c62828;
    }

    @media (max-width:768px) {
        .dash-grid {
            grid-template-columns: 1fr !important;
        }

        .dash-grid .page-card {
            grid-column: span 1 !important;
        }
    }
</style>

<?php require dirname(__DIR__) . '/includes/footer_admin.php'; ?>