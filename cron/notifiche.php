<?php
// ============================================================
//  cron/notifiche.php — Script per invio email di scadenza
//
//  Esecuzione:
//    php cron/notifiche.php
//
//  Cron job consigliato (ogni giorno alle 07:00):
//    0 7 * * * php /var/www/html/calibration_manager/cron/notifiche.php >> /var/log/calibration_notifiche.log 2>&1
//
//  Dipendenze:
//    composer require phpmailer/phpmailer
// ============================================================

// CLI only check (opzionale: commenta per debug da browser)
// if (php_sapi_name() !== 'cli') { http_response_code(403); exit('CLI only.'); }

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/queries.php';

// Carica Composer autoload (PHPMailer)
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!file_exists($autoload)) {
  echo "[ERRORE] vendor/autoload.php non trovato. Esegui: composer install\n";
  exit(1);
}
require_once $autoload;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ============================================================
//  CONFIGURAZIONE SMTP
//  PaperCut per sviluppo locale:
//    SMTP_HOST = 'localhost' (o 127.0.0.1)
//    SMTP_PORT = 25 (porta default PaperCut)
//    SMTP_AUTH = false (PaperCut non richiede autenticazione)
//
//  Per produzione: cambia le costanti qui sotto con le
//  credenziali del server SMTP reale (es. Gmail, SendGrid...)
// ============================================================
define('SMTP_HOST', 'localhost');
define('SMTP_PORT', 25);
define('SMTP_AUTH', false);
define('SMTP_USER', '');
define('SMTP_PASS', '');
define('SMTP_FROM', 'calibration@abbdalmine.local');
define('SMTP_NAME', 'ABB Calibration Manager');
define('SMTP_SECURE', '');  // '' per nessuna crittografia, 'tls' per STARTTLS, 'ssl' per SSL
define('APP_URL', 'http://localhost/thomas/Abb-tarature');

// ============================================================
//  MAIN
// ============================================================

$db = db();
$now = date('Y-m-d H:i:s');

echo "[{$now}] Avvio script notifiche scadenza tarature\n";

// Legge tarature in scadenza non ancora notificate
$tarature = $db->query("SELECT * FROM ( " . sql_tarature_in_scadenza() . " )")->fetchAll();

if (empty($tarature)) {
  echo "[OK] Nessuna taratura in scadenza da notificare.\n";
  exit(0);
}

echo "[INFO] Trovate " . count($tarature) . " taratura/e da notificare.\n";

$ok = 0;
$err = 0;

foreach ($tarature as $t) {
  $result = inviaEmail($t);

  if ($result === true) {
    // Segna notifica inviata
    $db->prepare("UPDATE tarature SET notifica_inviata = 1 WHERE id = ?")
      ->execute([$t['taratura_id']]);
    echo "[OK] Email inviata per '{$t['macchinario_nome']}' a {$t['email_admin']}\n";
    $ok++;
  } else {
    echo "[ERR] Errore per '{$t['macchinario_nome']}': {$result}\n";
    $err++;
  }
}

echo "[FINE] Completato. OK: {$ok}, Errori: {$err}\n";

// ============================================================
//  Funzione invio singola email
// ============================================================
function inviaEmail(array $t): bool|string
{
  $mail = new PHPMailer(true);

  try {
    // --- Server SMTP ---
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->Port = SMTP_PORT;
    $mail->SMTPAuth = SMTP_AUTH;
    if (SMTP_AUTH) {
      $mail->Username = SMTP_USER;
      $mail->Password = SMTP_PASS;
    }
    if (SMTP_SECURE !== '') {
      $mail->SMTPSecure = SMTP_SECURE;
    } else {
      $mail->SMTPAutoTLS = false;
    }
    $mail->CharSet = 'UTF-8';

    // --- Mittente / Destinatario ---
    $mail->setFrom(SMTP_FROM, SMTP_NAME);
    $mail->addAddress($t['email_admin']);
    $mail->addReplyTo(SMTP_FROM, SMTP_NAME);

    // --- Soggetto ---
    $giorni = (int) $t['giorni_rimanenti'];
    $urgency = $giorni <= 7 ? '🔴 URGENTE — ' : '⚠️ ';
    $mail->Subject = "{$urgency}Taratura in scadenza: {$t['macchinario_nome']} ({$giorni} giorni)";

    // --- Corpo HTML ---
    $mail->isHTML(true);
    $mail->Body = buildEmailHtml($t);
    $mail->AltBody = buildEmailText($t);

    $mail->send();
    return true;

  } catch (Exception $e) {
    return $mail->ErrorInfo;
  }
}

// ============================================================
//  Template email HTML
// ============================================================
function buildEmailHtml(array $t): string
{
  $giorni = (int) $t['giorni_rimanenti'];
  $color = $giorni <= 7 ? '#FF000F' : '#f57f17';
  $label = $giorni <= 7 ? 'URGENTE' : 'ATTENZIONE';
  $scadenza = date('d/m/Y', strtotime($t['data_scadenza']));
  $url_mac = ''; // opzionale: aggiungere link alla pagina pubblica

  return '<!DOCTYPE html>
<html lang="it">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"></head>
<body style="margin:0; padding:0; background:#f0f0f0; font-family:Arial, sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0f0; padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#fff; max-width:600px; width:100%;">

      <!-- Header ABB -->
      <tr>
        <td style="background:#1A1A1A; padding:20px 28px; border-bottom:4px solid #FF000F;">
          <span style="background:#FF000F; color:#fff; font-size:1.4rem; font-weight:900;
                       letter-spacing:-1px; padding:2px 10px; display:inline-block;">ABB</span>
          <span style="color:#888; font-size:.8rem; margin-left:10px; vertical-align:middle;">
            Calibration Manager — ABB S.p.A. Dalmine
          </span>
        </td>
      </tr>

      <!-- Alert banner -->
      <tr>
        <td style="background:' . $color . '; padding:14px 28px; color:#fff;">
          <strong style="font-size:1rem; text-transform:uppercase; letter-spacing:.5px;">
            ⚠ ' . htmlspecialchars($label) . ': Taratura in scadenza
          </strong>
        </td>
      </tr>

      <!-- Corpo -->
      <tr>
        <td style="padding:28px;">
          <p style="margin:0 0 20px; font-size:1rem; color:#333;">
            La seguente taratura è in scadenza tra <strong style="color:' . $color . ';">' . $giorni . ' giorni</strong>:
          </p>

          <!-- Card macchinario -->
          <table width="100%" cellpadding="0" cellspacing="0"
                 style="background:#f8f8f8; border:1px solid #e0e0e0; border-left:4px solid ' . $color . '; margin-bottom:20px;">
            <tr><td style="padding:16px 18px;">
              <p style="margin:0 0 12px; font-size:1.1rem; font-weight:700; color:#1A1A1A;">
                ' . htmlspecialchars($t['macchinario_nome']) . '
              </p>
              <table width="100%" cellpadding="4" cellspacing="0" style="font-size:.88rem; color:#555;">
                <tr><td width="40%"><strong>Reparto</strong></td><td>' . htmlspecialchars($t['reparto']) . '</td></tr>
                <tr><td><strong>Data scadenza</strong></td>
                    <td style="color:' . $color . '; font-weight:700;">' . $scadenza . '</td></tr>
                <tr><td><strong>Giorni rimanenti</strong></td>
                    <td style="color:' . $color . '; font-weight:700;">' . $giorni . ' giorni</td></tr>
              </table>
            </td></tr>
          </table>

          <p style="margin:0 0 24px; font-size:.9rem; color:#666;">
            Accedi al pannello di gestione per aggiornare la taratura prima della scadenza.
          </p>

          <a href="' . APP_URL . '/admin/tarature.php?filter=scadenza"
             style="display:inline-block; background:#FF000F; color:#fff; padding:12px 24px;
                    font-weight:700; font-size:.9rem; text-decoration:none; text-transform:uppercase;
                    letter-spacing:.5px;">
            Gestisci tarature →
          </a>
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style="background:#f8f8f8; border-top:1px solid #e0e0e0; padding:14px 28px;
                   font-size:.75rem; color:#aaa; text-align:center;">
          ABB S.p.A. — Via Luciano Lama 33, 24044 Dalmine (BG)<br>
          Questa è un\'email automatica del sistema Calibration Manager. Non rispondere a questo messaggio.
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body></html>';
}

// ============================================================
//  Template email testo plain
// ============================================================
function buildEmailText(array $t): string
{
  $giorni = (int) $t['giorni_rimanenti'];
  $scadenza = date('d/m/Y', strtotime($t['data_scadenza']));

  return "ABB Calibration Manager — Notifica scadenza taratura\n"
    . str_repeat('=', 50) . "\n\n"
    . "ATTENZIONE: Taratura in scadenza tra {$giorni} giorni\n\n"
    . "Macchinario:  {$t['macchinario_nome']}\n"
    . "Reparto:      {$t['reparto']}\n"
    . "Data scadenza: {$scadenza}\n\n"
    . "Accedi al pannello di gestione per rinnovare la taratura:\n"
    . APP_URL . "/admin/tarature.php?filter=scadenza\n\n"
    . str_repeat('-', 50) . "\n"
    . "ABB S.p.A. — Via Luciano Lama 33, 24044 Dalmine (BG)\n"
    . "Email automatica — non rispondere.\n";
}