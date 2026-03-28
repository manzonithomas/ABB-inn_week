<?php
// ============================================================
//  admin/chatbot.php
//  Assistente AI integrato nel pannello admin
// ============================================================
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/includes/auth.php';

$page_title = 'Assistente AI';
$active_nav = 'chatbot';
require_once dirname(__DIR__) . '/includes/header_admin.php';
?>

<style>
    .chat-wrap {
        max-width: 780px;
        margin: 0 auto;
    }

    .chat-box {
        background: #fff;
        border-radius: 2px;
        box-shadow: 0 1px 4px rgba(0,0,0,.08);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 180px);
        min-height: 420px;
        overflow: hidden;
    }

    .chat-header {
        background: var(--dark);
        color: #fff;
        padding: 14px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: .95rem;
        letter-spacing: .3px;
        flex-shrink: 0;
    }

    .chat-header .ai-dot {
        width: 9px;
        height: 9px;
        border-radius: 50%;
        background: #2ecc71;
        box-shadow: 0 0 6px #2ecc71;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50%       { opacity: .4; }
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        background: #f8f8f8;
    }

    .msg {
        display: flex;
        gap: 10px;
        max-width: 86%;
        animation: fadeIn .2s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(6px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .msg.user {
        align-self: flex-end;
        flex-direction: row-reverse;
    }

    .msg-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .8rem;
        flex-shrink: 0;
        font-weight: 700;
    }

    .msg.bot  .msg-avatar { background: var(--red); color: #fff; }
    .msg.user .msg-avatar { background: var(--dark); color: #fff; }

    .msg-bubble {
        padding: 11px 15px;
        border-radius: 2px;
        font-size: .9rem;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .msg.bot  .msg-bubble {
        background: #fff;
        border: 1px solid var(--border);
        border-left: 3px solid var(--red);
    }

    .msg.user .msg-bubble {
        background: var(--dark);
        color: #fff;
    }

    .msg-bubble ul {
        margin: 6px 0 0 0;
        padding-left: 18px;
    }

    .msg-bubble li { margin-bottom: 3px; }

    .typing-indicator {
        display: flex;
        gap: 5px;
        align-items: center;
        padding: 12px 15px;
    }

    .typing-indicator span {
        width: 7px;
        height: 7px;
        background: #ccc;
        border-radius: 50%;
        animation: bounce .9s infinite;
    }

    .typing-indicator span:nth-child(2) { animation-delay: .15s; }
    .typing-indicator span:nth-child(3) { animation-delay: .3s; }

    @keyframes bounce {
        0%, 60%, 100% { transform: translateY(0); }
        30%            { transform: translateY(-6px); }
    }

    .chat-input-area {
        border-top: 1px solid var(--border);
        padding: 14px 16px;
        background: #fff;
        display: flex;
        gap: 10px;
        flex-shrink: 0;
    }

    #chat-input {
        flex: 1;
        padding: 10px 14px;
        border: 1.5px solid var(--border);
        font-family: 'Barlow', sans-serif;
        font-size: .92rem;
        border-radius: 1px;
        resize: none;
        transition: border-color .15s;
        max-height: 120px;
        min-height: 42px;
    }

    #chat-input:focus {
        outline: none;
        border-color: var(--red);
    }

    #send-btn {
        background: var(--red);
        color: #fff;
        border: none;
        padding: 0 20px;
        font-family: 'Barlow', sans-serif;
        font-weight: 700;
        font-size: .9rem;
        cursor: pointer;
        border-radius: 1px;
        transition: background .15s;
        display: flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
    }

    #send-btn:hover:not(:disabled) { background: var(--red-dark); }
    #send-btn:disabled { opacity: .5; cursor: not-allowed; }

    .chat-suggestions {
        padding: 0 20px 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .suggestion-btn {
        background: #fff;
        border: 1px solid var(--border);
        color: var(--mid);
        font-family: 'Barlow', sans-serif;
        font-size: .78rem;
        padding: 5px 12px;
        border-radius: 20px;
        cursor: pointer;
        transition: border-color .15s, color .15s;
    }

    .suggestion-btn:hover {
        border-color: var(--red);
        color: var(--red);
    }

    /* Pulsante PDF nel chatbot */
    .pdf-download-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 12px;
        padding: 8px 16px;
        background: #ff000f;
        color: #fff !important;
        border-radius: 4px;
        font-size: .82rem;
        font-weight: 700;
        text-decoration: none !important;
        transition: background .15s;
        letter-spacing: .3px;
    }
    .pdf-download-btn:hover { background: #cc0000; }
    .pdf-download-btn i { font-size: .9rem; }

    /* Sovrascrivi o aggiungi queste regole */
    .msg.bot .msg-bubble {
        background: #fff;
        border: 1px solid var(--border);
        border-left: 3px solid var(--red);
        white-space: normal; /* IMPORTANTE: permette al browser di gestire i paragrafi HTML */
    }

    /* Formattazione elementi dentro la risposta dell'AI */
    .msg-bubble p { margin-bottom: 10px; }
    .msg-bubble p:last-child { margin-bottom: 0; }
    .msg-bubble ul, .msg-bubble ol { 
        margin: 10px 0; 
        padding-left: 20px; 
    }
    .msg-bubble li { margin-bottom: 5px; }
    .msg-bubble strong { font-weight: 700; color: #333; }

</style>
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<div class="chat-wrap">
    <div class="page-header">
        <h2><i class="fa fa-robot" style="color:var(--red)"></i> Assistente AI</h2>
        <span class="text-muted" style="font-size:.82rem;">
            Powered by Groq · Llama 3 · Gratuito
        </span>
    </div>

    <div class="chat-box">
        <div class="chat-header">
            <div class="ai-dot"></div>
            Assistente ABB Calibration Manager
        </div>

        <div class="chat-messages" id="chat-messages">
            <!-- Messaggio di benvenuto -->
            <div class="msg bot">
                <div class="msg-avatar"><i class="fa fa-robot"></i></div>
                <div class="msg-bubble">Ciao! Sono l'assistente del gestionale ABB Calibration Manager.

Posso aiutarti con domande su:
• Come usare le funzioni del sistema
• Gestione macchinari e tarature
• Configurazione email e QR code
• Risoluzione di problemi comuni

Come posso aiutarti?</div>
            </div>
        </div>

        <!-- Suggerimenti rapidi -->
        <div class="chat-suggestions" id="suggestions">
            <button class="suggestion-btn" onclick="sendSuggestion(this)">Come aggiungo una taratura?</button>
            <button class="suggestion-btn" onclick="sendSuggestion(this)">Scarica PDF scadenze 30 giorni</button>
            <button class="suggestion-btn" onclick="sendSuggestion(this)">PDF tutti i macchinari</button>
            <button class="suggestion-btn" onclick="sendSuggestion(this)">Come scarico un QR code?</button>
        </div>

        <div class="chat-input-area">
            <textarea id="chat-input" placeholder="Scrivi una domanda..." rows="1"></textarea>
            <button id="send-btn" onclick="sendMessage()">
                <i class="fa fa-paper-plane"></i> Invia
            </button>
        </div>
    </div>
</div>

<script>
const messagesEl = document.getElementById('chat-messages');
const inputEl    = document.getElementById('chat-input');
const sendBtn    = document.getElementById('send-btn');
const suggestEl  = document.getElementById('suggestions');

// Auto-resize textarea
inputEl.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Invio con Enter (Shift+Enter = a capo)
inputEl.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
});

function sendSuggestion(btn) {
    inputEl.value = btn.textContent;
    suggestEl.style.display = 'none';
    sendMessage();
}

function formatAIResponse(text) {
    // marked.parse trasforma il Markdown in HTML
    // Usiamo mangle: false e headerIds: false per evitare warning nelle versioni recenti
    return marked.parse(text, {
        mangle: false,
        headerIds: false
    });
}

function addMessage(text, role) {
    const icon  = role === 'user' ? 'fa-user' : 'fa-robot';
    const div   = document.createElement('div');
    div.className = 'msg ' + role;
    
    // Se è il bot, formattiamo il Markdown. Se è l'utente, mostriamo testo semplice.
    const content = role === 'bot' ? formatAIResponse(text) : escapeHtml(text);
    
    div.innerHTML = `
        <div class="msg-avatar"><i class="fa ${icon}"></i></div>
        <div class="msg-bubble">${content}</div>
    `;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
    return div;
}

function addTyping() {
    const div = document.createElement('div');
    div.className = 'msg bot';
    div.id = 'typing';
    div.innerHTML = `
        <div class="msg-avatar"><i class="fa fa-robot"></i></div>
        <div class="msg-bubble">
            <div class="typing-indicator">
                <span></span><span></span><span></span>
            </div>
        </div>
    `;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
}

function removeTyping() {
    const t = document.getElementById('typing');
    if (t) t.remove();
}


function addMessageWithPDF(text, pdfUrl, pdfLabel) {
    const div = document.createElement('div');
    div.className = 'msg bot';
    const content = text ? formatAIResponse(text) : '';
    div.innerHTML = `
        <div class="msg-avatar"><i class="fa fa-robot"></i></div>
        <div class="msg-bubble">
            ${content}
            <a href="${pdfUrl}" target="_blank" download class="pdf-download-btn">
                <i class="fa fa-file-pdf-o"></i> ${pdfLabel || 'Scarica PDF'}
            </a>
        </div>
    `;
    messagesEl.appendChild(div);
    messagesEl.scrollTop = messagesEl.scrollHeight;
}

function escapeHtml(str) {
    return str.replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#38;' }[m];
    });
}

async function sendMessage() {
    const text = inputEl.value.trim();
    if (!text) return;

    // Nasconde suggerimenti dopo la prima domanda
    suggestEl.style.display = 'none';

    addMessage(text, 'user');
    inputEl.value = '';
    inputEl.style.height = 'auto';
    sendBtn.disabled = true;

    addTyping();

    try {
        const res = await fetch('<?= BASE_URL ?>/chatbot/chat_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ message: text }),
        });
        const data = await res.json();
        removeTyping();

        if (data.error) {
            addMessage('⚠️ Errore: ' + data.error, 'bot');
        } else if (data.pdf_url) {
            addMessageWithPDF(data.answer, data.pdf_url, data.pdf_label);
        } else {
            addMessage(data.answer, 'bot');
        }
    } catch (e) {
        removeTyping();
        addMessage('⚠️ Impossibile contattare il server. Controlla la connessione.', 'bot');
    }

    sendBtn.disabled = false;
    inputEl.focus();
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer_admin.php'; ?>
