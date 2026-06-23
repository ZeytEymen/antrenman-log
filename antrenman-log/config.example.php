<?php
// =====================================================================
//  config.example.php  —  ÖRNEK AYAR DOSYASI
//  Bu dosyayı "config.php" adıyla kopyala ve kendi DB bilgilerini yaz.
//  Gerçek config.php git'e gönderilmez (.gitignore'da).
// =====================================================================

// ---------- VERİTABANI AYARLARI (BURAYI DÜZENLE) ----------
const DB_HOST = 'localhost';
const DB_NAME = 'veritabani_adi';
const DB_USER = 'kullanici_adi';      // kendi MySQL kullanıcın
const DB_PASS = 'sifre';              // kendi MySQL şifren
// -----------------------------------------------------------

date_default_timezone_set('Europe/Istanbul');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

// Oturum (sadece sen kullanacağın için sade tutuldu)
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

// PDO bağlantısı
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    exit('Veritabanına bağlanılamadı. config.php içindeki DB ayarlarını kontrol et.');
}

// ---------- Yardımcılar ----------
function h(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $to): never {
    header('Location: ' . $to);
    exit;
}

function require_login(): void {
    if (empty($_SESSION['uid'])) {
        redirect('login.php');
    }
}

function current_user(PDO $pdo): array {
    static $u = null;
    if ($u === null) {
        $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $st->execute([$_SESSION['uid']]);
        $u = $st->fetch() ?: [];
    }
    return $u;
}

// CSRF
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function check_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
            http_response_code(419);
            exit('Oturum doğrulaması başarısız. Sayfayı yenile.');
        }
    }
}

// Basit flash mesaj
function flash(string $msg): void { $_SESSION['flash'] = $msg; }
function take_flash(): ?string {
    $m = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $m;
}

// ---------- Fotoğraf yükleme (form analiz) ----------
const UPLOAD_DIR     = __DIR__ . '/uploads';
const MAX_UPLOAD     = 12 * 1024 * 1024;  // 12 MB
// İzin verilen görsel türleri: tarayıcıda gösterilebilenler
const ALLOWED_IMAGES = [
    IMAGETYPE_JPEG => 'jpg',
    IMAGETYPE_PNG  => 'png',
    IMAGETYPE_WEBP => 'webp',
    IMAGETYPE_GIF  => 'gif',
];

// Yüklenen tek bir görseli doğrula + uploads/ içine kaydet.
// Başarılıysa kayıtlı dosya adını, hatada bir hata mesajını döndürür.
// Dönüş: ['ok'=>true,'file'=>'...'] | ['ok'=>false,'error'=>'...']
function save_upload(array $file): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Dosya seçilmedi.'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Yükleme hatası (kod ' . (int)$file['error'] . ').'];
    }
    if ($file['size'] > MAX_UPLOAD) {
        return ['ok' => false, 'error' => 'Dosya çok büyük (en fazla 12 MB).'];
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Geçersiz yükleme.'];
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || !isset(ALLOWED_IMAGES[$info[2]])) {
        return ['ok' => false, 'error' => 'Sadece JPG, PNG, WEBP veya GIF yüklenebilir.'];
    }
    $ext  = ALLOWED_IMAGES[$info[2]];
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!is_dir(UPLOAD_DIR)) { @mkdir(UPLOAD_DIR, 0775, true); }
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . '/' . $name)) {
        return ['ok' => false, 'error' => 'Dosya kaydedilemedi (klasör izinlerini kontrol et).'];
    }
    return ['ok' => true, 'file' => $name];
}

// uploads/ içindeki bir dosyayı güvenli şekilde sil (dizin dışına çıkışı engelle).
function delete_upload(string $fileName): void {
    $base = basename($fileName);                 // yol kaçışını engelle
    $path = UPLOAD_DIR . '/' . $base;
    if ($base !== '' && is_file($path)) { @unlink($path); }
}
