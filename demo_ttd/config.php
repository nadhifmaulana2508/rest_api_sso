<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../config/db.php';

const TTD_APP_NAME = 'SIMPEG - TTD Central Demo';
const TTD_SSO_APP = 'simpeg';
const TTD_SSO_BASE_URL = 'https://apisso.bkkjateng.co.id';

function app_url(string $path = ''): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/demo_ttd/index.php');
    $marker = '/demo_ttd/';
    $pos = strpos($script, $marker);
    $base = $pos !== false ? substr($script, 0, $pos + strlen('/demo_ttd')) : '/demo_ttd';
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function storage_path(string $path = ''): string
{
    return __DIR__ . '/storage/' . ltrim($path, '/');
}
