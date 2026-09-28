<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../src/helpers.php';
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/Repositories/BookRepository.php';


use App\Auth;
use App\Database;
use App\Repositories\BookRepository;

$sort = isset(BookRepository::SORTABLE[$_GET['sort'] ?? '']) ? $_GET['sort'] : 'title';
$dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';

$db = Database::connection();
/** @var list<array<string, mixed>> $books */
$books = (new BookRepository($db))->getAll($sort, $dir);

$sortLink = static function (string $column, string $label) use ($sort, $dir): string {
    $isActive = $sort === $column;
    $nextDir = $isActive && $dir === 'asc' ? 'desc' : 'asc';
    $ariaSort = $isActive ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none';
    $arrow = $isActive ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    return sprintf(
        '<th scope="col" aria-sort="%s"><a href="?sort=%s&amp;dir=%s">%s<span aria-hidden="true">%s</span></a></th>',
        $ariaSort, e($column), $nextDir, e($label), $arrow
    );
};

ob_start();

?>


<div class="title-container">
    <h1 class="title">Seznam knih</h1>

    <div class="table-wrap">
        <table class="book-table" data-book-table>
            <thead>
                <tr>
                    <?= $sortLink('title', 'Název') ?>
                    <?= $sortLink('author', 'Autor') ?>
                    <?= $sortLink('year', 'Rok vydání') ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($books as $book): ?>
                <tr>
                    <td><a href="/kniha/?id=<?= (int) $book['id'] ?>" data-book-id="<?= (int) $book['id'] ?>"><?= e($book['title']) ?></a></td>
                    <td><?= e($book['author']) ?></td>
                    <td class="num"><?= (int) $book['year'] ?></td>
                </tr>
            <?php endforeach; ?>
            <tr class="book-table__empty" data-filter-empty hidden>
                <td colspan="3">Hledanému výrazu neodpovídá žádná kniha.</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<?php

layout('Seznam knih', ob_get_clean());
