<?php
/*
 * Project Industry - Vehicle and Facility Industrial Oil Tracking System
 * Copyright (C) 2026 Awosk
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */
?><!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1e4d6b">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Ürün Takip">
    <link rel="manifest" href="<?= ROOT_URL ?>manifest.php">
    <link rel="apple-touch-icon" href="<?= ROOT_URL ?>assets/icons/icon-192.png">
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('<?= ROOT_URL ?>sw.php').catch(() => {});
        }
    </script>
    <title><?= htmlspecialchars($sayfa_basligi ?? SITE_ADI) ?> | <?= SITE_ADI ?></title>
    <?php $tema = girisYapildi() ? mevcutTema() : 'light'; ?>
    <link rel="stylesheet" href="<?= ROOT_URL ?>assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
    <?php if ($tema === 'dark'): ?>
    <link rel="stylesheet" id="tema-css" href="<?= ROOT_URL ?>assets/css/style-dark.css?v=<?= filemtime(__DIR__ . '/../assets/css/style-dark.css') ?>">
    <?php else: ?>
    <link rel="stylesheet" id="tema-css" href="">
    <?php endif; ?>
</head>
<body>

<?php if (girisYapildi()):
    // ── Silinmiş / pasife alınmış kullanıcı oturumu otomatik kapat ──
    // $pdo bu noktada config/database.php tarafından zaten tanımlı olmalı
    if (isset($pdo)) {
        aktifKullaniciKontrol($pdo);
    }
    
    require_once __DIR__ . '/../classes/SistemAyarlari.php';
    $is_dashboard = SistemAyarlari::getir($pdo, 'dashboard_aktif', '0') === '1';
    $is_stok = SistemAyarlari::getir($pdo, 'stok_yonetimi_aktif', '0') === '1';

    $ku = mevcutKullanici();
    $curr = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar">
    <button class="nav-hamburger" id="hamburger" onclick="toggleDrawer()" aria-label="Menü">
        <span></span><span></span><span></span>
    </button>

    <!-- Geri / İleri -->
    <div class="nav-history">
        <button class="nav-history-btn" onclick="history.back()" title="Geri">&#8592;</button>
        <button class="nav-history-btn" onclick="history.forward()" title="İleri">&#8594;</button>
    </div>

    <div class="nav-brand">
        <span class="nav-icon">🔩</span>
        <span class="nav-title"><?= SITE_ADI ?></span>
    </div>

    <!-- Desktop linkler -->
    <ul class="nav-links-desktop">
        <?php if ($is_dashboard): ?>
        <li><a href="<?= ROOT_URL ?>index.php" <?= $curr=='index.php'?'class="active"':'' ?>>📊 Dashboard</a></li>
        <li><a href="<?= ROOT_URL ?>pages/operations/vehicles_cards.php" <?= $curr=='vehicles_cards.php'?'class="active"':'' ?>>🚗 Araçlar</a></li>
        <?php else: ?>
        <li><a href="<?= ROOT_URL ?>index.php" <?= in_array($curr, ['index.php', 'vehicles_cards.php'])?'class="active"':'' ?>>🚗 Araçlar</a></li>
        <?php endif; ?>
        
        <li><a href="<?= ROOT_URL ?>pages/operations/facilities.php" <?= $curr=='facilities.php'?'class="active"':'' ?>>🏭 Tesisler</a></li>
        
        <li class="dropdown <?= in_array($curr, ['products.php','vehicles.php','vehicle_types.php','facilities_management.php']) ? 'active' : '' ?>">
            <a href="#" <?= in_array($curr, ['products.php','vehicles.php','vehicle_types.php','facilities_management.php']) ? 'class="active"' : '' ?>>📁 Yönet ▾</a>
            <ul class="dropdown-menu">
                <li><a href="<?= ROOT_URL ?>pages/operations/products.php">🛢️ Ürün Yönetimi</a></li>
                <li><a href="<?= ROOT_URL ?>pages/operations/vehicles.php">🚗 Araç Yönetimi</a></li>
                <li><a href="<?= ROOT_URL ?>pages/operations/vehicle_types.php">🚗 Araç Türü Yönetimi</a></li>
                <li><a href="<?= ROOT_URL ?>pages/operations/facilities_management.php">🏭 Tesis Yönetimi</a></li>
            </ul>
        </li>
        
        <?php if ($is_stok): ?>
        <li class="dropdown <?= in_array($curr, ['stocks.php','suppliers.php','invoices.php','invoice_detail.php']) ? 'active' : '' ?>">
            <a href="#" <?= in_array($curr, ['stocks.php','suppliers.php','invoices.php','invoice_detail.php']) ? 'class="active"' : '' ?>>📦 Stok ▾</a>
            <ul class="dropdown-menu">
                <li><a href="<?= ROOT_URL ?>pages/operations/stocks.php">📊 Stoklar</a></li>
                <li><a href="<?= ROOT_URL ?>pages/operations/suppliers.php">🤝 Tedarikçiler</a></li>
                <li><a href="<?= ROOT_URL ?>pages/operations/invoices.php">📄 Faturalar (Giriş)</a></li>
            </ul>
        </li>
        <?php endif; ?>

        <li><a href="<?= ROOT_URL ?>pages/operations/transactions.php" <?= $curr=='transactions.php'?'class="active"':'' ?>>📋 İşlemler</a></li>
        <li><a href="<?= ROOT_URL ?>pages/operations/reports.php" <?= $curr=='reports.php'?'class="active"':'' ?>>📊 Raporlar</a></li>
        <?php if (isAdmin()): ?>
        <li class="dropdown <?= in_array($curr, ['users.php','logs.php','backup.php','update.php','system_settings.php','mail_queue.php']) ? 'active' : '' ?>">
            <a href="#" <?= in_array($curr, ['users.php','logs.php','backup.php','update.php','system_settings.php','mail_queue.php']) ? 'class="active"' : '' ?>>⚙️ Yönetim ▾</a>
            <ul class="dropdown-menu">
                <li><a href="<?= ROOT_URL ?>pages/management/users.php">👥 Kullanıcı Yönetimi</a></li>
                <li><a href="<?= ROOT_URL ?>pages/management/logs.php">🔍 Sistem Kayıtları</a></li>
                <li><a href="<?= ROOT_URL ?>pages/management/backup.php">💾 Veritabanı Yedekleme</a></li>
                <li><a href="<?= ROOT_URL ?>pages/management/update.php">🔄 Sistem Güncelleme</a></li>
                <li><a href="<?= ROOT_URL ?>pages/management/system_settings.php">⚙️ Sistem Ayarları</a></li>
                <li><a href="<?= ROOT_URL ?>pages/management/mail_queue.php">📬 Mail Kuyruğu</a></li>
            </ul>
        </li>
        <?php endif; ?>
    </ul>

    <div class="nav-user-desktop">
        <span>👤 <?= htmlspecialchars($ku['ad_soyad']) ?></span>
        <?php if (isAdmin()): ?><span class="badge-admin">Admin</span><?php endif; ?>
        <button class="btn-tema-toggle" onclick="temaToggle()" title="Tema değiştir" id="temaBtn"><?= mevcutTema() === 'dark' ? '☀️' : '🌙' ?></button>
        <a href="<?= ROOT_URL ?>pages/operations/profile_password.php" class="btn-logout-desktop" style="background:var(--border);color:var(--text);">🔑 Şifre</a>
        <a href="<?= ROOT_URL ?>pages/auth/logout.php" class="btn-logout-desktop">Çıkış</a>
    </div>
</nav>

<!-- Drawer -->
<div class="nav-drawer" id="navDrawer">
    <div class="nav-drawer-overlay" onclick="closeDrawer()"></div>
    <div class="nav-drawer-panel">
        <ul class="nav-drawer-links">
            <li><span class="drawer-section">Ana Menü</span></li>
            <?php if ($is_dashboard): ?>
            <li><a href="<?= ROOT_URL ?>pages/operations/dashboard.php" onclick="closeDrawer()">📊 Dashboard</a></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/vehicles_cards.php" onclick="closeDrawer()">🚗 Araçlar</a></li>
            <?php else: ?>
            <li><a href="<?= ROOT_URL ?>index.php" onclick="closeDrawer()">🚗 Araçlar</a></li>
            <?php endif; ?>
            <li><a href="<?= ROOT_URL ?>pages/operations/facilities.php" onclick="closeDrawer()">🏭 Tesisler</a></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/transactions.php" onclick="closeDrawer()">📋 İşlemler</a></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/reports.php" onclick="closeDrawer()">📊 Raporlar</a></li>
            
            <li><span class="drawer-section">Kayıtlar</span></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/products.php" onclick="closeDrawer()">🛢️ Ürünler</a></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/vehicles.php" onclick="closeDrawer()">🚗 Araç Yönetimi</a></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/vehicle_types.php" onclick="closeDrawer()">🚗 Araç Türü Yönetimi</a></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/facilities_management.php" onclick="closeDrawer()">🏭 Tesis Yönetimi</a></li>
            
            <?php if ($is_stok): ?>
            <li><span class="drawer-section">Stok</span></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/stocks.php" onclick="closeDrawer()">📊 Stoklar</a></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/suppliers.php" onclick="closeDrawer()">🤝 Tedarikçiler</a></li>
            <li><a href="<?= ROOT_URL ?>pages/operations/invoices.php" onclick="closeDrawer()">📄 Faturalar (Giriş)</a></li>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <li><span class="drawer-section">Yönetim</span></li>
            <li><a href="<?= ROOT_URL ?>pages/management/users.php" onclick="closeDrawer()">👥 Kullanıcı Yönetimi</a></li>
            <li><a href="<?= ROOT_URL ?>pages/management/logs.php" onclick="closeDrawer()">🔍 Sistem Kayıtları</a></li>
            <li><a href="<?= ROOT_URL ?>pages/management/backup.php" onclick="closeDrawer()">💾 Veritabanı Yedekleme</a></li>
            <li><a href="<?= ROOT_URL ?>pages/management/update.php" onclick="closeDrawer()">🔄 Sistem Güncelleme</a></li>
            <li><a href="<?= ROOT_URL ?>pages/management/system_settings.php" onclick="closeDrawer()">⚙️ Sistem Ayarları</a></li>
            <li><a href="<?= ROOT_URL ?>pages/management/mail_queue.php" onclick="closeDrawer()">📬 Mail Kuyruğu</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-drawer-footer">
            <a href="#" onclick="temaToggle(); closeDrawer(); return false;">
                <span id="temaDrawerIcon"><?= mevcutTema() === 'dark' ? '☀️' : '🌙' ?></span>
                <span id="temaDrawerLabel"><?= mevcutTema() === 'dark' ? 'Açık Temaya Geç' : 'Koyu Temaya Geç' ?></span>
            </a>
            <a href="<?= ROOT_URL ?>pages/operations/profile_password.php">🔑 Şifre Değiştir</a>
            <a href="<?= ROOT_URL ?>pages/auth/logout.php">🚪 Çıkış — <?= htmlspecialchars($ku['ad_soyad']) ?></a>
        </div>
    </div>
</div>

<!-- Bottom Nav -->
<nav class="bottom-nav">
    <a href="<?= ROOT_URL ?>pages/operations/vehicles_cards.php" class="<?= in_array($curr, ['index.php', 'vehicles_cards.php'])?'active':'' ?>">
        <span class="bn-icon">🚗</span>Araçlar
    </a>
    <a href="<?= ROOT_URL ?>pages/operations/facilities.php" class="<?= $curr=='facilities.php'?'active':'' ?>">
        <span class="bn-icon">🏭</span>Tesisler
    </a>
    <a href="<?= ROOT_URL ?>pages/operations/transactions.php" class="<?= $curr=='transactions.php'?'active':'' ?>">
        <span class="bn-icon">📋</span>İşlemler
    </a>
    <a href="<?= ROOT_URL ?>pages/operations/reports.php" class="<?= $curr=='reports.php'?'active':'' ?>">
        <span class="bn-icon">📊</span>Raporlar
    </a>
    <a href="#" onclick="toggleDrawer();return false;">
        <span class="bn-icon">☰</span>Menü
    </a>
</nav>

<?php endif; ?>

<div class="container">
<?php $flash = getFlash(); if ($flash): ?>
<div class="alert alert-<?= $flash['tur'] ?>">
    <span><?= htmlspecialchars($flash['mesaj']) ?></span>
    <button class="alert-close" onclick="this.parentElement.remove()">✕</button>
</div>
<?php endif; ?>

<style>
.btn-tema-toggle {
    background: none; border: none; cursor: pointer;
    font-size: 18px; padding: 4px 6px; border-radius: 6px;
    transition: .2s; line-height: 1; flex-shrink: 0;
}
.btn-tema-toggle:hover { background: rgba(255,255,255,.12); }
</style>

<script>
async function temaToggle() {
    var linkEl  = document.getElementById('tema-css');
    var mevcut  = linkEl && linkEl.href && linkEl.href.includes('style-dark') ? 'dark' : 'light';
    var yeni    = mevcut === 'light' ? 'dark' : 'light';
    var base    = '<?= ROOT_URL ?>';

    if (yeni === 'dark') {
        linkEl.href = base + 'assets/css/style-dark.css';
    } else {
        linkEl.href = '';
    }

    var icon  = yeni === 'dark' ? '☀️' : '🌙';
    var label = yeni === 'dark' ? 'Açık Temaya Geç' : 'Koyu Temaya Geç';
    var desktopBtn   = document.getElementById('temaBtn');
    var drawerIcon   = document.getElementById('temaDrawerIcon');
    var drawerLabel  = document.getElementById('temaDrawerLabel');
    if (desktopBtn)  desktopBtn.textContent  = icon;
    if (drawerIcon)  drawerIcon.textContent  = icon;
    if (drawerLabel) drawerLabel.textContent = label;

    try {
        await fetch(base + 'pages/operations/theme.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'tema=' + encodeURIComponent(yeni)
        });
    } catch(e) {}
}
</script>