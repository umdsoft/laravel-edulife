# Integratsiyalar: AI qoralama generatori va Telegram bot

Bosqich 4 (AI qoralamalar) va Bosqich 6 (Telegram bot + Mini App) ni ishga tushirish bo'yicha qo'llanma.
Ikkala integratsiya ham **kalit kiritilmaguncha o'chiq**: hech qanday tashqi so'rov yuborilmaydi, faqat log yoziladi
(shaxsiy ma'lumotsiz). Testlar `Http::fake()` bilan ishlaydi — haqiqiy Anthropic/Telegram'ga murojaat qilinmaydi.

---

## 0. Umumiy talablar (production)

| Nima | Nega | Qanday |
|---|---|---|
| Queue worker | AI so'rovlari va Telegram xabarlari navbatda bajariladi | `php artisan queue:work --tries=1 --timeout=400` (supervisor ostida) |
| Cron | haftalik AI reja, Telegram eslatmalari | `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1` |
| HTTPS domen | Telegram webhook va Mini App faqat HTTPS bilan ishlaydi | `APP_URL=https://...` |

Jadvaldagi vazifalar (`routes/console.php`, «stages 4 and 6» bloki):

| Buyruq | Qachon | Nima qiladi |
|---|---|---|
| `exam:ai-drafts-weekly` | dushanba 03:00 (Toshkent) | haftalik qoralama rejasi: `EXAM_AI_WEEKLY_TARGET` ta savol, byudjet ichida |
| `exam:telegram-reminders` | har 5 daqiqada | shanba imtihoni eslatmalari (oldingi kun kechqurun, 1 soat oldin, boshlanishda) |

---

## 1. AI qoralama generatori (Bosqich 4)

### 1.1. Kalit va sozlamalar

1. <https://console.anthropic.com> → **API Keys** → yangi kalit. Billing'da oylik limit qo'ying.
2. `.env` ga yozing (bo'sh qiymatlar `.env.example` da bor):

```dotenv
ANTHROPIC_API_KEY=sk-ant-...
EXAM_AI_MODEL=claude-opus-5-5        # model nomi faqat shu yerda / config/exam.php da
EXAM_AI_EFFORT=high                  # low | medium | high | xhigh | max
EXAM_AI_STORE_HINT=false             # true: AI taklif qilgan javob ustozga yashirin maslahat sifatida saqlanadi
EXAM_AI_WEEKLY_ENABLED=true
EXAM_AI_WEEKLY_TARGET=100            # haftasiga nechta qoralama (fanlar orasida teng bo'linadi)
EXAM_AI_WEEKLY_BUDGET_USD=15         # shu haftaning taxminiy xarajati shunga yetsa, yangi so'rov boshlanmaydi
```

3. `php artisan config:clear` (config cache ishlatilsa — `config:cache`).

Qolgan sozlamalar `config/exam.php` → `ai` bloki: `max_tokens`, `timeout_seconds`, bir so'rovdagi maksimal savol soni
(`max_count_per_run`, 20), manbadan olinadigan matn hajmi (`max_source_chars`, 40 000 belgi), narxlar jadvali (`pricing`,
1 mln token uchun USD — xarajat **taxminiy**, `response.usage` dan hisoblanadi), server tomonidagi refusal fallback
(`EXAM_AI_FALLBACKS`, standart `default`; bo'sh qiymat o'chiradi).

### 1.2. Manbalar (ustoz materiali)

Ustoz paneli → **AI qoralamalar** → «Manbalar» bo'limi:
- fan, mavzu (yoki butun fan), nomi va manba belgisi (masalan «Rimkevich, 2015»);
- fayl (`.txt`, `.md`, `.tex`, UTF-8, 2 MB gacha) yoki matnni joylash.

Fayl `storage/app/exam-sources/` ga, matni `exam_source_documents.content_text` ga yoziladi. AI masalani **faqat shu
matnlardan** tuzadi (spec 1-bo'lim: masala «noldan» ixtiro qilinmaydi). Uzun qo'llanmadan har so'rovda boshqa bo'lak
olinadi (bo'lak boshi so'rov id'siga bog'liq). PDF va skanlar hozircha qabul qilinmaydi — matnga aylantirib yuklang.

### 1.3. So'rov va natija

- Ustoz (yoki admin) **AI qoralamalar** sahifasida fan, mavzu, qiyinlik (1–5), format va sonini tanlaydi →
  `exam_ai_draft_runs` da yozuv paydo bo'ladi va `GenerateQuestionDraftsJob` navbatga qo'yiladi.
- `QuestionDraftService` Messages API'ga bitta `POST /v1/messages` yuboradi: system prompt + manba parchalari,
  `output_config.format` = qat'iy JSON sxema (`QuestionDraftSchema::jsonSchema()`).
- Javob **serverda qayta tekshiriladi** (har savol alohida): shart, 4–5 variant (A, B, C, D, E tartibda, bir xil emas),
  ruxsat etilgan `topic_code`, `difficulty_level` 1–5, `source_ref`, fizika uchun `units_note` majburiy, ortiqcha maydon yo'q.
  Yaroqsizlari rad etiladi (sababi `validation_errors` da), dublikatlar (normallashtirilgan shart bo'yicha) o'tkazib yuboriladi.
- Yaroqli savollar: `origin = ai_draft`, `QuestionReviewService::submitForReview()` orqali `review_status = in_review`,
  variantlar **`is_correct = NULL`**. To'g'ri javobni ustoz **Tekshiruv navbati**da belgilaydi; tasdiqlanmagan savol
  imtihonga tushmaydi (`QuestionApprovalGuard`).
- `EXAM_AI_STORE_HINT=true` bo'lsa AI o'zi to'g'ri deb hisoblagan variant `question_bank_options.ai_suggested_correct`
  ga yoziladi (faqat maslahat; baholashda ishlatilmaydi).

Har so'rov uchun saqlanadi: model (so'ralgan va javob bergan), prompt nomi va versiyasi, kirish/chiqish/kesh tokenlari,
taxminiy xarajat (USD), yaratilgan / rad etilgan / takror soni, xato matni (shaxsiy ma'lumotsiz).
Admin paneli → **AI qoralamalar**: jurnal, hafta/oy xarajati, tokenlar, haftalik limit.

Holatlar: `queued` → `running` → `succeeded` | `partial` (bir qismi rad etildi) | `failed` | `skipped`
(kalit yo'q, manba yo'q yoki haftalik byudjet tugagan). Muvaffaqiyatsiz so'rov avtomatik qayta yuborilmaydi
(ikki marta to'lamaslik uchun) — kerak bo'lsa yangi so'rov boshlang.

### 1.4. Promptlar

`resources/prompts/exam/question_draft.md` — front matter'da `name` va `version`, keyin `<!-- system -->` va
`<!-- user -->` bo'limlari; `{{placeholder}}` lar kod tomonidan to'ldiriladi. Promptni o'zgartirsangiz **versiyani
oshiring** (masalan `1.0.0` → `1.1.0`): versiya har so'rov bilan saqlanadi, sifat va xarajatni versiyalar bo'yicha
solishtirish mumkin.

---

## 2. Telegram bot va Mini App (Bosqich 6)

### 2.1. BotFather

1. Telegram'da [@BotFather](https://t.me/BotFather) → `/newbot` → nom (masalan «Shanba imtihoni») va username
   (`..._bot` bilan tugaydi). Token beriladi: `123456789:AA...`.
2. `/setdescription`, `/setabouttext`, `/setuserpic` — bot profili (ixtiyoriy).
3. `/setcommands` → quyidagini yuboring:

```
start - Boshlash
natijalar - Oxirgi natijalar
taklif - Do'stni taklif qilish
yordam - Yordam
bekor - Ro'yxatdan o'tishni qaytadan boshlash
```

   (`php artisan telegram:set-webhook` buni o'zi ham qiladi — §2.7.)
4. Mini App: `php artisan telegram:set-webhook` bot menyusidagi tugmani Mini App'ga ulaydi (quyida). Agar
   `t.me/<bot>/<app>` ko'rinishidagi havola kerak bo'lsa — BotFather → `/newapp` → URL sifatida `https://<domen>/telegram/app`.

### 2.2. `.env`

```dotenv
TELEGRAM_BOT_TOKEN=123456789:AA...
TELEGRAM_BOT_USERNAME=shanba_imtihoni_bot
TELEGRAM_WEBHOOK_SECRET=<32+ belgili tasodifiy satr, faqat A-Z a-z 0-9 _ ->
TELEGRAM_MINI_APP_URL=               # bo'sh = {APP_URL}/telegram/app
TELEGRAM_INIT_DATA_TTL=86400         # Mini App initData necha soniya amal qiladi
```

Sirni yaratish: `php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"`.

### 2.3. Webhook va menyu tugmasi

```bash
php artisan config:clear
php artisan telegram:set-webhook            # webhook = {APP_URL}/telegram/webhook, secret_token bilan
php artisan telegram:set-webhook --delete   # o'chirish
php artisan telegram:set-webhook --url=https://xxxx.ngrok-free.app/telegram/webhook   # lokal sinov
```

- Webhook URL: `POST https://<domen>/telegram/webhook`. Telegram har so'rovda `X-Telegram-Bot-Api-Secret-Token`
  sarlavhasini yuboradi; u `TELEGRAM_WEBHOOK_SECRET` ga teng bo'lmasa — 403. Token yoki sir bo'sh bo'lsa — 404.
  IP bo'yicha limit: 600 so'rov/daqiqa, har Telegram foydalanuvchisi uchun 30 xabar/daqiqa.
- Mini App URL: `https://<domen>/telegram/app`. Sahifa `Telegram.WebApp.initData` ni `POST /telegram/app/auth` ga
  yuboradi; server imzoni bot tokeni bilan tekshiradi (HMAC-SHA256, `WebAppData` kaliti), `auth_date` muddatini
  tekshiradi, keyin bog'langan foydalanuvchini tizimga kiritadi va imtihon sahifasiga (`student.exam.index`)
  yo'naltiradi. Ota-ona — `guardian.dashboard` ga.
- Lokal ishlab chiqishda HTTPS uchun ngrok / cloudflared tunnel ishlating va `APP_URL` ni tunnel manziliga qo'ying.

### 2.4. Bot oqimlari

| Oqim | Tafsilot |
|---|---|
| `/start ref_<KOD>` | kod `telegram_accounts.start_payload` ga yoziladi; ro'yxat tugaganda `ReferralService::recordJoin(..., 'telegram')` (Bosqich 7) |
| Ro'yxat (5 savol) | 1) kim: o'quvchi / ota-ona, 2) sinf (9/10/11/bitirgan), 3) fan (matematika / fizika / ikkalasi), 4) maqsad (OTM grant / Milliy sertifikat / SAT), 5) telefon (Telegram «kontakt» tugmasi, faqat o'z raqami, +998) + o'quvchi uchun ota-ona telefoni + rozilik |
| Natija | o'quvchi akkaunti (yoki shu telefondagi mavjud o'quvchi akkaunti) va `StudentExamProfile` (`filled_by`, `parent_phone`, `parent_consent_at`, `parent_consent_source = telegram_parent / telegram_student`, `onboarded_at`), keyin «Qabul testini boshlash» tugmasi (Mini App) |
| Saytdagi akkauntni ulash | tizimga kirgan foydalanuvchi `https://<domen>/telegram/link` ni ochadi → `t.me/<bot>?start=link_<token>` (15 daqiqa, bir martalik). Ota-onalar va saytda ro'yxatdan o'tganlar uchun |
| `/natijalar` | oxirgi 5 ta baholangan urinish |
| `/taklif` | `t.me/<bot>?start=ref_<KOD>` havolasi |
| Eslatmalar | haftalik sessiya uchun: oldingi kun 19:00 dan, 1 soat oldin, boshlanishda (`exam.telegram.reminders`). Faqat shu fanni tanlagan, botni bloklamagan o'quvchilarga, har biri bir marta (`telegram_notifications`) |
| Natija xabari | `ExamAttemptGraded` → o'quvchiga ball + natija sahifasi tugmasi; tasdiqlangan ota-onaga qisqa xabar |
| Yakshanba hisoboti | `WeeklyReportReady` (Bosqich 7) → ota-onaning Telegram'iga (faqat tasdiqlangan bog'lanish, `receives_reports`) |

Botni bloklagan foydalanuvchi (Telegram 403) `is_blocked = true` bo'ladi va unga boshqa xabar yuborilmaydi;
u botga qayta yozsa, belgi olib tashlanadi.

### 2.5. Matnlar

Botning barcha matnlari — `lang/uz/exam_bot.php` (marketing jamoasi tahrirlay oladi). Telegram HTML (`<b>`, `<i>`,
`<a>`) ruxsat etilgan; `:placeholder` qiymatlari kod tomonidan HTML-escape qilinadi. Rus tili kerak bo'lsa —
`lang/ru/exam_bot.php` xuddi shu kalitlar bilan.

### 2.6. Xavfsizlik va shaxsiy ma'lumotlar

- Loglarga faqat id'lar yoziladi (telefon, ism, chat id, xabar matni yozilmaydi); bot tokeni URL ichida bo'lgani uchun
  URL va xato matnlari loglanmaydi.
- Telefon orqali mavjud akkauntga ulash faqat `student` roli uchun; o'qituvchi/admin/ota-ona akkauntlari faqat
  `/telegram/link` orqali (saytga kirgan holda) ulanadi.
- Ota-ona roziligi `exam_student_profiles.parent_consent_at` da; rozilik berilmasa akkaunt yaratilmaydi.

### 2.7. Telegram WebView: imtihon va battle Mini App ichida (to'lqin 2)

Dizayn: `docs/exam/03_RASM_QIYINLIK_TELEGRAM.md` §3. Ishlatilgan Telegram API (manba —
<https://core.telegram.org/bots/webapps>, <https://core.telegram.org/bots/features#deep-linking>,
<https://core.telegram.org/api/links>): `WebApp.ready/expand`, `themeParams` + `themeChanged`,
`viewportStableHeight` + `viewportChanged`, `safeAreaInset` / `contentSafeAreaInset` (Bot API 8.0), `BackButton`,
`MainButton` (`setParams`, 7.10), `enableClosingConfirmation` (6.2), `disableVerticalSwipes` (7.7), `HapticFeedback`,
`openLink` / `openTelegramLink`, `shareToStory` (7.8, faqat mobil), `activated` / `deactivated` (8.0), initData HMAC
tekshiruvi (`secret_key = HMAC_SHA256("WebAppData", bot_token)`), deep link `t.me/<bot>?startapp=<param>`
(parametr: `A-Z a-z 0-9 _ -`, 64 belgigacha).

**BotFather'da bir marta:**

1. `/mybots` → bot → **Bot Settings → Configure Mini App → Enable Mini App**, URL: `https://<domen>/telegram/app`.
   Shundan keyin `https://t.me/<bot>?startapp=<param>` havolalari (do'stga battle chaqiruvi va boshqalar) Mini App'ni
   to'g'ridan-to'g'ri ochadi. Domen ham shu yerda (yoki `/setdomain`) ro'yxatdan o'tadi.
2. `/setcommands` endi shart emas: `php artisan telegram:set-webhook` (yoki faqat `php artisan telegram:setup-menu`)
   buyruqlarni `setMyCommands` bilan o'rnatadi — `/start`, `/imtihon`, `/battle`, `/natijalar`, `/taklif`, `/yordam`,
   `/bekor` (ro'yxat: `config/exam.php` → `telegram.commands`) va menyu tugmasini (`setChatMenuButton`, `web_app`)
   «Imtihonlar» deb Mini App'ga ulaydi.

**HTTPS shart.** Telegram `web_app` tugmalari va Mini App faqat `https://` manzilni ochadi. Lokal sinov uchun tunnel:

```bash
cloudflared tunnel --url http://127.0.0.1:8000        # yoki: ngrok http 8000
# chiqqan https://xxxx.trycloudflare.com manzilini APP_URL ga (yoki TELEGRAM_MINI_APP_URL ga) yozing:
#   TELEGRAM_MINI_APP_URL=https://xxxx.trycloudflare.com/telegram/app
php artisan config:clear
php artisan telegram:set-webhook --url=https://xxxx.trycloudflare.com/telegram/webhook
```

BotFather → Configure Mini App URL ham tunnel manzili bo'lishi kerak (tunnel o'zgarsa — yangilang).

**Kirish nuqtalari**

| Qayerdan | Nima ochiladi |
|---|---|
| Menyu tugmasi «Imtihonlar» | `/telegram/app` → imtihon bosh sahifasi |
| `/imtihon` | boshlangan imtihon bo'lsa — davom ettirish tugmasi, aks holda imtihonlar + topshirilmagan qabul testlari tugmalari |
| `/battle` | battle sahifasi |
| Eslatmalar | `to=exam_s_<sessiya>`: boshlangan urinish bo'lsa — Take, aks holda imtihon bosh sahifasi |
| Natija xabari | `to=result_<urinish>` — natija sahifasi |
| Yakshanba hisoboti (ota-ona) | `to=report_<hisobot>` — hisobot sahifasi |
| `t.me/<bot>?startapp=...` | `exam`, `exam_s_<id>`, `placement_<math\|physics>` (testni darhol boshlaydi), `result_<id>`, `progress`, `leaderboard`, `battle`, `battle_<kod>` (do'st chaqiruvi; 8 belgili tasodifiy kod, battle id emas) |

Noma'lum, noto'g'ri yoki boshqa o'quvchiga tegishli parametr — imtihon bosh sahifasi (`TelegramStartParam`).

**WebView rejimi.** Mini App orqali kirganda sessiyaga `webview=telegram` yoziladi. Faqat shunday sessiyalarga
Inertia `webview` / `telegram` prop'lari va Telegram SDK skripti beriladi — saytning oddiy javoblari o'zgarmaydi
(testlar tekshiradi). `StudentLayout` ixcham ko'rinishga o'tadi (yon menyu va header yo'q; pastda Imtihon / Battle /
Natijalar / Profil; «Saytda ochish» — `openLink` bilan tashqi brauzerda). Ranglar Telegram mavzusidan olinadi,
yorug'/tungi rejim Telegram'ga ergashadi (saytdagi tanlov o'zgarmaydi). Imtihon va jang paytida Telegram yopishni
so'raydi va pastga surib yopish o'chadi; Take'da `MainButton` — «Topshirish», `BackButton` — oldingi savol;
Mini App yig'ilsa (`deactivated`) yoki boshqa ilovaga o'tilsa, bu tab almashtirish kabi yoziladi.

**Cookie va sessiya.** Telegram Web (web.telegram.org) Mini App'ni boshqa saytdagi iframe ichida ochadi; u yerda
`SameSite=Lax` cookie saqlanmaydi. Shuning uchun HTTPS'da Mini App sessiyasining cookie'lari `SameSite=None; Secure;
Partitioned` bilan beriladi (`ApplyWebviewCookies`). Saytning o'z cookie'lari `config/session.php` bo'yicha (`lax`)
qoladi. Sozlash (`.env`, ixtiyoriy):

```dotenv
TELEGRAM_WEBVIEW_SAME_SITE=none      # none (standart) | lax
TELEGRAM_WEBVIEW_PARTITIONED=true    # CHIPS: uchinchi tomon cookie'lari bloklangan brauzerlar uchun
```

Cookie baribir yo'qolsa (401/419 yoki login sahifasi), ilova `POST /telegram/app/reauth` ga `initData` ni qayta
yuboradi va so'rovni bir marta takrorlaydi — login/parol formasi WebView'da hech qachon ko'rsatilmaydi. Bu ikki
endpoint (`/telegram/app/auth`, `/reauth`) CSRF'dan ozod, chunki ular initData imzosi bilan tekshiriladi va faqat
JSON qabul qiladi (boshqa saytdagi forma JSON yubora olmaydi). Sahifalarda `X-Frame-Options` / `frame-ancestors`
yo'q (Telegram Web'ning iframe'i bloklanmaydi); agar keyinchalik CSP qo'shilsa, `frame-ancestors https://web.telegram.org`
ruxsat etilishi kerak.

**Natija kartasini ulashish.** Mini App'dagi natija sahifasida «Natijani ulashish» (`MainButton`): mobil Telegram'da
`shareToStory`, boshqa joyda Telegram'ning «chatga yuborish» oynasi. Telegram serverlari rasmni bizning sessiyasiz
oladi, shuning uchun karta 30 daqiqalik imzolangan havola bilan beriladi (`/telegram/card/{attempt}`).

**Battle: do'stni chaqirish.** Mini App'dagi battle sahifasida «Do'stni chaqirish» → kutish sahifasida «Do'stga
yuborish» (Telegram'ning ulashish oynasi). Do'st havolani ochsa — chaqiruv sahifasi va «Qabul qilish». Chaqiruv
`exam.telegram.challenge_minutes` (15) daqiqa amal qiladi; tasodifiy raqib qidiruvi chaqiruvlarga qo'shilmaydi.
Vaqt va ochkolar faqat serverda hisoblanadi (savol ochilgan vaqt `battle_rounds.started_at`), shuning uchun biri
Telegram'da, ikkinchisi saytda o'ynasa ham sharoit bir xil. Telegram yig'ilganda so'rovlar to'xtaydi, qaytganda
holat darhol yangilanadi; Mini App'da sahifa har 1,5 soniyada to'liq prop'lar o'rniga kichik `GET
/student/battles/{battle}/state` so'raydi.

**Sinov: emulyator `/telegram/dev`** (faqat `APP_ENV=local`, aks holda 404). Demo o'quvchini, `start_param`ni,
mavzuni (yorug'/tungi), ekran o'lchamini (320–430 px) va platformani tanlang → «Ochish». Sahifa initData'ni serverda
imzolaydi (bot tokeni bilan, token bo'lmasa `exam.telegram.dev_bot_token`), ilovani iframe'da soxta
`window.Telegram.WebApp` bilan ochadi va Telegram'ning qismini o'ynaydi: Back / Main tugmalari, «Yig'ish»
(deactivated/activated), haptic, hodisalar jurnali. Telegram'ga hech narsa yuborilmaydi. Telegram akkaunti yo'q demo
o'quvchiga soxta `telegram_id` li akkaunt yaratiladi (`username = dev_emulator`, `is_blocked = true` — unga bot xabari
bormaydi). Emulyator shu brauzer sessiyasini ishlatadi: yashirin oynada oching.

---

## 3. Tekshirish

```bash
php artisan test --filter="QuestionDraftServiceTest|AiDraftPagesTest|TelegramBotTest|TelegramMiniAppTest|TelegramNotificationsTest|TelegramWebviewTest|BattleChallengeTest"
npx vitest run resources/js/Composables/__tests__/useTelegram.test.js
php artisan exam:ai-drafts-weekly      # kalitsiz: "0 AI draft run(s) started."
php artisan exam:telegram-reminders    # tokensiz: "Telegram bot is not configured; nothing sent."
```
