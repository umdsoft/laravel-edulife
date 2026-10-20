# Loyiha: masalalarda rasm va grafiklar, bankdan qiyinlik bo'yicha olish, Telegram WebView

Sana: 2026-10-20. Uchta ish parallel bajariladi (agentlar M, Q, T). Bu hujjat — ular uchun umumiy qarorlar.

## 0. Hozirgi holat (tekshirildi)

- Savollar ikki joyda saqlanadi:
  - **kurs testlari** — `questions` / `question_options` (qiyinlik `easy|medium|hard`). Battle savollari shu yerdan olinadi.
  - **imtihon banki** — `question_bank` / `question_bank_options` (qiyinlik `difficulty_level` 1–5).
  - Olimpiada savollarining o'z jadvali bor (`olympiad_questions`).
- Matn formati hamma joyda bitta: Markdown + KaTeX (`$...$`, `$$...$$`). Ikkita render qiluvchi bor:
  - `Utils/richText.js` + `Components/Student/RichContent.vue` — blok matn (dars, imtihon, natija);
  - `Components/MathText.vue` — qisqa matn (kurs testi variantlari va boshqalar).
- Rasm uchun eski ustunlar bor (`questions.question_image`, `question_options.option_image`, `question_bank_options.image`, `question_bank.question_media`), lekin ularni yuklash interfeysi yo'q, hamma joyda ko'rsatilmaydi ham.
- **Battle'dagi `Match.vue` formulalarni umuman render qilmaydi** — savol oddiy matn bo'lib chiqadi. Bu xato.
- Battle'da qiyinlik tanlash allaqachon bor (bir jangda — bitta daraja). Imtihon blueprint'ida "mavzu × qiyinlik × soni" qoidasi bor.
- Telegram Mini App: `initData` imzosi tekshiriladi, foydalanuvchi tizimga kiradi va `student.exam.index` ga yuboriladi. Lekin sahifalar oddiy sayt ko'rinishida, Telegram'ga moslashmagan. Battle Mini App'dan ochilmaydi.
- Xavfsizlik sarlavhalari (`X-Frame-Options`, CSP) o'rnatilmagan, shuning uchun Telegram Web'ning iframe'i sahifani bloklamaydi.

## 1. Rasm va grafiklar (agent M)

### 1.1. Asosiy qaror: yangi ustun emas, matn ichida

Rasm ham, grafik ham **savol matnining o'zida** saqlanadi — shart, variant, yechim, izoh, dars matnida bir xil format:

- **Rasm:** `![chizma](/storage/questions/2026/10/<uuid>.webp)`. Ixtiyoriy o'lcham: `![chizma](...){width=60%}`.
- **Grafik:** maxsus blok:

  ````
  ```graph
  {"x":[-5,5],"y":[-3,9],"grid":true,
   "functions":[{"expr":"x^2-2","label":"f(x)"}],
   "points":[{"x":2,"y":2,"label":"A"}],
   "axes":{"x":"t, s","y":"v, m/s"}}
  ```
  ````

Nega shunday:
- Bitta format hamma joyda ishlaydi: imtihon banki, kurs testi, battle, olimpiada, dars, import, AI qoralama, Telegram.
- Variantlarga ham rasm va grafik qo'yiladi (masalan, "qaysi grafik y = x² ga mos?").
- Yangi jadval yoki ustun kerak emas. Eski rasm ustunlari to'ldirilgan bo'lsa, ular ham ko'rsatiladi (orqaga moslik).

### 1.2. Grafik bloki (spec v1)

Grafik bizning kichik SVG chizgichimizda chiziladi. Tashqi kutubxona yo'q va `eval` ishlatilmaydi. Shu tufayli grafik yengil yuklanadi, tungi rejimda ham to'g'ri ko'rinadi va Telegram'da ham ishlaydi.

| Kalit | Ma'nosi |
|---|---|
| `x`, `y` | o'qlar oralig'i `[min, max]` |
| `grid`, `ticks` | to'r va bo'linmalar qadami |
| `axes` | o'q nomlari (fizika: kattalik va birlik) |
| `functions[]` | `expr` (x ga bog'liq ifoda: `+ - * / ^`, `sqrt`, `abs`, `sin` … `ln`, `log`, `exp`, `pi`, `e`), `domain`, `dashed`, `label` |
| `polylines[]` | nuqtalar ketma-ketligi: v–t, x–t va bo'lakli grafiklar |
| `points[]`, `segments[]`, `vectors[]`, `polygons[]`, `circles[]`, `labels[]`, `angles[]` | geometriya va kuchlar chizmasi |
| `width` | blok kengligi (foizda) |

- Murakkab chizmalar (elektr zanjiri, optik sxema, stereometriya) — ustoz GeoGebra yoki boshqa dasturda chizib, **rasm** sifatida yuklaydi.
- Spec server tomonda ham tekshiriladi (`GraphSpec` validatori): noto'g'ri JSON yoki ruxsat etilmagan funksiya bo'lsa, saqlashda xato chiqadi.

### 1.3. Yuklash va xavfsizlik

- Yuklash endpoint'i faqat ustoz va admin uchun. Formatlar: png, jpg, webp, gif. **SVG qabul qilinmaydi** (XSS xavfi).
- Hajmi 5 MB gacha. Server GD bilan 1600 px gacha kichraytirib, WebP ga o'giradi va `public` diskka `questions/YYYY/MM/<uuid>.webp` qilib saqlaydi.
- Matnda faqat **o'z yuklangan rasmlarimiz** ruxsat etiladi (`/storage/questions/...`). Tashqi havola render qilinmaydi. Sabablari: kuzatuvchi rasmlar, aralash kontent va Telegram'da yuklanmaslik.
- Bog'lanmagan rasmlar: 7 kundan keyin hech qaysi matnda uchramasa, rejali buyruq ularni o'chiradi.

### 1.4. Muharrir

`MarkdownEditor` asboblar paneliga ikkita tugma qo'shiladi:
- **"Rasm"** — yuklash, buferdan joylash (Ctrl+V) yoki sudrab tashlash;
- **"Grafik"** — konstruktor oynasi: oraliq, funksiyalar, nuqtalar, kesmalar; tirik ko'rinish bilan; natijada `graph` blokini qo'yadi.

Bu muharrir ishlatilgan hamma joyda tugmalar avtomatik paydo bo'ladi: imtihon savoli, tekshiruv navbatidagi tahrir, kurs testi savoli, dars, olimpiada savoli.

### 1.5. Ko'rsatish

- Bitta renderer hamma joyda ishlaydi: imtihon (Take, Result), kurs testi, **battle (Match — tuzatiladi)**, olimpiada, ustoz ko'rinishi, ota-ona hisoboti.
- Variantlarda rasm yoki grafik bo'lsa, ular 2 ustunli kartalar bo'lib chiqadi.
- Rasm ustiga bosilsa, kattalashadi (telefonda muhim).
- Telegram bot xabarlarida rasm va grafik o'rniga "[rasm]" / "[grafik]" yozuvi chiqadi.

### 1.6. Import va AI

- Import: JSON/CSV ichida yuklangan rasm havolasi yoki `graph` bloki bo'lishi mumkin. Yangi format — **ZIP** (`questions.json` + `images/`): ZIP ichidagi rasmlar avtomatik yuklanadi va havolalar almashtiriladi.
- AI qoralama: promptga `graph` spec qo'shiladi, AI grafikni JSON ko'rinishida yozadi va u server validatoridan o'tadi. AI rasm yarata olmaydi.
- Takroriy savolni aniqlashda rasm va grafik normalizatsiyadan chiqariladi.

## 2. Bankdan savol olishda qiyinlik (agent Q)

Talab: "misollar bazasini olayotganda qiyinlik darajasi tanlanishi lozim". Ikki tomonlama qo'llanadi.

### 2.1. Bankka savol tushganda — qiyinlik majburiy

Hech qayerda jim "o'rta" default'i qolmaydi:
- **Qo'lda kiritish** (imtihon banki, kurs testi, olimpiada): qiyinlik maydoni majburiy, oldindan tanlanmagan.
- **Import:** faylda qiyinlik ustuni bo'lmasa yoki noto'g'ri bo'lsa, preview'da har qatorga tanlagich chiqadi va "hammasiga qo'llash" tugmasi bor. Qiyinliksiz qatorni tasdiqlab bo'lmaydi.
- **AI qoralama:** qiyinlik ishga tushirish formasida tanlanadi (bor) va har bir qoralamaga yoziladi.

### 2.2. Bankdan savol olinganda — qiyinlik tanlanadi

- **Kurs testiga "Bankdan qo'shish":** fan, mavzu va **qiyinlik** filtri. Taqsimot ham beriladi ("oson 5 · o'rta 10 · qiyin 5"), tizim tasodifiy tanlaydi. Har darajada nechta savol borligi oldindan ko'rinadi.
- **Imtihon blueprint:** qoida qatorida qiyinlik aniq tanlanadi va har daraja uchun bankda nechta tasdiqlangan savol borligi ko'rsatiladi (bor imkoniyat aniqroq ko'rinadigan qilinadi).
- **Battle:** daraja tanlash bor. Faqat daraja nomlari bank bilan bir xil qilinadi.
- **Olimpiada:** savollarni bankdan qiyinlik bo'yicha olish.
- Hamma joy uchun bitta servis `QuestionPicker` (filtr + taqsimot + "ko'rganlarini takrorlamaslik").

### 2.3. Bitta shkala

- Imtihon banki: 1–5. Kurs testlari va battle: 3 daraja. Moslik: **1–2 = oson, 3 = o'rta, 4–5 = qiyin**. Bu bitta joyda (`Difficulty` klassi va JS'dagi juftligi) yoziladi.
- Nomlar hamma joyda bir xil: "1 — juda oson … 5 — juda qiyin".
- Haqiqiy yechilish ulushi ham ko'rsatiladi (battle'dagi p̂ va imtihon statistikasi): "ustoz: qiyin · amalda: 82% yechgan". Tizim faqat **taklif** qiladi, qiyinlikni o'zi o'zgartirmaydi.

## 3. Imtihon va battle Telegram ichida — WebView (agent T)

### 3.1. Kirish nuqtalari

- **Bot menyu tugmasi** ("Ochish") Mini App'ni ochadi. U `telegram:set-webhook` buyrug'i bilan o'rnatiladi; shu buyruq bot buyruqlarini ham o'rnatadi.
- `/imtihon` va `/battle` buyruqlariga javob — `web_app` inline tugmalari.
- Bildirishnomalarda ham shunday tugma: "Imtihonni boshlash", "Natijani ko'rish".
- Deep link: `t.me/<bot>?startapp=<param>` → `/telegram/app` parametrga qarab yo'naltiradi. Parametrlar: `exam`, `exam_s_<sessiya>`, `placement_<fan>`, `result_<urinish>`, `battle`, `battle_<kod>` (do'stga chaqiruv).

### 3.2. WebView rejimi

- Mini App orqali kirilganda sessiyaga `webview=telegram` belgisi yoziladi va Inertia prop sifatida uzatiladi. `StudentLayout` ixcham rejimga o'tadi: yon menyu, header va footer yo'q, pastda faqat "Imtihon / Battle / Natijalar / Profil" tablari qoladi.
- Telegram mavzusi (`themeParams`) bizning CSS tokenlarimizga ulanadi. Yorug' va tungi rejim Telegram'ga ergashadi.
- `expand()` chaqiriladi, `viewportStableHeight` va safe-area hisobga olinadi.
- Telegram `BackButton` — orqaga qaytish. Asosiy amal ("Keyingi", "Topshirish", "Javob berish") ixtiyoriy ravishda `MainButton` ga ulanadi.
- Imtihon yoki jang ketayotganda `enableClosingConfirmation()` va `disableVerticalSwipes()` yoqiladi, shunda tasodifan surib yopilmaydi.
- Javob to'g'ri yoki xato bo'lganda `HapticFeedback` beriladi.
- Tashqi havolalar (yechim videosi) `openLink` orqali ochiladi. Natija kartasi `shareToStory` yoki `openLink` bilan ulashiladi. Battle'ga chaqiruv `switchInlineQuery` yoki deep link orqali yuboriladi.
- Hammasi bitta `useTelegram()` composable'ida jamlanadi. Oddiy brauzerda bu funksiyalar hech narsa qilmaydi, shuning uchun sayt avvalgidek ishlayveradi.

### 3.3. Sessiya va xavfsizlik

- Telegram Web (brauzer ichidagi iframe) uchinchi tomon cookie'sini to'sishi mumkin. Shuning uchun:
  1. HTTPS'da Mini App kirishida cookie `SameSite=None; Secure` bilan beriladi (sozlamada);
  2. cookie yo'qolsa (401/419), frontend `initData` bilan **jim qayta kiradi**.
- `initData` yoshi va imzosi har kirishda tekshiriladi (bor). WebView'da login va parol formasi ko'rsatilmaydi.
- Anti-cheat: `visibilitychange` va Telegram `deactivated` hodisalari "ilovadan chiqdi" deb yoziladi.

### 3.4. Qaysi sahifalar WebView'da

- Imtihon: bosh sahifa, Take, Result, O'sishim, Reyting, anketa.
- Battle: Index (raqib qidirish, qiyinlik tanlash, do'stni chaqirish), Match (polling), History.
- Qolganlari (kurslar, lab, do'kon) WebView'da "Saytda ochish" havolasi bo'lib qoladi.

### 3.5. Sinov

- Haqiqiy Telegram'siz sinash uchun dev sahifa `/telegram/dev` (faqat `APP_ENV=local`). U test bot tokeni bilan imzolangan `initData` yasaydi va soxta `window.Telegram.WebApp` bilan ilovani ochadi.
- Playwright va feature testlar shu sahifa orqali ishlaydi. Haqiqiy Telegram'ga so'rov yuborilmaydi.

## 4. Agentlar chegarasi

| | M (rasm/grafik) | Q (qiyinlik) | T (Telegram WebView) |
|---|---|---|---|
| Asosiy fayllar | `Utils/richText.js`, `RichContent`, `MathText`, `MarkdownEditor`, yangi `Components/Media/*`, `GraphSpec`, upload controller | savol formalaridagi qiyinlik maydoni, import preview, blueprint UI, "Bankdan qo'shish", `QuestionPicker`, `Difficulty` | `useTelegram`, `StudentLayout` (webview rejimi), `Telegram/*` controller va servislar, bot menyusi, `/telegram/dev` |
| Battle `Match.vue` | savol va variant render'i | — | Telegram tugmalari va haptic |
| Exam `Take.vue` | variantlar tartibi (media bo'lsa grid) | — | MainButton va yopishni tasdiqlash |
| Migratsiya prefiksi | `2026_10_24_1xxxxx` | `2026_10_24_2xxxxx` | `2026_10_24_3xxxxx` |
