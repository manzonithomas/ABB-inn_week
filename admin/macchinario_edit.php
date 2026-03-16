<?php
// ============================================================
//  admin/macchinario_edit.php — Aggiungi / Modifica macchinario
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db     = db();
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_new = ($id === 0);

// ---- Carica dati esistenti ----
$m = [
    'nome'            => '',
    'codice_seriale'  => '',
    'reparto_id'      => '',
    'tipo_categoria'  => '',
    'unita_misura'    => '',
    'intervallo_mesi' => 12,
];

if (!$is_new) {
    $stmt = $db->prepare("SELECT * FROM macchinari WHERE id = ? AND attivo = 1");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flashSet('error', 'Macchinario non trovato.');
        redirect(BASE_URL . '/admin/macchinari.php');
    }
    $m = $row;
}

// ---- Reparti ----
$reparti = $db->query("SELECT id, nome FROM reparti ORDER BY nome")->fetchAll();

// ---- POST: salva ----
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $m['nome']            = trim($_POST['nome'] ?? '');
    $m['codice_seriale']  = trim($_POST['codice_seriale'] ?? '');
    $m['reparto_id']      = (int)($_POST['reparto_id'] ?? 0);
    $m['tipo_categoria']  = trim($_POST['tipo_categoria'] ?? '');
    $m['unita_misura']    = trim($_POST['unita_misura'] ?? '');
    $m['intervallo_mesi'] = max(1, (int)($_POST['intervallo_mesi'] ?? 12));

    // Validazione
    if ($m['nome'] === '')           $errors[] = 'Il nome del macchinario è obbligatorio.';
    if ($m['codice_seriale'] === '') $errors[] = 'Il codice seriale è obbligatorio.';
    if ($m['reparto_id'] === 0)      $errors[] = 'Seleziona un reparto.';

    // Controlla unicità seriale
    if (empty($errors)) {
        $chk = $db->prepare("SELECT id FROM macchinari WHERE codice_seriale = ? AND id != ?");
        $chk->execute([$m['codice_seriale'], $id]);
        if ($chk->fetch()) $errors[] = 'Il codice seriale è già utilizzato da un altro macchinario.';
    }

    if (empty($errors)) {
        if ($is_new) {
            $token = generateToken(32);
            $stmt  = $db->prepare("
                INSERT INTO macchinari
                    (nome, codice_seriale, reparto_id, tipo_categoria, unita_misura, intervallo_mesi, qr_token)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $m['nome'], $m['codice_seriale'], $m['reparto_id'],
                $m['tipo_categoria'], $m['unita_misura'], $m['intervallo_mesi'], $token
            ]);
            flashSet('success', 'Macchinario "' . $m['nome'] . '" aggiunto con successo.');
        } else {
            $stmt = $db->prepare("
                UPDATE macchinari SET
                    nome = ?, codice_seriale = ?, reparto_id = ?,
                    tipo_categoria = ?, unita_misura = ?, intervallo_mesi = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $m['nome'], $m['codice_seriale'], $m['reparto_id'],
                $m['tipo_categoria'], $m['unita_misura'], $m['intervallo_mesi'], $id
            ]);
            flashSet('success', 'Macchinario aggiornato.');
        }
        redirect(BASE_URL . '/admin/macchinari.php');
    }
}

$page_title = $is_new ? 'Nuovo macchinario' : 'Modifica macchinario';
$active_nav = 'macchinari';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<div style="max-width: 760px;">
    <!-- Breadcrumb -->
    <div style="margin-bottom:16px; font-size:.85rem; color:#888;">
        <a href="<?= BASE_URL ?>/admin/macchinari.php" style="color:#888; text-decoration:none;">
            <i class="fa fa-gears"></i> Macchinari
        </a>
        &rsaquo; <?= $is_new ? 'Nuovo' : e($m['nome']) ?>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="flash-msg flash-error">
            <?php foreach ($errors as $er): ?><?= e($er) ?><br><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="page-card">
        <h2 style="margin:0 0 24px; font-size:1.2rem; font-weight:800;">
            <?= $is_new ? '<i class="fa fa-plus-circle" style="color:var(--red)"></i> Nuovo macchinario' : '<i class="fa fa-pen" style="color:var(--red)"></i> Modifica macchinario' ?>
        </h2>

        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="nome">Nome macchinario *</label>
                    <input type="text" id="nome" name="nome" class="form-control"
                           value="<?= e($m['nome']) ?>" required autofocus
                           placeholder="es. Manometro banco 3">
                </div>
                <div class="form-group">
                    <label class="form-label" for="codice_seriale">Codice seriale *</label>
                    <input type="text" id="codice_seriale" name="codice_seriale" class="form-control"
                           value="<?= e($m['codice_seriale']) ?>" required
                           placeholder="es. ABB-2024-001"
                           style="font-family:monospace; font-size:1rem;">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="reparto_id">Reparto *</label>
                    <select id="reparto_id" name="reparto_id" class="form-control" required>
                        <option value="">— Seleziona reparto —</option>
                        <?php foreach ($reparti as $r): ?>
                            <option value="<?= $r['id'] ?>"
                                <?= (string)$m['reparto_id'] === (string)$r['id'] ? 'selected' : '' ?>>
                                <?= e($r['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="tipo_categoria">Tipo / Categoria</label>
                    <input type="text" id="tipo_categoria" name="tipo_categoria" class="form-control"
                           value="<?= e($m['tipo_categoria'] ?? '') ?>"
                           placeholder="es. Manometro, Sensore temperatura…">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="unita_misura">Unità di misura</label>
                    <input type="text" id="unita_misura" name="unita_misura" class="form-control"
                           value="<?= e($m['unita_misura'] ?? '') ?>"
                           placeholder="es. bar, °C, kV, A…">
                </div>
                <div class="form-group">
                    <label class="form-label" for="intervallo_mesi">Intervallo taratura (mesi) *</label>
                    <input type="number" id="intervallo_mesi" name="intervallo_mesi" class="form-control"
                           value="<?= (int)$m['intervallo_mesi'] ?>" min="1" max="120" required>
                    <div class="form-hint">Ogni quanti mesi va rifatta la taratura.</div>
                </div>
            </div>

            <?php if (!$is_new): ?>
                <div style="background:#f8f8f8; border:1px solid #e0e0e0; padding:12px 16px; margin-bottom:20px; font-size:.85rem; color:#666;">
                    <i class="fa fa-qrcode"></i>
                    Il QR code di questo macchinario è già generato.
                    <a href="<?= BASE_URL ?>/admin/qr_download.php?id=<?= $id ?>" style="color:var(--red); font-weight:700; margin-left:6px;">
                        Scarica QR &darr;
                    </a>
                </div>
            <?php endif; ?>

            <hr class="divider">
            <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                <a href="<?= BASE_URL ?>/admin/macchinari.php" class="btn btn-ghost">
                    Annulla
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-floppy-disk"></i>
                    <?= $is_new ? 'Crea macchinario' : 'Salva modifiche' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer_admin.php'; ?>
