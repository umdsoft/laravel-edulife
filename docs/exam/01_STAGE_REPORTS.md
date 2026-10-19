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
