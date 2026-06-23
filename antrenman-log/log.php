<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user($pdo);

$wid = (int)($_GET['id'] ?? 0);
$wq = $pdo->prepare('SELECT * FROM workouts WHERE id=? AND user_id=?');
$wq->execute([$wid, $me['id']]);
$workout = $wq->fetch();
if (!$workout) { http_response_code(404); exit('Antrenman bulunamadı.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $ex = (int)$_POST['exercise_id'];
        $reps = $_POST['reps'] !== '' ? (int)$_POST['reps'] : null;
        $wt   = $_POST['weight'] !== '' ? (float)str_replace(',', '.', $_POST['weight']) : null;
        $note = trim($_POST['rpe_note'] ?? '');
        if ($ex) {
            // sıradaki set numarası
            $sn = $pdo->prepare('SELECT COALESCE(MAX(set_number),0)+1 n FROM workout_sets WHERE workout_id=? AND exercise_id=?');
            $sn->execute([$wid, $ex]);
            $num = (int)$sn->fetch()['n'];
            $ins = $pdo->prepare('INSERT INTO workout_sets (workout_id,exercise_id,set_number,reps,weight,rpe_note)
                VALUES (?,?,?,?,?,?)');
            $ins->execute([$wid, $ex, $num, $reps, $wt, $note ?: null]);
        }
    } elseif ($act === 'delset') {
        $d = $pdo->prepare('DELETE FROM workout_sets WHERE id=? AND workout_id=?');
        $d->execute([(int)$_POST['set_id'], $wid]);
    } elseif ($act === 'note') {
        $n = $pdo->prepare('UPDATE workouts SET notes=? WHERE id=? AND user_id=?');
        $n->execute([trim($_POST['notes'] ?? '') ?: null, $wid, $me['id']]);
        flash('Not kaydedildi.');
    }
    redirect('log.php?id=' . $wid . (isset($_POST['exercise_id']) ? '#ex'.(int)$_POST['exercise_id'] : ''));
}

// Hareket listesi (dropdown)
$exAll = $pdo->query('SELECT * FROM exercises WHERE active=1 ORDER BY session, sort_order, name')->fetchAll();

// Bu antrenmanın setleri, harekete göre gruplu
$ss = $pdo->prepare('SELECT s.*, e.name, e.notes AS ex_note, e.category
    FROM workout_sets s JOIN exercises e ON e.id=s.exercise_id
    WHERE s.workout_id=? ORDER BY s.exercise_id, s.set_number');
$ss->execute([$wid]);
$sets = $ss->fetchAll();
$grouped = [];
foreach ($sets as $s) { $grouped[$s['exercise_id']][] = $s; }

// "Geçen sefer" referansı — bu antrenmandaki her hareket için bir önceki seans
function last_time(PDO $pdo, int $uid, int $exId, int $curWid, string $curDate): ?array {
    $q = $pdo->prepare('SELECT w.id, w.workout_date FROM workouts w
        JOIN workout_sets s ON s.workout_id=w.id
        WHERE w.user_id=? AND s.exercise_id=? AND w.id<>?
          AND (w.workout_date < ? OR (w.workout_date = ? AND w.id < ?))
        ORDER BY w.workout_date DESC, w.id DESC LIMIT 1');
    $q->execute([$uid, $exId, $curWid, $curDate, $curDate, $curWid]);
    $prev = $q->fetch();
    if (!$prev) return null;
    $s = $pdo->prepare('SELECT set_number,reps,weight FROM workout_sets WHERE workout_id=? AND exercise_id=? ORDER BY set_number');
    $s->execute([$prev['id'], $exId]);
    return ['date'=>$prev['workout_date'], 'sets'=>$s->fetchAll()];
}
function fmt_set(array $s): string {
    $wt = $s['weight']!==null ? rtrim(rtrim(number_format((float)$s['weight'],1),'0'),'.').'kg' : 'VA';
    $rp = $s['reps']!==null ? (int)$s['reps'] : '–';
    return $wt.'×'.$rp;
}

$title='Kayıt'; $nav='workout';
require __DIR__ . '/header.php';
?>
<div class="card">
  <h2>
    <?= h(date('d.m.Y', strtotime($workout['workout_date']))) ?>
    · <span class="pill <?= h($workout['session_label']) ?>"><?= h($workout['session_label']) ?></span>
  </h2>
  <a class="muted" href="workout.php" style="font-size:13px">← antrenman listesi</a>
</div>

<div class="card">
  <h2>Set ekle</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <label>Hareket</label>
    <select name="exercise_id" required>
      <?php
        $curSess=null;
        foreach ($exAll as $e):
          if ($e['session']!==$curSess){ if($curSess!==null) echo '</optgroup>'; $curSess=$e['session'];
            $lbl=['A'=>'Seans A','B'=>'Seans B','both'=>'Kardiyo/Ortak','none'=>'Diğer'][$curSess]??$curSess;
            echo '<optgroup label="'.h($lbl).'">'; }
      ?>
        <option value="<?= (int)$e['id'] ?>"<?= $e['session']===$workout['session_label']?' selected':'' ?>>
          <?= h($e['name']) ?><?= $e['is_optional']?' (ops.)':'' ?>
        </option>
      <?php endforeach; if($curSess!==null) echo '</optgroup>'; ?>
    </select>
    <div class="row" style="margin-top:10px">
      <div><label>Ağırlık (kg)</label><input type="number" step="0.5" name="weight" placeholder="40" inputmode="decimal"></div>
      <div><label>Tekrar</label><input type="number" name="reps" placeholder="20" inputmode="numeric"></div>
    </div>
    <div style="margin-top:10px"><label>Not (ops.)</label><input name="rpe_note" placeholder="form iyi / strap kullandım"></div>
    <button class="btn" style="margin-top:14px">Set ekle</button>
  </form>
  <p class="muted" style="font-size:12px;margin-top:10px">Vücut ağırlığı hareketinde ağırlığı boş bırak → "VA" görünür.</p>
</div>

<?php if (!$grouped): ?>
  <div class="card"><p class="muted">Bu antrenmanda henüz set yok. Yukarıdan ekle.</p></div>
<?php else: foreach ($grouped as $exId => $rows):
  $name=$rows[0]['name']; $exNote=$rows[0]['ex_note'];
  $prev = last_time($pdo, (int)$me['id'], (int)$exId, $wid, $workout['workout_date']); ?>
  <div class="card" id="ex<?= (int)$exId ?>">
    <h2 style="text-transform:none;color:var(--ink);font-size:16px;margin-bottom:6px"><?= h($name) ?></h2>
    <?php if ($exNote): ?><p class="muted" style="font-size:12px;margin:0 0 8px"><?= h($exNote) ?></p><?php endif; ?>
    <?php if ($prev): ?>
      <p class="ghosttxt" style="margin:0 0 10px">geçen (<?= h(date('d.m', strtotime($prev['date']))) ?>):
        <?= h(implode('  ', array_map('fmt_set', $prev['sets']))) ?></p>
    <?php endif; ?>
    <table>
      <tr><th>Set</th><th class="n">Ağırlık</th><th class="n">Tekrar</th><th>Not</th><th></th></tr>
      <?php foreach ($rows as $s): ?>
      <tr>
        <td class="n"><?= (int)$s['set_number'] ?></td>
        <td class="n"><?= $s['weight']!==null ? h(rtrim(rtrim(number_format((float)$s['weight'],1),'0'),'.')).' kg' : '<span class="muted">VA</span>' ?></td>
        <td class="n"><?= $s['reps']!==null ? (int)$s['reps'] : '–' ?></td>
        <td class="muted"><?= h($s['rpe_note']) ?></td>
        <td>
          <form method="post" style="display:inline">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delset">
            <input type="hidden" name="set_id" value="<?= (int)$s['id'] ?>">
            <button class="btn sm danger">×</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endforeach; endif; ?>

<div class="card">
  <h2>Antrenman notu</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="note">
    <textarea name="notes" rows="2" placeholder="genel his, ağrı durumu, diz/bilek..."><?= h($workout['notes']) ?></textarea>
    <button class="btn ghost sm" style="margin-top:10px">Notu kaydet</button>
  </form>
</div>
<?php require __DIR__ . '/footer.php'; ?>
