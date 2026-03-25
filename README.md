# ABB Calibration Manager

Sistema web per la gestione delle tarature dei macchinari, sviluppato in collaborazione con **ABB S.p.A. — Dalmine (BG)**.

Permette di registrare, monitorare e consultare le tarature degli strumenti di misura, con notifiche automatiche in prossimità della scadenza e accesso rapido tramite QR code.

---

## Funzionalità principali

- **Pannello admin** — gestione completa di macchinari, reparti e tarature
- **QR code** — ogni strumento ha un codice QR univoco: scansionandolo si accede subito alla scheda con lo stato aggiornato (valida / in scadenza / scaduta)
- **Pagine pubbliche** — consultabili senza login da smartphone, ideali per l'uso in reparto
- **Alert via email** — notifica automatica all'avvicinarsi della scadenza, configurabile con anticipo personalizzato
- **Esportazione PDF** — scheda completa dello strumento con storico tarature scaricabile in un click
- **Dashboard** — statistiche immediate su strumenti scaduti, in scadenza e conformi

---

## Stack tecnologico

| Componente | Tecnologia |
|---|---|
| Backend | PHP 8.1+ con PDO |
| Database | MySQL 8.0+ / MariaDB 10.4+ |
| Frontend | W3.CSS + Barlow (Google Fonts) |
| QR code | phpqrcode |
| Email | PHPMailer (via Composer) |
| PDF | FPDF |

---

## Struttura del progetto

```
calibration_manager/
├── config.php                  # Configurazione DB, costanti, helper globali
├── login.php                   # Accesso pannello admin
├── logout.php
├── composer.json               # Dipendenze PHP
├── schema.sql                  # Schema del database
│
├── includes/
│   ├── auth.php                # Gestione sessione admin
│   ├── header_admin.php        # Layout header area admin
│   ├── footer_admin.php
│   ├── header_public.php       # Layout header pagine pubbliche
│   ├── footer_public.php
│   └── queries.php             # Funzioni SQL riutilizzabili
│
├── admin/
│   ├── dashboard.php           # Dashboard con statistiche
│   ├── macchinari.php          # Lista macchinari
│   ├── macchinario_edit.php    # Aggiunta / modifica macchinario
│   ├── tarature.php            # Lista tarature
│   ├── taratura_edit.php       # Aggiunta / modifica taratura
│   ├── reparti.php             # Gestione reparti
│   ├── scheda_pdf.php          # Generazione PDF scheda strumento
│   ├── qr_download.php         # Download QR code PNG
│   ├── export.php              # Esportazione dati
│   └── impostazioni.php        # Cambio password e configurazione email
│
├── public/
│   ├── macchinario.php         # Scheda pubblica via QR (per strumento)
│   └── reparto.php             # Scheda pubblica via QR (per reparto)
│
├── cron/
│   └── notifiche.php           # Script per invio email alert scadenze
│
├── lib/
│   └── phpqrcode/              # Libreria generazione QR code
│
└── uploads/
    └── tarature/               # PDF delle tarature caricati dagli utenti
```

---

## Installazione in locale (XAMPP)

### 1. Posiziona il progetto
```
C:\xampp\htdocs\calibration_manager\
```

### 2. Crea il database
Apri phpMyAdmin, crea un database chiamato `calibration_manager` e importa il file `schema.sql`.

In alternativa da terminale:
```bash
mysql -u root < schema.sql
```

### 3. Configura `config.php`
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'calibration_manager');
define('DB_USER', 'root');
define('DB_PASS', '');  // password del tuo XAMPP, di solito vuota
```

### 4. Installa le dipendenze PHP
```bash
cd calibration_manager
composer install
```

### 5. Permessi cartella uploads (solo Linux/Mac)
```bash
chmod 755 uploads/tarature/
```

### 6. Primo accesso
- URL: `http://localhost/calibration_manager/login.php`
- Cambia la password al primo accesso da **Impostazioni**

---

## Come funziona il QR code

Ogni macchinario ha un token univoco generato alla creazione. Il QR code codifica un URL del tipo:

```
https://tuodominio.it/public/macchinario.php?token=XXXXXXXX
```

Scansionandolo con lo smartphone si accede alla scheda pubblica dello strumento, che mostra l'ultima taratura, lo stato di validità e il PDF allegato — senza bisogno di login.

È previsto anche un QR da apporre all'ingresso di ogni reparto, che elenca tutti gli strumenti del reparto con il loro stato.

---

## Email alert (cron job)

Le notifiche di scadenza vengono inviate dallo script `cron/notifiche.php`, da configurare come cron job sul server:

```bash
# Ogni giorno alle 07:00
0 7 * * * php /var/www/html/calibration_manager/cron/notifiche.php
```

I parametri SMTP (host, porta, credenziali) si impostano dalla pagina **Impostazioni** del pannello admin. Il numero di giorni di preavviso è anch'esso configurabile.

---

## Note sulla sicurezza

- La cartella `uploads/` contiene solo PDF e non esegue PHP
- I token QR sono stringhe hex a 64 caratteri generate con `random_bytes()`
- Tutte le query usano PDO con prepared statements (nessuna concatenazione diretta di input utente)
- Si consiglia di abilitare HTTPS in produzione

---

## Crediti

Progetto realizzato nell'ambito del percorso PCTO (ex alternanza scuola-lavoro)  
in collaborazione con **ABB S.p.A. — Dalmine (BG)**

| Nome | Classe |
|---|---|
| Thomas Manzoni | 5Ai |
| Mattia Esborni | 5Ai |
| Thomas Brattico | 3Ai |
| Luca Cremaschi | 5Ci |
| Syed Raza | 4Ai |

A.S. 2025/2026
