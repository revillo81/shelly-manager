<?php

function config(string $key = null, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    if ($key === null) {
        return $config;
    }
    return $config[$key] ?? $default;
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function base_url(): string
{
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    if ($script === '/' || $script === '.') {
        $script = '';
    }
    return $script;
}

function url(string $path): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function input(string $key, $default = null)
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function flash_success(string $message): void
{
    $_SESSION['flash_success'] = $message;
}

function flash_error(string $message): void
{
    $_SESSION['flash_error'] = $message;
}

function save_config(array $newConfig): void
{
    $file = __DIR__ . '/../config/config.php';
    $export = var_export($newConfig, true);
    $content = "<?php\n/**\n * Zentrale Konfiguration.\n */\nreturn " . $export . ";\n";
    file_put_contents($file, $content);
}
