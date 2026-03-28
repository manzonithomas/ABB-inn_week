<?php
// ============================================================
//  chatbot/chat_handler.php
//  Riceve la domanda via POST (JSON), chiama Groq API,
//  restituisce la risposta in JSON.
//  Supporta intent PDF senza chiamare Groq.
// ============================================================

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/queries.php';

header('Content-Type: application/json; charset=utf-8');

define('GROQ_API_KEY', 'CHIEDI_A_GIGI');
define('GROQ_MODEL',   'llama-3.1-8b-instant');

$input    = json_decode(file_get_contents('php://input'), true);
$question = trim($input['message'] ?? '');

if ($question === '') {
    echo json_encode(['error' => 'Messaggio vuoto.']);
    exit;
}

// ============================================================
//  INTENT PDF — gestito localmente, zero token Groq
// ============================================================

function trovaTReparto(string $testo): ?array
{
    $db   = db();
    $rows = $db->query("SELECT id, nome FROM reparti ORDER BY nome")->fetchAll();
    $tl   = mb_strtolower($testo);
    foreach ($rows as $r)
        if (str_contains($tl, mb_strtolower($r['nome']))) return $r;
    return null;
}

function trovaMacchinario(string $testo): ?array
{
    $db   = db();
    $rows = $db->query("SELECT id, nome FROM macchinari WHERE attivo = 1 ORDER BY nome")->fetchAll();
    $tl   = mb_strtolower($testo);
    foreach ($rows as $m)
        if (str_contains($tl, mb_strtolower($m['nome']))) return $m;
    return null;
}

$q_lower   = mb_strtolower($question);
$vuole_pdf = (bool) preg_match('/\b(pdf|genera|scarica|stampa|esporta|download)\b/ui', $question);

$giorni = 60;
if (preg_match('/(\d+)\s*(giorn|gg|days)/ui', $question, $mg))
    $giorni = (int) $mg[1];

$pdf_url = $pdf_label = $risposta_fissa = null;

if ($vuole_pdf) {
    $mac = trovaMacchinario($question);
    if ($mac) {
        $pdf_url        = BASE_URL . '/admin/scheda_pdf.php?id=' . $mac['id'];
        $pdf_label      = 'Scarica PDF — ' . $mac['nome'];
        $risposta_fissa = 'Ho trovato il macchinario **' . $mac['nome'] . '**. Clicca il pulsante per scaricare la scheda PDF con storico tarature.';
    } elseif (str_contains($q_lower, 'reparto')) {
        $rep = trovaTReparto($question);
        if ($rep) {
            $pdf_url        = BASE_URL . '/admin/lista_pdf.php?tipo=reparto&reparto_id=' . $rep['id'];
            $pdf_label      = 'Scarica PDF — Reparto ' . $rep['nome'];
            $risposta_fissa = 'Ecco la lista macchinari del reparto **' . $rep['nome'] . '**.';
        } else {
            $risposta_fissa = 'Non ho trovato il reparto specificato. Indica il nome esatto del reparto.';
        }
    } elseif (str_contains($q_lower, 'scadenz') || str_contains($q_lower, 'scadut')) {
        $pdf_url        = BASE_URL . '/admin/lista_pdf.php?tipo=scadenze&giorni=' . $giorni;
        $pdf_label      = 'Scarica PDF — Scadenze entro ' . $giorni . ' giorni';
        $risposta_fissa = 'PDF con macchinari scaduti o in scadenza entro **' . $giorni . ' giorni**.';
    } elseif (preg_match('/\b(tutti|lista|elenco|completa)\b/ui', $question)) {
        $pdf_url        = BASE_URL . '/admin/lista_pdf.php?tipo=tutti';
        $pdf_label      = 'Scarica PDF — Tutti i macchinari';
        $risposta_fissa = 'PDF con la lista completa di tutti i macchinari attivi.';
    }
}

if ($pdf_url !== null) {
    echo json_encode(['answer' => $risposta_fissa, 'pdf_url' => $pdf_url, 'pdf_label' => $pdf_label]);
    exit;
}

// ============================================================
//  GROQ — solo knowledge base, niente query DB nel prompt
// ============================================================
$kb_path   = __DIR__ . '/knowledge_base.txt';
$knowledge = file_exists($kb_path) ? file_get_contents($kb_path) : '';

$system_prompt = <<<PROMPT
Sei un assistente per il gestionale "ABB Calibration Manager" di ABB S.p.A. Dalmine.
Rispondi in italiano, in modo chiaro e conciso, SOLO in base alla knowledge base.
Se la risposta non è nella knowledge base, dillo senza inventare.
Usa elenchi puntati se utile. Massimo 150 parole per risposta.

Per generare PDF, l'utente può scrivere: "scarica PDF [nome macchinario]",
"PDF scadenze", "PDF reparto [nome]", "PDF tutti i macchinari".

--- KNOWLEDGE BASE ---
{$knowledge}
--- FINE KNOWLEDGE BASE ---
PROMPT;

$payload = json_encode([
    'model'    => GROQ_MODEL,
    'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user',   'content' => $question],
    ],
    'max_tokens'  => 400,
    'temperature' => 0.3,
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . GROQ_API_KEY],
    CURLOPT_TIMEOUT        => 20,
]);
$response = curl_exec($ch);
$err      = curl_error($ch);
curl_close($ch);

if ($err) { echo json_encode(['error' => 'Errore connessione: ' . $err]); exit; }

$data = json_decode($response, true);
if (isset($data['error'])) { echo json_encode(['error' => 'Errore API: ' . ($data['error']['message'] ?? 'sconosciuto')]); exit; }

echo json_encode(['answer' => $data['choices'][0]['message']['content'] ?? 'Nessuna risposta.']);
