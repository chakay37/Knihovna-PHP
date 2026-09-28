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
    public function getAll(string $sort = 'title', string $direction = 'asc'): array
    {
        $column = self::SORTABLE[$sort] ?? 'title';
        $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

        return $this->db
            ->query("SELECT id, title, author, year FROM books ORDER BY {$column} {$direction}, title ASC")
            ->fetchAll();
    }


    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $book = $stmt->fetch();
        if ($book === false) {
            return null;
        }
        $book['id'] = (int) $book['id'];
        $book['year'] = (int) $book['year'];
        $book['rating'] = $book['rating'] === null ? null : (int) $book['rating'];
        return $book;
    }
}