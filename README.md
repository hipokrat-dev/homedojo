# HomeDojo

Dört kişilik ev ekibi için Türkçe, mobil uyumlu görev ve ödül uygulaması. PHP 8.2+ ve MySQL/MariaDB ile Hostinger Business üzerinde çalışır. Node.js sunucusu veya derleme gerekmez.

## Özellikler

- Ana sayfada dört düzenlenebilir profil, ayrı bakiye ve toplam puan/seviye göstergesi.
- Görev ekleme, düzenleme, silme; görev başına ayrı puan.
- Türkiye saatine göre günlük, pazartesi yenilenen haftalık ve ayın ilk günü yenilenen aylık görevler.
- Tamamlanmamış görevler arasından sunucuda rastgele seçim yapan animasyonlu çark. Çevirme puan kazandırmaz; görevi tamamlamak kazandırır.
- Düzenlenebilir ödüller; yeterli bakiye ile alma, puanı düşme ve geçmiş kaydı.
- Görev/ödül değişiklikleri geçmiş puan kayıtlarını değiştirmez.
- MySQL InnoDB satır kilidi ile atomik puan işlemleri, tekrar tamamlama koruması ve ödül isteği tekrar koruması.
- Ortak ev şifresi, güvenli oturum, CSRF koruması ve giriş hız sınırı.

Profiller ortak ev şifresiyle erişilen bir aile alanını paylaşır. İsim seçimi kimlik doğrulaması değildir. Dört profil de görevleri/ödülleri yönetebilir; ayrı yönetici veya kişisel şifre yetkilendirmesi yoktur.

## Yerel çalışma

PHP 8.2+ (`pdo_sqlite`, `mbstring`) gerekir:

```sh
APP_ENV=development php -S localhost:3000 -t public
```

`http://localhost:3000` açılır. Yerel veriler `data/homedojo.sqlite` içinde tutulur. Geliştirme modu giriş şifresi gerektirmez. **Hostinger'da APP_ENV=development kullanmayın.**

## Testler

```sh
php tests/domain.php
php tests/store.php
node --check public/app.js
find app public tests -name '*.php' -exec php -l {} \;
```

Testler profil puan ayrımını, dönem sınırlarını, değiştirilen görev puanlarını, geçmişin korunmasını, tekrar talepleri, yetersiz bakiyeyi, boş çarkı, işlem geri almayı ve giriş sınırlamasını kapsar. Yerel entegrasyon SQLite kullanır; üretimde MySQL kullanılır.

## Hostinger yayını

Ayrıntılar: [docs/HOSTINGER.md](docs/HOSTINGER.md).

1. Depoyu Hostinger GIT aracına `main` dalıyla bağlayın. Kök `.htaccess` dosyası arayüzü `public/` içinden sunar ve özel dizinleri engeller.
2. Bu uygulamaya özel MySQL veritabanı ve kullanıcısı oluşturun.
3. Güvenli tek kullanımlık kurulum bağlantısı için `app/setup-key.php` dosyasını yalnızca sunucuda oluşturun. Anahtarı veya gerçek yapılandırmayı GitHub'a göndermeyin.
4. Kurulum ekranında MySQL bilgilerini, HTTPS site adresini ve en az 12 karakter ortak ev şifresini girin.
5. Kurulum tabloları oluşturur, şifreyi hash olarak saklar, anahtarı siler ve kendisini kilitler.

İlk kurulumdan sonra dört profilin adı Ayarlar ekranından değiştirilebilir. Yeni kurulumda altı örnek görev ve dört örnek ödül bulunur; tüm puanlar sıfırdan başlar. Test verileri yayına taşınmaz.

## Veri ve yedekleme

`homedojo_state` tablosundaki tek JSON belge bu küçük dört kişilik uygulamanın verilerini tutar. Her yazmada InnoDB satırı kilitlenir. Bu tasarım küçük aile kullanımı içindir; yüksek hacimli çok haneli kullanım için normalleştirilmiş şema gerekir. Oturumlar PHP oturum deposunda, giriş deneme sınırları `homedojo_login_limits` tablosundadır. Hostinger'ın günlük veritabanı yedeklerini etkin tutun. Güncellemeler mevcut verileri sıfırlamaz.
