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
$books = (new BookRepository($db))->all($sort, $dir);

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
?>

<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Knihovna</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,400;7..72,600;7..72,700&family=Courier+Prime&display=swap">
    <link rel="stylesheet" href="/assets/css/app.css">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
    <header class="site-header">
        <a class="brand" href="/">>Knihovna</a>
        <nav class="site-nav" aria-label="Navigace">
            <a href="/">Seznam knih</a>
            <?php if (Auth::checkToken()): ?>
                <a href="/admin">Správa</a>
                <a href="/admin/new">Přidat knihu</a>
                <a href="/admin/import">Import</a>
                <form method="post" action="/admin/odhlaseni" class="nav-logout">
                    <button type="submit" class="link-button">Odhlásit <?= Auth::getUser() !== null ? e(Auth::getUser()['username']) : '' ?></button>
                </form>
            <?php else: ?>
                <a href="/admin/prihlaseni">Administrace</a>
            <?php endif; ?>
        </nav>
    </header>
    <main>
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
                    <td><a href="/kniha/<?= (int) $book['id'] ?>" data-book-id="<?= (int) $book['id'] ?>"><?= e($book['title']) ?></a></td>
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
    </main>
</body>
</html>
