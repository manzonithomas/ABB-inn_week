<?php
// ============================================================
//  admin/template_taratura.php — Template PDF Certificato Taratura
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

// ---- Carica FPDF (stessa logica degli altri file) ----
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
$fpdf_lib = dirname(__DIR__) . '/lib/fpdf/fpdf.php';
if (file_exists($autoload))
    require_once $autoload;
elseif (file_exists($fpdf_lib))
    require_once $fpdf_lib;
else
    die('FPDF non trovato.');

// Istanzia FPDF compatibile con entrambe le versioni
if (class_exists('setasign\Fpdf\Fpdf')) {
    $pdf = new \setasign\Fpdf\Fpdf('P', 'mm', 'A4');
} else {
    $pdf = new FPDF('P', 'mm', 'A4');
}

// ---- Percorso Logo ABB ----
// Metti il tuo logo in /abb/assets/img/abb-logo.png (o cambia il percorso qui sotto)
$logo_path = 'https://images.seeklogo.com/logo-png/0/1/abb-logo-png_seeklogo-1844.png';

// ---- Creazione PDF ----
$pdf->SetMargins(15, 15, 15);
$pdf->SetAutoPageBreak(true, 15);
$pdf->AddPage();

// Intestazione con Logo
if (file_exists($logo_path)) {
    $pdf->Image($logo_path, 15, 15, 40); // X, Y, Larghezza in mm
} else {
    // Fallback se non trovi il logo
    $pdf->SetFont('Helvetica', 'B', 24);
    $pdf->SetTextColor(255, 0, 15); // Rosso ABB
    $pdf->Cell(0, 15, 'ABB', 0, 1, 'L');
    $pdf->SetTextColor(0, 0, 0);
}

// Linea separatrice sotto il logo
$pdf->SetDrawColor(255, 0, 15); // Rosso ABB
$pdf->SetLineWidth(0.8);
$pdf->Line(15, 35, 195, 35);

// Titolo del documento
$pdf->Ln(20); // Spazio
$pdf->SetFont('Helvetica', 'B', 18);
$pdf->Cell(0, 10, 'CERTIFICATO DI TARATURA', 0, 1, 'C');

// Testo Placeholder
$pdf->Ln(30);
$pdf->SetFont('Helvetica', '', 16);
$pdf->SetTextColor(100, 100, 100); // Grigio
$pdf->MultiCell(0, 12, 'QUI CI SARA\' IL PDF CON IL CERTIFICATO DI TARATURA', 0, 'C');

// Reset colore per eventuali footer
$pdf->SetTextColor(0, 0, 0);

// Linea fondo pagina
$pdf->SetY(-30);
$pdf->SetDrawColor(200, 200, 200);
$pdf->SetLineWidth(0.3);
$pdf->Line(15, 282, 195, 282);
$pdf->SetFont('Helvetica', 'I', 8);
$pdf->Cell(0, 10, 'ABB Calibration Manager - Documento template', 0, 0, 'C');

// Output del PDF
$pdf->Output('I', 'Template_Taratura.pdf'); // 'I' lo mostra nel browser
exit;