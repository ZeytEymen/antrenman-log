<?php
// photo.php — yüklenen fotoğrafı sadece giriş yapmış sahibine servis eder.
// Kullanım: <img src="photo.php?id=123">
require __DIR__ . '/config.php';
require_login();
$me = current_user($pdo);

$id = (int)($_GET['id'] ?? 0);

// Fotoğraf bu kullanıcının bir antrenmanına mı ait? (sahiplik kontrolü)
$st = $pdo->prepare('SELECT p.file_name
    FROM workout_photos p
    JOIN workouts w ON w.id = p.workout_id
    WHERE p.id = ? AND w.user_id = ?');
$st->execute([$id, $me['id']]);
$row = $st->fetch();
if (!$row) { http_response_code(404); exit('Fotoğraf bulunamadı.'); }

$path = UPLOAD_DIR . '/' . basename($row['file_name']);
if (!is_file($path)) { http_response_code(404); exit('Dosya bulunamadı.'); }

$info = @getimagesize($path);
$mime = $info['mime'] ?? 'application/octet-stream';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=86400');
header('X-Content-Type-Options: nosniff');
readfile($path);
