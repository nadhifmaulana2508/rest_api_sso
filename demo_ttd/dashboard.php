<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/middleware/ttd.php';

[$user,$profile] = require_ttd($pdo);
$flash = get_flash();
$stmt=$pdo->prepare("SELECT COUNT(*) total, MAX(signed_at) last_signed FROM ttd_documents WHERE id_peg=:id");
$stmt->execute(['id'=>$user['employee_id']]); $stat=$stmt->fetch() ?: ['total'=>0,'last_signed'=>null];
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard TTD</title><link rel="stylesheet" href="<?= e(app_url('assets/app.css')) ?>"></head><body>
<div class="shell"><header class="topbar"><div class="brand"><span class="brandmark">BKK</span> SIMPEG · TTD CENTRAL</div><div class="userline"><div class="avatar"><?= e(strtoupper(substr((string)$user['full_name'],0,1))) ?></div><div class="muted" style="font-size:13px"><?= e($user['full_name']) ?></div><a class="btn btn-soft" href="<?= e(app_url('logout.php')) ?>">Keluar</a></div></header>
<main class="container">
<?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
<div class="grid">
<section class="card hero"><span class="badge">TTD ACTIVE</span><h1 style="margin-top:14px">Middleware TTD berhasil dilewati</h1><p class="muted">SSO memvalidasi identitas pegawai, kemudian SIMPEG memeriksa TTD Central. Aplikasi internal lain cukup menggunakan layanan signature saat membutuhkan tanda tangan.</p>
<div class="kpis"><div class="kpi"><span class="muted">Status</span><strong>ACTIVE</strong></div><div class="kpi"><span class="muted">Dokumen TTD</span><strong><?= (int)$stat['total'] ?></strong></div><div class="kpi"><span class="muted">Terakhir TTD</span><strong style="font-size:14px"><?= e($stat['last_signed'] ?: 'Belum ada') ?></strong></div></div>
<div class="actions"><a class="btn btn-primary" href="<?= e(app_url('sign-demo.php')) ?>">Coba Tanda Tangani Dokumen</a><a class="btn btn-soft" href="<?= e(app_url('register-ttd.php')) ?>">Ganti TTD / PIN</a></div></section>
<aside class="card side"><h3>Profile SSO</h3><p><b><?= e($user['full_name']) ?></b><br><span class="muted"><?= e($user['employee_id']) ?></span></p><p><b><?= e($user['job_position'] ?? '-') ?></b><br><span class="muted"><?= e($user['unit_kerja'] ?? '-') ?></span></p><hr style="border:0;border-top:1px solid #edf1f6"><p class="muted" style="font-size:13px">TTD disimpan terpusat. PIN wajib dimasukkan setiap proses SIGN dan tidak dikirim ke aplikasi lain.</p></aside>
<section class="card full"><h3>Gambaran Integrasi</h3><div class="steps"><span class="step active">Aplikasi Internal</span><span class="step">→ Signature API</span><span class="step">→ Minta PIN</span><span class="step">→ Validasi</span><span class="step">→ TTD + QR Verifikasi</span></div></section>
</div></main></div></body></html>
