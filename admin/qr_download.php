<?php
// ============================================================
//  admin/qr_download.php — Genera e scarica QR code PNG
//
//  Dipendenze:
//    lib/phpqrcode/qrlib.php  (scaricabile da https://phpqrcode.sourceforge.net/)
//    Estensione GD abilitata in PHP
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id === 0) {
    http_response_code(400);
    die('ID mancante.');
}

// Carica dati macchinario
$stmt = db()->prepare("SELECT id, nome, codice_seriale, qr_token FROM macchinari WHERE id = ? AND attivo = 1");
$stmt->execute([$id]);
$mac = $stmt->fetch();

if (!$mac) {
    http_response_code(404);
    die('Macchinario non trovato.');
}

// Carica libreria phpqrcode
$qrlib = dirname(__DIR__) . '/lib/phpqrcode/qrlib.php';
if (!file_exists($qrlib)) {
    die('<div style="font-family:monospace; padding:20px; color:red;">
        <strong>Libreria phpqrcode non trovata.</strong><br><br>
        Scaricala da <a href="https://phpqrcode.sourceforge.net/" target="_blank">phpqrcode.sourceforge.net</a>
        e posiziona la cartella in <code>lib/phpqrcode/</code> del progetto.
    </div>');
}
require_once $qrlib;

// ---- Genera QR code in un file temporaneo ----
$url  = BASE_URL . '/public/macchinario.php?token=' . urlencode($mac['qr_token']);
$tmp  = tempnam(sys_get_temp_dir(), 'qr_') . '.png';

// Parametri QR: (testo, file output, livello correzione errori, dimensione cella px, margine celle)
QRcode::png($url, $tmp, QR_ECLEVEL_M, 8, 2);

// ---- Aggiungi nome macchinario sotto con GD ----
$qr_img   = imagecreatefrompng($tmp);
$qr_w     = imagesx($qr_img);
$qr_h     = imagesy($qr_img);

// Font (usa font GD built-in se non si ha un file TTF)
// Se vuoi un font custom, scarica un .ttf e aggiorna $font_path
$font_path = dirname(__DIR__) . '/lib/font/Barlow-Bold.ttf';
$use_ttf   = file_exists($font_path);

$font_size = 14;  // px per TTF
$padding   = 12;  // px intorno al testo

// Calcola dimensioni del testo per creare il canvas finale
$label_name   = $mac['nome'];
$label_serial = $mac['codice_seriale'];

if ($use_ttf) {
    $bbox_name   = imagettfbbox($font_size, 0, $font_path, $label_name);
    $bbox_serial = imagettfbbox(10,         0, $font_path, $label_serial);
    $text_h_name   = abs($bbox_name[7]   - $bbox_name[1]);
    $text_h_serial = abs($bbox_serial[7] - $bbox_serial[1]);
    $footer_h = $text_h_name + $text_h_serial + $padding * 2 + 8;
} else {
    // Font built-in: font 3 = ~7x13px
    $footer_h = 13 + 13 + $padding * 2 + 8;
}

$canvas_w = $qr_w;
$canvas_h = $qr_h + $footer_h;

$canvas = imagecreatetruecolor($canvas_w, $canvas_h);
$white  = imagecolorallocate($canvas, 255, 255, 255);
$black  = imagecolorallocate($canvas, 26, 26, 26);
$red    = imagecolorallocate($canvas, 255, 0, 15);
$gray   = imagecolorallocate($canvas, 120, 120, 120);

// Sfondo bianco
imagefilledrectangle($canvas, 0, 0, $canvas_w, $canvas_h, $white);

// Copia QR
imagecopy($canvas, $qr_img, 0, 0, 0, 0, $qr_w, $qr_h);

// Linea rossa decorativa
imagefilledrectangle($canvas, 0, $qr_h, $canvas_w, $qr_h + 3, $red);

// Testo nome macchinario
$y_name = $qr_h + 3 + $padding;
if ($use_ttf) {
    $bbox = imagettfbbox($font_size, 0, $font_path, $label_name);
    $tw   = abs($bbox[4] - $bbox[0]);
    $x    = (int)(($canvas_w - $tw) / 2);
    imagettftext($canvas, $font_size, 0, $x, $y_name + abs($bbox[7] - $bbox[1]), $black, $font_path, $label_name);
    $y_serial = $y_name + abs($bbox[7] - $bbox[1]) + 6;
    $bbox2    = imagettfbbox(10, 0, $font_path, $label_serial);
    $tw2      = abs($bbox2[4] - $bbox2[0]);
    $x2       = (int)(($canvas_w - $tw2) / 2);
    imagettftext($canvas, 10, 0, $x2, $y_serial + abs($bbox2[7] - $bbox2[1]), $gray, $font_path, $label_serial);
} else {
    // Fallback font built-in GD (font 3)
    $tw  = strlen($label_name) * 7;
    $x   = (int)(($canvas_w - $tw) / 2);
    imagestring($canvas, 3, max(2, $x), $y_name,   $label_name,   $black);
    $tw2 = strlen($label_serial) * 6;
    $x2  = (int)(($canvas_w - $tw2) / 2);
    imagestring($canvas, 2, max(2, $x2), $y_name + 16, $label_serial, $gray);
}

imagedestroy($qr_img);
@unlink($tmp);

// ---- Output come download PNG ----
$filename = 'QR_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $mac['nome']) . '.png';
header('Content-Type: image/png');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache');
imagepng($canvas, null, 0); // qualità massima
imagedestroy($canvas);
exit;
