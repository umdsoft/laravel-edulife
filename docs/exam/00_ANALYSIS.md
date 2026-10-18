# Bosqich 0 — To'liq tahlil: «Shanba imtihoni» va platformani matematika/fizikaga moslash

**Branch:** `feature/shanba-imtihoni` (asosi: `origin/main` `93f3ef4`, 2025-12-18)
**Holat:** faqat tahlil; ilova kodiga tegilmadi. Shu bosqichdan keyin tasdiq kutiladi.
**Usul:** har bir da'vo kodda tekshirildi (fayl:qator, migratsiya, `PRAGMA table_info`, `php artisan route:list`, `event:list`, `php artisan test`). Tekshirilmagan narsa "tasdiqlanmagan" deb belgilangan.

---

## 0. Muhim kontekst: ikkita ajralgan kod bazasi

| | `C:\xampp\htdocs\edulife` (lokal `main`) | `origin/main` (bu branch'ning asosi) |
|---|---|---|
| Umumiy ajdod | `382e8ca` | `382e8ca` |
| Ustiga qo'shilgan | 45 ta audit tuzatish commit'i (`AUDIT_REPORT.md`) | 10 ta commit: **1156 fayl, +252 000 qator** (English, Olympiad, Lab, Lab API va boshqalar) |
| Qo'shimcha | commit qilinmagan migratsiya nomi o'zgarishlari | — |

Prompt'da tilga olingan narsalar (`question_bank`, olimpiada dvigateli, `regions/schools`, Lab, English) **faqat `origin/main` da bor**. Shuning uchun tahlil va keyingi ish shu asosda olib boriladi.

Audit commit'larining 38/45 tasi `origin/main` uchun ham kerak:
- 31 tasi to'qnashuvsiz cherry-pick qilinadi;
- 14 tasida konflikt bor;
- 3 tasi kerak emas, chunki upstream ularni o'zi tuzatgan: `72cd95e`, `dfe744a`, `1683682`.

Batafsil reja — 5-bo'lim, Q-1.

---

## 1. Tasdiqlangan holat jadvali

Belgilar: ✅ Bor (ishlatsa bo'ladi) · ⚠️ Bor, lekin buzuq yoki to'liq emas · ❌ Yo'q

### 1.1. Prompt'dagi "qayta ishlatsa bo'ladigan" da'volar

| Da'vo | Holat | Dalil va izoh |
|---|---|---|
| `question_bank` jadvali | ✅ / ⚠️ | `2025_12_05_000009_create_question_bank_table.php`. Prompt'da sanalgan barcha ustunlar bor (`difficulty` easy/medium/hard/expert, `explanation`, `explanation_media` JSON, `status` draft/active/archived, `is_verified`, `created_by`, `concept_tags`, `times_used/answered/correct`).<br>Qo'shimcha ustunlar: `olympiad_type_id`, `section_type` (majburiy), `direction_id`, `question_type`, `question_html`, `question_media`, `options` JSON, `correct_answer` JSON (majburiy), `negative_marking`, `grading_rubric`, `skill_tags`.<br>**`topic_id` — FK'siz bo'sh uuid** (22-qator), **topics jadvali yo'q**.<br>Model `QuestionBank`: `demoQuestions()` mavjud bo'lmagan klassga ishora qiladi; `recordUsage()` atomik emas.<br>Bu jadvalga savol yaratadigan admin yoki ustoz UI **yo'q**. |
| `OlympiadExamService` | ⚠️ | Bor: `startExam`, `submitAnswer` (upsert va darhol baholash), `completeSection`, `submitExam`, `validateSession`; sessiya tokeni `Str::random(64)`.<br>**Lekin** `routes/student.php:243-249` dagi 7 ta exam route (`preflight`, `start`, `exam`, `getSection`, `submitAnswer`, `toggleFlag`, `completeSection`) **controller'da yo'q** — olimpiadani boshlab bo'lmaydi.<br>`section_attempts` ikki marta yaratiladi (service va model `start()`), unique index'ga uriladi. |
| `OlympiadGradingService` | ✅ / ⚠️ | Avto va qo'lda baholash, `recalculateAttemptScores`, o'rin (ball ↓, vaqt ↑, teng natijalar).<br>Ikkita bir-biriga zid vaznli ball formulasi bor: `OlympiadSection:265` va `GradingService:154`. |
| `AntiCheatService` | ⚠️ | Qurilma fingerprint'i, device lock, qoidabuzarliklar, appeal va heartbeat bor.<br>Xatolar:<br>• device lock `attempt_id` ga bog'lanmaydi, shuning uchun `verifyDevice` doim false;<br>• heartbeat klient vaqtini oladi, ya'ni amalda ishlamaydi;<br>• controller'dagi `reportViolation` boshqa yo'ldan yozadi (`security_violations`ga tushmaydi). |
| `AutoSubmitExpiredTestJob` | ⚠️ | Bor, lekin **hech qayerda schedule yoki dispatch qilinmaydi**. |
| `TestEvaluationService` (6 tur) | ⚠️ | single/multiple_choice, true_false, fill_blank, matching, ordering — bor.<br>`:60` `pass_rate` (null) sababli **har qanday urinish o'tadi**; `:101` `correct_order` → `ordering_items` bo'lishi kerak.<br>Faqat kurs `questions` jadvali bilan ishlaydi, `question_bank` bilan emas. |
| `regions`, `districts`, `schools` | ⚠️ | Jadvallar bor.<br>`RegionSeeder` (14 viloyat) `DatabaseSeeder`da chaqirilmaydi; tuman va maktab ma'lumotlari yo'q.<br>`users` va `student_profiles` bu jadvallarga **bog'lanmagan** (faqat olimpiada bog'langan). |
| `users` (UUID, `phone` unique, `role` enum) | ✅ | Rollar: `student, teacher, admin, super_admin`. Ota-ona roli yo'q. |
| spatie/permission | ⚠️ | O'rnatilgan, lekin rollar seed qilinmagan. Haqiqiy tekshiruv `users.role` ustuni orqali (`RoleMiddleware`). |
| Sanctum | ⚠️ | Faqat `api/v1/english` (stateful SPA). `User` modelida `HasApiTokens` yo'q. |
| `payments`, `subscription_plans`, `subscriptions`, `promo_codes` | ✅ / ⚠️ | Jadvallar va admin CRUD bor; `payments.provider` enum'da payme/click/uzum/manual qiymatlari bor.<br>**Payme/Click kodi yo'q.**<br>`User::hasActiveSubscription()` doim `true` qaytaradigan stub. |
| `LeaderboardService` | ✅ / ⚠️ | Olimpiada uchun, 10 soniyalik kesh.<br>Mavjud bo'lmagan `users.name` ni so'raydi.<br>Haftalik reyting uchun eng mos naqsh — `English\LeaderboardService` (davr: daily/weekly/all_time). |
| Sertifikat va QR | ⚠️ | simple-qrcode va dompdf bor.<br>`CertificateService::generatePdf` PDF o'rniga matn yozadi.<br>`certificate.verify` va `certificates.verify` route nomlari mos emas. |
| `CoinRewardService::awardReferralCoins` | ⚠️ | 100-qatorda bor, lekin **hech kim chaqirmaydi**; `$referee->name` mavjud emas.<br>Referral jadvallari yo'q. |
| `SafeHtml` naqshi | ⚠️ | Komponent yo'q.<br>DOMPurify + `resources/js/Composables/useSanitize.js` bor (5 joyda ishlatiladi).<br>39 ta `v-html` bor, ko'pi tozalanmagan, jumladan olimpiada `Exam/Index.vue:430`. |

### 1.2. Prompt'dagi "ma'lum xatolar"

| Xato | Holat | Dalil |
|---|---|---|
| `questions.is_active` | ✅ tasdiqlandi | `Student/TestAttemptController.php:61` |
| `randomize_questions` (aslida `shuffle_questions`) | ✅ tasdiqlandi | `:62` |
| `pass_rate` (aslida `passing_score`) | ✅ tasdiqlandi | `:308`, `TestEvaluationService.php:60` |
| `content` / `hint` (aslida `question_text` / `option_text`) | ✅ tasdiqlandi | `:144, :147, :150, :348`. `hint` ustuni umuman yo'q. |
| `max_attempts = 0` cheksiz bo'lishi kerak, `>=` bloklaydi | ✅ tasdiqlandi | `:55`, shuningdek `Student/TestController.php:43,120,137` |
| `routes/admin.php:145-146` approve/reject — metodlar yo'q; `quiz` relation | ✅ tasdiqlandi | `Admin/QuestionController` da faqat `index/show/destroy` bor. `:14,:32` `quiz.course` ni yuklaydi. Approve/reject uchun status ustuni ham yo'q. |
| XP/ELO `users` va `student_profiles` da takrorlangan | ✅ tasdiqlandi (aslida 3 joyda, English ham) | `users`: `xp_total, level, coin_balance, elo_rating, battle_rank, streak_*…`; `student_profiles`: `xp, level, coins, elo_rating, rank, streak_days…`.<br>Header `users` ni ko'rsatadi, do'kon esa `student_profiles` dan yechadi. |
| `maatwebsite/excel ^1.1` | ✅ tasdiqlandi | v1.1.5; tashlab yuborilgan `phpoffice/phpexcel` ni tortadi (76 advisory'ning 20 tasi shundan). Kodda ishlatilmaydi. |

**Prompt'da yo'q, lekin topilgan qo'shimcha xatolar:**
- **Avtorizatsiya ishlamaydi.** Bazaviy `Controller` bo'sh, `app/Policies` yo'q. 20 ta controller `authorize()` ni chaqiradi va fatal xato oladi (audit `20959d7` ni ko'chirish kerak).
- **XP ikki marta beriladi.** `event:list` da `AwardLessonXP` va `AwardCourseXP` ikki martadan ro'yxatdan o'tgan.
- **Mavjud bo'lmagan metodlarga 44 ta route** ishora qiladi (olimpiada imtihonidagi 7 tasi ham shu ichida).
- **Ro'yxatdan o'tish dev muhitda ishlamaydi.** `RegisterController:37` → `MassAssignmentException [phone_verified_at]` (upstream `preventSilentlyDiscardingAttributes` ni yoqqan).
- **`users.coins` ustuni yo'q.** `OlympiadPaymentTransaction:164` va `OlympiadPrize:149` shu ustunni `increment` qiladi.

### 1.3. "Yo'q (yangidan qilinadi)" ro'yxati

| Narsa | Holat |
|---|---|
| topics (matematika/fizika) | ❌ (faqat `english_topics`) |
| Fizika yo'nalishi | ❌ `DirectionSeeder`: Dasturlash, Ingliz, Rus, **Matematika (`math`)**, Marketing, Dizayn, Biznes. `OlympiadSeeder` qo'shimcha `mathematics` yaratadi — dublikat |
| KaTeX/MathJax | ❌ (`package.json` va kodda 0 ta) |
| Savol importi, AI qoralama, tekshiruv navbati | ❌ (`question_bank` uchun UI umuman yo'q) |
| Blueprint, variant generatori | ❌ (`olympiad_questions` hammaga bir xil ro'yxat beradi) |
| Haftalik imtihon jadvali, qabul testi, mastery, haftalik o'sish | ❌ |
| Ota-ona roli va bog'lanishi | ❌ |
| Telegram bot / Mini App | ❌ (faqat profildagi `telegram` havolasi) |
| SMS provayder | ❌ (`sms_logs`, `otp_codes` jadvallari bor, yuboruvchi kod yo'q) |
| Natija kartasi, tavsiya kodlari | ❌ |
| Payme/Click callback'lari | ❌ |
| AI integratsiya | ⚠️ `English\AIConversationService` Anthropic API'ga to'g'ridan-to'g'ri HTTP so'rov yuboradi. `config('services.anthropic')` aniqlanmagan, model eskirgan, mavjud bo'lmagan model klassiga yozadi. Exam uchun yaroqsiz — yangidan yoziladi |
| Feature flag mexanizmi | ❌ (`settings` jadvali va `Setting::get/set`, 1 soatlik kesh — runtime toggle uchun ishlatsa bo'ladi) |
| i18n (`lang/`) | ❌ — `lang/` papkasi yo'q, UI matnlari Vue ichida o'zbekcha hardcode qilingan |
| Rasm yuklash (savol va variant) | ⚠️ `question_image` va `option_image` ustunlari bor, yuklash kodi yo'q. medialibrary o'rnatilgan, lekin hech bir model `HasMedia` ishlatmaydi |
| O'quvchi profili: sinf, maktab, viloyat, maqsad, fan | ❌ |

### 1.4. Test va build bazaviy holati (`origin/main`)

- **`php artisan migrate` (SQLite):** 198 tadan 197 tasi o'tadi. `2025_12_17_045900_update_english_battles_battle_type_enum` faqat MySQL'da ishlaydigan `MODIFY` buyrug'i bilan yiqiladi.
- **`php artisan test`:** 21 tadan **20 tasi yiqiladi**, 19 tasi yuqoridagi migratsiya sababli. Migratsiya vaqtincha chetlab o'tilsa, 17 tasi yiqiladi. Sabablar:
  - factory'lar sxemaga mos emas (`DirectionFactory.name`, `CourseFactory.level/discount_price`, `ShopItemFactory.icon`);
  - `withoutVite()` yo'q;
  - login maydoni `login` ga o'zgargan, testlar eski nomni yuboradi;
  - `CoinServiceTest` ikkinchi `student_profile` yaratadi.
- **`route:list`:** 792 route (shundan English 392 ta).
- **`composer audit`:** 18 paketda 76 advisory.

---

## 2. Platformani matematika va fizikaga moslash — modullar xaritasi

| Modul | Hajm va joy | Fanga bog'liqmi? | Taklif |
|---|---|---|---|
| **English** | 392 route (`routes/english.php`, `api/v1/english`), 47 controller, 52 model, 46 migratsiya, ~70 servis, 180 Vue fayl, `EnglishServiceProvider` | Ha | **Flag bilan yashirish** — route guruhlari, provider, menyu. ⚠️ Jadvallar qolishi shart: `user_achievements` jadvalida `english_achievements` ga FK bor |
| Kurslar / LMS | `student.php`, `teacher.php` | `courses.direction_id` orqali | **Moslashtirish**: faqat Matematika va Fizika yo'nalishlari; `exam_topic_id` va `course_track` qo'shiladi. Talaba controller'lari (`CourseController:56`, `DashboardController:72`, `SearchController:23,56`) `is_active` ni filtrlamaydi — qo'shish kerak |
| Kurs testlari | `tests`, `questions`, `test_attempts` | Kurs orqali | Saqlanadi, xatolari tuzatiladi; `topic_id` qo'shiladi |
| Olimpiadalar | 50 route, 17 migratsiya | `olympiad_types`: language_english/russian, programming, math | **Moslashtirish**: physics turi qo'shiladi, qolganlari o'chiriladi; savollar `question_bank`dan (faqat approved); savol biriktirish UI quriladi. Hozir dvigatel ishlamaydi (7 metod yo'q) |
| Battle 1v1 | `battles`, `battle_rounds`, `MatchmakingService` | `direction_id` | **Hozircha flag bilan yashirish.** Model va migratsiya mos emas (`total_rounds`, `current_round`, `'waiting'` enum'da yo'q), Vue sahifalari yo'q. Keyin `question_bank` va fan bo'yicha ELO bilan qayta ulanadi |
| Turnirlar | 4 route | `direction_id` | Flag bilan yashirish (model va migratsiya mos emas, admin CRUD yo'q) |
| Virtual Lab | 26 route, 12 jadval, **57 tajriba** / 7 kategoriya (Mexanika, Termodinamika, Elektr, Optika, To'lqinlar, Magnetizm, Atom), 59 simulyatsiya komponenti | Fizika | **Saqlash** va fizika mavzulariga ulash (`lab_experiments.exam_topic_id`); tajribadan keyin 3–5 ta `question_bank` savoli. `formulas` maydoni bo'sh, KaTeX kerak |
| Gamifikatsiya (XP, level, streak, coin, achievements, missions) | — | Fanga bog'liq emas | Saqlanadi. XP va coin bitta manbaga birlashtiriladi (Q-3). Battle missiyalari o'chiriladi. Fanga xos yutuqlar seed qilinadi |
| Shop / coin paketlar | 5 route | Fanga bog'liq emas | Flag (ixtiyoriy) |
| Reytinglar | `/leaderboard` (xp, battles, weekly) | Global | Fan bo'yicha alohida reytinglar (Exam moduli), "battles" tabi yashiriladi |
| Sertifikatlar | — | Kurs orqali | Saqlanadi, buzuq qismlari tuzatiladi |
| To'lov va obuna | admin CRUD | Fanga bog'liq emas | Saqlanadi. Bosqich 8 gacha qo'lda to'lov |
| O'qituvchi tier, komissiya, payout | `TeacherScoreService` va boshqalar | Fanga bog'liq emas | Saqlanadi, MVP'da menyudan yashiriladi |
| Ijtimoiy (follow, feed), Support, Notifications | — | Fanga bog'liq emas | Saqlanadi (sahifalarning bir qismi yo'q) |
| Coding problems | 3 jadval | Dasturlash | Harakatsiz holda qoldiriladi (0 ta murojaat) |

**Yashiriladigan menyular:**
- `StudentLayout.vue` — yuqori nav (Ingliz tili, Turnirlar), mobil nav (english), dashboard'dagi English kartasi.
- `Components/Admin/Sidebar.vue` (massiv).
- `TeacherLayout.vue` (massiv).
- `bootstrap/app.php`ga `feature:` middleware alias, `HandleInertiaRequests::share()`ga `features` prop.

---

## 3. Arxitektura qarorlari (taklif — tasdiq kutiladi)

### 3.1. Modul joylashuvi
`app/Modules` **ishlatilmaydi**. Repo'dagi yagona katta modul (English) har bir qatlamda `English` papkasi sifatida tuzilgan, Exam ham xuddi shunday quriladi:

```
app/Models/Exam/*            app/Services/Exam/*          app/Jobs/Exam/*
app/Http/Controllers/Exam/*  (Inertia: student, parent, teacher review, admin)
app/Http/Controllers/Api/Exam/*  (Telegram Mini App, initData auth)
app/Http/Requests/Exam/*     app/Http/Resources/Exam/*    app/Events/Exam/*
app/Providers/ExamServiceProvider.php   routes/exam.php (web.php dan)
resources/js/Pages/Exam/*    resources/js/Components/Exam/*  (MathContent.vue va boshqalar)
database/seeders/Exam/*      resources/prompts/exam/*.md    config/exam.php, config/features.php
lang/uz/{subjects,exam_bot}.php
```
Jadvallar `exam_` prefiksi bilan nomlanadi (English'dagi `english_` kabi).

### 3.2. Savollar banki
`question_bank` **kengaytiriladi** (yangi jadval ochilmaydi). Shunda olimpiada, imtihon, battle va lab bitta bankdan foydalanadi. Additiv ustunlar:
- `subject_id`, `topic_id` (FK `exam_topics`ga), `difficulty_level` (1–5), `exam_formats` JSON, `source_ref`;
- `origin` (ai_draft / manual / import), `review_status` (draft / in_review / approved / rejected / retired);
- `reviewed_by`, `reviewed_at`, `reject_reason`, `solution_video_url`, `content_format`, `units_note`;
- `constants` JSON, `error_reports_count`, `is_demo`, `normalized_hash` (dublikatlarni aniqlash uchun).

Variantlar uchun **`question_bank_options` jadvali** yaratiladi. Bunda `is_correct` default **NULL**. Hozirgi `options` va `correct_answer` JSON maydonlari olimpiada orqaga mosligi uchun qoldiriladi va approve paytida sinxronlanadi. Sabab:
- "ustoz belgilamaguncha null" qoidasini va rasm biriktirishni JSON'da saqlash noqulay;
- variant darajasida indeks va audit kerak.

Muammo: `section_type` va `correct_answer` majburiy. Exam savollari uchun ularni nullable qilish yoki default qiymat berish kerak — bu ustun turini o'zgartirish bo'ladi, alohida migratsiyada qilinadi (Q-4).

### 3.3. Imtihon dvigateli
- **Olimpiadaning qayta ishlatiladigan qismlari:**
  - qurilma fingerprint'i va device lock (`user_devices`, lock, violations, appeals) — tuzatilgandan keyin;
  - sessiya tokeni naqshi;
  - `OlympiadLiveLeaderboard::recalculateRanks` reyting algoritmi;
  - `TestEvaluationService` baholovchilari (namuna sifatida).
- **Qayta ishlatilmaydi:**
  - `olympiad_questions` — hammaga bitta qat'iy ro'yxat, `(section, order)` bo'yicha unique;
  - `olympiad_attempts` — har olimpiadaga bitta urinish.

  Bular blueprint, har o'quvchiga alohida variant va haftalik takrorlanishni ifodalay olmaydi. O'rniga `exam_sessions`, `exam_variants`, `exam_attempts` va `exam_answers` quriladi.
- **Yangi savol turlari** `numeric` va `numeric_with_unit` uchun `App\Services\Exam\Grading\*` strategiya klasslari yaratiladi (har turga bitta grader). `expression` turi uchun interfeysda joy qoldiriladi.
- **Auto-submit server tomonda bo'ladi:** schedule qilingan job (hozir faqat brauzer bajaradi).

### 3.4. Rollar va autentifikatsiya
- Ota-ona uchun `users.role` enum'iga `guardian` qo'shiladi. Tizim hozir shu ustunga tayanadi; spatie esa ishlatilmaydi.
- Mini App avtorizatsiyasi: Telegram `initData` HMAC imzosi serverda tekshiriladi, keyin Sanctum token beriladi (`HasApiTokens` qo'shiladi).

### 3.5. Fan doirasida matn ko'rsatish
`<MathContent>` komponenti:
- markdown → KaTeX;
- DOMPurify (mavjud `useSanitize`);
- rasmlar uchun lightbox.

U barcha savol, dars, olimpiada, natija sahifalarida va Mini App'da ishlatiladi. Natija kartasi va PDF uchun server tomonda render qilinadi (Q-9).

---

## 4. Xavflar

| # | Xavf | Ta'siri | Yumshatish |
|---|---|---|---|
| R1 | Ikki ajralgan kod bazasi; audit tuzatishlarini ko'chirishda 14 ta konflikt | Yuqori | Bosqich 1 da tartib bilan `cherry-pick -x`, har guruhdan keyin test |
| R2 | Test infratuzilmasi buzuq (20/21 yiqilgan) | Yuqori | Bosqich 1 ning birinchi commit'i |
| R3 | SQLite noma'lum ustunni xato emas, literal deb qabul qiladi, shuning uchun sxema xatolari testda yashirinadi | O'rta | CI'da MySQL bilan ham test |
| R4 | `question_bank`dagi majburiy `section_type`/`correct_answer` olimpiada mantiqiga bog'langan | O'rta | Q-4 bo'yicha qaror; olimpiada oqimini regressiya testlari bilan himoyalash |
| R5 | XP/coin uch joyda saqlanadi; birlashtirish ko'p servisga ta'sir qiladi | Yuqori | Avval bitta manba, eski ustunlar uchun o'tish davri (accessor yoki sinxronlash) |
| R6 | English moduli ~50% kod; yashirishda bog'liqliklar (FK, provider, `user_achievements`) | O'rta | Faqat route, provider va menyu darajasida flag; jadvallarga tegilmaydi |
| R7 | Shanba kuni yuzlab foydalanuvchi bir vaqtda | Yuqori | Variantlarni oldindan generatsiya qilish (queue), javoblarni batch'da saqlash, Redis. Hozir `.env`da Redis yo'q (Q-10) |
| R8 | Bolalar ma'lumotlari (ota-ona roziligi, ommaviy reyting) | Yuqori | `consent_at`, taxallus, loglarda shaxsiy ma'lumot yo'q |
| R9 | AI qoralamada noto'g'ri javob | O'rta | `is_correct = NULL` majburiy; to'g'ri variantsiz approve bloklanadi (test bilan) |
| R10 | Ko'p sahifa va metod yo'q (44 route, ~20 Vue sahifa) | O'rta | Flag bilan yashiriladi yoki navbatma-navbat yaratiladi |

---

## 5. Savollar (tasdiq kerak)

1. **Kod bazasi.** Ish `origin/main` (93f3ef4) asosida davom etadimi? Lokal `C:\xampp\htdocs\edulife` eski nusxa, undagi commit qilinmagan migratsiya o'zgarishlari esa `origin/main` da yo'q. **Tavsiya:** `origin/main`. Bosqich 1 da 38 ta kerakli audit tuzatishini shu branch'ga ko'chiraman (`cherry-pick -x`, upstream o'zi tuzatgan joylarda upstream versiyasi olinadi).
2. **Fizika yo'nalishi va dublikat.** `math` va `mathematics` yo'nalishlarini bitta `math`ga birlashtirib, `physics` qo'shaymi? Kurslar faqat shu ikki yo'nalishda qoladimi?
3. **XP, coin va ELO uchun yagona manba.** **Tavsiya:** `student_profiles`, chunki servislarning ko'pi unga yozadi. `users.*` ustunlari o'tish davrida faqat o'qish uchun qoladi. ELO uchun `user_subject_ratings (user_id, subject_id, elo)` jadvali.
4. **`question_bank`ni kengaytirish** (3.2): `section_type` va `correct_answer` ni nullable qilishga ruxsat bormi? Bu ustun turini o'zgartirish, alohida migratsiyada qilinadi.
5. **Ota-ona.** `users.role` ga `guardian` qo'shilsinmi? Bitta telefon bitta akkauntmi (ota-ona va o'quvchi bitta telefonni ishlatsa nima bo'ladi)?
6. **O'quvchi profili.** Sinf, maktab, tuman, maqsad va fan `student_profiles` ga qo'shilsinmi? Tuman va maktablar ma'lumotini qayerdan olamiz (rasmiy ro'yxat bormi)?
7. **Olimpiada dvigateli.** Buzuq olimpiada oqimini (7 ta yo'q metod, ikki marta sektsiya yaratish, device lock) shu loyiha doirasida tuzataymi yoki u keyingi navbatga qoladimi? **Tavsiya:** anti-cheat qismini Exam uchun tuzatish, olimpiada UI'ni esa Bosqich 2b da.
8. **Battle va turnir.** Hozircha flag bilan yashirib, keyin `question_bank` bilan qayta qursam bo'ladimi?
9. **Formulalarni render qilish.** Natija kartasi va PDF'larda formula kerakmi? Kerak bo'lsa server tomonda headless brauzer (Browsershot, Node + Chromium) kerak bo'ladi — serverda bu imkoniyat bormi?
10. **Infratuzilma.** Production'da Redis, queue worker (supervisor) va cron bormi? Shanba yuklamasi uchun ular zarur.
11. **AI.** Anthropic API kaliti va byudjet bormi? Model `config/exam.php` da bo'ladi. Manba hujjatlar (qo'llanmalar) qaysi formatda: PDF, Word yoki skan?
12. **Telegram.** Bot tokeni, domen (Mini App HTTPS talab qiladi) va kutubxona: `nutgram/nutgram` (Laravel integratsiyasi yaxshi, faol rivojlanadi) yoki `irazasyed/telegram-bot-sdk`? **Tavsiya:** nutgram.
13. **`maatwebsite/excel`.** Import uchun 3.1 ga yangilaymi yoki olib tashlab, CSV/JSON importni `league/csv` (yoki o'zimizning parser) bilan qilaymi? **Tavsiya:** 3.1 — Excel shablonlari ustoz uchun qulay.
14. **Til.** `lang/uz/*` ga faqat yangi Exam matnlarimi yoki mavjud hardcode qilingan UI matnlari ham ko'chirilsinmi? **Tavsiya:** hozircha faqat yangi matnlar.

---

## 6. Keyingi bosqichga tayyormi: **YO'Q — tasdiq kutilmoqda**

Prompt bo'yicha Bosqich 0 dan keyin to'xtash kerak. Kamida 1, 2, 3, 4 va 7-savollarga javob kelgach, Bosqich 1 (poydevor tuzatishlari) boshlanadi. Uning birinchi qadamlari:
1. Test infratuzilmasini tuzatish: MySQL-only migratsiya, factory'lar, `withoutVite`, fillable.
2. Audit tuzatishlarini ko'chirish (38 ta commit).
3. `TestAttemptController` va `max_attempts` xatolari, admin approve/reject.
4. `composer update` va `maatwebsite/excel` qarori.
