<?php
use App\BookValidator;

/** @var string $action */
/** @var array<string, string> $old */
/** @var array<string, string> $errors */
/** @var string $submitLabel */
/** @var string $cancelHref */

$field = static fn (string $name): string => isset($errors[$name])
    ? sprintf(' aria-invalid="true" aria-describedby="%s-error"', $name)
    : '';
$error = static fn (string $name): string => isset($errors[$name])
    ? sprintf('<p class="field__error" id="%s-error">%s</p>', $name, e($errors[$name]))
    : '';
?>
<?php if ($errors): ?>
    <p class="flash flash--error" role="alert">Formulář obsahuje chyby. Opravte označená pole.</p>
<?php endif; ?>

<form method="post" action="<?= e($action) ?>" class="form">
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
        <textarea id="annotation" name="annotation" rows="10" maxlength="<?= BookValidator::MAX_ANNOTATION ?>"
                  data-char-counter="annotation-counter"
                  <?= $field('annotation') ?>><?= e($old['annotation'] ?? '') ?></textarea>
        <p class="field__hint" id="annotation-counter" aria-live="polite"></p>
        <?= $error('annotation') ?>
    </div>

    <div class="form__actions">
        <button type="submit" class="button"><?= e($submitLabel) ?></button>
        <a href="<?= e($cancelHref) ?>" class="button button--secondary">Zrušit</a>
    </div>
</form>
