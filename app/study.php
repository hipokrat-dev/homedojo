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
    "id": "k001",
    "example": "Aynada saçlarımı düzelttim."
  },
  {
    "target": "Masa",
    "context": "",
    "answer": "somut",
    "explanation": "Masayı görebilir ve yüzeyine dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k002",
    "example": "Kitabımı masanın üstüne koydum."
  },
  {
    "target": "Özgürlük",
    "context": "",
    "answer": "soyut",
    "explanation": "Özgürlük bir durum ve düşünce olarak kavranır; bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k003",
    "example": "Kuşların özgürlüğü üzerine düşündüm."
  },
  {
    "target": "Elma",
    "context": "",
    "answer": "somut",
    "explanation": "Elmayı görebilir, koklayabilir ve tadabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k004",
    "example": "Kırmızı elmayı dilimledim."
  },
  {
    "target": "Mutluluk",
    "context": "",
    "answer": "soyut",
    "explanation": "Mutluluk bir duygudur. Gülümsemeyi görebiliriz ama mutluluğun kendisini göremeyiz.",
    "stage": "Kelimeleri keşfet",
    "id": "k005",
    "example": "Arkadaşımla buluşmak bana mutluluk verdi."
  },
  {
    "target": "Cesaret",
    "context": "",
    "answer": "soyut",
    "explanation": "Cesaret bir özelliktir. Cesur davranışları görsek de özelliğin kendisi soyuttur.",
    "stage": "Kelimeleri keşfet",
    "id": "k006",
    "example": "İlk kez sahneye çıkmak cesaret ister."
  },
  {
    "target": "Portakal",
    "context": "",
    "answer": "somut",
    "explanation": "Portakalı görebilir, koklayabilir ve tadabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k007",
    "example": "Portakalın kabuğunu soydum."
  },
  {
    "target": "Dürüstlük",
    "context": "",
    "answer": "soyut",
    "explanation": "Dürüstlük, doğruyu söyleme ve doğru davranma özelliğidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k008",
    "example": "Dürüstlük, doğruyu söylemeyi gerektirir."
  },
  {
    "target": "Dostluk",
    "context": "",
    "answer": "soyut",
    "explanation": "Dostluk, kişiler arasındaki bir bağdır; beş duyuyla algılanan bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k009",
    "example": "Aramızdaki dostluk yıllardır sürüyor."
  },
  {
    "target": "Yetenek",
    "context": "",
    "answer": "soyut",
    "explanation": "Yetenek, bir şeyi yapabilme gücüdür. Ortaya çıkan ürünü görmek yeteneği somut yapmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k010",
    "example": "Resim yapma yeteneğini çalışarak geliştirdi."
  },
  {
    "target": "Sorumluluk",
    "context": "",
    "answer": "soyut",
    "explanation": "Sorumluluk, üstlenilen yükümlülükle ilgili bir kavramdır.",
    "stage": "Kelimeleri keşfet",
    "id": "k011",
    "example": "Bitkileri sulamak benim sorumluluğum."
  },
  {
    "target": "Adalet",
    "context": "",
    "answer": "soyut",
    "explanation": "Adalet, hakların gözetilmesiyle ilgili bir kavramdır.",
    "stage": "Kelimeleri keşfet",
    "id": "k012",
    "example": "Herkese eşit davranmak adalet duygusunu güçlendirir."
  },
  {
    "target": "Bardak",
    "context": "",
    "answer": "somut",
    "explanation": "Bardağı görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k013",
    "example": "Bardağa su doldurdum."
  },
  {
    "target": "Düşünce",
    "context": "",
    "answer": "soyut",
    "explanation": "Düşünce, zihinde oluşur. Yazısını görebiliriz ama düşüncenin kendisi soyuttur.",
    "stage": "Kelimeleri keşfet",
    "id": "k014",
    "example": "Bu düşünce aklıma yolda geldi."
  },
  {
    "target": "Ayakkabı",
    "context": "",
    "answer": "somut",
    "explanation": "Ayakkabıyı görme ve dokunma duyularıyla algılarız.",
    "stage": "Kelimeleri keşfet",
    "id": "k015",
    "example": "Ayakkabılarımın bağcıklarını bağladım."
  },
  {
    "target": "Kalem",
    "context": "",
    "answer": "somut",
    "explanation": "Kalemi görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k016",
    "example": "Kalemin ucunu açtım."
  },
  {
    "target": "Umut",
    "context": "",
    "answer": "soyut",
    "explanation": "Umut, güzel bir şeyin olacağına ilişkin beklentidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k017",
    "example": "Başaracağıma dair umudumu korudum."
  },
  {
    "target": "Yıldız",
    "context": "",
    "answer": "somut",
    "explanation": "Yıldızı gökyüzünde görebiliriz; uzakta olması onu soyut yapmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k018",
    "example": "Gökyüzünde parlak bir yıldız gördüm."
  },
  {
    "target": "Bilgi",
    "context": "",
    "answer": "soyut",
    "explanation": "Bilgi, öğrenilip kavranan şeydir. Bilginin yazıldığı kâğıt ise somuttur.",
    "stage": "Kelimeleri keşfet",
    "id": "k019",
    "example": "Okudukça yeni bilgiler öğrendim."
  },
  {
    "target": "Kararlılık",
    "context": "",
    "answer": "soyut",
    "explanation": "Kararlılık, kararından kolayca vazgeçmeme özelliğidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k020",
    "example": "Kararlılığı sayesinde çalışmayı sürdürdü."
  },
  {
    "target": "Bisiklet",
    "context": "",
    "answer": "somut",
    "explanation": "Bisikleti görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k021",
    "example": "Bisikletimin lastiğini şişirdim."
  },
  {
    "target": "Güven",
    "context": "",
    "answer": "soyut",
    "explanation": "Güven, birine veya bir şeye inanma duygusudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k022",
    "example": "Sözünü tutması ona duyduğum güveni artırdı."
  },
  {
    "target": "Bulut",
    "context": "",
    "answer": "somut",
    "explanation": "Bulutu görebiliriz; somut olması için ona dokunmak zorunda değiliz.",
    "stage": "Kelimeleri keşfet",
    "id": "k023",
    "example": "Beyaz bulut gökyüzünde ilerledi."
  },
  {
    "target": "Bal",
    "context": "",
    "answer": "somut",
    "explanation": "Balı tadabilir ve rengini görebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k024",
    "example": "Ekmeğime bal sürdüm."
  },
  {
    "target": "Sevgi",
    "context": "",
    "answer": "soyut",
    "explanation": "Sevgi bir duygudur; beş duyuyla doğrudan algılanan bir varlık değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k025",
    "example": "Aileme duyduğum sevgi çok güçlü."
  },
  {
    "target": "Hayal",
    "context": "",
    "answer": "soyut",
    "explanation": "Hayal zihnimizde canlandırdığımız şeydir; dış dünyada duyularla algılanan bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k026",
    "example": "Gelecek yaz için güzel bir hayal kurdum."
  },
  {
    "target": "Telefon",
    "context": "",
    "answer": "somut",
    "explanation": "Telefonu görebilir ve elimize alabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k027",
    "example": "Telefonun ekranını temizledim."
  },
  {
    "target": "Kedi",
    "context": "",
    "answer": "somut",
    "explanation": "Kediyi görebilir ve sesini işitebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k028",
    "example": "Kedi pencerenin önünde uyudu."
  },
  {
    "target": "Pişmanlık",
    "context": "",
    "answer": "soyut",
    "explanation": "Pişmanlık, bir davranıştan sonra duyulan üzüntüyle ilgili bir duygudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k029",
    "example": "Arkadaşımı kırınca pişmanlık duydum."
  },
  {
    "target": "Üzüntü",
    "context": "",
    "answer": "soyut",
    "explanation": "Üzüntü bir duygudur; bir nesne gibi beş duyuyla algılanmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k030",
    "example": "Ayrılırken içimi üzüntü kapladı."
  },
  {
    "target": "Toprak",
    "context": "",
    "answer": "somut",
    "explanation": "Toprağı görebilir, koklayabilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k031",
    "example": "Saksıya biraz toprak koydum."
  },
  {
    "target": "Deniz",
    "context": "",
    "answer": "somut",
    "explanation": "Denizi görebilir ve suyuna dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k032",
    "example": "Denizin mavi yüzeyine baktım."
  },
  {
    "target": "Özlem",
    "context": "",
    "answer": "soyut",
    "explanation": "Özlem, birini veya bir yeri yeniden görme isteğiyle ilgili bir duygudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k033",
    "example": "Uzakta yaşayan kuzenime özlem duyuyorum."
  },
  {
    "target": "Taş",
    "context": "",
    "answer": "somut",
    "explanation": "Taşa dokunabilir, rengini ve biçimini görebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k034",
    "example": "Yerden küçük bir taş aldım."
  },
  {
    "target": "Çiçek",
    "context": "",
    "answer": "somut",
    "explanation": "Çiçeği görebilir ve koklayabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k035",
    "example": "Çiçeğin yapraklarına dokundum."
  },
  {
    "target": "Çanta",
    "context": "",
    "answer": "somut",
    "explanation": "Çantayı görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k036",
    "example": "Defterlerimi çantama yerleştirdim."
  },
  {
    "target": "Endişe",
    "context": "",
    "answer": "soyut",
    "explanation": "Endişe, olabilecek bir şeyle ilgili kaygı duygusudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k037",
    "example": "Sonucu beklerken endişe duydum."
  },
  {
    "target": "Kitap",
    "context": "",
    "answer": "somut",
    "explanation": "Kitap, görme ve dokunma duyularıyla algılanır.",
    "stage": "Kelimeleri keşfet",
    "id": "k038",
    "example": "Kitabın sayfalarını çevirdim."
  },
  {
    "target": "Sabır",
    "context": "",
    "answer": "soyut",
    "explanation": "Sabır, bekleyebilme ve güçlüğe dayanabilme özelliğidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k039",
    "example": "Sıramızı beklemek sabır gerektirir."
  },
  {
    "target": "Kuş",
    "context": "",
    "answer": "somut",
    "explanation": "Kuşu görebilir ve ötüşünü duyabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k040",
    "example": "Kuş ağacın dalına kondu."
  },
  {
    "target": "Zekâ",
    "context": "",
    "answer": "soyut",
    "explanation": "Zekâ, düşünme ve öğrenmeyle ilgili bir yetidir.",
    "stage": "Kelimeleri keşfet",
    "id": "k041",
    "example": "Zekâsını farklı çözümler bulmak için kullandı."
  },
  {
    "target": "Başarı",
    "context": "",
    "answer": "soyut",
    "explanation": "Başarı, bir hedefe ulaşma durumudur; bir eşya değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k042",
    "example": "Bu başarı düzenli çalışmanın sonucudur."
  },
  {
    "target": "Güneş",
    "context": "",
    "answer": "somut",
    "explanation": "Güneş, gökyüzünde görme duyusuyla algılanır; ona dokunmamız gerekmez.",
    "stage": "Kelimeleri keşfet",
    "id": "k043",
    "example": "Güneş ufukta göründü."
  },
  {
    "target": "Anahtar",
    "context": "",
    "answer": "somut",
    "explanation": "Anahtarı görme ve dokunma duyularıyla algılarız.",
    "stage": "Kelimeleri keşfet",
    "id": "k044",
    "example": "Anahtarı cebimden çıkardım."
  },
  {
    "target": "Yağmur",
    "context": "",
    "answer": "somut",
    "explanation": "Yağmuru görebilir ve damlalarını tenimizde hissedebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k045",
    "example": "Yağmur penceremin camına vurdu."
  },
  {
    "target": "Kıskançlık",
    "context": "",
    "answer": "soyut",
    "explanation": "Kıskançlık bir duygudur; doğrudan beş duyuyla algılanmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k046",
    "example": "Arkadaşının ödülünü görünce kıskançlık hissetti."
  },
  {
    "target": "Ekmek",
    "context": "",
    "answer": "somut",
    "explanation": "Ekmeği görebilir, koklayabilir ve tadabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k047",
    "example": "Fırından sıcak ekmek aldım."
  },
  {
    "target": "Davul",
    "context": "",
    "answer": "somut",
    "explanation": "Davulu görebilir, ona dokunabilir ve sesini duyabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k048",
    "example": "Davulun sesini uzaktan duydum."
  },
  {
    "target": "Huzur",
    "context": "",
    "answer": "soyut",
    "explanation": "Huzur, rahatlık ve iç dinginliği anlatan bir durumdur.",
    "stage": "Kelimeleri keşfet",
    "id": "k049",
    "example": "Sessiz bir akşam bana huzur verdi."
  },
  {
    "target": "Kelebek",
    "context": "",
    "answer": "somut",
    "explanation": "Kelebeği görme duyusuyla algılarız.",
    "stage": "Kelimeleri keşfet",
    "id": "k050",
    "example": "Renkli kelebek çiçeğe kondu."
  },
  {
    "target": "Merak",
    "context": "",
    "answer": "soyut",
    "explanation": "Merak, öğrenmeye yönelik bir istek ve duygudur.",
    "stage": "Kelimeleri keşfet",
    "id": "k051",
    "example": "Merak, yeni sorular sormamı sağladı."
  },
  {
    "target": "Neşe",
    "context": "",
    "answer": "soyut",
    "explanation": "Neşe bir duygudur; duyularımızla algıladığımız bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k052",
    "example": "Güzel haber hepimize neşe verdi."
  },
  {
    "target": "Limon",
    "context": "",
    "answer": "somut",
    "explanation": "Limonun kokusunu ve ekşi tadını duyularımızla algılarız.",
    "stage": "Kelimeleri keşfet",
    "id": "k053",
    "example": "Limonun ekşi tadını aldım."
  },
  {
    "target": "Ağaç",
    "context": "",
    "answer": "somut",
    "explanation": "Ağacı görebilir ve gövdesine dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k054",
    "example": "Ağacın gövdesine dokundum."
  },
  {
    "target": "İyilik",
    "context": "",
    "answer": "soyut",
    "explanation": "İyilik bir değer ve kavramdır; yardım davranışı görülebilir ama iyilik bir nesne değildir.",
    "stage": "Kelimeleri keşfet",
    "id": "k055",
    "example": "Yardım etmek bir iyilik örneğidir."
  },
  {
    "target": "Saygı",
    "context": "",
    "answer": "soyut",
    "explanation": "Saygı bir tutum ve değerdir; bir nesne gibi algılanmaz.",
    "stage": "Kelimeleri keşfet",
    "id": "k056",
    "example": "Birbirimizi dinlemek saygı göstermektir."
  },
  {
    "target": "Korku",
    "context": "",
    "answer": "soyut",
    "explanation": "Korku bir duygudur; onu doğrudan göremeyiz veya ona dokunamayız.",
    "stage": "Kelimeleri keşfet",
    "id": "k057",
    "example": "Karanlıkta yürürken korku hissettim."
  },
  {
    "target": "Kar",
    "context": "",
    "answer": "somut",
    "explanation": "Karı görebilir ve soğukluğunu tenimizde hissedebiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k058",
    "example": "Kar taneleri eldivenime düştü."
  },
  {
    "target": "Heyecan",
    "context": "",
    "answer": "soyut",
    "explanation": "Heyecan bir duygudur. Kalbin hızlı atması onun belirtisi olabilir.",
    "stage": "Kelimeleri keşfet",
    "id": "k059",
    "example": "Yarışma öncesinde heyecan duydum."
  },
  {
    "target": "Su",
    "context": "",
    "answer": "somut",
    "explanation": "Suyu görebilir ve ona dokunabiliriz.",
    "stage": "Kelimeleri keşfet",
    "id": "k060",
    "example": "Suyu bardağa döktüm."
  },
  {
    "target": "Merhamet",
    "context": "Yaralı kuşa merhamet duydu.",
    "answer": "soyut",
    "explanation": "Merhamet, başka bir canlının acısına üzülme ve yardım etme duygusudur.",
    "stage": "Cümlede düşün",
    "id": "k061",
    "example": "Yaralı kuşa merhamet duydu."
  },
  {
    "target": "Zil sesi",
    "context": "Okulun zil sesini duyduk.",
    "answer": "somut",
    "explanation": "Zil sesi işitme duyusuyla algılanır; görünmemesi onu soyut yapmaz.",
    "stage": "Cümlede düşün",
    "id": "k062",
    "example": "Okulun zil sesini duyduk."
  },
  {
    "target": "Sabun kokusu",
    "context": "Ellerimden sabun kokusu geliyordu.",
    "answer": "somut",
    "explanation": "Bu koku koklama duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k063",
    "example": "Ellerimden sabun kokusu geliyordu."
  },
  {
    "target": "Işık",
    "context": "Yeni öğrendiğim bilgiler bana ışık oldu.",
    "answer": "soyut",
    "explanation": "Burada ışık, görülen aydınlık değil, yol gösteren bilgi anlamında kullanılmıştır.",
    "stage": "Cümlede düşün",
    "id": "k064",
    "example": "Yeni öğrendiğim bilgiler bana ışık oldu."
  },
  {
    "target": "Gözyaşı",
    "context": "Yanağından bir gözyaşı süzüldü.",
    "answer": "somut",
    "explanation": "Gözyaşı görülebilen bir damladır; üzüntü gibi bir duygu değildir.",
    "stage": "Cümlede düşün",
    "id": "k065",
    "example": "Yanağından bir gözyaşı süzüldü."
  },
  {
    "target": "Hoşgörü",
    "context": "Arkadaşının hatasına hoşgörüyle yaklaştı.",
    "answer": "soyut",
    "explanation": "Hoşgörü, farklılıklara ve hatalara anlayışla yaklaşma tutumudur.",
    "stage": "Cümlede düşün",
    "id": "k066",
    "example": "Arkadaşının hatasına hoşgörüyle yaklaştı."
  },
  {
    "target": "Gökkuşağı",
    "context": "Yağmurdan sonra gökkuşağı gördük.",
    "answer": "somut",
    "explanation": "Gökkuşağı görme duyusuyla algılanır; elle tutulması gerekmez.",
    "stage": "Cümlede düşün",
    "id": "k067",
    "example": "Yağmurdan sonra gökkuşağı gördük."
  },
  {
    "target": "Gülümseme",
    "context": "Yüzünde bir gülümseme belirdi.",
    "answer": "somut",
    "explanation": "Gülümseme görülebilen bir yüz hareketidir; mutluluk duygusunun kendisinden farklıdır.",
    "stage": "Cümlede düşün",
    "id": "k068",
    "example": "Yüzünde bir gülümseme belirdi."
  },
  {
    "target": "Sabır",
    "context": "Fidanın büyümesini sabırla bekledi.",
    "answer": "soyut",
    "explanation": "Sabır, beklemeye dayanabilme özelliğidir; fidanın kendisi ise somuttur.",
    "stage": "Cümlede düşün",
    "id": "k069",
    "example": "Fidanın büyümesini sabırla bekledi."
  },
  {
    "target": "Hayal kırıklığı",
    "context": "Maç ertelenince hayal kırıklığı yaşadım.",
    "answer": "soyut",
    "explanation": "Hayal kırıklığı bir duygudur; görülen veya dokunulan bir nesne değildir.",
    "stage": "Cümlede düşün",
    "id": "k070",
    "example": "Maç ertelenince hayal kırıklığı yaşadım."
  },
  {
    "target": "Azim",
    "context": "Zor soruyu çözmek için azim gösterdi.",
    "answer": "soyut",
    "explanation": "Azim, bir işi başarma konusunda güçlü ve sürekli isteği anlatır.",
    "stage": "Cümlede düşün",
    "id": "k071",
    "example": "Zor soruyu çözmek için azim gösterdi."
  },
  {
    "target": "Dostluk",
    "context": "Aralarındaki dostluk yıllarca sürdü.",
    "answer": "soyut",
    "explanation": "Dostluk, insanlar arasındaki bir bağdır.",
    "stage": "Cümlede düşün",
    "id": "k072",
    "example": "Aralarındaki dostluk yıllarca sürdü."
  },
  {
    "target": "Soğukluk",
    "context": "Buzun soğukluğunu avucumda hissettim.",
    "answer": "somut",
    "explanation": "Burada fiziksel soğukluk anlatılır; dokunma duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k073",
    "example": "Buzun soğukluğunu avucumda hissettim."
  },
  {
    "target": "Çilek kokusu",
    "context": "Sepetten çilek kokusu geliyordu.",
    "answer": "somut",
    "explanation": "Çilek kokusu koklama duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k074",
    "example": "Sepetten çilek kokusu geliyordu."
  },
  {
    "target": "Adalet",
    "context": "Hakem kararlarında adaleti gözetti.",
    "answer": "soyut",
    "explanation": "Adalet, haklı ve eşit davranmayla ilgili bir kavramdır.",
    "stage": "Cümlede düşün",
    "id": "k075",
    "example": "Hakem kararlarında adaleti gözetti."
  },
  {
    "target": "Limonun tadı",
    "context": "Limonun tadı dilimi burktu.",
    "answer": "somut",
    "explanation": "Buradaki tat, tatma duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k076",
    "example": "Limonun tadı dilimi burktu."
  },
  {
    "target": "Sıcaklık",
    "context": "Bardağın sıcaklığını elimde hissettim.",
    "answer": "somut",
    "explanation": "Burada fiziksel sıcaklık anlatılır; dokunma duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k077",
    "example": "Bardağın sıcaklığını elimde hissettim."
  },
  {
    "target": "Düşünce",
    "context": "Aklıma yeni bir düşünce geldi.",
    "answer": "soyut",
    "explanation": "Düşünce zihinde oluşur; bir nesne gibi algılanmaz.",
    "stage": "Cümlede düşün",
    "id": "k078",
    "example": "Aklıma yeni bir düşünce geldi."
  },
  {
    "target": "Sorumluluk",
    "context": "Bitkileri sulama sorumluluğunu üstlendim.",
    "answer": "soyut",
    "explanation": "Sorumluluk bir yükümlülüktür; bitki ve su gibi somut bir varlık değildir.",
    "stage": "Cümlede düşün",
    "id": "k079",
    "example": "Bitkileri sulama sorumluluğunu üstlendim."
  },
  {
    "target": "Minnettarlık",
    "context": "Yardımı için minnettarlık duydum.",
    "answer": "soyut",
    "explanation": "Minnettarlık, yapılan iyilik karşısında duyulan bir duygudur.",
    "stage": "Cümlede düşün",
    "id": "k080",
    "example": "Yardımı için minnettarlık duydum."
  },
  {
    "target": "Tuzun tadı",
    "context": "Çorbadaki tuzun tadını aldım.",
    "answer": "somut",
    "explanation": "Tuzun tadı tatma duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k081",
    "example": "Çorbadaki tuzun tadını aldım."
  },
  {
    "target": "Işık",
    "context": "Lambanın ışığı odayı aydınlattı.",
    "answer": "somut",
    "explanation": "Lambanın ışığı görme duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k082",
    "example": "Lambanın ışığı odayı aydınlattı."
  },
  {
    "target": "Özlem",
    "context": "Dedeme duyduğum özlem büyüdü.",
    "answer": "soyut",
    "explanation": "Burada anlatılan özlem bir duygudur.",
    "stage": "Cümlede düşün",
    "id": "k083",
    "example": "Dedeme duyduğum özlem büyüdü."
  },
  {
    "target": "Sevinç",
    "context": "Haberi duyunca büyük bir sevinç yaşadım.",
    "answer": "soyut",
    "explanation": "Sevinç, zihnimizde yaşadığımız bir duygudur.",
    "stage": "Cümlede düşün",
    "id": "k084",
    "example": "Haberi duyunca büyük bir sevinç yaşadım."
  },
  {
    "target": "Parfüm kokusu",
    "context": "Odaya parfüm kokusu yayıldı.",
    "answer": "somut",
    "explanation": "Parfüm kokusu koklama duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k085",
    "example": "Odaya parfüm kokusu yayıldı."
  },
  {
    "target": "Müzik",
    "context": "Radyodan gelen müziği dinledim.",
    "answer": "somut",
    "explanation": "Burada işitilen sesler anlatılır; müzik işitme duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k086",
    "example": "Radyodan gelen müziği dinledim."
  },
  {
    "target": "Pürüzlü yüzey",
    "context": "Taşın pürüzlü yüzeyine dokundum.",
    "answer": "somut",
    "explanation": "Yüzeyin pürüzünü dokunma duyusuyla algılarız.",
    "stage": "Cümlede düşün",
    "id": "k087",
    "example": "Taşın pürüzlü yüzeyine dokundum."
  },
  {
    "target": "Sıcaklık",
    "context": "Bizi karşılayan ailenin sıcaklığı içimi rahatlattı.",
    "answer": "soyut",
    "explanation": "Burada sıcaklık, fiziksel ısı değil, içtenlik ve yakınlık anlamındadır.",
    "stage": "Cümlede düşün",
    "id": "k088",
    "example": "Bizi karşılayan ailenin sıcaklığı içimi rahatlattı."
  },
  {
    "target": "Rüzgâr",
    "context": "Rüzgâr yüzüme vurdu.",
    "answer": "somut",
    "explanation": "Rüzgârı tenimizde hissederiz; dokunma duyusuyla algılanabilir.",
    "stage": "Cümlede düşün",
    "id": "k089",
    "example": "Rüzgâr yüzüme vurdu."
  },
  {
    "target": "Gök gürültüsü",
    "context": "Gök gürültüsü bizi uyandırdı.",
    "answer": "somut",
    "explanation": "Gök gürültüsünü işitebiliriz.",
    "stage": "Cümlede düşün",
    "id": "k090",
    "example": "Gök gürültüsü bizi uyandırdı."
  },
  {
    "target": "Alkış sesi",
    "context": "Salondaki alkış sesini duydum.",
    "answer": "somut",
    "explanation": "Alkış sesi işitme duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k091",
    "example": "Salondaki alkış sesini duydum."
  },
  {
    "target": "Gölge",
    "context": "Ağacın gölgesi yere düştü.",
    "answer": "somut",
    "explanation": "Gölgeyi görebiliriz; tutamamak bir şeyi soyut yapmaz.",
    "stage": "Cümlede düşün",
    "id": "k092",
    "example": "Ağacın gölgesi yere düştü."
  },
  {
    "target": "Eşitlik",
    "context": "Oyunda herkes için eşitlik istedik.",
    "answer": "soyut",
    "explanation": "Eşitlik, aynı haklara veya koşullara sahip olmayla ilgili bir kavramdır.",
    "stage": "Cümlede düşün",
    "id": "k093",
    "example": "Oyunda herkes için eşitlik istedik."
  },
  {
    "target": "Vicdan",
    "context": "Vicdanı, yaptığı hatayı düzeltmesini söyledi.",
    "answer": "soyut",
    "explanation": "Vicdan, doğru ve yanlışı değerlendirmeyle ilgili bir kavramdır.",
    "stage": "Cümlede düşün",
    "id": "k094",
    "example": "Vicdanı, yaptığı hatayı düzeltmesini söyledi."
  },
  {
    "target": "Dayanışma",
    "context": "Takım arkadaşlarım arasında dayanışma vardı.",
    "answer": "soyut",
    "explanation": "Dayanışma, birbirini desteklemeyle ilgili bir kavramdır.",
    "stage": "Cümlede düşün",
    "id": "k095",
    "example": "Takım arkadaşlarım arasında dayanışma vardı."
  },
  {
    "target": "Soğukluk",
    "context": "İki arkadaşın arasındaki soğukluk sona erdi.",
    "answer": "soyut",
    "explanation": "Burada soğukluk, düşük sıcaklık değil, ilişkideki uzaklık anlamındadır.",
    "stage": "Cümlede düşün",
    "id": "k096",
    "example": "İki arkadaşın arasındaki soğukluk sona erdi."
  },
  {
    "target": "Kuş cıvıltısı",
    "context": "Sabah kuş cıvıltısıyla uyandım.",
    "answer": "somut",
    "explanation": "Cıvıltı bir sestir; işitme duyusuyla algılanır.",
    "stage": "Cümlede düşün",
    "id": "k097",
    "example": "Sabah kuş cıvıltısıyla uyandım."
  },
  {
    "target": "Umut",
    "context": "Yarın için umudumu korudum.",
    "answer": "soyut",
    "explanation": "Umut, geleceğe ilişkin olumlu bir beklentidir.",
    "stage": "Cümlede düşün",
    "id": "k098",
    "example": "Yarın için umudumu korudum."
  },
  {
    "target": "Özgüven",
    "context": "Sunum yaptıkça özgüvenim arttı.",
    "answer": "soyut",
    "explanation": "Özgüven, kişinin kendisine duyduğu güvendir.",
    "stage": "Cümlede düşün",
    "id": "k099",
    "example": "Sunum yaptıkça özgüvenim arttı."
  },
  {
    "target": "Duman",
    "context": "Bacadan duman yükseldi.",
    "answer": "somut",
    "explanation": "Dumanı görebiliriz; kokusunu da algılayabiliriz.",
    "stage": "Cümlede düşün",
    "id": "k100",
    "example": "Bacadan duman yükseldi."
  }
]
JSON, true, 512, JSON_THROW_ON_ERROR);
}
function concept_rank(int $score): array {
    foreach([[900,'platinum','Platin'],[700,'gold','Altın'],[400,'silver','Gümüş'],[0,'copper','Bakır']] as [$min,$id,$name])
        if($score>=$min)return ['id'=>$id,'name'=>$name,'minimum'=>$min];
    return ['id'=>'copper','name'=>'Bakır','minimum'=>0];
}
function concept_study_snapshot(array $actor): ?array {
    if(($actor['memberType']??'')!=='child')return null;
    $answers=$actor['conceptStudyV1']??[];$cards=concept_cards();$correct=0;$failedIndex=null;
    foreach($cards as $index=>&$card){
        $saved=$answers[$card['id']]??null;
        if($saved){$card['response']=$saved['choice'];$card['correct']=$saved['choice']===$card['answer'];if($card['correct'])$correct++;else $failedIndex=$index;}
        else unset($card['answer'],$card['explanation'],$card['example']);
    }unset($card);
    $score=$correct*10;$meta=$actor['conceptStudyMeta']??[];$best=max($score,$meta['best']??0);
    return ['version'=>1,'round'=>$meta['round']??1,'cards'=>$cards,'completed'=>count($answers),'correct'=>$correct,'total'=>count($cards),
        'score'=>$score,'rank'=>concept_rank($score),'best'=>$best,'bestRank'=>concept_rank($best),'last'=>$meta['last']??null,
        'ended'=>$failedIndex!==null||count($answers)===count($cards),'failedIndex'=>$failedIndex];
}
function concept_actor_index(array $s,array $actor,array $in): int {
    $i=active_user_index($s,$actor['id']);
    if(($s['users'][$i]['memberType']??'')!=='child')throw new AppError('Çalışma kartları büyük çocuk hesabına açık.',403);
    if(isset($in['userId'])&&$in['userId']!==$actor['id'])throw new AppError('Yalnızca kendi kartını cevaplayabilirsin.',403);
    return $i;
}
function answer_concept_card(array &$s,array $actor,array $in): void {
    $i=concept_actor_index($s,$actor,$in);$user=$s['users'][$i];$view=concept_study_snapshot($user);
    // Missing round is accepted only on the original round for older cached clients.
    if(($in['round']??1)!==$view['round'])throw new AppError('Yarışma yenilendi. Güncel karttan devam et.',409);
    $id=$in['cardId']??null;$choice=$in['choice']??null;
    if(!is_string($id)||!in_array($choice,['somut','soyut'],true))throw new AppError('Somut veya soyut seç.');
    $cards=concept_cards();$answers=$user['conceptStudyV1']??[];
    if(isset($answers[$id]))return;
    if($view['ended'])throw new AppError('Bu tur bitti. Yeni yarışmayla başa dönebilirsin.',409);
    if(($cards[count($answers)]['id']??null)!==$id)throw new AppError('Kaldığın kart değişti. Güncel karttan devam et.',409);
    $answers[$id]=['choice'=>$choice,'answeredAt'=>now_tr()->format(DATE_ATOM)];
    $s['users'][$i]['conceptStudyV1']=$answers;
}
function restart_concept_study(array &$s,array $actor,array $in): void {
    $i=concept_actor_index($s,$actor,$in);$view=concept_study_snapshot($s['users'][$i]);$round=$in['round']??null;
    if(!is_int($round))throw new AppError('Yarışma numarası gerekli.');
    // A retried reset must never erase answers in the new round.
    if($round===$view['round']-1)return;
    if($round!==$view['round']||!$view['ended'])throw new AppError('Yarışma henüz bitmedi veya yenilendi.',409);
    $s['users'][$i]['conceptStudyMeta']=['round'=>$round+1,'best'=>$view['best'],
        'last'=>['score'=>$view['score'],'rank'=>$view['rank'],'completed'=>$view['completed'],'finishedAt'=>now_tr()->format(DATE_ATOM)]];
    $s['users'][$i]['conceptStudyV1']=[];
}
