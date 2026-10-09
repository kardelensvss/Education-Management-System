<?php
require_once 'auth.php';
if (isset($_SESSION['user_id'])) {
    header($_SESSION['user_type'] === 'student' ? "Location: student_dashboard.php" : "Location: teacher_dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Okul - Giriş</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="login-page">
    <div class="login-box">
        <div class="login-title">E-Okul Sistemi</div>
        <div class="login-sub">Öğrenci Not ve Karne Takip Sistemi</div>

                <?php if(isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php
                $e = $_GET['error'];
                if ($e === '2')      echo 'Çok fazla hatalı deneme! 15 dakika sonra tekrar dene.';
                elseif ($e === '3')  echo 'Oturum süresi doldu, sayfayı yenileyip tekrar dene.';
                else                 echo 'Hatalı kullanıcı adı veya şifre!';
                ?>
            </div>
        <?php endif; ?>

        <div class="login-tabs">
            <button class="tab-btn student active" onclick="switchTab('student', this)">Öğrenci</button>
            <button class="tab-btn teacher" onclick="switchTab('teacher', this)">Öğretmen</button>
        </div>

        <form id="student-form" class="login-form active" action="login_process.php" method="POST">
            <input type="hidden" name="login_type" value="student">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label class="form-label">Öğrenci Numarası</label>
                <input type="text" name="ogr_no" class="form-control" required placeholder="Örn: 2401001">
            </div>
            <div class="form-group">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control" required placeholder="Şifreniz">
            </div>
            <button type="submit" class="btn-login-student">Giriş Yap</button>
        </form>

        <form id="teacher-form" class="login-form" action="login_process.php" method="POST">
            <input type="hidden" name="login_type" value="teacher">
                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <div class="form-group">
                <label class="form-label">Kullanıcı Adı</label>
                <input type="text" name="username" class="form-control" required placeholder="Örn: admin">
            </div>
            <div class="form-group">
                <label class="form-label">Şifre</label>
                <input type="password" name="password" class="form-control" required placeholder="Şifreniz">
            </div>
            <button type="submit" class="btn-login-teacher">Giriş Yap</button>
        </form>
    </div>
</div>

<script>
function switchTab(type, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.login-form').forEach(f => f.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(type + '-form').classList.add('active');
}
</script>
</body>
</html>
