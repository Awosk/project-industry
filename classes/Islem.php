<?php
/*
 * Project Industry - Vehicle and Facility product tracking management system
 * Copyright (C) 2026 Awosk
 */

require_once __DIR__ . '/SistemAyarlari.php';

class Islem {
    // ─────────────────────────────────────────────
    // STOK YARDIMCI METODLARI (kod tekrarını önler)
    // ─────────────────────────────────────────────

    /**
     * Stoktan çıkış yapar ve hareket kaydını oluşturur.
     */
    private static function stokCikisYap($pdo, $urun_id, $miktar, $kayit_id, $aciklama, $olusturan_id): void {
        $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id = ?")
            ->execute([$miktar, $urun_id]);
        $pdo->prepare("INSERT INTO stock_movements (urun_id, islem_turu, miktar, kayit_id, aciklama, olusturan_id) VALUES (?, 'cikis', ?, ?, ?, ?)")
            ->execute([$urun_id, $miktar, $kayit_id, $aciklama, $olusturan_id]);
    }

    /**
     * Stok iadesi yapar ve hareket kaydını siler.
     */
    private static function stokIadeEt($pdo, $kayit_id): void {
        $sm = $pdo->prepare("SELECT * FROM stock_movements WHERE kayit_id=?");
        $sm->execute([$kayit_id]);
        $mevcut_hareket = $sm->fetch();

        if ($mevcut_hareket) {
            $pdo->prepare("UPDATE products SET stok = stok + ? WHERE id=?")
                ->execute([$mevcut_hareket['miktar'], $mevcut_hareket['urun_id']]);
            $pdo->prepare("DELETE FROM stock_movements WHERE id=?")
                ->execute([$mevcut_hareket['id']]);
        }
    }

    /**
     * Stok yönetimi aktif mi?
     */
    private static function stokYonetimiAktifMi($pdo): bool {
        return (int)SistemAyarlari::getir($pdo, 'stok_yonetimi_aktif', 0) === 1;
    }

    public static function aracYagEkle($pdo, $arac_id, $urun_id, $miktar, $tarih, $aciklama, $yag_bakimi, $mevcut_km, $olusturan_id) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO records (kayit_turu,arac_id,urun_id,miktar,tarih,aciklama,yag_bakimi,mevcut_km,olusturan_id) VALUES ('arac',?,?,?,?,?,?,?,?)");
            $stmt->execute([$arac_id, $urun_id, $miktar, $tarih, $aciklama ?: null, $yag_bakimi, $mevcut_km, $olusturan_id]);
            $kayit_id = $pdo->lastInsertId();
            
            if (self::stokYonetimiAktifMi($pdo)) {
                self::stokCikisYap($pdo, $urun_id, $miktar, $kayit_id, 'Araç Çıkışı', $olusturan_id);
            }
            $pdo->commit();
            return $kayit_id;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function aracKayitBul($pdo, $kayit_id, $arac_id) {
        $stmt = $pdo->prepare('SELECT lk.*,u.urun_kodu, u.urun_adi, u.birim FROM records lk JOIN products u ON lk.urun_id=u.id WHERE lk.id=? AND lk.arac_id=? AND lk.aktif=1');
        $stmt->execute([$kayit_id, $arac_id]);
        return $stmt->fetch();
    }
    
    public static function aciklamaGuncelle($pdo, $kayit_id, $aciklama) {
        return $pdo->prepare("UPDATE records SET aciklama=? WHERE id=?")->execute([$aciklama ?: null, $kayit_id]);
    }

    public static function kayitGuncelle($pdo, $kayit_id, $urun_id, $miktar, $tarih, $aciklama, $yag_bakimi = 0, $mevcut_km = null) {
        $pdo->beginTransaction();
        try {
            if ((int)SistemAyarlari::getir($pdo, 'stok_yonetimi_aktif', 0) === 1) {
                // Mevcut stok hareketini bul
                $sm_stmt = $pdo->prepare("SELECT * FROM stock_movements WHERE kayit_id = ? LIMIT 1");
                $sm_stmt->execute([$kayit_id]);
                $mevcut_hareket = $sm_stmt->fetch();

                if ($mevcut_hareket) {
                    // 1. Eski stoğu iade et
                    $pdo->prepare("UPDATE products SET stok = stok + ? WHERE id = ?")
                        ->execute([$mevcut_hareket['miktar'], $mevcut_hareket['urun_id']]);
                    
                    // 2. Yeni stoğu düş
                    $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id = ?")
                        ->execute([$miktar, $urun_id]);
                    
                    // 3. Stok hareketini güncelle
                    $pdo->prepare("UPDATE stock_movements SET urun_id = ?, miktar = ?, tarih = NOW(), aciklama = ? WHERE id = ?")
                        ->execute([$urun_id, $miktar, 'Kayıt Güncellendi: ' . ($aciklama ?: 'Açıklama yok'), $mevcut_hareket['id']]);
                } else {
                    // Hareket yoksa (sonradan aktif edildiyse) yeni hareket oluştur
                    $pdo->prepare("UPDATE products SET stok = stok - ? WHERE id = ?")
                        ->execute([$miktar, $urun_id]);
                    
                    $pdo->prepare("INSERT INTO stock_movements (urun_id, islem_turu, miktar, kayit_id, aciklama, tarih) VALUES (?, 'cikis', ?, ?, ?, NOW())")
                        ->execute([$urun_id, $miktar, $kayit_id, 'Kayıt Güncellendi (Yeni Hareket)']);
                }
            }

            // Ana kaydı güncelle
            $res = $pdo->prepare("UPDATE records SET urun_id = ?, miktar = ?, tarih = ?, aciklama = ?, yag_bakimi = ?, mevcut_km = ? WHERE id = ?")
                ->execute([$urun_id, $miktar, $tarih, $aciklama ?: null, $yag_bakimi, $mevcut_km, $kayit_id]);

            $pdo->commit();
            return $res;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
    
    public static function aracKayitSilBul($pdo, $kayit_id, $arac_id) {
        $stmt = $pdo->prepare('SELECT lk.*,u.urun_kodu, u.urun_adi, u.birim FROM records lk JOIN products u ON lk.urun_id=u.id WHERE lk.id=? AND lk.arac_id=?');
        $stmt->execute([$kayit_id, $arac_id]);
        return $stmt->fetch();
    }

    public static function kayitSil($pdo, $kayit_id, $arac_id) {
        $pdo->beginTransaction();
        try {
            if (self::stokYonetimiAktifMi($pdo)) {
                self::stokIadeEt($pdo, $kayit_id);
            }
            $res = $pdo->prepare("UPDATE records SET aktif=0 WHERE id=? AND arac_id=?")->execute([$kayit_id, $arac_id]);
            $pdo->commit();
            return $res;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function aracKayitlari($pdo, $arac_id) {
        $stmt = $pdo->prepare("
            SELECT lk.*,u.urun_adi,u.urun_kodu, u.birim, k.ad_soyad
            FROM records lk
            JOIN products u ON lk.urun_id=u.id
            LEFT JOIN users k ON lk.olusturan_id=k.id
            WHERE lk.arac_id=? AND lk.aktif=1
            ORDER BY lk.tarih DESC, lk.olusturma_tarihi DESC
        ");
        $stmt->execute([$arac_id]);
        return $stmt->fetchAll();
    }

    public static function tesisYagEkle($pdo, $tesis_id, $urun_id, $miktar, $tarih, $aciklama, $olusturan_id) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO records (kayit_turu, tesis_id, urun_id, miktar, tarih, aciklama, olusturan_id) VALUES ('tesis', ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$tesis_id, $urun_id, $miktar, $tarih, $aciklama ?: null, $olusturan_id]);
            $kayit_id = $pdo->lastInsertId();
            
            if (self::stokYonetimiAktifMi($pdo)) {
                self::stokCikisYap($pdo, $urun_id, $miktar, $kayit_id, 'Tesis Çıkışı', $olusturan_id);
            }
            $pdo->commit();
            return $kayit_id;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function tesisKayitBul($pdo, $kayit_id, $tesis_id) {
        $stmt = $pdo->prepare('SELECT lk.*,u.urun_kodu, u.urun_adi, u.birim FROM records lk JOIN products u ON lk.urun_id=u.id WHERE lk.id=? AND lk.tesis_id=? AND lk.aktif=1');
        $stmt->execute([$kayit_id, $tesis_id]);
        return $stmt->fetch();
    }
    
    public static function tesisKayitSilBul($pdo, $kayit_id, $tesis_id) {
        $stmt = $pdo->prepare('SELECT lk.*,u.urun_kodu, u.urun_adi, u.birim FROM records lk JOIN products u ON lk.urun_id=u.id WHERE lk.id=? AND lk.tesis_id=?');
        $stmt->execute([$kayit_id, $tesis_id]);
        return $stmt->fetch();
    }

    public static function tesisKayitSil($pdo, $kayit_id, $tesis_id) {
        $pdo->beginTransaction();
        try {
            if (self::stokYonetimiAktifMi($pdo)) {
                self::stokIadeEt($pdo, $kayit_id);
            }
            $res = $pdo->prepare("UPDATE records SET aktif=0 WHERE id=? AND tesis_id=?")->execute([$kayit_id, $tesis_id]);
            $pdo->commit();
            return $res;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function tesisKayitlari($pdo, $tesis_id) {
        $stmt = $pdo->prepare("
            SELECT lk.*, u.urun_adi, u.urun_kodu, u.birim, k.ad_soyad
            FROM records lk
            JOIN products u ON lk.urun_id = u.id
            LEFT JOIN users k ON lk.olusturan_id = k.id
            WHERE lk.tesis_id = ? AND lk.aktif = 1
            ORDER BY lk.tarih DESC, lk.olusturma_tarihi DESC
        ");
        $stmt->execute([$tesis_id]);
        return $stmt->fetchAll();
    }

    public static function islendiMi($pdo, $id) {
        $stmt = $pdo->prepare("SELECT islendi FROM records WHERE id=?");
        $stmt->execute([$id]);
        return $stmt->fetchColumn();
    }

    public static function genelKayitBul($pdo, $id) {
        $stmt = $pdo->prepare('
            SELECT lk.*, u.urun_kodu, u.urun_adi, u.birim, a.plaka, t.firma_adi
            FROM records lk
            JOIN products u ON lk.urun_id=u.id
            LEFT JOIN vehicles a ON lk.arac_id=a.id
            LEFT JOIN facilities t ON lk.tesis_id=t.id
            WHERE lk.id=?
        ');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function aktifGenelKayitBul($pdo, $id) {
        $stmt = $pdo->prepare('
            SELECT lk.*, u.urun_kodu, u.urun_adi, u.birim, a.plaka, t.firma_adi
            FROM records lk
            JOIN products u ON lk.urun_id=u.id
            LEFT JOIN vehicles a ON lk.arac_id=a.id
            LEFT JOIN facilities t ON lk.tesis_id=t.id
            WHERE lk.id=? AND lk.aktif=1
        ');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function islendiYap($pdo, $id, $ku_id) {
        return $pdo->prepare("UPDATE records SET islendi=1, islendi_tarih=NOW(), islendi_kullanici_id=? WHERE id=?")
            ->execute([$ku_id, $id]);
    }

    public static function islendiIptal($pdo, $id) {
        return $pdo->prepare("UPDATE records SET islendi=0, islendi_tarih=NULL, islendi_kullanici_id=NULL WHERE id=?")
            ->execute([$id]);
    }

    public static function aramaSartlariniOlustur($filtreler) {
        $where = ["lk.aktif = 1"];
        $params = [];

        if (!empty($filtreler['tarih_bas'])) { $where[] = "lk.tarih >= ?"; $params[] = $filtreler['tarih_bas']; }
        if (!empty($filtreler['tarih_bit'])) { $where[] = "lk.tarih <= ?"; $params[] = $filtreler['tarih_bit']; }
        if (!empty($filtreler['arac_id'])) { $where[] = "lk.arac_id = ?"; $params[] = $filtreler['arac_id']; }
        if (!empty($filtreler['tesis_id'])) { $where[] = "lk.tesis_id = ?"; $params[] = $filtreler['tesis_id']; }
        if (!empty($filtreler['urun_id'])) { $where[] = "lk.urun_id = ?"; $params[] = $filtreler['urun_id']; }
        $tur = $filtreler['tur'] ?? '';
        if ($tur === 'arac') { $where[] = "lk.kayit_turu = 'arac'"; }
        elseif ($tur === 'tesis') { $where[] = "lk.kayit_turu = 'tesis'"; }
        $islendi = $filtreler['islendi'] ?? '';
        if ($islendi === 'islendi') { $where[] = "lk.islendi = 1"; }
        elseif ($islendi === 'islenmedi') { $where[] = "lk.islendi = 0"; }

        return ['where' => implode(" AND ", $where), 'params' => $params];
    }

    public static function istatistikGetir($pdo, $sartlar) {
        $stmt = $pdo->prepare("SELECT COUNT(*), COUNT(CASE WHEN lk.kayit_turu='arac' THEN 1 END), COUNT(CASE WHEN lk.kayit_turu='tesis' THEN 1 END), COALESCE(SUM(lk.miktar),0) FROM records lk LEFT JOIN products u ON lk.urun_id=u.id WHERE " . $sartlar['where']);
        $stmt->execute($sartlar['params']);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return [
            'toplam' => (int)$row[0],
            'arac' => (int)$row[1],
            'tesis' => (int)$row[2],
            'litre' => (float)$row[3]
        ];
    }

    public static function listeSayfalamali($pdo, $sartlar, $offset, $limit, $sirala = 'desc') {
        $order = $sirala === 'asc'
            ? 'ORDER BY lk.tarih ASC, lk.olusturma_tarihi ASC'
            : 'ORDER BY lk.tarih DESC, lk.olusturma_tarihi DESC';
        $stmt = $pdo->prepare("
            SELECT lk.*, u.urun_adi, u.urun_kodu, u.birim, a.plaka, a.marka_model, at.tur_adi AS arac_turu,
                   t.firma_adi, k.ad_soyad, ik.ad_soyad AS islendi_ad_soyad
            FROM records lk
            LEFT JOIN products u ON lk.urun_id = u.id
            LEFT JOIN vehicles a ON lk.arac_id = a.id
            LEFT JOIN vehicles_type at ON a.arac_turu_id = at.id
            LEFT JOIN facilities t ON lk.tesis_id = t.id
            LEFT JOIN users k ON lk.olusturan_id = k.id
            LEFT JOIN users ik ON lk.islendi_kullanici_id = ik.id
            WHERE " . $sartlar['where'] . "
            $order
            LIMIT " . (int)$limit . " OFFSET " . (int)$offset
        );
        $stmt->execute($sartlar['params']);
        return $stmt->fetchAll();
    }

    public static function bekleyenSayisi($pdo) {
        return (int)$pdo->query("SELECT COUNT(*) FROM records WHERE aktif=1 AND islendi=0")->fetchColumn();
    }

    public static function islenenSayisi($pdo) {
        return (int)$pdo->query("SELECT COUNT(*) FROM records WHERE aktif=1 AND islendi=1")->fetchColumn();
    }

    public static function raporOzetUrunBazli($pdo, $where_sql, $params) {
        $stmt = $pdo->prepare("
            SELECT lk.urun_id, u.urun_kodu, u.urun_adi, u.birim,
                   COUNT(*) AS adet, COALESCE(SUM(lk.miktar), 0) AS toplam
            FROM records lk
            JOIN products u ON lk.urun_id = u.id
            WHERE $where_sql
            GROUP BY lk.urun_id, u.urun_kodu, u.urun_adi
            ORDER BY toplam DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function raporToplamKayit($pdo, $where_sql, $params) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM records lk WHERE $where_sql");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function raporDetayliKayitlar($pdo, $where_sql, $params, $limit = null, $offset = null) {
        $sql = "
            SELECT lk.*, u.urun_adi, u.urun_kodu, u.birim, a.plaka, a.marka_model, at.tur_adi AS arac_turu,
               t.firma_adi, k.ad_soyad
            FROM records lk
            JOIN products u  ON lk.urun_id    = u.id
            LEFT JOIN vehicles  a  ON lk.arac_id    = a.id
            LEFT JOIN vehicles_type at ON a.arac_turu_id = at.id
            LEFT JOIN facilities t  ON lk.tesis_id   = t.id
            LEFT JOIN users  k  ON lk.olusturan_id = k.id
            WHERE $where_sql
            ORDER BY lk.tarih DESC, lk.olusturma_tarihi DESC
        ";
        if ($limit !== null && $offset !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
