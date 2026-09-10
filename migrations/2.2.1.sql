-- Sürüm 2.2.1 Veritabanı Güncellemeleri - Fiş Çoklu Ürün (Kalemler) Desteği
SET foreign_key_checks = 0;

ALTER TABLE `slips` MODIFY `urun_id` int(11) NULL;
ALTER TABLE `slips` MODIFY `miktar` decimal(10,2) NULL;

CREATE TABLE IF NOT EXISTS `slip_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `slip_id` int(11) NOT NULL,
  `urun_id` int(11) NOT NULL,
  `miktar` decimal(10,2) NOT NULL,
  `kayit_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `slip_id` (`slip_id`),
  KEY `urun_id` (`urun_id`),
  KEY `kayit_id` (`kayit_id`),
  CONSTRAINT `fk_slip_items_slip` FOREIGN KEY (`slip_id`) REFERENCES `slips` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_slip_items_urun` FOREIGN KEY (`urun_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_slip_items_kayit` FOREIGN KEY (`kayit_id`) REFERENCES `records` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- Mevcut slips kayıtlarını slip_items tablosuna aktaralım
INSERT IGNORE INTO `slip_items` (`slip_id`, `urun_id`, `miktar`, `kayit_id`)
SELECT `id`, `urun_id`, `miktar`, `kayit_id` FROM `slips` WHERE `urun_id` IS NOT NULL;

INSERT IGNORE INTO `system_migrations` (`versiyon`, `uygulandi_tarih`) VALUES ('2.2.1', NOW());

SET foreign_key_checks = 1;
