# Ustoz paneli — to'liq ko'rib chiqish (2026-10-20)

Rol: ustoz (`teacher@test.uz`). Muhit: `http://127.0.0.1:8000`, baza `edulife_shanba`.
Usul: haqiqiy brauzer (Playwright, Chromium) — 1440 px va 375 px, konsol xatolari, HTTP ≥ 400 javoblar,
gorizontal siljish (overflow) tekshiruvi va `storage/logs/laravel.log`.

## Qamrov

Ko'rib chiqilgan sahifa va oqimlar (har biri 1440 va 375 px da ochildi, 27 ta sahifa × 2):

- Bosh sahifa (dashboard), «Kurslarim» — qidiruv, holat bo'yicha tablar.
- Kurs yaratish (bo'sh forma xatolari, barcha maydonlar, chegirmali narx, «Kurs haqida» ro'yxatlari),
  tahrirlash, rasm va tanishtiruv videosi yuklash, nusxalash / o'chirish bo'limi.
- Darslar rejasi: modul qo'shish (Enter bilan), tezkor dars qo'shish (video / matn / test), bir nechta dars
  qo'shish (Enter — keyingi qator), menyu orqali tartiblash.
- Dars muharriri: matn (Markdown + LaTeX), video yuklash, video bo'limlari (vaqt kiritish), dars fayllari (PDF).
- Testlar: yaratish (dars / modul / yakuniy), sozlamalar, faollashtirish (savol yetishmasa rad etiladi),
  natijalar sahifasi.
- Savollar: 6 turdagi savolning barchasi yaratildi (bitta / ko'p to'g'ri, rost-yolg'on, bo'sh joy,
  moslashtirish, tartiblash), tahrirlash, o'chirish, tartiblash.
- E'lonlar, savol-javob (javob Ctrl+Enter bilan), sharhlarga javob, o'quvchilar ro'yxati (qidiruv, filtr)
  va o'quvchi sahifasi, statistika (CSV eksport), profil (avatar, ijtimoiy tarmoqlar), bildirishnomalar.

Natija: barcha sahifalar 200, konsolda xato yo'q, ko'rib chiqish davomida `laravel.log` da yangi 500 yo'q.

## Topilgan xatolar

| ID | Jiddiylik | Qisqacha | Qanday takrorlanadi | Tuzatish |
|----|-----------|----------|---------------------|----------|
| TR-01 | Yuqori (xavfsizlik) | Dars/modul testi boshqa kursning darsiga bog'lanishi yoki umuman bog'lanmasligi mumkin edi | «Yangi test» → «Dars testi» → modul/dars tanlamasdan «Yaratish» — test yaratiladi, lekin hech qayerda ko'rinmaydi. So'rovda boshqa ustozning `lesson_id` sini yuborsa, test o'sha darsga tushadi (o'quvchi darsida test faqat `testable_id` bo'yicha qidiriladi) | `d950a99` — `StoreTestRequest`: dars testi uchun modul+dars, modul testi uchun modul majburiy va faqat shu kursdan |
| TR-02 | Yuqori | Savolni tahrirlash variantlarni o'chirib qayta yaratardi — variant ID lari o'zgarardi | Savolga o'quvchi javob bergan (yoki test yechayotgan) paytda ustoz xatoni tuzatadi → o'quvchining tanlagan varianti endi mos kelmaydi, javob noto'g'ri baholanadi, eski natija ko'rinishida tanlov yo'qoladi | `d950a99` — variantlar ID bo'yicha joyida yangilanadi, faqat olib tashlanganlari o'chadi |
| TR-03 | O'rta | Test natijalari: «Savollar bo'yicha» foizi faqat javob berilganlarga bo'linardi | 5 urinish (bittasi 0%, bittasi 73%) bo'lgan testda hamma savollar 100% ko'rinardi — javobsiz qoldirilgan savollar hisobga olinmagan | `d950a99` — javobsiz = xato; faqat topshirilgan urinishlar; «N marta javobsiz qoldirilgan» ko'rsatiladi |
| TR-04 | Past | Sharhga javob oynasida sharh matni o'rniga «—» | «Sharhlar» → «Tahrirlash» — oynada o'quvchi yozgani ko'rinmaydi (`content` maydoni yuborilmaydi) | `d950a99` — `review_text` |
| TR-05 | Past | Video yuklangach «Video bo'limlari» havolasi sahifani yangilamaguncha chiqmasdi | Video darsga video yuklash → «Dars resurslari» da bo'limlar havolasi yo'q | `d9711be` — ishlov tugagach dars qayta yuklanadi |
| TR-06 | Past (mobil) | 375 px da o'quvchi sahifasi va test natijalari jadvali 2–7 px ga gorizontal siljirdi | `/teacher/students/{id}` va test natijalari 375 px da | `d9711be` — grid ustunlari `grid-cols-1` / `min-w-0` |
| TR-07 | Past (matn) | Nashr qilingan kursda «Kurs tekshiruvga yuborishga tayyor» deyilardi; «Preview video yangilandi» (inglizcha) | Nashr qilingan kursni ochish; tanishtiruv videosini yuklash | `d950a99`, `d9711be` — «Kurs nashr qilingan…», «Tanishtiruv videosi yangilandi» |
| TR-08 | O'rta (UX) | Ijtimoiy tarmoq havolasi faqat to'liq `https://…` bilan qabul qilinardi | Telegram maydoniga `t.me/ustoz` yoki `@ustoz` yozish → xato | `d950a99` — `@handle`, `t.me/x`, `example.uz` avtomatik to'liq havolaga aylanadi |
| TR-09 | O'rta | O'quvchi testida LaTeX formulalar xom matn (`$\sin x$`) bo'lib chiqardi | Savol matniga `$…$` yozish → o'quvchi sahifasida formula chizilmaydi | `0048c3e` — savol, variantlar, izoh `MathText` (KaTeX) orqali. **Ustoz hududidan tashqari minimal o'zgarish** (Student/Tests) |
| TR-10 | O'rta | Bo'sh joy savolida o'quvchi `9,8` yozsa, ustozning `9.8` javobiga mos kelmasdi; faqat bitta to'g'ri javob kiritish mumkin edi | Javob `0.5`, o'quvchi `0,5` → xato | `d950a99` (`TestEvaluationService::normalizeFillBlank` — vergul/nuqta va bo'sh joylar farqlanmaydi, **umumiy servisdagi minimal o'zgarish**) + `d9711be` (bir nechta qabul qilinadigan javob) |

Regression testlar: `tests/Feature/Teacher/TeacherReviewFixesTest.php` (10 ta test),
`resources/js/Utils/__tests__/mathText.test.js`, `resources/js/Components/Teacher/__tests__/markdownTools.test.js`,
`resources/js/Components/Teacher/__tests__/format.test.js`.

## Qilingan UX yaxshilanishlar (`d9711be`)

Kundalik ishlaydigan matematika/fizika ustozi nuqtai nazaridan:

1. **Savol qiyinligi** — «Oson / O'rta / Qiyin» tanlagichi (bellashuvlar savolni shu daraja bo'yicha tanlaydi).
   Ro'yxatda har savolda belgi va testda darajalar soni (`Oson 3 · O'rta 6 · Qiyin 2`).
2. **Formulalarni oldindan ko'rish** — savol muharririda «Ko'rinishi» kartasi: savol, variantlar (to'g'risi
   belgilangan) va izoh o'quvchi ko'radigan holda, KaTeX bilan. Savollar ro'yxati va natijalar sahifasida ham
   formulalar chiziladi.
3. **Tez savol kiritish** — «Saqlash va yana qo'shish» (turi va qiyinligi saqlanib qoladi), `Ctrl+S` / `Ctrl+Enter`
   — saqlash, `Ctrl+Shift+Enter` — saqlab, keyingisini yozish. Mobil versiyada pastki panel bitta qatorga sig'adi.
4. **Savol nusxasi** — ro'yxat menyusida va tahrirlash sahifasida «Nusxa olish»: nusxa asl savoldan keyin turadi
   va darhol tahrirga ochiladi («xuddi shunday, boshqa sonlar bilan»).
5. **Bo'sh joy savoli** — bir nechta qabul qilinadigan yozilish (`0.5`, `1/2`), tushuntirish matni bilan.
6. **Dars matni muharriri** — asboblar paneli (sarlavha, qalin, ro'yxat, raqamli ro'yxat, formula, alohida qatordagi
   formula), `Ctrl+B`, `Ctrl+M`, `Ctrl+Shift+M`, «Yozish / Ko'rinishi» — ko'rinish o'quvchi dars sahifasi bilan bir xil
   (Markdown + KaTeX). `Ctrl+S` darsni saqlaydi.
7. **Materiali yo'q darslar** — darslar rejasida «Video yuklanmagan / Matn yozilmagan / Faol test yo'q» belgisi;
   kurs sahifasidagi «Nashrga tayyorlik» kartasida shu darslar ro'yxati (havola bilan) — tekshiruvga yuborishdan oldin.

## Tavsiyalar (qilinmagan)

- **Savol rasmi.** `questions.question_image` ustuni bor, lekin na ustoz formasi, na o'quvchi test sahifasi uni
  ishlatmaydi. Fizikada chizma muhim — yuklash va ko'rsatishni birga qo'shish kerak (o'quvchi tomoni ham).
- **Test parametrlari qat'iy.** Dars testi = 10 savol / 30 daqiqa / 86%, modul testi = 20 / 45 / 90%. Kichik
  «5 savollik tezkor test» qilib bo'lmaydi va testni boshqa darsga ko'chirib bo'lmaydi (tahrirda bog'lanish
  o'zgarmaydi). Mahsulot qarori kerak.
- **Bellashuv savollari LaTeX.** Bellashuv sahifasi (`Student/Battle/Match.vue`) savolni oddiy matn qilib
  ko'rsatadi — bellashuv agenti hududi; `MathText` komponentidan foydalanish mumkin.
- **Savol banki / import.** Savollarni boshqa testdan nusxalash yoki matndan (masalan, Word / Excel) ommaviy
  import qilish yo'q — ko'p savolli testlar uchun eng katta vaqt tejovchi imkoniyat bo'lardi.
- **Video ko'rish.** Yuklangan videoni ustoz muharrirda ko'ra olmaydi (faqat «Video tayyor» yozuvi), video
  bo'limlari vaqtini ham videoga qarab emas, qo'lda kiritadi.
- **Tezkor dars qo'shish.** Dars yaratilgach darslar rejasiga qaytadi; video/matnli dars uchun «qo'shish va
  ochish» varianti qadamni qisqartiradi.
- **Ko'rish vaqti.** O'quvchi sahifasida va statistikada «Ko'rish vaqti 0» — video tomosha vaqti agentining
  hududi (ma'lumot yozilmayapti).
- **Testlar sahifasi (o'quvchi) — LaTeX tanlash.** Moslashtirish savolida o'ng tomondagi variantlar `<select>`
  ichida — u yerda formula chizilmaydi (oddiy matn). Kerak bo'lsa, maxsus ochiladigan ro'yxat kerak.

## Build haqida eslatma

Ko'rib chiqish vaqtida `npm run build` boshqa agentning (laboratoriya) commit qilinmagan
`resources/js/Components/Lab/Simulations/NewtonSecondSimulation.vue` faylidagi sintaksis xatosi tufayli
yiqilardi (241-qator, `'0 (qo'zg'almadi)'` — qo'shtirnoq ichida apostrof). Fayl o'zgartirilmadi; brauzerda
tekshirish uchun build shu faylning oxirgi commit qilingan versiyasi bilan (vaqtinchalik vite konfiguratsiyasi,
fayl tegilmagan) yig'ildi.

## Test ma'lumotlari

Ko'rib chiqish uchun «Ustoz QA: Trigonometriya» qoralama kursi yaratildi (2 modul, 6 dars, e'lonlar, fayl,
video bo'limlari) — «Materiali yo'q darslar» belgilarini ko'rsatadi. Vaqtinchalik bog'lanmagan test o'chirildi.
