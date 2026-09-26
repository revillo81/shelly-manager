# Versionshistorie / Changelog

## 1.4.0 – Vollständige Mobil-Kompatibilität & Detailseiten-Optimierung
- Alle Detailseiten (Gerätesteuerung, Geräteeinstellungen, Skripte, Einstellungen, Gerät hinzufügen) vollständig für Smartphones und kleine Bildschirme optimiert
- Responsive Gerätenavigation (Subnav): Touch-optimierte Buttons in sauberem 2-Spalten-Raster
- Live-Messwerte auf der Geräteseite im gleichmäßigen 2-Spalten-Raster für kompakte Übersicht auf jedem Smartphone
- Geräteeigenschaften in sauberer zweispaltiger Eigenschaftstabelle dargestellt
- Steuerungskanäle (Schalter & Rollläden) mit voller Breite und großen Touch-Flächen
- Schnellaktionen auf der Einstellungsseite (Umbenennen, Firmware, Authentifizierung, Reset) mit für Fingerbedienung optimierten Eingabefeldern und Schaltflächen
- Skript-Verwaltung: 2-Spalten-Aktionsbuttons und automatisches Weiterscrollen zum Code-Editor bei Auswahl eines Skripts
- Dynamisches Cache-Busting für CSS und JS (`?v=filemtime`) zur sofortigen Aktualisierung auf Mobilgeräten ohne manuelles Leeren des Browser-Caches
- Modernes SVG-Hamburger-Menü für zuverlässige Anzeige auf allen mobilen Browsern (iOS Safari, Android Chrome)

## 1.3.0 – Geräteeinstellungen wie in der offiziellen Shelly-App
- Geräteeinstellungen sind jetzt wie in der offiziellen Shelly-Oberfläche in Kategorien gruppiert: Netzwerkeinstellungen, Verbindungseinstellungen, Geräteeinstellungen, Komponenten
- Neuer Bereich „Schnellaktionen" auf der Einstellungsseite:
  - Gerätename ändern
  - Firmware: auf Updates prüfen und aktualisieren (Gen1 und Gen2/Gen3)
  - Passwortschutz aktivieren/deaktivieren bzw. Passwort ändern (Gen2/Gen3 inkl. sicherer Schlüsselberechnung, Gen1 über die Login-Einstellungen)
  - Werksreset (nur Gen2/Gen3 – bei Gen1/Classic-Geräten gibt es dafür keine API, es wird ein Hinweis auf den physischen Reset-Taster angezeigt)
  - Neustart weiterhin direkt verfügbar

## 1.2.0 – Modernes Design & Live-Messwerte
- Überarbeitetes, moderneres Erscheinungsbild (Farben, Schatten, Karten, Buttons, Toggle-Schalter)
- Live-Messwerte direkt in der Baumansicht auf der Startseite (Leistung, Spannung, Strom, Temperatur, Luftfeuchtigkeit, Batterie), automatische Aktualisierung alle 20 Sekunden
- Geräte-Detailseite zeigt Live-Messwerte als übersichtliche Kacheln statt nur als Rohdaten
- Schalter werden als moderne Toggle-Switches dargestellt statt als einfache Ein/Aus-Buttons
- Paralleler Abruf der Live-Werte aller Geräte für ein performantes Dashboard auch bei vielen Geräten

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
