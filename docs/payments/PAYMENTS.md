# Onlayn to'lovlar: Payme va Click

EDULIFE talabalari quyidagilarni **Payme** yoki **Click** orqali karta (Uzcard, Humo) yoki ilova bilan to'laydi:

| Nima | Qayerda | To'lovdan keyin nima beriladi |
|---|---|---|
| Pullik kurs | Kurs sahifasi → «Sotib olish» | Kursga yozish (`enrollments`, `access_type = purchase`) |
| Coin paketi | Coin do'kon → «Coin paketlari» | Coinlar `student_profiles.coins` ga qo'shiladi (oylik limitga kirmaydi) |
| Obuna tarifi | Coin do'kon → «Obuna tariflari» (`/student/subscriptions`) | `subscriptions` faollashadi yoki muddati uzayadi |
| Olimpiada to'lovi | Olimpiada → Ro'yxatdan o'tish → To'lov | Ro'yxat tasdiqlanadi (`olympiad_registrations.status = confirmed`) |
| Shanba imtihoni (pullik) | Imtihon sahifasi (`/student/exam`) → Payme / Click | Shu imtihonga kirish (`exam_session_purchases`, §9) |

Rasmiy hujjatlar (kod ham shularga mos yozilgan):

- Payme Merchant API: <https://developer.help.paycom.uz/metody-merchant-api/>
  (xatolar — `/metody-merchant-api/oshibki-errors`, ma'lumot turlari va holatlar — `/metody-merchant-api/tipy-dannykh`,
  avtorizatsiya va IP manzillar — `/protokol-merchant-api/skhema-vzaimodeystviya`, chek GET usulida — `/initsializatsiya-platezhey/otpravka-cheka-po-metodu-get`,
  sandbox — `/pesochnitsa`)
- Click SHOP API: <https://docs.click.uz/shop-api/requests>, xatolar — <https://docs.click.uz/shop-api/errors>,
  to'lov tugmasi — <https://docs.click.uz/click-button>

---

## 1. Qanday ishlaydi (arxitektura)

```
Talaba "Sotib olish" → POST /payments/checkout (summa SERVERDA hisoblanadi, promokod serverda qo'llanadi)
        │  payment_orders (№ — buyurtma raqami) + payments (ledger, status pending)
        ▼
Payme: https://checkout.paycom.uz/base64(m=…;ac.order_id=№;a=tiyin;c=qaytish_url)
Click: https://my.click.uz/services/pay?service_id=…&merchant_id=…&amount=N.NN&transaction_param=№&return_url=…
        │
        ├─ Payme serveri → POST /payments/payme   (JSON-RPC: CheckPerformTransaction, CreateTransaction,
        │                                          PerformTransaction, CancelTransaction, CheckTransaction,
        │                                          GetStatement, SetFiscalData)
        └─ Click serveri → POST /payments/click/prepare, /payments/click/complete
        │
        ▼  PerformTransaction / Complete muvaffaqiyatli → PaymentFulfillmentService::fulfil()
           (DB tranzaksiya + SELECT … FOR UPDATE, aynan BIR marta)
        ▼
Talaba qaytadi → /payments/orders/{uuid} — holat sahifasi (callback kechiksa 3 soniyada yangilanadi)
               (shanba imtihoni: /student/exam?paid={uuid} yoki Telegram Mini App — §9)
```

Fayllar:

| Fayl | Vazifasi |
|---|---|
| `config/payments.php` | Barcha sozlamalar (env kalitlari) |
| `routes/payments.php` | Callback va checkout yo'llari (`routes/web.php` dan bir qatorda ulanadi) |
| `app/Services/Payments/PaymeService.php` | Payme Merchant API (holatlar mashinasi, 12 soat timeout, idempotentlik) |
| `app/Services/Payments/ClickService.php` | Click Prepare/Complete, `sign_string` MD5 tekshiruvi |
| `app/Services/Payments/PaymentFulfillmentService.php` | Mahsulotni berish / qaytarib olish (aynan bir marta) |
| `app/Services/Payments/CheckoutService.php`, `PaymentProducts.php` | Narx, promokod, buyurtma, checkout URL |
| `app/Http/Controllers/Payments/*` | Payme, Click, Checkout, Obuna sahifasi controllerlari |
| `payment_orders`, `payment_transactions` jadvallari | Buyurtma va provayder tranzaksiyalari (Payme `id`, Click `click_trans_id`, holat, vaqtlar, sabab) |
| `resources/js/Components/Payments/CheckoutForm.vue`, `CheckoutModal.vue` | To'lov oynasi (Payme/Click tanlash, promokod) |
| `resources/js/Pages/Student/Payments/Status.vue` | Qaytish (holat) sahifasi |
| `resources/js/Pages/Student/Subscriptions/Index.vue` | Obuna tariflari sahifasi |
| `Admin → To'lovlar` | Provayder, buyurtma №, tranzaksiya ID, holat (1/2/−1/−2), sabab, vaqtlar |

Har bir buyurtma bitta provayderga bog'lanadi: Click uchun yaratilgan buyurtmani Payme to'lay olmaydi (va aksincha) —
ikki marta pul yechilishining oldi olinadi.

---

## 2. Merchant hisoblarini ochish

### 2.1. Payme Business

1. <https://business.payme.uz> da yuridik shaxs / YaTT sifatida ro'yxatdan o'ting, shartnoma tuzing.
2. Kabinetda **veb-kassa** yarating — turi: **«Merchant API» (billing bilan)**.
3. Kassa sozlamalarida (Разработчикам / Dasturchilar uchun):
   - **Endpoint URL**: `https://<sizning-domen>/payments/payme`
   - **Account (hisob) maydoni**: nomi `order_id` (kalit), turi — raqam; sarlavha masalan «Buyurtma raqami».
     Boshqa nom tanlasangiz `.env` da `PAYME_ACCOUNT_FIELD` ni o'zgartiring.
   - Hisob turi: **bir martalik (одноразовый)** — har bir buyurtma bir marta to'lanadi.
4. Kassa yaratilgach Payme ikkita kalit beradi: **KEY** (ishchi) va **TEST_KEY** (sandbox uchun).
   **Merchant ID (kassa ID)** kassa parametrlarida turadi.
5. Login har doim `Paycom` — so'rovlar `Authorization: Basic base64("Paycom:<KEY>")` bilan keladi.

### 2.2. Click

1. Click bilan merchant shartnomasini tuzing (<https://click.uz> → biznes uchun, yoki Click menejeri orqali).
2. Merchant kabinetida (<https://merchant.click.uz>) xizmat (service) yarating va menejerga quyidagi URL'larni bering:
   - **Prepare URL**: `https://<sizning-domen>/payments/click/prepare`
   - **Complete URL**: `https://<sizning-domen>/payments/click/complete`
3. Click beradigan qiymatlar: **SERVICE_ID**, **MERCHANT_ID**, **SECRET_KEY**, **MERCHANT_USER_ID**.
4. Hisob (to'lov parametri) — buyurtma raqami (`merchant_trans_id` = `payment_orders.id`).

---

## 3. Provayderlarga beriladigan URL'lar

| Provayder | Maydon | URL |
|---|---|---|
| Payme | Endpoint URL | `POST https://<domen>/payments/payme` |
| Click | Prepare URL | `POST https://<domen>/payments/click/prepare` |
| Click | Complete URL | `POST https://<domen>/payments/click/complete` |
| ikkalasi | Qaytish (return) URL | `https://<domen>/payments/orders/{uuid}` — avtomatik qo'shiladi (shanba imtihoni: §9) |

Bu yo'llar `web` middleware guruhisiz ishlaydi: sessiya, cookie, CSRF va login talab qilinmaydi.
Himoya — Payme Basic auth kaliti va Click `sign_string` imzosi (ixtiyoriy IP allowlist).
HTTPS majburiy (Payme faqat TLS orqali so'rov yuboradi).

---

## 4. `.env` kalitlari

`.env.example` da barcha kalitlar bor. Haqiqiy qiymatlarni faqat serverdagi `.env` ga yozing (gitga emas):

```dotenv
PAYME_MERCHANT_ID=5e730e8e0b852a417aa49ceb   # kassa ID
PAYME_KEY=                                   # ishchi kalit
PAYME_TEST_KEY=                              # sandbox kaliti
PAYME_TEST_MODE=true                         # true: TEST_KEY qabul qilinadi, talaba test.paycom.uz ga yuboriladi
PAYME_ACCOUNT_FIELD=order_id
PAYME_IP_CHECK=false                         # true: faqat 185.234.113.1–15 dan
PAYME_IKPU_CODE=                             # ixtiyoriy: fiskal chek uchun MXIK (tasnif.soliq.uz)
PAYME_PACKAGE_CODE=
PAYME_VAT_PERCENT=0

CLICK_SERVICE_ID=
CLICK_MERCHANT_ID=
CLICK_SECRET_KEY=
CLICK_MERCHANT_USER_ID=
CLICK_IP_CHECK=false
CLICK_ALLOWED_IPS=                           # vergul bilan, Click menejeridan so'rang

PAYMENT_REVERSE_ON_REFUND=true               # to'lov qaytarilsa berilgan narsa qaytarib olinadi
# PAYME_RETURN_URL / CLICK_RETURN_URL — ixtiyoriy, "{order}" o'rniga buyurtma uuid qo'yiladi
```

Provayder kalitlari to'ldirilmaguncha talabalarga u ko'rsatilmaydi ("To'lov tizimlari sozlanmagan" ogohlantirishi chiqadi).
Kalitlarni o'zgartirgach: `php artisan config:clear` (yoki `config:cache`).

Mahalliy ishlatish: `.env` ga hech narsa qo'shish shart emas (kalitlarsiz to'lov tugmalari o'chirilgan bo'ladi).
Sandbox bilan sinash uchun yuqoridagi `PAYME_*` / `CLICK_*` kalitlarni test qiymatlari bilan qo'shing.

---

## 5. Sinov

### 5.1. Avtomatik testlar (provayderga hech qanday so'rov yuborilmaydi)

```bash
php artisan test tests/Feature/Payments
```

- `PaymeMerchantApiTest` — har bir metod va xato kodi: auth (-32504), POST emas (-32300), JSON (-32700),
  maydonlar (-32600), metod (-32601), noto'g'ri summa (-31001), buyurtma yo'q (-31050), to'lanmaydigan buyurtma (-31051/-31052),
  ikkinchi tranzaksiya (-31008), 12 soat timeout (holat −1, sabab 4), qayta chaqiruvlar bir xil javob, bekor qilish (−1/−2, -31007),
  CheckTransaction, GetStatement, SetFiscalData, sandbox stsenariysi to'liq.
- `ClickShopApiTest` — imzo (-1), summa (-2), action (-3), allaqachon to'langan (-4), buyurtma yo'q (-5), tranzaksiya yo'q (-6),
  so'rov xatosi (-8), bekor qilingan (-9), takroriy Prepare/Complete.
- `PaymentFulfillmentTest` — har bir mahsulot aynan bir marta beriladi va to'g'ri qaytarib olinadi.
- `CheckoutFlowTest` — server narxi, promokod, Payme/Click URL, qaytish sahifasi, admin sahifalar.

### 5.2. Payme sandbox (test.paycom.uz)

1. Server internetdan HTTPS orqali ochiq bo'lishi kerak (mahalliy kompyuterda: `cloudflared tunnel --url http://127.0.0.1:8000`
   yoki `ngrok http 8000`, so'ng kassa Endpoint URL ga tunnel manzilini yozing).
2. `.env`: `PAYME_MERCHANT_ID`, `PAYME_TEST_KEY`, `PAYME_TEST_MODE=true`.
3. Sayt orqali buyurtma yarating (masalan coin paketi → Payme). Buyurtma raqami (№) — admin «To'lovlar» sahifasida.
4. <https://test.paycom.uz> → Merchant ID va TEST_KEY ni kiriting → hisob turi **bir martalik**.
5. Sandbox stsenariylari (ikkalasi ham o'tishi kerak):
   - **Yaratish va tasdiqlanmagan tranzaksiyani bekor qilish**: «Неверная авторизация» → -32504; «Неверная сумма» → -31001;
     «Несуществующий счёт» → -31050; CheckPerformTransaction → `allow: true`; CreateTransaction ×2 → bir xil javob;
     yangi tranzaksiya → -31008; CancelTransaction → holat −1.
   - **Yaratish, tasdiqlash va tasdiqlangan tranzaksiyani bekor qilish**: PerformTransaction ×2 → holat 2, bir xil javob;
     CancelTransaction → holat −2 (berilgan narsa qaytarib olinadi).
6. Har bir sinov uchun yangi buyurtma oching (to'langan buyurtma qayta to'lanmaydi → -31051).

### 5.3. Click sinovi

Click ochiq sandbox bermaydi — sinov rejimini Click menejeri yoqadi (test xizmati / test kartalari).
Qo'lda tekshirish uchun imzoni o'zingiz hisoblab yuborishingiz mumkin:

```bash
# md5(click_trans_id + service_id + SECRET_KEY + merchant_trans_id + amount + action + sign_time)
SIGN=$(printf '%s' "1001${CLICK_SERVICE_ID}${CLICK_SECRET_KEY}15""25000.00""0""2026-10-20 12:00:00" | md5sum | cut -d' ' -f1)
curl -X POST https://<domen>/payments/click/prepare \
  -d click_trans_id=1001 -d service_id=$CLICK_SERVICE_ID -d click_paydoc_id=1 -d merchant_trans_id=15 \
  -d amount=25000.00 -d action=0 -d error=0 -d error_note=Success -d "sign_time=2026-10-20 12:00:00" -d sign_string=$SIGN
# Complete: imzoga merchant_prepare_id qo'shiladi (merchant_trans_id dan keyin), action=1
```

---

## 6. Holatlar, idempotentlik, qaytarish

**Payme tranzaksiya holatlari** (`payment_transactions.state`): `1` yaratilgan → `2` o'tkazilgan; `1` → `−1` bekor;
`2` → `−2` o'tkazilgandan keyin bekor. Click: `1` prepare, `2` complete, `−1` bekor.

- `CreateTransaction` takrorlansa — o'sha javob; holat 1 emas yoki 12 soat (43 200 000 ms, Payme `time` dan) o'tgan bo'lsa —
  tranzaksiya sabab **4** bilan bekor qilinadi va -31008.
- Bitta buyurtmada bitta jonli Payme tranzaksiyasi: boshqa `id` bilan kelgan CreateTransaction → -31008.
- `PerformTransaction` takrorlansa — o'sha javob, mahsulot qayta berilmaydi (`payment_orders.fulfilled_at`).
- `CancelTransaction` (holat 1) — buyurtma yana to'lanishi mumkin. (holat 2) — **qaytarish (refund)**:
  - kurs — kirish yopiladi (enrollment `refunded` + soft delete; admin oldin ochgan bo'lsa tegilmaydi);
  - coin — coinlar yechib olinadi; talaba ularni sarflab bo'lgan bo'lsa **-31007** (qaytarish rad etiladi);
  - obuna — uzaytirish bo'lsa eski tugash sanasi qaytadi, aks holda obuna bekor qilinadi;
  - olimpiada — ro'yxat `refunded`; imtihon boshlangan bo'lsa **-31007**.
  - `PAYMENT_REVERSE_ON_REFUND=false` — barcha qaytarishlar -31007 bilan rad etiladi.
- Click'da qaytarish callback yo'q (Click Merchant API `payment/reversal` orqali merchant boshlaydi) — hozircha admin qo'lda hal qiladi.
- Click Complete `error < 0` bilan kelsa — tranzaksiya bekor (−1) va javob **-9**; takroriy Complete → **-4** (Already paid).
- Promokod ishlatilishi faqat to'lov o'tganda yoziladi (`promo_code_usages.payment_id`), qaytarilsa o'chiriladi.
  100% chegirma — buyurtma provaydersiz (`manual`) darhol beriladi.
- Bir vaqtda kelgan ikki callback: buyurtma va tranzaksiya qatorlari `lockForUpdate` bilan qulflanadi, unique
  `(provider, provider_transaction_id)` indeksi takroriy yozuvni to'xtatadi.

---

## 7. Xavfsizlik

- Summa har doim bazadan olinadi (kurs `price`, paket `price`, tarif `price`, olimpiada `final_price`, imtihon `exam_sessions.price`); so'rovdagi summa e'tiborga olinmaydi.
- Payme: Basic auth `hash_equals` bilan (constant-time); test rejimida faqat TEST_KEY, ishchi rejimda faqat KEY.
- Click: `sign_string` MD5 `hash_equals` bilan; `service_id` tekshiriladi.
- Summa tiyin aniqligida tekshiriladi (Payme -31001, Click -2).
- Logga kalit, Authorization sarlavhasi yoki imzo yozilmaydi — faqat metod, buyurtma № va natija kodi.
- IP allowlist (`PAYME_IP_CHECK`, `CLICK_IP_CHECK`) — server proksi orqasida bo'lsa avval `TrustProxies` ni sozlang, aks holda
  `request()->ip()` proksi manzilini qaytaradi.
- Qaytish sahifasi buyurtmaning `uuid` si bilan ochiladi va faqat egasiga ko'rinadi.

---

## 8. Ishga tushirish (go-live) ro'yxati

- [ ] Domen HTTPS bilan ishlaydi (to'g'ri sertifikat), `APP_URL` = haqiqiy domen.
- [ ] `php artisan migrate` (yangi jadvallar: `payment_orders`, `payment_transactions`; `payments.type` kengaytirildi).
- [ ] Payme kassasi: Endpoint URL `https://<domen>/payments/payme`, account maydoni `order_id`, bir martalik hisob.
- [ ] Payme sandboxdagi ikkala stsenariy muvaffaqiyatli o'tdi (Payme texnik bo'limi tasdiqlaydi).
- [ ] Click menejeriga Prepare/Complete URL'lar berildi, test to'lov o'tdi.
- [ ] Serverdagi `.env`: `PAYME_KEY`, `PAYME_MERCHANT_ID`, `PAYME_TEST_MODE=false`; `CLICK_*` to'rt kalit.
- [ ] Fiskalizatsiya: MXIK (IKPU) va o'lchov/qadoq kodini `PAYME_IKPU_CODE` / `PAYME_PACKAGE_CODE` ga yozing
      (Click uchun fiskal ma'lumot Click kabinetida sozlanadi).
- [ ] Ixtiyoriy: `PAYME_IP_CHECK=true` (TrustProxies sozlangandan so'ng).
- [ ] `php artisan config:cache`, so'ng kichik summali haqiqiy to'lov: holat sahifasi «To'lov qabul qilindi», admin
      «To'lovlar» da holat 2, talabaga mahsulot berildi. Payme kabinetidan qaytarib ko'ring → holat −2.
- [ ] Admin: Obuna tariflari va coin paketlari narxlari to'g'ri, faol.
- [ ] Qaytarish siyosati (`PAYMENT_REVERSE_ON_REFUND`) va oferta matnida to'lov/qaytarish shartlari yozilgan.

---

## 9. Shanba imtihoni: alohida to'lov yoki obuna (`exam_session`)

Qaror: `docs/exam/QARORLAR.md` 9-band. O'quvchi pullik shanba imtihoniga uch yo'l bilan kiradi
(bitta joyda: `App\Services\Exam\ExamAccess`):

1. imtihon bepul (`exam_sessions.price = 0`; qabul testi narxidan qat'i nazar **har doim** bepul);
2. faol obunasi bor va tarifida **«Shanba imtihonlari kiradi»** (`subscription_plans.includes_exams`) yoqilgan;
3. shu imtihon uchun to'lagan (`exam_session_purchases.status = paid`).

Ustoz va admin cheklanmaydi. Tekshiruv `ExamAttemptService::start()` da (xato sababi `payment_required`, o'zbekcha
matn), urinishga qanday kirgani yoziladi (`exam_attempts.access_via`: `free | subscription | purchase | placement | staff`);
Take sahifasi va avtosaqlash ham qayta tekshiradi.

| Nima | Qayerda |
|---|---|
| Narx | Ustoz → Imtihonlar → yaratish/tahrirlash → «Narx» (so'm; 0 = bepul; pullik bo'lsa kamida 1 000). Kimdir to'lagach narx qulflanadi |
| Tarif sozlamasi | Admin → Obuna tariflari → «Shanba imtihonlari kiradi» (seed: standart, premium, vip va yillik turlari — ha; trial — yo'q) |
| Sotib olish | `POST /payments/checkout` `product_type=exam_session`, `product_id=<sessiya uuid>`; promokod qo'llanmaydi |
| Natija | `exam_session_purchases` (o'quvchi + sessiya bo'yicha bitta qator, `payment_order_id`, `amount`, `provider`, holat) |
| Qaytarish ro'yxati | Admin → To'lovlar → «Imtihon qaytarishlari» (`/admin/exam/refunds`) |

**Qachon sotiladi** (`ExamAccess::buyProblem`, Payme CheckPerform/Create va Click Prepare'da ham tekshiriladi):
sessiya `scheduled` yoki `open` va tugamagan, pullik, o'quvchining fani (yoki shaxsiy sessiya o'ziniki),
o'quvchi hali boshlamagan, to'lamagan va imtihonlar kiradigan obunasi yo'q. Aks holda — 422 / Payme -31052 /
Click -9 va sabab matni. Ikki marta to'lash: bitta to'lov o'tgach ikkinchi buyurtma ochilmaydi; agar Payme va Click
ikkalasi bir vaqtda o'tib ketsa, ikkinchisi `fulfilment.duplicate = true` bilan belgilanadi va qaytarish ro'yxatiga tushadi.

**Qaytish (return) URL.** Saytda: `/student/exam?paid=<buyurtma uuid>` — imtihon sahifasi to'lov holatini ko'rsatadi
va callback kelguncha 3 soniyada yangilanadi. Telegram Mini App'da to'lov sahifasi `Telegram.WebApp.openLink` bilan
tashqi brauzerda ochiladi (so'rov JSON: `{status, url, order}`), buyurtmaga `meta.return_to = telegram` yoziladi va
qaytish URL — `https://t.me/<bot>?startapp=exam_s_<sessiya>` (bot nomi bo'lmasa — sayt URL'i). Mini App qayta
ko'ringanda (`activated` / `visibilitychange`) sahifa holatni yangilaydi. Bu imtihon uchun `PAYME_RETURN_URL` /
`CLICK_RETURN_URL` shablonlari ishlatilmaydi.

**Bekor qilish va qaytarish.** Provayder refund API'si avtomatik chaqirilmaydi:
- ustoz sessiyani bekor qilsa — to'langan kirishlar `refund_pending` bo'ladi, o'quvchiga bildirishnoma;
- sessiya bekor qilingandan keyin kelgan to'lov ham darhol `refund_pending`;
- admin pulni (Payme/Click kabineti yoki naqd) qaytaradi va «Qaytarildi» ni bosadi → kirish `refunded`,
  buyurtma va `payments` qatori `refunded`;
- Payme kabinetidan o'tkazilgan tranzaksiya bekor qilinsa (CancelTransaction, holat 2 → −2): o'quvchi hali
  boshlamagan bo'lsa (yoki urinishi bekor qilingan bo'lsa) kirish `refunded`, boshlagan bo'lsa **-31007**.

**Eslatmalar (Telegram).** Pullik imtihonda kirishi yo'q o'quvchi eslatmani «💳 To'lash» tugmasi va narx qatori bilan
oladi, kirishi borlar — odatdagi «📝 Imtihonga kirish». Jadval o'zgarmagan (kechqurun, 1 soat oldin, boshlanishda).
Matnlar: `lang/uz/exam_payments.php`.

**Testlar:** `php artisan test tests/Feature/Exam/PaidExamsTest.php` — kirish matritsasi, narx validatsiyasi,
server summasi, Payme va Click callback'lari (aynan bir marta), ikki marta to'lash, boshlagandan keyin to'lash,
bekor qilish → qaytarish, eslatma tugmalari, sahifa prop'lari.
