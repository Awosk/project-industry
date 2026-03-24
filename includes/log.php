<?php
// =====================================================
// LOG SİSTEMİ — Ana ve Lite için ortak
// =====================================================

function logYaz($pdo, $aksiyon, $modul, $aciklama, $kayit_id = null, $eski = null, $yeni = null, $sistem = 'ana') {
    $ku = mevcutKullanici();
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '?';
    // Birden fazla IP varsa ilkini al
    $ip = trim(explode(',', $ip)[0]);

    try {
        $stmt = $pdo->prepare("
            INSERT INTO sistem_loglari
                (kullanici_id, kullanici_adi, ad_soyad, sistem, aksiyon, modul, kayit_id, aciklama, eski_deger, yeni_deger, ip_adresi)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $ku['id'],
            $ku['adi'],
            $ku['ad_soyad'],
            $sistem,
            $aksiyon,
            $modul,
            $kayit_id,
            $aciklama,
            $eski ? json_encode($eski, JSON_UNESCAPED_UNICODE) : null,
            $yeni  ? json_encode($yeni,  JSON_UNESCAPED_UNICODE) : null,
            $ip,
        ]);
    } catch (Exception $e) {
        // Log yazılamaması ana işlemi durdurmasın
        error_log('Log yazma hatası: ' . $e->getMessage());
    }
}

// Giriş/çıkış logları için oturum olmadan da çalışır
function logGiris($pdo, $kullanici, $sistem = 'ana') {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
        ?? $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? $_SERVER['REMOTE_ADDR']
        ?? '?';
    $ip = trim(explode(',', $ip)[0]);
    try {
        $pdo->prepare("
            INSERT INTO sistem_loglari (kullanici_id, kullanici_adi, ad_soyad, sistem, aksiyon, modul, aciklama, ip_adresi)
            VALUES (?, ?, ?, ?, 'giris', 'auth', 'Sisteme giriş yapıldı', ?)
        ")->execute([$kullanici['id'], $kullanici['kullanici_adi'], $kullanici['ad_soyad'], $sistem, $ip]);
    } catch (Exception $e) { error_log('Log hatası: ' . $e->getMessage()); }
}

function logCikis($pdo, $sistem = 'ana') {
    $ku = mevcutKullanici();
    if (!$ku['id']) return;
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '?';
    $ip = trim(explode(',', $ip)[0]);
    try {
        $pdo->prepare("
            INSERT INTO sistem_loglari (kullanici_id, kullanici_adi, ad_soyad, sistem, aksiyon, modul, aciklama, ip_adresi)
            VALUES (?, ?, ?, ?, 'cikis', 'auth', 'Sistemden çıkış yapıldı', ?)
        ")->execute([$ku['id'], $ku['adi'], $ku['ad_soyad'], $sistem, $ip]);
    } catch (Exception $e) { error_log('Log hatası: ' . $e->getMessage()); }
}
