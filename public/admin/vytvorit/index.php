<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/helpers.php';
require_admin();

use App\BookValidator;

$errors = [];
$old = [];

if (is_post()) {
    $old = $_POST;
    [$data, $errors] = BookValidator::validate($_POST);

    if ($errors === [] && books()->isDuplicate($data)) {
        $errors['title'] = 'Tato kniha už v evidenci je.';
    }

    if ($errors === []) {
        // Post/Redirect/Get: obnovení stránky pak neodešle formulář znovu
        redirect('/kniha/?id=' . books()->create($data));
    }

    http_response_code(422);
}

$old = array_map(static fn ($v) => is_string($v) ? $v : '', $old);
$field = static fn (string $name): string => isset($errors[$name])
    ? sprintf(' aria-invalid="true" aria-describedby="%s-error"', $name)
    : '';
$error = static fn (string $name): string => isset($errors[$name])
    ? sprintf('<p class="field__error" id="%s-error">%s</p>', $name, e($errors[$name]))
    : '';

ob_start();
?>
<div class="narrow">
    <h1>Přidat knihu</h1>

    <?php if ($errors): ?>
        <p class="flash flash--error" role="alert">Formulář obsahuje chyby. Opravte označená pole.</p>
    <?php endif; ?>

    <form method="post" action="/admin/vytvorit/" class="form">
        <div class="field">
            <label for="title">Název <span class="required" aria-hidden="true">*</span></label>
            <input id="title" name="title" value="<?= e($old['title'] ?? '') ?>"
                   required maxlength="<?= BookValidator::MAX_TEXT ?>"<?= $field('title') ?>>
            <?= $error('title') ?>
        </div>

        <div class="field">
            <label for="author">Autor <span class="required" aria-hidden="true">*</span></label>
            <input id="author" name="author" value="<?= e($old['author'] ?? '') ?>"
                   required maxlength="<?= BookValidator::MAX_TEXT ?>"<?= $field('author') ?>>
            <?= $error('author') ?>
        </div>

        <div class="field-row">
            <div class="field">
                <label for="year">Rok vydání <span class="required" aria-hidden="true">*</span></label>
                <input id="year" name="year" type="number" inputmode="numeric" value="<?= e($old['year'] ?? '') ?>"
                       required min="<?= BookValidator::MIN_YEAR ?>" max="<?= BookValidator::maxYear() ?>" step="1"<?= $field('year') ?>>
                <?= $error('year') ?>
            </div>

            <div class="field">
                <label for="rating">Hodnocení</label>
                <select id="rating" name="rating"<?= $field('rating') ?>>
                    <option value="">Bez hodnocení</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= $i ?>" <?= ($old['rating'] ?? '') === (string) $i ? 'selected' : '' ?>>
                            <?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?> (<?= $i ?>)
                        </option>
                    <?php endfor; ?>
                </select>
                <?= $error('rating') ?>
            </div>
        </div>

        <div class="field">
            <label for="annotation">Anotace</label>
            <textarea id="annotation" name="annotation" rows="6" maxlength="<?= BookValidator::MAX_ANNOTATION ?>"
                      <?= $field('annotation') ?>><?= e($old['annotation'] ?? '') ?></textarea>
            <?= $error('annotation') ?>
        </div>

        <div class="form__actions">
            <button type="submit" class="button">Uložit knihu</button>
            <a href="/" class="button button--secondary">Zrušit</a>
        </div>
    </form>
</div>
<?php
layout('Přidat knihu', ob_get_clean());
