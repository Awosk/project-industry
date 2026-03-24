<?php
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 3600,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', 3600);
    session_start();

    if (isset($_SESSION['kullanici_id'])) {
        $son_aktivite = $_SESSION['son_aktivite'] ?? time();
        if (time() - $son_aktivite > 3600) {
            session_destroy();
            session_start();
        } else {
            $_SESSION['son_aktivite'] = time();
        }
    }
}

if (!defined('ROOT_URL')) {
    // Protokol
    $proto = 'http';
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $proto = 'https';
    } elseif (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        $proto = 'https';
    }

    // Host
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Kök dizini fiziksel dosya yolundan hesapla (Windows backslash güvenli)
    // auth.php: htdocs/includes/auth.php → htdocs/ kök dizini
    $root_path = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    $doc_root  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));

    if ($doc_root && str_starts_with($root_path, $doc_root)) {
        $base = substr($root_path, strlen($doc_root));
    } else {
        $base = '';
    }

    // Başında / olduğundan emin ol, sonundaki / kaldır, sonra / ekle
    $base = '/' . ltrim($base, '/');
    $base = rtrim($base, '/');

    define('ROOT_URL', $proto . '://' . $host . $base . '/');
}

function girisKontrol() {
    if (!isset($_SESSION['kullanici_id'])) {
        header('Location: ' . ROOT_URL . 'login.php');
        exit;
    }
}

function adminKontrol() {
    girisKontrol();
    if ($_SESSION['kullanici_rol'] !== 'admin') {
        header('Location: ' . ROOT_URL . 'index.php?hata=yetki');
        exit;
    }
}

function girisYapildi()   { return isset($_SESSION['kullanici_id']); }
function mevcutTema()     { return $_SESSION['kullanici_tema'] ?? 'light'; }
function isAdmin()         { return ($_SESSION['kullanici_rol'] ?? '') === 'admin'; }
function mevcutKullanici() {
    return [
        'id'       => $_SESSION['kullanici_id']  ?? null,
        'adi'      => $_SESSION['kullanici_adi']  ?? '',
        'ad_soyad' => $_SESSION['ad_soyad']       ?? '',
        'rol'      => $_SESSION['kullanici_rol']  ?? 'kullanici',
        'tema'     => $_SESSION['kullanici_tema'] ?? 'light',
    ];
}

function flash($mesaj, $tur = 'success') {
    $_SESSION['flash'] = ['mesaj' => $mesaj, 'tur' => $tur];
}
function getFlash() {
    if (isset($_SESSION['flash'])) { $f = $_SESSION['flash']; unset($_SESSION['flash']); return $f; }
    return null;
}

function formatliTarih($t) {
    if (!$t) return '-';
    if (strlen($t) > 10) return date('d.m.Y H:i', strtotime($t));
    return date('d.m.Y', strtotime($t));
}
function formatliMiktar($m) {
    return number_format($m, 2, ',', '.') . ' L';
}