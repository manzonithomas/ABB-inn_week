<?php
// ============================================================
//  admin/tarature.php — Gestione tarature
//  Mostra l'ultima taratura per ogni macchinario.
//  Lo storico è espandibile inline riga per riga.
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db = db();

// ---- Eliminazione ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $del_id = (int) $_POST['delete_id'];
    $row = $db->prepare("SELECT pdf_path FROM tarature WHERE id = ?");
    $row->execute([$del_id]);
    $tar = $row->fetch();
    if ($tar) {
        $full = ROOT_DIR . '/' . $tar['pdf_path'];
        if (file_exists($full))
            @unlink($full);
        $db->prepare("DELETE FROM tarature WHERE id = ?")->execute([$del_id]);
        flashSet('success', 'Taratura eliminata.');
    }
    redirect(BASE_URL . '/admin/tarature.php');
}

// ---- Filtri ----
$filter = $_GET['filter'] ?? '';
$reparto_f = trim($_GET['reparto'] ?? '');
$macchinario_f = (int) ($_GET['macchinario_id'] ?? 0);
$search = trim($_GET['q'] ?? '');

$params = [];
$where = ['m.attivo = 1'];

if ($reparto_f !== '') {
    $where[] = 'r.nome = ?';
    $params[] = $reparto_f;
}
if ($macchinario_f > 0) {
    $where[] = 'm.id = ?';
    $params[] = $macchinario_f;
}
if ($search !== '') {
    $where[] = '(m.nome LIKE ? OR m.codice_seriale LIKE ? OR vt.tecnico LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($filter === 'scadenza') {
    $where[] = "vt.stato_scadenza = 'in_scadenza'";
} elseif ($filter === 'scadute') {
    $where[] = "vt.stato_scadenza = 'scaduta'";
} elseif ($filter === 'nessuna') {
    $where[] = "vt.taratura_id IS NULL";
}

$sql = "
    SELECT
        vt.*,
        (SELECT COUNT(*) FROM tarature t2 WHERE t2.macchinario_id = vt.macchinario_id) AS n_totale
    FROM v_ultima_taratura vt
    JOIN macchinari m ON m.id = vt.macchinario_id
    JOIN reparti r    ON r.nome = vt.reparto
    WHERE " . implode(' AND ', $where) . "
    ORDER BY
        CASE vt.stato_scadenza
            WHEN 'scaduta'     THEN 1
            WHEN 'in_scadenza' THEN 2
            WHEN 'valida'      THEN 3
            ELSE 4
        END,
        vt.data_scadenza ASC
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$righe = $stmt->fetchAll();

// Conteggi per filtri rapidi
$conteggi = $db->query("
    SELECT
        SUM(stato_scadenza = 'scaduta')     AS scadute,
        SUM(stato_scadenza = 'in_scadenza') AS in_scadenza,
        SUM(taratura_id IS NULL)            AS nessuna,
        COUNT(*)                            AS totale
    FROM v_ultima_taratura
")->fetch();

$reparti = $db->query("SELECT id, nome FROM reparti ORDER BY nome")->fetchAll();
$macchinari = $db->query("SELECT id, nome FROM macchinari WHERE attivo=1 ORDER BY nome")->fetchAll();

$page_title = 'Tarature';
$active_nav = 'tarature';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<div class="page-header">
    <h2>Tarature — ultima per macchinario (<?= count($righe) ?>)</h2>
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
        <?php
        $export_qs = http_build_query(array_filter([
            'source' => 'tarature',
            'filter' => $filter,
            'q' => $search,
            'reparto' => $reparto_f,
            'macchinario_id' => $macchinario_f ?: '',
        ]));
        ?>
        <a href="<?= BASE_URL ?>/admin/export.php?<?= $export_qs ?>" class="btn btn-ghost"
            title="Esporta in CSV le tarature visualizzate (rispetta i filtri attivi)">
            <i class="fa fa-file-csv"></i> Esporta CSV
        </a>
        <a href="<?= BASE_URL ?>/admin/taratura_edit.php" class="btn btn-primary">
            <i class="fa fa-plus"></i> Nuova taratura
        </a>
    </div>
</div>

<!-- Filtri rapidi -->
<div style="display:flex; gap:8px; margin-bottom:14px; flex-wrap:wrap;">
    <a href="<?= BASE_URL ?>/admin/tarature.php" class="btn btn-sm <?= !$filter ? 'btn-secondary' : 'btn-ghost' ?>">
        Tutte <span style="opacity:.6;">(<?= $conteggi['totale'] ?>)</span>
    </a>
    <a href="<?= BASE_URL ?>/admin/tarature.php?filter=scadenza"
        class="btn btn-sm <?= $filter === 'scadenza' ? 'btn-secondary' : 'btn-ghost' ?>"
        style="<?= $filter === 'scadenza' ? '' : 'color:#f57f17; border-color:#f57f17;' ?>">
        <i class="fa fa-clock"></i> In scadenza (<?= $conteggi['in_scadenza'] ?>)
    </a>
    <a href="<?= BASE_URL ?>/admin/tarature.php?filter=scadute"
        class="btn btn-sm <?= $filter === 'scadute' ? 'btn-secondary' : 'btn-ghost' ?>"
        style="<?= $filter === 'scadute' ? '' : 'color:#FF000F; border-color:#FF000F;' ?>">
        <i class="fa fa-circle-xmark"></i> Scadute (<?= $conteggi['scadute'] ?>)
    </a>
    <a href="<?= BASE_URL ?>/admin/tarature.php?filter=nessuna"
        class="btn btn-sm <?= $filter === 'nessuna' ? 'btn-secondary' : 'btn-ghost' ?>"
        style="<?= $filter === 'nessuna' ? '' : 'color:#888; border-color:#aaa;' ?>">
        <i class="fa fa-question-circle"></i> Nessuna (<?= $conteggi['nessuna'] ?>)
    </a>
</div>

<!-- Ricerca -->
<div class="page-card" style="padding:14px 20px; margin-bottom:16px;">
    <form method="GET" class="search-bar">
        <input type="hidden" name="filter" value="<?= e($filter) ?>">
        <input type="text" name="q" class="form-control" placeholder="Cerca macchinario, seriale, tecnico…"
            value="<?= e($search) ?>" style="max-width:240px;">
        <select name="reparto" class="form-control" style="max-width:180px;">
            <option value="">Tutti i reparti</option>
            <?php foreach ($reparti as $r): ?>
                <option value="<?= e($r['nome']) ?>" <?= $reparto_f === $r['nome'] ? 'selected' : '' ?>>
                    <?= e($r['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="macchinario_id" class="form-control" style="max-width:200px;">
            <option value="">Tutti i macchinari</option>
            <?php foreach ($macchinari as $mac): ?>
                <option value="<?= $mac['id'] ?>" <?= $macchinario_f === (int) $mac['id'] ? 'selected' : '' ?>>
                    <?= e($mac['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa fa-search"></i> Filtra
        </button>
        <?php if ($search || $reparto_f || $macchinario_f): ?>
            <a href="<?= BASE_URL ?>/admin/tarature.php<?= $filter ? '?filter=' . $filter : '' ?>"
                class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Tabella -->
<div class="page-card" style="padding:0;">
    <?php if (empty($righe)): ?>
        <div class="empty-state" style="padding:50px;">
            <div><i class="fa fa-clipboard-check"></i></div>
            <p>Nessuna taratura trovata.</p>
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="abb-table">
                <thead>
                    <tr>
                        <th>Macchinario</th>
                        <th>Reparto</th>
                        <th>Ultima taratura</th>
                        <th>Scadenza</th>
                        <th>Tecnico</th>
                        <th>Stato</th>
                        <th>Esito</th>
                        <th style="text-align:right;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($righe as $row):
                        $stato = $row['taratura_id'] ? ($row['stato_scadenza'] ?? 'nd') : 'nd';
                        $si = statoInfo($stato);
                        $n_stor = max(0, (int) $row['n_totale'] - 1);
                        $mac_id = $row['macchinario_id'];
                        ?>
                        <tr class="tar-row">
                            <td>
                                <div style="font-weight:700;"><?= e($row['macchinario_nome']) ?></div>
                                <div class="text-muted" style="font-size:.75rem; font-family:monospace;">
                                    <?= e($row['codice_seriale']) ?>
                                </div>
                            </td>
                            <td><?= e($row['reparto']) ?></td>
                            <td>
                                <?php if ($row['taratura_id']): ?>
                                    <div><?= fmtDate($row['data_inserimento']) ?></div>
                                    <?php if ($row['numero_certificato']): ?>
                                        <div class="text-muted" style="font-size:.75rem; font-family:monospace;">
                                            <?= e($row['numero_certificato']) ?>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600; color:<?= match ($stato) {
                                'scaduta' => '#FF000F',
                                'in_scadenza' => '#f57f17',
                                default => 'inherit'
                            } ?>">
                                <?php if ($row['data_scadenza']): ?>
                                    <?= fmtDate($row['data_scadenza']) ?>
                                    <?php if ($stato === 'in_scadenza'): ?>
                                        <div style="font-size:.72rem; color:#f57f17; font-weight:700;">
                                            <?= max(0, (int) $row['giorni_alla_scadenza']) ?> giorni
                                        </div>
                                    <?php elseif ($stato === 'scaduta'): ?>
                                        <div style="font-size:.72rem; color:#FF000F; font-weight:700;">
                                            <?= abs((int) $row['giorni_alla_scadenza']) ?> gg fa
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['tecnico'] ? e($row['tecnico']) : '<span class="text-muted">—</span>' ?></td>
                            <td>
                                <span class="stato-pill <?= $si['class'] ?>">
                                    <?= $si['icon'] ?>         <?= $si['label'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($row['taratura_id']): ?>
                                    <span class="esito-pill <?= $row['esito'] === 'conforme' ? 'esito-conforme' : 'esito-nc' ?>">
                                        <?= $row['esito'] === 'conforme' ? 'Conforme' : 'Non conf.' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions" style="justify-content:flex-end; flex-wrap:nowrap;">
                                    <?php if ($n_stor > 0): ?>
                                        <button type="button" class="btn btn-ghost btn-sm storico-toggle" data-mac="<?= $mac_id ?>"
                                            title="Mostra storico tarature precedenti">
                                            <i class="fa fa-clock-rotate-left"></i>
                                            <span class="stor-label">Storico (<?= $n_stor ?>)</span>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($row['pdf_path'] ?? false): ?>
                                        <a href="<?= BASE_URL ?>/<?= e($row['pdf_path']) ?>" target="_blank"
                                            class="btn btn-ghost btn-sm btn-icon" title="Apri PDF">
                                            <i class="fa fa-file-pdf" style="color:var(--red)"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="<?= BASE_URL ?>/admin/taratura_edit.php?macchinario_id=<?= $mac_id ?>"
                                        class="btn btn-ghost btn-sm btn-icon" title="Aggiungi nuova taratura">
                                        <i class="fa fa-plus-circle"></i>
                                    </a>
                                    <?php if ($row['taratura_id']): ?>
                                        <a href="<?= BASE_URL ?>/admin/taratura_edit.php?id=<?= $row['taratura_id'] ?>"
                                            class="btn btn-ghost btn-sm btn-icon" title="Modifica ultima taratura">
                                            <i class="fa fa-pen"></i>
                                        </a>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="delete_id" value="<?= $row['taratura_id'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm btn-icon confirm-delete"
                                                title="Elimina ultima taratura">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <!-- Riga storico nascosta -->
                        <?php if ($n_stor > 0): ?>
                            <tr class="storico-row" id="storico-<?= $mac_id ?>" style="display:none;">
                                <td colspan="8" style="padding:0; background:#f8f8f8; border-left:4px solid #e0e0e0;">
                                    <div id="storico-inner-<?= $mac_id ?>">
                                        <div style="padding:14px 20px; color:#aaa; font-size:.85rem;">
                                            <i class="fa fa-spinner fa-spin"></i> Caricamento…
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
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

    .storico-toggle.aperto {
        background: var(--dark);
        color: #fff;
        border-color: var(--dark);
    }
</style>

<script>
    const loaded = {};

    document.querySelectorAll('.storico-toggle').forEach(btn => {
        btn.addEventListener('click', function () {
            const macId = this.dataset.mac;
            const row = document.getElementById('storico-' + macId);
            const inner = document.getElementById('storico-inner-' + macId);
            const open = row.style.display !== 'none';

            if (open) {
                row.style.display = 'none';
                this.classList.remove('aperto');
                return;
            }

            row.style.display = '';
            this.classList.add('aperto');

            if (loaded[macId]) return;
            loaded[macId] = true;

            fetch('<?= BASE_URL ?>/admin/storico_ajax.php?macchinario_id=' + macId)
                .then(r => r.text())
                .then(html => { inner.innerHTML = html; })
                .catch(() => {
                    inner.innerHTML = '<div style="padding:12px 20px; color:#c00;">Errore nel caricamento.</div>';
                });
        });
    });
</script>

<?php require dirname(__DIR__) . '/includes/footer_admin.php'; ?>