<?php
/**
 * Project Industry - Fake Data Generator
 * Arayüzden miktar belirleyerek test verisi oluşturabilirsiniz.
 * 
 * UYARI: Production'da kullanmayın! Test bittikten sonra silin.
 * GÜVENLİK: Kullanım için sistem ayarlarından 'fake_data_aktif' açılmalıdır.
 *           Her kullanımdan sonra otomatik kapanır.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../classes/Kullanici.php';
require_once __DIR__ . '/../../classes/Arac.php';
require_once __DIR__ . '/../../classes/AracTuru.php';
require_once __DIR__ . '/../../classes/Tesis.php';
require_once __DIR__ . '/../../classes/Urun.php';
require_once __DIR__ . '/../../classes/Stok.php';
require_once __DIR__ . '/../../classes/Islem.php';
require_once __DIR__ . '/../../classes/SistemAyarlari.php';

adminKontrol();

// Fake Data aktif mi kontrolü
$fake_data_aktif = SistemAyarlari::getir($pdo, 'fake_data_aktif', '0') === '1';

$mesajlar = [];
$mesaj_tip = '';

// Ayar kapalıysa uyarı göster
if (!$fake_data_aktif && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $mesaj_tip = 'warning';
    $mesajlar[] = "⚠️ Fake Data özelliği kapalı. Sistem Ayarları'ndan aktif edebilirsiniz.";
}

$admin = $pdo->query("SELECT * FROM users WHERE rol='admin' AND aktif=1 LIMIT 1")->fetch();
if (!$admin) die('❌ Aktif admin kullanıcı bulunamadı.');
$ADMIN_ID = $admin['id'];

$sayfa_basligi = 'Fake Data (Test Verisi)';

// ── VERİ OLUŞTURMA / TEMİZLEME ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['olustur']) || isset($_POST['temizle']))) {
    // Ayar kapalıysa işlem yapma
    if (!$fake_data_aktif) {
        $mesaj_tip = 'danger';
        $mesajlar[] = "❌ Fake Data özelliği kapalı! Sistem Ayarları'ndan aktif edin.";
    } else {
        try {
            $fake_pdo = new PDO(
                "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
            );

            if (isset($_POST['temizle'])) {
                $fake_pdo->exec("SET foreign_key_checks = 0");
                $fake_pdo->exec("DELETE FROM mail_queue");
                $fake_pdo->exec("DELETE FROM system_logs");
                $fake_pdo->exec("DELETE FROM stock_movements");
                $fake_pdo->exec("DELETE FROM records");
                $fake_pdo->exec("DELETE FROM invoices");
                $fake_pdo->exec("DELETE FROM suppliers");
                $fake_pdo->exec("DELETE FROM products");
                $fake_pdo->exec("DELETE FROM facilities");
                $fake_pdo->exec("DELETE FROM vehicles");
                $fake_pdo->exec("DELETE FROM vehicles_type");
                $fake_pdo->exec("DELETE FROM users WHERE id != 1");
                $fake_pdo->exec("SET foreign_key_checks = 1");
                $mesaj_tip = 'success';
                $mesajlar[] = "✅ Tüm test verileri temizlendi!";
            }

            if (isset($_POST['olustur'])) {
                $arac_turu_sayisi   = max(0, (int)($_POST['arac_turu_sayisi'] ?? 3));
                $arac_sayisi        = max(0, (int)($_POST['arac_sayisi'] ?? 5));
                $tesis_sayisi       = max(0, (int)($_POST['tesis_sayisi'] ?? 3));
                $urun_sayisi        = max(0, (int)($_POST['urun_sayisi'] ?? 4));
                $kayit_sayisi       = max(0, (int)($_POST['kayit_sayisi'] ?? 20));
                $tedarikci_sayisi   = max(0, (int)($_POST['tedarikci_sayisi'] ?? 2));
                $fatura_sayisi      = max(0, (int)($_POST['fatura_sayisi'] ?? 3));
                $stok_baslangic     = max(0, (int)($_POST['stok_baslangic'] ?? 100));
                
                $olusturulan = ['arac_turu' => 0, 'arac' => 0, 'tesis' => 0, 'urun' => 0, 'kayit' => 0, 'tedarikci' => 0, 'fatura' => 0];
                
                $arac_tur_adlari = ['Kamyon', 'Kamyonet', 'Vinç', 'Forklift', 'Dozer', 'Ekskavatör', 'Beton Mikseri', 'Damperli Kamyon', 'Tanker', 'Pickup'];
                $arac_markalar = ['Mercedes Actros', 'Volvo FH', 'Scania R', 'MAN TGX', 'DAF XF', 'Iveco Stralis', 'Renault T', 'Ford Cargo', 'BMC Pro', 'Otokar'];
                $arac_plaka_on = ['34', '06', '35', '16', '41', '07', '20', '42', '55', '63'];
                $tesis_adlari = ['ABC İnşaat Ltd.', 'XYZ Mühendislik A.Ş.', 'Kaya Madencilik', 'Demir Lojistik', 'Yılmaz Enerji', 'Özkan Kimya', 'Aktaş Makina', 'Çelik Beton', 'Ege Seramik', 'Bursa Tekstil'];
                $tesis_adresleri = ['Organize Sanayi Bölgesi 1. Cadde', 'Merkez Mah. Sanayi Sok.', 'Atatürk Bulvarı No:', 'Cumhuriyet Cad. Fabrika Sok.', 'Yeni Mah. Fabrika Cad.'];
                $urun_adlari = ['Motor Yağı 15W40', 'Hidrolik Yağı 46', 'Şanzıman Yağı 80W90', 'Antifriz', 'Gres Yağı', 'Fren Hidroliği', 'Dizel Yakıt', 'Makine Yağı 10W30', 'Soğutma Suyu', 'Temizlik Malzemesi'];
                $urun_birimleri = ['LT', 'LT', 'LT', 'LT', 'KG', 'LT', 'LT', 'LT', 'LT', 'AD'];
                $tedarikci_adlari = ['Petrol Ofisi', 'Opet', 'Shell Türkiye', 'BP Madeni Yağlar', 'Mobil Oil', 'Castrol', 'Total Energies', 'Fuchs Lubricants'];
                $aciklamalar = ['Periyodik bakım', 'Yağ değişimi', 'Rutin bakım', 'Arıza sonrası', 'Sezonluk bakım', 'Kış hazırlığı', 'Yaz bakımı', 'Genel bakım', 'Kontrol sonrası', 'Planlı bakım'];
                
                $arac_tur_ids = [];
                for ($i = 0; $i < $arac_turu_sayisi; $i++) {
                    $ad = $arac_tur_adlari[$i % count($arac_tur_adlari)] . ($arac_turu_sayisi > count($arac_tur_adlari) ? ' ' . ($i + 1) : '');
                    $arac_tur_ids[] = AracTuru::ekle($fake_pdo, $ad, rand(1, 10));
                    $olusturulan['arac_turu']++;
                }
                
                $arac_ids = [];
                for ($i = 0; $i < $arac_sayisi; $i++) {
                    $tur_id = $arac_tur_ids[$i % count($arac_tur_ids)];
                    $plaka = $arac_plaka_on[array_rand($arac_plaka_on)] . ' ' . chr(65 + rand(0, 25)) . rand(100, 999) . ' ' . chr(65 + rand(0, 25)) . chr(65 + rand(0, 25));
                    $arac_ids[] = Arac::ekle($fake_pdo, $tur_id, $plaka, $arac_markalar[$i % count($arac_markalar)], $ADMIN_ID);
                    $olusturulan['arac']++;
                }
                
                $tesis_ids = [];
                for ($i = 0; $i < $tesis_sayisi; $i++) {
                    $ad = $tesis_adlari[$i % count($tesis_adlari)] . ($tesis_sayisi > count($tesis_adlari) ? ' ' . ($i + 1) : '');
                    $tesis_ids[] = Tesis::ekle($fake_pdo, $ad, $tesis_adresleri[array_rand($tesis_adresleri)] . ' No:' . rand(1, 200), $ADMIN_ID);
                    $olusturulan['tesis']++;
                }
                
                $urun_ids = [];
                for ($i = 0; $i < $urun_sayisi; $i++) {
                    $ad = $urun_adlari[$i % count($urun_adlari)] . ($urun_sayisi > count($urun_adlari) ? ' ' . ($i + 1) : '');
                    $id = Urun::ekle($fake_pdo, 'URUN-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $ad, $urun_birimleri[$i % count($urun_birimleri)], $ADMIN_ID);
                    $fake_pdo->prepare("UPDATE products SET stok = ? WHERE id = ?")->execute([$stok_baslangic, $id]);
                    $urun_ids[] = $id;
                    $olusturulan['urun']++;
                }
                
                $tedarikci_ids = [];
                for ($i = 0; $i < $tedarikci_sayisi; $i++) {
                    $ad = $tedarikci_adlari[$i % count($tedarikci_adlari)] . ($tedarikci_sayisi > count($tedarikci_adlari) ? ' ' . ($i + 1) : '');
                    $tedarikci_ids[] = Stok::tedarikciEkle($fake_pdo, $ad, 'Yetkili ' . ($i + 1), '05' . rand(10, 99) . ' ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99), 'test@test.com', 'Test Adres ' . ($i + 1));
                    $olusturulan['tedarikci']++;
                }
                
                for ($i = 0; $i < $fatura_sayisi; $i++) {
                    if (empty($tedarikci_ids) || empty($urun_ids)) break;
                    $kalemler = [];
                    for ($k = 0; $k < rand(1, min(3, count($urun_ids))); $k++) {
                        $miktar = rand(20, 100);
                        $kalemler[] = ['urun_id' => $urun_ids[array_rand($urun_ids)], 'miktar' => $miktar, 'tutar' => $miktar * rand(5, 15)];
                    }
                    Stok::faturaEkle($fake_pdo, 'FTR-' . date('Y') . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT), $tedarikci_ids[array_rand($tedarikci_ids)], date('Y-m-d', strtotime('-' . rand(1, 90) . ' days')), 'Test fatura', $ADMIN_ID, $kalemler);
                    $olusturulan['fatura']++;
                }
                
                for ($i = 0; $i < $kayit_sayisi; $i++) {
                    $urun_id = $urun_ids[array_rand($urun_ids)];
                    $miktar = rand(1, 30);
                    $tarih = date('Y-m-d', strtotime('-' . rand(0, 60) . ' days'));
                    if (rand(1, 100) <= 70 && !empty($arac_ids)) {
                        Islem::aracYagEkle($fake_pdo, $arac_ids[array_rand($arac_ids)], $urun_id, $miktar, $tarih, $aciklamalar[array_rand($aciklamalar)], rand(0, 1), rand(10000, 200000), $ADMIN_ID);
                    } elseif (!empty($tesis_ids)) {
                        Islem::tesisYagEkle($fake_pdo, $tesis_ids[array_rand($tesis_ids)], $urun_id, $miktar, $tarih, $aciklamalar[array_rand($aciklamalar)], $ADMIN_ID);
                    }
                    $olusturulan['kayit']++;
                }
                
                $mesaj_tip = 'success';
                $mesajlar[] = "✅ Fake data oluşturuldu!";
                foreach ($olusturulan as $key => $sayi) { if ($sayi > 0) $mesajlar[] = "  • $key: $sayi adet"; }
                
                // Kullanımdan sonra ayarı kapat
                SistemAyarlari::ayarKaydet($pdo, 'fake_data_aktif', '0');
                $mesajlar[] = "🔒 Güvenlik: Fake Data özelliği otomatik kapatıldı.";
            }
        } catch (Exception $e) {
            $mesaj_tip = 'danger';
            $mesajlar[] = "❌ Hata: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1><span>🎲</span> Fake Data Generator</h1>
    <a href="system_settings.php" class="btn btn-secondary btn-sm">⚙️ Sistem Ayarları</a>
</div>

<div class="warning" style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:12px 16px;margin-bottom:16px;font-size:13px;color:#92400e;">
    ⚠️ <strong>Uyarı:</strong> Bu sayfa test verisi oluşturur. Mevcut verileriniz varsa <strong>SAKIN</strong> kullanmayın!<br>
    🔒 Her kullanımdan sonra Fake Data özelliği otomatik kapatılır.
</div>

<?php if (!empty($mesajlar)): ?>
<div class="alert alert-<?= htmlspecialchars($mesaj_tip) ?>">
    <?php foreach ($mesajlar as $m): ?>
    <div><?= htmlspecialchars($m) ?></div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-title">📊 Test Verisi Oluştur</div>
    
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>🚗 Araç Türü</label>
                <input type="number" name="arac_turu_sayisi" value="3" min="0" max="20">
                <div class="form-note">Kamyon, Vinç vb.</div>
            </div>
            <div class="form-group">
                <label>🚛 Araç</label>
                <input type="number" name="arac_sayisi" value="5" min="0" max="50">
                <div class="form-note">Rastgele plaka</div>
            </div>
            <div class="form-group">
                <label>🏭 Tesis</label>
                <input type="number" name="tesis_sayisi" value="3" min="0" max="30">
                <div class="form-note">Firma + adres</div>
            </div>
            <div class="form-group">
                <label>📦 Ürün</label>
                <input type="number" name="urun_sayisi" value="4" min="0" max="20">
                <div class="form-note">Motor yağı vb.</div>
            </div>
            <div class="form-group">
                <label>📝 İşlem Kayıt</label>
                <input type="number" name="kayit_sayisi" value="20" min="0" max="200">
                <div class="form-note">Araç/Tesis çıkışı</div>
            </div>
            <div class="form-group">
                <label>💰 Başlangıç Stok</label>
                <input type="number" name="stok_baslangic" value="100" min="0" max="10000">
                <div class="form-note">Her ürüne</div>
            </div>
            <div class="form-group">
                <label>🤝 Tedarikçi</label>
                <input type="number" name="tedarikci_sayisi" value="2" min="0" max="10">
                <div class="form-note">Fatura için</div>
            </div>
            <div class="form-group">
                <label>📄 Fatura</label>
                <input type="number" name="fatura_sayisi" value="3" min="0" max="20">
                <div class="form-note">Stok girişi</div>
            </div>
        </div>
        <div style="display:flex;gap:10px;margin-top:18px;">
            <button type="submit" name="olustur" class="btn btn-primary" style="flex:1;">🎲 Fake Data Oluştur</button>
            <button type="button" onclick="setMax()" class="btn btn-secondary" style="flex:0 0 auto;width:auto;">⚡ Max</button>
        </div>
    </form>
    
    <form method="POST" onsubmit="return confirm('⚠️ Tüm test verileri silinecek! (users id=1 hariç) Emin misiniz?');" style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border);">
        <button type="submit" name="temizle" class="btn btn-danger" style="width:100%;">🗑️ Tüm Verileri Temizle</button>
    </form>
</div>

<script>
function setMax() {
    document.querySelector('[name="arac_turu_sayisi"]').value = 20;
    document.querySelector('[name="arac_sayisi"]').value = 50;
    document.querySelector('[name="tesis_sayisi"]').value = 30;
    document.querySelector('[name="urun_sayisi"]').value = 20;
    document.querySelector('[name="kayit_sayisi"]').value = 200;
    document.querySelector('[name="stok_baslangic"]').value = 10000;
    document.querySelector('[name="tedarikci_sayisi"]').value = 10;
    document.querySelector('[name="fatura_sayisi"]').value = 20;
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
