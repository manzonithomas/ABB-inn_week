<?php
// ============================================================
//  admin/qr_sheet.php — Selezione macchinari per foglio QR
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db = db();

$reparto_f = trim($_GET['reparto'] ?? '');
$search = trim($_GET['q'] ?? '');

$params = [];
$where = ['m.attivo = 1'];
if ($reparto_f !== '') {
    $where[] = 'r.nome = ?';
    $params[] = $reparto_f;
}
if ($search !== '') {
    $where[] = '(m.nome LIKE ? OR m.codice_seriale LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$stmt = $db->prepare("
    SELECT m.id, m.nome, m.codice_seriale, r.nome AS reparto_nome
    FROM macchinari m
    JOIN reparti r ON r.id = m.reparto_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY r.nome, m.nome
");
$stmt->execute($params);
$macchinari = $stmt->fetchAll();

$reparti = $db->query("SELECT id, nome FROM reparti ORDER BY nome")->fetchAll();

$per_reparto = [];
foreach ($macchinari as $m) {
    $per_reparto[$m['reparto_nome']][] = $m;
}

$page_title = 'Foglio QR';
$active_nav = 'qr_sheet';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<style>
    .mac-label {
        display: flex; align-items: center; gap: 14px;
        padding: 10px 20px; cursor: pointer;
        border-bottom: 1px solid #f5f5f5;
        transition: background .1s;
    }
    .mac-label:last-child { border-bottom: none; }
    .mac-label:hover { background: #fafafa; }
    .mac-label input[type="checkbox"] {
        width: 17px; height: 17px; flex-shrink: 0;
        cursor: pointer; accent-color: var(--red);
    }
    .mac-label.checked { background: #fff5f5; }
    .rep-header {
        display: flex; align-items: center; gap: 10px;
        padding: 11px 20px;
        background: #f4f4f4;
        border-bottom: 1px solid var(--border);
    }
    .rep-header input[type="checkbox"] {
        width: 17px; height: 17px; cursor: pointer; accent-color: var(--red);
    }
    .action-bar {
        position: sticky; top: 56px; z-index: 50;
        background: #fff;
        border-bottom: 2px solid var(--border);
        padding: 12px 20px;
        display: flex; align-items: center;
        justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,.06);
        margin-bottom: 16px;
    }
    .action-bar-left { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .count-badge {
        background: var(--red); color: #fff;
        font-size: .75rem; font-weight: 800;
        border-radius: 20px; padding: 2px 10px;
        min-width: 24px; text-align: center;
        display: none;
    }
    .count-badge.visible { display: inline-block; }
</style>

<div class="page-header">
    <h2>Foglio QR</h2>
    <div class="text-muted" style="font-size:.82rem; margin-top:2px;">
        Seleziona i macchinari e genera il PDF — 12 QR code per pagina A4
    </div>
</div>

<!-- Ricerca e filtro -->
<div class="page-card" style="padding:14px 20px; margin-bottom:16px;">
    <form method="GET" class="search-bar">
        <input type="text" name="q" class="form-control"
               placeholder="Cerca nome o seriale…"
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
                <a href="<?= BASE_URL ?>/admin/qr_sheet.php" class="btn btn-ghost btn-sm">
                    <i class="fa fa-xmark"></i> Reset
                </a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($macchinari)): ?>
        <div class="page-card">
            <div class="empty-state" style="padding:50px;">
                <i class="fa fa-qrcode" style="font-size:2.5rem; color:#ddd; display:block; margin-bottom:12px;"></i>
                <p>Nessun macchinario trovato.</p>
            </div>
        </div>
<?php else: ?>

    <form method="POST" action="<?= BASE_URL ?>/admin/qr_sheet_pdf.php" target="_blank" id="qr-form">

        <!-- Barra azioni sticky -->
        <div class="action-bar">
            <div class="action-bar-left">
                <button type="button" onclick="selezionaTutti(true)" class="btn btn-ghost btn-sm">
                    <i class="fa fa-check-double"></i> Tutti
                </button>
                <button type="button" onclick="selezionaTutti(false)" class="btn btn-ghost btn-sm">
                    <i class="fa fa-xmark"></i> Nessuno
                </button>
                <span id="count-badge" class="count-badge">0</span>
                <span id="count-text" class="text-muted" style="font-size:.85rem;">
                    Nessun macchinario selezionato
                </span>
            </div>
            <button type="submit" class="btn btn-primary" id="btn-genera" disabled>
                <i class="fa fa-file-pdf"></i> Genera PDF
            </button>
        </div>

        <!-- Lista macchinari per reparto -->
        <?php foreach ($per_reparto as $reparto_nome => $macs): ?>
                <div class="page-card" style="padding:0; margin-bottom:12px; overflow:hidden;">

                    <!-- Header reparto -->
                    <div class="rep-header">
                        <input type="checkbox" class="rep-check"
                               data-rep="<?= e($reparto_nome) ?>"
                               onchange="selezionaReparto(this)"
                               title="Seleziona tutto il reparto">
                        <span style="font-weight:700; font-size:.9rem;"><?= e($reparto_nome) ?></span>
                        <span class="text-muted" style="font-size:.78rem; margin-left:2px;">
                            <?= count($macs) ?> macchinari
                        </span>
                    </div>

                    <!-- Macchinari -->
                    <?php foreach ($macs as $m): ?>
                            <label class="mac-label" id="lbl-<?= $m['id'] ?>">
                                <input type="checkbox" name="ids[]" value="<?= $m['id'] ?>"
                                       class="mac-check" data-rep="<?= e($reparto_nome) ?>"
                                       onchange="aggiornaCont(); toggleLabel(this)">
                                <div style="flex:1; min-width:0;">
                                    <div style="font-weight:600; font-size:.9rem;">
                                        <?= e($m['nome']) ?>
                                    </div>
                                    <div style="font-size:.75rem; color:#aaa; font-family:monospace;">
                                        <?= e($m['codice_seriale']) ?>
                                    </div>
                                </div>
                                <i class="fa fa-qrcode" style="color:#e0e0e0; font-size:1.1rem;" id="ico-<?= $m['id'] ?>"></i>
                            </label>
                    <?php endforeach; ?>
                </div>
        <?php endforeach; ?>

    </form>

    <script>
    function aggiornaCont() {
        const checked = document.querySelectorAll('.mac-check:checked');
        const n = checked.length;
        const badge = document.getElementById('count-badge');
        const text  = document.getElementById('count-text');
        const btn   = document.getElementById('btn-genera');

        badge.textContent = n;
        badge.classList.toggle('visible', n > 0);
        text.textContent  = n > 0
            ? n + ' macchinari selezionat' + (n === 1 ? 'o' : 'i')
            : 'Nessun macchinario selezionato';
        btn.disabled = n === 0;

        // Aggiorna stato checkbox reparto
        document.querySelectorAll('.rep-check').forEach(rc => {
            const rep     = rc.dataset.rep;
            const all     = document.querySelectorAll(`.mac-check[data-rep="${CSS.escape(rep)}"]`);
            const chk     = document.querySelectorAll(`.mac-check[data-rep="${CSS.escape(rep)}"]:checked`);
            rc.checked       = chk.length === all.length && all.length > 0;
            rc.indeterminate = chk.length > 0 && chk.length < all.length;
        });
    }

    function toggleLabel(cb) {
        const lbl = document.getElementById('lbl-' + cb.value);
        const ico = document.getElementById('ico-' + cb.value);
        if (lbl) lbl.classList.toggle('checked', cb.checked);
        if (ico) ico.style.color = cb.checked ? 'var(--red)' : '#e0e0e0';
    }

    function selezionaReparto(repCheck) {
        const rep = repCheck.dataset.rep;
        document.querySelectorAll(`.mac-check[data-rep="${CSS.escape(rep)}"]`)
                .forEach(cb => { cb.checked = repCheck.checked; toggleLabel(cb); });
        aggiornaCont();
    }

    function selezionaTutti(val) {
        document.querySelectorAll('.mac-check').forEach(cb => { cb.checked = val; toggleLabel(cb); });
        document.querySelectorAll('.rep-check').forEach(rc => {
            rc.checked = val; rc.indeterminate = false;
        });
        aggiornaCont();
    }
    </script>

<?php endif; ?>

<?php require dirname(__DIR__) . '/includes/footer_admin.php'; ?>