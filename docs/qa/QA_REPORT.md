# EDULIFE — QA hisobot (brauzerda E2E test)

Sana: 2026-10-19. Muhit: http://127.0.0.1:8000, DB `edulife_shanba`. Vosita: Python Playwright (chromium, headless), desktop 1440x900 va mobil 375x812.
Skrinshotlar: `C:\Users\Umidbek\AppData\Local\Temp\claude\c--xampp-htdocs-edulife\da0d82b1-2b6f-4bd5-9f4c-89ddc6321d70\scratchpad\qa\shots\`

> Holat: barcha 4 bo'lim tekshirildi (TEACHER / ADMIN / STUDENT / PUBLIC SECTION COMPLETE). QA-T-01, QA-S-05, QA-S-06 test davomida fixer tomonidan tuzatildi va brauzerda qayta tasdiqlandi.

## Xulosa jadvali

| Bo'lim | Tekshirilgan sahifalar (desktop+mobil) | Muammosiz | Xatoli | Xatolar (ID) |
|---|---|---|---|---|
| Ustoz (teacher) | 26 | 20 | 6 | 13 (T-01…T-13) |
| Admin | 34 | 21 | 13 | 14 (A-01…A-14) |
| O'quvchi (student) | ~50 (shu jumladan 8 lab tajribasi) | ~22 | ~28 | 19 (S-01…S-20) |
| Ochiq sahifalar (public) | 4 (/, /login, /register, /verify/{code}) | 4 | 0 (2 kichik izoh) | 2 (P-01, P-02) |

**Severity bo'yicha (jami 48):** Blocker 5 (shundan 3 tasi tuzatildi: T-01, S-05, S-06; ochiq: S-04, S-15) · Critical 11 · Major 12 · Minor 14 · Cosmetic 6.

**Eng muhim ochiq muammolar:** S-15 (o'quvchi Testlar sahifasi 500), S-04 (pullik kursga yozilib bo'lmaydi), S-20 (kursni tugatib/sertifikat olib bo'lmaydi), S-16 (sharh 500), S-01 (8+ oq ekran sahifa), S-02 (battles 500), S-07/S-08 (lab kategoriyalar va yakunlash 500), A-06 (admin user yarata olmaydi), A-12 (olimpiada yaratib bo'lmaydi), A-01/A-02 (Xabarnomalar/Tizim loglari 500), A-11 (admin olimpiada sahifalari komponentlari yo'q).

## Topilgan xatolar

### Ustoz (QA-T-..)

#### QA-T-01 — Testga savol qo'shib bo'lmaydi (har qanday turdagi savol 500)
- **Severity:** Blocker
- **URL:** `POST /teacher/tests/{test}/questions` (sahifa: `/teacher/tests/{test}/questions/create`)
- **Qadamlar:** Ustoz -> Kurs -> Testlar -> test yaratish -> "Yangi savol" -> istalgan tur (Bitta to'g'ri javob, Ko'p to'g'ri javob, Rost/Yolg'on, Bo'sh joyni to'ldirish, Moslashtirish, Tartiblash) -> to'ldirib "Saqlash".
- **Kutilgan:** savol saqlanadi, savollar ro'yxatiga qaytadi.
- **Haqiqiy:** 500 Internal Server Error (Laravel xato oynasi). 6 ta turning hammasida bir xil. Natijada testni faollashtirib ham bo'lmaydi (min. 10/20/30 savol kerak) — o'quvchi uchun testlar butunlay ishlamaydi.
- **Dalil:** `shots/tq_nocorrect.png`; laravel.log:
  `SQLSTATE[HY000]: General error: 1364 Field 'course_id' doesn't have a default value ... insert into questions (type, question_text, points, explanation, sort_order, test_id, id, ...)`
- **Taxminiy sabab:** `QuestionController@store` (app/Http/Controllers/Teacher/QuestionController.php:49) `$test->questions()->create([...])` da `course_id` berilmaydi, `questions.course_id` esa NOT NULL (migration 2024_01_03_000002). `'course_id' => $test->course_id` qo'shish kerak (update/clone joylarini ham tekshiring).

#### QA-T-02 — Kursni nusxalash (clone) 500 beradi
- **Severity:** Major
- **URL:** `POST /teacher/courses/{course}/clone` (Kurs -> Ma'lumotlar -> Sozlamalar -> "Nusxalash")
- **Qadamlar:** Modul+darsli kursda Sozlamalar tabida yangi nom yozib "Nusxalash" -> dialogda "Nusxalash".
- **Kutilgan:** yangi qoralama kurs yaratiladi va uning edit sahifasiga o'tadi.
- **Haqiqiy:** 500 (Laravel xato oynasi), nusxa yaratilmaydi.
- **Dalil:** `shots/tclone_after.png`; laravel.log: `LazyLoadingViolationException: Attempted to lazy load [lessons] on model [App\Models\Module] but lazy loading is disabled` — `app/Services/CourseCloneService.php:68` (`cloneModules`).
- **Taxminiy sabab:** `$course->modules` olinganda `lessons` (va ehtimol `lessons.attachments`, `tests.questions.options`) eager-load qilinmagan. `$course->load('modules.lessons.attachments', 'tests.questions.options')` kabi oldindan yuklash kerak.

#### QA-T-03 — E'lonni tahrirlash saqlanmaydi (xato ko'rsatilmaydi)
- **Severity:** Major
- **URL:** `PUT /teacher/announcements/{id}` (Kurs -> E'lonlar -> ⋮ -> Tahrirlash -> Saqlash)
- **Kutilgan:** e'lon yangilanadi, modal yopiladi, "E'lon yangilandi" toast.
- **Haqiqiy:** modal ochiq qoladi, hech qanday xabar yo'q, o'zgarish saqlanmaydi. Inertia props: `errors = {"published_at":"The published at field is required when publish now is not present."}` — bu maydon edit modalida yo'q, shuning uchun xato ko'rinmaydi.
- **Dalil:** `shots/ta2_after.png`.
- **Taxminiy sabab:** `AnnouncementController@update` `StoreAnnouncementRequest` ni qayta ishlatadi; u yerda `published_at => required_without:publish_now`. Update uchun alohida request (published_at/publish_now ixtiyoriy) kerak.

#### QA-T-04 — Pullik kurs narxsiz yaratiladi (0 so'm, "bepul" emas)
- **Severity:** Major
- **URL:** `POST /teacher/courses` (`/teacher/courses/create`)
- **Qadamlar:** "Bepul kurs" o'chiq, "Narxi" bo'sh qoldirib, qolgan majburiy maydonlarni to'ldirib "Kursni yaratish".
- **Kutilgan:** "Narxi" majburiy xatosi (UI'da yulduzcha bor).
- **Haqiqiy:** kurs yaratiladi, narx `0.00`, `is_free=false` — pullik lekin 0 so'm kurs.
- **Taxminiy sabab:** `StoreCourseRequest`/`UpdateCourseRequest`: `'price' => ['required_without:is_free', ...]`. Frontend har doim `is_free: false` yuboradi, `false` "present" hisoblanadi — qoida ishlamaydi. `required_if:is_free,false` / `exclude_if:is_free,true|required` ishlating.

#### QA-T-05 — Validatsiya xabarlari inglizcha (UI o'zbekcha)
- **Severity:** Minor
- **Joylar:** kurs yaratish/tahrirlash ("The title field is required.", "The description field must be at least 100 characters.", "The discount price field must be less than 100000."), dars ("The title field is required."), bulk darslar ("The lessons.2.title field is required."), test ("The title field is required."), savol ("The question text field is required.", "The options.0.option_text field is required."), profil ("The website field must be a valid URL.", "The avatar field must be an image."), kurs rasmi ("The thumbnail field must be an image.").
- **Taxminiy sabab:** `lang/uz/validation.php` yo'q yoki `APP_LOCALE` != `uz`; maydon nomlari (`attributes`) tarjima qilinmagan.

#### QA-T-06 — "Bir nechta dars qo'shish" saqlangach noto'g'ri tabga qaytaradi
- **Severity:** Minor
- **Qadamlar:** Darslar rejasi -> modul ⋮ -> "Bir nechta dars qo'shish" -> 2 dars -> Saqlash.
- **Haqiqiy:** `/teacher/courses/{id}/edit?tab=curriculum` ga redirect bo'ladi — Edit.vue faqat `basic|media|settings` ni biladi, natijada "Ma'lumotlar" ochiladi, yaratilgan darslar ko'rinmaydi.
- **Kutilgan:** `/teacher/courses/{id}/modules` (Darslar rejasi).
- **Sabab:** `BulkLessonController@store` redirecti.
- Qo'shimcha: bo'sh qolgan 3-qator uchun xato "The lessons.2.title field is required." — foydalanuvchiga tushunarsiz (indeks 0 dan).

#### QA-T-07 — E'lonlar: qadalganlar boshida emas; rejalashtirilgan e'lon belgilanmaydi
- **Severity:** Minor
- **URL:** `/teacher/courses/{id}/announcements`
- **Haqiqiy:** ro'yxat faqat `latest()` bo'yicha — qadalgan e'lon pastda qoladi (modalda "Qadalgan e'lon ro'yxat boshida turadi" deyilgan). "Hozir e'lon qilish" o'chirilib 2026-12-01 ga rejalashtirilgan e'lon kartada `created_at` (bugungi sana) bilan chiqadi, "Rejalashtirilgan" belgisi yo'q.
- **Sabab:** `AnnouncementController@index` -> `orderByDesc('is_pinned')->latest('published_at')` kerak; `AnnouncementCard.vue:78` `created_at` o'rniga `published_at` + kelajakdagi sana uchun badge.

#### QA-T-08 — Testni faollashtirish uchun minimal savol soni oldindan aytilmaydi
- **Severity:** Minor
- **Haqiqiy:** "Faol" switch bosilganda "Testni faollashtirish uchun kamida 30 ta savol kerak" (dars testi 10, modul 20, yakuniy 30). Test yaratish/ro'yxat sahifasida bu talab yozilmagan; test sozlamalarida vaqt limiti/o'tish bali ham ko'rinmaydi (serverda avtomatik 30/45/60 daqiqa, 86/90/85%).
- **Taklif:** test kartasida "x / 10 savol" progress va o'tish bali/vaqtni ko'rsatish.

#### QA-T-09 — Dars videosi "qayta ishlanmoqda 0%" holatida qotib qoladi (muhit)
- **Severity:** Minor (muhitga bog'liq)
- **Qadamlar:** Video dars -> tahrirlash -> video yuklash (14 KB .webm).
- **Haqiqiy:** yuklash muvaffaqiyatli, keyin "Video qayta ishlanmoqda… 0%" doimiy. Serverda `ffmpeg` yo'q va `QUEUE_CONNECTION=database` — queue worker ishlamayapti, transcoding bu muhitda bajarilmaydi. Production'da worker + ffmpeg borligini tekshiring; UI'da "failed"/timeout holati ham bo'lishi kerak.
- **Dalil:** `shots/tl_vid1.png`, `shots/tl_vid2.png`.

#### QA-T-10 — Statistika: soxta "Trafik manbalari" va qirqilgan o'q yozuvlari
- **Severity:** Cosmetic
- **URL:** `/teacher/analytics`
- "Trafik manbalari" kartasi "Namunaviy" belgili qattiq kodlangan 45/30/15/10% ko'rsatadi (haqiqiy ma'lumot emas). Kunlik grafik X o'qida "20....", "24....", "28...." kabi qirqilgan sanalar.
- **Dalil:** `shots/t_d_teacher_analytics.png`.

#### QA-T-11 — Narx maydonlari "100000,00" ko'rinishida
- **Severity:** Cosmetic
- Kurs edit sahifasida narx `decimal:2` qiymati bilan number inputga tushadi (`100000,00`, `80000,00`). Butun son sifatida berish yaxshiroq.

#### QA-T-12 — Ustoz bildirishnomalari bo'sh (sarlavha/matn yo'q); kurs tasdiqlangani haqida xabar kelmadi
- **Severity:** Major
- **URL:** `/teacher/notifications`
- **Haqiqiy:** o'quvchi yozilgach/savol bergach 1 ta bildirishnoma keldi, lekin kartada faqat "1 minut oldin" — matn yo'q (`shots/tn_0.png`). Admin kursni tasdiqlagandan keyin "Natija haqida bildirishnoma olasiz" deyilgan bo'lsa ham tasdiq haqida bildirishnoma yo'q.
- **Sabab:** `App\Models\Notification` ustunlari `title`, `body`, `data`; `Pages/Teacher/Notifications/Index.vue` esa `notification.data?.title || notification.data?.message` o'qiydi (Laravel database-notification formatini kutadi). `title/body` ni ko'rsatish kerak. Shuningdek "Barchasini o'qilgan deb belgilash" bosilgach tugma yo'qoladi (to'g'ri), bitta bildirishnomani bosish `mark-read` qiladi (ishlaydi).

#### QA-T-13 — Savol-javob filtri "Javobsiz" javob berilgan savolni ham ko'rsatadi
- **Severity:** Minor
- **URL:** `/teacher/courses/{id}/qna?status=unanswered`
- **Qadamlar:** o'quvchi savol berdi -> ustoz javob yozdi (statistika "Javobsiz 0" bo'ldi) -> "Javobsiz" tabini bosish.
- **Haqiqiy:** javob berilgan savol baribir ro'yxatda. ("Qadalgan" tabi ham sinovdan keyin to'g'ri ishlashini tekshirish kerak.)

#### QA-T-14 — Sharhlar (reply) sinovdan o'tkazib bo'lmadi
- **Severity:** (bloklangan) — o'quvchi sharh yozishi uchun kursning ≥20% progressi kerak (`StudentReviewController@create`), darslar esa ochilmaydi (QA-S-06). QA-S-06 tuzatilgach qayta tekshirish kerak.

**Testlar/savollar (edit/delete/reorder/turi bo'yicha) QA-T-01 tuzatilgach qayta tekshirilishi kerak.**

TEACHER SECTION COMPLETE

### Admin (QA-A-..)

#### QA-A-01 — "Xabarnomalar" sahifasi 500
- **Severity:** Critical
- **URL:** `GET /admin/notifications` (sidebar -> Tizim -> Xabarnomalar)
- **Haqiqiy:** 500. laravel.log: `Call to undefined method App\Http\Controllers\Admin\NotificationController::index()`.
- **Sabab:** route `admin.notifications.index` mavjud, `Pages/Admin/Notifications/Index.vue` bor, lekin controllerda `index()` metodi yo'q.
- **Dalil:** `shots/a_d_admin_notifications.png`

#### QA-A-02 — "Tizim loglari" sahifasi 500
- **Severity:** Critical
- **URL:** `GET /admin/system-logs` (sidebar -> Tizim loglari)
- **Haqiqiy:** 500. laravel.log: `Class "Spatie\Activitylog\Models\Activity" not found` (`app/Http/Controllers/Admin/SystemLogController.php:18`).
- **Sabab:** `spatie/laravel-activitylog` paketi o'rnatilmagan (composer.json'da yo'q) yoki boshqa log manbasiga o'tkazish kerak.
- **Dalil:** `shots/a_d_admin_system_logs.png`

#### QA-A-03 — Admin kurs sahifasida Daraja "—" va narx noto'g'ri
- **Severity:** Minor
- **URL:** `/admin/courses/{id}`
- **Haqiqiy:** ustoz "O'rta" darajani tanlagan, adminda "Daraja: —". Narx 100 000 / chegirma 80 000 bo'lgan kursda "Narx 80 000 so'm, Chegirma narxi 80 000 so'm" (asl narx ko'rinmaydi). Ro'yxatda ham 80 000.
- **Sabab:** Show.vue `level` o'qiydi, DB ustuni `difficulty`; chegirmada `price` = chegirmali narx, `original_price` = asl narx — Show.vue `original_price` ni ishlatmaydi.

#### QA-A-04 — Dashboard'dagi o'sish foizlari soxta
- **Severity:** Minor
- **URL:** `/admin/dashboard` — "+12%", "+5%", "+8%", "+15%" qattiq kodlangan (Daromad 0 so'm bo'lsa ham "+15%").

#### QA-A-05 — Hisobotlar: yo'nalish nomi ikki marta
- **Severity:** Cosmetic — `/admin/reports` "Kurslar yo'nalishlar bo'yicha" qatorida "Matematika" ham chap ustunda, ham bar ustida takrorlanadi.

#### QA-A-06 — Admin yangi foydalanuvchi yarata olmaydi
- **Severity:** Critical
- **URL:** `POST /admin/users` (`/admin/users/create`)
- **Qadamlar:** Ism, Familiya, Telefon `+998901112233`, Email, Parol x2, Rol=O'quvchi -> Saqlash.
- **Haqiqiy:** foydalanuvchi yaratilmaydi; toast: "Foydalanuvchi yaratishda xatolik: Add fillable property [phone_verified_at] to allow mass assignment on [App\Models\User]." (ichki exception matni foydalanuvchiga ko'rsatilmoqda — bu ham xavfsizlik nuqtai nazaridan noto'g'ri).
- **Sabab:** `Admin\UserController@store` `phone_verified_at` ni `create()` ga beradi, `User::$fillable` da yo'q. `forceFill()` yoki fillable'ga qo'shish; catch blokda umumiy xabar ko'rsatish.

#### QA-A-07 — COIN paketi yaratish 500
- **Severity:** Major
- **URL:** `POST /admin/coin-packages` — laravel.log: `Add fillable property [is_featured] to allow mass assignment on [App\Models\CoinPackage]`.

#### QA-A-08 — Mavjud COIN paketini tahrirlab bo'lmaydi ("Narx butun son bo'lishi kerak")
- **Severity:** Major
- **URL:** `PUT /admin/coin-packages/{id}` — "Boshlang'ich" paketini ochib faqat Bonus COIN ni o'zgartirish -> "Narx butun son bo'lishi kerak.", "Original price butun son bo'lishi kerak."
- **Sabab:** model `price/original_price` ni `decimal:2` ("15000.00") qilib beradi, validatsiya `integer`. Formaga butun son berish yoki `numeric` qoidasi.

#### QA-A-09 — Tarif rejasini yaratish/tahrirlash 500
- **Severity:** Major
- **URL:** `POST /admin/subscription-plans`, `PUT /admin/subscription-plans/{id}` — laravel.log: `Add fillable property [annual_price] to allow mass assignment on [App\Models\SubscriptionPlan]`. Mavjud "Trial" tarifini faqat tavsifini o'zgartirib saqlash ham 500.

#### QA-A-10 — O'qituvchi kurslari soni 0 ko'rsatiladi
- **Severity:** Major
- **URL:** `/admin/teachers`, `/admin/teachers/{id}`
- **Haqiqiy:** Nilufar Usmonovaning nashr qilingan kursi bor, lekin "Kurslar 0", "O'qituvchi hali birorta kurs yaratmagan".
- **Sabab:** `TeacherController` `withCount('courses')` / `$user->courses` ishlatadi, `User::courses()` esa `belongsToMany(Course::class, 'enrollments')` — o'quvchi sifatida yozilgan kurslar. O'qituvchi uchun `taughtCourses()` (`hasMany(Course::class, 'teacher_id')`) kerak.

#### QA-A-11 — Admin olimpiada/savol sahifalari uchun Vue komponentlari yo'q (statik tekshiruv)
- **Severity:** Critical (sahifalar oq ekran bo'ladi)
- `Inertia::render` qilinadi, lekin fayl mavjud emas: `Admin/Olympiad/Edit`, `Admin/Olympiad/Grading/Queue`, `Admin/Olympiad/ParticipantDetails`, `Admin/Olympiad/Registrations`, `Admin/Questions/Show`. (Brauzerda olimpiada bo'limida tasdiqlanadi — pastga qarang.)

#### QA-A-12 — Olimpiada yaratib bo'lmaydi: "Turi" ro'yxati bo'sh, turlarni boshqarish UI yo'q
- **Severity:** Critical
- **URL:** `/admin/olympiads/create`
- **Haqiqiy:** "Turi" (majburiy) select'da faqat "Tanlang"; `olympiad_types` jadvali bo'sh (faqat `OlympiadSeeder` to'ldiradi), admin panelda tur/bosqich/seriya yaratish sahifasi yo'q. Natijada olimpiada yaratish, ko'rish, monitor, baholash va o'quvchi olimpiada oqimini umuman tekshirib/ishlatib bo'lmaydi.
- **Qo'shimcha:** bo'sh formada xatolar aralash tilda: "Olympiad type id to'ldirilishi shart.", "Olympiad start at to'ldirilishi shart." (attribute nomlari tarjima qilinmagan).
- **Taklif:** olimpiada turlari uchun CRUD yoki migratsiyada default turlar.

#### QA-A-13 — Tizim sozlamalari saqlanmaydi (toast "saqlandi" deydi)
- **Severity:** Major
- **URL:** `PUT /admin/settings`
- **Qadamlar:** XP tabida "Dars uchun" 30 -> 33 -> Saqlash -> "Sozlamalar saqlandi!" -> sahifani yangilash.
- **Haqiqiy:** qiymat yana 30. So'rov: `{"settings":[{"key":"xp.xp_per_lesson","value":33}, ...]}`.
- **Sabab:** frontend kalitni `group.key` (`xp.xp_per_lesson`) ko'rinishida yuboradi, `SettingController@update` esa `Setting::where('key', $settingData['key'])->update(...)` — DB'dagi kalit `xp_per_lesson`, 0 qator yangilanadi. Kalitlarni moslashtirish va 0 qator yangilanganda xato qaytarish.

#### QA-A-14 — Savollar banki: "Ko'rish" modalida variantlar/to'g'ri javob ko'rsatilmaydi
- **Severity:** Minor — `/admin/questions` -> ⋮ -> Ko'rish: faqat tur, ball, test nomi va savol matni; javob variantlari va to'g'ri javob yo'q (moderatsiya uchun kerak). Shuningdek `Admin/Questions/Show` render qilinadigan sahifa fayli yo'q (QA-A-11).

#### QA-A-15 — Tekshirib bo'lmagan admin oqimlari
- Kursni rad etish (Rad etish modal) — yagona kurs allaqachon tasdiqlangan; nashr qilingan kurs sahifasida tasdiqlash/rad etish/o'chirish tugmalari yo'q (admin nashrdagi kursni yashira/o'chira olmaydi — UI'da `admin.courses.destroy` uchun tugma topilmadi).
- To'lovlar/obunalar/sertifikatlar tafsilot sahifalari — ma'lumot yo'q (to'lov tizimi ishlamaydi, sertifikat olinmaydi).
- Olimpiada show/monitor/grading — olimpiada yaratib bo'lmaydi (QA-A-12).

ADMIN SECTION COMPLETE

### O'quvchi (QA-S-..) — dastlabki crawl natijalari

#### QA-S-01 — Bir qator o'quvchi sahifalari bo'sh oq ekran (Vue page komponenti yo'q)
- **Severity:** Critical
- **URL'lar (200 qaytadi, lekin body bo'sh):** `/student/profile/edit` (Student/Profile/Edit), `/student/settings/privacy` (Student/Settings/Privacy), `/student/friends/followers` (Student/Friends/Followers), `/student/friends/following` (Student/Friends/Following), `/student/support/faq` (Student/Support/Faq), `/student/support/create` (Student/Support/Create), `/student/battles/history` (Student/Battle/History), `/student/coins` (Student/Coins/History).
- Statik tekshiruvda yana: `Student/Battle/Match` (battle o'yini), `Student/Courses/Preview` (bepul dars preview), `Student/Olympiad/Certificates`, `Student/Profile/Private`, `Student/Support/Show` — fayllar yo'q.
- **Diqqat:** mavjud papka `Pages/Student/Battles/` (ko'plik), controller esa `Student/Battle/...` (birlik) render qiladi.
- **Dalil:** `shots/blank__student_profile_edit.png` va h.k.; `JSON.parse(#app.dataset.page).component` = yuqoridagi nomlar, konsolda xato ham yo'q (Inertia resolve jim yiqiladi).

#### QA-S-02 — Battles sahifasi 500
- **Severity:** Critical
- **URL:** `/student/battles` — laravel.log: `SQLSTATE[42S22]: Unknown column 'ended_at' in 'order clause' ... select * from battles where (player1_id = ... ) and status = completed order by ended_at desc`. `battles` jadvalida `ended_at` ustuni yo'q (ehtimol `completed_at`/`finished_at`).

#### QA-S-03 — "Mening buyumlarim" (shop/my-items) 500
- **Severity:** Major
- **URL:** `/student/shop/my-items` — `Call to undefined method App\Http\Controllers\Student\ShopController::myItems()`.

#### QA-S-04 — Pullik kursga yozilib bo'lmaydi
- **Severity:** Blocker (pullik kurslar uchun)
- `Student\EnrollmentController@enroll`: pullik kursda `back()->with('error', 'Pullik kurslarga yozilish hozircha ishlamaydi.')` — to'lov oqimi yo'q. Ustoz yaratgan pullik kursni hech bir o'quvchi o'qiy olmaydi. (Brauzerda tasdiqlash — pastda.)

- **Brauzerda tasdiqlandi:** `/student/courses/{slug}` -> "Sotib olish" -> toast "Pullik kurslarga yozilish hozircha ishlamaydi." (`shots/sc_enroll_paid.png`). Keyingi testlar uchun ustoz kursni "Bepul" qildi.

#### QA-S-05 — Kursni o'qish sahifasi 500 (yozilgan o'quvchi kursni ocha olmaydi)
- **Severity:** Blocker
- **URL:** `GET /student/learn/course/{slug}` (Kurs sahifasi -> "O'qishni davom ettirish")
- **Haqiqiy:** 500. laravel.log: `Call to undefined method Illuminate\Database\Eloquent\Relations\HasMany::questions()` — `app/Http/Controllers/Student/LearnController.php:72`: `$course->questions()->questions()->count()`.
- **Sabab:** ikki marta `questions()`; Q&A modeliga mos relation (masalan `$course->qnaQuestions()->whereNull('parent_id')`) kerak.
- **Dalil:** `shots/sl_learn.png`

#### QA-S-06 — Har qanday darsni ochish 500
- **Severity:** Blocker
- **URL:** `GET /student/learn/course/{slug}/lesson/{lesson}` (matnli va video dars)
- **Haqiqiy:** 500. laravel.log: `SQLSTATE[23000]: Column 'total_duration' cannot be null ... insert into lesson_progresses (user_id, lesson_id, enrollment_id, course_id, total_duration, ...)` — `LearnController.php:106` `LessonProgress::firstOrCreate([...], [..., 'total_duration' => $lesson->duration ...])`; matnli darsda / transkod qilinmagan videoda duration `null`.
- **Sabab:** `total_duration` ga `?? 0` berish yoki ustunni nullable/default 0 qilish.
- **Natija:** dars ko'rish, qayd yozish, darsni yakunlash, progress — hammasi bloklangan.
- **Dalil:** `shots/sle_text.png`, `shots/sle_vid.png`

#### QA-S-07 — Laboratoriya kategoriya sahifalari 500 (barcha 7 ta)
- **Severity:** Critical
- **URL:** `/student/lab/category/{mechanics|thermodynamics|electricity|optics|waves|magnetism|atomic}`
- **Haqiqiy:** 500. laravel.log: `Attempted to lazy load [category] on model [App\Models\LabExperiment] but lazy loading is disabled` — `LabController.php:476` (`formatExperimentCard`), chaqiruv `LabController.php:120`.
- **Sabab:** kategoriya sahifasida tajribalar `->with('category')` siz olinadi. Lab bosh sahifasidan kategoriyaga o'tib tajriba topib bo'lmaydi (tajribaga faqat to'g'ridan-to'g'ri URL bilan kirish mumkin).

#### QA-S-08 — Tajribani yakunlash 500 (manfiy vaqt) va SQL xato matni foydalanuvchiga ko'rsatiladi
- **Severity:** Critical
- **URL:** `POST /student/lab/api/attempt/{attempt}/complete` (`/student/lab/experiment/ohm-law/simulate` -> 5/5 vazifa -> "Tajribani yakunlash")
- **Haqiqiy:** 500; toastda xom SQL: `SQLSTATE[22003]: Numeric value out of range ... time_spent_seconds = -17997 ...`. Natija/XP/baho saqlanmaydi.
- **Sabab:** `time_spent_seconds` = now - started_at manfiy (≈ -5 soat) — `started_at` boshqa timezone'da (Asia/Tashkent vs UTC) saqlangan yoki `diffInSeconds` argument tartibi teskari. Shuningdek xato matnini toastga chiqarmaslik (QA-A-06 bilan bir xil muammo).
- **Dalil:** `shots/sl4_done.png`

#### QA-S-09 — Kunlik vazifalar: dashboard va /student/missions turli vazifalarni ko'rsatadi
- **Severity:** Minor
- Dashboard "Kunlik vazifalar": "1/3/5 ta dars tugating"; `/student/missions`: "1 ta battle yutib oling", "3 ta testdan o'ting", "5 ta battle o'ynang". Bir xil kun uchun bitta ro'yxat bo'lishi kerak.

#### QA-S-10 — XP sahifasi: "Daraja 1, 8 XP, 0%, Keyingi darajagacha 0 XP"
- **Severity:** Minor — progress 0% va "0 XP qoldi" bir-biriga zid; keyingi daraja chegarasi hisoblanmayapti.

#### QA-S-11 — Coin do'kon: onlayn to'lov ishlamaydi
- **Severity:** Major (monetizatsiya)
- `/student/shop` -> "Sotib olish" -> tasdiq dialogi "To'lov sahifasiga yo'naltirilasiz ... Xariddan keyingi balans 10 500 coin" -> "To'lovga o'tish" -> toast "Onlayn to'lov hozircha ishga tushirilmagan". Dialog matni noto'g'ri va'da beradi. Coin uchun buyumlar (my-items) sahifasi 500 (QA-S-03).

#### QA-S-12 — Testlar, sertifikatlar, sharh, battle, olimpiada oqimlari bloklangan
- **Testlar:** ustoz savol qo'sha olmaydi (QA-T-01) -> `/student/tests/course/{id}`: "Bu kursda hali testlar yo'q". Test boshlash/javob/natija/review — tekshirib bo'lmadi.
- **Sertifikatlar:** kurs tugatib bo'lmaydi (QA-S-06) -> `/student/certificates` bo'sh.
- **Sharh:** ≥20% progress kerak -> bloklangan.
- **Battles:** `/student/battles` 500 (QA-S-02), `Student/Battle/Match` komponenti yo'q.
- **Olimpiadalar:** admin olimpiada yarata olmaydi (QA-A-12); `/student/olympiads` va `/student/olympiad-history` ochiladi (bo'sh), `/student/olympiad-certificates` — oq ekran (komponent yo'q).
- **Turnirlar:** "Hozircha turnir yo'q", admin panelda turnir yaratish yo'q.

#### QA-S-13 — Om qonuni simulyatsiyasida formula qatori "I = U / R = 6 / 100 = 0.00 mA"
- **Severity:** Cosmetic — kalit ochiq holatda formula natijasi 0.00 mA ko'rsatiladi (6/100 = 60 mA bo'lishi kerak yoki formula yashirilsin).

#### QA-S-14 — Mobil dashboard: "Siz uchun tavsiyalar" kartasi to'liq kenglikda emas
- **Severity:** Cosmetic — 375px'da tavsiya kartasi ~75% kenglikda, quyidagi "Mashhur kurslar" kartasi esa 100% (`shots/s_m_student_dashboard.png`).

#### QA-S-15 — O'quvchining "Testlar" sahifasi 500 (faol test paydo bo'lganda)
- **Severity:** Blocker
- **URL:** `GET /student/tests/course/{course}` (Kurs -> Testlar; test natijasidagi "Davom etish" ham shu yerga olib boradi)
- **Haqiqiy:** 500. laravel.log: `Call to undefined relationship [module] on model [App\Models\Test]` — `app/Http/Controllers/Student/TestController.php:29` `->with(['module', 'lesson'])`.
- **Sabab:** `Test` modelida `module()` (`belongsTo(Module::class)`) relation yo'q. O'quvchi testni faqat to'g'ridan-to'g'ri `/student/tests/{test}/start` URL orqali boshlay oldi.

#### QA-S-16 — Sharh qoldirish 500
- **Severity:** Critical
- **URL:** `POST /student/courses/{course}/reviews` (kurs ≥20% -> "Sharh qoldirish" -> 5 yulduz + matn -> Yuborish)
- **Haqiqiy:** 500, sharh saqlanmaydi. laravel.log: `Add fillable property [comment] to allow mass assignment on [App\Models\Review]`. Natijada ustoz "Sharhlar"/javob berish va admin sharh moderatsiyasi ham bo'sh.

#### QA-S-17 — Matnli dars Markdown/formulalarni render qilmaydi
- **Severity:** Major (matematika/fizika platformasi uchun)
- **URL:** `/student/learn/course/{slug}/lesson/{lesson}` (matnli dars)
- **Haqiqiy:** ustoz kiritgan `# Tezlik\n\nTezlik $v = s/t$ ...\n- **muhim**: ...` bir qatorda xom holda chiqadi: "# Tezlik Tezlik $v = s/t$ formula bilan topiladi. - birlik: m/s - **muhim**: vektor kattalik" (sarlavha, ro'yxat, qalin, yangi qatorlar, LaTeX yo'q). Ustoz formasida "Markdown formatini qo'llab-quvvatlaydi" deb yozilgan.
- **Sabab:** `Lesson.vue:131` `<RichContent :html="lesson.text_content">` — markdown HTML'ga aylantirilmaydi (markdown-it + sanitize + KaTeX kerak).
- **Dalil:** `shots/sle_text2.png`

#### QA-S-18 — Test boshlash/natija sahifalaridagi kamchiliklar
- **Severity:** Minor
- `/student/tests/{test}/start`: "O'tish balli —%" (natija sahifasida esa 86.00%); "Savol turlari" ro'yxatida 4 tur (Rost/Yolg'on va Tartiblash yo'q), testda esa 6 tur bor.
- `/student/tests/history`: sana xom ISO formatda "2026-10-19T19:48:35.000000Z".
- `/student/tests/attempt/{id}/review` (javoblar tahlili yopiq testda) — hech qanday xabarsiz tarixga redirect.
- Test o'tilgandan keyin ham "Kinematika testi" (quiz turidagi dars) darslar rejasida "Boshlanmagan" bo'lib qoladi, progress 1/5 (test darsni yakunlamaydi).

#### QA-S-19 — O'quvchi kurs sahifalarida progress va darslar soni mos emas
- **Severity:** Minor
- `/student/learn/course/{slug}`: "6 ta dars" deydi, aslida 5; "Kurs dasturi 1/5". `/student/learn/course/{id}/notes|qna|announcements` yon panelida esa "0/5 dars" va hamma darslar "Boshlanmagan".

**Qayta tekshiruv (fixer tuzatgandan keyin):** QA-T-01 (savol qo'shish), QA-S-05 (kurs o'qish sahifasi), QA-S-06 (dars ochish) — brauzerda TUZATILGAN deb tasdiqlandi. Testga 6 xil savol qo'shildi, tahrirlandi (moslashtirish, tartiblash, ko'p javobli variantni o'chirish), tartibi o'zgartirildi, o'chirildi, test faollashtirildi; o'quvchi testni boshladi, barcha 6 turga javob berdi, 100% natija (+40 XP) oldi, noto'g'ri/javobsiz holatlarda ball to'g'ri hisoblandi (73%, 0%). Dars ochildi, qayd yozildi, dars yakunlandi (progress 20%).

#### QA-S-20 — Kursni oxirigacha tugatib bo'lmaydi -> sertifikat olinmaydi
- **Severity:** Critical
- **Qadamlar:** barcha darslarni ochib "Darsni yakunlash" bosish.
- **Haqiqiy:** 
  - "Test" turidagi dars ("Kinematika testi") o'quvchiga "Dars materiali hali yuklanmagan" deb ko'rinadi — unga bog'langan dars testi (faol, 10 savol) haqida havola ham, "Darsni yakunlash" tugmasi ham yo'q; testdan 100% bilan o'tilgandan keyin ham dars "Boshlanmagan".
  - Videosi yo'q (yoki transkod bo'lmagan) video dars ("Nyutonning ikkinchi qonuni") — "Dars materiali hali yuklanmagan", yakunlash tugmasi yo'q.
  - Natija: progress 40% da qotadi, `/student/certificates` bo'sh, sertifikat va `/verify/{code}` oqimini real sertifikat bilan tekshirib bo'lmadi.
  - Oxirgi darsda "Kursni yakunlash" tugmasi 2/5 progressda ham ko'rinadi.
- **Taklif:** quiz darsida bog'langan testga "Testni boshlash" tugmasi va test o'tilganda darsni avtomatik yakunlash; kontentsiz darslarni tekshiruvga yuborishda (CourseController@submit) bloklash. Yon paneldagi quiz dars ikonkasi video ikonka bo'lib chiqadi (Cosmetic).

#### QA-S-21 — Lab bosh sahifasi, simulyatsiyalar, vazifalar tekshiruvi ishlaydi (izoh)
- 8 ta tajriba (simple-pendulum, free-fall, ohm-law, light-refraction, ideal-gas-law, doppler-effect, electromagnet, photoelectric-effect) ochildi: tajriba sahifasi va simulyatsiya canvas'i yuklanadi, konsol xatosiz. Om qonuni to'liq bajarildi: "Tanishdim", 3 ta o'lchov vazifasi (60/120/20 mA, ±1% server tekshiruvi), kalkulyator (R = U/I) vazifasi — hammasi to'g'ri baholandi; progress qayta yuklashda saqlanadi. Faqat yakunlash 500 (QA-S-08).
- Kuzatish: electromagnet default sozlamada "Magnit maydon 12566.371 mT" (≈12.6 T) — o'zak/birlik koeffitsiyentini tekshirish tavsiya etiladi (Minor, fizik realizm).

STUDENT SECTION COMPLETE

### Ochiq sahifalar (QA-P-..)

#### QA-P-01 — 429 (throttle) xom "429 | TOO MANY REQUESTS" oynasi bilan ko'rsatiladi
- **Severity:** Minor
- **URL:** `POST /register` (throttle 3/min), `POST /login` (5/min)
- **Haqiqiy:** limitdan oshganda Inertia modalida Laravel'ning standart "429 TOO MANY REQUESTS" sahifasi chiqadi (`shots/pr_invalid.png`), forma ostida tushunarli o'zbekcha xabar yo'q. Bitta IP'dan (masalan, maktab/NAT) bir nechta o'quvchi ro'yxatdan o'tishida tez-tez uchraydi.
- **Taklif:** 429 ni ushlab "Juda ko'p urinish. {n} soniyadan keyin qayta urinib ko'ring" validatsiya xatosi sifatida qaytarish.

#### QA-P-02 — Ro'yxatdan o'tishda parol mos kelmasligi birinchi urinishda aytilmaydi
- **Severity:** Cosmetic — parol "123", tasdiq "321": faqat "Parol kamida 6 ta belgidan iborat bo'lishi kerak" chiqadi; mos kelmaslik xatosi keyingi urinishda chiqadi (Laravel birinchi yiqilgan qoidani ko'rsatadi). Frontendda oldindan tekshirish mumkin.

PUBLIC SECTION COMPLETE

## Ishlayotgan funksiyalar

### Ustoz
- Login (teacher) -> /teacher/dashboard redirect ishlaydi.
- Dashboard, Kurslar, Yangi kurs, O'quvchilar, Sharhlar, Statistika, Profil, Profilni tahrirlash, Bildirishnomalar sahifalari desktop va mobilda 200, konsol xatosiz, gorizontal overflow yo'q.
- Kurs yaratish: bo'sh forma xatolari, tavsif min 100, chegirma < narx validatsiyasi; "Nimalarni o'rganasiz/Talablar/Kimlar uchun" ro'yxatlari (Enter va "Qo'shish"); yaratilgach edit sahifasiga redirect + toast.
- Kurs tahrirlash: nomni o'zgartirish va saqlash (reloaddan keyin saqlangan), "Saqlanmagan o'zgarishlar" hint; rasm yuklash (PNG) ishlaydi, rasm bo'lmagan fayl rad etiladi; tanishtiruv videosi yuklash ishlaydi; nashrga tayyorlik checklist; erta yuborishda "Kamida bitta modul bo'lishi kerak" xatosi; to'liq kurs tekshiruvga yuborildi (status "Tekshiruvda").
- Qoralama kursni o'chirish (Sozlamalar -> Xavfli zona) ishlaydi.
- CourseNav tablari (Ma'lumotlar, Darslar rejasi, Testlar, E'lonlar, Savol-javob) ochiladi.
- Darslar rejasi: modul qo'shish/tahrirlash/o'chirish, "Yuqoriga surish" va drag&drop tartiblash (reloaddan keyin saqlangan), bepul modul belgisi; dars qo'shish (matn/video/test), darsni menyudan surish va drag&drop, darsni o'chirish; bulk darslar (xato bilan) yaratiladi.
- Matnli dars: matn (markdown) saqlanadi, bo'sh nom xatosi.
- Dars fayllari: TXT va PNG yuklash, o'chirish.
- Video bo'limlari: qo'shish, tahrirlash (1:30), o'chirish; soniya > 59 brauzer validatsiyasi bilan bloklanadi.
- Testlar: dars/modul/yakuniy test yaratish, bo'sh nom xatosi, test sozlamalarini tahrirlash, testni o'chirish, savolsiz testni faollashtirish rad etiladi.
- E'lonlar: yaratish (oddiy, qadalgan, rejalashtirilgan), qadash (toggle-pin).
- Kurslar ro'yxati: holat tablari va qidiruv (topilmasa "Mos kurs topilmadi" + "Filtrni tozalash").
- Profil: tahrirlash (headline, bio, tajriba, vebsayt, ko'nikmalar) saqlanadi, noto'g'ri URL xatosi; avatar yuklash (PNG) ishlaydi, rasm bo'lmagan fayl rad etiladi.
- Top bar: qo'ng'iroqcha -> /teacher/notifications; avatar menyusi (Profil, Profilni tahrirlash, Tungi rejim, Chiqish). Ustoz panelida global qidiruv yo'q (faqat kurslar ro'yxatidagi qidiruv).
- Savol-javob: o'quvchi savoli ko'rinadi, javob yozish, qadash, "muhim" belgilash ishlaydi; statistika kartalari yangilanadi.
- O'quvchilar: ro'yxat (progress, holat), o'quvchi tafsiloti (darslar bo'yicha o'zlashtirish, test urinishlari) ochiladi.
- Dashboard: yozilishlar grafigi va "So'nggi o'quvchilar" yangi yozilishni ko'rsatadi.
- Tungi rejim (avatar menyusi) ishlaydi, sahifalar to'q rangda to'g'ri ko'rinadi. Chiqish (logout) -> /login, keyin /teacher/dashboard login'ga qaytaradi.
- Kursni "Bepul" qilish va saqlash ishlaydi.
- Mobil (375px): drawer ochiladi, link bosilganda yopiladi va sahifaga o'tadi; kurs edit, darslar rejasi, testlar, e'lonlar, savol-javob, savol yaratish, dars edit sahifalarida gorizontal overflow yo'q.
- Testlar (QA-T-01 tuzatilgandan keyin): 6 xil savol turi qo'shiladi; moslashtirish/tartiblash/ko'p javobli savollarni tahrirlash, savolni menyudan surish, o'chirish; 10 savolli dars testi faollashtirildi.

### Admin
- Login, dashboard, hisobotlar (7 kun/30 kun/yil), barcha ro'yxat sahifalari desktop va mobilda (users, teachers, courses, directions, tags, questions, reviews, reports, leaderboard, achievements, daily missions, coin packages, payments, subscriptions, plans, promo codes, olympiads, certificates, certificate templates, notification send/templates, settings) ochiladi, gorizontal overflow yo'q.
- Kursni tasdiqlash (Kutilmoqda -> Nashr qilingan), kurslar filtri (holat, yo'nalish) va qidiruv.
- Foydalanuvchilar: qidiruv, rol/holat filtrlari, ko'rish, tahrirlash, bloklash/blokdan chiqarish, parollar mos kelmasligi xatosi, o'chirish tasdiq dialogi. Validatsiya xabarlari o'zbekcha.
- O'qituvchini tasdiqlash / tasdiqni bekor qilish.
- Yo'nalishlar, teglar, yutuqlar, kunlik vazifalar: yaratish / tahrirlash / o'chirish to'liq ishlaydi.
- Promo kodlar: yaratish, tahrirlash (10% -> 15%), to'xtatish (Faol emas), o'chirish.
- Reyting sozlamalari saqlanadi, "Reytingni yangilash" ishlaydi, Top 10 ko'rinadi.
- Xabar yuborish ("Faqat o'quvchilar" -> 4 ta foydalanuvchi) — o'quvchi bildirishnomalarida ko'rindi; xabarnoma shabloni yaratish.
- Sertifikat shabloni yaratish (PNG fayl bilan) va ko'rish.
- To'lovlar/obunalar/sharhlar filtrlari URL'ga yoziladi.
- Savollar banki ro'yxati va "Ko'rish" modali.

### O'quvchi
- Login, dashboard (statistika, tavsiyalar, kunlik vazifalar, yo'nalishlar), mobil pastki tab bar (Bosh, Kurslar, Lab, Reyting, Profil), header qidiruvi (`/student/search?q=Mexa` natija beradi).
- Katalog: saralash, yo'nalish/daraja/narx/reyting filtrlari, kurs sahifasi, istaklar ro'yxatiga qo'shish/olib tashlash, `/student/wishlist`.
- Bepul kursga yozilish ("Kursga muvaffaqiyatli yozildingiz!"), "Mening kurslarim" (Barchasi/Jarayonda/Tugatilgan).
- Kursni o'qish sahifasi, matnli dars, qayd yozish ("Yozuv saqlandi"), darsni yakunlash (progress 20% -> 40%), video dars pleyeri va video bo'limlari (1:30), dars fayllari tabi.
- Savol-javob: savol berish, ustoz javobi ko'rinadi; e'lonlar (rejalashtirilgan e'lon ko'rinmaydi — to'g'ri).
- Test: boshlash (qoidalar checkbox), 6 tur savolga javob, navigator, "Javob saqlandi", topshirish, natija (+40 XP), test tarixi.
- Bildirishnomalar: admin xabari va "Savolingizga javob berildi"; barchasini o'qilgan qilish.
- Hisob sozlamalari: username saqlanadi; noto'g'ri joriy parol xatosi.
- XP, yutuqlar (kategoriya tablari), reyting, turnirlar (bo'sh holat), olimpiadalar ro'yxati va tarixi (bo'sh holat), sertifikatlar (bo'sh holat), lab bosh sahifasi, lab reyting/badges/progress, 8 ta tajriba simulyatsiyasi va vazifa tekshiruvi.
- Coin do'kon: tasdiq dialogi va "to'lov ishga tushirilmagan" xabari (to'lov yo'q — QA-S-11).

### Ochiq sahifalar
- `/` mehmonni `/login` ga yo'naltiradi; login: bo'sh forma va noto'g'ri parol xatolari o'zbekcha; muvaffaqiyatli login rolga mos dashboardga olib boradi.
- `/register`: telefon formati va parol uzunligi xatolari; yangi o'quvchi (+998907776655) ro'yxatdan o'tdi va `/student/dashboard` ga tushdi ("Xush kelibsiz!").
- `/verify/INVALIDCODE123`: "Sertifikat topilmadi" sahifasi chiroyli ko'rinadi (desktop va mobil).
- Bloklangan foydalanuvchi uchun login kodi `status != blocked` shartini tekshiradi (kod bo'yicha; throttle sababli brauzerda alohida sinalmadi).

## Test davomida yaratilgan ma'lumotlar (edulife_shanba)
- Kurs "QA Kurs Fizika Mexanika (tahrir)" (id 01a15578-887e-7327-ab24-8d5d79d0184b, nashr qilingan, keyinchalik "Bepul"), 2 modul, 5 dars, 2 test (dars testi faol, 10 savol), 3 e'lon, 1 savol-javob.
- Promo kod QATEST10 (o'chirildi), xabarnoma shabloni "QA shablon", sertifikat shabloni "QA sertifikat", admin xabari "QA umumiy xabar".
- Yangi o'quvchi: Yangi Talaba, +998907776655 / password123. student@test.uz username = ali_qa, 3 ta test urinishi, Om qonuni lab urinishi.
- Admin tizim sozlamalari va reyting davri "Oylik" ga o'zgartirildi (sozlamalar baribir saqlanmadi — QA-A-13).

## Ikkinchi QA bosqichi

Sana: 2026-10-20 (server vaqti UTC 2026-10-19 ~20:00–21:30). Muhit: http://127.0.0.1:8000, DB `edulife_shanba`, Python Playwright (chromium headless), 1440x900 va 375x812 (is_mobile). Skriptlar va skrinshotlar: `C:\Users\Umidbek\AppData\Local\Temp\claude\c--xampp-htdocs-edulife\da0d82b1-2b6f-4bd5-9f4c-89ddc6321d70\scratchpad\qa2\` (quyida `shots/...` = shu papkadagi `shots\`). Har bir 500 uchun `storage/logs/laravel.log` yozuvi olindi.

Fixer parallel ishladi; har bir ID oxirgi holat bo'yicha (fixer commitlaridan keyin brauzerda qayta) tekshirildi. Test davomida assetlar bir necha marta qayta build qilindi ("Vite manifest not found" 500) — bunday holatlarda sahifa 20 s kutib qayta ochildi, ular xato sifatida hisoblanmadi.

### Qamrov (2-bosqich)
- **To'liq crawl (har rol, desktop + mobil, har sahifadagi barcha `<a href>` so'rovi):** admin 33 GET sahifa (+ olimpiada edit/registrations/monitor/participant, support, sertifikat), teacher 26, student 64 + 13 qo'shimcha (olimpiada, battle, support, 7 ta lab simulyatsiya, public profil). Buzilgan havola (404/500/`#`) topilmadi; yagona doimiy 500 — QA2-T-01.
- **Oqimlar:** kurs yaratish -> tekshiruvga yuborish -> admin rad etish (sabab) -> ustoz bildirishnomasi -> qayta yuborish -> tasdiqlash -> admin "Nashrdan olish" -> "Nashr qilish"; pullik kurs (o'quvchi xabari, admin "Kursga yozish"); dars yakunlash (matn, video oxirigacha, videosiz, quiz-dars testi) -> kurs 100% -> sertifikat -> PDF -> `/verify/{code}` (to'g'ri, noto'g'ri, SQL-inyeksiya satri); sharh -> ustoz javobi -> admin moderatsiya; battle 1v1 (ikki brauzer konteksti, durang + g'alaba/mag'lubiyat, ELO, coin, kunlik vazifa, mukofot olish); olimpiada to'liq oqimi (yaratish -> savol bankidan savollar -> e'lon -> ro'yxat -> 2 o'quvchi ro'yxatdan o'tdi -> jonli -> preflight -> imtihon (KaTeX) -> javoblar -> bo'limni yakunlash -> natija/reyting -> admin monitor/participant/grading/CSV -> natijalarni yakunlash -> sertifikat); lab: Om qonuni to'liq (✓/✗ tekshiruv, kalkulyator, yakunlash, XP/coin bir marta, qayta `complete` = 409) + 15 ta boshqa tajriba (7 kategoriya) ochildi/o'lchandi; profil tahriri, avatar, maxfiylik (yopiq profil boshqa o'quvchiga), kuzatish/kuzatuvchilar, yordam so'rovi (yaratish, javob, yopish) + admin yordam paneli javobi -> o'quvchi bildirishnomasi; admin: foydalanuvchi yaratish, COIN paket CRUD, tarif CRUD, sozlamalar, xabar yuborish, promo kod, savol ko'rish.
- **Kesishgan tekshiruvlar:** tungi rejim (student 15, admin 6, teacher 4 sahifa — och fonli blok qolmagan), Tab fokus halqasi (barcha rollarda 2px outline + ring, "Asosiy kontentga o'tish" skip-link), Esc: modal, lab kalkulyatori, mobil drawer, avatar menyusi yopiladi; "/" qidiruvga fokus; validatsiya xabarlari o'zbekcha; toastlar; ruxsatlar (pastda).
- **Ruxsatlar (brauzer konteksti, sessiya cookie bilan):** mehmon -> barcha `/student|/teacher|/admin` sahifalari `302 /login`; o'quvchi -> `/teacher/*`, `/admin/*` 403; boshqa o'quvchining test urinishi/natijasi/review, sertifikati, support so'rovi 403; yozilmagan pullik kursning learn/lesson/notes/tests sahifalari 404; boshqa o'quvchi qaydini PUT/DELETE/PATCH 403; o'quvchi `POST /admin/users`, `POST /teacher/courses`, `POST /teacher/reviews/{id}/reply` 403; ustoz -> `/admin/*`, `/student/*` 403, ustozning admin approve API'si 403/405; yopiq profil boshqa o'quvchiga faqat ism ("Bu o'quvchi profilini yopiq qilgan"). Ma'lumot sizib chiqishi aniqlanmadi.
- **Tekshirib bo'lmadi:** turnirga ro'yxatdan o'tish/chiqish (turnir yaratish imkoni yo'q — QA2-A-11), do'kondan buyum sotib olish va equip (coin balansi 0 — QA2-S-01), real to'lov/obuna/to'lov tafsilotlari (to'lov tizimi yo'q, `payments`/`subscriptions` bo'sh — UI orqali yaratib bo'lmaydi), olimpiada sovrin/coin taqsimoti (yakunlash xatosi — QA2-A-01).

### Qayta tekshiruv (re-test) jadvali

| ID | Holat | Dalil |
|---|---|---|
| QA-T-01 | Verified-fixed | pupil1 10 savolli testni (6 tur) 100% bilan topshirdi, ball to'g'ri |
| QA-T-02 | Verified-fixed | "Nusxalash" -> yangi qoralama `/teacher/courses/01a155de-…/edit`, "Kurs muvaffaqiyatli nusxalandi"; 2 modul, 5 dars, 2 test, 10 savol ko'chirildi; nusxa nomi endi "(nusxa)" (6c6357b) |
| QA-T-03 | Verified-fixed | E'lon ⋮ -> Tahrirlash -> Saqlash: modal yopildi, "E'lon yangilandi", matn saqlandi |
| QA-T-04 | Verified-fixed | Pullik, narx bo'sh -> "Narx to'ldirilishi shart." (`shots/t2_noprice.png`) |
| QA-T-05 | Verified-fixed | Kurs, savol, olimpiada, profil, sharh, support xatolari o'zbekcha ("Nomi to'ldirilishi shart.", "Sharh matni to'ldirilishi shart.") |
| QA-T-06 | Verified-fixed | Bulk darslar -> `/teacher/courses/{id}/modules`; bo'sh qator: "Har bir darsning nomi to'ldirilishi shart." |
| QA-T-07 | Verified-fixed | Qadalganlar boshida; "Rejalashtirilgan 01.12.2026, 15:00" belgisi (`shots/rt_t07.png`) |
| QA-T-08 | Verified-fixed | Testlar ro'yxati: "10 / 10", "0 / 20", "30 daq · 86%", "45 daq · 90%" |
| QA-T-09 | Verified-fixed | Yangi .webm -> "Video tayyor … asl formatda" (status completed); eski "processing 0%" video ham o'quvchida o'ynaydi va oxirida dars avtomatik yakunlanadi |
| QA-T-10 | Verified-fixed | "Namunaviy" trafik kartasi yo'q; grafik o'qi "20.09 … 19.10" |
| QA-T-11 | Verified-fixed | Narx inputida "50000" |
| QA-T-12 | Verified-fixed | Ustozga "Kursingiz rad etildi — «QA2 Pullik kurs»: sabab", "Kursingiz tasdiqlandi", "Yangi savol"; bosilganda kurs tahririga o'tadi |
| QA-T-13 | Verified-fixed | Avval props `filters.status=unanswered`, lekin 2 savol (biri javobli) qaytardi; 01080cc dan keyin tablar filtrlaydi |
| QA-T-14 | Verified-fixed | Sharh -> ustoz "Javob berish" -> "Javob yuborildi" -> admin tasdiqladi -> o'quvchi kurs sahifasida javob ko'rinadi (37a68dc) |
| QA-A-01 | Verified-fixed | `/admin/notifications` ochiladi; "Faqat ustozlar" -> tasdiq dialogi -> "1 ta foydalanuvchiga xabar yuborildi!"; ustozda bor, o'quvchida yo'q |
| QA-A-02 | Verified-fixed | `/admin/system-logs`: filtr, yozuvlar; vaqt endi mahalliy (d3fee1c) |
| QA-A-03 | Verified-fixed | `/admin/courses/{id}`: "Daraja: O'rta" (657c2da) |
| QA-A-04 | Verified-fixed | Dashboard'da qattiq kodlangan "+12% … +15%" yo'q (38881c1) |
| QA-A-05 | Verified-fixed | Hisobot: "Matematika | 2", "Fizika | 1" (takrorsiz) |
| QA-A-06 | Verified-fixed | "QA2 Foydalanuvchi" yaratildi; takroriy telefon/email xatolari o'zbekcha; ichki xato matni ko'rinmaydi |
| QA-A-07 | Verified-fixed | "QA2 paket" yaratildi va o'chirildi (pastda QA2-A-08) |
| QA-A-08 | Verified-fixed | Paket tahriri: "COIN paketi yangilandi!" |
| QA-A-09 | Partially-fixed | Yangi tarif yaratiladi/o'chiriladi; mavjud Trial/Standart tarifini tahrirlab bo'lmaydi — QA2-A-03 |
| QA-A-10 | Verified-fixed | `/admin/teachers`: Nilufar Usmonova — Kurslar 3, O'quvchilar 2 |
| QA-A-11 | Verified-fixed | Olympiad Edit, Registrations, Grading/Index, Monitor, ParticipantDetails (`/monitor/participant/{attempt}`) render bo'ladi; Questions — modal (A-14) |
| QA-A-12 | Verified-fixed | "Turi": Matematika/Fizika olimpiadasi; olimpiada yaratildi, tahrirlandi |
| QA-A-13 | Partially-fixed | "Dars uchun XP" 30->33 saqlanadi, lekin har saqlashda 13 ta maydon "topilmadi va saqlanmadi" — QA2-A-04 |
| QA-A-14 | Verified-fixed | "Ko'rish" modalida "Javob variantlari: 5 m/s — To'g'ri, 10 m/s, 2 m/s" (7a89d41) |
| QA-A-15 | Verified-fixed | Rad etish oqimi to'liq ishlaydi; nashr qilingan kursda "Nashrdan olish"/"O'chirish"; nashrdan olingan kurs katalogda yo'q, yozilmagan o'quvchiga 404, yozilgan o'quvchida ochiq qoladi; "Nashr qilish" bilan qaytdi (matn kamchiligi — QA2-A-12) |
| QA-S-01 | Verified-fixed | Profile/Edit, Settings/Privacy, Followers, Following, Support Faq/Create/Show, Coins/History, Courses/Preview, Olympiad/Certificates, Battle/History — barchasi kontent bilan |
| QA-S-02 | Verified-fixed | `/student/battles` 200; 1v1 o'yin oxirigacha ishladi (9f25b75 + migration) |
| QA-S-03 | Verified-fixed (sahifa) | `/student/shop/my-items` ochiladi; sotib olish/equip tekshirilmadi (QA2-S-01) |
| QA-S-04 | Partially-fixed | O'quvchiga "Bu kurs pullik. Onlayn to'lov hozircha ulanmagan — … +998 90 123 45 67"; admin "Kursga yozish" bilan ochdi (tasdiqlandi, o'quvchiga "Kursga kirish ochildi" bildirishnomasi). Onlayn to'lov yo'q (ma'lum cheklov) |
| QA-S-05 | Verified-fixed | Kurs o'qish sahifasi ochiladi |
| QA-S-06 | Verified-fixed | Barcha dars turlari ochiladi |
| QA-S-07 | Verified-fixed | Kategoriya sahifalari 200 (mechanics, optics, electricity, atomic …) |
| QA-S-08 | Verified-fixed | Om qonuni 5/5 -> "100% A+, XP +30, Coin +30, 3 daqiqa 55 soniya"; qayta `complete` x2 -> 409 "allaqachon yakunlangan"; XP bir marta (30). Coin esa ko'rinmaydi — QA2-S-01 |
| QA-S-09 | Verified-fixed | Dashboard va `/student/missions` bir xil ro'yxat ("1 ta dars tugating / 1 ta testdan o'ting / 1 ta battle yutib oling"). Lekin dars/test vazifalari progress olmaydi — QA2-S-02 |
| QA-S-10 | Verified-fixed | `/student/xp`: "Daraja 3 … Keyingi darajagacha 481 XP" |
| QA-S-11 | Verified-fixed | Coin paketlari "Tez orada" + "Onlayn to'lov hozircha ulanmagan" banneri |
| QA-S-12 | Partially-fixed | Testlar, sertifikat, sharh, battle, olimpiada endi ishlaydi; turnirlar bloklangan (QA2-A-11) |
| QA-S-13 | Verified-fixed | Kalit yoqilganda "I = U / R = 6 / 100 = 60.00 mA" |
| QA-S-14 | Verified-fixed | Mobil dashboard'da kartalar to'liq kenglikda (`shots/v2_dash_m.png`) |
| QA-S-15 | Verified-fixed | `/student/tests/course/{id}` 200 |
| QA-S-16 | Verified-fixed | Sharh saqlandi ("…moderatsiyadan so'ng kurs sahifasida ko'rinadi"), takroriy sharh bloklandi, yozilmagan o'quvchi 404 |
| QA-S-17 | Verified-fixed | Sarlavha, ro'yxat, qalin, KaTeX (`shots/rs17.png`); `<img onerror>`, `javascript:` havola, `<script>` tozalanadi (XSS yo'q) |
| QA-S-18 | Verified-fixed | Start: "O'tish balli 86%", 6 ta savol turi; tarix sanasi "20.10.2026, 01:38" |
| QA-S-19 | Verified-fixed | "5 ta dars", "5/5" |
| QA-S-20 | Verified-fixed | Yangi o'quvchi (pupil1): quiz-dars testidan 100% -> dars avtomatik "Tugatilgan"; videosiz dars "Darsni yakunlash"; video oxirida avtomatik; kurs 100% -> sertifikat EDULIFE-2026-29283904. Eski urinishli o'quvchi (student@test.uz) — 5779c55 dan keyin 5/5, sertifikat EDULIFE-2026-94BC5A3C |
| QA-P-01 | Verified-fixed (login) | Bosqich boshida hali xom "429 TOO MANY REQUESTS" modal edi (`shots/login_fail_pupil2.png`); oxirgi tekshiruvda 6-noto'g'ri urinishda forma ostida "Juda ko'p urinish. 54 soniyadan keyin qayta urinib ko'ring." Register throttle alohida tekshirilmadi |
| QA-P-02 | Verified-fixed | "123"/"321": "Parol kamida 6 ta belgidan…" va "Parollar mos kelmadi" birga |

**Re-test yakuni (51 qator):** Verified-fixed **47**, Partially-fixed **4** (QA-A-09, QA-A-13, QA-S-04, QA-S-12), Still-broken **0**. (QA-S-21 — izoh edi; qayta tekshiruvda lab tajribalari muammosiz.)

### Yangi topilgan xatolar (QA2-…)

#### QA2-S-01 — Coin balansi ikki joyda: lab/badge coinlari yo'qoladi, do'kon ishlatib bo'lmaydi
- **Severity:** Critical
- **URL:** `/student/coins`, `/student/shop`, header coin belgisi; `POST /student/lab/api/attempt/{id}/complete`
- **Qadamlar:** pupil2 Om qonuni tajribasini yakunladi ("Coin +30", "Birinchi tajriba" badge +5) -> `/student/coins`, `/student/shop`.
- **Kutilgan:** balans 35 (yoki 300+35), do'konda 100 coinlik "Test Hint" sotib olinadi.
- **Haqiqiy:** Coinlar tarixi: "Balans 0 coin · Jami olingan 0 · Jami sarflangan 0", jadvalda esa "+30 -> 330", "+5 -> 335" (`shots/s12__student_coins.png`). Header "0 coin", do'kon "Balansingiz 0", har bir buyumda "Coin yetarli emas"/"N-darajadan". Battle/vazifa coinlari esa profilga tushadi (pupil2 10 -> 13). Natijada hech bir o'quvchi do'kondan buyum ololmaydi, my-items/equip tekshirib bo'lmaydi.
- **Taxminiy sabab:** `LabAttempt::awardRewards` (app/Models/LabAttempt.php:515) `$user->addCoins()` — eski `users.coin_balance` ga yozadi; UI va `CoinService` esa `student_profiles.coins` dan o'qiydi (5a365ba). Lab va badge coinlarini `CoinService::earn` orqali berish; `users.coin_balance` dagi mavjud qiymatlarni migratsiya qilish.

#### QA2-S-02 — Yutuqlar va dars/test kunlik vazifalari hech qachon progress olmaydi
- **Severity:** Major
- **URL:** `/student/achievements`, `/student/missions`, dashboard "Kunlik vazifalar"
- **Qadamlar:** pro@test.uz bepul kursga yozilib matnli darsni yakunladi; pupil1 5 dars + 1 test (100%) + kursni tugatdi; pupil2 birinchi battle'ni yutdi va birinchi lab tajribasini tugatdi.
- **Kutilgan:** "5 ta dars tugating 1/5", "1 ta testdan o'ting 1/1"; "Birinchi qadam", "Kurs tugadi", "Imtihonchi", "Birinchi qon" ochiladi va "Olish" tugmasi chiqadi.
- **Haqiqiy:** dars/test vazifalari 0/5, 0/1; yutuqlar "0 / 32", hammasi "Qulflangan". Battle vazifalari esa ishlaydi (1/1 -> "Mukofotni olish" -> +15 XP +3 coin, bir marta). Achievement claim (eea75bc) ochilgan yutuq yo'qligi sababli tekshirib bo'lmaydi.
- **Taxminiy sabab:** dars yakunlash/test topshirish/kurs tugatish/battle g'alabasi joylaridan achievement va daily-mission progress servislari chaqirilmaydi (faqat battle mission hook bor).

#### QA2-S-03 — Kurs sertifikati PDF'i yo'q yoki soxta
- **Severity:** Major
- **URL:** `/student/certificates/{id}` -> "PDF yuklab olish" (`/student/certificates/{id}/download`)
- **Qadamlar:** kursni tugatish -> sertifikat -> "PDF yuklab olish".
- **Kutilgan:** PDF fayl yuklanadi.
- **Haqiqiy:** sertifikat berilganda `pdf_path = NULL`; tugma yangi oynani ochadi va o'sha sahifada toast "Sertifikat fayli topilmadi". Admin "Qayta generatsiya" qilgandan keyin yuklangan `certificate-EDULIFE-2026-154DBB18.pdf` — 104 baytli oddiy matn ("Certificate: … Recipient: … Course: … Issued: 2026-10-19"), haqiqiy PDF emas. Yangi sertifikatlar (EDULIFE-2026-29283904, -94BC5A3C) yana `pdf_path = NULL`. Shablon (`template_id`) ham bog'lanmaydi.
- **Taxminiy sabab:** sertifikat yaratishda PDF generatsiya job'i ishga tushmaydi (queue worker yo'q) va generator stub/fake PDF yozadi; yuklashda fayl bo'lmasa on-the-fly generatsiya qilish kerak.

#### QA2-S-04 — Olimpiadalar ro'yxati: avval 500, keyin jonli/yakunlangan olimpiadalar ko'rinmaydi, tarix bo'sh
- **Severity:** Major
- **URL:** `/student/olympiads`, `/student/olympiad-history`
- **Haqiqiy:** olimpiada mavjud bo'lishi bilan `/student/olympiads` 500 bergan: `Undefined array key "01a155d8-…"` (`app/Http/Controllers/Student/OlympiadController.php:63`) — dade6f9 dan keyin 200. Hozir: o'quvchi ro'yxatdan o'tgan va qatnashgan olimpiada "Jonli" va "Yakunlangan" holatda ro'yxatda yo'q ("Olimpiadalar topilmadi"); `/student/olympiad-history` "Ishtirok etilgan 0 … Hali olimpiadalarda ishtirok etmagansiz". O'quvchi natijaga faqat to'g'ridan-to'g'ri URL orqali yetadi.
- **Kutilgan:** jonli/yakunlangan olimpiadalar (yoki "Mening olimpiadalarim") ko'rinadi; tarixda qatnashuv bor.

#### QA2-S-05 — Fix'dan oldin o'tilgan quiz-dars yakunlanmagan (kurs bloklanadi)
- **Severity:** Major — **Verified-fixed (5779c55)**: student@test.uz kursni ochganda quiz-dars yakunlandi, 5/5, sertifikat berildi.

#### QA2-S-06 — Ustozning sharhga javobi o'quvchilarga ko'rinmasdi
- **Severity:** Minor — **Verified-fixed (37a68dc)**: kurs sahifasi "Sharhlar" tabida javob ko'rinadi.

#### QA2-S-07 — Sharh formasida "Comment to'ldirilishi shart."
- **Severity:** Minor — **Verified-fixed (6c6357b)**: "Sharh matni to'ldirilishi shart."

#### QA2-S-08 — Sertifikat sanasi va raqami sahifalar orasida mos emas
- **Severity:** Minor
- **URL:** `/student/certificates/{id}` vs `/verify/NHJNQT6G1YXV`
- **Haqiqiy:** o'quvchi sertifikatida "Berilgan 20.10.2026", "ID EDULIFE-2026-154DBB18"; verify sahifasida "Berilgan sana 19.10.2026", "Sertifikat raqami NHJNQT6G1YXV" (verification code). Admin sertifikat sahifasida "19 October 2026" (inglizcha oy). Sana UTC'da saqlanib turli joyda turlicha formatlanadi.

#### QA2-S-09 — Reytingdan o'quvchi profiliga o'tib bo'lmaydi (kuzatish funksiyasi yashirin)
- **Severity:** Minor
- **URL:** `/student/leaderboard`, `/student/friends`
- **Haqiqiy:** "Do'stlar" sahifasi "Reytingdan topish"ni tavsiya qiladi, lekin reyting qatorlarida havola yo'q (faqat `?type=` tablari). Kuzatish faqat `/student/users/{id}` URL'ini bilsangiz ishlaydi ("Ali ni kuzatishni boshladingiz", "Kuzatishni bekor qilish" — ishlaydi).

#### QA2-S-10 — Coin tarixi manbasi xom kod
- **Severity:** Cosmetic — `/student/coins` "MANBA" ustunida `lab_experiment`, `lab_badge`.

#### QA2-S-11 — Kurs kartasida "4.0 (0)"
- **Severity:** Cosmetic — dashboard/katalog: QA2 Pullik kurs "★ 4.0 (0)", kurs sahifasida esa "4.0 (1)". Sharhlar soni (reviews_count) yangilanmaydi.

#### QA2-S-12 — 403/404 sahifalari xom inglizcha Laravel sahifasi
- **Severity:** Minor
- **URL:** masalan boshqa o'quvchining sertifikati (`403 THIS ACTION IS UNAUTHORIZED.`), support so'rovi (`403 FORBIDDEN`), `/this-page-does-not-exist` (`404 NOT FOUND`)
- **Kutilgan:** o'zbekcha xato sahifasi, layout va "Bosh sahifaga qaytish". (Ruxsat nazorati to'g'ri, faqat ko'rinish.)

#### QA2-S-13 — Lab "Qarshilikni aniqlang" vazifasi noaniq
- **Severity:** Minor — Om qonuni 5-vazifa: 4-vazifadagi o'lchovdan (6 V / 20 mA) R = 300 Ω hisoblansa "noto'g'ri" deyiladi, faqat 100 Ω qabul qilinadi; shartda qaysi o'lchov nazarda tutilgani yozilmagan.

#### QA2-S-14 — Olimpiada ballari va o'rinlari noto'g'ri
- **Severity:** Critical
- **URL:** `/student/olympiads/{slug}/results`, `/results/leaderboard`, `/exam/leaderboard`, admin monitor/participant/CSV
- **Qadamlar:** 5 savolli bo'lim; pupil1 1/5, pupil2 2/5 to'g'ri javob berdi, topshirdi.
- **Kutilgan:** "Ball 2.00 / 5.00", o'rin 1 va 2, reytingda yuqori ball birinchi.
- **Haqiqiy:** "Ball 40.00 / 5.00" (bo'lim kartasida esa "2.00/5.00 ball"); "O'rin —", "Sarflangan vaqt —", "Percentil Top 0%"; reyting: "Ishtirokchilar 1" (2 bo'lsa ham), ikkalasi "0-o'rin", 20 ball 40 balldan yuqorida; `/exam/leaderboard` JSON `rank: 0`; admin participant "Ball 20.00 / 5" vs bo'lim "1.00 / 5.00"; CSV "O'rin" bo'sh (`shots/o16_pupil2.png`).
- **Taxminiy sabab:** attempt `total_score` ga foiz yoziladi; rank faqat finalize'da hisoblanadi (u esa yiqiladi — QA2-A-01).

#### QA2-S-15 — Olimpiada sertifikatini yuklab olish 500
- **Severity:** Major
- **URL:** `/student/olympiads/{slug}/certificate/download` ("Sertifikat olish")
- **laravel.log:** `You need to install the imagick extension to use this back end` (BaconQrCode ImagickImageBackEnd).
- **Taxminiy sabab:** QR uchun Imagick backend; SVG backend yoki GD'ga o'tish kerak.

#### QA2-S-16 — Battle tugagach header XP/coin yangilanmaydi
- **Severity:** Minor — g'olib sahifasida "G'alaba! ELO +20 +10 coin", header esa "50 XP · 0 coin" (DB: 150 XP, 10 coin) — sahifa yangilangunча eski qiymat (`shots/b4_end_p2.png`).

#### QA2-S-17 — Battle savollari kurs test bazasidan olinadi
- **Severity:** Minor — battle raundlari ustozning baholanadigan testi savollari ("QA rost/yolg'on savol 3", "Jism 2 s da 10 m …"): o'quvchi testdan oldin battle'da shu savollarni ko'radi va ✓/✗ natijasini biladi. Alohida battle savol banki yoki faqat tasdiqlangan question_bank ishlatish tavsiya etiladi.

#### QA2-S-18 — Unvon ELO o'zgarmasa ham o'zgaradi
- **Severity:** Cosmetic — pupil1 ELO 1000, "Unvon: Bronza"; 0:0 durangdan keyin ELO 1000, "Unvon: Kumush".

#### QA2-S-19 — Bor atom modeli simulyatsiyasida inglizcha yozuvlar
- **Severity:** Cosmetic — canvas'da "Jump", "→ Go" tugmalari (`shots/lab2_bohr-atom.png`).

#### QA2-A-01 — Olimpiada natijalarini yakunlab bo'lmaydi, ichki xato toastda
- **Severity:** Critical
- **URL:** `POST /admin/olympiads/{id}/grading/finalize` (Baholash paneli -> "Natijalarni yakunlash" -> "Ha, yakunlash")
- **Haqiqiy:** toast: "Attempted to lazy load [attempt] on model [App\Models\OlympiadLiveLeaderboard] but lazy loading is disabled." — holat Jonli/Baholanmoqda/Yakunlangan bo'lsa ham bir xil. O'rinlar, sovrinlar, har to'g'ri javob uchun coin (sozlamada 1 coin) berilmaydi; o'quvchi coinlari o'zgarmadi.
- **Taxminiy sabab:** finalize servisida `OlympiadLiveLeaderboard` -> `attempt` eager-load qilinmagan; xato matnini foydalanuvchiga chiqarmaslik.

#### QA2-A-02 — Olimpiada vaqtlari 5 soatga siljiydi (mahalliy vaqt UTC sifatida saqlanadi)
- **Severity:** Major
- **URL:** `/admin/olympiads/create|{id}/edit` ("Vaqtlar mahalliy vaqt bo'yicha")
- **Qadamlar:** ro'yxat 00:26–01:56 (Toshkent), boshlanish 01:57 kiritildi.
- **Haqiqiy:** DB'ga `registration_start_at = 2026-10-20 00:26:00` (UTC deb) yoziladi, `APP_TIMEZONE=UTC` -> o'quvchiga "Ro'yxat hali boshlanmagan" (aslida ochiq bo'lishi kerak edi). Ishlashi uchun vaqtni UTC'da kiritish kerak bo'ldi; shundan keyin o'quvchi sahifasida "Ro'yxat tugashi 19.10.2026 21:00", natijalarda "Boshlangan 19.10.2026 21:02" — UTC, mahalliy emas.
- **Taxminiy sabab:** datetime-local qiymatini `Asia/Tashkent` deb parse qilib UTC'ga o'girish, ko'rsatishda qaytarish kerak.

#### QA2-A-03 — Mavjud obuna tariflarini tahrirlab bo'lmaydi
- **Severity:** Major
- **URL:** `PUT /admin/subscription-plans/{id}`
- **Qadamlar:** Trial yoki Standart -> Tahrirlash -> faqat tavsifni o'zgartirish -> Saqlash.
- **Haqiqiy:** "Asl narx 0 dan katta bo'lishi kerak." / "Asl narx 149000 dan katta bo'lishi kerak." — forma "Chegirmasiz narx"ni narxga teng qilib to'ldiradi (seed: original_price = price), validatsiya `gt:price`.
- **Taklif:** original_price == price bo'lsa null deb yuborish yoki `gte` + ko'rsatmaslik.

#### QA2-A-04 — Tizim sozlamalarining 13 ta maydoni saqlanmaydi
- **Severity:** Major
- **URL:** `PUT /admin/settings`
- **Haqiqiy:** har saqlashda toast: "Ba'zi sozlamalar topilmadi va saqlanmadi: xp.xp_per_test, xp.xp_per_battle, xp.premium_xp_multiplier, coin.coin_per_test, coin.coin_per_battle, coin.transfer_fee_percent, coin.monthly_limit_trial, coin.monthly_limit_standard, coin.monthly_limit_premium, coin.monthly_limit_vip, battle.questions_per_match, battle.time_per_question, battle.daily_limit_trial". Shuningdek o'zgartirilmagan maydonlar ham tizim logiga "Sozlama o'zgartirildi" bo'lib yoziladi.
- **Sabab:** `settings` jadvalida bu kalitlar yo'q (seed/migratsiya kerak).

#### QA2-A-05 — Mobil (375px) gorizontal overflow
- **Severity:** Minor
- `/admin/directions` jadval sahifani 419px'ga kengaytiradi, "Holat/amallar" ustunlari ekrandan tashqarida (`shots/admin_m__admin_directions.png`); `/admin/subscription-plans` o'chirish tugmasi 3px; `/admin/certificates/{id}` kartalar 12px; `/teacher/dashboard` "So'nggi o'quvchilar/sharhlar" kartalari 10px (`shots/teacher_m__teacher_dashboard.png`); `/teacher/courses/{id}/tests/{test}` havola-tugmalar 487px gacha; `/student/olympiads/{slug}` kartalar 13px.

#### QA2-A-06 — Olimpiada tur/bosqich nomlari inglizcha
- **Severity:** Cosmetic — **Verified-fixed (2c74510)**: "Fizika olimpiadasi", "Maktab/Tuman/Viloyat/Respublika bosqichi".

#### QA2-A-07 — Admin sahifalarida vaqtlar UTC
- **Severity:** Minor — tizim loglari **Verified-fixed (d3fee1c)** ("20.10.2026 02:18"). Qolgan: olimpiada natija/registrations (21:02, 20:57), admin sertifikat "19 October 2026".

#### QA2-A-08 — COIN paketida asl narx narxdan kichik bo'lishi mumkin
- **Severity:** Minor — Narx 12 000, "Asl narx" 100 qabul qilindi va ro'yxatda "12 000 so'm ~~100 so'm~~" ko'rinadi.

#### QA2-A-09 — Tarif formasida "imkoniyatlar" (features) maydoni yo'q
- **Severity:** Minor — seed tariflarda features ro'yxati bor ("Barcha kurslar", "10 battle/kun" …), admin uni ko'ra/tahrirlay olmaydi; yangi tarif features'siz yaratiladi.

#### QA2-A-10 — Admin promo kodlari hech qayerda qo'llanmaydi
- **Severity:** Major
- **Qadamlar:** admin `QA2PROMO` (20%, faol, muddatsiz) yaratdi -> pullik kurs sahifasida promo maydoni yo'q; olimpiada ro'yxat formasidagi "Chegirma kodi" -> `POST /student/olympiads/validate-coupon/{id}` `{"code":"QA2PROMO"}` -> `valid:false, "Promokod noto'g'ri yoki muddati o'tgan"`.
- **Sabab:** olimpiada boshqa jadvalni (`discount_coupons`) tekshiradi; kurs uchun checkout yo'q. Promo kodlar bo'limi amalda ishlamaydi. (Test kodi o'chirildi.)

#### QA2-A-11 — Turnirlarni yaratish imkoni yo'q
- **Severity:** Major
- Admin panelda turnir route/sahifasi yo'q (`route:list` da faqat `student/tournaments*`), `tournaments` jadvali bo'sh -> o'quvchi `/student/tournaments` doim "Hozircha turnir yo'q"; ro'yxatdan o'tish/chiqish va entry fee (eda81b9) brauzerda tekshirib bo'lmaydi.

#### QA2-A-12 — "Nashrdan olish" dialogi "Rad etish" matnlari bilan
- **Severity:** Cosmetic — sarlavha "Kursni nashrdan olish", lekin maydon "Rad etish sababi", tugma "Rad etish", toast "Kurs rad etildi!", holat "Rad etilgan". Qayta nashr qilishda toast "Kurs muvaffaqiyatli tasdiqlandi!".

#### QA2-A-13 — Olimpiadani savolsiz e'lon qilish va jonli qilish mumkin
- **Severity:** Minor — "0 savol" bo'limli olimpiada "Tez kunda" -> "Ro'yxat ochiq" ga hech qanday ogohlantirishsiz o'tdi.

#### QA2-A-14 — Yordam so'rovlari ro'yxatida qatorlar havola emas
- **Severity:** Minor — `/admin/support` jadval qatori faqat click bilan ochiladi (`<a>` yo'q) — klaviatura/yangi tabda ochib bo'lmaydi. Panel o'zi ishlaydi: javob -> o'quvchiga "So'rovingizga javob berildi" bildirishnomasi, holat "Javob kutilmoqda".

#### QA2-S-20 — Olimpiada "Javoblarni ko'rish" 500 (olimpiada yakunlangandan keyin)
- **Severity:** Major
- **URL:** `/student/olympiads/{slug}/results/review` (Natijalar -> "Javoblarni ko'rish")
- **laravel.log:** `Attempted to lazy load [olympiadQuestion] on model [App\Models\OlympiadAnswer] but lazy loading is disabled.` — `app/Http/Controllers/Student/OlympiadResultController.php:133`. (Olimpiada jonli bo'lganda shu URL natijalar sahifasini ko'rsatgan, yakunlangach 500.) Crawl havola tekshiruvida topildi.

#### QA2-T-01 — Matnli dars uchun "Video bo'limlari" sahifasi 500
- **Severity:** Minor
- **URL:** `GET /teacher/lessons/{matnli-dars}/chapters`
- **laravel.log:** `Call to a member function chapters() on null` (videosi yo'q dars). 404 yoki bo'sh holat qaytishi kerak. (UI'dan bevosita havola yo'q — crawl orqali topildi.)



### Yangi xatolar — severity bo'yicha

| Severity | Jami | Ochiq | 2-bosqich davomida tuzatildi |
|---|---|---|---|
| Critical | 3 | 3 (QA2-S-01, QA2-S-14, QA2-A-01) | — |
| Major | 11 | 10 (QA2-S-02, S-03, S-04, S-15, S-20, A-02, A-03, A-04, A-10, A-11) | QA2-S-05 |
| Minor | 15 | 13 (QA2-S-08, S-09, S-12, S-13, S-16, S-17, A-05, A-07 qisman, A-08, A-09, A-13, A-14, T-01) | QA2-S-06, QA2-S-07 |
| Cosmetic | 6 | 5 (QA2-S-10, S-11, S-18, S-19, A-12) | QA2-A-06 |
| **Jami** | **35** | **31** | **4** |

### Eng muhim ochiq muammolar (Top 10)
1. **QA2-S-01** (Critical) — lab/badge coinlari `users.coin_balance` ga tushadi, UI/do'kon `student_profiles.coins` ni o'qiydi: balans 0, do'kon ishlatib bo'lmaydi.
2. **QA2-A-01** (Critical) — olimpiada "Natijalarni yakunlash" lazy-load xatosi bilan yiqiladi (xato matni toastda); o'rin, sovrin, coin berilmaydi.
3. **QA2-S-14** (Critical) — olimpiada ballari foizda saqlanadi ("40.00 / 5.00"), barcha o'rinlar 0, reyting tartibi noto'g'ri.
4. **QA2-S-02** (Major) — yutuqlar va dars/test kunlik vazifalari hech qachon progress olmaydi (faqat battle vazifalari ishlaydi).
5. **QA2-S-03** (Major) — kurs sertifikati PDF'i yaratilmaydi; qayta generatsiyadan keyin 104 baytli matn fayl.
6. **QA2-A-02** (Major) — olimpiada vaqtlari mahalliy deb kiritilib UTC deb saqlanadi: 5 soatlik siljish, ro'yxat "hali boshlanmagan".
7. **QA2-S-20 / QA2-S-15** (Major) — olimpiada "Javoblarni ko'rish" 500 va olimpiada sertifikatini yuklash 500 (imagick yo'q).
8. **QA2-S-04** (Major) — o'quvchi olimpiadalar ro'yxatida jonli/yakunlangan olimpiadalar yo'q, tarix 0.
9. **QA2-A-04 / QA2-A-03** (Major) — 13 ta tizim sozlamasi saqlanmaydi; mavjud obuna tariflarini tahrirlab bo'lmaydi.
10. **QA2-A-10 / QA2-A-11** (Major) — admin promo kodlari hech qayerda qo'llanmaydi; turnir yaratish imkoni yo'q (turnirlar bo'limi doim bo'sh).

### 2-bosqichda yaratilgan/o'zgartirilgan ma'lumotlar (edulife_shanba)
- Kurs **"QA2 Pullik kurs"** (id 01a155c6-b9e6-712a-8af7-84361109cac3, slug qa2-pullik-kurs-Q8Zo0w, 50 000 so'm, nashr qilingan; 1 modul, 1 matnli dars "QA2 Ochiq matnli dars" — bepul/preview). Rad etildi -> qayta yuborildi -> tasdiqlandi -> nashrdan olindi -> qayta nashr qilindi.
- Kurs nusxasi **"QA Kurs Fizika Mexanika (tahrir) (Copy)"** (id 01a155de-0491-…, qoralama) + 3 ta bulk dars ("QA2 bulk 1..3", birinchisiga .webm video).
- Yozilishlar: pupil1 -> QA2 Pullik kurs (admin gift) va QA Kurs (bepul); pro -> QA Kurs. Sertifikatlar: EDULIFE-2026-154DBB18 (pupil1, QA2), EDULIFE-2026-29283904 (pupil1, QA Kurs), EDULIFE-2026-94BC5A3C (student@test.uz). pupil1 test urinishi 100%.
- Sharh (pupil1, 4★, tasdiqlangan) + ustoz javobi. E'lon "Rejalashtirilgan e'lon" matni o'zgartirildi.
- Olimpiada **"QA2 Fizika olimpiadasi"** (id 01a155d8-5e85-…, slug qa2-fizika-olimpiadasi-2EP1mG, holat Yakunlangan, 5 savol, 2 ishtirokchi: pupil1 1/5, pupil2 2/5; olimpiada sertifikati OLY-2026-GO0RYQ8N).
- Battle: 2 ta (durang 0:0 — taymaut; pupil2 5:0 g'alaba). pupil2: ELO 1020, kunlik vazifa mukofoti olindi.
- Lab: pupil2 Om qonuni yakunlangan; pro 15 ta tajribada 1-vazifa/o'lchovlar.
- Admin: foydalanuvchi "QA2 Foydalanuvchi" (+998901112244, qa2user@test.uz, o'quvchi); xabar "QA2 ustozlarga xabar"; "Dars uchun XP" 30->33->30 qaytarildi; QA2 paket, QA2 tarif, QA2PROMO yaratilib o'chirildi.
- **Diqqat:** test paytida xato bilan seed tarif **"VIP Yillik"** o'chirib yuborildi; admin UI orqali qayta yaratildi (nom, slug `vip-yearly`, 2 983 140 / 3 975 000 so'm, yillik) — lekin yangi id, `sort_order` 0 va **features ro'yxati yo'q** (UI'da maydon yo'q, QA2-A-09). Asl features: ["Hamma narsa","Unlimited battle","25% COIN limit","2x XP multiplikator","1-on-1 support","Early access","25% chegirma"] — seederdan tiklash tavsiya etiladi.
- pupil2: bio/vebsayt/avatar yangilandi, profil **yopiq** qilindi; yordam so'rovi TICKET-2026-00001 (yopilgan, admin javobi bor). pupil1 student@test.uz ni kuzatadi.

QA2 COMPLETE

## Tuzatuvchi — 2-bosqich natijalari

Sana: 2026-10-20. Branch `main`, DB `edulife_shanba` (barcha yangi migratsiyalar ishga tushirildi; `SubscriptionPlanSeeder` va `LabExperimentSeeder` qayta ishga tushirildi). Yakuniy holat: `php artisan test` — 352 passed; `npx vitest run` — 1214 passed; `npm run build` — OK. Playwright (375x812): QA2-A-05 dagi barcha sahifalar 375px; asosiy sahifalarda 500 yo'q, `laravel.log` toza.

**Vaqt zonasi qarori (QA2-A-02):** `app.timezone = Asia/Tashkent` (env `APP_TIMEZONE`), MySQL sessiyasi `+05:00`. Shakl chegarasida alohida konvertatsiya o'rniga butun ilova Toshkent vaqtida ishlaydi (bitta mamlakat; "bugun" — vazifa/seriya, server formatlagan sanalar ham to'g'ri bo'ladi). UTC'da yozilgan eski qatorlar `2026_10_21_000008` migratsiyasi bilan bir marta +5 soat suriladi (`down()` qaytaradi). JSON/ISO chiqishi offset bilan — brauzer tomoni o'zgarmadi.

| ID | Holat | Commit / sabab |
|---|---|---|
| QA2-S-01 | Tuzatildi | 01ca4b9 — `User::addCoins/addXp/spendCoins` → `CoinService`/`student_profiles`; lab/badge/achievement/daily mission shu balansga; middleware va admin `users.*` ga qaramaydi; `users.coin_balance/xp_total` bir marta ko'chirilib nollanadi (pupil2: 13 + 335 → 348, keyin olimpiada mukofoti bilan 450) |
| QA2-S-02 | Tuzatildi | b278017 — dars/test/kurs/sharh → `GamificationProgress`; yutuq talablari (`{"lessons":1}`) to'g'ri o'qiladi; yutuqlar sahifasi oldingi progressni ham hisoblaydi |
| QA2-S-03 | Tuzatildi | 3048919 — DomPDF bilan haqiqiy PDF (o'zbekcha shablon, tekshirish kodi, SVG QR); berilganda yaratiladi, yuklashda yo'q/soxta bo'lsa qayta yaratiladi |
| QA2-S-04 | Tuzatildi | 53eeec8 — jonli/yakunlangan olimpiadalar ro'yxatda, qidiruv ishlaydi; topshirilganda tarixda "Natija kutilmoqda" |
| QA2-S-05 | Oldin tuzatilgan | 5779c55 |
| QA2-S-06 | Oldin tuzatilgan | 37a68dc |
| QA2-S-07 | Oldin tuzatilgan | 6c6357b |
| QA2-S-08 | Tuzatildi | e706df3 (+ f309da5) — verify sahifasida sertifikat raqami va tekshirish kodi alohida, sana dd.mm.yyyy, mahalliy vaqt; olimpiada sertifikati QR'i ham tekshiriladi |
| QA2-S-09 | Tuzatildi | 3c86f79 — reytingda ism profilga havola |
| QA2-S-10 | Tuzatildi | 54c89b5 — manba va tavsiflar o'zbekcha |
| QA2-S-11 | Tuzatildi | e83392e — reyting/sharhlar soni faqat tasdiqlangan sharhlardan; moderatsiya kursni yangilaydi; eski qiymatlar qayta hisoblandi |
| QA2-S-12 | Tuzatildi | f2f84b2 — 403/404/419/429 (debug o'chiq bo'lsa 500/503 ham) o'zbekcha Error sahifasi |
| QA2-S-13 | Tuzatildi | 053ad06 — vazifa 2-vazifadagi o'lchovni (6 V, 60 mA → 100 Ω) aniq ko'rsatadi |
| QA2-S-14 | Tuzatildi | c49152a, 00154cf — bitta formula: ball = olingan/maks ball, o'rin = vaznli foiz (teng bo'lsa vaqt); har topshirishda o'rinlar yangilanadi; eski urinishlar qayta hisoblandi (pupil2 1-o'rin 2.00/5.00, pupil1 2-o'rin) |
| QA2-S-15 | Tuzatildi | 6e4f496 — Imagick kerak emas (SVG QR, umumiy PDF shablon); sertifikat bir marta yaratiladi |
| QA2-S-16 | Tuzatildi | 4ee3de8 — battle tugashi bilan header (auth props) yangilanadi |
| QA2-S-17 | Kechiktirildi | Battle uchun alohida savol manbai kerak: hozir yagona manba — kurs testlari; `question_bank` ga o'tish olimpiada savollari bilan kesishadi va `battle_rounds` sxemasini o'zgartiradi. Mahsulot/kontent qarori (battle savol banki va uni boshqarish UI) talab qilinadi |
| QA2-S-18 | Tuzatildi | 7092099 — yagona ELO shkalasi (1000 = Bronza); ELO o'zgarmasa unvon o'zgarmaydi; mavjud unvonlar qayta hisoblandi |
| QA2-S-19 | Tuzatildi | b7d60a1 — "↓ Tushish" / "↑ Sakrash" |
| QA2-S-20 | Tuzatildi | c49152a — review sahifasi `sectionAttempts.answers.question` ni eager-load qiladi |
| QA2-A-01 | Tuzatildi | c49152a, 00154cf, c5e3bde — finalize eager-load bilan ishlaydi, o'rin/tarix/coin (har to'g'ri javob + top-10 bonus) bir marta; xato matni foydalanuvchiga chiqmaydi (DomainException → o'zbekcha xabar, qolgani logga) |
| QA2-A-02 | Tuzatildi | f309da5 — yuqoridagi vaqt zonasi qarori |
| QA2-A-03 | Oldin tuzatilgan | fdef130 (`gte:price`) — qayta tekshirildi, test bor |
| QA2-A-04 | Tuzatildi | c7130ff (kalitlar; assetlar qayta build qilinmagan edi) + 200df89 (o'zgarmagan maydonlar logga yozilmaydi) |
| QA2-A-05 | Tuzatildi | 99ec62e — Table wrapper `relative`, sahifa gridlari mobilda `grid-cols-1`, tarif kartasi va yo'nalishlar jadvali |
| QA2-A-06 | Oldin tuzatilgan | 2c74510 |
| QA2-A-07 | Tuzatildi | d3fee1c (loglar) + f309da5 (server vaqti Toshkent) + e706df3 (sertifikat sanasi dd.mm.yyyy) |
| QA2-A-08 | Tuzatildi | 0c4f087 |
| QA2-A-09 | Tuzatildi | 5867794, 83f353a — tarif formasida "Imkoniyatlar" |
| QA2-A-10 | Tuzatildi | d5afd12 — admin promo kodlari olimpiada "Chegirma kodi"da (limitlar, foydalanish yoziladi); kurs/obuna to'lovi oflayn (to'lov tizimi yo'q) — admin sahifasida izoh |
| QA2-A-11 | Tuzatildi | 5fd0a43 — Admin › Turnirlar CRUD; ro'yxat/chiqish/bekor qilishda coin qaytarish ishlaydi |
| QA2-A-12 | Tuzatildi | e97d678 |
| QA2-A-13 | Tuzatildi | dbb6e50 — savolsiz bo'lim bilan ro'yxat ochilmaydi |
| QA2-A-14 | Tuzatildi | 766dee5 |
| QA2-T-01 | Tuzatildi | ff8d1d8 |
| "VIP Yillik" | Tiklandi | e7f8e53 — seeder slug bo'yicha idempotent; `edulife_shanba` da features va tartib tiklandi |

## Yakuniy QA (3-bosqich)

Sana: 2026-10-20 (Toshkent vaqti 03:30–04:40; ilova `APP_TIMEZONE=Asia/Tashkent`). Muhit: http://127.0.0.1:8000, DB `edulife_shanba`, Python Playwright (chromium headless), 1440x900 va 375x812 (is_mobile). Skriptlar, skrinshotlar va yuklab olingan PDF'lar: `C:\Users\Umidbek\AppData\Local\Temp\claude\c--xampp-htdocs-edulife\da0d82b1-2b6f-4bd5-9f4c-89ddc6321d70\scratchpad\qa3\` (quyida `shots/...` = shu papkadagi `shots\`). Har bir rol uchun bitta login (storage_state qayta ishlatildi). Har sahifada konsol xatolari, muvaffaqiyatsiz so'rovlar va `storage/logs/laravel.log` yangi yozuvlari yig'ildi. Manba kodi, `.env`, git holati o'zgartirilmadi; faqat o'qish uchun `git log`, `route:list` va SELECT so'rovlari ishlatildi.

### Qamrov (3-bosqich)
- **To'liq oqimlar (brauzerda, dalil bilan):**
  - **Coin/XP yagona manba:** student@test.uz Om qonuni tajribasini yakunladi (+30 coin, "Birinchi tajriba" nishoni +5) → header, `/student/coins`, `/student/shop`, `/student/shop/my-items` — hammasida 535; "Ninja avatari" (500) sotib olindi → hamma joyda 35, tarixda "Sotib olindi: Ninja avatari −500 → 35"; "Taqish" → "Taqilgan", qayta yuklashda saqlanadi.
  - **Olimpiada to'liq oqimi:** admin "QA3 Fizika olimpiadasi"ni mahalliy vaqtlar bilan yaratdi (ro'yxat 03:12–03:52, boshlanish 03:54, tugash 05:42, narx 20 000 so'm) → edit/show/o'quvchi sahifalarida aynan shu vaqtlar → savol bankidan 5 savol → E'lon → Ro'yxat ochiq → admin promo kodlari: `NOTACODE` → "Promokod noto'g'ri…", `QA3HALF` (50%) → "Jami 10 000 so'm" → to'lov sahifasi (coin bilan to'lash), `QA3FULL` (100%) → "Bepul" → pupil1 va pupil2 ro'yxatdan o'tdi (promo_code_usages yozildi, usage_count 2) → Jonli → preflight → imtihon (KaTeX) → pupil2 5/5, pupil1 3/5 → natija/reyting jonli (#1, #2) → Baholashga → "Natijalarni yakunlash" → "Olimpiada natijalari yakunlandi" → coin: pupil2 +105 (5×1 + top-10 100), pupil1 +103; olimpiada tarixi, CSV (o'rin 1/2) → "Javoblarni ko'rish" 200 → sertifikat PDF (haqiqiy, QR, tekshirish kodi) → `/verify/NQKTR6SLV7MD` "Sertifikat tasdiqlandi".
  - **Kurs tugatish → sertifikat:** yangi o'quvchi (+998907776655) bepul kursga yozildi → 3 dars (matn, videosiz, matn) → quiz-dars testi 10/10 (100%) → dars avtomatik yakunlandi → video dars oxirigacha → kurs 100% → sertifikat EDULIFE-2026-8F3F8D66 (berilganda `pdf_path` yozildi) → "PDF yuklab olish" = 25 KB `%PDF-1.7`, pdf.js bilan chizildi (`shots/cert_student_pdf.png`) → `/verify/SHSTLZK4BWDW` tasdiqlandi; mehmon verify sahifasidan PDF yuklab oldi. Eski sertifikatlar (pdf_path NULL va 104 baytli soxta fayl) yuklashda haqiqiy PDF'ga qayta yaratildi.
  - **Kunlik vazifalar va yutuqlar:** o'sha o'quvchi 3 dars → "3 ta dars tugating 3/3" → "Mukofotni olish" (+15 XP, +3 coin); "Birinchi qadam" ochildi → "Olish" (+50 XP, +10 coin) → coin tarixi 13; kurs/test tugagach 4/32 yutuq ochildi ("Kurs tugadi" va b.), olingandan keyin "Olish" tugmasi yo'qoladi.
  - **Admin sozlamalari:** XP/Coin/Battle tablaridagi 16 ta maydonning hammasi +1 qilindi → "Sozlamalar saqlandi!" → qayta yuklashda hammasi saqlangan → asl qiymatlarga qaytarildi; tizim logida faqat o'zgargan maydon yoziladi.
  - **Obuna tarifi:** "Standart" tavsifi va imkoniyatlariga "QA3 imkoniyat" qo'shildi → ro'yxat kartasida va qayta ochilgan formada bor → asl holiga qaytarildi.
  - **Turnirlar:** admin bo'sh forma (brauzer `required`), "QA3 Turnir A/B" (50 coin kirish, 500 coin sovrin, Toshkent vaqti) yaratdi → o'quvchi ikkalasiga "Qo'shilish" (555 → 505 → 455) → A'dan chiqish (UI'da tugma yo'q — QA3-S-01; backend `DELETE …/withdraw` +50 qaytardi) → admin B'ni o'chirishga urindi ("Ishtirokchilari bor turnirni o'chirib bo'lmaydi…") → B "Bekor qilingan" → "kirish to'lovlari qaytarildi" (+50, balans 555) → A tahrirlandi va o'chirildi.
  - **Battle 1v1** (ikki kontekst): pupil2 5:0 g'alaba → header sahifa yangilanmasdan 1 065 → 1 165 XP, 555 → 565 coin (QA2-S-16).
  - **Xato sahifalari:** 403 (o'quvchi → /admin, /teacher), 404 (noma'lum URL, noma'lum sertifikat/kurs, mehmon), 419 (sessiya cookie o'chirilib login yuborildi → "Sahifa eskirdi…" toast), 429 (`/verify` 30/min dan oshirildi → "Xato 429 · Juda ko'p so'rov"; login 6-urinish → "Juda ko'p urinish. 28 soniyadan keyin…") — desktop va 375px.
- **Regressiya crawl (har rol, desktop + 375px, har sahifadagi barcha ichki `<a href>` tekshirildi):** admin 49 GET sahifa, super admin 49, teacher 26, student (pupil1) 74 + student@test.uz 70 — admin/teacher/pupil1 desktop va mobilda, student/super desktopda; jami 417 sahifa ochilishi, 456 ta ichki havola tekshirildi. **500 yo'q, konsol JS xatosi yo'q, `laravel.log` QA3 davomida bitta ham yangi yozuv olmadi (03:33 dagi hajm 2 871 228 bayt o'zgarmadi), gorizontal overflow yo'q.** Crawl'dagi 200 bo'lmagan javoblarning hammasi kutilgan: JSON endpointlar (`/monitor/stats|participants|leaderboard`, `/exam/leaderboard`, `/exam/section` 401 — urinish yakunlangan), boshqa o'quvchining battle/support/payment sahifasi 403/404, ishtirok etmagan olimpiada natijalari 404, bepul bo'lmagan dars preview 403, `/admin/teachers/{o'quvchi id}` 404; slug bilan ochilgan `/learn/course/{slug}/notes|qna|announcements` va `/courses/{slug}/reviews/create` 404 — UI bu sahifalarga id bilan havola beradi va id bilan 200 (crawl artefakti). Yagona buzilgan havola — QA3-S-06.
- **Mobil 375px (QA2-A-05 sahifalari):** `/admin/directions`, `/admin/subscription-plans`, `/admin/certificates/{id}`, `/teacher/dashboard`, `/teacher/courses/{id}/tests/{test}`, `/student/olympiads/{slug}` (+ natija, reyting, javoblar, turnirlar, do'kon, coinlar, admin turnirlar/promo/sozlamalar) — `scrollWidth = 375`, gorizontal overflow yo'q (`shots/mob_*.png`).

### Tasdiqlash jadvali (QA2 — fixer "Tuzatildi" deb belgilaganlar)

| ID | Holat | Dalil |
|---|---|---|
| QA2-S-01 | Verified-fixed | Lab +30/+5 → header/coin tarixi/do'kon = 535; Ninja 500 → 35 hamma joyda; equip saqlanadi (`shots/c2_student_after.png`, `c3_student_equipped.png`, `c4_coins.png`). Olimpiada, turnir, vazifa, yutuq coinlari ham shu balansga tushadi. Kamchilik: taqilgan avatar hech qayerda ko'rinmaydi — QA3-S-04 |
| QA2-S-02 | Verified-fixed | Yangi o'quvchi: 3 dars → vazifa 3/3 + mukofot; "Birinchi qadam" ochildi va olindi; kurs+test → 4/32 yutuq (`shots/m2_phone_ach.png`) |
| QA2-S-03 | Verified-fixed | 4 ta kurs sertifikati yuklandi: 24–26 KB `%PDF-1.7`, `%%EOF`, o'zbekcha matn, QR, tekshirish kodi (`shots/cert_student_pdf.png`); eski NULL/soxta fayllar qayta yaratildi; yangi sertifikatda `pdf_path` darhol yoziladi |
| QA2-S-04 | Verified-fixed | `/student/olympiads`: "Yakunlangan · Ro'yxatda" QA3 va QA2 kartalari + "Natijalar"; `/student/olympiad-history`: 2 ishtirok, o'rin, coin |
| QA2-S-05/06/07 | Verified-fixed (spot) | Kurs sahifasida ustoz javobi ko'rinadi ("Ustoz javobi … Rahmat!"); quiz-dars test o'tilganda yakunlanadi |
| QA2-S-08 | Verified-fixed | O'quvchi sertifikati, verify, admin ro'yxat/sahifa va PDF: "20.10.2026"; "Sertifikat raqami" va "Tekshirish kodi" alohida |
| QA2-S-09 | Verified-fixed | Reytingdagi ismlar `/student/users/{id}` havolasi; profilda "Kuzatish" |
| QA2-S-10 | Verified-fixed | Manba: "Laboratoriya tajribasi", "Laboratoriya nishoni", "Do'kon", "Olimpiada mukofoti", "Turnir (qaytarildi)", "Kunlik vazifa", "Yutuq" |
| QA2-S-11 | Verified-fixed | Katalog: "QA2 Pullik kurs ★ 4.0 (1)" (`shots/s4_catalog.png`) |
| QA2-S-12 | Verified-fixed | 403/404/419/429 o'zbekcha, ilova ko'rinishida, mobil ham (`shots/e1_*.png`, `e2_429_*.png`, `e1_419_inertia.png`) |
| QA2-S-13 | Verified-fixed | 5-vazifa: "2-vazifadagi o'lchov (U = 6 V, I = 60 mA = 0,06 A) bo'yicha…" |
| QA2-S-14 | Verified-fixed | "Ball 5.00 / 5.00 · #1 · 2 ishtirokchidan", "3.00 / 5.00 · #2"; monitor/CSV/JSON rank 1 va 2. Kamchilik: percentil ziddiyati — QA3-S-03 |
| QA2-S-15 | Verified-fixed | "Sertifikat olish" → PDF (OLY-2026-M0EY1TQC, `shots/oly_cert_pdf.png`); 3 marta yuklashdan keyin ham DB'da bitta yozuv. (pupil2'da fix'dan oldingi 3 ta takroriy yozuv qolgan — pastda) |
| QA2-S-16 | Verified-fixed | Battle tugashi bilan header XP/coin yangilandi (sahifa yangilanmadi) |
| QA2-S-17 | Still-broken (kechiktirilgan) | Battle raundlari hali ham ustoz testi savollari: "QA rost/yolg'on savol 2", "Jism 2 s da 10 m yo'l bosdi…" (`shots/b1_end_p1.png`) — fixer ham "Kechiktirildi" deb belgilagan |
| QA2-S-18 | Verified-fixed (qisman) | ELO 980/962 → "Bronza" (yagona shkala). ELO o'zgarmaydigan durang qayta o'ynalmadi |
| QA2-S-19 | Verified-fixed | Bor modeli: "↑ Sakrash" / "↓ Tushish" (`shots/lab2_bohr-atom.png`) |
| QA2-S-20 | Verified-fixed | Yakunlangan olimpiadada "Javoblarni ko'rish" 200, 3/5 to'g'ri. Kamchilik: LaTeX xom va javob harf bilan — QA3-S-02 |
| QA2-A-01 | Verified-fixed | "Natijalarni yakunlash" → "Olimpiada natijalari yakunlandi", holat "Yakunlangan", coin bir marta (+105/+103), `user_olympiad_history` 2 qator, ichki xato toastda yo'q |
| QA2-A-02 | Verified-fixed | Kiritilgan 03:12/03:52/03:54/05:42 — edit, admin show, o'quvchi show/natija, DB'da aynan shu; ro'yxat o'z vaqtida ochiq |
| QA2-A-03 | Verified-fixed | "Standart" (narx = asl narx) tahrirlandi: "Obuna rejasi yangilandi!" |
| QA2-A-04 | Verified-fixed | 16 maydon saqlanadi; tizim logida faqat o'zgargan maydon ("xp_multiplier_premium"). Lekin bu qiymatlar ilovada ishlatilmaydi — QA3-A-01 |
| QA2-A-05 | Verified-fixed | Barcha ko'rsatilgan sahifalar 375px'da overflow'siz |
| QA2-A-06 | Verified-fixed | "Fizika olimpiadasi", "Maktab bosqichi" |
| QA2-A-07 | Verified-fixed | Admin ishtirokchilar "20.10.2026 03:45", natija "03:54/03:57", sertifikat "20.10.2026", tizim logi "03:50" |
| QA2-A-08 | Verified-fixed | Narx 12 000, asl narx 100 → "Asl narx joriy narxdan kam bo'lmasligi kerak" |
| QA2-A-09 | Verified-fixed | Tarif formasida "Imkoniyatlar" (har qator — bitta); VIP Yillik imkoniyatlari tiklangan |
| QA2-A-10 | Verified-fixed | Admin promo kodlari olimpiada "Chegirma kodi"da ishlaydi (50% → 10 000 so'm, 100% → Bepul, noto'g'ri kod → o'zbekcha xato), foydalanish yoziladi |
| QA2-A-11 | Partially-fixed | Admin CRUD, holatlar, kirish to'lovi, bekor qilishda qaytarish, ishtirokchili turnirni o'chirish taqiqi — ishlaydi. O'quvchi turnirdan chiqa olmaydi (UI yo'q) — QA3-S-01 |
| QA2-A-12 | Verified-fixed | Dialog "Kursni nashrdan olish", maydon "Nashrdan olish sababi", toast "Kurs nashrdan olindi!", holat "Nashrdan olingan", qaytarish "Kurs qayta nashr qilindi!" |
| QA2-A-13 | Verified-fixed | Savolsiz olimpiada: "Ro'yxatni ochish" → "Savolsiz bo'lim bor (Test savollari)…" |
| QA2-A-14 | Verified-fixed | `/admin/support` jadvalida mavzu `<a href="/admin/support/{id}">` |
| QA2-T-01 | Verified-fixed | Matnli dars `/chapters` → dars tahririga redirect + "Bu darsda video yo'q…" |
| "VIP Yillik" | Verified-fixed | Tariflar ro'yxatida 7 ta imkoniyat bilan |

**Yakun (35 qator):** Verified-fixed **33** (shundan QA2-S-18 qisman sinalgan), Partially-fixed **1** (QA2-A-11), Still-broken **1** (QA2-S-17 — kechiktirilgan, ma'lum).

### Birinchi bosqich (QA-*) regressiya spot-check
Oqim bilan qayta tekshirildi: QA-S-02 (battle 1v1 oxirigacha), QA-S-03 (my-items, sotib olish, equip), QA-S-05/06/20 (yozilish, matn/videosiz/video darslar, kursni tugatish, sertifikat), QA-S-07/08 (lab kategoriya 200, Om qonuni yakunlash 200 + natija oynasi, XP/coin bir marta), QA-S-09 (dashboard va missions bir xil), QA-S-16/QA-T-14 (sharh va ustoz javobi ko'rinadi), QA-S-18 (quiz-dars kartasi "o'tish 86%", 10/10 test, +40 XP), QA-A-08 (paket validatsiyasi o'zbekcha), QA-A-09 (tarif tahriri), QA-A-12 (olimpiada yaratish), QA-A-13 (sozlamalar saqlanadi), QA-A-15 (nashrdan olish/qayta nashr), QA-P-01 (login 429 forma xatosi), QA-P-02 ("Parollar mos kelmadi" birinchi urinishda). Crawl orqali sahifa darajasida: QA-T-01..13 sahifalari, QA-A-01/02/03/04/05/10/11/14, QA-S-01/10/14/15/19 sahifalari — hammasi 200, kontent bilan. **Regressiya topilmadi.**

### Yangi topilgan xatolar (QA3-…)

#### QA3-A-01 — Admin "Tizim sozlamalari" (XP/Coin/Battle) saqlanadi, lekin ilova ularni ishlatmaydi
- **Severity:** Major
- **URL:** `/admin/settings` (XP, Coin, Battle tablari)
- **Qadamlar:** sozlamalarda "Dars uchun XP 30", "Test uchun XP 100", "Battle g'alabasi 150 XP", "Dars uchun coin 5", "Test uchun coin 25", "Battle g'alabasi coin 35" turibdi → yangi o'quvchi 3 dars tugatdi va testdan 100% bilan o'tdi; pupil2 battle'da g'alaba qozondi.
- **Kutilgan:** dars uchun 30 XP + 5 coin, test uchun 100 XP + 25 coin, battle g'alabasi 150 XP + 35 coin (admin kiritgan qiymatlar).
- **Haqiqiy:** 3 dars → jami ~21 XP va 0 coin (header "41 XP", yozilish bonusi bilan), test → "+40 XP", 0 coin; battle → "+10 coin", +100 XP. Sozlamani o'zgartirish natijaga ta'sir qilmaydi.
- **Dalil:** `shots/m2_phone_ach.png`, `shots/g2_phone_result.png`, `shots/b1_end_p2.png`. Kod: `xp_per_lesson`, `coin_per_lesson`, `coin_per_test_pass`, `coin_per_battle_win`, `coin_monthly_limit_*`, `battle_daily_limit_*`, `coin_transfer_fee_percent` kalitlari faqat `Admin/SettingController` va `Pages/Admin/Settings/Index.vue` da uchraydi; `XPRewardService` qattiq konstantalar ishlatadi (`XP_LESSON_TEXT = 8`, `XP_LESSON_VIDEO = 5`, `XP_TEST_HIGH = 40`, `XP_BATTLE_WIN = 100`).
- **Taxminiy sabab:** QA2-A-04 da sahifa faqat saqlanadigan qilindi; mukofot servislari `Setting`/`SettingsService` dan o'qimaydi.

#### QA3-S-01 — O'quvchi turnirdan chiqa olmaydi (UI'da "Chiqish" tugmasi yo'q)
- **Severity:** Major
- **URL:** `/student/tournaments/{id}` (ro'yxatdan o'tgandan keyin)
- **Qadamlar:** pupil2 "QA3 Turnir A" (50 coin) → "Qo'shilish" → "Turnirga yozildingiz", 505 coin → turnir sahifasini qayta ochish.
- **Kutilgan:** "Turnirdan chiqish" tugmasi (coin qaytariladi).
- **Haqiqiy:** faqat "Siz ishtirok etyapsiz" belgisi, sahifada birorta tugma yo'q; ro'yxat sahifasida ham yo'q (`Pages/Student/Tournaments/*` da withdraw chaqiruvi umuman yo'q). Backend ishlaydi: `DELETE /student/tournaments/{id}/withdraw` → `{"success":true,"message":"Turnirdan chiqdingiz, coin qaytarildi"}`, tarixda "Turnirdan chiqish … +50", balans 505. Hozir coin faqat admin turnirni bekor qilsa qaytadi.
- **Dalil:** `shots/t2_pupil2_A_wd.png`, `shots/t3_after_wd.png`, `shots/t3_coins.png`.

#### QA3-S-02 — Olimpiada "Javoblarni ko'rish": LaTeX xom, javob faqat harf, to'g'ri javob ko'rsatilmaydi
- **Severity:** Minor
- **URL:** `/student/olympiads/{slug}/results/review`
- **Haqiqiy:** savol matni "$2 \cdot 10^8$ m/s", "($c = 4200$ J/(kg·°C))" ko'rinishida (imtihon sahifasida KaTeX to'g'ri chiziladi); savol ochilganda "Sizning javobingiz: d" — variant matni yo'q, to'g'ri variant ko'rsatilmaydi, o'quvchi xatosini tushuna olmaydi.
- **Dalil:** `shots/o11_pupil1_review.png`, `shots/o12_pupil1_review_q4.png`.

#### QA3-S-03 — Olimpiada percentili sahifalar orasida qarama-qarshi
- **Severity:** Minor
- **URL:** `/student/olympiads/{slug}/results` va `/results/leaderboard`
- **Haqiqiy:** pupil1 (#2 / 2): natija sahifasida "Percentil Top 100%", reyting sahifasida "Sizning o'rningiz #2 · Top 0%"; DB `percentile = 0`. pupil2 (#1 / 2) ikkalasida "Top 50%". Bitta formula va tushunarli matn kerak.
- **Dalil:** `shots/o11_pupil1_results.png`, `shots/o11_pupil1_leaderboard.png`.

#### QA3-S-04 — Taqilgan (equip) do'kon buyumi hech qayerda ko'rinmaydi
- **Severity:** Minor
- **Qadamlar:** student@test.uz "Ninja avatari"ni sotib oldi → "Taqish" → "Taqilgan".
- **Kutilgan:** avatar header/profil/reytingda ko'rinadi.
- **Haqiqiy:** header, profil, reyting, battle — hammasida avvalgidek "AV" bosh harflari; `auth.user.avatar = null`. Equip faqat "Mening buyumlarim"dagi belgi.
- **Dalil:** `shots/c3_student_equipped.png`, `shots/c4_profile.png`.

#### QA3-S-05 — Ishtirok etmagan o'quvchi uchun olimpiada reytingidagi "Natijalar" havolasi 404
- **Severity:** Minor
- **URL:** `/student/olympiads/{slug}/results/leaderboard` (student@test.uz, olimpiadada qatnashmagan)
- **Haqiqiy:** reyting sahifasi 200, lekin breadcrumb "Natijalar" → `/results` → 404 "Sahifa topilmadi" (crawl'dagi yagona buzilgan havola).
- **Dalil:** `crawl_student.json` (`badlinks`), `shots/c6_lb.png`.

#### QA3-A-02 — Admin olimpiada monitoridagi "To'liq reyting" xom JSON ochadi
- **Severity:** Minor
- **URL:** `/admin/olympiads/{id}/monitor` → "To'liq reyting" (`/monitor/leaderboard`)
- **Haqiqiy:** brauzerda `{"leaderboard":[{"rank":1,"rank_change":0,"user_id":…` matni. Qo'shimcha: natijalar CSV'i o'rin bo'yicha emas, ism bo'yicha tartiblangan.
- **Dalil:** `shots/o9_monitor_leaderboard.png`.

#### QA3-A-03 — Admin olimpiadani o'chira yoki qoralamaga qaytara olmaydi
- **Severity:** Minor
- **Qadamlar:** "QA3 Savolsiz olimpiada" yaratildi → "E'lon qilish (tez kunda)".
- **Haqiqiy:** olimpiada o'quvchilar ro'yxatida "Tez kunda" bo'lib turibdi; admin menyusida faqat "Ko'rish / Tahrirlash / Ro'yxatdagilar" — o'chirish (route `DELETE admin/olympiads/{olympiad}` bor) yoki "Qoralama"ga qaytarish UI'da yo'q.
- **Dalil:** `shots/a1_a13.png`.

#### QA3-P-01 — `/verify` faqat tekshirish kodini qabul qiladi, sertifikat raqamini emas
- **Severity:** Minor
- **Haqiqiy:** `/verify/KRQ3C9LKI3D2` → tasdiqlandi; sertifikatda katta yozilgan raqam bilan `/verify/EDULIFE-2026-94BC5A3C` (yoki `OLY-2026-M0EY1TQC`) → "Sertifikat topilmadi". Sahifada ikkala maydon ham bor, foydalanuvchi qaysi kodni kiritishni bilmaydi.
- **Dalil:** `shots/v1_EDULIFE_2026_94BC5A3C.png`.

#### QA3-S-06 — Kosmetik kamchiliklar
- **Severity:** Cosmetic
- Olimpiada sertifikati modalidagi "PNG" tugmasi ham `.pdf` yuklaydi (`sertifikat-OLY-2026-M0EY1TQC.pdf`).
- `/verify/{olimpiada kodi}`: "Yo'nalish: 2-o'rin sertifikati" (yorliq noto'g'ri).
- Admin sertifikat sahifasi: "QR kod — QR mavjud emas", PDF'da esa QR bor.
- Header XP "1,772 XP" / "1,065 XP" (inglizcha vergul), boshqa joylarda "1 772".
- Kunlik vazifa "30 daqiqa video tomosha qiling … 0 / 1800" (soniyalar).
- Turnir sahifasi "Sovrinlar: 0 XP" — admin 500 coin sovrin jamg'armasi kiritgan.
- O'quvchi olimpiada sahifasi "Tavsif kiritilmagan." — admin "Qisqa tavsif" kiritgan (u faqat ro'yxat kartasida chiqadi).
- Katalogda "Saralash" select'i bo'sh turadi (tanlangan qiymat ko'rinmaydi).
- Coin tarixida "Jami olingan" turnirdan qaytarilgan 50 coinni ham qo'shadi (605); migratsiyadan oldingi yozuvlarda "Balans" ustuni ketma-ket emas (pupil2: 10, 13, 330, 335, 348).

### Yangi xatolar — severity bo'yicha

| Severity | Soni | ID |
|---|---|---|
| Blocker | 0 | — |
| Critical | 0 | — |
| Major | 2 | QA3-A-01, QA3-S-01 |
| Minor | 7 | QA3-S-02, S-03, S-04, S-05, A-02, A-03, P-01 |
| Cosmetic | 1 (9 ta kichik band) | QA3-S-06 |

### 3-bosqichda yaratilgan/o'zgartirilgan ma'lumotlar (edulife_shanba)
- Promo kodlar **QA3FULL** (100%, 2 marta ishlatilgan) va **QA3HALF** (50%, 1 marta).
- Olimpiada **"QA3 Fizika olimpiadasi"** (id 01a15654-afaf-…, slug qa3-fizika-olimpiadasi-5xc1ks, Yakunlangan; pupil2 5/5 #1, pupil1 3/5 #2; +998907776655 "To'lov kutilmoqda" 10 000 so'm; sertifikat OLY-2026-M0EY1TQC). **"QA3 Savolsiz olimpiada"** (01a15671-…, "Tez kunda", savolsiz — UI'dan o'chirib bo'lmadi, QA3-A-03; o'quvchilar ro'yxatida ko'rinadi).
- Turnir **"QA3 Turnir B"** (Bekor qilingan, coin qaytarilgan); "QA3 Turnir A" yaratilib o'chirildi.
- student@test.uz: Om qonuni yakunlandi (+30/+5), "Ninja avatari" sotib olindi va taqildi (balans 35).
- +998907776655 (Yangi Talaba): QA Kurs'ga yozildi va tugatdi, test 100%, sertifikat EDULIFE-2026-8F3F8D66, "3 ta dars tugating" va "Birinchi qadam" mukofotlari olindi (balans 13).
- pupil1/pupil2: yangi battle (pupil2 5:0), olimpiada coinlari (pupil1 304, pupil2 565).
- Admin: sozlamalar +1 qilinib asl qiymatlarga qaytarildi (premium ko'paytiruvchi 1.5); "Standart" tarif tavsifi/imkoniyatlari o'zgartirilib qaytarildi; "QA2 Pullik kurs" nashrdan olinib qayta nashr qilindi; "QA3 paket" saqlanmadi (validatsiya).
- **Eslatma:** pupil2 da fix'dan oldin (02:12–02:24) yaratilgan 3 ta takroriy olimpiada sertifikati (OLY-2026-GO0RYQ8N, -Y92EHGLH, -TPCUTDRD — bitta olimpiada uchun) tozalanmagan.

### Yakuniy tayyorlik xulosasi
**End-to-end ishlaydi (brauzerda tasdiqlandi):**
- Ro'yxatdan o'tish/login (o'zbekcha xatolar, 429/419 xabarlari), rollar bo'yicha ruxsatlar va o'zbekcha 403/404/419/429 sahifalari.
- Ustoz: kurs, darslar, testlar, e'lonlar, Q&A, sharhga javob; admin moderatsiyasi (tasdiqlash, nashrdan olish/qayta nashr).
- O'quvchi: bepul kursga yozilish → darslar (matn, video, quiz-test) → kursni tugatish → haqiqiy PDF sertifikat (QR) → ochiq `/verify/{kod}`.
- Gamifikatsiya: yagona coin/XP balansi (lab, nishon, vazifa, yutuq, battle, olimpiada, turnir, do'kon) header/tarix/do'konda bir xil; do'kondan sotib olish va equip; kunlik vazifalar va yutuqlar progress oladi, mukofot bir marta olinadi.
- Olimpiada: Toshkent vaqtida yaratish → savol banki → holatlar → admin promo kodlari → imtihon → jonli o'rinlar → yakunlash → coin/tarix → javoblarni ko'rish → PDF sertifikat va tekshirish.
- Battle 1v1, admin turnir CRUD + bekor qilishda coin qaytarish, admin sozlamalari va tariflar (imkoniyatlar bilan) saqlanadi.
- Barcha rollarda 417 sahifa ochilishi: 500 yo'q, JS xatosi yo'q, `laravel.log` toza, 375px'da overflow yo'q.

**Hali yetishmaydi / ochiq:**
- QA3-A-01 (Major): admin XP/coin/battle sozlamalari amalda ishlamaydi — mukofotlar qattiq kodlangan; darslar va testlar coin bermaydi.
- QA3-S-01 (Major): turnirdan chiqish UI'si yo'q.
- QA2-S-17: battle savollari hali ham baholanadigan kurs testlaridan (mahsulot qarori kutilmoqda).
- Onlayn to'lov yo'q (kurs, obuna, coin paketlari, pullik olimpiada — faqat admin "Kursga yozish", coin bilan to'lash yoki 100% promo): ma'lum cheklov.
- Minor: olimpiada javoblar tahlili (LaTeX, to'g'ri javob), percentil, equip ko'rinmasligi, reytingdan 404 havola, admin "To'liq reyting" JSON, olimpiadani o'chirish UI'si, verify faqat kod bilan; kosmetik bandlar (QA3-S-06).

**Verdikt:** platforma asosiy o'quv, sertifikat, gamifikatsiya va olimpiada oqimlarida **ishlab chiqarishga deyarli tayyor** — Blocker/Critical yo'q. Ishga tushirishdan oldin QA3-A-01 (sozlamalarni mukofotlarga ulash yoki sahifadan olib tashlash) va QA3-S-01 (turnirdan chiqish tugmasi)ni tuzatish, QA3-S-02 (olimpiada javoblar tahlili)ni yaxshilash tavsiya etiladi; to'lov tizimi alohida ish sifatida qoladi.

QA3 COMPLETE

## Tuzatuvchi — 3-bosqich natijalari

Sana: 2026-10-20. Branch `main`, DB `edulife_shanba` (yangi migratsiyalar `2026_10_21_000012`, `2026_10_21_000013` ishga tushirildi). Yakuniy holat: `php artisan test` — 367 passed; `npx vitest run` — 1217 passed; `npm run build` — OK. Brauzer tekshiruvi (Python Playwright, har rolga bitta login: student@test.uz, pupil1, admin, mehmon; skript va skrinshotlar: `scratchpad\fix3\verify.py`, `scratchpad\fix3\shots\`): konsol xatosi yo'q, `laravel.log` ga yangi yozuv tushmadi, o'zgargan sahifalar 375px'da `scrollWidth = 375`.

**QA3-A-01 qarori:** mukofot kodi endi `RewardSettings` orqali sozlamalardan o'qiydi; har bir kalitning default qiymati — shu paytgacha kodda bo'lgan qiymat. Jadvaldagi seeder qiymatlari (30 XP, 1 500 coin/oy, kuniga 3 battle…) hech qachon qo'llanilmagan edi: ularni shundayligicha yoqish barcha o'quvchilar mukofotini (va hammasi "trial" bo'lgani uchun battle/coin limitlarini) bir zumda o'zgartirardi. Shuning uchun migratsiya **faqat seeder qiymatida turgan** qatorlarni hozirgi amaldagi qiymatga tenglashtirdi (dars 8 XP / 0 coin, test 40 XP / 0 coin, battle g'alabasi 100 XP / 10 coin, ko'paytiruvchilar 1, limitlar 0 = cheksiz, ELO K 20); admin o'zgartirgan qiymat saqlanadi. Admin sahifada qiymat o'zgartirilsa — keyingi mukofot shu qiymat bilan (kesh `Setting` saqlanganda tozalanadi). Dars XP turiga nisbatan (video ×5/8, amaliy ×15/8), test XP/coin natija bandiga nisbatan (70–89% ×5/8, 50–69% ×1/4) hisoblanadi — tavsiflar sahifada. `coin_transfer_fee_percent` sahifadan olindi (o'tkazma funksiyasi yo'q); VIP ko'paytiruvchi va barcha kunlik battle limitlari qo'shildi.

| ID | Holat | Commit / izoh |
|---|---|---|
| QA3-A-01 | Tuzatildi | bb6eab7 — dars/test/battle XP va coin, premium/VIP ko'paytiruvchi, oylik coin limiti (qaytarishlar hisobga olinmaydi), kunlik battle limiti, battle savol soni/vaqti, ELO K sozlamalardan; testlar o'zgargan sozlama mukofotni o'zgartirishini tasdiqlaydi |
| QA3-S-01 | Tuzatildi | 620222f — "Turnirdan chiqish" + tasdiqlash oynasi ("5 coin balansingizga qaytariladi"), toast "Turnirdan chiqdingiz, 5 coin qaytarildi", header 30 → 35; turnir boshlangach chiqish taqiqlanadi |
| QA3-S-02 | Tuzatildi | 4aacc39 — savol/variant/izoh `RichContent` (Markdown + KaTeX) bilan; har variant matni, "Sizning javobingiz" va (results_config ruxsat bersa) "To'g'ri javob"; ruxsat bo'lmasa to'g'ri javob umuman yuborilmaydi |
| QA3-S-03 | Tuzatildi | 17c8212 — yagona `top_percent = ⌈o'rin / ishtirokchilar × 100⌉`: pupil1 natijada ham, reytingda ham "Top 100% · 2 ishtirokchidan" |
| QA3-S-04 | Tuzatildi | c81ffcf — taqilgan avatar header, profil, reyting va battle'da (`avatar_url`); profilda taqilgan nishon; seeder avatar/nishonlari mavjud bo'lmagan fayllarga ishora qilardi → `public/images/shop/*.svg` (do'kon kartalarida ham) |
| QA3-S-05 | Tuzatildi | be80266 — ishtirok etmaganga reytingda "Natijalar" havolasi yo'q |
| QA3-A-02 | Tuzatildi | ca4906a — "To'liq reyting" → `Admin/Olympiad/Leaderboard` sahifasi (JSON so'rovlarga JSON qoladi); natijalar CSV o'rin bo'yicha |
| QA3-A-03 | Tuzatildi | 7365d8f — "Boshqa amallar": Qoralamaga qaytarish (ro'yxatdagilar yo'q), Bekor qilish (jonli bo'lmaguncha; coin qaytariladi), O'chirish (ro'yxat/urinish yo'q); ro'yxatda ham O'chirish; hammasi tasdiqlash oynasi bilan |
| QA3-P-01 | Tuzatildi | dba2d52 — `/verify/EDULIFE-2026-…`, `/verify/OLY-2026-…` (katta-kichik harf farqsiz) tasdiqlanadi; topilmasa qayta qidirish maydoni |
| QA3-S-06 | Tuzatildi (8/9) | ab3d860 "PNG" olib tashlandi ("PDF yuklab olish"); 018704b verify: "Yo'nalish" = fan, alohida "Sertifikat turi"; 6c63194 admin sertifikat sahifasida QR; 428d036 header "1 772 XP"; b3ecc9e "0 / 30 daqiqa"; 6d9af1e "500 coin sovrin jamg'armasi" (nol sovrinlar ko'rsatilmaydi); be8447f olimpiada sahifasida qisqa tavsif; 9e9c1b3 "Saralash" = "Eng mashhur" (bo'sh `filters` = `[]`, `[].sort` funksiya edi); 02a1ccb "Jami olingan" qaytarishlarsiz, "Jami sarflangan" qaytarishlarni ayirib |
| QA3-S-06 (balans ustuni) | Kechiktirildi | Migratsiyadan oldingi coin tarixi yozuvlaridagi `balance_after` ikki eski hisobdan kelgan (pupil2: 10, 13, 330…); tarixiy ma'lumot qayta yozilmadi |
| QA2-S-17 | Tuzatildi (keyinroq) | 9d8cac8 — ikkala o'yinchiga bir xil, bir xil darajadagi tasodifiy savollar; ball qiyinlikka qarab (docs/battle/SCORING.md); 91718c2 — parametrlar admin sozlamalarida |
| To'lov tizimi | Qo'shildi (keyinroq) | bdf6394, 58dd39c — Payme va Click (docs/payments/PAYMENTS.md); kalitlarsiz tugmalar o'chiq |

**Ma'lumotlar tozalash (edulife_shanba):** "QA3 Savolsiz olimpiada" yangi admin "O'chirish" oqimi orqali brauzerda o'chirildi ("Olimpiada o'chirildi", soft delete). pupil2 ning takroriy olimpiada sertifikatlari OLY-2026-Y92EHGLH va OLY-2026-TPCUTDRD o'chirildi, urinishga bog'langan OLY-2026-GO0RYQ8N qoldi. Tekshiruv uchun vaqtinchalik "QA3 Fix turnir (tekshiruv)" (5 coin) yaratildi, student@test.uz qo'shilib chiqdi (coin tarixida −5/+5, balans 35 o'zgarmadi), so'ng turnir o'chirildi. Shop avatar/nishon rasmlari va mukofot sozlamalari migratsiyalar bilan yangilandi (yuqorida).

**Topilgan, lekin tuzatilmagan:** "30 daqiqa video tomosha qiling" vazifasi progress olmaydi — pleer `video-log` endpointini chaqirmaydi (alohida ish). *Keyinroq tuzatildi: c02a935.*
