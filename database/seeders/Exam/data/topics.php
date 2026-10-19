<?php

/*
 * Topic tree of the exam module (prompt 4a.1). Uzbek (Latin); the teacher
 * edits it later in the panel. Codes are stable identifiers: never rename a
 * code that questions already use.
 *
 * subject code => [ section code => [name, grade_from, grade_to, [topic code => name]] ]
 */

return [
    'math' => [
        'math.arith' => ['Arifmetika va sonlar', 5, 9, [
            'math.arith.natural' => 'Natural sonlar va amallar',
            'math.arith.fractions' => 'Oddiy va o\'nli kasrlar',
            'math.arith.gcd_lcm' => 'Bo\'linish, EKUB va EKUK',
            'math.arith.roots' => 'Haqiqiy sonlar va ildizlar',
        ]],
        'math.percent' => ['Foiz va proporsiya', 5, 9, [
            'math.percent.percent' => 'Foizlar',
            'math.percent.ratio' => 'Nisbat va proporsiya',
            'math.percent.compound' => 'Murakkab foizlar',
        ]],
        'math.algebra' => ['Algebraik ifodalar', 7, 9, [
            'math.algebra.powers' => 'Daraja va uning xossalari',
            'math.algebra.polynomials' => 'Ko\'phadlar',
            'math.algebra.formulas' => 'Qisqa ko\'paytirish formulalari',
            'math.algebra.factoring' => 'Ko\'paytuvchilarga ajratish',
            'math.algebra.rational' => 'Ratsional ifodalar',
        ]],
        'math.equations' => ['Tenglamalar va tengsizliklar', 7, 11, [
            'math.equations.linear' => 'Chiziqli tenglamalar',
            'math.equations.quadratic' => 'Kvadrat tenglamalar',
            'math.equations.rational' => 'Ratsional tenglamalar',
            'math.equations.irrational' => 'Irratsional tenglamalar',
            'math.equations.modulus' => 'Modulli tenglamalar',
            'math.equations.inequalities' => 'Tengsizliklar',
        ]],
        'math.systems' => ['Tenglamalar sistemalari', 7, 11, [
            'math.systems.linear' => 'Chiziqli tenglamalar sistemalari',
            'math.systems.nonlinear' => 'Nochiziqli tenglamalar sistemalari',
        ]],
        'math.functions' => ['Funksiyalar va grafiklar', 7, 11, [
            'math.functions.domain' => 'Funksiya va uning aniqlanish sohasi',
            'math.functions.linear' => 'Chiziqli funksiya',
            'math.functions.quadratic' => 'Kvadrat funksiya',
            'math.functions.composition' => 'Murakkab funksiya va grafik almashtirishlari',
        ]],
        'math.progressions' => ['Progressiyalar', 9, 11, [
            'math.progressions.arithmetic' => 'Arifmetik progressiya',
            'math.progressions.geometric' => 'Geometrik progressiya',
        ]],
        'math.explog' => ['Ko\'rsatkichli va logarifmik funksiyalar', 10, 11, [
            'math.explog.exponential' => 'Ko\'rsatkichli tenglamalar',
            'math.explog.log_properties' => 'Logarifm va uning xossalari',
            'math.explog.log_equations' => 'Logarifmik tenglamalar',
        ]],
        'math.trig' => ['Trigonometriya', 9, 11, [
            'math.trig.values' => 'Trigonometrik funksiyalar qiymatlari',
            'math.trig.identities' => 'Trigonometrik ayniyatlar',
            'math.trig.equations' => 'Trigonometrik tenglamalar',
        ]],
        'math.calculus' => ['Hosila va boshlang\'ich funksiya', 10, 11, [
            'math.calculus.derivative' => 'Hosila',
            'math.calculus.applications' => 'Hosilaning tatbiqlari',
            'math.calculus.integral' => 'Boshlang\'ich funksiya va integral',
        ]],
        'math.planimetry' => ['Planimetriya', 7, 9, [
            'math.planimetry.triangles' => 'Uchburchaklar',
            'math.planimetry.quadrilaterals' => 'To\'rtburchaklar',
            'math.planimetry.circle' => 'Aylana va doira',
            'math.planimetry.areas' => 'Yuzalar',
        ]],
        'math.stereometry' => ['Stereometriya', 10, 11, [
            'math.stereometry.polyhedra' => 'Ko\'pyoqlar (prizma, piramida)',
            'math.stereometry.revolution' => 'Aylanish jismlari',
        ]],
        'math.vectors' => ['Vektorlar va koordinatalar', 9, 11, [
            'math.vectors.coordinates' => 'Koordinatalar usuli',
            'math.vectors.vectors' => 'Vektorlar',
        ]],
        'math.combinatorics' => ['Kombinatorika va ehtimollik', 9, 11, [
            'math.combinatorics.counting' => 'Kombinatorika',
            'math.combinatorics.probability' => 'Ehtimollik',
        ]],
        'math.word' => ['Matnli masalalar', 5, 11, [
            'math.word.motion' => 'Harakatga oid masalalar',
            'math.word.work' => 'Ishga oid masalalar',
            'math.word.mixtures' => 'Aralashma va qotishmalar',
        ]],
    ],

    'physics' => [
        'phys.kinematics' => ['Kinematika', 7, 10, [
            'phys.kinematics.uniform' => 'To\'g\'ri chiziqli tekis harakat',
            'phys.kinematics.accelerated' => 'Tekis tezlanuvchan harakat',
            'phys.kinematics.freefall' => 'Erkin tushish',
            'phys.kinematics.circular' => 'Aylana bo\'ylab harakat',
            'phys.kinematics.projectile' => 'Gorizontga burchak ostida otilgan jism harakati',
        ]],
        'phys.dynamics' => ['Dinamika (Nyuton qonunlari, ishqalanish)', 7, 10, [
            'phys.dynamics.newton' => 'Nyuton qonunlari',
            'phys.dynamics.friction' => 'Ishqalanish kuchi',
            'phys.dynamics.elastic' => 'Elastiklik kuchi',
            'phys.dynamics.gravitation' => 'Butun olam tortishish qonuni',
        ]],
        'phys.conservation' => ['Saqlanish qonunlari (impuls, energiya)', 7, 10, [
            'phys.conservation.momentum' => 'Impuls va impulsning saqlanish qonuni',
            'phys.conservation.work_power' => 'Mexanik ish va quvvat',
            'phys.conservation.energy' => 'Energiya va uning saqlanish qonuni',
        ]],
        'phys.statics' => ['Statika va gidrostatika', 7, 10, [
            'phys.statics.moment' => 'Muvozanat va kuch momenti',
            'phys.statics.pressure' => 'Bosim, Paskal qonuni',
            'phys.statics.archimedes' => 'Arximed kuchi',
        ]],
        'phys.oscillations' => ['Tebranishlar va to\'lqinlar', 9, 11, [
            'phys.oscillations.mechanical' => 'Mexanik tebranishlar',
            'phys.oscillations.waves' => 'Mexanik to\'lqinlar va tovush',
        ]],
        'phys.thermo' => ['Molekulyar fizika va termodinamika', 8, 10, [
            'phys.thermo.gas' => 'MKN va gaz qonunlari',
            'phys.thermo.heat' => 'Issiqlik miqdori',
            'phys.thermo.laws' => 'Termodinamika qonunlari',
        ]],
        'phys.electrostatics' => ['Elektrostatika', 8, 10, [
            'phys.electrostatics.coulomb' => 'Kulon qonuni',
            'phys.electrostatics.field' => 'Elektr maydon va potensial',
            'phys.electrostatics.capacitors' => 'Kondensatorlar',
        ]],
        'phys.dc' => ['O\'zgarmas tok', 8, 10, [
            'phys.dc.ohm' => 'Om qonuni',
            'phys.dc.circuits' => 'O\'tkazgichlarni ulash',
            'phys.dc.power' => 'Tok ishi va quvvati',
        ]],
        'phys.magnetism' => ['Magnetizm va elektromagnit induksiya', 9, 11, [
            'phys.magnetism.field' => 'Magnit maydon, Amper va Lorens kuchlari',
            'phys.magnetism.induction' => 'Elektromagnit induksiya',
        ]],
        'phys.optics' => ['Optika', 9, 11, [
            'phys.optics.geometric' => 'Yorug\'likning qaytishi va sinishi',
            'phys.optics.lenses' => 'Linzalar',
            'phys.optics.wave' => 'To\'lqin optikasi',
        ]],
        'phys.atomic' => ['Atom va yadro fizikasi', 9, 11, [
            'phys.atomic.photoeffect' => 'Fotoeffekt va foton',
            'phys.atomic.structure' => 'Atom va yadro tuzilishi',
            'phys.atomic.radioactivity' => 'Radioaktivlik va yadro reaksiyalari',
        ]],
    ],
];
