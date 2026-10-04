<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/helpers.php';
require_admin();

use App\BookValidator;

$errors = [];
// Slouží ke znovu vyplnění formůláře při chybě ve validaci.
$old = [];

// Při potvrzení tvorby knihy.
if (is_post()) {
    $old = $_POST;
    // Validace formátu knihy.
    [$data, $errors] = BookValidator::validate($_POST);

    if ($errors === [] && books()->isDuplicate($data)) {
        $errors['title'] = 'Tato kniha už v evidenci je.';
    }

    if ($errors === []) {
        // Post/Redirect/Get: 
        // Vytvoření knížky. 
        // Redirect na stránku knížky, aby refresh neposílal form znovu.
        redirect('/kniha/?id=' . books()->create($data));
    }

    http_response_code(422);
}

// Převede hodnoty na string aby mohli být znovu bezpečně vloženy.
$old = array_map(static fn ($v) => is_string($v) ? $v : '', $old);

ob_start();
?>
<div class="narrow">
    <h1>Přidat knihu</h1>
    <?= book_form('/admin/vytvorit/', $old, $errors, 'Uložit knihu', '/') ?>
</div>
<?php
layout('Přidat knihu', ob_get_clean());
