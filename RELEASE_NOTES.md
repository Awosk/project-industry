# Release Notes - v2.2.2

## 🛠️ Hata Düzeltmeleri & İyileştirmeler

### 🎲 Fake Data Temizliği ve Fiş Entegrasyonu
- **Veri Temizleme Düzeltildi:** "Tüm Verileri Temizle" seçeneği çalıştırıldığında `slips` ve `slip_items` tablolarının silinmemesi sebebiyle oluşan yetim (orphaned) fiş hatası giderildi. Artık fişler ve kalemleri de temizleniyor.
- **Fake Data Fiş Oluşturma:** Fake Data oluşturulurken sisteme rastgele araç/tesis ve ürünlere bağlı demo fişler (bekleyen, onaylanan, iptal) eklenmesi sağlandı.
- **Silinmiş Varlık Dayanıklılığı:** Bağlı aracı, tesisi veya ürünü silinmiş olan fişlerin sayfayı bozması engellendi; bu tür kayıtlar otomatik olarak `Kayıt Silindi` statüsüne alınarak admin tarafından güvenle silinebilmesi sağlandı.

---

# Release Notes - v2.2.1

## 🎉 Yeni Özellikler

### 📦 Fişlerde Çoklu Ürün Kalemi Desteği
- **Tek Fişte Birden Fazla Ürün:** Tek bir çıkış fişine birden fazla ürün ve miktar eklenebilmesi sağlandı.
- **➕ Başka Ürün Ekle Düğmesi:** Ürün seçim kutusunun altına yerleştirilen düğme ile dinamik olarak yeni ürün satırları eklenebilir ve gerekirse satırlar silinebilir.
- **Dinamik Aramalı Ürün Seçimi:** Her eklenen ürün satırında ürün kodu veya adına göre anında filtreleme yapan arama kutusu ve canlı stok bilgisi entegre edildi.
- **Gelişmiş Fiş Kartı Görünümü:** Fiş listesinde fişe bağlı tüm ürün kalemleri miktar, birim ve işlem detay bağlantılarıyla birlikte temiz kartlar halinde gösterilir.
- **Çoklu Çıkış & Stok Düşümü:** Fiş sahada onaylandığında tüm ürünler için ayrı ayrı çıkış kaydı oluşturulur ve stoklar düşülür.
- **Aramalı Araç ve Tesis Seçimi:** Fiş açma modalında yüzlerce araç veya tesis arasından plaka, model ve firma adına göre anında filtreleme sağlandı.

---

# Release Notes - v2.2.0

## 🎉 Yeni Özellikler

### 🧾 Dijital Fiş Sistemi (Talep & Onaylı Ürün Çıkışı)
- **Depo & Saha Entegrasyonu:** Depo personelinin fiziksel kağıt fiş yazma ihtiyacı ortadan kaldırıldı; araç/tesis seçilerek anında sistem üzerinden dijital çıkış fişi oluşturulabiliyor.
- **Onay Mekanizması:** Saha personeli yağı/ürünü teslim ederken tek tıkla fişi onaylayabilir. Onaylandığı an otomatik olarak `records` tablosuna kesin çıkış işlenir ve ürün stoğu düşülür.
- **İptal & Silme Yaşam Döngüsü:** Hatalı veya vazgeçilen fişler oluşturan personel veya admin tarafından önce "İptal Edildi" statüsüne alınır; iptal edilen fişler istendiğinde sistemden kalıcı olarak silinebilir.
- **Canlı Bildirim (Polling):** Yeni bir fiş açıldığında sahada ekranı açık olan personellere anlık `🔔 Yeni Fiş Açıldı` bildirimi gösterilir.
- **Navigasyon Rozeti:** Masaüstü menüde, mobil menüde ve alt gezinme çubuğunda bekleyen fiş adedi canlı rozet (badge) ile gösterilir (`🧾 Fişler [X]`).
- **Bağlı Kayıt Silinme Koruması:** Onaylanmış bir fişin oluşturduğu ürün çıkış kaydı araç veya tesis üzerinden sonradan silinirse, fiş durumu otomatik olarak `🗑️ Kayıt Silindi` olarak gösterilir ve bu durumdayken fiş sistemden kalıcı olarak silinebilir.

---

# Release Notes - v2.1.0

## 🎉 Yeni Özellikler

### 📋 İşlemler Sayfası - Gruplama
- Art arda aynı araca/tesise eklenen kayıtlar tek kutuda gruplanıyor
- Sol tarafta araç/tesis bilgisi sabit, sağ tarafta her kayıt ayrı satırda
- Araya başka araç/tesis girerse gruplama otomatik kırılıyor
- Her kayıt köşesiz kutu içinde, daha okunabilir görünüm

### 🔔 Canlı Bildirim Sistemi (Polling)
- İşlemler sayfası açıkken yeni kayıt eklendiğinde bildirim gösteriliyor
- Sağ üst köşede "🔔 Yeni Kayıt Eklendi" bildirimi + "🔄 Yenile" butonu
- Her 10 saniyede bir otomatik kontrol (Apache2+PHP-FPM uyumlu)
- Bildirimler sayfa yenilenene kadar kalıcı

### 🔀 Sıralama Yönü Değiştirme
- İşlemler sayfasında "Yeni → Eski" / "Eski → Yeni" sıralama butonu
- Filtreleme ve sayfalama ile uyumlu

## 🛠️ İyileştirmeler

### Kod Kalitesi
- **Kod Tekrarı Temizliği:**
  - IP adresi alma kodu tekilleştirildi (`istemciIpAdresiGetir()` fonksiyonu)
  - Mail bildirim array'leri tekilleştirildi
  - Stok hareketi yardımcı metodları eklendi (`stokCikisYap`, `stokIadeEt`)
  - `Islem::aramaSartlariniOlustur` undefined key hatası düzeltildi

### CSS Düzenlemesi
- Tüm sayfalardaki inline CSS'ler `style.css` dosyasına taşındı
- Hardcoded renkler CSS değişkenlerine dönüştürüldü (`var(--danger)`, `var(--primary)`, vb.)
- Dark mode uyumluluğu sağlandı
- Dashboard, vehicle_detail, facility_detail sayfalarındaki `<style>` blokları kaldırıldı

### İşlemler Sayfası
- "İşle" butonu AJAX ile çalışıyor (sayfa yenilenmiyor)
- İşlendi butonuna basınca badge otomatik kayboluyor
- Yeni eklenen kartlardaki butonlar da çalışıyor
- Kutular arası boşluk optimize edildi

### Fake Data Generator
- Yönetim paneline taşındı (`pages/management/fake_data.php`)
- Güvenlik: Sistem ayarlarından açılması gerekiyor
- Her kullanımdan sonra otomatik kapanıyor
- Max değerleri doldurma butonu eklendi

### Sistem Ayarları
- Yeni toggle: "🎲 Fake Data (Test Verisi)"
- Toggle kapalıyken Fake Data sayfası çalışmıyor

## 🐛 Hata Düzeltmeleri
- İşlemler sayfası sayfalama URL hatası (`??` → `?`)
- Gruplanmış kayıtlarda tesis URL yönlendirme hatası
- `lastId` hesaplaması (sayfalama/filtreleme etkilemiyor)
- SSE yerine polling kullanılıyor (Apache2+PHP-FPM uyumluluğu)

## 📦 Teknik Değişiklikler
- `api/check_new_records.php` - Polling endpoint'i eklendi
- `api/events.php` - SSE endpoint'i kaldırıldı
- `assets/css/style.css` - Tüm stiller tek dosyada toplandı
- `install/database.sql` - `fake_data_aktif` ayarı eklendi, `sse_bildirim_aktif` kaldırıldı
- `.gitignore` - `test.php` eklendi
