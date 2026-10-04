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
    public function getAll(string $sort = 'title', string $direction = 'asc', string $search = '', int $page = 1, int $perPage = 10): array
    {
        [$where, $params] = $this->searchWhere($search);

        if ($direction === 'none') {
            $order = 'id ASC';
        } else {
            $column = in_array($sort, self::SORTABLE, true) ? $sort : 'title';
            $order = "{$column} " . ($direction === 'desc' ? 'DESC' : 'ASC') . ', title ASC';
        }

        $offset = max(0, $page - 1) * $perPage;

        $stmt = $this->db->prepare("SELECT id, title, author, year FROM books {$where} ORDER BY {$order} LIMIT :limit OFFSET :offset");
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function countAll(string $search = ''): int
    {
        [$where, $params] = $this->searchWhere($search);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM books {$where}");
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /** @return array{0: string, 1: array<string, string>} */
    private function searchWhere(string $search): array
    {
        if ($search === '') {
            return ['', []];
        }

        return [
            'WHERE title LIKE :search OR author LIKE :search OR CAST(year AS CHAR) LIKE :search',
            ['search' => '%' . $search . '%'],
        ];
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

    /** @param array{title: string, author: string, year: int, annotation: ?string, rating: ?int} $book */
    public function update(int $id, array $book): void
    {
        $this->db
            ->prepare('UPDATE books SET title = :title, author = :author, year = :year, annotation = :annotation, rating = :rating WHERE id = :id')
            ->execute([...$book, 'id' => $id]);
    }

    /**
     * Kniha je duplicitní, pokud má stejný název + autora + rok.
     *
     * @param array{title: string, author: string, year: int} $book
     */
    public function isDuplicate(array $book, ?int $exceptId = null): bool
    {
        $sql = 'SELECT 1 FROM books WHERE title = ? AND author = ? AND year = ?';
        $params = [$book['title'], $book['author'], $book['year']];

        if ($exceptId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $exceptId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    public function delete(int $id): void
    {
        $this->db->prepare('DELETE FROM books WHERE id = ?')->execute([$id]);
    }
}
