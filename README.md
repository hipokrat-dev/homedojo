# HomeDojo

Dört kişilik ev ekibi için Türkçe, mobil uyumlu görev ve ödül uygulaması. PHP 8.2+ ve MySQL/MariaDB ile Hostinger Business üzerinde çalışır. Node.js sunucusu veya derleme gerekmez.

## Özellikler

- Ana sayfada dört düzenlenebilir profil, ayrı bakiye ve toplam puan/seviye göstergesi.
- Görev ekleme, düzenleme, silme; görev başına ayrı puan.
- Atama anından başlayan süreler: 1 gün (24 saat), 1 hafta (7 gün), 1 ay (sonraki ayın aynı günü ve saati; o gün yoksa ayın son günü).
- Önce hedef ödül seçimi; görev adları ve puanlarını gösteren, günlük/haftalık/aylık filtreli animasyonlu çark. Sunucudaki rastgele seçim görevi, puanını ve son tarihini kalıcı olarak atar.
- Ana sayfada atanmış görevler, geri sayım ve hedef ödül ilerlemesi. Çevirme puan kazandırmaz; zamanında tamamlamak kazandırır.
- Son tarih sunucuda, görevin çarktan alındığı an referans alınarak hesaplanır. Son tarihe eşit veya sonraki tamamlama isteği sunucuda reddedilir; süresi dolan kayıt geçmişte sıfır puanla görünür.
- Atanmış görevin başlığı, puanı ve süresi sabitlenir; görev havuzunu sonradan düzenlemek bu atamayı değiştirmez. Tamamlanan görev kendi kayıtlı süresi dolana kadar tekrar alınamaz. İptal edilen görev puan vermez; geçmişte tutulur ve yeniden seçilebilir.
- Açık sayfa 15 saniyede bir güncellenir; profil hedefi, atamalar, kazanımlar ve ödül geçmişi veritabanında tutulur.
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
node tests/http.mjs
find app public tests -name '*.php' -exec php -l {} \;
```

Testler profil puan ayrımını, dönem sınırlarını, değiştirilen görev puanlarını, geçmişin korunmasını, tekrar talepleri, yetersiz bakiyeyi, boş çarkı, işlem geri almayı ve giriş sınırlamasını kapsar. Yerel entegrasyon SQLite kullanır; üretimde MySQL kullanılır.

## Hostinger yayını

Ayrıntılar: [docs/HOSTINGER.md](docs/HOSTINGER.md).

1. Depoyu Hostinger GIT aracına `main` dalıyla bağlayın. Kök `.htaccess` dosyası arayüzü `public/` içinden sunar ve özel dizinleri engeller.
2. Bu uygulamaya özel MySQL veritabanı ve kullanıcısı oluşturun.
3. Güvenli tek kullanımlık kurulum bağlantısı için `app/setup-key.php` dosyasını yalnızca sunucuda oluşturun. Anahtarı veya gerçek yapılandırmayı GitHub'a göndermeyin.
4. Kurulum ekranında MySQL bilgilerini, HTTPS site adresini ve en az 12 karakter ortak ev şifresini girin.
5. Kurulum tabloları oluşturur, şifreyi hash olarak saklar, anahtarı siler ve kendisini kilitler. Yapılandırma public_html dışında homedojo-private/config.local.php dosyasında tutulur.

İlk kurulumdan sonra dört profilin adı Ayarlar ekranından değiştirilebilir. Yeni kurulumda on örnek görev ve dört örnek ödül bulunur; tüm puanlar sıfırdan başlar. Test verileri yayına taşınmaz.

## Otomatik yayınlama

Hostinger, GitHub'daki `main` dalını izler. Bu dala gönderilen veya birleştirilen her commit otomatik olarak dağıtılır. Yerel dosyayı değiştirmek tek başına yayın başlatmaz; değişikliğin GitHub'a gönderilmesi gerekir. GitHub Actions kontrolleri bağımsızdır ve otomatik dağıtımı durdurmaz.

Üretim veritabanı yapılandırması web kökü dışında korunur; yeni sürümler görev, puan veya ödül verilerini sıfırlamaz. Ayrıntılar [yayınlama rehberinde](docs/HOSTINGER.md).

## Veri ve yedekleme

`homedojo_state` tablosundaki tek JSON belge bu küçük dört kişilik uygulamanın verilerini tutar. Her yazmada InnoDB satırı kilitlenir. Bu tasarım küçük aile kullanımı içindir; yüksek hacimli çok haneli kullanım için normalleştirilmiş şema gerekir. Oturumlar PHP oturum deposunda, giriş deneme sınırları `homedojo_login_limits` tablosundadır. Hostinger'ın günlük veritabanı yedeklerini etkin tutun. Güncellemeler mevcut verileri sıfırlamaz.

### Sürüm 2 veri geçişi

Mevcut JSON kaydına `assignments` alanı eklenir; eski profiller, görevler, puanlar ve ödül geçmişi korunur. On örnek görev yalnızca yeni kurulumda oluşturulur. Süre aşımı sunucu saatinden hesaplandığı için ayrıca cron işi gerekmez. Eski tamamlamalar geçerli dönem içinde yeniden görev seçimini engeller.

## Kişisel sayfalar ve aile yarışmaları

Her profilin `#profile/u1` … `#profile/u4` adresinde kendi sayfası vardır. Yenilemede sayfa ve profil korunur. Aile panosu dört kişinin toplam kazanılan ve kullanılabilir puanını gösterir; sıralama toplam kazanımla yapılır. Bu, ortak ev oturumu içinde profil ayrımıdır; ayrı şifreli dört hesap değildir.

Aile yarışması için ad, ortak büyük ödül, toplam hedef puan ve süre belirlenir. Dört profil otomatik katılır. Yarışma başlangıcından sonra tamamlanan görevler, yarışma son tarihinden önceyse ortak hedefe katkı sağlar. Önceden alınmış fakat yarışma sırasında tamamlanan görevler de sayılır; önceden kazanılmış puanlar sayılmaz. İptal/süre aşımı puan kazandırmaz. Bir seferde bir aktif yarışma başlatılır; katkı sıralaması, sonuç ve iptal kayıtları saklanır. Ortak ödül hedefe ulaşınca alındı olarak işaretlenebilir; kişisel puan bakiyeleri düşmez. Bu bir aile içi ödül kaydıdır, dışarıdan ürün satın alma işlemi değildir.

Sürüm 3 geçişi `competitions` alanını ekler. Eski görev atamalarının mevcut son tarihleri korunur; yeni süre hesabı yeni atamalarda geçerlidir. Aktif yarışmanın süresi ve puan hedefi başlangıçta sabitlenir; gerekirse iptal edilip yeni yarışma başlatılır.

Ana HTML PHP üzerinden önbelleksiz sunulur; JavaScript ve CSS adresleri içerik özetine göre sürümlenir. Böylece her otomatik dağıtım yeni dosya adresi üretir. Önceden açık sekmeler yeni sürüm için yenilenmelidir.
