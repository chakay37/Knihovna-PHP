<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            env('DB_HOST', 'db'),
            env('DB_PORT', '3306'),
            env('DB_NAME', 'knihovna')
        );

        self::$pdo = new PDO($dsn, env('DB_USER'), env('DB_PASSWORD'));

        return self::$pdo;
    }
}