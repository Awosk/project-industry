-- Migration: v1.0.5
-- E-posta servisi ve bildirim sistemi için gerekli tablo ve kolon değişiklikleri

SET foreign_key_checks = 0;

-- 1. kullanicilar tablosuna email kolonu ekle
ALTER TABLE `kullanicilar`
  ADD COLUMN IF NOT EXISTS `email` varchar(150) DEFAULT NULL AFTER `kullanici_adi`;

-- 2. SMTP ve sistem ayarları tablosu
CREATE TABLE IF NOT EXISTS `sistem_ayarlar` (
  `anahtar` varchar(100) NOT NULL,
  `deger` text DEFAULT NULL,
  `guncelleme_tarihi` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`anahtar`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Varsayılan ayarlar
INSERT IGNORE INTO `sistem_ayarlar` (`anahtar`, `deger`) VALUES
('smtp_aktif',     '0'),
('smtp_host',      ''),
('smtp_port',      '587'),
('smtp_sifrelem',  'tls'),
('smtp_kullanici', ''),
('smtp_sifre',     ''),
('smtp_gonderen',  ''),
('smtp_ad',        '');

-- 3. Admin bildirim filtreleri tablosu
CREATE TABLE IF NOT EXISTS `admin_bildirim_filtreler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kullanici_id` int(11) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 0,
  `modul` varchar(50) NOT NULL,
  `aksiyon` varchar(50) NOT NULL,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `kullanici_modul_aksiyon` (`kullanici_id`, `modul`, `aksiyon`),
  CONSTRAINT `fk_bildirim_kullanici` FOREIGN KEY (`kullanici_id`) REFERENCES `kullanicilar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 4. Şifre sıfırlama token tablosu
CREATE TABLE IF NOT EXISTS `sifre_sifirlama` (
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

SET foreign_key_checks = 1;