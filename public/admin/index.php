<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/helpers.php';
require_admin();

$books = books()->getAll();

ob_start();
?>
<div class="page-head">
    <h1>Správa knih</h1>
    <p class="page-head__meta"><?= count($books) ?> knih v evidenci</p>
</div>

<div class="toolbar">
    <a class="button" href="/admin/vytvorit/">Přidat knihu</a>
    <a class="button button--secondary" href="/admin/import/">Importovat z JSON</a>
</div>

<?php if ($books === []): ?>
    <div class="empty"><p>Evidence je prázdná. Začněte importem připraveného souboru books.json.</p></div>
<?php else: ?>
    <div class="table-wrap">
        <table class="book-table">
            <thead>
            <tr><th scope="col">Název</th><th scope="col">Autor</th><th scope="col">Rok</th><th scope="col"><span class="visually-hidden">Akce</span></th></tr>
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
<?php endif; ?>
<?php
layout('Správa knih', ob_get_clean());
