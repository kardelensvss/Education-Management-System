-- E-Okul Sistemi - Veritabanı kurulum dosyası
-- Kullanım: phpMyAdmin > php_odev veritabanı > İçe aktar
-- UYARI: Mevcut tabloları siler ve yeniden kurar.

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS not_log;
DROP TABLE IF EXISTS giris_denemeleri;
DROP TABLE IF EXISTS devamsizlik;
DROP TABLE IF EXISTS notlar;
DROP TABLE IF EXISTS dersler;
DROP TABLE IF EXISTS ogrenciler;
DROP TABLE IF EXISTS ogretmenler;
DROP TABLE IF EXISTS bolumler;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE bolumler (
  id INT NOT NULL AUTO_INCREMENT,
  bolum_adi VARCHAR(100) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY bolum_adi (bolum_adi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ogretmenler (
  id INT NOT NULL AUTO_INCREMENT,
  kullanici_adi VARCHAR(50) NOT NULL,
  ad VARCHAR(50) NOT NULL,
  soyad VARCHAR(50) NOT NULL,
  sifre VARCHAR(255) NOT NULL,
  rol ENUM('admin','ogretmen') NOT NULL DEFAULT 'ogretmen',
  PRIMARY KEY (id),
  UNIQUE KEY kullanici_adi (kullanici_adi)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE ogrenciler (
  id INT NOT NULL AUTO_INCREMENT,
  ogr_no VARCHAR(20) NOT NULL,
  ad VARCHAR(50) NOT NULL,
  soyad VARCHAR(50) NOT NULL,
  bolum_id INT NOT NULL,
  sinif TINYINT NOT NULL DEFAULT 1,
  sifre VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ogr_no (ogr_no),
  KEY bolum_id (bolum_id),
  CONSTRAINT ogrenciler_bolum_fk FOREIGN KEY (bolum_id) REFERENCES bolumler (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dersler (
  id INT NOT NULL AUTO_INCREMENT,
  ders_adi VARCHAR(100) NOT NULL,
  bolum_id INT NOT NULL,
  sinif TINYINT NOT NULL DEFAULT 1,
  ogretmen_id INT NULL DEFAULT NULL,
  PRIMARY KEY (id),
  KEY bolum_id (bolum_id),
  KEY ogretmen_id (ogretmen_id),
  CONSTRAINT dersler_bolum_fk FOREIGN KEY (bolum_id) REFERENCES bolumler (id),
  CONSTRAINT dersler_ogretmen_fk FOREIGN KEY (ogretmen_id) REFERENCES ogretmenler (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE notlar (
  id INT NOT NULL AUTO_INCREMENT,
  ogrenci_id INT NOT NULL,
  ders_id INT NOT NULL,
  vize1 DECIMAL(5,2) DEFAULT NULL,
  vize2 DECIMAL(5,2) DEFAULT NULL,
  vize3 DECIMAL(5,2) DEFAULT NULL,
  final DECIMAL(5,2) DEFAULT NULL,
  ortalama DECIMAL(5,2) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY ogrenci_ders (ogrenci_id, ders_id),
  KEY ders_id (ders_id),
  CONSTRAINT notlar_ogrenci_fk FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler (id) ON DELETE CASCADE,
  CONSTRAINT notlar_ders_fk FOREIGN KEY (ders_id) REFERENCES dersler (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE devamsizlik (
  id INT NOT NULL AUTO_INCREMENT,
  ogrenci_id INT NOT NULL,
  tarih DATE NOT NULL,
  durum ENUM('Tam','Yarim') NOT NULL DEFAULT 'Tam',
  PRIMARY KEY (id),
  UNIQUE KEY ogrenci_tarih (ogrenci_id, tarih),
  CONSTRAINT devamsizlik_ogrenci_fk FOREIGN KEY (ogrenci_id) REFERENCES ogrenciler (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE giris_denemeleri (
  id INT NOT NULL AUTO_INCREMENT,
  ip VARCHAR(45) NOT NULL,
  kullanici VARCHAR(50) NOT NULL,
  tarih DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY ip_tarih (ip, tarih)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE not_log (
  id INT NOT NULL AUTO_INCREMENT,
  tarih DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  ogretmen_adi VARCHAR(100) NOT NULL,
  ogrenci_bilgi VARCHAR(150) NOT NULL,
  ders_adi VARCHAR(100) NOT NULL,
  islem VARCHAR(30) NOT NULL,
  eski_deger VARCHAR(120) NOT NULL,
  yeni_deger VARCHAR(120) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- BÖLÜMLER
INSERT INTO bolumler (id, bolum_adi) VALUES
(1, 'Bilgisayar Mühendisliği'),
(2, 'Elektrik-Elektronik Mühendisliği');

-- ÖĞRETMENLER (şifre ikisi için de: 123456)
-- admin: her şeyi yönetir / ogretmen1: sadece kendisine atanan derslerin notlarını girer
INSERT INTO ogretmenler (id, kullanici_adi, ad, soyad, sifre, rol) VALUES
(1, 'admin', 'Admin', 'Öğretmen', '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2', 'admin'),
(2, 'ogretmen1', 'Mert', 'Kara', '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2', 'ogretmen');


-- ÖĞRENCİLER (şifre hepsi için: 123456)
INSERT INTO ogrenciler (id, ogr_no, ad, soyad, bolum_id, sinif, sifre) VALUES
(1, '2401001', 'Ahmet', 'Yılmaz', 1, 1, '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2'),
(2, '2401002', 'Elif', 'Kaya', 1, 1, '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2'),
(3, '2301001', 'Mehmet', 'Demir', 1, 2, '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2'),
(4, '2301002', 'Zeynep', 'Çelik', 1, 2, '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2'),
(5, '2201001', 'Burak', 'Arslan', 1, 3, '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2'),
(6, '2201002', 'Selin', 'Aydın', 1, 3, '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2'),
(7, '2101001', 'Can', 'Öztürk', 1, 4, '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2'),
(8, '2402001', 'Ayşe', 'Şahin', 2, 1, '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2'),
(9, '2302001', 'Emre', 'Koç', 2, 2, '$2y$10$tGFLpDODiOYuUYz3J/7v6OwnDP41EK8znfJerCDqRT.riIAvXzow2');

-- DERSLER
INSERT INTO dersler (id, ders_adi, bolum_id, sinif, ogretmen_id) VALUES
(1, 'Algoritma ve Programlamaya Giriş', 1, 1, 2),
(2, 'Ayrık Matematik', 1, 1, 2),
(3, 'Veri Yapıları', 1, 2, 2),
(4, 'Nesne Yönelimli Programlama', 1, 2, 2),
(5, 'Veritabanı Yönetim Sistemleri', 1, 3, NULL),
(6, 'Web Programlama', 1, 3, NULL),
(7, 'Yazılım Mühendisliği', 1, 4, NULL),
(8, 'Bitirme Projesi', 1, 4, NULL),
(9, 'Devre Analizi', 2, 1, NULL),
(10, 'Elektromanyetik Alan Teorisi', 2, 2, NULL);

-- NOTLAR (ortalama = vize ortalaması x 0.40 + final x 0.60; final yoksa ortalama boş)
INSERT INTO notlar (ogrenci_id, ders_id, vize1, vize2, vize3, final, ortalama) VALUES
(1, 1, 62, 70, 78, NULL, NULL),
(1, 2, 60, 46, 54, 58, 56.13),
(2, 1, 59, 49, 44, 42, 45.47),
(2, 2, 79, 68, 73, 65, 68.33),
(3, 3, 86, 74, 91, 73, 77.27),
(3, 4, 70, 70, 68, 77, 73.93),
(4, 3, 61, 61, 55, 41, 48.2),
(4, 4, 51, 67, 54, 56, 56.53),
(5, 5, 70, 83, 69, 81, 78.2),
(5, 6, 75, 79, 63, 58, 63.73),
(6, 5, 91, 93, 79, 81, 83.67),
(6, 6, 60, 65, 45, 58, 57.47),
(7, 7, 62, 49, 58, 61, 59.13),
(7, 8, 86, 97, 83, 84, 85.87),
(8, 9, 87, 84, 82, 77, 79.93),
(9, 10, 72, 74, 57, 49, 56.47);

-- DEVAMSIZLIK
INSERT INTO devamsizlik (ogrenci_id, tarih, durum) VALUES
(1, '2026-09-28', 'Tam'),
(1, '2026-10-02', 'Yarim'),
(2, '2026-10-01', 'Tam'),
(3, '2026-09-30', 'Yarim'),
(3, '2026-10-05', 'Tam'),
(5, '2026-10-06', 'Tam'),
(6, '2026-10-02', 'Yarim'),
(8, '2026-10-05', 'Tam'),
(9, '2026-10-07', 'Yarim');
