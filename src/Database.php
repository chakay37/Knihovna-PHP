<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    // Připojení pro všechny requesty. 
    // Statická vlastnost žije po dobu běhu.
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        // ??=: pokud $pdo ještě není vytvořeno, vytvoří se a uloží.
        return self::$pdo ??= new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', 'db'),
                env('DB_PORT', '3306'),
                env('DB_NAME', 'knihovna')
            ),
            // Přihlašovací údaje bez výchozí hodnoty.
            env('DB_USER'),
            env('DB_PASSWORD')
        );
    }
}
