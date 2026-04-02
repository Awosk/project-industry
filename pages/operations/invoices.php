<?php
/*
 * Project Industry - Vehicle and Facility product tracking management system
 * Copyright (C) 2026 Awosk
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/log.php';
require_once __DIR__ . '/../../classes/SistemAyarlari.php';
require_once __DIR__ . '/../../classes/Stok.php';
require_once __DIR__ . '/../../classes/Urun.php';

girisKontrol();

if (SistemAyarlari::getir($pdo, 'stok_yonetimi_aktif', '0') !== '1') {
    flash('Stok yönetimi aktif değil.', 'danger');
    header('Location: ../../index.php');
    exit;
}

$sayfa_basligi = 'Faturalar (Stok Girişi)';
$ku = mevcutKullanici();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fatura_ekle'])) {
    csrfDogrula();
    $fatura_no = trim($_POST['fatura_no']);
    $tedarikci_id = (int)$_POST['tedarikci_id'];
    $tarih = $_POST['tarih'];
    $notlar = trim($_POST['notlar']);
    
    $kalemler = [];
    if (!empty($_POST['urun_id']) && is_array($_POST['urun_id'])) {
        foreach ($_POST['urun_id'] as $k => $uid) {
            $uid = (int)$uid;
            $u_info = $pdo->query("SELECT urun_adi, birim FROM products WHERE id=".$uid)->fetch();
            $kalemler[] = [
                'urun_id' => $uid,
                'urun_adi' => $u_info ? $u_info['urun_adi'] : 'Bilinmeyen',
                'birim' => $u_info ? $u_info['birim'] : '',
                'miktar' => (float)($_POST['miktar'][$k] ?? 0),
                'tutar' => (float)($_POST['tutar'][$k] ?? 0)
            ];
        }
    }
    
    try {
        $tedarikci_bilgi = $pdo->query("SELECT firma_adi FROM suppliers WHERE id=".(int)$tedarikci_id)->fetch();
        $firma_adi = $tedarikci_bilgi ? $tedarikci_bilgi['firma_adi'] : 'Bilinmeyen';
        
        $yeni_id = Stok::faturaEkle($pdo, $fatura_no, $tedarikci_id, $tarih, $notlar, $ku['id'], $kalemler);
        $yeni_veri = ['fatura_no'=>$fatura_no, 'tedarikci_id'=>$tedarikci_id, 'tedarikci_adi'=>$firma_adi, 'tarih'=>$tarih, 'notlar'=>$notlar, 'kalemler'=>$kalemler];
        logYaz($pdo, 'ekle', 'fatura', 'Fatura eklendi: '.$fatura_no, $yeni_id, null, $yeni_veri, 'lite');
        flash('Fatura başarıyla eklendi ve stoklara işlendi.');
    } catch(Exception $e) {
        flash('Bir hata oluştu: ' . $e->getMessage(), 'danger');
    }
    header('Location: invoices.php'); exit;
}

if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    try {
        $eski_fatura = Stok::faturaDetay($pdo, $id);
        Stok::faturaSil($pdo, $id);
        if($eski_fatura) {
            logYaz($pdo, 'sil', 'fatura', 'Fatura silindi: '.$eski_fatura['fatura_no'], $id, $eski_fatura, null, 'lite');
        }
        flash('Fatura silindi ve stoklar geri alındı.');
    } catch(Exception $e) {
        flash('Hata: ' . $e->getMessage(), 'danger');
    }
    header('Location: invoices.php'); exit;
}

$faturalar = Stok::tumFaturalar($pdo);
$tedarikciler = Stok::tumTedarikciler($pdo);
$urunler = Urun::tumUrunler($pdo);

require_once __DIR__ . '/../../includes/header.php';

// Prepare products for JS
$urun_options = '<option value="">-- Ürün Seçin --</option>';
foreach ($urunler as $u) {
    $urun_options .= '<option value="' . $u['id'] . '">' . htmlspecialchars($u['urun_kodu'] . ' - ' . $u['urun_adi']) . ' (' . htmlspecialchars($u['birim']) . ')</option>';
}
?>

<div class="page-header">
    <h1><span>📄</span> Faturalar (Stok Girişi)</h1>
    <button class="btn btn-primary btn-sm" onclick="document.getElementById('faturaModal').style.display='flex'">➕ Yeni Fatura Gir</button>
</div>

<div class="card">
    <div class="card-title">📋 Kayıtlı Faturalar (<?= count($faturalar) ?>)</div>
    <?php if (empty($faturalar)): ?>
    <div class="alert alert-info">Henüz fatura kaydı bulunmuyor.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Fatura No</th>
                    <th>Tedarikçi</th>
                    <th>Tarih</th>
                    <th>Kalem Sayısı</th>
                    <th>Toplam Tutar</th>
                    <th>İşlemi Yapan</th>
                    <th>İşlem</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($faturalar as $f): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['fatura_no']) ?></strong></td>
                    <td><?= htmlspecialchars($f['firma_adi']) ?></td>
                    <td><?= date('d.m.Y', strtotime($f['tarih'])) ?></td>
                    <td><?= $f['kalem_sayisi'] ?> Kalem</td>
                    <td><?= number_format($f['toplam_tutar'], 2, ',', '.') ?> ₺</td>
                    <td><?= htmlspecialchars($f['ad_soyad'] ?? '-') ?></td>
                    <td>
                        <a href="invoice_detail.php?id=<?= $f['id'] ?>" class="btn btn-sm btn-secondary">🔍 Detay</a>
                        <a href="?sil=<?= $f['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu faturayı silmek, eklenen tüm stokları geri alacaktır. Emin misiniz?')">🗑️ Sil</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Fatura Ekleme Modal -->
<div id="faturaModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;">
    <div class="modal-box" style="max-width:800px;width:100%;margin-top:40px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="font-weight:700;font-size:16px;">➕ Yeni Fatura (Stok Girişi)</div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('faturaModal').style.display='none'">✕ Kapat</button>
        </div>
        
        <form method="post">
            <?= csrfInput() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Tedarikçi *</label>
                    <select name="tedarikci_id" required>
                        <option value="">-- Seçiniz --</option>
                        <?php foreach($tedarikciler as $t): ?>
                        <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['firma_adi']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Fatura No *</label>
                    <input type="text" name="fatura_no" required>
                </div>
                <div class="form-group">
                    <label>Tarih *</label>
                    <input type="date" name="tarih" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                    <label>Notlar</label>
                    <input type="text" name="notlar">
                </div>
            </div>

            <div style="margin-top:20px; border-top:1px solid var(--border); padding-top:15px;">
                <div style="font-weight:600;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;">
                    <span>📦 Fatura Kalemleri (Ürünler)</span>
                    <button type="button" class="btn btn-sm btn-secondary" onclick="kalemEkle()">➕ Satır Ekle</button>
                </div>
                
                <table style="width:100%;border-collapse:collapse;margin-bottom:15px;">
                    <thead>
                        <tr style="text-align:left;border-bottom:1px solid var(--border);">
                            <th style="padding:8px 4px;">Ürün</th>
                            <th style="padding:8px 4px;width:100px;">Miktar</th>
                            <th style="padding:8px 4px;width:120px;">Tutar (₺)</th>
                            <th style="padding:8px 4px;width:40px;"></th>
                        </tr>
                    </thead>
                    <tbody id="kalemlerBody">
                        <!-- Initial Row -->
                        <tr>
                            <td style="padding:4px;"><select name="urun_id[]" required><?= $urun_options ?></select></td>
                            <td style="padding:4px;"><input type="number" name="miktar[]" step="0.01" required></td>
                            <td style="padding:4px;"><input type="number" name="tutar[]" step="0.01"></td>
                            <td style="padding:4px;"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✕</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div style="margin-top:20px;text-align:right;">
                <button type="submit" name="fatura_ekle" class="btn btn-primary" style="padding:10px 20px;font-size:16px;">💾 Kaydet ve Stoklara İşle</button>
            </div>
        </form>
    </div>
</div>

<script>
function kalemEkle() {
    var tbody = document.getElementById('kalemlerBody');
    var tr = document.createElement('tr');
    tr.innerHTML = `
        <td style="padding:4px;"><select name="urun_id[]" required><?= addslashes($urun_options) ?></select></td>
        <td style="padding:4px;"><input type="number" name="miktar[]" step="0.01" required></td>
        <td style="padding:4px;"><input type="number" name="tutar[]" step="0.01"></td>
        <td style="padding:4px;"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✕</button></td>
    `;
    tbody.appendChild(tr);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>