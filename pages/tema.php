<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
girisKontrol();

header('Content-Type: application/json; charset=utf-8');

$tema = trim($_POST['tema'] ?? '');
if (!in_array($tema, ['light', 'dark'])) {
    echo json_encode(['ok' => false]);
    exit;
}

$ku = mevcutKullanici();
$pdo->prepare("UPDATE kullanicilar SET tema = ? WHERE id = ?")
    ->execute([$tema, $ku['id']]);

$_SESSION['kullanici_tema'] = $tema;

echo json_encode(['ok' => true]);
