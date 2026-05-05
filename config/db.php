<?php
declare(strict_types=1);

// response helper (tetap relatif dari api/config/)
require_once __DIR__ . '/../helpers/response.php';

/**
 * Cari project root dengan mendeteksi file .env ke atas max 6 level.
 * Default fallback: 2 level di atas (api/config -> project root).
 */
function findProjectRoot(string $startDir): string {
  $dir = $startDir;
  for ($i = 0; $i < 6; $i++) {
    if (is_file($dir . '/.env')) return $dir;
    $parent = dirname($dir);
    if ($parent === $dir) break;
    $dir = $parent;
  }
  return dirname($startDir, 2);
}

$PROJECT_ROOT = findProjectRoot(__DIR__);
$envFile      = $PROJECT_ROOT . '/.env';

/** Load .env sebagai array; error → balas JSON dan exit */
function loadEnv(string $file): array {
  if (!is_file($file)) {
    sendResponse(500, "File .env tidak ditemukan di: {$file}");
    exit;
  }
  $data = parse_ini_file($file, false, INI_SCANNER_RAW);
  if ($data === false) {
    sendResponse(500, "Gagal membaca .env di: {$file}");
    exit;
  }
  return $data;
}

$env = loadEnv($envFile);

/** Ambil env: prioritaskan environment variable sistem, lalu isi .env, lalu default */
$ENV = function (string $key, $default = null) use ($env) {
  $v = getenv($key);
  if ($v !== false) return $v;
  return $env[$key] ?? $default;
};

// ----- Konfigurasi DB (PORT kompatibel dengan DB_ROOT lama) -----
$DB_HOST = (string)$ENV('DB_HOST', 'localhost');
$DB_USER = (string)$ENV('DB_USER', 'root');
$DB_PASS = (string)$ENV('DB_PASS', '');
$DB_NAME = (string)$ENV('DB_NAME', '');
$DB_PORT = (int)$ENV('DB_PORT', $ENV('DB_ROOT', 3306)); // fallback DB_ROOT

try {
  $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset=utf8mb4";
  $options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];
  $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
  // sukses: $pdo siap dipakai
} catch (PDOException $e) {
  sendResponse(500, "Koneksi database gagal: " . $e->getMessage());
  exit;
}
