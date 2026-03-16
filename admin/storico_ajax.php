<?php
// ============================================================
//  admin/storico_ajax.php — Ritorna HTML storico tarature
//  Chiamato via fetch() da tarature.php
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$mac_id = (int) ($_GET['macchinario_id'] ?? 0);
if ($mac_id === 0) {
    http_response_code(400);
    exit;
}

$db = db();

// Recupera il nome del macchinario e l'id dell'ultima taratura
$mac = $db->prepare("SELECT nome FROM macchinari WHERE id = ?");
$mac->execute([$mac_id]);
$mac = $mac->fetch();
if (!$mac) {
    http_response_code(404);
    exit;
}

// ID dell'ultima taratura (quella già mostrata nella riga principale)
$ultima_id = (int) $db->prepare("
    SELECT id FROM tarature WHERE macchinario_id = ?
    ORDER BY data_inserimento DESC, id DESC LIMIT 1
")->execute([$mac_id]) ? $db->query("
    SELECT id FROM tarature WHERE macchinario_id = $mac_id
    ORDER BY data_inserimento DESC, id DESC LIMIT 1
")->fetchColumn() : 0;

// Fetch storico: tutte le tarature TRANNE l'ultima
$stmt = $db->prepare("
    SELECT t.*,
        CASE
            WHEN t.data_scadenza < CURDATE() THEN 'scaduta'
            WHEN DATEDIFF(t.data_scadenza, CURDATE()) <= 30 THEN 'in_scadenza'
            ELSE 'valida'
        END AS stato_scadenza,
        -- Questa taratura è stata sostituita? (esiste una successiva)
        EXISTS (
            SELECT 1 FROM tarature t2
            WHERE t2.macchinario_id = t.macchinario_id
              AND (t2.data_inserimento > t.data_inserimento
                   OR (t2.data_inserimento = t.data_inserimento AND t2.id > t.id))
        ) AS sostituita
    FROM tarature t
    WHERE t.macchinario_id = ?
      AND t.id != ?
    ORDER BY t.data_inserimento DESC, t.id DESC
");
$stmt->execute([$mac_id, $ultima_id]);
$storico = $stmt->fetchAll();

if (empty($storico)) {
    echo '<div style="padding:12px 20px; color:#aaa; font-size:.85rem;">Nessuna taratura storica.</div>';
    exit;
}
?>
<div style="border-top:1px solid #e8e8e8;">
    <div style="display:flex; align-items:center; gap:8px; padding:10px 18px 8px;
                font-size:.72rem; font-weight:700; text-transform:uppercase;
                letter-spacing:.8px; color:#888; background:#f4f4f4;">
        <i class="fa fa-clock-rotate-left"></i>
        Storico tarature precedenti —
        <?= e($mac['nome']) ?>
        <span style="font-weight:400;">(
            <?= count($storico) ?> registrazioni)
        </span>
    </div>
    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="background:#efefef;">
                <th
                    style="padding:7px 18px; font-size:.72rem; font-weight:700; text-align:left; color:#666; text-transform:uppercase; letter-spacing:.4px;">
                    Data</th>
                <th
                    style="padding:7px 14px; font-size:.72rem; font-weight:700; text-align:left; color:#666; text-transform:uppercase; letter-spacing:.4px;">
                    Scadenza</th>
                <th
                    style="padding:7px 14px; font-size:.72rem; font-weight:700; text-align:left; color:#666; text-transform:uppercase; letter-spacing:.4px;">
                    N° Certificato</th>
                <th
                    style="padding:7px 14px; font-size:.72rem; font-weight:700; text-align:left; color:#666; text-transform:uppercase; letter-spacing:.4px;">
                    Tecnico</th>
                <th
                    style="padding:7px 14px; font-size:.72rem; font-weight:700; text-align:left; color:#666; text-transform:uppercase; letter-spacing:.4px;">
                    Ente</th>
                <th
                    style="padding:7px 14px; font-size:.72rem; font-weight:700; text-align:left; color:#666; text-transform:uppercase; letter-spacing:.4px;">
                    Esito</th>
                <th
                    style="padding:7px 14px; font-size:.72rem; font-weight:700; text-align:left; color:#666; text-transform:uppercase; letter-spacing:.4px;">
                    Stato</th>
                <th style="padding:7px 14px; text-align:right;"></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($storico as $t):
                $si = statoInfo($t['stato_scadenza']);
                ?>
                <tr style="border-bottom:1px solid #f0f0f0; opacity: <?= $t['sostituita'] ? '.65' : '1' ?>;">
                    <td style="padding:9px 18px; font-size:.85rem; color:#555;">
                        <?= fmtDate($t['data_inserimento']) ?>
                        <?php if ($t['sostituita']): ?>
                            <span style="display:inline-flex; align-items:center; gap:3px; margin-left:6px;
                                     font-size:.68rem; font-weight:700; text-transform:uppercase;
                                     color:#999; background:#eee; padding:1px 6px; border-radius:8px;">
                                <i class="fa fa-rotate" style="font-size:.6rem;"></i> Sostituita
                            </span>
                        <?php endif; ?>
                    </td>
                    <td
                        style="padding:9px 14px; font-size:.85rem; color:<?= $t['stato_scadenza'] === 'scaduta' ? '#c00' : '#555' ?>;">
                        <?= fmtDate($t['data_scadenza']) ?>
                    </td>
                    <td style="padding:9px 14px; font-size:.8rem; font-family:monospace; color:#777;">
                        <?= e($t['numero_certificato'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px; font-size:.85rem; color:#555;">
                        <?= e($t['tecnico']) ?>
                    </td>
                    <td style="padding:9px 14px; font-size:.85rem; color:#555;">
                        <?= e($t['ente_certificatore'] ?? '—') ?>
                    </td>
                    <td style="padding:9px 14px;">
                        <span style="display:inline-block; padding:1px 8px; font-size:.72rem; font-weight:700;
                                 border-radius:20px; text-transform:uppercase;
                                 background:<?= $t['esito'] === 'conforme' ? '#e8f5e9' : '#ffebee' ?>;
                                 color:<?= $t['esito'] === 'conforme' ? '#2e7d32' : '#c62828' ?>;">
                            <?= $t['esito'] === 'conforme' ? 'Conforme' : 'Non conf.' ?>
                        </span>
                    </td>
                    <td style="padding:9px 14px;">
                        <span class="stato-pill <?= $si['class'] ?>" style="font-size:.68rem;">
                            <?= $si['icon'] ?>
                            <?= $si['label'] ?>
                        </span>
                    </td>
                    <td style="padding:9px 14px; text-align:right; white-space:nowrap;">
                        <?php if ($t['pdf_path']): ?>
                            <a href="<?= BASE_URL ?>/<?= e($t['pdf_path']) ?>" target="_blank"
                                style="color:var(--red); font-size:.8rem; text-decoration:none; margin-right:8px;"
                                title="Apri PDF">
                                <i class="fa fa-file-pdf"></i>
                            </a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/admin/taratura_edit.php?id=<?= $t['id'] ?>"
                            style="color:#888; font-size:.8rem; text-decoration:none;" title="Modifica">
                            <i class="fa fa-pen"></i>
                        </a>
                    </td>
                </tr>
                <?php if ($t['note']): ?>
                    <tr style="background:#fafafa; border-bottom:1px solid #f0f0f0;">
                        <td colspan="8" style="padding:4px 18px 8px; font-size:.8rem; color:#888; font-style:italic;">
                            <i class="fa fa-note-sticky" style="margin-right:4px;"></i>
                            <?= e($t['note']) ?>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>