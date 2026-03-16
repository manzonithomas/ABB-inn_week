<?php
// ============================================================
//  admin/export.php — Export CSV macchinari + tarature
//  Accetta gli stessi parametri GET di macchinari.php e tarature.php
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
requireLogin();

$db = db();

// ---- Leggi filtri (compatibili con entrambe le pagine) ----
$filter = $_GET['filter'] ?? '';
$reparto_f = trim($_GET['reparto'] ?? '');
$macchinario_f = (int) ($_GET['macchinario_id'] ?? 0);
$search = trim($_GET['q'] ?? '');
$source = $_GET['source'] ?? 'tarature'; // 'macchinari' o 'tarature'

// ---- Costruisci WHERE ----
$params = [];
$where = ['m.attivo = 1'];

if ($filter === 'scadenza') {
    $where[] = "t.id IN (SELECT taratura_id FROM v_tarature_in_scadenza)";
} elseif ($filter === 'scadute') {
    $where[] = "t.data_scadenza < CURRENT_DATE";
}
if ($reparto_f !== '') {
    $where[] = 'r.nome = ?';
    $params[] = $reparto_f;
}
if ($macchinario_f > 0) {
    $where[] = 'm.id = ?';
    $params[] = $macchinario_f;
}
if ($search !== '') {
    $where[] = '(m.nome LIKE ? OR m.codice_seriale LIKE ? OR t.tecnico LIKE ? OR t.numero_certificato LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Se esporto dalla pagina macchinari: prendo solo l'ultima taratura per macchinario
if ($source === 'macchinari') {
    $sql = "
        SELECT
            m.nome              AS 'Macchinario',
            m.codice_seriale    AS 'Codice seriale',
            r.nome              AS 'Reparto',
            m.tipo_categoria    AS 'Categoria',
            m.unita_misura      AS 'Unità misura',
            m.intervallo_mesi   AS 'Intervallo (mesi)',
            t.data_inserimento  AS 'Data taratura',
            t.data_scadenza     AS 'Scadenza',
            CASE
                WHEN t.data_scadenza IS NULL               THEN 'Nessuna taratura'
                WHEN t.data_scadenza < CURRENT_DATE        THEN 'Scaduta'
                WHEN DATEDIFF(t.data_scadenza, CURRENT_DATE) <= 30 THEN 'In scadenza'
                ELSE 'Valida'
            END                 AS 'Stato',
            t.tecnico           AS 'Tecnico',
            t.ente_certificatore AS 'Ente certificatore',
            t.numero_certificato AS 'N° certificato',
            t.esito             AS 'Esito',
            t.note              AS 'Note'
        FROM macchinari m
        JOIN reparti r ON r.id = m.reparto_id
        LEFT JOIN tarature t ON t.id = (
            SELECT id FROM tarature
            WHERE macchinario_id = m.id
            ORDER BY data_inserimento DESC, id DESC
            LIMIT 1
        )
        WHERE " . implode(' AND ', $where) . "
        ORDER BY r.nome, m.nome
    ";
} else {
    // Dalla pagina tarature: tutte le tarature (anche storiche) con filtri
    $sql = "
        SELECT
            m.nome              AS 'Macchinario',
            m.codice_seriale    AS 'Codice seriale',
            r.nome              AS 'Reparto',
            m.tipo_categoria    AS 'Categoria',
            m.unita_misura      AS 'Unità misura',
            t.data_inserimento  AS 'Data taratura',
            t.data_scadenza     AS 'Scadenza',
            CASE
                WHEN t.data_scadenza < CURRENT_DATE        THEN 'Scaduta'
                WHEN DATEDIFF(t.data_scadenza, CURRENT_DATE) <= 30 THEN 'In scadenza'
                ELSE 'Valida'
            END                 AS 'Stato',
            t.tecnico           AS 'Tecnico',
            t.ente_certificatore AS 'Ente certificatore',
            t.numero_certificato AS 'N° certificato',
            t.esito             AS 'Esito',
            t.note              AS 'Note'
        FROM tarature t
        JOIN macchinari m ON m.id = t.macchinario_id
        JOIN reparti r    ON r.id = m.reparto_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY t.data_inserimento DESC, t.id DESC
        LIMIT 5000
    ";
}

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// ---- Output CSV ----
$filename = 'export_calibration_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');

// BOM UTF-8 — fondamentale per aprire correttamente in Excel italiano
echo "\xEF\xBB\xBF";

$out = fopen('php://output', 'w');

// Intestazioni colonne
if (!empty($rows)) {
    fputcsv($out, array_keys($rows[0]), ';');
}

// Righe dati
foreach ($rows as $row) {
    // Formatta le date in italiano
    $row['Data taratura'] = $row['Data taratura'] ? fmtDate($row['Data taratura']) : '';
    $row['Scadenza'] = $row['Scadenza'] ? fmtDate($row['Scadenza']) : '';
    // Esito leggibile
    if (isset($row['Esito'])) {
        $row['Esito'] = $row['Esito'] === 'conforme' ? 'Conforme' : 'Non conforme';
    }
    fputcsv($out, $row, ';');
}

fclose($out);
exit;