<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/log.php';
girisKontrol();

$sayfa_basligi = 'Araç Yönetimi';
$ku = mevcutKullanici();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ekle'])) {
    csrfDogrula();
    $tur   = trim($_POST['arac_turu']);
    $plaka = strtoupper(trim($_POST['plaka']));
    $model = trim($_POST['marka_model']);
    if ($tur && $plaka && $model) {
        $mevcut = $pdo->prepare("SELECT * FROM lite_araclar WHERE plaka=?");
        $mevcut->execute([$plaka]); $mevcut = $mevcut->fetch();
        if ($mevcut && $mevcut['aktif'] == 0) {
            $pdo->prepare("UPDATE lite_araclar SET arac_turu=?, marka_model=?, olusturan_id=?, aktif=1 WHERE id=?")->execute([$tur, $model, $ku['id'], $mevcut['id']]);
            logYaz($pdo,'ekle','arac','Silinen araç reaktif edildi: '.$plaka.' ('.$tur.') - '.$model, $mevcut['id'], null, ['tur'=>$tur,'plaka'=>$plaka,'model'=>$model], 'lite');
            flash('Daha önce silinmiş araç tekrar aktif edildi.');
        } elseif ($mevcut && $mevcut['aktif'] == 1) {
            flash('Bu plaka zaten kayıtlı.', 'danger');
        } else {
            try {
                $pdo->prepare("INSERT INTO lite_araclar (arac_turu, plaka, marka_model, olusturan_id) VALUES (?,?,?,?)")->execute([$tur, $plaka, $model, $ku['id']]);
                $yeni_id = $pdo->lastInsertId();
                logYaz($pdo,'ekle','arac','Araç eklendi: '.$plaka.' ('.$tur.') - '.$model, $yeni_id, null, ['tur'=>$tur,'plaka'=>$plaka,'model'=>$model], 'lite');
                flash('Araç eklendi.');
            } catch (PDOException $e) { flash('Bir hata oluştu.', 'danger'); }
        }
    } else { flash('Tüm alanlar zorunludur.', 'danger'); }
    header('Location: araclar.php'); exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duzenle'])) {
    csrfDogrula();
    $did   = (int)$_POST['duzenle_id'];
    $tur   = trim($_POST['duzenle_tur']);
    $plaka = strtoupper(trim($_POST['duzenle_plaka']));
    $model = trim($_POST['duzenle_model']);
    if ($did && $tur && $plaka && $model) {
        $sr = $pdo->prepare('SELECT * FROM lite_araclar WHERE id=?'); $sr->execute([$did]); $sr = $sr->fetch();
        $cakisma = $pdo->prepare("SELECT id FROM lite_araclar WHERE plaka=? AND id!=? AND aktif=1");
        $cakisma->execute([$plaka, $did]);
        if ($cakisma->fetch()) {
            flash('Bu plaka başka bir araçta kayıtlı.', 'danger');
        } else {
            $pdo->prepare("UPDATE lite_araclar SET arac_turu=?, plaka=?, marka_model=? WHERE id=?")->execute([$tur, $plaka, $model, $did]);
            logYaz($pdo,'guncelle','arac','Araç güncellendi: '.$plaka, $did,
                ['arac_turu'=>$sr['arac_turu'],'plaka'=>$sr['plaka'],'marka_model'=>$sr['marka_model']],
                ['arac_turu'=>$tur,'plaka'=>$plaka,'marka_model'=>$model], 'lite');
            flash('Araç güncellendi.');
        }
    } else { flash('Tüm alanlar zorunludur.', 'danger'); }
    header('Location: araclar.php'); exit;
}

if (isset($_GET['sil'])) {
    $sil_id = (int)$_GET['sil'];
    $sr = $pdo->prepare('SELECT * FROM lite_araclar WHERE id=?'); $sr->execute([$sil_id]); $sr = $sr->fetch();
    $pdo->prepare("UPDATE lite_araclar SET aktif=0 WHERE id=?")->execute([$sil_id]);
    if ($sr) logYaz($pdo,'sil','arac','Araç silindi: '.$sr['plaka'].' - '.$sr['marka_model'], $sil_id, $sr, null, 'lite');
    flash('Araç silindi.');
    header('Location: araclar.php'); exit;
}

$araclar = $pdo->query("SELECT a.*, k.ad_soyad FROM lite_araclar a LEFT JOIN kullanicilar k ON a.olusturan_id=k.id WHERE a.aktif=1 ORDER BY a.arac_turu, a.plaka")->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h1><span>🚗</span> Araç Yönetimi</h1>
</div>

<div class="card">
    <div class="card-title">➕ Yeni Araç Ekle</div>
    <form method="post">
        <?= csrfInput() ?>
        <div class="form-grid">
            <div class="form-group">
                <label>Araç Türü *</label>
                <select name="arac_turu" required>
                    <option value="">-- Seçin --</option>
                    <option>Damper</option><option>Transmikser</option><option>Kamyonet</option><option>Su Tankeri/Arazöz</option><option>Tır Silobas</option><option>Tır Lowbet</option>
                    <option>İş Makinesi</option><option>Beton Pompası</option>
                    <option>Binek</option><option>Ekskavatör</option>
                    <option>Forklift</option><option>Vinç/Hiyap</option>
                    <option>Rok</option><option>Diğer</option>
                </select>
            </div>
            <div class="form-group">
                <label>Plaka *</label>
                <input type="text" name="plaka" required placeholder="Örn: 34 ABC 123" maxlength="20">
            </div>
            <div class="form-group">
                <label>Marka / Model *</label>
                <input type="text" name="marka_model" required placeholder="Örn: Ford Cargo 1848T" maxlength="150">
            </div>
        </div>
        <div style="margin-top:14px;">
            <button type="submit" name="ekle" class="btn btn-primary">💾 Ekle</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="card-title">📋 Kayıtlı Araçlar (<?= count($araclar) ?>)</div>
    <?php if (empty($araclar)): ?>
    <div class="alert alert-info">Henüz araç kaydı yok.</div>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Tür</th><th>Plaka</th><th>Marka/Model</th><th>İşlem</th></tr></thead>
            <tbody>
            <?php foreach ($araclar as $i => $a): ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><span class="badge badge-info"><?= htmlspecialchars($a['arac_turu']) ?></span></td>
                <td><strong><?= htmlspecialchars($a['plaka']) ?></strong></td>
                <td><?= htmlspecialchars($a['marka_model']) ?></td>
                <td style="display:flex;gap:6px;flex-wrap:wrap;">
                    <a href="arac_detay.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-primary">👁️ Detay</a>
                    <button class="btn btn-sm btn-secondary"
                        onclick="aracDuzenleModal(<?= $a['id'] ?>, '<?= htmlspecialchars($a['arac_turu'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['plaka'], ENT_QUOTES) ?>', '<?= htmlspecialchars($a['marka_model'], ENT_QUOTES) ?>')">✏️ Düzenle</button>
                    <a href="?sil=<?= $a['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">🗑️</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Düzenleme Modal -->
<div id="aracDuzenleModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;">
    <div class="modal-box" style="max-width:460px;">
        <div style="font-weight:700;font-size:16px;margin-bottom:16px;">✏️ Araç Düzenle</div>
        <form method="post">
            <?= csrfInput() ?>
            <input type="hidden" name="duzenle_id" id="duzenle_arac_id">
            <div class="form-group">
                <label>Araç Türü *</label>
                <select name="duzenle_tur" id="duzenle_tur" required>
                    <?php foreach (['Damper','Transmikser','Kamyonet','Su Tankeri/Arazöz','Tır Silobas','Tır Lowbet','İş Makinesi','Beton Pompası','Binek','Ekskavatör','Forklift','Vinç/Hiyap','Rok','Diğer'] as $t): ?>
                    <option value="<?= $t ?>"><?= $t ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Plaka *</label>
                <input type="text" name="duzenle_plaka" id="duzenle_plaka" required maxlength="20">
            </div>
            <div class="form-group">
                <label>Marka / Model *</label>
                <input type="text" name="duzenle_model" id="duzenle_model" required maxlength="150">
            </div>
            <div style="display:flex;gap:8px;margin-top:16px;">
                <button type="submit" name="duzenle" class="btn btn-primary" style="flex:1;">💾 Kaydet</button>
                <button type="button" class="btn btn-secondary" onclick="document.getElementById('aracDuzenleModal').style.display='none'">İptal</button>
            </div>
        </form>
    </div>
</div>

<script>
function aracDuzenleModal(id, tur, plaka, model) {
    document.getElementById('duzenle_arac_id').value = id;
    document.getElementById('duzenle_tur').value = tur;
    document.getElementById('duzenle_plaka').value = plaka;
    document.getElementById('duzenle_model').value = model;
    var m = document.getElementById('aracDuzenleModal');
    m.style.display = 'flex';
}
document.getElementById('aracDuzenleModal').addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
