<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';
require_once __DIR__.'/middleware/auth.php';

$user=require_auth();
$idPeg=(string)$user['employee_id'];
$error=null;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_validate($_POST['_token']??null)) {
        $error='Sesi formulir tidak valid.';
    } else {
        $pin=(string)($_POST['pin']??'');
        $pin2=(string)($_POST['pin_confirmation']??'');
        $data=(string)($_POST['signature_data']??'');
        if (!preg_match('/^\d{6}$/',$pin)) $error='PIN harus 6 digit angka.';
        elseif ($pin!==$pin2) $error='Konfirmasi PIN tidak sama.';
        elseif ($data==='') $error='TTD belum dibuat atau diupload.';
        else {
            try {
                $path=store_signature_image($data,$idPeg);
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO signature_profiles(id_peg,signature_path,status) VALUES(:id,:path,'ACTIVE')
                    ON DUPLICATE KEY UPDATE signature_path=VALUES(signature_path),status='ACTIVE',updated_at=CURRENT_TIMESTAMP")
                    ->execute(['id'=>$idPeg,'path'=>$path]);
                $pdo->prepare("INSERT INTO signature_security(id_peg,pin_hash,failed_attempt,locked_until) VALUES(:id,:pin,0,NULL)
                    ON DUPLICATE KEY UPDATE pin_hash=VALUES(pin_hash),failed_attempt=0,locked_until=NULL,updated_at=CURRENT_TIMESTAMP")
                    ->execute(['id'=>$idPeg,'pin'=>password_hash($pin,PASSWORD_DEFAULT)]);
                log_signature($pdo,$idPeg,'REGISTER','SUCCESS');
                $pdo->commit();
                flash('success','TTD dan PIN berhasil diaktifkan.');
                redirect_to('dashboard.php');
            } catch(Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error=$e->getMessage();
            }
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Registrasi TTD</title><link rel="stylesheet" href="<?=e(base_url('assets/app.css'))?>"></head><body><div class="app"><?php require __DIR__.'/partials/header.php';?>
<main class="page"><div class="grid">
<section class="card hero"><span class="badge">REGISTRASI WAJIB</span><h1 style="margin-top:14px">Buat TTD Digital Anda</h1><p class="muted">Gambar langsung atau upload TTD, lalu buat PIN 6 digit. Setelah aktif, PIN akan diminta setiap kali melakukan tanda tangan.</p>
<?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?>
<form method="post" id="form"><?=csrf_input()?>
<label>Gambar / Upload TTD</label><div class="signature-stage"><canvas id="pad" width="1000" height="400"></canvas></div>
<div class="actions" style="margin-top:12px"><button type="button" class="btn btn-soft" id="clear">Hapus</button><label class="btn btn-soft" style="margin:0;cursor:pointer">Upload PNG/JPG<input id="upload" type="file" accept="image/png,image/jpeg" hidden></label></div>
<input type="hidden" name="signature_data" id="signatureData">
<div class="two" style="margin-top:18px"><div><label>PIN TTD 6 digit</label><input class="input pin" type="password" inputmode="numeric" maxlength="6" name="pin" required></div><div><label>Konfirmasi PIN</label><input class="input pin" type="password" inputmode="numeric" maxlength="6" name="pin_confirmation" required></div></div>
<button class="btn btn-primary" style="margin-top:18px">Simpan & Aktifkan TTD</button></form></section>
<aside class="card side"><h3>Identitas dari SSO</h3><p><b><?=e($user['full_name']??'-')?></b><br><span class="muted"><?=e($user['employee_id']??'-')?></span></p><p><b>Jabatan</b><br><span class="muted"><?=e($user['job_position']??'-')?></span></p><p><b>Unit Kerja</b><br><span class="muted"><?=e($user['unit_kerja']??'-')?></span></p><div class="alert alert-warning">PIN tidak pernah disimpan sebagai angka asli. Sistem hanya menyimpan hash PIN.</div></aside>
</div></main></div>
<script>
const c=document.getElementById('pad'),ctx=c.getContext('2d');ctx.lineWidth=5;ctx.lineCap='round';ctx.strokeStyle='#0b315d';let drawing=false,last=null;
function pt(e){const r=c.getBoundingClientRect(),p=e.touches?e.touches[0]:e;return{x:(p.clientX-r.left)*c.width/r.width,y:(p.clientY-r.top)*c.height/r.height}}
function st(e){drawing=true;last=pt(e);e.preventDefault()}function mv(e){if(!drawing)return;const p=pt(e);ctx.beginPath();ctx.moveTo(last.x,last.y);ctx.lineTo(p.x,p.y);ctx.stroke();last=p;e.preventDefault()}function en(){drawing=false}
['mousedown','touchstart'].forEach(x=>c.addEventListener(x,st,{passive:false}));['mousemove','touchmove'].forEach(x=>c.addEventListener(x,mv,{passive:false}));['mouseup','mouseleave','touchend'].forEach(x=>c.addEventListener(x,en));
clear.onclick=()=>ctx.clearRect(0,0,c.width,c.height);
upload.onchange=e=>{const f=e.target.files[0];if(!f)return;const r=new FileReader();r.onload=()=>{const im=new Image();im.onload=()=>{ctx.clearRect(0,0,c.width,c.height);const s=Math.min(c.width/im.width,c.height/im.height),w=im.width*s,h=im.height*s;ctx.drawImage(im,(c.width-w)/2,(c.height-h)/2,w,h)};im.src=r.result};r.readAsDataURL(f)};
form.onsubmit=()=>signatureData.value=c.toDataURL('image/png');
</script></body></html>
