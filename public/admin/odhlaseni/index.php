<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/helpers.php';

use App\Auth;

if (is_post()) {
    Auth::logout();
}

redirect('/');
