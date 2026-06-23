<?php
// header.php — ortak üst kısım. Kullanım: $title ayarla, sonra include.
$title = $title ?? 'Antrenman Takip';
$nav   = $nav   ?? '';
$msg   = take_flash();
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#15171c">
<title><?= h($title) ?></title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php">ANTRENMAN<span>·LOG</span></a>
  <nav>
    <a href="index.php"   class="<?= $nav==='home'   ?'on':'' ?>">Özet</a>
    <a href="workout.php" class="<?= $nav==='workout'?'on':'' ?>">Antrenman</a>
    <a href="weight.php"  class="<?= $nav==='weight' ?'on':'' ?>">Kilo</a>
    <a href="history.php" class="<?= $nav==='history'?'on':'' ?>">İlerleme</a>
    <a href="exercises.php" class="<?= $nav==='ex'?'on':'' ?>">Hareketler</a>
    <a href="settings.php" class="<?= $nav==='set'?'on':'' ?>">Ayarlar</a>
    <a href="logout.php" class="muted">Çıkış</a>
  </nav>
</header>
<main class="wrap">
<?php if ($msg): ?><div class="flash"><?= h($msg) ?></div><?php endif; ?>
