<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (girisYapildi()) { header('Location: ' . ROOT_URL . 'index.php'); exit; }

$hata = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kadi  = trim($_POST['kullanici_adi'] ?? '');
    $sifre = $_POST['sifre'] ?? '';
    if ($kadi && $sifre) {
        $stmt = $pdo->prepare("SELECT * FROM kullanicilar WHERE kullanici_adi=? AND aktif=1");
        $stmt->execute([$kadi]);
        $k = $stmt->fetch();
        if ($k && password_verify($sifre, $k['sifre'])) {
            $_SESSION['kullanici_id']  = $k['id'];
            $_SESSION['kullanici_adi'] = $k['kullanici_adi'];
            $_SESSION['ad_soyad']      = $k['ad_soyad'];
            $_SESSION['kullanici_rol'] = $k['rol'];
            $_SESSION['kullanici_tema']= $k['tema'] ?? 'light';
            require_once __DIR__ . '/includes/log.php';
            logGiris($pdo, $k, 'lite');
            header('Location: ' . ROOT_URL . 'index.php'); exit;
        } else { $hata = 'Kullanıcı adı veya şifre hatalı.'; }
    } else { $hata = 'Lütfen tüm alanları doldurun.'; }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#1e4d6b">
    <title>Giriş | <?= SITE_ADI ?></title>
    <link rel="stylesheet" href="<?= ROOT_URL ?>assets/css/style.css">
</head>
<body>
<div class="login-page">
    <div class="login-box">
        <div class="login-logo">
            <div class="logo-icon">🔩</div>
            <h2><?= SITE_ADI ?></h2>
            <p>Araç/Tesis Yağ Takip Sistemi</p>
        </div>
        <?php if ($hata): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($hata) ?></div>
        <?php endif; ?>
        <form method="post">
            <div class="form-group">
                <label>Kullanıcı Adı</label>
                <input type="text" name="kullanici_adi" autofocus required placeholder="kullanıcı adı">
            </div>
            <div class="form-group">
                <label>Şifre</label>
                <input type="password" name="sifre" required placeholder="••••••••">
            </div>
            <button type="submit" class="btn btn-primary">🔐 Giriş Yap</button>
        </form>
    </div>
</div>
</body>
</html>
