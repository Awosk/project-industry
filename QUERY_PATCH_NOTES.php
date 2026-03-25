<?php
// islemler.php içinde değiştirilecek ana sorgu:
// LEFT JOIN lite_arac_turleri t ON a.arac_turu_id = t.id eklendi
// a.arac_turu → t.tur_adi olarak değiştirildi

/*
ESKI:
    SELECT lk.*, u.urun_adi, u.urun_kodu, a.plaka, a.marka_model, a.arac_turu,
           t.firma_adi, k.ad_soyad, ik.ad_soyad AS islendi_ad_soyad
    FROM lite_kayitlar lk
    LEFT JOIN lite_urunler u ON lk.urun_id = u.id
    LEFT JOIN lite_araclar a ON lk.arac_id = a.id
    LEFT JOIN lite_tesisler t ON lk.tesis_id = t.id
    LEFT JOIN kullanicilar k ON lk.olusturan_id = k.id
    LEFT JOIN kullanicilar ik ON lk.islendi_kullanici_id = ik.id
    WHERE $where_sql
    ORDER BY lk.tarih DESC, lk.olusturma_tarihi DESC
    LIMIT $sayfa_basina OFFSET $offset

YENİ:
    SELECT lk.*, u.urun_adi, u.urun_kodu, a.plaka, a.marka_model, at.tur_adi AS arac_turu,
           t.firma_adi, k.ad_soyad, ik.ad_soyad AS islendi_ad_soyad
    FROM lite_kayitlar lk
    LEFT JOIN lite_urunler u ON lk.urun_id = u.id
    LEFT JOIN lite_araclar a ON lk.arac_id = a.id
    LEFT JOIN lite_arac_turleri at ON a.arac_turu_id = at.id
    LEFT JOIN lite_tesisler t ON lk.tesis_id = t.id
    LEFT JOIN kullanicilar k ON lk.olusturan_id = k.id
    LEFT JOIN kullanicilar ik ON lk.islendi_kullanici_id = ik.id
    WHERE $where_sql
    ORDER BY lk.tarih DESC, lk.olusturma_tarihi DESC
    LIMIT $sayfa_basina OFFSET $offset
*/

/*
raporlar.php içinde değiştirilecek sorgular:

ESKI:
    SELECT lk.*, u.urun_adi, u.urun_kodu, a.plaka, a.marka_model, a.arac_turu,
           t.firma_adi, k.ad_soyad
    FROM lite_kayitlar lk
    JOIN lite_urunler u  ON lk.urun_id    = u.id
    LEFT JOIN lite_araclar  a ON lk.arac_id    = a.id
    LEFT JOIN lite_tesisler t ON lk.tesis_id   = t.id
    LEFT JOIN kullanicilar  k ON lk.olusturan_id = k.id
    WHERE $where_sql
    ORDER BY lk.tarih DESC, lk.olusturma_tarihi DESC

YENİ:
    SELECT lk.*, u.urun_adi, u.urun_kodu, a.plaka, a.marka_model, at.tur_adi AS arac_turu,
           t.firma_adi, k.ad_soyad
    FROM lite_kayitlar lk
    JOIN lite_urunler u  ON lk.urun_id    = u.id
    LEFT JOIN lite_araclar  a  ON lk.arac_id    = a.id
    LEFT JOIN lite_arac_turleri at ON a.arac_turu_id = at.id
    LEFT JOIN lite_tesisler t  ON lk.tesis_id   = t.id
    LEFT JOIN kullanicilar  k  ON lk.olusturan_id = k.id
    WHERE $where_sql
    ORDER BY lk.tarih DESC, lk.olusturma_tarihi DESC
*/
