<?php
// ============================================================
//  admin/macchinari.php — Gestione macchinari
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db = db();

// ---- Eliminazione ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];
    // Verifica se ci sono tarature associate
    $count = (int) $db->prepare("SELECT COUNT(*) FROM tarature WHERE macchinario_id = ?")
        ->execute([$id]) ? $db->query("SELECT COUNT(*) FROM tarature WHERE macchinario_id = $id")->fetchColumn() : 0;
    $st = $db->prepare("SELECT COUNT(*) FROM tarature WHERE macchinario_id = ?");
    $st->execute([$id]);
    $has_tar = (int) $st->fetchColumn();

    if ($has_tar > 0) {
        flashSet('error', 'Impossibile eliminare: il macchinario ha ' . $has_tar . ' taratura/e associate.');
    } else {
        $db->prepare("DELETE FROM macchinari WHERE id = ?")->execute([$id]);
        flashSet('success', 'Macchinario eliminato con successo.');
    }
    redirect(BASE_URL . '/admin/macchinari.php');
}

// ---- Ricerca / filtri ----
$search = trim($_GET['q'] ?? '');
$reparto_f = trim($_GET['reparto'] ?? '');

$params = [];
$where = ['m.attivo = 1'];

if ($search !== '') {
    $where[] = '(m.nome LIKE ? OR m.codice_seriale LIKE ? OR m.tipo_categoria LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($reparto_f !== '') {
    $where[] = 'r.nome = ?';
    $params[] = $reparto_f;
}

$sql = "
    SELECT m.*, r.nome AS reparto_nome,
           vt.stato_scadenza, vt.data_scadenza,
           vt.giorni_alla_scadenza, vt.taratura_id
    FROM macchinari m
    JOIN reparti r ON r.id = m.reparto_id
    LEFT JOIN v_ultima_taratura vt ON vt.macchinario_id = m.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY r.nome, m.nome
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$macchinari = $stmt->fetchAll();

// Reparti per il filtro
$reparti = $db->query("SELECT id, nome FROM reparti ORDER BY nome")->fetchAll();

$page_title = 'Macchinari';
$active_nav = 'macchinari';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<div class="page-header">
    <h2>Macchinari (<?= count($macchinari) ?>)</h2>
    <?php
    // BTN EXPORT
    $export_qs = http_build_query(array_filter([
        'source' => 'macchinari',
        'q' => $search,
        'reparto' => $reparto_f,
    ]));
    ?>
    <a href="<?= BASE_URL ?>/admin/export.php?<?= $export_qs ?>" class="btn btn-ghost"
        title="Esporta in CSV i macchinari visualizzati con la loro ultima taratura (tiene conto dei filtri applicati)">
        <i title="Esporta in CSV i macchinari visualizzati con la loro ultima taratura" class="fa fa-file-csv"></i>
        Esporta CSV
    </a>
    <a href="<?= BASE_URL ?>/admin/macchinario_edit.php" class="btn btn-primary">
        <i class="fa fa-plus"></i> Nuovo macchinario
    </a>
</div>

<!-- Ricerca e filtri -->
<div class="page-card" style="padding:14px 20px;">
    <form method="GET" class="search-bar">
        <input type="text" name="q" class="form-control" placeholder="Cerca nome, seriale, categoria…"
            value="<?= e($search) ?>" style="max-width:280px;">
        <select name="reparto" class="form-control" style="max-width:200px;">
            <option value="">Tutti i reparti</option>
            <?php foreach ($reparti as $r): ?>
                <option value="<?= e($r['nome']) ?>" <?= $reparto_f === $r['nome'] ? 'selected' : '' ?>>
                    <?= e($r['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa fa-search"></i> Filtra
        </button>
        <?php if ($search || $reparto_f): ?>
            <a href="<?= BASE_URL ?>/admin/macchinari.php" class="btn btn-ghost btn-sm">
                <i class="fa fa-xmark"></i> Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<div class="page-card" style="padding:0;">
    <?php if (empty($macchinari)): ?>
        <div class="empty-state" style="padding:50px;">
            <div><i class="fa fa-gears"></i></div>
            <p>Nessun macchinario trovato.</p>
            <a href="<?= BASE_URL ?>/admin/macchinario_edit.php" class="btn btn-primary">
                <i class="fa fa-plus"></i> Aggiungi il primo
            </a>
            <?php
            // BTN EXPORT
            $export_qs = http_build_query(array_filter([
                'source' => 'macchinari',
                'q' => $search,
                'reparto' => $reparto_f,
            ]));
            ?>
            <a href="<?= BASE_URL ?>/admin/export.php?<?= $export_qs ?>" class="btn btn-ghost"
                title="Esporta in CSV i macchinari visualizzati con la loro ultima taratura (tiene conto dei filtri applicati)">
                <i class="fa fa-file-csv"></i> Esporta CSV
            </a>
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="abb-table">
                <thead>
                    <tr>
                        <th>Macchinario</th>
                        <th>Seriale</th>
                        <th>Reparto</th>
                        <th>Categoria</th>
                        <th>Stato taratura</th>
                        <th>Scadenza</th>
                        <th style="text-align:right;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($macchinari as $m):
                        $stato = $m['stato_scadenza'] ?? 'nd';
                        $si = statoInfo($stato);
                        if (!$m['taratura_id']) {
                            $stato = 'nd';
                            $si = statoInfo('nd');
                        }
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:700;"><?= e($m['nome']) ?></div>
                                <div class="text-muted" style="font-size:.78rem;">
                                    Ogni <?= (int) $m['intervallo_mesi'] ?> mesi
                                </div>
                            </td>
                            <td style="font-family:monospace; font-size:.85rem;"><?= e($m['codice_seriale']) ?></td>
                            <td><?= e($m['reparto_nome']) ?></td>
                            <td><?= e($m['tipo_categoria'] ?? '—') ?></td>
                            <td>
                                <span class="stato-pill <?= $si['class'] ?>">
                                    <?= $si['icon'] ?>         <?= $si['label'] ?>
                                </span>
                            </td>
                            <td><?= $m['data_scadenza'] ? fmtDate($m['data_scadenza']) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td>
                                <div class="actions" style="justify-content:flex-end;">
                                    <!-- QR Download -->
                                    <a href="<?= BASE_URL ?>/admin/qr_download.php?id=<?= $m['id'] ?>"
                                        class="btn btn-ghost btn-sm btn-icon" title="Scarica QR code">
                                        <i class="fa fa-qrcode"></i>
                                    </a>
                                    <!-- Nuova taratura -->
                                    <a href="<?= BASE_URL ?>/admin/taratura_edit.php?macchinario_id=<?= $m['id'] ?>"
                                        class="btn btn-ghost btn-sm btn-icon" title="Aggiungi taratura">
                                        <i class="fa fa-plus-circle"></i>
                                    </a>
                                    <!-- Modifica -->
                                    <a href="<?= BASE_URL ?>/admin/macchinario_edit.php?id=<?= $m['id'] ?>"
                                        class="btn btn-ghost btn-sm btn-icon" title="Modifica">
                                        <i class="fa fa-pen"></i>
                                    </a>
                                    <!-- Elimina -->
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?= $m['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm btn-icon confirm-delete"
                                            title="Elimina">
                                            <i class="fa fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require dirname(__DIR__) . '/includes/footer_admin.php'; ?>