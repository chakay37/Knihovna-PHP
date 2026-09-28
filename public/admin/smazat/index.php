<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/helpers.php';
require_admin();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (is_post() && $id) {
    books()->delete($id);
}

redirect('/admin/');
