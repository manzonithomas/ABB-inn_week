<?php
// ============================================================
//  admin/lista_pdf.php — PDF lista macchinari
//  Parametri GET:
//    tipo   = scadenze | reparto | tutti
//    giorni = N  (solo per tipo=scadenze, default 60)
//    reparto_id = N (solo per tipo=reparto)
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
requireLogin();

$tipo       = $_GET['tipo']       ?? 'tutti';
$giorni     = max(1, (int) ($_GET['giorni']     ?? 60));
$reparto_id = (int) ($_GET['reparto_id'] ?? 0);

// ---- Dipendenze FPDF ----
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($autoload))
    require_once $autoload;
else
    die('FPDF non trovato.');

// ---- Carica dati ----
$db   = db();
$base = sql_ultima_taratura('vt');

switch ($tipo) {
    case 'scadenze':
        $sql = "SELECT vt.* FROM ( {$base}
                WHERE vt.stato_scadenza IN ('scaduta','in_scadenza')
                   OR (vt.data_scadenza IS NOT NULL
                       AND DATEDIFF(vt.data_scadenza, CURDATE()) <= ?)
                ORDER BY vt.data_scadenza ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$giorni]);
        $titolo_lista = "Macchinari in scadenza (entro {$giorni} giorni)";
        $filename     = "Scadenze_{$giorni}gg_" . date('Ymd') . '.pdf';
        break;

    case 'reparto':
        $sql = "SELECT vt.* FROM ( {$base}
                WHERE vt.reparto_id = ?
                ORDER BY vt.macchinario_nome ASC";
        // Serve il nome reparto
        $rn = $db->prepare("SELECT nome FROM reparti WHERE id = ?");
        $rn->execute([$reparto_id]);
        $reparto_nome = $rn->fetchColumn() ?: 'Reparto ' . $reparto_id;
        $stmt = $db->prepare($sql);
        $stmt->execute([$reparto_id]);
        $titolo_lista = "Macchinari – Reparto: {$reparto_nome}";
        $filename     = 'Reparto_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $reparto_nome) . '_' . date('Ymd') . '.pdf';
        break;

    default: // tutti
        $sql = "SELECT vt.* FROM ( {$base}
                ORDER BY vt.reparto ASC, vt.macchinario_nome ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute([]);
        $titolo_lista = 'Tutti i macchinari';
        $filename     = 'Macchinari_' . date('Ymd') . '.pdf';
        break;
}

$rows = $stmt->fetchAll();

// ============================================================
//  CLASSE PDF
// ============================================================
class ListaPDF extends FPDF
{
    public string $titolo_lista = '';

    function Header()
    {
        $this->SetFillColor(255, 0, 15);
        $this->Rect(0, 0, 210, 12, 'F');
        $this->SetFont('Helvetica', 'B', 14);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, 2);
        $this->Cell(20, 8, 'ABB', 0, 0, 'L');
        $this->SetFont('Helvetica', '', 9);
        $this->SetXY(32, 3);
        $this->Cell(0, 6, 'Calibration Manager  |  ' . $this->titolo_lista, 0, 0, 'L');
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
            0, 5,
            'ABB S.p.A.  |  Via Luciano Lama 33, 24044 Dalmine (BG)  |  ' .
            'Generato il ' . date('d/m/Y \\a\\l\\l\\e H:i') . '  |  Pag. ' . $this->PageNo(),
            0, 0, 'L'
        );
        $this->SetTextColor(0, 0, 0);
    }

    function TableHeader(): void
    {
        $this->SetFillColor(240, 240, 240);
        $this->SetFont('Helvetica', 'B', 7.5);
        $this->SetTextColor(80, 80, 80);
        $this->SetX(10);
        $widths = [52, 28, 28, 28, 28, 26];
        $labels = ['Macchinario', 'Reparto', 'Ult. taratura', 'Scadenza', 'Giorni', 'Stato'];
        foreach ($labels as $i => $l)
            $this->Cell($widths[$i], 6, $l, 0, 0, 'L', true);
        $this->Ln();
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.1);
        $this->SetX(10);
        $this->Cell(190, 0, '', 'T', 1);
        $this->SetTextColor(0, 0, 0);
    }

    function TableRow(array $row, bool $alt): void
    {
        $stato = $row['stato_scadenza'] ?? 'nd';
        $stato_label = [
            'valida'      => 'Valida',
            'in_scadenza' => 'In scadenza',
            'scaduta'     => 'Scaduta',
            'nd'          => 'N/D',
        ][$stato] ?? 'N/D';

        // Colore riga
        if ($stato === 'scaduta')          $this->SetFillColor(255, 235, 238);
        elseif ($stato === 'in_scadenza')  $this->SetFillColor(255, 249, 220);
        elseif ($alt)                       $this->SetFillColor(250, 250, 250);
        else                                $this->SetFillColor(255, 255, 255);

        $this->SetFont('Helvetica', '', 7.5);
        $this->SetTextColor(26, 26, 26);
        $this->SetX(10);

        $widths = [52, 28, 28, 28, 28, 26];
        $giorni_str = $row['giorni_alla_scadenza'] !== null
            ? (int) $row['giorni_alla_scadenza'] . ' gg'
            : '—';
        $data_ult = $row['data_inserimento']
            ? date('d/m/Y', strtotime($row['data_inserimento']))
            : '—';
        $data_sc = $row['data_scadenza']
            ? date('d/m/Y', strtotime($row['data_scadenza']))
            : '—';

        $cells = [
            $row['macchinario_nome'],
            $row['reparto'],
            $data_ult,
            $data_sc,
            $giorni_str,
            $stato_label,
        ];

        foreach ($cells as $i => $txt)
            $this->Cell($widths[$i], 6, $txt, 0, 0, 'L', true);
        $this->Ln();
    }
}

// ============================================================
//  GENERA PDF
// ============================================================
$pdf = new ListaPDF('P', 'mm', 'A4');
$pdf->titolo_lista = $titolo_lista;
$pdf->SetMargins(10, 18, 10);
$pdf->SetAutoPageBreak(true, 14);
$pdf->SetTitle($titolo_lista);
$pdf->AddPage();

// Titolo
$pdf->SetFont('Helvetica', 'B', 16);
$pdf->SetTextColor(26, 26, 26);
$pdf->SetX(10);
$pdf->Cell(0, 9, $titolo_lista, 0, 1, 'L');
$pdf->SetFont('Helvetica', '', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->SetX(10);
$pdf->Cell(0, 5, 'Totale: ' . count($rows) . ' macchinari  —  Generato il ' . date('d/m/Y \a\l\l\e H:i'), 0, 1, 'L');
$pdf->Ln(4);

if (empty($rows)) {
    $pdf->SetFont('Helvetica', 'I', 9);
    $pdf->SetTextColor(160, 160, 160);
    $pdf->SetX(10);
    $pdf->Cell(0, 8, 'Nessun macchinario trovato con i criteri selezionati.', 0, 1, 'L');
} else {
    $pdf->TableHeader();
    $alt = false;
    foreach ($rows as $row) {
        if ($pdf->GetY() > 272) {
            $pdf->AddPage();
            $pdf->TableHeader();
        }
        $pdf->TableRow($row, $alt);
        $alt = !$alt;
    }

    // Legenda stati
    $pdf->Ln(6);
    $pdf->SetX(10);
    $pdf->SetFont('Helvetica', 'B', 7.5);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, 'LEGENDA STATI:', 0, 1);
    $legends = [
        [[255, 235, 238], 'Scaduta — taratura scaduta, richiede intervento immediato'],
        [[255, 249, 220], 'In scadenza — taratura in scadenza entro 30 giorni'],
        [[250, 250, 250], 'Valida — taratura in corso di validità'],
    ];
    foreach ($legends as [$color, $label]) {
        [$r, $g, $b] = $color;
        $pdf->SetX(12);
        $pdf->SetFillColor($r, $g, $b);
        $pdf->Cell(8, 4, '', 0, 0, 'L', true);
        $pdf->SetFont('Helvetica', '', 7.5);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Cell(0, 4, '  ' . $label, 0, 1, 'L');
    }
}

$pdf->Output('D', $filename);
exit;
