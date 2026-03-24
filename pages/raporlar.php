<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
girisKontrol();

$sayfa_basligi = 'Raporlar';
$ku = mevcutKullanici();

// ── FİLTRE DEĞERLERİ ──
$f_donem       = $_GET['donem']      ?? 'bu_ay';   // bugun / bu_hafta / bu_ay / gecen_ay / ozel
$f_tarih_bas   = $_GET['tarih_bas']  ?? '';
$f_tarih_bit   = $_GET['tarih_bit']  ?? '';
$f_urun_ids    = array_filter(array_map('intval', (array)($_GET['urun_ids'] ?? [])));
$f_tur         = in_array($_GET['tur'] ?? '', ['arac','tesis']) ? $_GET['tur'] : 'tumu';
$f_kullanici   = (int)($_GET['kullanici_id'] ?? 0);

// Dönem → tarih aralığına çevir
$bugun = date('Y-m-d');
switch ($f_donem) {
    case 'bugun':
        $tarih_bas = $bugun; $tarih_bit = $bugun; break;
    case 'bu_hafta':
        $tarih_bas = date('Y-m-d', strtotime('monday this week'));
        $tarih_bit = $bugun; break;
    case 'bu_ay':
        $tarih_bas = date('Y-m-01'); $tarih_bit = $bugun; break;
    case 'gecen_ay':
        $tarih_bas = date('Y-m-01', strtotime('first day of last month'));
        $tarih_bit = date('Y-m-t',  strtotime('last day of last month')); break;
    case 'ozel':
        $tarih_bas = $f_tarih_bas ?: date('Y-m-01');
        $tarih_bit = $f_tarih_bit ?: $bugun; break;
    default:
        $tarih_bas = date('Y-m-01'); $tarih_bit = $bugun;
}

// ── SORGU ──
$where  = ["lk.aktif = 1", "lk.tarih BETWEEN ? AND ?"];
$params = [$tarih_bas, $tarih_bit];

if ($f_tur === 'arac') {
    $where[] = "lk.kayit_turu = 'arac'";
} elseif ($f_tur === 'tesis') {
    $where[] = "lk.kayit_turu = 'tesis'";
}

if ($f_kullanici) {
    $where[] = "lk.olusturan_id = ?";
    $params[] = $f_kullanici;
}

if (!empty($f_urun_ids)) {
    $placeholders = implode(',', array_fill(0, count($f_urun_ids), '?'));
    $where[] = "lk.urun_id IN ($placeholders)";
    $params  = array_merge($params, $f_urun_ids);
}

$where_sql = implode(" AND ", $where);

$kayitlar = $pdo->prepare("
    SELECT
        lk.*,
        u.urun_adi, u.urun_kodu,
        a.plaka, a.marka_model, a.arac_turu,
        t.firma_adi,
        k.ad_soyad
    FROM lite_kayitlar lk
    JOIN lite_urunler u  ON lk.urun_id    = u.id
    LEFT JOIN lite_araclar  a ON lk.arac_id    = a.id
    LEFT JOIN lite_tesisler t ON lk.tesis_id   = t.id
    LEFT JOIN kullanicilar  k ON lk.olusturan_id = k.id
    WHERE $where_sql
    ORDER BY lk.tarih DESC, lk.olusturma_tarihi DESC
");
$kayitlar->execute($params);
$kayitlar = $kayitlar->fetchAll();

// ── ÖZET: Ürün bazlı toplam ──
$urun_ozet = [];
foreach ($kayitlar as $r) {
    $key = $r['urun_id'];
    if (!isset($urun_ozet[$key])) {
        $urun_ozet[$key] = [
            'urun_kodu' => $r['urun_kodu'],
            'urun_adi'  => $r['urun_adi'],
            'toplam'    => 0,
            'adet'      => 0,
        ];
    }
    $urun_ozet[$key]['toplam'] += $r['miktar'];
    $urun_ozet[$key]['adet']++;
}
usort($urun_ozet, fn($a,$b) => $b['toplam'] <=> $a['toplam']);

$genel_toplam = array_sum(array_column($kayitlar, 'miktar'));

// ── Filtre listeleri ──
$tum_urunler     = $pdo->query("SELECT id, urun_kodu, urun_adi FROM lite_urunler WHERE aktif=1 ORDER BY urun_adi")->fetchAll();
$tum_kullanicilar = $pdo->query("SELECT id, ad_soyad FROM kullanicilar WHERE aktif=1 ORDER BY ad_soyad")->fetchAll();

// PDF modu?
$pdf_mod = isset($_GET['pdf']);

if ($pdf_mod) {
    // PDF: sadece print view render et
    ob_start();
}

if (!$pdf_mod) require_once __DIR__ . '/../includes/header.php';
?>

<?php if ($pdf_mod): ?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Rapor <?= htmlspecialchars($tarih_bas) ?> / <?= htmlspecialchars($tarih_bit) ?></title>
<style>
    body { font-family: Arial, sans-serif; font-size: 12px; color: #222; margin: 20px; }
    h1 { font-size: 18px; margin-bottom: 4px; }
    .meta { font-size: 11px; color: #666; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th { background: #1e4d6b; color: #fff; padding: 6px 8px; text-align: left; font-size: 11px; }
    td { padding: 5px 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
    tr:nth-child(even) td { background: var(--hover-bg); }
    .ozet-tablo th { background: #374151; }
    .toplam-row td { font-weight: 700; background: #f0f9ff !important; }
    .badge { display:inline-block; padding:1px 6px; border-radius:4px; font-size:10px; font-weight:700; }
    /* badge-arac → style.css */
    /* badge-tesis → style.css */
    @media print {
        @page { margin: 15mm; }
        body { margin: 0; }
    }
</style>
</head>
<body>
<?php else: ?>
<div class="page-header">
    <h1><span>📊</span> Raporlar</h1>
    <a href="?<?= http_build_query(array_merge($_GET, ['pdf'=>'1'])) ?>" target="_blank" class="btn btn-primary btn-sm">🖨️ PDF / Yazdır</a>
</div>

<!-- FİLTRE -->
<div class="card">
    <div class="card-title">🔍 Rapor Filtresi</div>
    <form method="get" id="rapor_form">
        <div class="form-grid">
            <!-- Dönem -->
            <div class="form-group">
                <label>Dönem</label>
                <select name="donem" onchange="donemDegisti(this.value)">
                    <option value="bugun"      <?= $f_donem=='bugun'     ?'selected':'' ?>>Bugün</option>
                    <option value="bu_hafta"   <?= $f_donem=='bu_hafta'  ?'selected':'' ?>>Bu Hafta</option>
                    <option value="bu_ay"      <?= $f_donem=='bu_ay'     ?'selected':'' ?>>Bu Ay</option>
                    <option value="gecen_ay"   <?= $f_donem=='gecen_ay'  ?'selected':'' ?>>Geçen Ay</option>
                    <option value="ozel"       <?= $f_donem=='ozel'      ?'selected':'' ?>>Özel Aralık</option>
                </select>
            </div>
            <!-- Özel tarih -->
            <div class="form-group" id="ozel_tarih_bas" style="<?= $f_donem!='ozel'?'display:none':'' ?>">
                <label>Başlangıç</label>
                <input type="date" name="tarih_bas" value="<?= htmlspecialchars($f_tarih_bas) ?>">
            </div>
            <div class="form-group" id="ozel_tarih_bit" style="<?= $f_donem!='ozel'?'display:none':'' ?>">
                <label>Bitiş</label>
                <input type="date" name="tarih_bit" value="<?= htmlspecialchars($f_tarih_bit) ?>">
            </div>
            <!-- Tür -->
            <div class="form-group">
                <label>Kayıt Türü</label>
                <select name="tur">
                    <option value="tumu"  <?= $f_tur=='tumu' ?'selected':'' ?>>Tümü</option>
                    <option value="arac"  <?= $f_tur=='arac' ?'selected':'' ?>>🚗 Araçlar</option>
                    <option value="tesis" <?= $f_tur=='tesis'?'selected':'' ?>>🏭 Tesisler</option>
                </select>
            </div>
            <!-- Kullanıcı -->
            <div class="form-group">
                <label>Kaydeden</label>
                <select name="kullanici_id">
                    <option value="">Tüm Kullanıcılar</option>
                    <?php foreach ($tum_kullanicilar as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $f_kullanici==$u['id']?'selected':'' ?>>
                        <?= htmlspecialchars($u['ad_soyad']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Çoklu Ürün Seçimi -->
        <div class="form-group" style="margin-top:12px;">
            <label>Ürün Seçimi <span style="font-weight:400;color:var(--muted);font-size:12px;">(boş bırakılırsa tümü)</span></label>
            <div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;">
                <?php foreach ($tum_urunler as $u): ?>
                <label style="display:flex;align-items:center;gap:5px;font-size:13px;font-weight:400;cursor:pointer;
                              padding:4px 10px;border:1.5px solid var(--border);border-radius:6px;
                              <?= in_array($u['id'], $f_urun_ids) ? 'background:var(--primary);color:#fff;border-color:var(--primary);' : '' ?>">
                    <input type="checkbox" name="urun_ids[]" value="<?= $u['id'] ?>"
                           <?= in_array($u['id'], $f_urun_ids) ? 'checked' : '' ?>
                           style="display:none;" onchange="this.closest('label').classList.toggle('checked-urun', this.checked); this.closest('label').style.background=this.checked?'var(--primary)':''; this.closest('label').style.color=this.checked?'#fff':''; this.closest('label').style.borderColor=this.checked?'var(--primary)':'var(--border)';">
                    <?= htmlspecialchars($u['urun_kodu']) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="btn-group" style="margin-top:14px;">
            <button type="submit" class="btn btn-primary">📊 Raporu Getir</button>
            <a href="raporlar.php" class="btn btn-secondary">✕ Temizle</a>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- RAPOR BAŞLIĞI (her iki modda) -->
<?php
$donem_etiket = [
    'bugun'     => 'Bugün',
    'bu_hafta'  => 'Bu Hafta',
    'bu_ay'     => 'Bu Ay',
    'gecen_ay'  => 'Geçen Ay',
    'ozel'      => 'Özel Aralık',
][$f_donem] ?? 'Bu Ay';

$tur_etiket = ['arac'=>'Araçlar','tesis'=>'Tesisler','tumu'=>'Tümü'][$f_tur];

$secili_urun_adlari = [];
if (!empty($f_urun_ids)) {
    foreach ($tum_urunler as $u) {
        if (in_array($u['id'], $f_urun_ids)) $secili_urun_adlari[] = $u['urun_kodu'];
    }
}
?>

<?php if ($pdf_mod): ?>
<h1>📊 <?= SITE_ADI ?> — Rapor</h1>
<div class="meta">
    Dönem: <strong><?= htmlspecialchars($tarih_bas) ?> – <?= htmlspecialchars($tarih_bit) ?></strong>
    &nbsp;|&nbsp; Tür: <strong><?= $tur_etiket ?></strong>
    <?php if (!empty($secili_urun_adlari)): ?>
    &nbsp;|&nbsp; Ürünler: <strong><?= htmlspecialchars(implode(', ', $secili_urun_adlari)) ?></strong>
    <?php endif; ?>
    &nbsp;|&nbsp; Toplam: <strong><?= number_format($genel_toplam, 2, ',', '.') ?> L</strong>
    &nbsp;|&nbsp; <?= count($kayitlar) ?> kayıt
    &nbsp;|&nbsp; Oluşturulma: <?= date('d.m.Y H:i') ?>
</div>
<?php else: ?>
<div class="card" style="padding:14px 16px;">
    <div style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;">
        <div>
            <div style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;">Dönem</div>
            <div style="font-weight:700;"><?= $donem_etiket ?>: <?= date('d.m.Y', strtotime($tarih_bas)) ?> – <?= date('d.m.Y', strtotime($tarih_bit)) ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;">Toplam Miktar</div>
            <div style="font-weight:700;font-size:20px;color:var(--primary-l);"><?= number_format($genel_toplam, 2, ',', '.') ?> L</div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;">Kayıt Sayısı</div>
            <div style="font-weight:700;"><?= count($kayitlar) ?></div>
        </div>
        <?php if (!empty($secili_urun_adlari)): ?>
        <div>
            <div style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;">Ürünler</div>
            <div style="font-weight:600;"><?= htmlspecialchars(implode(', ', $secili_urun_adlari)) ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($kayitlar)): ?>

<!-- ÖZET TABLO: Ürün Bazlı -->
<?php if ($pdf_mod): ?>
<h3 style="margin:16px 0 8px;">Ürün Bazlı Özet</h3>
<table class="ozet-tablo">
    <thead><tr><th>Ürün Kodu</th><th>Ürün Adı</th><th>İşlem Sayısı</th><th>Toplam (L)</th></tr></thead>
    <tbody>
    <?php foreach ($urun_ozet as $o): ?>
    <tr>
        <td><?= htmlspecialchars($o['urun_kodu']) ?></td>
        <td><?= htmlspecialchars($o['urun_adi']) ?></td>
        <td><?= $o['adet'] ?></td>
        <td><?= number_format($o['toplam'], 2, ',', '.') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="toplam-row">
        <td colspan="2"><strong>GENEL TOPLAM</strong></td>
        <td><strong><?= count($kayitlar) ?></strong></td>
        <td><strong><?= number_format($genel_toplam, 2, ',', '.') ?> L</strong></td>
    </tr>
    </tbody>
</table>
<?php else: ?>
<div class="card">
    <div class="card-title">📦 Ürün Bazlı Özet</div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Ürün Kodu</th><th>Ürün Adı</th><th>İşlem</th><th>Toplam</th></tr></thead>
            <tbody>
            <?php foreach ($urun_ozet as $o): ?>
            <tr>
                <td><code><?= htmlspecialchars($o['urun_kodu']) ?></code></td>
                <td><?= htmlspecialchars($o['urun_adi']) ?></td>
                <td><?= $o['adet'] ?></td>
                <td><strong><?= number_format($o['toplam'], 2, ',', '.') ?> L</strong></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background:var(--hover);font-weight:700;">
                <td colspan="2">Genel Toplam</td>
                <td><?= count($kayitlar) ?></td>
                <td><?= number_format($genel_toplam, 2, ',', '.') ?> L</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- DETAY TABLO -->
<?php if ($pdf_mod): ?>
<h3 style="margin:16px 0 8px;">Detaylı Kayıtlar</h3>
<table>
    <thead>
        <tr>
            <th>Tarih</th>
            <th>Tür</th>
            <th>Araç / Tesis</th>
            <th>Ürün</th>
            <th>Miktar (L)</th>
            <th>Kaydeden</th>
            <th>Açıklama</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($kayitlar as $r): ?>
    <tr>
        <td><?= date('d.m.Y', strtotime($r['tarih'])) ?></td>
        <td><?= $r['kayit_turu'] === 'arac' ? 'Araç' : 'Tesis' ?></td>
        <td>
            <?php if ($r['kayit_turu'] === 'arac'): ?>
                <?= htmlspecialchars($r['plaka']) ?><br>
                <span class="text-muted-sm"><?= htmlspecialchars($r['marka_model']) ?></span>
            <?php else: ?>
                <?= htmlspecialchars($r['firma_adi']) ?>
            <?php endif; ?>
        </td>
        <td>
            <?= htmlspecialchars($r['urun_kodu']) ?><br>
            <span class="text-muted-sm"><?= htmlspecialchars($r['urun_adi']) ?></span>
        </td>
        <td><?= number_format($r['miktar'], 2, ',', '.') ?></td>
        <td><?= htmlspecialchars($r['ad_soyad'] ?? '-') ?></td>
        <td><?= $r['aciklama'] ? htmlspecialchars($r['aciklama']) : '<span class="text-empty">—</span>' ?>
            <?php if ($r['yag_bakimi']): ?>
            <br><span class="text-warning-sm">🔧 YAĞ BAKIMI<?= $r['mevcut_km'] ? ' — '.number_format($r['mevcut_km']).' KM' : '' ?></span>
            <?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<div class="card">
    <div class="card-title">📋 Detaylı Kayıtlar <span style="font-weight:400;font-size:13px;color:var(--muted);">(<?= count($kayitlar) ?>)</span></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Tür</th>
                    <th>Araç / Tesis</th>
                    <th>Ürün</th>
                    <th>Miktar</th>
                    <th>Kaydeden</th>
                    <th>Açıklama</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($kayitlar as $r): ?>
            <tr>
                <td><?= date('d.m.Y', strtotime($r['tarih'])) ?></td>
                <td>
                    <?php if ($r['kayit_turu'] === 'arac'): ?>
                    <span class="badge badge-info">🚗 Araç</span>
                    <?php else: ?>
                    <span class="badge badge-success">🏭 Tesis</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($r['kayit_turu'] === 'arac'): ?>
                    <strong><?= htmlspecialchars($r['plaka']) ?></strong><br>
                    <span style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($r['marka_model']) ?></span>
                    <?php else: ?>
                    <strong><?= htmlspecialchars($r['firma_adi']) ?></strong>
                    <?php endif; ?>
                </td>
                <td>
                    <code><?= htmlspecialchars($r['urun_kodu']) ?></code><br>
                    <span style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($r['urun_adi']) ?></span>
                </td>
                <td><strong><?= number_format($r['miktar'], 2, ',', '.') ?> L</strong></td>
                <td><?= htmlspecialchars($r['ad_soyad'] ?? '-') ?></td>
                <td style="font-size:12px;color:var(--muted);">
                    <?= $r['aciklama'] ? htmlspecialchars($r['aciklama']) : '—' ?>
                    <?php if ($r['yag_bakimi']): ?>
                    <br><span style="font-size:11px;font-weight:700;color:var(--warning);">🔧 YAĞ BAKIMI<?= $r['mevcut_km'] ? ' — '.number_format($r['mevcut_km']).' KM' : '' ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php else: ?>
<?php if (!$pdf_mod): ?>
<div class="alert alert-info" style="margin-top:14px;">Bu filtreye uygun kayıt bulunamadı.</div>
<?php endif; ?>
<?php endif; ?>

<?php if ($pdf_mod): ?>
<script>window.onload = function(){ window.print(); }</script>
</body></html>
<?php else: ?>
<script>
function donemDegisti(val) {
    var ozel = val === 'ozel';
    document.getElementById('ozel_tarih_bas').style.display = ozel ? '' : 'none';
    document.getElementById('ozel_tarih_bit').style.display = ozel ? '' : 'none';
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php endif; ?>