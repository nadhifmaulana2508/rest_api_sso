<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
require_once __DIR__.'/middleware/signature.php';
[$user,$profile,$security]=require_signature_ready($pdo);
$flash=pull_flash();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TTD Saya</title><link rel="stylesheet" href="<?=e(base_url('assets/app.css'))?>"></head><body><div class="app"><?php require __DIR__.'/partials/header.php';?><main class="page">
<?php if($flash):?><div class="alert alert-<?=e($flash['type'])?>"><?=e($flash['message'])?></div><?php endif;?>
<div class="grid"><section class="card hero"><span class="badge">TTD SAYA</span><h2 style="margin-top:14px">Preview Tanda Tangan</h2><div class="result-box"><img class="signature-img" src="<?=e(base_url($profile['signature_path']))?>" alt="TTD"></div><div class="actions" style="margin-top:16px"><a class="btn btn-soft" href="<?=e(base_url('register-signature.php'))?>">Ganti TTD / PIN</a></div></section>
<aside class="card side"><h3>Status</h3><p><b>ACTIVE</b></p><p class="muted">Terdaftar: <?=e($profile['registered_at'])?></p><p class="muted">Update: <?=e($profile['updated_at'])?></p><div class="alert alert-success">TTD siap digunakan untuk proses signing melalui PIN.</div></aside></div></main></div></body></html>
