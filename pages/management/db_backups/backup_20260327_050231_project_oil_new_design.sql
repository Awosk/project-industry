-- Project Oil Veritabanı Yedeği
-- Tarih: 27.03.2026 05:02:31
-- Veritabanı: project_oil_new_design
-- Tablolar: admin_bildirim_filtreler, kullanicilar, lite_arac_turleri, lite_araclar, lite_kayitlar, lite_tesisler, lite_urunler, mail_queue, sifre_sifirlama, sistem_ayarlar, sistem_loglari, sistem_migrations

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ─────────────────────────────────
DROP TABLE IF EXISTS `admin_bildirim_filtreler`;
CREATE TABLE `admin_bildirim_filtreler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 0,
  `modul` varchar(50) NOT NULL,
  `aksiyon` varchar(50) NOT NULL,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanici_modul_aksiyon` (`kullanici_id`,`modul`,`aksiyon`),
  CONSTRAINT `fk_bildirim_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ─────────────────────────────────
DROP TABLE IF EXISTS `kullanicilar`;
CREATE TABLE `kullanicilar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ad_soyad` varchar(100) NOT NULL,
  `kullanici_adi` varchar(50) NOT NULL,
  `sifre` varchar(255) NOT NULL,
  `rol` enum('admin','kullanici') NOT NULL DEFAULT 'kullanici',
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `tema` enum('light','dark') NOT NULL DEFAULT 'light',
  `email` varchar(150) DEFAULT NULL,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `mail_bildirim_aktif` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanici_adi` (`kullanici_adi`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `kullanicilar` (`id`, `ad_soyad`, `kullanici_adi`, `sifre`, `rol`, `aktif`, `tema`, `email`, `olusturma_tarihi`, `mail_bildirim_aktif`) VALUES
('1', 'Erdem Gümüş', 'awosk', '$2y$10$zSpBAZuvDNk9JK8/neZjUO1okjBR3VGqlHc8TBvaj0sTg4ZTshru6', 'admin', '1', 'light', 'tester@awosk.xyz', '2026-03-27 04:51:06', '0'),
('2', 'Awosk Demo', 'demo', '$2y$10$8FX6Am4UJisHcDLZjq4vJOdCiOTCvoqXbp0AdYXF7Vu50KRqSIJlS', 'kullanici', '1', 'light', NULL, '2026-03-27 05:02:14', '0');

-- ─────────────────────────────────
DROP TABLE IF EXISTS `lite_arac_turleri`;
CREATE TABLE `lite_arac_turleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tur_adi` varchar(100) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tur_adi` (`tur_adi`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `lite_arac_turleri` (`id`, `tur_adi`, `aktif`, `olusturma_tarihi`) VALUES
('1', 'Araba', '1', '2026-03-27 04:59:44'),
('2', 'Gamyon', '1', '2026-03-27 04:59:47'),
('3', 'Otobüs', '1', '2026-03-27 04:59:50');

-- ─────────────────────────────────
DROP TABLE IF EXISTS `lite_araclar`;
CREATE TABLE `lite_araclar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `arac_turu_id` int(11) DEFAULT NULL,
  `plaka` varchar(20) NOT NULL,
  `marka_model` varchar(150) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `olusturan_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `plaka` (`plaka`),
  KEY `olusturan_id` (`olusturan_id`),
  KEY `arac_turu_id` (`arac_turu_id`),
  CONSTRAINT `lite_araclar_ibfk_1` FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar` (`id`),
  CONSTRAINT `lite_araclar_ibfk_tur` FOREIGN KEY (`arac_turu_id`) REFERENCES `lite_arac_turleri` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `lite_araclar` (`id`, `arac_turu_id`, `plaka`, `marka_model`, `aktif`, `olusturma_tarihi`, `olusturan_id`) VALUES
('1', '1', 'ARABA', 'araba', '1', '2026-03-27 05:00:00', '1'),
('2', '2', 'GAMYON', 'gamyon', '1', '2026-03-27 05:00:09', '1');

-- ─────────────────────────────────
DROP TABLE IF EXISTS `lite_kayitlar`;
CREATE TABLE `lite_kayitlar` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kayit_turu` enum('arac','tesis') NOT NULL,
  `arac_id` int(11) DEFAULT NULL,
  `tesis_id` int(11) DEFAULT NULL,
  `urun_id` int(11) NOT NULL,
  `miktar` decimal(10,2) NOT NULL,
  `tarih` date NOT NULL,
  `aciklama` text DEFAULT NULL,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `olusturan_id` int(11) DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `yag_bakimi` tinyint(1) NOT NULL DEFAULT 0,
  `mevcut_km` int(11) DEFAULT NULL,
  `islendi` tinyint(1) NOT NULL DEFAULT 0,
  `islendi_tarih` datetime DEFAULT NULL,
  `islendi_kullanici_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `arac_id` (`arac_id`),
  KEY `tesis_id` (`tesis_id`),
  KEY `urun_id` (`urun_id`),
  KEY `olusturan_id` (`olusturan_id`),
  KEY `islendi_kullanici_id` (`islendi_kullanici_id`),
  CONSTRAINT `fk_islendi_kullanici` FOREIGN KEY (`islendi_kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL,
  CONSTRAINT `lite_kayitlar_ibfk_1` FOREIGN KEY (`arac_id`) REFERENCES `lite_araclar` (`id`),
  CONSTRAINT `lite_kayitlar_ibfk_2` FOREIGN KEY (`tesis_id`) REFERENCES `lite_tesisler` (`id`),
  CONSTRAINT `lite_kayitlar_ibfk_3` FOREIGN KEY (`urun_id`) REFERENCES `lite_urunler` (`id`),
  CONSTRAINT `lite_kayitlar_ibfk_4` FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `lite_kayitlar` (`id`, `kayit_turu`, `arac_id`, `tesis_id`, `urun_id`, `miktar`, `tarih`, `aciklama`, `olusturma_tarihi`, `olusturan_id`, `aktif`, `yag_bakimi`, `mevcut_km`, `islendi`, `islendi_tarih`, `islendi_kullanici_id`) VALUES
('1', 'arac', '1', NULL, '1', '5.00', '2026-03-27', NULL, '2026-03-27 05:01:05', '1', '1', '0', NULL, '1', '2026-03-27 05:01:36', '1'),
('2', 'arac', '2', NULL, '1', '15.00', '2026-03-27', NULL, '2026-03-27 05:01:16', '1', '1', '1', '250000', '1', '2026-03-27 05:01:37', '1'),
('3', 'tesis', NULL, '1', '1', '50.00', '2026-03-27', 'tesis ihtiyacı', '2026-03-27 05:01:27', '1', '1', '0', NULL, '0', NULL, NULL);

-- ─────────────────────────────────
DROP TABLE IF EXISTS `lite_tesisler`;
CREATE TABLE `lite_tesisler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_adi` varchar(200) NOT NULL,
  `firma_adresi` text NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `olusturan_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `olusturan_id` (`olusturan_id`),
  CONSTRAINT `lite_tesisler_ibfk_1` FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `lite_tesisler` (`id`, `firma_adi`, `firma_adresi`, `aktif`, `olusturma_tarihi`, `olusturan_id`) VALUES
('1', 'karaambar', 'karaambar kamyoncular derneği', '1', '2026-03-27 05:00:22', '1');

-- ─────────────────────────────────
DROP TABLE IF EXISTS `lite_urunler`;
CREATE TABLE `lite_urunler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `urun_kodu` varchar(50) NOT NULL,
  `urun_adi` varchar(200) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `olusturan_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `urun_kodu` (`urun_kodu`),
  KEY `olusturan_id` (`olusturan_id`),
  CONSTRAINT `lite_urunler_ibfk_1` FOREIGN KEY (`olusturan_id`) REFERENCES `kullanicilar` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `lite_urunler` (`id`, `urun_kodu`, `urun_adi`, `aktif`, `olusturma_tarihi`, `olusturan_id`) VALUES
('1', 'GURU', '10W40 Bol acılı guru (süper mario tarifi)', '1', '2026-03-27 05:00:55', '1');

-- ─────────────────────────────────
DROP TABLE IF EXISTS `mail_queue`;
CREATE TABLE `mail_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_email` varchar(150) NOT NULL,
  `to_name` varchar(100) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `status` enum('pending','sent','failed','paused','processing','force','cancelled') NOT NULL DEFAULT 'pending',
  `attempt_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `sent_at` datetime DEFAULT NULL,
  `hata_mesaji` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ─────────────────────────────────
DROP TABLE IF EXISTS `sifre_sifirlama`;
CREATE TABLE `sifre_sifirlama` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `token` varchar(100) NOT NULL,
  `son_kullanma` datetime NOT NULL,
  `kullanildi` tinyint(1) NOT NULL DEFAULT 0,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `kullanici_id` (`kullanici_id`),
  CONSTRAINT `fk_sifre_sifirlama_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- ─────────────────────────────────
DROP TABLE IF EXISTS `sistem_ayarlar`;
CREATE TABLE `sistem_ayarlar` (
  `anahtar` varchar(100) NOT NULL,
  `deger` text DEFAULT NULL,
  `guncelleme_tarihi` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`anahtar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `sistem_ayarlar` (`anahtar`, `deger`, `guncelleme_tarihi`) VALUES
('mail_cooldown_bitis', '', '2026-03-27 04:51:06'),
('mail_cooldown_dakika', '15', '2026-03-27 04:51:06'),
('mail_rate_limit_adet', '10', '2026-03-27 04:51:06'),
('mail_rate_limit_dakika', '5', '2026-03-27 04:51:06'),
('smtp_ad', '', '2026-03-27 04:51:06'),
('smtp_aktif', '0', '2026-03-27 04:51:06'),
('smtp_gonderen', '', '2026-03-27 04:51:06'),
('smtp_host', '', '2026-03-27 04:51:06'),
('smtp_kullanici', '', '2026-03-27 04:51:06'),
('smtp_port', '587', '2026-03-27 04:51:06'),
('smtp_sifre', '', '2026-03-27 04:51:06'),
('smtp_sifrelem', 'tls', '2026-03-27 04:51:06');

-- ─────────────────────────────────
DROP TABLE IF EXISTS `sistem_loglari`;
CREATE TABLE `sistem_loglari` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) DEFAULT NULL,
  `kullanici_adi` varchar(50) DEFAULT NULL,
  `ad_soyad` varchar(100) DEFAULT NULL,
  `sistem` enum('ana','lite') NOT NULL DEFAULT 'ana',
  `aksiyon` enum('ekle','guncelle','sil','giris','cikis') NOT NULL,
  `modul` varchar(50) NOT NULL,
  `kayit_id` int(11) DEFAULT NULL,
  `aciklama` text NOT NULL,
  `eski_deger` longtext DEFAULT NULL CHECK (json_valid(`eski_deger`)),
  `yeni_deger` longtext DEFAULT NULL CHECK (json_valid(`yeni_deger`)),
  `ip_adresi` varchar(45) DEFAULT NULL,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `kullanici_id` (`kullanici_id`),
  CONSTRAINT `sistem_loglari_ibfk_1` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `sistem_loglari` (`id`, `kullanici_id`, `kullanici_adi`, `ad_soyad`, `sistem`, `aksiyon`, `modul`, `kayit_id`, `aciklama`, `eski_deger`, `yeni_deger`, `ip_adresi`, `olusturma_tarihi`) VALUES
('1', '1', 'awosk', 'Erdem Gümüş', 'lite', 'giris', 'auth', NULL, 'Sisteme giriş yapıldı', NULL, NULL, '::1', '2026-03-27 04:55:29'),
('2', '1', 'awosk', 'Erdem Gümüş', 'lite', 'cikis', 'auth', NULL, 'Sistemden çıkış yapıldı', NULL, NULL, '::1', '2026-03-27 04:58:51'),
('3', '1', 'awosk', 'Erdem Gümüş', 'lite', 'giris', 'auth', NULL, 'Sisteme giriş yapıldı', NULL, NULL, '::1', '2026-03-27 04:58:58'),
('4', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'arac_tur', '1', 'Araç türü eklendi: Araba', NULL, '{\"tur_adi\":\"Araba\"}', '::1', '2026-03-27 04:59:44'),
('5', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'arac_tur', '2', 'Araç türü eklendi: Gamyon', NULL, '{\"tur_adi\":\"Gamyon\"}', '::1', '2026-03-27 04:59:47'),
('6', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'arac_tur', '3', 'Araç türü eklendi: Otobüs', NULL, '{\"tur_adi\":\"Otobüs\"}', '::1', '2026-03-27 04:59:50'),
('7', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'arac', '1', 'Araç eklendi: ARABA (Araba) - araba', NULL, '{\"tur_id\":1,\"plaka\":\"ARABA\",\"model\":\"araba\"}', '::1', '2026-03-27 05:00:00'),
('8', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'arac', '2', 'Araç eklendi: GAMYON (Gamyon) - gamyon', NULL, '{\"tur_id\":2,\"plaka\":\"GAMYON\",\"model\":\"gamyon\"}', '::1', '2026-03-27 05:00:09'),
('9', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'tesis', '1', 'Tesis eklendi: karaambar', NULL, '{\"firma\":\"karaambar\",\"adres\":\"karaambar kamyoncular derneği\"}', '::1', '2026-03-27 05:00:22'),
('10', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'urun', '1', 'Ürün eklendi: GURU - 10W40 Bol acılı guru (süper mario tarifi)', NULL, '{\"kod\":\"GURU\",\"adi\":\"10W40 Bol acılı guru (süper mario tarifi)\"}', '::1', '2026-03-27 05:00:55'),
('11', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'arac_kayit', '1', 'ARABA aracına yağ eklendi: GURU 10W40 Bol acılı guru (süper mario tarifi), 5L', NULL, '{\"plaka\":\"ARABA\",\"urun_id\":1,\"miktar\":5,\"tarih\":\"2026-03-27\",\"yag_bakimi\":0,\"mevcut_km\":null,\"aciklama\":\"\"}', '::1', '2026-03-27 05:01:05'),
('12', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'arac_kayit', '2', 'GAMYON aracına yağ eklendi: GURU 10W40 Bol acılı guru (süper mario tarifi), 15L [YAĞ BAKIMI - 250,000 KM]', NULL, '{\"plaka\":\"GAMYON\",\"urun_id\":1,\"miktar\":15,\"tarih\":\"2026-03-27\",\"yag_bakimi\":1,\"mevcut_km\":250000,\"aciklama\":\"\"}', '::1', '2026-03-27 05:01:16'),
('13', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'tesis_kayit', '3', 'karaambar tesisine yağ eklendi: GURU - 10W40 Bol acılı guru (süper mario tarifi), 50L, tarih:2026-03-27. Açıklama: tesis ihtiyacı', NULL, '{\"tesis_id\":1,\"firma\":\"karaambar\",\"urun_id\":1,\"miktar\":50,\"tarih\":\"2026-03-27\",\"aciklama\":\"tesis ihtiyacı\"}', '::1', '2026-03-27 05:01:27'),
('14', '1', 'awosk', 'Erdem Gümüş', 'lite', 'guncelle', 'islendi', '1', 'Kayıt depoya işlendi: ARABA — GURU 10W40 Bol acılı guru (süper mario tarifi), 5.00L', '{\"islendi\":0}', '{\"islendi\":1,\"islendi_kullanici_id\":1}', '::1', '2026-03-27 05:01:36'),
('15', '1', 'awosk', 'Erdem Gümüş', 'lite', 'guncelle', 'islendi', '2', 'Kayıt depoya işlendi: GAMYON — GURU 10W40 Bol acılı guru (süper mario tarifi), 15.00L', '{\"islendi\":0}', '{\"islendi\":1,\"islendi_kullanici_id\":1}', '::1', '2026-03-27 05:01:37'),
('16', '1', 'awosk', 'Erdem Gümüş', 'lite', 'ekle', 'kullanici', '2', 'Yeni kullanıcı eklendi: demo (kullanici)', NULL, '{\"ad_soyad\":\"Awosk Demo\",\"kullanici_adi\":\"demo\",\"rol\":\"kullanici\"}', '::1', '2026-03-27 05:02:14'),
('17', '1', 'awosk', 'Erdem Gümüş', 'lite', 'guncelle', 'kullanici', '1', 'E-posta güncellendi: awosk', '{\"email\":null}', '{\"email\":\"tester@awosk.xyz\"}', '::1', '2026-03-27 05:02:20');

-- ─────────────────────────────────
DROP TABLE IF EXISTS `sistem_migrations`;
CREATE TABLE `sistem_migrations` (
  `versiyon` varchar(20) NOT NULL,
  `uygulandi_tarih` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`versiyon`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT INTO `sistem_migrations` (`versiyon`, `uygulandi_tarih`) VALUES
('1.1.0', '2026-03-27 04:51:06');

SET foreign_key_checks = 1;
