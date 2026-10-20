# Bosqich hisobotlari

Branch: `feature/shanba-imtihoni` (loyiha papkasi `C:\xampp\htdocs\edulife`). Push qilinmagan.
Egasining commit qilinmagan ishlari (`main` dagi migratsiya nomlari, `DailyMissionSeeder`) `git stash` da saqlangan:
`git stash list` → *"user WIP on main … before switching to feature/shanba-imtihoni"*.
Qaytarish: `git checkout main && git stash pop`.

---

## Bosqich 1 — Poydevor tuzatishlari

### Nima qilindi
- **Test infratuzilmasi** (`f675c15`):
  - factory'lar sxemaga moslandi (`DirectionFactory`, `CourseFactory`, `ShopItemFactory`);
  - `TestCase::withoutVite()` yoqildi;
  - login testlari endi `login` maydonini yuboradi;
  - ro'yxatdan o'tishdagi `MassAssignmentException` tuzatildi.
- **Xavfsizlik bo'yicha audit tuzatishlari** (`wip/authz` → `feb41fa`), 17 ta:
  - policy'lar va `AuthorizesRequests`;
  - nested route scoping;
  - pullik kontent oshkor bo'lishi;
  - admin rollarini faqat super_admin boshqaradi;
  - bloklangan foydalanuvchilar;
  - battle javoblari oshkor bo'lishi;
  - support ichki eslatmalari;
  - turnir to'lovining atomikligi;
  - fayl yuklash;
  - event'larning ikki marta ro'yxatdan o'tishi;
  - demo parollar;
  - sertifikatlar;
  - PII yashirish;
  - sozlamalar;
  - SQL bindings;
  - video egaligi;
  - `course_qna` indeksi.
- **Runtime/sxema tuzatishlari** (`wip/runtime` → `b8acd5e`):
  - mukofotlar atomikligi va `coin_transactions`;
  - `LessonProgress`;
  - test baholash, `max_attempts = 0` = cheksiz;
  - kurs maydonlari;
  - Q&A bildirishnomasi;
  - admin sahifalari;
  - bildirishnomalar;
  - video transcoding;
  - `ExampleTest`;
  - admin savollarining o'lik route'lari.
- **Frontend va paketlar** (`wip/frontend` → `9b53a31`):
  - Ziggy (330 ta `route()` chaqiruvi, 0 ta mos kelmaydigan);
  - axios/polling tuzatishlari;
  - yetishmagan importlar;
  - `text_content`;
  - `maatwebsite/excel` 1.x → 3.1;
  - Laravel 12.41 → 12.70.
- Kompilyatsiya qilingan Blade view'lar git kuzatuvidan chiqarildi (`39b9358`).

### Natijalar
| | Oldin (`origin/main`) | Hozir |
|---|---|---|
| `php artisan test` | 21 tadan 20 tasi yiqilgan | **221 / 221 o'tadi** |
| `npx vitest run` | yo'q edi | **1172 / 1172 o'tadi** |
| `migrate:fresh --seed` (SQLite) | yiqilardi | o'tadi |
| `composer audit` | 76 advisory | **0** |
| `npm audit` | 31 (4 critical) | 10 (faqat major yangilash bilan tuzaladi: vitest 5, tailwind 4) |
| route'lar | 792 | 373 |

---

## Bosqich 2 — Ma'lumotlar modeli (`wip/examdb`)
Batafsil: `docs/exam/02_DATA_MODEL.md`.
- **Jadvallar:**
  - imtihon tuzilmasi: `exam_subjects`, `exam_topics` (daraxt), `exam_blueprints` va qoidalar, `exam_sessions`, `exam_variants`, `exam_attempts`, `exam_answers`;
  - savollar banki: `question_bank` (kengaytirilgan), `question_bank_options` (`is_correct` NULL), `question_bank_answers` (numeric / numeric_with_unit);
  - o'quvchi ma'lumotlari: mastery, haftalik natijalar, `exam_student_profiles`;
  - aloqalar: `guardian` roli, `guardian_student`, `telegram_accounts`, referral jadvallari, `question_error_reports`.
- **Tasdiqlanmagan savol imtihonga tushmaydi:** buni `QuestionApprovalGuard` ushlaydi. Unga ko'ra `single_choice` da aynan 1 ta to'g'ri variant bo'lishi shart.
- **Seed:**
  - 2 fan, 26 bo'lim, 82 mavzu;
  - 130 ta tasdiqlangan demo savol, javoblari dastur bilan qayta hisoblab tekshirilgan;
  - har fan uchun qabul testi va shanba imtihoni blueprint'i.

## Bosqich 2b — Platformani 2 fanga moslash
- **Ingliz tili to'liq olib tashlandi:** 821 fayl, 54 jadval uchun tozalash migratsiyasi.
- **Boshqa fanlar olib tashlandi** (`wip/subjects`):
  - faqat `math` va `physics` yo'nalishlari qoldi;
  - rus tili va dasturlash olimpiadasi, coding qismi olib tashlandi;
  - mavjud bazada boshqa yo'nalishlar **o'chirilmaydi, nofaol qilinadi**, chunki o'chirish ularga bog'langan kurslarni ham cascade bilan o'chirardi;
  - takroriy `mathematics` yo'nalishi `math` ga birlashtiriladi.
- **Bitta ustoz rejimi** (`wip/teacher`):
  - olib tashlandi: reyting/tier, komissiya, daromad, payout, bank hisoblari (22 route);
  - moliyaviy jadvallar (`teacher_earnings`, `teacher_payouts`, `teacher_bank_accounts`) saqlanib qoldi.
- **Fizika laboratoriyasi:**
  - 59 ta simulyatsiyaning formulalari tuzatildi;
  - 142 ta formula va hisoblangan topshiriqlar qo'shildi;
  - topshiriqlar serverda tekshiriladi (`wip/labcheck`), to'g'ri javoblar brauzerga yuborilmaydi.

---

## Ma'lum cheklovlar / qolgan ishlar
1. **Battle tuzilishi buzuq:** `battles` jadvali eski 2024-yil migratsiyasidan qolgan (`current_round`, `total_rounds`, `waiting` holati yo'q). `Battle/Match.vue` va `History.vue` sahifalari yo'q. Battle'ni ishlatish uchun sxema va UI qayta qurilishi kerak (Bosqich 2b davomi).
2. **Turnir sxemasi mos emas:** jadvalda `entry_fee_coins` bor, kod esa `entry_fee` va `min_level` ni o'qiydi. Natijada to'lov amalda olinmaydi.
3. **Olimpiada imtihon oqimi ishlamaydi:** 7 ta controller metodi yo'q. Anti-cheat'dagi device lock va heartbeat xatolari Exam dvigateli uchun Bosqich 5 da tuzatiladi.
4. **Yo'q metodlar va sahifalar:** ~30 route mavjud bo'lmagan controller metodlariga ishora qiladi, ~20 Vue sahifa mavjud emas (Shop, XP, Support/Show, Friends va boshqalar).
5. **XP va coin bitta manbada emas** (Q-3): `users.*` va `student_profiles.*` ikkalasi ishlatiladi. Admin leaderboard `student_profiles.xp` ga o'tkazildi, header esa hali `users.*` ni ko'rsatadi.
6. **`teacher_profiles` qatori hech qayerda yaratilmaydi**, shuning uchun ustoz profilini saqlash yiqiladi. Profil saqlashda `expertise` va `experience_years` ustunlari ham mavjud emas.
7. **`Student/TestController@index` yiqiladi:** u mavjud bo'lmagan `module` va `lesson` relation'larini yuklaydi.
8. **Ustoz talabalarning emailini ko'rmaydi** (PII yashirildi). Kerak bo'lsa, `makeVisible('email')` qilinadi.
9. **MySQL'da tekshirilmagan:** ENUM o'zgarishi, `->change()` va cleanup migratsiyalari hali MySQL'da hech qachon ishga tushirilmagan. CI'da MySQL bilan bir marta ishga tushirish kerak.

## Savollar
- **Telefon:** ota-ona va o'quvchi bitta telefonni ishlatsa nima qilamiz? `users.phone` unique.
- **Maktablar ro'yxati:** manba kerak. Hozir `school_name` erkin matn sifatida saqlanadi.
- **Demo savollar:** ularni ustoz ko'rib chiqishi kerak. To'g'ri variantni ustoz emas, seeder hisoblab qo'ygan.
- **Mastery formulasi:** taklif — eksponensial o'rtacha, haftalik imtihonda α = 0,3, qabul testida α = 1. Bosqich 5 da tasdiqlanadi.

## Keyingi bosqichga tayyormi: **HA**
Bosqich 3 — ustoz paneli:
- mavzular CRUD;
- savollar banki;
- tekshiruv navbati;
- KaTeX `<MathContent>`;
- Excel/CSV/JSON importi.

---

## Bosqich 3 — Ustoz paneli
Savollar banki, mavzular daraxti, tekshiruv navbati va import. Hammasi mavjud `QuestionReviewService`, `QuestionApprovalGuard`, `Topic` daraxti va `question_bank` + `question_bank_options` + `question_bank_answers` ustiga qurildi. Yangi ustun ham, migratsiya ham kerak bo'lmadi.

### Nima qilindi (fayllar ro'yxati bilan)
- **Servislar** (`app/Services/Exam/`):
  - `TopicTreeService` — mavzular daraxti (savollar soni bilan), qo'shish (kod avtomatik yaratiladi: `math.kombinatorika_va_ehtimollik`), nomini o'zgartirish, ko'chirish (butun ichki daraxtning `level` i yangilanadi, o'z ichiga va boshqa fanga ko'chirib bo'lmaydi), drag-sort. Savollar, ichki mavzular, blueprint qoidalari, mastery yoki LMS kontenti bog'langan mavzu **o'chirilmaydi, faolsizlantiriladi**;
  - `QuestionEditorService` — savolni yaratish va tahrirlash (variantlar A–E yoki son javob). Tasdiqlangan savolning sharti yoki javobi o'zgarsa, u **qayta `in_review` ga tushadi** va imtihonlardan chiqadi. Tasdiqlash doim `QuestionReviewService::approve` orqali o'tadi (guard ishlaydi, eski olimpiada ustunlari sinxronlanadi). Shuningdek bir bosishda belgilash, son javobni saqlash va nusxa yaratish;
  - `QuestionImportService` — CSV, Excel (`xlsx/xls/ods`, `maatwebsite/excel` olib kelgan PhpSpreadsheet orqali) va JSON. Dublikatlar `QuestionText::hash` bo'yicha aniqlanadi: bankdagilar bilan ham, fayl ichida ham. Uchta formatdagi shablon fayllar ham shu yerda yaratiladi.
- **Controller'lar** (`app/Http/Controllers/Teacher/Exam/`):
  - `TopicController`, `QuestionController`, `ReviewController`, `ImportController`;
  - umumiy validatsiya va sahifa ma'lumotlari: `Concerns/HandlesBankQuestions.php`.
- **Route'lar** (`routes/teacher.php`, «stage 3» bloki; `auth` + `role:teacher,admin,super_admin`):
  - `teacher.exam.topics.*`;
  - `teacher.exam.questions.{index,create,store,edit,update,destroy,duplicate,retire}`;
  - `teacher.exam.review` va `teacher.exam.review.{mark,answer,update,approve,reject}`;
  - `teacher.exam.import.{index,template,upload,preview,confirm}`.
- **Sahifalar** (`resources/js/Pages/Teacher/Exam/`):
  - `Topics/Index` — fanlar bo'yicha tablar, daraxt, drag-sort, nomini joyida o'zgartirish, ko'chirish va o'chirish oynalari;
  - `Questions/Index` — fan, mavzu (ichki daraxti bilan), qiyinlik, tur, kelib chiqishi, format, manba va holat bo'yicha filtrlar, qidiruv, sahifalash; shart va variantlar KaTeX bilan ko'rinadi;
  - `Questions/Create`, `Questions/Edit` — `single_choice` (4–5 variant), `multiple_choice`, `numeric`, `numeric_with_unit` (birlik, abs/rel xatolik, qo'shimcha birliklar va SI uchun tayyor koeffitsiyentlar, doimiylar `g = 10 m/s²`), yechim matni va video havolasi. Kiritish Markdown+LaTeX'da, ko'rinish o'quvchi sahifasidagi bilan bir xil (`RichContent`);
  - `Review/Index` — **tekshiruv navbati**: bitta savol katta ko'rinishda, variantlar KaTeX bilan, manba, mavzu va qiyinlik ko'rsatiladi. Klaviatura: `1–5` — to'g'ri variantni belgilash, `Enter` — tasdiqlash, `R` — rad etish (sabab bilan, tayyor sabablar ham bor), `N` yoki `→` — keyingi savol, `E` — shart va variantlarni joyida tahrirlash. Mavzu, fan va qiyinlik bir bosishda saqlanadi. Progress: «Bu hafta tasdiqlandi: N / 100» (`config/exam.php` → `bank.weekly_review_target`). Navbatni fan va kelib chiqishi bo'yicha filtrlash mumkin;
  - `Import/Index`, `Import/Preview` — fayl yuklash, shablonlarni yuklab olish, oldindan ko'rish (xatolar, ogohlantirishlar, dublikatlar). Kerakmas qatorlarni olib tashlash mumkin. Fayldagi javoblar faqat ustoz alohida belgi qo'ysa qabul qilinadi.
- **Komponentlar** (`resources/js/Components/Exam/Bank/`): `QuestionForm`, `OptionsEditor`, `NumericAnswerFields`, `TopicTree`, `bank.js` (yorliqlar, birlik presetlari, klaviatura xaritasi).
- **Navigatsiya:**
  - `TeacherLayout` ga «Savollar banki» va «Tekshiruv navbati» qo'shildi; ikkinchisida kutayotgan savollar soni ko'rinadi;
  - bu son `HandleInertiaRequests` dagi lazy `exam_review_pending` prop'idan olinadi (faqat teacher/admin uchun, bitta indeksli `count`).
- **Konfiguratsiya:** `config/exam.php` yaratildi, uning `bank` blokida: haftalik reja, sahifa hajmi, import cheklovlari (500 qator, 4 MB, preview 120 daqiqa).

**Asosiy qoida server tomonida:**
- `approve` endpoint'i `QuestionApprovalGuard` ga tayanadi. To'g'ri variant belgilanmagan savol tasdiqlanmaydi: so'rov `approve` xatosi bilan qaytadi, savol holati o'zgarmaydi.
- Import qilingan savollar har doim `in_review` holatida tushadi. Fayldagi javob ustoz tasdiqlamaguncha qabul qilinmaydi: variantlarda `is_correct = NULL`, son javobda `value = NULL`.

### Migratsiyalar
Yo'q: mavjud ustunlar yetarli bo'ldi.

### Testlar: nima tekshirildi, natija (php artisan test chiqishi)
| Fayl | Tekshiradi |
|---|---|
| `tests/Feature/Exam/ReviewQueueTest.php` (11) | to'g'ri variantsiz approve bloklanadi (holat va variantlar o'zgarmaydi); begona variant rad etiladi; belgilash → Enter → keyingi savol; multiple_choice'da belgi yoqilib-o'chiriladi; son javobsiz approve bloklanadi, birlik bilan saqlangach (`0,01`) o'tadi; rad etishga sabab shart; joyida tahrirlashda belgilar saqlanadi; boshqa fanning mavzusi rad etiladi; keyingi savolga o'tish aylanadi va fan bo'yicha filtrlanadi; haftalik progress (demo va o'tgan haftalar hisobga kirmaydi) |
| `tests/Feature/Exam/QuestionBankTest.php` (11) | 9 xil filtr va holatlar soni; bir qadamda yaratish va tasdiqlash (legacy sinxron); belgisiz savol saqlanadi, lekin tasdiqlanmaydi; validatsiya (4–5 variant, bitta to'g'ri, mavzu va fan mosligi, `javascript:` URL); 5 variantli multiple_choice; numeric_with_unit (birlik, rel xatolik, doimiylar); tahrirlangan tasdiqlangan savol qayta tekshiruvga tushadi; muomaladan chiqarish, nusxa, o'chirish |
| `tests/Feature/Exam/TopicTreeTest.php` (6) | daraxt va savollar soni; kod avtomatik yaratilishi; nomini o'zgartirish (kod o'zgarmaydi); bo'limni faolsizlantirish ichidagilarni ham o'chiradi; ko'chirishda `level` lar yangilanadi, sikl va boshqa fanga ko'chirish rad etiladi; drag-sort; ishlatilayotgan mavzu faolsizlantiriladi, ishlatilmaydigani o'chiriladi |
| `tests/Feature/Exam/QuestionImportTest.php` (7) | CSV: `;` ajratgich, nom yoki kod bo'yicha mavzu, bankdagi va fayl ichidagi dublikat (normalizatsiya: bo'shliq, harf, nuqta), xato qatorlar; tasdiqsiz javob qabul qilinmaydi, tasdiq bilan belgilanadi, lekin savol baribir `in_review`; token bir martalik; olib tashlangan va oraliqda dublikatga aylangan qatorlar o'tkazib yuboriladi; JSON (ichma-ich variantlar va `answer`); Excel shablon to'liq aylanadi; CSV va JSON shablonlar xatosiz o'qiladi; yaroqsiz fayllar rad etiladi |
| `tests/Feature/Exam/QuestionBankAuthorizationTest.php` (4) | bosqich 3 dagi 28 route'ning hammasida student va guardian 403 oladi, mehmon login sahifasiga yo'naltiriladi, teacher, admin va super_admin sahifalarni ochadi, badge o'quvchiga yuborilmaydi |
| `resources/js/Components/Exam/Bank/__tests__/bank.test.js` (5, vitest) | klaviatura xaritasi (matn maydonlarida va fokusdagi tugmada ishlamaydi), guard'ning client nusxasi, birlik presetlari, mavzular ro'yxati |

Natija:
- `php artisan test --filter="ReviewQueueTest|QuestionBankTest|TopicTreeTest|QuestionImportTest|QuestionBankAuthorizationTest"`: **39 passed**;
- `npx vitest run resources/js/Components/Exam/Bank`: **5 passed**;
- to'liq `php artisan test`: **633 passed, 3 failed**. Yiqilganlarning birortasi bu bosqichga tegishli emas:
  - `TeacherPagesSmokeTest` — Bosqich 5 ning `{blueprint}` va `{session}` route'lari uchun fixture yo'q;
  - `UzbekValidationMessagesTest` — Bosqich 4 dagi `StartQuestionDraftRunRequest` da maydon nomlari o'zbekcha emas;
  - `VideoTranscodingServiceTest` — beqaror, qayta ishga tushirilganda o'tdi.
- Vue fayllari `@vue/compiler-sfc` bilan tekshirildi (parse + compileScript + compileTemplate).

### Ma'lum cheklovlar / qolgan ishlar
- **Admin uchun layout:** admin bu sahifalarni `TeacherLayout` ichida ko'radi. Admin menyusiga havola qo'shilmagan.
- **Rasm biriktirish yo'q:** savol va variantlarga rasm (medialibrary) hali qo'shilmagan. `question_bank_options.image` ustuni bor, lekin UI yo'q.
- **Formula klaviaturasi:** ustoz panelida faqat mavjud MarkdownEditor tugmalari bor (formula, alohida qatordagi formula); √, π, Ω kabi belgilar paneli hali yo'q.
- **`true_false` va `expression` turlari** bank formasida yo'q (guard `expression` ni baribir tasdiqlatmaydi).
- **Import preview keshda turadi** (`CACHE_STORE`, 120 daqiqa). Kesh tozalansa, faylni qayta yuklash kerak.
- **Dublikat tekshiruvi aniq moslik bo'yicha:** normallashtirilgan shartning hash'i solishtiriladi; o'xshash, lekin boshqacha yozilgan shartlarni topmaydi.
- **Filtrdagi qidiruv `LIKE`** — bank o'n minglab savolga yetsa, FULLTEXT indeks kerak bo'ladi.

### Savollar (agar bo'lsa)
1. **Tasdiqlangan savolni tahrirlash:** hozir shart, javob, mavzu yoki qiyinlik o'zgarsa, savol qayta tekshiruvga tushadi. «Saqlash va tasdiqlash» bosilsa, darhol qayta tasdiqlanadi. Imtihonda ishlatilgan savol uchun tahrir o'rniga «nusxa + muomaladan chiqarish» ni majburiy qilish kerakmi?
2. **Fayldagi javoblar:** ustoz preview'da tasdiqlasa ham, savollar `in_review` da qoladi va har birini navbatda tasdiqlash kerak. Ishonchli manbalar uchun «import bilan birga tasdiqlash» kerakmi?
3. **Haftalik reja:** dushanba 00:00 dan (Asia/Tashkent) hisoblanadi; demo savollar va `reviewed_by` bo'sh bo'lganlar kirmaydi. To'g'rimi?

### Keyingi bosqichga tayyormi: **HA**
Bank, navbat va import ishlaydi. Imtihon dvigateli (Bosqich 5) faqat `examEligible()` savollarni oladi, AI qoralamalar (Bosqich 4) `in_review` holatida shu navbatga tushadi.

---

## Bosqich 7 — Hisobotlar, reyting, natija kartasi, tavsiya

Doira: prompt'ning 1-bo'limi (o'quvchi yo'li, 2, 6, 7-qadamlar), 9-bo'lim (Bosqich 7) va 11-bo'lim (bolalar ma'lumotlari, rozilik). Bosqich 5 yozadigan jadvallar (`exam_attempts`, `exam_student_topic_mastery`, `exam_student_weekly_results`) bu yerda faqat o'qiladi.

### Nima qilindi (fayllar ro'yxati bilan)

**Anketa (onboarding), `student.exam.onboarding`**
- Kim to'ldiradi (ota-ona / o'quvchi), sinf (9 / 10 / 11 / bitirgan), fan (matematika / fizika / ikkalasi), maqsad (OTM grant / Milliy sertifikat / SAT), maktab (erkin matn), ota-ona telefoni, ota-ona roziligi, reytingda ism ko'rinishiga rozilik. Mobil uchun katta tugmali qisqa forma; saqlangach `student.exam.index` ga qaytadi.
- Rozilik vaqti saqlanadi: `parent_consent_at`, `parent_consent_source` (`web_parent` / `web_student`). Forma qayta saqlansa, birinchi rozilik vaqti o'zgarmaydi. Reytingdagi ism roziligi: `public_name_consent_at` (o'chirilsa NULL).
- `StudentExamProfile::isComplete()` (fanlar va ota-ona roziligi bor) va `exam.onboarded` middleware: profili to'liq bo'lmagan o'quvchini anketaga yuboradi. Bosqich 5 shu middleware'ni imtihon route'lariga qo'yishi mumkin.
- Fayllar: `app/Http/Controllers/Student/Exam/OnboardingController.php`, `app/Http/Middleware/EnsureExamOnboarded.php`, `resources/js/Pages/Student/Exam/Onboarding.vue`, `resources/js/Components/Exam/People/ChoiceGroup.vue`.

**Ota-ona (guardian) paneli, `guardian.dashboard`**
- Ota-ona akkaunti: `/guardian/register` (rol `guardian`, o'quvchi ro'yxati bilan bir xil qoidalar). Ro'yxatdan o'tish sahifasida «Ota-ona sifatida» havolasi bor.
- Farzandni bog'lash: ota-ona farzandining telefon raqamini kiritadi va rozilik beradi. Natijada `guardian_student` qatori «kutilmoqda» holatida yaratiladi (`consent_at` bor, `verified_at` NULL).
- **SMS yo'q**, shuning uchun bog'lanishni **ustoz tasdiqlaydi**: «Ota-ona so'rovlari» sahifasi (`teacher.exam.guardian-links.index`). Sahifa ota-onaning telefoni o'quvchi anketada yozgan raqamga mos kelishini ko'rsatadi. Rad etish sababi saqlanadi (`approved_by`, `rejected_at`, `reject_reason`).
- Ota-ona faqat **tasdiqlangan o'z farzandlarini** ko'radi: oxirgi natija, o'sish grafigi, mavzular xaritasi, 3 ta tavsiya, farzand sahifasi, yakshanba hisobotlari. Begona yoki tasdiqlanmagan farzand va hisobot so'ralsa — 404.
- Rollar ajratilgan: ota-ona `student` / `teacher` / `admin` hududlariga kira olmaydi (403), boshqa rollar esa ota-ona paneliga kira olmaydi. `/` va login ota-onani `/guardian/dashboard` ga yuboradi.
- Fayllar:
  - `routes/guardian.php`;
  - `app/Http/Controllers/Guardian/{Dashboard,Link,Report,Register}Controller.php`;
  - `app/Http/Controllers/Teacher/Exam/GuardianLinkController.php`;
  - `app/Services/Exam/GuardianLinkService.php`;
  - `resources/js/Layouts/GuardianLayout.vue`;
  - `resources/js/Pages/Guardian/{Dashboard,Child,Reports,Report,Register}.vue`;
  - `resources/js/Pages/Teacher/Exam/GuardianLinks.vue`.

**«O'sishim» sahifasi, `student.exam.progress`**
- Haftalar bo'yicha o'sish grafigi: `chart.js` + `vue-chartjs` (allaqachon `package.json` da bor). Har fan alohida chiziq, tungi rejimga moslashadi, ekran o'quvchisi uchun yashirin jadval ham bor.
- Fanlar bo'yicha mavzular xaritasi: bo'lim → mavzular. Bo'lim balli saqlanmaydi, u bo'lim daraxtidagi mastery qatorlarining `answered_count` bo'yicha vaznli o'rtachasi sifatida hisoblanadi (02_DATA_MODEL §6).
- Eng zaif 5 ta mavzu va keyingi hafta uchun 3 ta tavsiya mavzu. Tanlash qoidasi: avval eng past mastery'li mavzular. Ma'lumot yetmasa, ro'yxat o'quvchi fani va sinfiga mos, hali o'lchanmagan mavzular bilan to'ldiriladi.
- Natija kartasini oldindan ko'rish va yuklab olish, shuningdek taklif havolasi (nusxa olish tugmasi bilan).
- Fayllar: `app/Services/Exam/ReportBuilder.php`, `ProgressController.php`, `Pages/Student/Exam/Progress.vue`, `Components/Exam/People/{GrowthChart,TopicMap,Recommendations}.vue`, `mastery.js`.

**Reytinglar, `student.exam.leaderboard`**
- Fan va hafta bo'yicha uch xil reyting:
  - umumiy top (foiz bo'yicha; teng natijalar bir xil o'rin oladi: 1, 2, 2, 4);
  - «eng ko'p o'sganlar» (faqat `delta_prev > 0`);
  - maktablar (o'rtacha foiz). Maktab reytingga kamida `config('exam.leaderboard.school_min_participants')` ta (standart 5) ishtirokchi bilan kiradi. Maktab `school_id` bo'lsa shu bo'yicha, bo'lmasa normallashtirilgan nomi bo'yicha guruhlanadi.
- **Ism faqat rozilik bilan** ko'rsatiladi: «Ali V.». Rozilik bo'lmasa — o'quvchining taxallusi yoki user id'dan hosil qilingan barqaror taxallus («Zukko Burgut 42»): u har hafta bir xil, lekin bolani oshkor qilmaydi.
- O'quvchi o'z o'rnini har uch reytingda ko'radi. Brauzerga user id yuborilmaydi: o'quvchining o'z qatori ochiq bo'lmagan `uid` bilan belgilanadi.
- Kesh: haftaning butun reytingi `exam.leaderboard.cache_minutes` (10 daqiqa) ga saqlanadi. Imtihon baholanganda (`ExamAttemptGraded`) yoki ism roziligi, taxallus yoki maktab o'zgarganda kesh versiyasi oshiriladi.
- Fayllar: `app/Services/Exam/{LeaderboardService,LeaderboardNames}.php`, `LeaderboardController.php`, `Pages/Student/Exam/Leaderboard.vue`.

**Natija kartasi (1080×1920 PNG), `student.exam.card`**
- GD + FreeType bilan chiziladi. Loyihada Intervention, Imagick yoki Browsershot yo'q; shrift DejaVu (dompdf bilan keladi, o'zbek lotin harflari bor, `config/exam.php` da almashtirsa bo'ladi).
- Kartada: hafta, fan, ball va foiz, «+delta%» (faqat o'sish bo'lsa), o'rin, eng ko'p o'sgan mavzu va taklif havolasining QR kodi. QR `simple-qrcode` ning BaconQrCode enkoderi bilan chiziladi: PNG backend Imagick talab qiladi, u o'rnatilmagan.
- **O'quvchi ismi kartada yo'q.**
- «Eng ko'p o'sgan mavzu» shu haftaning va oldingi haftalik urinishning `exam_answers` ballari ulushini mavzu bo'yicha solishtirib aniqlanadi.
- Kartani olish: `?result=<id>`, `?attempt=<attempt id>` (Bosqich 5 natija sahifasi uchun), `?download=1`. Faqat o'z natijasi; aks holda 404.
- Fayllar: `app/Services/Exam/ResultCardRenderer.php`, `CardController.php`.

**Tavsiya (referral)**
- Har foydalanuvchiga bitta kod (`ReferralCode`). Havola `/r/{code}` (`referral.landing`): kod sessiya va cookie'ga (30 kun) yoziladi, foydalanuvchi ro'yxatdan o'tish sahifasiga yuboriladi. `?ref=` parametri ham ishlaydi. Telegram `/start ref_<code>` ni Bosqich 6 `ReferralService::recordJoin()` orqali ulaydi.
- Holatlar faqat oldinga siljiydi:
  - `joined` — ro'yxatdan o'tganda;
  - `placement_done` — qabul testi `ExamAttemptGraded` bilan baholanganda (qayta baholash hisobga olinmaydi);
  - `subscribed` — `Subscription` modeli `active` holatda saqlanganda. Bu Payme/Click fulfilment'i va admin tomonidan yaratilgan obunalarni ham qamraydi; tranzaksiya tugagandan keyin ishlaydi va to'lov oqimiga xato qaytarmaydi.
- Har o'tishda `App\Events\Exam\ReferralStatusChanged` chiqadi.
- **Mukofot:** mavjud `CoinRewardService::awardReferralCoins` qayta ishlatildi (100 coin, taklif qilgan o'quvchiga, bir marta, `reward_granted_at` atomar belgilanadi). Asos: coin — platforma ichidagi bonus qiymat, pul emas, prompt aynan shuni so'raydi. Mukofot bosqichi `exam.referral.reward_stage` (standart `placement_done`: taklif qilingan odam haqiqatan bepul testni topshirgan bo'lishi kerak). Ota-ona taklif qilsa coin berilmaydi (coin o'quvchi profilida saqlanadi).
- Coin tarixida taklif qilingan odamning faqat ismi yoziladi (familiya yozilmaydi).
- Fayllar: `app/Services/Exam/ReferralService.php`, `app/Http/Controllers/Exam/ReferralLandingController.php`, `app/Listeners/Exam/AdvanceReferralOn{Placement,Subscription}.php`, `RegisterController` (1 qator).

**Yakshanba hisoboti**
- `SendWeeklyGuardianReportsJob` + `php artisan exam:weekly-reports {--week=}` buyrug'i. `routes/console.php` da har yakshanba 09:00 (Asia/Tashkent) ga rejalashtirilgan, `withoutOverlapping`.
- Hisobot faqat shu shartlar bajarilganda yuboriladi: bog'lanish tasdiqlangan, rad etilmagan, rozilik bor, `receives_reports = true` va ota-ona bloklanmagan.
- Hisobotda: har fan bo'yicha ball / maksimum / foiz / delta / o'rin, bo'limlar xaritasi va keyingi hafta uchun 3 ta mavzu. Imtihon topshirilmagan hafta «topshirilmadi» deb ko'rsatiladi.
- Hisobot `exam_guardian_reports` jadvaliga saqlanadi. `(guardian_student_id, week_start)` unique, shuning uchun qayta ishga tushirish takror yubormaydi.
- Ilova ichida xabar (`AppDatabaseChannel`, turi `exam_weekly_report`, hisobot sahifasiga havola) yaratiladi va `App\Events\Exam\WeeklyReportReady($link, $report)` chiqadi. Telegram'ga Bosqich 6 yuboradi.
- Fayllar: `app/Jobs/Exam/SendWeeklyGuardianReportsJob.php`, `app/Console/Commands/Exam/SendWeeklyReportsCommand.php`, `app/Notifications/Exam/WeeklyGuardianReportNotification.php`, `app/Models/Exam/GuardianReport.php`, `app/Events/Exam/{WeeklyReportReady,ReferralStatusChanged}.php`.

**Navigatsiya**
- O'quvchi menyusida «Shanba imtihoni» ostida «O'sishim» va «Imtihon reytingi». «Reyting» nomi o'rniga shu nom tanlandi, chunki «Musobaqalar» bo'limida umumiy «Reyting» allaqachon bor.
- Ustoz menyusida «Ota-ona so'rovlari».
- Ota-ona uchun alohida `GuardianLayout`: «Farzandlarim», «Hisobotlar».

**Demo akkaunt** (`Database\Seeders\Exam\DemoGuardianSeeder`, `TestUserSeeder` dan chaqiriladi, faqat local/testing, qayta ishga tushirish xavfsiz)
- Ota-ona: `guardian@test.uz` / `+998900000010` / parol `password123`.
- `student@test.uz` ga tasdiqlangan holda bog'langan. O'quvchining anketasi bo'sh bo'lsa, demo anketa ham yaratiladi.

### Migratsiyalar
| Fayl | Nima |
|---|---|
| `2026_10_23_300001_add_onboarding_fields_to_exam_student_profiles_table` | `filled_by`, `parent_phone` (indeks), `parent_consent_at`, `parent_consent_source`, `public_name_consent_at`, `onboarded_at` |
| `2026_10_23_300002_add_approval_fields_to_guardian_student_table` | `approved_by` (FK users, null on delete), `rejected_at`, `reject_reason`, indeks `(verified_at, rejected_at)` |
| `2026_10_23_300003_create_exam_guardian_reports_table` | yakshanba hisobotlari: `guardian_student_id`, `guardian_id`, `student_id`, `week_start`, `payload` JSON, `sent_at`, `read_at`; unique `(guardian_student_id, week_start)` |

Uchala migratsiya faqat qo'shuvchi. `edulife_shanba` (MySQL) bazasida `--path` bilan ishga tushirildi; boshqa agentlarning kutilayotgan migratsiyalariga tegilmadi. `exam:weekly-reports` va reyting so'rovlari MySQL'da ham tekshirildi.

### Testlar: nima tekshirildi, natija
`php artisan test tests/Feature/Exam/Stage7` — **45 ta test, 342 ta tekshiruv — hammasi o'tadi**.

| Fayl | Nima tekshiradi |
|---|---|
| `ExamOnboardingTest` | rozilik majburiy (profil yaratilmaydi); telefon formati; saqlash (fanlar, sinf / bitirgan, maqsad, maktab, telefon normallashtiriladi, rozilik vaqti va manbasi); ism roziligi va uni qaytarib olish; qayta saqlashda birinchi rozilik vaqti saqlanadi; `exam.onboarded` middleware; boshqa rollar kira olmaydi |
| `GuardianAccessTest` | ota-ona student / teacher / admin hududiga kira olmaydi va aksincha; `/` va login yo'naltirishi; dashboard'da faqat o'z tasdiqlangan farzandlari; kutilayotgan, rad etilgan va begona farzand → 404; begona hisobot → 404, o'qilganda `read_at` belgilanadi; bog'lash so'rovi (rozilik, mavjud telefon, normallashtirish); ustoz tasdiqlaydi yoki rad etadi, ota-ona o'zi tasdiqlay olmaydi; ota-ona ro'yxatdan o'tishi |
| `ExamLeaderboardTest` | ism faqat rozilik bilan, aks holda barqaror taxallus (haqiqiy ism chiqmaydi); teng natijalar uchun o'rinlar; o'sganlar ro'yxatida faqat musbat delta; o'quvchining o'z o'rni; maktab chegarasi (2 ta ishtirokchili maktab chiqmaydi); kesh va uni tozalash; rozilik o'zgarganda kesh yangilanadi; sahifada boshqa o'quvchilarning user id'si yo'q; natijasiz holat |
| `WeeklyGuardianReportTest` | hisobot mazmuni (ball, delta, o'rin, bo'limlar xaritasi, 3 ta tavsiya eng zaifdan boshlab), ilova ichidagi xabar va `WeeklyReportReady`; o'lchangan mavzular kam bo'lsa tavsiyalar yangi mavzular bilan to'ldiriladi; faqat tasdiqlangan, rozilik berilgan va obuna bo'lgan bog'lanishlar; qayta ishga tushirish takror yubormaydi; topshirilmagan hafta; buyruq va jadval (`0 9 * * 0`, Asia/Tashkent) |
| `ReferralFunnelTest` | `/r/{code}` → ro'yxatdan o'tish → `joined` va hodisa; `?ref=` ham ishlaydi; noma'lum, o'z-o'zini, takroriy va nofaol kod rad etiladi; haftalik urinish holatni o'zgartirmaydi; qabul testi → `placement_done` va coin bir marta; obuna → `subscribed`; holat orqaga qaytmaydi; qayta baholash hisobga olinmaydi; ota-ona taklif qilsa coin yo'q; bekor qilingan obuna hisobga olinmaydi |
| `ResultCardAndProgressTest` | karta PNG, 1080×1920, `attachment`; kartada ism yo'q, delta faqat o'sishda; natija yo'q yoki begona bo'lsa 404; attempt bo'yicha karta; eng ko'p o'sgan mavzu; «O'sishim» sahifasi propslari va yangi o'quvchi uchun bo'sh holat |
| `DemoGuardianSeederTest` | demo ota-ona `student@test.uz` ga bog'langan, qayta seed qilish takror yaratmaydi |

Vue fayllar `@vue/compiler-sfc` bilan tekshirildi (parse, compileScript, compileTemplate): 14 ta yangi va 3 ta tahrirlangan fayl.

To'liq to'plam (`php artisan test`): **642 ta o'tadi, 1 ta yiqiladi** — `TeacherPagesSmokeTest`: Bosqich 5 ning yangi `teacher.exam.blueprints.edit` va `teacher.exam.sessions.*` route'lari uchun smoke testda fixture yo'q. Bosqich 7 ga aloqasi yo'q, Bosqich 5 da tuzatiladi. Bosqich 7 ning route'lari (student, guardian, teacher) smoke testlardan o'tadi.

### Ma'lum cheklovlar / qolgan ishlar
1. **Telefon tasdig'i yo'q.** Ota-ona va farzand o'rtasidagi bog'lanishni ustoz qo'lda tasdiqlaydi. SMS provayder ulanganda `GuardianLinkService::request()` ga kod yuborish qo'shiladi; ustoz tasdig'i ham qolishi mumkin.
2. **Ota-ona Telegram orqali ro'yxatdan o'tsa** (Bosqich 6), bog'lanish ham shu navbatga tushishi kerak, ya'ni `GuardianLinkService::request()` dan foydalanish lozim.
3. **Natija kartasini Bosqich 5 natija sahifasiga ulash kerak:** `route('student.exam.card', { attempt, download: 1 })`. Hozircha karta «O'sishim» sahifasida.
4. **Mavzu tarixi saqlanmaydi.** «Eng ko'p o'sgan mavzu» ikki haftalik urinishning javoblaridan hisoblanadi. Agar o'tgan haftada shu mavzu bo'yicha savol bo'lmagan bo'lsa, u chiqmaydi.
5. **Maktablar** erkin matn bo'yicha guruhlanadi (bo'shliq va katta-kichik harf normallashtiriladi). «14-maktab» va «14 maktab» hozircha ikki xil maktab hisoblanadi.
6. **Kesh versiyasi kalit sifatida saqlanadi.** Eski kalitlar TTL (10 daqiqa) tugaganda o'zi o'chadi.

### Savollar (agar bo'lsa)
1. **Mukofot:** coin (100) qabul testidan keyin beriladi. «Bonus tahlil» kabi boshqa qiymat kerakmi yoki mukofot obunadan keyin berilsinmi (`EXAM_REFERRAL_REWARD_STAGE=subscribed`)?
2. **Maktab chegarasi:** 5 ta ishtirokchi to'g'rimi?
3. **Rozilik:** o'quvchi o'zi to'ldirganda «ota-onam rozi» belgisi yetarlimi yoki ota-ona akkaunti tasdiqlamaguncha imtihon yopiq turishi kerakmi?
4. **Nav nomi:** «Imtihon reytingi» qoldiriladimi yoki umumiy «Reyting» bilan birlashtiriladimi?

### Keyingi bosqichga tayyormi: **HA**
Bosqich 7 ning barcha bandlari testlar bilan ishlaydi. Bosqich 8 (to'lov) mavjud `subscriptions` oqimi orqali tavsiya voronkasiga allaqachon ulangan.

---

## Bosqich 4 — AI qoralama generatori

### Nima qilindi (fayllar ro'yxati bilan)
- **Servis:** `app/Services/Exam/QuestionDraftService.php` — Anthropic Messages API (`POST /v1/messages`) Laravel `Http` klienti orqali (SDK qo'shilmadi: bitta so'rov yetarli, `Http::fake()` bilan to'liq testlanadi). Model `config/exam.php` → `exam.ai.model` (standart `claude-opus-5-5`, `EXAM_AI_MODEL`), kalit `ANTHROPIC_API_KEY`; `output_config.format` — qat'iy JSON sxema, `effort` sozlanadi, refusal uchun server tomonidagi fallback (`fallbacks: "default"`).
- **Sxema va validatsiya:** `QuestionDraftSchema.php` — `stem` (LaTeX-markdown), `options[]` 4–5 ta (A…E tartibda, takrorsiz), `topic_code` (faqat ruxsat etilgan kodlar), `difficulty_level` 1–5, `source_ref`, `units_note` (fizikada majburiy), `suggested_correct_label`; ortiqcha maydon rad etiladi. Har savol serverda alohida tekshiriladi, dublikatlar (`normalized_hash`) o'tkazib yuboriladi.
- **AI to'g'ri javobni belgilamaydi:** savollar `origin = ai_draft`, `QuestionReviewService::submitForReview()` orqali `in_review`, variantlar `is_correct = NULL`. `EXAM_AI_STORE_HINT=true` (standart o'chiq) bo'lsa AI taxmini faqat `ai_suggested_correct` ga yoziladi.
- **Promptlar:** `resources/prompts/exam/question_draft.md` (front matter'da `version`), `QuestionDraftPrompt.php` — yuklash, versiya, bir o'tishli to'ldirish (manbadagi LaTeX `{{…}}` buzilmaydi).
- **Manbalar:** `exam_source_documents` + `storage/app/exam-sources/`; model `app/Models/Exam/ExamSourceDocument.php`. Uzun hujjatdan har so'rovda boshqa bo'lak olinadi.
- **Navbat, reja, log:** `app/Jobs/Exam/GenerateQuestionDraftsJob.php` (qayta urinishsiz — ikki marta to'lamaslik uchun), `QuestionDraftWeeklyPlanner.php` + `php artisan exam:ai-drafts-weekly` (dushanba 03:00, `routes/console.php`; haftalik maqsad, byudjet limiti, takror ishga tushirishda ikkilanmaydi), `app/Models/Exam/QuestionDraftRun.php` (`exam_ai_draft_runs`: prompt versiyasi, model, tokenlar, taxminiy xarajat, natija soni, xato).
- **UI:** admin «AI qoralamalar» (`Admin\Exam\AiUsageController`, `Pages/Admin/Exam/AiDrafts/Index.vue`: jurnal, hafta/oy xarajati va tokenlar, limit, ishga tushirish formasi); ustoz «AI qoralamalar» (`Teacher\Exam\AiSourceController`, `Pages/Teacher/Exam/AiDrafts/Index.vue`: manba yuklash/joylash, yoqish/o'chirish, so'rov formasi, oxirgi so'rovlar); umumiy komponentlar `Components/Exam/AiDrafts/*`; `StartQuestionDraftRunRequest`; ikkala panel menyusida «AI qoralamalar».
- **Sozlamalar va hujjat:** `config/exam.php` → `ai` bloki, `.env.example`, `lang/uz/validation.php` (maydon nomlari), `docs/exam/INTEGRATIONS.md` (1-bo'lim).

### Migratsiyalar
- `2026_10_23_400001_create_exam_source_documents_table`
- `2026_10_23_400002_create_exam_ai_draft_runs_table`

`edulife_shanba` da `php artisan migrate` bajarildi.

### Testlar: nima tekshirildi, natija (php artisan test chiqishi)
`tests/Feature/Exam/QuestionDraftServiceTest.php` (15), `AiDraftPagesTest.php` (7):
- yaroqli javob → `in_review` qoralamalar, barcha variantlarda `is_correct = NULL`, so'rov sarlavhalari/modeli/sxemasi (`Http::assertSent`);
- model config'dan, prompt versiyasi saqlanadi; maslahat flag'i faqat `ai_suggested_correct` ga yozadi;
- xarajat jurnali: tokenlar (kesh bilan) va taxminiy narx;
- rad etish: 3 variant, begona mavzu, takror variantlar, qiyinlik 9, harflar tartibi, ortiqcha maydon, obyekt emas; fizikada `units_note` majburiy;
- JSON emas / refusal / `max_tokens` / API xatosi → `failed`, savol yozilmaydi; dublikat o'tkazib yuboriladi;
- kalit yoki manba yo'q → `skipped`, `Http::assertNothingSent()`; haftalik reja maqsadni bo'ladi, takror chaqiruv yangi so'rov ochmaydi, byudjet to'lsa to'xtaydi;
- sahifalar: admin jurnali, forma validatsiyasi, rollar, ustoz fayl yuklash (BOM, `exam-sources/`), matn joylash, yaroqsiz fayl, o'chirish.

Natija: `55 passed (275 assertions)` (Bosqich 4 + 6 testlari birga). To'liq to'plam — Bosqich 6 hisobotida.

### Ma'lum cheklovlar / qolgan ishlar
- Manba faqat matn (`.txt/.md/.tex` yoki joylash). PDF/skan qo'llanmalar uchun matnga aylantirish (yoki Claude'ning PDF `document` bloki) keyingi qadam.
- Faqat `single_choice` qoralamalar (spec: 4–5 variant). `numeric` / `numeric_with_unit` qoralamalari yo'q.
- Xarajat — `usage` dan taxminiy hisob (`exam.ai.pricing`); haqiqiy hisob Anthropic Console'da.
- So'rov navbatda bajariladi: natija queue worker ishlaganda paydo bo'ladi (dev'da `QUEUE_CONNECTION=database` — `php artisan queue:work` kerak).

### Savollar (agar bo'lsa)
1. Haftalik maqsad (`EXAM_AI_WEEKLY_TARGET=100`) — ikkala fan uchun **jami** deb oldim (har fanga 50). Har fanga 100 kerakmi?
2. Haftalik byudjet standarti $15 — mosmi?
3. AI taxminini yashirin maslahat sifatida ko'rsatish (`EXAM_AI_STORE_HINT`) — o'chiq qoldirildi; tekshiruv navbati (Bosqich 3) uni ko'rsatishi kerakmi?

### Keyingi bosqichga tayyormi: **HA**
Kalit kiritilishi bilan ishlaydi; kalitsiz hech narsa yuborilmaydi. Qoralamalar ustozning tekshiruv navbatiga tushadi.

---

## Bosqich 6 — Telegram bot + Mini App

### Nima qilindi (fayllar ro'yxati bilan)
- **Kutubxona tanlovi:** o'zimizning yupqa klient `app/Services/Telegram/TelegramClient.php` (Laravel `Http`). nutgram / telegram-bot-sdk olinmadi: 5 ta metod kerak (`sendMessage`, `answerCallbackQuery`, `setWebhook`, `deleteWebhook`, `setChatMenuButton`), suhbat holati o'z jadvalimizda, `Http::fake()` bilan to'liq testlanadi — yangi bog'liqlik va ikkinchi routing/kesh mexanizmi kerak emas. Token bo'sh bo'lsa hech narsa yuborilmaydi (log, shaxsiy ma'lumotsiz).
- **Webhook:** `POST /telegram/webhook` (`Telegram\WebhookController`): `web` middleware'siz, `X-Telegram-Bot-Api-Secret-Token` `hash_equals` bilan, IP limit 600/daq, foydalanuvchi limiti 30/daq; sozlanmagan bo'lsa 404. Xato loglarida faqat sinf nomi va qator (QueryException ichidagi telefonlar loglanmaydi).
- **Bot:** `BotUpdateHandler.php` — `/start [ref_<kod> | link_<token>]`, `/natijalar`, `/taklif`, `/yordam`, `/bekor`, faqat shaxsiy chat. `BotRegistration.php` — 5 savol (kim, sinf, fan, maqsad, telefon kontakti + o'quvchi uchun ota-ona telefoni) va rozilik → o'quvchi akkaunti (yoki shu telefondagi mavjud o'quvchi) + `StudentExamProfile` (`filled_by`, `parent_phone`, `parent_consent_at`, `parent_consent_source`, `onboarded_at` — Bosqich 7 ustunlari), keyin «Qabul testini boshlash» (Mini App). O'qituvchi/admin/ota-ona telefoni bot orqali olinmaydi. Taklif kodi `start_payload` da saqlanadi va ro'yxat oxirida `ReferralService::recordJoin($user, $code, 'telegram')` ga uzatiladi.
- **Saytdagi akkauntni ulash:** `GET /telegram/link` (auth) → `t.me/<bot>?start=link_<token>` (15 daqiqa, bir martalik; `TelegramLinkService.php`) — ota-onalar va saytda ro'yxatdan o'tganlar uchun.
- **Mini App:** `GET /telegram/app` (Blade, `telegram-web-app.js`), `POST /telegram/app/auth` — `TelegramInitData.php` initData HMAC'ini bot tokeni bilan tekshiradi (`WebAppData` kaliti), `auth_date` TTL (`TELEGRAM_INIT_DATA_TTL`), keyin bog'langan foydalanuvchini `Auth::login` qiladi va `student.exam.index` ga (bo'lmasa `student.dashboard`), ota-onani `guardian.dashboard` ga yo'naltiradi (`Route::has` bilan). Ro'yxatdan o'tmagan — 409, noto'g'ri/eskirgan — 401.
- **Eslatmalar:** `TelegramReminderService.php` + `php artisan exam:telegram-reminders` (har 5 daqiqa): oldingi kun 19:00 dan, 1 soat oldin, boshlanishda; faqat haftalik `scheduled/open` sessiyalar, shu fanni tanlagan, botni bloklamagan o'quvchilar. `telegram_notifications` (unique: akkaunt + tur + ref) — har xabar bir marta.
- **Hodisalar:** `app/Listeners/Telegram/*` — `ExamAttemptGraded` → o'quvchiga natija + tasdiqlangan ota-onaga; `ExamSessionOpened` → boshlanish eslatmasi; `WeeklyReportReady` → ota-onaga yakshanba hisoboti. `AppServiceProvider` dagi o'z blokida `class_exists` bilan ro'yxatdan o'tkaziladi; `shouldQueue()` token bo'sh bo'lsa listener'ni umuman ishga tushirmaydi. Telegram 403 → `is_blocked`.
- **Buyruqlar:** `telegram:set-webhook [--delete] [--url=]` (webhook + menyu tugmasi Mini App'ga).
- **Matnlar:** hammasi `lang/uz/exam_bot.php` da (`BotText` qiymatlarni HTML-escape qiladi).
- **Marshrutlar:** `routes/exam_integrations.php` (web.php dan bitta `require`), jadval `routes/console.php` dagi o'z blokida.
- **Hujjat:** `docs/exam/INTEGRATIONS.md` — BotFather, `.env`, webhook URL, Mini App URL, ngrok, oqimlar, xavfsizlik.

### Migratsiyalar
- `2026_10_23_400003_create_telegram_bot_states_table` — ro'yxat suhbati holati
- `2026_10_23_400004_create_telegram_notifications_table` — yuborilgan xabarlar (idempotentlik)

`edulife_shanba` da `php artisan migrate` bajarildi (shu vaqtda navbatda turgan Bosqich 5 migratsiyasi `2026_10_23_200001` ham qo'llandi).

### Testlar: nima tekshirildi, natija (php artisan test chiqishi)
`TelegramBotTest.php` (16), `TelegramMiniAppTest.php` (8), `TelegramNotificationsTest.php` (9):
- webhook: sirsiz / noto'g'ri sir → 403, sozlanmagan → 404 va hech narsa yuborilmaydi, throttle va `web` istisnosi;
- `/start ref_<kod>` → `start_payload`, birinchi savol tugmalari; guruh chati va botlar e'tiborsiz;
- to'liq ro'yxat (o'quvchi va ota-ona yo'li), begona kontakt va +998 bo'lmagan raqam rad, rozilik yo'q → akkaunt yo'q, eskirgan tugma, mavjud o'quvchini telefon orqali ulash, o'qituvchi telefoni olinmaydi, referral `joined`;
- `link_<token>` bir martalik, `/telegram/link` deep link, `/natijalar`, bloklangan bot → `is_blocked`;
- initData: to'g'ri → login va yo'naltirish, o'zgartirilgan / boshqa token → `bad_hash`, hash yo'q, eskirgan, ro'yxatdan o'tmagan (409), token bo'sh, ota-ona yo'naltirishi, sahifa;
- eslatma oynalari (juma 18:59 / 19:00 / shanba 08:59 / 09:00 / 10:00 / 10:15), sessiya tanlovi (bekor qilingan, boshqa tur, keyingi hafta yo'q), qabul qiluvchilar (fan, bloklangan, bog'lanmagan), ikki marta ishga tushirishda bitta xabar, token yo'q → hech narsa;
- listener'lar: natija o'quvchiga va faqat tasdiqlangan ota-onaga, takror hodisada qayta yuborilmaydi; token yo'q → `Http::assertNothingSent()`; hisobot matni, tugma, tasdiqlanmagan bog'lanishga yuborilmaydi.

Natija: Bosqich 4 + 6 — `55 passed (275 assertions)`. To'liq `php artisan test` (boshqa bosqichlar parallel ishlayotgan paytda): `635 passed, 2 failed` — `UzbekValidationMessagesTest` (yangi forma maydonlari nomi; tuzatildi) va `TeacherPagesSmokeTest` (Bosqich 5 marshrutlari `teacher.exam.blueprints.edit`, `teacher.exam.sessions.*` uchun smoke fixture yo'q — Bosqich 5 egasiga).

### Ma'lum cheklovlar / qolgan ishlar
- Telegram Web (brauzerdagi web.telegram.org) Mini App'ni iframe'da ochadi — `SameSite=Lax` session cookie u yerda saqlanmasligi mumkin; mobil va desktop ilovalar ishlaydi. Kerak bo'lsa `SESSION_SAME_SITE=none` + `SESSION_SECURE_COOKIE=true`.
- Ota-ona botda «Ota-onaman» deb ro'yxatdan o'tsa, yaratiladigan akkaunt — **o'quvchi akkaunti** (farzand imtihonni shu Telegram orqali topshiradi), telefoni ota-onaniki. Alohida ota-ona akkaunti saytda (Bosqich 7) ochiladi va `/telegram/link` bilan ulanadi.
- Ota-onaga bog'lanish so'rovi botdan yaratilmaydi (Bosqich 7 qoidasi: ustoz/admin tasdiqlaydi).
- Natija kartasi (PNG) va yechim videolari botga alohida yuborilmaydi — natija sahifasi tugmasi orqali.
- Webhook update'larni sinxron qayta ishlaydi (oddiy amallar); juda katta yuklamada navbatga o'tkazish mumkin.

### Savollar (agar bo'lsa)
1. **Q-5 (bitta telefon):** ota-ona botda farzandi uchun ro'yxatdan o'tsa, `users.phone` = ota-onaning raqami bo'ladi; keyin ota-ona saytda shu raqam bilan alohida akkaunt ocha olmaydi. Qoldiramizmi yoki ota-ona yo'lida o'quvchi uchun telefonsiz akkaunt kerakmi?
2. O'quvchi «ota-onam rozi» deb bosgan rozilik (`parent_consent_source = telegram_student`) yetarlimi yoki ota-ona bog'langanda qayta tasdiqlashi shartmi?
3. Eslatma vaqtlari: oldingi kun 19:00, 1 soat oldin, boshlanishda — mosmi?

### Keyingi bosqichga tayyormi: **HA**
Bot va Mini App token kiritilishi bilan ishlaydi (`php artisan telegram:set-webhook`); tokensiz barcha yo'llar jim. Bosqich 5/7 hodisalari ulangan.

---

## Bosqich 5 — Variant generatori va imtihon dvigateli

### Nima qilindi (fayllar ro'yxati bilan)
- **Variant generatori** — `app/Services/Exam/VariantGenerator.php`, `ExamAudience.php`:
  - har qoida (mavzu daraxti × qiyinlik × qism × soni) faqat `examEligible()` (tasdiqlangan) savollardan oladi; bitta savol ikki qoidaga tushmaydi, eng tor qoida birinchi tanlaydi;
  - o'quvchi oxirgi `avoid_repeat_weeks` haftada ko'rgan savollar imkon qadar berilmaydi (yetmasa takrorlanadi, `warnings: repeat`);
  - bank yetmasa ±1 qiyinlikdan to'ldiriladi (`used_fallback = true`, `warnings: adjacent`), baribir yetmasa `warnings: shortage`;
  - savollar tartibi (avval yopiq, keyin ochiq qism) va javob variantlari Mt19937 RNG bilan aralashtiriladi, `seed` saqlanadi: `plan(blueprint, seed, seen)` bir xil variantni qayta beradi (audit);
  - oldindan generatsiya: `App\Jobs\Exam\GenerateExamVariantsJob` (queue, unique), `php artisan exam:generate-variants {session} [--queue]`; `exam:tick` boshlanishdan `engine.pregenerate_hours` (24) soat oldin navbatga qo'yadi. Kech kelgan o'quvchiga variant boshlaganda yaratiladi.
- **Bank yetarliligi** — `ExamReadinessChecker.php`: har qoida uchun nomzodlar, qat'iy taqsimot, yetishmovchilik va qo'shni qiyinlik uni qoplay oladimi (`ok / fallback / short`). Ustoz uni sessiya sahifasida (rejalashtirishdan oldin) va reja muharririda jonli ko'radi.
- **Imtihon dvigateli** — `ExamAttemptService.php`, `ExamSessionLifecycle.php`, `ExamPlacementService.php`, `app/Exceptions/Exam/ExamUnavailable.php`:
  - sessiya holati jadval bo'yicha o'zgaradi: `draft → scheduled → open → closed → graded` (yoki `cancelled`); `exam:tick` har daqiqada ishlaydi (`routes/console.php`), sahifa ochilganda esa lazy `sync()`; `ExamSessionOpened` bir marta yuboriladi;
  - bir o'quvchi — bitta urinish (unique + davom ettirish); `expires_at = boshlanish + davomiylik`, sessiya tugashi bilan cheklanadi;
  - javoblar batch'da saqlanadi (`X-Exam-Token` sarlavhasi bilan), muddatdan keyin (15 s grace) qabul qilinmaydi;
  - muddati o'tgan urinishlar avtomatik topshiriladi (`AutoSubmitExpiredExamAttemptsJob`, `AutoSubmitExpiredTestJob` naqshi); sessiya yopilganda qolganlar topshiriladi, o'rinlar hisoblanadi, natija e'lon qilinadi;
  - anti-cheat yengil: tab almashish `violations_count` va `violation_log` ga yoziladi, bloklamaydi; IP va user agent saqlanadi.
- **Baholash** — `GradingService.php`, `Grading/AnswerChecker.php`, `Grading/NumberParser.php`, `Grading/UnitNormalizer.php`:
  - `single_choice`, `true_false`, `multiple_choice` (hammasi yoki hech narsa), `numeric` (abs/rel xatolik; `0,5`, `1/2`, `2,5·10^-3`, `3e8` yozuvlari), `numeric_with_unit` (birlik normalizatsiyasi `m/s^2 = m/s²`, `accepted_units` konversiyasi; son to'g'ri, birlik xato → `wrong_unit_ratio` ulushi);
  - `php artisan exam:regrade {question}` — savol tuzatilganda joriy kalit bilan, `retired` bo'lsa bekor qilib (0 dan 0) barcha urinishlarni qayta baholaydi, mastery va haftalik natijalarni (delta, o'rin) yangilaydi; `ExamAttemptGraded($attempt, regraded: true)` yuboriladi.
- **Qabul testi va mastery** — `ExamPlacementService.php`, `MasteryService.php`: har fanga bitta doimiy ochiq qabul sessiyasi, bir marta topshiriladi; qayta topshirish faqat ustoz ruxsati bilan (o'quvchiga xos yopiq sessiya). Natija `student_topic_mastery` ga yoziladi (alpha = 1), haftalik imtihon EMA bilan yangilaydi (alpha = 0,3). Formula — `02_DATA_MODEL.md` §6.
- **Haftalik natija** — `ExamResultService.php`: har haftalik sessiya uchun `exam_student_weekly_results` (ball, foiz, `delta_prev`, `rank`, `percentile`, `participants_count`).
- **Hodisalar:** `App\Events\Exam\ExamAttemptGraded`, `App\Events\Exam\ExamSessionOpened`.
- **Ustoz UI** (`routes/exam_engine.php`, `Teacher\Exam\BlueprintController`, `SessionController`):
  - `Pages/Teacher/Exam/Blueprints/{Index,Form}` — qoidalar muharriri va jonli bank tekshiruvi;
  - `Pages/Teacher/Exam/Sessions/{Index,Form,Show}` — shanba imtihonini rejalashtirish (fan, reja, sana, vaqt oynasi, davomiylik), bank yetarliligi, variantlar soni, jonli ishtirok (har 15 s), natijalar jadvali, CSV, qabul testini qayta topshirishga ruxsat;
  - menyu: «Imtihonlar».
- **O'quvchi UI** (`Student\Exam\ExamController`, `AttemptController`, `ExamAttemptPresenter`):
  - `Pages/Student/Exam/Index` — profil to'liq bo'lmasa `student.exam.onboarding` ga yo'naltiradi; fan bo'yicha qabul testi, keyingi shanba imtihonigacha qolgan vaqt, tarix;
  - `Take` — bitta savol yoki ro'yxat, KaTeX, server taymeri, avtosaqlash, birlik tanlash, topshirishni tasdiqlash, 375px;
  - `Result` — ball, o'tgan haftaga nisbatan delta, mavzular, xato savollarga to'g'ri javob, yechim matni va videosi (haftalik imtihonda faqat sessiya yopilgandan keyin), natija kartasi havolasi;
  - menyu: «Shanba imtihoni».
- **Seed:** `database/seeders/Exam/ExamDemoSessionSeeder.php` (`ExamSeeder` ichida): ikki fanning qabul sessiyasi, demo o'quvchi (`student@test.uz`) profili, kelgusi shanba 10:00–13:00 matematika imtihoni va uning varianti.
- **Sozlamalar:** `config/exam.php` → `engine` bloki.

### Migratsiyalar
- `2026_10_23_200001_add_engine_fields_to_exam_tables` — faqat qo'shuvchi:
  - `exam_variants.warnings` (JSON);
  - `exam_attempts.violation_log` (JSON);
  - `exam_sessions.restricted_to_user_id` (indeksli uuid, FK'siz — SQLite jadvalni qayta qurmasligi uchun).

### Testlar: nima tekshirildi, natija (php artisan test chiqishi)
| Fayl | Tekshiradi |
|---|---|
| `tests/Feature/Exam/VariantGeneratorTest.php` (5) | blueprint'ga moslik, faqat tasdiqlangan savollar, unikallik, yopiq → ochiq tartib, variantlar permutatsiyasi; seed bilan qayta tiklash; o'quvchilar orasida farq va oxirgi haftalarni takrorlamaslik; bank yetmasligi (qo'shni qiyinlik, shortage, ogohlantirish, fallback o'chirilganda); job, `exam:tick` va buyruq bilan oldindan generatsiya |
| `tests/Feature/Exam/ExamGradingTest.php` (5), `tests/Unit/Exam/GradingParserTest.php` (18) | har format: single, true_false, multiple (qisman = 0), numeric (yozuv shakllari, abs/rel, bir nechta javob), numeric_with_unit (normalizatsiya, konversiya, birlik xato → ulush); son va birlik parserlari |
| `tests/Feature/Exam/ExamEngineFlowTest.php` (6) | boshlash, batch avtosaqlash, topshirish va baholash, bitta urinish, sessiya oynasi va auditoriya, jadval bo'yicha ochish/yopish, avtomatik topshirish, kech javob rad etilishi, o'rinlar, tab almashish logi |
| `tests/Feature/Exam/ExamMasteryRegradeTest.php` (3) | qabul testi → mastery (alpha 1), bir marta, ustoz ruxsati bilan qayta; EMA 0,3 va haftalik delta; regrade (tuzatilgan va muomaladan chiqarilgan savol) |
| `tests/Feature/Exam/ExamPagesTest.php` (6) | o'quvchi sahifalari va oqimi, javob kaliti Take sahifasiga chiqmasligi, token, haftalik tahlil faqat yopilgandan keyin, natija kartasi havolasi, avtorizatsiya (begona urinish, rollar, onboarding), ustoz rejasi muharriri va jonli tekshiruv, sessiyani rejalashtirish → variantlar → CSV, qayta topshirishga ruxsat |
| `tests/Feature/Exam/ExamDemoSeedTest.php` (1) | seed qilingan demo bank bilan ikki fanning qabul testi, kelgusi shanba sessiyasi, idempotentlik |

Natija:
- bosqich testlari: **44 passed (477 assertions)**;
- to'liq `php artisan test`: **644 passed**;
- `TeacherPagesSmokeTest` ga `{blueprint}` va `{session}` fixture'lari qo'shildi;
- Vue fayllari `@vue/compiler-sfc` bilan tekshirildi.

### Ma'lum cheklovlar / qolgan ishlar
- Imtihonga kirish huquqi (obuna/to'lov) hali tekshirilmaydi — Bosqich 8.
- Auditoriya — profilida shu fan bor faol o'quvchilar. Alohida «ro'yxatdan o'tish» yo'q; `registration_closes_at` faqat ma'lumot uchun.
- Olimpiadadagi qurilma qulfi va heartbeat ishlatilmadi (u servis `OlympiadAttempt` ga bog'langan); faqat log yoziladi.
- Rejadan qoida o'chirilsa, undan oldin yaratilgan, lekin boshlanmagan variantdagi savol bali 1 deb olinadi. Qoidalar id bo'yicha yangilanadi, shuning uchun odatda ballar saqlanadi.
- Savol tuzatilganda regrade avtomatik ishlamaydi — `exam:regrade {question}` qo'lda ishga tushiriladi.
- `expression` turi baholanmaydi (generator uni tanlamaydi).
- Auditoriya 200 tagacha bo'lsa variantlar so'rov ichida yaratiladi, ko'proq bo'lsa queue'da — dev'da `php artisan queue:work` kerak.

### Savollar (agar bo'lsa)
1. `multiple_choice` hozir «hammasi yoki hech narsa». Qisman ball kerakmi?
2. `retired` savol hamma uchun bekor qilinadi (0 dan 0). Yoki hammaga to'liq ball berilsinmi?
3. Haftalik imtihonda ball darhol ko'rinadi, savollar tahlili esa sessiya yopilgandan keyin ochiladi. Shu tartib to'g'rimi?
4. Qabul testini qayta topshirishga hozir ustoz ruxsat beradi. Admin uchun ham alohida tugma kerakmi?

### Keyingi bosqichga tayyormi: **HA**
Generator, dvigatel, baholash, regrade, mastery va haftalik natijalar ishlaydi. Bosqich 6 va 7 `ExamAttemptGraded`, `ExamSessionOpened` hodisalarini va `exam_student_weekly_results` jadvalini ishlatadi.

---

## Qiyinlik darajasi — bankdan savol olishda va bankka kiritishda (to'lqin 2, agent Q)

Talab: «misollar bazasini olayotganda qiyinlik darajasi tanlanishi lozim». Dizayn — `03_RASM_QIYINLIK_TELEGRAM.md` §2.

### Inventarizatsiya (ish boshida qanday edi → endi)
**Savol bankka tushadigan joylar**

| Joy | Avval | Endi |
|---|---|---|
| Imtihon banki formasi (`Teacher/Exam/Questions/Create`, `Edit`) | ixtiyoriy, yangi formada 3 oldindan tanlangan | majburiy (qoralama ham), oldindan tanlanmagan; «Saqlash va yana qo'shish» faqat hozir tanlangan darajani keyingi formaga o'tkazadi |
| Tekshiruv navbati, inline tahrir | null qilib qo'yish mumkin edi | tanlash mumkin, o'chirib bo'lmaydi; tanlanmagan bo'lsa ogohlantirish |
| Import CSV / Excel / JSON | yo'q → ogohlantirish, noto'g'ri → xato (qator o'tkazilardi), eski `difficulty` ustuni jim `medium` | yo'q yoki noto'g'ri → qatorda daraja tanlagich, «Hammasiga qo'llash»; darajasiz qator bo'lsa tasdiqlab bo'lmaydi (server ham rad etadi) |
| AI qoralama | forma 3 ni oldindan tanlardi; har qoralamaga AI bergan daraja yoziladi | forma bo'sh boshlanadi (majburiy); har qoralamada `difficulty_level` borligi test bilan tekshirildi |
| Kurs testi savoli (`Teacher/QuestionController`, `QuestionFormFields`) | ixtiyoriy, jim `medium` | yaratishda majburiy, yangilashda tozalab bo'lmaydi; forma bo'sh boshlanadi |
| Olimpiada savoli | o'z muharriri yo'q — savollar bankdan olinadi | — |
| Seeder'lar | `ExamDemoQuestionSeeder` — har savolda 1–5 (verifier tekshiradi); kurs savollari seeder'da yo'q | o'zgarmadi |
| AI haftalik reja (`QuestionDraftWeeklyPlanner`) | sozlamadagi darajalar ro'yxati | o'zgarmadi (aniq sozlama, jim default emas) |

**Savol bankdan olinadigan joylar**

| Joy | Avval | Endi |
|---|---|---|
| Imtihon blueprint / variant generatori | qoida: mavzu × qiyinlik (null = istalgan) × soni; ±1 fallback | qoidada daraja aniq tanlanadi (1–5 yoki «Istalgan»), har daraja uchun bankdagi tasdiqlangan savollar soni ko'rinadi; generator o'zgarmadi |
| Kurs testi | bankdan olish yo'q edi | yangi «Bankdan qo'shish» |
| Battle | kurs test savollaridan, p̂ bo'yicha bitta daraja, har darajada savollar soni ko'rinardi | nomlar `Difficulty` dan; hisoblash o'zgarmadi |
| Olimpiada (`assignQuestions`) | tasodifiy N ta, qiyinliksiz | daraja tanlanadi (1–5, oson/o'rta/qiyin yoki «Istalgan»), har darajadagi bo'sh savollar soni ko'rinadi |

### Nima qilindi (fayllar ro'yxati bilan)
- **Bitta shkala** — `app/Support/Difficulty.php` va `resources/js/Utils/difficulty.js`: 1–5 nomlari («1 — juda oson» … «5 — juda qiyin»), Oson / O'rta / Qiyin, moslik 1–2 = oson, 3 = o'rta, 4–5 = qiyin (ikki tomonga), eski `question_bank.difficulty` qiymati, yechilish ulushidan taklif. Tarqoq nomlar (`bank.js`, `format.js`, battle `scoring.js`, `BattleQuestionSelector::LEVEL_LABELS`, AI forma, blueprint forma, `QuestionEditorService`, `QuestionDraftService`) shu yerdan oladi.
- **Kiritish** — `HandlesBankQuestions`, `Exam\QuestionController::create`, `StoreQuestionRequest`, `UpdateQuestionRequest`, `Teacher\QuestionController`, `QuestionFormFields.vue` (faqat qiyinlik maydoni), kurs testi `Create/Edit.vue`, `Review/Index.vue`, `AiDraftRunForm.vue`.
- **Import** — `QuestionImportService` (`needs_difficulty`, `rowsMissingDifficulty()`, `import(..., $difficulties)` darajasiz qatorda `DomainException`), `ImportController::confirm` (`difficulties[qator] = 1..5`; rad etilsa fayl keshda qoladi, faqat muvaffaqiyatli tasdiqdan keyin o'chadi), `Import/Preview.vue`, `Import/Index.vue`.
- **`App\Services\Exam\QuestionPicker`** — filtrlar (fan, mavzu daraxti, tur, qism, tasdiqlangan / imtihonga yaroqli, istisno id'lar, qo'shimcha shart), har daraja va guruh bo'yicha sonlar, taqsimot (`{3: 5}`, `{1: 2, 3: 5, 5: 1}`, `{easy: 5, medium: 10, hard: 5}`, `{any: n}`), seed bilan tasodifiy tanlash, qatorma-qator yetishmovchilik; o'quvchi berilsa, uning oxirgi 8 haftadagi variantlaridagi savollar oxiriga suriladi. `BlueprintCapacityChecker` pool'larini shu orqali quradi va `levelCounts()` beradi; `ExamReadinessChecker` har qoida uchun `levels` qo'shadi. `VariantGenerator` tegilmadi.
- **Kurs testi «Bankdan qo'shish»** — `Teacher\TestBankController` (`create`, `search` JSON, `store`), `Pages/Teacher/Courses/Tests/Questions/FromBank.vue`, `Questions/Index.vue` (tugma, «Bankdan» belgisi). Fan, mavzu, qiyinlik; har darajada nechta savol borligi; qo'lda belgilash (KaTeX ko'rinishi `RichContent` bilan) yoki taqsimot («oson 5 · o'rta 10 · qiyin 5»). Faqat tasdiqlangan variantli savollar (`single_choice`, `multiple_choice`) nusxalanadi: `questions` + `question_options`, daraja guruhga o'giriladi, yechim → `explanation`, `bank_question_id` — kelib chiqishi. Bir testga bitta bank savoli ikki marta tushmaydi (tranzaksiya ichida qayta tekshiriladi). Testdagi savolning nusxasi (`duplicate`) kelib chiqishni o'chiradi.
- **Blueprint** — `Blueprints/Form.vue`: yangi qoida darajasiz boshlanadi; tanlovda «Istalgan (1–5) · N ta» va «3 — o'rta · N ta»; qoida ostida 1–5 sonlari, tanlangan daraja yetmasa ogohlantirish. `BlueprintController::validated` — har qoidada kalit bo'lishi shart (`present`; null faqat «Istalgan» uchun).
- **Olimpiada** — `Admin\OlympiadController::assignQuestions` (majburiy `difficulty`, `QuestionPicker` orqali), `edit` → `bankLevels`; `Admin/Olympiad/Edit.vue`.
- **Kalibrlash (faqat taklif)** — `App\Services\Exam\DifficultyCalibration`: bank — yakunlangan, bekor qilinmagan urinishlardagi baholangan javoblar; kurs testi — battle p̂ (`docs/battle/SCORING.md`). Kamida `exam.calibration.min_answers` (standart 20) javob bo'lsa: «amalda: 82% yechgan (34 ta javob)»; chegaralar battle'niki (p ≥ 0,70 — oson, p < 0,40 — qiyin), 1–5 taklif. Bank ro'yxatida belgi va «Belgilangan va amaldagi qiyinlik farq qiladi» filtri (`?calibration=mismatch`); bank tahriri, tekshiruv navbati, kurs testi ro'yxati va tahririda ham ko'rinadi. Daraja hech qachon avtomatik o'zgarmaydi.

### Migratsiyalar
- `2026_10_24_200001_add_bank_question_id_to_questions_table` — `questions.bank_question_id` (nullable uuid, FK `question_bank`, `nullOnDelete`) va `(test_id, bank_question_id)` indeksi. Faqat qo'shuvchi.

### Marshrutlar
- `GET  teacher/tests/{test}/questions/from-bank` → `teacher.tests.questions.bank`
- `GET  teacher/tests/{test}/questions/from-bank/search` → `teacher.tests.questions.bank.search` (JSON)
- `POST teacher/tests/{test}/questions/from-bank` → `teacher.tests.questions.bank.store` (throttle 30/daq)

### Testlar: nima tekshirildi, natija
| Fayl | Tekshiradi |
|---|---|
| `tests/Unit/DifficultyTest.php` (6), `resources/js/Utils/__tests__/difficulty.test.js` (8) | nomlar, moslik ikki tomonga, qat'iy parse, eski qiymat, yechilish ulushidan taklif |
| `tests/Feature/Exam/DifficultyEntryTest.php` (4) | bank formasi har intent'da qiyinliksiz rad etadi, tahrirda tozalab bo'lmaydi, yangi formada default yo'q, «yana qo'shish» darajani o'tkazadi, tekshiruvda null / 9 rad, AI ishga tushirish darajasiz rad va har qoralamada daraja |
| `tests/Feature/Teacher/TeacherReviewFixesTest.php` (o'zgartirildi) | kurs savoli darajasiz yaratilmaydi, yangilashda tozalanmaydi |
| `tests/Feature/Exam/ImportDifficultyTest.php` (4) | preview'da `needs_difficulty`, noto'g'ri qiymat xato emas; tasdiq darajasiz rad etiladi, qisman tanlov ham, qator olib tashlansa talab yo'qoladi, tanlangan daraja yoziladi, servis `DomainException`, faqat ustoz |
| `tests/Feature/Exam/QuestionPickerTest.php` (7) | filtrlar va sonlar, daraja va guruh taqsimoti, `any` oxirida, yetishmovchilik, seed, istisnolar, o'quvchining oxirgi variantlari oxirida (eskilari hisobga olinmaydi), noto'g'ri taqsimot |
| `tests/Feature/Teacher/TestBankImportTest.php` (6) | sonlar faqat variantli tasdiqlangan savollar bo'yicha; qo'lda tanlash nusxasi (matn, variantlar, javob kaliti, yechim, guruh, origin); dublikat yo'q; tasodifiy taqsimot va yetishmovchilik xabari; validatsiya; faqat kurs egasi; nusxa origin'siz |
| `tests/Feature/Exam/BlueprintDifficultyTest.php` (4) | jonli tekshiruvda har daraja soni va yetishmovchilik, kalitsiz qoida rad, null = istalgan, tahrir sahifasida `levels`, `BlueprintCapacityChecker::levelCounts`, o'quvchiga yopiq |
| `tests/Feature/Admin/OlympiadBankDifficultyTest.php` (1) | olimpiada bo'limi faqat tanlangan darajadan to'ladi, darajasiz rad, `bankLevels` |
| `tests/Feature/Exam/DifficultyCalibrationTest.php` (2) | bank: kam javobda belgi yo'q, bekor qilingan va tugamagan urinishlar hisoblanmaydi, farq filtri; kurs: battle p̂; daraja o'zgarmaydi |

Natija:
- yangi testlar: **34 passed (388 assertions)** + vitest 8;
- to'liq `php artisan test`: **700 passed, 1 failed** — `StudentPagesSmokeTest`: `student.battles.challenge.show` uchun `{code}` fixture yo'q (agent T'ning yangi marshruti, bu ishga aloqasi yo'q);
- `npx vitest run`: **1670 passed**;
- Vue fayllari `@vue/compiler-sfc` bilan tekshirildi;
- mavjud testlar yangi qoidaga moslandi: kurs savoli yaratish payload'lariga `difficulty`, olimpiada `assignQuestions` ga `difficulty: any` qo'shildi.

### Ma'lum cheklovlar / qolgan ishlar
- Kurs testiga faqat variantli savollar ko'chiriladi: son javobli (`numeric`, `numeric_with_unit`) savolning xatolik va birlik qoidalari kurs testida yo'q.
- `questions.difficulty` ustunining bazadagi `DEFAULT 'medium'` qoldi (eski yo'llar va kurs nusxasi uchun); jim default faqat formalar va so'rovlardan olib tashlandi.
- AI qoralamaga AI o'zi baholagan daraja yoziladi (ishga tushirishdagi daraja — so'rov uchun maqsad). Odatda ular bir xil.
- Battle savollari kurs testlaridan olinadi, bank bilan bog'lanmagan; battle lobby'sida nomlar endi bir xil, sonlar avvalgidek ko'rinadi.
- Kalibrlash bankda faqat imtihon javoblaridan hisoblanadi; kurs testiga ko'chirilgan nusxa javoblari bank savoliga qaytib yozilmaydi.
- Blueprint'da «Istalgan» hamon null bo'lib saqlanadi (generator uchun ma'nosi o'zgarmadi).

### Savollar (Qaror kerak)
1. «Saqlash va yana qo'shish» oldingi savolda tanlangan darajani keyingi formaga o'tkazadi (ketma-ket o'xshash masalalar uchun). Har safar bo'sh boshlansinmi?
2. Kalibrlash uchun eng kam javob soni 20 (`exam.calibration.min_answers`). Shu yetarlimi?
3. Farq guruh bo'yicha aniqlanadi (oson / o'rta / qiyin): masalan, 4 va 5 orasidagi farq belgilanmaydi. Shu kerakmi yoki 1–5 bo'yicha ±2 farq ham belgilansinmi?
4. Kurs testiga son javobli bank savollarini «bo'sh joyni to'ldirish» sifatida ko'chirish kerakmi (xatoliksiz, aniq javob bilan)?
5. AI qoralamada daraja AI bahosi bo'lib qolsinmi yoki ishga tushirishdagi daraja majburan yozilsinmi?

### Keyingi bosqichga tayyormi: **HA**
Hech qayerda jim «o'rta» qolmadi; bankdan olishning barcha yo'llarida daraja tanlanadi va mavjud savollar soni ko'rinadi.

---

## Telegram WebView (imtihon va battle)

Talab: «Imtihon va battle'larni bemalol Telegram botda ishlash imkoni bo'lsin, ya'ni WebView shaklida bo'lishi kerak».
Dizayn: `03_RASM_QIYINLIK_TELEGRAM.md` §3 (agent T). Sozlash: `INTEGRATIONS.md` §2.7. Commitlar: `87c2cfa` (server,
bot, emulyator), `200b068` (frontend).

### Nima qilindi (fayllar ro'yxati bilan)

**Kirish nuqtalari**
- Deep link va `web_app` tugmalari uchun bitta grammatika: `exam`, `exam_s_<sessiya>`, `placement_<fan>`, `result_<urinish>`,
  `progress`, `leaderboard`, `battle`, `battle_<kod>`, `report[_<id>]` (ota-ona). Belgilar va uzunlik Telegram qoidasi
  bo'yicha tekshiriladi; noma'lum yoki begona parametr — imtihon bosh sahifasi. `placement_<fan>` testni darhol
  boshlaydi (kirish sahifasi CSRF tokeni bilan POST yuboradi). — `app/Services/Telegram/TelegramStartParam.php`.
- `telegram:setup-menu` (yangi): `setMyCommands` (`/start /imtihon /battle /natijalar /taklif /yordam /bekor`) va
  `setChatMenuButton` (`web_app`). `telegram:set-webhook` uni o'zi chaqiradi. — `TelegramSetupMenuCommand.php`,
  `TelegramClient::setMyCommands()`, `config/exam.php` → `telegram.commands`.
- `/imtihon` (boshlangan urinish bo'lsa — davom ettirish; aks holda imtihonlar va topshirilmagan qabul testlari) va
  `/battle` javoblari `web_app` tugmalari bilan. Eslatmalar `exam_s_<id>`, natija xabari `result_<id>`, yakshanba
  hisoboti `report_<id>` ochadi. — `BotUpdateHandler`, `TelegramReminderService`, `ExamTelegramNotifier`, `lang/uz/exam_bot.php`.

**WebView rejimi**
- Mini App kirishida sessiyaga `webview=telegram` yoziladi (`WebviewSession`). Faqat shunday sessiyalarga Inertia
  `webview` / `telegram` prop'lari va Telegram SDK skripti beriladi (`HandleInertiaRequests`, `app.blade.php`).
- `useTelegram()` (`resources/js/Composables/useTelegram.js`): `ready/expand`; `themeParams` → CSS tokenlar (yorug'/tungi
  Telegram'ga ergashadi, saytdagi tanlov o'zgarmaydi); `viewportStableHeight`; safe area; `BackButton` va `MainButton`
  sahifa bo'yicha (stek); imtihon/jang paytida `enableClosingConfirmation` + `disableVerticalSwipes`; `HapticFeedback`;
  tashqi havolalar `openLink` bilan, `t.me` havolalari `openTelegramLink` bilan; `shareToStory` yoki ulashish oynasi;
  `activated/deactivated`; 401/419 da jim qayta kirish. Oddiy brauzerda hammasi no-op. `app.js` da bitta chaqiruv.
- `StudentLayout` → `WebviewShell.vue`: yon menyu, header va footer yo'q; pastda Imtihon / Battle / Natijalar / Profil;
  «Saytda ochish»; WebView uchun qilinmagan bo'limlarda «Bu bo'lim saytda to'liq ishlaydi» eslatmasi; klaviatura
  ochilganda tablar yashirinadi, maydon ko'rinadigan joyga suriladi.
- Take: `MainButton` «Topshirish · 4/15» → tasdiqlash → «Ha, topshirish»; `BackButton` — oldingi savol (birinchi savolda
  yo'q); Mini App yig'ilsa yoki boshqa ilovaga o'tilsa `tab_switch` kabi yoziladi (ikki marta sanalmaydi). Son javob —
  `inputmode="decimal"` (bor edi).
- Result: «Natijani ulashish» (`MainButton`): mobil Telegram'da story, boshqa joyda chatga yuborish. Karta 30 daqiqalik
  imzolangan havola orqali (`/telegram/card/{attempt}`, `ShareCardController`).

**Sessiya**
- `ApplyWebviewCookies`: HTTPS'da Mini App sessiyasi cookie'lari `SameSite=None; Secure; Partitioned` (sozlanadi:
  `TELEGRAM_WEBVIEW_SAME_SITE`, `TELEGRAM_WEBVIEW_PARTITIONED`). Saytning cookie'lari `lax` bo'lib qoladi.
- `POST /telegram/app/reauth`: initData bilan jim qayta kirish, yangi CSRF token qaytaradi. `auth` va `reauth` CSRF'dan
  ozod, lekin faqat JSON qabul qiladi (boshqa forma — 415). Login sahifasiga tushib qolsa, ilova uni ko'rsatmay qayta
  kiradi va o'sha sahifaga qaytadi; daqiqasiga 3 tadan ortiq urinish bo'lsa — «cookie bloklangan» xabari (cheksiz
  aylanish yo'q). `X-Frame-Options` / `frame-ancestors` yo'qligi testda tekshiriladi.

**Battle**
- Do'stni chaqirish: `battles.invite_code` (8 belgili tasodifiy kod), chaqiruv sahifasi va «Qabul qilish»
  (`BattleChallengeController`, `MatchmakingService::createChallenge/acceptChallenge`, `Pages/Student/Battle/Challenge.vue`).
  Tasodifiy qidiruv chaqiruvlarga qo'shilmaydi; chaqiruv 15 daqiqa amal qiladi. Kutish sahifasida «Do'stga yuborish».
- Match (Mini App'da): javobga haptic; jang paytida yopishni so'rash va Back yo'q; sahifa 1,5 s da to'liq prop'lar
  o'rniga ~100 baytlik `GET /student/battles/{battle}/state` so'raydi va faqat o'zgarish bo'lsa yangilanadi; Telegram
  yig'ilganda so'rovlar to'xtaydi. Vaqt faqat serverda — biri Telegram'da, biri saytda o'ynasa ham teng.
  Kichik ekranda hisob bir qatorda, avatarlar yashirin. Saytdagi Match avvalgidek ishlaydi.

**Emulyator `/telegram/dev`** (faqat `APP_ENV=local`) — `DevEmulatorController`, `resources/views/telegram/dev.blade.php`,
`resources/telegram/webapp-mock.js`: initData serverda imzolanadi, ilova iframe'da soxta `Telegram.WebApp` bilan ochiladi
(Telegram Web'ning postMessage protokoli), Back/Main tugmalari, mavzu, o'lcham, «Yig'ish», hodisalar jurnali.

### Migratsiyalar
- `2026_10_24_300001_add_invite_code_to_battles_table` — `battles.invite_code` (nullable, unique). Additiv.

### Marshrutlar
- `POST /telegram/app/reauth` (`telegram.app.reauth`), `GET /telegram/card/{attempt}` (`telegram.card`, signed);
- `POST /student/battles/challenge`, `GET|POST /student/battles/challenge/{code}`, `GET /student/battles/{battle}/state`;
- `GET /telegram/dev`, `POST /telegram/dev/sign`, `GET /telegram/dev/sdk.js` (local'dan tashqarida 404).

### Testlar: nima tekshirildi, natija
| Test | Nima |
|---|---|
| `tests/Feature/Exam/TelegramWebviewTest.php` (18) | deep link'lar va validatsiya (begona urinish, noto'g'ri belgilar, 64+ belgi, xavfli `return`), placement POST, webview belgisi va prop, saytda prop/skript/`card_share` yo'qligi, reauth (qayta kirish, akkauntni almashtirish, 401), faqat JSON, cookie atributlari (HTTPS'da none+secure+partitioned, HTTP'da lax, sayt sahifasi lax), frame sarlavhasi yo'q, emulyator (testing'da 404, local'da imzo va kirish), `setMyCommands` + menyu tugmasi, set-webhook buyruqlarni ham o'rnatadi, `/imtihon` va `/battle` `web_app` tugmalari, eslatma va natija tugmalari, imzolangan karta |
| `tests/Feature/Student/BattleChallengeTest.php` (4) | chaqiruv va qabul, tasodifiy qidiruv qo'shilmaydi, kod battle id emas, deep link, state poll faqat o'yinchiga va raqib javobida o'zgaradi, eskirgan/o'z chaqiruvi, savol yetmasa 422 |
| `useTelegram.test.js` (9, vitest) | brauzerda no-op, initData'siz Telegram obyekti ham no-op, mavzu xaritasi, init, MainButton va yopish himoyasi, haptic / openLink / story, yig'ish-qaytish, 419 da qayta kirish va bitta takror |

- Mavjud testlar: `TelegramMiniAppTest` (nisbiy auth URL), `TelegramNotificationsTest` (hisobot tugmasi endi `web_app`),
  `StudentPagesSmokeTest` (`{code}` fixture) moslandi.
- To'liq `php artisan test`: **726 passed** (smoke fixture'dan oldin 725 passed + 1 failed edi). `npx vitest run`: **1681 passed**.
- Playwright (emulyator orqali, alohida test build'i bilan, `public/build` ga tegilmadi): **44/44** — qabul testi
  deep link → Take → yig'ish (yozildi) → MainButton bilan topshirish → natija; 320/375/430 px, yorug'/tungi: bosh sahifa,
  O'sishim, reyting — gorizontal scroll yo'q; ikki emulyatsiya qilingan o'quvchi orasida do'st chaqiruvi bilan to'liq
  battle; oddiy brauzerda SDK yo'q. Skrinshotlar: scratchpad `telegram-webview/`.

### Ma'lum cheklovlar / qolgan ishlar
- Haqiqiy Telegram'da sinalmadi (HTTPS domen/tunnel va bot kerak) — faqat emulyatorda.
- `t.me/<bot>?startapp=` havolasi uchun BotFather'da Main Mini App yoqilishi kerak; bot username bo'lmasa chaqiruv
  sayt havolasi bilan ulashiladi.
- Inline rejim (`switchInlineQuery`) ishlatilmadi: botda inline so'rovlarga javob yo'q; do'stga chaqiruv `t.me/share/url` orqali.
- Ota-ona paneli (`GuardianLayout`) WebView'da ixcham emas — hisobot sahifasi oddiy ko'rinishda ochiladi.
- Desktop Safari uchinchi tomon cookie'larini `Partitioned` bilan ham saqlamasligi mumkin: u holda Telegram Web'da jim
  qayta kirish ishlaydi, lekin juda tez-tez bo'lsa «cookie bloklangan» xabari chiqadi.
- Saytdagi Match sahifasi avvalgidek har 1,5 s da to'liq prop'larni so'raydi (sayt o'zgarmasin deb).
- Emulyator uchun dev bazada ikki demo o'quvchi yaratildi: «TG Demo Bir» (+998900000801) va «TG Demo Ikki» (+998900000802).

### Savollar (Qaror kerak)
1. «Do'stni chaqirish» hozir faqat Mini App ichida ko'rinadi (sayt o'zgarmasin). Saytda ham ko'rsatilsinmi?
2. `placement_<fan>` deep link testni tasdiqsiz darhol boshlaydi (tugma «Qabul testini boshlash» deydi). Avval imtihon
   sahifasi ochilib, «Boshlash»ni bosish kerakmi?
3. Jang paytida pastki tablar (Imtihon / Battle / …) ko'rinib turadi va ular bilan chiqib ketish mumkin. Jang paytida
   yashirilsinmi?
4. Natija kartasi faqat haftalik imtihonda bor (Bosqich 7). Qabul testi natijasini ham ulashish kerakmi?
5. Saytning Match sahifasiga ham yengil state so'rovi qo'yilsinmi (trafik kamayadi, xatti-harakat o'zgarmaydi)?

### Keyingi bosqichga tayyormi: **HA**
Imtihon (bosh sahifa, anketa, qabul testi, Take, natija, O'sishim, reyting) va battle (lobby, do'st chaqiruvi, jang,
tarix) Mini App ichida ishlaydi; sayt o'zgarmadi. Haqiqiy botda sinash uchun: tunnel + `telegram:set-webhook` +
BotFather Mini App URL.

---

## Rasm va grafiklar (to'lqin 2, agent M)

Talab: matematika va fizika masalalarida rasm va grafik shartda, variantlarda, yechimda va dars matnida bo'lishi kerak. Bu imtihon banki, kurs testi, battle, olimpiada, import, AI qoralama va Telegram uchun. Dizayn — `03_RASM_QIYINLIK_TELEGRAM.md` §1. Ustoz uchun qo'llanma — `RASM_VA_GRAFIK.md`.

### Nima qilindi (fayllar ro'yxati bilan)
- **Bitta renderer.** `Utils/richText.js` ga `richSegments()` qo'shildi. U matnni HTML bo'laklari va `graph` bloklariga ajratadi.
  - Rasm `![izoh](/storage/questions/YYYY/MM/<nom>.webp){width=60%}` ko'rinishida yoziladi. Faqat o'zimizga yuklangan rasmlar chiqadi, boshqalari tashlab yuboriladi.
  - Rasm `loading="lazy"` va izoh (alt) bilan chiqadi. Bosilganda kattalashadi (`Components/Media/ImageZoom.vue`, `useImageZoom.js`).
  - KaTeX va DOMPurify avvalgidek ishlaydi. Rasm va formula sanitizatsiyadan keyin qo'yiladi, atributlari escape qilinadi.
  - `RichContent.vue` va `MathText.vue` shu bitta kodni ishlatadi. `MathText zoom="badge"` rejimida variant kartasidagi rasmni bosish variantni tanlaydi. Rasm esa ⤢ belgisi orqali kattalashadi.
- **Grafik.**
  - `Components/Media/GraphView.vue` — o'zimizning SVG chizgichimiz. U spec v1 ning hammasini chizadi: oraliq, to'r va bo'linmalar, o'q nomlari, teng masshtab, kenglik, funksiyalar (`domain`, `dashed`, `label`, `color`), siniq chiziqlar, nuqtalar, kesmalar, strelkali vektorlar, ko'pburchaklar, aylanalar, yozuvlar va burchaklar.
  - Ranglar mavzu tokenlaridan olinadi, shuning uchun tungi rejimda ham to'g'ri ko'rinadi.
  - Grafik konteynerning haqiqiy kengligida chiziladi, shuning uchun telefonda ham aniq ko'rinadi. Tor kartada shrift ixchamlashadi.
  - `Utils/graph/expr.js` — xavfsiz ifoda tahlilchisi. `eval` va `Function` ishlatilmaydi, `constructor` kabi prototip nomlari rad etiladi.
  - `Utils/graph/spec.js` — validator, bo'linmalar va namunalash. Uzilish nuqtasi bisection bilan topiladi, shuning uchun `1/x`, `tan x` va `floor x` grafiklarida tik chiziq chiqmaydi.
- **Server.**
  - `app/Support/RichText/GraphExpression.php` va `GraphSpec.php` JS kodining aynan nusxasi: grammatika, kalitlar, chegaralar va xabarlar bir xil.
  - `RichTextMedia.php` quyidagilarni tekshiradi: rasm havolasi, maydon chegaralari (savolda 6 ta rasm va 3 ta grafik, darsda 40 va 20) va yopilmagan blok.
  - `app/Rules/SafeRichText.php` saqlashda qo'llanadi:
    - imtihon banki va tekshiruvdagi tahrir (`HandlesBankQuestions`: shart, variantlar, yechim);
    - kurs testi (`Store/UpdateQuestionRequest`: savol, izoh, `option_text`, `match_text`);
    - dars (`Store/UpdateLessonRequest`).
  - Olimpiada savollari bankdan olinadi, shuning uchun alohida tekshiruv kerak emas.
- **Yuklash.** `POST /media/question-images` faqat ustoz va admin uchun, daqiqasiga 60 marta.
  - `App\Services\Media\QuestionImageStore` faqat png, jpg, webp va gif qabul qiladi. Tur fayl nomidan emas, mazmunidan aniqlanadi, shuning uchun nomi `.png` bo'lgan SVG yoki HTML rad etiladi.
  - Chegaralar: 5 MB va 40 megapiksel.
  - Rasm GD bilan qayta kodlanadi va JPEG burilishi (EXIF) to'g'rilanadi. Keyin 1600 pikselgacha kichraytiriladi va WebP sifatida `public` diskka `questions/YYYY/MM/<uuid>.webp` qilib saqlanadi.
- **Tozalash.** `media:prune-question-images` buyrug'i (`--dry-run`, `--days=7`) har kuni soat 04:30 da ishlaydi.
  - Hech qaysi matnda ishlatilmagan va 7 kundan eski rasmlar o'chiriladi.
  - Havolalar shu jadvallarda qidiriladi: `question_bank`, `question_bank_options`, `questions`, `question_options`, `lessons`, `olympiad_demo_questions`. O'chirilgan (soft-delete) qatorlar ham hisobga olinadi.
- **Muharrir.**
  - `Components/Media/MediaTools.vue` ikkita tugma qo'shadi:
    - **«Rasm»** — fayl tanlash, Ctrl+V yoki sudrab tashlash; yuklash foizi va xato xabari ko'rinadi;
    - **«Grafik»** — `GraphBuilderDialog.vue` konstruktorini ochadi.
  - Konstruktorda oraliq, o'q nomlari, to'r, teng masshtab, kenglik, funksiyalar, siniq chiziqlar uchun nuqtalar jadvali, nuqtalar, kesmalar va vektorlar bor. Grafik shu zahoti chiziladi. Qo'shimcha kalitlar «JSON» bo'limida kiritiladi. Kursor grafik bloki ichida tursa, shu blok tahrirlanadi.
  - `MarkdownEditor` ga ulandi, shuning uchun imtihon savoli va yechimi, tekshiruvdagi tahrir va dars matnida tugmalar avtomatik paydo bo'ldi.
  - Oddiy matn maydonlari ham ulandi:
    - imtihon variantlari (`OptionsEditor` → `MediaTextarea`);
    - kurs testi savoli va izohi (`QuestionFormFields`);
    - kurs testi variantlari (`SingleChoice`, `MultipleChoice`; grafik bir necha qatordan iborat, shuning uchun variant maydoni textarea qilindi).
- **Ko'rsatish.**
  - Variantlardan birida rasm yoki grafik bo'lsa, variantlar 2 ustunli kartalar bo'lib chiqadi. 360 px dan tor ekranda ular 1 ustunda turadi (`optionMedia.js`). Bu `QuestionSingleChoice`/`MultipleChoice` (imtihon Take va kurs testi Attempt), imtihon Result, olimpiada imtihoni va tahlilida ishlaydi.
  - Kurs testi Review'da tanlangan variantlar ham renderer orqali chiqadi.
  - Eski rasm ustunlari ishlashda davom etadi. Variant rasmi (`question_bank_options.image`) endi `QuestionImage.vue` orqali chiqadi va bosilganda kattalashadi.
  - **Battle `Match.vue` tuzatildi:** savol va variantlar `MathText` bilan chiqadi. Raundlar ro'yxatida rasm va grafik o'rniga «[rasm]» / «[grafik]» yoziladi.
  - Ustoz va admin ro'yxatlarida (kurs testlari, admin savollar banki, olimpiada baholash navbati) bir qatorli ko'rinishda ham shu belgilar chiqadi. Batafsil ko'rinishda formula, rasm va grafik to'liq chiziladi.
  - Ota-ona hisobotida savol matni ko'rsatilmaydi, shuning uchun u yerda o'zgarish kerak bo'lmadi.
- **Import.**
  - JSON va CSV ichida o'zimizga yuklangan rasm havolasi va `graph` bloki bo'lishi mumkin. Har qatorning sharti, variantlari va yechimi tekshiriladi.
  - Yangi **ZIP** formati qo'shildi: `questions.json` (yoki `questions.csv`) va `images/` papkasi (`QuestionZipReader`).
    - Matnda havola qilingan har bir rasm yuklashdagi tekshiruvlardan bir marta o'tadi va havola almashtiriladi.
    - Topilmagan, SVG yoki soxta rasm o'sha qatorning xatosi bo'ladi. Ishlatilmagan rasm yuklanmaydi.
    - Arxiv diskka ochilmaydi va uning hajmi cheklangan.
  - ZIP shabloni (`QuestionZipTemplate`) «Import» sahifasidan yuklab olinadi. Unda bitta rasmli va bitta v–t grafikli savol bor.
  - Preview shabloniga tegilmadi, chunki u agent Q ning ishi.
- **AI.**
  - Prompt `question_draft.md` **1.1.0** versiyasiga ko'tarildi. Unga graph spec, namuna va «rasm qo'yma» qoidasi qo'shildi.
  - `QuestionDraftSchema` rasmli qoralamani va noto'g'ri grafikli qoralamani rad etadi. Sababi ishga tushirish jurnalidagi `validation_errors` ga yoziladi.
- **Takror savollar.** `QuestionText::normalize` rasm va grafikni olib tashlab solishtiradi. Faqat rasmdan iborat matn o'zgarmaydi, shunda ikki xil rasm bir xil savol deb hisoblanmaydi.
- **Telegram.** `BotText::get()` qiymatlarida va yangi `BotText::questionText()` da rasm o'rniga «[rasm]», grafik o'rniga «[grafik]» yoziladi.
- **Seed.** `ExamMediaDemoSeeder` (`DatabaseSeeder` dan chaqiriladi) 5 ta tasdiqlangan `is_demo` savol qo'shadi:
  - parabola;
  - «qaysi grafik y = 2x − 1 ga mos» — variantlari grafik;
  - v–t grafigi bo'yicha yo'l (24 m);
  - ikki perpendikular kuchning teng ta'sir etuvchisi (5 N);
  - GD bilan chizilib, yuklama sifatida saqlangan uchburchak rasmi.

  Javoblar alohida hisoblanadi. Variantlar, grafikdagi nuqtalar va «to'g'ri grafik» ham tekshiriladi. Seed dev bazaga yozildi.
- **Hujjat.** `docs/exam/RASM_VA_GRAFIK.md` yozildi. Unda quyidagi namunalar bor: parabola, bo'lakli v–t grafigi, vektorlar, burchagi belgilangan uchburchak, giperbola va aylana. Namunalar PHP va JS testlarida tekshiriladi.

### Migratsiyalar
- Yo'q. Rasm va grafik matnning o'zida saqlanadi.

### Marshrutlar
- `POST /media/question-images` → `media.question-images.store` (`auth`, `role:teacher,admin,super_admin`, `throttle:60,1`). Fayl: `routes/exam_media.php`, `web.php` dan ulangan.
- `GET teacher/exam/import/template?format=zip` — mavjud marshrutga yangi format qo'shildi.
- Rejali buyruq: `media:prune-question-images`, har kuni 04:30 (Asia/Tashkent).

### Testlar: nima tekshirildi, natija
| Fayl | Tekshiradi |
|---|---|
| `Utils/graph/__tests__/expr.test.js` (6) | amallar tartibi, yashirin ko'paytirish, funksiyalar, modul, `t`, NaN; `alert(1)`, `constructor`, `__proto__` rad etiladi; uzunlik va chuqurlik chegarasi |
| `Utils/graph/__tests__/spec.test.js` (10) | spec validatsiyasi; `1/x` ikki bo'lakda, `tan x` beshta o'suvchi bo'lakda, `floor` sakrashi, `sqrt` sohasi, `exp` uzilmaydi, `domain`; bo'linmalar |
| `Utils/__tests__/richTextMedia.test.js` (13) | rasm allow-list (tashqi, `//`, `javascript:`, `data:`, `../`, svg, query); alt, URL va grafik yozuvlaridagi XSS; zoom, badge, width; `hasMedia`, «[rasm]» / «[grafik]» |
| `battleRender.test.js` (2), `guideExamples.test.js` (2), `markdownMediaTools.test.js` (5) | Match.vue MathText bilan chiqaradi va payload KaTeX, rasm, grafikka aylanadi; qo'llanma namunalari; muharrir yordamchilari |
| `tests/Unit/RichText/GraphSpecTest.php` (5), `QuestionTextMediaTest.php` (2) | PHP validator JS bilan bir xil; RichTextMedia qoidalari; qo'llanma namunalari; takror xeshida rasm va grafik hisobga olinmaydi |
| `tests/Feature/Media/QuestionImageUploadTest.php` (7) | PNG → WebP; JPG, WebP, GIF va admin; 3200 → 1600 px; SVG va soxta `.png` rad; > 5 MB rad; mehmon 401, o'quvchi 403; throttle |
| `PruneQuestionImagesTest.php` (2) | dry-run; faqat eski va bog'lanmagan rasm o'chadi; o'chirilgan qatorlardagi havola ham saqlanadi |
| `QuestionZipImportTest.php` (4) | shablon import qilinadi, rasm WebP bo'ladi va havola almashadi; yo'q, SVG, soxta, tashqi rasm, noto'g'ri grafik va `../` qator xatosi bo'ladi; `questions.json` siz ZIP rad etiladi; JSON ichida URL va grafik |
| `MediaValidationOnSaveTest.php` (2) | bank, kurs testi va darsda to'g'ri media qabul qilinadi, noto'g'ri media rad etiladi |
| `AiDraftGraphTest.php` (2) | to'g'ri grafik saqlanadi; noto'g'ri ifoda, noma'lum kalit va rasm sababi bilan rad etiladi; prompt versiyasi |
| `BattleMathPayloadTest.php` (1), `TelegramMediaTextTest.php` (2), `MediaDemoSeedTest.php` (2) | battle payload o'zgarmaydi; Telegram belgilari va escape; demo seed tekshirilgan va idempotent |

Natija:
- mening testlarim: PHP **29 passed (261 assertions)**, vitest **38 passed**;
- to'liq `php artisan test`: **729 passed (17500 assertions)**, xato yo'q;
- `npx vitest run`: **1683 passed**;
- Vue fayllari `@vue/compiler-sfc` bilan tekshirildi. Grafik, konstruktor va variant kartalari SSR orqali chizilib, yorug' va tungi rejimda, 375 va 1100 px kenglikda ko'rib chiqildi.

### Ma'lum cheklovlar / qolgan ishlar
- Yangi Tailwind klasslari (`min-[360px]:grid-cols-2`, `lg:grid-cols-2`, `order-first`) faqat `npm run build` dan keyin ishlaydi.
- Eski HTML darslardagi `<img>` teglari avvalgidek DOMPurify orqali chiqadi. Allow-list faqat Markdown rasmlariga qo'llanadi. Bazada bunday rasm yo'q.
- Kurs testining `question_image` va `option_image` ustunlari o'quvchiga yuborilmasdi. Ularni yuklash interfeysi yo'q, bazada qiymat ham yo'q, shuning uchun hozircha qo'shilmadi.
- Kurs testidagi «Moslashtirish», «Tartiblash» va «Bo'sh joy» turlarida media tugmalari yo'q.
- Tekshiruv navbatidagi va bank ro'yxatidagi variantlar 2 ustunli kartaga o'tkazilmadi, chunki fayllar agent Q ning ishi bilan band edi. Media u yerda ham ko'rinadi.
- GIF animatsiyasi saqlanmaydi: WebP ga o'girilganda faqat birinchi kadr qoladi.
- Grafik yozuvlarida LaTeX ishlamaydi. O'rniga Unicode ishlatiladi: `x²`, `F₁`, `α`.

### Savollar (Qaror kerak)
1. Bir maydonda 6 ta rasm va 3 ta grafik, darsda 40 va 20 ta — shu chegaralar yetarlimi?
2. Tashqi havoladagi rasm rad etiladi. Uni avtomatik yuklab olish kerakmi? Bu SSRF xavfi tufayli hozircha qilinmadi.
3. Dizaynga ko'ra takror savol aniqlashda rasm va grafik hisobga olinmaydi. Shuning uchun matni bir xil, grafigi boshqa savol «dublikat» deb belgilanadi. Shu ma'qulmi?
4. Bog'lanmagan rasmlar 7 kundan keyin o'chiriladi. Shu muddat to'g'rimi?
5. Olimpiadaga alohida savol muharriri kerakmi? Hozir savollar bankdan olinadi.

### Keyingi bosqichga tayyormi: **HA**
Rasm va grafik bitta formatda hamma joyda ishlaydi: kiritish, tekshirish, saqlash, ko'rsatish, import, AI va Telegram. Battle'dagi formula xatosi tuzatildi.

---

## Telegram — WebView-first (audit va tuzatishlar)

Talab: «telegram bot web li bo'lishi kerak, yana 1 bor to'liq analiz qilib chiq». Agent TW. To'liq audit (topilmalar
jadvali, dalillar, platformalar, xavfsizlik, Telegram hujjatlari bo'yicha tekshiruv): `docs/exam/TELEGRAM_AUDIT.md`.
Sozlash: `INTEGRATIONS.md` §2.8. Commitlar: `c0b24d6` (server), `5ff7c39` (frontend, emulyator), `0b0cf8d` (limitlar,
MainButton), `63744e3` (ustoz/admin chatda) va hujjatlar commiti.

### Nima qilindi (fayllar ro'yxati bilan)

**Chat — faqat ochish va bildirishnoma**
- `/start` (oddiy va `ref_<kod>`) — bitta qisqa xabar + «🚀 Ochish» (`web_app`); menyu tugmasi «Ochish».
  `/natijalar` — 1 qator + «Natijalarni ochish» / «Natijani ko'rish»; `/taklif` — «📤 Do'stga yuborish» (`t.me/share/url`)
  + «🎁 Taklif sahifasi»; ro'yxatdan o'tmaganlarning har buyrug'iga «Ochish». Ustoz/admin chatda faqat «Saytda ochish»
  oladi. Ota-onaga natija xabari endi farzand sahifasini ochadi (`child_<id>`). `my_chat_member` — bloklash darhol
  bilinadi. — `BotUpdateHandler.php`, `ExamTelegramNotifier.php`, `TelegramClient.php`, `lang/uz/exam_bot.php`.
- Chatdagi 5 savollik anketa — faqat zaxira (`/royxat`), o'zi boshlanmaydi; `/bekor` uni bekor qiladi. Sabab: barcha
  rasmiy klientlarda Mini App bor (6.0), lekin eski/uchinchi tomon klientlar uchun arzon zaxira.

**Ro'yxat Mini App'da**
- `GET /telegram/app/register` (`Pages/Telegram/Register.vue`): kim (o'quvchi / farzandni ro'yxatdan o'tkazuvchi ota-ona /
  natijalarni kuzatuvchi ota-ona), sinf, fan, maqsad, maktab, telefon (`requestContact` yoki qo'lda), ota-ona telefoni +
  rozilik, reytingda ism roziligi; MainButton «Ro'yxatdan o'tish». O'quvchi → qabul testi; ota-ona → panel. Deep link
  (`battle_<kod>`, `ref_<kod>`) ro'yxatdan keyin davom etadi. Botga yozmaganlardan `requestWriteAccess`.
- `POST /telegram/app/register`, `POST /telegram/app/contact` (`MiniAppRegistrationController`): har so'rovda initData;
  `requestContact` javobi `TelegramInitData::verifyContact()` bilan (imzo, `contact.user_id`, 15 daqiqa) yoki botga kelgan
  kontakt (webhook keshi). Qo'lda yozilgan telefon faqat yangi akkaunt ochadi, mavjudini ulamaydi.
- Qoidalar bitta joyda — `TelegramRegistration` (WebView va chat zaxirasi): ustoz/admin telefoni rad, boshqa Telegram'ga
  ulangan akkaunt rad, profil saytdagi anketa kabi yoziladi, ota-ona bog'lanishi `GuardianLinkService::request()` orqali
  (ustoz tasdiqlaydi), taklif `ReferralService::recordJoin()`.
- `/telegram/app/auth`: ro'yxatdan o'tmagan → 409 + ro'yxat URL (oldingi sessiya yopiladi); ustoz/admin → 403.

**Ota-ona WebView'da**
- `GuardianLayout` → `WebviewShell` (Farzandlarim / Hisobotlar); `WebviewShell` endi `tabs` / `supported` qabul qiladi.

**Boshqa tuzatishlar**
- Limitlar: `TelegramRateLimits` — Mini App endpointlari Telegram foydalanuvchisi bo'yicha (CGNAT), battle `state` /
  chaqiruv / qabul alohida hisoblagichda, webhook 6000/daq.
- `useTelegram`: `hideProgress()` endi `setParams` dan oldin — nofaol MainButton nofaol qoladi.
- «Saytda ochish»: bir martalik kirish havolasi (`/telegram/app/site-link` → `/telegram/site-login/{token}`).
- Saytda «Telegramni ulash» (o'quvchi Sozlamalari, ota-ona paneli). O'sishim'da `#taklif` kartasi va «Telegram'da yuborish».
- Emulyator: yangi (ro'yxatdan o'tmagan) foydalanuvchi, ota-onalar, `requestContact` / `requestWriteAccess` (haqiqiy SDK
  protokoli), faqat o'quvchi/ota-ona imzolanadi. — `DevEmulatorController.php`, `dev.blade.php`, `webapp-mock.js`.

### Migratsiyalar
Yo'q (kontakt keshda, yangi ustun kerak bo'lmadi).

### Marshrutlar
`GET|POST /telegram/app/register`, `POST /telegram/app/contact`, `POST /telegram/app/site-link`,
`GET /telegram/site-login/{token}`, `POST /telegram/dev/contact` (faqat local). Limiterlar: §2.8.

### Testlar: nima tekshirildi, natija
| Test | Nima |
|---|---|
| `TelegramWebviewRegistrationTest.php` (25) | ro'yxatdan o'tmagan → 409 + ro'yxat, oldingi sessiya yopiladi; ustoz/admin 403; o'quvchi (kontakt, profil, akkaunt initData'dan, qabul testi POST); do'st chaqiruvi davom etadi; taklif (`start_param` va chat); farzandni ro'yxatdan o'tkazuvchi ota-ona; kuzatuvchi ota-ona (bog'lanish so'rovi, WebView prop); noma'lum farzand — hech narsa yaratilmaydi; rozilik va javoblar majburiy; mavjud o'quvchi faqat tasdiqlangan telefon bilan; qo'lda yozilgan telefon tasdiqlanmagan; ustoz va ulangan telefon rad; kontakt tekshiruvi (begona, boshqa token, eski, o'zgartirilgan, chet el raqami); webhook kontakti; faqat JSON va to'g'ri initData; qayta yuborish; ota-onaga natija tugmasi va `child_` izolyatsiyasi; qisqa javoblar `web_app` bilan; ro'yxatdan o'tmagan buyruqlar; ustoz chatda; bloklash/blokdan chiqarish; limitlar (CGNAT, battle); «Saytda ochish» bir marta |
| `TelegramBotTest.php` (16) | `/start` endi bitta `web_app` xabar; chat anketasi `/royxat` bilan (eski tekshiruvlar saqlandi); webhook limiter |
| `useTelegram.test.js` (10, vitest) | + nofaol MainButton nofaol qoladi |

- To'liq `php artisan test`: **754 passed (17846 assertions)**. `npx vitest run`: **1684 passed**. `npm run build` — bajarildi.
- Playwright (emulyator, har safar yangi demo foydalanuvchilar): **145/145** — ro'yxat ekrani 320/375/430 × yorug'/tungi,
  yangi o'quvchi → qabul testi → natija → taklif, `startapp=ref_`, ikki xil ota-ona, telefon zaxiralari, o'quvchi
  sahifalari 3 o'lcham × 2 mavzu, Telegram ↔ sayt battle. Eski `run_webview_8000.py` ham yangi ma'lumot bilan: **45/45**.

### Ma'lum cheklovlar / qolgan ishlar
- Haqiqiy Telegram'da sinalmadi (HTTPS tunnel + bot kerak). `requestContact` javobining imzosi Telegram hujjatida
  yozilmagan (SDK manbasidan); shuning uchun webhook'dagi kontakt ikkinchi dalil sifatida ham qabul qilinadi.
- Webhook'ga `my_chat_member` kelishi uchun `php artisan telegram:set-webhook` ni qayta ishga tushirish kerak.
- Shanba imtihoni demo sessiyasi (24.10) Playwright'da jonli topshirilmadi — egasining ma'lumotiga tegilmadi.
- `addToHomeScreen`, fullscreen, Cloud/Device storage ishlatilmadi (audit §1).
- Boshqa modullardagi `throttle:N,1` ham umumiy hisoblagichda (audit m-13) — o'zgartirilmadi.
- Emulyator har ishga tushishda dev bazada «Sinov Telegram» o'quvchi/ota-onalarni yaratadi (telefonlar `+99891…`).

### Savollar (Qaror kerak)
1. Telegram'da ochilgan akkaunt uchun saytda parol o'rnatish kerakmi (hozir faqat bir martalik havola)?
2. Saytdagi ota-ona akkaunti Telegram kontakti bilan avtomatik ulansinmi (hozir — ha, o'quvchi kabi)?
3. Qo'lda yozilgan (tasdiqlanmagan) telefon bilan yangi akkaunt ochish qolsinmi?
4. Chat zaxirasi `/royxat` qolsinmi?
5. initData TTL 24 soatdan 6 soatga tushirilsinmi?

### Keyingi bosqichga tayyormi: **HA**
O'quvchi va ota-ona bot orqali qiladigan hamma ish Mini App'da: ro'yxat, qabul testi, imtihon, natija, battle, o'sish,
reyting, taklif, ota-ona paneli va hisobotlar. Chat faqat «Ochish» va `web_app` tugmali bildirishnomalar.
