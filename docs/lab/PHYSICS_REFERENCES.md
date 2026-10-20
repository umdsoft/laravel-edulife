# Virtual laboratoriya: fizik modellar, konstantalar va manbalar

Bu hujjat virtual fizika laboratoriyasining 59 ta tajribasi uchun ma'lumotnoma: har bir tajribada qaysi model va formulalar ishlatilgani, konstantalar va jadval qiymatlari qayerdan olingani (manba havolalari bilan), o'lchov asboblarining aniqligi va o'lchov noaniqligi qanday modellashtirilgani, standart sozlamalarda qanday natija kutilishi va qanday soddalashtirishlar qilingani.

Kod manbalari:

- fizika: `resources/js/Components/Lab/physics/*.js` (har bir modulga `__tests__/*.test.js` testlari bor);
- konstantalar: `resources/js/Components/Lab/physics/constants.js` (CODATA 2022);
- o'lchov asboblari: `resources/js/Components/Lab/physics/instruments.js`;
- simulyatsiyalar: `resources/js/Components/Lab/Simulations/*.vue` (uchidan-uchiga testlar: `Simulations/__tests__/*.e2e.test.js`);
- vazifalar va kutilgan javoblar: `database/seeders/data/lab/experiments/*.json`, formulalar katalogi: `database/seeders/data/lab/formulas.json`.

## 1. Fundamental konstantalar (CODATA 2022)

Manba: NIST, CODATA 2022 tavsiya etilgan qiymatlar — <https://physics.nist.gov/cuu/Constants/Table/allascii.txt>. 2019-yilgi SI qayta ta'rifidan beri c, h, e, k, N_A aniq (xatoliksiz) qiymatlarga ega; ulardan hosil bo'lgan R, σ, F ham aniq.

| Kattalik | Belgi | Qiymat | Izoh |
|---|---|---|---|
| Yorug'likning vakuumdagi tezligi | c | 299 792 458 m/s | aniq |
| Plank doimiysi | h | 6,626 070 15·10⁻³⁴ J·s | aniq |
| Elementar zaryad | e | 1,602 176 634·10⁻¹⁹ C | aniq |
| Bolsman doimiysi | k | 1,380 649·10⁻²³ J/K | aniq |
| Avogadro doimiysi | N_A | 6,022 140 76·10²³ mol⁻¹ | aniq |
| Universal gaz doimiysi | R = N_A·k | 8,314 462 618… J/(mol·K) | aniq |
| Stefan–Bolsman doimiysi | σ | 5,670 374 419…·10⁻⁸ W/(m²·K⁴) | aniq |
| Elektr doimiysi | ε₀ | 8,854 187 8188·10⁻¹² F/m | u_r = 1,6·10⁻¹⁰ |
| Magnit doimiysi | μ₀ | 1,256 637 061 27·10⁻⁶ N/A² | 4π·10⁻⁷ dan 5,5·10⁻¹⁰ ga farq qiladi |
| Kulon doimiysi | k = 1/(4πε₀) | 8,987 551 786·10⁹ N·m²/C² | |
| Elektron massasi | mₑ | 9,109 383 7139·10⁻³¹ kg | |
| Proton/elektron massalari nisbati | mₚ/mₑ | 1836,152 673 426 | |
| Bor radiusi | a₀ | 5,291 772 105 44·10⁻¹¹ m | |
| Nozik struktura doimiysi | α | 7,297 352 5643·10⁻³ | |
| Ridberg doimiysi | R∞ | 10 973 731,568 157 m⁻¹ | |
| Vodorod uchun Ridberg doimiysi | R_H = R∞/(1 + mₑ/mₚ) | 1,096 775 8·10⁷ m⁻¹ | keltirilgan massa tuzatmasi |
| Ridberg energiyasi | R∞hc | 13,605 693 eV | vodorod uchun 13,598 4 eV |
| 1 u ning energiya ekvivalenti | | 931,494 103 72 MeV | |
| Normal atmosfera | | 101 325 Pa | aniq |
| Erkin tushish tezlanishining standart qiymati | gₙ | 9,806 65 m/s² | aniq (shartli qiymat) |

## 2. Erkin tushish tezlanishi: 9,81 m/s² va Toshkent

Laboratoriya vazifalarida **g = 9,81 m/s²** ishlatiladi. Bu O'zbekiston maktab darsliklari va masalalar to'plamlaridagi qiymat. Toshkentdagi mahalliy qiymat WGS 84 normal og'irlik formulasi (Somigliana) va balandlik bo'yicha "erkin havo" tuzatmasidan hisoblanadi:

g(φ) = 9,780 325 3359 · (1 + 0,001 931 852 652 41·sin²φ) / √(1 − 0,006 694 379 990 13·sin²φ),
g(φ, h) = g(φ) − (3,0877·10⁻⁶ − 4,3·10⁻⁹·sin²φ)·h + 7,2·10⁻¹³·h²

(manba: WGS 84, NIMA TR8350.2; <https://en.wikipedia.org/wiki/Theoretical_gravity>). Toshkent uchun φ = 41,30° sh.k., h ≈ 455 m: g(φ) = 9,8029 m/s², balandlik tuzatmasi bilan **g ≈ 9,8015 m/s²** (Bouguer tuzatmasi bilan ≈ 9,8020 m/s²). Bu 9,81 dan atigi 0,09 % ga kam: bu farq har qanday vazifa tolerantligidan (≥ 1 %) va maktab asboblarining aniqligidan ancha kichik. Shuning uchun Toshkentda mayatnik bilan o'lchangan g ≈ 9,80 m/s² ham to'g'ri javob sifatida qabul qilinadi. Kodda: `constants.js` → `gravityAt()`, `G_TASHKENT`, `LAB_GRAVITY`.

## 3. O'lchov asboblari va o'lchov noaniqligi

Haqiqiy laboratoriyada hech bir asbob "aniq" qiymatni ko'rsatmaydi: har bir ko'rsatkich asbob shkalasining bo'linmasigacha yaxlitlanadi va kichik tasodifiy xatolikka ega bo'ladi. Simulyatsiyalar buni `instruments.js` modulida bir joyda modellashtiradi:

- **Shkala bo'linmasi (aniqlik).** Har bir ko'rsatkich asbob bo'linmasiga yaxlitlanadi (`quantize`): masalan, elektron sekundomer 0,001 s, qo'l sekundomeri 0,01 s, chizg'ich 1 mm, transportir 0,5°, raqamli termometr 0,1 °C, 3½ raqamli multimetr (2000 hisob, avtomatik diapazon).
- **Tasodifiy xatolik.** Bevosita o'lchanadigan kattaliklarga (vaqt, uzunlik, burchak, harorat, asbob ko'rsatkichi) kichik tasodifiy xatolik qo'shiladi: normal taqsimot, ±2,5σ da kesilgan (`truncatedNormal`). Shuning uchun xatolik hech qachon |Δ| ≤ 2,5σ + bo'linma/2 chegarasidan chiqmaydi.
- **Takrorlanuvchanlik.** Tasodifiy sonlar generatori (mulberry32) simulyatsiya nomi va sozlamalardan hosil qilingan urug' (seed) bilan ishga tushiriladi: bir xil sozlamadagi k-o'lchov har doim bir xil son beradi (natijalar varag'i va testlar takrorlanadi), takroriy o'lchovlar esa haqiqiy laboratoriyadagidek biroz farq qiladi.
- **Tekshiriluvchanlik qoidasi.** Har bir o'lchov vazifasi uchun eng katta xatolik (2,5σ + bo'linma/2) vazifa tolerantligining yarmidan oshmaydi. Bu testlar bilan isbotlangan, ya'ni to'g'ri bajarilgan o'lchov doim qabul qilinadi.
- **Formulalar ko'rinishida xatolik yo'q.** Hisoblangan qiymatlar, nazariy ko'rsatkichlar va kalkulyator natijalari xatoliksiz; ular faqat ma'noli raqamlargacha yaxlitlanadi.

Asboblar katalogi (`INSTRUMENTS`):

| Asbob | Bo'linma | σ (bitta o'lchov) | Ishlab chiqaruvchi aniqligi |
|---|---|---|---|
| Elektron taymer / fotodarvoza | 0,001 s | 0,5 ms | ±1 ms |
| Qo'l sekundomeri | 0,01 s | 0,03 s | ±0,01 s + reaksiya ≈ 0,1 s |
| Metrli chizg'ich | 1 mm | 0,3 mm | ±0,5 mm |
| Ruletka | 1 cm | 3 mm | ±0,5 cm |
| Optik skamya shkalasi | 1 mm | 0,3 mm | ±0,5 mm |
| Ekran mikrometri | 0,01 mm | 0,003 mm | ±0,01 mm |
| Indikator soat (kengayish) | 0,01 mm | 0,002 mm | ±0,01 mm |
| Transportir / goniometr diski | 0,5° | 0,1° | ±0,5° |
| Verniyerli goniometr (spektrometr) | 0,01° | 0,003° | ±1′ |
| Raqamli termometr | 0,1 °C | 0,03 °C | ±0,2 °C |
| Raqamli multimetr (3½ raqam) | diapazon/2000 | 0,05 % | ±(0,5 % + 2 raqam) |
| Analog o'lchov asbobi | — | 0,2 % | 1,5-aniqlik sinfi |
| Laboratoriya tarozisi | 0,1 g | 0,03 g | ±0,1 g |
| Dinamometr | 0,05 N | 0,01 N | ±0,05 N |
| Teslametr (Xoll zondi) | 0,01 mT | 0,1 % | ±1 % |
| Chastota o'lchagich | 0,1 Hz | — | ±0,1 Hz |
| Geyger–Myuller hisoblagichi | 1 imp | Puasson √N (fizik) | — |

Tajribalarda ishlatilgan aniq asbob, uning σ si va vazifa tolerantligi har bir tajriba bo'limida ko'rsatilgan.

## 4. Testlar

- `physics/__tests__/*.test.js` — har bir formulaning darslikdagi qiymatlar bilan mustaqil tekshiruvi, chegaraviy holatlar;
- `physics/__tests__/calculator.test.js` — har bir formula identifikatori uchun mustaqil qo'lda hisoblangan namuna va har bir vazifaning kutilgan javobi fizika funksiyalari bilan qayta hisoblanishi;
- `physics/__tests__/constants.test.js` — CODATA 2022 qiymatlari va Toshkentdagi g;
- `physics/__tests__/instruments.test.js` — yaxlitlash, urug'li generator, kesilgan normal taqsimot chegaralari;
- `Simulations/__tests__/allSimulations.test.js` — 59 ta simulyatsiyaning hammasi o'z konfiguratsiyasi bilan ishga tushadi, xatosiz chiziladi va 3 s ishlaydi;
- `Simulations/__tests__/*.e2e.test.js` — haqiqiy Vue komponenti brauzersiz ishga tushiriladi (`mountSimulation.js`): slayderlar o'rnatiladi, tajriba bajariladi, "O'lchash" bosiladi va chiqarilgan o'lchov mustaqil hisob bilan hamda vazifaning kutilgan javobi bilan (nom, birlik, tolerantlik — server qoidalari bo'yicha) solishtiriladi.

## 5. Mexanika

Bo'limning umumiy qoidalari (10 ta tajriba uchun):

- Fizika: `resources/js/Components/Lab/physics/mechanics.js` (SI birliklari, sof funksiyalar), testlar: `physics/__tests__/mechanics.test.js` (80 ta) va `Simulations/__tests__/mechanics.e2e.test.js` (40 ta: 27 ta o'lchov vazifasi × 30 marta takroriy o'lchov + slayder chegaralari bo'yicha tekshiruv).
- Erkin tushish tezlanishi: barcha vazifalarda **g = 9,81 m/s²** (`constants.LAB_GRAVITY`, o'zbek maktab darsliklari qiymati). Toshkentdagi normal og'irlik tezlanishi `gravityAt(41,30°, 455 m)` = **9,8015 m/s²** (WGS 84 Somigliana formulasi), farqi **0,087 %** — barcha vazifa tolerantliklaridan (≥ 1 %) va maktab asboblari aniqligidan ancha kichik. Standart qiymat gₙ = 9,806 65 m/s² (aniq, konvensiya).
- O'lchov asboblari (`MECH_INSTRUMENTS`, `instruments.js` katalogidan meros, unga o'zgartirish kiritilmagan):

| Asbob | Ajratish qobiliyati | Tasodifiy σ | Eng katta xato (2,5σ + ½ bo'lim) | Manba |
|---|---|---|---|---|
| Elektron taymer + fotodarvoza | 0,1 ms | 0,2 ms | 0,55 ms | PASCO ME-8930 Smart Timer (0,1 ms): https://www.manualsdir.com/manuals/340748/pasco-me-8930-smart-timer.html ; Vernier fotodarvoza vaqt o'lchash aniqligi: https://www.vernier.com/til/1467 |
| Metrli chizg'ich / sterjen shkalasi | 1 mm | 0,3 mm | 1,25 mm | `INSTRUMENTS.metreRule` |
| Po'lat ruletka (kopirovka izi markazi) | 1 mm | 1 mm | 3,0 mm | — |
| Kuch datchigi (±50 N diapazon) | 0,05 N | 0,01 N | 0,05 N | Vernier Dual-Range Force Sensor DFS-BTA: https://www.vernier.com/products/dfs-bta/ , https://www.vernier.com/manuals/dfs-bta |

- Har bir o'lchov `createInstrument(spec, nom)` orqali: qiymat asbob bo'limiga yaxlitlanadi, kichik kesilgan-normal tarqoqlik (±2,5σ) qo'shiladi, urug' simulyatsiya nomi + sozlamalardan olinadi — bir xil sozlamadagi k-o'lchov doim bir xil son (takrorlanuvchan), `Math.random` ishlatilmaydi.
- Qat'iy qoida: har bir seed vazifasi uchun eng katta o'lchov xatosi ≤ ½ tolerantlik (e2e testda isbotlangan, jadvallar quyida).
- Hisoblangan qiymatlar (nazariy readout, kalkulyator, energiyalar) shovqinsiz.
- Testlash jarayonida topilgan harness kamchiligi: Vue `vModelText` `Document`/`ShadowRoot` klasslarini tekshiradi, `mountSimulation.js` esa faqat `document` ni yaratadi — slayder qiymatini `sim.set()` bilan o'zgartirish "Document is not defined" xatosini beradi. e2e testda `globalThis.Document ??= class {}` bilan aylanib o'tildi.

### 1. Oddiy mayatnik (`simple-pendulum`, simulyatsiya: `PendulumSimulation`)

**Model va formulalar.** To'liq nochiziqli tenglama θ'' = −(g/L)·sin θ (− b·θ', standart b = 0) RK4 bilan (qadam ≤ 2 ms) yechiladi. Aniq davr T = 4√(L/g)·K(sin(θ₀/2)), K(k) = π/(2·AGM(1, √(1−k²))); kichik burchakda T₀ = 2π√(L/g); qator T = T₀(1 + θ₀²/16 + 11θ₀⁴/3072 + …). g = 4π²L/T². Testda davr mustaqil ravishda Simpson integrali bilan hisoblanadi.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| g (laboratoriya) | 9,81 m/s² | `constants.LAB_GRAVITY`; gₙ = 9,806 65 m/s²: https://physics.nist.gov/cgi-bin/cuu/Value?gn |
| g (Toshkent) | 9,8015 m/s² | WGS 84 normal og'irlik: https://en.wikipedia.org/wiki/Theoretical_gravity |
| Aniq davr, AGM formulasi, qator | — | https://en.wikipedia.org/wiki/Pendulum_(mechanics) |

**Asboblar, aniqlik va noaniqlik.** Eng past nuqtadagi fotodarvoza yuqoriga o'tishda taymerni ishga tushiradi va **N = 10** to'liq tebranishdan keyin to'xtatadi (real laboratoriya usuli). O'lchov: T = t₁₀/10, 5 kasr xona bilan (t₁₀ 0,1 ms aniqlikda, shuning uchun bo'lish aniq). Eng katta xato: 0,55 ms/10 + 0,005 ms = **0,06 ms** (T = 2,0147 s da 0,003 %); vazifa tolerantligi 1 % (yarmi 10 ms). Sozlama o'zgarsa eski o'lchov o'chiriladi.

**Standart sozlamalarda kutilgan natijalar.** L = 1,0 m, m = 0,5 kg, θ₀ = 15°: T = **2,0147 s** (T₀ = 2,0061 s, +0,43 %); L = 0,5 m: T = **1,4246 s**; g-vazifasi (L = 1, T₀ = 2,0061 s): g = **9,8097 m/s²** (avval 9,81 deb yozilgan edi — 5 raqamgacha aniqlashtirildi). Bitta namunaviy o'lchov: t₁₀ = 20,1467 s → T = 2,01467 s.

**Soddalashtirishlar.** Nuqtaviy yuk, vaznsiz ip, havo so'nishi yo'q (amplituda kamaymaydi). Real mayatnikda sharcha radiusi r fizik mayatnik tuzatmasini beradi (T ≈ T₀·(1 + r²/(5L²)·…)), havo amplitudani asta kamaytiradi; qo'l sekundomeri bilan (0,01 s, reaksiya ≈ 0,1 s) 10 tebranishda xato ≈ 0,01 s/tebranish bo'lardi.

### 2. Erkin tushish (`free-fall`, simulyatsiya: `FreeFallSimulation`)

**Model va formulalar.** s = gt²/2, v = gt, t = √(2h/g), v = √(2gh), g = 2h/t² (yopiq yechim, Euler drift yo'q).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| g | 9,81 m/s² | yuqoridagi |
| Po'lat zichligi | 7850 kg/m³ | https://www.engineeringtoolbox.com/metal-alloys-densities-d_50.html |
| Havo zichligi (20 °C, 1 atm) | 1,204 kg/m³ | https://en.wikipedia.org/wiki/Density_of_air |
| Shar qarshilik koeffitsienti C_d | 0,47 | https://en.wikipedia.org/wiki/Drag_coefficient |

**Asboblar, aniqlik va noaniqlik.** Elektromagnit qo'yib yuborganda taymer ishga tushadi, pastdagi fotodarvoza to'xtatadi: 0,1 ms, σ = 0,2 ms, eng katta xato 0,55 ms (+ ko'rsatish 0,05 ms) → h = 1 m da **0,13 %**, h = 2 m da 0,09 % (yarim tolerantlik 0,5 %). Ekranda g = 2h/t² o'qilgan vaqtdan hisoblanadi (masalan t = 0,4512 s → g = 9,824 m/s²).

**Realizm o'zgarishi.** Balandlik 1–10 m o'rniga **0,2–2,0 m** (laboratoriya erkin tushish asbobi). Sabab: Ø25 mm po'lat sharcha (m = 64,2 g, v_t ≈ 67,4 m/s) uchun kvadratik havo qarshiligi tushish vaqtini h = 10 m da +0,36 %, h = 5 m da +0,18 % ga, h = 2 m da esa atigi +0,07 % ga oshiradi (`fallTimeQuadraticDrag`, testlangan) — havosiz model faqat laboratoriya balandliklarida to'g'ri. Vazifalar: h = 5 m → 1,0 m, h = 10 m → 2,0 m ("2 marta" mantiqi saqlangan).

**Standart sozlamalarda kutilgan natijalar.** h = 1,0 m: t = **0,45152 s**, v = **4,4294 m/s**; h = 2,0 m: t = **0,63855 s**; g-vazifasi (h = 1,0 m, t = 0,4518 s): g = **9,7980 m/s²**.

**Soddalashtirishlar.** Havo qarshiligi hisobga olinmaydi (≤ 0,07 %), elektromagnitning qo'yib yuborish kechikishi (real asbobda bir necha ms) modellanmagan.

### 3. Nyuton ikkinchi qonuni (`newtons-second-law`, simulyatsiya: `NewtonSecondSimulation`)

**Model va formulalar.** a = (F − f)/m, agar F > f; F ≤ f da brusok tinch turadi (a = 0, tinchlikdagi ishqalanish). Harakat tinch holatdan: x = at²/2. O'lchov: a = 2s/t², s = 1,000 m.

**Konstantalar va jadval qiymatlari.** Konstantalar yo'q (F va f to'g'ridan-to'g'ri beriladi).

**Asboblar, aniqlik va noaniqlik.** 1,2 m li trek; qo'yib yuborishda taymer ishga tushadi, s = 1,00 m dagi fotodarvoza to'xtatadi. a ning eng katta xatosi (t ± 0,55 ms dan) + yaxlitlash 0,0005: a = 5 → 0,184 %, a = 2,5 → 0,143 %, a = 3 → 0,152 % (yarim tolerantlik 0,5 %). Avval simulyatsiya "o'lchov" sifatida nazariy a ni chiqarar edi — endi real asbob kabi vaqtdan hisoblanadi; o'lchov faqat brusok darvozadan o'tgach (yoki qo'zg'almagan holda 0) mumkin.

**Realizm o'zgarishi.** Trek 15,4 m (50 px/m) o'rniga 1,2 m; slayderlar: m 0,5–5 kg (avval 10), F 1–20 N (avval 50), f 0–10 N (avval 20).

**Standart sozlamalarda kutilgan natijalar.** F = 10 N, m = 2 kg: a = **5,0000 m/s²** (t = 0,6325 s); m = 4 kg: **2,5000**; f = 4 N: **3,0000**; F = 20 N (hisob): **10,000 m/s²**.

**Soddalashtirishlar.** Tinchlikdagi va sirpanish ishqalanishi teng; ip/blok, dinamometr inersiyasi yo'q.

### 4. Qiya tekislik (`inclined-plane`, simulyatsiya: `InclinedPlaneSimulation`)

**Model va formulalar.** N = mg·cos θ, F∥ = mg·sin θ, sirpanadi agar tg θ > μ: a = g(sin θ − μ cos θ), aks holda a = 0 va f = F∥. Massaga bog'liq emas (testda m = 5 kg bilan tekshirilgan).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| μ (slayder 0–0,5; standart 0,1) | yog'och–yog'och μₛ = 0,5, μₖ = 0,3; po'lat–po'lat (quruq) 0,6 / 0,3; mumlangan yog'och–ho'l qor 0,14 / 0,1; teflon–po'lat 0,04 (OpenStax 6.1-jadval) | https://openstax.org/books/university-physics-volume-1/pages/6-2-friction ; https://www.engineeringtoolbox.com/friction-coefficients-d_778.html |

μ = 0,1 — juda silliq (mumlangan / teflon qoplamali) sirt; oddiy yog'och brusok uchun real μₖ ≈ 0,3 (u holda 30° da a = 2,36 m/s²).

**Asboblar, aniqlik va noaniqlik.** 1,2 m li taxta (avval 8 m — 50 px/m), qiyalik bo'ylab s = 1,00 m dagi fotodarvoza: a = 2s/t². Eng katta xato: a = 4,0554 → 0,169 %, 4,905 → 0,183 %, 6,2430 → 0,203 % (yarim tolerantlik 0,5 %). 0,1 ms taymer zarur: 1 ms li taymer bilan θ = 45° da xato 0,62 % bo'lardi (> 0,5 %).

**Standart sozlamalarda kutilgan natijalar.** θ = 30°, μ = 0,1: a = **4,0554 m/s²**; μ = 0: **4,9050**; θ = 45°, μ = 0,1: **6,2430**; N (m = 1 kg, 30°) = **8,4957 N**. θ = 10°, μ = 0,2: tg 10° = 0,176 < 0,2 → brusok qo'zg'almaydi (a = 0).

**Soddalashtirishlar.** μₛ = μₖ; real tajribada μₛ > μₖ bo'lgani uchun brusok tg θ ≈ μₖ dan biroz kattaroq burchakda ham turishi mumkin; aylanish/ag'darilish yo'q.

### 5. Prujina tebranishlari (`spring-oscillations`, simulyatsiya: `SpringOscillationSimulation`)

**Model va formulalar.** x = A cos ωt, ω = √(k/m), T = 2π√(m/k), E = kA²/2, k = 4π²m/T². Vertikal prujinada og'irlik faqat muvozanatni siljitadi, davrga ta'sir qilmaydi.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Prujina effektiv massasi | m_eff = m + m_s/3 (model: m_s = 0) | https://en.wikipedia.org/wiki/Effective_mass_(spring%E2%80%93mass_system) |

**Asboblar, aniqlik va noaniqlik.** Muvozanatdagi fotodarvoza, N = 10 tebranish, T = t₁₀/10 (5 kasr). Eng katta xato 0,06 ms (T = 0,31416 s da 0,019 %).

**Realizm o'zgarishi.** k slayderi 10–200 o'rniga **30–200 N/m** (2 kg yuk 10 N/m prujinani 2 m cho'zar edi), amplituda 0,05–0,2 o'rniga 0,02–0,15 m.

**Standart sozlamalarda kutilgan natijalar.** k = 50 N/m, m = 0,5 kg: T = **0,62832 s**; m = 2,0 kg: **1,2566 s**; k = 200 N/m: **0,31416 s**; E (k = 50, A = 0,1 m) = **0,25000 J**.

**Soddalashtirishlar.** Prujina vaznsiz va ideal chiziqli; real prujinada m_s/3 qo'shiladi (masalan m_s = 30 g → T ≈ 1 % katta), katta amplitudada (A > mg/k) prujina siqilish sohasiga o'tadi — ogohlantirish yo'q.

### 6. Gorizontga burchak ostida otilgan jism (`projectile-motion`, simulyatsiya: `ProjectileSimulation`)

**Model va formulalar.** x = v₀ cos θ·t, y = h₀ + v₀ sin θ·t − gt²/2; t = (v₀ sin θ + √(v₀² sin²θ + 2gh₀))/g, L = v₀ cos θ·t (h₀ = 0 da L = v₀² sin 2θ/g), H = h₀ + (v₀ sin θ)²/(2g).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Stol usti otgich uzoqliklari | 45° da ≈ 1,3 / 1,9 / 3,2 m (v₀ ≈ 3,6–5,6 m/s) | PASCO Mini Launcher ME-6825B: https://cdn.pasco.com/product_document/Mini-Launcher-ME-6825B-Manual.pdf |

**Asboblar, aniqlik va noaniqlik.** Tushish izi kopirovka qog'ozida, uzoqlik ruletka bilan: 1 mm, σ = 1 mm, eng katta xato 3 mm + 0,5 mm (L = 2,207 m da 0,159 %; yarim tolerantlik 0,5 %). O'lchov faqat sharcha tushgach.

**Realizm o'zgarishi.** v₀ = 20 m/s → 40,8 m uzoqlik laboratoriya emas (dala). Endi stol usti prujinali otgich: **v₀ 1–8 m/s, h₀ 0–1,5 m**, standart v₀ = 5 m/s. Chizma masshtabi ≤ 300 px/m, yerda metrli belgilar (piksel emas).

**Standart sozlamalarda kutilgan natijalar.** v₀ = 5 m/s, θ = 45°, h₀ = 0: L = **2,5484 m**, H = **0,63710 m**; θ = 30° va 60°: L = **2,2070 m**; h₀ = 1,0 m, θ = 0°: L = **2,2576 m** (t = 0,45152 s).

**Soddalashtirishlar.** Havo qarshiligi yo'q: sonli integrallash (C_d = 0,47) bo'yicha v₀ = 5 m/s, 45° da Ø25 mm po'lat sharcha uzoqligi ≈ 0,43 % ga, yengil plastik sharchaniki (1,1 g/cm³) ≈ 3 % ga kamayadi — shuning uchun real tajribada po'lat sharcha ishlatiladi. Real otgichda v₀ ±1–2 % tarqaladi (uzoqlikda ±2–4 %) — bu modellanmagan, chunki u o'lchov asbobi emas, apparatning o'zgaruvchanligi.

### 7. Impuls saqlanish qonuni (`momentum-conservation`, simulyatsiya: `MomentumSimulation`)

**Model va formulalar.** v₁' = ((m₁ − e m₂)u₁ + (1+e)m₂u₂)/(m₁+m₂), v₂' = ((m₂ − e m₁)u₂ + (1+e)m₁u₁)/(m₁+m₂) (impuls saqlanishi + Nyutonning tiklanish qonuni v₂' − v₁' = −e(u₂ − u₁)); ΔE = (1−e²)m₁m₂(u₁−u₂)²/(2(m₁+m₂)).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Tiklanish koeffitsienti e | 1 (prujinali/magnitli bamper) yoki 0 (velcro) | https://en.wikipedia.org/wiki/Coefficient_of_restitution |

**Asboblar, aniqlik va noaniqlik.** To'qnashuvdan keyin har bir aravachaning 10,0 cm li bayroqchasi fotodarvozadan o'tadi: Δt = L/|v| taymerda (0,1 ms), v = L/Δt; noelastik holatda birlashgan aravachalar bitta o'lchov. Chiqariladigan o'lchovlar: "1-aravacha tezligi", "2-aravacha tezligi", "Yakuniy impuls" (p = m₁v₁' + m₂v₂'). Eng katta xato: elastik holatda **0,50 %** (v₂' = 0,767 m/s da Δt = 0,130 s, xato 0,42 %), noelastikda 0,16 %. Shu sababli impuls vazifalarining tolerantligi **1 % → 2 %** (yarmi 1 % ≥ 0,50 %); real havo trekida impulsni ±2–3 % aniqlikda tekshirish odatiy. O'lchov endi faqat to'qnashuvdan keyin (avval to'qnashuvdan oldin bosilsa ham "yakuniy impuls" chiqar edi).

**Realizm o'zgarishi.** m 1–5 kg, v ≤ 10 m/s (8 m/s yaqinlashish), 26,7 m trek o'rniga havo yostiqli 2 m trek, **m 0,1–1 kg, |v| ≤ 1 m/s**. Vazifa sonlari masshtablandi: m₁ = 0,4 kg, u₁ = 0,5 m/s, m₂ = 0,2 kg, u₂ = −0,3 m/s.

**Standart sozlamalarda kutilgan natijalar.** p = **0,14000 kg·m/s**; elastik: v₁' = −0,033333, v₂' = **0,76667 m/s**; noelastik: v = 0,23333 m/s; ΔE = **0,042667 J**. Namunaviy o'lchov: v₁' = −0,0333, v₂' = 0,7663 m/s, p' = 0,1399 kg·m/s.

**Soddalashtirishlar.** Trekda ishqalanish va havo oqimining notekisligi yo'q, to'qnashuv bir onda, aravacha massalari aniq ma'lum (tarozi xatosi 0,1 g — 0,05 % dan kam).

### 8. Aylana harakat (`circular-motion`, simulyatsiya: `CircularMotionSimulation`)

**Model va formulalar.** v = ωr, a = ω²r = v²/r, F = mω²r, T = 2π/ω. O'lchov: a = 4π²r/T², T = t₁₀/10.

**Konstantalar va jadval qiymatlari.** Konstantalar yo'q.

**Asboblar, aniqlik va noaniqlik.** Fotodarvoza φ = 0 da; 10 ta aylanish vaqti 0,1 ms taymerda; r — yelkadagi teshik holati (apparat shkalasi, shovqinsiz). Eng katta xato: ≤ 0,02 % (yarim tolerantlik 0,5 %). "Aylanish davri (T)" va "Markazga intilma tezlanish" chiqariladi; avval nazariy a ni chiqarar edi.

**Realizm o'zgarishi.** r 0,5–2,5 m, m 0,5–5 kg o'rniga stol usti asbob: **r 0,1–0,5 m, ω 1–10 rad/s, m 0,05–0,5 kg**; chizma 500 px/m. Vazifalar: (r = 0,4 m, ω = 5) → a = 10,0; ω = 10 → 40,0 (4 marta); r = 0,2 → 5,0 (2 marta kam); F (m = 0,2 kg) = 2,0 N.

**Standart sozlamalarda kutilgan natijalar.** a = **10,000 m/s²**, v = 2,0 m/s, T = 1,2566 s, F = **2,0000 N**.

**Soddalashtirishlar.** ω qat'iy o'zgarmas (motor tezligi tebranishi, yelka ishqalanishi yo'q); r o'qilmaydi — apparatda belgilangan.

### 9. Aylanma moment (`torque-rotation`, simulyatsiya: `TorqueSimulation`)

**Model va formulalar.** M = F·r·sin θ, yelka d = r·|sin θ|; I = ml²/12 + md² (Shteyner); ε = M/I; tinch holatdan φ = εt²/2. 2 m, 1 kg sterjen gorizontal tekislikda.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Ingichka sterjen inersiyasi | ml²/12 | https://en.wikipedia.org/wiki/List_of_moments_of_inertia |
| Shteyner teoremasi | I = I_c + md² | https://en.wikipedia.org/wiki/Parallel_axis_theorem |

**Asboblar, aniqlik va noaniqlik.** Har "O'lchash"da: kuch datchigi F (0,05 N, σ 0,01 N, eng katta xato 0,05 N), sterjen shkalasi r (1 mm, eng katta xato 1,25 mm); θ burchakli tutqich bilan beriladi (o'qilmaydi — 0,5° li transportir xatosi θ = 30° da sin bo'yicha 1,5 % bo'lar edi). M = F·r·sin θ: eng katta xato **0,38 %** (yarim tolerantlik 0,5 %). Chiqariladi: "Kuch (datchik)", "O'qdan masofa (r)", "Moment (…)".

**Standart sozlamalarda kutilgan natijalar.** F = 20 N, r = 1 m: θ = 90° → **20,000 N·m**, θ = 30° → **10,000**; r = 1,6 m → **32,000**; I (d = 0) = **0,33333 kg·m²**; ε = **60,000 rad/s²**.

**Soddalashtirishlar.** Kuch sterjenga nisbatan doimiy burchakda, o'qda ishqalanish yo'q; 2 m × 20 N — katta, ammo real ko'rgazmali qurilma o'lchamida.

### 10. Energiya saqlanish qonuni (`energy-conservation`, simulyatsiya: `EnergyConservationSimulation`)

**Model va formulalar.** Qarshilik kuchi F_q = k·mg (o'zgarmas): a = g(1 − k), s = at²/2, v = at; Eₚ = mg(h − s), Eₖ = mv²/2, Q = k·mg·s; Eₖ + Eₚ + Q = mgh har onda. Yerga tushganda E_mex = mgh(1 − k), Q = k·mgh.

**Konstantalar va jadval qiymatlari.** g = 9,81 m/s²; havo qarshiligi bahosi uchun ρ_havo = 1,204 kg/m³, C_d = 0,47 (yuqoridagi manbalar): 1 kg po'lat shar (Ø 62 mm) 3 m dan tushganda havo ≈ 0,3 % energiyani issiqlikka aylantiradi.

**Asboblar, aniqlik va noaniqlik.** Energiya to'g'ridan-to'g'ri o'lchanmaydi — model holatidan hisoblanadi, shuning uchun shovqin yo'q (faqat 0,001 J yaxlitlash). O'lchov nomi "Jami energiya (Eₖ + Eₚ)" — server prefiksi "Jami energiya" ga mos, lekin bu mexanik energiya ekanini aniq ko'rsatadi. Tekshirildi: k = 0,2 da yerga tushgandan keyingi o'lchov `mechanical_energy_landing` = 23,544 J ga aniq teng; tushish davomida E_mex = mgh − k·mg·s.

**Standart sozlamalarda kutilgan natijalar.** m = 1 kg, h = 3 m, k = 0: **29,430 J** (istalgan onda); m = 2 kg, h = 5 m: **98,100 J**; k = 0,2, yerga tushgach: **23,544 J** (Q = 5,886 J); urilish tezligi (k = 0): **7,6720 m/s**.

**Soddalashtirishlar.** O'zgarmas qarshilik kuchi — o'quv modeli (havo qarshiligi aslida ~v²); real tajribada energiya h va v (fotodarvoza) o'lchovlaridan hisoblanadi; 1–10 m va 5 kg gacha — ko'rgazmali model, laboratoriya asbobi emas.


## 6. Termodinamika

Fizik modellar: `resources/js/Components/Lab/physics/thermodynamics.js` (birlik testlari: `physics/__tests__/thermodynamics.test.js`, simulyatsiyalarning uchdan-uchgacha testlari: `Simulations/__tests__/thermodynamics.e2e.test.js`). Konstantalar `physics/constants.js` dan olinadi (CODATA 2022 / SI 2019, aniq qiymatlar): R = N_A·k = 8,314 462 618 J/(mol·K) (avval yaxlitlangan 8,314 ishlatilgan edi), σ = 5,670 374 419·10⁻⁸ W/(m²·K⁴), 1 atm = 101 325 Pa, 0 °C = 273,15 K. Material jadvallari (`LINEAR_EXPANSION`, `SPECIFIC_HEAT`, `THERMAL_CONDUCTIVITY`, `WATER`) va asboblar (`THERMO_INSTRUMENTS`) bitta joyda — fizika modulida — saqlanadi; simulyatsiyalar ularni shu yerdan oladi.

Umumiy shovqin qoidasi: har bir o'qish asbob bo'limiga yaxlitlanadi va kichik, takrorlanuvchi (seed qilingan) tasodifiy xatoga ega; xato ±2,5σ da kesiladi, shuning uchun eng katta xato = 2,5σ + bo'lim/2. Har bir o'lchash vazifasi uchun eng katta xato ≤ ½ · ruxsat etilgan chetlanish (testda isbotlangan). Hisoblangan (nazariy) ko'rsatkichlarda shovqin yo'q.

| Asbob | Bo'lim | σ (1 o'qish) | Eng katta xato |
|---|---|---|---|
| Indikator soat (dial indicator) | 0,01 mm | 0,002 mm | 0,010 mm |
| Raqamli termometr / termojuft | 0,1 °C | 0,03 °C | 0,125 °C |
| Qo'l sekundomeri | 0,01 s | 0,03 s | 0,08 s |
| Gaz silindri shkalasi (porshen) | 0,1 L | 0,02 L | 0,10 L |
| Raqamli manometr | 0,01 atm | 0,002 atm | 0,010 atm |

### 1. Issiqlik kengayishi (`thermal-expansion`, simulyatsiya: `ThermalExpansionSimulation`)

**Model va formulalar.** ΔL = α·L₀·ΔT, L = L₀(1 + αΔT); L₀ 20 °C da berilgan. Harorat analitik rampa bilan o'zgaradi: T(t) = clamp(20 ± 30 °C/s · t), isitish 100 °C gacha (ΔT = 80 °C), sovitish −20 °C gacha (ΔT = −40 °C). O'lchash tugmasi uzayishni indikator soatdan o'qiydi: nom `ΔL (Po'lat, ΔT=80°C)`, birlik `mm`, qiymat 0,01 mm aniqlikda (masalan `0.96`). Ekrandagi jonli ko'rsatkich ham 0,01 mm ga yaxlitlangan (avval 0,001 mm va L — 1 µm aniqlikda chiqarilardi, bu indikatordan aniqroq edi).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| α, po'lat ("Metall" → "Po'lat" deb nomlandi) | 12·10⁻⁶ 1/K | uglerodli po'lat 10,8–12,5·10⁻⁶, sof temir 12,0·10⁻⁶ — https://www.engineeringtoolbox.com/linear-expansion-coefficients-d_95.html |
| α, alyuminiy | 23·10⁻⁶ 1/K | 23·10⁻⁶ (25 °C) — o'sha manba; CRC: 23,1·10⁻⁶ |
| α, mis | 17·10⁻⁶ 1/K | 16–16,7·10⁻⁶ (25 °C), 20–100 °C oralig'idagi o'rtacha ≈ 17·10⁻⁶ — o'sha manba |
| α, shisha (oyna) | 9·10⁻⁶ 1/K | "Glass, plate" 9,0·10⁻⁶ — o'sha manba |

**Asboblar, aniqlik va noaniqlik.** Indikator soat 0,01 mm, σ = 0,002 mm, eng katta xato 0,010 mm. 1 % chetlanish 0,96 mm da atigi 0,0096 mm — indikatorning bitta bo'limidan ham kichik, ya'ni haqiqiy laboratoriyada bajarib bo'lmaydi. Shuning uchun ruxsat etilgan chetlanish isitish vazifalarida 1 % → 3 %, sovitish vazifasida (−0,48 mm) 1 % → 5 % ga oshirildi (0,010 mm ≤ ½·3 %·0,96 = 0,0144 mm; 0,010 ≤ ½·5 %·0,48 = 0,012 mm). Termometr 0,1 °C.

**Standart sozlamalarda kutilgan natijalar.** Po'lat, L₀ = 1 m, isitish: ΔL = 0,96 mm; alyuminiy 1 m: 1,84 mm; mis 2 m: 2,72 mm; po'lat 1 m sovitish: −0,48 mm; shisha 1 m isitish: 0,72 mm.

**Soddalashtirishlar.** Sterjen butun uzunligi bo'ylab bir xil haroratda deb olinadi; α harorat oralig'ida o'zgarmas (o'rtacha qiymat). Haqiqiy tajribada sterjen bug' yoki suv bilan isitiladi, harorat bir necha minutda o'rnatiladi va indikatorning nol nuqtasini sozlash xatosi qo'shiladi.

### 2. Solishtirma issiqlik sig'imi (`specific-heat`, simulyatsiya: `SpecificHeatSimulation`)

**Model va formulalar.** O'zgarmas quvvatli isitgich, yo'qotishlarsiz: Q = P·t, T(t) = T₀ + P·t/(m·c), T₀ = 20 °C, isitish 100 °C da to'xtaydi (t_max = m·c·80/P). Vaqt tezlashtiriladi, ekrandagi vaqt — fizik vaqt. O'lchash tugmasi: (1) termometrdan haroratni o'qiydi → `Harorat (Suv)` °C; (2) agar namuna ≥ 1 °C isigan bo'lsa, sekundomer ko'rsatkichi t va termometr ko'rsatkichlari (boshlang'ich T₀ — isitish boshlanganda o'qiladi) dan c = P·t/(m·(T − T₀)) hisoblaydi → `Issiqlik sig'imi (Suv)` J/(kg·°C), 4 qiymatli raqam. Avval isitishdan oldin ham "o'lchangan" c sifatida jadval qiymati chiqarilardi — bu olib tashlandi.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| c, suv | 4186 J/(kg·K) | IAPWS-95: 4184 (20 °C), 4182 (25 °C), 4180 (40 °C), 4197 (80 °C), 4216 (100 °C); 20–100 °C o'rtachasi ≈ 4188 — https://www.engineeringtoolbox.com/specific-heat-capacity-water-d_660.html |
| c, temir | 450 J/(kg·K) | 450 — https://www.engineeringtoolbox.com/specific-heat-metals-d_152.html (CRC: 449) |
| c, mis | 385 J/(kg·K) | CRC 385; Engineering ToolBox 390 |
| c, alyuminiy | 897 J/(kg·K) | CRC 897; Engineering ToolBox 910 |
| c, moy | 2000 J/(kg·K) | o'simlik / mashina moyi ≈ 1,9–2,0 kJ/(kg·K) |

**Asboblar, aniqlik va noaniqlik.** Termometr 0,1 °C (σ 0,03 °C), sekundomer 0,01 s (σ 0,03 s), isitgich quvvati — pasport qiymati. c ning eng katta nisbiy xatosi = 2·0,125/ΔT + 0,08 s/t + yaxlitlash: suv (ΔT = 80 °C, t = 669,76 s) 0,34 %, temir (t = 72 s) 0,44 % — ikkalasi ham ½·1 % dan kichik; vazifa chetlanishi 1 % o'zgarmadi.

**Standart sozlamalarda kutilgan natijalar.** Suv, 0,2 kg, 100 W: t = 669,76 s, Q = 66 976 J, c ≈ 4186 J/(kg·°C) (±0,3 %). Temir, 0,2 kg, 100 W: t = 72 s, Q = 7200 J, c ≈ 450 J/(kg·°C).

**Soddalashtirishlar.** Isitgich, idish va atrof-muhitga issiqlik yo'qotilishi hisobga olinmaydi (haqiqiy maktab tajribasida c 5–15 % katta chiqadi); qattiq jismlar ham "idishda" isitiladi. Pufakchalar — faqat bezak (Math.random, o'qishlarga ta'sir qilmaydi).

### 3. Issiqlik uzatish (`heat-transfer`, simulyatsiya: `HeatTransferSimulation`)

**Model va formulalar.** Statsionar Furye qonuni q = k·ΔT/d, d = 0,1 m; nurlanish (nazariy ko'rsatkich) q = εσ(T₁⁴ − T₂⁴), ε = 1, harorat kelvinda. O'lchash tugmasi sterjen uchlaridagi ikki termojuftni o'qiydi va q = k·(T₁ − T₂)/d ni 3 qiymatli raqam bilan chiqaradi: `Issiqlik oqimi (Mis)` = `308000` W/m² (avval `308000.0` — 7 xonali soxta aniqlik). Termojuft ko'rsatkichlari ekranda ko'rsatiladi.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| k, mis | 385 W/(m·K) | texnik mis: ETP (C11000) 390, sof mis 401 — https://www.engineeringtoolbox.com/thermal-conductivity-metals-d_858.html; https://en.wikipedia.org/wiki/List_of_thermal_conductivities (385–401). Maktab sterjeni texnik mis — 385 qoldirildi (ETP dan 1,3 % kam) |
| k, alyuminiy | 205 W/(m·K) | texnik qotishma (6063 ≈ 200–210); sof Al 237 — o'sha manbalar |
| k, temir | 80 W/(m·K) | sof temir 79,5–80,4 (CRC 80,2) |
| k, shisha | 1,05 W/(m·K) | "Glass" 1,05; oyna 0,96 — https://www.engineeringtoolbox.com/thermal-conductivity-d_429.html |
| k, yog'och | 0,15 W/(m·K) | qarag'ay 0,12–0,15, eman 0,17 — o'sha manba |
| σ | 5,670 374 419·10⁻⁸ W/(m²·K⁴) | https://physics.nist.gov/cgi-bin/cuu/Value?sigma (aniq) |

**Asboblar, aniqlik va noaniqlik.** Ikki termojuft 0,1 °C (σ 0,03 °C): ΔT xatosi ≤ 0,25 K; 3 qiymatli raqamga yaxlitlash. Eng katta xato: mis 100/20 °C — 0,31 % + 0,16 % = 0,47 % ≤ ½·1 %; boshqa vazifalarda kichikroq. Chetlanish 1 % o'zgarmadi.

**Standart sozlamalarda kutilgan natijalar.** Mis 100/20 °C: q = 308 000 W/m²; temir: 64 000 W/m²; shisha: 840 W/m²; mis 200/0 °C: 770 000 W/m²; qora jism nurlanishi 100 °C → 20 °C: 680,61 W/m² (ekranda 681).

**Soddalashtirishlar.** Yon sirtlardan issiqlik yo'qotilmaydi, uchlar rezervuar haroratida; sterjen va rezervuar orasidagi kontakt qarshiligi yo'q. Haqiqiy tajribada q sovuq uchdagi suvning isishidan (kalorimetrik) topiladi va 5–10 % noaniqlik bo'ladi.

### 4. Agregat holat o'zgarishi (`phase-changes`, simulyatsiya: `PhaseChangeSimulation`)

**Model va formulalar.** 0,5 kg muz −20 °C dan 150 °C li bug'gacha o'zgarmas quvvat bilan isitiladi; isitish egri chizig'i T(Q) bo'lakli-analitik: muz (Q/(m·c_muz)), erish platosi 0 °C (m·λ), suv (m·c_suv), qaynash platosi 100 °C (m·r), bug' (m·c_bug'). Q = P·t. Yangi "📏" tugmasi termometr (`Harorat`, °C, 0,1 °C) va sekundomer (`Isitish vaqti`, s, 0,01 s) ko'rsatkichlarini yozadi — plato boshlanishi va oxiri vaqtlaridan bosqich davomiyligini topish mumkin (vazifalar ro'yxati o'zgarmadi).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat (darslik) | Ishonchli qiymat va manba |
|---|---|---|
| c_muz | 2100 J/(kg·K) | 2,050 (0 °C), 2,000 (−10 °C), 1,943 (−20 °C) kJ/(kg·K), −20…0 °C o'rtachasi ≈ 2,00 — https://www.engineeringtoolbox.com/ice-thermal-properties-d_576.html. Darslik qiymati (2100) saqlandi: hisoblash vazifasi aynan shu qiymat bilan berilgan; farq ≈ 5 % — hujjatlashtirildi |
| λ (erish) | 334 000 J/kg | 334 kJ/kg — https://www.engineeringtoolbox.com/latent-heat-melting-solids-d_96.html; IAPWS 333,55 kJ/kg (+0,13 %) |
| c_suv | 4186 J/(kg·K) | yuqoridagi 2-tajribaga qarang |
| r (bug'lanish, 100 °C) | 2 260 000 J/kg | 2256,4 kJ/kg (IAPWS-95) — https://www.engineeringtoolbox.com/water-properties-d_1573.html (+0,16 %) |
| c_bug' | 2000 J/(kg·K) | 1 atm, 100–150 °C da c_p ≈ 2,0 kJ/(kg·K) |
| T_qaynash | 100 °C | ITS-90 bo'yicha 1 atm da 99,97 °C |

**Asboblar, aniqlik va noaniqlik.** Termometr 0,1 °C (eng katta xato 0,125 °C), sekundomer 0,01 s (0,08 s). O'lchash vazifasi yo'q (faqat hisoblash vazifalari, kalkulyator — shovqinsiz).

**Standart sozlamalarda kutilgan natijalar.** P = 200 W: muzni isitish 21 kJ (105 s), erish 167 kJ (835 s, 105…940 s), suvni isitish 209,3 kJ (1046,5 s, 940…1986,5 s), qaynash 1130 kJ (5650 s, 1986,5…7636,5 s), bug'ni 150 °C gacha 50 kJ (250 s); jami 1577,3 kJ, 7886,5 s.

**Soddalashtirishlar.** Idish issiqlik sig'imi va yo'qotishlar yo'q; harorat namuna bo'ylab bir xil; bosim 1 atm. Pufakchalar/bug' tasviri — faqat bezak.

### 5. Ideal gaz qonuni (`ideal-gas-law`, simulyatsiya: `IdealGasSimulation`)

**Model va formulalar.** PV = nRT (R aniq qiymatda). P va V slayderlari — Boyl–Mariott; T yoki n o'zgarganda "doimiy" tanlovi bo'yicha V (Gey-Lyussak) yoki P (Sharl) qayta hisoblanadi. O'lchash: haqiqiy qurilmadagidek silindr shkalasidan hajm va manometrdan bosim o'qiladi — `Bosim P (manometr)` atm, `Hajm V (silindr shkalasi)` L. Avval `Hisoblangan V` (formula bo'yicha hisoblangan, 0,001 L) chiqarilardi; vazifalarda `expected.measurement` "Hajm V" ga almashtirildi. Molekulalar tasviri endi seed qilingan PRNG bilan (takrorlanuvchi).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| R | 8,314 462 618 J/(mol·K) | https://physics.nist.gov/cgi-bin/cuu/Value?r (aniq, N_A·k) |
| 1 atm | 101 325 Pa | aniq (ta'rif) |
| V_m (273,15 K, 1 atm) | 22,413 969 54 L/mol | CODATA 2022 — https://physics.nist.gov/cgi-bin/cuu/Value?mvol (kalkulyator testida mustaqil tekshiruv) |

R almashtirilgani uchun kutilgan qiymatlar qayta hisoblandi: 24,616 → 24,617 L; 32,821 → 32,823 L; 12,308 → 12,309 L; 2,4616 → 2,4617 atm.

**Asboblar, aniqlik va noaniqlik.** Silindr shkalasi 0,1 L (σ 0,02 L, eng katta xato 0,10 L), manometr 0,01 atm. 1-, 2-vazifa: 0,10 L ≤ ½·1 %·24,6 L ✓. 3-vazifa (12,309 L): 1 % chetlanish 0,123 L — yarmi (0,062 L) shkala bo'limidan kichik, shuning uchun chetlanish 1 % → 2 % ga oshirildi.

**Standart sozlamalarda kutilgan natijalar.** n = 1 mol, T = 300 K, P = 1 atm: V = 24,617 L (o'qish 24,6 L); 400 K: 32,823 L (32,8); 2 atm: 12,309 L (12,3); V = 10 L, 300 K: P = 2,4617 atm (ekranda 2,46 atm); PV = nRT = 2494,3 J.

**Soddalashtirishlar.** Gaz ideal (real havo 300 K, 1–10 atm da < 0,5 % farq qiladi); harorat o'zgarishi bir zumda o'rnatiladi. 1 mol (≈ 25 L) — darslik masshtabi; maktabdagi shpritsli qurilmalar 20–60 mL bilan ishlaydi.

### 6. Kalorimetriya (`calorimetry`, simulyatsiya: `CalorimetrySimulation`)

**Model va formulalar.** Issiqlik balansi, yo'qotishlarsiz: t = (c·m₁·t₁ + c·m₂·t₂)/(c·m₁ + c·m₂); Q = c·m₁·(t₁ − t). O'lchash: aralashtirishdan oldin termometr ikki suvni o'lchaydi (`Issiq suv harorati`, `Sovuq suv harorati`), aralashtirgandan keyin — `Yakuniy harorat` (°C, 0,1 °C). Avval aralashtirmasdan ham "Yakuniy harorat" chiqarilardi va ekranda javob oldindan ko'rsatilardi — endi yakuniy harorat va Q faqat aralashtirilgandan keyin ko'rinadi (3-vazifa "avval hisoblang, so'ng o'lchang" deydi).

**Konstantalar va jadval qiymatlari.** c_suv = 4186 J/(kg·K) (2-tajribadagi manba); ikkala jism suv bo'lgani uchun c natijaga ta'sir qilmaydi.

**Asboblar, aniqlik va noaniqlik.** Termometr 0,1 °C, σ 0,03 °C, eng katta xato 0,125 °C ≤ ½·1 %·44 °C = 0,22 °C ✓ (52 va 70 °C da ham). Chetlanish 1 % o'zgarmadi.

**Standart sozlamalarda kutilgan natijalar.** 200 g 80 °C + 300 g 20 °C → 44,0 °C, Q = 30 139 J (30,14 kJ); issiq suv 100 °C → 52,0 °C; 500 g 80 °C + 100 g 20 °C → 70,0 °C.

**Soddalashtirishlar.** Kalorimetr idishining issiqlik sig'imi va atrofga yo'qotish hisobga olinmaydi. Haqiqiy alyuminiy kalorimetrda (≈ 50 g, C ≈ 45 J/K) 1-tajriba ≈ 43,5 °C beradi (−1,1 %); bu model vazifalardagi ideal qiymatlar bilan mos kelishi uchun qo'shilmadi — xulosa vazifasida shu farq muhokama qilinadi.

### 7. Karno sikli (`carnot-cycle`, simulyatsiya: `CarnotCycleSimulation`)

**Model va formulalar.** 1 mol bir atomli ideal gaz (γ = 5/3), 1→2 izotermik kengayish T_H da 1 L → 2 L, adiabatalar T·V^(γ−1) = const, η = 1 − T_C/T_H, Q_H = nRT_H·ln(V₂/V₁), Q_C = nRT_C·ln(V₂/V₁), W = Q_H − Q_C. P–V diagrammasi aniq izoterma/adiabatalardan chiziladi. Faqat nazariy ko'rsatkichlar (o'lchov asbobi yo'q, shovqin yo'q).

**Konstantalar va jadval qiymatlari.** R = 8,314 462 618 J/(mol·K) (NIST). Shu sababli Q_H = 3457,7 → 3457,9 J, W = 1728,8 → 1728,9 J ga yangilandi.

**Asboblar, aniqlik va noaniqlik.** — (hisoblash vazifalari, chetlanish 1 %).

**Standart sozlamalarda kutilgan natijalar.** T_H = 600 K, T_C = 300 K: η = 50,0 %, Q_H = 3458 J, Q_C = 1729 J, W = 1729 J; T_H = 800 K, T_C = 200 K: η = 75,0 %.

**Soddalashtirishlar.** Qaytar (cheksiz sekin) jarayonlar; ishqalanish va issiqlik qarshiligi yo'q — real dvigatellarning FIK i Karno chegarasidan ancha kichik.

### 8. Entropiya (`entropy`, simulyatsiya: `EntropySimulation`)

**Model va formulalar.** Izotermik: ΔS = Q/T; izobarik: T₂ = T₁ + Q/(nC_p), ΔS = nC_p·ln(T₂/T₁), C_p = 5R/2; adiabatik qaytar siqilish (V → V/2): ΔS = 0, T₂ = T₁·2^(γ−1). Nazariy ko'rsatkichlar, shovqin yo'q.

**Konstantalar va jadval qiymatlari.** C_p = 5R/2 = 20,786 J/(mol·K) (aniq R bilan; avval 20,785). Vazifa matni va tekshiruv kiritmalari 20,786 ga yangilandi: ΔS = 1,6032 J/K (o'zgarmadi), T₂ = 324,06 → 324,05 K.

**Asboblar, aniqlik va noaniqlik.** — (hisoblash vazifalari, chetlanish 1 %).

**Standart sozlamalarda kutilgan natijalar.** Izotermik, 300 K, 500 J: ΔS = 1,667 J/K; izobarik: ΔS = 1,603 J/K, T₂ = 324,1 K; adiabatik: ΔS = 0, T₂ = 476,2 K.

**Soddalashtirishlar.** Ideal gaz, qaytar jarayonlar; "tartibsizlik" animatsiyasi — sifatli tasvir.


## 7. Elektr

Fizika: `resources/js/Components/Lab/physics/electricity.js` (SI birliklar, sof funksiyalar). Konstantalar `physics/constants.js` dan olinadi (CODATA 2022). Topshiriqlar: `database/seeders/data/lab/experiments/electricity.json`. Testlar: `physics/__tests__/electricity.test.js`, `physics/__tests__/calculator.test.js`, `Simulations/__tests__/electricity.e2e.test.js`.

**Bo'lim bo'yicha umumiy konstantalar**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Elektr doimiysi ε₀ | 8,854 187 8188·10⁻¹² F/m (CODATA 2022; avval 2018 dagi 8,854 187 8128·10⁻¹² ishlatilgan) | https://physics.nist.gov/cgi-bin/cuu/Value?ep0 |
| Kulon doimiysi k = 1/(4πε₀) | 8,987 551 7862·10⁹ N·m²/C² (avval 8,987 551 7923·10⁹) | https://physics.nist.gov/cuu/Constants/Table/allascii.txt |
| Elementar zaryad e | 1,602 176 634·10⁻¹⁹ C (aniq) | https://physics.nist.gov/cgi-bin/cuu/Value?e |
| Boltsman doimiysi k_B | 1,380 649·10⁻²³ J/K (aniq) | https://physics.nist.gov/cgi-bin/cuu/Value?k |
| Issiqlik kuchlanishi U_T = k_BT/e (300 K) | 25,852 mV | k_B va e dan hisoblangan |

**Bo'lim bo'yicha umumiy asbob — raqamli multimetr (DMM).** Avtomatik diapazonli 3½ razryadli multimetr (2000 hisob, UNI-T UT33/UT39, Mastech MAS830, DT-830 sinfi). Har bir diapazon ko'pi bilan 1999 hisob ko'rsatadi:

| Funksiya | Diapazonlar va oxirgi raqam |
|---|---|
| Kuchlanish | 199,9 mV (0,1 mV) · 1,999 V (1 mV) · 19,99 V (10 mV) · 199,9 V (0,1 V) · 1000 V (1 V) |
| Tok | 1,999 mA (1 µA) · 19,99 mA (10 µA) · 199,9 mA (0,1 mA) · 10 A kirish (0,01 A) |
| Qarshilik | 199,9 Ω (0,1 Ω) · 1999 Ω (1 Ω) · 19,99 kΩ · 199,9 kΩ · 1,999 MΩ · 19,99 MΩ |

Masalan 20,00 mA 20 mA li diapazonga sig'maydi (eng ko'pi 19,99), shuning uchun asbob 200 mA diapazonga o'tib "20.0 mA" ko'rsatadi. Bitta o'qishning tasodifiy sochilishi σ = 0,05 % (oxirgi raqamning "titrashi"), ±2,5σ da kesilgan (seedli mulberry32 generator: bir xil sozlamada k-chi o'qish har doim bir xil son). Eng katta xato = 2,5σ·|x| + ½·oxirgi raqam. Ishlab chiqaruvchi aniqligi ±(0,5 % + 2 raqam) DCV, ±(1–2 %) DCA — bu barcha o'qishlar uchun bir xil bo'lgan tizimli xato, faqat hujjatlashtirilgan. Ekrandagi jonli ko'rsatkich sochilishsiz (yaxlitlangan qiymat), sochilish faqat "O'lchash" tugmasi bilan yozilgan o'lchovda.

---

### 1. Om qonuni (`ohm-law`, simulyatsiya: `OhmLawSimulation`)

**Model va formulalar.** I = U/R (zanjir qismi uchun Om qonuni); P = U·I. Kalit ochiq bo'lsa I = 0 va rezistordagi kuchlanish ham 0 (voltmetr rezistorga parallel).

**Konstantalar va jadval qiymatlari.** Konstanta yo'q.

| Kattalik | Qiymat | Manba |
|---|---|---|
| Kuchlanish, qarshilik | slayderlar: U = 1…12 V (0,5 V qadam), R = 10…500 Ω | topshiriq sozlamasi |

**Asboblar, aniqlik va noaniqlik.** Ampermetr — DMM, ketma-ket (200 mA diapazon, oxirgi raqam 0,1 mA; ≥ 200 mA da 10 A kirish, 0,01 A). Voltmetr — DMM (V). σ = 0,05 %. Kalit ochiq bo'lganda ampermetr "0.000 mA" ko'rsatadi, "O'lchash" tugmasi esa o'chirilgan: ma'nosiz 0 o'lchovi oldingi to'g'ri o'lchovning o'rnini egallamasligi uchun (server oxirgi o'lchovni tekshiradi).

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 2. U = 6 V, R = 100 Ω | 60,0 mA | 0,21 % | 1 % |
| 3. U = 12 V, R = 100 Ω | 120,0 mA | 0,17 % | 1 % |
| 4. U = 6 V, R = 300 Ω | 20,0 mA | 0,38 % | 1 % |

**Standart sozlamalarda kutilgan natijalar.** U = 6 V, R = 100 Ω, kalit yopiq → ampermetr 60.0 mA, voltmetr 6.00 V, P = 0,36 W = 360 mW.

**Soddalashtirishlar.** Manba ideal (ichki qarshiligi 0), rezistor nominal qiymatga teng (haqiqiy rezistorlarning ±1–5 % dopuski yo'q), ampermetrning ichki qarshiligi (200 mA diapazonda ≈ 1 Ω) hisobga olinmaydi — 100 Ω da bu tokni ≈ 1 % kamaytirgan bo'lardi. R = 10 Ω, U = 12 V da rezistorda 14,4 W ajraladi — real maktab rezistori (1–2 W) uchun haddan tashqari; bunday rejimda reostat kerak.

### 2. Ketma-ket ulanish (`series-circuits`, simulyatsiya: `SeriesCircuitSimulation`)

**Model va formulalar.** R = R₁ + R₂ + R₃, I = U/R, Uₖ = I·Rₖ, ΣUₖ = U, P = U·I.

**Konstantalar va jadval qiymatlari.** Konstanta yo'q. Slayderlar: U = 3…24 V, Rₖ = 50…500 Ω (50 Ω qadam).

**Asboblar, aniqlik va noaniqlik.** Umumiy qarshilik real laboratoriyadagidek o'lchanadi: manba o'chirilgan, DMM ommetri R₁–R₂–R₃ zanjirining uchlariga ulanadi (1999 Ω diapazon, oxirgi raqam 1 Ω; ≥ 2 kΩ da 10 Ω). σ = 0,05 %.

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 2. 100 + 200 + 150 Ω | 450 Ω | 0,24 % | 1 % |
| 5. 100 + 200 + 500 Ω | 800 Ω | 0,19 % | 1 % |

**Standart sozlamalarda kutilgan natijalar.** U = 12 V; R = 450 Ω (ommetr "450 Ω"); I = 26,67 mA; U₁ = 2,67 V, U₂ = 5,33 V, U₃ = 4,00 V; P = 320 mW.

**Soddalashtirishlar.** Rezistorlar ideal va nominal; tutashtiruvchi simlar qarshiligi (≈ 0,01–0,1 Ω) yo'q.

### 3. Parallel ulanish (`parallel-circuits`, simulyatsiya: `ParallelCircuitSimulation`)

**Model va formulalar.** 1/R = 1/R₁ + 1/R₂ + 1/R₃, Iₖ = U/Rₖ, I = ΣIₖ = U/R (Kirxgofning 1-qonuni).

**Konstantalar va jadval qiymatlari.** Konstanta yo'q. Slayderlar: U = 3…24 V, Rₖ = 50…500 Ω.

**Asboblar, aniqlik va noaniqlik.** DMM ommetri ikki shina orasida, manba o'chirilgan (199,9 Ω diapazon, 0,1 Ω). σ = 0,05 %.

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 2. 100 ‖ 200 ‖ 150 Ω | 46,154 Ω (ommetr 46.2 Ω) | 0,23 % | 1 % |
| 5. 300 ‖ 300 ‖ 300 Ω | 100,0 Ω | 0,18 % | 1 % |

**Standart sozlamalarda kutilgan natijalar.** U = 12 V; R = 46,15 Ω; I₁ = 120 mA, I₂ = 60 mA, I₃ = 80 mA, I = 260 mA; P = 3,12 W.

**Soddalashtirishlar.** Ideal manba; ekrandagi toklar nazariy (hisoblangan) qiymatlar, ular o'lchov emas.

### 4. Kirxgof qonunlari (`kirchhoff-laws`, simulyatsiya: `KirchhoffSimulation`)

**Model va formulalar.** ℰ – R₁ – (R₂ ‖ R₃) zanjiri; noma'lum toklar I₁, I₂, I₃ chiziqli sistemadan (Gauss usuli, qisman tanlash bilan) topiladi: I₁ − I₂ − I₃ = 0, R₁I₁ + R₂I₂ = ℰ, R₂I₂ − R₃I₃ = 0. Natija ketma-ket–parallel soddalashtirish bilan mustaqil tekshiriladi: I₁ = ℰ/(R₁ + R₂R₃/(R₂ + R₃)), I₂ = I₁R₃/(R₂ + R₃). Kanvasdagi KCL/KVL qatorlari I₂R₂ va I₃R₃ alohida hisoblangani uchun haqiqiy tekshiruv.

**Konstantalar va jadval qiymatlari.** Slayderlar: ℰ = 6…24 V (2 V qadam), R₁ = 1…10 Ω, R₂, R₃ = 2…12 Ω.

**Asboblar, aniqlik va noaniqlik.** Asosiy tok DMM ning 10 A kirishida (oxirgi raqam 0,01 A; 200 mA dan kichik toklar mA kirishida), σ = 0,05 %. 1,2 A tokda kvantlash (±0,005 A = 0,42 %) o'zi 1 % tolerantlikning yarmidan katta bo'lardi, shuning uchun uchala o'lchov vazifasida tolerantlik **1 % → 2 %** qilindi (bu odatdagi 3½ razryadli DMM ning 10 A diapazon aniqligi ±(1,2–2 %) ga mos).

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 2. ℰ = 12 V, 4/6/3 Ω | 2,00 A | 0,38 % | 2 % |
| 4. ℰ = 24 V | 4,00 A | 0,25 % | 2 % |
| 5. ℰ = 12 V, 4/12/12 Ω | 1,20 A | 0,54 % | 2 % |

**Standart sozlamalarda kutilgan natijalar.** ℰ = 12 V, R₁ = 4, R₂ = 6, R₃ = 3 Ω → R_um = 6 Ω, I₁ = 2,00 A, I₂ = 0,667 A, I₃ = 1,333 A, U₁ = 8 V, U₂ = U₃ = 4 V.

**Soddalashtirishlar.** Manba ideal (ichki qarshiligi 0). Kichik qarshiliklar va katta toklar (24 V da 4 A, R₁ da 64 W) faqat quvvatli reostatlar / quvvatli rezistorlar bilan real; maktab rezistorlari bunday quvvatga chidamaydi. Real tajribada odatda 10–100 Ω va 0,1–0,5 A ishlatiladi.

### 5. Kondensatorlar (`capacitors`, simulyatsiya: `CapacitorSimulation`)

**Model va formulalar.** τ = RC; zaryadlanish U_C(t) = U₀(1 − e^(−t/τ)), I = (U₀/R)e^(−t/τ); razryadlanish U_C(t) = U₀e^(−t/τ); q = CU_C, W = CU_C²/2. Hammasi o'tgan vaqtning aniq (yopiq ko'rinishdagi) funksiyasi — kadr chastotasiga bog'liq emas. Tez jarayonlar ekranda kamida 4 s ga cho'ziladi ("sekinlashtirilgan ×N"), fizik vaqt t esa haqiqiy.

**Konstantalar va jadval qiymatlari.** U₀ = 12 V (manba), C = 10…500 µF, R = 100…5000 Ω → τ = 1 ms…2,5 s.

**Asboblar, aniqlik va noaniqlik.** Kuchlanish DMM voltmetri bilan (kirish qarshiligi 10 MΩ: 10 MΩ·100 µF = 1000 s ≫ 5τ, yuklama sezilmaydi). Kalitni taymer boshqaradi: jarayon aniq t = 5τ da to'xtaydi, shuning uchun "jarayon tugagach" o'lchovida vaqt xatosi yo'q. 5τ da U_C = 11,92 V (19,99 V diapazon, 0,01 V) va razryaddan keyin 80,9 mV (199,9 mV diapazon, 0,1 mV). Eski versiyada natija 2 o'nlik xonagacha "0.08 V" deb yuborilar edi (1 % xato), shuning uchun razryad vazifasida 15 % tolerantlik qo'yilgan edi; endi voltmetr millivoltda 3 raqam ko'rsatadi va tolerantlik **15 % → 2 %** qilindi (DMM aniqligi ±(0,5 % + 2 raqam) = ±0,6 mV ≈ 0,75 % ga mos). Nisbiy sezgirlik δU/U = δt/τ har qanday t da bir xil, 5τ dagi kichik qoldiq esa real tajribada kondensatorning sizib chiqishi va dielektrik absorbsiyasi tufayli yuqoriroq bo'ladi (pastga qarang).

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 3. Zaryadlanish, t = 5τ = 500 ms | 11,919 V | 0,17 % | 1 % |
| 6. Razryad, t = 5τ = 500 ms | 0,080855 V (80,9 mV) | 0,19 % | 2 % |

**Standart sozlamalarda kutilgan natijalar.** C = 100 µF, R = 1000 Ω → τ = 100,0 ms; t = τ da 7,585 V (63,2 %); 5τ da 11,92 V; razryaddan keyin 80,9 mV; q = 1,2 mC, W = 7,2 mJ; boshlang'ich tok 12 mA.

**Soddalashtirishlar.** Kondensator ideal: sizib chiqish toki, ekvivalent ketma-ket qarshilik (ESR) va elektrolitik kondensatorlarning dielektrik absorbsiyasi (uzib qo'yilgandan keyin bir necha foiz kuchlanish "qaytib keladi") modellashtirilmagan; elektrolitik kondensator sig'imining dopuski odatda ±20 %. Shuning uchun real laboratoriyada τ ni odatda U_C = 0,368U₀ (t = τ) nuqtasida o'lchashadi.

### 6. RC zanjirlar (`rc-circuits`, simulyatsiya: `RCCircuitSimulation`)

**Model va formulalar.** 5-tajribadagi kabi; qo'shimcha: p ulushga yetish vaqti t = −τ·ln(1 − p) (50 % uchun t = τ·ln 2 ≈ 0,693τ). Grafikda U_C(t) va I(t) aniq egri chiziqlar (200 nuqta, [0; 5τ]).

**Konstantalar va jadval qiymatlari.** U₀ = 10 V, R = 100…5000 Ω, C = 10…500 µF.

**Asboblar, aniqlik va noaniqlik.** DMM voltmetri (σ = 0,05 %), taymer jarayonni aniq 5τ da to'xtatadi. Razryad vazifasida tolerantlik **15 % → 2 %** (yuqoridagi sabab bilan).

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 2. Zaryadlanish, 5τ = 500 ms | 9,9326 V | 0,18 % | 1 % |
| 6. Razryad, 5τ = 500 ms | 0,067379 V (67,4 mV) | 0,20 % | 2 % |

**Standart sozlamalarda kutilgan natijalar.** R = 1000 Ω, C = 100 µF → τ = 100 ms; t = τ da 6,321 V; t½ = 69,31 ms; 5τ da 9,93 V; razryaddan keyin 67,4 mV; I₀ = 10 mA. R = 2000 Ω, C = 250 µF → τ = 500 ms.

**Soddalashtirishlar.** 5-tajribadagi kabi (ideal kondensator va manba).

### 7. Elektr quvvati (`electric-power`, simulyatsiya: `ElectricPowerSimulation`)

**Model va formulalar.** P = U·I = I²R = U²/R (R = U/I); W = P·t; E[kW·soat] = P[W]·t[soat]/1000; 1 kW·soat = 3,6 MJ; to'lov = E·tarif.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Aholi uchun elektr energiyasi tarifi (oyiga 200 kW·soatgacha, 2026-yil 1-iyundan) | 650 so'm/(kW·soat) (avval 600; simulyatsiyada eski 500 qiymati turgan edi) | Vazirlar Mahkamasining 15.05.2026 dagi 243-son qarori, Energetika vazirligi: https://gov.uz/oz/minenergy/news/view/165918 ; https://interfax.com/newsroom/top-stories/117593/ |
| Normadan yuqori iste'mol | 201–500 kW·soat — 900 so'm, 501–1000 kW·soat — 1100 so'm | o'sha qaror (https://anhor.uz/uzl/news/elektr-energiyasi-tabiiy-gaz-yangi-tariflar/) |

**Asboblar, aniqlik va noaniqlik.** Raqamli vattmetr (rozetkali quvvat o'lchagich sinfi): oxirgi raqam 0,1 W, σ = 0,05 %, aniqlik ±(0,5 % + 0,2 W).

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 2. U = 12 V, I = 2 A | 24,0 W | 0,33 % | 1 % |
| 3. U = 24 V, I = 5 A | 120,0 W | 0,17 % | 1 % |
| 5. (hisob) 120 W × 5 soat × 650 so'm | 390 so'm (avval 300 so'm 500 so'mlik tarif bilan) | — | 1 % |

**Standart sozlamalarda kutilgan natijalar.** U = 12 V, I = 2 A → P = 24,0 W, R = 6,0 Ω; 1 soatda 24 Wh = 0,024 kW·soat, narxi 15,6 so'm.

**Soddalashtirishlar.** U va I alohida slayderlar — bu yuklama qarshiligini R = U/I tanlash bilan teng; cho'g'lanma lampaning qarshiligi haroratga bog'liqligi (sovuq holatda ~10 marta kichik) modellashtirilmagan. Tarif maishiy iste'molchining oylik normasi ichidagi narx.

### 8. Elektrostatika (`electrostatics`, simulyatsiya: `ElectrostaticsSimulation`)

**Model va formulalar.** Kulon qonuni F = k|q₁q₂|/r², vektor ko'rinishi F₁₂ = kq₁q₂(r₁ − r₂)/|r₁ − r₂|³ (Nyutonning 3-qonuni: kuchlar teng va qarama-qarshi). Kuch chiziqlari E = ΣEᵢ yo'nalish maydonida RK4 bilan chiziladi (geometrik qurilish), ekvipotensiallar V = Σkqᵢ/rᵢ ning marching-squares konturlari.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| k | 8,987 551 7862·10⁹ N·m²/C² (matnda darslikdagidek 8,99·10⁹) | CODATA 2022, https://physics.nist.gov/cuu/Constants/Table/allascii.txt |
| Havoning elektr mustahkamligi | ≈ 3 MV/m | https://hypertextbook.com/facts/2000/AliceHong.shtml |

Kutilgan qiymatlar k ning 2022 qiymati bilan qayta hisoblandi: 14,979 mN, 59,917 mN, 24,965 mN, 89 876 V/m (5 qiymatli raqamda 2018 dagi bilan bir xil, farq 7·10⁻¹⁰).

**Asboblar, aniqlik va noaniqlik.** 1-zaryad o'rnatilgan kuch datchigi: oxirgi raqam 0,01 mN, σ = 0,1 %, natija har doim mN da.

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 2. 5 µC, −3 µC, r = 3 m | 14,979 mN | 0,28 % | 1 % |
| 3. r = 1,5 m | 59,917 mN | 0,26 % | 1 % |
| 4. 10 µC, 10 µC, r = 6 m | 24,965 mN | 0,27 % | 1 % |

**Standart sozlamalarda kutilgan natijalar.** q₁ = 5 µC, q₂ = −3 µC, r = 300 cm → F = 14,98 mN, tortishish.

**Soddalashtirishlar (apparat masshtabi).** Mikrokulon zaryadlar va metrli masofalar — darslik masalalari masshtabi. Havoda radiusi R bo'lgan sharning eng katta zaryadi q_max = 4πε₀R²·E_teshilish ≈ R²·3,3·10⁻⁴ C/m², ya'ni 5 µC uchun R ≥ 12 cm, 10 µC uchun R ≥ 17 cm kerak (Van-de-Graaf generatori sharlari o'lchamida). 1 m masofada bunday sharlar endi to'liq "nuqtaviy" emas (induksiya tuzatmasi bir necha foiz). Real maktab/universitet tajribasida Kulon tarozisi (masalan, PASCO ES-9070) nanokulon zaryadlar va 2–20 cm masofalar bilan ishlaydi; kuchlar 10⁻⁵…10⁻³ N; o'lchov noaniqligi 5–10 %. Simulyatsiyadagi kuch datchigi ideallashtirilgan (σ = 0,1 %).

### 9. Elektr maydoni (`electric-field`, simulyatsiya: `ElectricFieldSimulation`)

**Model va formulalar.** E = Σkqᵢ(r − rᵢ)/|r − rᵢ|³, φ = Σkqᵢ/|r − rᵢ|. Zaryadlar (3; 2,5) m va (5; 2,5) m da (kanvas masshtabi: 100 px = 1 m, to'r katagi 0,5 m). Zond kanvas bosilganda 10 cm qadamli koordinata to'rining eng yaqin tuguniga o'rnatiladi (real maydon xaritasi taxtasidagi belgilangan nuqtalar kabi) — shuning uchun dipol markazi (4; 2,5) m ga aniq tushish mumkin.

**Konstantalar va jadval qiymatlari.** k — 8-tajribadagi kabi (CODATA 2022). Zaryadlar −20…20 µC.

**Asboblar, aniqlik va noaniqlik.** Maydon o'lchagich (field-mill turidagi): 4 qiymatli raqam, SI prefiksi bilan (masalan, 63.55 kV/m), σ = 0,1 %; eng katta xato 2,5σ + ½ oxirgi raqam.

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 2. ±10 µC, zond (4; 1,5) m | 63,552 kV/m | 0,26 % | 1 % |
| 3. Dipol markazi (4; 2,5) m | 179,75 kV/m | 0,28 % | 2 % |

**Standart sozlamalarda kutilgan natijalar.** q₁ = +10 µC, q₂ = −10 µC, zond (4; 1,5) m → E = 63,55 kV/m, φ = 0 V (o'rta perpendikulyar ekvipotensial); markazda E = 2kq/(1 m)² = 179,8 kV/m.

**Soddalashtirishlar.** Ikki o'lchamli tasvir, nuqtaviy zaryadlar (8-tajribadagi masshtab izohi bu yerda ham amal qiladi: 10 µC uchun shar radiusi ≥ 17 cm). Zond maydonni buzmaydi deb olingan.

### 10. Diodlar va LEDlar (`diodes-leds`, simulyatsiya: `DiodesSimulation`)

**Model va formulalar.** Shokli o'tishi + ketma-ket qarshilik: I = I_s(e^(V_j/(nU_T)) − 1), V_D = V_j + I·R_s. Manba → R → diod zanjirining ishchi nuqtasi Lambert W funksiyasi orqali aniq: R_t = R + R_s, I = (nU_T/R_t)·W((I_sR_t/(nU_T))·e^((U + I_sR_t)/(nU_T))) − I_s. Soddalashtirilgan (darslik) modeli I ≈ (U − U_f)/R kalkulyatorda; uning aniq modeldan farqi barcha vazifalarda < 2 % (tekshiruv tolerantligi).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Model harorati | 300 K (U_T = 25,85 mV). SPICE modellari TNOM = 27 °C da berilgan; 20–25 °C xonada real diod kuchlanishi −2 mV/K koeffitsient bilan atigi 4–14 mV katta (tokka ta'siri < 0,3 %) | SPICE standart TNOM; https://workforce.libretexts.org/Bookshelves/Electronics_Technology/Electric_Circuits_III_-_Semiconductors_(Kuphaldt)/03%3A_Diodes_and_Rectifiers/3.14%3A_SPICE_Models |
| Kremniy diod — 1N4001 (Motorola SPICE modeli) | I_s = 14,11 nA, n = 1,984, R_s = 33,89 mΩ → 10 mA da 0,691 V, 42 mA da 0,766 V, 1 A da 0,96 V (ma'lumotnoma: V_F typ 0,93 V, max 1,1 V @ 1 A; I_R ≤ 5 µA, onsemi typ 0,05 µA @ 25 °C); teskari tok ≈ 14 nA. Avval I_s = 10⁻¹⁴ A, n = 1 (ideal) model edi: 42 mA da 0,752 V | https://ocw.bib.upct.es/pluginfile.php/12558/mod_resource/content/1/4%20DYSE%20LTSpice%20ejemplos%20analogicos.pdf ; https://www.vishay.com/docs/88503/1n4001.pdf ; https://physcourse.thu.edu.tw/~mengwen/exp-electronics/datasheet/1N4001-1N4007.pdf |
| Germaniy diod — Д7 turidagi qotishma (alloy) to'g'rilagich, 0,3 A | I_s = 0,1 µA, n = 1 → 10 mA da 0,30 V, 47 mA da 0,34 V, 0,3 A da 0,39 V (o'zgarmadi) | https://www.radiomuseum.org/tubes/tube_d7j.html |
| Qizil 5 mm LED (GaAlAs, Kingbright L-7113SRD-E) | ma'lumotnoma: V_F typ 1,85 V, max 2,5 V @ 20 mA, I_F max 30 mA; model I_s = 10⁻¹⁷ A, n = 2 → 20 mA da 1,82 V (o'zgarmadi) | https://www.electronicsdatasheets.com/download/118057.pdf?format=pdf |

Taqqoslash uchun nuqta-kontaktli Ge diod (1N34A, Д9) 40 mA da 0,7–1 V beradi (1N34A: V_F ≤ 1,0 V @ 5 mA) — darslikdagi "germaniy ≈ 0,3 V" qiymati qotishma to'g'rilagich diodlarga tegishli.

**Asboblar, aniqlik va noaniqlik.** DMM ampermetri ketma-ket (199,9 mA diapazon, 0,1 mA; 19,99 mA diapazon 0,01 mA), σ = 0,05 %.

| Vazifa | Kutilgan | Eng katta xato | Tolerantlik |
|---|---|---|---|
| 2. Si, 5 V, 100 Ω | 42,336 mA (avval 42,483 — ideal model) | 0,24 % | 2 % |
| 3. Ge, 5 V, 100 Ω | 46,626 mA | 0,23 % | 2 % |
| 4. LED, 5 V, **150 Ω** (avval 100 Ω → 31,5 mA, LED ning 30 mA chegarasidan yuqori) | 21,170 mA (avval 31,548) | 0,36 % | 2 % |

**Standart sozlamalarda kutilgan natijalar.** Kremniy, U = 5 V, R = 100 Ω → I = 42,3 mA, V_D = 0,766 V, U_R = 4,234 V. Germaniy → 46,6 mA (V_D = 0,337 V). LED, 150 Ω → 21,2 mA (V_D = 1,825 V); 100 Ω → 31,5 mA. Teskari ulanish U = −5 V: Si −14 nA (ampermetr "-0.000 mA" ≈ 0), Ge −0,1 µA.

**Soddalashtirishlar.** Teskari teshilish (breakdown) va haroratning I_s ga ta'siri modellashtirilmagan; LED ning ketma-ket qarshiligi (≈ 10–20 Ω) va yorug'lik chiqishi sifat jihatdan (yorqinlik ∝ tok, 20 mA da to'liq). Diod turlari orasidagi tarqoqlik (bir xil turdagi diodlarda V_F ±30–50 mV) yo'q.


## 8. Magnetizm

Umumiy konstantalar (barcha magnetizm tajribalari uchun):

| Kattalik | Qiymat | Manba |
|---|---|---|
| Magnit doimiysi μ₀ | 1,256 637 061 27·10⁻⁶ N/A² (u_r = 1,6·10⁻¹⁰); 4π·10⁻⁷ dan farqi −1,3·10⁻¹⁰ (nisbiy) | NIST CODATA 2022, https://physics.nist.gov/cgi-bin/cuu/Value?mu0 |
| Yer magnit maydoni, Toshkent (41,30° N, 69,24° E, 455 m, 2026-10-20) | F = 53 966 nT, H = 25 536 nT, Z = 47 542 nT, I = 61,76°, D = 5,69° E | WMM2025 (BGS web-servisi): https://geomag.bgs.ac.uk/web_service/GMModels/wmm/2025/?latitude=41.2995&longitude=69.2401&altitude=0.455&date=2026-10-20&format=json ; IGRF-14 bilan 10 nT gacha mos |
| O'zbekiston elektr tarmog'i | 220 V, 50 Hz; uch fazali liniya kuchlanishi √3·220 ≈ 380 V | CIA World Factbook, https://www.cia.gov/the-world-factbook/about/archives/2023/countries/uzbekistan/travel-facts (worldstandards.eu 230 V deb ko'rsatadi — IEC nominali; maktab darsliklari va amaldagi tarmoq 220 V) |

`physics/magnetism.js` endi μ₀ ni `constants.js` (`MU_0`) dan oladi (`MU0` nomi saqlangan). Barcha topshiriq javoblari 5 xonali aniqlikda o'zgarmadi.

Umumiy shovqin siyosati: har bir o'lchash asbobi o'qishni asbob bo'linmasiga (oxirgi raqamga) yaxlitlaydi va kichik, takrorlanuvchi (seed = simulyatsiya nomi + sozlamalar + o'lchov tartib raqami) tarqoqlik qo'shadi; tarqoqlik ±2,5σ bilan cheklangan. Har bir o'lchov topshirig'i uchun maksimal xato ≤ ½·tolerantlik ekani testda isbotlangan (`Simulations/__tests__/magnetism.e2e.test.js`).

### 1. Magnit maydoni chiziqlari (`magnetic-field-lines`, simulyatsiya: `MagneticFieldSimulation`)

**Model va formulalar.** Polosali magnit — Gilbert ikki qutbli modeli: qutb kuchi q = p_m/ℓ, qutblar orasidagi masofa ℓ = 0,85 × 10 cm = 8,5 cm (effektiv qutblar uchlardan biroz ichkarida). Maydon B = Σ μ₀q/(4π)·r̂/r²; maydon chiziqlari shu maydon bo'ylab RK2 bilan chiziladi; kompas strelkasi lokal B yo'nalishida. Yangi "O'lchash" amali: magnit o'qida (S uchidan tashqarida), markazdan r masofadagi teslametr zondi B = μ₀q/(4π)·[1/(r − ℓ/2)² − 1/(r + ℓ/2)²] ni o'qiydi. Uzoqda bu dipol formulasi B = μ₀·2p_m/(4πr³) ga intiladi (topshiriq 3 dagi hisoblash formulasi). Masshtab: 200 px = 10 cm (0,5 mm/px).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| μ₀ | 1,25663706127·10⁻⁶ T·m/A | https://physics.nist.gov/cgi-bin/cuu/Value?mu0 |
| Magnit momenti (100 %) | 2 A·m² (10 cm li maktab magniti uchun odatiy 1–10 A·m²: ferrit Br ≈ 0,4 T, alniko ≈ 1,2 T, hajm ~15 cm³ → m = Br·V/μ₀ ≈ 5–15 A·m²) | model qiymati, tartibi tekshirildi |
| Yer maydoni (Toshkent) | ≈ 54 µT (gorizontal ≈ 25,5 µT) | WMM2025, yuqoridagi havola |

**Asboblar, aniqlik va noaniqlik.** Raqamli teslametr (Xoll zondi), 3½ xona, diapazonlar 20 / 200 / 2000 mT, ruxsat 0,01 / 0,1 / 1 mT (PHYWE Teslameter digital 13610-93: https://phywe.com/equipment-accessories/measurement-devices/oscilloscopes/phywe-teslameter-digital_2108). Tarqoqlik σ = 0,1 % (zondni joylash). Maks. xato = 2,5σ + ½ raqam. Zond o'lchashdan oldin nolga keltiriladi (Yer maydoni kompensatsiya qilinadi). Bu tajribada o'lchov topshirig'i yo'q (faqat hisoblash: 0,2 mT va 40 µT, tolerantlik 1 %).

**Standart sozlamalarda kutilgan natijalar.** Magnit kuchi 50 % (p_m = 1 A·m²), zond r = 10 cm: model B = 0,2979 mT → teslametr "0,30 mT"; dipol formulasi 0,200 mT. r = 18 cm da model dipol formulasidan atigi ~12 % katta. Topshiriq 4: 10 A, 5 cm → 40,0 µT ≈ 0,74 × Yer maydoni (Toshkent).

**Soddalashtirishlar.** Qutbli model magnitdan tashqarida aniq, ichida emas; kompaslar Yer maydonini sezmaydi (real tajribada kuchsiz magnit (10 %) yaqinida kompas Yer maydoni ta'sirida ham buriladi); teslametr zondi faqat o'q bo'ylab qo'yiladi. Yaqin masofada (r ≈ magnit uzunligi) dipol formulasi ~33 % kichik natija beradi (0,200 mT o'rniga 0,298 mT) — bu formulaning chegarasi, xato emas (nazariya matnida tushuntirildi).

### 2. Elektromagnit (`electromagnet`, simulyatsiya: `ElectromagnetSimulation`)

**Model va formulalar.** Uzun solenoid: H = n·I, n = N/l, l = 10 cm. O'zak magnitlanishi to'yinadigan B–H egri chizig'i bilan: J_lin = μ₀(μr − 1)H; |J_lin| ≤ J_k (tirsak) bo'lsa J = J_lin, aks holda J = J_k + (J_s − J_k)·tanh((|J_lin| − J_k)/(J_s − J_k)); B = μ₀H + J (`coreSolenoidField`). Tirsakdan pastda bu aynan darslikdagi B = μ₀μr·N·I/l; tirsakda egri uzluksiz va silliq (hosila 1), yuqorida B → B_s + μ₀H. Avval simulyatsiya to'yinishda ham chiziqli qiymatni (masalan 157 T!) o'lchov sifatida berar edi — endi teslametr haqiqiy ≈ 2,03 T ni ko'rsatadi, panelda chiziqli formula qiymati va ogohlantirish ham chiqadi. Tok slayderi 0,5–5 A (qadam 0,5) dan 0,1–5 A (qadam 0,1) ga o'zgartirildi: avval temir o'zakda eng kichik sozlama ham 1,57 T (deyarli to'yingan) edi.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Temir μr | 5000 (99,8 % toza temirning maksimal qiymati) | https://en.wikipedia.org/wiki/Permeability_(electromagnetism) |
| Temir B_s | 2,0 T ("yumshoq temir"; yuqori singdiruvchan temir qotishmalari 1,6–2,2 T, toza temir ≈ 2,15 T) | https://en.wikipedia.org/wiki/Saturation_(magnetic) |
| Temir tirsagi J_k | 1,2 T (0,6·B_s; real temirning B–H egrisi ~1,2–1,4 T dan bukiladi) | model parametri |
| Ferrit (Mn-Zn, TDK N87) μr | 2000 (μi = 2200 ± 25 %) | TDK/EPCOS N87 ma'lumotnomasi, https://web.pa.msu.edu/people/edmunds/Disco_Kraken/Components/Transformers/tdk_ferrite_n87_siferrit.pdf |
| Ferrit B_s | 0,45 T (N87: 490 mT 25 °C da, 390 mT 100 °C da) | o'sha manba |
| Ferrit tirsagi | 0,27 T | model parametri |

**Asboblar, aniqlik va noaniqlik.** Teslametr (1-tajribadagidek): 2,5133 mT → ruxsat 0,01 mT, maks. xato 0,45 %; 5,0265 mT → 0,35 %; 6,2832 mT → 0,33 %; 628,32 mT → ruxsat 1 mT, 0,33 %. Hammasi topshiriq tolerantligi 1 % ning yarmidan kichik (testda isbotlangan, 30 tadan o'lchov).

**Standart sozlamalarda kutilgan natijalar.** N = 100, I = 2 A, havo: 2,5133 mT (o'qish "2,51 mT"). Topshiriqlar: N = 200, 2 A → 5,0265 mT; N = 100, 5 A → 6,2832 mT; **temir o'zak topshirig'i o'zgardi: N = 50, I = 0,5 A, 1570,8 mT → N = 50, I = 0,2 A, 628,32 mT** (sabab: 1,57 T real temirda B–H egrisining tirsagidan yuqorida, chiziqli formula u yerda ~2 % ortiqcha beradi; 0,63 T esa chiziqli sohada, formula aniq). To'yinish misollari: temir N = 100, I = 2 A: chiziqli 12,57 T → haqiqiy 2,0025 T; N = 500, I = 5 A: 157,08 T → 2,0314 T; ferrit N = 100, 2 A: 5,03 T → 0,4525 T.

**Soddalashtirishlar.** Cheksiz uzun solenoid formulasi (10 cm uzunlik, radius ~2 cm bo'lgan real g'altak markazida maydon ~7 % kichik bo'ladi); o'zak yopiq magnit zanjir sifatida (ochiq sterjen o'zakda demagnitlanish tufayli effektiv μ ancha kichik — real elektromagnitda o'nlab–yuzlab); gisterezis va qoldiq magnitlanish modellashtirilmagan; teslametr zondi o'zak ichidagi ingichka tirqishda deb faraz qilinadi.

### 3. Elektromagnit induksiya (`em-induction`, simulyatsiya: `EMInductionSimulation`)

**Model va formulalar.** G'altak radiusi a = 6 cm, magnit 12 cm (qutblar 10 cm oraliqda), m = 3 A·m², N qutb chapda. Bir o'ramdan o'tuvchi B-oqim (Gilbert modeli, magnit ichidagi magnitlanish oqimini hisobga oladi, shuning uchun magnit g'altakdan o'tganda uzluksiz): Φ = (μ₀q/2)·[u(x_N) − u(x_S)], u(x) = x/√(x² + a²). dΦ/dt aniq analitik: (μ₀q/2)·[a²/(x_N²+a²)^{3/2} − a²/(x_S²+a²)^{3/2}]·v; ℰ = −N·dΦ/dt. Harakat — o'zgarmas tezlikli "moki" harakati (vaqtning yopiq funksiyasi), 20 marta sekinlashtirib ko'rsatiladi. Ishora tekshirildi: S uchi oldinda yaqinlashganda ℰ > 0, induksiyalangan maydon +x, g'altakning magnitga qaragan uchi S (itarish). Yangi "O'lchash": millivoltmetr PEAK HOLD — boshlanganidan beri bosib o'tilgan yo'ldagi max|ℰ| (`inductionPeakEMF`: zich skanerlash + oltin kesim, kadr chastotasiga bog'liq emas).

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| μ₀ | 1,25663706127·10⁻⁶ T·m/A | NIST CODATA 2022 |
| Magnit momenti | 3 A·m² (12 cm magnit uchun real tartib) | model qiymati |

**Asboblar, aniqlik va noaniqlik.** 3½ xonali multimetr, 200 mV diapazon (ruxsat 0,1 mV), PEAK HOLD rejimi: σ = 0,5 % (qisqa impuls cho'qqisini ushlash), maks. xato 2,5σ + 0,05 mV. O'lchov topshirig'i yo'q.

**Standart sozlamalarda kutilgan natijalar.** N = 50, v = 2 m/s: max|ℰ| = 27,29 mV (magnit markazi g'altakdan ±5,33 cm da). ℰ ∝ N·v: N = 100, v = 5 m/s → 136,5 mV; N = 20, v = 1 m/s → 5,46 mV. Hisoblash topshiriqlari: −1,0 V va −2,0 V.

**Soddalashtirishlar.** Magnit o'q bo'ylab, o'zgarmas tezlikda (real qo'l harakatida tezlik o'zgaradi); g'altak bir qatlamli, barcha o'ramlar bitta tekislikda deb olingan (real g'altak uzunligi bo'ylab oqim o'rtachalanadi va cho'qqi biroz pasayadi); induksiya tokining magnitga teskari ta'siri (tormozlanish) hisobga olinmagan.

### 4. Lents qonuni (`lenz-law`, simulyatsiya: `LenzLawSimulation`)

**Model va formulalar.** 3-tajriba modeli: a = 5 cm, magnit 10 cm (qutblar 8,5 cm), m = 3 A·m²; zanjir qarshiligi R = 10 Ω + 0,02 Ω·N; I = ℰ/R. Lents yo'nalishi: B' dΦ/dt ga qarshi; o'ng qo'l qoidasi bilan o'ramlar tepasida ⊙ / pastida ⊗ va g'altak uchlaridagi qutblar to'g'ri ekani tekshirildi (B' chiqadigan uch — N). Yangi "O'lchash": induksiya toki va EYuK ning PEAK HOLD qiymatlari.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Mis sim qarshiligi | 0,02 Ω/o'ram (r = 5 cm, Ø0,5 mm mis: 0,0172·0,314/0,196 ≈ 0,028 Ω — tartibi mos) | ρ_Cu = 1,72·10⁻⁸ Ω·m (CRC) |

**Asboblar, aniqlik va noaniqlik.** Multimetr PEAK HOLD: mA diapazonlar 2/20/200 mA (20 mA da ruxsat 0,01 mA), mV diapazon 0,1 mV; σ = 0,5 %. O'lchov topshirig'i yo'q (hisoblash: 0,045455 A, 2,0 V).

**Standart sozlamalarda kutilgan natijalar.** N = 50, v = 3 m/s, R = 11 Ω: max|ℰ| = 58,16 mV, max I = 5,29 mA.

**Soddalashtirishlar.** G'altak induktivligi (L/R vaqt doimiysi) hisobga olinmagan — kichik g'altakda u ~10⁻⁵ s, impuls davomiyligi ~10⁻² s, ta'siri < 0,1 %; real demonstratsion galvanometr inersiyali, qisqa impulsda cho'qqini to'liq ko'rsatmaydi (shuning uchun raqamli PEAK HOLD olingan).

### 5. Elektr dvigatel (`electric-motor`, simulyatsiya: `ElectricMotorSimulation`)

**Model va formulalar.** N = 50 o'ramli 5 × 4 cm ramka (S = 20 cm²), bir jinsli B = (kuch %)·0,5 T, ikki segmentli kollektor: M(θ) = NBIS|sin θ|, o'rtacha M̄ = (2/π)NBIS. Tok I tok rostlagichli (CC) manba bilan beriladi, shuning uchun tezlikka bog'liq emas; manba kuchlanishi U = I·R_a + ℰ̄, ℰ̄ = (2/π)NBSω (teskari EYuK; ℰ̄·I = M̄·ω — energiya balansi testda). Barqaror rejim: M̄ = b·ω ⇒ ω = M̄/b, n = 60ω/(2π). Ishga tushish aniq yechim bilan: ω(t) = ω_ss + (ω₀ − ω_ss)e^{−t/τ}, τ = J/b = 0,5 s. Rotor θ = 90° (moment eng katta) holatdan boshlanadi — θ = 0 "o'lik nuqta"da real dvigatel o'zi ishga tushmaydi. Animatsiya 20 marta sekinlashtirilgan (600–3000 RPM ni 60 kadr/s da chizib bo'lmaydi), ko'rsatilgan barcha sonlar fizik.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Yuklama b | **5·10⁻³ → 5·10⁻⁴ N·m·s** | real dvigatel tezligiga moslash (pastga qarang) |
| R_a (chulg'am + cho'tkalar) | 0,5 Ω | 50 o'ram × 0,18 m ≈ 9 m mis sim, 0,5 mm² → 0,31 Ω + cho'tkalar |
| J = b·τ | 2,5·10⁻⁴ kg·m² | rotor + shkiv |
| Kichik DC dvigatel tezligi (taqqoslash) | Mabuchi RF-300CA: 2700–3700 ayl/min (3 V, salt) | https://static.chipdip.ru/lib/092/DOC006092869.pdf |

Avvalgi b = 5·10⁻³ N·m·s bilan topshiriq sozlamalarida 60,8 ayl/min chiqardi — real maktab dvigateli uchun haddan tashqari sekin. Endi: I = 2 A, B = 0,25 T → 607,93 RPM; slayder diapazoni bo'yicha 60,8 … 3039,6 RPM, manba kuchlanishi 0,3 … 12,6 V (0–15 V / 5 A maktab manbai yetadi).

**Asboblar, aniqlik va noaniqlik.** Optik (lazerli) taxometr DT-2234C turidagi: ruxsat 0,1 RPM (< 1000 RPM), 1 RPM (≥ 1000), aniqlik ±(0,05 % + 1 raqam) (https://makezine.com/article/workshop/tool-review-dt-2234c-digital-laser-tachometer/). Tarqoqlik σ = 0,1 % (kollektorli dvigatel tezligining pulsatsiyasi). Maks. xato: 607,93 RPM da 0,26 %, 1215,9 RPM da 0,29 % — tolerantlik 2 % (yarmi 1 %) ichida; ishga tushgandan 3 s keyin qoldiq e^{−6} = 0,25 %.

**Standart sozlamalarda kutilgan natijalar.** I = 2 A, 50 % (B = 0,25 T): F = NBIL = 1,25 N, M_max = 0,05 N·m, M̄ = 31,8 mN·m, ω = 63,66 rad/s, n = **607,93 RPM** (avval 60,793), ℰ̄ = 1,013 V, U = 2,01 V, P_mex = 2,03 W. I = 4 A: **1215,9 RPM** (avval 121,59), U = 4,03 V. Hisoblash topshiriqlari o'zgarmadi (0,05 N·m; 1,25 N).

**Soddalashtirishlar.** Faqat yopishqoq (b·ω) yuklama, quruq ishqalanish yo'q (real dvigatel kichik tokda umuman aylanmaydi); moment pulsatsiyasi o'rtachalangan (tezlik pulsatsiyasi taxometr tarqoqligida); kuchlanish manbai bilan ishlaganda (CV) tok tezlik oshgani sari kamayadi — bu yerda CC rejim ataylab tanlangan, shunda n ∝ I·B (topshiriq: "tokni 2 marta oshiring").

### 6. Transformator (`transformer`, simulyatsiya: `TransformerSimulation`)

**Model va formulalar.** Ideal transformator: U₂ = U₁·N₂/N₁, I₂ = U₂/R, P₁ = P₂ (η = 1), I₁ = I₂·N₂/N₁. Funksiya `transformer()` η < 1 ni ham qo'llaydi (P₁ = P₂/η), lekin simulyatsiyada η = 1 (nazariya — "ideal transformator"). "O'lchash" endi uchta asbob ko'rsatishini yozadi: U₂ (voltmetr), I₂ va I₁ (ampermetrlar). Tarmoq: 220 V / 50 Hz (ekranda "AC 50 Hz").

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| Tarmoq kuchlanishi va chastotasi | 220 V, 50 Hz | CIA World Factbook (yuqorida) |
| Uch fazali liniya kuchlanishi | 380 V = √3·220 (0,26 % aniqlikda) | 220/380 V tizimi |
| Kichik transformatorlar FIK | 90–98 % (faqat hujjatda) | — |

**Asboblar, aniqlik va noaniqlik.** 3½ xonali multimetr, AC V: 200 V diapazonda 0,1 V, 2000 V diapazonda 1 V; A: 2 A da 1 mA, 20 A da 10 mA; σ = 0,05 %. Maks. xato: 440 V → 0,24 %, 110 V → 0,17 %, 190 V → 0,15 % (tolerantlik 1 %, yarmi 0,5 %).

**Standart sozlamalarda kutilgan natijalar.** N₁ = 100, N₂ = 200, U₁ = 220 V, R = 100 Ω: U₂ = 440 V (o'qish "440"), I₂ = 4,40 A, I₁ = 8,80 A, P = 1936 W. Topshiriqlar: N₂ = 50 → 110,0 V; U₁ = 380 V, N₂ = 50 → 190,0 V; hisoblash 4,4 A va 8,8 A.

**Soddalashtirishlar.** Ideal: chulg'am qarshiligi, sochilish oqimi, o'zakdagi isroflar va salt ishlash (magnitlovchi) toki yo'q — real kichik transformatorda U₂ yuklama ostida bir necha foiz kamayadi. Real 220 V birlamchi chulg'am uchun ~1000 o'ram kerak bo'ladi (o'zak 1,2–1,5 T dan oshmasligi uchun: N₁ ≥ U/(4,44·f·B·S)); 100 o'ram — o'quv nisbati, faqat N₂/N₁ muhim. 440 V va ~2 kW yuklama maktab laboratoriyasida xavfli — bu faqat virtual tajriba.

### 7. O'zgaruvchan tok generatori (`electric-generator`, simulyatsiya: `GeneratorSimulation`)

**Model va formulalar.** NΦ(t) = NBS·cos ωt, ℰ(t) = −d(NΦ)/dt = ℰ₀ sin ωt, ℰ₀ = NBSω, ω = 2πf, f = n/60 (bir juft qutb), U_ef = ℰ₀/√2. Faza rpm o'zgarganda uzluksiz saqlanadi. Yangi "O'lchash": ossillograf avtomatik o'lchovlari — EYuK amplitudasi, ta'sir etuvchi qiymat, chastota.

**Konstantalar va jadval qiymatlari.**

| Kattalik | Qiymat | Manba |
|---|---|---|
| B | 0,5 T (qutblar orasi, kuchli NdFeB magnit yoki elektromagnit; oddiy maktab generatorida 0,1–0,3 T) | model qiymati |
| Ramka | 8 × 10 cm, S = 0,008 m² | — |
| 50 Hz uchun tezlik | 3000 ayl/min (bir juft qutb) | f = n/60 |

**Asboblar, aniqlik va noaniqlik.** Raqamli ossillograf: Vamp, Vrms 0,01 V ruxsat, σ = 0,5 % (vertikal); chastota 0,001 Hz, σ = 0,05 % (dastak tezligining tebranishi). O'lchov topshirig'i yo'q (hisoblash: 2,5133 V; 1,7772 V; 10,053 V; 1,0 Hz — tolerantlik 1 %).

**Standart sozlamalarda kutilgan natijalar.** n = 60 ayl/min, N = 100: f = 1,000 Hz, ω = 6,283 rad/s, ℰ₀ = 2,5133 V (ekranda "2,51 V"), U_ef = 1,7772 V. n = 120, N = 200: ℰ₀ = 10,053 V, f = 2,000 Hz.

**Soddalashtirishlar.** Bir jinsli maydon, salt ishlash (yuklama toki va uning tormozlovchi momenti yo'q), cho'tka va halqalardagi kuchlanish tushuvi hisobga olinmagan; 30–120 ayl/min — qo'lda aylantiriladigan maktab generatori uchun real diapazon.


## 9. Optika

Fizika moduli: `resources/js/Components/Lab/physics/optics.js` (SI birliklar, burchaklar normaldan). Linza va ko'zgular uchun yagona ishora qoidasi — «haqiqiy musbat»: 1/F = 1/d + 1/f, F > 0 yig'uvchi (qavariq linza, botiq ko'zgu), F < 0 sochuvchi; f > 0 haqiqiy, f < 0 mavhum tasvir; Γ = −f/d.

Barcha o'lchov asboblari `OPTICS_INSTRUMENTS` da (umumiy `instruments.js` katalogidan olingan). Har bir ko'rsatish = aniq qiymat + kesilgan normal sochilish (|z| ≤ 2,5σ), so'ng shkala bo'linmasiga yaxlitlanadi; demak |xato| ≤ 2,5σ + bo'linma/2. Sochilish simulyatsiya nomi va sozlamalardan seed qilinadi: bir xil sozlamadagi k-chi o'lchov har doim bir xil son. Har bir o'lchov vazifasi uchun «maksimal xato ≤ ½ · ruxsat etilgan xatolik» qoidasi `Simulations/__tests__/optics.e2e.test.js` da isbotlanadi (har vazifa uchun 30 marta takroriy o'lchov).

Umumiy manbalar:
- SCHOTT optik shisha katalogi (N-BK7, F2, N-SF11 ma'lumot varaqlari): https://media.schott.com/api/public/content/38cbbe876d324e03b1881d33f3d26635
- Sindirish ko'rsatkichlari jadvali (589,29 nm, 20 °C): https://en.wikipedia.org/wiki/List_of_refractive_indices
- Suvning sindirish ko'rsatkichi, IAPWS R9-97: https://iapws.org/relguide/Rindex.html
- Natriy D chiziqlari (NIST): https://physics.nist.gov/PhysRefData/Handbook/Tables/sodiumtable2.htm
- He–Ne lazer to'lqin uzunligi: https://en.wikipedia.org/wiki/Helium%E2%80%93neon_laser
- Frenel formulalari va Bryuster burchagi: https://en.wikipedia.org/wiki/Fresnel_equations , https://en.wikipedia.org/wiki/Brewster%27s_angle

| Asbob (`OPTICS_INSTRUMENTS`) | Bo'linma | σ | Maksimal xato |
|---|---|---|---|
| `protractor` — optik disk / transportir | 0,5° | 0,1° | ±0,5° |
| `benchImage` — optik skamya, tasvirni ko'z bilan fokuslash / parallaks | 1 mm | 1 mm | ±3 mm |
| `spectrometer` — goniometr-spektrometr (nonius) | 0,01° | 0,002° | ±0,01° (bitta chiziq) |
| `fringeMicrometer` — o'lchov mikroskopi / ekran mikrometri | 0,01 mm | 0,01 mm | ±0,03 mm (bitta o'rnatish) |
| `photometer` — fotodiod + 4½ xonali o'lchagich (20 000 hisob, avto-diapazon), % da | diapazonga qarab 0,00001…0,01 % | 0,05 % (nisbiy) | 0,125 % + ½ raqam |

### 1. Yorug'lik qaytishi (`light-reflection`, simulyatsiya: `ReflectionSimulation`)
**Model va formulalar.** Qaytish qonuni θr = θi (normaldan). Ko'zgu uchun qaytarish koeffitsiyenti 0,95 (burchakka bog'liq emas), shisha va suv uchun qutblanmagan yorug'lik Frenel formulalari: R = (Rs + Rp)/2, rs = (n₁cosθi − n₂cosθt)/(n₁cosθi + n₂cosθt), rp = (n₂cosθi − n₁cosθt)/(n₂cosθi + n₁cosθt). Tik tushishda R = ((n₁ − n₂)/(n₁ + n₂))² (`normal_reflectance`).

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Shisha (maktab qiymati) | n = 1,5 (N-BK7: 1,51673 589,3 nm da) | https://media.schott.com/api/public/content/38cbbe876d324e03b1881d33f3d26635 |
| Suv, 20 °C, 589 nm | n = 1,333 (IAPWS: 1,3330) | https://iapws.org/relguide/Rindex.html , https://en.wikipedia.org/wiki/List_of_refractive_indices |
| Kumush/alyuminiy ko'zgu | R ≈ 0,95 (Al ≈ 0,92, Ag ≈ 0,95–0,98 ko'rinadigan sohada) | https://en.wikipedia.org/wiki/Mirror |

**Asboblar, aniqlik va noaniqlik.** Qaytgan nur burchagi transportirda o'qiladi: bo'linma 0,5°, σ = 0,1°, maksimal xato ±0,5°. Vazifa xatoliklari: α = 45° → 2,5 % (±1,125°; avval 1 % = ±0,45° maktab transportiri aniqligidan kichik edi), α = 70° → 1,5 % (±1,05°). «Shishadan qaytish» hisob vazifasi 1 % (shovqinsiz kalkulyator).

**Standart sozlamalarda kutilgan natijalar.** α = 45°, ko'zgu → «Qaytish burchagi» = 45,0° (qaytgan ulush 95,0 %). Shisha: 45° da R = 5,02 %; suv: 45° da R = 2,79 %. Tik tushish (havo–shisha) R = 4,00 %.

**Soddalashtirishlar.** Ko'zgu qaytarishi burchakka bog'liq emas deb olingan; nur dastasi kengligi va disk markazlashtirish xatosi hisobga olinmagan.

### 2. Yorug'lik sinishi (`light-refraction`, simulyatsiya: `RefractionSimulation`)
**Model va formulalar.** Snell qonuni n₁sin α = n₂sin γ; n₁sin α/n₂ > 1 bo'lsa to'liq ichki qaytish (singan nur yo'q, qaytish burchagi o'lchanadi). Chegaraviy burchak sin α₀ = n₂/n₁ (faqat n₁ > n₂). Qaytgan ulush — Frenel formulalari. Teskari masala n₂ = n₁sin α/sin γ (`refractive_index_from_angles`).

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Havo | 1,000293 (vazifalarda 1,0; farq 0,03 %) | https://en.wikipedia.org/wiki/List_of_refractive_indices |
| Suv | 1,333 | https://iapws.org/relguide/Rindex.html |
| Shisha (maktab) / N-BK7 | 1,5 / 1,51673 | https://media.schott.com/api/public/content/38cbbe876d324e03b1881d33f3d26635 |
| Akril (PMMA) | 1,49 | https://en.wikipedia.org/wiki/List_of_refractive_indices |
| Flint F2 | 1,62004 (n_d) | SCHOTT katalogi (yuqorida) |
| Olmos | 2,417 | https://en.wikipedia.org/wiki/List_of_refractive_indices |

**Asboblar, aniqlik va noaniqlik.** Singan nur burchagi transportirda: 0,5°, σ = 0,1°, ±0,5°. Vazifa xatoliklari (avval hammasi 1 %): 28,126° → 4 % (±1,13°), 35,264° → 3 % (±1,06°), 48,59° → 2,5 % (±1,21°). Chegaraviy burchak (41,81°) va n₂ = 1,4998 hisob vazifalari 1 % da qoldi. 5-vazifa matnida γ = 28,13° Snell bo'yicha aniq qiymat ekani aniq yozildi (transportir odatda 28,0° ko'rsatadi).

**Standart sozlamalarda kutilgan natijalar.** n₁ = 1,00, n₂ = 1,50, α = 45° → γ = 28,126°, o'lchov 28,0° (ba'zan 28,5°); qaytgan ulush 5,02 %. n₁ = 1,5 → n₂ = 1: α₀ = 41,81°, α = 30° da γ = 48,59°, α = 45° da to'liq ichki qaytish. Olmos–havo α₀ = 24,44°, suv–havo 48,61°.

**Soddalashtirishlar.** n slayderi 0,1 qadamli (suv 1,333 ni aniq qo'yib bo'lmaydi, 1,3 qo'yiladi); dispersiya yo'q (monoxromatik nur); yarim silindr sirtidagi ikkinchi sinish hisobga olinmagan (real asbobda nur yarim silindr markazidan o'tadi, shuning uchun bu to'g'ri).

### 3. Prizma va dispersiya (`prism-dispersion`, simulyatsiya: `DispersionSimulation`)
**Model va formulalar.** Prizma shishasi — haqiqiy SCHOTT N-BK7, sindirish ko'rsatkichi katalogdagi Sellmeyer formulasi bilan: n²(λ) − 1 = Σ Bᵢλ²/(λ² − Cᵢ), λ µm da (`bk7Index`, `sellmeierIndex`). Nur natriy D chizig'i (589,3 nm) uchun eng kichik og'ish holatida tushadi: θ₁ = arcsin(n_D sin(A/2)); har bir rang uchun aniq og'ish δ = θ₁ + θ₂' − A (ikki yoqda Snell). Δδ = δ(400 nm) − δ(700 nm) — burchak dispersiyasi. δ_min = 2arcsin(n sin(A/2)) − A (`prism_min_deviation`). Ikki hadli Koshi formulasi n = A + B/λ² (A = 1,5046, B = 0,00420 µm²) nazariya va kalkulyatorda (`cauchy_index`, `cauchy_dispersion`) qoldi — u 400–700 nm da Sellmeyerdan 1,2·10⁻⁴ dan kam farq qiladi.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| N-BK7 Sellmeyer B₁, B₂, B₃ | 1,03961212; 0,231792344; 1,01046945 | https://media.schott.com/api/public/content/38cbbe876d324e03b1881d33f3d26635 |
| N-BK7 Sellmeyer C₁, C₂, C₃ (µm²) | 0,00600069867; 0,0200179144; 103,560653 | o'sha |
| N-BK7 katalog: n(404,7) / n(486,1) / n(587,6) / n(589,3) / n(656,3) / n(706,5) | 1,53024 / 1,52238 / 1,51680 / 1,51673 / 1,51432 / 1,51289 (Sellmeyer bilan 5 xonagacha mos — testda tekshiriladi) | o'sha |
| n_F − n_C, ν_d | 0,008054; 64,17 | o'sha |
| Koshi BK7: A, B | 1,5046; 0,00420 µm² | https://en.wikipedia.org/wiki/Cauchy%27s_equation |
| Natriy D₂ / D₁ (havoda) | 588,995 / 589,592 nm (o'rtacha 589,3 nm) | https://physics.nist.gov/PhysRefData/Handbook/Tables/sodiumtable2.htm |

**Tuzatishlar.** Avval simulyatsiya Koshi formulasini «haqiqiy shisha» deb ishlatardi: Δn = 0,017679 → endi N-BK7: **0,017785** (+0,6 %); Δδ(60°) 1,5673° → **1,5765°**; Δδ(40°) 0,81219° → **0,81703°**. Δn vazifasining tekshiruv formulasi `cauchy_dispersion` → yangi `bk7_dispersion` (calculator.js + formulas.json, tajriba formulalari ro'yxatiga qo'shildi). `prism_angular_dispersion` endi N-BK7 bilan hisoblaydi.

**Asboblar, aniqlik va noaniqlik.** Og'ish burchaklari goniometr-spektrometrda: 0,01°, σ = 0,002°, har bir chiziq ±0,01°; Δδ ikki ko'rsatishning ayirmasi → ±0,02°. Vazifa xatoliklari: A = 60° → 3 % (±0,047°), A = 40° → 5 % (±0,041°) (avval 1 % = ±0,008° — 1′ li spektrometr bilan erishib bo'lmaydi). Δn — shisha katalogidan (shovqinsiz), 6 kasr xona bilan, xatolik 1 %. Simulyatsiya alohida «Og'ish burchagi δ_qiz …» va «Og'ish burchagi δ_bin …» o'lchovlarini ham chiqaradi.

**Standart sozlamalarda kutilgan natijalar.** A = 60°: tushish burchagi 49,32°, n(700) = 1,51306, n(400) = 1,53085, Δn = 0,017785; δ_qiz = 38,319°, δ_bin = 39,896°, Δδ = 1,5765° (o'lchov masalan 38,32°/39,90° → 1,58°). A = 40°: Δδ = 0,81703°. A = 90°: n·sin45° > 1 — nur 2-yoqda to'liq ichki qaytadi. δ_min(n = 1,5167, A = 60°) = 38,638°.

**Soddalashtirishlar.** Nur ingichka, prizma ideal tekis yoqli; harorat 20 °C (dn/dT hisobga olinmagan, BK7 da ~2·10⁻⁶ 1/K); 400 va 700 nm «chiziqlari» spektrdan ajratib olingan monoxromatik nurlar deb qaraladi.

### 4. Yig'uvchi linza (`convex-lens`, simulyatsiya: `ConvexLensSimulation`)
**Model va formulalar.** Yupqa linza 1/F = 1/d + 1/f ⇒ f = dF/(d − F); Γ = −f/d; D = 1/F (dptr). d = F da tasvir cheksizlikda: simulyatsiya «1/d' = 0» (1/cm) chiqaradi. Uchta asosiy nur `thinLensPrincipalRays` bilan chiziladi va hisoblangan tasvir nuqtasidan aniq o'tadi.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Yupqa linza formulasi, ishoralar | 1/F = 1/d + 1/f, haqiqiy musbat | https://en.wikipedia.org/wiki/Thin_lens |

**Asboblar, aniqlik va noaniqlik.** Optik skamya 1 mm shkala; tasvir ekranni eng aniq tasvirgacha surib (haqiqiy) yoki parallaks usulida (mavhum) topiladi: σ = 1 mm, ±3 mm. Natija 1 mm gacha (0,1 cm). Vazifa xatoliklari (avval 1 %): f = 16,667 cm → 4 %, 20 cm → 3,5 %, 30 cm → 2,5 %, −40 cm → 2 %. Kattalashtirish (hisob) 1 %.

**Standart sozlamalarda kutilgan natijalar.** F = 10 cm, d = 25 cm, h = 3 cm → f = 16,667 cm (o'lchov 16,6–16,7 cm), Γ = −0,667, tasvir balandligi 2,00 cm, haqiqiy, teskari, kichraygan. d = 20 → f = 20 (Γ = −1); d = 15 → 30 (Γ = −2); d = 8 → −40 (Γ = +5, mavhum). D = 10 dptr.

**Soddalashtirishlar.** Yupqa linza, paraksial nurlar; sferik va xromatik aberratsiya yo'q; fokuslash noaniqligi tasvir masofasiga bog'liq emas deb olingan (real fokus chuqurligi tasvir kattalashganda ortadi).

### 5. Sochuvchi linza (`concave-lens`, simulyatsiya: `ConcaveLensSimulation`)
**Model va formulalar.** Xuddi shu formula F < 0 bilan: haqiqiy buyum uchun f < 0 (mavhum), 0 < Γ < 1 (to'g'ri, kichraygan).

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Yupqa linza formulasi | 1/F = 1/d + 1/f, F < 0 | https://en.wikipedia.org/wiki/Thin_lens |

**Asboblar, aniqlik va noaniqlik.** Mavhum tasvir o'rni optik skamyada parallaks usulida: σ = 1 mm, ±3 mm. Vazifa xatoliklari (avval 1 % = ±1 mm, real usul bilan erishib bo'lmaydi): −10 cm → 6,5 %, −12 cm → 5,5 %, −15 cm → 4,5 %. Kattalashtirish (hisob) 1 %.

**Standart sozlamalarda kutilgan natijalar.** F = −15 cm, d = 30 cm → f = −10,0 cm, Γ = +0,333; d = 60 → −12,0 cm; F = −30, d = 30 → −15,0 cm. D = −6,67 dptr.

**Soddalashtirishlar.** Yupqa linza, paraksial; real laboratoriyada sochuvchi linza fokusi ko'pincha yig'uvchi linza bilan juftlab o'lchanadi — bu yerda parallaks usuli modellashtirilgan.

### 6. Sferik ko'zgular (`curved-mirrors`, simulyatsiya: `CurvedMirrorSimulation`)
**Model va formulalar.** F = R/2 (botiq +, qavariq −), 1/F = 1/d + 1/f, Γ = −f/d; f > 0 — ko'zgu oldidagi haqiqiy, f < 0 — ko'zgu orqasidagi mavhum tasvir. Nurlar (`mirrorPrincipalRays`) uchlik tekisligida qaytariladi (paraksial).

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Ko'zgu formulasi | 1/F = 1/d + 1/f, F = R/2 | https://en.wikipedia.org/wiki/Curved_mirror |

**Asboblar, aniqlik va noaniqlik.** Optik skamya, σ = 1 mm, ±3 mm. Vazifa xatoliklari (avval 1 %): botiq d = 40 → 2 %, d = 60 → 2,5 %, d = 15 (f = −60) → 1,5 %, qavariq (f = −13,333) → 5 %. Kattalashtirish (hisob) 1 %.

**Standart sozlamalarda kutilgan natijalar.** Botiq, F = 20 cm, d = 40 cm → f = 40,0 cm, Γ = −1 (o'lchov 39,9–40,1 cm). d = 60 → 30 cm (Γ = −0,5); d = 15 → −60 cm (Γ = +4); qavariq |F| = 20, d = 40 → −13,333 cm (Γ = +0,333).

**Soddalashtirishlar.** Paraksial (sferik aberratsiya yo'q), buyum balandligi 8 cm sxematik.

### 7. Yorug'lik interferensiyasi (`light-interference`, simulyatsiya: `InterferenceSimulation`)
**Model va formulalar.** Ekrandagi intensivlik (Fraungofer, aniq geometriya sin θ = y/√(y² + L²)): I/I₀ = cos²(πd sin θ/λ)·(sin β/β)², β = πa sin θ/λ, a = 0,02 mm. Polosalar oralig'i Δy = λL/d; λ = Δy·d/L. O'lchov: mikroskop chizig'i −N/2 va +N/2 yorug' polosalar markaziga qo'yiladi, Δy = (x₊ − x₋)/N; N = 2·min(5, ⌈d/a⌉ − 1) (polosalar markaziy difraksiya maksimumi ichida bo'lishi uchun; d = 0,1 mm da N = 8, aks holda 10). Simulyatsiya «N ta oraliq kengligi (mikroskop)» (mm) va «Interferensiya oralig'i» (mm, 3 kasr) ni chiqaradi.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Yung formulasi | Δy = λL/d | https://en.wikipedia.org/wiki/Double-slit_experiment |
| Lazer manbalari (ma'lumot uchun) | He–Ne 632,8 nm (havoda 632,816; vakuumda 632,991), yashil DPSS 532 nm, qizil diod 650 nm, binafsha diod 405 nm | https://en.wikipedia.org/wiki/Helium%E2%80%93neon_laser |

**Asboblar, aniqlik va noaniqlik.** O'lchov mikroskopi 0,01 mm, σ = 0,01 mm har bir o'rnatishga (±0,03 mm); Δy xatosi ≤ 2·0,03/N + 0,0005 mm. Vazifa xatoliklari: Δy = 1,2 mm → 1,5 % (avval 1 %), 0,8 mm → 2 % (avval 1 %), 3,0 mm va 2,4 mm → 1 % (o'zgarmagan). λ hisob vazifasi 1 %.

**Standart sozlamalarda kutilgan natijalar.** λ = 600 nm, d = 0,5 mm, L = 1 m → Δy = 1,200 mm, N = 10, 10·Δy = 12,00 mm (o'lchovlar masalan 12,01 / 12,03 / 11,98 mm → 1,201 / 1,203 / 1,198 mm). λ = 400 → 0,800 mm; d = 0,2 → 3,000 mm; L = 2 → 2,400 mm.

**Soddalashtirishlar.** Tekis to'lqin, kogerent nur, ideal bir xil tirqishlar; ekran ko'rinishi ±10 mm (20 px = 1 mm) — mikroskop o'lchovi ekran kadridan tashqaridagi polosalarni ham "ko'radi".

### 8. Yorug'lik difraksiyasi (`light-diffraction`, simulyatsiya: `DiffractionSimulation`)
**Model va formulalar.** Bitta tirqish: I/I₀ = (sin β/β)², β = πa sin θ/λ; minimumlar a sin φ = mλ; ekranda y₁ = L tg φ₁ (`singleSlitMinimumPosition`), L = 1 m. O'lchov: mikroskop chizig'i ikkala birinchi minimumga qo'yiladi, 2y₁ = x₊ − x₋, so'ng φ₁ = arctg(y₁/L). Simulyatsiya «Minimumlar orasidagi masofa 2y₁ …» (mm) va «Birinchi minimum burchagi …» (°, 4 kasr) ni chiqaradi. Markaziy maksimum kengligi b = 2L tg φ₁ (`central_maximum_width`).

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Bitta tirqish minimumlari | a sin φ = mλ | https://en.wikipedia.org/wiki/Diffraction#Single-slit_diffraction |

**Asboblar, aniqlik va noaniqlik.** O'lchov mikroskopi 0,01 mm, σ = 0,01 mm, ±0,03 mm har bir minimum; y₁ xatosi ≤ 0,03 mm ⇒ φ xatosi ≤ 0,03 mm / 1 m = 0,0017°. Vazifa xatoliklari (avval 1 %): 0,34378° → 1,5 %, 0,22918° → 2 %, 0,17189° → 2,5 %. Hisob vazifasi (b = 12,000 mm) 1 %.

**Standart sozlamalarda kutilgan natijalar.** λ = 600 nm, a = 0,10 mm → φ₁ = 0,34378°, y₁ = 6,0001 mm, 2y₁ = 12,000 mm (o'lchov 11,99–12,01 mm → 0,3435–0,3441°). λ = 400 → 0,22918° (y₁ = 4,000 mm); a = 0,20 → 0,17189° (y₁ = 3,000 mm).

**Soddalashtirishlar.** Fraungofer yaqinlashuvi (L = 1 m ≫ a²/λ = 1,7 cm — to'g'ri); L aniq deb olingan (lentali o'lchov ±1 mm = 0,1 % — barcha xatoliklardan ancha kichik).

### 9. Yorug'lik polyarizatsiyasi (`polarization`, simulyatsiya: `PolarizationSimulation`)
**Model va formulalar.** Qutblanmagan nur (I₀/2 s + I₀/2 p) havo–shisha (n = 1,5) chegarasidan qaytadi: Is = I₀Rs/2, Ip = I₀Rp/2 (Frenel). Analizator (Malyus, har bir tashkil etuvchi uchun): I(φ) = Is cos²φ + Ip sin²φ, φ — s-yo'nalishdan. Bryuster tg α_B = n₂/n₁ = 56,31°. Qutblanish darajasi o'lchov kabi: P = (I_max − I_min)/(I_max + I_min), I_max = I(0°), I_min = I(90°).

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Shisha (maktab) | n = 1,5 (N-BK7 uchun α_B = 56,60°) | https://en.wikipedia.org/wiki/Brewster%27s_angle |
| Frenel formulalari | Rs, Rp | https://en.wikipedia.org/wiki/Fresnel_equations |
| Malyus qonuni | I = I₀cos²φ | https://en.wikipedia.org/wiki/Polarizer |

**Asboblar, aniqlik va noaniqlik.** Fotodiod + 4½ xonali o'lchagich, % da (20 000 hisob, diapazonlar 0,2/2/20/200 %), nisbiy σ = 0,05 % (barqarorlashtirilgan manba). Xatolik: I/I₀ = 7,2908 % → ±0,0096 %, P(30°) → ±0,12 % (absolyut), P(56°) → ±0,01 % (2 kasrgacha yaxlitlash bilan). Vazifa xatoliklari 1 % (o'zgarmagan; qoida bajariladi). Simulyatsiya qo'shimcha «Analizator φ=0°: I_max/I₀» va «Analizator φ=90°: I_min/I₀» o'lchovlarini chiqaradi.

**Standart sozlamalarda kutilgan natijalar.** α = 0°: R = 4,00 %, Is = Ip = 2,00 %, P = 0. α = 56°: P = 99,986 %, I(φ = 0°)/I₀ = 7,2908 % (o'lchov 7,28–7,30 %), I_min = 0,00052 %. α = 30°: Is = 2,8898 %, Ip = 1,2625 %, P = 39,192 %. Malyus: I₀ = 100, φ = 60° → 25.

**Soddalashtirishlar.** Analizator ideal (yutilish va ekstinksiya koeffitsiyenti hisobga olinmagan), shisha yutmaydi, orqa sirtdan qaytish yo'q; real He–Ne lazer quvvati ~1 % tebranadi — bu yerda manba barqarorlashtirilgan deb qabul qilingan.


## 10. To'lqinlar

Fizika moduli: `resources/js/Components/Lab/physics/waves.js` (SI birliklar: m, s, Hz, N, kg; istisno nomida ko'rsatilgan, masalan `photonEnergyEV`). c, h, e, R konstantalari `constants.js` dan olinadi (2019 SI aniq qiymatlari, CODATA 2022) va eski nomlar bilan qayta eksport qilinadi. Barcha harakatlar vaqtning yopiq (analitik) funksiyasi — kadr chastotasiga bog'liq emas.

Ekrandagi har bir uzunlikning fizik masshtabi ko'rsatilgan (piksel fizik birlik sifatida ishlatilmaydi). O'lchov asboblari `WAVE_INSTRUMENTS` da (umumiy `instruments.js` katalogidan). Har bir ko'rsatish = aniq model qiymati + kesilgan normal sochilish (|z| ≤ 2,5σ), so'ng asbob bo'linmasiga yaxlitlanadi: |xato| ≤ 2,5σ + bo'linma/2. Sochilish simulyatsiya nomi va sozlamalardan seed qilinadi — bir xil sozlamadagi k-chi o'lchov har doim bir xil son. «Maksimal xato ≤ ½ · vazifa xatoligi» qoidasi `Simulations/__tests__/waves.e2e.test.js` da har bir o'lchov vazifasi uchun isbotlanadi (32 marta takroriy o'lchov, server qoidasi — nom prefiksi, birlik, xatolik — qayta tekshiriladi).

| Asbob (`WAVE_INSTRUMENTS`) | Bo'linma | σ | Maksimal xato | Qayerda |
|---|---|---|---|---|
| `lightGateTimer` — elektron sekundomer (optik datchiklar / ossillograf) | 1 ms | 0,5 ms | ±1,75 ms | shnurdagi to'lqin tezligi, 10 ta urish vaqti |
| `tape` — po'lat ruletka / metr chizg'ich | 1 mm | 0,3 mm | ±1,25 mm | tovush λ, to'lqinli vannada Nλ |
| `displacementSensor` — masofa datchigi | 0,1 mm | 0,03 mm | ±0,125 mm | rezonans amplitudasi |
| `frequencyCounter` — chastotomer / generator displeyi | 0,1 Hz | 0 | ±0,05 Hz | Melde garmonikalari, Dopler |

Umumiy manbalar:
- SI aniq konstantalari, CODATA 2022 (NIST): https://physics.nist.gov/cuu/Constants/Table/allascii.txt
- Tovush tezligi: https://en.wikipedia.org/wiki/Speed_of_sound ; O. Cramer, J. Acoust. Soc. Am. 93, 2510 (1993), formula koeffitsiyentlari: https://gsd.ime.usp.br/yili/SpeedOfSound/Speed.html , https://sengpielaudio.com/calculator-airpressure.htm
- ISO 21348:2007 spektral kategoriyalar (qoralama matni): https://www.acttr.com/images/pdf/ISO_DIS_21348.pdf , https://cdn.standards.iteh.ai/samples/39911/7088f5099b2343c3810f06b6ca9bc35b/ISO-21348-2007.pdf
- Dopler effekti (ishoralar): https://en.wikipedia.org/wiki/Doppler_effect
- Majburiy so'nuvchi tebranishlar: https://en.wikipedia.org/wiki/Harmonic_oscillator#Driven_harmonic_oscillators , https://en.wikipedia.org/wiki/Q_factor
- Kapillyar–gravitatsion to'lqinlar dispersiyasi: https://en.wikipedia.org/wiki/Capillary_wave

### 1. To'lqin xususiyatlari (`wave-properties`, simulyatsiya: `WaveBasicsSimulation`)
**Model va formulalar.** Yuguruvchi ko'ndalang to'lqin y(x, t) = A·sin(kx − ωt), k = 2π/λ, ω = 2πν; tepalik x = λ/4 + v·t (mod λ) bo'ylab +x tomonga siljiydi. v = λ·ν, T = 1/ν. Tezlik real usulda o'lchanadi: shnur bo'ylab ikkita optik datchik A (x = 2 m) va B (x = 7 m), s = 5,00 m; tepalik A → B vaqti t = s/v elektron sekundomerda o'qiladi, «To'lqin tezligi» = s/t (4 ta qiymatli raqam), birligi m/s. O'lchov nomida s va t ko'rsatiladi: `To'lqin tezligi (s = 5.00 m, t = 1.667 s)`.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Masshtab | 1 px = 1 cm (900 px = 9 m rezina shnur) | apparatura (tanlangan) |
| Datchiklar oralig'i | s = 5,00 m | apparatura |
| Shnurdagi tezlik oralig'i | 0,25 … 6 m/s (λ = 0,5…2 m, ν = 0,5…3 Hz) — yumshoq rezina shnur/«slinki» uchun real | https://en.wikipedia.org/wiki/Slinky |

**Asboblar, aniqlik va noaniqlik.** Elektron sekundomer 1 ms, σ = 0,5 ms (±1,75 ms). Tezlikning maksimal nisbiy xatosi: t = 5 s da 0,085 %, t = 1,667 s da 0,155 %, eng tez holatda (v = 6 m/s, t = 0,833 s) 0,26 % — hammasi ½·1 % dan kichik. Vazifa xatoligi 1 % (o'zgarmagan).

**Standart sozlamalarda kutilgan natijalar.** ν = 1 Hz, λ = 1,0 m, A = 40 cm → v = 1,00 m/s, t = 5,000 s (o'lchov 0,9997…1,0004 m/s). Vazifalar: (1 Hz; 1,0 m) → 1,0 m/s; (2 Hz; 1,5 m) → 3,0 m/s; (0,5 Hz; 2,0 m) → 1,0 m/s; davr ν = 2 Hz → 0,5 s. Avval vazifalar px/s da edi (100, 300, 100 px/s) — endi m/s.

**Soddalashtirishlar.** Haqiqiy muhitda tezlik muhit (taranglik, chiziqli zichlik) bilan aniqlanadi va ν o'zgarsa λ o'zgaradi; simulyatsiyada v = λν bog'lanishni ko'rsatish uchun λ va ν alohida tanlanadi. So'nish va uchdan qaytish yo'q (cheksiz shnur). Amplituda 10…60 cm.

### 2. Turg'un to'lqinlar (`standing-waves`, simulyatsiya: `StandingWavesSimulation`)
**Model va formulalar.** Melde tajribasi: ikki uchi mahkamlangan L = 1,000 m ip, y(x, t) = A·sin(nπx/L)·cos(2πνₙt); νₙ = n·v/(2L) = n·ν₁, λₙ = 2L/n, tugunlar xₖ = kL/n (n + 1 ta), do'ngliklar (k + ½)L/n (n ta). Ipdagi tezlik v = √(T/μ), ya'ni tanlangan ν₁ taranglikka mos: T = μv² = 4μL²ν₁² (ekranda ko'rsatiladi). O'lchov: n-garmonika rezonansidagi generator chastotasi chastotomerda — `Garmonika chastotasi (n = 3)`, Hz. (Avvalgi nom «Harmonik 3 chastotasi» o'zbekcha emas edi; seed prefiksi endi «Garmonika chastotasi».)

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Ip uzunligi | L = 1,000 m | apparatura |
| Chiziqli zichlik | μ = 1,0 g/m (ingichka ip/rezina) | https://en.wikipedia.org/wiki/Melde%27s_experiment |
| ν₁ oralig'i | 5…50 Hz (v = 10…100 m/s, T = 0,1…10 N, ya'ni 10 g…1 kg yuk) | https://en.wikipedia.org/wiki/String_vibration |

**Asboblar, aniqlik va noaniqlik.** Chastotomer 0,1 Hz, sochilishsiz (generator chastotasi kvarts bilan barqaror; νₙ = n·ν₁ har doim 0,5 Hz ga karrali, yaxlitlash xatosi 0). Vazifa xatoligi 1 %.

**Standart sozlamalarda kutilgan natijalar.** ν₁ = 20 Hz, n = 1 → 20,0 Hz, λ₁ = 2,000 m, v = 40,0 m/s, T = 1,60 N. Vazifalar: ν₁ = 20 Hz, n = 3 → 60,0 Hz (4 tugun, 3 do'nglik); λ₃ = 0,66667 m; ν₁ = 15 Hz, n = 4 → 60,0 Hz; v = λ₃ν₃ = 40,0 m/s. Avval ν₁ = 2 Hz va 1,5 Hz (6 Hz, v = 4 m/s) edi — 1 m li ipda bunday tezlik uchun T = 0,016 N kerak, ip osilib qoladi; Melde qurilmasida real chastotalar o'nlab Hz.

**Soddalashtirishlar.** Ideal egiluvchan ip (bikrlik, so'nish, vibrator uchining siljishi hisobga olinmagan); rezonans aniq νₙ da. Amplituda chizmada 10× kattalashtirilgan, tez tebranishlar sekinlashtirib ko'rsatiladi.

### 3. Tovush to'lqinlari (`sound-waves`, simulyatsiya: `SoundWavesSimulation`)
**Model va formulalar.** Bo'ylama to'lqin s(x, t) = s₀·sin(kx − ωt), bosim Δp = −B·∂s/∂x ∝ −cos(kx − ωt) (siljishdan 90° farq). Havodagi tovush tezligi harorat bo'yicha (yangi slayder −20…40 °C, standart 20 °C): v(t) = 343·√((273,15 + t)/293,15) m/s — ideal gaz qonuni v ∝ √T, 20 °C dagi 343 m/s ga bog'langan. λ = v/ν. O'lchov: mikrofon karnaydan siljitiladi va ossillografda signal yana bir fazaga kelgan masofa ruletkada o'qiladi — `To'lqin uzunligi (f = 440 Hz, t = 20 °C)`, m.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Havoda tovush tezligi, 20 °C (laboratoriya qiymati) | 343 m/s | darslik |
| Ideal gaz √(γRT/M), 293,15 K, γ = 1,4, M = 28,9647 g/mol | 343,2 m/s | https://en.wikipedia.org/wiki/Speed_of_sound |
| Cramer (1993), quruq havo, 101,325 kPa, 400 ppm CO₂, 20 °C | 343,36 m/s (50 % namlikda ≈ 344,0) | https://gsd.ime.usp.br/yili/SpeedOfSound/Speed.html |
| 0 °C da (model / adabiyot) | 331,1 / 331,3…331,5 m/s (−0,1 %) | https://en.wikipedia.org/wiki/Speed_of_sound |
| Suv, 20 °C | ≈ 1481 m/s (nazariyada ≈ 1480) | https://en.wikipedia.org/wiki/Speed_of_sound |
| Po'lat | bo'ylama ≈ 5900–6000 m/s, yupqa sterjen √(E/ρ) ≈ 5100 m/s (avval «≈ 5000 m/s» edi) | https://en.wikipedia.org/wiki/Speed_of_sound |
| Havoning molyar massasi | 28,9647 g/mol | https://www.engineeringtoolbox.com/molecular-mass-air-d_679.html |

**Asboblar, aniqlik va noaniqlik.** Ruletka 1 mm, σ = 0,3 mm (±1,25 mm). Nisbiy xato: 440 Hz → 0,16 %, 1000 Hz → 0,36 %, 100 Hz → 0,04 % (½·1 % = 0,5 % dan kichik). Chastota slayderi qadami 50 → 10 Hz qilindi: avval 440 Hz faqat boshlang'ich holatda bor edi, slayder qo'zg'atilgach unga qaytib bo'lmasdi.

**Standart sozlamalarda kutilgan natijalar.** 440 Hz, 20 °C → λ = 0,77955 m (o'lchov 0,778…0,781 m), T = 2,27 ms. Vazifalar (20 °C): 440 Hz → 0,77955 m; 1000 Hz → 0,343 m; 100 Hz → 3,43 m; davr 440 Hz → 0,0022727 s. 0 °C da 440 Hz → 0,7525 m.

**Soddalashtirishlar.** 343 m/s Cramer qiymatidan 0,1 % kichik (namlik, CO₂ hisobga olinmagan) — 1 % xatolik ichida. Akustik qaytishlar, karnay yaqin maydoni yo'q; animatsiya 500× sekinlashtirilgan (1 m = 200 px).

### 4. Rezonans (`resonance`, simulyatsiya: `ResonanceSimulation`)
**Model va formulalar.** m·x″ + b·x′ + k·x = F₀·sin ωt, ζ = b/(2mω₀). Barqaror rejim x = Aₛₜ·R·sin(ωt − δ), r = ν/ν₀, R = 1/√((1 − r²)² + (2ζr)²), δ = atan2(2ζr, 1 − r²). r = 1 da R = 1/(2ζ) = Q; amplituda cho'qqisi ν_rez = ν₀√(1 − 2ζ²) (ζ < 1/√2), balandligi 1/(2ζ√(1 − ζ²)); yarim quvvat kengligi Δν ≈ 2ζν₀. «Rezonans» belgisi A ≥ A_max/√2 bo'lganda. O'lchov: statik siljish Aₛₜ = F₀/k = 10 mm, barqaror amplituda A masofa datchigida o'qiladi, A/Aₛₜ yoziladi — `Amplituda (f = 2.0 Hz, A = 50.0 mm)`, birlik `A/Aₛₜ`.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Statik siljish | Aₛₜ = 10 mm (ekranda 1 px = 1 mm) | apparatura |
| ν₀, ζ oralig'i | 1…4 Hz, 0,05…0,5 (prujinali mayatnik + magnit so'ndirgich) | https://en.wikipedia.org/wiki/Damping |
| Formulalar | R(r, ζ), ν_rez, Q = 1/(2ζ) | https://en.wikipedia.org/wiki/Harmonic_oscillator#Driven_harmonic_oscillators , https://en.wikipedia.org/wiki/Q_factor |

**Asboblar, aniqlik va noaniqlik.** Masofa datchigi 0,1 mm, σ = 0,03 mm (±0,125 mm). Nisbiy xato: R = 5 (A = 50 mm) → 0,25 %; R = 10 (100 mm) → 0,125 %; R = 1,3216 (13,2 mm) → 0,95 %. Shuning uchun «Rezonansdan uzoqda» vazifasining xatoligi 1 % → 2 % (kichik amplitudani 0,1 mm datchik bilan 0,5 % dan aniq o'lchab bo'lmaydi; real laboratoriyada ham shunday). Qolgan vazifalar 1 %.

**Standart sozlamalarda kutilgan natijalar.** ν₀ = 2 Hz, ν = 2 Hz, ζ = 0,1 → A/Aₛₜ = 5,00 (A = 50,0 mm), δ = 90°, Q = 5, ν_rez = 1,9799 Hz. Vazifalar: 5,0; ν = 1 Hz → 1,3216; ζ = 0,05 → 10,0; ν_rez = 1,9799 Hz.

**Soddalashtirishlar.** O'tish jarayoni (barqaror rejimga ~ 1/(ζω₀) vaqt) ko'rsatilmaydi — amplituda darhol barqaror qiymatda; chiziqli prujina, yopishqoq so'nish.

### 5. Dopler effekti (`doppler-effect`, simulyatsiya: `DopplerSimulation`)
**Model va formulalar.** ν′ = ν·(v + v_k)/(v − v_m), muhit tinch; v_k > 0 — kuzatuvchi manbaga yaqinlashadi, v_m > 0 — manba kuzatuvchiga yaqinlashadi (manfiy — uzoqlashadi). v_m ≥ v (tovush to'sig'i) yoki v_k < −v da formula yaroqsiz (NaN). Manba oldida λ = (v − v_m)/ν, orqasida (v + v_m)/ν; to'lqin frontlari: tₙ = nT da chiqarilgan front radiusi v(t − tₙ), markazi manbaning o'sha paytdagi joyida. M = |v_m|/v. O'lchov: kuzatuvchi yonidagi mikrofon → chastotomer, `Kuzatilgan chastota`, Hz.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Tovush tezligi | 343 m/s (20 °C) | yuqoridagi 3-tajriba |
| Dopler formulasi va ishoralar | ν′ = ν(v + v_k)/(v − v_m) | https://en.wikipedia.org/wiki/Doppler_effect |

**Asboblar, aniqlik va noaniqlik.** Chastotomer 0,1 Hz (10 s o'lchov oynasi), sochilishsiz: ±0,05 Hz = 0,01 % (vazifa xatoligi 1 %).

**Standart sozlamalarda kutilgan natijalar.** Hammasi tinch, ν = 440 Hz → 440,0 Hz, λ₀ = 78,0 cm. Vazifalar: v_m = +30 m/s → 482,17 Hz; −30 m/s → 404,61 Hz; v_k = +30 m/s → 478,48 Hz; ikkalasi +30 m/s → 524,35 Hz (hisob).

**Soddalashtirishlar.** Harakat kuzatuvchi–manba chizig'i bo'ylab (burchak yo'q), shamol yo'q. To'lqin frontlari sxematik (sekinlashtirilgan), lekin λ nisbati aniq.

### 6. To'lqin interferensiyasi (`wave-interference`, simulyatsiya: `WaveInterferenceSimulation`)
**Model va formulalar.** Ikki kogerent nuqtaviy manba (to'lqinli vanna): y = A·sin(kr₁ − ωt) + A·sin(kr₂ − ωt). Maksimum Δr = mλ, minimum Δr = (m + ½)λ (giperbolalar); tugun chiziqlari soni N = 2·⌈d/λ − ½⌉. λ = v/ν, v = 0,20 m/s. O'lchov: stroboskop yorug'ida chizg'ich N ta to'liq to'lqin uzunligi ustiga qo'yiladi (N ≤ 10, N·λ ≤ 30 cm), λ = (Nλ)/N — `To'lqin uzunligi (7λ = 28.0 cm)`, cm.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Masshtab | 1 cm = 20 px (vanna 40 × 25 cm) | apparatura |
| To'lqin tezligi | 20 cm/s (suv chuqurligi ≈ 4 mm). Dispersiya ω² = (gk + σk³/ρ)·tanh(kh), σ = 0,0728 N/m: 1 Hz → 19,8 cm/s, 5 Hz → 20,3 cm/s, 10 Hz → 21,3 cm/s (7 % ichida o'zgarmas) | https://en.wikipedia.org/wiki/Capillary_wave |
| Manbalar oralig'i | 2,5…20 cm | apparatura |

**Asboblar, aniqlik va noaniqlik.** Chizg'ich 1 mm, σ = 0,3 mm (±1,25 mm Nλ uchun), natija 4 ta qiymatli raqam. Nisbiy xato: λ = 4 cm (N = 7) → 0,50 %, λ = 2 cm (N = 10) → 0,68 %. Ikkala λ vazifasining xatoligi 1 % → 2 % (to'lqinli vannada λ ni proyeksiyadan o'lchash odatda ±2…3 %). Hisob vazifalari (tugun chiziqlari) — butun son, shovqinsiz.

**Standart sozlamalarda kutilgan natijalar.** ν = 5 Hz, d = 10 cm → λ = 4,00 cm, 7λ = 28,0 cm, d/λ = 2,50, 4 ta tugun chizig'i. Vazifalar: 5 Hz → 4,0 cm; 10 Hz → 2,0 cm; d = 10 cm, λ = 4 cm → 4; d = 10 cm, λ = 2 cm → 10. Avval px edi (80 px, 40 px; d = 200 px) — endi cm (tugun soni o'zgarmadi). Kalkulyatorda `nodal_lines` kirishlari px → cm.

**Soddalashtirishlar.** Dispersiya va amplitudaning masofa bilan kamayishi (1/√r) hisobga olinmagan; proyeksiya kattalashtirishi 1:1 deb olingan.

### 7. Elektromagnit spektr (`em-spectrum`, simulyatsiya: `EMSpectrumSimulation`)
**Model va formulalar.** ν = c/λ, E = hν = hc/λ, hc/e = 1239,84198 eV·nm (aniq). Har bir diapazon uchun misol to'lqin uzunligi — chegaralarning geometrik o'rtasi √(λmin·λmax) (ochiq diapazonlarda — chegara). «Foton energiyasi» — ma'lumotnoma ko'rsatkichi (asbob o'lchovi emas), 4 ta qiymatli raqam, eV.

**Konstantalar va jadval qiymatlari.**
| Diapazon | λ | ν | E | Manba |
|---|---|---|---|---|
| Radio | > 1 m | < 300 MHz | < 1,24 µeV | ITU / darslik |
| Mikroto'lqin | 1 mm – 1 m | 300 MHz – 300 GHz | 1,24 µeV – 1,24 meV | https://en.wikipedia.org/wiki/Microwave |
| Infraqizil | 760 nm – 1 mm | 300 GHz – 394 THz | 1,24 meV – 1,63 eV | ISO 21348 (IR 760 nm – 1 mm) |
| Ko'rinadigan | 380–760 nm | 394–789 THz | 1,63–3,26 eV | ISO 21348 (380 ≤ λ < 760 nm); CIE jadvallari 780 nm gacha |
| Ultrabinafsha | 10–380 nm | 789 THz – 30 PHz | 3,26–124 eV | ISO 21348: UV 100–400 nm, EUV 10–121 nm |
| Rentgen | 10 pm – 10 nm | 30 PHz – 30 EHz | 124 eV – 124 keV | darslik; ISO 21348: 1 pm – 10 nm |
| Gamma | < 10 pm | > 30 EHz | > 124 keV | darslik; ISO 21348: < 1 pm |
| c, h, e | 299 792 458 m/s; 6,626 070 15·10⁻³⁴ J·s; 1,602 176 634·10⁻¹⁹ C (aniq) | | | https://physics.nist.gov/cuu/Constants/Table/allascii.txt |

**Asboblar, aniqlik va noaniqlik.** Asbob yo'q (ma'lumotnoma qiymati), shovqin yo'q; 4 ta qiymatli raqam → ≤ 0,05 % yaxlitlash. Vazifa xatoligi 1 %.

**Standart sozlamalarda kutilgan natijalar.** Ko'rinadigan yorug'lik: λ = 537 nm, ν = 5,58·10¹⁴ Hz, E = 2,307 eV. Vazifalar: ko'rinadigan → 2,3071 eV; rentgen (λ = 0,316 nm) → 3920,7 eV; λ = 550 nm → ν = 5,4508·10¹⁴ Hz, E = 2,2543 eV. Diapazon jadvali tekshirildi va to'g'ri; o'zgartirilmadi.

**Soddalashtirishlar.** Rentgen/gamma chegarasi shartli (asli manba bo'yicha farqlanadi); UB/ko'rinadigan 380–400 nm ustma-ust sohasi ko'rinadiganga berilgan. Spektr chizig'i masshtabi shartli (logarifmik emas).

### 8. Urishlar hodisasi (`beats-phenomenon`, simulyatsiya: `BeatsSimulation`)
**Model va formulalar.** y = A·sin 2πν₁t + A·sin 2πν₂t = 2A·cos(π(ν₁ − ν₂)t)·sin(2πν̄t), ν̄ = (ν₁ + ν₂)/2; urishlar chastotasi ν_u = |ν₁ − ν₂|, davri T_u = 1/|ν₁ − ν₂|. O'lchov: ossillografdagi 10 ta to'liq urish vaqti t elektron sekundomerda, ν_u = 10/t (4 ta qiymatli raqam) — `Urishlar chastotasi (10 urish: t = 2.500 s)`, Hz. ν₁ = ν₂ da urish yo'q → 0 Hz.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| Chastotalar oralig'i | 400…500 Hz, qadam 2 Hz (kamerton/generator) | https://en.wikipedia.org/wiki/Beat_(acoustics) |

**Asboblar, aniqlik va noaniqlik.** Elektron sekundomer 1 ms, σ = 0,5 ms. Nisbiy xato: ν_u = 4 Hz (t = 2,5 s) → 0,12 %, ν_u = 10 Hz (t = 1 s) → 0,23 % (½·1 % dan kichik). Vazifa xatoligi 1 %.

**Standart sozlamalarda kutilgan natijalar.** 440/444 Hz → ν_u = 4 Hz (t₁₀ = 2,500 s), T_u = 0,25 s, ν̄ = 442 Hz. Vazifalar: 4,0 Hz; 450 Hz bilan → 10,0 Hz; T_u = 0,25 s.

**Soddalashtirishlar.** Teng amplitudali, sof sinusoidal tovushlar; ~15 Hz dan katta farqda quloq urishni emas, «g'adir-budurlik»ni sezadi (simulyatsiya baribir |ν₁ − ν₂| ni ko'rsatadi).


## 11. Atom va yadro fizikasi

Fizika moduli: `resources/js/Components/Lab/physics/atomic.js`. h, c, e, mₑ, a₀, α, R∞, R_H, 1 u = 931,494 103 72 MeV `constants.js` dan olinadi (CODATA 2022) va eski nomlar bilan qayta eksport qilinadi. Avvalgi versiyada mₑ, a₀, α, 1 u energiyasi CODATA 2018 qiymatlari edi, Ridberg doimiysi esa yaxlitlangan 1,097·10⁷ m⁻¹ va 13,6 eV edi.

Umumiy manbalar:
- CODATA 2022 (NIST): https://physics.nist.gov/cuu/Constants/Table/allascii.txt
- NIST Atomic Spectra Database (ASD v5): https://physics.nist.gov/PhysRefData/ASD/lines_form.html
- Havo–vakuum to'lqin uzunligi o'tkazish (Ciddor/Edlén formulasi): https://www.astro.uu.se/valdwiki/Air-to-vacuum%20conversion
- Chiqish ishi: H. B. Michaelson, J. Appl. Phys. 48, 4729 (1977), https://doi.org/10.1063/1.323539
- Yarim yemirilish davrlari: IAEA LiveChart / ENSDF, https://nds.iaea.org/relnsd/v1/data?fields=ground_states&nuclides=131i ; NUBASE2020, https://www-nds.iaea.org/amdc/ame2020/nubase_4.mas20.txt
- Atom massalari: AME2020, https://www-nds.iaea.org/amdc/ame2020/mass_1.mas20.txt
- U-235 bo'linish energiyasi: Ma et al., Phys. Rev. C 88, 014605 (2013), arXiv:1212.6625; ν̄: IAEA neutron standards, https://www-nds.iaea.org/standards/Reports/ThermalConstants.pdf
- Rentgen chiziqlari: NIST X-ray Transition Energies, https://physics.nist.gov/PhysRefData/XrayTrans/Html/search.html
- Kristall panjara doimiylari: Leybold P7.1.2.1, https://www.physics.wisc.edu/courses/home/spring2020/407/experiments/xray/P7121_E.PDF ; Handbook of Mineralogy (silvin), https://www.handbookofmineralogy.org/pdfs/sylvite.pdf ; Si — CODATA 2022 "lattice parameter of silicon".

Atom bo'limidagi tajribalarning ko'pchiligida vazifalar hisob-kitob (kalkulyator) vazifalari. Avval bu simulyatsiyalar (fotoeffekt, Bor modeli, emissiya spektrlari, radioaktiv yemirilish, rentgen difraksiyasi) hech qanday o'lchov chiqarmasdi. Endi ularning har birida «O'lchash» tugmasi bor: u haqiqiy laboratoriyada asbobdan o'qiladigan kattalikni (tormozlovchi kuchlanish, impulslar soni, Bregg burchagi, spektral chiziq) o'lchovlar paneliga yozadi, talaba hisob natijasini shu bilan solishtiradi. Vazifalar ro'yxati o'zgarmagan.

### 1. Fotoeffekt (`photoelectric-effect`, simulyatsiya: `PhotoelectricSimulation`)
**Model va formulalar.** Eynshteyn tenglamasi hν = W + KE_max; KE_max = hν − W, tormozlovchi kuchlanish U₀ = KE_max/e, chegaraviy chastota ν₀ = W/h, v_max = √(2·KE_max/mₑ). Fototok I = η·e·P/(hν), P — katoddagi yorug'lik quvvati (100 % = 1 mW), kvant chiqishi η = 10⁻³ (metallar uchun odatiy). hν ≤ W bo'lsa intensivlikdan qat'i nazar I = 0.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| h, e, mₑ | CODATA 2022 | https://physics.nist.gov/cuu/Constants/Table/allascii.txt |
| Chiqish ishi, eV | Cs 2,14; K 2,30; Na 2,75; Ca 2,87; Mg 3,66; Al 4,28; Ag 4,26; Zn 4,33; Cu 4,65; Pt 5,65 | Michaelson 1977, https://doi.org/10.1063/1.323539 |

Tuzatildi: Na 2,36 → 2,75 eV, K 2,29 → 2,30 eV (Michaelson tavsiya qiymatlari; adabiyotda sirt holatiga qarab ±0,2 eV tarqoqlik bor).

**Asboblar, aniqlik va noaniqlik.** U₀ — 3½ raqamli voltmetr (2 V diapazon → 1 mV, 20 V → 10 mV), tok nolga tushgan nuqtani topish σ = 3 mV. I — 3½ raqamli mikroampermetr (2 µA diapazon → 1 nA), σ = 0,2 %. Vazifalar hisob-kitob (±1 %), o'lchovlar solishtirish uchun.

**Standart sozlamalarda kutilgan natijalar.** ν = 600 THz, W = 2,00 eV, intensivlik 50 %: hν = 2,4814 eV, KE_max = 0,4814 eV, U₀ ≈ 0,481 V, ν₀ = 483,6 THz (λ₀ = 620 nm), v_max = 412 km/s, I ≈ 0,201 µA. Vazifalar: 2,4814 eV; 0,4814 eV; 483,6 THz; ν = 800 THz da 1,3085 eV.

**Soddalashtirishlar.** Kvant chiqishi chastotaga bog'liq emas; kontakt potensiallar farqi, anod toki va qorong'i tok hisobga olinmaydi (haqiqiy fotoelementda U₀ kontakt potensiali qadar siljiydi). Tok–kuchlanish egri chizig'ining "dumi" (elektronlar energiyasi taqsimoti) modellashtirilmagan.

### 2. Bor atomi (`bohr-atom`, simulyatsiya: `BohrModelSimulation`)
**Model va formulalar.** Eₙ = −R_H·hc/n², R_H·hc = 13,598 4 eV (proton harakati — keltirilgan massa — hisobga olingan); rₙ = a₀n², vₙ = αc/n. O'tish fotoni ΔE = E_yuqori − E_quyi, 1/λ = R_H(1/n₁² − 1/n₂²) — vakuumdagi to'lqin uzunligi. Darslikdagi 13,6 eV bilan farq 0,01 %, R = 1,097·10⁷ m⁻¹ bilan 0,02 %.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| R∞ | 10 973 731,568 157 m⁻¹ | CODATA 2022 |
| R_H = R∞/(1 + mₑ/mₚ) | 1,096 775 8·10⁷ m⁻¹ | CODATA 2022 dan hisoblangan |
| a₀ | 5,291 772 105 44·10⁻¹¹ m | CODATA 2022 |
| Hα, Hβ, Hγ, Hδ (havoda) | 656,279; 486,135; 434,047; 410,173 nm | NIST ASD |

Model (vakuum → havoga o'tkazilgan) NIST ASD qiymatlariga 0,01 nm gacha mos keladi; qoldiq — nozik struktura.

**Asboblar, aniqlik va noaniqlik.** Bu ko'rgazmali model: «O'lchash» sath energiyasi Eₙ (4 ta kasr), oxirgi foton energiyasi ΔE va to'lqin uzunligini (0,01 nm) yozadi; tasodifiy xatolik qo'shilmaydi (nazariy ko'rsatkich).

**Standart sozlamalarda kutilgan natijalar.** n = 3: E₃ = −1,5109 eV (avval −1,5111). 3 → 2: λ = 656,47 nm vakuumda (havoda 656,28 nm; avval 656,34), ΔE = 1,8887 eV (avval 1,8889). 2 → 1 (Layman α): 121,57 nm (avval 121,54).

**Soddalashtirishlar.** Bor modeli: nozik struktura, Lemb siljishi, spin hisobga olinmaydi; orbitalar klassik doiralar sifatida chiziladi.

### 3. Emissiya spektrlari (`emission-spectra`, simulyatsiya: `EmissionSpectraSimulation`)
**Model va formulalar.** Vodorod chiziqlari Ridberg formulasidan (R_H) hisoblanadi va havodagi to'lqin uzunligiga o'tkaziladi (n_havo ≈ 1,000 28, farq 0,028 %). He, Na, Hg chiziqlari — NIST ASD dagi kuzatilgan (havodagi) qiymatlar. Foton energiyasi E = hc/λ.

**Konstantalar va jadval qiymatlari.**
| Element | Chiziqlar (havoda), nm | Manba |
|---|---|---|
| H | 656,279; 486,135; 434,047; 410,173 | NIST ASD |
| He | 667,815; 587,562 (D3); 501,568; 492,193; 471,315; 447,148 | NIST ASD |
| Na | 589,592 (D1); 588,995 (D2) | NIST ASD |
| Hg | 579,067; 576,961; 546,075; 435,834; 404,657 | NIST ASD |

Avval jadval 0,1 nm gacha yaxlitlangan edi (masalan Na D2 589,0, Hg 579,1); endi to'liq NIST qiymatlari.

**Asboblar, aniqlik va noaniqlik.** Spektroskop (`SPECTROSCOPE`): bo'linma 0,1 nm, σ = 0,03 nm, maksimal xato ±0,125 nm (Hβ uchun 0,026 %). Talaba ip-ko'rsatkichni chiziqqa qo'yib, «O'lchash» bilan λ ni o'qiydi.

**Standart sozlamalarda kutilgan natijalar.** Hβ (4 → 2) formula bo'yicha 486,27 nm (vakuumda; avval 486,17), spektroskopda ≈ 486,1 nm (havoda). Na D (589 nm): E = 2,105 eV; Hg yashil chizig'i (546,1 nm): E = 2,2704 eV.

**Soddalashtirishlar.** Chiziqlar kengligi va nisbiy intensivliklar sxematik; Na D dubletini ajratish uchun spektroskop aniqligi yetarli (0,6 nm farq).

### 4. Radioaktiv yemirilish (`radioactive-decay`, simulyatsiya: `RadioactiveDecaySimulation`)
**Model va formulalar.** N(t) = N₀·2^(−t/T½) = N₀·e^(−λt), λ = ln2/T½, A = λN. Har bir yadroning yemirilish vaqti oldindan τ = −ln(u)/λ ko'rinishida tanlanadi (teskari o'zgartirish), shuning uchun natija kadr chastotasiga bog'liq emas va statistika aniq binomial: N(t) ~ Bin(N₀, e^(−λt)). Tasodifiy sonlar endi urug'li generatordan (`Math.random` o'rniga): bir xil T½, N₀ va ishga tushirish raqamida natija takrorlanadi.

**Konstantalar va jadval qiymatlari.** Nazariya matnidagi haqiqiy izotoplar (ENSDF/IAEA, NUBASE2020): ²²⁰Rn 55,6 s; ¹³⁷ᵐBa 2,552 min; ²³⁴ᵐPa 1,159 min; ¹³¹I 8,0252 sutka; ²²²Rn 3,8222 sutka; ⁶⁰Co 5,2712 yil; ¹³⁷Cs 30,08 yil; ²²⁶Ra 1600 yil; ¹⁴C 5700 yil; ²³⁸U 4,468·10⁹ yil. Simulyatsiyadagi 5–30 s yarim yemirilish davrlari maktabda ishlatiladigan ²²⁰Rn, ¹³⁷ᵐBa, ²³⁴ᵐPa bilan bir tartibda.

**Asboblar, aniqlik va noaniqlik.** Geyger–Myuller hisoblagichi (100 % samaradorlik, fon yo'q): oxirgi 1 s dagi impulslar soni — butun son, Puasson/binomial tarqoqlik (±√N) fizik, asbobga tegishli emas. «O'lchash» vaqtni (0,01 s), qolgan yadrolar soni N va 1 s dagi impulslarni yozadi. Vazifalar hisob-kitob (±1 %).

**Standart sozlamalarda kutilgan natijalar.** T½ = 10 s, N₀ = 1000: t = 10 s da N ≈ 500 ± 16, t = 20 s da ≈ 250 ± 14; λ = 0,069315 s⁻¹; A₀ = 69,315 Bq. 10-sekunddagi 1 s oynada kutilgan impulslar ≈ 36 ± 6.

**Soddalashtirishlar.** Hisoblagichning o'lik vaqti, fon nurlanishi (~0,3 imp/s) va geometrik samaradorlik modellashtirilmagan; qiz yadrolar barqaror deb olinadi.

### 5. Yadro bo'linishi (`nuclear-fission`, simulyatsiya: `NuclearFissionSimulation`)
**Model va formulalar.** ²³⁵U + n → ¹⁴¹Ba + ⁹²Kr + 3n; Δm = Σm(reagentlar) − Σm(mahsulotlar), ΔE = Δm·931,494 MeV/u. 1 kg ²³⁵U dagi energiya E = (m/M)·N_A·E₁. Bo'laklarning kinetik energiyasi impuls saqlanishidan taqsimlanadi. Tasodifiy yo'nalishlar urug'li generatordan.

**Konstantalar va jadval qiymatlari.**
| Kattalik | Qiymat | Manba |
|---|---|---|
| m(²³⁵U), m(¹⁴¹Ba), m(⁹²Kr) | 235,043 928 117; 140,914 403 653; 91,926 173 092 u | AME2020 |
| mₙ | 1,008 664 916 06 u | CODATA 2022 |
| Bu reaksiyaning Q qiymati | Δm = 0,186 02 u → 173,28 MeV | hisoblangan |
| Bitta bo'linishdagi to'liq energiya | ≈ 200 MeV (maktab qiymati); baholangan 202,36 ± 0,26 MeV | Ma et al. 2013 |
| Bitta bo'linishdagi neytronlar ν̄ | 2,4355 (avval 2,43) | IAEA neutron standards |

**Asboblar, aniqlik va noaniqlik.** O'lchov asbobi yo'q (ko'rgazmali model, vazifalar hisob-kitob). 

**Standart sozlamalarda kutilgan natijalar.** Δm = 0,186 u → 173,26 MeV; 1 kg ²³⁵U, E₁ = 200 MeV → 8,2115·10¹³ J (≈ 2,8·10⁶ kg toshko'mir, q ≈ 29 MJ/kg).

**Soddalashtirishlar.** Bitta bo'linish kanali (Ba + Kr + 3n) ko'rsatiladi; haqiqiy bo'linish mahsulotlari keng taqsimotga ega, 173 MeV — faqat bo'laklar va neytronlarning darhol ajraladigan energiyasi (β/γ va antineytrinosiz).

### 6. To'lqin-zarra dualligi (`wave-particle-duality`, simulyatsiya: `DoubleSlitSimulation`)
**Model va formulalar.** Yung tajribasi: I(y) = I₀·cos²(πd·y/(λL))·sinc²(πa·y/(λL)); polosalar oralig'i Δy = λL/d (L = 1,00 m). Qorong'i polosalar d·sinθ = (k + ½)λ — tirqish kengligi ta'sirida siljimaydi, shuning uchun o'lchov qorong'i polosalar markazlari bo'yicha qilinadi. Foton energiyasi E = hc/λ.

**Konstantalar va jadval qiymatlari.** h, c, e — CODATA 2022 (aniq). Ko'rinadigan to'lqin uzunliklari 400–700 nm.

**Asboblar, aniqlik va noaniqlik.** O'lchov mikroskopi / ekran mikrometri: bo'linma 0,01 mm, ip-ko'rsatkichni polosa markaziga qo'yish σ = 0,04 mm (bitta oraliq uchun). Markazga nisbatan simmetrik ikki qorong'i polosa orasidagi N ta oraliq uzunligi o'lchanadi va Δy = uzunlik/N; N — ekranga (±19 mm) va markaziy difraksion maksimum ichiga sig'adigan eng katta toq son. Maksimal xato (2,5σ + 0,005)/N — har bir vazifada vazifa tolerantligining (1 %) yarmidan kichik (test bilan isbotlangan, 30 marta takroriy o'lchov). «O'lchash» ikkita yozuv chiqaradi: `O'lchangan masofa (N oraliq)` va `Polosalar oralig'i Δy`.

**Standart sozlamalarda kutilgan natijalar.** λ = 550 nm, d = 0,10 mm: Δy = 5,500 mm. Vazifalar: (550 nm; 0,1 mm) → 5,5 mm; (400 nm; 0,1 mm) → 4,0 mm; (550 nm; 0,2 mm) → 2,75 mm; E(550 nm) = 2,2543 eV.

**Soddalashtirishlar.** Monoxromatik nuqtaviy manba, Fraungofer yaqinlashuvi (L ≫ d); fotonlarning birma-bir kelishi ko'rgazmali animatsiya.

### 7. Rentgen difraksiyasi (`xray-diffraction`, simulyatsiya: `XRayDiffractionSimulation`)
**Model va formulalar.** Bregg sharti nλ = 2d·sinθ (θ — nur va atom tekisliklari orasidagi sirpanish burchagi), n_max = ⌊2d/λ⌋, og'ish burchagi 2θ. d = λn/(2 sinθ).

**Konstantalar va jadval qiymatlari.**
| Kristall | Panjara doimiysi a | Tekislik | d | Manba |
|---|---|---|---|---|
| NaCl | 564,02 pm | (200) | 0,282 01 nm | Leybold P7.1.2.1 |
| LiF | 402,7 pm | (200) | 0,201 35 nm | Leybold P7.1.2.1 |
| KCl | 629,31 pm | (200) | 0,314 66 nm | Handbook of Mineralogy |
| Olmos | 356,679 pm | (111) | 0,205 93 nm | APS/ANL jadvali |
| Si | 543,102 0511 pm | (111) | 0,313 56 nm | CODATA 2022 |

| Chiziq | λ | Manba |
|---|---|---|
| Cu Kα₁ / Kα₂ / Kα (o'rtacha) / Kβ | 0,154 059 / 0,154 443 / 0,154 187 / 0,139 223 nm | NIST X-ray Transition Energies |
| Mo Kα₁ / Kα (o'rtacha) / Kβ | 0,070 932 / 0,071 075 / 0,063 230 nm | NIST |

Qo'shildi: LiF va KCl kristallari, Mo anod chizig'i; NaCl d = 0,2820 nm → 0,282 01 nm (aniqroq).

**Asboblar, aniqlik va noaniqlik.** Rentgen goniometri (θ–2θ bog'langan, qadamli motor): bo'linma 0,1°, σ = 0,02°, maksimal xato ±0,1°. «O'lchash» har bir mavjud tartib uchun `Bregg burchagi θ₁`, `θ₂`, … ni yozadi. Vazifalar hisob-kitob (±1 %).

**Standart sozlamalarda kutilgan natijalar.** λ = 0,154 nm (Cu Kα), NaCl: θ₁ = 15,845°, θ₂ = 33,098°, θ₃ = 54,997°. Vazifalar (maktab qiymatlari d = 0,282 nm): θ₁ = 15,846°, θ₂ = 33,10°; d = 0,314 nm da θ₁ = 14,195°; θ = 15,85° dan d = 0,281 93 nm.

**Soddalashtirishlar.** Faqat bitta tekisliklar oilasi; tormozlovchi (uzluksiz) spektr, intensivlik (struktura omili) va Kα₁/Kα₂ dubletining ajralishi ko'rsatilmaydi.

