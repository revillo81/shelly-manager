<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/' . $class . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

require __DIR__ . '/helpers.php';

session_name(config('session_name', 'shelly_manager_sid'));
session_start();

Database::pdo();
Lang::init();
