<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user($pdo);

// Vücut ağırlığı verileri
$bw = $pdo->prepare('SELECT log_date, weight_kg FROM bodyweight WHERE user_id=? ORDER BY log_date ASC');
$bw->execute([$me['id']]);
$weights = $bw->fetchAll();

$start   = (float)$me['start_weight'];
$target  = (float)$me['target_weight'];
$latest  = $weights ? (float)end($weights)['weight_kg'] : $start;
$first   = $weights ? (float)$weights[0]['weight_kg'] : $start;
$lost    = $first - $latest;
$toGo    = $latest - $target;
$span    = max($start - $target, 0.1);
$pct     = max(0, min(100, round(($start - $latest) / $span * 100)));

// Son antrenmanlar
$w = $pdo->prepare('SELECT w.*, COUNT(s.id) AS sets
    FROM workouts w LEFT JOIN workout_sets s ON s.workout_id=w.id
    WHERE w.user_id=? GROUP BY w.id ORDER BY w.workout_date DESC, w.id DESC LIMIT 6');
$w->execute([$me['id']]);
$recent = $w->fetchAll();

// Bu hafta kaç antrenman
$cw = $pdo->prepare("SELECT COUNT(*) c FROM workouts WHERE user_id=? AND YEARWEEK(workout_date,1)=YEARWEEK(CURDATE(),1)");
$cw->execute([$me['id']]);
$thisWeek = (int)$cw->fetch()['c'];

$title='Özet'; $nav='home';
require __DIR__ . '/header.php';
?>
<div class="grid">
  <div class="stat">
    <div class="label">Güncel kilo</div>
    <div class="num accent"><?= number_format($latest,1) ?><small> kg</small></div>
  </div>
  <div class="stat">
    <div class="label">Hedefe kalan</div>
    <div class="num"><?= number_format(max($toGo,0),1) ?><small> kg</small></div>
  </div>
  <div class="stat">
    <div class="label">Verilen (başlangıçtan)</div>
    <div class="num down"><?= number_format(max($lost,0),1) ?><small> kg</small></div>
  </div>
  <div class="stat">
    <div class="label">Bu hafta</div>
    <div class="num"><?= $thisWeek ?><small> antrenman</small></div>
  </div>
</div>

<div class="card">
  <h2>Hedef: <?= number_format($start,0) ?> → <?= number_format($target,0) ?> kg</h2>
  <div class="bar"><span style="width:<?= $pct ?>%"></span></div>
  <p class="muted" style="margin:10px 0 0;font-size:13px">%<?= $pct ?> tamamlandı</p>
</div>

<div class="card">
  <h2>Kilo grafiği</h2>
  <?php if (count($weights) < 2): ?>
    <p class="muted">Grafik için en az 2 kilo kaydı gerekli. <a class="accent" href="weight.php">Kilo ekle →</a></p>
  <?php else: ?>
    <canvas id="bwChart" height="150"></canvas>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Son antrenmanlar</h2>
  <?php if (!$recent): ?>
    <p class="muted">Henüz kayıt yok. <a class="accent" href="workout.php">Antrenman başlat →</a></p>
  <?php else: ?>
    <table>
      <tr><th>Tarih</th><th>Seans</th><th class="n">Set</th><th></th></tr>
      <?php foreach ($recent as $r): ?>
      <tr>
        <td><?= h(date('d.m.Y', strtotime($r['workout_date']))) ?></td>
        <td><span class="pill <?= h($r['session_label']) ?>"><?= h($r['session_label']) ?></span></td>
        <td class="n"><?= (int)$r['sets'] ?></td>
        <td><a class="btn sm ghost" href="log.php?id=<?= (int)$r['id'] ?>">Aç</a></td>
      </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
</div>

<?php if (count($weights) >= 2): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const labels = <?= json_encode(array_map(fn($r)=>date('d.m', strtotime($r['log_date'])), $weights)) ?>;
const data   = <?= json_encode(array_map(fn($r)=>(float)$r['weight_kg'], $weights)) ?>;
new Chart(document.getElementById('bwChart'), {
  type:'line',
  data:{labels, datasets:[{
    data, borderColor:'#f0a830', backgroundColor:'rgba(240,168,48,.12)',
    fill:true, tension:.3, pointRadius:3, pointBackgroundColor:'#f0a830'
  },{
    label:'hedef', data:labels.map(()=><?= $target ?>), borderColor:'#5bbf6a',
    borderDash:[5,5], pointRadius:0, fill:false
  }]},
  options:{plugins:{legend:{display:false}},
    scales:{
      y:{grid:{color:'#2c313b'},ticks:{color:'#8a9099'}},
      x:{grid:{display:false},ticks:{color:'#8a9099'}}
    }}
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/footer.php'; ?>
