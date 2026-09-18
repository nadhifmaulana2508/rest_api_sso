<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function get_ttd_profile(PDO $pdo, string $idPeg): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM ttd_profiles WHERE id_peg = :id_peg LIMIT 1');
    $stmt->execute(['id_peg' => $idPeg]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function require_ttd(PDO $pdo): array
{
    $user = require_login();
    $profile = get_ttd_profile($pdo, (string)$user['employee_id']);

    if (!$profile || ($profile['status'] ?? '') !== 'ACTIVE') {
        set_flash('warning', 'TTD belum terdaftar. Registrasi TTD dan buat PIN terlebih dahulu.');
        redirect(app_url('register-ttd.php'));
    }

    return [$user, $profile];
}
