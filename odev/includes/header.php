<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title ?? 'E-Okul') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="role-<?= $_SESSION['user_type'] === 'teacher' ? 'teacher' : 'student' ?>">

<div class="layout">

    <!-- SIDEBAR -->
    <aside class="sidebar">
        <div class="sidebar-logo">
            <i class="fa-solid fa-graduation-cap"></i>
            E-Okul
        </div>

        <div class="sidebar-user">
            <div class="sidebar-user-name"><?= htmlspecialchars($_SESSION['user_name']) ?></div>
                     <div class="sidebar-user-role"><?= $_SESSION['user_type'] === 'teacher' ? (is_admin() ? 'Admin' : 'Öğretmen') : 'Öğrenci' ?></div>
        </div>

        <nav class="sidebar-nav">
            <?php if($_SESSION['user_type'] === 'teacher'): ?>
                <!-- Bu bağlantılar teacher_dashboard.php içindeki sekmelere geçiş yapar -->
                <a href="teacher_dashboard.php#ogrenciler" class="sidebar-link" data-tab="ogrenciler">
                    <i class="fa-solid fa-users"></i> Öğrenciler
                </a>
                <a href="teacher_dashboard.php#dersler" class="sidebar-link" data-tab="dersler">
                    <i class="fa-solid fa-book"></i> Dersler
                </a>
                <a href="teacher_dashboard.php#notlar" class="sidebar-link" data-tab="notlar">
                    <i class="fa-solid fa-star-half-stroke"></i> Notlar
                </a>
                <a href="teacher_dashboard.php#devamsizlik" class="sidebar-link" data-tab="devamsizlik">
                    <i class="fa-solid fa-calendar-xmark"></i> Devamsızlık
                </a>
                                <?php if (is_admin()): ?>
                <a href="teacher_dashboard.php#ogretmenler" class="sidebar-link" data-tab="ogretmenler">
                    <i class="fa-solid fa-chalkboard-user"></i> Öğretmenler
                </a>
                <a href="not_log.php" class="sidebar-link<?= basename($_SERVER['PHP_SELF']) === 'not_log.php' ? ' active' : '' ?>">
                    <i class="fa-solid fa-clock-rotate-left"></i> Not Kayıtları
                </a>
                <?php endif; ?>
            <?php else: ?>
                <a href="student_dashboard.php#karne" class="sidebar-link">
                    <i class="fa-solid fa-user"></i> Bilgilerim
                </a>
                <a href="student_dashboard.php#notlar" class="sidebar-link">
                    <i class="fa-solid fa-star-half-stroke"></i> Notlarım
                </a>
                <a href="student_dashboard.php#devamsizlik" class="sidebar-link">
                    <i class="fa-solid fa-calendar-xmark"></i> Devamsızlığım
                </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="logout.php" class="sidebar-logout">
                <i class="fa-solid fa-right-from-bracket"></i> Çıkış Yap
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <div class="page-header">
            <h2><?= htmlspecialchars($page_title ?? 'E-Okul') ?></h2>
        </div>
        <div class="content-area animate-fade-in">