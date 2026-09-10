-- Sürüm 2.2.0 Veritabanı Güncellemeleri - Fiş Sistemi
SET foreign_key_checks = 0;

CREATE TABLE IF NOT EXISTS `slips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `kayit_turu` enum('arac','tesis') NOT NULL,
  `arac_id` int(11) DEFAULT NULL,
  `tesis_id` int(11) DEFAULT NULL,
  `urun_id` int(11) NOT NULL,
  `miktar` decimal(10,2) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `yag_bakimi` tinyint(1) NOT NULL DEFAULT 0,
  `mevcut_km` int(11) DEFAULT NULL,
  `durum` enum('bekliyor','onaylandi','iptal') NOT NULL DEFAULT 'bekliyor',
  `olusturan_id` int(11) NOT NULL,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  `onaylayan_id` int(11) DEFAULT NULL,
  `onay_tarihi` datetime DEFAULT NULL,
  `kayit_id` int(11) DEFAULT NULL,
  `iptal_eden_id` int(11) DEFAULT NULL,
  `iptal_tarihi` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kayit_turu` (`kayit_turu`),
  KEY `arac_id` (`arac_id`),
  KEY `tesis_id` (`tesis_id`),
  KEY `urun_id` (`urun_id`),
  KEY `durum` (`durum`),
  KEY `olusturan_id` (`olusturan_id`),
  KEY `onaylayan_id` (`onaylayan_id`),
  KEY `kayit_id` (`kayit_id`),
  KEY `iptal_eden_id` (`iptal_eden_id`),
  CONSTRAINT `fk_slips_arac` FOREIGN KEY (`arac_id`) REFERENCES `vehicles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_slips_tesis` FOREIGN KEY (`tesis_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_slips_urun` FOREIGN KEY (`urun_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_slips_olusturan` FOREIGN KEY (`olusturan_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_slips_onaylayan` FOREIGN KEY (`onaylayan_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_slips_kayit` FOREIGN KEY (`kayit_id`) REFERENCES `records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_slips_iptal_eden` FOREIGN KEY (`iptal_eden_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

INSERT IGNORE INTO `system_migrations` (`versiyon`, `uygulandi_tarih`) VALUES ('2.2.0', NOW());

SET foreign_key_checks = 1;
