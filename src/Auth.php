<?php

declare(strict_types=1);
namespace App;

// Admin přihlášení: ověření hesla, čtení/mazání přihlašovací session
class Auth
{
    // Ověří jméno a heslo; při úspěchu zapíše admina do session a vrátí true.
    public static function attemptLogin(string $username, string $password): bool
    {
        self::ensureAdminFromEnv(); // Při prvním přihlášení založí účet z .env.

        // Najde řádek podle uživatelského jména.
        $stmt = Database::connection()->prepare('SELECT id, username, password_hash FROM admin WHERE username = ?');
        $stmt->execute([$username]);
        $admin = $stmt->fetch(); // false, pokud takové jméno neexistuje.

        // password_verify porovná zadané heslo s uloženým hashem.
        if ($admin === false || !password_verify($password, $admin['password_hash'])) {
            return false;
        }

        // Po úspěšném přihlášení se vydá nové ID session.
        session_regenerate_id(true);
        $_SESSION['admin'] = ['id' => (int) $admin['id'], 'username' => $admin['username']];

        return true;
    }

    // true, pokud je v aktuální session uložený přihlášený admin.
    public static function isToken(): bool
    {
        return isset($_SESSION['admin']);
    }

    /** @return array{id: int, username: string} | null Přihlášený admin, nebo null. */
    public static function getToken(): ?array
    {
        return $_SESSION['admin'] ?? null;
    }

    // Odhlášení. Zahodí celou session a vydá nové, prázdné ID.
    public static function logout(): void
    {
        // Smaže starou session a vydá nové, prázdné ID.
        session_regenerate_id(true);
        $_SESSION = [];
    }

    // Prázdnou tabulku admin naplní účtem z ADMIN_USERNAME a ADMIN_PASSWORD v .env.
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
