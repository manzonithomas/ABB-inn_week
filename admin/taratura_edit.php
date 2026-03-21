<?php
// ============================================================
//  admin/taratura_edit.php — Aggiungi / Modifica taratura
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db = db();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$is_new = ($id === 0);

// Macchinario preselezionato (quando si arriva da macchinari.php)
$presel_mac = isset($_GET['macchinario_id']) ? (int) $_GET['macchinario_id'] : 0;

// ---- Carica taratura esistente ----
$t = [
    'macchinario_id' => $presel_mac,
    'data_inserimento' => date('Y-m-d'),
    'data_scadenza' => '',
    'tecnico' => '',
    'ente_certificatore' => '',
    'numero_certificato' => '',
    'esito' => 'conforme',
    'note' => '',
    'pdf_path' => '',
];

if (!$is_new) {
    $stmt = $db->prepare("SELECT * FROM tarature WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flashSet('error', 'Taratura non trovata.');
        redirect(BASE_URL . '/admin/tarature.php');
    }
    $t = $row;
}

// ---- Macchinari per il select ----
$macchinari = $db->query("
    SELECT m.id, m.nome, m.codice_seriale, r.nome AS reparto_nome
    FROM macchinari m
    JOIN reparti r ON r.id = m.reparto_id
    WHERE m.attivo = 1
    ORDER BY r.nome, m.nome
")->fetchAll();

// ---- POST: salva ----
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $t['macchinario_id'] = (int) ($_POST['macchinario_id'] ?? 0);
    $t['data_inserimento'] = trim($_POST['data_inserimento'] ?? '');
    $t['data_scadenza'] = trim($_POST['data_scadenza'] ?? '');
    $t['tecnico'] = trim($_POST['tecnico'] ?? '');
    $t['ente_certificatore'] = trim($_POST['ente_certificatore'] ?? '');
    $t['numero_certificato'] = trim($_POST['numero_certificato'] ?? '');
    $t['esito'] = $_POST['esito'] === 'non_conforme' ? 'non_conforme' : 'conforme';
    $t['note'] = trim($_POST['note'] ?? '');

    // Validazione
    if ($t['macchinario_id'] === 0)
        $errors[] = 'Seleziona un macchinario.';
    if ($t['data_inserimento'] === '')
        $errors[] = 'La data di inserimento è obbligatoria.';
    if ($t['data_scadenza'] === '')
        $errors[] = 'La data di scadenza è obbligatoria.';
    if ($t['tecnico'] === '')
        $errors[] = 'Il nome del tecnico è obbligatorio.';

    // Valida date
    if (!empty($t['data_inserimento']) && !empty($t['data_scadenza'])) {
        if (strtotime($t['data_scadenza']) <= strtotime($t['data_inserimento'])) {
            $errors[] = 'La data di scadenza deve essere successiva alla data di inserimento.';
        }
    }

    // Gestione PDF
    $pdf_path = $t['pdf_path']; // mantieni vecchio path di default
    $has_new_pdf = isset($_FILES['pdf']) && $_FILES['pdf']['error'] !== UPLOAD_ERR_NO_FILE;

    if ($is_new && !$has_new_pdf) {
        $errors[] = 'Il file PDF della taratura è obbligatorio.';
    }

    if ($has_new_pdf && empty($errors)) {
        try {
            // Elimina vecchio PDF se esiste
            if ($pdf_path && file_exists(ROOT_DIR . '/' . $pdf_path)) {
                @unlink(ROOT_DIR . '/' . $pdf_path);
            }
            $pdf_path = savePdf($_FILES['pdf']);
        } catch (RuntimeException $e) {
            $errors[] = $e->getMessage();
        }
    }

    if (empty($errors)) {
        if ($is_new) {
            $stmt = $db->prepare("
                INSERT INTO tarature
                    (macchinario_id, data_inserimento, data_scadenza, tecnico,
                     ente_certificatore, numero_certificato, esito, note, pdf_path, notifica_inviata)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([
                $t['macchinario_id'],
                $t['data_inserimento'],
                $t['data_scadenza'],
                $t['tecnico'],
                $t['ente_certificatore'],
                $t['numero_certificato'],
                $t['esito'],
                $t['note'],
                $pdf_path
            ]);
            flashSet('success', 'Taratura inserita con successo.');
        } else {
            $stmt = $db->prepare("
                UPDATE tarature SET
                    macchinario_id = ?, data_inserimento = ?, data_scadenza = ?,
                    tecnico = ?, ente_certificatore = ?, numero_certificato = ?,
                    esito = ?, note = ?, pdf_path = ?, notifica_inviata = 0
                WHERE id = ?
            ");
            $stmt->execute([
                $t['macchinario_id'],
                $t['data_inserimento'],
                $t['data_scadenza'],
                $t['tecnico'],
                $t['ente_certificatore'],
                $t['numero_certificato'],
                $t['esito'],
                $t['note'],
                $pdf_path,
                $id
            ]);
            flashSet('success', 'Taratura aggiornata.');
        }
        redirect(BASE_URL . '/admin/tarature.php');
    }
}

$page_title = $is_new ? 'Nuova taratura' : 'Modifica taratura';
$active_nav = 'tarature';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<div style="max-width: 820px;">
    <!-- Breadcrumb -->
    <div style="margin-bottom:16px; font-size:.85rem; color:#888;">
        <a href="<?= BASE_URL ?>/admin/tarature.php" style="color:#888; text-decoration:none;">
            <i class="fa fa-clipboard-check"></i> Tarature
        </a>
        &rsaquo; <?= $is_new ? 'Nuova' : 'Modifica' ?>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="flash-msg flash-error">
            <?php foreach ($errors as $er): ?>         <?= e($er) ?><br><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="page-card">
        <h2 style="margin:0 0 24px; font-size:1.2rem; font-weight:800;">
            <?= $is_new
                ? '<i class="fa fa-plus-circle" style="color:var(--red)"></i> Nuova taratura'
                : '<i class="fa fa-pen" style="color:var(--red)"></i> Modifica taratura' ?>
        </h2>

        <form method="POST" enctype="multipart/form-data">

            <!-- Macchinario -->
            <div class="form-group">
                <label class="form-label" for="macchinario_id">Macchinario *</label>
                <select id="macchinario_id" name="macchinario_id" class="form-control" required
                    onchange="calcolaScadenza()">
                    <option value="">— Seleziona macchinario —</option>
                    <?php
                    $cur_rep = '';
                    foreach ($macchinari as $mac):
                        if ($mac['reparto_nome'] !== $cur_rep) {
                            if ($cur_rep !== '')
                                echo '</optgroup>';
                            echo '<optgroup label="' . e($mac['reparto_nome']) . '">';
                            $cur_rep = $mac['reparto_nome'];
                        }
                        ?>
                        <option value="<?= $mac['id'] ?>" data-intervallo="<?= /* ci serve per calcolo scadenza automatico */
                              $db->prepare("SELECT intervallo_mesi FROM macchinari WHERE id=?")
                                  ->execute([$mac['id']]) ?>" <?= (string) $t['macchinario_id'] === (string) $mac['id'] ? 'selected' : '' ?>>
                            <?= e($mac['nome']) ?> — <?= e($mac['codice_seriale']) ?>
                        </option>
                    <?php endforeach;
                    if ($cur_rep)
                        echo '</optgroup>'; ?>
                </select>
            </div>

            <!-- Date -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="data_inserimento">Data inserimento / esecuzione *</label>
                    <input type="date" id="data_inserimento" name="data_inserimento" class="form-control"
                        value="<?= e($t['data_inserimento']) ?>" required onchange="calcolaScadenza()">
                </div>
                <div class="form-group">
                    <label class="form-label" for="data_scadenza">Data scadenza *</label>
                    <input type="date" id="data_scadenza" name="data_scadenza" class="form-control"
                        value="<?= e($t['data_scadenza']) ?>" required>
                    <div class="form-hint" id="scadenza-hint">Calcolata automaticamente in base all'intervallo del
                        macchinario.</div>
                </div>
            </div>

            <!-- Tecnico / Ente -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="tecnico">Tecnico esecutore *</label>
                    <input type="text" id="tecnico" name="tecnico" class="form-control" value="<?= e($t['tecnico']) ?>"
                        required placeholder="Nome e cognome del tecnico">
                </div>
                <div class="form-group">
                    <label class="form-label" for="ente_certificatore">Ente certificatore</label>
                    <input type="text" id="ente_certificatore" name="ente_certificatore" class="form-control"
                        value="<?= e($t['ente_certificatore'] ?? '') ?>"
                        placeholder="es. SIT, ACCREDIA, laboratorio interno…">
                </div>
            </div>

            <!-- Numero cert / Esito -->
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="numero_certificato">Numero certificato</label>
                    <input type="text" id="numero_certificato" name="numero_certificato" class="form-control"
                        value="<?= e($t['numero_certificato'] ?? '') ?>" placeholder="es. 2024/TAR/001"
                        style="font-family:monospace;">
                </div>
                <div class="form-group">
                    <label class="form-label" for="esito">Esito taratura *</label>
                    <select id="esito" name="esito" class="form-control">
                        <option value="conforme" <?= $t['esito'] === 'conforme' ? 'selected' : '' ?>>✓ Conforme</option>
                        <option value="non_conforme" <?= $t['esito'] === 'non_conforme' ? 'selected' : '' ?>>✕ Non conforme
                        </option>
                    </select>
                </div>
            </div>

            <!-- Note -->
            <div class="form-group">
                <label class="form-label" for="note">Note / Osservazioni</label>
                <textarea id="note" name="note" class="form-control" rows="3"
                    placeholder="Eventuali osservazioni sulla taratura…"><?= e($t['note'] ?? '') ?></textarea>
            </div>

            <!-- Upload PDF -->
            <div class="form-group">
                <label class="form-label" for="pdf">
                    Certificato PDF <?= $is_new ? '*' : '(lascia vuoto per mantenere il precedente)' ?>
                </label>
                <?php if (!$is_new && $t['pdf_path']): ?>
                    <div
                        style="margin-bottom:10px; padding:10px 14px; background:#fafafa; border:1px solid #e0e0e0; display:flex; align-items:center; gap:10px;">
                        <i class="fa fa-file-pdf" style="color:var(--red); font-size:1.2rem;"></i>
                        <span style="font-size:.88rem;">PDF attuale:
                            <a href="<?= BASE_URL ?>/<?= e($t['pdf_path']) ?>" target="_blank"
                                style="color:var(--red); font-weight:700;">
                                Visualizza &nearr;
                            </a>
                        </span>
                    </div>
                <?php endif; ?>
                <input type="file" id="pdf" name="pdf" class="form-control" accept="application/pdf" <?= $is_new ? 'required' : '' ?>>
                <div class="form-hint">Solo file PDF. Dimensione massima: 10 MB.</div>
            </div>

            <hr class="divider">
            <div style="display:flex; gap:10px; justify-content:flex-end; flex-wrap:wrap;">
                <a href="<?= BASE_URL ?>/admin/tarature.php" class="btn btn-ghost">Annulla</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-floppy-disk"></i>
                    <?= $is_new ? 'Inserisci taratura' : 'Salva modifiche' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Calcolo automatico data scadenza
    const machineries = <?= json_encode(
        array_column(
            $db->query("SELECT id, intervallo_mesi FROM macchinari WHERE attivo=1")->fetchAll(),
            'intervallo_mesi',
            'id'
        )
    ) ?>;

    function calcolaScadenza() {
        document.addEventListener('DOMContentLoaded', function () {
            const scad = document.getElementById('data_scadenza').value;
            if (!scad) calcolaScadenza();
        });
        const macId = document.getElementById('macchinario_id').value;
        const datIns = document.getElementById('data_inserimento').value;
        if (!macId || !datIns) return;
        const mesi = machineries[macId];
        if (!mesi) return;
        const d = new Date(datIns);
        d.setMonth(d.getMonth() + parseInt(mesi));
        const yyyy = d.getFullYear();
        const mm = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        document.getElementById('data_scadenza').value = `${yyyy}-${mm}-${dd}`;
    }
</script>

<?php require dirname(__DIR__) . '/includes/footer_admin.php'; ?>