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

## ✨ Temel Özellikler (v2.1.0 Yenilikleri)

### 📊 Modern Dashboard & İstatistikler
- **Sistem Özeti:** Aktif araç, tesis, ürün sayılarını ve bugünkü işlem trafiğini anlık takip edin.
- **Son İşlemler:** Yapılan son 5 işlemi anasayfadan hızlıca görün.
- **Mobil Uyumluluk:** Mobilde alt alta yığılmayan, kompakt 2x2 grid yapısı.

### 📦 Gelişmiş Stok Yönetimi
- **Tam Takip:** Ürünlerin giriş (fatura) ve çıkış (araç/tesis kaydı) hareketlerini otomatik izleyin.
- **Tedarikçi Yönetimi:** Stok girişlerini tedarikçi bazlı faturalandırın.
- **Manuel Müdahale:** Sayım farkları için hızlı giriş, çıkış ve stok eşitleme modülleri.
- **Akıllı Dengeleme:** Kayıt düzenleme veya silme işlemlerinde stok bakiyeleri otomatik iade edilir/eşitlenir.

### 🪪 Görsel Kart Arayüzü & Anında (Live) Arama
- **Modern Kart Arayüzü:** Araç ve tesisler ekran üzerinde temiz ve görsel kartlar halinde listelenir.
- **Anında Dinamik Filtreleme:** Yazmaya başladığınız an sonuçlar saniyesinde JavaScript ile filtrelenir.

### 📋 Operasyon ve Yönetim
- **Ürün Yönetimi:** Sistemde kullanılan ürünlerin birim ve stok takibi ile yönetilmesi.
- **Araç Türleri (Öncelik Sistemi):** Araç kategorizasyonu ve sıralama önceliği.

### 📨 Asenkron E-Posta Bildirim Sistemi
- **Akıllı Mail Kuyruğu (Queue):** Sayfa yavaşlamadan arka planda asenkron mail gönderimi.
- **Limit & Kilit:** Spam koruması ve race-condition önleme.

### 🏗️ Yazılım Mimarisi & Güvenlik
- **Saf OOP Mimari:** İş mantığı tamamen servis sınıflarında (classes/) toplanmıştır.
- **Gelişmiş JSON Loglama:** Her işlemin "Eski" ve "Yeni" veri detayları JSON formatında tam şeffaflıkla kaydedilir.
- **Güvenlik:** CSRF koruması, Brute-force koruması ve yetki hiyerarşisi.

### 🔔 Canlı Bildirimler (v2.1.0)
- **Polling Bildirim:** İşlemler sayfası açıkken yeni kayıt eklendiğinde bildirim (her 10 saniyede kontrol).
- **Gruplama:** Art arda aynı araca/tesise eklenen kayıtlar tek kutuda gruplanır.

---

## 🛠️ Yönetim Paneli (Sadece Yönetici / Admin)

- **Aç/Kapat (Toggle) Özellikler:** Dashboard ve Stok Yönetimi özellikleri ayarlar sayfasından istendiği an kapatılarak sistem "Hızlı Kullanım" moduna geçirilebilir.
- **Kapsamlı Loglama:** Yapılan her işlemin tüm teknik detaylarıyla izlenebilmesi.
- **SMTP Port Otomasyonu:** Kolay mail ayarları ve test aracı.
- **Veritabanı Yedekleme & Güncelleme:** Tek tıkla yedek alma ve GitHub üzerinden otomatik sistem güncelleme.

---

## ⚙️ Sistem Gereksinimleri

- **Apache Web Server** (.htaccess desteği aktif olmalı)
- **PHP ≥ 8.0**
  - `PDO` & `PDO MySQL`
  - `ZipArchive`
  - `file_get_contents` ve `allow_url_fopen`
- **MySQL** veya **MariaDB** veritabanı sunucusu

---

## 🚀 Kurulum Adımları (Downloader)

Uygulamanın çalışır duruma gelmesi için özel hazırladığımız **Downloader** sihirbazını kullanmanız önerilir:

1. Sunucunuzda boş bir klasör (örn. `htdocs/`) hazırlayın.
2. Github **[Releases](https://github.com/Awosk/project-industry/releases)** sayfasından **`downloader.php`** dosyasını indirin ve dizine atın.
3. Web tarayıcınızdan `downloader.php`'ye gidin. Script sürümü çekip kuracaktır.
4. Kurulum sihirbazında (`/install`) veritabanı bilgilerinizi girin ve **Dashboard/Stok** gibi özellikleri aktif edip Admin hesabınızı oluşturun.
5. Kurulum bittiğinde `install/` klasörünü silin.

---

## 📄 Lisans

Bu proje **GNU GPL v3** Lisansı ile özgür bir yazılımdır.

## ✉️ İletişim

Geliştirici: **Awosk** - **GitHub:** [@Awosk](https://github.com/Awosk)
<p align="center">Desteklemek için projeye bir ⭐ bırakmayı unutmayın!</p>
