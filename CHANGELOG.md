# Versionshistorie / Changelog

## 1.1.0 – Erweiterte Geräteeinstellungen
- Neue Seite „Geräteeinstellungen“: alle vom Gerät gemeldeten Konfigurationsabschnitte (WLAN, MQTT, Cloud, Bluetooth, Relais/Schalter, Rollladen, Eingänge, System usw.) können direkt bearbeitet und gespeichert werden
- Neue Seite „Skripte“ (Gen2/Gen3): Skripte auflisten, erstellen, löschen, starten/stoppen, aktivieren/deaktivieren sowie den Skriptcode direkt im Browser bearbeiten
- Geräte-Neustart direkt über die Weboberfläche
- Generischer Konfigurationsmechanismus über die Shelly-RPC- bzw. Gen1-Settings-API, damit auch künftige/erweiterte Gerätefelder ohne Codeänderung bearbeitbar sind

## 1.0.0 – Erstveröffentlichung
- Zentrale Verwaltung von Shelly-Geräten (Gen1 Classic und Gen2/Gen3 Plus/Pro) über die lokale HTTP-API
- Automatische Geräteerkennung (Typ, Modell, Firmware, Generation)
- Manuelles Hinzufügen per IP-Adresse
- Netzwerk-Scan über einen konfigurierbaren IP-Bereich (Standard 192.168.1.0/24)
- Unterstützung für passwortgeschützte Shelly-Geräte (verschlüsselte Speicherung der Zugangsdaten)
- Baumstruktur mit Gruppen (Räume) auf der Startseite, Detailseite pro Gerät
- Live-Steuerung von Schaltern und Rollläden sowie Live-Statusabfrage
- SQLite-Datenbank ohne Setup, optionale Umschaltung auf MariaDB/MySQL über die Einstellungen
- Mehrsprachigkeit (Deutsch/Englisch)
- Admin-Login mit Passwortschutz, CSRF-Schutz auf allen Formularen
- Hilfe-Seite und Versionshistorie
