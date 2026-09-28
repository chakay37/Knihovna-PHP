<?php

declare(strict_types=1);
namespace App;

class Auth
{
    public static function attemptLogin(string $username, string $password): bool
    {
        self::ensureAdminFromEnv();

        $stmt = Database::connection()->prepare('SELECT id, username, password_hash FROM admin WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin === false || !password_verify($password, $admin['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin'] = ['id' => (int) $admin['id'], 'username' => $admin['username']];

        return true;
    }

    public static function isToken(): bool
    {
        return isset($_SESSION['admin']);
    }

    /** @return array{id: int, username: string} | null */
    public static function getToken(): ?array
    {
        return $_SESSION['admin'] ?? null;
    }

    public static function logout(): void
    {
        // Smaže starou session a vydá nové, prázdné ID
        session_regenerate_id(true);
        $_SESSION = [];
    }

    // Prázdnou tabulku admin naplní účtem z ADMIN_USERNAME a ADMIN_PASSWORD v .env
    private static function ensureAdminFromEnv(): void
    {
        $username = env('ADMIN_USERNAME');
        $password = env('ADMIN_PASSWORD');
        $db = Database::connection();

        if (!$username || !$password || $db->query('SELECT 1 FROM admin LIMIT 1')->fetchColumn()) {
            return;
        }

        $db->prepare('INSERT INTO admin (username, password_hash) VALUES (?, ?)')
            ->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    }
}
