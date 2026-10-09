<?php defined('TEACHER_PANEL') or exit;

    $islem = $_POST['islem'] ?? '';
    $sekme = in_array($_POST['sekme'] ?? '', $sekmeler) ? $_POST['sekme'] : 'ogrenciler';

    // CSRF kontrolü: token yoksa veya yanlışsa hiçbir işlem yapılmaz
    if (!csrf_check()) {
        $_SESSION['flash_error'] = "Oturum doğrulaması başarısız. Sayfayı yenileyip tekrar deneyin.";
        header("Location: teacher_dashboard.php#" . $sekme);
        exit;
    }

        // Öğrenci/ders yönetimi sadece admin'e açık
$sadece_admin = ['ogrenci_ekle', 'ogrenci_sil', 'sifre_sifirla', 'ders_ekle', 'ders_sil',
                 'ders_ata', 'ogretmen_ekle', 'ogretmen_sil', 'ogretmen_sifre',
                 'ogrenci_duzenle', 'ders_duzenle'];
    if (in_array($islem, $sadece_admin) && !$admin_mi) {
        $_SESSION['flash_error'] = "Bu işlem için yetkiniz yok.";
        header("Location: teacher_dashboard.php#" . $sekme);
        exit;
    }


    // ---- Öğrenci ekle ----
    if ($islem == 'ogrenci_ekle') {
        $ogr_no   = trim($_POST['ogr_no'] ?? '');
        $ad       = trim($_POST['ad'] ?? '');
        $soyad    = trim($_POST['soyad'] ?? '');
        $bolum_id = (int)($_POST['bolum_id'] ?? 0);
        $sinif    = (int)($_POST['sinif'] ?? 0);
       $sifre    = $_POST['sifre'] ?? '';

if ($ogr_no === '' || $ad === '' || $soyad === '' || mb_strlen($sifre) < 6 || $bolum_id < 1 || $sinif < 1 || $sinif > 4) {
            $_SESSION['flash_error'] = "Lütfen tüm alanları doldurun.";
        } else {
            try {
                $db->prepare("INSERT INTO ogrenciler (ogr_no, ad, soyad, bolum_id, sinif, sifre) VALUES (?,?,?,?,?,?)")
                   ->execute([$ogr_no, $ad, $soyad, $bolum_id, $sinif, password_hash($sifre, PASSWORD_DEFAULT)]);
                $_SESSION['flash_success'] = "Öğrenci eklendi.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Bu öğrenci numarası zaten kayıtlı.";
            }
        }
    }

     // ---- Öğrenci sil ----
    if ($islem == 'ogrenci_sil') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $db->beginTransaction();
            $n = $db->prepare("SELECT ogrenci_id, ders_id, vize1, vize2, vize3, final FROM notlar WHERE ogrenci_id = ?");
            $n->execute([$id]);
            foreach ($n->fetchAll() as $k) {
                not_logla($db, (int)$k['ogrenci_id'], (int)$k['ders_id'], $k, [], 'Silindi');
            }
            $db->prepare("DELETE FROM ogrenciler WHERE id = ?")->execute([$id]);
            $db->commit();
            $_SESSION['flash_success'] = "Öğrenci silindi.";
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $_SESSION['flash_error'] = "Öğrenci silinemedi.";
        }
    }

    // ---- Öğrenci düzenle ----
    if ($islem == 'ogrenci_duzenle') {
        $id       = (int)($_POST['id'] ?? 0);
        $ogr_no   = trim($_POST['ogr_no'] ?? '');
        $ad       = trim($_POST['ad'] ?? '');
        $soyad    = trim($_POST['soyad'] ?? '');
        $bolum_id = (int)($_POST['bolum_id'] ?? 0);
        $sinif    = (int)($_POST['sinif'] ?? 0);

        $s = $db->prepare("SELECT bolum_id FROM ogrenciler WHERE id = ?");
        $s->execute([$id]);
        $eski_bolum = $s->fetchColumn();

        if ($id < 1 || $eski_bolum === false) {
            $_SESSION['flash_error'] = "Öğrenci bulunamadı.";
        } elseif ($ogr_no === '' || $ad === '' || $soyad === '' || $bolum_id < 1 || $sinif < 1 || $sinif > 4) {
            $_SESSION['flash_error'] = "Lütfen tüm alanları doldurun.";
        } else {
            // Bölüm değişiyorsa ve öğrencinin notu varsa izin verme (notlar yanlış bölümde kalırdı)
            $not_say = 0;
            if ((int)$eski_bolum !== $bolum_id) {
                $n = $db->prepare("SELECT COUNT(*) FROM notlar WHERE ogrenci_id = ?");
                $n->execute([$id]);
                $not_say = (int)$n->fetchColumn();
            }

            if ($not_say > 0) {
                $_SESSION['flash_error'] = "Bu öğrencinin notları var. Bölümünü değiştirmeden önce Notlar sekmesinden notlarını silin.";
            } else {
                try {
                    $db->prepare("UPDATE ogrenciler SET ogr_no = ?, ad = ?, soyad = ?, bolum_id = ?, sinif = ? WHERE id = ?")
                       ->execute([$ogr_no, $ad, $soyad, $bolum_id, $sinif, $id]);
                    $_SESSION['flash_success'] = "Öğrenci güncellendi.";
                } catch (PDOException $e) {
                    $_SESSION['flash_error'] = ($e->errorInfo[1] ?? 0) == 1062
                        ? "Bu öğrenci numarası başka bir öğrencide kayıtlı."
                        : "Bölüm geçersiz.";
                }
            }
        }
    }

        // ---- Öğrenci şifresini sıfırla ----
    if ($islem == 'sifre_sifirla') {
        $id   = (int)($_POST['id'] ?? 0);
        $yeni = $_POST['yeni_sifre'] ?? '';
        if ($id < 1 || mb_strlen($yeni) < 6) {
            $_SESSION['flash_error'] = "Yeni şifre en az 6 karakter olmalıdır.";
        } else {
            $db->prepare("UPDATE ogrenciler SET sifre = ? WHERE id = ?")
               ->execute([password_hash($yeni, PASSWORD_DEFAULT), $id]);
            $_SESSION['flash_success'] = "Öğrenci şifresi güncellendi.";
        }
    }

    // ---- Ders ekle ----
    if ($islem == 'ders_ekle') {
        $ders_adi = trim($_POST['ders_adi'] ?? '');
        $bolum_id = (int)($_POST['bolum_id'] ?? 0);
        $sinif    = (int)($_POST['sinif'] ?? 0);

        if ($ders_adi === '' || $bolum_id < 1 || $sinif < 1 || $sinif > 4) {
            $_SESSION['flash_error'] = "Lütfen tüm alanları doldurun.";
        } else {
            $db->prepare("INSERT INTO dersler (ders_adi, bolum_id, sinif) VALUES (?,?,?)")
               ->execute([$ders_adi, $bolum_id, $sinif]);
            $_SESSION['flash_success'] = "Ders eklendi.";
        }
    }

        // ---- Ders sil ----
    if ($islem == 'ders_sil') {
        $id = (int)($_POST['id'] ?? 0);
        try {
            $db->beginTransaction();
            $n = $db->prepare("SELECT ogrenci_id, ders_id, vize1, vize2, vize3, final FROM notlar WHERE ders_id = ?");
            $n->execute([$id]);
            foreach ($n->fetchAll() as $k) {
                not_logla($db, (int)$k['ogrenci_id'], (int)$k['ders_id'], $k, [], 'Silindi');
            }
            $db->prepare("DELETE FROM dersler WHERE id = ?")->execute([$id]);
            $db->commit();
            $_SESSION['flash_success'] = "Ders silindi.";
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $_SESSION['flash_error'] = "Ders silinemedi.";
        }
    }

    // ---- Ders düzenle ----
    if ($islem == 'ders_duzenle') {
        $id       = (int)($_POST['id'] ?? 0);
        $ders_adi = trim($_POST['ders_adi'] ?? '');
        $sinif    = (int)($_POST['sinif'] ?? 0);

        if ($id < 1 || $ders_adi === '' || $sinif < 1 || $sinif > 4) {
            $_SESSION['flash_error'] = "Lütfen tüm alanları doldurun.";
        } else {
            $db->prepare("UPDATE dersler SET ders_adi = ?, sinif = ? WHERE id = ?")
               ->execute([$ders_adi, $sinif, $id]);
            $_SESSION['flash_success'] = "Ders güncellendi.";
        }
    }
    
        // ---- Derse öğretmen ata ----
    if ($islem == 'ders_ata') {
        $ders_id  = (int)($_POST['id'] ?? 0);
        $ogr_id   = (int)($_POST['ogretmen_id'] ?? 0);
        $at_gecerli = true;

        if ($ogr_id > 0) {
            $k = $db->prepare("SELECT COUNT(*) FROM ogretmenler WHERE id = ?");
            $k->execute([$ogr_id]);
            $at_gecerli = $k->fetchColumn() > 0;
        }

        if ($ders_id < 1 || !$at_gecerli) {
            $_SESSION['flash_error'] = "Ders veya öğretmen bulunamadı.";
        } else {
            $db->prepare("UPDATE dersler SET ogretmen_id = ? WHERE id = ?")
               ->execute([$ogr_id > 0 ? $ogr_id : null, $ders_id]);
            $_SESSION['flash_success'] = $ogr_id > 0 ? "Öğretmen derse atandı." : "Dersin öğretmen ataması kaldırıldı.";
        }
    }

    // ---- Öğretmen ekle ----
    if ($islem == 'ogretmen_ekle') {
        $kadi  = trim($_POST['kullanici_adi'] ?? '');
        $ad    = trim($_POST['ad'] ?? '');
        $soyad = trim($_POST['soyad'] ?? '');
        $sifre = $_POST['sifre'] ?? '';
        $rol   = (($_POST['rol'] ?? '') === 'admin') ? 'admin' : 'ogretmen';

        if ($kadi === '' || $ad === '' || $soyad === '' || mb_strlen($sifre) < 6) {
            $_SESSION['flash_error'] = "Tüm alanları doldurun (şifre en az 6 karakter).";
        } else {
            try {
                $db->prepare("INSERT INTO ogretmenler (kullanici_adi, ad, soyad, sifre, rol) VALUES (?,?,?,?,?)")
                   ->execute([$kadi, $ad, $soyad, password_hash($sifre, PASSWORD_DEFAULT), $rol]);
                $_SESSION['flash_success'] = "Öğretmen eklendi.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Bu kullanıcı adı zaten kayıtlı.";
            }
        }
    }

    // ---- Öğretmen sil ----
    if ($islem == 'ogretmen_sil') {
        $id = (int)($_POST['id'] ?? 0);
        $s = $db->prepare("SELECT rol FROM ogretmenler WHERE id = ?");
        $s->execute([$id]);
        $silinecek_rol = $s->fetchColumn();
        $admin_sayisi  = (int)$db->query("SELECT COUNT(*) FROM ogretmenler WHERE rol = 'admin'")->fetchColumn();

        if ($id === $benim_id) {
            $_SESSION['flash_error'] = "Kendi hesabını silemezsin.";
        } elseif ($silinecek_rol === false) {
            $_SESSION['flash_error'] = "Öğretmen bulunamadı.";
        } elseif ($silinecek_rol === 'admin' && $admin_sayisi <= 1) {
            $_SESSION['flash_error'] = "Son admin hesabı silinemez.";
        } else {
            $db->prepare("DELETE FROM ogretmenler WHERE id = ?")->execute([$id]);
            $_SESSION['flash_success'] = "Öğretmen silindi. Dersleri atanmamış duruma geçti.";
        }
    }

    // ---- Öğretmen şifresini sıfırla ----
    if ($islem == 'ogretmen_sifre') {
        $id   = (int)($_POST['id'] ?? 0);
        $yeni = $_POST['yeni_sifre'] ?? '';
        if ($id < 1 || mb_strlen($yeni) < 6) {
            $_SESSION['flash_error'] = "Yeni şifre en az 6 karakter olmalıdır.";
        } else {
            $db->prepare("UPDATE ogretmenler SET sifre = ? WHERE id = ?")
               ->execute([password_hash($yeni, PASSWORD_DEFAULT), $id]);
            $_SESSION['flash_success'] = "Öğretmen şifresi güncellendi.";
        }
    }


    // ---- Not gir / güncelle ----
    if ($islem == 'not_gir') {
        $ogrenci_id = (int)($_POST['ogrenci_id'] ?? 0);
        $ders_id    = (int)($_POST['ders_id'] ?? 0);

        $gecerli = true;
        $oku = function ($alan) use (&$gecerli) {
            $v = trim($_POST[$alan] ?? '');
            if ($v === '') return null;
            if (!is_numeric($v) || $v < 0 || $v > 100) { $gecerli = false; return null; }
            return (float)$v;
        };
        $v1  = $oku('vize1');
        $v2  = $oku('vize2');
        $v3  = $oku('vize3');
        $fin = $oku('final');

        $kontrol = $db->prepare("
            SELECT COUNT(*) FROM ogrenciler o
            JOIN dersler d ON d.bolum_id = o.bolum_id
            WHERE o.id = ? AND d.id = ?
        ");
        $kontrol->execute([$ogrenci_id, $ders_id]);

        if ($ogrenci_id < 1 || $ders_id < 1) {
            $_SESSION['flash_error'] = "Lütfen öğrenci ve ders seçin.";
        } elseif (!$gecerli) {
            $_SESSION['flash_error'] = "Notlar 0 ile 100 arasında olmalıdır.";
               } elseif (!$kontrol->fetchColumn()) {
            $_SESSION['flash_error'] = "Seçilen ders bu öğrencinin bölümüne ait değil.";
        } elseif (!ders_yetkisi($db, $ders_id)) {
            $_SESSION['flash_error'] = "Bu dersin notlarını girme yetkiniz yok.";
        } else {
                        // Değişiklik kaydı için eski notları oku
            $eski = $db->prepare("SELECT vize1, vize2, vize3, final FROM notlar WHERE ogrenci_id = ? AND ders_id = ?");
            $eski->execute([$ogrenci_id, $ders_id]);
            $eski_not = $eski->fetch() ?: null;
            $vizeler = array_filter([$v1, $v2, $v3], fn($v) => $v !== null);
            $v_ort = count($vizeler) > 0 ? array_sum($vizeler) / count($vizeler) : null;
            $ort = ($v_ort !== null && $fin !== null) ? ($v_ort * 0.4) + ($fin * 0.6) : null;

            $db->prepare("
                INSERT INTO notlar (ogrenci_id, ders_id, vize1, vize2, vize3, final, ortalama)
                VALUES (?,?,?,?,?,?,?)
                ON DUPLICATE KEY UPDATE vize1=VALUES(vize1), vize2=VALUES(vize2), vize3=VALUES(vize3),
                                        final=VALUES(final), ortalama=VALUES(ortalama)
            ")->execute([$ogrenci_id, $ders_id, $v1, $v2, $v3, $fin, $ort]);
            not_logla($db, $ogrenci_id, $ders_id, $eski_not,
                ['vize1' => $v1, 'vize2' => $v2, 'vize3' => $v3, 'final' => $fin],
                $eski_not === null ? 'Eklendi' : 'Güncellendi');
            $_SESSION['flash_success'] = "Not kaydedildi.";
        }
    }

    // ---- Not sil ----
       if ($islem == 'not_sil') {
        $id = (int)($_POST['id'] ?? 0);
        $s = $db->prepare("SELECT ogrenci_id, ders_id, vize1, vize2, vize3, final FROM notlar WHERE id = ?");
        $s->execute([$id]);
        $kayit = $s->fetch();
        if (!$kayit) {
            $_SESSION['flash_error'] = "Not kaydı bulunamadı.";
        } elseif (!ders_yetkisi($db, (int)$kayit['ders_id'])) {
            $_SESSION['flash_error'] = "Bu dersin notlarını silme yetkiniz yok.";
        } else {
            $db->prepare("DELETE FROM notlar WHERE id = ?")->execute([$id]);
            not_logla($db, (int)$kayit['ogrenci_id'], (int)$kayit['ders_id'], $kayit, [], 'Silindi');
            $_SESSION['flash_success'] = "Not kaydı silindi.";
        }
    }
    // ---- Devamsızlık gir ----
    if ($islem == 'devamsizlik_gir') {
        $ogrenci_id = (int)($_POST['ogrenci_id'] ?? 0);
        $tarih      = $_POST['tarih'] ?? '';
        $durum      = $_POST['durum'] ?? '';
        $d = DateTime::createFromFormat('Y-m-d', $tarih);
        $tarih_gecerli = $d && $d->format('Y-m-d') === $tarih;

        if ($ogrenci_id < 1 || !$tarih_gecerli || !in_array($durum, ['Tam', 'Yarim'])) {
            $_SESSION['flash_error'] = "Lütfen öğrenci, tarih ve durum seçin.";
        } else {
            try {
                $db->prepare("INSERT INTO devamsizlik (ogrenci_id, tarih, durum) VALUES (?,?,?)")
                   ->execute([$ogrenci_id, $tarih, $durum]);
                $_SESSION['flash_success'] = "Devamsızlık kaydedildi.";
            } catch (PDOException $e) {
                $_SESSION['flash_error'] = "Bu öğrenci için bu tarihte zaten kayıt var.";
            }
        }
    }

    // ---- Devamsızlık sil ----
    if ($islem == 'devamsizlik_sil') {
        $db->prepare("DELETE FROM devamsizlik WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
        $_SESSION['flash_success'] = "Devamsızlık kaydı silindi.";
    }

    header("Location: teacher_dashboard.php#" . $sekme);
    exit;
