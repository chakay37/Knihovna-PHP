<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

class BookRepository
{
    public const SORTABLE = ['title', 'author', 'year'];

    public function __construct(private readonly PDO $db)
    {
    }

    /** @return list<array<string, mixed>> */
    public function getAll(string $sort = 'title', string $direction = 'asc'): array
    {
        $column = in_array($sort, self::SORTABLE, true) ? $sort : 'title';
        $direction = $direction === 'desc' ? 'DESC' : 'ASC';

        return $this->db
            ->query("SELECT id, title, author, year FROM books ORDER BY {$column} {$direction}, title ASC")
            ->fetchAll();
    }

    public function count(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM books')->fetchColumn();
    }

    /** @return array<string, mixed>|null */
    public function get(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM books WHERE id = ?');
        $stmt->execute([$id]);

        return $stmt->fetch() ?: null;
    }

    /** @return list<array<string, mixed>> Ostatní knihy autora kromě knihy $exceptId */
    public function getByAuthor(string $author, int $exceptId): array
    {
        $stmt = $this->db->prepare('SELECT id, title, author, year FROM books WHERE author = ? AND id <> ? ORDER BY year, title');
        $stmt->execute([$author, $exceptId]);

        return $stmt->fetchAll();
    }

    /** @param array{title: string, author: string, year: int, annotation: ?string, rating: ?int} $book */
    public function create(array $book): int
    {
        $this->db
            ->prepare('INSERT INTO books (title, author, year, annotation, rating) VALUES (:title, :author, :year, :annotation, :rating)')
            ->execute($book);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Kniha je duplicitní, pokud má stejný název + autora + rok.
     *
     * @param array{title: string, author: string, year: int} $book
     */
    public function isDuplicate(array $book): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM books WHERE title = ? AND author = ? AND year = ?');
        $stmt->execute([$book['title'], $book['author'], $book['year']]);

        return $stmt->fetchColumn() !== false;
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM books WHERE id = ?')->execute([$id]);
    }
}
