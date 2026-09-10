<?php
/*
 * Project Industry - Vehicle and Facility product tracking management system
 * Copyright (C) 2026 Awosk
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

require_once __DIR__ . '/Islem.php';

class Fis {
    /**
     * Tek ürünlü fiş ekleme (geriye dönük uyumluluk).
     */
    public static function ekle($pdo, $kayit_turu, $hedef_id, $urun_id, $miktar, $aciklama, $yag_bakimi, $mevcut_km, $olusturan_id) {
        return self::ekleCoklu($pdo, $kayit_turu, $hedef_id, [
            ['urun_id' => $urun_id, 'miktar' => $miktar]
        ], $aciklama, $yag_bakimi, $mevcut_km, $olusturan_id);
    }

    /**
     * Çoklu ürün içeren fiş (çıkış talebi) oluşturur.
     */
    public static function ekleCoklu($pdo, $kayit_turu, $hedef_id, array $kalemler, $aciklama, $yag_bakimi, $mevcut_km, $olusturan_id) {
        $gecerli_kalemler = [];
        foreach ($kalemler as $k) {
            $u_id = (int)($k['urun_id'] ?? 0);
            $mikt = (float)($k['miktar'] ?? 0);
            if ($u_id > 0 && $mikt > 0) {
                $gecerli_kalemler[] = ['urun_id' => $u_id, 'miktar' => $mikt];
            }
        }

        if (empty($gecerli_kalemler)) {
            return false;
        }

        $arac_id  = ($kayit_turu === 'arac') ? (int)$hedef_id : null;
        $tesis_id = ($kayit_turu === 'tesis') ? (int)$hedef_id : null;
        $yag_b    = ($kayit_turu === 'arac' && $yag_bakimi) ? 1 : 0;
        $km       = ($kayit_turu === 'arac' && $yag_b && $mevcut_km) ? (int)$mevcut_km : null;

        $ilk_kalem = $gecerli_kalemler[0];

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO slips (kayit_turu, arac_id, tesis_id, urun_id, miktar, aciklama, yag_bakimi, mevcut_km, durum, olusturan_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'bekliyor', ?)
            ");
            $stmt->execute([
                $kayit_turu,
                $arac_id,
                $tesis_id,
                $ilk_kalem['urun_id'],
                $ilk_kalem['miktar'],
                $aciklama ?: null,
                $yag_b,
                $km,
                $olusturan_id
            ]);
            $slip_id = $pdo->lastInsertId();

            $item_stmt = $pdo->prepare("INSERT INTO slip_items (slip_id, urun_id, miktar) VALUES (?, ?, ?)");
            foreach ($gecerli_kalemler as $item) {
                $item_stmt->execute([$slip_id, $item['urun_id'], $item['miktar']]);
            }

            $pdo->commit();
            return $slip_id;
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Bir fişe ait ürün kalemlerini getirir.
     */
    public static function kalemleriGetir($pdo, $slip_id) {
        $stmt = $pdo->prepare("
            SELECT si.*, 
                   COALESCE(u.urun_kodu, '—') AS urun_kodu, 
                   COALESCE(u.urun_adi, '[Silinmiş Ürün]') AS urun_adi, 
                   COALESCE(u.birim, 'LT') AS birim,
                   r.aktif AS kayit_aktif
            FROM slip_items si
            LEFT JOIN products u ON si.urun_id = u.id
            LEFT JOIN records r ON si.kayit_id = r.id
            WHERE si.slip_id = ?
            ORDER BY si.id ASC
        ");
        $stmt->execute([$slip_id]);
        return $stmt->fetchAll();
    }

    /**
     * Tekil fiş detayını tüm kalemleriyle birlikte getirir.
     */
    public static function bul($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT s.*,
                   a.plaka, a.marka_model,
                   t.firma_adi,
                   k1.ad_soyad AS olusturan_ad,
                   k2.ad_soyad AS onaylayan_ad,
                   k3.ad_soyad AS iptal_eden_ad
            FROM slips s
            LEFT JOIN vehicles a ON s.arac_id = a.id
            LEFT JOIN facilities t ON s.tesis_id = t.id
            LEFT JOIN users k1 ON s.olusturan_id = k1.id
            LEFT JOIN users k2 ON s.onaylayan_id = k2.id
            LEFT JOIN users k3 ON s.iptal_eden_id = k3.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        $slip = $stmt->fetch();

        if ($slip) {
            $slip['kalemler'] = self::kalemleriGetir($pdo, $id);
            
            // Eğer kalemler tablosunda yoksa (eski veri kalmışsa) slips tablosundaki veriyi kalem yap
            if (empty($slip['kalemler']) && !empty($slip['urun_id'])) {
                $slip['kalemler'] = [[
                    'id' => 0,
                    'slip_id' => $slip['id'],
                    'urun_id' => $slip['urun_id'],
                    'miktar' => $slip['miktar'],
                    'kayit_id' => $slip['kayit_id'],
                    'kayit_aktif' => 1,
                    'urun_kodu' => '—',
                    'urun_adi' => '[Ürün]',
                    'birim' => 'LT'
                ]];
            }

            // Kayıt silinmişlik kontrolü
            $tum_kayitlar_silindi = false;
            if ($slip['durum'] === 'onaylandi') {
                $tum_kayitlar_silindi = true;
                if (!empty($slip['kalemler'])) {
                    foreach ($slip['kalemler'] as $item) {
                        if ($item['kayit_id'] && isset($item['kayit_aktif']) && (int)$item['kayit_aktif'] === 1) {
                            $tum_kayitlar_silindi = false;
                            break;
                        }
                    }
                }
            }
            $slip['kayit_silinmis'] = $tum_kayitlar_silindi;
        }

        return $slip;
    }

    /**
     * Fişleri kalemleriyle birlikte listeler.
     */
    public static function listele($pdo, $durum = null, $limit = 500) {
        $sql = "
            SELECT s.*,
                   a.plaka, a.marka_model,
                   t.firma_adi,
                   k1.ad_soyad AS olusturan_ad,
                   k2.ad_soyad AS onaylayan_ad,
                   k3.ad_soyad AS iptal_eden_ad
            FROM slips s
            LEFT JOIN vehicles a ON s.arac_id = a.id
            LEFT JOIN facilities t ON s.tesis_id = t.id
            LEFT JOIN users k1 ON s.olusturan_id = k1.id
            LEFT JOIN users k2 ON s.onaylayan_id = k2.id
            LEFT JOIN users k3 ON s.iptal_eden_id = k3.id
        ";
        $params = [];

        if ($durum && in_array($durum, ['bekliyor', 'onaylandi', 'iptal'])) {
            $sql .= " WHERE s.durum = ?";
            $params[] = $durum;
        }

        $sql .= " ORDER BY s.olusturma_tarihi DESC LIMIT " . (int)$limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $slips = $stmt->fetchAll();

        if (empty($slips)) {
            return [];
        }

        $slip_ids = array_column($slips, 'id');
        $in_clause = implode(',', array_fill(0, count($slip_ids), '?'));

        $items_stmt = $pdo->prepare("
            SELECT si.*, 
                   COALESCE(u.urun_kodu, '—') AS urun_kodu, 
                   COALESCE(u.urun_adi, '[Silinmiş Ürün]') AS urun_adi, 
                   COALESCE(u.birim, 'LT') AS birim,
                   r.aktif AS kayit_aktif
            FROM slip_items si
            LEFT JOIN products u ON si.urun_id = u.id
            LEFT JOIN records r ON si.kayit_id = r.id
            WHERE si.slip_id IN ($in_clause)
            ORDER BY si.id ASC
        ");
        $items_stmt->execute($slip_ids);
        $tum_kalemler = $items_stmt->fetchAll();

        $kalem_map = [];
        foreach ($tum_kalemler as $k) {
            $kalem_map[$k['slip_id']][] = $k;
        }

        foreach ($slips as &$s) {
            $s['kalemler'] = $kalem_map[$s['id']] ?? [];

            $tum_kayitlar_silindi = false;
            if ($s['durum'] === 'onaylandi') {
                $tum_kayitlar_silindi = true;
                if (!empty($s['kalemler'])) {
                    foreach ($s['kalemler'] as $item) {
                        if ($item['kayit_id'] && isset($item['kayit_aktif']) && (int)$item['kayit_aktif'] === 1) {
                            $tum_kayitlar_silindi = false;
                            break;
                        }
                    }
                }
            }
            $s['kayit_silinmis'] = $tum_kayitlar_silindi;
        }

        return $slips;
    }

    /**
     * Bekleyen fiş sayısını döner.
     */
    public static function bekleyenSayisi($pdo): int {
        return (int)$pdo->query("SELECT COUNT(*) FROM slips WHERE durum = 'bekliyor'")->fetchColumn();
    }

    /**
     * En son bekleyen fiş ID'sini döner.
     */
    public static function sonBekleyenId($pdo): int {
        return (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM slips WHERE durum = 'bekliyor'")->fetchColumn();
    }

    /**
     * Fişi onaylar:
     * - Fişteki her ürün için records tablosuna çıkış işler ve stok düşer
     * - Her kalem için oluşan kayit_id'yi slip_items tablosuna işler
     * - Fiş durumunu 'onaylandi' yapar.
     */
    public static function onayla($pdo, $id, $onaylayan_id) {
        $slip = self::bul($pdo, $id);
        if (!$slip || $slip['durum'] !== 'bekliyor' || empty($slip['kalemler'])) {
            return false;
        }

        $bugun = date('Y-m-d');
        $not_ana = $slip['aciklama'] ? '[Fiş #' . $slip['id'] . '] ' . $slip['aciklama'] : '[Fiş #' . $slip['id'] . '] Çıkış yapıldı';

        $ilk_kayit_id = null;
        $update_item_stmt = $pdo->prepare("UPDATE slip_items SET kayit_id = ? WHERE id = ?");

        foreach ($slip['kalemler'] as $item) {
            if ($slip['kayit_turu'] === 'arac') {
                $kayit_id = Islem::aracYagEkle(
                    $pdo,
                    $slip['arac_id'],
                    $item['urun_id'],
                    $item['miktar'],
                    $bugun,
                    $not_ana,
                    $slip['yag_bakimi'],
                    $slip['mevcut_km'],
                    $onaylayan_id
                );
            } else {
                $kayit_id = Islem::tesisYagEkle(
                    $pdo,
                    $slip['tesis_id'],
                    $item['urun_id'],
                    $item['miktar'],
                    $bugun,
                    $not_ana,
                    $onaylayan_id
                );
            }

            if (!$ilk_kayit_id) {
                $ilk_kayit_id = $kayit_id;
            }

            if ($item['id'] > 0) {
                $update_item_stmt->execute([$kayit_id, $item['id']]);
            }
        }

        $stmt = $pdo->prepare("
            UPDATE slips 
            SET durum = 'onaylandi', onaylayan_id = ?, onay_tarihi = NOW(), kayit_id = ? 
            WHERE id = ?
        ");
        $stmt->execute([$onaylayan_id, $ilk_kayit_id, $id]);

        return $ilk_kayit_id;
    }

    /**
     * Fişi iptal eder. Sadece 'bekliyor' durumundaki fişler iptal edilebilir.
     */
    public static function iptalEt($pdo, $id, $iptal_eden_id): bool {
        $slip = self::bul($pdo, $id);
        if (!$slip || $slip['durum'] !== 'bekliyor') {
            return false;
        }

        $stmt = $pdo->prepare("
            UPDATE slips 
            SET durum = 'iptal', iptal_eden_id = ?, iptal_tarihi = NOW() 
            WHERE id = ?
        ");
        return $stmt->execute([$iptal_eden_id, $id]);
    }

    /**
     * İptal edilmiş veya tüm kayıtları silinmiş fişi kalıcı olarak siler.
     */
    public static function sil($pdo, $id): bool {
        $slip = self::bul($pdo, $id);
        if (!$slip) {
            return false;
        }

        if ($slip['durum'] !== 'iptal' && empty($slip['kayit_silinmis'])) {
            return false;
        }

        $stmt = $pdo->prepare("DELETE FROM slips WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
