<?php
require_once 'auth.php';
check_auth();

$ogrenci_mi = ($_SESSION['user_type'] === 'student');
$tablo      = $ogrenci_mi ? 'ogrenciler' : 'ogretmenler';
$geri       = $ogrenci_mi ? 'student_dashboard.php' : 'teacher_dashboard.php';
$id         = (int)$_SESSION['user_id'];

$success = null;
$error   = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $eski   = $_POST['eski_sifre'] ?? '';
    $yeni   = $_POST['yeni_sifre'] ?? '';
    $tekrar = $_POST['yeni_sifre_tekrar'] ?? '';

    if (!csrf_check()) {
        $error = "Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.";
    } else {
        $stmt = $db->prepare("SELECT sifre FROM $tablo WHERE id = ?");
        $stmt->execute([$id]);
        $kayitli = $stmt->fetchColumn();

        if (!$kayitli || !password_verify($eski, $kayitli)) {
            $error = "Mevcut şifre yanlış.";
        } elseif (mb_strlen($yeni) < 6) {
            $error = "Yeni şifre en az 6 karakter olmalıdır.";
        } elseif ($yeni !== $tekrar) {
            $error = "Yeni şifreler birbiriyle uyuşmuyor.";
        } elseif ($yeni === $eski) {
            $error = "Yeni şifre eskisiyle aynı olamaz.";
        } else {
            $db->prepare("UPDATE $tablo SET sifre = ? WHERE id = ?")
               ->execute([password_hash($yeni, PASSWORD_DEFAULT), $id]);
            session_regenerate_id(true);
            $success = "Şifren başarıyla değiştirildi.";
        }
    }
}

$page_title = "Şifre Değiştir";
include 'includes/header.php';
?>

<div style="background:#fff; padding:15px; border-radius:5px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1); max-width:480px;">
    <h3 style="border-bottom:1px solid #ddd; padding-bottom:10px; margin-bottom:15px;">Şifre Değiştir</h3>

    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

        <div class="form-group">
            <label class="form-label">Mevcut Şifre</label>
            <input type="password" name="eski_sifre" class="form-control" required>
        </div>
        <div class="form-group">
            <label class="form-label">Yeni Şifre (en az 6 karakter)</label>
            <input type="password" name="yeni_sifre" class="form-control" required minlength="6">
        </div>
        <div class="form-group">
            <label class="form-label">Yeni Şifre (Tekrar)</label>
            <input type="password" name="yeni_sifre_tekrar" class="form-control" required minlength="6">
        </div>

        <div style="display:flex; gap:8px; margin-top:10px;">
            <button type="submit" class="btn btn-primary">Şifreyi Değiştir</button>
            <a href="<?= $geri ?>" class="btn" style="background:#888; color:#fff; text-decoration:none;">Geri Dön</a>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>