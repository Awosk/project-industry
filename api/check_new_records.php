<?php
/**
 * Project Industry - Yeni Kayıt Kontrolü (Polling)
 * Apache2+PHP-FPM ile SSE çalışmadığı için polling kullanılıyor.
 */

// Session kilitleme sorununu önle
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_write_close();

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$lastId = isset($_GET['lastId']) ? (int)$_GET['lastId'] : 0;

// Yeni kayıt var mı?
$stmt = $pdo->prepare("SELECT COUNT(*) as sayi, MAX(id) as son_id FROM records WHERE id > ? AND aktif = 1");
$stmt->execute([$lastId]);
$sonuc = $stmt->fetch();

if ($sonuc['sayi'] > 0) {
    echo json_encode([
        'ok' => true,
        'yeni_var' => true,
        'yeni_sayisi' => (int)$sonuc['sayi'],
        'son_id' => (int)$sonuc['son_id']
    ]);
} else {
    echo json_encode([
        'ok' => true,
        'yeni_var' => false
    ]);
}
