<?php

class Lang
{
    private static array $strings = [];
    private static string $current = 'de';

    public static function init(): void
    {
        $available = ['de', 'en'];

        if (isset($_GET['lang']) && in_array($_GET['lang'], $available, true)) {
            $_SESSION['lang'] = $_GET['lang'];
        }

        self::$current = $_SESSION['lang'] ?? config('default_lang', 'de');
        if (!in_array(self::$current, $available, true)) {
            self::$current = 'de';
        }

        $file = __DIR__ . '/../lang/' . self::$current . '.php';
        self::$strings = file_exists($file) ? require $file : [];
    }

    public static function current(): string
    {
        return self::$current;
    }

    public static function t(string $key): string
    {
        return self::$strings[$key] ?? $key;
    }
}

function t(string $key): string
{
    return Lang::t($key);
}
