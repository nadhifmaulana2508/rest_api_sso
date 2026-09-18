<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../lib/helpers.php';

function require_login(): array
{
    $user = current_user();
    if (!$user || empty($_SESSION['sso_token'])) {
        set_flash('error', 'Silakan login melalui SIMPEG.');
        redirect(app_url('index.php'));
    }
    return $user;
}
