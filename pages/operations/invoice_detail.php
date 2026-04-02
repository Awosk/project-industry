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

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: invoices.php'); exit; }

$fatura = Stok::faturaDetay($pdo, $id);
if (!$fatura) { flash('Fatura bulunamadı.', 'danger'); header('Location: invoices.php'); exit; }

$sayfa_basligi = 'Fatura Detayı - ' . $fatura['fatura_no'];
$ku = mevcutKullanici();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fatura_guncelle'])) {
    csrfDogrula();
    $fatura_no = trim($_POST['fatura_no']);
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
    
    if ($fatura_no && $tarih) {
        try {
            $eski_veri = Stok::faturaDetay($pdo, $id);
            
            Stok::faturaGuncelle($pdo, $id, $fatura_no, $tarih, $notlar, $ku['id'], $kalemler);
            
            $yeni_veri = [
                'fatura_no'=>$fatura_no, 
                'tedarikci_id'=>$eski_veri['tedarikci_id'], 
                'tedarikci_adi'=>$eski_veri['firma_adi'], 
                'tarih'=>$tarih, 
                'notlar'=>$notlar, 
                'kalemler'=>$kalemler
            ];
            logYaz($pdo, 'guncelle', 'fatura', 'Fatura güncellendi: '.$fatura_no, $id, $eski_veri, $yeni_veri, 'lite');
            
            flash('Fatura bilgileri ve kalemleri başarıyla güncellendi.');
        } catch(Exception $e) {
            flash('Bir hata oluştu: ' . $e->getMessage(), 'danger');
        }
    } else {
        flash('Fatura No ve Tarih zorunludur.', 'danger');
    }
    header('Location: invoice_detail.php?id='.$id); exit;
}

$urunler = Urun::tumUrunler($pdo);
$urun_options = '<option value="">-- Ürün Seçin --</option>';
foreach ($urunler as $u) {
    $urun_options .= '<option value="' . $u['id'] . '">' . htmlspecialchars($u['urun_kodu'] . ' - ' . $u['urun_adi']) . ' (' . htmlspecialchars($u['birim']) . ')</option>';
}

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1><span>📄</span> Fatura: <?= htmlspecialchars($fatura['fatura_no']) ?></h1>
    <a href="invoices.php" class="btn btn-secondary btn-sm">← Geri</a>
</div>

<div style="display:flex; flex-wrap:wrap; gap:20px;">
    
    <!-- Fatura Bilgileri -->
    <div class="card" style="flex:1; min-width:300px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
            <div class="card-title" style="margin:0;">📝 Fatura Bilgileri</div>
            <button class="btn btn-sm btn-secondary" onclick="document.getElementById('faturaDuzenleModal').style.display='flex'">✏️ Düzenle</button>
        </div>
        <table style="width:100%; border-collapse:collapse;">
            <tr><td style="padding:8px 0; border-bottom:1px solid var(--border); color:var(--muted);">Fatura No:</td><td style="padding:8px 0; border-bottom:1px solid var(--border); font-weight:bold;"><?= htmlspecialchars($fatura['fatura_no']) ?></td></tr>
            <tr><td style="padding:8px 0; border-bottom:1px solid var(--border); color:var(--muted);">Tedarikçi:</td><td style="padding:8px 0; border-bottom:1px solid var(--border); font-weight:bold;"><?= htmlspecialchars($fatura['firma_adi']) ?></td></tr>
            <tr><td style="padding:8px 0; border-bottom:1px solid var(--border); color:var(--muted);">Tarih:</td><td style="padding:8px 0; border-bottom:1px solid var(--border); font-weight:bold;"><?= date('d.m.Y', strtotime($fatura['tarih'])) ?></td></tr>
            <tr><td style="padding:8px 0; border-bottom:1px solid var(--border); color:var(--muted);">Notlar:</td><td style="padding:8px 0; border-bottom:1px solid var(--border);"><?= htmlspecialchars($fatura['notlar'] ?: '-') ?></td></tr>
            <tr><td style="padding:8px 0; color:var(--muted);">Toplam Tutar:</td><td style="padding:8px 0; font-weight:bold; color:var(--primary); font-size:18px;"><?= number_format($fatura['toplam_tutar'], 2, ',', '.') ?> ₺</td></tr>
        </table>
    </div>

    <!-- Kalemler -->
    <div class="card" style="flex:2; min-width:400px;">
        <div class="card-title">📦 Fatura Kalemleri (Stoğa Girenler)</div>
        <?php if (empty($fatura['kalemler'])): ?>
            <div class="alert alert-info">Bu faturada kalem bulunmuyor.</div>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Ürün</th>
                            <th>Miktar</th>
                            <th>İşlem Zamanı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($fatura['kalemler'] as $k): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($k['urun_adi']) ?></strong></td>
                            <td><span class="badge badge-info" style="font-size:14px;"><?= (float)$k['miktar'] ?> <?= htmlspecialchars($k['birim']) ?></span></td>
                            <td><span style="font-size:12px;color:var(--muted);"><?= date('d.m.Y H:i', strtotime($k['tarih'])) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- Düzenleme Modal -->
<div id="faturaDuzenleModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:flex-start;justify-content:center;padding:20px;overflow-y:auto;">
    <div class="modal-box" style="max-width:800px;width:100%;margin-top:40px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
            <div style="font-weight:700;font-size:16px;">✏️ Faturayı Düzenle</div>
            <button type="button" class="btn btn-sm btn-secondary" onclick="document.getElementById('faturaDuzenleModal').style.display='none'">✕ Kapat</button>
        </div>
        
        <form method="post">
            <?= csrfInput() ?>
            <div class="form-grid">
                <div class="form-group">
                    <label>Fatura No *</label>
                    <input type="text" name="fatura_no" value="<?= htmlspecialchars($fatura['fatura_no']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Tarih *</label>
                    <input type="date" name="tarih" value="<?= htmlspecialchars($fatura['tarih']) ?>" required>
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Notlar</label>
                    <input type="text" name="notlar" value="<?= htmlspecialchars($fatura['notlar']) ?>">
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
                        <?php if (!empty($fatura['kalemler'])): ?>
                            <?php foreach ($fatura['kalemler'] as $k): ?>
                            <tr>
                                <td style="padding:4px;">
                                    <select name="urun_id[]" required>
                                        <option value="">-- Ürün Seçin --</option>
                                        <?php foreach ($urunler as $u): ?>
                                        <option value="<?= $u['id'] ?>" <?= $u['id'] == $k['urun_id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['urun_kodu'] . ' - ' . $u['urun_adi']) ?> (<?= htmlspecialchars($u['birim']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td style="padding:4px;"><input type="number" name="miktar[]" step="0.01" value="<?= (float)$k['miktar'] ?>" required></td>
                                <td style="padding:4px;"><input type="number" name="tutar[]" step="0.01" value="0"></td>
                                <td style="padding:4px;"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✕</button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td style="padding:4px;"><select name="urun_id[]" required><?= $urun_options ?></select></td>
                                <td style="padding:4px;"><input type="number" name="miktar[]" step="0.01" required></td>
                                <td style="padding:4px;"><input type="number" name="tutar[]" step="0.01"></td>
                                <td style="padding:4px;"><button type="button" class="btn btn-sm btn-danger" onclick="this.closest('tr').remove()">✕</button></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div style="font-size:12px; color:var(--muted);">* Kalemleri kaydettiğinizde stoklar baştan hesaplanacaktır. Tutarlar kaydedilmezse 0 olarak işlenir.</div>
            </div>

            <div style="margin-top:20px;text-align:right;">
                <button type="submit" name="fatura_guncelle" class="btn btn-primary" style="padding:10px 20px;font-size:16px;">💾 Kaydet ve Stokları Güncelle</button>
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