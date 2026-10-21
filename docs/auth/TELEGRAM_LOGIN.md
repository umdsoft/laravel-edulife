# Telegram orqali kirish (sayt + bot = bitta akkaunt)

Talab (egasi): "login qismi telegram botga ulangan bo'lsin — telefon raqamini yozishi bilan parol botga borsin;
botda ham saytda ham bir xil ma'lumot va testlar bo'lsin". Qo'shimcha: "botdagi inson doim auth holatida bo'ladi,
bot Mini App ko'rinishida bo'lishi kerak".

## 1. Asosiy qoidalar

- **Bitta akkaunt.** Saytdagi `users` yozuvi va Telegram'dagi `telegram_accounts` yozuvi `telegram_accounts.user_id`
  orqali bog'lanadi. Sayt ham, Mini App ham shu `users.id` bilan ishlaydi — imtihonlar, battle, tangalar, natijalar bir xil.
- **Telefon — login.** `users.phone` unikal, har doim `+998XXXXXXXXX` ko'rinishida saqlanadi. Saytga yozilgan har qanday
  ko'rinish (`90 123 45 67`, `998901234567`, `+998 (90) 123-45-67`) shu ko'rinishga keltiriladi.
- **Mini App ichida login ekrani yo'q.** Mini App har ochilganda `initData` (Telegram imzosi) bilan avtomatik kiradi;
  sessiya uzilsa — jim qayta kirish (`/telegram/app/reauth`). Ro'yxatdan o'tmagan foydalanuvchi faqat ro'yxat ekranini ko'radi.
- **Xodimlar (ustoz, admin) Telegram orqali kirmaydi** — ular email/telefon + parol bilan kiradi; xodim telefoniga kod
  yuborilmaydi, Telegram akkaunti xodimga telefon orqali avtomatik bog'lanmaydi.
- **Email va parol** saytda ikkinchi yorliq ("Email va parol") sifatida qoladi — admin, ustoz va Telegram'i yo'qlar uchun.

## 2. Saytda kirish: telefon + kod (bot ulangan foydalanuvchi)

1. Login sahifasi, "Telefon raqam" yorlig'i (birinchi): `+998` raqam → `POST /login/phone`.
2. Server: raqam o'quvchi / ota-ona akkauntiga tegishli bo'lsa va akkauntga Telegram ulangan bo'lsa, bot shu chatga
   6 xonali kod yuboradi: `<code>`-da kod, "📋 Kodni nusxalash" (copy_text), "✅ Ha, bu men" va "🚫 Bu men emas" tugmalari,
   "Kodni hech kimga bermang" ogohlantirishi.
3. **Javob har doim bir xil** (raqam bormi-yo'qmi bilinmaydi): "Agar raqam botga ulangan bo'lsa, kod Telegram'ga yuborildi".
   Keyingi qadam aniq: kod maydoni + "Kod kelmadimi? Telegram orqali kiring" tugmasi (3-bo'lim) + "botni bloklagan bo'lsangiz,
   /start bosib qayta ishga tushiring".
4. Foydalanuvchi kodni kiritadi (`POST /login/phone/verify`) **yoki** Telegram'da "Ha, bu men" ni bosadi — kutayotgan
   brauzer har 2.5 soniyada holatni so'raydi (`POST /login/telegram/status`) va kiradi. Kirgach rolga qarab yo'naltiriladi.

Kod: tasodifiy 6 raqam (`random_int`), bazada faqat HMAC-SHA256 (APP_KEY + so'rov id), 5 daqiqa, 5 urinish, bir marta
ishlatiladi; yangi kod eskisini bekor qiladi. "Ha, bu men" faqat shu so'rovni boshlagan brauzer sessiyasini kiritadi
(so'rov id sessiyada saqlanadi, bot tugmasidagi id — boshqa brauzerda hech narsa bermaydi) va faqat o'sha Telegram
foydalanuvchisi bosganda ishlaydi.

## 3. Telefon hali botga ulanmagan: "Telegram orqali kirish"

1. Sayt `POST /login/telegram` → bir martalik token (32 belgi, bazada sha256, **10 daqiqa**, sessiyaga bog'langan),
   havola `t.me/<bot>?start=login_<token>` + QR (kompyuter uchun). Sahifa holatni so'rab turadi.
2. Bot `/start login_<token>`: bitta qisqa xabar + **web_app tugma "✅ Saytga kirishni tasdiqlash"** — Mini App ochiladi
   (`/telegram/app?to=login_<token>` → `/telegram/app/login`).
3. Mini App (initData bilan, login ekranisiz):
   - Telegram akkaunt allaqachon o'quvchi/ota-onaga ulangan → shu akkaunt bilan brauzer tasdiqlanadi;
   - ulanmagan → `Telegram.WebApp.requestContact()` (server imzoni tekshiradi, `contact.user_id` = initData foydalanuvchisi):
     - raqam mavjud o'quvchi/ota-onaniki va u boshqa Telegram'ga ulanmagan → Telegram akkaunt shu akkauntga ulanadi, brauzer tasdiqlanadi;
     - raqam xodimniki → rad ("email va parol bilan kiring"); boshqa Telegram'ga ulangan → rad;
     - raqam yangi → Mini App'dagi oddiy ro'yxat ekrani (`TelegramRegistration` qoidalari: kim, sinf, fan, maqsad, rozilik);
       ro'yxat tugagach brauzer tasdiqlanadi.
   - Mini App "Saytda kirish tasdiqlandi ✅" ni ko'rsatadi; brauzer o'zi kiradi.
4. Zaxira (Mini App'siz eski Telegram): xabardagi "Ilova ochilmayaptimi?" tugmasi chatda `request_contact` klaviaturasini
   beradi; kontakt (faqat o'ziniki) xuddi shu qoidalar bilan ulanadi; yangi raqam uchun bo'sh o'quvchi (yoki ro'yxat sahifasida
   tanlangan bo'lsa ota-ona) akkaunti `TelegramRegistration::bare()` orqali ochiladi, profil saytdagi onboarding'da to'ldiriladi.

## 4. Ro'yxatdan o'tish sahifasi

Telefon-birinchi, parolsiz: "O'quvchiman / Ota-onaman" → "Telegram orqali ro'yxatdan o'tish" (3-bo'limdagi oqim) — asosiy yo'l;
kod maydoni ham bor (raqam botga ulangan bo'lsa). Eski parol bilan ro'yxat "Parol bilan" yorlig'ida qoladi (Telegram'i yo'qlar uchun).

## 5. Xavfsizlik

- Web endpointlar `web` guruhida (CSRF), `guest` middleware; kirishda `session()->regenerate()`, "Meni eslab qol" avvalgidek.
- Rate limit (nomli): kod yuborish — raqamga 3 / 10 daqiqa va 10 / sutka, IP ga 30 / daqiqa; kodni tekshirish — sessiyaga
  10 / daqiqa, IP ga 60; havola — sessiyaga 10, IP ga 30; holat so'rovi — sessiyaga 60 / daqiqa. Bloklanganda o'zbekcha xabar
  ("Juda ko'p urinish. N soniyadan keyin qayta urinib ko'ring.").
- Loglarda telefon, kod, token yo'q (faqat id). Kirishlar `audit_logs` ga yoziladi ("Tizimga kirdi: … (Telegram kod)").
- Bloklangan (`status = blocked`) akkaunt hech bir yo'l bilan kirmaydi.
- Mini App ichida foydalanuvchi doim kirgan holatda: har ochilishda initData bilan kiradi, sessiya uzilsa jim qayta
  kirish (`/telegram/app/reauth`) ishlaydi. "Remember" cookie qo'yilmaydi — Telegram Web iframe'ida u SameSite=Lax bo'lib
  qolardi (sessiya cookie'lari esa SameSite=None, `ApplyWebviewCookies`).

## 6. Ma'lumotlar

`telegram_login_requests`: id, kind (`code` | `link`), user_id, telegram_account_id, role, code_hash, token_hash, attempts,
status (`pending` → `approved` → `used`; yoki `cancelled` / `refused` / `expired`), reason, remember, expires_at, approved_at, used_at.

## 7. Lokal sinov (Telegram'siz)

`APP_ENV=local` va `TELEGRAM_BOT_TOKEN` bo'sh bo'lsa, bot yuborishi kerak bo'lgan xabarlar **lokal qutiga** yoziladi
(`DevOutbox`, cache). `/telegram/dev` sahifasida "Bot chat" paneli shu xabarlarni ko'rsatadi; tugmalar ishlaydi:
"Ha, bu men", "Kontaktni ulashish", web_app tugmasi esa Mini App'ni emulyator ichida ochadi.

1. Kod bilan: `127.0.0.1:8000/login` → bot ulangan test o'quvchi raqami → `/telegram/dev` → "Bot chat" dagi kodni kiriting
   (yoki "✅ Ha, bu men").
2. Yangi raqam: login sahifasida "Telegram orqali kirish" → "Emulyatorda ochish (local)" → emulyatorda
   "★ Yangi Telegram foydalanuvchi" → "Botga yuborish" → kelgan xabardagi "✅ Saytga kirishni tasdiqlash" → Mini App'da
   "Raqamni ulashish" → ro'yxat → brauzer tab avtomatik kiradi.

Avtomatik testlar: `tests/Feature/Auth/TelegramLoginTest.php`.
