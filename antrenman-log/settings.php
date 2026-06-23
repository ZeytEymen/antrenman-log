<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user($pdo);
$pwErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['action'] ?? '';
    if ($act === 'goals') {
        $sw = (float)str_replace(',', '.', $_POST['start_weight'] ?? $me['start_weight']);
        $tw = (float)str_replace(',', '.', $_POST['target_weight'] ?? $me['target_weight']);
        $hc = (int)($_POST['height_cm'] ?? $me['height_cm']);
        $st = $pdo->prepare('UPDATE users SET start_weight=?, target_weight=?, height_cm=? WHERE id=?');
        $st->execute([$sw, $tw, $hc, $me['id']]);
        flash('Hedefler güncellendi.');
        redirect('settings.php');
    } elseif ($act === 'pw') {
        $cur = $_POST['current'] ?? '';
        $new = $_POST['new'] ?? '';
        $new2 = $_POST['new2'] ?? '';
        if (!password_verify($cur, $me['password_hash'])) {
            $pwErr = 'Mevcut şifre yanlış.';
        } elseif (strlen($new) < 6) {
            $pwErr = 'Yeni şifre en az 6 karakter olmalı.';
        } elseif ($new !== $new2) {
            $pwErr = 'Yeni şifreler eşleşmiyor.';
        } else {
            $st = $pdo->prepare('UPDATE users SET password_hash=? WHERE id=?');
            $st->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
            flash('Şifre değiştirildi.');
            redirect('settings.php');
        }
    }
}

$title='Ayarlar'; $nav='set';
require __DIR__ . '/header.php';
?>
<div class="card">
  <h2>Hedef & profil</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="goals">
    <div class="row">
      <div><label>Başlangıç kilosu</label><input type="number" step="0.1" name="start_weight" value="<?= h($me['start_weight']) ?>"></div>
      <div><label>Hedef kilo</label><input type="number" step="0.1" name="target_weight" value="<?= h($me['target_weight']) ?>"></div>
      <div><label>Boy (cm)</label><input type="number" name="height_cm" value="<?= h($me['height_cm']) ?>"></div>
    </div>
    <button class="btn" style="margin-top:14px">Kaydet</button>
  </form>
</div>

<div class="card">
  <h2>Şifre değiştir</h2>
  <?php if ($pwErr): ?><div class="err"><?= h($pwErr) ?></div><?php endif; ?>
  <form method="post" autocomplete="off">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="pw">
    <label>Mevcut şifre</label><input type="password" name="current" required>
    <div class="row" style="margin-top:10px">
      <div><label>Yeni şifre</label><input type="password" name="new" required></div>
      <div><label>Yeni şifre (tekrar)</label><input type="password" name="new2" required></div>
    </div>
    <button class="btn" style="margin-top:14px">Şifreyi değiştir</button>
  </form>
  <p class="muted" style="font-size:12px;margin-top:10px">İlk kurulumda varsayılan şifre <b>antrenman</b> idi — mutlaka değiştir.</p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
