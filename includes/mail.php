<?php
/*
 * Project Oil - Vehicle and Facility Industrial Oil Tracking System
 * Copyright (C) 2026 Awosk
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

// =====================================================
// MAIL SERVİSİ — PHPMailer wrapper
// =====================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';
require_once __DIR__ . '/phpmailer/Exception.php';

/**
 * Sistem ayarlarından SMTP konfigürasyonunu çeker.
 */
function smtpAyarlariGetir($pdo): array {
    try {
        $rows = $pdo->query("SELECT anahtar, deger FROM sistem_ayarlar WHERE anahtar LIKE 'smtp_%'")->fetchAll();
        $ayarlar = [];
        foreach ($rows as $row) {
            $ayarlar[$row['anahtar']] = $row['deger'];
        }
        return $ayarlar;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * SMTP aktif mi?
 */
function smtpAktifMi($pdo): bool {
    try {
        $deger = $pdo->query("SELECT deger FROM sistem_ayarlar WHERE anahtar = 'smtp_aktif'")->fetchColumn();
        return $deger === '1';
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Mail gönder.
 * @return array ['ok' => bool, 'hata' => string]
 */
function mailGonder($pdo, string $alici_email, string $alici_ad, string $konu, string $icerik_html): array {
    if (!smtpAktifMi($pdo)) {
        return ['ok' => false, 'hata' => 'SMTP aktif değil'];
    }

    $ayarlar = smtpAyarlariGetir($pdo);

    if (empty($ayarlar['smtp_host']) || empty($ayarlar['smtp_kullanici'])) {
        return ['ok' => false, 'hata' => 'SMTP ayarları eksik'];
    }

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = $ayarlar['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $ayarlar['smtp_kullanici'];
        $mail->Password   = $ayarlar['smtp_sifre'];
        $mail->SMTPSecure = $ayarlar['smtp_sifrelem'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = (int)($ayarlar['smtp_port'] ?? 587);
        $mail->CharSet    = 'UTF-8';

        $gonderen_ad    = $ayarlar['smtp_ad']       ?: SITE_ADI;
        $gonderen_email = $ayarlar['smtp_gonderen'] ?: $ayarlar['smtp_kullanici'];

        $mail->setFrom($gonderen_email, $gonderen_ad);
        $mail->addAddress($alici_email, $alici_ad);

        $mail->isHTML(true);
        $mail->Subject = $konu;
        $mail->Body    = mailSablonu($konu, $icerik_html);
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $icerik_html));

        $mail->send();
        return ['ok' => true, 'hata' => ''];

    } catch (MailException $e) {
        error_log('Mail gönderme hatası: ' . $e->getMessage());
        return ['ok' => false, 'hata' => $e->getMessage()];
    }
}

/**
 * Şifre sıfırlama maili gönder.
 */
function sifreSifirlamaMailiGonder($pdo, array $kullanici, string $token): bool {
    $link    = ROOT_URL . 'sifre_sifirlama.php?token=' . $token;
    $icerik  = '
        <p>Merhaba <strong>' . htmlspecialchars($kullanici['ad_soyad']) . '</strong>,</p>
        <p>' . SITE_ADI . ' hesabınız için şifre sıfırlama talebinde bulunuldu.</p>
        <p style="margin:24px 0;">
            <a href="' . $link . '" style="background:#1e4d6b;color:#fff;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-block;">
                🔑 Şifremi Sıfırla
            </a>
        </p>
        <p style="color:#666;font-size:13px;">Bu link <strong>30 dakika</strong> geçerlidir. Eğer bu talebi siz yapmadıysanız bu maili görmezden gelebilirsiniz.</p>
        <p style="color:#666;font-size:12px;word-break:break-all;">Link çalışmıyorsa kopyalayıp tarayıcınıza yapıştırın:<br>' . $link . '</p>
    ';

    $sonuc = mailGonder($pdo, $kullanici['email'], $kullanici['ad_soyad'], '🔑 Şifre Sıfırlama — ' . SITE_ADI, $icerik);
    return $sonuc['ok'];
}

/**
 * Admin bildirim maili gönder.
 */
function adminBildirimGonder($pdo, string $aksiyon, string $modul, string $aciklama, ?array $kullanici_bilgi = null): void {
    if (!smtpAktifMi($pdo)) return;

    try {
        // Bu modül+aksiyon için bildirim açık olan adminleri bul
        $stmt = $pdo->prepare("
            SELECT k.email, k.ad_soyad
            FROM admin_bildirim_filtreler f
            JOIN kullanicilar k ON f.kullanici_id = k.id
            WHERE f.aktif = 1
              AND f.modul = ?
              AND f.aksiyon = ?
              AND k.aktif = 1
              AND k.email IS NOT NULL
              AND k.email != ''
        ");
        $stmt->execute([$modul, $aksiyon]);
        $alicilar = $stmt->fetchAll();

        if (empty($alicilar)) return;

        $aksiyon_etiket = [
            'ekle'     => '➕ Ekleme',
            'sil'      => '🗑️ Silme',
            'guncelle' => '✏️ Güncelleme',
            'giris'    => '🔐 Giriş',
            'cikis'    => '🚪 Çıkış',
        ][$aksiyon] ?? $aksiyon;

        $modul_etiket = [
            'arac'       => '🚗 Araç',
            'tesis'      => '🏭 Tesis',
            'arac_kayit' => '🛢️ Araç Yağ Kaydı',
            'tesis_kayit'=> '🛢️ Tesis Yağ Kaydı',
            'urun'       => '📦 Ürün',
            'kullanici'  => '👤 Kullanıcı',
            'auth'       => '🔐 Oturum',
            'sistem'     => '⚙️ Sistem',
            'islendi'    => '✅ Depoya İşlendi',
        ][$modul] ?? $modul;

        $yapan = $kullanici_bilgi
            ? htmlspecialchars($kullanici_bilgi['ad_soyad'] ?? $kullanici_bilgi['adi'] ?? 'Bilinmiyor')
            : 'Sistem';

        $icerik = '
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <tr>
                    <td style="padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:700;width:140px;">İşlem</td>
                    <td style="padding:8px 12px;border:1px solid #e2e8f0;">' . $aksiyon_etiket . '</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:700;">Modül</td>
                    <td style="padding:8px 12px;border:1px solid #e2e8f0;">' . $modul_etiket . '</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:700;">Açıklama</td>
                    <td style="padding:8px 12px;border:1px solid #e2e8f0;">' . htmlspecialchars($aciklama) . '</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:700;">Yapan</td>
                    <td style="padding:8px 12px;border:1px solid #e2e8f0;">' . $yapan . '</td>
                </tr>
                <tr>
                    <td style="padding:8px 12px;background:#f8fafc;border:1px solid #e2e8f0;font-weight:700;">Tarih</td>
                    <td style="padding:8px 12px;border:1px solid #e2e8f0;">' . date('d.m.Y H:i:s') . '</td>
                </tr>
            </table>
        ';

        $konu = '[' . SITE_ADI . '] ' . $aksiyon_etiket . ' — ' . $modul_etiket;

        foreach ($alicilar as $alici) {
            mailGonder($pdo, $alici['email'], $alici['ad_soyad'], $konu, $icerik);
        }

    } catch (Exception $e) {
        error_log('Admin bildirim hatası: ' . $e->getMessage());
    }
}

/**
 * Mail HTML şablonu.
 */
function mailSablonu(string $baslik, string $icerik): string {
    return '<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f0f4f8;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f4f8;padding:32px 16px;">
    <tr><td align="center">
        <table width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">
            <!-- Header -->
            <tr>
                <td style="background:linear-gradient(135deg,#1e4d6b,#2980b9);border-radius:12px 12px 0 0;padding:24px 32px;text-align:center;">
                    <div style="font-size:28px;margin-bottom:8px;">🔩</div>
                    <div style="color:#fff;font-size:18px;font-weight:700;">' . SITE_ADI . '</div>
                </td>
            </tr>
            <!-- Body -->
            <tr>
                <td style="background:#fff;padding:32px;border-left:1px solid #e2e8f0;border-right:1px solid #e2e8f0;">
                    <h2 style="margin:0 0 20px;color:#1e293b;font-size:18px;">' . htmlspecialchars($baslik) . '</h2>
                    ' . $icerik . '
                </td>
            </tr>
            <!-- Footer -->
            <tr>
                <td style="background:#f8fafc;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 12px 12px;padding:16px 32px;text-align:center;">
                    <p style="margin:0;color:#94a3b8;font-size:12px;">' . SITE_ADI . ' &copy; ' . date('Y') . ' — Bu mail otomatik olarak gönderilmiştir.</p>
                </td>
            </tr>
        </table>
    </td></tr>
</table>
</body>
</html>';
}