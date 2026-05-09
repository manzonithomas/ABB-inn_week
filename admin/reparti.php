<?php
// ============================================================
//  admin/reparti.php — CRUD reparti
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db = db();

// ---- Eliminazione ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $id = (int) $_POST['delete_id'];
    $st = $db->prepare("SELECT COUNT(*) FROM macchinari WHERE reparto_id = ? AND attivo = 1");
    $st->execute([$id]);
    if ((int) $st->fetchColumn() > 0) {
        flashSet('error', 'Impossibile eliminare: il reparto ha macchinari associati.');
    } else {
        $db->prepare("DELETE FROM reparti WHERE id = ?")->execute([$id]);
        flashSet('success', 'Reparto eliminato.');
    }
    redirect(BASE_URL . '/admin/reparti.php');
}

// ---- Salvataggio (add / edit inline) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $id_edit = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $descrizione = trim($_POST['descrizione'] ?? '');
    $errors = [];

    if ($nome === '')
        $errors[] = 'Il nome del reparto è obbligatorio.';

    // Controlla unicità nome
    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM reparti WHERE nome = ? AND id != ?");
        $chk->execute([$nome, $id_edit]);
        if ($chk->fetch())
            $errors[] = 'Esiste già un reparto con questo nome.';
    }

    if (empty($errors)) {
        if ($id_edit === 0) {
            $db->prepare("INSERT INTO reparti (nome, descrizione) VALUES (?, ?)")
                ->execute([$nome, $descrizione]);
            flashSet('success', "Reparto \"$nome\" aggiunto.");
        } else {
            $db->prepare("UPDATE reparti SET nome = ?, descrizione = ? WHERE id = ?")
                ->execute([$nome, $descrizione, $id_edit]);
            flashSet('success', "Reparto \"$nome\" aggiornato.");
        }
        redirect(BASE_URL . '/admin/reparti.php');
    }
    // Se errori, li mostriamo dopo
}

// ---- Lista reparti con conteggio macchinari ----
$reparti = $db->query("
    SELECT r.*,
           COUNT(m.id) AS n_macchinari
    FROM reparti r
    LEFT JOIN macchinari m ON m.reparto_id = r.id AND m.attivo = 1
    GROUP BY r.id
    ORDER BY r.nome
")->fetchAll();

// Reparto in modifica (click su matita)
$edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
$edit_row = null;
if ($edit_id > 0) {
    $st = $db->prepare("SELECT * FROM reparti WHERE id = ?");
    $st->execute([$edit_id]);
    $edit_row = $st->fetch();
}

$page_title = 'Reparti';
$active_nav = 'reparti';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<div class="page-header">
    <h2>Reparti (<?= count($reparti) ?>)
    </h2>
</div>

<!-- Form aggiunta / modifica -->
<div class="page-card" style="margin-bottom:20px;">
    <h3 style="margin:0 0 18px; font-size:1rem; font-weight:800;">
        <?php if ($edit_row): ?>
            <i class="fa fa-pen" style="color:var(--red)"></i> Modifica reparto
        <?php else: ?>
            <i class="fa fa-plus-circle" style="color:var(--red)"></i> Nuovo reparto
        <?php endif; ?>
    </h3>

    <?php if (!empty($errors)): ?>
        <div class="flash-msg flash-error" style="margin-bottom:16px;">
            <?php foreach ($errors as $er): ?>
                <?= e($er) ?><br>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="save" value="1">
        <input type="hidden" name="id" value="<?= $edit_row ? $edit_row['id'] : 0 ?>">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label" for="nome">Nome reparto *</label>
                <input type="text" id="nome" name="nome" class="form-control"
                    value="<?= e($edit_row['nome'] ?? ($_POST['nome'] ?? '')) ?>" placeholder="es. Assemblaggio VD4"
                    required autofocus>
            </div>
            <div class="form-group">
                <label class="form-label" for="descrizione">Descrizione</label>
                <input type="text" id="descrizione" name="descrizione" class="form-control"
                    value="<?= e($edit_row['descrizione'] ?? ($_POST['descrizione'] ?? '')) ?>"
                    placeholder="Breve descrizione del reparto">
            </div>
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-floppy-disk"></i>
                <?= $edit_row ? 'Salva modifiche' : 'Aggiungi reparto' ?>
            </button>
            <?php if ($edit_row): ?>
                <a href="<?= BASE_URL ?>/admin/reparti.php" class="btn btn-ghost">
                    <i class="fa fa-xmark"></i> Annulla
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Lista reparti -->
<div class="page-card" style="padding:0;">
    <?php if (empty($reparti)): ?>
        <div class="empty-state" style="padding:40px;">
            <p>Nessun reparto configurato.</p>
        </div>
    <?php else: ?>
        <div class="table-scroll">
            <table class="abb-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th>Macchinari</th>
                        <th style="text-align:right;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reparti as $r): ?>
                        <tr <?= $edit_id === (int) $r['id'] ? 'style="background:#fff8f0;"' : '' ?>>
                            <td style="font-weight:700;">
                                <?= e($r['nome']) ?>
                            </td>
                            <td class="text-muted">
                                <?= e($r['descrizione'] ?? '—') ?>
                            </td>
                            <td>
                                <?php if ($r['n_macchinari'] > 0): ?>
                                    <a href="<?= BASE_URL ?>/admin/macchinari.php?reparto=<?= urlencode($r['nome']) ?>"
                                        style="color:var(--red); font-weight:700; text-decoration:none;">
                                        <?= $r['n_macchinari'] ?>
                                        <i class="fa fa-arrow-up-right-from-square" style="font-size:.7rem;"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions" style="justify-content:flex-end;">
                                    <!-- Storico pubblico reparto -->
                                    <a href="<?= BASE_URL ?>/public/reparto.php?reparto=<?= urlencode($r['nome']) ?>"
                                        target="_blank" class="btn btn-ghost btn-sm btn-icon" title="Pagina pubblica reparto">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                    <!-- Modifica -->
                                    <a href="<?= BASE_URL ?>/admin/reparti.php?edit=<?= $r['id'] ?>"
                                        class="btn btn-ghost btn-sm btn-icon" title="Modifica">
                                        <i class="fa fa-pen"></i>
                                    </a>
                                    <!-- Elimina -->
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                                        <button type="submit" class="btn btn-danger btn-sm btn-icon confirm-delete"
                                            title="Elimina" <?= $r['n_macchinari'] > 0 ? 'disabled style="opacity:.4; cursor:not-allowed;"' : '' ?>>
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