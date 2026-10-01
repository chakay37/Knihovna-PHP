<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/helpers.php';

use App\Auth;

if (Auth::isToken()) {
    redirect('/');
}

$error = null;
$username = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';

if (is_post()) {
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    if (Auth::attemptLogin($username, $password)) {
        redirect('/');
    }

    $error = 'Nesprávné uživatelské jméno nebo heslo.';
    http_response_code(401);
}

ob_start();
?>
<div class="narrow">
    <h1>Přihlášení do administrace</h1>
    <?php if ($error): ?>
        <p class="flash flash--error" role="alert"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" action="/admin/prihlaseni/" class="form">
        <div class="field">
            <label for="username">Uživatelské jméno</label>
            <input id="username" name="username" value="<?= e($username) ?>" required autocomplete="username" autofocus>
        </div>
        <div class="field">
            <label for="password">Heslo</label>
            <input id="password" name="password" type="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="button">Přihlásit se</button>
    </form>
</div>
<?php
layout('Přihlášení', ob_get_clean());
