<?php
// ============================================================
//  admin/impostazioni.php — Impostazioni sistema
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db     = db();
$admin  = $db->query("SELECT * FROM admin WHERE id = 1")->fetch();
$errors_pw  = [];
$errors_cfg = [];
$success_pw  = false;
$success_cfg = false;

// ---- POST: cambio password ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'password') {
    $pw_current = $_POST['pw_current'] ?? '';
    $pw_new     = $_POST['pw_new']     ?? '';
    $pw_confirm = $_POST['pw_confirm'] ?? '';

    if (!password_verify($pw_current, $admin['password_hash'])) {
        $errors_pw[] = 'La password attuale non è corretta.';
    }
    if (strlen($pw_new) < 8) {
        $errors_pw[] = 'La nuova password deve essere di almeno 8 caratteri.';
    }
    if ($pw_new !== $pw_confirm) {
        $errors_pw[] = 'Le due nuove password non coincidono.';
    }
    if (empty($errors_pw)) {
        $hash = password_hash($pw_new, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("UPDATE admin SET password_hash = ? WHERE id = 1")->execute([$hash]);
        flashSet('success', 'Password aggiornata con successo.');
        redirect(BASE_URL . '/admin/impostazioni.php');
    }
}

// ---- POST: impostazioni email ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'config') {
    $email    = trim($_POST['email'] ?? '');
    $giorni   = max(1, min(365, (int)($_POST['giorni_preavviso'] ?? 30)));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors_cfg[] = 'Indirizzo email non valido.';
    }
    if (empty($errors_cfg)) {
        $db->prepare("UPDATE admin SET email = ?, giorni_preavviso = ? WHERE id = 1")
           ->execute([$email, $giorni]);
        $admin['email']            = $email;
        $admin['giorni_preavviso'] = $giorni;
        flashSet('success', 'Impostazioni salvate.');
        redirect(BASE_URL . '/admin/impostazioni.php');
    }
}

$page_title = 'Impostazioni';
$active_nav = 'impostazioni';
require dirname(__DIR__) . '/includes/header_admin.php';
?>

<div style="max-width: 700px;">

    <!-- Email / Notifiche -->
    <div class="page-card" style="margin-bottom:20px;">
        <h2 style="margin:0 0 20px; font-size:1.1rem; font-weight:800;">
            <i class="fa fa-envelope" style="color:var(--red)"></i> Notifiche email
        </h2>

        <?php if (!empty($errors_cfg)): ?>
            <div class="flash-msg flash-error">
                <?php foreach ($errors_cfg as $e): ?><?= e($e) ?><br><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="config">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="email">Email destinatario notifiche</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= e($admin['email']) ?>" required>
                    <div class="form-hint">A questo indirizzo arriveranno gli alert di scadenza taratura.</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="giorni_preavviso">Giorni di preavviso</label>
                    <input type="number" id="giorni_preavviso" name="giorni_preavviso" class="form-control"
                           value="<?= (int)$admin['giorni_preavviso'] ?>" min="1" max="365" required>
                    <div class="form-hint">Invia la notifica X giorni prima della scadenza.</div>
                </div>
            </div>

            <div style="background:#f0f8ff; border:1px solid #bee3f8; padding:12px 16px; margin-bottom:18px; font-size:.85rem; color:#2c5f8a;">
                <strong><i class="fa fa-circle-info"></i> Configurazione PaperCut SMTP</strong><br>
                Le email vengono inviate tramite <code>cron/notifiche.php</code> via PHPMailer.<br>
                PaperCut ascolta di default su <code>localhost:25</code>. Configura le credenziali nel file <code>cron/notifiche.php</code>.
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-floppy-disk"></i> Salva impostazioni
                </button>
            </div>
        </form>
    </div>

    <!-- Cambio password -->
    <div class="page-card">
        <h2 style="margin:0 0 20px; font-size:1.1rem; font-weight:800;">
            <i class="fa fa-lock" style="color:var(--red)"></i> Cambio password
        </h2>

        <?php if (!empty($errors_pw)): ?>
            <div class="flash-msg flash-error">
                <?php foreach ($errors_pw as $e): ?><?= e($e) ?><br><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="action" value="password">
            <div class="form-group">
                <label class="form-label" for="pw_current">Password attuale</label>
                <input type="password" id="pw_current" name="pw_current" class="form-control"
                       autocomplete="current-password" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="pw_new">Nuova password</label>
                    <input type="password" id="pw_new" name="pw_new" class="form-control"
                           autocomplete="new-password" required minlength="8">
                    <div class="form-hint">Minimo 8 caratteri.</div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="pw_confirm">Conferma nuova password</label>
                    <input type="password" id="pw_confirm" name="pw_confirm" class="form-control"
                           autocomplete="new-password" required minlength="8">
                </div>
            </div>
            <div style="display:flex; justify-content:flex-end;">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-key"></i> Aggiorna password
                </button>
            </div>
        </form>
    </div>

    <!-- Info sistema -->
    <div class="page-card" style="background:#fafafa; margin-top:20px;">
        <h2 style="margin:0 0 14px; font-size:.9rem; font-weight:800; text-transform:uppercase; letter-spacing:.5px; color:#888;">
            Informazioni sistema
        </h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:.85rem; color:#555;">
            <div><strong>PHP:</strong> <?= PHP_VERSION ?></div>
            <div><strong>GD:</strong> <?= function_exists('imagepng') ? '✓ Disponibile' : '✗ Non disponibile' ?></div>
            <div><strong>phpqrcode:</strong> <?= file_exists(dirname(__DIR__).'/lib/phpqrcode/qrlib.php') ? '✓ Presente' : '✗ Non installato' ?></div>
            <div><strong>PHPMailer:</strong> <?= file_exists(dirname(__DIR__).'/vendor/autoload.php') ? '✓ Installato' : '✗ Esegui composer install' ?></div>
            <div><strong>Upload dir:</strong> <?= is_writable(UPLOAD_DIR) ? '✓ Scrivibile' : '✗ Non scrivibile' ?></div>
            <div><strong>DB:</strong> <?= DB_NAME ?></div>
        </div>
    </div>
</div>

<?php require dirname(__DIR__) . '/includes/footer_admin.php'; ?>
