<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';

$token=(string)($_GET['token'] ?? '');
$row=null;
if(preg_match('/^[a-f0-9]{64}$/',$token)){
    $stmt=$pdo->prepare("SELECT d.*,p.nama AS employee_name,mj.nama_jabatan AS job_position
                         FROM ttd_documents d
                         LEFT JOIN tb_pegawai p ON p.id_peg=d.id_peg
                         LEFT JOIN tb_jabatan j ON j.id_peg=d.id_peg AND j.status_jab='Aktif'
                         LEFT JOIN tb_master_jabatan mj ON CAST(j.kode_jabatan AS CHAR)=CAST(mj.kode_jabatan AS CHAR)
                         WHERE d.verification_token=:t LIMIT 1");
    $stmt->execute(['t'=>$token]); $row=$stmt->fetch();
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Verifikasi Dokumen</title><link rel="stylesheet" href="<?= e(app_url('assets/app.css')) ?>"></head><body>
<div class="login-card" style="min-height:100vh"><div class="card" style="width:min(650px,100%)"><div class="brand"><span class="brandmark">BKK</span>BPR BKK JATENG · VERIFIKASI</div>
<?php if(!$row): ?><div class="alert alert-error" style="margin-top:24px"><b>Dokumen tidak ditemukan / token tidak valid.</b></div>
<?php else: ?><div class="alert alert-success" style="margin-top:24px"><b>✓ Dokumen tercatat pada TTD Central</b></div><h2><?= e($row['document_title']) ?></h2><p><b>ID Dokumen</b><br><?= e($row['document_id']) ?></p><p><b>Penandatangan</b><br><?= e($row['employee_name'] ?: mask_employee_id($row['id_peg'])) ?></p><p><b>Jabatan</b><br><?= e($row['job_position'] ?: '-') ?></p><p><b>Aplikasi</b><br><?= e($row['application']) ?></p><p><b>Waktu TTD</b><br><?= e($row['signed_at']) ?></p><p><b>Document Hash (SHA-256)</b></p><div class="code"><?= e($row['document_hash']) ?></div><p class="muted" style="font-size:12px">Halaman ini adalah demonstrasi audit/verifikasi internal, bukan klaim TTE tersertifikasi PSrE.</p><?php endif; ?>
</div></div></body></html>
