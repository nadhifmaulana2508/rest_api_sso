<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/sso.php';

if (current_user()) {
    redirect(app_url('dashboard.php'));
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idPeg = trim((string)($_POST['id_peg'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if ($idPeg === '' || $password === '') {
        $error = 'ID Pegawai dan password wajib diisi.';
    } else {
        try {
            $login = sso_login($idPeg, $password);
            $loginBody = $login['body'];
            $token = $loginBody['data']['token'] ?? $loginBody['token'] ?? null;

            if ($login['status'] !== 200 || !$token) {
                $error = $loginBody['message'] ?? 'Login SSO gagal.';
            } else {
                $who = sso_whoami($token);
                $whoBody = $who['body'];
                $user = $whoBody['data'] ?? null;

                if ($who['status'] !== 200 || !is_array($user)) {
                    $error = $whoBody['message'] ?? 'Gagal mengambil profil pegawai.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['sso_token'] = $token;
                    $_SESSION['user'] = $user;

                    $stmt = $pdo->prepare("SELECT status FROM ttd_profiles WHERE id_peg=:id LIMIT 1");
                    $stmt->execute(['id' => $user['employee_id']]);
                    $ttd = $stmt->fetch();

                    redirect($ttd && $ttd['status'] === 'ACTIVE'
                        ? app_url('dashboard.php')
                        : app_url('register-ttd.php'));
                }
            }
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SIMPEG - TTD Central Demo</title><link rel="stylesheet" href="<?= e(app_url('assets/app.css')) ?>"></head>
<body>
<div class="login-wrap">
  <section class="login-visual">
    <div class="brand" style="color:white"><span class="brandmark" style="background:#fff;color:#0b4f91">BKK</span> BPR BKK JATENG</div>
    <div style="max-width:640px;margin-top:64px">
      <span class="badge" style="background:#ffffff22;color:#fff">DEMO TTD CENTRAL</span>
      <h1 style="font-size:clamp(38px,5vw,70px);line-height:1.02;margin:22px 0">Satu TTD.<br>Untuk semua aplikasi.</h1>
      <p style="font-size:18px;opacity:.88">Login tetap menggunakan SSO SIMPEG. Jika TTD belum tersedia, middleware mengarahkan pegawai ke registrasi TTD dan pembuatan PIN.</p>
    </div>
  </section>
  <section class="login-card">
    <form class="card" method="post" autocomplete="off">
      <div class="brand"><span class="brandmark">BKK</span><div>SIMPEG<br><small class="muted">TTD Central Demo</small></div></div>
      <h2 style="margin-top:32px">Login Pegawai</h2>
      <p class="muted">Autentikasi melalui BKK SSO 2026 dengan app <b>simpeg</b>.</p>
      <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
      <label>ID Pegawai</label><input class="input" name="id_peg" required autofocus>
      <label>Password</label><input class="input" type="password" name="password" required>
      <button class="btn btn-primary w100" style="margin-top:20px">Login melalui SSO</button>
      <p class="muted" style="font-size:12px;margin-top:18px">Demo tidak menyimpan password SSO. Token disimpan hanya pada session PHP.</p>
    </form>
  </section>
</div>
</body></html>
