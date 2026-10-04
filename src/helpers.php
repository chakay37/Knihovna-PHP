<?php
declare(strict_types=1);

session_start();

require_once __DIR__ . '/Auth.php'; // Třída pro přihlášení/odhlášení a kontrolu admin session.
require_once __DIR__ . '/Database.php'; // Vytvoření PDO připojení k databázi.
require_once __DIR__ . '/BookValidator.php'; // Validace dat formuláře/importu knihy.
require_once __DIR__ . '/Repositories/BookRepository.php'; // Práce s tabulkou books v databázi.

use App\Auth;
use App\Database;
use App\Repositories\BookRepository;

// Escapování výstupu do HTML.
// Aby uživatelský vstup (např. název knihy) nemohl provést XSS.
function e(mixed $value): string
{
    // ENT_QUOTES: escapuje i jednoduché uvozovky (bezpečné i v atributech '...').
    // ENT_SUBSTITUTE: neplatné UTF-8 znaky nahradí místo chyby/prázdného výstupu.
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Přečte proměnnou prostředí (.env); vrátí $default, pokud klíč neexistuje.
function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    // getenv() vrací false (ne null), když proměnná neexistuje – nutno ošetřit zvlášť.
    return $value === false ? $default : $value;
}

// Vykreslí stránku do společné šablony (hlavička/patička z layout.php).
// $content je už hotové HTML těla stránky (obvykle z ob_get_clean()).
function layout(string $title, string $content): void
{
    require __DIR__ . '/Views/layout.php';
}

/**
 * Vykreslí formulář knihy (společný pro vytvoření i editaci).
 *
 * @param array<string, string> $old Hodnoty k předvyplnění polí.
 * @param array<string, string> $errors Chybové hlášky podle názvu pole.
 */
function book_form(string $action, array $old, array $errors, string $submitLabel, string $cancelHref): string
{
    ob_start();
    require __DIR__ . '/Views/book_form.php';
    return ob_get_clean();
}

// Odešle HTTP přesměrování na $url.
function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

// true, pokud je aktuální požadavek POST (formulář byl odeslán).
function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// Ochrana admin-only stránek: není-li uživatel přihlášen, přesměruje na přihlášení
function require_admin(): void
{
    if (!Auth::isToken()) {
        redirect('/admin/prihlaseni/');
    }
}

// Vytvoří novou instanci repository nad aktuálním databázovým připojením.
// Volá se znovu na každém místě potřeby (books()->...), repository samo je bezstavové.
function books(): BookRepository
{
    return new BookRepository(Database::connection());
}
