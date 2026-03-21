<?php
// ============================================================
//  admin/macchinari.php — CRUD macchinari
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db = db();

// ---- Eliminazione ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];
    $st = $db->prepare("SELECT COUNT(*) FROM tarature WHERE macchinario_id = ?");
    $st->execute([$id]);
    if ((int) $st->fetchColumn() > 0) {
        flashSet('error', 'Impossibile eliminare: il macchinario ha tarature associate.');
    } else {
        $db->prepare("DELETE FROM macchinari WHERE id = ?")->execute([$id]);
        flashSet('success', 'Macchinario eliminato.');
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

$stmt = $db->prepare("
    SELECT m.*, r.nome AS reparto_nome
    FROM macchinari m
    JOIN reparti r ON r.id = m.reparto_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY r.nome, m.nome
");
$stmt->execute($params);
$macchinari = $stmt->fetchAll();

$reparti = $db->query("SELECT id, nome FROM reparti ORDER BY nome")->fetchAll();

$page_title = 'Macchinari';
$active_nav = 'macchinari';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<div class="page-header">
    <h2>Macchinari (<?= count($macchinari) ?>)</h2>
    <a href="<?= BASE_URL ?>/admin/macchinario_edit.php" class="btn btn-primary">
        <i class="fa fa-plus"></i> Nuovo macchinario
    </a>
</div>

<!-- Ricerca -->
<div class="page-card" style="padding:14px 20px; margin-bottom:16px;">
    <form method="GET" class="search-bar">
        <input type="text" name="q" class="form-control" placeholder="Cerca nome, seriale, categoria…"
            value="<?= e($search) ?>" style="max-width:260px;">
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
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="abb-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Seriale</th>
                        <th>Reparto</th>
                        <th>Categoria</th>
                        <th>Unità</th>
                        <th>Intervallo</th>
                        <th style="text-align:right;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($macchinari as $m): ?>
                        <tr>
                            <td style="font-weight:700;"><?= e($m['nome']) ?></td>
                            <td style="font-family:monospace; font-size:.85rem;"><?= e($m['codice_seriale']) ?></td>
                            <td><?= e($m['reparto_nome']) ?></td>
                            <td><?= e($m['tipo_categoria'] ?? '—') ?></td>
                            <td><?= e($m['unita_misura'] ?? '—') ?></td>
                            <td><?= (int) $m['intervallo_mesi'] ?> mesi</td>
                            <td>
                                <div class="actions" style="justify-content:flex-end;">
                                    <a href="<?= BASE_URL ?>/admin/qr_download.php?id=<?= $m['id'] ?>"
                                        class="btn btn-ghost btn-sm btn-icon" title="Scarica QR">
                                        <i class="fa fa-qrcode"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/macchinario_edit.php?id=<?= $m['id'] ?>"
                                        class="btn btn-ghost btn-sm btn-icon" title="Modifica">
                                        <i class="fa fa-pen"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/scheda_pdf.php?id=<?= $m['id'] ?>" target="_blank"
                                        class="btn btn-ghost btn-sm btn-icon" title="Scarica scheda PDF">
                                        <i class="fa fa-file-pdf" style="color:var(--red)"></i>
                                    </a>
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