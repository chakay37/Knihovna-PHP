<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/helpers.php';

use App\Repositories\BookRepository;

$sort = in_array($_GET['sort'] ?? '', BookRepository::SORTABLE, true) ? $_GET['sort'] : 'title';
$dir = in_array($_GET['dir'] ?? '', ['asc', 'desc', 'none'], true) ? $_GET['dir'] : 'asc';
$search = trim((string) ($_GET['q'] ?? ''));
$books = books()->getAll($sort, $dir, $search);
$is_list = ($_GET['view'] ?? 'list') !== 'cards';

$sortFieldLabels = [
    'title' => 'Název',
    'author' => 'Autor',
    'year' => 'Rok vydání',
];

$buildQuery = static fn (array $params): string => '?' . http_build_query(
    array_filter($params, static fn ($value) => $value !== null)
);

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
        'nextSort' => $column,
        'nextDir' => $nextDir,
    ];
};

$sortLink = static function (string $column, string $label) use ($sortState, $is_list, $search, $buildQuery): string {
    $state = $sortState($column);
    $href = $buildQuery([
        'sort' => $state['nextSort'],
        'dir' => $state['nextDir'],
        'view' => $is_list ? null : 'cards',
        'q' => $search !== '' ? $search : null,
    ]);

    return sprintf(
        '<th scope="col" aria-sort="%s"><a href="%s">%s <span aria-hidden="true">%s</span></a></th>',
        $state['ariaSort'], $href, e($label), $state['arrow']
    );
};

$dirState = $sortState($sort);
$dirHref = $buildQuery([
    'sort' => $dirState['nextSort'],
    'dir' => $dirState['nextDir'],
    'view' => $is_list ? null : 'cards',
    'q' => $search !== '' ? $search : null,
]);

$viewHref = static fn (string $view): string => $buildQuery([
    'sort' => $sort,
    'dir' => $dir,
    'view' => $view === 'list' ? null : $view,
    'q' => $search !== '' ? $search : null,
]);

ob_start();
?>
<div class="title-container">
    <div class="page-head">
        <h1 class="title">Seznam knih</h1>
        <p class="page-head__meta"><?= count($books) ?> knih v evidenci</p>
    </div>

    <div class="toolbar">
        <button type="button" class="button" data-print>Tisk</button>
        <a class="button" href="/admin/vytvorit/">Přidat knihu</a>
        <a class="button button--secondary" href="/admin/import/">Importovat z JSON</a>

        <form method="get" class="search-form">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <input type="hidden" name="view" value="<?= $is_list ? 'list' : 'cards' ?>">
            <label class="visually-hidden" for="search-field">Hledat</label>
            <span class="search-field-wrap">
                <input type="search" id="search-field" name="q" value="<?= e($search) ?>"
                       placeholder="Hledat podle názvu, autora nebo roku…" class="search-field">
                <button type="submit" name="q" value="" class="search-field__clear" aria-label="Vymazat hledání">&times;</button>
            </span>
            <button type="submit" class="button button--secondary">Hledat</button>
        </form>

        <form method="get" class="sort-form">
            <input type="hidden" name="view" value="<?= $is_list ? 'list' : 'cards' ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <input type="hidden" name="q" value="<?= e($search) ?>">
            Řadit podle:
            <span class="select-wrap button button--secondary">
                <select id="sort-field" name="sort">
                    <?php foreach ($sortFieldLabels as $column => $label): ?>
                        <option value="<?= $column ?>" <?= $sort === $column ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </span>
        </form>

        <a class="button button--secondary" href="<?= $dirHref ?>" aria-label="Směr řazení" aria-sort="<?= $dirState['ariaSort'] ?>">
            <span aria-hidden="true"><?= $dirState['arrow'] ?></span>
        </a>

        <div class="view-switch">
            <a class="button button--secondary" href="<?= $viewHref('list') ?>" <?= $is_list ? 'aria-current="page"' : '' ?>>Seznam</a>
            <a class="button button--secondary" href="<?= $viewHref('cards') ?>" <?= !$is_list ? 'aria-current="page"' : '' ?>>Dlaždice</a>
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
                            <form method="post" action="/admin/smazat/"
                                  data-confirm="Opravdu smazat knihu „<?= e($book['title']) ?>“?">
                                <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                                <button type="submit" class="link-button link-button--danger">Smazat</button>
                            </form>
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
                    <form method="post" action="/admin/smazat/"
                          data-confirm="Opravdu smazat knihu „<?= e($book['title']) ?>“?">
                        <input type="hidden" name="id" value="<?= (int) $book['id'] ?>">
                        <button type="submit" class="link-button link-button--danger">Smazat</button>
                    </form>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
layout('Seznam knih', ob_get_clean());
