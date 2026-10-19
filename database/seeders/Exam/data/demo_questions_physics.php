<?php

use Database\Seeders\Exam\Support\DemoMath as M;

/*
 * Demo questions - physics. 5 per section, is_demo = true. Same format as
 * demo_questions_math.php plus:
 *
 * option_unit     unit printed after every option value (closed questions)
 * unit            numeric_with_unit: canonical unit of `answer`
 * accepted_units  {unit: factor} - factor converts ONE such unit into the
 *                 canonical unit (72 km/soat * 1/3.6 = 20 m/s)
 * constants       values the stem fixes (g = 10 m/s^2 ...), also stored in
 *                 question_bank.constants
 */

$g = ['g' => ['value' => 10, 'unit' => 'm/s²']];
$length = ['sm' => 0.01, 'cm' => 0.01, 'mm' => 0.001, 'km' => 1000];

return [
    // ---------------- Kinematika ----------------
    ['topic' => 'phys.kinematics.uniform', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Velosipedchi 5 m/s tezlik bilan tekis harakatlanmoqda. U 2 minutda qancha yo\'l bosadi?',
        'answer' => 600, 'compute' => fn () => 5 * 2 * 60, 'option_unit' => 'm',
        'options' => [600, 10, 300, 150]],
    ['topic' => 'phys.kinematics.accelerated', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Jism tinch holatdan 2 m/s² tezlanish bilan harakatlana boshladi. 5 s dan keyin uning tezligi qancha bo\'ladi?',
        'answer' => 10, 'compute' => fn () => 0 + 2 * 5, 'option_unit' => 'm/s',
        'options' => [10, 2.5, 25, 7]],
    ['topic' => 'phys.kinematics.freefall', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Jism 45 m balandlikdan boshlang\'ich tezliksiz erkin tushmoqda. Uning tushish vaqtini toping ($g = 10$ m/s²).',
        'answer' => 3, 'compute' => fn () => sqrt(2 * 45 / 10), 'option_unit' => 's', 'constants' => $g,
        'options' => [3, 4.5, 9, 2]],
    ['topic' => 'phys.kinematics.circular', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Radiusi 0,5 m bo\'lgan g\'ildirak chetidagi nuqta 4 m/s chiziqli tezlik bilan aylanmoqda. Nuqtaning markazga intilma tezlanishini m/s² larda toping.',
        'answer' => 32, 'compute' => fn () => 4 ** 2 / 0.5],
    ['topic' => 'phys.kinematics.projectile', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => 'Jism gorizontga $30^\circ$ burchak ostida 20 m/s tezlik bilan otildi. Jism ko\'tarilgan eng katta balandlikni toping ($g = 10$ m/s², havo qarshiligi hisobga olinmaydi). Javobni birligi bilan yozing.',
        'answer' => 5, 'unit' => 'm', 'accepted_units' => $length, 'constants' => $g,
        'compute' => function () {
            // integrate the vertical motion until the vertical speed vanishes
            $vy = 20 * M::sinDeg(30);

            return $vy * ($vy / 10) - 10 * ($vy / 10) ** 2 / 2;
        }],

    // ---------------- Dinamika ----------------
    ['topic' => 'phys.dynamics.newton', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Massasi 2 kg bo\'lgan jismga 10 N kuch ta\'sir qilmoqda. Jismning tezlanishini toping.',
        'answer' => 5, 'compute' => fn () => 10 / 2, 'option_unit' => 'm/s^2',
        'options' => [5, 20, 0.2, 12]],
    ['topic' => 'phys.dynamics.friction', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Massasi 5 kg bo\'lgan brusok gorizontal sirtda sirpanmoqda. Sirpanish ishqalanish koeffitsiyenti 0,2. Ishqalanish kuchini toping ($g = 10$ m/s²).',
        'answer' => 10, 'compute' => fn () => 0.2 * 5 * 10, 'option_unit' => 'N', 'constants' => $g,
        'options' => [10, 1, 25, 50]],
    ['topic' => 'phys.dynamics.elastic', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Bikrligi 200 N/m bo\'lgan prujina 5 sm ga cho\'zildi. Elastiklik kuchini toping.',
        'answer' => 10, 'compute' => fn () => 200 * 5 / 100, 'option_unit' => 'N',
        'options' => [10, 1000, 40, 1]],
    ['topic' => 'phys.dynamics.newton', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Massalari 3 kg va 2 kg bo\'lgan ikki jism ip bilan bog\'langan va silliq gorizontal sirtda yotibdi. 3 kg li jismga ipga parallel 20 N kuch ta\'sir qiladi. Ipning taranglik kuchini nyutonlarda toping.',
        'answer' => 8, 'compute' => function () {
            $a = 20 / (3 + 2);

            return 2 * $a; // the rope drags only the 2 kg body
        }],
    ['topic' => 'phys.dynamics.friction', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => 'Brusok qiyalik burchagi $30^\circ$ bo\'lgan silliq qiya tekislikdan sirpanib tushmoqda. Brusok tezlanishini toping ($g = 10$ m/s², ishqalanish yo\'q). Javobni birligi bilan yozing.',
        'answer' => 5, 'unit' => 'm/s²', 'accepted_units' => ['m/s^2' => 1, 'm/s2' => 1], 'constants' => $g,
        'compute' => fn () => 10 * M::sinDeg(30)],

    // ---------------- Saqlanish qonunlari ----------------
    ['topic' => 'phys.conservation.momentum', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Massasi 0,5 kg bo\'lgan to\'p 4 m/s tezlik bilan harakatlanmoqda. To\'pning impulsini toping.',
        'answer' => 2, 'compute' => fn () => 0.5 * 4, 'option_unit' => 'kg \cdot m/s',
        'options' => [2, 8, 0.125, 4.5]],
    ['topic' => 'phys.conservation.work_power', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Jism 50 N kuch ta\'sirida kuch yo\'nalishida 4 m ga ko\'chdi. Kuchning bajargan ishini toping.',
        'answer' => 200, 'compute' => fn () => 50 * 4 * M::cosDeg(0), 'option_unit' => 'J',
        'options' => [200, 12.5, 54, 46]],
    ['topic' => 'phys.conservation.energy', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Massasi 2 kg bo\'lgan jism 20 m balandlikdan boshlang\'ich tezliksiz tushmoqda. Yerga urilish oldidagi tezligini toping ($g = 10$ m/s², havo qarshiligi yo\'q).',
        'answer' => 20, 'compute' => fn () => sqrt(2 * (2 * 10 * 20) / 2), 'option_unit' => 'm/s', 'constants' => $g,
        'options' => [20, 400, 10, 40]],
    ['topic' => 'phys.conservation.momentum', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Massasi 4 kg, tezligi 3 m/s bo\'lgan arava tinch turgan massasi 2 kg li aravaga urilib, unga ilashib qoldi. Aravalarning birgalikdagi tezligini m/s larda toping.',
        'answer' => 2, 'compute' => fn () => (4 * 3 + 2 * 0) / (4 + 2)],
    ['topic' => 'phys.conservation.work_power', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => 'Nasos 20 s da 100 kg suvni 6 m balandlikka ko\'taradi. Nasosning foydali quvvatini toping ($g = 10$ m/s²). Javobni birligi bilan yozing.',
        'answer' => 300, 'unit' => 'Vt', 'accepted_units' => ['W' => 1, 'kVt' => 1000, 'kW' => 1000], 'constants' => $g,
        'compute' => fn () => 100 * 10 * 6 / 20],

    // ---------------- Statika va gidrostatika ----------------
    ['topic' => 'phys.statics.pressure', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Yuzasi 0,5 m² bo\'lgan sirtga 200 N kuch tik ta\'sir qilmoqda. Bosimni toping.',
        'answer' => 400, 'compute' => fn () => 200 / 0.5, 'option_unit' => 'Pa',
        'options' => [400, 100, 40, 1000]],
    ['topic' => 'phys.statics.pressure', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Suvning 2 m chuqurlikdagi gidrostatik bosimini toping ($\rho = 1000$ kg/m³, $g = 10$ m/s²).',
        'answer' => 20000, 'compute' => fn () => 1000 * 10 * 2, 'option_unit' => 'Pa', 'constants' => $g,
        'options' => [20000, 2000, 200000, 5000]],
    ['topic' => 'phys.statics.moment', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Uzunligi 1 m bo\'lgan yengil richagning bir uchiga 30 N, ikkinchi uchiga 10 N yuk osilgan. Richag muvozanatda bo\'lishi uchun tayanch og\'ir yukdan qanday masofada bo\'lishi kerak?',
        'answer' => 25, 'option_unit' => 'sm', 'compute' => function () {
            // moments about the support: 30 x = 10 (1 - x), in cm
            for ($cm = 0; $cm <= 100; $cm++) {
                if (30 * $cm === 10 * (100 - $cm)) {
                    return $cm;
                }
            }
        },
        'options' => [25, 75, 33, 50]],
    ['topic' => 'phys.statics.archimedes', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Hajmi 0,002 m³ bo\'lgan jism suvga to\'liq botirildi. Unga ta\'sir etuvchi Arximed kuchini nyutonlarda toping ($\rho_{suv} = 1000$ kg/m³, $g = 10$ m/s²).',
        'answer' => 20, 'compute' => fn () => 1000 * 10 * 0.002, 'constants' => $g],
    ['topic' => 'phys.statics.pressure', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => 'Gidravlik pressning kichik porsheni yuzasi 10 sm², katta porsheniniki 500 sm². Kichik porshenga 200 N kuch qo\'yilsa, katta porshen qanday kuch bilan bosadi? Javobni birligi bilan yozing.',
        'answer' => 10000, 'unit' => 'N', 'accepted_units' => ['kN' => 1000],
        'compute' => fn () => (200 / 10) * 500], // equal pressure on both pistons

    // ---------------- Tebranishlar va to'lqinlar ----------------
    ['topic' => 'phys.oscillations.mechanical', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Mayatnik 20 s da 40 marta tebrandi. Tebranish davrini toping.',
        'answer' => 0.5, 'compute' => fn () => 20 / 40, 'option_unit' => 's',
        'options' => [0.5, 2, 800, 20]],
    ['topic' => 'phys.oscillations.waves', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'To\'lqin uzunligi 2 m, chastotasi 5 Hz. To\'lqinning tarqalish tezligini toping.',
        'answer' => 10, 'compute' => fn () => 2 * 5, 'option_unit' => 'm/s',
        'options' => [10, 2.5, 0.4, 7]],
    ['topic' => 'phys.oscillations.mechanical', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Massasi 0,1 kg bo\'lgan yuk bikrligi 40 N/m bo\'lgan prujinaga osilgan. Tebranish davrini toping ($\pi \approx 3{,}14$).',
        'answer' => 0.314, 'compute' => fn () => 2 * 3.14 * sqrt(0.1 / 40), 'option_unit' => 's',
        'options' => [0.314, 3.14, 0.05, 1.57]],
    ['topic' => 'phys.oscillations.mechanical', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Matematik mayatnikning uzunligi 4 marta oshirilsa, uning tebranish davri necha marta ortadi?',
        'answer' => 2, 'compute' => function () {
            $period = fn ($l) => 2 * M_PI * sqrt($l / 10);

            return $period(4) / $period(1);
        }],
    ['topic' => 'phys.oscillations.waves', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => 'Odam qoyaga qarab qichqirdi va aks-sadoni 4 s dan keyin eshitdi. Tovush tezligi 340 m/s bo\'lsa, qoyagacha bo\'lgan masofani toping. Javobni birligi bilan yozing.',
        'answer' => 680, 'unit' => 'm', 'accepted_units' => $length,
        'compute' => fn () => 340 * 4 / 2], // the sound travels there and back

    // ---------------- Molekulyar fizika va termodinamika ----------------
    ['topic' => 'phys.thermo.heat', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Massasi 2 kg bo\'lgan suvni 10 °C ga isitish uchun qancha issiqlik miqdori kerak ($c = 4200$ J/(kg·°C))?',
        'answer' => 84000, 'compute' => fn () => 4200 * 2 * 10, 'option_unit' => 'J',
        'options' => [84000, 8400, 42000, 840]],
    ['topic' => 'phys.thermo.gas', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Ideal gaz izotermik siqilib, uning hajmi 3 marta kamaydi. Gaz bosimi qanday o\'zgardi?',
        'answer' => 3, 'compute' => fn () => (1 * 3) / 1, // p1 V1 = p2 V2 with V2 = V1 / 3
        'options' => [['\text{3 marta ortdi}', 3], ['\text{3 marta kamaydi}', 1 / 3], ['\text{o\'zgarmadi}', 1], ['\text{9 marta ortdi}', 9]]],
    ['topic' => 'phys.thermo.laws', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Gazga 500 J issiqlik miqdori berildi va gaz 200 J ish bajardi. Gaz ichki energiyasining o\'zgarishini toping.',
        'answer' => 300, 'compute' => fn () => 500 - 200, 'option_unit' => 'J',
        'options' => [300, 700, 200, -300]],
    ['topic' => 'phys.thermo.gas', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Ballondagi gazning 27 °C dagi bosimi 200 kPa. Gaz 127 °C gacha isitilsa, uning bosimi necha kPa bo\'ladi (hajm o\'zgarmaydi)? Javobni butun songacha yaxlitlang.',
        'answer' => 800 / 3, 'tolerance' => 0.5, 'note' => 'butun songacha yaxlitlanadi (267)',
        'compute' => fn () => 200 * (127 + 273) / (27 + 273)],
    ['topic' => 'phys.thermo.heat', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => '0 °C dagi 0,5 kg muzni to\'liq eritish uchun qancha issiqlik miqdori kerak ($\lambda = 330$ kJ/kg)? Javobni birligi bilan yozing.',
        'answer' => 165, 'unit' => 'kJ', 'accepted_units' => ['J' => 0.001, 'MJ' => 1000],
        'compute' => fn () => 330 * 0.5],

    // ---------------- Elektrostatika ----------------
    ['topic' => 'phys.electrostatics.coulomb', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Ikki nuqtaviy zaryad orasidagi masofa 2 marta oshirilsa, ularning o\'zaro ta\'sir kuchi qanday o\'zgaradi?',
        'answer' => 0.25, 'compute' => fn () => (1 / 2 ** 2) / (1 / 1 ** 2),
        'options' => [['\text{4 marta kamayadi}', 0.25], ['\text{2 marta kamayadi}', 0.5], ['\text{4 marta ortadi}', 4], ['\text{o\'zgarmaydi}', 1]]],
    ['topic' => 'phys.electrostatics.field', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Zaryadi 2 nKl bo\'lgan zarraga elektr maydonida 4 µN kuch ta\'sir qilmoqda. Maydon kuchlanganligini toping.',
        'answer' => 2000, 'compute' => fn () => 4e-6 / 2e-9, 'option_unit' => 'N/Kl',
        'options' => [2000, 8, 500, 0.5]],
    ['topic' => 'phys.electrostatics.capacitors', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Sig\'imi 5 µF bo\'lgan kondensator 100 V kuchlanishli manbaga ulangan. Kondensator zaryadini toping.',
        'answer' => 0.5, 'compute' => fn () => 5e-6 * 100 * 1000, 'option_unit' => 'mKl',
        'options' => [0.5, 20, 500, 0.05]],
    ['topic' => 'phys.electrostatics.coulomb', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Vakuumda bir-biridan 3 m masofada turgan 1 µKl va 2 µKl zaryadlarning o\'zaro ta\'sir kuchini millinyutonlarda toping ($k = 9 \cdot 10^9$ N·m²/Kl²).',
        'answer' => 2, 'compute' => fn () => 9e9 * 1e-6 * 2e-6 / 3 ** 2 * 1000],
    ['topic' => 'phys.electrostatics.capacitors', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => 'Sig\'imi 20 µF bo\'lgan kondensator 200 V kuchlanishgacha zaryadlandi. Kondensator elektr maydonining energiyasini toping. Javobni birligi bilan yozing.',
        'answer' => 0.4, 'unit' => 'J', 'accepted_units' => ['mJ' => 0.001, 'kJ' => 1000],
        'compute' => function () {
            $c = 20e-6;
            $q = $c * 200;

            return $q ** 2 / (2 * $c);
        }],

    // ---------------- O'zgarmas tok ----------------
    ['topic' => 'phys.dc.ohm', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Qarshiligi 20 Ω bo\'lgan o\'tkazgichdan 0,5 A tok o\'tmoqda. O\'tkazgich uchlaridagi kuchlanishni toping.',
        'answer' => 10, 'compute' => fn () => 0.5 * 20, 'option_unit' => 'V',
        'options' => [10, 40, 2.5, 20]],
    ['topic' => 'phys.dc.circuits', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Qarshiliklari 4 Ω va 6 Ω bo\'lgan rezistorlar ketma-ket ulangan. Umumiy qarshilikni toping.',
        'answer' => 10, 'compute' => fn () => 4 + 6, 'option_unit' => '\Omega',
        'options' => [10, 2.4, 24, 2]],
    ['topic' => 'phys.dc.circuits', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Qarshiliklari 3 Ω va 6 Ω bo\'lgan rezistorlar parallel ulangan. Umumiy qarshilikni toping.',
        'answer' => 2, 'compute' => fn () => 1 / (1 / 3 + 1 / 6), 'option_unit' => '\Omega',
        'options' => [2, 9, 4.5, 18]],
    ['topic' => 'phys.dc.ohm', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'EYuK 12 V, ichki qarshiligi 1 Ω bo\'lgan manbaga 5 Ω li rezistor ulangan. Zanjirdagi tok kuchini amperlarda toping.',
        'answer' => 2, 'compute' => fn () => 12 / (5 + 1)],
    ['topic' => 'phys.dc.power', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => '220 V kuchlanishda ishlaydigan elektr choynakdan 5 A tok o\'tadi. Choynak 10 minutda qancha elektr energiyasi sarflaydi? Javobni birligi bilan yozing.',
        'answer' => 660, 'unit' => 'kJ', 'accepted_units' => ['J' => 0.001, 'MJ' => 1000],
        'compute' => fn () => 220 * 5 * 10 * 60 / 1000],

    // ---------------- Magnetizm va elektromagnit induksiya ----------------
    ['topic' => 'phys.magnetism.field', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Induksiyasi 0,2 T bo\'lgan bir jinsli magnit maydonda uzunligi 0,5 m bo\'lgan o\'tkazgich induksiya chiziqlariga perpendikulyar joylashgan. O\'tkazgichdan 4 A tok o\'tsa, unga ta\'sir etuvchi Amper kuchini toping.',
        'answer' => 0.4, 'compute' => fn () => 0.2 * 4 * 0.5 * M::sinDeg(90), 'option_unit' => 'N',
        'options' => [0.4, 1.6, 10, 0.025]],
    ['topic' => 'phys.magnetism.field', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Zaryadi $1{,}6 \cdot 10^{-19}$ Kl bo\'lgan zarra $10^6$ m/s tezlik bilan induksiyasi 0,5 T bo\'lgan maydonga induksiya chiziqlariga perpendikulyar uchib kirdi. Lorens kuchini toping.',
        'answer' => 8e-14, 'compute' => fn () => 1.6e-19 * 1e6 * 0.5,
        'options' => [['8 \cdot 10^{-14}\ \text{N}', 8e-14], ['3{,}2 \cdot 10^{-13}\ \text{N}', 3.2e-13], ['8 \cdot 10^{-20}\ \text{N}', 8e-20], ['1{,}6 \cdot 10^{-13}\ \text{N}', 1.6e-13]]],
    ['topic' => 'phys.magnetism.induction', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Yuzasi 0,02 m² bo\'lgan halqani kesib o\'tuvchi magnit maydon induksiyasi 0,1 s davomida 0 dan 0,5 T gacha bir tekis oshdi (maydon halqa tekisligiga perpendikulyar). Halqada hosil bo\'lgan induksiya EYuK ini toping.',
        'answer' => 0.1, 'compute' => fn () => (0.5 - 0) * 0.02 / 0.1, 'option_unit' => 'V',
        'options' => [0.1, 1, 0.01, 0.25]],
    ['topic' => 'phys.magnetism.induction', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Induktivligi 0,4 H bo\'lgan g\'altakdagi tok kuchi 0,2 s davomida 5 A dan 1 A gacha bir tekis kamaydi. O\'zinduksiya EYuK ini voltlarda toping.',
        'answer' => 8, 'compute' => fn () => abs(0.4 * (1 - 5) / 0.2)],
    ['topic' => 'phys.magnetism.field', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => 'Proton induksiyasi 0,1 T bo\'lgan maydonga induksiya chiziqlariga perpendikulyar ravishda $10^6$ m/s tezlik bilan uchib kirdi. Proton chizadigan aylananing radiusini toping ($m_p = 1{,}6 \cdot 10^{-27}$ kg, $e = 1{,}6 \cdot 10^{-19}$ Kl). Javobni birligi bilan yozing.',
        'answer' => 0.1, 'unit' => 'm', 'accepted_units' => $length,
        'compute' => fn () => 1.6e-27 * 1e6 / (1.6e-19 * 0.1)], // q v B = m v^2 / r

    // ---------------- Optika ----------------
    ['topic' => 'phys.optics.geometric', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Yorug\'lik nurining ko\'zguga tushish burchagi $35^\circ$. Qaytish burchagini toping.',
        'answer' => 35, 'compute' => fn () => 35, 'option_unit' => '^\circ',
        'options' => [35, 55, 70, 145]],
    ['topic' => 'phys.optics.geometric', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Yorug\'likning shishadagi tezligi $2 \cdot 10^8$ m/s. Shishaning sindirish ko\'rsatkichini toping ($c = 3 \cdot 10^8$ m/s).',
        'answer' => 1.5, 'compute' => fn () => 3e8 / 2e8,
        'options' => [1.5, ['\frac{2}{3}', 2 / 3], 6, ['\frac{4}{3}', 4 / 3]]],
    ['topic' => 'phys.optics.lenses', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Fokus masofasi 10 sm bo\'lgan yig\'uvchi linzadan 30 sm masofada buyum turibdi. Buyumning tasviri linzadan qanday masofada hosil bo\'ladi?',
        'answer' => 15, 'compute' => fn () => 1 / (1 / 10 - 1 / 30), 'option_unit' => 'sm',
        'options' => [15, 20, 7.5, 40]],
    ['topic' => 'phys.optics.lenses', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Fokus masofasi 25 sm bo\'lgan yig\'uvchi linzaning optik kuchini dioptriyalarda toping.',
        'answer' => 4, 'compute' => fn () => 1 / (25 / 100)],
    ['topic' => 'phys.optics.wave', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => 'Davri 1,2 µm bo\'lgan difraksion panjaraga monoxromatik yorug\'lik tik tushmoqda. Birinchi tartibli maksimum $30^\circ$ burchak ostida kuzatildi. Yorug\'likning to\'lqin uzunligini toping. Javobni birligi bilan yozing.',
        'answer' => 600, 'unit' => 'nm', 'accepted_units' => ['µm' => 1000, 'mkm' => 1000, 'm' => 1e9],
        'compute' => fn () => 1.2e-6 * M::sinDeg(30) / 1 * 1e9], // d sin(phi) = k lambda

    // ---------------- Atom va yadro fizikasi ----------------
    ['topic' => 'phys.atomic.structure', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$^{27}_{13}\mathrm{Al}$ atomi yadrosida nechta neytron bor?',
        'answer' => 14, 'compute' => fn () => 27 - 13,
        'options' => [14, 13, 27, 40]],
    ['topic' => 'phys.atomic.radioactivity', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Radioaktiv izotopning yarim yemirilish davri 2 sutka. 6 sutkadan keyin dastlabki yadrolarning qanday qismi yemirilmay qoladi?',
        'answer' => 0.125, 'compute' => function () {
            $left = 1.0;
            for ($day = 2; $day <= 6; $day += 2) {
                $left /= 2;
            }

            return $left;
        },
        'options' => [['\frac{1}{8}', 0.125], ['\frac{1}{3}', 1 / 3], ['\frac{1}{6}', 1 / 6], ['\frac{1}{4}', 0.25]]],
    ['topic' => 'phys.atomic.photoeffect', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Metall sirtiga energiyasi 5 eV bo\'lgan fotonlar tushmoqda. Metallning chiqish ishi 3 eV. Fotoelektronlarning maksimal kinetik energiyasini toping.',
        'answer' => 2, 'compute' => fn () => 5 - 3, 'option_unit' => 'eV',
        'options' => [2, 8, ['\frac{5}{3}', 5 / 3], 15]],
    ['topic' => 'phys.atomic.radioactivity', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$^{238}_{92}\mathrm{U}$ yadrosi bitta $\alpha$-yemirilishga uchradi. Hosil bo\'lgan yadroning massa sonini toping.',
        'answer' => 234, 'compute' => fn () => 238 - 4],
    ['topic' => 'phys.atomic.photoeffect', 'type' => 'numeric_with_unit', 'difficulty' => 4,
        'stem' => 'To\'lqin uzunligi 600 nm bo\'lgan fotonning energiyasini toping ($h = 6{,}6 \cdot 10^{-34}$ J·s, $c = 3 \cdot 10^8$ m/s, $1\ \text{eV} = 1{,}6 \cdot 10^{-19}$ J). Javobni eV da 0,01 aniqlikda yoki J da yozing.',
        'answer' => 2.0625, 'unit' => 'eV', 'accepted_units' => ['J' => 1 / 1.6e-19],
        'tolerance_type' => 'rel', 'tolerance' => 0.005, 'note' => '≈ 2,06 eV = 3,3·10⁻¹⁹ J',
        'compute' => fn () => 6.6e-34 * 3e8 / 600e-9 / 1.6e-19],
];
