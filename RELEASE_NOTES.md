# 🛢️ Project Industry - Sürüm 2.0.1 Yayın Notları (Release Notes)

Project Industry için şimdiye kadarki en kapsamlı mimari yenilik ve arayüz güncellemesi olan **v2.0.1** sürümünü duyurmaktan gurur duyuyoruz!

## 🚀 Neler Yeni? (Öne Çıkanlar)

1. **Modern Arayüz (UI) Revizyonu:**
   * Eski tip yan menü (sidebar) navigasyonu iptal edilip, tamamen profesyonel "Üst Menü (Top Navbar)" ve mobil cihazlar için "Alt Navigasyon Barları (Bottom Nav & Drawer)" sistemine geçtik.
   * Modası geçmiş uzun metin tabloları yerine; şık, tıklanabilirliği yüksek ve okunabilir "Kart (Card)" mimarisine geçiş yapıldı.
2. **Anında Canlı Arama (Live Search):**
   * Araçlar ve Tesisler sayfasında artık arama yapmak için enter'a basıp sayfa yüklenmesini beklemenize gerek yok. Tek bir harf yazdığınız an saniyesinde (**Real-time JavaScript filter**) eşleşen kartlar önünüze gelir. ⚡
3. **Mükemmel Mimari: Sınıf Çılgınlığı (OOP Refactor):**
   * Sistemin hiçbir arayüz (HTML) sayfasında `$pdo->query` veya benzeri satır arası (inline) SQL kodu kalmadı!
   * Tüm işlemler; `Kullanici.php`, `SistemGuncelleme.php`, `Tesis.php`, `SistemYedek.php` gibi merkeze hizmet eden yepyeni servis sınıflarına aktarıldı.
   * `classes/` ve `includes/` dizinlerinin doğrudan tarayıcı ile görüntülenmesi `htaccess` güvenlik duvarı ile engellendi.

---

## 📥 Kurulum & Güncelleme Nasıl Yapılır?

### Daha Önce Kurulum Yapanlar (Anında Sistem Güncellemesi):
* Eski sürüm bir Project Industry (Örn. v1.4.0) kullanıyorsanız; Yönetim Panelinize girin, **Sistem Güncelleme** sekmesine tıklayın ve sayfadaki **"Güncelle" (veya İndir/Kur)** butonuna basın. Sistem hiçbir dosyanızı bozmadan kendi kendisini 2.0.1 sürümüne güncelleyecektir.

### Sıfırdan (Yeni) Kurulum Yapanlar (Downloader.php ile):
Eğer yepyeni bir kurulum yapacaksanız tüm arşivi indirmekle uğraşmanıza gerek yok:
1. Aşağıdaki "Assets" bölümünden sadece **`downloader.php`** dosyasını bilgisayarınıza indirin.
2. Kurulum yapacağınız sunucunuzun (veya hosting'inizin) ilgili boş dizinine (`public_html` / `htdocs`) sadece **`downloader.php`** dosyasını yükleyin.
3. Tarayıcınızdan `siteniz.com/downloader.php` adresine girin.
4. Sihirbazımız saniyeler içinde GitHub'dan tüm 2.0.1 paketinizi indirip kuracak ve sizi `/install` ekranına alarak veritabanınızı oluşturacaktır.

*Tüm süreç sadece 1 dakikanızı alır.* İyi kullanımlar dileriz! 🛠️
