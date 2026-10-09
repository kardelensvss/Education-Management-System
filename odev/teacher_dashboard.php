<?php
require_once 'auth.php';
check_auth('teacher');
define('TEACHER_PANEL', true);

$admin_mi = is_admin();
$benim_id = (int)$_SESSION['user_id'];

$sekmeler = ['ogrenciler', 'dersler', 'notlar', 'devamsizlik', 'ogretmenler'];

// İşlemler (POST): teacher_actions.php yönlendirir ve exit eder
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    require __DIR__ . '/teacher_actions.php';
}


// ============================================================
// MESAJLAR
// ============================================================
$success = $_SESSION['flash_success'] ?? null;
$error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

$page_title = "Öğretmen Paneli";
include 'includes/header.php';

// ============================================================
// VERİLER
// ============================================================
$bolumler = $db->query("SELECT * FROM bolumler ORDER BY bolum_adi")->fetchAll();

$students = $db->query("
    SELECT o.*, b.bolum_adi
    FROM ogrenciler o
    JOIN bolumler b ON o.bolum_id = b.id
    ORDER BY b.bolum_adi, o.sinif, o.ad
")->fetchAll();

$courses = $db->query("
    SELECT d.*, b.bolum_adi
    FROM dersler d
    JOIN bolumler b ON d.bolum_id = b.id
    ORDER BY b.bolum_adi, d.sinif, d.ders_adi
")->fetchAll();

$grades = $db->query("
    SELECT n.*, o.ogr_no, o.ad, o.soyad, o.bolum_id, b.bolum_adi, d.ders_adi
    FROM notlar n
    JOIN ogrenciler o ON n.ogrenci_id = o.id
    JOIN bolumler b ON o.bolum_id = b.id
    JOIN dersler d ON n.ders_id = d.id
    ORDER BY b.bolum_adi, o.sinif, o.ad, d.ders_adi
")->fetchAll();

$attendance = $db->query("
    SELECT dv.*, o.ogr_no, o.ad, o.soyad, o.bolum_id, b.bolum_adi
    FROM devamsizlik dv
    JOIN ogrenciler o ON dv.ogrenci_id = o.id
    JOIN bolumler b ON o.bolum_id = b.id
    ORDER BY dv.tarih DESC, o.ad
")->fetchAll();

$ogretmenler = $db->query("SELECT id, kullanici_adi, ad, soyad, rol FROM ogretmenler ORDER BY rol, ad")->fetchAll();
$ogretmen_adlari = [];
foreach ($ogretmenler as $o) {
    $ogretmen_adlari[(int)$o['id']] = $o['ad'] . ' ' . $o['soyad'];
}

// Admin olmayan öğretmen sadece kendisine atanan dersleri ve notlarını görür
if (!$admin_mi) {
    $kendi = array_column(array_filter($courses, fn($c) => (int)$c['ogretmen_id'] === $benim_id), 'id');
    $courses = array_values(array_filter($courses, fn($c) => in_array($c['id'], $kendi)));
    $grades  = array_values(array_filter($grades,  fn($g) => in_array($g['ders_id'], $kendi)));
}

// Not modalı için: var olan notlar (kutular otomatik dolsun)
$mevcut = [];
foreach ($grades as $n) {
    $mevcut[$n['ogrenci_id'] . '_' . $n['ders_id']] = [
        'vize1' => $n['vize1'], 'vize2' => $n['vize2'], 'vize3' => $n['vize3'], 'final' => $n['final']
    ];
}

$json_flags = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE;

// Bölüm seçeneklerini bir kez hazırla (filtrelerde tekrar kullanılır)
$bolum_secenekleri = '';
foreach ($bolumler as $b) {
    $bolum_secenekleri .= '<option value="' . $b['id'] . '">' . htmlspecialchars($b['bolum_adi']) . '</option>';
}
?>

<link rel="stylesheet" href="assets/css/teacher.css">

<div style="margin-bottom: 1rem; text-align: right;">
    <a href="change_password.php" class="btn btn-primary" style="text-decoration:none;">Şifre Değiştir</a>
    <?php if ($admin_mi): ?>
        <a href="not_log.php" class="btn btn-primary" style="text-decoration:none;">Not Kayıtları</a>
    <?php else: ?>
        <style>
        /* Sadece görünümü sadeleştirir, asıl koruma sunucu tarafındadır */
        button[onclick*="ogrModal"], button[onclick*="dersModal"],
        button[onclick*="ogrDuzenleAc"], button[onclick*="dersDuzenleAc"],
        form:has(input[value="ogrenci_sil"]), form:has(input[value="sifre_sifirla"]),
        form:has(input[value="ders_sil"]) { display: none !important; }
        </style>
    <?php endif; ?>
</div>
<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<!-- ÖZET KARTLAR -->
<div class="grid">
    <div class="stat-card"><div class="stat-title">Öğrenci</div><div class="stat-value"><?= count($students) ?></div></div>
    <div class="stat-card"><div class="stat-title">Ders</div><div class="stat-value"><?= count($courses) ?></div></div>
    <div class="stat-card"><div class="stat-title">Not Kaydı</div><div class="stat-value"><?= count($grades) ?></div></div>
    <div class="stat-card"><div class="stat-title">Devamsızlık Kaydı</div><div class="stat-value"><?= count($attendance) ?></div></div>
</div>

<!-- SEKMELER -->
<div class="tab-bar">
    <button class="tab-link" data-tab="ogrenciler"><i class="fa-solid fa-users"></i> Öğrenciler</button>
    <button class="tab-link" data-tab="dersler"><i class="fa-solid fa-book"></i> Dersler</button>
    <button class="tab-link" data-tab="notlar"><i class="fa-solid fa-star-half-stroke"></i> Notlar</button>
    <button class="tab-link" data-tab="devamsizlik"><i class="fa-solid fa-calendar-xmark"></i> Devamsızlık</button>
        <?php if ($admin_mi): ?>
    <button class="tab-link" data-tab="ogretmenler"><i class="fa-solid fa-chalkboard-user"></i> Öğretmenler</button>
    <?php endif; ?>
</div>

<!-- ===================== ÖĞRENCİLER ===================== -->
<div class="tab-panel card-box" id="tab-ogrenciler">
    <div class="toolbar">
        <input type="text" id="ogrAra" class="form-control" placeholder="Numara veya ad ile ara...">
        <select id="ogrBolum" class="form-control"><option value="">Tüm Bölümler</option><?= $bolum_secenekleri ?></select>
        <button class="btn btn-primary ekle-btn" onclick="document.getElementById('ogrModal').showModal()"><i class="fa-solid fa-user-plus"></i> Yeni Öğrenci</button>
    </div>
    <div class="sonuc-bilgi"><span id="ogrSayac"><?= count($students) ?></span> öğrenci gösteriliyor</div>
    <div class="tablo-kap">
    <table class="table" id="ogrTablo">
        <thead><tr><th>No</th><th>Ad Soyad</th><th>Bölüm</th><th>Sınıf</th><th class="merkez">İşlem</th></tr></thead>
        <tbody>
            <?php foreach($students as $s): ?>
            <tr data-bolum="<?= $s['bolum_id'] ?>" data-ara="<?= htmlspecialchars(mb_strtolower($s['ogr_no'].' '.$s['ad'].' '.$s['soyad'], 'UTF-8')) ?>">
                <td><?= htmlspecialchars($s['ogr_no']) ?></td>
                <td><?= htmlspecialchars($s['ad'].' '.$s['soyad']) ?></td>
                <td><?= htmlspecialchars($s['bolum_adi']) ?></td>
                <td><?= (int)$s['sinif'] ?>. Sınıf</td>
                <td class="merkez" style="white-space:nowrap;">
                    <form method="POST" style="margin:0; display:inline;" onsubmit="return sifreSor(this);">
                        <input type="hidden" name="islem" value="sifre_sifirla">
                        <input type="hidden" name="sekme" value="ogrenciler">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <input type="hidden" name="yeni_sifre" value="">
                        <button type="submit" class="btn-ikon duzenle" title="Şifre sıfırla"><i class="fa-solid fa-key"></i></button>
                    </form>
                                       <button type="button" class="btn-ikon duzenle" title="Düzenle" onclick="ogrDuzenleAc(<?= (int)$s['id'] ?>)"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Emin misiniz? Öğrencinin notları ve devamsızlıkları da silinecek.');">
                        <input type="hidden" name="islem" value="ogrenci_sil">
                        <input type="hidden" name="sekme" value="ogrenciler">
                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                        <button type="submit" class="btn-ikon sil" title="Sil"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr id="ogrYok" style="display:none;"><td colspan="5" class="merkez" style="color:#94a3b8; padding:1.5rem;">Sonuç bulunamadı.</td></tr>
        </tbody>
    </table>
    </div>
</div>

<!-- ===================== DERSLER ===================== -->
<div class="tab-panel card-box" id="tab-dersler">
    <div class="toolbar">
        <input type="text" id="dersAra" class="form-control" placeholder="Ders adı ile ara...">
        <select id="dersBolum" class="form-control"><option value="">Tüm Bölümler</option><?= $bolum_secenekleri ?></select>
        <button class="btn btn-primary ekle-btn" onclick="document.getElementById('dersModal').showModal()"><i class="fa-solid fa-plus"></i> Yeni Ders</button>
    </div>
    <div class="sonuc-bilgi"><span id="dersSayac"><?= count($courses) ?></span> ders gösteriliyor</div>
    <div class="tablo-kap">
    <table class="table" id="dersTablo">
        <thead><tr><th>Ders Adı</th><th>Bölüm</th><th>Sınıf</th><th>Öğretmen</th><th class="merkez">İşlem</th></tr></thead>
        <tbody>
            <?php foreach($courses as $c): ?>
            <tr data-bolum="<?= $c['bolum_id'] ?>" data-ara="<?= htmlspecialchars(mb_strtolower($c['ders_adi'], 'UTF-8')) ?>">
                <td><?= htmlspecialchars($c['ders_adi']) ?></td>
                <td><?= htmlspecialchars($c['bolum_adi']) ?></td>
                               <td><?= (int)$c['sinif'] ?>. Sınıf</td>
                <td>
                    <?php if ($admin_mi): ?>
                    <form method="POST" style="margin:0;">
                        <input type="hidden" name="islem" value="ders_ata">
                        <input type="hidden" name="sekme" value="dersler">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <select name="ogretmen_id" class="form-control" style="max-width:200px; margin:0;" onchange="this.form.submit()">
                            <option value="0">— Atanmamış —</option>
                            <?php foreach ($ogretmenler as $o): ?>
                            <option value="<?= (int)$o['id'] ?>" <?= (int)$c['ogretmen_id'] === (int)$o['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($o['ad'] . ' ' . $o['soyad']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <?php else: ?>
                        <?= htmlspecialchars($ogretmen_adlari[(int)$c['ogretmen_id']] ?? '-') ?>
                    <?php endif; ?>
                </td>
                <td class="merkez">
                    <button type="button" class="btn-ikon duzenle" title="Düzenle" onclick="dersDuzenleAc(<?= (int)$c['id'] ?>)"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Emin misiniz? Bu derse ait tüm notlar da silinecek.');">
                        <input type="hidden" name="islem" value="ders_sil">
                        <input type="hidden" name="sekme" value="dersler">
                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn-ikon sil" title="Sil"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr id="dersYok" style="display:none;"><td colspan="5" class="merkez" style="color:#94a3b8; padding:1.5rem;">Sonuç bulunamadı.</td></tr>
        </tbody>
    </table>
    </div>
</div>

<!-- ===================== NOTLAR ===================== -->
<div class="tab-panel card-box" id="tab-notlar">
    <div class="toolbar">
        <input type="text" id="notAra" class="form-control" placeholder="Öğrenci no, ad veya ders ile ara...">
        <select id="notBolum" class="form-control"><option value="">Tüm Bölümler</option><?= $bolum_secenekleri ?></select>
        <button class="btn btn-primary ekle-btn" onclick="notModalAc()"><i class="fa-solid fa-pen-to-square"></i> Not Gir</button>
    </div>
    <div class="sonuc-bilgi"><span id="notSayac"><?= count($grades) ?></span> not kaydı gösteriliyor</div>
    <div class="tablo-kap">
    <table class="table" id="notTablo">
        <thead>
            <tr>
                <th>No</th><th>Ad Soyad</th><th>Ders</th>
                <th class="merkez">Vize 1</th><th class="merkez">Vize 2</th><th class="merkez">Vize 3</th>
                <th class="merkez">Final</th><th class="merkez">Ortalama</th><th class="merkez">Durum</th>
                <th class="merkez">İşlem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($grades as $g): ?>
            <tr data-bolum="<?= $g['bolum_id'] ?>" data-ara="<?= htmlspecialchars(mb_strtolower($g['ogr_no'].' '.$g['ad'].' '.$g['soyad'].' '.$g['ders_adi'], 'UTF-8')) ?>">
                <td><?= htmlspecialchars($g['ogr_no']) ?></td>
                <td><?= htmlspecialchars($g['ad'].' '.$g['soyad']) ?></td>
                <td><?= htmlspecialchars($g['ders_adi']) ?></td>
                <td class="merkez"><?= $g['vize1'] ?? '-' ?></td>
                <td class="merkez"><?= $g['vize2'] ?? '-' ?></td>
                <td class="merkez"><?= $g['vize3'] ?? '-' ?></td>
                <td class="merkez"><?= $g['final'] ?? '-' ?></td>
                <td class="merkez"><strong><?= $g['ortalama'] !== null ? number_format($g['ortalama'], 2) : '-' ?></strong></td>
                <td class="merkez">
                    <?php if($g['ortalama'] !== null): ?>
                        <span class="badge <?= $g['ortalama'] >= 50 ? 'badge-success' : 'badge-danger' ?>"><?= $g['ortalama'] >= 50 ? 'Geçti' : 'Kaldı' ?></span>
                    <?php else: ?>-<?php endif; ?>
                </td>
                <td class="merkez" style="white-space:nowrap;">
                    <button type="button" class="btn-ikon duzenle" title="Düzenle" onclick="notModalAc(<?= (int)$g['ogrenci_id'] ?>, <?= (int)$g['ders_id'] ?>)"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Bu not kaydı silinsin mi?');">
                        <input type="hidden" name="islem" value="not_sil">
                        <input type="hidden" name="sekme" value="notlar">
                        <input type="hidden" name="id" value="<?= $g['id'] ?>">
                        <button type="submit" class="btn-ikon sil" title="Sil"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr id="notYok" style="display:none;"><td colspan="10" class="merkez" style="color:#94a3b8; padding:1.5rem;">Sonuç bulunamadı.</td></tr>
        </tbody>
    </table>
    </div>
</div>

<?php if ($admin_mi): ?>
<!-- ===================== ÖĞRETMENLER ===================== -->
<div class="tab-panel card-box" id="tab-ogretmenler">
    <div class="toolbar">
        <strong>Öğretmen Hesapları</strong>
        <button class="btn btn-primary ekle-btn" onclick="document.getElementById('ogretmenModal').showModal()"><i class="fa-solid fa-user-plus"></i> Yeni Öğretmen</button>
    </div>
    <div class="tablo-kap">
    <table class="table">
        <thead><tr><th>Kullanıcı Adı</th><th>Ad Soyad</th><th>Rol</th><th>Atanan Dersler</th><th class="merkez">İşlem</th></tr></thead>
        <tbody>
            <?php foreach ($ogretmenler as $o):
                               $atanan = array_values(array_filter($courses, fn($c) => (int)$c['ogretmen_id'] === (int)$o['id']));
            ?>
            <tr>
                <td><?= htmlspecialchars($o['kullanici_adi']) ?></td>
                <td><?= htmlspecialchars($o['ad'] . ' ' . $o['soyad']) ?></td>
                <td><span class="badge <?= $o['rol'] === 'admin' ? 'badge-danger' : 'badge-success' ?>"><?= $o['rol'] === 'admin' ? 'Admin' : 'Öğretmen' ?></span></td>
                                <td>
                    <?php if ($o['rol'] === 'admin'): ?>
                        Hepsi
                    <?php elseif (!$atanan): ?>
                        <span style="color:#94a3b8;">Atanmamış</span>
                    <?php else: foreach ($atanan as $a): ?>
                        <div><?= htmlspecialchars($a['ders_adi']) ?>
                            <span style="color:#888; font-size:11px;">(<?= htmlspecialchars($a['bolum_adi']) ?>, <?= (int)$a['sinif'] ?>. sınıf)</span>
                        </div>
                    <?php endforeach; endif; ?>
                </td>
                <td class="merkez" style="white-space:nowrap;">
                    <form method="POST" style="margin:0; display:inline;" onsubmit="return sifreSor(this);">
                        <input type="hidden" name="islem" value="ogretmen_sifre">
                        <input type="hidden" name="sekme" value="ogretmenler">
                        <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                        <input type="hidden" name="yeni_sifre" value="">
                        <button type="submit" class="btn-ikon duzenle" title="Şifre sıfırla"><i class="fa-solid fa-key"></i></button>
                    </form>
                    <?php if ((int)$o['id'] !== $benim_id): ?>
                    <form method="POST" style="margin:0; display:inline;" onsubmit="return confirm('Bu öğretmen silinsin mi? Dersleri atanmamış duruma geçecek.');">
                        <input type="hidden" name="islem" value="ogretmen_sil">
                        <input type="hidden" name="sekme" value="ogretmenler">
                        <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                        <button type="submit" class="btn-ikon sil" title="Sil"><i class="fa-solid fa-trash"></i></button>
                    </form>
                    <?php else: ?>
                        <span style="font-size:12px; color:#888;">(sen)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>

<!-- Yeni öğretmen -->
<dialog id="ogretmenModal">
    <h3><i class="fa-solid fa-user-plus"></i> Yeni Öğretmen Ekle</h3>
    <form method="POST">
        <input type="hidden" name="islem" value="ogretmen_ekle">
        <input type="hidden" name="sekme" value="ogretmenler">
        <input type="text" name="kullanici_adi" class="form-control" required placeholder="Kullanıcı adı">
        <div class="form-2li">
            <input type="text" name="ad" class="form-control" required placeholder="Ad">
            <input type="text" name="soyad" class="form-control" required placeholder="Soyad">
        </div>
        <input type="password" name="sifre" class="form-control" required minlength="6" placeholder="Şifre (en az 6 karakter)">
        <select name="rol" class="form-control" required>
            <option value="ogretmen">Öğretmen (sadece kendi dersleri)</option>
            <option value="admin">Admin (her şeyi yönetir)</option>
        </select>
        <div class="ipucu">Derslerini "Dersler" sekmesinden atayabilirsin.</div>
        <div class="modal-butonlar">
            <button type="button" class="btn btn-gri" onclick="this.closest('dialog').close()">Vazgeç</button>
            <button type="submit" class="btn btn-primary">Ekle</button>
        </div>
    </form>
</dialog>
<?php endif; ?>


<!-- ===================== DEVAMSIZLIK ===================== -->
<div class="tab-panel card-box" id="tab-devamsizlik">
    <div class="toolbar">
        <input type="text" id="devAra" class="form-control" placeholder="Numara veya ad ile ara...">
        <select id="devBolum" class="form-control"><option value="">Tüm Bölümler</option><?= $bolum_secenekleri ?></select>
        <button class="btn btn-primary ekle-btn" onclick="devModalAc()"><i class="fa-solid fa-calendar-plus"></i> Devamsızlık Gir</button>
    </div>
    <div class="sonuc-bilgi"><span id="devSayac"><?= count($attendance) ?></span> kayıt gösteriliyor</div>
    <div class="tablo-kap">
    <table class="table" id="devTablo">
        <thead><tr><th>Tarih</th><th>No</th><th>Ad Soyad</th><th>Bölüm</th><th>Durum</th><th class="merkez">Sil</th></tr></thead>
        <tbody>
            <?php foreach($attendance as $a): ?>
            <tr data-bolum="<?= $a['bolum_id'] ?>" data-ara="<?= htmlspecialchars(mb_strtolower($a['ogr_no'].' '.$a['ad'].' '.$a['soyad'], 'UTF-8')) ?>">
                <td><?= date('d.m.Y', strtotime($a['tarih'])) ?></td>
                <td><?= htmlspecialchars($a['ogr_no']) ?></td>
                <td><?= htmlspecialchars($a['ad'].' '.$a['soyad']) ?></td>
                <td><?= htmlspecialchars($a['bolum_adi']) ?></td>
                <td><?= $a['durum'] == 'Tam' ? 'Tam Gün' : 'Yarım Gün' ?></td>
                <td class="merkez">
                    <form method="POST" style="margin:0;" onsubmit="return confirm('Bu devamsızlık kaydı silinsin mi?');">
                        <input type="hidden" name="islem" value="devamsizlik_sil">
                        <input type="hidden" name="sekme" value="devamsizlik">
                        <input type="hidden" name="id" value="<?= $a['id'] ?>">
                        <button type="submit" class="btn-ikon sil" title="Sil"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr id="devYok" style="display:none;"><td colspan="6" class="merkez" style="color:#94a3b8; padding:1.5rem;">Sonuç bulunamadı.</td></tr>
        </tbody>
    </table>
    </div>
</div>


<!-- ============================================================ -->
<!-- PENCERELER (MODAL)                                           -->
<!-- ============================================================ -->
<!-- Öğrenci düzenle -->
<dialog id="ogrDuzenleModal">
    <h3><i class="fa-solid fa-pen"></i> Öğrenciyi Düzenle</h3>
    <form method="POST">
        <input type="hidden" name="islem" value="ogrenci_duzenle">
        <input type="hidden" name="sekme" value="ogrenciler">
        <input type="hidden" name="id" id="odId">
        <input type="text" name="ogr_no" id="odNo" class="form-control" required placeholder="Öğrenci No">
        <div class="form-2li">
            <input type="text" name="ad" id="odAd" class="form-control" required placeholder="Ad">
            <input type="text" name="soyad" id="odSoyad" class="form-control" required placeholder="Soyad">
        </div>
        <select name="bolum_id" id="odBolum" class="form-control" required>
            <?= $bolum_secenekleri ?>
        </select>
        <select name="sinif" id="odSinif" class="form-control" required>
            <?php for($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"><?= $i ?>. Sınıf</option><?php endfor; ?>
        </select>
        <div class="ipucu">Notu olan öğrencinin bölümü değiştirilemez, önce notlarını silmelisin. Şifre için anahtar simgesini kullan.</div>
        <div class="modal-butonlar">
            <button type="button" class="btn btn-gri" onclick="this.closest('dialog').close()">Vazgeç</button>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </div>
    </form>
</dialog>

<!-- Ders düzenle -->
<dialog id="dersDuzenleModal">
    <h3><i class="fa-solid fa-pen"></i> Dersi Düzenle</h3>
    <form method="POST">
        <input type="hidden" name="islem" value="ders_duzenle">
        <input type="hidden" name="sekme" value="dersler">
        <input type="hidden" name="id" id="ddId">
        <input type="text" name="ders_adi" id="ddAd" class="form-control" required placeholder="Ders Adı">
        <select name="sinif" id="ddSinif" class="form-control" required>
            <?php for($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"><?= $i ?>. Sınıf</option><?php endfor; ?>
        </select>
        <div class="ipucu">Dersin bölümü değiştirilemez (notlar tutarsız olur). Yanlış bölümse dersi silip doğru bölümde yeniden ekle.</div>
        <div class="modal-butonlar">
            <button type="button" class="btn btn-gri" onclick="this.closest('dialog').close()">Vazgeç</button>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </div>
    </form>
</dialog>

<!-- Yeni öğrenci -->
<dialog id="ogrModal">
    <h3><i class="fa-solid fa-user-plus"></i> Yeni Öğrenci Ekle</h3>
    <form method="POST">
        <input type="hidden" name="islem" value="ogrenci_ekle">
        <input type="hidden" name="sekme" value="ogrenciler">
        <input type="text" name="ogr_no" class="form-control" required placeholder="Öğrenci No">
        <div class="form-2li">
            <input type="text" name="ad" class="form-control" required placeholder="Ad">
            <input type="text" name="soyad" class="form-control" required placeholder="Soyad">
        </div>
        <select name="bolum_id" class="form-control" required>
            <option value="">Bölüm seç...</option>
            <?= $bolum_secenekleri ?>
        </select>
        <select name="sinif" class="form-control" required>
            <option value="">Sınıf seç...</option>
            <?php for($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"><?= $i ?>. Sınıf</option><?php endfor; ?>
        </select>
        <input type="password" name="sifre" class="form-control" required minlength="6" placeholder="Şifre (en az 6 karakter)">
        <div class="modal-butonlar">
            <button type="button" class="btn btn-gri" onclick="this.closest('dialog').close()">Vazgeç</button>
            <button type="submit" class="btn btn-primary">Ekle</button>
        </div>
    </form>
</dialog>

<!-- Yeni ders -->
<dialog id="dersModal">
    <h3><i class="fa-solid fa-plus"></i> Yeni Ders Ekle</h3>
    <form method="POST">
        <input type="hidden" name="islem" value="ders_ekle">
        <input type="hidden" name="sekme" value="dersler">
        <input type="text" name="ders_adi" class="form-control" required placeholder="Ders Adı">
        <select name="bolum_id" class="form-control" required>
            <option value="">Bölüm seç...</option>
            <?= $bolum_secenekleri ?>
        </select>
        <select name="sinif" class="form-control" required>
            <option value="">Sınıf seç...</option>
            <?php for($i = 1; $i <= 4; $i++): ?><option value="<?= $i ?>"><?= $i ?>. Sınıf</option><?php endfor; ?>
        </select>
        <div class="modal-butonlar">
            <button type="button" class="btn btn-gri" onclick="this.closest('dialog').close()">Vazgeç</button>
            <button type="submit" class="btn btn-primary">Ekle</button>
        </div>
    </form>
</dialog>

<!-- Not girişi -->
<dialog id="notModal">
    <h3><i class="fa-solid fa-pen-to-square"></i> Not Girişi</h3>
    <form method="POST">
        <input type="hidden" name="islem" value="not_gir">
        <input type="hidden" name="sekme" value="notlar">

        <div class="mini-baslik">Öğrenci</div>
        <div class="form-2li">
            <input type="text" id="nmAra" class="form-control" placeholder="Numara veya ad ara...">
            <select id="nmBolum" class="form-control"><option value="">Tüm Bölümler</option><?= $bolum_secenekleri ?></select>
        </div>
        <select name="ogrenci_id" id="nmOgr" class="form-control" required></select>

        <div class="mini-baslik">Ders</div>
        <select name="ders_id" id="nmDers" class="form-control" required></select>

        <div class="mini-baslik">Notlar (boş bırakılabilir)</div>
        <div class="form-2li">
            <input type="number" name="vize1" id="nmVize1" class="form-control" min="0" max="100" step="0.01" placeholder="Vize 1">
            <input type="number" name="vize2" id="nmVize2" class="form-control" min="0" max="100" step="0.01" placeholder="Vize 2">
            <input type="number" name="vize3" id="nmVize3" class="form-control" min="0" max="100" step="0.01" placeholder="Vize 3">
            <input type="number" name="final" id="nmFinal" class="form-control" min="0" max="100" step="0.01" placeholder="Final">
        </div>
        <div class="ipucu">Ortalama = Vize ortalaması × %40 + Final × %60 (vize ve final birlikte girilince hesaplanır).</div>

        <div class="modal-butonlar">
            <button type="button" class="btn btn-gri" onclick="this.closest('dialog').close()">Vazgeç</button>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </div>
    </form>
</dialog>

<!-- Devamsızlık girişi -->
<dialog id="devModal">
    <h3><i class="fa-solid fa-calendar-plus"></i> Devamsızlık Girişi</h3>
    <form method="POST">
        <input type="hidden" name="islem" value="devamsizlik_gir">
        <input type="hidden" name="sekme" value="devamsizlik">

        <div class="mini-baslik">Öğrenci</div>
        <div class="form-2li">
            <input type="text" id="dmAra" class="form-control" placeholder="Numara veya ad ara...">
            <select id="dmBolum" class="form-control"><option value="">Tüm Bölümler</option><?= $bolum_secenekleri ?></select>
        </div>
        <select name="ogrenci_id" id="dmOgr" class="form-control" required></select>

        <div class="mini-baslik">Tarih ve durum</div>
        <div class="form-2li">
            <input type="date" name="tarih" id="dmTarih" class="form-control" required value="<?= date('Y-m-d') ?>">
            <select name="durum" class="form-control" required>
                <option value="Tam">Tam Gün</option>
                <option value="Yarim">Yarım Gün</option>
            </select>
        </div>

        <div class="modal-butonlar">
            <button type="button" class="btn btn-gri" onclick="this.closest('dialog').close()">Vazgeç</button>
            <button type="submit" class="btn btn-primary">Kaydet</button>
        </div>
    </form>
</dialog>


<script>
// PHP'den gelen veriler (geri kalan kod assets/js/teacher.js içinde)
const ogrenciler = <?= json_encode(array_map(fn($s) => [
    'id' => (int)$s['id'], 'ogr_no' => $s['ogr_no'], 'ad' => $s['ad'], 'soyad' => $s['soyad'],
    'bolum_id' => (int)$s['bolum_id'], 'sinif' => (int)$s['sinif']
], $students), $json_flags) ?>;

const dersler = <?= json_encode(array_map(fn($c) => [
    'id' => (int)$c['id'], 'ders_adi' => $c['ders_adi'], 'bolum_id' => (int)$c['bolum_id'], 'sinif' => (int)$c['sinif']
], $courses), $json_flags) ?>;

const mevcutNotlar = <?= json_encode($mevcut, $json_flags) ?>;
const csrfToken = <?= json_encode(csrf_token()) ?>;
</script>
<script src="assets/js/teacher.js"></script>

<?php include 'includes/footer.php'; ?>
