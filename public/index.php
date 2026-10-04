<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

use App\Auth;
use App\Repositories\BookRepository;

$sort = in_array($_GET['sort'] ?? '', BookRepository::SORTABLE, true) ? $_GET['sort'] : 'title';
$dir = in_array($_GET['dir'] ?? '', ['asc', 'desc', 'none'], true) ? $_GET['dir'] : 'asc';
$search = trim((string) ($_GET['q'] ?? ''));
$is_list = ($_GET['view'] ?? 'list') !== 'cards';

$perPage = 9;
$total = books()->countAll($search);
$totalPages = max(1, (int) ceil($total / $perPage));
$page = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);
$books = books()->getAll($sort, $dir, $search, $page, $perPage);

$sortFieldLabels = [
    'title' => 'Název',
    'author' => 'Autor',
    'year' => 'Rok vydání',
];

$buildQuery = static fn (array $params): string => '?' . http_build_query(
    array_filter($params, static fn ($value) => $value !== null)
);

$baseQuery = [
    'sort' => $sort,
    'dir' => $dir,
    'view' => $is_list ? null : 'cards',
    'q' => $search !== '' ? $search : null,
];

$sortState = static function (string $column) use ($sort, $dir): array {
    $isActive = $sort === $column && $dir !== 'none';

    if (!$isActive) {
        $nextDir = 'asc';
    } elseif ($dir === 'asc') {
        $nextDir = 'desc';
    } else {
        $nextDir = 'none';
    }

    return [
        'ariaSort' => $isActive ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none',
        'arrow' => $isActive ? ($dir === 'asc' ? '▲' : '▼') : '<i class="fa-solid fa-sort"></i>',
        'nextDir' => $nextDir,
    ];
};

$sortLink = static function (string $column, string $label) use ($sortState, $baseQuery, $buildQuery): string {
    $state = $sortState($column);
    $href = $buildQuery([...$baseQuery, 'sort' => $column, 'dir' => $state['nextDir']]);

    return sprintf(
        '<th scope="col" aria-sort="%s"><a href="%s">%s <span aria-hidden="true">%s</span></a></th>',
        $state['ariaSort'], $href, e($label), $state['arrow']
    );
};

$viewHref = static fn (string $view): string => $buildQuery([...$baseQuery, 'view' => $view === 'list' ? null : $view]);

$pageHref = static fn (int $targetPage): string => $buildQuery([...$baseQuery, 'page' => $targetPage > 1 ? $targetPage : null]);

ob_start();
?>
<div class="title-container">
    <div class="page-head">
        <h1 class="title">Seznam knih</h1>
        <p class="page-head__meta"><?= $total ?> knih v evidenci</p>
    </div>

    <div class="toolbar">
        <button type="button" class="button" data-print><i class="fa-solid fa-print"></i> Tisk</button>

        <form method="get" class="search-form">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <input type="hidden" name="view" value="<?= $is_list ? 'list' : 'cards' ?>">
            <label class="visually-hidden" for="search-field">Hledat</label>
            <div class="search-group">
                <span class="search-field-wrap">
                    <input type="search" id="search-field" name="q" value="<?= e($search) ?>"
                           placeholder="Hledat podle názvu, autora nebo roku…" class="search-field">
                    <button type="button" class="search-field__clear" data-clear-search aria-label="Vymazat hledání">&times;</button>
                </span>
                <button type="submit" class="button"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <form method="get" class="sort-form">
            <input type="hidden" name="view" value="<?= $is_list ? 'list' : 'cards' ?>">
            <input type="hidden" name="q" value="<?= e($search) ?>">
            <input type="hidden" name="dir" id="sort-dir-input" value="<?= e($dir) ?>">
            Řadit podle:
            <span class="select-wrap button button--secondary">
                <select id="sort-field" name="sort">
                    <?php foreach ($sortFieldLabels as $column => $label): ?>
                        <option value="<?= $column ?>" data-dir="asc" <?= ($sort === $column && $dir === 'asc') ? 'selected' : '' ?>><?= e($label) ?> (vzestupně)</option>
                        <option value="<?= $column ?>" data-dir="desc" <?= ($sort === $column && $dir === 'desc') ? 'selected' : '' ?>><?= e($label) ?> (sestupně)</option>
                    <?php endforeach; ?>
                </select>
            </span>
        </form>

        <div class="view-switch">
            <a class="button button--secondary" href="<?= $viewHref('list') ?>" <?= $is_list ? 'aria-current="page"' : '' ?>><i class="fa-solid fa-list"></i> Seznam</a>
            <a class="button button--secondary" href="<?= $viewHref('cards') ?>" <?= !$is_list ? 'aria-current="page"' : '' ?>><i class="fa-solid fa-table-cells"></i> Dlaždice</a>
        </div>
    </div>

    <?php if ($is_list): ?>
        <div class="table-wrap">
            <table class="book-table">
                <thead>
                    <tr>
                        <?= $sortLink('title', 'Název') ?>
                        <?= $sortLink('author', 'Autor') ?>
                        <?= $sortLink('year', 'Rok vydání') ?>
                        <th scope="col"><span class="visually-hidden">Akce</span></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($books as $book): ?>
                    <tr>
                        <td><a href="/kniha/?id=<?= (int) $book['id'] ?>"><?= e($book['title']) ?></a></td>
                        <td><?= e($book['author']) ?></td>
                        <td class="num"><?= (int) $book['year'] ?></td>
                        <td class="actions">
                            <?php if (Auth::isToken()): ?>
                                <a class="link-button" href="/kniha/?id=<?= (int) $book['id'] ?>&edit=1">Upravit</a>
                                <form method="post" action="/admin/smazat/"
                                    data-confirm="Opravdu smazat knihu „<?= e($book['title']) ?>“?">
                                    <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                                    <button type="submit" class="link-button link-button--danger">Smazat</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="card-grid">
            <?php foreach ($books as $book): ?>
                <article class="book-card">
                    <h3><a href="/kniha/?id=<?= (int) $book['id'] ?>"><?= e($book['title']) ?></a></h3>
                    <p><?= e($book['author']) ?> · <?= (int) $book['year'] ?></p>
                    <div class="actions">
                        <?php if (Auth::isToken()): ?>
                            <a class="link-button" href="/kniha/?id=<?= (int) $book['id'] ?>&edit=1">Upravit</a>
                        <?php endif; ?>
                        <form method="post" action="/admin/smazat/"
                              data-confirm="Opravdu smazat knihu „<?= e($book['title']) ?>“?">
                            <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                            <button type="submit" class="link-button link-button--danger">Smazat</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($totalPages > 1): ?>
        <nav class="pagination" aria-label="Stránkování">
            <a class="button button--secondary" href="<?= $pageHref(max(1, $page - 1)) ?>"
               <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>&larr; Předchozí</a>
            <span class="pagination__status">Strana <?= $page ?> z <?= $totalPages ?></span>
            <a class="button button--secondary" href="<?= $pageHref(min($totalPages, $page + 1)) ?>"
               <?= $page >= $totalPages ? 'aria-disabled="true"' : '' ?>>Další &rarr;</a>
        </nav>
    <?php endif; ?>
</div>
<?php
layout('Seznam knih', ob_get_clean());
