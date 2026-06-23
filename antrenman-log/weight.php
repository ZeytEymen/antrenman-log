<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (($_POST['action'] ?? '') === 'add') {
        $d = $_POST['log_date'] ?: date('Y-m-d');
        $kg = (float)str_replace(',', '.', $_POST['weight_kg'] ?? '');
        $note = trim($_POST['note'] ?? '');
        if ($kg > 0) {
            // aynı güne tekrar girilirse güncelle
            $st = $pdo->prepare('INSERT INTO bodyweight (user_id,log_date,weight_kg,note)
                VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE weight_kg=VALUES(weight_kg), note=VALUES(note)');
            $st->execute([$me['id'], $d, $kg, $note ?: null]);
            flash('Kilo kaydedildi.');
        }
    } elseif (($_POST['action'] ?? '') === 'del') {
        $st = $pdo->prepare('DELETE FROM bodyweight WHERE id=? AND user_id=?');
        $st->execute([(int)$_POST['id'], $me['id']]);
        flash('Kayıt silindi.');
    }
    redirect('weight.php');
}

$rows = $pdo->prepare('SELECT * FROM bodyweight WHERE user_id=? ORDER BY log_date DESC');
$rows->execute([$me['id']]);
$list = $rows->fetchAll();

$title='Kilo'; $nav='weight';
require __DIR__ . '/header.php';
?>
<div class="card">
  <h2>Yeni kilo kaydı</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <div class="row">
      <div><label>Tarih</label><input type="date" name="log_date" value="<?= date('Y-m-d') ?>"></div>
      <div><label>Kilo (kg)</label><input type="number" step="0.1" name="weight_kg" placeholder="108.0" required></div>
    </div>
    <div style="margin-top:10px"><label>Not (opsiyonel)</label><input name="note" placeholder="sabah aç karnına"></div>
    <button class="btn" style="margin-top:14px">Kaydet</button>
  </form>
</div>

<div class="card">
  <h2>Geçmiş</h2>
  <?php if (!$list): ?>
    <p class="muted">Henüz kayıt yok.</p>
  <?php else: ?>
    <table>
      <tr><th>Tarih</th><th class="n">Kilo</th><th>Not</th><th></th></tr>
      <?php $prev=null; foreach ($list as $r):
        $delta = $prev!==null ? (float)$r['weight_kg'] - $prev : null;
        $prev = (float)$r['weight_kg']; ?>
      <tr>
        <td><?= h(date('d.m.Y', strtotime($r['log_date']))) ?></td>
        <td class="n"><?= number_format((float)$r['weight_kg'],1) ?></td>
        <td class="muted"><?= h($r['note']) ?></td>
        <td>
          <form method="post" onsubmit="return confirm('Silinsin mi?')" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="del">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn sm danger">Sil</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
