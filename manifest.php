<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// ROOT_URL'i hesapla
$root = rtrim(ROOT_URL, '/');

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo json_encode([
    'name'             => SITE_ADI,
    'short_name'       => SITE_ADI,
    'description'      => SITE_ADI . ' Araç Yağ Takip Sistemi',
    'start_url'        => $root . '/index.php',
    'scope'            => $root . '/',
    'display'          => 'standalone',
    'background_color' => '#1e4d6b',
    'theme_color'      => '#1e4d6b',
    'orientation'      => 'portrait',
    'icons'            => [
        [
            'src'     => $root . '/assets/icons/icon-192.png',
            'sizes'   => '192x192',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
        [
            'src'     => $root . '/assets/icons/icon-512.png',
            'sizes'   => '512x512',
            'type'    => 'image/png',
            'purpose' => 'any maskable',
        ],
    ],
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
