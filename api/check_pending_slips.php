<?php
/**
 * Project Industry - Yeni Fiş Kontrolü (Polling)
 * Apache2+PHP-FPM uyumlu asenkron bildirim uç noktası
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_write_close();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Fis.php';

header('Content-Type: application/json');

$lastId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as yeni_sayisi, COALESCE(MAX(id), 0) as son_id FROM slips WHERE id > ? AND durum = 'bekliyor'");
$stmt->execute([$lastId]);
$sonuc = $stmt->fetch();

$toplamBekleyen = Fis::bekleyenSayisi($pdo);

if ($sonuc && (int)$sonuc['yeni_sayisi'] > 0) {
    echo json_encode([
        'ok' => true,
        'yeni_var' => true,
        'yeni_sayisi' => (int)$sonuc['yeni_sayisi'],
        'son_id' => (int)$sonuc['son_id'],
        'toplam_bekleyen' => $toplamBekleyen
    ]);
} else {
    echo json_encode([
        'ok' => true,
        'yeni_var' => false,
        'toplam_bekleyen' => $toplamBekleyen
    ]);
}
