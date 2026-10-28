<?php
declare(strict_types=1);
// Versioned deck: IDs and order must remain stable so saved progress stays valid.
function concept_cards(): array {
    static $cards=null;
    return $cards ??= json_decode(<<<'JSON'
[
  {
    "target": "Ayna",
    "context": "",
    "answer": "somut",
    "explanation": "Aynayı görebilir ve yüzeyine dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k001"
  },
  {
    "target": "Masa",
    "context": "",
    "answer": "somut",
    "explanation": "Masayı görebilir ve yüzeyine dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k002"
  },
  {
    "target": "Özgürlük",
    "context": "",
    "answer": "soyut",
    "explanation": "Özgürlük bir durum ve düşünce olarak kavranır; bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k003"
  },
  {
    "target": "Elma",
    "context": "",
    "answer": "somut",
    "explanation": "Elmayı görebilir, koklayabilir ve tadabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k004"
  },
  {
    "target": "Mutluluk",
    "context": "",
    "answer": "soyut",
    "explanation": "Mutluluk bir duygudur. Gülümsemeyi görebiliriz ama mutluluğun kendisini göremeyiz.",
    "stage": "Kelimeleri keşfet",
    "id": "k005"
  },
  {
    "target": "Cesaret",
    "context": "",
    "answer": "soyut",
    "explanation": "Cesaret bir özelliktir. Cesur davranışları görsek de özelliğin kendisi soyuttur.",
    "stage": "Kelimeleri keşfet",
    "id": "k006"
  },
  {
    "target": "Portakal",
    "context": "",
    "answer": "somut",
    "explanation": "Portakalı görebilir, koklayabilir ve tadabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k007"
  },
  {
    "target": "Dürüstlük",
    "context": "",
    "answer": "soyut",
    "explanation": "Dürüstlük, doğruyu söyleme ve doğru davranma özelliğidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k008"
  },
  {
    "target": "Dostluk",
    "context": "",
    "answer": "soyut",
    "explanation": "Dostluk, kişiler arasındaki bir bağdır; beş duyuyla algılanan bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k009"
  },
  {
    "target": "Yetenek",
    "context": "",
    "answer": "soyut",
    "explanation": "Yetenek, bir şeyi yapabilme gücüdür. Ortaya çıkan ürünü görmek yeteneği somut yapmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k010"
  },
  {
    "target": "Sorumluluk",
    "context": "",
    "answer": "soyut",
    "explanation": "Sorumluluk, üstlenilen yükümlülükle ilgili bir kavramdır.",
    "stage": "Kelimeleri keşfet",
    "id": "k011"
  },
  {
    "target": "Adalet",
    "context": "",
    "answer": "soyut",
    "explanation": "Adalet, hakların gözetilmesiyle ilgili bir kavramdır.",
    "stage": "Kelimeleri keşfet",
    "id": "k012"
  },
  {
    "target": "Bardak",
    "context": "",
    "answer": "somut",
    "explanation": "Bardağı görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k013"
  },
  {
    "target": "Düşünce",
    "context": "",
    "answer": "soyut",
    "explanation": "Düşünce, zihinde oluşur. Yazısını görebiliriz ama düşüncenin kendisi soyuttur.",
    "stage": "Kelimeleri keşfet",
    "id": "k014"
  },
  {
    "target": "Ayakkabı",
    "context": "",
    "answer": "somut",
    "explanation": "Ayakkabıyı görme ve dokunma duyularıyla algılarız.",
    "stage": "Kelimeleri keşfet",
    "id": "k015"
  },
  {
    "target": "Kalem",
    "context": "",
    "answer": "somut",
    "explanation": "Kalemi görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k016"
  },
  {
    "target": "Umut",
    "context": "",
    "answer": "soyut",
    "explanation": "Umut, güzel bir şeyin olacağına ilişkin beklentidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k017"
  },
  {
    "target": "Yıldız",
    "context": "",
    "answer": "somut",
    "explanation": "Yıldızı gökyüzünde görebiliriz; uzakta olması onu soyut yapmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k018"
  },
  {
    "target": "Bilgi",
    "context": "",
    "answer": "soyut",
    "explanation": "Bilgi, öğrenilip kavranan şeydir. Bilginin yazıldığı kâğıt ise somuttur.",
    "stage": "Kelimeleri keşfet",
    "id": "k019"
  },
  {
    "target": "Kararlılık",
    "context": "",
    "answer": "soyut",
    "explanation": "Kararlılık, kararından kolayca vazgeçmeme özelliğidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k020"
  },
  {
    "target": "Bisiklet",
    "context": "",
    "answer": "somut",
    "explanation": "Bisikleti görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k021"
  },
  {
    "target": "Güven",
    "context": "",
    "answer": "soyut",
    "explanation": "Güven, birine veya bir şeye inanma duygusudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k022"
  },
  {
    "target": "Bulut",
    "context": "",
    "answer": "somut",
    "explanation": "Bulutu görebiliriz; somut olması için ona dokunmak zorunda değiliz.",
    "stage": "Kelimeleri keşfet",
    "id": "k023"
  },
  {
    "target": "Bal",
    "context": "",
    "answer": "somut",
    "explanation": "Balı tadabilir ve rengini görebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k024"
  },
  {
    "target": "Sevgi",
    "context": "",
    "answer": "soyut",
    "explanation": "Sevgi bir duygudur; beş duyuyla doğrudan algılanan bir varlık değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k025"
  },
  {
    "target": "Hayal",
    "context": "",
    "answer": "soyut",
    "explanation": "Hayal zihnimizde canlandırdığımız şeydir; dış dünyada duyularla algılanan bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k026"
  },
  {
    "target": "Telefon",
    "context": "",
    "answer": "somut",
    "explanation": "Telefonu görebilir ve elimize alabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k027"
  },
  {
    "target": "Kedi",
    "context": "",
    "answer": "somut",
    "explanation": "Kediyi görebilir ve sesini işitebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k028"
  },
  {
    "target": "Pişmanlık",
    "context": "",
    "answer": "soyut",
    "explanation": "Pişmanlık, bir davranıştan sonra duyulan üzüntüyle ilgili bir duygudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k029"
  },
  {
    "target": "Üzüntü",
    "context": "",
    "answer": "soyut",
    "explanation": "Üzüntü bir duygudur; bir nesne gibi beş duyuyla algılanmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k030"
  },
  {
    "target": "Toprak",
    "context": "",
    "answer": "somut",
    "explanation": "Toprağı görebilir, koklayabilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k031"
  },
  {
    "target": "Deniz",
    "context": "",
    "answer": "somut",
    "explanation": "Denizi görebilir ve suyuna dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k032"
  },
  {
    "target": "Özlem",
    "context": "",
    "answer": "soyut",
    "explanation": "Özlem, birini veya bir yeri yeniden görme isteğiyle ilgili bir duygudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k033"
  },
  {
    "target": "Taş",
    "context": "",
    "answer": "somut",
    "explanation": "Taşa dokunabilir, rengini ve biçimini görebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k034"
  },
  {
    "target": "Çiçek",
    "context": "",
    "answer": "somut",
    "explanation": "Çiçeği görebilir ve koklayabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k035"
  },
  {
    "target": "Çanta",
    "context": "",
    "answer": "somut",
    "explanation": "Çantayı görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k036"
  },
  {
    "target": "Endişe",
    "context": "",
    "answer": "soyut",
    "explanation": "Endişe, olabilecek bir şeyle ilgili kaygı duygusudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k037"
  },
  {
    "target": "Kitap",
    "context": "",
    "answer": "somut",
    "explanation": "Kitap, görme ve dokunma duyularıyla algılanır.",
    "stage": "Kelimeleri keşfet",
    "id": "k038"
  },
  {
    "target": "Sabır",
    "context": "",
    "answer": "soyut",
    "explanation": "Sabır, bekleyebilme ve güçlüğe dayanabilme özelliğidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k039"
  },
  {
    "target": "Kuş",
    "context": "",
    "answer": "somut",
    "explanation": "Kuşu görebilir ve ötüşünü duyabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k040"
  },
  {
    "target": "Zekâ",
    "context": "",
    "answer": "soyut",
    "explanation": "Zekâ, düşünme ve öğrenmeyle ilgili bir yetidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k041"
  },
  {
    "target": "Başarı",
    "context": "",
    "answer": "soyut",
    "explanation": "Başarı, bir hedefe ulaşma durumudur; bir eşya değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k042"
  },
  {
    "target": "Güneş",
    "context": "",
    "answer": "somut",
    "explanation": "Güneş, gökyüzünde görme duyusuyla algılanır; ona dokunmamız gerekmez.",
    "stage": "Kelimeleri keşfet",
    "id": "k043"
  },
  {
    "target": "Anahtar",
    "context": "",
    "answer": "somut",
    "explanation": "Anahtarı görme ve dokunma duyularıyla algılarız.",
    "stage": "Kelimeleri keşfet",
    "id": "k044"
  },
  {
    "target": "Yağmur",
    "context": "",
    "answer": "somut",
    "explanation": "Yağmuru görebilir ve damlalarını tenimizde hissedebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k045"
  },
  {
    "target": "Kıskançlık",
    "context": "",
    "answer": "soyut",
    "explanation": "Kıskançlık bir duygudur; doğrudan beş duyuyla algılanmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k046"
  },
  {
    "target": "Ekmek",
    "context": "",
    "answer": "somut",
    "explanation": "Ekmeği görebilir, koklayabilir ve tadabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k047"
  },
  {
    "target": "Davul",
    "context": "",
    "answer": "somut",
    "explanation": "Davulu görebilir, ona dokunabilir ve sesini duyabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k048"
  },
  {
    "target": "Huzur",
    "context": "",
    "answer": "soyut",
    "explanation": "Huzur, rahatlık ve iç dinginliği anlatan bir durumdur.",
    "stage": "Kelimeleri keşfet",
    "id": "k049"
  },
  {
    "target": "Kelebek",
    "context": "",
    "answer": "somut",
    "explanation": "Kelebeği görme duyusuyla algılarız.",
    "stage": "Kelimeleri keşfet",
    "id": "k050"
  },
  {
    "target": "Merak",
    "context": "",
    "answer": "soyut",
    "explanation": "Merak, öğrenmeye yönelik bir istek ve duygudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k051"
  },
  {
    "target": "Neşe",
    "context": "",
    "answer": "soyut",
    "explanation": "Neşe bir duygudur; duyularımızla algıladığımız bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k052"
  },
  {
    "target": "Limon",
    "context": "",
    "answer": "somut",
    "explanation": "Limonun kokusunu ve ekşi tadını duyularımızla algılarız.",
    "stage": "Kelimeleri keşfet",
    "id": "k053"
  },
  {
    "target": "Ağaç",
    "context": "",
    "answer": "somut",
    "explanation": "Ağacı görebilir ve gövdesine dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k054"
  },
  {
    "target": "İyilik",
    "context": "",
    "answer": "soyut",
    "explanation": "İyilik bir değer ve kavramdır; yardım davranışı görülebilir ama iyilik bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k055"
  },
  {
    "target": "Saygı",
    "context": "",
    "answer": "soyut",
    "explanation": "Saygı bir tutum ve değerdir; bir nesne gibi algılanmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k056"
  },
  {
    "target": "Korku",
    "context": "",
    "answer": "soyut",
    "explanation": "Korku bir duygudur; onu doğrudan göremeyiz veya ona dokunamayız.",
    "stage": "Kelimeleri keşfet",
    "id": "k057"
  },
  {
    "target": "Kar",
    "context": "",
    "answer": "somut",
    "explanation": "Karı görebilir ve soğukluğunu tenimizde hissedebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k058"
  },
  {
    "target": "Heyecan",
    "context": "",
    "answer": "soyut",
    "explanation": "Heyecan bir duygudur. Kalbin hızlı atması onun belirtisi olabilir.",
    "stage": "Kelimeleri keşfet",
    "id": "k059"
  },
  {
    "target": "Su",
    "context": "",
    "answer": "somut",
    "explanation": "Suyu görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k060"
  },
  {
    "target": "Merhamet",
    "context": "Yaralı kuşa merhamet duydu.",
    "answer": "soyut",
    "explanation": "Merhamet, başka bir canlının acısına üzülme ve yardım etme duygusudur.",
    "stage": "Cümlede düşün",
    "id": "k061"
  },
  {
    "target": "Zil sesi",
    "context": "Okulun zil sesini duyduk.",
    "answer": "somut",
    "explanation": "Zil sesi işitme duyusuyla algılanır; görünmemesi onu soyut yapmaz.",
    "stage": "Cümlede düşün",
    "id": "k062"
  },
  {
    "target": "Sabun kokusu",
    "context": "Ellerimden sabun kokusu geliyordu.",
    "answer": "somut",
    "explanation": "Bu koku koklama duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k063"
  },
  {
    "target": "Işık",
    "context": "Yeni öğrendiğim bilgiler bana ışık oldu.",
    "answer": "soyut",
    "explanation": "Burada ışık, görülen aydınlık değil, yol gösteren bilgi anlamında kullanılmıştır.",
    "stage": "Cümlede düşün",
    "id": "k064"
  },
  {
    "target": "Gözyaşı",
    "context": "Yanağından bir gözyaşı süzüldü.",
    "answer": "somut",
    "explanation": "Gözyaşı görülebilen bir damladır; üzüntü gibi bir duygu değildir.",
    "stage": "Cümlede düşün",
    "id": "k065"
  },
  {
    "target": "Hoşgörü",
    "context": "Arkadaşının hatasına hoşgörüyle yaklaştı.",
    "answer": "soyut",
    "explanation": "Hoşgörü, farklılıklara ve hatalara anlayışla yaklaşma tutumudur.",
    "stage": "Cümlede düşün",
    "id": "k066"
  },
  {
    "target": "Gökkuşağı",
    "context": "Yağmurdan sonra gökkuşağı gördük.",
    "answer": "somut",
    "explanation": "Gökkuşağı görme duyusuyla algılanır; elle tutulması gerekmez.",
    "stage": "Cümlede düşün",
    "id": "k067"
  },
  {
    "target": "Gülümseme",
    "context": "Yüzünde bir gülümseme belirdi.",
    "answer": "somut",
    "explanation": "Gülümseme görülebilen bir yüz hareketidir; mutluluk duygusunun kendisinden farklıdır.",
    "stage": "Cümlede düşün",
    "id": "k068"
  },
  {
    "target": "Sabır",
    "context": "Fidanın büyümesini sabırla bekledi.",
    "answer": "soyut",
    "explanation": "Sabır, beklemeye dayanabilme özelliğidir; fidanın kendisi ise somuttur.",
    "stage": "Cümlede düşün",
    "id": "k069"
  },
  {
    "target": "Hayal kırıklığı",
    "context": "Maç ertelenince hayal kırıklığı yaşadım.",
    "answer": "soyut",
    "explanation": "Hayal kırıklığı bir duygudur; görülen veya dokunulan bir nesne değildir.",
    "stage": "Cümlede düşün",
    "id": "k070"
  },
  {
    "target": "Azim",
    "context": "Zor soruyu çözmek için azim gösterdi.",
    "answer": "soyut",
    "explanation": "Azim, bir işi başarma konusunda güçlü ve sürekli isteği anlatır.",
    "stage": "Cümlede düşün",
    "id": "k071"
  },
  {
    "target": "Dostluk",
    "context": "Aralarındaki dostluk yıllarca sürdü.",
    "answer": "soyut",
    "explanation": "Dostluk, insanlar arasındaki bir bağdır.",
    "stage": "Cümlede düşün",
    "id": "k072"
  },
  {
    "target": "Soğukluk",
    "context": "Buzun soğukluğunu avucumda hissettim.",
    "answer": "somut",
    "explanation": "Burada fiziksel soğukluk anlatılır; dokunma duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k073"
  },
  {
    "target": "Çilek kokusu",
    "context": "Sepetten çilek kokusu geliyordu.",
    "answer": "somut",
    "explanation": "Çilek kokusu koklama duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k074"
  },
  {
    "target": "Adalet",
    "context": "Hakem kararlarında adaleti gözetti.",
    "answer": "soyut",
    "explanation": "Adalet, haklı ve eşit davranmayla ilgili bir kavramdır.",
    "stage": "Cümlede düşün",
    "id": "k075"
  },
  {
    "target": "Limonun tadı",
    "context": "Limonun tadı dilimi burktu.",
    "answer": "somut",
    "explanation": "Buradaki tat, tatma duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k076"
  },
  {
    "target": "Sıcaklık",
    "context": "Bardağın sıcaklığını elimde hissettim.",
    "answer": "somut",
    "explanation": "Burada fiziksel sıcaklık anlatılır; dokunma duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k077"
  },
  {
    "target": "Düşünce",
    "context": "Aklıma yeni bir düşünce geldi.",
    "answer": "soyut",
    "explanation": "Düşünce zihinde oluşur; bir nesne gibi algılanmaz.",
    "stage": "Cümlede düşün",
    "id": "k078"
  },
  {
    "target": "Sorumluluk",
    "context": "Bitkileri sulama sorumluluğunu üstlendim.",
    "answer": "soyut",
    "explanation": "Sorumluluk bir yükümlülüktür; bitki ve su gibi somut bir varlık değildir.",
    "stage": "Cümlede düşün",
    "id": "k079"
  },
  {
    "target": "Minnettarlık",
    "context": "Yardımı için minnettarlık duydum.",
    "answer": "soyut",
    "explanation": "Minnettarlık, yapılan iyilik karşısında duyulan bir duygudur.",
    "stage": "Cümlede düşün",
    "id": "k080"
  },
  {
    "target": "Tuzun tadı",
    "context": "Çorbadaki tuzun tadını aldım.",
    "answer": "somut",
    "explanation": "Tuzun tadı tatma duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k081"
  },
  {
    "target": "Işık",
    "context": "Lambanın ışığı odayı aydınlattı.",
    "answer": "somut",
    "explanation": "Lambanın ışığı görme duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k082"
  },
  {
    "target": "Özlem",
    "context": "Dedeme duyduğum özlem büyüdü.",
    "answer": "soyut",
    "explanation": "Burada anlatılan özlem bir duygudur.",
    "stage": "Cümlede düşün",
    "id": "k083"
  },
  {
    "target": "Sevinç",
    "context": "Haberi duyunca büyük bir sevinç yaşadım.",
    "answer": "soyut",
    "explanation": "Sevinç, zihnimizde yaşadığımız bir duygudur.",
    "stage": "Cümlede düşün",
    "id": "k084"
  },
  {
    "target": "Parfüm kokusu",
    "context": "Odaya parfüm kokusu yayıldı.",
    "answer": "somut",
    "explanation": "Parfüm kokusu koklama duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k085"
  },
  {
    "target": "Müzik",
    "context": "Radyodan gelen müziği dinledim.",
    "answer": "somut",
    "explanation": "Burada işitilen sesler anlatılır; müzik işitme duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k086"
  },
  {
    "target": "Pürüzlü yüzey",
    "context": "Taşın pürüzlü yüzeyine dokundum.",
    "answer": "somut",
    "explanation": "Yüzeyin pürüzünü dokunma duyusuyla algılarız.",
    "stage": "Cümlede düşün",
    "id": "k087"
  },
  {
    "target": "Sıcaklık",
    "context": "Bizi karşılayan ailenin sıcaklığı içimi rahatlattı.",
    "answer": "soyut",
    "explanation": "Burada sıcaklık, fiziksel ısı değil, içtenlik ve yakınlık anlamındadır.",
    "stage": "Cümlede düşün",
    "id": "k088"
  },
  {
    "target": "Rüzgâr",
    "context": "Rüzgâr yüzüme vurdu.",
    "answer": "somut",
    "explanation": "Rüzgârı tenimizde hissederiz; dokunma duyusuyla algılanabilir.",
    "stage": "Cümlede düşün",
    "id": "k089"
  },
  {
    "target": "Gök gürültüsü",
    "context": "Gök gürültüsü bizi uyandırdı.",
    "answer": "somut",
    "explanation": "Gök gürültüsünü işitebiliriz.",
    "stage": "Cümlede düşün",
    "id": "k090"
  },
  {
    "target": "Alkış sesi",
    "context": "Salondaki alkış sesini duydum.",
    "answer": "somut",
    "explanation": "Alkış sesi işitme duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k091"
  },
  {
    "target": "Gölge",
    "context": "Ağacın gölgesi yere düştü.",
    "answer": "somut",
    "explanation": "Gölgeyi görebiliriz; tutamamak bir şeyi soyut yapmaz.",
    "stage": "Cümlede düşün",
    "id": "k092"
  },
  {
    "target": "Eşitlik",
    "context": "Oyunda herkes için eşitlik istedik.",
    "answer": "soyut",
    "explanation": "Eşitlik, aynı haklara veya koşullara sahip olmayla ilgili bir kavramdır.",
    "stage": "Cümlede düşün",
    "id": "k093"
  },
  {
    "target": "Vicdan",
    "context": "Vicdanı, yaptığı hatayı düzeltmesini söyledi.",
    "answer": "soyut",
    "explanation": "Vicdan, doğru ve yanlışı değerlendirmeyle ilgili bir kavramdır.",
    "stage": "Cümlede düşün",
    "id": "k094"
  },
  {
    "target": "Dayanışma",
    "context": "Takım arkadaşlarım arasında dayanışma vardı.",
    "answer": "soyut",
    "explanation": "Dayanışma, birbirini desteklemeyle ilgili bir kavramdır.",
    "stage": "Cümlede düşün",
    "id": "k095"
  },
  {
    "target": "Soğukluk",
    "context": "İki arkadaşın arasındaki soğukluk sona erdi.",
    "answer": "soyut",
    "explanation": "Burada soğukluk, düşük sıcaklık değil, ilişkideki uzaklık anlamındadır.",
    "stage": "Cümlede düşün",
    "id": "k096"
  },
  {
    "target": "Kuş cıvıltısı",
    "context": "Sabah kuş cıvıltısıyla uyandım.",
    "answer": "somut",
    "explanation": "Cıvıltı bir sestir; işitme duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k097"
  },
  {
    "target": "Umut",
    "context": "Yarın için umudumu korudum.",
    "answer": "soyut",
    "explanation": "Umut, geleceğe ilişkin olumlu bir beklentidir.",
    "stage": "Cümlede düşün",
    "id": "k098"
  },
  {
    "target": "Özgüven",
    "context": "Sunum yaptıkça özgüvenim arttı.",
    "answer": "soyut",
    "explanation": "Özgüven, kişinin kendisine duyduğu güvendir.",
    "stage": "Cümlede düşün",
    "id": "k099"
  },
  {
    "target": "Duman",
    "context": "Bacadan duman yükseldi.",
    "answer": "somut",
    "explanation": "Dumanı görebiliriz; kokusunu da algılayabiliriz.",
    "stage": "Cümlede düşün",
    "id": "k100"
  }
]
JSON, true, 512, JSON_THROW_ON_ERROR);
}
function concept_study_snapshot(array $actor): ?array {
    if(($actor['memberType']??'')!=='child')return null;
    $answers=$actor['conceptStudyV1']??[];$cards=concept_cards();$correct=0;
    foreach($cards as &$card){
        $saved=$answers[$card['id']]??null;
        if($saved){$card['response']=$saved['choice'];$card['correct']=$saved['choice']===$card['answer'];if($card['correct'])$correct++;}
        else unset($card['answer'],$card['explanation']);
    }unset($card);
    return ['version'=>1,'cards'=>$cards,'completed'=>count($answers),'correct'=>$correct,'total'=>count($cards)];
}
function answer_concept_card(array &$s,array $actor,array $in): void {
    $i=active_user_index($s,$actor['id']);$user=$s['users'][$i];
    if(($user['memberType']??'')!=='child')throw new AppError('Çalışma kartları büyük çocuk hesabına açık.',403);
    if(isset($in['userId'])&&$in['userId']!==$actor['id'])throw new AppError('Yalnızca kendi kartını cevaplayabilirsin.',403);
    $id=$in['cardId']??null;$choice=$in['choice']??null;
    if(!is_string($id)||!in_array($choice,['somut','soyut'],true))throw new AppError('Somut veya soyut seç.');
    $cards=concept_cards();$answers=$user['conceptStudyV1']??[];
    // The first answer is final. Retrying a request or answering on two devices cannot double count.
    if(isset($answers[$id]))return;
    if(($cards[count($answers)]['id']??null)!==$id)throw new AppError('Kaldığın kart değişti. Güncel karttan devam et.',409);
    $answers[$id]=['choice'=>$choice,'answeredAt'=>now_tr()->format(DATE_ATOM)];
    $s['users'][$i]['conceptStudyV1']=$answers;
}
