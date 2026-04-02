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

// Kurulum kontrolü: .env yoksa kurulum sihirbazına yönlendir
if (!file_exists(__DIR__ . '/.env')) {
    header('Location: install/');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/classes/SistemAyarlari.php';
girisKontrol();

$isMobile = preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"] ?? '');

if (SistemAyarlari::getir($pdo, 'dashboard_aktif', '0') === '1' && !$isMobile) {
    require_once __DIR__ . '/pages/operations/dashboard.php';
    exit;
}

// Eğer dashboard aktif değilse veya mobil cihazdan giriliyorsa, araçlar sayfasını yükle
require_once __DIR__ . '/pages/operations/vehicles_cards.php';
exit;