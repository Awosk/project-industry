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

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$root = rtrim(ROOT_URL, '/');

header('Content-Type: application/javascript; charset=utf-8');
header('Cache-Control: no-cache'); // SW her zaman taze alınsın
?>
const CACHE = 'yag-takip-v<?= SITE_VERSIYONU ?>';
const ROOT  = '<?= $root ?>';
const OFFLINE_ASSETS = [
    ROOT + '/assets/css/style.css',
    ROOT + '/assets/js/app.js',
    ROOT + '/assets/icons/icon-192.png',
];

self.addEventListener('install', e => {
    e.waitUntil(caches.open(CACHE).then(c => c.addAll(OFFLINE_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', e => {
    // PHP sayfalarını ve POST isteklerini her zaman ağdan al
    if (e.request.url.includes('.php') || e.request.method !== 'GET') return;
    e.respondWith(
        caches.match(e.request).then(cached =>
            cached || fetch(e.request).then(resp => {
                if (resp && resp.status === 200) {
                    caches.open(CACHE).then(c => c.put(e.request, resp.clone()));
                }
                return resp;
            })
        )
    );
});
