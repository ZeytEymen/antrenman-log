<?php
require __DIR__ . '/config.php';
if (!empty($_SESSION['uid'])) redirect('index.php');

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';
    $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
    $st->execute([$u]);
    $user = $st->fetch();
    if ($user && password_verify($p, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = $user['id'];
        redirect('index.php');
    }
    $err = 'Kullanıcı adı veya şifre hatalı.';
}
?><!doctype html>
<html lang="tr"><head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Giriş · Antrenman Log</title>
<link rel="stylesheet" href="style.css">
</head><body>
<div class="login-wrap">
  <div class="card">
    <a class="brand" href="#" style="font-size:18px">ANTRENMAN<span>·LOG</span></a>
    <p class="muted" style="margin:6px 0 20px;font-size:13px">Kişisel günlük — giriş yap</p>
    <?php if ($err): ?><div class="err"><?= h($err) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <?= csrf_field() ?>
      <label>Kullanıcı adı</label>
      <input name="username" autofocus required>
      <label style="margin-top:12px">Şifre</label>
      <input type="password" name="password" required>
      <button class="btn" style="margin-top:18px;width:100%">Giriş yap</button>
    </form>
  </div>
</div>
</body></html>
