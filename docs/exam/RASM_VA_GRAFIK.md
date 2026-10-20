# Masalalarda rasm va grafik — ustoz uchun qo'llanma

Rasm va grafikni savol shartiga, har bir variantga, yechimga va dars matniga qo'yish mumkin. Bu imtihon bankida, kurs testlarida, battle'da, olimpiadada va importda bir xil ishlaydi. O'quvchi telefonda rasmni bossa, u kattalashadi. Telegram xabarlarida rasm o'rniga «[rasm]», grafik o'rniga «[grafik]» yoziladi.

## 1. Rasm qo'yish

1. Matn maydoni ustidagi **«Rasm»** tugmasini bosing va faylni tanlang. Variant maydonlarida bu tugma kichik rasm belgisi ko'rinishida turadi.
2. Yoki rasmni **nusxalab, maydonga Ctrl+V** bilan joylang (masalan, ekran surati).
3. Yoki faylni maydonga **sudrab tashlang**.

Yuklash davomida foiz ko'rsatiladi. Tugagach, matnga shunday qator qo'shiladi:

```
![chizma](/storage/questions/2026/10/9b2f3c4e-….webp)
```

- Kvadrat qavs ichidagi so'z rasm izohi. Uni o'zgartiring, masalan `![ABC uchburchak](...)`. Izohni ekran o'quvchi dasturlar o'qiydi, rasm ochilmay qolsa ham shu so'z ko'rinadi.
- O'lchamni belgilash uchun oxiriga `{width=60%}` (sahifa kengligiga nisbatan) yoki `{width=300px}` yozing.
- Ruxsat etilgan formatlar: PNG, JPG, WebP, GIF. Fayl hajmi 5 MB gacha bo'lishi kerak. **SVG qabul qilinmaydi.**
- Server rasmni 1600 pikselgacha kichraytirib, WebP formatiga o'giradi.
- **Faqat platformaga yuklangan rasm ishlaydi.** Boshqa saytdan olingan havola (`https://...`) saqlanmaydi, chunki forma xato beradi. Bunday rasmni avval kompyuterga saqlab, keyin «Rasm» tugmasi bilan yuklang.
- Bitta maydonda 6 tagacha rasm bo'lishi mumkin, dars matnida 40 tagacha.
- Yuklangan, lekin hech qaysi matnda ishlatilmagan rasm 7 kundan keyin avtomatik o'chiriladi.

Murakkab chizmalarni (elektr zanjiri, optik sxema, fazoviy jism) GeoGebra yoki boshqa dasturda chizing va **rasm** sifatida yuklang.

## 2. Grafik qo'yish

1. **«Grafik»** tugmasini bosing. Konstruktor oynasi ochiladi.
2. O'qlar oralig'ini (x min/max, y min/max) va o'q nomlarini kiriting. Fizikada o'q nomi kattalik va birlikdan iborat bo'ladi, masalan `t, s` va `v, m/s`.
3. Kerakli qismlarni qo'shing:
   - **Funksiya** — `x^2 - 2`, `2x + 1`, `sin(x)`, `1/x`. Faqat ma'lum oraliqda chizish uchun «x: dan … gacha» maydonlarini to'ldiring.
   - **Siniq chiziq** — v–t, x–t grafiklari va bo'lakli grafiklar uchun. Nuqtalar jadvaliga koordinatalarni kiriting.
   - **Nuqta** — `(2; 2)` va nomi `A`. «Bo'sh» belgisi qo'yilsa, nuqta ichi bo'sh doiracha bo'lib chiziladi (oraliqqa kirmaydigan uchlar uchun).
   - **Kesma** va **vektor** — boshlanish va tugash nuqtalari bilan. Vektor uchida strelka bo'ladi.
4. O'ng tomonda (telefonda tepada) grafik darhol chizilib boradi. Xato bo'lsa, uning sababi qizil rangda yoziladi.
5. **«Grafikni qo'yish»** tugmasini bosing. Matnga `graph` bloki qo'shiladi.

Mavjud grafikni o'zgartirish uchun kursorni blok ichiga qo'ying va «Grafik» tugmasini bosing. Konstruktor shu grafik bilan ochiladi.

Ko'pburchak, aylana, yozuv va burchak qo'shish uchun konstruktordagi **«JSON»** bo'limidan foydalaning. Quyidagi namunalarni nusxalab, sonlarini o'zgartirsangiz bo'ladi.

Ifodalarda ishlatish mumkin bo'lgan belgilar: `+ - * / ^`, qavslar, `|x|` (modul), `pi`, `e`, `sqrt`, `abs`, `sin`, `cos`, `tan`, `cot`, `asin`, `acos`, `atan`, `ln`, `log` (o'nlik), `exp`, `floor`, `ceil`, `round`, `sign`, `min(a, b)`, `max(a, b)`. O'zgaruvchi `x` yoki `t` bo'lishi mumkin. Ko'paytirish belgisini tushirib yozsa ham bo'ladi: `2x`, `3(x+1)`.

`1/x` yoki `tan x` kabi uzilishga ega funksiyalar uzilish nuqtasida to'g'ri chiziladi: shoxlar orasiga tik chiziq tortilmaydi.

### Grafik bloki kalitlari

| Kalit | Ma'nosi | Misol |
|---|---|---|
| `x`, `y` | o'qlar oralig'i (majburiy) | `"x":[-5,5]` |
| `grid` | to'r (standart: bor) | `"grid":false` |
| `ticks` | bo'linma qadami: bitta son yoki `[x, y]` | `"ticks":[1,2]` |
| `axes` | o'q nomlari | `"axes":{"x":"t, s","y":"v, m/s"}` |
| `equal` | x va y uchun bir xil masshtab (geometriya uchun) | `"equal":true` |
| `width` | kenglik foizda (20–100) | `"width":60` |
| `functions` | `expr`, `domain`, `dashed`, `label`, `color` | `{"expr":"x^2","domain":[0,2]}` |
| `polylines` | `points`, `dashed`, `label`, `color` | `{"points":[[0,0],[2,4]]}` |
| `points` | `x`, `y`, `label`, `hollow`, `color` | `{"x":1,"y":2,"label":"A"}` |
| `segments`, `vectors` | `from`, `to`, `label`, `color` (kesmada `dashed` ham bor) | `{"from":[0,0],"to":[3,4]}` |
| `polygons` | `points` (kamida 3 ta), `fill`, `dashed`, `label`, `color` | `{"points":[[0,0],[4,0],[0,3]],"fill":true}` |
| `circles` | `center`, `r`, `fill`, `dashed`, `label`, `color` | `{"center":[0,0],"r":2}` |
| `labels` | `x`, `y`, `text`, `color` | `{"x":1,"y":1,"text":"O"}` |
| `angles` | `vertex`, `from`, `to`, `label`, `right`, `color` | `{"vertex":[0,0],"from":[1,0],"to":[0,1],"right":true}` |

- Ranglar: `blue`, `red`, `green`, `orange`, `purple`, `gray`. Tungi rejimda ranglar avtomatik moslashadi.
- Nomlar va yozuvlar oddiy matn bo'lishi kerak va 40 belgidan oshmasligi kerak. Bunda `²`, `₁`, `α`, `°` kabi belgilarni ishlatish mumkin.
- Bitta maydonda 3 tagacha grafik bo'lishi mumkin. Kalit noto'g'ri yozilsa yoki ifodada ruxsat etilmagan belgi bo'lsa, savol saqlanmaydi va xato sababi ko'rsatiladi.

## 3. Namunalar

### Parabola

```graph
{"x":[-3,5],"y":[-5,6],"grid":true,"functions":[{"expr":"x^2-2x-3","label":"y = x² − 2x − 3"}],"points":[{"x":-1,"y":0,"label":"A"},{"x":3,"y":0,"label":"B"},{"x":1,"y":-4,"label":"C"}]}
```

### v–t grafigi (bo'lakli)

```graph
{"x":[0,9],"y":[0,5],"ticks":[1,1],"axes":{"x":"t, s","y":"v, m/s"},"polylines":[{"points":[[0,0],[2,4],[6,4],[8,0]],"label":"v(t)"}],"segments":[{"from":[2,0],"to":[2,4],"dashed":true},{"from":[6,0],"to":[6,4],"dashed":true}]}
```

Bu grafikda bosib o'tilgan yo'l grafik ostidagi yuzaga teng: 4 + 16 + 4 = 24 m.

### Kuch vektorlari

```graph
{"x":[-1,5],"y":[-1,5],"equal":true,"ticks":[1,1],"axes":{"x":"x, N","y":"y, N"},"vectors":[{"from":[0,0],"to":[3,0],"label":"F₁","color":"blue"},{"from":[0,0],"to":[0,4],"label":"F₂","color":"red"},{"from":[0,0],"to":[3,4],"label":"F","color":"green"}],"angles":[{"vertex":[0,0],"from":[3,0],"to":[0,4],"right":true}]}
```

### Burchagi belgilangan uchburchak

```graph
{"x":[-1,6],"y":[-1,5],"equal":true,"grid":false,"polygons":[{"points":[[0,0],[5,0],[0,4]],"fill":true}],"labels":[{"x":-0.4,"y":-0.4,"text":"C"},{"x":5.3,"y":-0.4,"text":"B"},{"x":-0.4,"y":4.3,"text":"A"}],"angles":[{"vertex":[0,0],"from":[5,0],"to":[0,4],"right":true},{"vertex":[5,0],"from":[0,0],"to":[0,4],"label":"α"}]}
```

### Giperbola (uzilish bilan) va asimptota

```graph
{"x":[-4,6],"y":[-3,7],"functions":[{"expr":"1/(x-1)+2","label":"y = 1/(x − 1) + 2"},{"expr":"2","dashed":true,"color":"gray"}],"segments":[{"from":[1,-3],"to":[1,7],"dashed":true,"color":"gray"}]}
```

### Aylana

```graph
{"x":[-4,4],"y":[-4,4],"equal":true,"circles":[{"center":[0,0],"r":3,"label":"R = 3"}],"points":[{"x":0,"y":0,"label":"O"}],"segments":[{"from":[0,0],"to":[3,0],"label":"R"}]}
```

## 4. Variantlarda rasm va grafik

«Qaysi grafik y = x² ga mos?» kabi savollarda har bir variant maydoniga grafik yoki rasm qo'ying. Variant maydonlari tagida ham «Rasm» va «Grafik» tugmalari bor. Agar variantlardan kamida bittasida rasm yoki grafik bo'lsa, o'quvchiga variantlar 2 ustunli kartalar ko'rinishida chiqadi (360 pikseldan tor ekranda 1 ustunda).

## 5. Import

- JSON yoki CSV fayl matnida platformaga yuklangan rasm havolasi (`/storage/questions/...`) yoki `graph` bloki bo'lishi mumkin.
- Rasmli savollarni import qilish uchun **ZIP** formatidan foydalaning. ZIP ichida `questions.json` fayli va `images/` papkasi bo'ladi. Matnda rasmga `![izoh](images/rasm.png)` deb murojaat qilinadi. Import paytida rasmlar yuklanadi va havolalar avtomatik almashtiriladi. Tayyor shablonni «Import» sahifasidan yuklab oling: «ZIP (rasmlar bilan)».
- Topilmagan yoki qabul qilinmagan rasm, shuningdek noto'g'ri grafik o'sha qatorning xatosi sifatida ko'rsatiladi. Bunday qator import qilinmaydi.

## 6. AI qoralamalar

AI grafikni `graph` bloki ko'rinishida yozishi mumkin, rasm yarata olmaydi. Grafigi noto'g'ri bo'lgan qoralama rad etiladi va sababi ishga tushirishlar jurnaliga yoziladi.
