<?php
// ============================================================
//  config.php — Configurazione globale
// ============================================================

// --- Database ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'calibration_manager');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// --- URL base (dinamico, senza trailing slash) ---
define('BASE_URL', rtrim(
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . dirname($_SERVER['SCRIPT_NAME']),
    '/admin/public/cron'
));

// --- Filesystem ---
define('ROOT_DIR', __DIR__);
define('UPLOAD_DIR', ROOT_DIR . '/uploads/tarature/');
define('UPLOAD_URL', BASE_URL . '/uploads/tarature/');

// --- Limiti upload PDF ---
define('MAX_PDF_SIZE', 10 * 1024 * 1024); // 10 MB

// ============================================================
//  Connessione PDO (singleton)
// ============================================================
function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $pdo->exec("SET NAMES utf8mb4 COLLATE utf8mb4_general_ci");
        } catch (PDOException $e) {
            // In produzione: loggare l'errore, non mostrarlo
            die('<p style="font-family:monospace;color:red;">Errore DB: ' . htmlspecialchars($e->getMessage()) . '</p>');
        }
    }
    return $pdo;
}

// ============================================================
//  Helpers
// ============================================================

/** Escape HTML — usare sempre per output utente */
function e(mixed $val): string
{
    return htmlspecialchars((string) $val, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirect e stop */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Formatta data DB (Y-m-d) → italiano (d/m/Y) */
function fmtDate(?string $d): string
{
    if (!$d)
        return '—';
    return date('d/m/Y', strtotime($d));
}

/** Genera token casuale sicuro (hex) */
function generateToken(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}

/** Classe CSS W3 e label per stato scadenza */
function statoInfo(string $stato): array
{
    return match ($stato) {
        'scaduta' => ['class' => 'stato-scaduta', 'label' => 'Scaduta', 'icon' => '✕'],
        'in_scadenza' => ['class' => 'stato-in-scadenza', 'label' => 'In scadenza', 'icon' => '!'],
        'valida' => ['class' => 'stato-valida', 'label' => 'Valida', 'icon' => '✓'],
        default => ['class' => 'stato-nd', 'label' => 'N/D', 'icon' => '?'],
    };
}

/** Flash message: salva in sessione */
function flashSet(string $type, string $msg): void
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

/** Flash message: leggi e cancella */
function flashGet(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/** Render flash HTML */
function flashRender(): void
{
    $f = flashGet();
    if (!$f)
        return;
    $cls = $f['type'] === 'success' ? 'flash-success' : 'flash-error';
    echo '<div class="flash-msg ' . $cls . '">' . e($f['msg']) . '</div>';
}

/** Verifica estensione e MIME del PDF caricato */
function validatePdf(array $file): string|bool
{
    if ($file['error'] !== UPLOAD_ERR_OK)
        return 'Errore durante il caricamento del file.';
    if ($file['size'] > MAX_PDF_SIZE)
        return 'Il file supera il limite di 10 MB.';
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf')
        return 'Sono accettati solo file PDF.';
    $mime = mime_content_type($file['tmp_name']);
    if ($mime !== 'application/pdf')
        return 'Il file non è un PDF valido.';
    return true;
}

/** Salva PDF caricato, ritorna il path relativo o lancia eccezione */
function savePdf(array $file): string
{
    $v = validatePdf($file);
    if ($v !== true)
        throw new RuntimeException($v);
    if (!is_dir(UPLOAD_DIR))
        mkdir(UPLOAD_DIR, 0755, true);
    $filename = generateToken(16) . '.pdf';
    $dest = UPLOAD_DIR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new RuntimeException('Impossibile salvare il file. Controlla i permessi della cartella uploads/.');
    }
    return 'uploads/tarature/' . $filename;
}