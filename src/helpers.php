<?php
declare(strict_types=1);

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