<?php
// ============================================================
//  admin/scheda_pdf.php — Scheda macchinario in PDF
//  Genera un PDF A4 con tutti i dati del macchinario
//  e lo storico completo delle tarature.
//
//  Dipendenze:
//    composer require setasign/fpdf
//    lib/phpqrcode/qrlib.php
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
requireLogin();

$id = (int) ($_GET['id'] ?? 0);
if ($id === 0) {
    http_response_code(400);
    die('ID mancante.');
}

// ---- Carica dati ----
$db = db();
$stmt = $db->prepare("
    SELECT vt.*, m.tipo_categoria, m.unita_misura, m.intervallo_mesi
    FROM ( " . sql_ultima_taratura('vt') . "
    JOIN macchinari m ON m.id = vt.macchinario_id
    WHERE vt.macchinario_id = ?
");
$stmt->execute([$id]);
$mac = $stmt->fetch();
if (!$mac) {
    http_response_code(404);
    die('Macchinario non trovato.');
}

// Storico tarature
$storico = $db->prepare("
    SELECT * FROM tarature
    WHERE macchinario_id = ?
    ORDER BY data_inserimento DESC, id DESC
");
$storico->execute([$id]);
$tarature = $storico->fetchAll();

// ---- Dipendenze ----
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
$fpdf_lib = dirname(__DIR__) . '/lib/fpdf/fpdf.php';
if (file_exists($autoload))
    require_once $autoload;
elseif (file_exists($fpdf_lib))
    require_once $fpdf_lib;
else
    die('FPDF non trovato. Esegui: composer require setasign/fpdf');

$qrlib = dirname(__DIR__) . '/lib/phpqrcode/qrlib.php';
$has_qr = file_exists($qrlib);
if ($has_qr)
    require_once $qrlib;

// ---- Helper date ----
function fmtD(?string $d): string
{
    if (!$d)
        return '—';
    return date('d/m/Y', strtotime($d));
}

// ---- Genera QR temporaneo ----
$tmp_qr = null;
if ($has_qr) {
    $url = BASE_URL . '/public/macchinario.php?token=' . urlencode($mac['qr_token']);
    $tmp_qr = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
    QRcode::png($url, $tmp_qr, QR_ECLEVEL_M, 6, 2);
}

// ============================================================
//  CLASSE PDF PERSONALIZZATA
// ============================================================
class SchedaPDF extends FPDF
{
    public string $mac_nome = '';
    public string $mac_serial = '';

    function Header()
    {
        // Barra rossa in cima
        $this->SetFillColor(255, 0, 15);
        $this->Rect(0, 0, 210, 12, 'F');

        // Logo ABB
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, 2);
        $this->Cell(20, 8, 'ABB', 0, 0, 'L');

        // Titolo
        $this->SetFont('Helvetica', '', 9);
        $this->SetXY(32, 3);
        $this->Cell(0, 6, 'Calibration Manager  |  Scheda macchinario', 0, 0, 'L');

        // Nome macchinario a destra
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetXY(0, 3);
        $this->Cell(200, 6, $this->mac_nome . '  —  ' . $this->mac_serial, 0, 0, 'R');

        $this->SetTextColor(0, 0, 0);
        $this->Ln(14);
    }

    function Footer()
    {
        $this->SetY(-10);
        $this->SetFont('Helvetica', '', 7);
        $this->SetTextColor(160, 160, 160);
        $this->SetFillColor(240, 240, 240);
        $this->Rect(0, 287, 210, 10, 'F');
        $this->SetXY(10, 288);
        $this->Cell(
            0,
            5,
            'ABB S.p.A.  |  Via Luciano Lama 33, 24044 Dalmine (BG)  |  ' .
            'Generato il ' . date('d/m/Y \a\l\l\e H:i') . '  |  Pag. ' . $this->PageNo(),
            0,
            0,
            'L'
        );
        $this->SetTextColor(0, 0, 0);
    }

    // Sezione con titolo su sfondo grigio scuro
    function SectionTitle(string $title): void
    {
        $this->SetFillColor(26, 26, 26);
        $this->SetTextColor(255, 255, 255);
        $this->SetFont('Helvetica', 'B', 9);
        $this->SetX(10);
        $this->Cell(190, 6, '  ' . strtoupper($title), 0, 1, 'L', true);
        $this->SetTextColor(0, 0, 0);
        $this->Ln(2);
    }

    // Riga campo: etichetta | valore
    function Campo(string $label, string $value, float $lw = 55): void
    {
        $this->SetFont('Helvetica', 'B', 8);
        $this->SetTextColor(100, 100, 100);
        $this->SetX(12);
        $this->Cell($lw, 6, $label, 0, 0, 'L');
        $this->SetFont('Helvetica', '', 9);
        $this->SetTextColor(26, 26, 26);
        $this->Cell(190 - $lw, 6, $value, 0, 1, 'L');
    }

    // Riga tabella storico
    function RigaStorico(array $cols, bool $header = false, bool $alt = false): void
    {
        $widths = [28, 28, 42, 40, 28, 24];
        if ($header) {
            $this->SetFillColor(240, 240, 240);
            $this->SetFont('Helvetica', 'B', 7.5);
            $this->SetTextColor(80, 80, 80);
        } else {
            $this->SetFillColor($alt ? 250 : 255, $alt ? 250 : 255, $alt ? 250 : 255);
            $this->SetFont('Helvetica', '', 8);
            $this->SetTextColor(26, 26, 26);
        }
        $this->SetX(10);
        foreach ($cols as $i => $txt) {
            $this->Cell($widths[$i], 6, $txt, 0, 0, 'L', true);
        }
        $this->Ln();
        $this->SetTextColor(0, 0, 0);
    }
}

// ============================================================
//  COSTRUISCI PDF
// ============================================================
$pdf = new SchedaPDF('P', 'mm', 'A4');
$pdf->mac_nome = $mac['macchinario_nome'];
$pdf->mac_serial = $mac['codice_seriale'];
$pdf->SetMargins(10, 18, 10);
$pdf->SetAutoPageBreak(true, 14);
$pdf->SetTitle('Scheda ' . $mac['macchinario_nome']);
$pdf->AddPage();

// ---- TITOLO MACCHINARIO ----
$pdf->SetFont('Helvetica', 'B', 18);
$pdf->SetTextColor(26, 26, 26);
$pdf->SetX(10);
$pdf->Cell(0, 10, $mac['macchinario_nome'], 0, 1, 'L');

$pdf->SetFont('Helvetica', '', 9);
$pdf->SetTextColor(120, 120, 120);
$pdf->SetX(10);
$pdf->Cell(0, 5, $mac['codice_seriale'] . '  —  ' . $mac['reparto'], 0, 1, 'L');
$pdf->Ln(4);

// ---- STATO taratura corrente (banner colorato) ----
$stato = $mac['taratura_id'] ? ($mac['stato_scadenza'] ?? 'nd') : 'nd';
$stato_colors = [
    'valida' => [232, 245, 233],
    'in_scadenza' => [255, 243, 205],
    'scaduta' => [255, 235, 238],
    'nd' => [240, 240, 240],
];
$stato_labels = [
    'valida' => 'TARATURA VALIDA',
    'in_scadenza' => 'IN SCADENZA',
    'scaduta' => 'TARATURA SCADUTA',
    'nd' => 'NESSUNA TARATURA',
];
$stato_tc = [
    'valida' => [46, 125, 50],
    'in_scadenza' => [230, 81, 0],
    'scaduta' => [198, 40, 40],
    'nd' => [100, 100, 100],
];
[$sr, $sg, $sb] = $stato_colors[$stato];
[$tr, $tg, $tb] = $stato_tc[$stato];

$pdf->SetFillColor($sr, $sg, $sb);
$pdf->SetTextColor($tr, $tg, $tb);
$pdf->SetFont('Helvetica', 'B', 10);
$pdf->SetX(10);

// Banner a tutta larghezza con QR a destra
$banner_h = 14;
$qr_size = 28;  // mm

if ($tmp_qr) {
    $pdf->Cell(152, $banner_h, '  ' . $stato_labels[$stato], 0, 0, 'L', true);
    // QR sovrapposto a destra
    $qr_x = 10 + 152 + 4;
    $qr_y = $pdf->GetY();
    $pdf->SetFillColor($sr, $sg, $sb);
    $pdf->Cell(34, $banner_h, '', 0, 1, 'L', true);
    $pdf->Image($tmp_qr, $qr_x, $qr_y - $banner_h, $qr_size, $qr_size);
    // Spazio extra per QR che sporge sotto
    if ($qr_size > $banner_h)
        $pdf->Ln($qr_size - $banner_h + 2);
} else {
    $pdf->Cell(190, $banner_h, '  ' . $stato_labels[$stato], 0, 1, 'L', true);
}

$pdf->SetTextColor(0, 0, 0);
$pdf->Ln(4);

// ---- DATI MACCHINARIO ----
$pdf->SectionTitle('Dati macchinario');
$pdf->Campo('Reparto', $mac['reparto']);
$pdf->Campo('Codice seriale', $mac['codice_seriale']);
$pdf->Campo('Categoria', $mac['tipo_categoria'] ?: '—');
$pdf->Campo('Unità di misura', $mac['unita_misura'] ?: '—');
$pdf->Campo('Intervallo taratura', $mac['intervallo_mesi'] . ' mesi');
$pdf->Ln(4);

// ---- ULTIMA TARATURA ----
$pdf->SectionTitle('Ultima taratura');
if ($mac['taratura_id']) {
    $pdf->Campo('Data esecuzione', fmtD($mac['data_inserimento']));
    $pdf->Campo('Data scadenza', fmtD($mac['data_scadenza']));
    $pdf->Campo('Tecnico', $mac['tecnico'] ?: '—');
    $pdf->Campo('Ente certificatore', $mac['ente_certificatore'] ?: '—');
    $pdf->Campo('N° certificato', $mac['numero_certificato'] ?: '—');
    $pdf->Campo('Esito', $mac['esito'] === 'conforme' ? 'Conforme' : 'Non conforme');
    if ($mac['note']) {
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetX(12);
        $pdf->Cell(55, 5, 'Note', 0, 0);
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(26, 26, 26);
        $pdf->MultiCell(135, 5, $mac['note'], 0, 'L');
    }
} else {
    $pdf->SetFont('Helvetica', 'I', 9);
    $pdf->SetTextColor(160, 160, 160);
    $pdf->SetX(12);
    $pdf->Cell(0, 6, 'Nessuna taratura registrata.', 0, 1);
    $pdf->SetTextColor(0, 0, 0);
}
$pdf->Ln(4);

// ---- STORICO TARATURE ----
if (!empty($tarature)) {
    $pdf->SectionTitle('Storico tarature (' . count($tarature) . ' registrazioni)');

    $pdf->RigaStorico(
        ['Data', 'Scadenza', 'Tecnico', 'Ente / N° cert.', 'Esito', 'Stato'],
        true
    );

    // Separatore
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->SetLineWidth(0.1);
    $pdf->SetX(10);
    $pdf->Cell(190, 0, '', 'T', 1);

    $alt = false;
    foreach ($tarature as $t) {
        // Controlla salto pagina manuale
        if ($pdf->GetY() > 270) {
            $pdf->AddPage();
            $pdf->SectionTitle('Storico tarature (continua)');
            $pdf->RigaStorico(
                ['Data', 'Scadenza', 'Tecnico', 'Ente / N° cert.', 'Esito', 'Stato'],
                true
            );
        }

        // Stato
        $sc = $t['data_scadenza'];
        if (!$sc) {
            $st_label = '—';
        } elseif ($sc < date('Y-m-d')) {
            $st_label = 'Scaduta';
        } elseif (strtotime($sc) <= strtotime('+30 days')) {
            $st_label = 'In scadenza';
        } else {
            $st_label = 'Valida';
        }

        $ente_cert = implode(' / ', array_filter([
            $t['ente_certificatore'],
            $t['numero_certificato']
        ])) ?: '—';

        $pdf->RigaStorico([
            fmtD($t['data_inserimento']),
            fmtD($t['data_scadenza']),
            $t['tecnico'],
            $ente_cert,
            $t['esito'] === 'conforme' ? 'Conforme' : 'Non conf.',
            $st_label,
        ], false, $alt);

        $alt = !$alt;
    }
}

// ---- Pulizia ----
if ($tmp_qr)
    @unlink($tmp_qr);

// ---- Output ----
$filename = 'Scheda_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $mac['macchinario_nome']) . '.pdf';
$pdf->Output('D', $filename);
exit;