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
