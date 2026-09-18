<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/middleware/auth.php';
require_once __DIR__ . '/middleware/ttd.php';

$user = require_login();
$idPeg = (string)$user['employee_id'];
$existing = get_ttd_profile($pdo, $idPeg);
$flash = get_flash();
$error = null;

function save_signature_image(string $dataUrl, string $idPeg): string
{
    if (!preg_match('#^data:image/(png|jpeg);base64,(.+)$#', $dataUrl, $m)) {
        throw new RuntimeException('Format gambar TTD tidak valid.');
    }
    $raw = base64_decode($m[2], true);
    if ($raw === false || strlen($raw) < 100 || strlen($raw) > 3 * 1024 * 1024) {
        throw new RuntimeException('Ukuran gambar TTD tidak valid.');
    }

    $info = @getimagesizefromstring($raw);
    if (!$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
        throw new RuntimeException('File bukan PNG/JPG yang valid.');
    }

    $dir = storage_path('signatures');
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        throw new RuntimeException('Folder penyimpanan TTD tidak dapat dibuat.');
    }

    $filename = hash('sha256', $idPeg) . '.png';
    $full = $dir . '/' . $filename;

    if (function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring($raw);
        if ($src) {
            $w = imagesx($src); $h = imagesy($src);
            $dst = imagecreatetruecolor($w, $h);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
            imagefill($dst, 0, 0, $transparent);
            for ($y=0;$y<$h;$y++) {
                for ($x=0;$x<$w;$x++) {
                    $rgb = imagecolorat($src,$x,$y);
                    $r=($rgb>>16)&255; $g=($rgb>>8)&255; $b=$rgb&255;
                    if ($r>238 && $g>238 && $b>238) {
                        imagesetpixel($dst,$x,$y,$transparent);
                    } else {
                        $c=imagecolorallocatealpha($dst,$r,$g,$b,0);
                        imagesetpixel($dst,$x,$y,$c);
                    }
                }
            }
            imagepng($dst, $full, 6);
            imagedestroy($src); imagedestroy($dst);
        } else {
            file_put_contents($full, $raw);
        }
    } else {
        file_put_contents($full, $raw);
    }

    return 'storage/signatures/' . $filename;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = (string)($_POST['pin'] ?? '');
    $pin2 = (string)($_POST['pin_confirmation'] ?? '');
    $signatureData = (string)($_POST['signature_data'] ?? '');

    if (!preg_match('/^\d{6}$/', $pin)) {
        $error = 'PIN harus tepat 6 digit angka.';
    } elseif ($pin !== $pin2) {
        $error = 'Konfirmasi PIN tidak sama.';
    } elseif ($signatureData === '') {
        $error = 'Silakan gambar atau upload TTD.';
    } else {
        try {
            $relative = save_signature_image($signatureData, $idPeg);
            $hash = password_hash($pin, PASSWORD_DEFAULT);

            $sql = "INSERT INTO ttd_profiles (id_peg,signature_path,pin_hash,status,failed_attempt,locked_until)
                    VALUES (:id,:path,:pin,'ACTIVE',0,NULL)
                    ON DUPLICATE KEY UPDATE signature_path=VALUES(signature_path),pin_hash=VALUES(pin_hash),
                    status='ACTIVE',failed_attempt=0,locked_until=NULL,updated_at=CURRENT_TIMESTAMP";
            $pdo->prepare($sql)->execute(['id'=>$idPeg,'path'=>$relative,'pin'=>$hash]);

            $pdo->prepare("INSERT INTO ttd_logs(id_peg,application,action,status,ip_address,user_agent)
                           VALUES(:id,'SIMPEG','REGISTER','SUCCESS',:ip,:ua)")
                ->execute([
                    'id'=>$idPeg,
                    'ip'=>$_SERVER['REMOTE_ADDR'] ?? null,
                    'ua'=>substr($_SERVER['HTTP_USER_AGENT'] ?? '',0,255)
                ]);

            set_flash('success', 'TTD dan PIN berhasil dibuat. Middleware TTD sekarang lolos.');
            redirect(app_url('dashboard.php'));
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Registrasi TTD</title><link rel="stylesheet" href="<?= e(app_url('assets/app.css')) ?>"></head><body>
<div class="shell">
<header class="topbar"><div class="brand"><span class="brandmark">BKK</span> SIMPEG · TTD CENTRAL</div><a class="btn btn-soft" href="<?= e(app_url('logout.php')) ?>">Keluar</a></header>
<main class="container">
  <div class="grid">
    <section class="card hero">
      <span class="badge">MIDDLEWARE TTD</span><h1 style="margin-top:14px">Registrasi Tanda Tangan</h1>
      <p class="muted">Akun SSO sudah valid. Karena profil TTD belum aktif, akses dashboard ditahan sampai registrasi selesai.</p>
      <div class="steps"><span class="step">1. Login SSO ✓</span><span class="step active">2. Registrasi TTD</span><span class="step active">3. Buat PIN</span><span class="step">4. Dashboard</span></div>
      <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <form method="post" id="ttdForm">
        <label>Gambar TTD langsung</label>
        <div class="signature-stage"><canvas id="pad" width="1000" height="400"></canvas></div>
        <div class="actions">
          <button type="button" class="btn btn-soft" id="clearPad">Hapus</button>
          <label class="btn btn-soft" style="margin:0;cursor:pointer">Upload PNG/JPG<input id="upload" type="file" accept="image/png,image/jpeg" hidden></label>
        </div>
        <input type="hidden" name="signature_data" id="signatureData">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:18px">
          <div><label>Buat PIN TTD (6 digit)</label><input class="input pin" inputmode="numeric" maxlength="6" name="pin" type="password" required></div>
          <div><label>Konfirmasi PIN</label><input class="input pin" inputmode="numeric" maxlength="6" name="pin_confirmation" type="password" required></div>
        </div>
        <button class="btn btn-primary" style="margin-top:20px">Simpan TTD & Aktifkan PIN</button>
      </form>
    </section>
    <aside class="card side">
      <h3>Identitas Pegawai</h3><div class="userline"><div class="avatar"><?= e(strtoupper(substr((string)($user['full_name'] ?? 'P'),0,1))) ?></div><div><b><?= e($user['full_name'] ?? '-') ?></b><div class="muted"><?= e($user['employee_id'] ?? '-') ?></div></div></div>
      <hr style="border:0;border-top:1px solid #edf1f6;margin:20px 0">
      <p><b>Unit</b><br><span class="muted"><?= e($user['unit_kerja'] ?? '-') ?></span></p><p><b>Jabatan</b><br><span class="muted"><?= e($user['job_position'] ?? '-') ?></span></p>
      <div class="alert alert-warning">PIN hanya untuk otorisasi pemakaian TTD. PIN disimpan dalam bentuk hash, bukan angka asli.</div>
    </aside>
  </div>
</main></div>
<script>
const c=document.getElementById('pad'),ctx=c.getContext('2d');ctx.lineWidth=5;ctx.lineCap='round';ctx.strokeStyle='#0c2f5a';let draw=false,last=null;
function point(e){const r=c.getBoundingClientRect(),p=e.touches?e.touches[0]:e;return{x:(p.clientX-r.left)*c.width/r.width,y:(p.clientY-r.top)*c.height/r.height}}
function start(e){draw=true;last=point(e);e.preventDefault()}function move(e){if(!draw)return;const p=point(e);ctx.beginPath();ctx.moveTo(last.x,last.y);ctx.lineTo(p.x,p.y);ctx.stroke();last=p;e.preventDefault()}function end(){draw=false}
['mousedown','touchstart'].forEach(x=>c.addEventListener(x,start,{passive:false}));['mousemove','touchmove'].forEach(x=>c.addEventListener(x,move,{passive:false}));['mouseup','mouseleave','touchend'].forEach(x=>c.addEventListener(x,end));
document.getElementById('clearPad').onclick=()=>ctx.clearRect(0,0,c.width,c.height);
document.getElementById('upload').onchange=e=>{const f=e.target.files[0];if(!f)return;const r=new FileReader();r.onload=()=>{const im=new Image();im.onload=()=>{ctx.clearRect(0,0,c.width,c.height);const s=Math.min(c.width/im.width,c.height/im.height),w=im.width*s,h=im.height*s;ctx.drawImage(im,(c.width-w)/2,(c.height-h)/2,w,h)};im.src=r.result};r.readAsDataURL(f)};
document.getElementById('ttdForm').onsubmit=()=>{document.getElementById('signatureData').value=c.toDataURL('image/png')};
</script></body></html>
