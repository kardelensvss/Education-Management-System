<?php
// ------------------------------------------------------------
// config.example.php - ÖRNEK AYAR DOSYASI
// Bu dosyayı "config.php" adıyla kopyala ve aşağıdaki
// veritabanı bilgilerini kendi bilgisayarına göre düzenle.
// ------------------------------------------------------------

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$host     = 'localhost';
$dbname   = 'php_odev';
$username = 'root';
$password = '';   // kendi MySQL şifreni buraya yaz

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı bağlantı hatası: php_odev veritabanının var olduğundan ve database.sql dosyasının içe aktarıldığından emin ol.");
}

if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost:8080');
}