<?php
declare(strict_types=1);
require_once __DIR__.'/config/bootstrap.php';

if (current_user()) redirect_to('dashboard.php');

$error=null;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!csrf_validate($_POST['_token']??null)) {
        $error='Sesi formulir tidak valid.';
    } else {
        $idPeg=trim((string)($_POST['id_peg']??''));
        $password=(string)($_POST['password']??'');
        if ($idPeg===''||$password==='') {
            $error='ID Pegawai dan password wajib diisi.';
        } else {
            try {
                $login=sso_login($idPeg,$password);
                $body=$login['body'];
                $token=$body['data']['token']??$body['token']??null;
                if ($login['status']!==200||!$token) {
                    $error=$body['message']??'Login SSO gagal.';
                } else {
                    $who=sso_whoami($token);
                    $profile=$who['body']['data']??null;
                    if ($who['status']!==200||!is_array($profile)) {
                        $error=$who['body']['message']??'Profil SSO tidak dapat diambil.';
                    } else {
                        session_regenerate_id(true);
                        $_SESSION['sso_token']=$token;
                        $_SESSION['user']=$profile;
                        log_signature($pdo,(string)$profile['employee_id'],'LOGIN','SUCCESS',null,'TTD IN BKK');
                        $sig=get_signature_profile($pdo,(string)$profile['employee_id']);
                        $sec=get_signature_security($pdo,(string)$profile['employee_id']);
                        redirect_to(($sig&&$sec&&$sig['status']==='ACTIVE')?'dashboard.php':'register-signature.php');
                    }
                }
            } catch(Throwable $e) {
                $error=$e->getMessage();
            }
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>TTD IN BKK</title><link rel="stylesheet" href="<?=e(base_url('assets/app.css'))?>"></head><body>
<div class="login">
  <section class="login-left">
    <div class="brand" style="color:#fff"><span class="brandmark" style="background:#fff;color:#093a67">BKK</span>PT BPR BKK JATENG</div>
    <div style="max-width:640px;margin-top:70px">
      <span class="badge" style="background:#ffffff22;color:#fff">TTD DIGITAL TERPUSAT</span>
      <h1 style="font-size:clamp(42px,5vw,72px);line-height:1;margin:20px 0">TTD IN BKK</h1>
      <p style="font-size:18px;opacity:.9">Satu identitas SSO, satu profil tanda tangan, dan satu PIN untuk penggunaan TTD lintas aplikasi internal.</p>
    </div>
  </section>
  <section class="login-right">
    <form class="card" method="post">
      <div class="brand"><span class="brandmark">BKK</span><div>TTD IN BKK<br><small class="muted">Login menggunakan BKK SSO 2026</small></div></div>
      <h2 style="margin-top:30px">Login Pegawai</h2>
      <?php if($error):?><div class="alert alert-error"><?=e($error)?></div><?php endif;?>
      <?=csrf_input()?>
      <label>ID Pegawai</label><input class="input" name="id_peg" required autofocus>
      <label>Password</label><input class="input" type="password" name="password" required>
      <button class="btn btn-primary w100" style="margin-top:20px">Masuk melalui SSO</button>
      <p class="muted" style="font-size:12px;margin-top:16px">Password hanya diteruskan ke BKK SSO dan tidak disimpan di database TTD IN BKK.</p>
    </form>
  </section>
</div></body></html>
