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
    GROUP BY lk.urun_id 
    ORDER BY toplam DESC 
    LIMIT 5
")->fetchAll();

// Son 7 Günlük İşlem Trendi (Grafik için)
$trend_data = $pdo->query("
    SELECT DATE(tarih) as gun, COUNT(*) as adet 
    FROM records 
    WHERE aktif=1 AND tarih >= DATE_SUB(CURDATE(), INTERVAL 6 DAY) 
    GROUP BY gun 
    ORDER BY gun ASC
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
    GROUP BY t.id
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
           (CASE WHEN lk.arac_id IS NOT NULL THEN a.plaka ELSE t.firma_adi END) as hedef_adi
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

<!-- Grafik Kütüphanesi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
.dash-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px; }
.dash-grid > a { min-width: 0; }
@media (min-width: 768px) { .dash-grid { grid-template-columns: repeat(4, 1fr); } }

.dash-card {
    background: var(--card); border-radius: var(--r-md); padding: 20px; text-align: center; border: 1px solid var(--border);
    box-shadow: 0 4px 12px rgba(0,0,0,0.03); transition: 0.3s; position: relative; overflow: hidden; text-decoration: none; color: inherit;
}
.dash-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.08); border-color: var(--primary-l); }
.dash-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; background:var(--primary); opacity:0.1; }
.dash-card.warning::before { background:#e74c3c; opacity:1; }

.dash-icon { font-size: 32px; margin-bottom: 10px; }
.dash-value { font-size: 28px; font-weight: 800; color: var(--text); line-height: 1.2; }
.dash-label { font-size: 13px; color: var(--muted); font-weight: 600; margin-top: 5px; }

.report-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 25px; }
.report-grid > div { min-width: 0; }
@media (min-width: 992px) { .report-grid { grid-template-columns: 2fr 1fr; } }

.chart-container { background: var(--card); padding: 20px; border-radius: var(--r-md); border: 1px solid var(--border); box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
.chart-title { font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; color: var(--text); }

.list-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid var(--border); }
.list-item:last-child { border-bottom: none; }
.list-item-title { font-weight: 600; font-size: 14px; }
.list-item-sub { font-size: 12px; color: var(--muted); }
</style>

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
        <div class="card" style="margin:0; border-color: #e74c3c;">
            <div class="card-title" style="color: #e74c3c;">⚠️ Kritik Stoklar (< 10)</div>
            <?php foreach($kritik_stoklar as $ks): ?>
            <div class="list-item">
                <div>
                    <div class="list-item-title"><?= htmlspecialchars($ks['urun_adi']) ?></div>
                    <div class="list-item-sub"><?= htmlspecialchars($ks['urun_kodu']) ?></div>
                </div>
                <div style="text-align: right;">
                    <div style="font-weight: 800; color: #e74c3c;"><?= (float)$ks['stok'] ?></div>
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
const trendCtx = document.getElementById('trendChart').getContext('2d');
new Chart(trendCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($trend_labels) ?>,
        datasets: [{
            label: 'İşlem Sayısı',
            data: <?= json_encode($trend_values) ?>,
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

// Araç Türü Pasta Grafiği
const typeCtx = document.getElementById('typeChart').getContext('2d');
new Chart(typeCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($tur_labels) ?>,
        datasets: [{
            data: <?= json_encode($tur_values) ?>,
            backgroundColor: ['#1e4d6b', '#2980b9', '#3498db', '#a29bfe', '#dfe6e9'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
        cutout: '70%'
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>