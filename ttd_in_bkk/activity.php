<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
require_once __DIR__.'/middleware/signature.php';
[$user,$profile,$security]=require_signature_ready($pdo);
$st=$pdo->prepare("SELECT * FROM signature_logs WHERE id_peg=:id ORDER BY id DESC LIMIT 100");
$st->execute(['id'=>$user['employee_id']]);$rows=$st->fetchAll();
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Aktivitas</title><link rel="stylesheet" href="<?=e(base_url('assets/app.css'))?>"></head><body><div class="app"><?php require __DIR__.'/partials/header.php';?><main class="page"><section class="card"><h2>Aktivitas TTD</h2><p class="muted">Audit trail aktivitas registrasi dan signing.</p><div class="table-wrap"><table class="table"><thead><tr><th>Waktu</th><th>Aksi</th><th>Status</th><th>Dokumen</th><th>IP</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['created_at'])?></td><td><?=e($r['action'])?></td><td><?=e($r['status'])?></td><td><?=e($r['document_id']??'-')?></td><td><?=e($r['ip_address']??'-')?></td></tr><?php endforeach;?></tbody></table></div></section></main></div></body></html>
