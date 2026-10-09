<?php
require_once 'auth.php';

const MAX_DENEME  = 5;   // izin verilen hatalı deneme
const KILIT_DAKIKA = 15; // kilit süresi (dakika)

function cok_deneme($db, $ip) {
    $s = $db->prepare(
        "SELECT COUNT(*) FROM giris_denemeleri
         WHERE ip = ? AND tarih > (NOW() - INTERVAL " . KILIT_DAKIKA . " MINUTE)"
    );
    $s->execute([$ip]);
    return $s->fetchColumn() >= MAX_DENEME;
}

function deneme_kaydet($db, $ip, $kullanici) {
    // 1 günden eski kayıtları temizle
    $db->exec("DELETE FROM giris_denemeleri WHERE tarih < (NOW() - INTERVAL 1 DAY)");
    $db->prepare("INSERT INTO giris_denemeleri (ip, kullanici) VALUES (?, ?)")
       ->execute([$ip, mb_substr($kullanici, 0, 50)]);
}

function denemeleri_temizle($db, $ip) {
    $db->prepare("DELETE FROM giris_denemeleri WHERE ip = ?")->execute([$ip]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

if (!csrf_check()) {
    header("Location: index.php?error=3");
    exit;
}

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

if (cok_deneme($db, $ip)) {
    header("Location: index.php?error=2");
    exit;
}

$login_type = $_POST['login_type'] ?? '';
$password   = $_POST['password'] ?? '';

if ($login_type === 'student') {
    $tablo    = 'ogrenciler';
    $kolon    = 'ogr_no';
    $kimlik   = trim($_POST['ogr_no'] ?? '');
    $yonlen   = 'student_dashboard.php';
    $tip      = 'student';
} elseif ($login_type === 'teacher') {
    $tablo    = 'ogretmenler';
    $kolon    = 'kullanici_adi';
    $kimlik   = trim($_POST['username'] ?? '');
    $yonlen   = 'teacher_dashboard.php';
    $tip      = 'teacher';
} else {
    header("Location: index.php");
    exit;
}

$ekstra = ($tip === 'teacher') ? ', rol' : '';
$stmt = $db->prepare("SELECT id, ad, soyad, sifre$ekstra FROM $tablo WHERE $kolon = ?");
$stmt->execute([$kimlik]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['sifre'])) {
    // Gerekirse hash algoritmasını güncelle
    if (password_needs_rehash($user['sifre'], PASSWORD_DEFAULT)) {
        $db->prepare("UPDATE $tablo SET sifre = ? WHERE id = ?")
           ->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    }
    denemeleri_temizle($db, $ip);
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_type'] = $tip;
    $_SESSION['user_name'] = $user['ad'] . ' ' . $user['soyad'];
    $_SESSION['rol']       = ($tip === 'teacher') ? $user['rol'] : 'student';
    header("Location: $yonlen");
    exit;
}

deneme_kaydet($db, $ip, $kimlik);
header("Location: index.php?error=1");
exit;