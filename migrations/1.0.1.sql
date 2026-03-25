-- Migration: v1.0.1
-- Araç türleri arayüzden yönetilebilir hale getirildi.
-- lite_arac_turleri tablosu oluşturulur,
-- mevcut arac_turu text verileri buraya taşınır,
-- lite_araclar.arac_turu sütunu id referansına dönüştürülür.

SET foreign_key_checks = 0;

-- 1. Yeni tablo oluştur
CREATE TABLE IF NOT EXISTS `lite_arac_turleri` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tur_adi` varchar(100) NOT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `tur_adi` (`tur_adi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 2. Mevcut araçlardaki türleri (distinct) yeni tabloya kopyala
INSERT IGNORE INTO `lite_arac_turleri` (`tur_adi`)
SELECT DISTINCT `arac_turu`
FROM `lite_araclar`
WHERE `arac_turu` IS NOT NULL AND `arac_turu` != '';

-- 3. lite_araclar'a yeni id sütunu ekle
ALTER TABLE `lite_araclar`
  ADD COLUMN IF NOT EXISTS `arac_turu_id` int(11) NULL AFTER `arac_turu`;

-- 4. Mevcut text değerlerini id'ye dönüştür
UPDATE `lite_araclar` a
INNER JOIN `lite_arac_turleri` t ON t.tur_adi = a.arac_turu
SET a.arac_turu_id = t.id;

-- 5. Eski text sütununu sil
ALTER TABLE `lite_araclar` DROP COLUMN `arac_turu`;

-- 6. Foreign key ekle
ALTER TABLE `lite_araclar`
  ADD CONSTRAINT `lite_araclar_ibfk_tur` FOREIGN KEY (`arac_turu_id`) REFERENCES `lite_arac_turleri` (`id`);

SET foreign_key_checks = 1;
