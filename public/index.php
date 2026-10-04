<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

use App\Auth; // Kontrola přihlášení admina.
use App\Repositories\BookRepository;

// Sloupec řazení z URL (?sort=). Pokud chybí nebo je mimo SORTABLE, použije se 'title'.
$sort = in_array($_GET['sort'] ?? '', BookRepository::SORTABLE, true) ? $_GET['sort'] : 'title';
// Směr řazení z URL (?dir=). Povolené jsou jen asc/desc/none, jinak výchozí 'asc'.
$dir = in_array($_GET['dir'] ?? '', ['asc', 'desc', 'none'], true) ? $_GET['dir'] : 'asc';
// Vyhledávací dotaz z URL (?q=).
$search = trim((string) ($_GET['q'] ?? ''));
// Layout: true = tabulka (výchozí), false = dlaždice (?view=cards).
$is_list = ($_GET['view'] ?? 'list') !== 'cards';

// Kolik knih se zobrazí na jedné stránce (?perPage=), v rozsahu 1..100, jinak výchozích 9.
$perPageParam = filter_var($_GET['perPage'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100]]);
$perPage = $perPageParam !== false ? $perPageParam : 9;
$total = books()->countAll($search); // Celkový počet knih odpovídajících hledání (bez stránkování).
$totalPages = max(1, (int) ceil($total / $perPage)); // Počet stránek; minimálně 1 i při nulovém výsledku.
// Stránka z URL (?page=), ohraničená na rozsah 1..$totalPages, aby nešlo zadat neplatnou stránku.
$page = min(max(1, (int) ($_GET['page'] ?? 1)), $totalPages);
// Z databáze se stáhne aktuální stránka knih (LIMIT/OFFSET uvnitř repository).
$books = books()->getAll($sort, $dir, $search, $page, $perPage);

$sortFieldLabels = [
    'title' => 'Název',
    'author' => 'Autor',
    'year' => 'Rok vydání',
];

// Sestaví query string z pole. Klíče s hodnotou null se do URL nezapíší vůbec
// (slouží k "vynechání" parametru, např. view, pokud je to výchozí seznamový pohled).
$buildQuery = static fn (array $params): string => '?' . http_build_query(
    array_filter($params, static fn ($value) => $value !== null)
);

// Parametry, které musí nést úplně každý odkaz na této stránce (řazení, pohled, hledání),
// aby kliknutí na cokoliv jiného (např. přepnutí stránky) nezahodilo stav, ve kterém uživatel je.
$baseQuery = [
    'sort' => $sort,
    'dir' => $dir,
    'view' => $is_list ? null : 'cards',
    'q' => $search !== '' ? $search : null,
    'perPage' => $perPage !== 9 ? $perPage : null,
];

// Rotuje cyklus inputů určující směr (cyklus asc -> desc -> none -> asc)? a jakou ikonu zobrazit.
$sortState = static function (string $column) use ($sort, $dir): array {
    $isActive = $sort === $column && $dir !== 'none';

    if (!$isActive) {
        $nextDir = 'asc'; // Neaktivní sloupec vždy začíná vzestupně.
    } elseif ($dir === 'asc') {
        $nextDir = 'desc'; // Druhé kliknutí: sestupně.
    } else {
        $nextDir = 'none'; // Třetí kliknutí: zrušit řazení podle tohoto sloupce.
    }

    return [
        'ariaSort' => $isActive ? ($dir === 'asc' ? 'ascending' : 'descending') : 'none',
        'arrow' => $isActive ? ($dir === 'asc' ? '▲' : '▼') : '<i class="fa-solid fa-sort"></i>',
        'nextDir' => $nextDir,
    ];
};

// Vykreslí jedno záhlaví tabulky <th> jako odkaz, který při kliknutí přepne řazení, podle cyklu.
$sortLink = static function (string $column, string $label) use ($sortState, $baseQuery, $buildQuery): string {
    $state = $sortState($column);
    $href = $buildQuery([...$baseQuery, 'sort' => $column, 'dir' => $state['nextDir']]);

    return sprintf(
        '<th scope="col" aria-sort="%s"><a href="%s">%s <span aria-hidden="true">%s</span></a></th>',
        $state['ariaSort'], $href, e($label), $state['arrow']
    );
};

// Odkaz pro přepnutí pohledu (seznam/dlaždice).
$viewHref = static fn (string $view): string => $buildQuery([...$baseQuery, 'view' => $view === 'list' ? null : $view]);

// Odkaz na konkrétní stránku (pagination).
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
                                <div class="actions-group">
                                    <a class="link-button" href="/kniha/?id=<?= (int) $book['id'] ?>&edit=1">Upravit</a>
                                    <form method="post" action="/admin/smazat/"
                                        data-confirm="Opravdu smazat knihu „<?= e($book['title']) ?>“?">
                                        <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                                        <button type="submit" class="link-button link-button--danger">Smazat</button>
                                    </form>
                                </div>
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
                        
                            <form method="post" action="/admin/smazat/"
                                data-confirm="Opravdu smazat knihu „<?= e($book['title']) ?>“?">
                                <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                                <button type="submit" class="link-button link-button--danger">Smazat</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <div class="pagination-bar">
        <?php if ($totalPages > 1): ?>
            <nav class="pagination" aria-label="Stránkování">
                <a class="button button--secondary" href="<?= $pageHref(max(1, $page - 1)) ?>"
                   <?= $page <= 1 ? 'aria-disabled="true"' : '' ?>>&larr; Předchozí</a>
                <span class="pagination__status">Strana <?= $page ?> z <?= $totalPages ?></span>
                <a class="button button--secondary" href="<?= $pageHref(min($totalPages, $page + 1)) ?>"
                   <?= $page >= $totalPages ? 'aria-disabled="true"' : '' ?>>Další &rarr;</a>
            </nav>
        <?php endif; ?>

        <form method="get" class="per-page-form">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <input type="hidden" name="view" value="<?= $is_list ? 'list' : 'cards' ?>">
            <input type="hidden" name="q" value="<?= e($search) ?>">
            <label for="per-page-field">Počet záznamů:</label>
            <input type="number" id="per-page-field" name="perPage" min="1" max="100" step="1"
                   value="<?= $perPage ?>" class="per-page-field">
        </form>
    </div>
</div>
<?php
layout('Seznam knih', ob_get_clean());
