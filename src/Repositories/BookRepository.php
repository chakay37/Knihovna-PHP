<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class BookRepository
{
    public const SORTABLE = ['title' => 'title', 'author' => 'author', 'year' => 'year'];

    public function __construct(private readonly PDO $db)
    {
    }

        /** @return list<array<string, mixed>> */
    public function all(string $sort = 'title', string $direction = 'asc'): array
    {
        $column = self::SORTABLE[$sort] ?? 'title';
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        return $this->db
            ->query("SELECT id, title, author, year FROM books ORDER BY {$column} {$direction}, title ASC")
            ->fetchAll();
    }
}