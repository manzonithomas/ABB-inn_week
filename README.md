# ABB Calibration Manager

Sistema web per la gestione delle tarature dei macchinari industriali, sviluppato in collaborazione con **ABB S.p.A. — Dalmine (BG)**.

Permette di registrare, monitorare e consultare le tarature degli strumenti di misura, con notifiche automatiche in prossimità della scadenza e accesso rapido tramite QR code.

Il progetto è hostato su un **Raspberry Pi** all'interno della rete scolastica.

---

## Funzionalità principali

- **Pannello admin** — gestione completa di macchinari, reparti e tarature con filtri, ordinamenti e ricerca
- **QR code** — ogni strumento ha un codice QR univoco: scansionandolo si accede subito alla scheda con lo stato aggiornato (valida / in scadenza / scaduta)
- **Pagine pubbliche** — consultabili senza login da smartphone, ideali per l'uso in reparto
- **PDF dinamici** — la pagina pubblica legge dal database in tempo reale: se viene caricata una nuova taratura, lo stato si aggiorna automaticamente senza ristampare nulla
- **Generazione fogli QR** — selezione multipla macchinari e generazione PDF con 12 QR per pagina A4, pronto per la stampa
- **Scheda strumento PDF** — PDF scaricabile con tutti i dati del macchinario e lo storico completo delle tarature
- **Lista PDF** — generazione di liste macchinari in PDF (tutti, per reparto, in scadenza)
- **Alert via email** — notifica automatica all'avvicinarsi della scadenza, configurabile con anticipo personalizzato
- **Esportazione CSV** — export dei dati filtrati compatibile con Excel
- **Dashboard** — statistiche immediate su strumenti scaduti, in scadenza e conformi, con calendario visivo
- **Assistente IA** — chatbot integrato che guida l'amministratore nell'uso del sistema e genera PDF su richiesta

---

## Stack tecnologico

| Componente | Tecnologia                                        |
| ---------- | ------------------------------------------------- |
| Backend    | PHP 8.1+ con PDO                                  |
| Database   | MySQL 8.0+ / MariaDB 10.4+                        |
| Frontend   | W3.CSS + Barlow / Barlow Condensed (Google Fonts) |
| QR code    | phpqrcode                                         |
| Email      | PHPMailer (via Composer)                          |
| PDF        | FPDF (via Composer)                               |
| IA chatbot | Groq API + Llama 3 + Marked.js                    |
| Hosting    | Raspberry Pi                                      |

---

## Struttura del progetto

```
calibration_manager/
├── config.php                  # Configurazione DB, costanti, helper globali
├── index.php                   # Redirect automatico (login o dashboard)
├── login.php                   # Accesso pannello admin
├── logout.php
├── .htaccess                   # Blocco accesso file sensibili, no directory listing
├── composer.json               # Dipendenze PHP (PHPMailer, FPDF)
├── calibration_manager.sql     # Schema completo del database con dati di esempio
│
├── includes/
│   ├── auth.php                # Gestione sessione e login admin
│   ├── queries.php             # Funzioni SQL riutilizzabili (sostituiscono le viste MySQL)
│   ├── header_admin.php        # Layout header area admin con navigazione
│   ├── footer_admin.php
│   ├── header_public.php       # Layout header pagine pubbliche
│   └── footer_public.php
│
├── admin/
│   ├── dashboard.php           # Dashboard con statistiche e calendario scadenze
│   ├── macchinari.php          # Lista macchinari con ricerca e filtro reparto
│   ├── macchinario_edit.php    # Aggiunta / modifica macchinario
│   ├── tarature.php            # Lista tarature con filtri, ordinamento, storico inline
│   ├── taratura_edit.php       # Aggiunta / modifica taratura + upload PDF
│   ├── reparti.php             # Gestione reparti (CRUD inline)
│   ├── scheda_pdf.php          # Generazione PDF scheda strumento con storico
│   ├── qr_download.php         # Download singolo QR code PNG
│   ├── qr_sheet.php            # Selezione multipla macchinari per foglio QR
│   ├── qr_sheet_pdf.php        # Generazione PDF con 12 QR per pagina A4
│   ├── lista_pdf.php           # Generazione PDF liste (tutti / reparto / scadenze)
│   ├── export.php              # Esportazione CSV con BOM UTF-8 per Excel
│   ├── storico_ajax.php        # Caricamento AJAX storico tarature precedenti
│   ├── chatbot.php             # Interfaccia assistente IA
│   └── impostazioni.php        # Configurazione email, preavviso, cambio password
│
├── public/
│   ├── macchinario.php         # Scheda pubblica via QR (per singolo strumento)
│   └── reparto.php             # Scheda pubblica via QR (elenco macchinari reparto)
│
├── chatbot/
│   ├── chat_handler.php        # Backend API per il chatbot (Groq + knowledge base)
│   └── knowledge_base.txt      # Base di conoscenza del chatbot
│
├── cron/
│   └── notifiche.php           # Script per invio email alert scadenze
│
├── lib/
│   └── phpqrcode/              # Libreria generazione QR code
│
├── vendor/                     # Dipendenze Composer (PHPMailer, FPDF)
│
└── uploads/
    └── tarature/               # PDF delle tarature caricati dagli utenti
```

---

## Come funziona il QR code

Ogni macchinario ha un token univoco di 64 caratteri hex generato con `random_bytes()` alla creazione. Il QR code codifica un URL del tipo:

```
https://php.progettothomas.it/abb/public/macchinario.php?token=XXXXXXXX
```

Scansionandolo con lo smartphone si accede alla scheda pubblica dello strumento, che mostra:
- Stato della taratura (valida / in scadenza / scaduta) con indicazione dei giorni rimanenti
- Dettagli dell'ultima taratura (tecnico, ente certificatore, numero certificato, esito)
- Download del PDF del certificato
- Storico delle tarature precedenti con relativi PDF

**I PDF sono dinamici:** la pagina legge sempre dal database, quindi se viene registrata una nuova taratura lo stato si aggiorna istantaneamente senza dover rigenerare o ristampare i QR code.

È previsto anche un QR da apporre all'ingresso di ogni reparto (`public/reparto.php`), che elenca tutti gli strumenti del reparto con il loro stato.

---

## Flessibilità e integrazione

L'architettura è pensata per essere adattabile:
- Il file `config.php` contiene tutta la configurazione della connessione al database: è sufficiente modificare le costanti per collegarsi a un database esterno
- Se i certificati di taratura sono già memorizzati in un database interno ABB, è possibile modificare le query in `includes/queries.php` per leggerli direttamente, senza stravolgere la struttura dell'applicazione
- Le notifiche email sono indipendenti e configurabili tramite la pagina Impostazioni

---

## Email alert (cron job)

Le notifiche di scadenza vengono inviate dallo script `cron/notifiche.php`, da configurare come cron job sul server:

```bash
# Ogni giorno alle 07:00
0 7 * * * php /var/www/html/calibration_manager/cron/notifiche.php >> /var/log/calibration_notifiche.log 2>&1
```

I parametri SMTP (host, porta, credenziali) si impostano direttamente nello script `cron/notifiche.php`. Il numero di giorni di preavviso è configurabile dalla pagina **Impostazioni** del pannello admin.

---

## Installazione

### Requisiti
- PHP 8.0+ con estensioni `pdo_mysql`, `gd`, `mbstring`
- MySQL 8.0+ o MariaDB 10.4+
- Composer (per le dipendenze)
- Accesso alla rete per le API Groq (chatbot, opzionale)

### 1. Clona il progetto
```bash
cd /var/www/html/
# o la directory desiderata
```

### 2. Crea il database
Importa il file `calibration_manager.sql` in phpMyAdmin o da terminale:
```bash
mysql -u root -p < calibration_manager.sql
```

### 3. Configura `config.php`
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'calibration_manager');
define('DB_USER', 'nome_utente');
define('DB_PASS', 'tua_password');
(di solito è nome utente 'root' con password vuota)
```

### 4. Installa le dipendenze
```bash
composer install
```

### 5. Permessi cartella uploads (Linux)
```bash
chmod 755 uploads/tarature/
```

### 6. Primo accesso
- URL: `https://php.progettothomas.it/abb`
- Password predefinita: gestita dall'hash nel database (admin123)
- Cambia la password al primo accesso da **Impostazioni**

---

## Sicurezza

- I token QR sono stringhe hex a 64 caratteri generate con `random_bytes()`, non prevedibili
- Tutte le query usano PDO con prepared statements
- La cartella `uploads/` contiene solo PDF, l'`.htaccess` blocca l'esecuzione di PHP
- File sensibili (`config.php`, `auth.php`, `composer.json`) protetti da `.htaccess`
- Le pagine pubbliche sono read-only: non permettono modifiche ai dati
- Si consiglia di abilitare HTTPS in produzione (già attivo sul Raspberry con proxy inverso)

---

## Crediti

Progetto realizzato nell'ambito del percorso **Info12** in collaborazione con **ABB S.p.A. — Dalmine (BG)**

| Nome            | Classe |
| --------------- | ------ |
| Thomas Manzoni  | 5Ai    |
| Mattia Esborni  | 5Ai    |
| Thomas Brattico | 3Ai    |
| Luca Cremaschi  | 5Ci    |
| Syed Raza       | 4Ai    |

I.T.I. Marconi — Dalmine (BG)  
A.S. 2025/2026
