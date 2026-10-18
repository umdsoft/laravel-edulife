# EDULIFE — Audit hisoboti

**Sana:** 2026-10-18  ·  **Branch:** `main`  ·  **Audit oralig'i:** `382e8ca..16c1cbe` (44 commit, push qilinmagan)

---

## 1. Qisqacha xulosa

Loyiha katta hajmdagi, ko'p funksiyali LMS (admin / o'qituvchi / talaba panellari, kurslar, testlar, battle, turnir, coin-do'kon, sertifikat, to'lovlar). Kod tez yozilgan, lekin **kod va ma'lumotlar bazasi sxemasi bir-biridan ajralib ketgan**: ko'p controller va servislar mavjud bo'lmagan ustun, relation, route nomi yoki Vue sahifaga murojaat qilardi. Audit boshida:

- Deyarli butun **o'qituvchi paneli** ishlamas edi (`$this->authorize()` 76 joyda fatal xato beradi, policy umuman yo'q edi).
- Frontenddagi **`route()` helper** 157 ta route nomidan faqat 10 tasini bilardi, qolganlari `#` qaytarardi. Shu sabab talaba va o'qituvchi panelidagi ko'pchilik link va formalar ishlamasdi.
- **Test baholash** mavjud bo'lmagan `pass_rate` ustunini ishlatardi, shuning uchun har qanday urinish, hatto 0% ham, "o'tdi" deb hisoblanib XP berardi.
- Jiddiy xavfsizlik teshiklari bor edi:
  - o'qituvchidan adminga o'tadigan **stored XSS**;
  - pullik dars kontentining **ochiq oshkor bo'lishi**;
  - admin super_admin'ni egallay olardi;
  - bloklangan foydalanuvchi tizimga kira olardi;
  - coin va mukofotlarni **ikki marta olish** (race condition);
  - `.php` fayl yuklash imkoni.

**Audit davomida** 44 ta kichik commit qilindi: Critical va High darajadagi topilmalarning ko'pchiligi tuzatildi va har biriga test yozildi. Testlar soni **2 tadan 103 ta PHP va 6 ta frontend testga** ko'tarildi. Composer'da zaif paket qolmadi, npm'da 24 tadan 7 ta qoldi.

**Umumiy baho:** audit oldidan **3/10**, hozir **5.5/10**.
- Asosiy oqimlar (auth, rollar, kurs ko'rish, test topshirish, mukofotlar, sertifikat, o'qituvchi CRUD) endi ishlaydi va testlar bilan himoyalangan.
- Ammo hali ham 20 ta Inertia sahifa yo'q, bir nechta biznes-mantiq qarorlari kutilmoqda (6-bo'lim), va MySQL'da integratsion test yo'q.

---

## 2. Stek va arxitektura

| Qatlam | Texnologiya |
|---|---|
| Backend | PHP 8.2.12 (XAMPP, Windows), **Laravel 12.70** (auditda 12.41 dan yangilandi), Sanctum 4, spatie/permission 6 (deyarli ishlatilmaydi — rol `users.role` ustunida), spatie/medialibrary, dompdf, maatwebsite/excel 3.1, simple-qrcode |
| Frontend | **Inertia 2 + Vue 3.5**, Vite 7, Tailwind 3, Headless UI, Chart.js, hls.js, vuedraggable, **Ziggy 2.6** (auditda qo'shildi) |
| DB | MySQL (`.env`), testlar SQLite `:memory:` da |
| Real-time | Reverb (`BROADCAST_CONNECTION=reverb`), `Battle*` eventlar |
| Navbat | `database` driver; jobs: TranscodeVideo, GenerateCertificatePdf, CalculateAllTeacherScores va boshqalar |

**Tuzilma:**
- 74 ta controller (`Admin/`, `Teacher/`, `Student/`, `Auth/`), 76 ta model (UUID kalitlar), 17 ta servis, 47 ta FormRequest, 93 ta migratsiya, 318 ta route, 104 ta Vue sahifa.
- Route fayllari: `routes/{web,auth,admin,teacher,student}.php`. `routes/api.php` ulanmagan.
- Avtorizatsiya ikki qatlamli:
  - `role:` middleware (`RoleMiddleware`) guruh darajasida tekshiradi;
  - auditda qo'shilgan `app/Policies/*` resurs egaligini tekshiradi.
- Gamifikatsiya ma'lumotlari ikki joyda saqlanadi: `users` (xp_total, coin_balance, level) va `student_profiles` (xp, coins, level). Bu 6-bo'limdagi Q-3 qarorini talab qiladi.

**Boshlang'ich holat (audit oldidan):**
- **Testlar:** 2 ta, shundan 1 tasi yiqilardi (`/` → 302).
- **Migratsiyalar:** SQLite'da yiqilardi, shuning uchun hech qanday DB test yozib bo'lmasdi.
- **`php artisan route:list`:** crash bo'lardi (`XPController` import qilinmagan).
- **Build:** o'tardi, lekin bitta 1.9 MB'lik JS chunk chiqardi.
- **`composer audit`:** 19 paketda advisory (critical: phpspreadsheet; high: laravel/framework, guzzle, commonmark va boshqalar).
- **`npm audit`:** 24 ta (2 critical, 18 high).

---

## 3. Topilgan muammolar jadvali

Holat: ✅ Tuzatildi · ⚠️ Qisman tuzatildi · 🟡 Qaror kerak · ⏳ Ochiq (texnik qarz)

| # | Jiddiylik | Kategoriya | Fayl:qator | Tavsif | Holat |
|---|---|---|---|---|---|
| 1 | Critical | AuthZ | `app/Http/Controllers/Controller.php` | `AuthorizesRequests` trait va policy'lar yo'q edi, shuning uchun 76 ta `authorize()` chaqiruvi fatal xato berardi (o'qituvchi paneli ishlamasdi) | ✅ |
| 2 | Critical | XSS | `Pages/Admin/Courses/Show.vue:61`, `Student/Courses/Show.vue:88`, `Learn/Lesson.vue`, `AnnouncementCard.vue`, `Questions/Index.vue` | O'qituvchi matni `v-html` bilan chiqarilardi: o'qituvchidan adminga stored XSS, undan super_admin yaratish mumkin edi | ✅ |
| 3 | Critical | Access control | `Student/CourseController.php` `show()` | Har qanday talaba pullik kursning `video_url`/`text_content`ini (va draft kurslarni) JSON'da ko'ra olardi | ✅ |
| 4 | Critical | Frontend | `resources/js/app.js:8-41` | Qo'lda yozilgan `route()` 147 ta route nomida `#` qaytarardi | ✅ (Ziggy) |
| 5 | Critical | Biznes-mantiq | `TestEvaluationService.php:60` | `pass_rate` null bo'lgani uchun har qanday ball "o'tdi" hisoblanib XP berilardi | ✅ |
| 6 | Critical | Runtime | `TestAttemptController.php` | Mavjud bo'lmagan `is_active`, `randomize_questions`, `content`, `show_answers_after` ustunlari ishlatilardi, test boshlab bo'lmasdi | ✅ |
| 7 | Critical | Runtime | `CertificateService.php` | Sertifikat yaratish har doim yiqilardi va kursni tugatish so'rovi 500 berardi | ✅ |
| 8 | Critical | Routing | `routes/student.php` | 5 ta controller import qilinmagan edi: 10 ta route 500 berardi, `route:list` crash bo'lardi | ✅ |
| 9 | Critical | Runtime | `Models/LessonProgress.php` | Model noto'g'ri jadvalga (`lesson_progress`) bog'langan edi, butun o'qish/progress oqimi ishlamasdi | ✅ |
| 10 | Critical | Runtime | `Teacher/Dashboard|Earnings|AnalyticsController` | `teacher_earnings.amount` ustuni yo'q, MySQL'da sahifalar 500 berardi | ✅ |
| 11 | Critical | AuthN | `database/seeders/AdminUserSeeder.php` | `admin123` / `moderator123` standart parollari har qanday muhitda seed qilinardi | ✅ |
| 12 | High | Priv. escalation | `Admin/UserController.php`, `Store/UpdateUserRequest` | Oddiy admin super_admin parolini almashtira, o'zini super_admin qila yoki uni o'chira olardi | ✅ |
| 13 | High | AuthN | `AuthController.php:30`, `RoleMiddleware.php` | Bloklangan foydalanuvchi tizimga kirardi; login/register'da throttle yo'q edi; telefon raqamni enumeratsiya qilish mumkin edi | ✅ |
| 14 | High | Race condition | `MissionService`, `AchievementService`, `CoinService` | Mukofotni ikki marta olish, coin balansi manfiyga tushishi, `stock` manfiy bo'lishi mumkin edi | ✅ |
| 15 | High | Biznes-mantiq | `AchievementService.php:79,122` | Mavjud bo'lmagan `unlocked_at`/`is_claimed` ishlatilardi: unlock yiqilardi, claim cheksiz XP berardi | ✅ (migratsiya + atomik claim) |
| 16 | High | Race condition | `Student/TournamentController.php` | Entry fee ikki marta yechilardi yoki ikki marta qaytarilardi | ✅ |
| 17 | High | Data exposure | `Student/BattleController.php:117` | Barcha raundlar `is_correct` bilan klientga yuborilardi; `time_taken` klientdan olinardi | ✅ |
| 18 | High | Race condition | `BattleController::submitAnswer` | Raund ikki marta hal qilinishi mumkin edi (ikki barobar ELO/XP) | ✅ |
| 19 | High | File upload | `StoreAttachmentRequest`, `VideoController` | Fayl turiga cheklov yo'q edi (`.php`/`.html` public diskka tushardi), `upload_id` orqali path traversal mumkin edi | ✅ |
| 20 | High | IDOR (latent) | `routes/teacher.php`, shallow controllerlar | Nested route'larda child parent'ga tegishliligi tekshirilmasdi; shallow amallar bo'sh `Course` olardi | ✅ |
| 21 | High | Privacy | `Models/User.php` `$hidden` | Telefon (login), email, `failed_login_attempts` reviews, Q&A, battle va do'stlar ro'yxatida boshqalarga ko'rinardi | ✅ |
| 22 | High | Privacy | `Student/SupportController.php:111` | Talabaga support xodimlarining ichki eslatmalari (`is_internal_note`) ko'rinardi | ✅ |
| 23 | High | Ikki barobar mukofot | `bootstrap/app.php`, `EventServiceProvider` | Event discovery tufayli XP listener'lari ikki marta ro'yxatdan o'tardi (ikki barobar XP, ikki sertifikat) | ✅ |
| 24 | High | Runtime | `Teacher/CourseController.php` | `level`, `what_will_learn`, `thumbnail_url` noto'g'ri ustunlar edi: rasm saqlanmasdi, kursni ko'rikka yuborib bo'lmasdi | ✅ |
| 25 | High | Runtime | `Models/Lesson.php` | `video()` va `lessonProgress()` relation'lari yo'q edi | ✅ |
| 26 | High | Runtime | `Student/CourseController.php:132` | `courses.views_count` ustuni yo'q edi, kurs sahifasi 500 berardi | ✅ (migratsiya) |
| 27 | High | Runtime | `StudentQnaController.php:55` | Mavjud bo'lmagan `NewQuestionNotification` sabab savol saqlangach 500 berardi | ✅ |
| 28 | High | Runtime | Admin: Report, Leaderboard, Question, CertificateTemplate, Payment, Review, PromoCode, DailyMission, TeacherPayout, Subscription | Mavjud bo'lmagan ustun/relation'lar, noto'g'ri enum qiymatlari | ✅ |
| 29 | High | Runtime | `Admin/TeacherPayoutController.php:51` | Bank hisobi hech qachon `is_verified` bo'lmasdi, shuning uchun o'qituvchi pul yecha olmasdi | ✅ |
| 30 | High | Frontend | `Learn/Lesson.vue`, `Tests/Attempt.vue` | JSON endpoint'larga `router.post` yuborilardi (Inertia xato modali, test natijasiga o'tilmasdi) | ✅ |
| 31 | High | Frontend | 13 ta Vue fayl | Mavjud bo'lmagan route nomlari ishlatilardi (shallow prefix) | ✅ |
| 32 | High | Runtime | `VideoTranscodingService.php:300` | ffmpeg standart 60 soniyalik timeout'da o'ldirilardi | ✅ |
| 33 | High | Runtime | 20 ta `Inertia::render` | Vue sahifalari mavjud emas (Battle/Match, Shop/*, XP/Index, Support/Show, Teacher/Courses/Show va boshqalar) | 🟡 Q-7 |
| 34 | High | Biznes-mantiq | `Teacher/EarningsController::withdraw` | `teacherProfile->balance` ustuni yo'q, shuning uchun pul yechish hech qachon ishlamaydi | 🟡 Q-1 |
| 35 | High | Infra | XAMPP `DocumentRoot = htdocs` | `/edulife/.env`, `storage/logs`, `vendor` va `storage/app/public` veb orqali ochiq | 🟡 Q-2 |
| 36 | High | Data integrity | `User` va `StudentProfile` | XP, coin va level ikki joyda saqlanadi, ikkita turli level formulasi bor | 🟡 Q-3 |
| 37 | Medium | Biznes-mantiq | `LessonProgressController` | Progressni soxtalashtirish mumkin: barcha darslarga `/complete` yuborib sertifikat olish | 🟡 Q-4 |
| 38 | Medium | Biznes-mantiq | `Student/TestController::checkPrerequisites` | `lesson_test`/`module_id` mavjud emas, shuning uchun prerequisite tekshiruvi amalda ishlamaydi | 🟡 Q-5 |
| 39 | Medium | Abuse | `StudentQnaController::store/upvote`, `StudentReviewController::store` | Har savolga +5 XP (limitsiz), cheksiz upvote, review'da progress tekshiruvi yo'q | 🟡 Q-6 |
| 40 | Medium | Runtime | `Teacher/CourseController.php` | `discount_price` saqlanmaydi (sxemada `original_price` bor, ma'nosi farqli) | 🟡 Q-8 |
| 41 | Medium | Validatsiya | `Admin/UpdateUserRequest.php` | Status qoidasi `inactive`ni qabul qiladi, enum'da esa `pending|active|blocked` bor | 🟡 Q-9 |
| 42 | Medium | Notifications | `QnaController:59`, `SendAnnouncementNotificationsJob` | `$user->notify()` database kanali maxsus `notifications` jadvaliga mos emas | 🟡 Q-10 |
| 43 | Medium | Settings | `SettingsController::updateAccount` | Telefon formati va unikalligi tekshirilmasdi | ✅ (qayta tasdiqlash — Q-11) |
| 44 | Medium | Runtime | `SettingsController::deleteAccount` | Mavjud bo'lmagan `is_active` yozilardi, akkaunt o'chmasdi | ✅ |
| 45 | Medium | Runtime | `User::$fillable` | Profil va maxfiylik sozlamalari jimgina tashlab yuborilardi | ✅ |
| 46 | Medium | SQLi pattern | `CourseRecommendationService.php:27-40` | `orderByRaw` ichiga qiymatlar interpolatsiya qilinardi | ✅ |
| 47 | Medium | Performance | `Student/CourseController::show` | Reyting taqsimoti uchun 5 ta so'rov | ✅ (1 groupBy) |
| 48 | Medium | Performance | Frontend bundle | Bitta 1.9 MB chunk | ✅ (entry 338 kB, 138 chunk) |
| 49 | Medium | Performance | Teacher Dashboard/Earnings/Analytics, TeacherScoreService | Oyma-oy loop ichida so'rovlar (6–30 ta), har o'qituvchiga ~25 ta so'rov, kesh yo'q | ⏳ |
| 50 | Medium | Race | `MissionService::generateDailyMissions`, `SupportTicket::generateTicketNumber` | Avval tekshirib keyin yoziladi, unique index'ga uriladi | ⏳ |
| 51 | Medium | Scheduling | `routes/console.php` | `AutoSubmitExpiredTestJob`, `ProcessBattleMatchmaking`, `GenerateDailyMissions` hech qachon ishga tushmaydi | ⏳ |
| 52 | Medium | Config | `.env` | `APP_DEBUG=true`, `SESSION_DRIVER` ikki marta yozilgan, `SESSION_SECURE_COOKIE` yo'q | 🟡 Q-12 |
| 53 | Medium | Deps | `npm audit` | 7 ta qoldi (braces/micromatch/chokidar/postcss-selector-parser) — tuzatish uchun Tailwind 3 → 4 kerak | 🟡 Q-13 |
| 54 | Low | Runtime | `CertificateService::generatePdf` | PDF o'rniga oddiy matn faylini yozadi (dompdf o'rnatilgan, lekin ishlatilmaydi) | ⏳ |
| 55 | Low | Crypto | `Certificate::generateVerificationCode` | `md5(uniqid())` — vaqtdan taxmin qilish mumkin edi | ✅ (`Str::random`) |
| 56 | Low | Rate limit | `/verify/{code}` | Throttle yo'q edi, bekor qilingan sertifikat ham ko'rsatilardi | ✅ |
| 57 | Low | AuthZ | `Teacher/VideoController::status` | Egalik tekshiruvi yo'q edi | ✅ |
| 58 | Low | Performance | `course_qna.parent_id` | Indeks yo'q edi | ✅ (migratsiya) |
| 59 | Low | Hygiene | `verify_output.txt` | Bcrypt hash va stack trace bilan git'da va veb'da ochiq edi | ✅ (o'chirildi) |
| 60 | Low | Dead code | `BattleService`, `TournamentService`, `TestAntiCheatService`, `Console/Commands/Verify*`, `BattleQuestion`/`BattleAnswer`/`UserDailyMission`/`UserStreak` modellari | Ishlatilmaydi yoki mavjud bo'lmagan jadvallarga murojaat qiladi | ⏳ |
| 61 | Low | Runtime | `Admin/SystemLogController` | `spatie/activitylog` o'rnatilmagan | 🟡 Q-14 |
| 62 | Low | Code style | 284 fayl | `pint --test` bo'yicha uslub nomuvofiqligi (ommaviy formatlash diff'ni shishiradi) | ⏳ |
| 63 | Low | Info leak | `config/ziggy.php` | `@routes` barcha admin route URI'larini mehmonlarga ham beradi | ⏳ |
| 64 | Low | Commission | `Admin/TeacherController:170` va `TeacherScoreService` | Komissiya stavkalari ikki joyda, bir-biriga zid (10/15/18/20 va 30/30/25/20) | 🟡 Q-15 |
| 65 | Low | Video units | `VideoTranscodingService` va `Lesson::formatted_duration` | Davomiylik soniyada yoziladi, accessor esa uni daqiqa deb o'qiydi | 🟡 Q-16 |

---

## 4. Bajarilgan tuzatishlar (commit'lar)

**Dependencies va infra**
- `72cd95e` fix(db): drop username index before column so migration runs on SQLite
- `72a1c1c` chore(deps): maatwebsite/excel 1.x → 3.1 (ishchi papkada oldindan bor bo'lgan o'zgarish, alohida commit qilindi)
- `d87b1fa` chore(deps): composer update (semver doirasida) — Laravel 12.70, guzzle, phpspreadsheet, commonmark; `composer audit` toza
- `1f517f6` test(frontend): vitest + test-utils; `npm audit fix` (24 → 7)
- `81abd2f` perf(db): `course_qna.parent_id` indeksi; `verify_output.txt` o'chirildi

**Xavfsizlik va avtorizatsiya**
- `20959d7` fix(authz): `AuthorizesRequests` va 8 ta policy (Course, TestAttempt, LessonNote, Certificate, Review, Enrollment, TeacherBankAccount, User)
- `4b6f947` fix(teacher): `scoped()`/`scopeBindings()` va shallow controller'lar parent'ni child'dan oladi
- `a4fcfb3` fix(auth): throttle, bloklangan foydalanuvchini rad etish, sessiyani regenerate qilish, enumeratsiya yopildi
- `7260539` fix(admin): admin rollarini faqat super_admin beradi va boshqaradi
- `ded844e` fix(student): kurs sahifasi pullik kontent va PII oshkor qilmaydi; `views_count` migratsiyasi
- `52abedd` fix(battle): javoblar oshkor bo'lmaydi, raund qulflanadi, vaqt serverda hisoblanadi
- `cdfb0e9` fix(support): ichki eslatmalar yashirildi
- `a8c55a9` fix(uploads): fayl turlari allowlist'i, chunk upload sanitizatsiyasi
- `f44b124` fix(seed): local muhitdan tashqarida standart admin paroli yo'q
- `1683682` fix(security): v-html → matn (stored XSS)
- `0497554` + `d99048b` fix(privacy): User serializatsiyasida phone/email va boshqa maydonlar yashirildi
- `3f997a3` fix(security): `orderByRaw` bindings
- `b5980bc` fix(teacher): video status egalik tekshiruvi

**Biznes-mantiq va runtime**
- `dfe744a` fix(routes): yetishmayotgan importlar
- `4cbefc9` fix(rewards): atomik claim va spend; `user_achievements.is_claimed` migratsiyasi
- `6a28984` fix(models): LessonProgress jadvali, Lesson relation'lari
- `db358dc` fix(tests): `passing_score`, haqiqiy ustunlar, atomik submit
- `095b014` fix(tournament): atomik entry fee
- `1486e7a` fix(events): listener'lar endi ikki marta ro'yxatdan o'tmaydi
- `b3e25b6` fix(certificates): generatsiya va verify
- `d933255`, `16c1cbe` fix(teacher): earnings `net_amount`
- `20b2a23`, `cf732ab` fix(teacher/lessons): kurs va dars maydonlari haqiqiy ustunlarga
- `c514b2d` fix(settings): telefon validatsiyasi, fillable maydonlar, akkauntni o'chirish
- `d13dfad` fix(qna): mavjud bo'lmagan notification klassi
- `4e6ce2c`, `7d7c035`, `a3d771b`, `5c40c2b`, `160273f` fix(admin): admin sahifalaridagi sxema xatolari
- `d7e7765` fix(notifications): mark-all-read
- `05b866d` fix(video): ffmpeg timeout, `video_duration`

**Frontend**
- `2b9c4d8` feat(frontend): Ziggy va lazy sahifalar
- `9400e14` fix(frontend): route nomlari
- `102428c` fix(frontend): axios, listener va polling tozalash
- `3fa20de` fix(frontend): importlar va null-safe murojaatlar
- `a1265a2` test: ExampleTest to'g'rilandi

---

## 5. Testlar

| | Oldin | Keyin |
|---|---|---|
| PHP testlar (PHPUnit) | 2 (1 ta yiqilgan) | **103 o'tadi**, 599 assertion |
| Frontend testlar (vitest) | 0 | **6 o'tadi** (3 fayl) |
| SQLite'da migratsiya | yiqilardi | o'tadi |
| `route:list` | crash | 318 route |
| Build | 1 chunk, 1.9 MB | entry 338 kB, 138 lazy chunk |
| `composer audit` | 19 paketda advisory | **0** |
| `npm audit` | 24 (2 critical) | 7 (0 critical) |

**Coverage:** o'lchanmadi, chunki PHP'da Xdebug ham, PCOV ham yo'q. Boshlang'ich coverage amalda ~0% edi (bitta trivial test). Hozir qamrab olingan oqimlar:
- **Auth:** login, logout, register, throttle, bloklangan foydalanuvchi, enumeratsiya.
- **Rollar:** har bir panel uchun mehmon, talaba va o'qituvchi kirishi.
- **O'qituvchi:** kurs, modul va dars CRUD; IDOR; nested scoping; upload xavfsizligi; earnings va dashboard.
- **Talaba:** kurs ko'rish (kontent oshkor bo'lmasligi); test oqimi (baholash, ikki marta topshirish, boshqa talaba); mission, achievement, shop (race); turnir; battle; support; Q&A; sozlamalar.
- **Admin:** foydalanuvchilarni boshqarish (super_admin himoyasi), listing sahifalari, sertifikat, daily mission, promo code, payout.
- **Boshqa:** sertifikat generatsiyasi va verify, event'lar bir martalik, seeder, User serializatsiyasi.

Yangi test fayllari:
- `tests/Feature/Auth/*`
- `tests/Feature/Teacher/*`
- `tests/Feature/Student/*`
- `tests/Feature/Admin/*`
- `tests/Feature/{CertificateTest,AdminSeederTest,UserSerializationTest,NotificationMarkAllReadTest,VideoTranscodingServiceTest}.php`
- `tests/Unit/EventRegistrationTest.php`
- `tests/Concerns/CreatesCourses.php`
- `resources/js/__tests__/*`

**Ishga tushirish:** `php artisan test`, `npx vitest run`.

> ⚠️ SQLite noma'lum ustunni (qo'shtirnoq ichidagi) xato emas, oddiy matn deb qabul qiladi, shuning uchun ba'zi sxema xatolari SQLite testlarida ko'rinmaydi. Tavsiya: CI'da MySQL bilan ham test ishga tushiring.

**Statik tahlil:**
- **PHPStan 2.1 (level 0–2, Larastan'siz):** 4 ta haqiqiy xato topildi. 2 tasi tuzatildi; `BattleService` o'lik kod; `SystemLogController` uchun Q-14. Qolgan ~1680 ta xabar Larastan yo'qligi sababli Eloquent bo'yicha false-positive.
- **Pint:** 284 faylda uslub farqi bor. Ommaviy formatlash qilinmadi, chunki u diff'ni shishiradi.

---

## 6. Qaror kerak

| ID | Masala | Tavsiyam |
|---|---|---|
| **Q-1** | O'qituvchi balansi: `teacher_profiles.balance` ustuni yo'q, `withdraw` doim rad etadi; payout bekor qilinganda pul qaytarilmaydi | Balansni ustunda saqlamang, hisoblang: `SUM(net_amount WHERE status='confirmed') − SUM(payouts WHERE status IN (pending,processing,completed))`. Withdraw'ni `DB::transaction` ichida o'qituvchi qatorini `lockForUpdate` qilib bajaring. Cancel'da balans hisoblangani uchun alohida qaytarish kerak bo'lmaydi |
| **Q-2** | XAMPP `DocumentRoot` loyiha ildiziga qaragan, shuning uchun `.env` va `storage/logs` veb orqali ochiq | Production'da vhost `DocumentRoot` → `edulife/public`. Lokal uchun ildizga `.htaccess` qo'ying: `RewriteRule ^(.*)$ public/$1 [L]`. Video va ilovalarni enrollment tekshiruvi bor controller yoki signed URL orqali bering |
| **Q-3** | XP, coin va level ikki manbada (`users.*` va `student_profiles.*`), ikkita level formulasi bor | Yagona manba — `student_profiles`. `User::addXp/addCoins`ni olib tashlang. `HandleInertiaRequests` profil qiymatlarini ulashsin |
| **Q-4** | Darsni "tugatdim" deb soxtalashtirish orqali sertifikat olish mumkin | Video darsda serverda o'lchangan tomosha vaqti ≥ 80% bo'lsin. Qo'lda `complete` faqat matnli darslarda. Sertifikat uchun yakuniy testdan o'tish talab qilinsin |
| **Q-5** | Test prerequisite'lari (`lesson_test`, `module_id`) sxemaga mos emas | `testable_type/testable_id` asosida qayta yozing va `begin()` ichida ham tekshiring |
| **Q-6** | XP farming (Q&A +5, upvote, review) | Kunlik limit va upvote uchun `(user_id, question_id)` pivot jadvali. Review `store` ichida ham 20% progress tekshiruvi |
| **Q-7** | 20 ta Vue sahifa mavjud emas (`Battle/Match`, `Shop/*`, `XP/Index`, `Coins/History`, `Support/Create|Show|Faq`, `Profile/Edit|Private`, `Settings/Privacy`, `Friends/*`, `Courses/Preview`, `Teacher/Courses/Show`, `Teacher/Courses/Tests/Show`, `Teacher/Earnings/History`, `Teacher/Students/Show`, `Admin/Questions/Show`); `Student/Battles/*` sahifalari esa hech qayerda render qilinmaydi | Avval Battle/Match (matchmaking aynan shu sahifaga yo'naltiradi), Support/Show va Shop/Index sahifalarini yarating. Ishlatilmaydigan route'larni `->only([...])` bilan yoping |
| **Q-8** | `discount_price` va `original_price` semantikasi | Forma uchun `price` = sotuv narxi, `original_price` = chizilgan eski narx bo'lsin. Request'ni `original_price gt:price` qilib o'zgartiring |
| **Q-9** | Admin UI'dagi `inactive` statusi | `pending`ga xaritalang yoki enum'ga `inactive` qo'shing (migratsiya kerak) |
| **Q-10** | Laravel `notify()` database kanali maxsus `notifications` jadvaliga mos emas | Hamma joyda `App\Models\Notification::send()` ishlating. `QuestionAnsweredNotification` va `CourseAnnouncementNotification`ni shu helperga o'tkazing |
| **Q-11** | Telefon raqamni (login) o'zgartirishda parol yoki OTP so'ralmaydi | `current_password` va OTP qayta tasdiqlashini qo'shing (UI ham o'zgaradi) |
| **Q-12** | Production `.env` | `APP_DEBUG=false`, `SESSION_SECURE_COOKIE=true`, `SESSION_ENCRYPT=true`, `SESSION_DRIVER` bir marta. `.env.production` ga tegilmadi |
| **Q-13** | Tailwind 3 → 4 (qolgan 7 ta npm advisory) | Alohida branch'da migratsiya qiling. Advisory'lar faqat build vaqtidagi paketlarda, runtime'ga ta'sir qilmaydi |
| **Q-14** | `SystemLogController` `spatie/laravel-activitylog`ni talab qiladi | Paketni o'rnating yoki route va menyuni olib tashlang |
| **Q-15** | Komissiya stavkalari ikki joyda, bir-biriga zid | Bitta manba qoldiring (`config/edulife.php` yoki `TeacherProfile::getCommissionRate`) |
| **Q-16** | Video davomiyligi birligi (soniya yoki daqiqa) | Saqlashda soniya ishlating, accessor'larni shunga moslang |
| **Q-17** | Daily mission `task_type` uchun uch xil lug'at (forma, seeder, BattleController) | Bitta enum yoki konstanta ro'yxati tanlang; admin formasi va seeder shunga o'tsin |
| **Q-18** | O'qituvchi o'z talabalarining email'ini ko'rishi kerakmi? (hozir yashirin) | Kerak bo'lsa `StudentsController` ichida `makeVisible('email')` qiling |
| **Q-19** | O'chirilgan akkauntning telefoni unique bo'lib qoladi va qayta ro'yxatdan o'tishni bloklaydi | O'chirishda telefonni anonimlashtiring (masalan `del_<id>`) yoki unique qoidani `deleted_at IS NULL` bilan cheklang |

---

## 7. Yo'l xaritasi

**1–2 hafta (barqarorlik):**
1. Q-1, Q-2, Q-7 (Battle/Match, Support/Show, Shop) va Q-12.
2. CI: GitHub Actions'da MySQL service bilan `php artisan test`, `npx vitest run`, `npm run build` va `composer audit`. Hozirgi `tests.yml` Laravel skeleton'iniki va faqat `master` branch'ida ishlaydi — `main`ga moslang.
3. Dev muhitda `Model::shouldBeStrict(!app()->isProduction())` yoqing: jimgina tashlab yuborilgan atributlar va lazy loading xatolari darhol ko'rinadi. Bu auditdagi ko'p xatolarning asosiy sababi edi.
4. Larastan qo'shing (level 5 dan boshlab, baseline bilan).

**1 oy (texnik qarz):**
5. Q-3 (gamifikatsiya uchun yagona manba), Q-10 (notifications), Q-4/Q-5/Q-6 (abuse).
6. O'lik kodni olib tashlang (#60) va ishlatilmaydigan resource route'larni `->only()` bilan cheklang (24 ta route mavjud bo'lmagan metodga ishora qiladi).
7. Performance:
   - dashboard'lardagi oylik loop'larni `selectRaw … groupBy` bilan almashtiring;
   - `TeacherScoreService`da kurs ID'larini bir marta oling, `chunkById` ishlating, natijani keshlang;
   - admin dashboard agregatlarini 5–10 daqiqaga keshlang;
   - `course_views` jadvali uchun pruning yoki kunlik agregatsiya.
8. Rejalashtiriladigan job'lar (#51) uchun `routes/console.php`ga schedule qo'shing; `queue.retry_after` qiymatini transcoding timeout'idan katta qiling.
9. PDF sertifikat uchun dompdf va QR'ni haqiqiy implementatsiya qiling.

**Doimiy:**
10. Monitoring:
    - Sentry yoki Flare (exception'lar uchun);
    - Laravel Pulse (sekin so'rovlar va navbat uchun);
    - `failed_jobs` uchun alert;
    - `/up` health-check'ni monitoringga ulang.
11. Uslub: Pint'ni pre-commit hook yoki CI orqali faqat o'zgargan fayllarga qo'llang.
12. Har bir yangi controller uchun policy va feature test majburiy bo'lsin. Pull request shabloniga "sxema bilan tekshirildimi?" punktini qo'shing.

---

### Eslatmalar

- `git push` qilinmadi; `migrate:fresh`, `db:wipe` va `reset --hard` ishlatilmadi; `.env` va `.env.production` o'zgartirilmadi; MySQL bazaga yozilmadi. Barcha DB ishlari SQLite (`:memory:` va scratchpad) da bajarildi.
- Qo'shilgan 3 ta migratsiya faqat additiv: `user_achievements.is_claimed/claimed_at`, `courses.views_count`, `course_qna` indekslari. Mavjud bitta migratsiyaga (`remove_username_from_teacher_profiles`) faqat `dropIndex` qo'shildi; MySQL'da u allaqachon bajarilgan, shuning uchun ta'siri yo'q.
- Ishchi papkada siz commit qilmagan migratsiya nomlari o'zgarishlari va `DailyMissionSeeder.php` o'zgarishi bor. Ularga tegilmadi va commit qilinmadi; testlar shu holat bilan ishga tushirildi.
- Production'ga chiqarishdan oldin: `php artisan migrate` (3 ta yangi migratsiya), `composer install`, `npm ci && npm run build`.
