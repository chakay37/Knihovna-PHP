<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/helpers.php';
require_admin();

use App\BookValidator;

const MAX_UPLOAD_MB = 2;

$preparedFile = __DIR__ . '/../../../data/books.json';
$preparedExists = is_file($preparedFile);

/** @var array{imported: int, skipped: int, errors: list<string>}|null $result */
$result = null;

if (is_post()) {
    $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];
    $json = null;

    if (($_POST['source'] ?? '') === 'prepared') {
        $json = $preparedExists ? file_get_contents($preparedFile) : null;
        $problem = $preparedExists ? null : 'Připravený soubor data/books.json neexistuje.';
    } else {
        $file = $_FILES['file'] ?? [];
        $uploadError = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $problem = match (true) {
            $uploadError === UPLOAD_ERR_NO_FILE => 'Vyberte soubor k nahrání.',
            $uploadError === UPLOAD_ERR_INI_SIZE,
            $uploadError === UPLOAD_ERR_FORM_SIZE,
            ($file['size'] ?? 0) > MAX_UPLOAD_MB * 1024 * 1024 => 'Soubor je větší než ' . MAX_UPLOAD_MB . ' MB.',
            $uploadError !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name']) => 'Soubor se nepodařilo nahrát.',
            default => null,
        };
        $json = $problem === null ? file_get_contents($file['tmp_name']) : null;
    }

    $items = is_string($json) ? json_decode($json, true) : null;
    if ($problem === null && (!is_array($items) || !array_is_list($items))) {
        $problem = 'Soubor musí obsahovat platný JSON se seznamem knih ([ {...}, {...} ]).';
    }

    if ($problem !== null) {
        $result['errors'][] = $problem;
    } else {
        foreach ($items as $i => $item) {
            [$data, $errors] = is_array($item) ? BookValidator::validate($item) : [null, []];

            $label = sprintf('Záznam č. %d', $i + 1);
            if (($data['title'] ?? '') !== '') {
                $label .= ' („' . $data['title'] . '“)';
            }

            $skip = match (true) {
                $data === null => 'není to objekt s údaji o knize.',
                $errors !== [] => implode(' ', $errors),
                books()->isDuplicate($data) => 'už je v evidenci.',
                default => null,
            };

            if ($skip === null) {
                books()->create($data);
                $result['imported']++;
            } else {
                $result['skipped']++;
                $result['errors'][] = "$label: $skip";
            }
        }
    }
}

ob_start();
?>
<div class="narrow">
    <h1>Import knih</h1>

    <?php if ($result !== null): ?>
        <section class="import-result <?= $result['imported'] > 0 ? 'import-result--ok' : 'import-result--warn' ?>" aria-live="polite">
            <h2>Výsledek importu</h2>
            <p>Importováno <strong><?= $result['imported'] ?></strong>, přeskočeno <strong><?= $result['skipped'] ?></strong>.</p>
            <?php if ($result['errors']): ?>
                <ul class="import-result__errors">
                    <?php foreach ($result['errors'] as $message): ?>
                        <li><?= e($message) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($result['imported'] > 0): ?>
                <p><a href="/">Zobrazit seznam knih</a></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>

    <?php if ($preparedExists): ?>
        <form method="post" action="/admin/import/" class="form form--boxed">
            <input type="hidden" name="source" value="prepared">
            <h2>Připravený soubor</h2>
            <p>Naimportuje knihy ze souboru <code>data/books.json</code> na serveru.</p>
            <button type="submit" class="button">Importovat books.json</button>
        </form>
    <?php endif; ?>

    <form method="post" action="/admin/import/" enctype="multipart/form-data" class="form form--boxed">
        <input type="hidden" name="source" value="upload">
        <h2>Vlastní soubor</h2>
        <div class="field">
            <label for="file">Soubor JSON (nejvýše <?= MAX_UPLOAD_MB ?> MB)</label>
            <input id="file" name="file" type="file" accept=".json,application/json" required>
        </div>
        <button type="submit" class="button">Nahrát a importovat</button>
    </form>
</div>
<?php
layout('Import knih', ob_get_clean());
