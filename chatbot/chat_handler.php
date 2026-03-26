<?php
// ============================================================
//  chatbot/chat_handler.php
//  Riceve la domanda via POST (JSON), chiama Groq API,
//  restituisce la risposta in JSON.
// ============================================================

require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// --- Configurazione Groq ---
define('GROQ_API_KEY', 'CHIEDI_A_GIGI');         
define('GROQ_MODEL',   'llama-3.1-8b-instant');     // modello gratuito e veloce

// --- Leggi la domanda ---
$input = json_decode(file_get_contents('php://input'), true);
$question = trim($input['message'] ?? '');

if ($question === '') {
    echo json_encode(['error' => 'Messaggio vuoto.']);
    exit;
}

// --- Carica la knowledge base ---
$kb_path = __DIR__ . '/knowledge_base.txt';
$knowledge = file_exists($kb_path) ? file_get_contents($kb_path) : '';

// --- Costruisci il prompt di sistema ---
$system_prompt = <<<PROMPT
Sei un assistente utile e preciso per il gestionale "ABB Calibration Manager" di ABB S.p.A. Dalmine.
Il tuo compito è rispondere alle domande del gestore del sistema in italiano, in modo chiaro e conciso.
Rispondi SOLO in base alle informazioni contenute nella knowledge base qui sotto.
Se la risposta non è nella knowledge base, dillo chiaramente senza inventare.
Sii diretto, usa elenchi puntati quando è utile, e non superare i 200 parole per risposta.

--- KNOWLEDGE BASE ---
{$knowledge}
--- FINE KNOWLEDGE BASE ---
PROMPT;

// --- Chiama Groq API ---
$payload = json_encode([
    'model'    => GROQ_MODEL,
    'messages' => [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user',   'content' => $question],
    ],
    'max_tokens'  => 512,
    'temperature' => 0.3,
]);

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . GROQ_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 20,
]);

$response = curl_exec($ch);
$err      = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(['error' => 'Errore di connessione: ' . $err]);
    exit;
}

$data = json_decode($response, true);

if (isset($data['error'])) {
    echo json_encode(['error' => 'Errore API: ' . ($data['error']['message'] ?? 'sconosciuto')]);
    exit;
}

$answer = $data['choices'][0]['message']['content'] ?? 'Nessuna risposta ricevuta.';
echo json_encode(['answer' => $answer]);
