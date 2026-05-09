<?php
// ============================================================
//  public/macchinario.php — Pagina pubblica (QR scan)
//  Mostra l'ultima taratura del macchinario
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/queries.php';

$token = trim($_GET['token'] ?? '');

if ($token === '') {
    http_response_code(400);
    $page_title = 'Token mancante';
    require dirname(__DIR__) . '/includes/header_public.php';
    echo '<div class="pub-container"><div class="machine-header"><p style="color:#c00;">URL non valido: token mancante.</p></div></div>';
    require dirname(__DIR__) . '/includes/footer_public.php';
    exit;
}

// Carica macchinario + ultima taratura dalla view
$stmt = db()->prepare("SELECT * FROM ( " . sql_ultima_taratura() . " WHERE qr_token = ?");
$stmt->execute([$token]);
$data = $stmt->fetch();

if (!$data) {
    http_response_code(404);
    $page_title = 'Macchinario non trovato';
    require dirname(__DIR__) . '/includes/header_public.php';
    echo '<div class="pub-container"><div class="machine-header"><p style="color:#c00;">Macchinario non trovato o QR code non valido.</p></div></div>';
    require dirname(__DIR__) . '/includes/footer_public.php';
    exit;
}

// Stato
$stato = $data['taratura_id'] ? $data['stato_scadenza'] : 'nd';
$stato_css = match ($stato) {
    'valida' => 'valida',
    'in_scadenza' => 'in-scadenza',
    'scaduta' => 'scaduta',
    default => 'in-scadenza',
};
$stato_label = match ($stato) {
    'valida' => 'Taratura valida',
    'in_scadenza' => 'In scadenza',
    'scaduta' => 'Taratura scaduta',
    default => 'Nessuna taratura',
};
$stato_icon = match ($stato) {
    'valida' => '✓',
    'in_scadenza' => '!',
    'scaduta' => '✕',
    default => '?',
};
$stato_sub = '';
if ($data['taratura_id']) {
    if ($stato === 'valida') {
        $stato_sub = 'Scade il ' . fmtDate($data['data_scadenza']) . ' (' . max(0, (int) $data['giorni_alla_scadenza']) . ' giorni)';
    } elseif ($stato === 'in_scadenza') {
        $stato_sub = 'Scade il ' . fmtDate($data['data_scadenza']) . ' — solo ' . max(0, (int) $data['giorni_alla_scadenza']) . ' giorni rimanenti!';
    } elseif ($stato === 'scaduta') {
        $gg = abs((int) $data['giorni_alla_scadenza']);
        $stato_sub = 'Scaduta il ' . fmtDate($data['data_scadenza']) . ' (' . $gg . ' giorni fa)';
    }
} else {
    $stato_sub = 'Nessuna taratura registrata per questo macchinario.';
}

// Tarature precedenti (tutte tranne l'ultima) con PDF
$stmt_stor = db()->prepare("
    SELECT id, data_inserimento, data_scadenza, numero_certificato,
           tecnico, ente_certificatore, esito, pdf_path
    FROM tarature
    WHERE macchinario_id = ?
      AND id != ?
    ORDER BY data_inserimento DESC, id DESC
");
$stmt_stor->execute([$data['macchinario_id'], $data['taratura_id'] ?? 0]);
$storico = $stmt_stor->fetchAll();

$page_title = e($data['macchinario_nome']);
require dirname(__DIR__) . '/includes/header_public.php';
?>

<div class="pub-container">

    <!-- Header macchinario -->
    <div class="machine-header">
        <div class="machine-tag">Macchinario</div>
        <h1 class="machine-name"><?= e($data['macchinario_nome']) ?></h1>
        <div class="machine-serial">
            <i class="fa fa-barcode" style="color:#ccc; margin-right:4px;"></i>
            <?= e($data['codice_seriale']) ?>
            &nbsp;&mdash;&nbsp;
            <i class="fa fa-location-dot" style="color:#ccc; margin-right:4px;"></i>
            <?= e($data['reparto']) ?>
        </div>
    </div>

    <!-- Banner stato -->
    <div class="stato-banner <?= $stato_css ?>">
        <div class="stato-icon"><?= $stato_icon ?></div>
        <div class="stato-text">
            <div class="stato-label"><?= $stato_label ?></div>
            <?php if ($stato_sub): ?>
                <div class="stato-sub"><?= e($stato_sub) ?></div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($data['taratura_id']): ?>

        <!-- Dettagli taratura -->
        <div class="info-card">
            <h3>Dettagli taratura</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-lbl">Data esecuzione</div>
                    <div class="info-val"><?= fmtDate($data['data_inserimento']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-lbl">Data scadenza</div>
                    <div class="info-val"
                        style="color:<?= $stato === 'scaduta' ? '#FF000F' : ($stato === 'in_scadenza' ? '#f57f17' : 'inherit') ?>; font-weight:700;">
                        <?= fmtDate($data['data_scadenza']) ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-lbl">Tecnico</div>
                    <div class="info-val"><?= e($data['tecnico']) ?></div>
                </div>
                <div class="info-item">
                    <div class="info-lbl">Ente certificatore</div>
                    <div class="info-val"><?= $data['ente_certificatore'] ? e($data['ente_certificatore']) : '—' ?></div>
                </div>
                <?php if ($data['numero_certificato']): ?>
                    <div class="info-item">
                        <div class="info-lbl">N° certificato</div>
                        <div class="info-val" style="font-family:monospace;"><?= e($data['numero_certificato']) ?></div>
                    </div>
                <?php endif; ?>
                <div class="info-item">
                    <div class="info-lbl">Esito</div>
                    <div class="info-val">
                        <span
                            class="esito-pill <?= $data['esito'] === 'conforme' ? 'esito-conforme' : 'esito-non-conforme' ?>">
                            <?= $data['esito'] === 'conforme' ? '✓ Conforme' : '✕ Non conforme' ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php if ($data['note'] ?? false): ?>
                <div style="margin-top:14px; padding-top:14px; border-top:1px solid #f0f0f0;">
                    <div class="info-lbl" style="margin-bottom:6px;">Note</div>
                    <div style="font-size:.9rem; color:#444; line-height:1.5;"><?= nl2br(e($data['note'])) ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Informazioni strumento -->
        <div class="info-card">
            <h3>Strumento</h3>
            <div class="info-grid">
                <?php if ($data['tipo_categoria'] ?? false): ?>
                    <div class="info-item">
                        <div class="info-lbl">Categoria</div>
                        <div class="info-val"><?= e($data['tipo_categoria']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($data['unita_misura'] ?? false): ?>
                    <div class="info-item">
                        <div class="info-lbl">Unità di misura</div>
                        <div class="info-val"><?= e($data['unita_misura']) ?></div>
                    </div>
                <?php endif; ?>
                <div class="info-item">
                    <div class="info-lbl">Intervallo taratura</div>
                    <div class="info-val">Ogni <?= (int) $data['intervallo_mesi'] ?> mesi</div>
                </div>
                <div class="info-item">
                    <div class="info-lbl">Reparto</div>
                    <div class="info-val"><?= e($data['reparto']) ?></div>
                </div>
            </div>
        </div>

        <!-- Download PDF -->
        <?php if ($data['pdf_path']): ?>
            <a href="<?= BASE_URL ?>/<?= e($data['pdf_path']) ?>" target="_blank" class="btn-pdf"
                style="margin-bottom:12px; display:flex;">
                <i class="fa fa-file-pdf"></i>
                Apri certificato di taratura (PDF)
            </a>
        <?php endif; ?>

    <?php endif; ?>

    <!-- Storico PDF tarature precedenti -->
    <?php if (!empty($storico)): ?>
        <div class="info-card" style="margin-bottom:12px;">
            <div style="display:flex; align-items:center; justify-content:space-between; cursor:pointer; user-select:none;"
                onclick="toggleStorico()" id="storico-toggle">
                <h3 style="margin:0;">
                    <i class="fa fa-clock-rotate-left" style="margin-right:6px;"></i>
                    Certificati precedenti (<?= count($storico) ?>)
                </h3>
                <i class="fa fa-chevron-down" id="storico-chevron" style="color:#aaa; transition:transform .2s;"></i>
            </div>
            <div id="storico-list" style="display:none; margin-top:14px;">
                <?php foreach ($storico as $i => $s): ?>
                    <?php if ($i > 0): ?>
                        <div style="border-top:1px solid #f0f0f0;"></div>
                    <?php endif; ?>
                    <div style="display:flex; align-items:center; justify-content:space-between;
                                gap:10px; padding:10px 0; flex-wrap:wrap;">
                        <div style="flex:1; min-width:0;">
                            <div style="font-size:.88rem; font-weight:600; color:#333;">
                                <?= fmtDate($s['data_inserimento']) ?>
                                <span style="color:#aaa; font-weight:400;">→</span>
                                <?= fmtDate($s['data_scadenza']) ?>
                            </div>
                            <div style="font-size:.75rem; color:#aaa; margin-top:2px;">
                                <?= e($s['tecnico']) ?>
                                <?php if ($s['numero_certificato']): ?>
                                    &nbsp;·&nbsp;
                                    <span style="font-family:monospace;"><?= e($s['numero_certificato']) ?></span>
                                <?php endif; ?>
                                &nbsp;·&nbsp;
                                <span style="font-weight:700; color:<?= $s['esito'] === 'conforme' ? '#2e7d32' : '#c62828' ?>;">
                                    <?= $s['esito'] === 'conforme' ? 'Conforme' : 'Non conforme' ?>
                                </span>
                            </div>
                        </div>
                        <?php if ($s['pdf_path']): ?>
                            <a href="<?= BASE_URL ?>/<?= e($s['pdf_path']) ?>" target="_blank" style="display:inline-flex; align-items:center; gap:6px;
                                      padding:7px 14px; background:#fff; border:1.5px solid var(--border);
                                      color:var(--red); font-size:.82rem; font-weight:700;
                                      text-decoration:none; white-space:nowrap;
                                      transition:border-color .15s;" onmouseover="this.style.borderColor='#FF000F'"
                                onmouseout="this.style.borderColor='var(--border)'">
                                <i class="fa fa-file-pdf"></i> PDF
                            </a>
                        <?php else: ?>
                            <span style="font-size:.78rem; color:#ccc; font-style:italic;">PDF non disponibile</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
            function toggleStorico() {
                const list = document.getElementById('storico-list');
                const chevron = document.getElementById('storico-chevron');
                const open = list.style.display !== 'none';
                list.style.display = open ? 'none' : 'block';
                chevron.style.transform = open ? '' : 'rotate(180deg)';
            }
        </script>
    <?php endif; ?>

    <!-- Link storico -->
    <a href="<?= BASE_URL ?>/public/reparto.php?reparto=<?= urlencode($data['reparto']) ?>" class="storico-link">
        <i class="fa fa-clock-rotate-left" style="margin-right:6px;"></i>
        🔙 Storico tarature reparto <?= e($data['reparto']) ?>
    </a>

    <!-- Aggiornato al -->
    <div style="text-align:center; font-size:.75rem; color:#bbb; margin-top:8px;">
        Pagina aggiornata al <?= date('d/m/Y \o\r\e H:i') ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer_public.php'; ?>