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

    /** @param array{title: string, author: string, year: int, annotation: ?string, rating: ?int} $book */
    public function create(array $book): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO books (title, author, year, annotation, rating)
             VALUES (:title, :author, :year, :annotation, :rating)'
        );
        $stmt->execute([
            'title' => $book['title'],
            'author' => $book['author'],
            'year' => $book['year'],
            'annotation' => $book['annotation'],
            'rating' => $book['rating'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Kniha je duplicitní, pokud má stejný název + autora + rok. */
    public function isDuplicate(string $title, string $author, int $year): bool
    {
        $stmt = $this->db->prepare(
            'SELECT 1 FROM books WHERE title = :title AND author = :author AND year = :year LIMIT 1'
        );
        $stmt->execute(['title' => $title, 'author' => $author, 'year' => $year]);
        return $stmt->fetchColumn() !== false;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM books WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM books')->fetchColumn();
    }
}