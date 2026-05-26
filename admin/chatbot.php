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
    /* ── Sorint palette override (solo in questa pagina) ──────── */
    .chat-wrap {
        --s-blue:   #0d2340;
        --s-blue2:  #1a3a6e;
        --s-orange: #f47920;
        --s-orange2:#d96510;
        --s-light:  #eef2f8;
        --s-border: #d0d8e8;

        max-width: 780px;
        margin: 0 auto;
    }

    .chat-box {
        background: #fff;
        border-radius: 4px;
        box-shadow: 0 2px 12px rgba(13,35,64,.13);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 180px);
        min-height: 420px;
        overflow: hidden;
        border-top: 3px solid var(--s-orange);
    }

    /* ── Header ─────────────────────────────────────────────── */
    .chat-header {
        background: var(--s-blue);
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

    .chat-header .sorint-badge {
        background: var(--s-orange);
        color: #fff;
        font-size: .68rem;
        font-weight: 800;
        padding: 2px 9px;
        border-radius: 20px;
        letter-spacing: .8px;
        text-transform: uppercase;
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

    /* ── Messages area ───────────────────────────────────────── */
    .chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 14px;
        background: var(--s-light);
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

    .msg.bot  .msg-avatar { background: var(--s-orange); color: #fff; }
    .msg.user .msg-avatar { background: var(--s-blue);   color: #fff; }

    .msg-bubble {
        padding: 11px 15px;
        border-radius: 4px;
        font-size: .9rem;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .msg.bot .msg-bubble {
        background: #fff;
        border: 1px solid var(--s-border);
        border-left: 3px solid var(--s-orange);
        white-space: normal;
    }

    .msg.user .msg-bubble {
        background: var(--s-blue);
        color: #fff;
    }

    .msg-bubble p             { margin-bottom: 10px; }
    .msg-bubble p:last-child  { margin-bottom: 0; }
    .msg-bubble ul,
    .msg-bubble ol            { margin: 10px 0; padding-left: 20px; }
    .msg-bubble li            { margin-bottom: 5px; }
    .msg-bubble strong        { font-weight: 700; color: #1a2a4a; }

    #reset-btn {
        margin-left: auto;
        background: transparent;
        border: 1px solid rgba(255,255,255,.25);
        color: rgba(255,255,255,.7);
        font-family: 'Barlow', sans-serif;
        font-size: .75rem;
        font-weight: 600;
        padding: 4px 10px;
        border-radius: 3px;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 5px;
        transition: background .15s, color .15s;
    }
    #reset-btn:hover {
        background: rgba(255,255,255,.12);
        color: #fff;
    }
    .typing-indicator {
        display: flex;
        gap: 5px;
        align-items: center;
        padding: 12px 15px;
    }

    .typing-indicator span {
        width: 7px;
        height: 7px;
        background: var(--s-orange);
        border-radius: 50%;
        animation: bounce .9s infinite;
    }

    .typing-indicator span:nth-child(2) { animation-delay: .15s; }
    .typing-indicator span:nth-child(3) { animation-delay: .3s; }

    @keyframes bounce {
        0%, 60%, 100% { transform: translateY(0); }
        30%            { transform: translateY(-6px); }
    }

    /* ── Input area ──────────────────────────────────────────── */
    .chat-input-area {
        border-top: 1px solid var(--s-border);
        padding: 14px 16px;
        background: #fff;
        display: flex;
        gap: 10px;
        flex-shrink: 0;
    }

    #chat-input {
        flex: 1;
        padding: 10px 14px;
        border: 1.5px solid var(--s-border);
        font-family: 'Barlow', sans-serif;
        font-size: .92rem;
        border-radius: 3px;
        resize: none;
        transition: border-color .15s;
        max-height: 120px;
        min-height: 42px;
    }

    #chat-input:focus {
        outline: none;
        border-color: var(--s-orange);
    }

    #send-btn {
        background: var(--s-orange);
        color: #fff;
        border: none;
        padding: 0 20px;
        font-family: 'Barlow', sans-serif;
        font-weight: 700;
        font-size: .9rem;
        cursor: pointer;
        border-radius: 3px;
        transition: background .15s;
        display: flex;
        align-items: center;
        gap: 7px;
        white-space: nowrap;
    }

    #send-btn:hover:not(:disabled) { background: var(--s-orange2); }
    #send-btn:disabled { opacity: .5; cursor: not-allowed; }

    /* ── Suggestions ─────────────────────────────────────────── */
    .chat-suggestions {
        padding: 0 20px 10px;
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }

    .suggestion-btn {
        background: #fff;
        border: 1px solid var(--s-border);
        color: var(--s-blue2);
        font-family: 'Barlow', sans-serif;
        font-size: .78rem;
        padding: 5px 12px;
        border-radius: 20px;
        cursor: pointer;
        transition: border-color .15s, color .15s, background .15s;
    }

    .suggestion-btn:hover {
        border-color: var(--s-orange);
        color: var(--s-orange);
        background: #fff8f2;
    }

    /* ── PDF / Excel download button ─────────────────────────── */
    .pdf-download-btn {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        margin-top: 12px;
        padding: 8px 16px;
        background: var(--s-orange);
        color: #fff !important;
        border-radius: 4px;
        font-size: .82rem;
        font-weight: 700;
        text-decoration: none !important;
        transition: background .15s;
        letter-spacing: .3px;
    }
    .pdf-download-btn:hover { background: var(--s-orange2); }
    .pdf-download-btn i     { font-size: .9rem; }

    /* ── Page header tweak ───────────────────────────────────── */
    .sorint-powered {
        font-size: .78rem;
        color: #666;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .sorint-powered strong { color: var(--s-orange); }
</style>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

<div class="chat-wrap">
    <div class="page-header">
        <h2><i class="fa fa-robot" style="color:#f47920"></i> Assistente AI</h2>
        <span class="sorint-powered">
            Sviluppato nell'ambito del progetto
            <strong>Lock &amp; Learn</strong>
            · <span style="color:#0d2340;font-weight:700">Sorint.lab</span>
            · Powered by Groq &amp; Llama 3
        </span>
    </div>

    <div class="chat-box">
        <div class="chat-header">
            <div class="ai-dot"></div>
            Assistente ABB Calibration Manager
            <span class="sorint-badge">Sorint.lab</span>
            <button id="reset-btn" onclick="resetChat()" title="Pulisci chat">
                <i class="fa fa-rotate-left"></i> Reset
            </button>
        </div>

        <div class="chat-messages" id="chat-messages">
            <!-- Messaggio di benvenuto -->
            <div class="msg bot">
                <div class="msg-avatar"><i class="fa fa-robot"></i></div>
                <div class="msg-bubble">Ciao! Sono l'assistente del gestionale <strong>ABB Calibration Manager</strong>, realizzato da <strong>Sorint.lab</strong> nell'ambito del programma <strong>Lock &amp; Learn</strong>.

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
    sendMessage();
}

function formatAIResponse(text) {
    return marked.parse(text, { mangle: false, headerIds: false });
}

function addMessage(text, role) {
    const icon  = role === 'user' ? 'fa-user' : 'fa-robot';
    const div   = document.createElement('div');
    div.className = 'msg ' + role;
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

function resetChat() {
    messagesEl.innerHTML = `
        <div class="msg bot">
            <div class="msg-avatar"><i class="fa fa-robot"></i></div>
            <div class="msg-bubble">Ciao! Sono l'assistente del gestionale <strong>ABB Calibration Manager</strong>, realizzato da <strong>Sorint.lab</strong> nell'ambito del programma <strong>Lock &amp; Learn</strong>.

Posso aiutarti con domande su:
• Come usare le funzioni del sistema
• Gestione macchinari e tarature
• Configurazione email e QR code
• Risoluzione di problemi comuni

Come posso aiutarti?</div>
        </div>`;
    inputEl.value = '';
    inputEl.style.height = 'auto';
    inputEl.focus();
}

async function sendMessage() {
    const text = inputEl.value.trim();
    if (!text) return;

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
