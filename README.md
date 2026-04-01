# 🛢️ Project Industry

![GitHub repo size](https://img.shields.io/github/repo-size/Awosk/project-industry?style=for-the-badge)
![GitHub stars](https://img.shields.io/github/stars/Awosk/project-industry?style=for-the-badge)
![PHP Version](https://img.shields.io/badge/PHP-%E2%89%A5%208.0-777bb4?style=for-the-badge&logo=php)
![License](https://img.shields.io/github/license/Awosk/project-industry?color=blue&style=for-the-badge)

---

## 📌 Proje Hakkında

`Project Industry`, endüstriyel tesislerde veya araç filolarında ürün ve sarf malzeme tüketimini izlemek için geliştirilmiş, **modern arayüz tasarımı** ve **tamamen Nesne Yönelimli (OOP)** sağlam mimariye sahip hızlı bir web uygulamasıdır. Herhangi bir ağır framework (ör. Laravel, Symfony) kullanılmadan; **Saf (Vanilla) PHP 8.x** ve Vanilla JS ile geliştirilmiş MVC benzeri bir katmanlı servis mimarisi ile çalışır. 

Personeller görsel kartlar üzerinden saniyeler içinde anlık arama (live search) yaparak araç bulabilir ve ürün çıkışı işleyebilir, yöneticiler ise geçmiş tüm çıkış kayıtlarını, sistem loglarını, asenkron mail kuyruğunu ve veritabanı yedeklerini denetleyebilir.

---

## ✨ Temel Özellikler

### 🪪 Görsel Kart Arayüzü & Anında (Live) Arama
- **Modern Kart Arayüzü:** Araç ve tesisler ekran üzerinde temiz, duyarlı ve görsel kartlar halinde listelenir.
- **Anında Dinamik Filtreleme:** Arama çubuğuna yazmaya başladığınız an (sayfa yenilenmeden) saniyesinde sonuçlar JavaScript ile filtrelenir.
- **Kart İçi Geçmiş:** Hangi araca/tesise, ne zaman, hangi ürünün, ne kadar ve kim tarafından verildiği doğrudan takip edilebilir.

### 📋 Operasyon ve Yönetim
- **Ürün Yönetimi:** Sistemde kullanılan endüstriyel ürünlerin dinamik olarak eklenmesi, düzenlenmesi (Soft Delete desteği ile).
- **Araç Türleri (Öncelik Sistemi):** Araçları türlerine (Tır, Damper, Beko vb.) göre kategorize eder ve sıralama önceliği atar.

### 📨 Asenkron E-Posta Bildirim Sistemi
- **Akıllı Mail Kuyruğu (Queue):** Mailler web sayfasını yavaşlatmadan arka planda sıraya eklenir ve arka plan işleyicisi (`mail_worker.php`) tarafından sırayla gönderilir.
- **Limit & Kilit Mekanizmaları:** Spam kısıtlamaları ve race-condition koruması (`FOR UPDATE` SQL kilidi) kusursuz şekilde devrededir.

### 🏗️ Sağlam Yazılım Mimarisi (v2.0+)
- **Sınıf Tabanlı (OOP) Veri İşleme:** Hiçbir görünüm/arayüz (HTML) sayfasında doğrudan `$pdo->query` bulunmaz. Veriler `Arac`, `Kullanici`, `SistemGuncelleme` gibi izole servis sınıflarından yönetilir.
- **Güvenlik Çemberi:** Otomatik CSRF token doğrulaması, Brute-force deneme-yanılma koruması, yetki kontrolleri (`girisKontrol()` vs.) ve kapalı dizin (`classes/`, `includes/`) koruması mevcuttur.

---

## 🛠️ Yönetim Paneli (Sadece Yönetici / Admin)

- **Kapsamlı Loglama:** Sistemde yapılan her ekleme, silme, şifre değiştirme işlemi loglanır ve paneldan izlenebilir.
- **SMTP Port Otomasyonu:** Dinamik şifreleme ve deneme maili yetenekleriyle ayarlanabilir.
- **Seçmeli Veritabanı Yedekleme:** Tüm tablo yapılarını ve dataları bir `.sql` dosyasına yedekler (`SistemYedek` servisi üzerinden tam izole).
- **Güvenli Sistem Güncelleme:** GitHub üzerinden yayınlanan son kararlı sürümü otomatik olarak indrip kurar ve veritabanı migration (.sql) dosyalarını otomatik uygular.

---

## ⚙️ Sistem Gereksinimleri

- **Apache Web Server** (.htaccess desteği aktif olmalı)
- **PHP ≥ 8.0**
  - `PDO` & `PDO MySQL` (Veritabanı için)
  - `ZipArchive` (Sistem veya eklenti güncellemeleri için)
  - `file_get_contents` ve `allow_url_fopen` (GitHub API bağlantıları için aktif olmalı)
- **MySQL** veya **MariaDB** veritabanı sunucusu

---

## 🚀 Kurulum Adımları (Downloader)

Uygulamanın çalışır duruma gelmesi için klasik `git clone` yöntemi yerine özel hazırladığımız **Downloader** sihirbazını kullanmanız şiddetle tavsiye edilir:

1. Kurulum yapacağınız sunucu / hosting üzerinde projenin çalışacağı ve dışarıya açık olan boş bir klasör (örn. `htdocs/`) hazırlayın.
2. Github **[Releases](https://github.com/Awosk/project-industry/releases)** sayfamızdan, indirmek istediğiniz son sürüme tıklayın. İndirilecekler arşivinden sadece **`downloader.php`** dosyasını ana dizininize atın.
3. Web tarayıcınızdan `http://siteniz.com/downloader.php` adresine gidin. Script, saniyeler içinde GitHub alt yapısından en güncel Project Industry sürümünü çekip sunucunuza kuracaktır.
4. Dosyalar çıkarıldıktan sonra script sizi otomatik olarak **`/install`** (kurulum sihirbazı) dizinine yönlendirir.
5. Veritabanı bilgilerinizi girin, Admin hesabınızı oluşturun ve bitirin!
6. *Opsiyonel: Arka plan mail gönderimleri için sunucunuza `cron/mail_worker.php` dosyasını her dakikada bir tetikleyen bir cron job ekleyin.*

---

## 📄 Lisans

Bu proje **GNU GPL v3** Lisansı ile özgür bir yazılımdır. Koddaki değiştirme/dağıtma işlemleri sırasında orijinal lisansı korumanız gereklidir. Daha fazla bilgi için proje kökündeki `LICENSE` dosyasına göz atabilirsiniz.

## ✉️ İletişim

Geliştirici: **Awosk** - **GitHub:** [@Awosk](https://github.com/Awosk)
<p align="center">Desteklemek için projeye bir ⭐ bırakmayı unutmayın!</p>
