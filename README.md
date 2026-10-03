# HomeDojo

Aile üyeleri için Türkçe, mobil uyumlu görev ve ödül uygulaması. PHP 8.2+ ve MySQL/MariaDB ile Hostinger Business üzerinde çalışır. Node.js sunucusu veya derleme gerekmez.

## Özellikler

- Sade aile panosunda aktif üyelerin toplam ve kullanılabilir puanı; kişisel sayfada yalnızca kişinin hedefi ve görevleri.
- Görev ekleme, düzenleme, silme; görev başına ayrı puan.
- Atama anından başlayan süreler: 1 gün (24 saat), 1 hafta (7 gün), 1 ay (sonraki ayın aynı günü ve saati; o gün yoksa ayın son günü).
- Önce hedef ödül seçimi; görev adları ve puanlarını gösteren, günlük/haftalık/aylık filtreli animasyonlu çark. Sunucudaki rastgele seçim görevi, puanını ve son tarihini kalıcı olarak atar.
- Kişisel sayfada atanmış görevler, geri sayım ve hedef ödül ilerlemesi. Çevirme puan kazandırmaz; zamanında onaya göndermek ve seçilen aile üyesinin onaylaması kazandırır.
- Son tarih sunucuda, görevin çarktan alındığı an referans alınarak hesaplanır. Son tarihe eşit veya sonraki tamamlama isteği sunucuda reddedilir; süresi dolan kayıt geçmişte sıfır puanla görünür.
- Atanmış görevin başlığı, puanı ve süresi sabitlenir; görev havuzunu sonradan düzenlemek bu atamayı değiştirmez. Tamamlanan görev kendi kayıtlı süresi dolana kadar tekrar alınamaz. İptal edilen görev puan vermez; geçmişte tutulur ve yeniden seçilebilir.
- Açık sayfa 15 saniyede bir güncellenir; profil hedefi, atamalar, kazanımlar ve ödül geçmişi veritabanında tutulur.
- Düzenlenebilir ödüller; yeterli bakiye ile alma, puanı düşme ve geçmiş kaydı.
- Görev/ödül değişiklikleri geçmiş puan kayıtlarını değiştirmez.
- MySQL InnoDB satır kilidi ile atomik puan işlemleri, tekrar tamamlama koruması ve ödül isteği tekrar koruması.
- Kişisel kullanıcı adı/şifre, Baba için admin yetkisi, güvenli oturum, CSRF koruması ve giriş hız sınırı.

Her üye ayrı kimlik doğrular. Oturumdaki kimlik sunucuda denetlenir; URL veya istek gövdesi değiştirerek başka bir profil adına işlem yapılamaz. Görev, ödül, kullanıcı ve yarışma yönetimi yalnızca Baba admin hesabına açıktır. Ortak ev şifresi sadece kişisel hesaplara tek seferlik geçiş için kullanılır; geçişten sonra girişte kabul edilmez.

## Yerel çalışma

PHP 8.2+ (`pdo_sqlite`, `mbstring`) gerekir:

```sh
APP_ENV=development php -S localhost:3000 -t public
```

`http://localhost:3000` açılır. Yerel veriler `data/homedojo.sqlite` içinde tutulur. Yeni yerel veritabanında kişisel hesap oluşturma ekranı açılır. Hesaplar oluşturulduktan sonra geliştirme modunda da kişisel giriş gerekir. **Hostinger'da APP_ENV=development kullanmayın.**

## Testler

```sh
php tests/domain.php
php tests/store.php
php tests/accounts.php
php tests/personal-catalogs.php
php tests/profile.php
php tests/family.php
php tests/progress.php
php tests/site.php
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

İlk kurulumdan sonra ev şifresiyle giriş yapıp hesap geçiş ekranında dört kullanıcı adı ve şifresini belirleyin; Baba'ya ait mevcut profili seçin. Mevcut puanlar ve atamalar korunur. Sonraki isim/kullanıcı adı/şifre değişiklikleri Admin paneli → Kullanıcılar ekranındadır. Yeni kurulumda on örnek görev ve dört örnek ödül bulunur; tüm puanlar sıfırdan başlar. Test verileri yayına taşınmaz.

## Otomatik yayınlama

Hostinger, GitHub'daki `main` dalını izler. Bu dala gönderilen veya birleştirilen her commit otomatik olarak dağıtılır. Yerel dosyayı değiştirmek tek başına yayın başlatmaz; değişikliğin GitHub'a gönderilmesi gerekir. GitHub Actions kontrolleri bağımsızdır ve otomatik dağıtımı durdurmaz.

Üretim veritabanı yapılandırması web kökü dışında korunur; yeni sürümler görev, puan veya ödül verilerini sıfırlamaz. Ayrıntılar [yayınlama rehberinde](docs/HOSTINGER.md).

## Veri ve yedekleme

`homedojo_state` tablosundaki tek JSON belge bu küçük aile uygulamasının verilerini tutar. Her yazmada InnoDB satırı kilitlenir. Bu tasarım küçük aile kullanımı içindir; yüksek hacimli çok haneli kullanım için normalleştirilmiş şema gerekir. Oturumlar PHP oturum deposunda, giriş deneme sınırları `homedojo_login_limits` tablosundadır. Hostinger'ın günlük veritabanı yedeklerini etkin tutun. Güncellemeler mevcut verileri sıfırlamaz.

### Sürüm 2 veri geçişi

Mevcut JSON kaydına `assignments` alanı eklenir; eski profiller, görevler, puanlar ve ödül geçmişi korunur. On örnek görev yalnızca yeni kurulumda oluşturulur. Süre aşımı sunucu saatinden hesaplandığı için ayrıca cron işi gerekmez. Eski tamamlamalar geçerli dönem içinde yeniden görev seçimini engeller.

## Kişisel sayfalar ve aile yarışmaları

Her profilin `#profile/u1` … `#profile/u4` adresinde kendi sayfası vardır. Yenilemede sayfa korunur; kişisel sayfa her zaman giriş yapan hesaba aittir. Aile panosu aktif üyelerin toplam kazanılan ve kullanılabilir puanını gösterir; sıralama toplam kazanımla yapılır. Diğer üyelerin özel görev/ödül geçmişi gönderilmez; yalnızca seçilen onaycı kendisine gelen görevi görür.

Aile yarışması için ad, ortak büyük ödül, toplam hedef puan ve süre belirlenir. Yarışma başladığında aktif profiller otomatik katılır. Yarışma başlangıcından sonra tamamlanan görevler, yarışma son tarihinden önceyse ortak hedefe katkı sağlar. Önceden alınmış fakat yarışma sırasında tamamlanan görevler de sayılır; önceden kazanılmış puanlar sayılmaz. İptal/süre aşımı puan kazandırmaz. Bir seferde bir aktif yarışma başlatılır; katkı sıralaması, sonuç ve iptal kayıtları saklanır. Ortak ödül hedefe ulaşınca alındı olarak işaretlenebilir; kişisel puan bakiyeleri düşmez. Bu bir aile içi ödül kaydıdır, dışarıdan ürün satın alma işlemi değildir.

Sürüm 3 geçişi `competitions` alanını ekler. Eski görev atamalarının mevcut son tarihleri korunur; yeni süre hesabı yeni atamalarda geçerlidir. Aktif yarışmanın süresi ve puan hedefi başlangıçta sabitlenir; gerekirse iptal edilip yeni yarışma başlatılır.

Ana HTML PHP üzerinden önbelleksiz sunulur; JavaScript ve CSS adresleri içerik özetine göre sürümlenir. Böylece her otomatik dağıtım yeni dosya adresi üretir. Önceden açık sekmeler yeni sürüm için yenilenmelidir.

## Sürüm 4: kişisel hesaplar ve görev onayı

Eski veriler otomatik olarak korunur. Önceki ortak oturum yalnızca geçiş formunu açabilir; hesaplar kurulana kadar görev/puan yazmaları durdurulur. Geçiş tek işlemle dört hesabı oluşturur, Baba'ya admin rolü verir ve eski ortak oturumları geçersiz kılar. Şifre hashleri hiçbir API yanıtına dahil edilmez. Admin şifre yenilediğinde ilgili hesabın açık oturumları bir sonraki istekte sona erer.

Tamamladım → kullanıcı başka bir üyeyi seçer → görev `pending` olur. Bu aşamada puan eklenmez ve aynı görev yeniden çarkta görünmez. Yalnızca seçilen onaycı onaylayabilir veya geri gönderebilir. Sunucu süresinden önce gönderilen görevin onayı daha sonra verilse de puan geçerlidir. Yarışma hesabında onaya gönderilme zamanı esas alınır; onay gelene kadar puan toplamı artmaz. Geri gönderilen görev, asıl son tarihi dolmadıysa yeniden gönderilebilir; son tarih uzatılmaz. Tekrarlanan onaylar ikinci kez puan vermez.

## Sürüm 5: kişiye özel görev ve ödüller

Admin panelinde Görevler veya Ödüller sekmesine girip kişi seçin. Her kaydın zorunlu bir sahibi vardır; üyeler yalnızca kendi görev havuzunu ve ödüllerini alır. Admin kendi kişisel çarkında ve ödül mağazasında da yalnızca kendi kayıtlarını kullanır. Başkasının ödül kimliği ile hedef seçme veya ödül alma sunucuda reddedilir. Aile yarışmasının ortak büyük ödülü bu kişisel kataloglardan ayrıdır.

Eski ortak şablonlar her üye için bağımsız kişisel kopyalara ayrılır. Kopyaların kimlikleri kararlıdır; tekrar okuma/yayınlama yeni kopya üretmez. Mevcut hedef, atama ve geçmiş referansları ilgili kişisel kayda taşınır; puanlar, son tarihler, onaycılar ve geçmiş tutarlar korunur. Böylece başlangıçta aynı olan örnekler kullanıcı bazında bağımsız düzenlenip silinebilir. Bir kaydın kişisini değiştirmek yeni seçimleri etkiler; önceden alınmış görevlerin sabit puan ve süreleri korunur.

## Ortak görevler

Admin görev formunda tek kişi yerine **Ortak görev** seçebilir; en az iki katılımcı işaretlenir. Aynı şablon seçilen kişilerin çarklarında görünür. Her kullanıcı görevi ayrı alır, kendi süresiyle tamamlar ve seçtiği başka üyenin onayından sonra kendi puanını kazanır. Bir kişinin tamamlaması diğerine puan vermez; iptal ve yeniden alma sınırları da kişiseldir. Katılımcı veya puan değişiklikleri sonraki atamalarda geçerlidir; alınmış görevler korunur. Ortak kayıt tek yerden düzenlenir. Admin kişi filtresine ek olarak **Ortak görevler** filtresi vardır. Ödüller kişiye özel kalır; aile yarışmasının ortak büyük ödülü ayrı yönetilir.

## Profil fotoğrafı ve giriş bilgileri

Her üye **Profil ayarları** ekranından fotoğraf ekleyebilir, değiştirebilir veya kaldırabilir. Tarayıcı 8 MB altındaki JPG/PNG/WebP görseli kırpıp küçültür; sunucu dosyayı doğrular ve 256×256 JPEG olarak yeniden kodlar. SVG ve bozuk dosyalar kabul edilmez, metadata tutulmaz. Fotoğraf JSON verisi içinde MySQL'de saklanır, dosya sistemine yüklenmez; otomatik dağıtımda korunur. PHP GD eklentisi gerekir.

Kullanıcı adı veya şifre değişimi mevcut şifreyi gerektirir. Kullanıcı adları benzersizdir; yeni şifre tekrar doğrulanır. Başarılı değişimde oturum kimliği ve CSRF yenilenir; mevcut cihaz açık kalır, diğer cihazların oturumları bir sonraki istekte kapanır. Kullanıcı kendi rolünü veya başka bir hesabın bilgilerini değiştiremez. Başarısız doğrulamalar sınırlandırılır.


## Kullanıcı yönetimi ve küçük çocuk günlük programı

Admin paneli → Kullanıcılar üzerinden yeni hesap eklenir; her hesabın türü Ebeveyn, Çocuk veya Küçük çocuk olarak seçilir. Baba admin kalır; Ebeveyn türü seçmek admin yetkisi vermez. Çıkar işlemi hesabı arşivler, oturumlarını kapatır ve aile panosundan kaldırır. Puan/işlem geçmişi korunur. Çıkarılan kullanıcılar bölümünden geri eklenebilir; eski oturumlar yeniden açılmaz. Açık görevler iptal edilir, bekleyen onaylar uygun aktif üyeye aktarılır. Kullanıcı adları arşivdeyken de ayrılmış kalır.

Küçük çocuk seçilince sekiz günlük görev içeren kişisel program ve onaycı ebeveyn belirlenir. Kullanıcı kartındaki Günlük program ekranından görev adı, simge, başlangıç, bitiş ve puan değiştirilebilir; görev eklenip kaldırılabilir. Saatler Europe/Istanbul'a göre her gün tekrar eder, cron gerekmez. Varsayılanlar: 07:00 yatak, 08:00 kahvaltı, 08:30 diş, 12:00 oyun, 18:00 oyuncaklar, 19:30 pijama, 20:00 diş, 20:30 yatak. Sabah görevleri 12:00, oyun 18:00, akşam görevleri 23:00'te kapanır. Her biri başlangıçta 10 puandır.

Çocuk yalnızca günlük mektuplarını görür. Zarf açılınca renkli görev kartı çıkar; Yaptım seçili ebeveyne onay gönderir ve sıradaki uygun göreve geçer. Çark/ödül/onaylama işlemleri bu hesap türünde sunucuda da engellenir. Puan yalnızca ebeveyn onayıyla yazılır. Bitiş saatinden sonra gönderilemez; zamanında gönderilmiş görev daha sonra onaylanabilir. Çift tıklama tek kayıt oluşturur. Sonradan program düzenlemek gönderilmiş görevin puanını veya süresini değiştirmez. Hareket azaltma tercihi animasyonu kapatır. Mektup açma durumu yalnızca o sayfa oturumundadır; görev geçmişi veritabanında saklanır.


## Çocuk günlük görevleri ve ebeveyn takip paneli

Çocuk hesabında **Günlük görevler** menüsü ve kişisel sayfadan kısayol bulunur; çark ve ödüller de kullanılabilir. Küçük çocuk hesabı mektup ekranını kullanmaya devam eder. Her iki türün günlük programı Admin → Kullanıcılar → Günlük program üzerinden düzenlenir. Günlük görevler seçili ebeveyne (atanmamışsa admin ebeveyne) onaya gider; normal görev tamamlama uç noktası bu onaycı kuralını değiştiremez.

Ebeveyn ve admin hesaplarında **Çocuk takibi** menüsü bulunur. Gün veya hafta (Pazartesi–Pazar), tarih, çocuk ve günlük program/çark filtresi seçilebilir. Rapor her görev için onaylı/toplam, bekleyen, devam eden, süresi dolan/iptal edilen sayılarını ve başarı yüzdesini gösterir. Sabah/akşam aynı adlı görevler saatleriyle ayırt edilir. Rapor yalnızca ebeveynlere sunulur; bu erişim admin düzenleme yetkisi vermez.

Günlük başarı = onaylanan / başlangıç saati gelmiş planlı görev sayısı. Gönderilmeyen ve süresi dolan görevler de paydada yer alır. Gelecek günler ve henüz başlamamış görevler dahil edilmez; hiç görev yoksa yüzde yerine çizgi gösterilir. Bekleyen onaylar başarı sayılmaz. Haftalık toplam, görev sayıları üzerinden hesaplanır; günlük yüzdelerin basit ortalaması alınmaz. Sonradan onaylanan kayıt asıl görev gününe yazılır. Çark raporu ayrı olarak seçili tarihte alınmış görevleri izler; haftalık/aylık görevin hâlâ devam ettiği ayrıca görünür.

Sürüm 8 ilk okumada takip başlangıcını veritabanına bir kez kaydeder. Günlük program değişiklikleri bugünden itibaren uygulanır ve tarihli sürümler halinde tutulur; geçmiş günlerin programı değişmez. İlk takip başlamadan önce süresi bitmiş görevler paydada sayılmaz. Önceki sürümde hiç kaydedilmeyen günler için başarısızlık uydurulmaz; eski günlerde yalnızca var olan görev kayıtları gösterilir ve takip başlangıcı panelde açıklanır. Gönderilmiş görevler düzenleme/silmeden sonra da raporda kalır. Çıkarılan çocuklar raporda Arşiv etiketiyle incelenebilir.


Canlı alan adı `https://yapeglen.com` adresidir. Üretim için genel adres ayarı `app/site.php` dosyasından alınır; özel veritabanı ayarları değiştirilmez. Alan adı değişirse bu dosya yeni HTTPS adresiyle güncellenmelidir. Origin ve CSRF doğrulaması korunur. Geliştirme ortamında yerel `public_url` kullanılır.

### Çocuktan ebeveyne ödül isteği
Çocuk, Ödül istekleri ekranından bir ebeveyn seçerek hayalindeki ödülü gönderir. Seçilen ebeveyn (veya admin) puan belirleyip onaylar ya da notuyla geri çevirir. Onay, kişisel ödülü ve aktif hedefi oluşturur; puan kazandırmaz. Çocuk görev onaylarıyla puan toplar ve yeterli bakiyede mevcut ödül akışını kullanır. Bekleyen istek geri çekilebilir; en fazla beş istek bekleyebilir. Ses tercihi çarkla ortaktır; hareket azaltma tercihi efektlerde korunur.

Doğrulama: `php tests/wishes.php`, `node tests/http.mjs`.

Oyuncu şifreleri en az 4 karakterdir; sayı kullanmak yeterlidir. Eski ev şifresi unutulmuş ve kişisel hesaplar henüz açılmamışsa `recover.php`, yalnızca Hostinger üzerinden yüklenen `app/recovery-key.json` içindeki SHA-256 anahtar özeti ve en fazla 24 saatlik son kullanma zamanı ile açılır. Dosya ve anahtar GitHub’a gönderilmez. Başarılı kullanım dosyayı tüketir ve 30 dakikalık hesap kurulum oturumu açar; mevcut veriler ve veritabanı ayarları değişmez. Kişisel hesaplar oluşturulunca bu kurtarma yolu kapanır.

Çocuk ve küçük çocuk günlük programındaki bitiş saati hatırlatma amaçlıdır: başlangıç saati geldikten sonra gün sonuna kadar gönderilebilir. Geç gönderim kaydedilir ve ebeveyne gösterilir; ebeveyn sonraki günlerde de onaylayabilir. Puan yalnızca onay sonrası eklenir. Çarktan alınan süreli görevlerin son teslim kuralı sürer.

Ebeveynlerin Sayfam ekranında aktif çocukların günlük programları bulunur. Anne dahil her ebeveyn, saati gelen bir günlük görevi çocuk bildirim göndermeden de gözlemleyerek onaylayabilir; mevcut bekleyen bildirim de bu şekilde onaylanır. Gerçek onaylayan kaydedilir ve eşzamanlı/tekrarlanan onaylar ikinci puan oluşturmaz.

### Ebeveyn–çocuk odaklı kullanım
Ebeveyn ana ekranı günlük görevler ve doğrudan onay içindir; takip ekranında gün, hafta veya en fazla bir yıllık tarih aralığı seçilir. Kullanıcı türleri Ebeveyn, Büyük çocuk ve Küçük çocuk olarak gösterilir. Çocuk menüsünde aile panosu, onaylar ve ödül kataloğu bulunmaz. Büyük çocuk görev onayını yalnızca ebeveynden isteyebilir. Ebeveyn günlük veya çark görevlerini istek gelmeden de onaylayabilir. Yeni çark görevleri tüm havuzdan seçilir, ödül seçme önkoşulu ve süre sınırı yoktur; eski görevlerin kayıtları korunur.

Ebeveyn, bugünkü günlük görevleri başlangıç saati gelmeden de doğrudan onaylayabilir. Gerçek işlem zamanı ve erken onay bilgisi kaydedilir. Çocuğun kendi gönderiminde başlangıç saati kuralı sürer.
