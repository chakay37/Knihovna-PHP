<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        return self::$pdo ??= new PDO(
            sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                env('DB_HOST', 'db'),
                env('DB_PORT', '3306'),
                env('DB_NAME', 'knihovna')
            ),
            env('DB_USER'),
            env('DB_PASSWORD')
        );
    }
}
