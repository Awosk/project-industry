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
     * Yeni fiş (çıkış talebi) oluşturur.
     */
    public static function ekle($pdo, $kayit_turu, $hedef_id, $urun_id, $miktar, $aciklama, $yag_bakimi, $mevcut_km, $olusturan_id) {
        $arac_id  = ($kayit_turu === 'arac') ? (int)$hedef_id : null;
        $tesis_id = ($kayit_turu === 'tesis') ? (int)$hedef_id : null;
        $yag_b    = ($kayit_turu === 'arac' && $yag_bakimi) ? 1 : 0;
        $km       = ($kayit_turu === 'arac' && $yag_b && $mevcut_km) ? (int)$mevcut_km : null;

        $stmt = $pdo->prepare("
            INSERT INTO slips (kayit_turu, arac_id, tesis_id, urun_id, miktar, aciklama, yag_bakimi, mevcut_km, durum, olusturan_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'bekliyor', ?)
        ");
        $stmt->execute([
            $kayit_turu,
            $arac_id,
            $tesis_id,
            $urun_id,
            $miktar,
            $aciklama ?: null,
            $yag_b,
            $km,
            $olusturan_id
        ]);
        return $pdo->lastInsertId();
    }

    /**
     * Tekil fiş detayını tüm ilişkili bilgilerle getirir.
     */
    public static function bul($pdo, $id) {
        $stmt = $pdo->prepare("
            SELECT s.*,
                   u.urun_kodu, u.urun_adi, u.birim,
                   a.plaka, a.marka_model,
                   t.firma_adi,
                   k1.ad_soyad AS olusturan_ad,
                   k2.ad_soyad AS onaylayan_ad,
                   k3.ad_soyad AS iptal_eden_ad,
                   r.id AS bagli_kayit_id,
                   r.aktif AS kayit_aktif
            FROM slips s
            JOIN products u ON s.urun_id = u.id
            LEFT JOIN vehicles a ON s.arac_id = a.id
            LEFT JOIN facilities t ON s.tesis_id = t.id
            LEFT JOIN users k1 ON s.olusturan_id = k1.id
            LEFT JOIN users k2 ON s.onaylayan_id = k2.id
            LEFT JOIN users k3 ON s.iptal_eden_id = k3.id
            LEFT JOIN records r ON s.kayit_id = r.id
            WHERE s.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Fişleri listeler (opsiyonel durum filtresi ile).
     */
    public static function listele($pdo, $durum = null, $limit = 500) {
        $sql = "
            SELECT s.*,
                   u.urun_kodu, u.urun_adi, u.birim,
                   a.plaka, a.marka_model,
                   t.firma_adi,
                   k1.ad_soyad AS olusturan_ad,
                   k2.ad_soyad AS onaylayan_ad,
                   k3.ad_soyad AS iptal_eden_ad,
                   r.id AS bagli_kayit_id,
                   r.aktif AS kayit_aktif
            FROM slips s
            JOIN products u ON s.urun_id = u.id
            LEFT JOIN vehicles a ON s.arac_id = a.id
            LEFT JOIN facilities t ON s.tesis_id = t.id
            LEFT JOIN users k1 ON s.olusturan_id = k1.id
            LEFT JOIN users k2 ON s.onaylayan_id = k2.id
            LEFT JOIN users k3 ON s.iptal_eden_id = k3.id
            LEFT JOIN records r ON s.kayit_id = r.id
        ";
        $params = [];

        if ($durum && in_array($durum, ['bekliyor', 'onaylandi', 'iptal'])) {
            $sql .= " WHERE s.durum = ?";
            $params[] = $durum;
        }

        $sql .= " ORDER BY s.olusturma_tarihi DESC LIMIT " . (int)$limit;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Bekleyen fiş sayısını döner (Rozet ve bildirimler için).
     */
    public static function bekleyenSayisi($pdo): int {
        return (int)$pdo->query("SELECT COUNT(*) FROM slips WHERE durum = 'bekliyor'")->fetchColumn();
    }

    /**
     * En son bekleyen fiş ID'sini döner (Polling için).
     */
    public static function sonBekleyenId($pdo): int {
        return (int)$pdo->query("SELECT COALESCE(MAX(id), 0) FROM slips WHERE durum = 'bekliyor'")->fetchColumn();
    }

    /**
     * Fişi onaylar:
     * - records tablosuna ürün çıkışı ekler (stok düşüşü otomatik tetiklenir)
     * - fiş durumunu 'onaylandi' yapar
     * - oluşan işlem kaydının id'sini ilişkilendirir.
     */
    public static function onayla($pdo, $id, $onaylayan_id) {
        $slip = self::bul($pdo, $id);
        if (!$slip || $slip['durum'] !== 'bekliyor') {
            return false;
        }

        $bugun = date('Y-m-d');
        $not   = $slip['aciklama'] ? '[Fiş #' . $slip['id'] . '] ' . $slip['aciklama'] : '[Fiş #' . $slip['id'] . '] Çıkış yapıldı';

        if ($slip['kayit_turu'] === 'arac') {
            $kayit_id = Islem::aracYagEkle(
                $pdo,
                $slip['arac_id'],
                $slip['urun_id'],
                $slip['miktar'],
                $bugun,
                $not,
                $slip['yag_bakimi'],
                $slip['mevcut_km'],
                $onaylayan_id
            );
        } else {
            $kayit_id = Islem::tesisYagEkle(
                $pdo,
                $slip['tesis_id'],
                $slip['urun_id'],
                $slip['miktar'],
                $bugun,
                $not,
                $onaylayan_id
            );
        }

        if ($kayit_id) {
            $stmt = $pdo->prepare("
                UPDATE slips 
                SET durum = 'onaylandi', onaylayan_id = ?, onay_tarihi = NOW(), kayit_id = ? 
                WHERE id = ?
            ");
            $stmt->execute([$onaylayan_id, $kayit_id, $id]);
            return $kayit_id;
        }

        return false;
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
     * İptal edilmiş veya bağlı kaydı silinmiş fişi kalıcı olarak siler.
     */
    public static function sil($pdo, $id): bool {
        $slip = self::bul($pdo, $id);
        if (!$slip) {
            return false;
        }

        $kayit_silinmis = ($slip['durum'] === 'onaylandi' && $slip['kayit_id'] && ($slip['kayit_aktif'] === null || (int)$slip['kayit_aktif'] === 0));

        if ($slip['durum'] !== 'iptal' && !$kayit_silinmis) {
            return false;
        }

        $stmt = $pdo->prepare("DELETE FROM slips WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
