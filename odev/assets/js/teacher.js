// teacher_dashboard.php tarafından kullanılır.
// Şu değişkenler sayfada PHP tarafından tanımlanır: ogrenciler, dersler, mevcutNotlar, csrfToken

// ------------------------------------------------------------
// CSRF: sayfadaki bütün POST formlarına gizli token ekle
// ------------------------------------------------------------
document.querySelectorAll('form[method="POST"]').forEach(f => {
    const i = document.createElement('input');
    i.type = 'hidden';
    i.name = 'csrf_token';
    i.value = csrfToken;
    f.appendChild(i);
});

// ------------------------------------------------------------
// SEKMELER (sayfa yenilenmeden değişir, adres çubuğunda #notlar gibi görünür)
// ------------------------------------------------------------
const sekmeAdlari = ['ogrenciler', 'dersler', 'notlar', 'devamsizlik', 'ogretmenler'];

function sekmeAc(ad) {
    if (!sekmeAdlari.includes(ad) || !document.getElementById('tab-' + ad)) ad = 'ogrenciler';
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.toggle('active', p.id === 'tab-' + ad));
    document.querySelectorAll('.tab-link').forEach(b => b.classList.toggle('active', b.dataset.tab === ad));
    // Soldaki menüde de aktif sekmeyi vurgula
    document.querySelectorAll('.sidebar-link[data-tab]').forEach(l => l.classList.toggle('active', l.dataset.tab === ad));
    history.replaceState(null, '', '#' + ad);
}
document.querySelectorAll('.tab-link').forEach(b => b.addEventListener('click', () => sekmeAc(b.dataset.tab)));

// Soldaki menüden tıklanınca sayfa yenilenmeden sekme değişsin
document.querySelectorAll('.sidebar-link[data-tab]').forEach(l => {
    l.addEventListener('click', e => {
        e.preventDefault();
        sekmeAc(l.dataset.tab);
    });
});

sekmeAc(location.hash.replace('#', ''));

// ------------------------------------------------------------
// LİSTE FİLTRELEME (arama + bölüm)
// p = ogr | ders | not | dev
// ------------------------------------------------------------
function filtreKur(p) {
    const ara = document.getElementById(p + 'Ara');
    const bolum = document.getElementById(p + 'Bolum');
    const satirlar = document.querySelectorAll('#' + p + 'Tablo tbody tr[data-ara]');
    const yok = document.getElementById(p + 'Yok');
    const sayac = document.getElementById(p + 'Sayac');

    function filtrele() {
        const metin = ara.value.trim().toLocaleLowerCase('tr');
        let gorunen = 0;
        satirlar.forEach(tr => {
            const goster = tr.dataset.ara.includes(metin) && (bolum.value === '' || tr.dataset.bolum === bolum.value);
            tr.style.display = goster ? '' : 'none';
            if (goster) gorunen++;
        });
        yok.style.display = gorunen === 0 ? '' : 'none';
        sayac.textContent = gorunen;
    }
    ara.addEventListener('input', filtrele);
    bolum.addEventListener('change', filtrele);
}
['ogr', 'ders', 'not', 'dev'].forEach(filtreKur);

// ------------------------------------------------------------
// MODAL İÇİ ÖĞRENCİ SEÇİMİ (arama + bölüm filtreli açılır liste)
// p = nm (not penceresi) | dm (devamsızlık penceresi)
// ------------------------------------------------------------
function secenekEkle(select, deger, metin) {
    const o = document.createElement('option');
    o.value = deger;
    o.textContent = metin;
    select.appendChild(o);
}

function ogrenciListele(p) {
    const metin = document.getElementById(p + 'Ara').value.trim().toLocaleLowerCase('tr');
    const bolum = document.getElementById(p + 'Bolum').value;
    const sec = document.getElementById(p + 'Ogr');
    const onceki = sec.value;

    sec.innerHTML = '';
    secenekEkle(sec, '', 'Öğrenci seç...');
    ogrenciler.forEach(o => {
        const anahtar = (o.ogr_no + ' ' + o.ad + ' ' + o.soyad).toLocaleLowerCase('tr');
        if (!anahtar.includes(metin)) return;
        if (bolum !== '' && String(o.bolum_id) !== bolum) return;
        secenekEkle(sec, o.id, o.ogr_no + ' - ' + o.ad + ' ' + o.soyad);
    });
    if ([...sec.options].some(x => x.value === onceki)) sec.value = onceki;

    if (p === 'nm') dersListele();
}

['nm', 'dm'].forEach(p => {
    document.getElementById(p + 'Ara').addEventListener('input', () => ogrenciListele(p));
    document.getElementById(p + 'Bolum').addEventListener('change', () => ogrenciListele(p));
});

// ------------------------------------------------------------
// NOT PENCERESİ
// ------------------------------------------------------------
const nmOgr = document.getElementById('nmOgr');
const nmDers = document.getElementById('nmDers');

// Seçilen öğrencinin bölümündeki dersleri göster
function dersListele() {
    const ogr = ogrenciler.find(o => String(o.id) === nmOgr.value);
    const onceki = nmDers.value;

    nmDers.innerHTML = '';
    if (!ogr) {
        secenekEkle(nmDers, '', 'Önce öğrenci seçin...');
    } else {
        secenekEkle(nmDers, '', 'Ders seç...');
        dersler.filter(d => d.bolum_id === ogr.bolum_id)
               .forEach(d => secenekEkle(nmDers, d.id, d.ders_adi + ' (' + d.sinif + '. Sınıf)'));
        if ([...nmDers.options].some(x => x.value === onceki)) nmDers.value = onceki;
    }
    notlariDoldur();
}

// Daha önce girilmiş not varsa kutuları doldur
function notlariDoldur() {
    const kayit = mevcutNotlar[nmOgr.value + '_' + nmDers.value];
    [['vize1', 'nmVize1'], ['vize2', 'nmVize2'], ['vize3', 'nmVize3'], ['final', 'nmFinal']].forEach(([alan, id]) => {
        document.getElementById(id).value = (kayit && kayit[alan] !== null) ? kayit[alan] : '';
    });
}

nmOgr.addEventListener('change', dersListele);
nmDers.addEventListener('change', notlariDoldur);

// Pencereyi aç (satırdaki kalem simgesinden gelirse öğrenci ve ders hazır seçili olur)
function notModalAc(ogrId, dersId) {
    document.getElementById('nmAra').value = '';
    document.getElementById('nmBolum').value = '';
    ogrenciListele('nm');
    if (ogrId) {
        nmOgr.value = ogrId;
        dersListele();
        if (dersId) { nmDers.value = dersId; notlariDoldur(); }
    }
    document.getElementById('notModal').showModal();
}

// ------------------------------------------------------------
// DEVAMSIZLIK PENCERESİ
// ------------------------------------------------------------
function devModalAc() {
    document.getElementById('dmAra').value = '';
    document.getElementById('dmBolum').value = '';
    ogrenciListele('dm');
    document.getElementById('devModal').showModal();
}
function ogrDuzenleAc(id) {
    const o = ogrenciler.find(x => x.id === id);
    if (!o) return;
    document.getElementById('odId').value = o.id;
    document.getElementById('odNo').value = o.ogr_no;
    document.getElementById('odAd').value = o.ad;
    document.getElementById('odSoyad').value = o.soyad;
    document.getElementById('odBolum').value = o.bolum_id;
    document.getElementById('odSinif').value = o.sinif;
    document.getElementById('ogrDuzenleModal').showModal();
}

function dersDuzenleAc(id) {
    const d = dersler.find(x => x.id === id);
    if (!d) return;
    document.getElementById('ddId').value = d.id;
    document.getElementById('ddAd').value = d.ders_adi;
    document.getElementById('ddSinif').value = d.sinif;
    document.getElementById('dersDuzenleModal').showModal();
}

function sifreSor(f) {
    const s = prompt('Öğrenci için yeni şifre (en az 6 karakter):');
    if (s === null) return false;
    f.yeni_sifre.value = s;
    return true;
}
