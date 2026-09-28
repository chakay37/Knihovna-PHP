<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../src/helpers.php';
require_once __DIR__ . '/../../src/Auth.php';
require_once __DIR__ . '/../../src/Database.php';
require_once __DIR__ . '/../../src/Repositories/BookRepository.php';

use App\Database;
use App\Repositories\BookRepository;

// /kniha/?id=5
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$book = $id !== false
    ? (new BookRepository(Database::connection()))->get($id)
    : null;

if ($book === null) {
    http_response_code(404);
}

ob_start();
?>
<?php if ($book === null): ?>
    <div class="title-container">
        <h1 class="title">Kniha nenalezena</h1>
        <p>Požadovaná kniha neexistuje.</p>
        <p><a href="/">&larr; Zpět na seznam knih</a></p>
    </div>
<?php else: ?>
    <article class="book-detail">
        <div class="title-container">
            <h1 class="title"><?= e($book['title']) ?></h1>
        </div>

        <dl class="book-detail-info">
            <dt>Autor</dt>
            <dd><?= e($book['author']) ?></dd>

            <dt>Rok vydání</dt>
            <dd><?= (int) $book['year'] ?></dd>

            <?php if ($book['rating'] !== null): ?>
                <dt>Hodnocení</dt>
                <dd><?= (int) $book['rating'] ?></dd>
            <?php endif; ?>
        </dl>

        <?php if ($book['annotation'] !== null && $book['annotation'] !== ''): ?>
            <section class="book-detail-annotation">
                <h2>Anotace</h2>
                <p><?= nl2br(e($book['annotation'])) ?></p>
            </section>
        <?php endif; ?>

        <p><a href="/">&larr; Zpět na seznam knih</a></p>
    </article>
<?php endif; ?>
<?php
layout($book['title'] ?? 'Kniha nenalezena', ob_get_clean());
