<?php

class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            if (!empty($user['lang'])) {
                $_SESSION['lang'] = $user['lang'];
            }
            return true;
        }
        return false;
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect('login.php');
        }
    }

    public static function logout(): void
    {
        $_SESSION = [];
        session_destroy();
    }

    public static function userId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function username(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    public static function changePassword(string $current, string $new): bool
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([self::userId()]);
        $user = $stmt->fetch();
        if (!$user || !password_verify($current, $user['password_hash'])) {
            return false;
        }
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), self::userId()]);
        return true;
    }
}
