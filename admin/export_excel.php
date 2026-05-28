<?php
// ============================================================
//  admin/export_excel.php
//  Genera file XLSX per il chatbot AI.
//  Parametri GET:
//    tipo        = tutti | reparto | storico | scadenze
//    reparto_id  = (int) obbligatorio se tipo=reparto
//    giorni      = (int) default 60, usato se tipo=scadenze
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';
requireLogin();

$db     = db();
$tipo   = $_GET['tipo']       ?? 'tutti';
$rep_id = (int)($_GET['reparto_id'] ?? 0);
$giorni = max(1, (int)($_GET['giorni'] ?? 60));

// ============================================================
//  QUERY
// ============================================================
$params = [];

$base_sql = "
    SELECT
        m.nome                                          AS 'Macchinario',
        m.codice_seriale                                AS 'Codice seriale',
        r.nome                                          AS 'Reparto',
        m.tipo_categoria                                AS 'Categoria',
        m.unita_misura                                  AS 'Unità misura',
        t.data_inserimento                              AS 'Data taratura',
        t.data_scadenza                                 AS 'Scadenza',
        CASE
            WHEN t.data_scadenza IS NULL                THEN 'Nessuna taratura'
            WHEN t.data_scadenza < CURRENT_DATE         THEN 'Scaduta'
            WHEN DATEDIFF(t.data_scadenza,CURRENT_DATE) <= 30 THEN 'In scadenza'
            ELSE 'Valida'
        END                                             AS 'Stato',
        t.tecnico                                       AS 'Tecnico',
        t.ente_certificatore                            AS 'Ente certificatore',
        t.numero_certificato                            AS 'N° certificato',
        t.esito                                         AS 'Esito',
        t.note                                          AS 'Note'
    FROM macchinari m
    JOIN reparti r ON r.id = m.reparto_id
    LEFT JOIN tarature t ON t.id = (
        SELECT id FROM tarature
        WHERE macchinario_id = m.id
        ORDER BY data_inserimento DESC, id DESC
        LIMIT 1
    )
    WHERE m.attivo = 1
";

$storico_sql = "
    SELECT
        m.nome                                          AS 'Macchinario',
        m.codice_seriale                                AS 'Codice seriale',
        r.nome                                          AS 'Reparto',
        t.data_inserimento                              AS 'Data taratura',
        t.data_scadenza                                 AS 'Scadenza',
        CASE
            WHEN t.data_scadenza < CURRENT_DATE         THEN 'Scaduta'
            WHEN DATEDIFF(t.data_scadenza,CURRENT_DATE) <= 30 THEN 'In scadenza'
            ELSE 'Valida'
        END                                             AS 'Stato',
        t.tecnico                                       AS 'Tecnico',
        t.ente_certificatore                            AS 'Ente certificatore',
        t.numero_certificato                            AS 'N° certificato',
        t.esito                                         AS 'Esito',
        t.note                                          AS 'Note'
    FROM tarature t
    JOIN macchinari m ON m.id = t.macchinario_id
    JOIN reparti r    ON r.id = m.reparto_id
    WHERE m.attivo = 1
    ORDER BY t.data_inserimento DESC, t.id DESC
    LIMIT 5000
";

switch ($tipo) {
    case 'reparto':
        if ($rep_id <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'reparto_id mancante']);
            exit;
        }
        $sql      = $base_sql . ' AND m.reparto_id = ? ORDER BY m.nome';
        $params[] = $rep_id;
        // recupera nome reparto per il filename
        $rep_nome = $db->prepare('SELECT nome FROM reparti WHERE id = ?');
        $rep_nome->execute([$rep_id]);
        $rep_label = preg_replace('/[^a-zA-Z0-9_]/', '_', $rep_nome->fetchColumn() ?: 'reparto');
        $sheet_title = 'Reparto ' . $rep_label;
        $filename    = 'macchinari_' . $rep_label . '_' . date('Ymd') . '.xlsx';
        break;

    case 'scadenze':
        $sql      = $base_sql . ' AND t.data_scadenza IS NOT NULL AND t.data_scadenza >= CURRENT_DATE AND DATEDIFF(t.data_scadenza,CURRENT_DATE) <= ? ORDER BY t.data_scadenza ASC';
        $params[] = $giorni;
        $sheet_title = 'Scadenze ' . $giorni . ' giorni';
        $filename    = 'scadenze_' . $giorni . 'gg_' . date('Ymd') . '.xlsx';
        break;

    case 'storico':
        $sql         = $storico_sql;
        $sheet_title = 'Storico tarature';
        $filename    = 'storico_tarature_' . date('Ymd') . '.xlsx';
        break;

    default: // tutti
        $sql         = $base_sql . ' ORDER BY r.nome, m.nome';
        $sheet_title = 'Tutti i macchinari';
        $filename    = 'macchinari_tutti_' . date('Ymd') . '.xlsx';
        break;
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ============================================================
//  GENERA XLSX con ZipArchive + OpenXML puro
// ============================================================
function xmlEsc(string $v): string {
    return htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function dateIta(?string $d): string {
    if (!$d) return '';
    $t = strtotime($d);
    return $t ? date('d/m/Y', $t) : $d;
}

// Condivide stringhe (shared strings)
$shared    = [];
$sharedIdx = [];

function getSharedIdx(string $v): int {
    global $shared, $sharedIdx;
    if (!isset($sharedIdx[$v])) {
        $sharedIdx[$v] = count($shared);
        $shared[]      = $v;
    }
    return $sharedIdx[$v];
}

// Prepara intestazioni
$headers = empty($rows) ? [] : array_keys($rows[0]);

// Prepara celle del foglio
$sheetRows = [];

// Riga intestazione
$hRow = [];
foreach ($headers as $h) {
    $hRow[] = ['t' => 's', 'v' => getSharedIdx($h)];
}
$sheetRows[] = $hRow;

// Righe dati
foreach ($rows as $row) {
    $xRow = [];
    foreach ($headers as $col) {
        $raw = $row[$col] ?? '';
        // formatta date
        if (in_array($col, ['Data taratura', 'Scadenza'])) {
            $raw = dateIta($raw);
        }
        // esito leggibile
        if ($col === 'Esito' && $raw !== '') {
            $raw = ($raw === 'conforme') ? 'Conforme' : 'Non conforme';
        }
        $raw = (string)$raw;
        $xRow[] = ['t' => 's', 'v' => getSharedIdx($raw)];
    }
    $sheetRows[] = $xRow;
}

// ---- XML sheet ----
$colLetters = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P'];
$xmlSheet   = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<sheetData>';

foreach ($sheetRows as $ri => $xRow) {
    $rowNum  = $ri + 1;
    $xmlSheet .= '<row r="' . $rowNum . '">';
    foreach ($xRow as $ci => $cell) {
        $col     = $colLetters[$ci] ?? 'A';
        $cellRef = $col . $rowNum;
        $xmlSheet .= '<c r="' . $cellRef . '" t="s"><v>' . $cell['v'] . '</v></c>';
    }
    $xmlSheet .= '</row>';
}
$xmlSheet .= '</sheetData></worksheet>';

// ---- XML shared strings ----
$xmlSst = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
    . ' count="' . count($shared) . '" uniqueCount="' . count($shared) . '">';
foreach ($shared as $s) {
    $xmlSst .= '<si><t xml:space="preserve">' . xmlEsc($s) . '</t></si>';
}
$xmlSst .= '</sst>';

// ---- Workbook ----
$xmlWb = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
    . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
    . '<sheets><sheet name="' . xmlEsc($sheet_title) . '" sheetId="1" r:id="rId1"/></sheets>'
    . '</workbook>';

// ---- Styles minimali ----
$xmlStyles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
    . '<fonts><font><sz val="11"/></font></fonts>'
    . '<fills><fill><patternFill patternType="none"/></fill></fills>'
    . '<borders><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
    . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
    . '<cellXfs><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
    . '</styleSheet>';

// ---- Rels ----
$xmlWbRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
    . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/sharedStrings" Target="sharedStrings.xml"/>'
    . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
    . '</Relationships>';

$xmlPkgRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
    . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
    . '</Relationships>';

$xmlCT = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
    . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
    . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
    . '<Default Extension="xml"  ContentType="application/xml"/>'
    . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
    . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
    . '<Override PartName="/xl/sharedStrings.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sharedStrings+xml"/>'
    . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
    . '</Types>';

// ---- Crea XLSX in memoria ----
$tmp = tempnam(sys_get_temp_dir(), 'xlsx_');

$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossibile creare il file Excel.']);
    exit;
}

$zip->addFromString('[Content_Types].xml',          $xmlCT);
$zip->addFromString('_rels/.rels',                  $xmlPkgRels);
$zip->addFromString('xl/workbook.xml',              $xmlWb);
$zip->addFromString('xl/_rels/workbook.xml.rels',   $xmlWbRels);
$zip->addFromString('xl/worksheets/sheet1.xml',     $xmlSheet);
$zip->addFromString('xl/sharedStrings.xml',         $xmlSst);
$zip->addFromString('xl/styles.xml',                $xmlStyles);
$zip->close();

// ---- Output ----
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-cache, no-store');

readfile($tmp);
unlink($tmp);
exit;
