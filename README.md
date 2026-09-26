# Shelly Manager

Eine schlanke PHP-Webanwendung zur zentralen Verwaltung von Shelly-Geräten über deren lokale HTTP-API (keine Cloud nötig).

## Funktionen
- Automatische Erkennung von Shelly Gen1 (Classic) und Gen2/Gen3 (Plus/Pro) Geräten
- Manuelles Hinzufügen per IP-Adresse oder Netzwerk-Scan über einen IP-Bereich (Standard: `192.168.1.0/24`)
- Baumstruktur mit Gruppen (z.B. Räume) auf der Startseite, Detailseite pro Gerät
- Steuerung von Schaltern/Rollläden und Live-Statusabfrage direkt über die Weboberfläche
- Unterstützung für passwortgeschützte Shelly-Geräte (Zugangsdaten werden verschlüsselt gespeichert)
- SQLite als Standarddatenbank (kein Setup nötig), optionale Umschaltung auf MariaDB/MySQL über die Einstellungen
- Mehrsprachig: Deutsch / Englisch
- Admin-Login mit Passwortschutz, CSRF-Schutz
- Hilfe-Seite und Versionshistorie (`CHANGELOG.md`)

## Installation

Voraussetzungen: PHP 8.0+ mit den Erweiterungen `pdo_sqlite` (Standard) bzw. `pdo_mysql` (optional), `curl`, `openssl`, `json`.

Document Root ist der Ordner `public/`. Alle anderen Ordner (`src`, `config`, `database`, `lang`, `templates`) müssen **nicht** öffentlich erreichbar sein (Apache: `.htaccess`-Dateien sind bereits enthalten).

### Lokaler Test mit dem PHP-Entwicklungsserver

```bash
php -S localhost:8000 -t public
```

Anschließend `http://localhost:8000` im Browser öffnen.

Die SQLite-Datenbank sowie der Verschlüsselungsschlüssel für gespeicherte Geräte-Passwörter werden beim ersten Aufruf automatisch unter `database/shelly.sqlite` bzw. `config/app_key.php` angelegt.

### Standard-Zugangsdaten

- Benutzername: `admin`
- Passwort: `admin123`

**Bitte nach der ersten Anmeldung unter „Einstellungen“ ändern.**

### Wechsel auf MariaDB/MySQL

Unter „Einstellungen“ → „Datenbank“ kann der Datenbanktyp umgeschaltet werden. Die Zieldatenbank muss vorher angelegt sein (Zugriff für den angegebenen Benutzer). Das Schema wird beim Wechsel automatisch erzeugt; vorhandene Daten aus SQLite werden nicht automatisch übernommen.

## Shelly-API

Die Anwendung nutzt ausschließlich die lokale, dokumentierte HTTP-API der Shelly-Geräte:
https://shelly-api-docs.shelly.cloud/
