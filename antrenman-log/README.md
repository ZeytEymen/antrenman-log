# Antrenman Log — kişisel kilo & ağırlık takip uygulaması

Tek kullanıcılı, kendi sunucunda çalışan PHP + MySQL uygulaması.
Kilonu, antrenmanlarını ve her hareketteki ağırlık/tekrarı kaydeder; ilerlemeyi grafikle gösterir.

## Gereksinimler
- PHP 8.1+ (PDO MySQL eklentisi açık)
- MySQL 5.7+ veya MariaDB 10.3+
- Herhangi bir web sunucu (Apache / Nginx / cPanel / Plesk)

## Kurulum (5 adım)

**1. Veritabanını oluştur**
```bash
mysql -u KULLANICI -p < schema.sql
```
(phpMyAdmin kullanıyorsan: yeni DB değil, doğrudan `schema.sql`'i **Import** et — dosya `gymtrack` DB'sini kendi oluşturur.)

**2. `config.php`'yi düzenle** — en üstteki 4 satır:
```php
const DB_HOST = 'localhost';
const DB_NAME = 'gymtrack';
const DB_USER = 'mysql_kullanicin';
const DB_PASS = 'mysql_sifren';
```

**3. Dosyaları sunucuya yükle**
Tüm `.php` ve `style.css` dosyalarını web kök dizinine (örn. `public_html/antrenman/`) at.

**4. Tarayıcıdan aç ve giriş yap**
```
https://senin-domainin/antrenman/
Kullanıcı adı: zeyt
Şifre:        antrenman
```

**5. ŞİFRENİ DEĞİŞTİR**
Giriş yaptıktan sonra **Ayarlar → Şifre değiştir**. (Varsayılan şifreyle bırakma.)
Kullanıcı adını değiştirmek istersen MySQL'den:
`UPDATE users SET username='yeni_ad' WHERE id=1;`

## Kullanım
- **Antrenman** → "Yeni antrenman başlat" (tarih + Seans A/B) → set ekle ekranına düşer.
  Her sette hareket + ağırlık + tekrar gir. "Geçen sefer" satırı bir önceki seansını gösterir (progressive overload için).
- **Kilo** → günlük kilo kaydı; özet sayfasında grafik ve hedef çubuğu (108 → 85).
- **İlerleme** → hareket seç, en yüksek ağırlığın zaman içindeki grafiği + seans dökümü.
- **Hareketler** → yeni hareket ekle / pasifleştir (program kütüphanen).
- **Ayarlar** → hedef kilo, boy, şifre.

## Güvenlik notları
- Sadece sen kullanacağın için sade tutuldu; yine de **HTTPS** kullan ve varsayılan şifreyi değiştir.
- İstersen `.htaccess` ile dizine ekstra şifre (Basic Auth) koyabilirsin.
- Grafikler için Chart.js CDN'den çekilir (internet gerekir). Tamamen offline istersen
  `chart.umd.min.js`'i indirip yerelden çağırabilirsin.

## Hareket kütüphanesi
`schema.sql` Faz 1 programındaki hareketlerle (Seans A / B, ACL-güvenli bacak,
strap'li çekişler, düşük etkili kardiyo) önceden dolu gelir. Programın değişince
**Hareketler** sayfasından düzenlersin.
