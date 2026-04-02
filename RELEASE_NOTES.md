# Release Notes - v2.0.0

## Yenilikler ve Özellikler
* **Stok Yönetimi Modülü:**
  * Ürünler için artık detaylı stok takibi yapılabiliyor.
  * Yeni fatura girişi (stok girişi) ile ürünlerin stoğu otomatik artırılıyor.
  * Tedarikçiler modülü eklendi; faturalar tedarikçilere bağlanabiliyor.
  * Araçlara veya tesislere çıkış yapıldığında (örneğin yağ değişimi/ürün ekleme) stok otomatik olarak düşüyor.
  * "Stoklar" sayfası eklendi: Mevcut stokları görüntüleme ve manuel olarak stokları eşitleme, girme veya çıkma (sayım, zayiat vs.) imkanı sağlandı.
  * Yanlış girilen faturaların/kayıtların düzenlenmesi veya silinmesi durumunda stok bakiyesi arka planda akıllı (transaction destekli) bir sistemle otomatik olarak eşitleniyor/iade ediliyor.
* **Dashboard (Özet Ekranı):**
  * Uygulamanın girişine şık, profesyonel ve modern bir Dashboard eklendi.
  * Dashboard üzerinde aktif araç, tesis, ürün çeşidi sayısı ve bugünkü işlemler gibi kritik sistem istatistikleri görülebiliyor.
  * Son 5 işlem hızlıca listeleniyor.
* **Aç/Kapat (Toggle) Yapısı:**
  * Yönetici panelindeki "Sistem Ayarları" üzerinden Dashboard veya Stok Yönetimi özellikleri istendiği an açılıp kapatılabiliyor.
  * Özellikler kapalıyken sistem, eski "hızlı kullanım" moduna (sadece Araç Kartları) geri dönüyor.
* **Gelişmiş JSON Loglama:**
  * Sistem Kayıtları (Log) altyapısı yenilendi. Eklenen, silinen veya güncellenen kayıtların "Eski" ve "Yeni" verileri JSON formatında tam teşekküllü (tüm dizi/array detaylarıyla) ve okunabilir şekilde listeleniyor.

## İyileştirmeler (Görsel ve Mobil)
* Mobil cihazlarda tabloların metinleri sıkıştırması (yamulması) sorunu `white-space: nowrap` ve kaydırılabilir yatay eksen (`overflow-x: auto`) kullanılarak profesyonel bir mobil tablo deneyimi haline getirildi.
* Dashboard, mobil cihazlarda alt alta yığılmak yerine şık bir 2x2 ızgara (grid) yapısına kavuşturuldu.
* Mobil girişte, anasayfa yönlendirmesi cihaz algılama ile yapıldı; mobilde her zaman Araç Kartları açılacak, istendiğinde sol çekmece menüden Dashboard'a ulaşılabilecek şekilde optimize edildi.
* Kurulum Sihirbazı (`install/index.php`) güncellendi; artık kurulum sırasında Dashboard ve Stok Takibi özellikleri kutucuklarla (checkbox) seçilerek aktif edilebiliyor.

## Teknik
* Veritabanı şeması güncellendi (migrations/2.0.0.sql).
* PHP 8.x + Vanilla JS mimarisi korunarak, projeye gereksiz kütüphane/framework yükü bindirilmedi.
