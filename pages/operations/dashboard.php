<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/SistemAyarlari.php';

girisKontrol();

if (SistemAyarlari::getir($pdo, 'dashboard_aktif', '0') !== '1') {
    header('Location: vehicles_cards.php');
    exit;
}

$is_stok = SistemAyarlari::getir($pdo, 'stok_yonetimi_aktif', '0') === '1';
$sayfa_basligi = 'Dashboard';
require_once __DIR__ . '/../../includes/header.php';

// --- VERİ ÇEKME İŞLEMLERİ ---

// Genel Sayılar
$arac_sayisi = $pdo->query("SELECT COUNT(*) FROM vehicles WHERE aktif=1")->fetchColumn();
$tesis_sayisi = $pdo->query("SELECT COUNT(*) FROM facilities WHERE aktif=1")->fetchColumn();
$urun_sayisi = $pdo->query("SELECT COUNT(*) FROM products WHERE aktif=1")->fetchColumn();
$bugun_islem = $pdo->query("SELECT COUNT(*) FROM records WHERE aktif=1 AND DATE(olusturma_tarihi) = CURDATE()")->fetchColumn();

// Kritik Stok (Eğer stok aktifse ve stok < 10 ise)
$kritik_stoklar = [];
if ($is_stok) {
    $kritik_stoklar = $pdo->query("SELECT urun_adi, urun_kodu, stok, birim FROM products WHERE aktif=1 AND stok < 10 ORDER BY stok ASC LIMIT 5")->fetchAll();
}

// En Çok Kullanılan 5 Ürün
$populer_urunler = $pdo->query("
    SELECT u.urun_adi, SUM(lk.miktar) as toplam, u.birim 
    FROM records lk 
    JOIN products u ON lk.urun_id = u.id 
    WHERE lk.aktif=1 
    GROUP BY lk.urun_id, u.urun_adi, u.birim 
    ORDER BY toplam DESC 
    LIMIT 5
")->fetchAll();

// Son 7 Günlük İşlem Trendi (Grafik için)
$trend_data = $pdo->query("
    SELECT tarih as gun, COUNT(*) as adet 
    FROM records 
    WHERE aktif=1 AND tarih >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
    GROUP BY tarih 
    ORDER BY tarih ASC
")->fetchAll();

$trend_labels = [];
$trend_values = [];
foreach ($trend_data as $t) {
    $trend_labels[] = date('d M', strtotime($t['gun']));
    $trend_values[] = $t['adet'];
}

// Araç Türü Dağılımı (Pasta Grafiği için)
$tur_dagilimi = $pdo->query("
    SELECT t.tur_adi, COUNT(v.id) as adet 
    FROM vehicles v 
    JOIN vehicles_type t ON v.arac_turu_id = t.id 
    WHERE v.aktif=1 
    GROUP BY t.id, t.tur_adi
")->fetchAll();

$tur_labels = [];
$tur_values = [];
foreach ($tur_dagilimi as $td) {
    $tur_labels[] = $td['tur_adi'];
    $tur_values[] = $td['adet'];
}

// Son 5 işlem
$son_islemler = $pdo->query("
    SELECT lk.*, u.urun_adi, k.ad_soyad,
           COALESCE(a.plaka, t.firma_adi, 'Bilinmiyor') as hedef_adi
    FROM records lk
    LEFT JOIN products u ON lk.urun_id = u.id
    LEFT JOIN users k ON lk.olusturan_id = k.id
    LEFT JOIN vehicles a ON lk.arac_id = a.id
    LEFT JOIN facilities t ON lk.tesis_id = t.id
    WHERE lk.aktif=1
    ORDER BY lk.olusturma_tarihi DESC
    LIMIT 5
")->fetchAll();
?>

<!-- Grafik Kütüphanesi (Yerel Dosyadan) -->
<script src="<?= ROOT_URL ?>assets/js/chart.min.js"></script>

<div class="page-header">
    <h1><span>🚀</span> Hoş Geldiniz, <?= explode(' ', mevcutKullanici()['ad_soyad'])[0] ?></h1>
    <div style="font-size:14px; color:var(--muted);"><?= date('d F Y, l') ?></div>
</div>

<!-- Üst İstatistik Kartları -->
<div class="dash-grid">
    <a href="<?= ROOT_URL ?>pages/operations/vehicles_cards.php" class="dash-card">
        <div class="dash-icon">🚗</div>
        <div class="dash-value"><?= $arac_sayisi ?></div>
        <div class="dash-label">Aktif Araç</div>
    </a>
    <a href="<?= ROOT_URL ?>pages/operations/facilities.php" class="dash-card">
        <div class="dash-icon">🏭</div>
        <div class="dash-value"><?= $tesis_sayisi ?></div>
        <div class="dash-label">Aktif Tesis</div>
    </a>
    <a href="<?= ROOT_URL ?>pages/operations/products.php" class="dash-card">
        <div class="dash-icon">🛢️</div>
        <div class="dash-value"><?= $urun_sayisi ?></div>
        <div class="dash-label">Ürün Çeşidi</div>
    </a>
    <a href="<?= ROOT_URL ?>pages/operations/transactions.php" class="dash-card">
        <div class="dash-icon">⚡</div>
        <div class="dash-value"><?= $bugun_islem ?></div>
        <div class="dash-label">Bugünkü İşlem</div>
    </a>
</div>

<div class="report-grid">
    <!-- Sol Kolon: Trend Grafiği -->
    <div class="chart-container">
        <div class="chart-title">📈 İşlem Trendi (Son 7 Gün)</div>
        <canvas id="trendChart" style="max-height: 300px;"></canvas>
    </div>

    <!-- Sağ Kolon: Dağılım Grafiği -->
    <div class="chart-container">
        <div class="chart-title">🍕 Araç Dağılımı</div>
        <canvas id="typeChart" style="max-height: 300px;"></canvas>
    </div>
</div>

<div class="report-grid">
    <!-- Sol Kolon: Tablolar -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        <!-- Son İşlemler -->
        <div class="card" style="margin:0;">
            <div class="card-title">🕒 Son İşlemler</div>
            <div class="table-wrap">
                <table style="min-width: 100%;">
                    <thead>
                        <tr><th>Tarih</th><th>Hedef</th><th>Ürün</th><th>Miktar</th></tr>
                    </thead>
                    <tbody>
                        <?php if (empty($son_islemler)): ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:var(--muted);">Henüz bir işlem kaydı bulunmuyor.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($son_islemler as $islem): ?>
                        <tr>
                            <td><span style="font-size:12px;"><?= date('d.m H:i', strtotime($islem['olusturma_tarihi'])) ?></span></td>
                            <td><strong><?= htmlspecialchars($islem['hedef_adi']) ?></strong></td>
                            <td><?= htmlspecialchars($islem['urun_adi']) ?></td>
                            <td><span class="badge badge-info"><?= (float)$islem['miktar'] ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top:15px; text-align:right;">
                <a href="<?= ROOT_URL ?>pages/operations/transactions.php" class="btn btn-sm btn-secondary">Tümünü Gör &rarr;</a>
            </div>
        </div>
    </div>

    <!-- Sağ Kolon: Yan Bilgiler -->
    <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <?php if ($is_stok && !empty($kritik_stoklar)): ?>
        <!-- Kritik Stok Uyarıları -->
        <div class="card" style="margin:0; border-color: var(--danger);">
            <div class="card-title" style="color: var(--danger);">⚠️ Kritik Stoklar (< 10)</div>
            <?php foreach($kritik_stoklar as $ks): ?>
            <div class="list-item">
                <div>
                    <div class="list-item-title"><?= htmlspecialchars($ks['urun_adi']) ?></div>
                    <div class="list-item-sub"><?= htmlspecialchars($ks['urun_kodu']) ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 800; color: var(--danger);"><?= (float)$ks['stok'] ?></div>
                    <div class="list-item-sub"><?= htmlspecialchars($ks['birim']) ?></div>
                </div>
            </div>
            <?php endforeach; ?>
            <a href="<?= ROOT_URL ?>pages/operations/stocks.php" class="btn btn-sm btn-danger" style="width:100%; margin-top:10px;">Stokları Düzenle</a>
        </div>
        <?php endif; ?>

        <!-- Popüler Ürünler -->
        <div class="card" style="margin:0;">
            <div class="card-title">🔥 En Çok Kullanılanlar</div>
            <?php foreach($populer_urunler as $pu): ?>
            <div class="list-item">
                <div class="list-item-title"><?= htmlspecialchars($pu['urun_adi']) ?></div>
                <div style="font-weight: 700;"><?= (float)$pu['toplam'] ?> <small><?= htmlspecialchars($pu['birim']) ?></small></div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<script>
// İşlem Trend Grafiği
const trendLabels = <?= json_encode($trend_labels) ?>;
const trendValues = <?= json_encode($trend_values) ?>;

if (trendLabels.length > 0) {
    const trendCtx = document.getElementById('trendChart').getContext('2d');
    new Chart(trendCtx, {
        type: 'line',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'İşlem Sayısı',
                data: trendValues,
                borderColor: '#1e4d6b',
                backgroundColor: 'rgba(30, 77, 107, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
}

// Araç Türü Pasta Grafiği
const typeLabels = <?= json_encode($tur_labels) ?>;
const typeValues = <?= json_encode($tur_values) ?>;

if (typeLabels.length > 0) {
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    
    // Geniş renk paleti (30 renk - tekrar etmez)
    const chartColors = [
        '#1e4d6b', '#2980b9', '#3498db', '#a29bfe', '#dfe6e9',
        '#e74c3c', '#f39c12', '#27ae60', '#8e44ad', '#16a085',
        '#c0392b', '#d35400', '#f1c40f', '#2ecc71', '#9b59b6',
        '#1abc9c', '#e67e22', '#34495e', '#7f8c8d', '#00cec9',
        '#6c5ce7', '#fd79a8', '#00b894', '#e17055', '#636e72',
        '#0984e3', '#b2bec3', '#d63031', '#a4b0be', '#57606f'
    ];
    
    // Dinamik renk atama (veri sayısına göre)
    const bgColors = typeLabels.map((_, i) => chartColors[i % chartColors.length]);
    
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: typeLabels,
            datasets: [{
                data: typeValues,
                backgroundColor: bgColors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            cutout: '70%'
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>