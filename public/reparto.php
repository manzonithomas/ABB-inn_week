<?php
// ============================================================
//  public/reparto.php — Storico tarature per reparto
//  Accessibile tramite QR code all'ingresso dell'area
// ============================================================
require_once dirname(__DIR__) . '/config.php';

$reparto = trim($_GET['reparto'] ?? '');

if ($reparto === '') {
    // Mostra lista di tutti i reparti
    $reparti = db()->query("
        SELECT r.nome, COUNT(m.id) AS n_macchinari
        FROM reparti r
        LEFT JOIN macchinari m ON m.reparto_id = r.id AND m.attivo = 1
        GROUP BY r.nome ORDER BY r.nome
    ")->fetchAll();
    $page_title = 'Reparti';
    require dirname(__DIR__) . '/includes/header_public.php';
    ?>
    <div class="pub-container">
        <div class="machine-header">
            <div class="machine-tag">Seleziona reparto</div>
            <h1 class="machine-name" style="font-size:1.6rem;">Reparti ABB Dalmine</h1>
        </div>
        <?php foreach ($reparti as $r): ?>
            <a href="?reparto=<?= urlencode($r['nome']) ?>"
               style="display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:#fff; margin-bottom:10px; text-decoration:none; color:#1A1A1A; border-left:4px solid #FF000F;">
                <div>
                    <div style="font-weight:700; font-size:1rem;"><?= e($r['nome']) ?></div>
                    <div style="font-size:.82rem; color:#888;"><?= $r['n_macchinari'] ?> macchinari</div>
                </div>
                <i class="fa fa-chevron-right" style="color:#ccc;"></i>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
    require dirname(__DIR__) . '/includes/footer_public.php';
    exit;
}

// ---- Carica macchinari del reparto con ultima taratura ----
$stmt = db()->prepare("
    SELECT * FROM v_ultima_taratura
    WHERE reparto = ?
    ORDER BY stato_scadenza DESC, macchinario_nome ASC
");
$stmt->execute([$reparto]);
$macchinari = $stmt->fetchAll();

if (empty($macchinari)) {
    http_response_code(404);
    $page_title = 'Reparto non trovato';
    require dirname(__DIR__) . '/includes/header_public.php';
    echo '<div class="pub-container"><div class="machine-header"><p style="color:#c00;">Reparto non trovato o nessun macchinario registrato.</p></div></div>';
    require dirname(__DIR__) . '/includes/footer_public.php';
    exit;
}

// Conta stati
$n_valide     = count(array_filter($macchinari, fn($m) => $m['stato_scadenza'] === 'valida'));
$n_scadenza   = count(array_filter($macchinari, fn($m) => $m['stato_scadenza'] === 'in_scadenza'));
$n_scadute    = count(array_filter($macchinari, fn($m) => $m['stato_scadenza'] === 'scaduta'));
$n_notar      = count(array_filter($macchinari, fn($m) => !$m['taratura_id']));

$page_title = 'Reparto ' . $reparto;
require dirname(__DIR__) . '/includes/header_public.php';
?>

<div class="pub-container">

    <!-- Header reparto -->
    <div class="machine-header">
        <div class="machine-tag">Reparto</div>
        <h1 class="machine-name" style="font-size:1.7rem;"><?= e($reparto) ?></h1>
        <div class="machine-serial">
            <?= count($macchinari) ?> macchinari registrati
            &nbsp;—&nbsp;
            Aggiornato al <?= date('d/m/Y') ?>
        </div>
    </div>

    <!-- Riepilogo stati -->
    <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:8px; margin-bottom:16px;">
        <div style="background:#e8f5e9; padding:12px; text-align:center; border-radius:2px;">
            <div style="font-size:1.5rem; font-weight:800; color:#2e7d32;"><?= $n_valide ?></div>
            <div style="font-size:.72rem; font-weight:700; text-transform:uppercase; color:#2e7d32;">Valide</div>
        </div>
        <div style="background:#fff8e1; padding:12px; text-align:center; border-radius:2px;">
            <div style="font-size:1.5rem; font-weight:800; color:#f57f17;"><?= $n_scadenza ?></div>
            <div style="font-size:.72rem; font-weight:700; text-transform:uppercase; color:#f57f17;">In scadenza</div>
        </div>
        <div style="background:#ffebee; padding:12px; text-align:center; border-radius:2px;">
            <div style="font-size:1.5rem; font-weight:800; color:#c62828;"><?= $n_scadute + $n_notar ?></div>
            <div style="font-size:.72rem; font-weight:700; text-transform:uppercase; color:#c62828;">Scadute / N/D</div>
        </div>
    </div>

    <!-- Lista macchinari -->
    <?php foreach ($macchinari as $m):
        $stato     = $m['taratura_id'] ? $m['stato_scadenza'] : 'nd';
        $stato_css = match($stato) {
            'valida'      => 'valida',
            'in_scadenza' => 'in-scadenza',
            'scaduta'     => 'scaduta',
            default       => 'in-scadenza',
        };
        $border = match($stato) {
            'valida'      => '#2e7d32',
            'in_scadenza' => '#f57f17',
            'scaduta'     => '#FF000F',
            default       => '#bbb',
        };
    ?>
        <a href="<?= BASE_URL ?>/public/macchinario.php?token=<?= urlencode($m['qr_token']) ?>"
           style="display:block; background:#fff; margin-bottom:10px; text-decoration:none; color:inherit; border-left:4px solid <?= $border ?>; padding:14px 16px; position:relative;">

            <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:10px;">
                <div style="flex:1;">
                    <div style="font-weight:700; font-size:.98rem; margin-bottom:2px;"><?= e($m['macchinario_nome']) ?></div>
                    <div style="font-size:.78rem; color:#888; font-family:monospace;"><?= e($m['codice_seriale']) ?></div>
                </div>
                <div style="text-align:right; flex-shrink:0;">
                    <span class="stato-pill stato-<?= $stato === 'nd' ? 'nd' : $stato_css ?>"
                          style="font-size:.7rem; padding:2px 8px;">
                        <?php
                        echo match($stato) {
                            'valida'      => '✓ Valida',
                            'in_scadenza' => '! In scadenza',
                            'scaduta'     => '✕ Scaduta',
                            default       => '? Nessuna',
                        };
                        ?>
                    </span>
                </div>
            </div>

            <?php if ($m['taratura_id']): ?>
                <div style="margin-top:8px; font-size:.8rem; color:#666; display:flex; gap:16px; flex-wrap:wrap;">
                    <span><i class="fa fa-calendar-check" style="color:#aaa;"></i> <?= fmtDate($m['data_inserimento']) ?></span>
                    <span><i class="fa fa-calendar-xmark" style="color:<?= $border ?>;"></i>
                        <strong><?= fmtDate($m['data_scadenza']) ?></strong>
                        <?php if ($stato === 'in_scadenza'): ?>
                            <span style="color:#f57f17;">(<?= max(0,(int)$m['giorni_alla_scadenza']) ?>gg)</span>
                        <?php elseif ($stato === 'scaduta'): ?>
                            <span style="color:#FF000F;">(scaduta)</span>
                        <?php endif; ?>
                    </span>
                    <?php if ($m['tecnico']): ?>
                        <span><i class="fa fa-user-gear" style="color:#aaa;"></i> <?= e($m['tecnico']) ?></span>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="margin-top:6px; font-size:.8rem; color:#bbb; font-style:italic;">
                    Nessuna taratura registrata
                </div>
            <?php endif; ?>

            <i class="fa fa-chevron-right" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#e0e0e0;"></i>
        </a>
    <?php endforeach; ?>

    <!-- Link back ai reparti -->
    <a href="<?= BASE_URL ?>/public/reparto.php" class="storico-link" style="margin-top:10px;">
        <i class="fa fa-arrow-left" style="margin-right:6px;"></i> 🔙 Tutti i reparti
    </a>

</div>

<?php require dirname(__DIR__) . '/includes/footer_public.php'; ?>
