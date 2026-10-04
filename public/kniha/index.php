<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/helpers.php';

use App\Auth;
use App\BookValidator;
// ID může být pouze kladné číslo.
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
// Získá book pomocí ID
$book = $id ? books()->get($id) : null;

if ($book === null) {
    http_response_code(404);
}

// Autorizace editace
$canEdit = $book !== null && Auth::isToken();
// Bool jestli je zaplý mód editace.
$editing = $canEdit && (($_GET['edit'] ?? '') === '1' || is_post());

$errors = [];

// Při potvrzení editace.
if ($editing && is_post()) {
    // Validace formuláře knihy.
    [$data, $errors] = BookValidator::validate($_POST);

    if ($errors === [] && books()->isDuplicate($data, $id)) {
        $errors['title'] = 'Tato kniha už v evidenci je.';
    }

    if ($errors === []) {
        // Post/Redirect/Get: 
        // Aktualizace knížky. 
        // Redirect na stránku knížky bez edit módu, aby refresh neposílal form znovu.
        books()->update($id, $data);
        redirect('/kniha/?id=' . $id);
    }
    // Chyba ve validaci. Slouží ke znovu vyplnění formůláře.
    $old = $_POST;
    http_response_code(422);
} elseif ($editing) {
    $old = [
        'title' => $book['title'],
        'author' => $book['author'],
        'year' => (string) $book['year'],
        'rating' => $book['rating'] !== null ? (string) $book['rating'] : '',
        'annotation' => $book['annotation'] ?? '',
    ];
} else {
    $old = [];
}

$old = array_map(static fn ($v) => is_string($v) ? $v : '', $old);

$sameAuthor = $book !== null && !$editing ? books()->getByAuthor($book['author'], (int) $book['id']) : [];

ob_start();
?>
<?php if ($book === null): ?>
    <div class="title-container">
        <h1 class="title">Kniha nenalezena</h1>
        <p>Požadovaná kniha neexistuje.</p>
    </div>
<?php elseif ($editing): ?>
    <div class="narrow">
        <h1>Upravit knihu</h1>
        <?= book_form('/kniha/?id=' . $id, $old, $errors, 'Uložit změny', '/kniha/?id=' . $id) ?>
    </div>
<?php else: ?>
    <article class="book-detail">
        <div class="toolbar">
            <a class="button button--secondary" href="/">&larr; Zpět na seznam knih</a>
            <?php if ($canEdit): ?>
                <a class="button button--secondary" href="/kniha/?id=<?= $id ?>&edit=1">Upravit</a>
            <?php endif; ?>
        </div>

        <div class="book-detail-card">
            <div class="title-container">
                <h1 class="title"><?= e($book['title']) ?></h1>
            </div>

            <div class="book-detail-info">
                <h2>Autor</h2>
                <p><?= e($book['author']) ?></p>

                <h2>Rok vydání</h2>
                <p><?= (int) $book['year'] ?></p>

                <?php if ($book['rating'] !== null): ?>
                    <h2>Hodnocení</h2>
                    <p><?= str_repeat('★', (int) $book['rating']) . str_repeat('☆', 5 - (int) $book['rating']) ?> (<?= (int) $book['rating'] ?>)</p>
                <?php endif; ?>
            </div>

            <?php if ($book['annotation'] !== null): ?>
                <section class="book-detail-annotation">
                    <h2>Anotace</h2>
                    <p><?= nl2br(e($book['annotation'])) ?></p>
                </section>
            <?php endif; ?>
        </div>

        <?php if ($sameAuthor !== []): ?>
            <section class="book-detail-same-author">
                <h2>Další knihy autora</h2>
                <div class="table-wrap">
                    <table class="book-table">
                        <thead>
                            <tr>
                                <th scope="col">Název</th>
                                <th scope="col">Rok vydání</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sameAuthor as $other): ?>
                            <tr>
                                <td><a href="/kniha/?id=<?= (int) $other['id'] ?>"><?= e($other['title']) ?></a></td>
                                <td class="num"><?= (int) $other['year'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>
    </article>
<?php endif; ?>
<?php
layout($book['title'] ?? 'Kniha nenalezena', ob_get_clean());
