-- Sürüm 2.0.0 Veritabanı Güncellemeleri
SET foreign_key_checks = 0;

-- 1. Ayarları ekleyelim
INSERT IGNORE INTO system_settings (anahtar, deger) VALUES ('dashboard_aktif', '0');
INSERT IGNORE INTO system_settings (anahtar, deger) VALUES ('stok_yonetimi_aktif', '0');

-- 2. Ürünlere stok kolonunu ekleyelim
ALTER TABLE products ADD COLUMN stok DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER birim;

-- 3. Tedarikçiler tablosu
CREATE TABLE IF NOT EXISTS `suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `firma_adi` varchar(200) NOT NULL,
  `yetkili_kisi` varchar(100) DEFAULT NULL,
  `telefon` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `adres` text DEFAULT NULL,
  `aktif` tinyint(1) NOT NULL DEFAULT 1,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 4. Faturalar (Stok Girişleri)
CREATE TABLE IF NOT EXISTS `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fatura_no` varchar(100) NOT NULL,
  `tedarikci_id` int(11) NOT NULL,
  `tarih` date NOT NULL,
  `toplam_tutar` decimal(12,2) DEFAULT NULL,
  `notlar` text DEFAULT NULL,
  `olusturan_id` int(11) DEFAULT NULL,
  `olusturma_tarihi` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `tedarikci_id` (`tedarikci_id`),
  KEY `olusturan_id` (`olusturan_id`),
  CONSTRAINT `fk_invoice_supplier` FOREIGN KEY (`tedarikci_id`) REFERENCES `suppliers` (`id`),
  CONSTRAINT `fk_invoice_user` FOREIGN KEY (`olusturan_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

-- 5. Stok Hareketleri
CREATE TABLE IF NOT EXISTS `stock_movements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `urun_id` int(11) NOT NULL,
  `islem_turu` enum('giris','cikis') NOT NULL,
  `miktar` decimal(10,2) NOT NULL,
  `fatura_id` int(11) DEFAULT NULL,
  `kayit_id` int(11) DEFAULT NULL,
  `aciklama` text DEFAULT NULL,
  `olusturan_id` int(11) DEFAULT NULL,
  `tarih` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `urun_id` (`urun_id`),
  KEY `fatura_id` (`fatura_id`),
  KEY `kayit_id` (`kayit_id`),
  CONSTRAINT `fk_stock_product` FOREIGN KEY (`urun_id`) REFERENCES `products` (`id`),
  CONSTRAINT `fk_stock_invoice` FOREIGN KEY (`fatura_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stock_record` FOREIGN KEY (`kayit_id`) REFERENCES `records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_turkish_ci;

SET foreign_key_checks = 1;