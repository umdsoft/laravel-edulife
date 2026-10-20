# Boshqa kompyuterda ishga tushirish

Talablar: PHP 8.2+ (`pdo_mysql`, `mbstring`, `gd`, `zip`, `intl` yoqilgan), Composer 2, Node.js 20+, MySQL 8 / MariaDB 10.4+ (XAMPP bo'lsa ham bo'ladi).

## 1. Kodni olish

```bash
git clone <repo-url> edulife
cd edulife
composer install --prefer-dist
npm ci
```

## 2. `.env`

```bash
cp .env.example .env
php artisan key:generate
```

`.env` da kamida shularni to'g'rilang:

```env
APP_NAME=EDULIFE
APP_URL=http://127.0.0.1:8000
APP_TIMEZONE=Asia/Tashkent

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=edulife_shanba
DB_USERNAME=root
DB_PASSWORD=
```

Payme / Click kalitlari bo'sh qolsa, to'lov tugmalari o'chiq ko'rinadi, qolgan hamma narsa ishlaydi. Kalitlarni qo'yish tartibi: [payments/PAYMENTS.md](payments/PAYMENTS.md).

`ANTHROPIC_API_KEY` (AI qoralamalar) va `TELEGRAM_*` (bot, Mini App) bo'sh qolsa, bu ikki xizmat o'chiq turadi. Sozlash: [exam/INTEGRATIONS.md](exam/INTEGRATIONS.md).

## 3. Baza

MySQL'da bo'sh baza yarating (`utf8mb4_unicode_ci`):

```sql
CREATE DATABASE edulife_shanba CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

So'ng jadvallar va boshlang'ich ma'lumotlar:

```bash
php artisan migrate --seed
php artisan storage:link
```

## 4. Ishga tushirish

```bash
npm run build
php artisan serve --host=127.0.0.1 --port=8000
```

Brauzerda: http://127.0.0.1:8000

Frontend ustida ishlayotganda `npm run build` o'rniga `npm run dev` ni alohida oynada ishlating.

Navbatdagi ishlar (video qayta ishlash, bildirishnomalar) uchun alohida oynada:

```bash
php artisan queue:work
php artisan schedule:work
```

`schedule:work` shanba imtihonlarini vaqtida ochib-yopadi, yakshanba hisobotini, Telegram eslatmalarini va haftalik AI qoralamalarni ishga tushiradi. Serverda uning o'rniga cron: `* * * * * php artisan schedule:run`.

Video darslarni qayta ishlash uchun `ffmpeg` o'rnatilgan bo'lishi kerak.

## 5. Kirish uchun hisoblar

| Rol | Login | Parol |
|---|---|---|
| Admin | admin@edulife.uz | admin123 |
| Ustoz | teacher@test.uz | password123 |
| O'quvchi | student@test.uz | password123 |
| O'quvchi (Pro) | pro@test.uz | password123 |
| Ota-ona | guardian@test.uz | password123 |

Login daqiqasiga 10 urinish bilan cheklangan.

## 6. Testlar

```bash
php artisan test      # backend (SQLite xotirada, asosiy bazaga tegmaydi)
npx vitest run        # laboratoriya formulalari va frontend
```

## Hujjatlar

- Shanba imtihoni: [exam/00_ANALYSIS.md](exam/00_ANALYSIS.md), [exam/02_DATA_MODEL.md](exam/02_DATA_MODEL.md)
- Battle ballari: [battle/SCORING.md](battle/SCORING.md)
- To'lovlar: [payments/PAYMENTS.md](payments/PAYMENTS.md)
- Laboratoriya formulalari manbalari: [lab/PHYSICS_REFERENCES.md](lab/PHYSICS_REFERENCES.md)
- QA va ustoz paneli: [qa/QA_REPORT.md](qa/QA_REPORT.md), [qa/TEACHER_REVIEW.md](qa/TEACHER_REVIEW.md)
- Dizayn: [design/DESIGN_SYSTEM.md](design/DESIGN_SYSTEM.md)
