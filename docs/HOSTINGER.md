# Hostinger kurulum ve yayınlama

Hedef depo: https://github.com/hipokrat-dev/homedojo
Hedef site: https://olivedrab-barracuda-526213.hostingersite.com/

Bu proje mevcut PHP/HTML siteye uygundur. PHP 8.2 veya üstü, PDO MySQL, mbstring ve Apache/LiteSpeed rewrite gerekir. `public_html` altında depo kökü olmalıdır; `.htaccess` dosyası public dizinine yönlendirir. Alternatif olarak document root doğrudan public seçilebilir; bu durumda güvenlik başlıklarını sunucu yapılandırmasına da ekleyin.

## 1. GitHub bağlantısı

Hostinger → ilgili web sitesi → Gelişmiş → GIT.
Depo URL'si: `https://github.com/hipokrat-dev/homedojo.git`, dal: `main`.
Mevcut başka uygulamaları değiştirmeyin. Hedef dizin boş olmalıdır; varsayılan Hostinger başlangıç dosyası varsa önce yedek klasöre taşıyın. Deploy sonrasında `app`, `public` ve kök `.htaccess` bulunmalıdır. GitHub otomatik deploy webhook'u panel üzerinden açılabilir; bu ilk sürümde otomatik yayınlama ayrıca etkinleştirilmiş sayılmaz.

## 2. Veritabanı

Hostinger → Veritabanları → Yönetici. Uygulamaya özel `homedojo` veritabanı/kullanıcısı oluşturun; Hostinger'ın eklediği kullanıcı önekiyle tam adları kullanın. Var olan başka site veritabanını kullanmayın. Güçlü şifreyi kendiniz belirleyin ve güvenli yerde saklayın.

## 3. Tek kullanımlık kurulum

Yerelde şu komutla kurulum anahtarı dosyası oluşturulabilir:

```sh
php -r '$key=bin2hex(random_bytes(32)); file_put_contents("app/setup-key.php", "<?php return ".var_export($key,true).";\n");'
```

Yalnızca `app/setup-key.php` dosyasını Hostinger Dosya Yöneticisi ile sunucudaki aynı konuma yükleyin. Bu dosya `.gitignore` ile dışlanır. Anahtarı kimseyle paylaşmayın.
`https://SITE-ADRESI/setup.php?key=ANAHTAR` bağlantısını açın. İlk ziyarette anahtar URL'den çıkarılır ve yetki kısa ömürlü PHP oturumuna alınır. Veritabanı bağlantısını ve ev şifresini site sahibi girmelidir.
Kurulumdan sonra `public_html` dışında `homedojo-private/config.local.php` oluşur, setup anahtarı silinir ve kurulum kapanır. Ev şifresi düz metin olarak saklanmaz. Veritabanı parolası yalnızca web kökü dışındaki PHP yapılandırmasında kalır; app dizini de dışarıya kapalıdır.

## 4. Yayın kontrolü

- Ana sayfa ev şifresiyle giriş ekranını göstermeli.
- Giriş yapmadan `api.php?action=state` isteği 401 dönmeli.
- `/app/seed.json`, `/app/config.local.php` ve `/.git/config` istekleri 403/404 dönmeli.
- Kurulumdan sonra `/setup.php` 403 dönmeli.
- Giriş yaparak bir profili seçin; görev ve ödül puanlarının diğer profillerden ayrı olduğunu kontrol edin.
- Site alan adı değişirse config.local.php içindeki public_url değerini HTTPS kök alan adıyla güncelleyin.

## Güncelleme ve kurtarma

Hostinger'da Deploy işlemi kaynak dosyaları günceller. Üretim yapılandırması public_html dışında homedojo-private/config.local.php içinde tutulur ve kaynak dağıtımına dahil edilmez. Kurulum anahtarı tek kullanımlıktır. Veritabanı kaynak dağıtımından bağımsızdır. Yayınlamadan önce veritabanı yedeği alın. Hata durumunda PHP hata günlüğünü inceleyin; ayrıntılı SQL/parola hataları ziyaretçilere gösterilmez.

Ev şifresini sıfırlamak gerekirse site sahibi güvenli bir ortamda `password_hash` oluşturup `homedojo-private/config.local.php` içindeki hash'i değiştirebilir. PHP oturumları da temizlenerek açık oturumlar sonlandırılmalıdır.
