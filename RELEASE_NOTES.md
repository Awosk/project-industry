# 🏭 Project Industry - Sürüm 1.0.0 Yayın Notları (Release Notes)

🎉 Artık sadece yağ takibi ile sınırlı değiliz! **Project Oil** uygulamasının büyük finaliyle birlikte, projemiz **Project Industry** adı altında tamamen yeniden doğdu. Bu bizim yepyeni başlangıcımız: **v1.0.0**!

## 🚀 Yeni Neler Var? (Project Industry v1.0.0)

1. **Evrensel Ürün Takibi (Yağ → Ürün):**
   * Artık sadece yağ değil; gres, antifriz, sarf malzeme, filtre, yedek parça gibi her türlü **ürün** eklenebiliyor. Tüm arayüz ve bildirimler artık "Yağ Kaydı" yerine genel "Ürün Kaydı" konseptiyle çalışıyor.
   * "Miktar (Litre)" zorunluluğu kalktı! Ürünlere "Birim" (LT, KG, Adet, Kutu vb.) özelliği eklendi. Sistemde artık her ürün kendi birim formatıyla işlem görüyor.

2. **Dinamik Miktar Formatlama:**
   * Geçmiş kayıt ekranları, raporlar, araç ve tesis detayları... Nereye bakarsanız bakın, eklediğiniz bir ürün kendi gerçek birimiyle (örn: 5.00 KG veya 2.00 Adet) gösterilir. Sabit Litre "L" karakteri bağımlılığı sona erdi.

3. **Modern Arayüz & Mimari:**
   * Vanilla PHP 8.x + MySQL OOP mimarisi sayesinde tam hız.
   * Araçlar ve Tesisler listelerinde canlı Arama (Live Search) özelliği.
   * PWA ve Otomatik Mail Worker desteği aynen devam ediyor!

---

## 📥 Kurulum Nasıl Yapılır?

Eğer yepyeni bir kurulum yapacaksanız tüm arşivi indirmekle uğraşmanıza gerek yok:

1. Aşağıdaki "Assets" bölümünden sadece **`downloader.php`** dosyasını bilgisayarınıza indirin.
2. Kurulum yapacağınız sunucunuzun (veya hosting'inizin) projenin çalışmasını istediğiniz ilgili dizinine (public_html / htdocs) sadece **`downloader.php`** dosyasını yükleyin.
3. Tarayıcınızdan `siteniz.com/downloader.php` adresine girin.
4. Sihirbazımız saniyeler içinde GitHub alt yapısından yepyeni 1.0.0 paketinizi çekecek ve sizi otomatik olarak `/install` ekranına alıp veritabanınızı oluşturacaktır.

Eski sisteme (`Project Oil`) ait bilgisi olmayan sıfır veritabanlı standart ilk kurulumdur. İyi kullanımlar dileriz! 🏭✨
