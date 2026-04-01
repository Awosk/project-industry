<?php
/*
 * Project Industry - Vehicle and Facility product tracking management system
 * Copyright (C) 2026 Awosk
 */

class Kullanici {
    public static function bul($pdo, $id) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function bulAktif($pdo, $id) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id=? AND aktif=1');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public static function bulKullaniciAdi($pdo, $kullanici_adi) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE kullanici_adi=?');
        $stmt->execute([$kullanici_adi]);
        return $stmt->fetch();
    }

    public static function listele($pdo) {
        return $pdo->query("SELECT * FROM users WHERE aktif=1 ORDER BY ad_soyad")->fetchAll();
    }

    public static function tumuSecenekIcin($pdo) {
        return $pdo->query("SELECT id, ad_soyad FROM users WHERE aktif=1 ORDER BY ad_soyad")->fetchAll();
    }

    public static function ekle($pdo, $ad_soyad, $kullanici_adi, $sifre_hash, $rol, $email) {
        $stmt = $pdo->prepare("INSERT INTO users (ad_soyad, kullanici_adi, sifre, rol, email) VALUES (?,?,?,?,?)");
        $stmt->execute([$ad_soyad, $kullanici_adi, $sifre_hash, $rol, $email]);
        return $pdo->lastInsertId();
    }

    public static function reaktifEt($pdo, $id, $ad_soyad, $sifre_hash, $rol) {
        return $pdo->prepare("UPDATE users SET ad_soyad=?, sifre=?, rol=?, aktif=1 WHERE id=?")
            ->execute([$ad_soyad, $sifre_hash, $rol, $id]);
    }

    public static function rolGuncelle($pdo, $id, $rol) {
        return $pdo->prepare("UPDATE users SET rol=? WHERE id=?")->execute([$rol, $id]);
    }

    public static function sifreGuncelle($pdo, $id, $sifre_hash) {
        return $pdo->prepare("UPDATE users SET sifre=? WHERE id=?")->execute([$sifre_hash, $id]);
    }

    public static function emailGuncelle($pdo, $id, $email) {
        return $pdo->prepare("UPDATE users SET email=? WHERE id=?")->execute([$email, $id]);
    }

    public static function temaGuncelle($pdo, $id, $tema) {
        return $pdo->prepare("UPDATE users SET tema=? WHERE id=?")->execute([$tema, $id]);
    }

    public static function sil($pdo, $id) {
        return $pdo->prepare("UPDATE users SET aktif=0 WHERE id=?")->execute([$id]);
    }
}
