<?php
require __DIR__ . '/config.php';
require_login();
$me = current_user($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $name = trim($_POST['name'] ?? '');
        if ($name !== '') {
            $st = $pdo->prepare('INSERT INTO exercises (name,category,session,is_optional,notes,sort_order)
                VALUES (?,?,?,?,?,?)');
            $st->execute([
                $name,
                in_array($_POST['category']??'',['lower','upper','core','cardio','other'],true)?$_POST['category']:'other',
                in_array($_POST['session']??'',['A','B','both','none'],true)?$_POST['session']:'none',
                isset($_POST['is_optional'])?1:0,
                trim($_POST['notes']??'') ?: null,
                (int)($_POST['sort_order']??100),
            ]);
            flash('Hareket eklendi.');
        }
    } elseif ($act === 'toggle') {
        $st = $pdo->prepare('UPDATE exercises SET active = 1-active WHERE id=?');
        $st->execute([(int)$_POST['id']]);
    } elseif ($act === 'del') {
        // setlerde kullanılıyorsa silme yerine pasifleştir
        $c = $pdo->prepare('SELECT COUNT(*) c FROM workout_sets WHERE exercise_id=?');
        $c->execute([(int)$_POST['id']]);
        if ((int)$c->fetch()['c'] > 0) {
            $pdo->prepare('UPDATE exercises SET active=0 WHERE id=?')->execute([(int)$_POST['id']]);
            flash('Hareket kayıtlarda geçtiği için silinmedi, pasifleştirildi.');
        } else {
            $pdo->prepare('DELETE FROM exercises WHERE id=?')->execute([(int)$_POST['id']]);
            flash('Hareket silindi.');
        }
    }
    redirect('exercises.php');
}

$rows = $pdo->query('SELECT * FROM exercises ORDER BY session, sort_order, name')->fetchAll();
$catLbl=['lower'=>'Alt','upper'=>'Üst','core'=>'Core','cardio'=>'Kardiyo','other'=>'Diğer'];

$title='Hareketler'; $nav='ex';
require __DIR__ . '/header.php';
?>
<div class="card">
  <h2>Yeni hareket ekle</h2>
  <form method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="add">
    <label>İsim</label>
    <input name="name" placeholder="örn. Hip Thrust (makine)" required>
    <div class="row" style="margin-top:10px">
      <div><label>Kategori</label>
        <select name="category">
          <option value="lower">Alt</option><option value="upper">Üst</option>
          <option value="core">Core</option><option value="cardio">Kardiyo</option>
          <option value="other">Diğer</option>
        </select></div>
      <div><label>Seans</label>
        <select name="session">
          <option value="A">A</option><option value="B">B</option>
          <option value="both">Ortak</option><option value="none" selected>Yok</option>
        </select></div>
      <div><label>Sıra</label><input type="number" name="sort_order" value="100"></div>
    </div>
    <div style="margin-top:10px"><label>Not (ops.)</label><input name="notes" placeholder="strap kullan / ACL dikkat"></div>
    <label style="display:flex;align-items:center;gap:8px;margin-top:12px;color:var(--ink)">
      <input type="checkbox" name="is_optional" style="width:auto"> Opsiyonel hareket
    </label>
    <button class="btn" style="margin-top:14px">Ekle</button>
  </form>
</div>

<div class="card">
  <h2>Kütüphane</h2>
  <table>
    <tr><th>İsim</th><th>Kat.</th><th>Seans</th><th>Durum</th><th></th></tr>
    <?php foreach ($rows as $e): ?>
    <tr style="<?= $e['active']?'':'opacity:.45' ?>">
      <td>
        <?= h($e['name']) ?><?= $e['is_optional']?' <span class="pill opt">ops</span>':'' ?>
        <?php if ($e['notes']): ?><br><span class="ghosttxt" style="font-family:var(--sans)"><?= h($e['notes']) ?></span><?php endif; ?>
      </td>
      <td class="muted"><?= h($catLbl[$e['category']]??$e['category']) ?></td>
      <td><?php if(in_array($e['session'],['A','B'],true)): ?><span class="pill <?= h($e['session']) ?>"><?= h($e['session']) ?></span><?php else: ?><span class="muted"><?= $e['session']==='both'?'ortak':'–' ?></span><?php endif; ?></td>
      <td>
        <form method="post" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
          <button class="btn sm ghost"><?= $e['active']?'Aktif':'Pasif' ?></button>
        </form>
      </td>
      <td>
        <form method="post" onsubmit="return confirm('Silinsin mi?')" style="display:inline">
          <?= csrf_field() ?><input type="hidden" name="action" value="del"><input type="hidden" name="id" value="<?= (int)$e['id'] ?>">
          <button class="btn sm danger">Sil</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php require __DIR__ . '/footer.php'; ?>
