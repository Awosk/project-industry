<?php
/*
 * Project Industry - Vehicle and Facility product tracking management system
 * Copyright (C) 2026 Awosk
 */

class Stok {
    // Tedarikçiler
    public static function tumTedarikciler($pdo) {
        return $pdo->query("SELECT * FROM suppliers WHERE aktif=1 ORDER BY firma_adi")->fetchAll();
    }
    
    public static function tedarikciEkle($pdo, $firma_adi, $yetkili, $telefon, $email, $adres) {
        $stmt = $pdo->prepare("INSERT INTO suppliers (firma_adi, yetkili_kisi, telefon, email, adres) VALUES (?,?,?,?,?)");
        $stmt->execute([$firma_adi, $yetkili, $telefon, $email, $adres]);
        return $pdo->lastInsertId();
    }
    
    public static function tedarikciGuncelle($pdo, $id, $firma_adi, $yetkili, $telefon, $email, $adres) {
        return $pdo->prepare("UPDATE suppliers SET firma_adi=?, yetkili_kisi=?, telefon=?, email=?, adres=? WHERE id=?")
            ->execute([$firma_adi, $yetkili, $telefon, $email, $adres, $id]);
    }
    
    public static function tedarikciSil($pdo, $id) {
        return $pdo->prepare("UPDATE suppliers SET aktif=0 WHERE id=?")->execute([$id]);
    }

    // Faturalar (Girişler)
    public static function tumFaturalar($pdo) {
        return $pdo->query("
            SELECT f.*, t.firma_adi, k.ad_soyad, 
            (SELECT COUNT(*) FROM stock_movements WHERE fatura_id=f.id) as kalem_sayisi
            FROM invoices f
            JOIN suppliers t ON f.tedarikci_id = t.id
            LEFT JOIN users k ON f.olusturan_id = k.id
            ORDER BY f.tarih DESC, f.id DESC
        ")->fetchAll();
    }
    
    public static function faturaEkle($pdo, $fatura_no, $tedarikci_id, $tarih, $notlar, $olusturan_id, $kalemler) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO invoices (fatura_no, tedarikci_id, tarih, notlar, olusturan_id) VALUES (?,?,?,?,?)");
            $stmt->execute([$fatura_no, $tedarikci_id, $tarih, $notlar, $olusturan_id]);
            $fatura_id = $pdo->lastInsertId();
            
            $toplam = 0;
            $stmt_hareket = $pdo->prepare("INSERT INTO stock_movements (urun_id, islem_turu, miktar, fatura_id, aciklama, olusturan_id) VALUES (?, 'giris', ?, ?, 'Fatura Girişi', ?)");
            $stmt_stok = $pdo->prepare("UPDATE products SET stok = stok + ? WHERE id=?");
            
            foreach ($kalemler as $kalem) {
                if (!empty($kalem['urun_id']) && $kalem['miktar'] > 0) {
                    $stmt_hareket->execute([$kalem['urun_id'], $kalem['miktar'], $fatura_id, $olusturan_id]);
                    $stmt_stok->execute([$kalem['miktar'], $kalem['urun_id']]);
                    $toplam += (float)($kalem['tutar'] ?? 0);
                }
            }
            
            $pdo->prepare("UPDATE invoices SET toplam_tutar=? WHERE id=?")->execute([$toplam, $fatura_id]);
            $pdo->commit();
            return $fatura_id;
        } catch(Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public static function faturaDetay($pdo, $fatura_id) {
        $stmt = $pdo->prepare("
            SELECT f.*, t.firma_adi 
            FROM invoices f 
            JOIN suppliers t ON f.tedarikci_id = t.id 
            WHERE f.id=?
        ");
        $stmt->execute([$fatura_id]);
        $fatura = $stmt->fetch();
        if($fatura) {
            $stmt_k = $pdo->prepare("SELECT s.*, u.urun_adi, u.birim FROM stock_movements s JOIN products u ON s.urun_id=u.id WHERE s.fatura_id=?");
            $stmt_k->execute([$fatura_id]);
            $fatura['kalemler'] = $stmt_k->fetchAll();
        }
        return $fatura;
    }
    
    public static function faturaGuncelle($pdo, $fatura_id, $fatura_no, $tarih, $notlar, $olusturan_id, $kalemler) {
        $pdo->beginTransaction();
        try {
            // Fatura bilgilerini güncelle
            $pdo->prepare("UPDATE invoices SET fatura_no=?, tarih=?, notlar=? WHERE id=?")->execute([$fatura_no, $tarih, $notlar, $fatura_id]);
            
            // Eski kalemleri bul ve stoktan düş
            $eski_kalemler = $pdo->prepare("SELECT urun_id, miktar FROM stock_movements WHERE fatura_id=?");
            $eski_kalemler->execute([$fatura_id]);
            $stmt_stok_dus = $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id=?");
            foreach ($eski_kalemler->fetchAll() as $ek) {
                $stmt_stok_dus->execute([$ek['miktar'], $ek['urun_id']]);
            }
            
            // Eski hareketleri sil
            $pdo->prepare("DELETE FROM stock_movements WHERE fatura_id=?")->execute([$fatura_id]);
            
            // Yeni kalemleri ekle ve stoka ekle
            $toplam = 0;
            $stmt_hareket = $pdo->prepare("INSERT INTO stock_movements (urun_id, islem_turu, miktar, fatura_id, aciklama, olusturan_id) VALUES (?, 'giris', ?, ?, 'Fatura Girişi (Güncellendi)', ?)");
            $stmt_stok_ekle = $pdo->prepare("UPDATE products SET stok = stok + ? WHERE id=?");
            
            foreach ($kalemler as $kalem) {
                if (!empty($kalem['urun_id']) && $kalem['miktar'] > 0) {
                    $stmt_hareket->execute([$kalem['urun_id'], $kalem['miktar'], $fatura_id, $olusturan_id]);
                    $stmt_stok_ekle->execute([$kalem['miktar'], $kalem['urun_id']]);
                    $toplam += (float)($kalem['tutar'] ?? 0);
                }
            }
            
            $pdo->prepare("UPDATE invoices SET toplam_tutar=? WHERE id=?")->execute([$toplam, $fatura_id]);
            $pdo->commit();
            return true;
        } catch(Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public static function faturaSil($pdo, $fatura_id) {
        $pdo->beginTransaction();
        try {
            $kalemler = $pdo->prepare("SELECT urun_id, miktar FROM stock_movements WHERE fatura_id=?");
            $kalemler->execute([$fatura_id]);
            $stmt_stok = $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id=?");
            foreach ($kalemler->fetchAll() as $kalem) {
                $stmt_stok->execute([$kalem['miktar'], $kalem['urun_id']]);
            }
            // deletes movements implicitly because of ON DELETE CASCADE
            $pdo->prepare("DELETE FROM invoices WHERE id=?")->execute([$fatura_id]); 
            $pdo->commit();
            return true;
        } catch(Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // Manuel Stok İşlemleri
    public static function manuelIslem($pdo, $urun_id, $islem_turu, $miktar, $aciklama, $olusturan_id) {
        $pdo->beginTransaction();
        try {
            if ($islem_turu === 'esitle') {
                $eski_stok = $pdo->query("SELECT stok FROM products WHERE id=".(int)$urun_id)->fetchColumn();
                $fark = $miktar - $eski_stok;
                if ($fark == 0) { $pdo->rollBack(); return true; }
                $gercek_islem = $fark > 0 ? 'giris' : 'cikis';
                $gercek_miktar = abs($fark);
                
                $pdo->prepare("UPDATE products SET stok = ? WHERE id=?")->execute([$miktar, $urun_id]);
                $pdo->prepare("INSERT INTO stock_movements (urun_id, islem_turu, miktar, aciklama, olusturan_id) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$urun_id, $gercek_islem, $gercek_miktar, $aciklama ?: 'Stok Eşitleme', $olusturan_id]);
            } else {
                // giris veya cikis
                if ($islem_turu === 'giris') {
                    $pdo->prepare("UPDATE products SET stok = stok + ? WHERE id=?")->execute([$miktar, $urun_id]);
                } else {
                    $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id=?")->execute([$miktar, $urun_id]);
                }
                $pdo->prepare("INSERT INTO stock_movements (urun_id, islem_turu, miktar, aciklama, olusturan_id) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$urun_id, $islem_turu, $miktar, $aciklama ?: 'Manuel İşlem', $olusturan_id]);
            }
            $pdo->commit();
            return true;
        } catch(Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}