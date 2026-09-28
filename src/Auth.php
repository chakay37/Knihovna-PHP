<?php

declare(strict_types=1);
namespace App;

use PDO;

class Auth
{
    public function __construct(private readonly PDO $db)
    {
    }

    public function attemptLogin(string $username, string $password): bool
    {
        return false;
    }

    public static function checkToken(): bool
    {
        return true;
        return isset($_SESSION['admin']);
    }

    /** @return array{id: int, username: string} | null */
    public static function getUser(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
