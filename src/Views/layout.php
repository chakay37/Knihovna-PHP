<?php
use App\Auth;
/** @var string $title */
/** @var string $content */
?>
<!DOCTYPE html>
<html lang="cs">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - Knihovna</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,400;7..72,600;7..72,700&family=Courier+Prime&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Literata:opsz,wght@7..72,400;7..72,600;7..72,700&display=swap">
    <link rel="stylesheet" href="/assets/css/app.css">
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <script src="/assets/js/app.js" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="site-header-content">
            <a class="brand" href="/">Knihovna</a>
            <nav class="site-nav" aria-label="Navigace">
                <a href="/">Seznam knih</a>
                <?php if (Auth::isToken()): ?>
                    <a href="/admin/vytvorit/">Přidat knihu</a>
                    <a href="/admin/import/">Import</a>
                    <form method="post" action="/admin/odhlaseni/">
                        <button type="submit" class="link-button">Odhlásit <?= e(Auth::getToken()['username'] ?? '') ?></button>
                    </form>
                <?php else: ?>
                    <a href="/admin/prihlaseni/">Administrace</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main>
        <?= $content ?>
    </main>
</body>
</html>
