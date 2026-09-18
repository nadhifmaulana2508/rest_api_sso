<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';

$token=(string)($_GET['token']??'');
$row=null;
if(preg_match('/^[a-f0-9]{64}$/',$token)) {
    $st=$pdo->prepare("SELECT * FROM signature_documents WHERE verification_token=:t LIMIT 1");
    $st->execute(['t'=>$token]);$row=$st->fetch();
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verifikasi TTD</title><link rel="stylesheet" href="<?=e(base_url('assets/app.css'))?>"></head><body><div class="login-right" style="min-height:100vh"><div class="card" style="width:min(650px,100%)"><div class="brand"><span class="brandmark">BKK</span>VERIFIKASI TTD IN BKK</div>
<?php if(!$row):?><div class="alert alert-error" style="margin-top:22px"><b>Token tidak valid / dokumen tidak ditemukan.</b></div><?php else:?><div class="alert alert-success" style="margin-top:22px"><b>✓ Dokumen tercatat pada TTD IN BKK</b></div><h2><?=e($row['document_name'])?></h2><p><b>ID Dokumen</b><br><?=e($row['document_id'])?></p><p><b>ID Pegawai</b><br><?=e($row['id_peg'])?></p><p><b>Aplikasi</b><br><?=e($row['application'])?></p><p><b>Waktu TTD</b><br><?=e($row['signed_at'])?></p><p><b>SHA-256</b></p><div class="code"><?=e($row['document_hash'])?></div><p class="muted" style="font-size:12px">Prototype ini merupakan verifikasi audit internal, bukan klaim TTE tersertifikasi PSrE.</p><?php endif;?></div></div></body></html>
