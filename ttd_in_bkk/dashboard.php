<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
require_once __DIR__.'/middleware/signature.php';

[$user,$profile,$security]=require_signature_ready($pdo);
$flash=pull_flash();
$st=$pdo->prepare("SELECT COUNT(*) total,MAX(signed_at) last_signed FROM signature_documents WHERE id_peg=:id");
$st->execute(['id'=>$user['employee_id']]);$stat=$st->fetch()?:['total'=>0,'last_signed'=>null];
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard - TTD IN BKK</title><link rel="stylesheet" href="<?=e(base_url('assets/app.css'))?>"></head><body><div class="app"><?php require __DIR__.'/partials/header.php';?>
<main class="page"><?php if($flash):?><div class="alert alert-<?=e($flash['type'])?>"><?=e($flash['message'])?></div><?php endif;?>
<div class="grid"><section class="card hero"><span class="badge">TTD ACTIVE</span><h1 style="margin-top:14px">Halo, <?=e($user['full_name']??'Pegawai')?> 👋</h1><p class="muted">Akun Anda telah terhubung ke BKK SSO dan profil TTD sudah aktif.</p>
<div class="kpis"><div class="kpi"><span class="muted">Status TTD</span><strong>ACTIVE</strong></div><div class="kpi"><span class="muted">Dokumen Ditandatangani</span><strong><?= (int)$stat['total'] ?></strong></div><div class="kpi"><span class="muted">Terakhir TTD</span><strong style="font-size:14px"><?=e($stat['last_signed']?:'Belum ada')?></strong></div></div>
<div class="actions" style="margin-top:18px"><a class="btn btn-primary" href="<?=e(base_url('documents.php'))?>">Coba Tanda Tangani</a><a class="btn btn-soft" href="<?=e(base_url('signature.php'))?>">Lihat TTD Saya</a></div></section>
<aside class="card side"><h3>Profil Pegawai</h3><p><b><?=e($user['full_name']??'-')?></b><br><span class="muted"><?=e($user['employee_id']??'-')?></span></p><p><b><?=e($user['job_position']??'-')?></b><br><span class="muted"><?=e($user['unit_kerja']??'-')?></span></p><p><b>Cabang</b><br><span class="muted"><?=e($user['branch_name']??'-')?></span></p></aside>
<section class="card full"><h3>Alur Penggunaan</h3><div class="actions"><span class="badge">Aplikasi</span><span>→</span><span class="badge">Request TTD</span><span>→</span><span class="badge">Masukkan PIN</span><span>→</span><span class="badge">Validasi</span><span>→</span><span class="badge">TTD + QR</span></div></section>
</div></main></div></body></html>
