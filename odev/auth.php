<?php
require_once 'config.php';

function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function check_auth($required_type = null) {
    global $db;

    if (!is_logged_in()) {
        header("Location: " . SITE_URL . "/index.php");
        exit;
    }

    // Kullanıcı hâlâ var mı, rolü değişti mi? (silinen/yetkisi düşen kişi içeride kalmasın)
    $uid = (int)$_SESSION['user_id'];
    if ($_SESSION['user_type'] === 'teacher') {
        $s = $db->prepare("SELECT rol FROM ogretmenler WHERE id = ?");
        $s->execute([$uid]);
        $rol = $s->fetchColumn();
        if ($rol !== false) $_SESSION['rol'] = $rol;
    } else {
        $s = $db->prepare("SELECT COUNT(*) FROM ogrenciler WHERE id = ?");
        $s->execute([$uid]);
        $rol = $s->fetchColumn() > 0 ? 'student' : false;
    }
    if ($rol === false) {
        header("Location: " . SITE_URL . "/logout.php");
        exit;
    }

    if ($required_type && $_SESSION['user_type'] !== $required_type) {
        // Eğer yetkisiz bir sayfaya girmeye çalışıyorsa kendi paneline yönlendir
        if ($_SESSION['user_type'] === 'student') {
            header("Location: " . SITE_URL . "/student_dashboard.php");
        } else {
            header("Location: " . SITE_URL . "/teacher_dashboard.php");
        }
        exit;
    }
}
function is_admin() {
    return ($_SESSION['rol'] ?? '') === 'admin';
}

// Admin her derse, öğretmen sadece kendisine atanan derslere yetkilidir
function ders_yetkisi($db, $ders_id) {
    if (is_admin()) return true;
    $s = $db->prepare("SELECT COUNT(*) FROM dersler WHERE id = ? AND ogretmen_id = ?");
    $s->execute([(int)$ders_id, (int)($_SESSION['user_id'] ?? 0)]);
    return $s->fetchColumn() > 0;
}

// Not değişikliğini not_log tablosuna yazar (değişiklik yoksa yazmaz)
function not_logla($db, $ogrenci_id, $ders_id, $eski, $yeni, $islem) {
    $bicim = function ($n) {
        if (!$n) return '-';
        $p = [];
        foreach (['vize1', 'vize2', 'vize3', 'final'] as $k) {
            $v = $n[$k] ?? null;
            $p[] = $k . '=' . ($v === null ? '-' : rtrim(rtrim(number_format((float)$v, 2, '.', ''), '0'), '.'));
        }
        return implode(', ', $p);
    };
    $eski_s = $bicim($eski);
    $yeni_s = $bicim($yeni);
    if ($eski && $eski_s === $yeni_s) return;

    $o = $db->prepare("SELECT ogr_no, ad, soyad FROM ogrenciler WHERE id = ?");
    $o->execute([(int)$ogrenci_id]);
    $ogr = $o->fetch();
    $d = $db->prepare("SELECT ders_adi FROM dersler WHERE id = ?");
    $d->execute([(int)$ders_id]);
    $ders = $d->fetchColumn();

    $db->prepare("INSERT INTO not_log (ogretmen_adi, ogrenci_bilgi, ders_adi, islem, eski_deger, yeni_deger) VALUES (?,?,?,?,?,?)")
       ->execute([
           $_SESSION['user_name'] ?? '?',
           $ogr ? $ogr['ogr_no'] . ' - ' . $ogr['ad'] . ' ' . $ogr['soyad'] : '?',
           $ders ?: '?',
           $islem, $eski_s, $yeni_s
       ]);
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check() {
    $gelen = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $gelen);
}

?>