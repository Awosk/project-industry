# Release Notes - v2.1.0

## 🎉 Yeni Özellikler

### 📋 İşlemler Sayfası - Gruplama
- Art arda aynı araca/tesise eklenen kayıtlar tek kutuda gruplanıyor
- Sol tarafta araç/tesis bilgisi sabit, sağ tarafta her kayıt ayrı satırda
- Araya başka araç/tesis girerse gruplama otomatik kırılıyor
- Her kayıt köşesiz kutu içinde, daha okunabilir görünüm

### 🔔 Canlı Bildirim Sistemi (SSE)
- İşlemler sayfası açıkken yeni kayıt eklendiğinde anında bildirim gösteriliyor
- Sağ üst köşede "🔔 Yeni Kayıt Eklendi" bildirimi + "🔄 Yenile" butonu
- Bağlantı kopsa bile otomatik yeniden bağlanma
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
- SSE bağlantı sızıntısı (sayfa değişiminde eski bağlantı kapatılıyor)
- `lastId` hesaplaması (sayfalama/filtreleme etkilemiyor)

## 📦 Teknik Değişiklikler
- `api/events.php` - SSE endpoint eklendi
- `assets/css/style.css` - Tüm stiller tek dosyada toplandı
- `install/database.sql` - `fake_data_aktif` ayarı eklendi
- `.gitignore` - `test.php` eklendi
