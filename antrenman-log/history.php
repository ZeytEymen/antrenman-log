<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user($pdo);

$exList = $pdo->query('SELECT id,name FROM exercises WHERE active=1 ORDER BY name')->fetchAll();
$sel = (int)($_GET['ex'] ?? ($exList[0]['id'] ?? 0));

$points = [];
if ($sel) {
    $q = $pdo->prepare('SELECT w.workout_date,
            MAX(s.weight) AS max_w,
            SUM(COALESCE(s.weight,0)*COALESCE(s.reps,0)) AS volume,
            COUNT(s.id) AS sets, SUM(s.reps) AS total_reps
        FROM workout_sets s JOIN workouts w ON w.id=s.workout_id
        WHERE w.user_id=? AND s.exercise_id=?
        GROUP BY w.workout_date ORDER BY w.workout_date ASC');
    $q->execute([$me['id'], $sel]);
    $points = $q->fetchAll();
}

$title='İlerleme'; $nav='history';
require __DIR__ . '/header.php';
?>
<div class="card">
  <h2>Hareket ilerlemesi</h2>
  <form method="get">
    <label>Hareket seç</label>
    <select name="ex" onchange="this.form.submit()">
      <?php foreach ($exList as $e): ?>
        <option value="<?= (int)$e['id'] ?>"<?= $e['id']==$sel?' selected':'' ?>><?= h($e['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </form>
</div>

<?php if (count($points) < 1): ?>
  <div class="card"><p class="muted">Bu hareket için kayıt yok.</p></div>
<?php else: ?>
<div class="card">
  <h2>En yüksek ağırlık (kg)</h2>
  <?php if (count($points) >= 2): ?>
    <canvas id="exChart" height="150"></canvas>
  <?php else: ?>
    <p class="muted">Grafik için en az 2 farklı tarih gerekli.</p>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Seans seans</h2>
  <table>
    <tr><th>Tarih</th><th class="n">Maks kg</th><th class="n">Set</th><th class="n">Σ tekrar</th><th class="n">Hacim</th></tr>
    <?php foreach (array_reverse($points) as $p): ?>
    <tr>
      <td><?= h(date('d.m.Y', strtotime($p['workout_date']))) ?></td>
      <td class="n"><?= $p['max_w']!==null ? h(rtrim(rtrim(number_format((float)$p['max_w'],1),'0'),'.')) : '–' ?></td>
      <td class="n"><?= (int)$p['sets'] ?></td>
      <td class="n"><?= (int)$p['total_reps'] ?></td>
      <td class="n"><?= number_format((float)$p['volume'],0) ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>

<?php if (count($points) >= 2): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('exChart'),{
  type:'line',
  data:{
    labels: <?= json_encode(array_map(fn($p)=>date('d.m', strtotime($p['workout_date'])), $points)) ?>,
    datasets:[{
      data: <?= json_encode(array_map(fn($p)=>$p['max_w']!==null?(float)$p['max_w']:null, $points)) ?>,
      borderColor:'#f0a830', backgroundColor:'rgba(240,168,48,.12)', fill:true,
      tension:.3, pointRadius:3, pointBackgroundColor:'#f0a830', spanGaps:true
    }]
  },
  options:{plugins:{legend:{display:false}},
    scales:{y:{grid:{color:'#2c313b'},ticks:{color:'#8a9099'}},
            x:{grid:{display:false},ticks:{color:'#8a9099'}}}}
});
</script>
<?php endif; ?>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
