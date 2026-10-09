<?php
require_once 'auth.php';
check_auth('teacher');

if (!is_admin()) {
    header("Location: teacher_dashboard.php");
    exit;
}

$page_title = "Not Değişiklik Kayıtları";
include 'includes/header.php';

$kayitlar = $db->query("SELECT * FROM not_log ORDER BY id DESC LIMIT 200")->fetchAll();

$sayac = ['Eklendi' => 0, 'Güncellendi' => 0, 'Silindi' => 0];
foreach ($kayitlar as $k) {
    if (isset($sayac[$k['islem']])) $sayac[$k['islem']]++;
}
$rozet = ['Eklendi' => 'badge-success', 'Güncellendi' => 'badge-warning', 'Silindi' => 'badge-danger'];

// "vize1=62, final=-" biçimindeki metni diziye çevirir
function not_ayristir($metin) {
    $sonuc = [];
    if ($metin === '-' || $metin === '') return $sonuc;
    foreach (explode(', ', $metin) as $parca) {
        [$anahtar, $deger] = array_pad(explode('=', $parca, 2), 2, '-');
        $sonuc[$anahtar] = $deger;
    }
    return $sonuc;
}
$alan_adi = ['vize1' => 'Vize 1', 'vize2' => 'Vize 2', 'vize3' => 'Vize 3', 'final' => 'Final'];
?>

<style>
.nl-kart { background:#fff; padding:15px; border-radius:5px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1); }
.nl-arac { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:10px; }
.nl-arac .form-control { max-width:240px; }
.nl-geri { margin-left:auto; }
.nl-bilgi { font-size:12px; color:#888; margin-bottom:8px; }
.nl-kap { overflow:auto; max-height:520px; }
.nl-kap .table th { position:sticky; top:0; z-index:1; }
.nl-kap .table td { vertical-align:top; }
.nl-tarih { white-space:nowrap; color:#555; }
.nl-tarih small { color:#888; }
.nl-fark { line-height:1.8; white-space:nowrap; }
.nl-etiket { display:inline-block; width:50px; color:#888; font-size:11px; text-transform:uppercase; }
.nl-eski { color:#999; text-decoration:line-through; }
.nl-ok { color:#888; margin:0 4px; }
.nl-bos { text-align:center; color:#94a3b8; padding:1.5rem !important; }
</style>

<!-- ÖZET KARTLAR -->
<div class="grid">
    <div class="stat-card"><div class="stat-title">Toplam Kayıt</div><div class="stat-value"><?= count($kayitlar) ?></div></div>
    <div class="stat-card"><div class="stat-title">Eklendi</div><div class="stat-value"><?= $sayac['Eklendi'] ?></div></div>
    <div class="stat-card"><div class="stat-title">Güncellendi</div><div class="stat-value"><?= $sayac['Güncellendi'] ?></div></div>
    <div class="stat-card"><div class="stat-title">Silindi</div><div class="stat-value"><?= $sayac['Silindi'] ?></div></div>
</div>

<div class="nl-kart">
    <div class="nl-arac">
        <input type="text" id="nlAra" class="form-control" placeholder="Öğretmen, öğrenci veya ders ara...">
        <select id="nlIslem" class="form-control">
            <option value="">Tüm işlemler</option>
            <option value="Eklendi">Eklendi</option>
            <option value="Güncellendi">Güncellendi</option>
            <option value="Silindi">Silindi</option>
        </select>
        <a href="teacher_dashboard.php" class="btn btn-primary nl-geri">&larr; Panele Dön</a>
    </div>
    <div class="nl-bilgi"><span id="nlSayac"><?= count($kayitlar) ?></span> kayıt gösteriliyor (en fazla son 200)</div>

    <div class="nl-kap">
    <table class="table" id="nlTablo">
        <thead>
            <tr><th>Tarih</th><th>Öğretmen</th><th>Öğrenci</th><th>Ders</th><th>İşlem</th><th>Değişiklik</th></tr>
        </thead>
        <tbody>
            <?php foreach ($kayitlar as $k):
                $eski_d = not_ayristir($k['eski_deger']);
                $yeni_d = not_ayristir($k['yeni_deger']);
            ?>
            <tr data-islem="<?= htmlspecialchars($k['islem']) ?>"
                data-ara="<?= htmlspecialchars(mb_strtolower($k['ogretmen_adi'] . ' ' . $k['ogrenci_bilgi'] . ' ' . $k['ders_adi'], 'UTF-8')) ?>">
                <td class="nl-tarih"><?= date('d.m.Y', strtotime($k['tarih'])) ?><br><small><?= date('H:i', strtotime($k['tarih'])) ?></small></td>
                <td><?= htmlspecialchars($k['ogretmen_adi']) ?></td>
                <td><?= htmlspecialchars($k['ogrenci_bilgi']) ?></td>
                <td><?= htmlspecialchars($k['ders_adi']) ?></td>
                <td><span class="badge <?= $rozet[$k['islem']] ?? 'badge-warning' ?>"><?= htmlspecialchars($k['islem']) ?></span></td>
                <td>
                    <?php foreach ($alan_adi as $anahtar => $etiket):
                        $e = $eski_d[$anahtar] ?? '-';
                        $y = $yeni_d[$anahtar] ?? '-';
                        if ($e === $y) continue; // değişmeyen alanı gösterme
                    ?>
                        <div class="nl-fark">
                            <span class="nl-etiket"><?= $etiket ?></span>
                            <span class="nl-eski"><?= htmlspecialchars($e === '-' ? 'boş' : $e) ?></span>
                            <span class="nl-ok">&rarr;</span>
                            <strong><?= htmlspecialchars($y === '-' ? 'boş' : $y) ?></strong>
                        </div>
                    <?php endforeach; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <tr id="nlYok" style="<?= $kayitlar ? 'display:none;' : '' ?>"><td colspan="6" class="nl-bos">Kayıt bulunamadı.</td></tr>
        </tbody>
    </table>
    </div>
</div>

<script>
(function () {
    const ara = document.getElementById('nlAra');
    const islem = document.getElementById('nlIslem');
    const sayac = document.getElementById('nlSayac');
    const yok = document.getElementById('nlYok');
    const satirlar = document.querySelectorAll('#nlTablo tbody tr[data-ara]');

    function filtrele() {
        const metin = ara.value.trim().toLocaleLowerCase('tr');
        let gorunen = 0;
        satirlar.forEach(tr => {
            const goster = tr.dataset.ara.includes(metin) && (islem.value === '' || tr.dataset.islem === islem.value);
            tr.style.display = goster ? '' : 'none';
            if (goster) gorunen++;
        });
        yok.style.display = gorunen === 0 ? '' : 'none';
        sayac.textContent = gorunen;
    }
    ara.addEventListener('input', filtrele);
    islem.addEventListener('change', filtrele);
})();
</script>

<?php include 'includes/footer.php'; ?>