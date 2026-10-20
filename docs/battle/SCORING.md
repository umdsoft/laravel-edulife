# Battle: savol tanlash va ochko hisoblash

Bu hujjat "Bilimlar jangi" (1 ga 1 battle) savollari qanday tanlanishi va ochkolar
qanday hisoblanishini tushuntiradi. Ochkolar xalqaro olimpiadalardagi kabi
hisoblanadi: ko'pchilik yechadigan savol kam ochko beradi, kam odam yechadigan savol
ko'p ochko beradi.

Kod: `app/Services/BattleQuestionSelector.php`, `app/Services/BattleScoringService.php`,
`app/Services/BattleScoringSettings.php`, `app/Http/Controllers/Student/BattleController.php`.

---

## 1. Savollar qayerdan olinadi

Faqat o'qituvchilar yaratgan kurs testlarining savollari ishlatiladi:

- kurs holati `published` yoki `approved`;
- test faol (`tests.is_active = 1`);
- savol jangning yo'nalishiga (Matematika / Fizika) tegishli; "Istalgan mavzu"da hamma yo'nalishlar;
- savol turi avtomatik tekshiriladigan va bir bosishda javob beriladigan: `single_choice`
  (bitta to'g'ri javob) va `true_false` (Rost / Yolg'on); kamida bitta to'g'ri varianti bor.

`multiple_choice`, `fill_blank`, `matching`, `ordering` jangda ishlatilmaydi: ularni bir necha
soniyada ikki o'yinchi uchun bir xil va adolatli tekshirib bo'lmaydi.

## 2. Bitta jang = bitta qiyinlik darajasi

Jangdagi **barcha savollar bir xil darajada** (Oson / O'rta / Qiyin).

### Savolning darajasi qanday aniqlanadi

Har bir savol uchun yechilish ulushi p̂ hisoblanadi (3-bo'lim). Daraja p̂ bo'yicha:

| p̂ (to'g'ri yechganlar ulushi) | Daraja |
|---|---|
| p̂ ≥ 0,70 | Oson |
| 0,40 ≤ p̂ < 0,70 | O'rta |
| p̂ < 0,40 | Qiyin |

Yangi savolda statistika yo'q, shuning uchun p̂ o'qituvchi belgilagan qiyinlikning
boshlang'ich qiymatiga teng (easy → 0,80, medium → 0,60, hard → 0,35) va savol o'sha darajada
bo'ladi. Savol ko'p o'ynalgani sari daraja haqiqiy natijalarga qarab o'zgaradi: masalan, "O'rta"
deb belgilangan, lekin deyarli hech kim yecha olmaydigan savol "Qiyin"ga o'tadi.

### Jangning darajasi qanday tanlanadi

Lobbida o'quvchi "Qiyinlik"ni tanlaydi:

- **Avtomatik (standart)**: ikki o'yinchining o'rtacha ELO reytingi bo'yicha:
  ELO < 1200 → Oson, 1200–1499 → O'rta, ≥ 1500 → Qiyin. Agar bu darajada savol yetarli
  bo'lmasa, eng yaqin darajadan olinadi (Oson → O'rta → Qiyin; O'rta → Oson → Qiyin; Qiyin → O'rta → Oson).
- **Oson / O'rta / Qiyin**: aynan shu daraja. Savol yetarli bo'lmasa, qidiruv boshlanmaydi
  va o'quvchiga xabar ko'rsatiladi:
  `"Qiyin" darajasida jang uchun savollar hali yetarli emas: 2 ta bor, kamida 3 ta kerak. Boshqa qiyinlikni yoki "Avtomatik"ni tanlang.`

Qaysi darajani tanlagan o'yinchi faqat o'sha darajani yoki "Avtomatik"ni tanlagan raqib bilan
uchrashadi. Kutayotgan o'yinchining tanlovi ustun.

Hech bir darajada kamida 3 ta savol bo'lmasa:
`Jang uchun savollar hali yetarli emas (bir xil qiyinlikdagi kamida 3 ta test savoli kerak). Boshqa yo'nalishni tanlang yoki keyinroq urinib ko'ring.`

Lobbida har bir daraja uchun savollar soni ko'rsatiladi; savoli yetarli bo'lmagan daraja tanlab bo'lmaydi.

## 3. Ikki o'yinchiga bir xil savollar

Raqib topilganda, bir marta:

1. tasodifiy `question_seed` (urug') yaratiladi;
2. tanlangan darajadagi savollar id bo'yicha tartiblanadi va shu urug' bilan aralashtiriladi (Mt19937);
3. birinchi N tasi olinadi (N — admin sozlamasi "Bir o'yindagi savollar", standart 5;
   savol kam bo'lsa — bor savollar, lekin kamida 3);
4. tartiblangan id'lar `battles.question_ids`ga, har bir savol `battle_rounds`ga yoziladi.

Javob variantlarining tartibi ham urug'dan olinadi (`sha256(urug' | variant_id)` bo'yicha
tartib), shuning uchun ikkala o'yinchi ham, sahifani qayta yuklagan o'yinchi ham aynan bir xil
savollarni, bir xil tartibda, bir xil variantlar tartibida ko'radi. Rost / Yolg'on savolda
tartib o'zgarmaydi.

### Yaqinda tushgan savollar takrorlanmaydi

Har ikkala o'yinchining oxirgi 20 ta jangida tushgan savollar oxiriga suriladi: avval ular
ko'rmagan savollar (tasodifiy tartibda), yetmasa — eng uzoq vaqt oldin ko'rilganlari.
Savollar juda kam bo'lsa ham jang bo'ladi, faqat takrorlanish ehtimoli oshadi.

## 4. Savolning qiymati (empirik qiyinlik)

### Statistika

Har bir savol uchun `battle_question_stats` jadvalida yuritiladi:

- `attempts`: jangda necha marta berilgan (har raundda 2 ta urinish: ikkala o'yinchi;
  vaqt tugab javob berilmagani ham urinish, yechilmagan deb hisoblanadi);
- `correct`: necha marta to'g'ri yechilgan.

Bundan tashqari kurs testlaridagi javoblar (`test_answers`, tekshirilganlari) ham hisobga
olinadi, lekin har biri **0,5** urinish sifatida (testni qayta topshirish natijani biroz
oshiradi, shuning uchun to'liq vazn berilmaydi).

n = jangdagi urinishlar + 0,5 × testdagi javoblar
c = jangdagi to'g'ri javoblar + 0,5 × testdagi to'g'ri javoblar

### Bayes silliqlash (yangi savollar uchun)

Bir-ikki urinishdan xulosa chiqarib bo'lmaydi (1 ta urinish, 1 ta to'g'ri = 100% emas).
Shuning uchun o'qituvchi belgilagan qiyinlik "m ta virtual urinish" sifatida qo'shiladi:

```
p̂ = (c + m · p₀) / (n + m)
```

- p₀: belgilangan qiyinlikning boshlang'ich qiymati (easy 0,80 · medium 0,60 · hard 0,35);
- m = 10: boshlang'ich qiymat 10 ta urinishga teng.

Yangi savolda p̂ = p₀. Urinishlar ko'paygani sari p̂ haqiqiy ulush c/n ga yaqinlashadi.
p̂ doim [0,01; 0,99] oralig'ida.

### Qiymat formulasi

Standart model (`linear`):

```
V = 100 × (1 + 2 × (1 − p̂))
```

Hamma yechadigan savol ≈ 100, yarmi yechadigani 200, deyarli hech kim yechmaydigani ≈ 300.

Muqobil model (`logit`, IRT / Rasch qiyinligi):

```
b = ln((1 − p̂) / p̂)       — savol qiyinligi logitlarda (p̂ = 0,5 da 0)
V = 100 × e^(0,5 × b)
```

Qiymat jang boshlanganda hisoblanadi va `battle_rounds.question_value`ga yoziladi: ikkala
o'yinchi bir xil qiymat uchun o'ynaydi, jang davomida u o'zgarmaydi.

## 5. Raund ochkosi

```
ochko = V × s^(−γ) × (1 + λ × (1 − t / T))      (to'g'ri javob uchun; noto'g'ri = 0)
```

- s: shu jangda savolni yechganlar soni (1 yoki 2), γ = 1;
  faqat bittangiz yechsangiz — **to'liq qiymat**, ikkalangiz yechsangiz — **yarmi** (Codeforces /
  CTF dagi dinamik ochko kabi: yechuvchilar ko'p bo'lsa, har biriga kamroq);
- t: javob vaqti (server o'lchaydi), T: savol vaqti (standart 20 s);
- λ = 0,2: tezlik bonusi, ko'pi bilan +20%. Tezlik ikkinchi darajali: eng tez "ikkalangiz
  yechdingiz" ham eng sekin "faqat siz yechdingiz"dan kam.

Raund g'olibi: ochkosi ko'p bo'lgan o'yinchi (teng bo'lsa — durang).

## 6. Jang natijasi va ELO

Jang g'olibi — **jami ochkosi** ko'p bo'lgan o'yinchi (teng bo'lsa — durang). ELO shu natija
bo'yicha o'zgaradi (avvalgidek: g'alaba 1, durang 0,5, mag'lubiyat 0; K = admin sozlamasi
"ELO K koeffitsienti", yangi o'yinchilarga ×2, 2400+ ga ×½).

## 7. Misol

Daraja: O'rta, T = 20 s, standart sozlamalar.

| Savol | Statistika | p̂ | b (logit) | Qiymat V |
|---|---|---|---|---|
| S1 | n = 80, c = 52 | (52 + 6) / 90 = 0,644 | −0,59 | 100 × (1 + 2 × 0,356) = **171** |
| S2 | yangi, medium | 0,60 | −0,41 | 100 × (1 + 2 × 0,40) = **180** |
| S3 | n = 30, c = 12 | (12 + 6) / 40 = 0,45 | +0,20 | 100 × (1 + 2 × 0,55) = **210** |

Ali va Vali (ikkalasi ham ELO 1000):

| Raund | Ali | Vali | Yechganlar | Ali ochkosi | Vali ochkosi |
|---|---|---|---|---|---|
| 1 (S1, 171) | to'g'ri, 4 s | to'g'ri, 10 s | 2 | 171 × ½ × (1 + 0,2 × 0,8) = **99** | 171 × ½ × 1,10 = **94** |
| 2 (S2, 180) | noto'g'ri | to'g'ri, 12 s | 1 | 0 | 180 × 1 × 1,08 = **194** |
| 3 (S3, 210) | to'g'ri, 15 s | noto'g'ri | 1 | 210 × 1 × 1,05 = **221** | 0 |
| **Jami** | | | | **320** | **288** |

Ikkalasi ham 2 tadan savol yechdi, lekin Ali faqat o'zi yechgan savol qiyinroq (kamroq
yechiladigan) edi, shuning uchun Ali g'olib. ELO: K = 40 (yangi o'yinchi), kutilgan natija 0,5 →
Ali +20, Vali −20.

Jangdan keyin statistika yangilanadi: S1 → n = 82, c = 54 (V = 170); S2 → n = 2, c = 1
(p̂ = 7/12 = 0,583, V = 183); S3 → n = 32, c = 13 (V = 210).

Natija sahifasida har bir raund uchun savol qiymati, umumiy yechilish foizi (p̂), shu jangda
kim yechgani va ikkala o'yinchining ochkosi ko'rsatiladi; "Ochkolar qanday hisoblanadi?"
bo'limida formulalar.

## 8. Sozlamalar

Har bir son `settings` jadvalidagi shu kalitli qator (group `battle`) bilan o'zgartiriladi;
qator bo'lmasa yoki noto'g'ri bo'lsa, standart qiymat ishlatiladi.

| Kalit | Standart | Ma'nosi |
|---|---|---|
| `battle_scoring_base` | 100 | qiymat asosi |
| `battle_scoring_model` | `linear` | `linear` yoki `logit` |
| `battle_scoring_k` | 2 | linear: k |
| `battle_scoring_logit_beta` | 0,5 | logit: β |
| `battle_scoring_prior_strength` | 10 | m: boshlang'ich qiymat necha urinishga teng |
| `battle_scoring_prior_easy` / `_medium` / `_hard` | 0,80 / 0,60 / 0,35 | p₀ |
| `battle_scoring_test_weight` | 0,5 | kurs testi javobining vazni (0 = hisobga olinmaydi) |
| `battle_scoring_solver_exponent` | 1 | γ: yechuvchilar orasida bo'lish (0 = bo'linmaydi) |
| `battle_scoring_speed_bonus` | 0,2 | λ: tezlik bonusi |
| `battle_level_easy_min_p` | 0,70 | shundan yuqori p̂ — Oson |
| `battle_level_hard_max_p` | 0,40 | shundan past p̂ — Qiyin |
| `battle_elo_medium_from` | 1200 | "Avtomatik": shu ELO dan O'rta |
| `battle_elo_hard_from` | 1500 | "Avtomatik": shu ELO dan Qiyin |
| `battle_recent_battles` | 20 | oxirgi nechta jangdagi savollar takrorlanmaydi |

Mavjud sozlamalar ("Bir o'yindagi savollar", "Har bir savolga vaqt", "ELO K koeffitsienti",
kunlik limitlar) avvalgidek ishlaydi.
