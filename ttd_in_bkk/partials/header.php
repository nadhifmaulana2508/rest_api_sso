<?php $u=current_user(); ?>
<header class="topbar">
  <div class="brand"><span class="brandmark">BKK</span><div>TTD IN BKK</div></div>
  <nav class="navlinks">
    <a href="<?=e(base_url('dashboard.php'))?>">Dashboard</a>
    <a href="<?=e(base_url('signature.php'))?>">TTD Saya</a>
    <a href="<?=e(base_url('documents.php'))?>">Dokumen</a>
    <a href="<?=e(base_url('activity.php'))?>">Aktivitas</a>
  </nav>
  <div class="user">
    <div class="avatar"><?=e(strtoupper(substr((string)($u['full_name']??'P'),0,1)))?></div>
    <div><b><?=e($u['full_name']??'-')?></b><div class="muted" style="font-size:12px"><?=e($u['job_position']??'-')?></div></div>
    <a class="btn btn-soft" href="<?=e(base_url('logout.php'))?>">Keluar</a>
  </div>
</header>
