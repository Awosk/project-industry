<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
girisKontrol();

// Yetki hatası yönlendirmesi
if (isset($_GET['hata']) && $_GET['hata'] === 'yetki') {
    flash('Bu sayfaya erişim yetkiniz bulunmuyor.', 'danger');
}

$sayfa_basligi = 'Araçlar';

// Araç listesi + son kayıt sayısı
$araclar = $pdo->query("
    SELECT a.*,
           k.ad_soyad AS olusturan_adi,
           COUNT(CASE WHEN lk.aktif = 1 THEN 1 END) AS kayit_sayisi,
           MAX(CASE WHEN lk.aktif = 1 THEN lk.tarih END) AS son_kayit
    FROM lite_araclar a
    LEFT JOIN kullanicilar k ON a.olusturan_id = k.id
    LEFT JOIN lite_kayitlar lk ON lk.arac_id = a.id
    WHERE a.aktif = 1
    GROUP BY a.id
    ORDER BY a.arac_turu, a.plaka
")->fetchAll();

// Arama
$arama = trim($_GET['q'] ?? '');
if ($arama) {
    $araclar = array_filter($araclar, function($a) use ($arama) {
        return stripos($a['plaka'], $arama) !== false
            || stripos($a['marka_model'], $arama) !== false
            || stripos($a['arac_turu'], $arama) !== false;
    });
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <h1><span>🚗</span> Araçlar</h1>
    <a href="pages/araclar.php" class="btn btn-primary btn-sm">➕ Araç Ekle</a>
</div>

<!-- Arama -->
<div class="card" style="padding:12px 16px; margin-bottom:14px;">
    <form method="get" style="display:flex; gap:8px;">
        <input type="text" name="q" value="<?= htmlspecialchars($arama) ?>" placeholder="🔍  Plaka veya model ara..." style="flex:1;">
        <?php if ($arama): ?>
        <a href="index.php" class="btn btn-secondary btn-sm">✕</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($araclar)): ?>
<div class="alert alert-info">Henüz araç kaydı yok. <a href="pages/araclar.php" class="btn btn-sm btn-primary" style="margin-left:8px">➕ Ekle</a></div>
<?php else: ?>
<div class="arac-grid">
    <?php foreach ($araclar as $a): ?>
    <a href="pages/arac_detay.php?id=<?= $a['id'] ?>" class="arac-card">
        <div class="arac-card-plaka"><?= htmlspecialchars($a['plaka']) ?></div>
        <div class="arac-card-model"><?= htmlspecialchars($a['marka_model']) ?></div>
        <div class="arac-card-meta">
            <span class="badge badge-info arac-card-tur"><?= htmlspecialchars($a['arac_turu']) ?></span>
            <span class="arac-card-sayi">
                <?php if ($a['kayit_sayisi'] > 0): ?>
                📋 <?= $a['kayit_sayisi'] ?> kayıt
                <?php if ($a['son_kayit']): ?>
                · <?= formatliTarih($a['son_kayit']) ?>
                <?php endif; ?>
                <?php else: ?>
                <span style="color:var(--muted)">Kayıt yok</span>
                <?php endif; ?>
            </span>
        </div>
    </a>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>