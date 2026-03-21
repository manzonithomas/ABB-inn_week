<?php
// ============================================================
//  admin/qr_sheet_pdf.php — Foglio QR in PDF
//  Layout fisso: 3 colonne x 4 righe = 12 QR per pagina (A4)
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

// ---- Valida input ----
$ids = array_filter(
    array_map('intval', $_POST['ids'] ?? []),
    fn($id) => $id > 0
);
if (empty($ids)) {
    http_response_code(400);
    die('Nessun macchinario selezionato.');
}

// ---- Dipendenze ----
$qrlib = dirname(__DIR__) . '/lib/phpqrcode/qrlib.php';
if (!file_exists($qrlib))
    die('lib/phpqrcode/qrlib.php non trovato.');
require_once $qrlib;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
$fpdf_lib = dirname(__DIR__) . '/lib/fpdf/fpdf.php';
if (file_exists($autoload))
    require_once $autoload;
elseif (file_exists($fpdf_lib))
    require_once $fpdf_lib;
else
    die('FPDF non trovato. Esegui: composer require setasign/fpdf');

// ---- Carica macchinari ----
$db = db();
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $db->prepare("
    SELECT m.nome, m.codice_seriale, m.qr_token
    FROM macchinari m
    WHERE m.id IN ($placeholders) AND m.attivo = 1
    ORDER BY m.nome
");
$stmt->execute(array_values($ids));
$macchinari = $stmt->fetchAll();
if (empty($macchinari))
    die('Nessun macchinario trovato.');

// ---- Font TTF opzionale ----
$font_path = dirname(__DIR__) . '/lib/font/Barlow-Bold.ttf';
$use_ttf = file_exists($font_path);

// ---- Genera PNG QR (identico a qr_download.php) ----
function generaQrPng(array $mac, string $url, bool $use_ttf, string $font_path): string
{
    $tmp = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
    QRcode::png($url, $tmp, QR_ECLEVEL_M, 8, 2);

    $qr = imagecreatefrompng($tmp);
    $qw = imagesx($qr);
    $qh = imagesy($qr);

    $name = $mac['nome'];
    $serial = $mac['codice_seriale'];
    $pad = 10;
    $fs = 14;

    if ($use_ttf) {
        $bn = imagettfbbox($fs, 0, $font_path, $name);
        $bs = imagettfbbox(10, 0, $font_path, $serial);
        $fh = abs($bn[7] - $bn[1]) + abs($bs[7] - $bs[1]) + $pad * 2 + 8;
    } else {
        $fh = 13 + 13 + $pad * 2 + 8;
    }

    $cw = $qw;
    $ch = $qh + $fh;
    $cv = imagecreatetruecolor($cw, $ch);
    $white = imagecolorallocate($cv, 255, 255, 255);
    $black = imagecolorallocate($cv, 26, 26, 26);
    $red = imagecolorallocate($cv, 255, 0, 15);
    $gray = imagecolorallocate($cv, 120, 120, 120);

    imagefilledrectangle($cv, 0, 0, $cw, $ch, $white);
    imagecopy($cv, $qr, 0, 0, 0, 0, $qw, $qh);
    imagefilledrectangle($cv, 0, $qh, $cw, $qh + 3, $red);

    $yn = $qh + 3 + $pad;
    if ($use_ttf) {
        $b = imagettfbbox($fs, 0, $font_path, $name);
        $x = (int) (($cw - abs($b[4] - $b[0])) / 2);
        imagettftext($cv, $fs, 0, $x, $yn + abs($b[7] - $b[1]), $black, $font_path, $name);
        $ys = $yn + abs($b[7] - $b[1]) + 6;
        $b2 = imagettfbbox(10, 0, $font_path, $serial);
        $x2 = (int) (($cw - abs($b2[4] - $b2[0])) / 2);
        imagettftext($cv, 10, 0, $x2, $ys + abs($b2[7] - $b2[1]), $gray, $font_path, $serial);
    } else {
        $x = max(2, (int) (($cw - strlen($name) * 7) / 2));
        imagestring($cv, 3, $x, $yn, $name, $black);
        $x2 = max(2, (int) (($cw - strlen($serial) * 6) / 2));
        imagestring($cv, 2, $x2, $yn + 16, $serial, $gray);
    }

    imagedestroy($qr);
    @unlink($tmp);

    $out = tempnam(sys_get_temp_dir(), 'qrf_') . '.png';
    imagepng($cv, $out, 0);
    imagedestroy($cv);
    return $out;
}

// ---- Layout fisso ottimale per A4 ----
// 3 colonne x 4 righe = 12 QR per pagina
// Margini 10mm, celle 63x68mm, QR 52mm
$mg = 10;   // margine mm
$cols = 3;
$rows = 4;
$cw = (210 - $mg * 2) / $cols;  // ~63.3 mm
$ch = (297 - $mg * 2) / $rows;  // ~69.3 mm
$qmm = 52;   // QR mm nel PDF

// ---- Crea PDF ----
$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins($mg, $mg, $mg);
$pdf->SetAutoPageBreak(false);
$pdf->SetTitle('Foglio QR — ABB Calibration Manager');
$pdf->AddPage();

$col = 0;
$row = 0;
$count = 0;
$ipp = $cols * $rows; // 12 per pagina
$tmp_files = [];

foreach ($macchinari as $mac) {
    if ($count > 0 && $count % $ipp === 0) {
        $pdf->AddPage();
        $col = 0;
        $row = 0;
    }

    $x = $mg + $col * $cw;
    $y = $mg + $row * $ch;

    // Bordo cella leggero
    $pdf->SetDrawColor(220, 220, 220);
    $pdf->SetLineWidth(0.2);
    $pdf->Rect($x + 0.5, $y + 0.5, $cw - 1, $ch - 1);

    // QR centrato nella cella
    $url = BASE_URL . '/public/macchinario.php?token=' . urlencode($mac['qr_token']);
    $png = generaQrPng($mac, $url, $use_ttf, $font_path);
    $tmp_files[] = $png;

    $ix = $x + ($cw - $qmm) / 2;
    $iy = $y + ($ch - $qmm) / 2;
    $pdf->Image($png, $ix, $iy, $qmm, $qmm, 'PNG');

    $col++;
    $count++;
    if ($col >= $cols) {
        $col = 0;
        $row++;
    }
}

foreach ($tmp_files as $f)
    @unlink($f);

$pdf->Output('D', 'QR_Sheet_' . date('Ymd_His') . '.pdf');
exit;