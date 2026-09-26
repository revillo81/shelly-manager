<?php

class Crypto
{
    private static function key(): string
    {
        $file = __DIR__ . '/../config/app_key.php';
        if (!file_exists($file)) {
            $key = bin2hex(random_bytes(32));
            file_put_contents($file, "<?php\nreturn " . var_export($key, true) . ";\n");
        }
        return require $file;
    }

    public static function encrypt(?string $plain): ?string
    {
        if ($plain === null || $plain === '') {
            return null;
        }
        $key = hash('sha256', self::key(), true);
        $iv = random_bytes(16);
        $cipher = openssl_encrypt($plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $cipher);
    }

    public static function decrypt(?string $encoded): ?string
    {
        if ($encoded === null || $encoded === '') {
            return null;
        }
        $key = hash('sha256', self::key(), true);
        $data = base64_decode($encoded);
        if ($data === false || strlen($data) < 17) {
            return null;
        }
        $iv = substr($data, 0, 16);
        $cipher = substr($data, 16);
        $plain = openssl_decrypt($cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return $plain === false ? null : $plain;
    }
}
