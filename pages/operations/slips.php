<?php
/*
 * Project Industry - Vehicle and Facility product tracking management system
 * Copyright (C) 2026 Awosk
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/log.php';
require_once __DIR__ . '/../../classes/Fis.php';
require_once __DIR__ . '/../../classes/Arac.php';
require_once __DIR__ . '/../../classes/Tesis.php';
require_once __DIR__ . '/../../classes/Urun.php';

girisKontrol();

$sayfa_basligi = 'Fiş Yönetimi';
$ku = mevcutKullanici();

// ── YENİ FİŞ OLUŞTUR ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fis_ekle'])) {
    csrfDogrula();
    $kayit_turu = ($_POST['kayit_turu'] ?? '') === 'tesis' ? 'tesis' : 'arac';
    $hedef_id   = (int)($_POST['hedef_id'] ?? 0);
    $urun_id    = (int)($_POST['urun_id'] ?? 0);
    $miktar     = (float)str_replace(',', '.', $_POST['miktar'] ?? '0');
    $aciklama   = trim($_POST['aciklama'] ?? '');
    $yag_bakimi = isset($_POST['yag_bakimi']) ? 1 : 0;
    $mevcut_km  = (!empty($_POST['mevcut_km'])) ? (int)$_POST['mevcut_km'] : null;

    if ($hedef_id > 0 && $urun_id > 0 && $miktar > 0) {
        $yeni_id = Fis::ekle($pdo, $kayit_turu, $hedef_id, $urun_id, $miktar, $aciklama, $yag_bakimi, $mevcut_km, $ku['id']);
        
        $u = Urun::bulId($pdo, $urun_id);
        $urun_ad = $u ? ($u['urun_kodu'] . ' ' . $u['urun_adi']) : '?';
        $hedef_ad = '?';
        if ($kayit_turu === 'arac') {
            $a = Arac::aktifDetayliBulId($pdo, $hedef_id);
            $hedef_ad = $a ? $a['plaka'] : '?';
        } else {
            $t = Tesis::bulId($pdo, $hedef_id);
            $hedef_ad = $t ? $t['firma_adi'] : '?';
        }

        $log_mesaj = "Yeni çıkış fişi açıldı: #$yeni_id — $hedef_ad ($urun_ad, $miktar " . ($u['birim'] ?? 'LT') . ")";
        logYaz($pdo, 'ekle', 'fis', $log_mesaj, $yeni_id, null, [
            'kayit_turu' => $kayit_turu,
            'hedef_id'   => $hedef_id,
            'urun_id'    => $urun_id,
            'miktar'     => $miktar,
            'aciklama'   => $aciklama
        ], 'lite');

        flash("Fiş #$yeni_id başarıyla oluşturuldu.");
    } else {
        flash('Lütfen tüm zorunlu alanları eksiksiz ve geçerli doldurun.', 'danger');
    }
    header('Location: slips.php');
    exit;
}

// ── FİŞ ONAYLA (SAHA PERSONELİ) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fis_onayla'])) {
    csrfDogrula();
    $fis_id = (int)($_POST['fis_id'] ?? 0);
    $slip   = Fis::bul($pdo, $fis_id);

    if ($slip && $slip['durum'] === 'bekliyor') {
        $kayit_id = Fis::onayla($pdo, $fis_id, $ku['id']);
        if ($kayit_id) {
            $hedef_ad = $slip['kayit_turu'] === 'arac' ? $slip['plaka'] : $slip['firma_adi'];
            $log_mesaj = "Fiş #$fis_id onaylandı ve çıkış işlendi: $hedef_ad — {$slip['urun_adi']}, {$slip['miktar']} {$slip['birim']}";
            logYaz($pdo, 'guncelle', 'fis', $log_mesaj, $fis_id, ['durum' => 'bekliyor'], ['durum' => 'onaylandi', 'kayit_id' => $kayit_id], 'lite');
            flash("Fiş #$fis_id onaylandı ve ürün çıkışı başarıyla gerçekleştirildi.");
        } else {
            flash('Fiş onaylanırken bir hata oluştu.', 'danger');
        }
    } else {
        flash('Onaylanacak fiş bulunamadı veya daha önce işlem görmüş.', 'danger');
    }
    header('Location: slips.php');
    exit;
}

// ── FİŞ İPTAL ET (DEPOCU / ADMİN) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fis_iptal'])) {
    csrfDogrula();
    $fis_id = (int)($_POST['fis_id'] ?? 0);
    $slip   = Fis::bul($pdo, $fis_id);

    if ($slip && $slip['durum'] === 'bekliyor') {
        if (isAdmin() || (int)$slip['olusturan_id'] === (int)$ku['id']) {
            Fis::iptalEt($pdo, $fis_id, $ku['id']);
            logYaz($pdo, 'guncelle', 'fis', "Fiş #$fis_id iptal edildi", $fis_id, ['durum' => 'bekliyor'], ['durum' => 'iptal'], 'lite');
            flash("Fiş #$fis_id iptal edildi.");
        } else {
            flash('Sadece fişi oluşturan personel veya admin iptal edebilir.', 'danger');
        }
    } else {
        flash('İptal edilecek fiş bulunamadı.', 'danger');
    }
    header('Location: slips.php');
    exit;
}

// ── İPTAL EDİLMİŞ VEYA KAYDI SİLİNMİŞ FİŞİ SİL ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fis_sil'])) {
    csrfDogrula();
    $fis_id = (int)($_POST['fis_id'] ?? 0);
    $slip   = Fis::bul($pdo, $fis_id);

    if ($slip) {
        $kayit_silinmis = ($slip['durum'] === 'onaylandi' && $slip['kayit_id'] && ($slip['kayit_aktif'] === null || (int)$slip['kayit_aktif'] === 0));
        if ($slip['durum'] === 'iptal' || $kayit_silinmis) {
            if (isAdmin() || (int)$slip['olusturan_id'] === (int)$ku['id']) {
                Fis::sil($pdo, $fis_id);
                logYaz($pdo, 'sil', 'fis', "Fiş kalıcı olarak silindi: #$fis_id", $fis_id, null, null, 'lite');
                flash("Fiş #$fis_id kalıcı olarak silindi.");
            } else {
                flash('Sadece fişi oluşturan personel veya admin silebilir.', 'danger');
            }
        } else {
            flash('Yalnızca iptal edilmiş veya bağlı kaydı silinmiş fişler silinebilir.', 'danger');
        }
    } else {
        flash('Silinecek fiş bulunamadı.', 'danger');
    }
    $tab = $_GET['tab'] ?? 'onaylandi';
    header('Location: slips.php?tab=' . urlencode($tab));
    exit;
}

// ── LİSTELEME VE FİLTRELEME ──
$tab = $_GET['tab'] ?? 'bekliyor';
if (!in_array($tab, ['bekliyor', 'onaylandi', 'iptal', 'tumu'])) {
    $tab = 'bekliyor';
}

$durum_filtre = ($tab === 'tumu') ? null : $tab;
$fisler = Fis::listele($pdo, $durum_filtre);
$bekleyen_adet = Fis::bekleyenSayisi($pdo);

// Modal Seçim Verileri
$araclar   = Arac::tumAraclar($pdo);
$tesisler  = Tesis::tumTesislerIdAd($pdo);
$urunler   = Urun::tumUrunler($pdo);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="page-header" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <div>
        <h1 style="margin:0;font-size:22px;display:flex;align-items:center;gap:8px;">
            <span>🧾</span> Fiş Yönetimi
            <?php if ($bekleyen_adet > 0): ?>
                <span class="badge badge-warning" style="font-size:13px;"><?= $bekleyen_adet ?> Bekliyor</span>
            <?php endif; ?>
        </h1>
        <p style="margin:4px 0 0;color:var(--muted);font-size:13px;">Depo taleplerini ve saha çıkış onaylarını yönetin.</p>
    </div>
    <div>
        <button class="btn btn-primary" onclick="fisModalAc()">
            <span>➕</span> Yeni Fiş Aç
        </button>
    </div>
</div>

<!-- Sekmeler (Tabs) -->
<div class="fis-tabs" style="display:flex;gap:8px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px;overflow-x:auto;">
    <a href="slips.php?tab=bekliyor" class="btn <?= $tab === 'bekliyor' ? 'btn-primary' : 'btn-secondary' ?>" style="display:flex;align-items:center;gap:6px;">
        <span>⏳ Bekleyen Fişler</span>
        <?php if ($bekleyen_adet > 0): ?>
            <span class="badge" style="background:rgba(255,255,255,0.25);color:inherit;font-size:11px;"><?= $bekleyen_adet ?></span>
        <?php endif; ?>
    </a>
    <a href="slips.php?tab=onaylandi" class="btn <?= $tab === 'onaylandi' ? 'btn-primary' : 'btn-secondary' ?>">
        <span>✅ Onaylanan Fişler</span>
    </a>
    <a href="slips.php?tab=iptal" class="btn <?= $tab === 'iptal' ? 'btn-primary' : 'btn-secondary' ?>">
        <span>🚫 İptal Edilenler</span>
    </a>
    <a href="slips.php?tab=tumu" class="btn <?= $tab === 'tumu' ? 'btn-primary' : 'btn-secondary' ?>">
        <span>📋 Tümü</span>
    </a>
</div>

<!-- Fiş Listesi -->
<?php if (empty($fisler)): ?>
    <div class="card" style="text-align:center;padding:40px 20px;color:var(--muted);">
        <div style="font-size:36px;margin-bottom:12px;">📭</div>
        <div style="font-size:15px;font-weight:600;">Bu kategoride henüz fiş bulunmuyor.</div>
        <div style="font-size:13px;margin-top:6px;">Yeni bir ürün çıkışı talebi açmak için "Yeni Fiş Aç" butonunu kullanabilirsiniz.</div>
    </div>
<?php else: ?>
    <div style="display:flex;flex-direction:column;gap:12px;">
        <?php foreach ($fisler as $f): 
            $is_arac = ($f['kayit_turu'] === 'arac');
            $hedef_baslik = $is_arac ? htmlspecialchars($f['plaka']) : htmlspecialchars($f['firma_adi']);
            $hedef_alt = $is_arac ? htmlspecialchars($f['marka_model'] ?? '') : 'Endüstriyel Tesis';
            $hedef_link = $is_arac ? "vehicle_detail.php?id=" . $f['arac_id'] : "facility_detail.php?id=" . $f['tesis_id'];
            $kayit_silinmis = ($f['durum'] === 'onaylandi' && $f['kayit_id'] && ($f['kayit_aktif'] === null || (int)$f['kayit_aktif'] === 0));
            
            $border_renk = 'var(--primary-l)';
            if ($f['durum'] === 'onaylandi') {
                $border_renk = $kayit_silinmis ? '#e74c3c' : '#27ae60';
            } elseif ($f['durum'] === 'iptal') {
                $border_renk = '#e74c3c';
            }
        ?>
        <div class="card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:16px;padding:16px;border-left:4px solid <?= $border_renk ?>;">
            
            <!-- Sol Bilgi -->
            <div style="flex:1;min-width:240px;">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;flex-wrap:wrap;">
                    <span style="font-size:12px;font-weight:700;color:var(--muted);">#<?= $f['id'] ?></span>
                    <span class="badge badge-<?= $is_arac ? 'arac' : 'tesis' ?>" style="font-size:11px;">
                        <?= $is_arac ? '🚗 Araç' : '🏭 Tesis' ?>
                    </span>
                    <a href="<?= $hedef_link ?>" style="font-size:16px;font-weight:800;color:var(--primary);text-decoration:none;">
                        <?= $hedef_baslik ?>
                    </a>
                    <?php if ($hedef_alt): ?>
                        <span style="font-size:12px;color:var(--muted);">(<?= $hedef_alt ?>)</span>
                    <?php endif; ?>

                    <?php if ($f['durum'] === 'bekliyor'): ?>
                        <span class="badge badge-warning">⏳ Onay Bekliyor</span>
                    <?php elseif ($f['durum'] === 'onaylandi'): ?>
                        <?php if ($kayit_silinmis): ?>
                            <span class="badge badge-danger" style="background:#e74c3c;color:#fff;">🗑️ Kayıt Silindi</span>
                        <?php else: ?>
                            <span class="badge badge-success">✅ Onaylandı</span>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="badge badge-danger">🚫 İptal Edildi</span>
                    <?php endif; ?>

                    <?php if ($f['yag_bakimi']): ?>
                        <span class="badge badge-info" style="font-size:11px;">🛢️ Bakım<?= $f['mevcut_km'] ? ' - ' . number_format($f['mevcut_km']) . ' KM' : '' ?></span>
                    <?php endif; ?>
                </div>

                <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:6px;">
                    <span style="font-size:15px;font-weight:700;color:var(--text);">
                        <?= htmlspecialchars($f['urun_kodu']) ?> — <?= htmlspecialchars($f['urun_adi']) ?>
                    </span>
                    <span style="font-size:16px;font-weight:800;color:var(--primary-l);">
                        <?= formatliMiktar($f['miktar'], $f['birim']) ?>
                    </span>
                </div>

                <?php if (!empty($f['aciklama'])): ?>
                    <div style="font-size:12px;color:var(--text);margin-top:6px;background:rgba(0,0,0,0.03);padding:6px 10px;border-radius:4px;display:inline-block;">
                        💬 <?= htmlspecialchars($f['aciklama']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($kayit_silinmis): ?>
                    <div style="font-size:12px;color:#e74c3c;margin-top:6px;background:rgba(231,76,60,0.08);border:1px solid rgba(231,76,60,0.2);padding:6px 10px;border-radius:4px;display:inline-block;font-weight:600;">
                        ⚠️ Bu fiş onaylanarak ürün çıkışı yapılmıştı ancak çıkış kaydı araç/tesis üzerinden silinmiş.
                    </div>
                <?php endif; ?>

                <div style="font-size:11px;color:var(--muted);margin-top:8px;display:flex;gap:14px;flex-wrap:wrap;">
                    <span>👤 Açan: <strong><?= htmlspecialchars($f['olusturan_ad'] ?? 'Bilinmiyor') ?></strong> (<?= formatliTarih($f['olusturma_tarihi']) ?>)</span>
                    <?php if ($f['durum'] === 'onaylandi' && $f['onaylayan_ad']): ?>
                        <span>✅ Onaylayan: <strong><?= htmlspecialchars($f['onaylayan_ad']) ?></strong> (<?= formatliTarih($f['onay_tarihi']) ?>)</span>
                    <?php endif; ?>
                    <?php if ($f['durum'] === 'iptal' && $f['iptal_eden_ad']): ?>
                        <span>🚫 İptal Eden: <strong><?= htmlspecialchars($f['iptal_eden_ad']) ?></strong> (<?= formatliTarih($f['iptal_tarihi']) ?>)</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sağ Aksiyonlar -->
            <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <?php if ($f['durum'] === 'bekliyor'): ?>
                    <!-- Onay Butonu (Saha) -->
                    <form method="post" onsubmit="return confirm('Bu fişi onaylayarak ürün çıkışını yapmak istediğinize emin misiniz?\n\nAraç/Tesis: <?= addslashes($hedef_baslik) ?>\nÜrün: <?= addslashes($f['urun_adi']) ?> (<?= $f['miktar'] . ' ' . $f['birim'] ?>)\n\nStoktan düşülecek ve işlem kaydı açılacaktır.');">
                        <?= csrfInput() ?>
                        <input type="hidden" name="fis_id" value="<?= $f['id'] ?>">
                        <button type="submit" name="fis_onayla" class="btn btn-primary" style="display:flex;align-items:center;gap:6px;">
                            <span>✅</span> Onayla (Çıkış Yap)
                        </button>
                    </form>

                    <!-- İptal Butonu (Oluşturan / Admin) -->
                    <?php if (isAdmin() || (int)$f['olusturan_id'] === (int)$ku['id']): ?>
                    <form method="post" onsubmit="return confirm('Bu fişi iptal etmek istediğinize emin misiniz?');">
                        <?= csrfInput() ?>
                        <input type="hidden" name="fis_id" value="<?= $f['id'] ?>">
                        <button type="submit" name="fis_iptal" class="btn btn-secondary" style="color:var(--danger);border-color:var(--danger);">
                            <span>❌</span> İptal Et
                        </button>
                    </form>
                    <?php endif; ?>

                <?php elseif ($f['durum'] === 'onaylandi'): ?>
                    <?php if ($kayit_silinmis): ?>
                        <?php if (isAdmin() || (int)$f['olusturan_id'] === (int)$ku['id']): ?>
                        <form method="post" onsubmit="return confirm('Çıkış kaydı silinmiş olan bu fişi sistemden kalıcı olarak silmek istediğinize emin misiniz?');">
                            <?= csrfInput() ?>
                            <input type="hidden" name="fis_id" value="<?= $f['id'] ?>">
                            <button type="submit" name="fis_sil" class="btn btn-secondary" style="color:var(--danger);border-color:var(--danger);font-size:12px;display:flex;align-items:center;gap:4px;">
                                <span>🗑️</span> Fişi Sil
                            </button>
                        </form>
                        <?php endif; ?>
                    <?php elseif ($f['kayit_id']): ?>
                        <a href="transactions.php?id=<?= $f['kayit_id'] ?>" class="btn btn-secondary" style="font-size:12px;display:flex;align-items:center;gap:4px;">
                            <span>📋</span> İşlem Kaydı
                        </a>
                    <?php endif; ?>

                <?php elseif ($f['durum'] === 'iptal'): ?>
                    <?php if (isAdmin() || (int)$f['olusturan_id'] === (int)$ku['id']): ?>
                    <form method="post" onsubmit="return confirm('İptal edilmiş bu fişi kalıcı olarak silmek istediğinize emin misiniz?');">
                        <?= csrfInput() ?>
                        <input type="hidden" name="fis_id" value="<?= $f['id'] ?>">
                        <button type="submit" name="fis_sil" class="btn btn-secondary" style="color:var(--danger);font-size:12px;display:flex;align-items:center;gap:4px;">
                            <span>🗑️</span> Sil
                        </button>
                    </form>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ===================================================== -->
<!-- YENİ FİŞ MODAL -->
<!-- ===================================================== -->
<div id="fisModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:2000;align-items:center;justify-content:center;padding:16px;">
    <div class="modal-box" style="max-width:500px;width:100%;max-height:90vh;overflow-y:auto;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:10px;">
            <div style="font-weight:700;font-size:16px;display:flex;align-items:center;gap:6px;">
                <span>🧾</span> Yeni Çıkış Fişi Aç
            </div>
            <button type="button" onclick="fisModalKapat()" style="background:none;border:none;font-size:18px;cursor:pointer;color:var(--muted);">✕</button>
        </div>

        <form method="post">
            <?= csrfInput() ?>

            <!-- Kayıt Türü -->
            <div class="form-group" style="margin-bottom:14px;">
                <label style="font-weight:600;margin-bottom:6px;display:block;">Kayıt Türü</label>
                <div style="display:flex;gap:12px;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="radio" name="kayit_turu" value="arac" checked onchange="turDegisti('arac')">
                        <span>🚗 Araç</span>
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;">
                        <input type="radio" name="kayit_turu" value="tesis" onchange="turDegisti('tesis')">
                        <span>🏭 Tesis</span>
                    </label>
                </div>
            </div>

            <!-- Araç Seçimi (Aramalı) -->
            <div class="form-group" id="alan_arac" style="margin-bottom:14px;position:relative;">
                <label style="font-weight:600;margin-bottom:6px;display:block;">Araç Seçin *</label>
                <div style="position:relative;">
                    <input type="text" id="arac_arama_input" placeholder="🔍 Plaka veya araç ara (örn: 52 AGD veya Tank)..." autocomplete="off"
                           class="form-control" style="width:100%;padding:9px 34px 9px 12px;border-radius:6px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:13px;"
                           onfocus="dropdownAc('arac')" oninput="filtrele('arac')">
                    <span id="arac_temizle" onclick="secimTemizle('arac')" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-weight:bold;font-size:14px;" title="Temizle">✕</span>
                </div>
                <input type="hidden" name="hedef_id_arac" id="hedef_id_arac" value="">
                
                <div id="arac_dropdown" class="search-dropdown-menu" style="display:none;">
                    <?php foreach ($araclar as $a): ?>
                        <div class="search-opt-item arac-opt" 
                             data-id="<?= $a['id'] ?>" 
                             data-text="<?= htmlspecialchars(mb_strtolower($a['plaka'] . ' ' . ($a['marka_model'] ?? '') . ' ' . ($a['tur_adi'] ?? ''), 'UTF-8')) ?>"
                             data-label="<?= htmlspecialchars($a['plaka'] . ' (' . ($a['marka_model'] ?? 'Belirtilmedi') . ')') ?>"
                             onclick="ogeSec('arac', this)">
                            <div>
                                <span style="font-weight:700;color:var(--primary);font-size:13px;"><?= htmlspecialchars($a['plaka']) ?></span>
                                <?php if (!empty($a['marka_model'])): ?>
                                    <span style="color:var(--text);font-size:12px;margin-left:4px;">— <?= htmlspecialchars($a['marka_model']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($a['tur_adi'])): ?>
                                <span class="badge badge-info" style="font-size:10px;"><?= htmlspecialchars($a['tur_adi']) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div id="arac_yok" style="display:none;padding:12px;text-align:center;color:var(--muted);font-size:12px;">Eşleşen araç bulunamadı.</div>
                </div>
            </div>

            <!-- Tesis Seçimi (Aramalı) -->
            <div class="form-group" id="alan_tesis" style="display:none;margin-bottom:14px;position:relative;">
                <label style="font-weight:600;margin-bottom:6px;display:block;">Tesis Seçin *</label>
                <div style="position:relative;">
                    <input type="text" id="tesis_arama_input" placeholder="🔍 Tesis adı ara..." autocomplete="off"
                           class="form-control" style="width:100%;padding:9px 34px 9px 12px;border-radius:6px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:13px;"
                           onfocus="dropdownAc('tesis')" oninput="filtrele('tesis')">
                    <span id="tesis_temizle" onclick="secimTemizle('tesis')" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-weight:bold;font-size:14px;" title="Temizle">✕</span>
                </div>
                <input type="hidden" name="hedef_id_tesis" id="hedef_id_tesis" value="">
                
                <div id="tesis_dropdown" class="search-dropdown-menu" style="display:none;">
                    <?php foreach ($tesisler as $t): ?>
                        <div class="search-opt-item tesis-opt" 
                             data-id="<?= $t['id'] ?>" 
                             data-text="<?= htmlspecialchars(mb_strtolower($t['firma_adi'], 'UTF-8')) ?>"
                             data-label="<?= htmlspecialchars($t['firma_adi']) ?>"
                             onclick="ogeSec('tesis', this)">
                            <span style="font-weight:700;color:var(--primary);font-size:13px;"><?= htmlspecialchars($t['firma_adi']) ?></span>
                        </div>
                    <?php endforeach; ?>
                    <div id="tesis_yok" style="display:none;padding:12px;text-align:center;color:var(--muted);font-size:12px;">Eşleşen tesis bulunamadı.</div>
                </div>
            </div>

            <!-- Ürün Seçimi (Aramalı) -->
            <div class="form-group" style="margin-bottom:14px;position:relative;">
                <label style="font-weight:600;margin-bottom:6px;display:block;">Ürün *</label>
                <div style="position:relative;">
                    <input type="text" id="urun_arama_input" placeholder="🔍 Ürün adı veya kodu yazın (örn: 10w40, Castrol)..." autocomplete="off"
                           class="form-control" style="width:100%;padding:9px 34px 9px 12px;border-radius:6px;border:1px solid var(--border);background:var(--card);color:var(--text);font-size:13px;"
                           onfocus="dropdownAc('urun')" oninput="filtrele('urun')">
                    <span id="urun_temizle" onclick="secimTemizle('urun')" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);cursor:pointer;color:var(--muted);font-weight:bold;font-size:14px;" title="Temizle">✕</span>
                </div>
                <input type="hidden" name="urun_id" id="modal_urun_id" value="">
                
                <div id="urun_dropdown" class="search-dropdown-menu" style="display:none;">
                    <?php foreach ($urunler as $u): ?>
                        <div class="search-opt-item urun-opt" 
                             data-id="<?= $u['id'] ?>" 
                             data-text="<?= htmlspecialchars(mb_strtolower($u['urun_kodu'] . ' ' . $u['urun_adi'], 'UTF-8')) ?>"
                             data-label="<?= htmlspecialchars($u['urun_kodu'] . ' — ' . $u['urun_adi']) ?>"
                             onclick="ogeSec('urun', this)">
                            <div>
                                <span style="font-weight:700;color:var(--text);font-size:13px;"><?= htmlspecialchars($u['urun_kodu']) ?></span>
                                <span style="color:var(--muted);font-size:12px;margin-left:4px;">— <?= htmlspecialchars($u['urun_adi']) ?></span>
                            </div>
                            <span class="badge <?= $u['stok'] > 0 ? 'badge-success' : 'badge-danger' ?>" style="font-size:11px;">
                                Stok: <?= number_format($u['stok'], 2, ',', '.') ?> <?= htmlspecialchars($u['birim']) ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <div id="urun_yok" style="display:none;padding:12px;text-align:center;color:var(--muted);font-size:12px;">Eşleşen ürün bulunamadı.</div>
                </div>
            </div>

            <!-- Miktar -->
            <div class="form-group" style="margin-bottom:14px;">
                <label style="font-weight:600;margin-bottom:6px;display:block;">Miktar *</label>
                <input type="number" name="miktar" step="any" min="0.01" required class="form-control" placeholder="Örn: 20" style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid var(--border);background:var(--card);color:var(--text);">
            </div>

            <!-- Araç Ek Bilgileri (KM ve Yağ Bakımı) -->
            <div id="alan_arac_ek" style="margin-bottom:14px;background:rgba(0,0,0,0.02);padding:10px;border-radius:6px;border:1px dashed var(--border);">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;margin-bottom:8px;">
                    <input type="checkbox" name="yag_bakimi" id="yag_bakimi_chk" value="1" onchange="yagBakimiToggle()">
                    <span style="font-weight:600;">🛢️ Periyodik Yağ Bakımı</span>
                </label>
                <div id="km_alani" style="display:none;margin-top:6px;">
                    <label style="font-size:12px;color:var(--muted);display:block;margin-bottom:4px;">Mevcut KM</label>
                    <input type="number" name="mevcut_km" id="mevcut_km_input" placeholder="Örn: 154000" class="form-control" style="width:100%;padding:6px 10px;border-radius:6px;border:1px solid var(--border);background:var(--card);color:var(--text);">
                </div>
            </div>

            <!-- Açıklama / Not -->
            <div class="form-group" style="margin-bottom:16px;">
                <label style="font-weight:600;margin-bottom:6px;display:block;">Not / Açıklama</label>
                <textarea name="aciklama" rows="2" class="form-control" placeholder="Gerekirse talep detayını belirtin..." style="width:100%;padding:8px 10px;border-radius:6px;border:1px solid var(--border);background:var(--card);color:var(--text);resize:vertical;"></textarea>
            </div>

            <!-- Gizli Gerçek hedef_id -->
            <input type="hidden" name="hedef_id" id="modal_hedef_id" value="">

            <div style="display:flex;gap:8px;justify-content:flex-end;">
                <button type="button" class="btn btn-secondary" onclick="fisModalKapat()">İptal</button>
                <button type="submit" name="fis_ekle" class="btn btn-primary" onclick="return formDogrula()">
                    <span>💾</span> Fişi Oluştur
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function fisModalAc() {
    var m = document.getElementById('fisModal');
    m.style.display = 'flex';
}

function fisModalKapat() {
    var m = document.getElementById('fisModal');
    m.style.display = 'none';
    ['arac', 'tesis', 'urun'].forEach(function(t) {
        var dd = document.getElementById(t + '_dropdown');
        if (dd) dd.style.display = 'none';
    });
}

function turDegisti(tur) {
    var aracDiv = document.getElementById('alan_arac');
    var tesisDiv = document.getElementById('alan_tesis');
    var aracEkDiv = document.getElementById('alan_arac_ek');

    if (tur === 'arac') {
        aracDiv.style.display = 'block';
        tesisDiv.style.display = 'none';
        aracEkDiv.style.display = 'block';
    } else {
        aracDiv.style.display = 'none';
        tesisDiv.style.display = 'block';
        aracEkDiv.style.display = 'none';
    }
}

function yagBakimiToggle() {
    var chk = document.getElementById('yag_bakimi_chk');
    var km = document.getElementById('km_alani');
    km.style.display = chk.checked ? 'block' : 'none';
}

function trKucuk(str) {
    return (str || '').toLocaleLowerCase('tr-TR').trim();
}

function dropdownAc(tur) {
    ['arac', 'tesis', 'urun'].forEach(function(t) {
        if (t !== tur) {
            var el = document.getElementById(t + '_dropdown');
            if (el) el.style.display = 'none';
        }
    });
    var dd = document.getElementById(tur + '_dropdown');
    if (dd) dd.style.display = 'block';
    filtrele(tur);
}

function filtrele(tur) {
    var inputEl = document.getElementById(tur + '_arama_input');
    var val = trKucuk(inputEl.value);
    var items = document.querySelectorAll('.' + tur + '-opt');
    var yokDiv = document.getElementById(tur + '_yok');
    var temizle = document.getElementById(tur + '_temizle');
    
    if (temizle) {
        temizle.style.display = val.length > 0 ? 'block' : 'none';
    }

    var gorunen = 0;
    items.forEach(function(item) {
        var text = trKucuk(item.getAttribute('data-text'));
        if (!val || text.indexOf(val) > -1) {
            item.style.display = 'flex';
            gorunen++;
        } else {
            item.style.display = 'none';
        }
    });

    if (yokDiv) {
        yokDiv.style.display = (gorunen === 0) ? 'block' : 'none';
    }
}

function ogeSec(tur, el) {
    var id = el.getAttribute('data-id');
    var label = el.getAttribute('data-label');
    
    if (tur === 'arac') {
        document.getElementById('hedef_id_arac').value = id;
        document.getElementById('arac_arama_input').value = label;
    } else if (tur === 'tesis') {
        document.getElementById('hedef_id_tesis').value = id;
        document.getElementById('tesis_arama_input').value = label;
    } else if (tur === 'urun') {
        document.getElementById('modal_urun_id').value = id;
        document.getElementById('urun_arama_input').value = label;
    }
    
    var temizle = document.getElementById(tur + '_temizle');
    if (temizle) temizle.style.display = 'block';

    var dd = document.getElementById(tur + '_dropdown');
    if (dd) dd.style.display = 'none';
}

function secimTemizle(tur) {
    if (tur === 'arac') {
        document.getElementById('hedef_id_arac').value = '';
        document.getElementById('arac_arama_input').value = '';
        document.getElementById('arac_arama_input').focus();
    } else if (tur === 'tesis') {
        document.getElementById('hedef_id_tesis').value = '';
        document.getElementById('tesis_arama_input').value = '';
        document.getElementById('tesis_arama_input').focus();
    } else if (tur === 'urun') {
        document.getElementById('modal_urun_id').value = '';
        document.getElementById('urun_arama_input').value = '';
        document.getElementById('urun_arama_input').focus();
    }
    var temizle = document.getElementById(tur + '_temizle');
    if (temizle) temizle.style.display = 'none';
    filtrele(tur);
    dropdownAc(tur);
}

// Modal dışına tıklandığında dropdownları kapat
document.addEventListener('click', function(e) {
    ['arac', 'tesis', 'urun'].forEach(function(t) {
        var input = document.getElementById(t + '_arama_input');
        var dropdown = document.getElementById(t + '_dropdown');
        if (dropdown && dropdown.style.display !== 'none') {
            if (!input.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        }
    });
});

function formDogrula() {
    var tur = document.querySelector('input[name="kayit_turu"]:checked').value;
    var hedefIdInput = document.getElementById('modal_hedef_id');
    
    if (tur === 'arac') {
        var val = document.getElementById('hedef_id_arac').value;
        if (!val) {
            alert('Lütfen listeden bir araç arayıp seçin.');
            document.getElementById('arac_arama_input').focus();
            return false;
        }
        hedefIdInput.value = val;
    } else {
        var val = document.getElementById('hedef_id_tesis').value;
        if (!val) {
            alert('Lütfen listeden bir tesis arayıp seçin.');
            document.getElementById('tesis_arama_input').focus();
            return false;
        }
        hedefIdInput.value = val;
    }
    
    var urunVal = document.getElementById('modal_urun_id').value;
    if (!urunVal) {
        alert('Lütfen listeden bir ürün arayıp seçin.');
        document.getElementById('urun_arama_input').focus();
        return false;
    }

    return true;
}

// ── POLLING: YENİ FİŞ BİLDİRİMİ (HER 10 SANİYE) ──
(function() {
    var sonId = <?= Fis::sonBekleyenId($pdo) ?>;
    var yeniFisSayisi = 0;
    var bildirim = null;
    var pollingInterval = null;

    function bildirimGoster() {
        if (!bildirim) {
            bildirim = document.createElement('div');
            bildirim.id = 'yeni-fis-bildirim';
            bildirim.style.cssText = 'position:fixed;top:70px;right:20px;background:#1e4d6b;color:#fff;padding:16px 20px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.25);z-index:9999;display:flex;align-items:center;gap:12px;animation:slideIn 0.3s ease-out;max-width:360px;';
            document.body.appendChild(bildirim);
        }
        bildirim.innerHTML = `
            <div style="flex:1;">
                <div style="font-weight:700;font-size:14px;">🔔 ${yeniFisSayisi} Yeni Fiş Açıldı!</div>
                <div style="font-size:12px;opacity:0.85;margin-top:2px;">Bekleyen fişleri görmek için listeyi yenileyin</div>
            </div>
            <button onclick="location.reload()" style="background:var(--card);color:var(--primary);border:none;padding:8px 14px;border-radius:6px;font-weight:700;font-size:12px;cursor:pointer;white-space:nowrap;">🔄 Yenile</button>
        `;
    }

    function yeniFisKontrol() {
        fetch('<?= ROOT_URL ?>api/check_pending_slips.php?lastId=' + sonId)
            .then(r => r.json())
            .then(data => {
                if (data.ok && data.yeni_var) {
                    sonId = data.son_id;
                    yeniFisSayisi += data.yeni_sayisi;
                    bildirimGoster();
                }
            })
            .catch(function(){});
    }

    pollingInterval = setInterval(yeniFisKontrol, 10000);

    window.addEventListener('beforeunload', function() {
        if (pollingInterval) clearInterval(pollingInterval);
    });
})();
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
