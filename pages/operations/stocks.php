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

$sayfa_basligi = 'Stoklar';
$ku = mevcutKullanici();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manuel_islem'])) {
    csrfDogrula();
    $urun_id = (int)$_POST['urun_id'];
    $islem_turu = $_POST['islem_turu']; // giris, cikis, esitle
    $miktar = (float)str_replace(',', '.', $_POST['miktar']);
    $aciklama = trim($_POST['aciklama']);
    
    if ($urun_id && in_array($islem_turu, ['giris', 'cikis', 'esitle']) && $miktar >= 0) {
        try {
            $eski_stok = $pdo->query("SELECT stok FROM products WHERE id=".$urun_id)->fetchColumn();
            
            Stok::manuelIslem($pdo, $urun_id, $islem_turu, $miktar, $aciklama, $ku['id']);
            
            $yeni_stok = $pdo->query("SELECT stok FROM products WHERE id=".$urun_id)->fetchColumn();
            
            $eski_veri = ['eski_stok' => (float)$eski_stok];
            $yeni_veri = ['islem_turu' => $islem_turu, 'islem_miktar' => $miktar, 'yeni_stok' => (float)$yeni_stok, 'aciklama' => $aciklama];
            
            logYaz($pdo, 'guncelle', 'stok_hareketi', 'Manuel stok işlemi ('.$islem_turu.'): '.$miktar, $urun_id, $eski_veri, $yeni_veri, 'lite');
            flash('Stok işlemi başarıyla uygulandı.');
        } catch(Exception $e) {
            flash('Hata: ' . $e->getMessage(), 'danger');
        }
    } else {
        flash('Lütfen geçerli bir miktar ve işlem türü seçin.', 'danger');
    }
    header('Location: stocks.php'); exit;
}

$urunler = Urun::listeleDetayli($pdo);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1><span>📦</span> Stoklar</h1>
</div>

<div class="card">
    <div class="card-title">📋 Mevcut Stok Durumu</div>
    <?php if (empty($urunler)): ?>
    <div class="alert alert-info">Henüz ürün kaydı bulunmuyor.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ürün Kodu</th>
                    <th>Ürün Adı</th>
                    <th>Mevcut Stok</th>
                    <th>İşlemler</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($urunler as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['urun_kodu']) ?></strong></td>
                    <td><?= htmlspecialchars($u['urun_adi']) ?></td>
                    <td style="color:<?= $u['stok'] > 0 ? 'var(--success)' : 'var(--danger)' ?>;font-weight:bold;font-size:16px;">
                        <?= (float)$u['stok'] ?> <span style="font-size:12px;color:var(--text);font-weight:normal;"><?= htmlspecialchars($u['birim']) ?></span>
                    </td>
                    <td style="display:flex;gap:6px;">
                        <button class="btn btn-sm btn-secondary" onclick="manuelIslemModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['urun_kodu'].' - '.$u['urun_adi'], ENT_QUOTES) ?>', 'giris', <?= (float)$u['stok'] ?>)">➕ Giriş</button>
                        <button class="btn btn-sm btn-secondary" onclick="manuelIslemModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['urun_kodu'].' - '.$u['urun_adi'], ENT_QUOTES) ?>', 'cikis', <?= (float)$u['stok'] ?>)">➖ Çıkış</button>
                        <button class="btn btn-sm btn-secondary" onclick="manuelIslemModal(<?= $u['id'] ?>, '<?= htmlspecialchars($u['urun_kodu'].' - '.$u['urun_adi'], ENT_QUOTES) ?>', 'esitle', <?= (float)$u['stok'] ?>)">⚙️ Eşitle</button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-title">🕒 Son 10 Stok Hareketi</div>
    <?php
    $son_hareketler = $pdo->query("
        SELECT s.*, u.urun_kodu, u.urun_adi, u.birim, k.ad_soyad
        FROM stock_movements s
        JOIN products u ON s.urun_id = u.id
        LEFT JOIN users k ON s.olusturan_id = k.id
        ORDER BY s.tarih DESC
        LIMIT 10
    ")->fetchAll();
    ?>
    <?php if (empty($son_hareketler)): ?>
        <div class="alert alert-info">Hiç hareket yok.</div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Tarih</th><th>Ürün</th><th>İşlem</th><th>Miktar</th><th>Açıklama</th><th>Kullanıcı</th></tr></thead>
                <tbody>
                <?php foreach($son_hareketler as $h): ?>
                    <tr>
                        <td><?= date('d.m.Y H:i', strtotime($h['tarih'])) ?></td>
                        <td><?= htmlspecialchars($h['urun_kodu'] . ' - ' . $h['urun_adi']) ?></td>
                        <td>
                            <?php if ($h['islem_turu'] === 'giris'): ?>
                                <span class="badge" style="background:var(--success-l);color:var(--success);border:1px solid var(--success);">GİRİŞ</span>
                            <?php else: ?>
                                <span class="badge" style="background:var(--danger-l);color:var(--danger);border:1px solid var(--danger);">ÇIKIŞ</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:bold;"><?= (float)$h['miktar'] ?> <span style="font-size:11px;font-weight:normal;"><?= htmlspecialchars($h['birim']) ?></span></td>
                        <td><?= htmlspecialchars($h['aciklama'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($h['ad_soyad'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Manuel İşlem Modal -->
<div id="manuelModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:20px;">
    <div class="modal-box" style="max-width:400px;width:100%;">
        <div style="font-weight:700;font-size:16px;margin-bottom:16px;" id="manuelBaslik">Stok İşlemi</div>
        <form method="post">
            <?= csrfInput() ?>
            <input type="hidden" name="urun_id" id="m_urun_id">
            <input type="hidden" name="islem_turu" id="m_islem_turu">
            
            <div style="margin-bottom:15px; font-weight:bold;" id="m_urun_isim"></div>
            <div style="margin-bottom:15px; font-size:13px; color:var(--muted);" id="m_mevcut_stok"></div>

            <div class="form-group">
                <label id="m_miktar_label">Miktar *</label>
                <input type="number" name="miktar" id="m_miktar" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label>Açıklama (Opsiyonel)</label>
                <input type="text" name="aciklama" id="m_aciklama" placeholder="Örn: Sayım farkı, zayiat vb.">
            </div>
            
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="submit" name="manuel_islem" class="btn btn-primary" style="flex:1;">💾 Kaydet</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('manuelModal').style.display='none'">İptal</button>
            </div>
        </form>
    </div>
</div>

<script>
function manuelIslemModal(urunId, urunIsim, islemTuru, mevcutStok) {
    document.getElementById('m_urun_id').value = urunId;
    document.getElementById('m_islem_turu').value = islemTuru;
    document.getElementById('m_urun_isim').innerText = urunIsim;
    document.getElementById('m_mevcut_stok').innerText = "Mevcut Stok: " + mevcutStok;
    
    var baslik = "";
    var miktarLabel = "";
    if (islemTuru === 'giris') { baslik = "➕ Manuel Stok Girişi"; miktarLabel = "Eklenecek Miktar *"; }
    else if (islemTuru === 'cikis') { baslik = "➖ Manuel Stok Çıkışı"; miktarLabel = "Düşülecek Miktar *"; }
    else if (islemTuru === 'esitle') { baslik = "⚙️ Stok Eşitleme"; miktarLabel = "Yeni Gerçek Stok Miktarı *"; }
    
    document.getElementById('manuelBaslik').innerText = baslik;
    document.getElementById('m_miktar_label').innerText = miktarLabel;
    
    if(islemTuru === 'esitle') {
        document.getElementById('m_miktar').value = mevcutStok;
    } else {
        document.getElementById('m_miktar').value = '';
    }
    
    document.getElementById('m_aciklama').value = '';
    
    document.getElementById('manuelModal').style.display = 'flex';
    setTimeout(() => document.getElementById('m_miktar').focus(), 50);
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>