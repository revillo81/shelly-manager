<?php
/**
 * Zentrale Konfiguration.
 * Standard: SQLite (kein Setup notwendig). Optional Umschaltung auf MariaDB/MySQL,
 * entweder hier direkt oder komfortabel über die Einstellungen-Seite der Anwendung.
 */
return [
    'app_name' => 'Shelly Manager',
    'default_lang' => 'de',

    // 'sqlite' oder 'mysql'
    'db_driver' => 'sqlite',

    'sqlite' => [
        'path' => __DIR__ . '/../database/shelly.sqlite',
    ],

    'mysql' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'dbname' => 'shelly_manager',
        'user' => 'shelly',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    // Standard-IP-Range für den Netzwerk-Scan
    'default_ip_range' => '192.168.1.0/24',

    // Session-Cookie Name
    'session_name' => 'shelly_manager_sid',

    // Timeout für Verbindungen zu Shelly-Geräten (Sekunden)
    'device_timeout' => 3,

    // Anzahl paralleler Verbindungen beim Netzwerk-Scan
    'scan_parallelism' => 40,
];
