# Telegram bot va Mini App — to'liq audit (WebView-first)

Sana: 2026-10-21. Bajardi: agent TW. Talab (ustoz): «telegram bot web li bo'lishi kerak, yana 1 bor to'liq analiz qilib chiq».

Maqsad: o'quvchi yoki ota-ona bot orqali qiladigan **hamma ish Mini App (WebView) ichida** bo'lsin. Chat faqat ilovani
ochish va bildirishnoma (`web_app` tugmasi bilan) yetkazish uchun.

Tekshirilgan holat: `a0c7674` (audit boshlanishi). Dalillardagi `fayl:qator` shu commit bo'yicha (`git show a0c7674:<fayl>`).
Tuzatishlar: `c0b24d6`, `5ff7c39`, `0b0cf8d`, `63744e3` (+ hujjat commiti). Skrinshotlar: scratchpad
`telegram-audit/` (to'liq yo'l pastda, §7).

---

## 0. Xulosa

| | Oldin (`a0c7674`) | Hozir |
|---|---|---|
| Yangi foydalanuvchi | `/start` → chatda 5 savollik anketa (tugmalar, «kontakt» klaviaturasi, ota-ona telefonini yozish) | `/start` → bitta qisqa xabar + «🚀 Ochish» (`web_app`). Ro'yxatdan o'tish Mini App'da |
| Ro'yxatdan o'tmagan foydalanuvchi Mini App'ni ochsa | «Botga qayting va /start bosing» — **boshi berk ko'cha** | WebView ro'yxat ekrani (o'quvchi / farzandni ro'yxatdan o'tkazuvchi ota-ona / kuzatuvchi ota-ona) |
| Telefon | faqat chatdagi «kontakt» tugmasi | `Telegram.WebApp.requestContact()` (imzosi serverda tekshiriladi) yoki qo'lda yozish |
| Ota-ona | chatda «Ota-onaman» → **o'quvchi** akkaunti ota-ona telefoni bilan; ota-ona akkaunti faqat saytda | WebView'da ota-ona akkaunti + farzandga bog'lanish so'rovi; ota-ona paneli ixcham WebView ko'rinishida |
| `/natijalar`, `/taklif` | chatda uzun matn | bir qator + `web_app` tugmalari |
| Ustoz/admin Telegram'ni ulagan bo'lsa | Mini App initData bilan ustoz/admin sessiyasini ochardi | Mini App ularni kiritmaydi, «Saytda ochish» |
| Limitlar | IP bo'yicha umumiy hisoblagich (CGNAT'da butun sinf 429) | Telegram foydalanuvchisi bo'yicha, har marshrutga alohida |

Chatda ro'yxatdan o'tish **olib tashlanmadi, zaxira sifatida qoldi** (`/royxat`), lekin endi o'zi boshlanmaydi. Sabab: barcha
rasmiy klientlar (iOS, Android, Desktop, macOS, Web K/A) Mini App'ni Bot API 6.0 (2022-aprel) dan beri ochadi va
`requestContact` 6.9 (2023-sentabr) dan beri bor; lekin uchinchi tomon klientlari va juda eski ilovalar `web_app`
tugmasini ochmasligi mumkin. Bot klient turini bilmaydi, shuning uchun /start matnida «Ilova ochilmasa: /royxat»
deyilgan. Zaxira bir xil qoidalar bilan ishlaydi (`TelegramRegistration`), qo'shimcha xarajati yo'q.

---

## 1. Telegram hujjatlari bo'yicha tekshiruv

Manbalar: <https://core.telegram.org/bots/webapps> (Mini Apps), <https://core.telegram.org/bots/api> (Bot API),
<https://core.telegram.org/bots/features#deep-linking>, `telegram-web-app.js` manbasi (<https://telegram.org/js/telegram-web-app.js>).

| Imkoniyat | Versiya | Biz qanday ishlatamiz | Holat |
|---|---|---|---|
| initData tekshiruvi: `secret = HMAC_SHA256("WebAppData", bot_token)`, `hash = HMAC_SHA256(secret, data_check_string)`, `hash` dan boshqa barcha maydonlar alifbo tartibida (8.0 dagi `signature` ham kiradi), `auth_date` | — | `TelegramInitData::verify()` — to'g'ri; `signature` maydoni ham imzolanadi (emulyator testi) | ✅ |
| Ed25519 (`signature`, uchinchi tomon tekshiruvi, Telegram ochiq kaliti) | 8.0 | kerak emas: bot tokeni serverda bor | — |
| `requestContact(callback)` | 6.9 | Ro'yxat ekrani. Hujjatda callback faqat `bool` qaytaradi; SDK manbasida (`getRequestedContact`) callback'ning 2-argumenti `{status, response, responseUnsafe}`, `response` = `contact=<json>&auth_date=…&hash=…` — Telegram klienti initData kabi imzolaydi. Server `TelegramInitData::verifyContact()` bilan tekshiradi: imzo, `contact.user_id` = initData foydalanuvchisi, 15 daqiqalik yangilik. Bu hujjatda yozilmagan, shuning uchun **ikkinchi dalil** ham bor: ulashilgan kontakt botga xabar bo'lib keladi (`message.contact.user_id = from.id`, Bot API `Contact`), webhook uni 15 daqiqa saqlaydi | ✅ yangi |
| `contactRequested` hodisasi (`status: sent/cancelled`) | 6.9 | rad etilsa — qo'lda yozish | ✅ yangi |
| `requestWriteAccess` / `allows_write_to_pm` | 6.9 | to'g'ridan-to'g'ri havola (`startapp`) bilan kelib, botga hech yozmagan foydalanuvchidan ro'yxatdan keyin xabar yuborishga ruxsat so'raladi (aks holda eslatmalar 403) | ✅ yangi |
| `startapp` / `start_param`, `tgWebAppStartParam` | — | Main Mini App va direct link `start_param` beradi; inline/menyu `web_app` tugmalari bermaydi → o'z tugmalarimizda `?to=`. `A-Z a-z 0-9 _ -`, ≤ 64 | ✅ (+ `ref_`, `invite`, `child_`) |
| Main Mini App (BotFather → Configure Mini App) | 7.x | `t.me/<bot>?startapp=…` uchun shart (INTEGRATIONS §2.7) | ✅ hujjatda |
| Menyu tugmasi (`setChatMenuButton`, `web_app`) | 6.0 | «Ochish» (oldin «Imtihonlar») | ✅ |
| `web_app` inline tugmalari | 6.0 | faqat shaxsiy chatda ishlaydi — bot faqat shaxsiy chatga javob beradi | ✅ |
| `BackButton` 6.1, `MainButton` (+`setParams` 7.10), `SecondaryButton` 7.10 | | Back/Main ishlatiladi; Secondary kerak bo'lmadi | ✅ |
| `MainButton.hideProgress()` | | SDK manbasida `isActive = true` qiladi → biz uni `setParams` dan oldin chaqiramiz (topilma M-9) | ✅ tuzatildi |
| `enableClosingConfirmation` 6.2, `disableVerticalSwipes` 7.7 | | imtihon va jang paytida | ✅ |
| `safeAreaInset` / `contentSafeAreaInset` (+hodisalar) | 8.0 | CSS `--tg-safe-*` (ro'yxat ekrani ham) | ✅ |
| `activated` / `deactivated` | 8.0 | Take'da «ilovadan chiqdi» | ✅ |
| `requestFullscreen` | 8.0 | ishlatilmaydi: to'liq ekranda Telegram sarlavhasi (Orqaga/Yopish) yo'qoladi, imtihonda bu xavfli | qaror |
| `addToHomeScreen` / `checkHomeScreenStatus` | 8.0 | ishlatilmadi (Minor m-11) | qoldi |
| `CloudStorage` 6.9, `DeviceStorage` / `SecureStorage` | 9.0 | kerak emas: holat serverda | — |
| `hideKeyboard` | 9.1 | ishlatilmadi (fokusdagi maydon `scrollIntoView` bilan ko'rinadi) | — |
| `shareToStory` 7.8 (faqat mobil), `openLink`, `openTelegramLink` | | natija kartasi, havolalar, taklif | ✅ |
| `my_chat_member` update (bloklash/blokdan chiqarish) | Bot API | `allowed_updates` ga qo'shildi, `is_blocked` yangilanadi | ✅ yangi |

---

## 2. Foydalanuvchi yo'llari

«Chat» = bot suhbatida; «WV» = Mini App ichida.

| Yo'l | Oldin | Hozir | Tekshiruv |
|---|---|---|---|
| Yangi o'quvchi `/start` | Chat: 5 savol | Chat: 1 xabar + «Ochish» → WV ro'yxat → **qabul testi** (tanlangan fan) | `test_start_with_ref_code_…`, Playwright B |
| `/start ref_<kod>` | kod saqlanadi, chatda anketa | kod saqlanadi → WV ro'yxat → `ReferralService::recordJoin` | `test_invite_code_…`, Playwright C |
| `t.me/<bot>?startapp=ref_<kod>` | ishlamas edi (ro'yxatdan o'tmagan → boshi berk) | WV ro'yxat, taklif `start_param` dan yoziladi | `test_invite_code_…`, Playwright C |
| Saytdagi o'quvchi Telegram'ni ulaydi | `/telegram/link` bor edi, lekin saytda **unga tugma yo'q** edi | 1) WV'da telefonni ulashsa — shu telefondagi akkaunt ulanadi; 2) sayt: Sozlamalar → «Telegramni ulash» | `test_an_existing_student_is_linked_only_…` |
| Ota-ona (farzand o'zi Telegram'siz) | Chat: «Ota-onaman» → o'quvchi akkaunti | WV: «Farzandimni ro'yxatdan o'tkazaman» → o'quvchi akkaunti (`filled_by=parent`) | `test_parent_registering_the_child_…`, Playwright D |
| Ota-ona (farzandning o'z akkaunti bor) | faqat saytda ro'yxat + `/telegram/link` | WV: «Natijalarni kuzataman» → ota-ona akkaunti + bog'lanish so'rovi (ustoz tasdiqlaydi) → ixcham ota-ona paneli | `test_parent_watching_…`, Playwright E |
| Ota-ona hisobotlari | yakshanba hisoboti `web_app`; natija xabari tugmasiz; panel sayt ko'rinishida | natija xabari → farzand sahifasi (`child_<id>`), panel WV'da | `test_guardian_result_message_…` |
| Ustoz/admin botni ochadi | Mini App ustoz sessiyasini ochardi; chatda Mini App tugmalari | Mini App 403 + «Saytda ochish»; chatda faqat sayt tugmasi | `test_staff_accounts_are_never_…`, `test_staff_in_the_chat_…` |
| Qabul testi | WV (deep link `placement_<fan>`) | o'zgarmadi; ro'yxatdan keyin avtomatik | Playwright B |
| Shanba imtihoni: eslatma → ochish → topshirish → natija → karta | WV (`exam_s_<id>`, `result_<id>`, `shareToStory`) | o'zgarmadi | `TelegramWebviewTest`, Playwright G (eslatma havolasi) |
| Battle: tasodifiy raqib / do'st chaqiruvi / biri Telegram'da, biri saytda | WV | + «Saytda ochish» endi saytga kirgan holda ochadi; limit tuzatildi | Playwright H (Telegram ↔ sayt) |
| O'sish, reyting | WV tablari | o'zgarmadi; «Natijalar» tabi | Playwright G |
| Taklif | `/taklif` chatda havola matni | `/taklif`: «📤 Do'stga yuborish» (`t.me/share/url`) + «🎁 Taklif sahifasi» (WV `#taklif`, «Telegram'da yuborish») | `test_bot_replies_are_short_…`, Playwright B |
| `/natijalar` | oxirgi 5 natija ro'yxati chatda | 1 qator + «Natijalarni ochish» / «Natijani ko'rish» | `test_bot_replies_are_short_…` |
| `/yordam` | buyruqlar | «Hammasi ilovada» + buyruqlar | — |
| `/bekor` | chat anketasini qayta boshlardi | chat anketasini bekor qiladi, «Ochish» | — |

---

## 3. Topilmalar

Darajalar: **Blocker** — yo'l umuman ishlamaydi; **Critical** — xavfsizlik yoki ommaviy nosozlik; **Major** — muhim
yo'l buziladi yoki talabga zid; **Minor** — kichik.

| ID | Daraja | Topilma | Dalil | Holat |
|---|---|---|---|---|
| B-1 | Blocker | Ro'yxatdan o'tmagan Telegram foydalanuvchisi Mini App'ni ochsa (menyu tugmasi, `startapp` havolasi, do'st chaqiruvi) — «Botga qayting va /start bosing». Ro'yxat faqat chatda | `MiniAppController.php:134-136`, `BotUpdateHandler.php:132`, `BotRegistration.php` | ✅ `c0b24d6`, `5ff7c39`: WV ro'yxat (`MiniAppRegistrationController`, `Pages/Telegram/Register.vue`); `to` saqlanadi (masalan `battle_<kod>`) |
| C-1 | Critical | `throttle:N,1` mehmon so'rovlarini **IP bo'yicha bitta umumiy hisoblagich** bilan sanaydi (`ThrottleRequests::resolveRequestSignature`). Mobil operatorlar CGNAT ortida minglab telefonni bitta IP'ga yig'adi → shanba 10:00 da sinf Mini App'ni ochsa `/telegram/app/auth` (20/daq) 429 beradi. Playwright 1-o'tishda ro'yxat ekrani «Too Many Attempts» ko'rsatdi | `routes/exam_integrations.php:38,43`; skrinshot `C-error.png` (1-o'tish) | ✅ `0b0cf8d`: `TelegramRateLimits` — Telegram foydalanuvchisi bo'yicha 30/daq + IP bo'yicha 600/daq; test `test_mini_app_limits_count_per_telegram_user_…` |
| C-2 | Critical | Saytdan `/telegram/link` bilan Telegram'ni ulagan ustoz/admin Mini App'da faqat initData bilan (parolsiz) to'liq ustoz/admin sessiyasini olardi | `MiniAppController.php:138-145` | ✅ `c0b24d6`: faqat `student` / `guardian`; boshqalarga 403 + sayt havolasi (auth va reauth) |
| M-1 | Major | Bitta telefonda ikki Telegram akkaunti: ro'yxatdan o'tmagan B ochganda oldingi A sessiyasi qolardi (409 javobi sessiyaga tegmasdi). WV ro'yxati bilan bu Critical bo'lardi | `MiniAppController.php:134-136` | ✅ `c0b24d6`: 409 da `logout` + `invalidate`; test `test_an_unregistered_telegram_user_…` |
| M-2 | Major | Chatda «Ota-onaman» — **o'quvchi** akkaunti ota-ona telefoni bilan; ota-ona akkaunti (`guardian`) va farzandga bog'lanish Telegram'dan mumkin emas edi (`/guardian/register` faqat `guest` sayt sahifasi) | `BotRegistration.php:232`, `lang/uz/exam_bot.php:62`, `routes/guardian.php:14` | ✅ WV'da ikki yo'l: farzandni ro'yxatdan o'tkazish (eski xulq) va «Natijalarni kuzataman» (ota-ona akkaunti + `GuardianLinkService::request`, ustoz tasdiqlaydi) |
| M-3 | Major | Ota-ona paneli WebView'da sayt ko'rinishida (yon menyu, header) | `GuardianLayout.vue:47` | ✅ `5ff7c39`: `WebviewShell` (Farzandlarim / Hisobotlar tablari); Playwright E 320/375/430 × yorug'/tungi |
| M-4 | Major | Ota-onaga natija xabari tugmasiz | `ExamTelegramNotifier.php:64` | ✅ `web_app` → `child_<id>` (faqat tasdiqlangan farzand) |
| M-5 | Major | Battle `state` so'rovi (1,5 s, 40/daq) «Do'stni chaqirish» (10/daq) va «Qabul qilish» (20/daq) bilan **bitta hisoblagichda** (foydalanuvchi bo'yicha) → jangdan keyin bir daqiqa «Do'stni chaqirish» 429 | `routes/exam_integrations.php:49-52` | ✅ `0b0cf8d`: har marshrutga alohida limiter; test `test_battle_routes_have_their_own_counters` |
| M-6 | Major | Webhook limiti 600/daq IP bo'yicha — hamma update Telegram'ning bir necha IP'sidan keladi; eslatma paytida ommaviy bosishlar sekinlashadi (Telegram qayta yuboradi) | `routes/exam_integrations.php:34` | ✅ 6000/daq (`telegram-webhook`); webhook baribir maxfiy sarlavha bilan himoyalangan |
| M-7 | Major | `/natijalar`, `/taklif`, menyu tugmalari chatda uzun matn — WebView-first talabiga zid | `BotUpdateHandler.php:154,194,200-230,299-321` | ✅ `c0b24d6`: qisqa matn + `web_app` |
| M-8 | Major | Telegram'da yaratilgan akkauntning paroli tasodifiy — «Saytda ochish» saytning login sahifasiga olib borardi | `WebviewShell.vue:43-44` (`a0c7674`), `BotRegistration.php` (`Str::random(40)`) | ✅ bir martalik kirish havolasi (`POST /telegram/app/site-link` → `GET /telegram/site-login/{token}`, 2 daqiqa, bir marta); Playwright H. Parol o'rnatish — «Qaror kerak» |
| M-9 | Major | `MainButton` `active: false` real Telegram'da ishlamasdi: `setParams` dan keyin chaqirilgan `hideProgress()` tugmani yana faol qiladi (SDK manbasi) | `useTelegram.js:375` | ✅ `0b0cf8d`; vitest «keeps an inactive MainButton inactive» |
| M-10 | Major | Saytda Telegram'ni ulash tugmasi umuman yo'q (`telegram.link` hech bir sahifada ishlatilmagan) | `grep telegram.link resources/js` (`a0c7674`) | ✅ o'quvchi Sozlamalari va ota-ona paneli (faqat saytda) |
| m-1 | Minor | Mini App'dan ulashilgan kontakt botga ham keladi → bot «tugmalardan birini tanlang» deb javob berardi (yashirin xato, WV `requestContact` bilan paydo bo'lardi) | `BotUpdateHandler.php:62` | ✅ jim saqlanadi (WV ro'yxati uchun ikkinchi dalil) |
| m-2 | Minor | Bloklash faqat 403 da bilinardi | `TelegramClient.php:96` | ✅ `my_chat_member` |
| m-3 | Minor | Bitirgan o'quvchi: chatda `grade=11`, saytda `null` | `BotRegistration.php:250` | ✅ ikkalasi `null` (`TelegramRegistration`) |
| m-4 | Minor | Menyu tugmasi «Imtihonlar» (battle, natija, ota-ona ham shu yerda) | `lang/uz/exam_bot.php:141` | ✅ «Ochish» |
| m-5 | Minor | Emulyator har qanday foydalanuvchi (admin ham) uchun initData imzolardi (faqat local) | `DevEmulatorController.php:67` | ✅ faqat o'quvchi/ota-ona |
| m-6 | Minor | Emulyatorda yangi foydalanuvchi, ota-ona, `requestContact` yo'q edi | `dev.blade.php` | ✅ «★ Yangi Telegram foydalanuvchi», ota-onalar, `requestContact` / `requestWriteAccess` (haqiqiy SDK protokoli) |
| m-7 | Minor | `startapp` havolasi bilan kelgan, botga yozmagan foydalanuvchiga xabar ketmaydi (403 → `is_blocked`) | — | ✅ ro'yxatdan keyin `requestWriteAccess` (agar `allows_write_to_pm` false); akkaunt `chat_id = user.id` bilan yaratiladi |
| m-8 | Minor | Ustoz/admin chatda Mini App tugmalarini olardi | `BotUpdateHandler.php:194` | ✅ `63744e3` |
| m-9 | Minor | initData TTL 24 soat (`config/exam.php:106`) — o'g'irlangan initData shu muddat ishlaydi | — | qoldi: uzoq imtihon/battle va jim qayta kirish uchun; `TELEGRAM_INIT_DATA_TTL` bilan qisqartirish mumkin |
| m-10 | Minor | Telegram Web + Safari: `Partitioned` cookie ham saqlanmasligi mumkin | `ApplyWebviewCookies` | qoldi (jim qayta kirish, 3/daq dan keyin xabar) |
| m-11 | Minor | `addToHomeScreen` (8.0) ishlatilmadi | — | qoldi |
| m-12 | Minor | Bir martalik kirish havolasi — «login CSRF» ehtimoli (birov o'z havolasini yuborsa, qabul qiluvchi uning akkauntiga kiradi) | `MiniAppController::siteLogin` | qabul qilingan xavf: 2 daqiqa, bir marta, faqat o'quvchi/ota-ona |
| m-13 | Minor | Boshqa modullarda ham `throttle:N,1` bitta hisoblagichni bo'lishadi (masalan imtihon dvigateli: `answers` 120, `events` 60, `sessions.start` 20 — `routes/exam_engine.php:42-47`) | — | xabar qilindi (Bosqich 5 fayli); hozirgi chastotada (avtosaqlash 15 s) xavf past |
| m-14 | Minor | Qo'lda yozilgan telefon — tasdiqlanmagan; kimdir begona raqamni yozsa, keyin raqam egasi Telegram kontakti bilan shu akkauntni oladi | `TelegramRegistration::existing()` | to'g'ri xulq (egasi ustun), «Qaror kerak» da |
| m-15 | Minor | Shanba imtihoni demo sessiyasi 24.10 ga rejalashtirilgan, Playwright'da jonli topshirilmadi (egasining ma'lumotiga tegilmadi) | — | feature testlar (`TelegramWebviewTest`, eslatma/natija tugmalari) + Playwright G (eslatma havolasi) |

Ijobiy (o'zgartirish kerak emas): webhook sirini `hash_equals` bilan tekshirish; faqat shaxsiy chat; foydalanuvchi bo'yicha
30 xabar/daq; loglarda faqat id'lar (telefon, ism, matn yo'q; yangi kodda ham); `start_param` grammatikasi va egalik
tekshiruvi (begona `result_`, `report_`, `child_` → bosh sahifa); `return` yo'li faqat `/student|/guardian`; `X-Frame-Options`
yo'q (Telegram Web iframe'i ishlaydi); ota-ona faqat tasdiqlangan farzandini ko'radi (`ensureWard`, `child_` ham).

---

## 4. Platformalar

| Klient | Cookie / sessiya | Ekran | Izoh |
|---|---|---|---|
| iOS (WKWebView) | birinchi tomon cookie | `safeAreaInset` (pastki 34 px), pastga surib yopish imtihonda o'chiq (7.7) | `shareToStory` bor |
| Android (WebView) | birinchi tomon cookie | klaviatura `viewportChanged` → tablar yashirinadi, maydon ko'rinadi | tizim «orqaga» = `BackButton` |
| Desktop / macOS | birinchi tomon | keng oyna: kontent `max-w-content` | `shareToStory` yo'q → `t.me/share/url` |
| Telegram Web K/A | **uchinchi tomon iframe**: HTTPS'da `SameSite=None; Secure; Partitioned` (ro'yxat ekrani ham `telegram/app/*` ichida); cookie yo'qolsa jim qayta kirish | — | Safari: m-10 |
| Uchinchi tomon / eski klient | `web_app` ochilmasligi mumkin | — | `/royxat` (chat zaxirasi) |

- **initData muddati o'tgan** (Mini App 24 soatdan ortiq ochiq): qayta kirish 401 → «Mini App'ni yopib, qayta oching». Ro'yxat ekrani ham shu xabarni ko'rsatadi.
- **@username yo'q** foydalanuvchi: hech narsa username'ga bog'liq emas (emulyatordagi yangi foydalanuvchilarda username yo'q).
- **Botni bloklagan**: Mini App ishlaydi; xabarlar yuborilmaydi (`is_blocked`, endi `my_chat_member` bilan darhol).
- **Bitta telefonda ikki Telegram akkaunti**: har ochilish `/telegram/app` orqali initData'ni qayta tekshiradi va sessiyani shu akkauntga o'tkazadi; ro'yxatdan o'tmagan akkaunt uchun avvalgi sessiya yopiladi (M-1).

---

## 5. Xavfsizlik

- **initData**: har kirishda (auth, reauth, register, contact) HMAC + `auth_date`; sessiyaga ishonilmaydi.
- **Kontakt**: `requestContact` javobining imzosi, `contact.user_id`, 15 daqiqa; yoki webhook'dagi `message.contact` (`user_id = from.id`). Qo'lda yozilgan raqam faqat **yangi** akkaunt ochadi va `phone_verified_at = NULL`; mavjud akkauntni hech qachon ulamaydi (`reg.phone_taken`).
- **Akkaunt egallab olinmasligi**: telefon bo'yicha faqat `student` (o'quvchi yo'li) va `guardian` (ota-ona yo'li) ulanadi; ustoz/admin telefoni — `reg.role_conflict`; boshqa Telegram'ga ulangan akkaunt — `reg.already_linked`. Ustoz/admin Mini App'ga kirmaydi.
- **CSRF**: `/telegram/app/{auth,reauth,register,contact}` ozod, chunki faqat JSON (boshqa sayt formasi JSON yubora olmaydi, CORS yo'q) va har biri initData bilan; `site-link` oddiy CSRF bilan.
- **Limitlar**: `TelegramRateLimits` (§3 C-1, M-5, M-6).
- **Shaxsiy ma'lumot**: loglarda id va sabab kaliti; kontakt keshda 15 daqiqa, ro'yxatdan keyin o'chiriladi; javobda telefon niqoblangan (`+998 90 *** ** 33`).
- **Ota-ona izolyatsiyasi**: WebView'da ham `role:guardian` va `ensureWard`; `child_<id>` faqat tasdiqlangan bog'lanish.

## 6. i18n, unumdorlik, qulaylik

- Bot matnlari — `lang/uz/exam_bot.php` (yangi kalitlar `--- TW ---` bloklarida). Vue sahifalar loyiha odati bo'yicha o'zbekcha matn bilan.
- Unumdorlik (Playwright, 375 px, HTML + Inertia prop'lari, KB): Take (qabul testi) 99, imtihon bosh sahifasi 84, O'sishim 107, imtihon reytingi 82, battle 99 (SDK va JS bundle keshdan). Battle: Mini App 1,5 s da kichik `state` so'rovi (Playwright H: ~12 s jangda 7 so'rov).
- Qulaylik: ro'yxat ekrani — `fieldset/legend` (ChoiceGroup), har maydon yorlig'i, xatolar `role=alert`, tasdiqlangan telefon `role=status`; WebView tablari `aria-current`; Telegram'da asosiy tugma — tizim tugmasi (MainButton), saytda sahifadagi tugma.

## 7. Playwright (emulyator, yangi demo ma'lumot bilan)

Skript: `C:\Users\Umidbek\AppData\Local\Temp\claude\c--xampp-htdocs-edulife\da0d82b1-2b6f-4bd5-9f4c-89ddc6321d70\scratchpad\t\run_tw_audit.py`
(har ishga tushishda yangi Telegram foydalanuvchilari WebView'da ro'yxatdan o'tadi — qabul testi hech qachon «allaqachon
topshirilgan» bo'lmaydi). Eski `run_webview_8000.py` ham shunday qilindi.
Skrinshotlar: `C:\Users\Umidbek\AppData\Local\Temp\claude\c--xampp-htdocs-edulife\da0d82b1-2b6f-4bd5-9f4c-89ddc6321d70\scratchpad\telegram-audit\`.

Natija: **145/145** tekshiruv o'tdi (`telegram-audit/results.txt`). Eski `run_webview_8000.py` (yangi foydalanuvchi bilan): **45/45**.
Audit vaqtidagi birinchi o'tishda 7 ta xato chiqdi — ulardan C-1 (429 «Too Many Attempts»), M-5 va M-9 topildi va tuzatildi; qolganlari skript xatolari edi.

| Bo'lim | Nima | Skrinshotlar |
|---|---|---|
| A | ro'yxat ekrani 320/375/430 × yorug'/tungi (bo'sh va to'ldirilgan) | `A-register-*.png` |
| B | yangi o'quvchi: ro'yxat (`requestContact`) → qabul testi → topshirish → natija → «Natijalar» → taklif | `B-01..04` |
| C | `startapp=ref_<kod>` → taklif yozildi (DB) | — |
| D | ota-ona farzandni ro'yxatdan o'tkazadi | `D-01-parent-child-375-dark.png` |
| E | kuzatuvchi ota-ona → bog'lanish so'rovi → ixcham panel, 3 o'lcham × 2 mavzu | `E-*.png` |
| F | `requestContact` rad etildi / klient < 6.9 → qo'lda; mavjud raqam rad etildi | `F-*.png` |
| G | o'quvchi sahifalari 3 o'lcham × 2 mavzu, eslatma havolasi, profil, «saytda ishlaydi» eslatmasi | `G-*.png` |
| H | battle: A Telegram'da, B «Saytda ochish» orqali **saytda**; ikkalasi oxirigacha | `H-01..04` |
| I | Telegram'dan tashqarida: kirish va ro'yxat sahifalari tushuntiradi, saytda SDK yo'q | `I-register-outside-telegram.png` |

## 8. Qaror kerak

1. **Telegram'da ochilgan akkaunt uchun saytda parol.** Hozir saytga faqat Mini App'dagi «Saytda ochish» (bir martalik havola) bilan kiriladi. «Parol o'rnatish» (joriy parolsiz, faqat Telegram'dan kirgan sessiyada) qo'shilsinmi?
2. **Kuzatuvchi ota-ona telefoni sayt akkauntiga to'g'ri kelsa** (saytda ota-ona bo'lib ro'yxatdan o'tgan), Telegram kontakti bilan avtomatik ulanadi (o'quvchi kabi). Shu qoladimi yoki ota-onalar faqat `/telegram/link` bilan ulansinmi?
3. **Qo'lda yozilgan telefon** bilan yangi akkaunt ochish (tasdiqlanmagan) ruxsat etilsinmi yoki faqat Telegram kontakti?
4. **Chat zaxirasi** (`/royxat`) qolsinmi yoki butunlay olib tashlansinmi (hamma rasmiy klientlarda Mini App bor)?
5. initData TTL 24 soat → 6 soatga tushirilsinmi (imtihon 3 soatgacha)?
