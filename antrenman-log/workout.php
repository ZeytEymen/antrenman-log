<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (($_POST['action'] ?? '') === 'new') {
        $d = $_POST['workout_date'] ?: date('Y-m-d');
        $s = in_array($_POST['session_label'] ?? '', ['A','B','Serbest'], true) ? $_POST['session_label'] : 'Serbest';
        $st = $pdo->prepare('INSERT INTO workouts (user_id,workout_date,session_label) VALUES (?,?,?)');
        $st->execute([$me['id'], $d, $s]);
        redirect('log.php?id=' . $pdo->lastInsertId());
    }
    if (($_POST['action'] ?? '') === 'del') {
        $st = $pdo->prepare('DELETE FROM workouts WHERE id=? AND user_id=?');
        $st->execute([(int)$_POST['id'], $me['id']]);
        flash('Antrenman silindi.');
        redirect('workout.php');
    }
}

$w = $pdo->prepare('SELECT w.*, COUNT(s.id) AS sets
    FROM workouts w LEFT JOIN workout_sets s ON s.workout_id=w.id
    WHERE w.user_id=? GROUP BY w.id ORDER BY w.workout_date DESC, w.id DESC');
$w->execute([$me['id']]);
$list = $w->fetchAll();

$title='Antrenman'; $nav='workout';
require __DIR__ . '/header.php';
?>
<div class="card">
  <h2>Yeni antrenman başlat</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="new">
    <div class="row">
      <div><label>Tarih</label><input type="date" name="workout_date" value="<?= date('Y-m-d') ?>"></div>
      <div><label>Seans</label>
        <select name="session_label">
          <option value="A">A (Seans A)</option>
          <option value="B">B (Seans B)</option>
          <option value="Serbest">Serbest</option>
        </select>
      </div>
    </div>
    <button class="btn" style="margin-top:14px">Başlat →</button>
  </form>
</div>

<div class="card">
  <h2>Tüm antrenmanlar</h2>
  <?php if (!$list): ?>
    <p class="muted">Henüz antrenman yok.</p>
  <?php else: ?>
    <table>
      <tr><th>Tarih</th><th>Seans</th><th class="n">Set</th><th></th></tr>
      <?php foreach ($list as $r): ?>
      <tr>
        <td><?= h(date('d.m.Y', strtotime($r['workout_date']))) ?></td>
        <td><span class="pill <?= h($r['session_label']) ?>"><?= h($r['session_label']) ?></span></td>
        <td class="n"><?= (int)$r['sets'] ?></td>
        <td style="white-space:nowrap">
          <a class="btn sm ghost" href="log.php?id=<?= (int)$r['id'] ?>">Aç</a>
          <form method="post" onsubmit="return confirm('Antrenman ve setleri silinsin mi?')" style="display:inline">
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
