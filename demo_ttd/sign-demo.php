<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/middleware/ttd.php';

[$user,$profile] = require_ttd($pdo);
$docId='DEMO-' . date('Ymd-His');
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Demo Sign</title><link rel="stylesheet" href="<?= e(app_url('assets/app.css')) ?>"><script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script></head><body>
<div class="shell"><header class="topbar"><div class="brand"><span class="brandmark">BKK</span> SIGNATURE DEMO</div><a class="btn btn-soft" href="<?= e(app_url('dashboard.php')) ?>">← Dashboard</a></header>
<main class="container"><div class="grid"><section class="card hero"><div class="split"><div><span class="badge">DOKUMEN DUMMY</span><h2 style="margin-top:12px">Persetujuan Internal</h2></div><button class="btn btn-primary" id="openSign">Tanda Tangani</button></div>
<div class="doc" id="doc"><div class="doc-head"><div><b>PT BPR BKK JATENG (Perseroda)</b><div class="muted">Dokumen demonstrasi TTD Central</div></div><div><b><?= e($docId) ?></b></div></div><h3 style="margin-top:30px">Nota Persetujuan Demo</h3><p>Dokumen ini digunakan untuk memperagakan proses otorisasi tanda tangan digital internal melalui SIMPEG dan TTD Central.</p><p><b>Penandatangan:</b> <?= e($user['full_name']) ?><br><b>Jabatan:</b> <?= e($user['job_position'] ?? '-') ?></p><div id="result" class="sign-result" style="display:none"><div class="sign-box"><div><div class="muted">TTD Pegawai</div><img id="sigImg" class="signature-img" alt="TTD"></div></div><div class="sign-box"><div><div class="muted">QR Verifikasi</div><div class="qr-wrap"><div id="qr"></div><div class="qr-logo">BKK</div></div></div></div></div></div></section>
<aside class="card side"><h3>Flow</h3><div class="steps" style="display:grid"><span class="step active">1. User klik Tanda Tangani</span><span class="step">2. Signature API minta PIN</span><span class="step">3. PIN diverifikasi server</span><span class="step">4. Generate hash & audit trail</span><span class="step">5. Response TTD + QR</span></div><div id="hashBox" class="code" style="display:none"></div></aside></div></main></div>
<div class="modal" id="modal"><div class="modal-card"><h3>Verifikasi Tanda Tangan</h3><p class="muted">Masukkan PIN TTD 6 digit milik Anda.</p><input class="input pin" maxlength="6" inputmode="numeric" type="password" id="pin" placeholder="••••••"><div id="msg"></div><div class="actions"><button class="btn btn-soft" id="cancel">Batal</button><button class="btn btn-primary" id="doSign">Verifikasi & TTD</button></div></div></div>
<script>
const modal=document.getElementById('modal');openSign.onclick=()=>{modal.classList.add('show');pin.focus()};cancel.onclick=()=>modal.classList.remove('show');
doSign.onclick=async()=>{msg.innerHTML='';doSign.disabled=true;try{const r=await fetch('<?= e(app_url('api/sign.php')) ?>',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({pin:pin.value,document_id:'<?= e($docId) ?>',document_title:'Nota Persetujuan Demo',application:'SIMPEG'})});const j=await r.json();if(!r.ok||!j.success)throw new Error(j.message||'Gagal tanda tangan');modal.classList.remove('show');result.style.display='grid';sigImg.src=j.data.signature_url;hashBox.style.display='block';hashBox.textContent='SHA-256: '+j.data.document_hash;qr.innerHTML='';if(window.QRCode){new QRCode(qr,{text:j.data.verification_url,width:150,height:150})}else{qr.innerHTML='<div class="code">'+j.data.verification_url+'</div>'}}catch(e){msg.innerHTML='<div class="alert alert-error">'+e.message+'</div>'}finally{doSign.disabled=false}};
</script></body></html>
