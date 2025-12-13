# A1 Speakout Starter Course Content

## 📚 Manba Ma'lumotlari

- **Kitob:** Speakout Starter (3rd Edition)
- **Mualliflar:** Frances Eales, Steve Oakes
- **Nashriyot:** Pearson Education Limited
- **CEFR Darajasi:** A1
- **Maqsadli auditoriya:** Ingliz tilini o'rganayotgan o'zbeklar (boshlang'ich daraja)

---

## 📊 Kurs Strukturasi

| Ko'rsatkich | Qiymat |
|-------------|--------|
| **Jami Bo'limlar (Units)** | 8 ta |
| **Jami Darslar (Lessons)** | 40 ta |
| **So'z boyligi (Vocabulary)** | ~350 ta |
| **Grammatika qoidalari** | 32 ta |
| **Testlar** | 8 ta (7 unit test + 1 final test) |
| **Baholangan vaqt** | 60 soat |

---

## 📋 Bo'limlar Ro'yxati

### Unit 1: Xush kelibsiz! (Welcome)
- **Darslar:** 5 ta
- **Mavzular:** Salomlashish, Mamlakatlar, Kasblar, Shaxsiy ma'lumotlar
- **Grammatika:** BE verb (I/you/he/she/it), Articles (a/an), Have/Has

### Unit 2: Odamlar (People)
- **Darslar:** 5 ta
- **Mavzular:** Raqamlar (11-100), Oila, Suhbatlar, Savol so'zlari
- **Grammatika:** BE verb (we/they), Possessive adjectives, Wh-questions

### Unit 3: Narsalar (Things)
- **Darslar:** 5 ta
- **Mavzular:** Sevimlilar, Stol buyumlari, Kiyimlar, Ranglar
- **Grammatika:** Possessive 's, Have questions, How much?, Like/Don't like

### Unit 4: Har kun (Everyday)
- **Darslar:** 5 ta
- **Mavzular:** Ovqat/Ichimlik, Kundalik tartib, Kafe, Vaqt
- **Grammatika:** Frequency adverbs, Present Simple 3rd person, Polite requests

### Unit 5: Harakat (Action)
- **Darslar:** 5 ta
- **Mavzular:** Fe'llar, Qobiliyatlar, Iltimos, Oylar va sanalar
- **Grammatika:** Object pronouns, Can/Can't, Could you?, Ordinal numbers

### Unit 6: Qayerda? (Where)
- **Darslar:** 5 ta
- **Mavzular:** Xonalar/Mebel, Mahalla, Yo'l so'rash, Shahar joylari
- **Grammatika:** Prepositions of place, There is/are, The article

### Unit 7: Sog'lom hayot (Health)
- **Darslar:** 5 ta
- **Mavzular:** Kundalik harakatlar, O'tmish qahramonlari, Sog'liq, Sport
- **Grammatika:** Wh-questions review, Was/Were, What's wrong?, Imperatives

### Unit 8: Dam olish (Time Out)
- **Darslar:** 5 ta
- **Mavzular:** Hafta oxiri, Tashqariga chiqish, Transport, Rejalar
- **Grammatika:** Past Simple (regular/irregular), Transport phrases, Want/Would like

---

## 🔧 Import Instrukciyasi

### Talablar

1. Laravel 12+ ishga tushirilgan
2. MySQL database konfiguratsiyasi
3. English Learning System modellari mavjud
4. `EnglishLevel` jadvalida A1 daraja mavjud

### Qadamlar

#### 1. JSON fayllarni tekshirish

```bash
ls -la storage/data/a1-speakout/

# Ko'rinishi:
# - metadata.json
# - unit-1-welcome.json
# - unit-2-people.json
# - ...
# - unit-8-time-out.json
```

#### 2. Seeder fayllarni tekshirish

```bash
ls -la database/seeders/English/Speakout/

# Ko'rinishi:
# - A1SpeakoutSeeder.php
# - A1SpeakoutUnitSeeder.php
# - A1SpeakoutLessonSeeder.php
```

#### 3. Seeder ishga tushirish

```bash
php artisan db:seed --class=Database\\Seeders\\English\\Speakout\\A1SpeakoutSeeder
```

**Kutilgan chiqish:**
```
🚀 Starting A1 Speakout Course Seeder...
✓ Found A1 Level: Pre-Intermediate
⚠️  Deleting old A1 content...
   ✓ Deleted: 10 units, 50 lessons
   ✓ Detached: 500 vocabulary, 40 grammar relations
📚 Loading: Speakout Starter 3rd Edition (3rd Edition)
📖 Processing Unit 1...
      ✓ Unit created: Xush kelibsiz!
         • Lesson 1: Salom va Xayrlashish
           ✓ 10 vocabulary items
           ✓ Grammar: be-verb-i-you
         • Lesson 2: Mamlakatlar va Millatlar
           ✓ 12 vocabulary items
           ✓ Grammar: be-verb-he-she-it
   ✓ Unit 1 completed
...
✅ A1 Speakout course seeded successfully!
```

#### 4. Natijani tekshirish

```bash
php artisan tinker
```

```php
// Units sonini tekshirish
>>> \App\Models\English\EnglishLevel::where('code', 'A1')->first()->units->count()
=> 8

// Birinchi unitning darslarini tekshirish
>>> \App\Models\English\EnglishUnit::where('unit_number', 1)->first()->lessons->count()
=> 5

// Vocabulary umumiy soni
>>> \App\Models\English\EnglishVocabulary::where('english_level_id', function($q) {
    $q->select('id')->from('english_levels')->where('code', 'A1');
})->count()
=> ~350

// Grammar rules soni
>>> \App\Models\English\EnglishGrammarRule::where('english_level_id', function($q) {
    $q->select('id')->from('english_levels')->where('code', 'A1');
})->count()
=> ~32
```

---

## 🎯 Content Yo'riqnomasi

### Tillar
- **Asosiy til:** O'zbek tili
- **O'rganilayotgan til:** Ingliz tili
- **Qo'shimcha:** Rus tili tarjimalari (ba'zi joylarda)

### Kontekst
- Barcha misollar O'zbekiston kontekstida
- Shaharlar: Toshkent, Samarqand, Buxoro
- O'zbek ismlari: Ali, Aziza, Jasur, Nilufar
- Mahalliy kontekst: so'm, plov, lag'mon

### Audio
- TTS (Text-to-Speech) formatida
- UK va US variantlari
- Format: `tts:word:variant` (masalan: `tts:hello:uk`)
- Frontend TTS service generate qiladi

### Testlar
- **Lesson Quiz:** Har bir oddiy darsda 10 savol
- **Unit Test:** Har bir bo'lim oxirida 20 savol
- **Final Test:** 8-bo'lim oxirida 30 savol
- **O'tish bali:** 80%

### Reward System
| Dars turi | XP | Coins |
|-----------|-----|-------|
| vocabulary | 2 | 1 |
| grammar | 3 | 1 |
| practice | 2 | 1 |
| conversation | 3 | 1 |
| test | 5 | 2 |

---

## 📁 Fayl Strukturasi

```
storage/data/a1-speakout/
├── README.md                    (Siz bu faylni o'qiyapsiz)
├── CHANGELOG.md                 (O'zgarishlar tarixi)
├── metadata.json                (Kurs metadata)
├── unit-1-welcome.json          (1-bo'lim: 5 dars)
├── unit-2-people.json           (2-bo'lim: 5 dars)
├── unit-3-things.json           (3-bo'lim: 5 dars)
├── unit-4-everyday.json         (4-bo'lim: 5 dars)
├── unit-5-action.json           (5-bo'lim: 5 dars)
├── unit-6-where.json            (6-bo'lim: 5 dars)
├── unit-7-health.json           (7-bo'lim: 5 dars)
└── unit-8-time-out.json         (8-bo'lim: 5 dars + final test)

database/seeders/English/Speakout/
├── A1SpeakoutSeeder.php         (Asosiy orchestrator)
├── A1SpeakoutUnitSeeder.php     (Unit yaratuvchi)
└── A1SpeakoutLessonSeeder.php   (Lesson yaratuvchi)
```

---

## 🔍 Muammolarni Hal Qilish

### Muammo: "A1 level not found"
**Yechim:**
```bash
php artisan db:seed --class=Database\\Seeders\\English\\LevelSeeder
```

### Muammo: "Unit file not found"
**Yechim:**
JSON fayllar to'g'ri joyda ekanligini tekshiring:
```bash
ls storage/data/a1-speakout/*.json
```

### Muammo: "Invalid JSON"
**Yechim:**
JSON faylni validate qiling:
```bash
php -r "json_decode(file_get_contents('storage/data/a1-speakout/unit-1-welcome.json')); echo json_last_error_msg();"
```

### Muammo: Seeder qayta ishlatilganda duplicate content
**Yechim:**
Seeder avtomatik eski contentni o'chiradi. Agar muammo bo'lsa, qo'lda o'chiring:
```php
php artisan tinker
>>> $a1 = \App\Models\English\EnglishLevel::where('code', 'A1')->first();
>>> $a1->units()->delete();
```

---

## 📞 Yordam

Muammo yuzaga kelsa:
1. Loglarni tekshiring: `storage/logs/laravel.log`
2. Database ulanishini tekshiring
3. Migration ishlagan-ishlamaganini tekshiring
4. GitHub Issues: [github.com/yourproject/issues](https://github.com)

---

## 📜 Litsenziya

Bu content **faqat ta'lim maqsadlarida** ishlatilishi mumkin.

**Manba:** Speakout Starter 3rd Edition © Pearson Education Limited

**Eslatma:** Bu content Speakout Starter kitobidan moslashtirilgan. Asl kitobni sotib olishni tavsiya qilamiz.

---

✅ **Tayyor!** Savollar bo'lsa, murojaat qiling.
