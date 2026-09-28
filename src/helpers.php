<?php
declare(strict_types=1);

// Společný začátek každé stránky: session, třídy a pomocné funkce
session_start();

require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/BookValidator.php';
require_once __DIR__ . '/Repositories/BookRepository.php';

use App\Auth;
use App\Database;
use App\Repositories\BookRepository;

// Escapování výstupu do HTML
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function layout(string $title, string $content): void
{
    require __DIR__ . '/Views/layout.php';
}

function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

function is_post(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function require_admin(): void
{
    if (!Auth::isToken()) {
        redirect('/admin/prihlaseni/');
    }
}

function books(): BookRepository
{
    return new BookRepository(Database::connection());
}
