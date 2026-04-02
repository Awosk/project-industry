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

girisKontrol();

if (SistemAyarlari::getir($pdo, 'stok_yonetimi_aktif', '0') !== '1') {
    flash('Stok yönetimi aktif değil.', 'danger');
    header('Location: ../../index.php');
    exit;
}

$sayfa_basligi = 'Tedarikçiler';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekle'])) {
    csrfDogrula();
    $firma_adi = trim($_POST['firma_adi']);
    $yetkili = trim($_POST['yetkili_kisi'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adres = trim($_POST['adres'] ?? '');
    
    if ($firma_adi) {
        $yeni_id = Stok::tedarikciEkle($pdo, $firma_adi, $yetkili, $telefon, $email, $adres);
        $yeni_veri = ['firma_adi'=>$firma_adi, 'yetkili_kisi'=>$yetkili, 'telefon'=>$telefon, 'email'=>$email, 'adres'=>$adres];
        logYaz($pdo, 'ekle', 'tedarikci', 'Tedarikçi eklendi: '.$firma_adi, $yeni_id, null, $yeni_veri, 'lite');
        flash('Tedarikçi başarıyla eklendi.');
    } else {
        flash('Firma adı zorunludur.', 'danger');
    }
    header('Location: suppliers.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duzenle'])) {
    csrfDogrula();
    $id = (int)$_POST['duzenle_id'];
    $firma_adi = trim($_POST['duzenle_firma_adi']);
    $yetkili = trim($_POST['duzenle_yetkili_kisi'] ?? '');
    $telefon = trim($_POST['duzenle_telefon'] ?? '');
    $email = trim($_POST['duzenle_email'] ?? '');
    $adres = trim($_POST['duzenle_adres'] ?? '');
    
    if ($id && $firma_adi) {
        $eski_kayit = $pdo->prepare("SELECT firma_adi, yetkili_kisi, telefon, email, adres FROM suppliers WHERE id=?");
        $eski_kayit->execute([$id]);
        $eski_veri = $eski_kayit->fetch();

        Stok::tedarikciGuncelle($pdo, $id, $firma_adi, $yetkili, $telefon, $email, $adres);
        
        $yeni_veri = ['firma_adi'=>$firma_adi, 'yetkili_kisi'=>$yetkili, 'telefon'=>$telefon, 'email'=>$email, 'adres'=>$adres];
        logYaz($pdo, 'guncelle', 'tedarikci', 'Tedarikçi güncellendi: '.$firma_adi, $id, $eski_veri, $yeni_veri, 'lite');
        flash('Tedarikçi güncellendi.');
    } else {
        flash('Firma adı zorunludur.', 'danger');
    }
    header('Location: suppliers.php'); exit;
}

if (isset($_GET['sil'])) {
    $id = (int)$_GET['sil'];
    
    $eski_kayit = $pdo->prepare("SELECT firma_adi, yetkili_kisi, telefon, email, adres FROM suppliers WHERE id=?");
    $eski_kayit->execute([$id]);
    $eski_veri = $eski_kayit->fetch();
    
    Stok::tedarikciSil($pdo, $id);
    if($eski_veri) {
        logYaz($pdo, 'sil', 'tedarikci', 'Tedarikçi silindi: '.$eski_veri['firma_adi'], $id, $eski_veri, null, 'lite');
    }
    flash('Tedarikçi silindi.');
    header('Location: suppliers.php'); exit;
}

$tedarikciler = Stok::tumTedarikciler($pdo);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header">
    <h1><span>🤝</span> Tedarikçiler</h1>
</div>

<div class="card">
    <div class="card-title">➕ Yeni Tedarikçi Ekle</div>
    <form method="post">
        <?= csrfInput() ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Firma Adı *</label>
                <input type="text" name="firma_adi" required>
            </div>
            <div class="form-group">
                <label>Yetkili Kişi</label>
                <input type="text" name="yetkili_kisi">
            </div>
            <div class="form-group">
                <label>Telefon</label>
                <input type="text" name="telefon">
            </div>
            <div class="form-group">
                <label>E-posta</label>
                <input type="email" name="email">
            </div>
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Adres</label>
                <textarea name="adres" rows="2"></textarea>
            </div>
        </div>
        <div style="margin-top:14px;">
            <button type="submit" name="ekle" class="btn btn-primary">💾 Ekle</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-title">📋 Kayıtlı Tedarikçiler (<?= count($tedarikciler) ?>)</div>
    <?php if (empty($tedarikciler)): ?>
    <div class="alert alert-info">Henüz tedarikçi kaydı yok.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Firma Adı</th><th>Yetkili</th><th>İletişim</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($tedarikciler as $t): ?>
            <tr>
                <td><strong><?= htmlspecialchars($t['firma_adi']) ?></strong></td>
                <td><?= htmlspecialchars($t['yetkili_kisi'] ?? '-') ?></td>
                <td>
                    <?= htmlspecialchars($t['telefon']) ?><br>
                    <span style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($t['email']) ?></span>
                </td>
                <td style="display:flex;gap:6px;">
                    <button class="btn btn-sm btn-secondary" onclick='duzenleModal(<?= json_encode([$t["id"], $t["firma_adi"], $t["yetkili_kisi"], $t["telefon"], $t["email"], $t["adres"]]) ?>)'>✏️ Düzenle</button>
                    <a href="?sil=<?= $t['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">🗑️ Sil</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<div id="duzenleModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;">
    <div class="modal-box" style="max-width:500px;width:100%;">
        <div style="font-weight:700;font-size:16px;margin-bottom:16px;">✏️ Tedarikçi Düzenle</div>
        <form method="post">
            <?= csrfInput() ?>
            <input type="hidden" name="duzenle_id" id="d_id">
            <div class="form-group">
                <label>Firma Adı *</label>
                <input type="text" name="duzenle_firma_adi" id="d_firma_adi" required>
            </div>
            <div class="form-group">
                <label>Yetkili Kişi</label>
                <input type="text" name="duzenle_yetkili_kisi" id="d_yetkili_kisi">
            </div>
            <div style="display:flex;gap:10px;">
                <div class="form-group" style="flex:1;">
                    <label>Telefon</label>
                    <input type="text" name="duzenle_telefon" id="d_telefon">
                </div>
                <div class="form-group" style="flex:1;">
                    <label>E-posta</label>
                    <input type="email" name="duzenle_email" id="d_email">
                </div>
            </div>
            <div class="form-group">
                <label>Adres</label>
                <textarea name="duzenle_adres" id="d_adres" rows="2"></textarea>
            </div>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="submit" name="duzenle" class="btn btn-primary" style="flex:1;">💾 Kaydet</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('duzenleModal').style.display='none'">İptal</button>
            </div>
        </form>
    </div>
</div>

<script>
function duzenleModal(data) {
    document.getElementById('d_id').value = data[0];
    document.getElementById('d_firma_adi').value = data[1] || '';
    document.getElementById('d_yetkili_kisi').value = data[2] || '';
    document.getElementById('d_telefon').value = data[3] || '';
    document.getElementById('d_email').value = data[4] || '';
    document.getElementById('d_adres').value = data[5] || '';
    document.getElementById('duzenleModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>