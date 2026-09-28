<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

use App\Repositories\BookRepository;

$sort = in_array($_GET['sort'] ?? '', BookRepository::SORTABLE, true) ? $_GET['sort'] : 'title';
$dir = ($_GET['dir'] ?? '') === 'desc' ? 'desc' : 'asc';
$books = books()->getAll($sort, $dir);

$sortLink = static function (string $column, string $label) use ($sort, $dir): string {
    $isActive = $sort === $column;
    $nextDir = $isActive && $dir === 'asc' ? 'desc' : 'asc';
    $ariaSort = $isActive ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none';
    $arrow = $isActive ? ($dir === 'asc' ? ' ▲' : ' ▼') : '';
    return sprintf(
        '<th scope="col" aria-sort="%s"><a href="?sort=%s&amp;dir=%s">%s<span aria-hidden="true">%s</span></a></th>',
        $ariaSort, $column, $nextDir, e($label), $arrow
    );
};

ob_start();
?>
<div class="title-container">
    <h1 class="title">Seznam knih</h1>

    <div class="table-wrap">
        <table class="book-table">
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
                    <td><a href="/kniha/?id=<?= (int) $book['id'] ?>"><?= e($book['title']) ?></a></td>
                    <td><?= e($book['author']) ?></td>
                    <td class="num"><?= (int) $book['year'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
layout('Seznam knih', ob_get_clean());
