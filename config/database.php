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

// .env dosyasını oku
$env_path = __DIR__ . '/../.env';
if (file_exists($env_path)) {
    foreach (file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

define('DB_HOST',    $_ENV['DB_HOST']    ?? 'localhost');
define('DB_NAME',    $_ENV['DB_NAME']    ?? '');
define('DB_USER',    $_ENV['DB_USER']    ?? '');
define('DB_PASS',    $_ENV['DB_PASS']    ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');

define('SITE_ADI', $_ENV['SITE_ADI'] ?? 'Endüstriyel Araç/Tesis Takip Sistemi');

// Versiyon bilgisi .env'den değil, version.php'den gelir.
// Bu dosya her release'de güncellenir; .env kişisel ayarlara dokunulmaz.
require_once __DIR__ . '/../version.php';

date_default_timezone_set('Europe/Istanbul');

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
        DB_USER, DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die('<div style="font-family:sans-serif;padding:40px;color:#c0392b">
        <h2>⚠️ Veritabanı Bağlantı Hatası</h2>
        <p>'.htmlspecialchars($e->getMessage()).'</p>
    </div>');
}