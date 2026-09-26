<?php

class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $driver = config('db_driver', 'sqlite');

        if ($driver === 'mysql') {
            $cfg = config('mysql');
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                $cfg['host'],
                $cfg['port'],
                $cfg['dbname'],
                $cfg['charset'] ?? 'utf8mb4'
            );
            self::$pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } else {
            $cfg = config('sqlite');
            $dir = dirname($cfg['path']);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            self::$pdo = new PDO('sqlite:' . $cfg['path'], null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
            self::$pdo->exec('PRAGMA foreign_keys = ON');
        }

        self::migrate(self::$pdo, $driver);

        return self::$pdo;
    }

    private static function migrate(PDO $pdo, string $driver): void
    {
        if ($driver === 'mysql') {
            $statements = [
                "CREATE TABLE IF NOT EXISTS users (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    username VARCHAR(100) NOT NULL UNIQUE,
                    password_hash VARCHAR(255) NOT NULL,
                    lang VARCHAR(5) NOT NULL DEFAULT 'de',
                    created_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

                "CREATE TABLE IF NOT EXISTS device_groups (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    parent_id INT NULL,
                    name VARCHAR(150) NOT NULL,
                    sort_order INT NOT NULL DEFAULT 0
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

                "CREATE TABLE IF NOT EXISTS devices (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    group_id INT NULL,
                    name VARCHAR(150) NOT NULL,
                    ip VARCHAR(45) NOT NULL,
                    mac VARCHAR(20) NULL,
                    type VARCHAR(100) NULL,
                    model VARCHAR(100) NULL,
                    generation INT NOT NULL DEFAULT 1,
                    app VARCHAR(100) NULL,
                    firmware VARCHAR(100) NULL,
                    auth_enabled TINYINT NOT NULL DEFAULT 0,
                    username VARCHAR(100) NULL,
                    password_enc TEXT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'unknown',
                    last_seen DATETIME NULL,
                    raw_info MEDIUMTEXT NULL,
                    notes TEXT NULL,
                    created_at DATETIME NOT NULL,
                    updated_at DATETIME NOT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

                "CREATE TABLE IF NOT EXISTS settings (
                    setting_key VARCHAR(100) PRIMARY KEY,
                    setting_value TEXT NULL
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            ];
        } else {
            $statements = [
                "CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT NOT NULL UNIQUE,
                    password_hash TEXT NOT NULL,
                    lang TEXT NOT NULL DEFAULT 'de',
                    created_at TEXT NOT NULL
                )",

                "CREATE TABLE IF NOT EXISTS device_groups (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    parent_id INTEGER NULL,
                    name TEXT NOT NULL,
                    sort_order INTEGER NOT NULL DEFAULT 0
                )",

                "CREATE TABLE IF NOT EXISTS devices (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    group_id INTEGER NULL,
                    name TEXT NOT NULL,
                    ip TEXT NOT NULL,
                    mac TEXT NULL,
                    type TEXT NULL,
                    model TEXT NULL,
                    generation INTEGER NOT NULL DEFAULT 1,
                    app TEXT NULL,
                    firmware TEXT NULL,
                    auth_enabled INTEGER NOT NULL DEFAULT 0,
                    username TEXT NULL,
                    password_enc TEXT NULL,
                    status TEXT NOT NULL DEFAULT 'unknown',
                    last_seen TEXT NULL,
                    raw_info TEXT NULL,
                    notes TEXT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                )",

                "CREATE TABLE IF NOT EXISTS settings (
                    setting_key TEXT PRIMARY KEY,
                    setting_value TEXT NULL
                )",
            ];
        }

        foreach ($statements as $sql) {
            $pdo->exec($sql);
        }

        $stmt = $pdo->query('SELECT COUNT(*) AS c FROM users');
        $count = (int)$stmt->fetch()['c'];
        if ($count === 0) {
            $pdo->prepare('INSERT INTO users (username, password_hash, lang, created_at) VALUES (?, ?, ?, ?)')
                ->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'de', now()]);
        }
    }
}
