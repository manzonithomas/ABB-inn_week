# ABB Calibration Manager

Sistema web per la gestione delle tarature dei macchinari — ABB S.p.A. Dalmine.

---

## Stack tecnologico

- **PHP 8.1+** con PDO
- **MySQL 8.0+ / MariaDB 10.4+**
- **W3.CSS** + font Barlow (Google Fonts)
- **phpqrcode** per la generazione dei QR code
- **PHPMailer** per le email di notifica (via Composer)

---

## Installazione (XAMPP / locale)

### 1. Posiziona il progetto
```
C:\xampp\htdocs\calibration_manager\
```

### 2. Crea il database
Importa `schema.sql` in phpMyAdmin oppure:
```bash
mysql -u root -p < schema.sql
```

### 3. Configura
Apri `config.php` e imposta:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'calibration_manager');
define('DB_USER', 'root');
define('DB_PASS', '');
define('BASE_URL', 'http://localhost/calibration_manager');
```

### 4. Installa PHPMailer (Composer)
```bash
cd calibration_manager
composer install
```

### 5. Installa phpqrcode
- Scarica da: https://phpqrcode.sourceforge.net/
- Estrai e posiziona la cartella come `lib/phpqrcode/`
- Verifica che esista il file `lib/phpqrcode/qrlib.php`

### 6. Permessi cartella uploads
Assicurati che la cartella `uploads/tarature/` sia scrivibile:
```bash
chmod 755 uploads/tarature/   # Linux/Mac
# Su Windows con XAMPP di solito non serve
```

### 7. Primo accesso
- URL: `http://localhost/calibration_manager/login.php`
- Password default: `changeme123`
- **Cambia subito la password** da Impostazioni dopo il primo login!

---

## Struttura cartelle

```
calibration_manager/
├── config.php                  # Configurazione DB, costanti, helpers
├── index.php                   # Redirect intelligente
├── login.php                   # Login admin
├── logout.php                  # Logout
├── composer.json               # Dipendenze PHP
├── schema.sql                  # Schema database
│
├── includes/
│   ├── auth.php                # Gestione sessione admin
│   ├── header_admin.php        # Layout header pannello admin
│   ├── footer_admin.php        # Layout footer pannello admin
│   ├── header_public.php       # Layout header pagine pubbliche
│   └── footer_public.php       # Layout footer pagine pubbliche
│
├── admin/
│   ├── dashboard.php           # Dashboard con statistiche
│   ├── macchinari.php          # Lista macchinari
│   ├── macchinario_edit.php    # Aggiungi / modifica macchinario
│   ├── tarature.php            # Lista tarature
│   ├── taratura_edit.php       # Aggiungi / modifica taratura
│   ├── qr_download.php         # Genera e scarica QR code PNG
│   └── impostazioni.php        # Cambio password + config email
│
├── public/
│   ├── macchinario.php         # Pagina QR macchinario (pubblica)
│   └── reparto.php             # Storico reparto (QR ingresso area)
│
├── cron/
│   └── notifiche.php           # Script email alert scadenze
│
├── lib/
│   ├── phpqrcode/              # Libreria QR code (da installare)
│   │   └── qrlib.php
│   └── font/
│       └── Barlow-Bold.ttf     # Font opzionale per QR (da scaricare)
│
├── uploads/
│   └── tarature/               # PDF tarature caricati
│
└── vendor/                     # Generato da Composer (non committare)
```

---

## QR code: come funziona

Ogni macchinario ha un `qr_token` univoco (stringa hex 64 char).

**QR su macchinario** → punta a:
```
http://tuodominio.it/public/macchinario.php?token=XXXXXXXX
```
Mostra l'**ultima taratura** con stato (valida/in scadenza/scaduta) e PDF.

**QR all'ingresso reparto** → punta a:
```
http://tuodominio.it/public/reparto.php?reparto=Assemblaggio VD4
```
Mostra lo **storico di tutti i macchinari** del reparto.

Per scaricare il QR da stampare: Admin → Macchinari → icona QR.

---

## Email alert (cron job)

### PaperCut (sviluppo locale)
1. Avvia PaperCut SMTP server
2. In `cron/notifiche.php` le impostazioni default sono già configurate per PaperCut su `localhost:25`
3. Test manuale: `php cron/notifiche.php`

### Produzione
Modifica le costanti SMTP in `cron/notifiche.php`:
```php
define('SMTP_HOST',   'smtp.tuoprovider.it');
define('SMTP_PORT',   587);
define('SMTP_AUTH',   true);
define('SMTP_USER',   'user@abbdalmine.it');
define('SMTP_PASS',   'password');
define('SMTP_SECURE', 'tls');
```

### Cron job (Linux)
```bash
crontab -e
# Aggiungi (ogni giorno alle 07:00):
0 7 * * * php /var/www/html/calibration_manager/cron/notifiche.php >> /var/log/calibration.log 2>&1
```

---

## Sicurezza (note per produzione)

- Mettere la cartella `uploads/` fuori dalla webroot, oppure aggiungere un `.htaccess` che blocchi l'esecuzione di PHP nella cartella uploads
- Abilitare HTTPS
- Cambiare la password di default `changeme123`
- Valutare di limitare l'accesso alla cartella `admin/` per IP (`.htaccess`)

---

## Crediti

Progetto scolastico — Classe 5ª Informatica  
In collaborazione con ABB S.p.A. — Dalmine (BG)
