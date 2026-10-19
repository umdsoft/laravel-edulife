<?php

use Database\Seeders\Exam\Support\DemoMath as M;

/*
 * Demo questions - mathematics. 5 per section (prompt 11: "har asosiy
 * mavzuga 5 ta tasdiqlangan namuna savol"), is_demo = true.
 *
 * answer   hand-written expected value
 * compute  independent computation of the same value; the seeder and
 *          DemoQuestionVerifier refuse a question whose two values differ
 * options  closed questions: numbers or [latex, value]; the correct option
 *          is found by VALUE (exactly one must equal the answer) and the
 *          seeder rotates the order, so position carries no meaning
 */

return [
    // ---------------- Arifmetika va sonlar ----------------
    ['topic' => 'math.arith.natural', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$125 \cdot 8 - 999$ ifodaning qiymatini toping.',
        'answer' => 1, 'compute' => fn () => 125 * 8 - 999,
        'options' => [1, 11, 0, 101]],
    ['topic' => 'math.arith.gcd_lcm', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '12 va 18 sonlarining eng kichik umumiy karralisini (EKUK) toping.',
        'answer' => 36, 'compute' => fn () => M::lcm(12, 18),
        'options' => [36, 6, 72, 216]],
    ['topic' => 'math.arith.fractions', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '$\frac{2}{3} + \frac{3}{4} - \frac{5}{12}$ ni hisoblang.',
        'answer' => 1, 'compute' => fn () => 2 / 3 + 3 / 4 - 5 / 12,
        'options' => [1, ['\frac{7}{12}', 7 / 12], ['\frac{5}{6}', 5 / 6], ['\frac{13}{12}', 13 / 12]]],
    ['topic' => 'math.arith.roots', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$\sqrt{50} \cdot \sqrt{8}$ ni hisoblang.',
        'answer' => 20, 'compute' => fn () => sqrt(50) * sqrt(8)],
    ['topic' => 'math.arith.gcd_lcm', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => '$\mathrm{EKUB}(84;\,126) + \mathrm{EKUK}(84;\,126)$ ni toping.',
        'answer' => 294, 'compute' => fn () => M::gcd(84, 126) + M::lcm(84, 126)],

    // ---------------- Foiz va proporsiya ----------------
    ['topic' => 'math.percent.percent', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '240 ning 15 % ini toping.',
        'answer' => 36, 'compute' => fn () => 240 * 15 / 100,
        'options' => [36, 24, 30, 16]],
    ['topic' => 'math.percent.ratio', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '$\frac{x}{12} = \frac{5}{8}$ proporsiyadan $x$ ni toping.',
        'answer' => 7.5, 'compute' => fn () => 12 * 5 / 8,
        'options' => [7.5, 7, 8, 9.6]],
    ['topic' => 'math.percent.percent', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Mahsulot narxi avval 20 % oshirildi, so\'ng yangi narx 20 % kamaytirildi. Yakuniy narx dastlabki narxdan necha foiz kam?',
        'answer' => 4, 'compute' => fn () => (1 - 1.2 * 0.8) * 100,
        'options' => [4, 0, 2, 20]],
    ['topic' => 'math.percent.compound', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Omonatga 1 000 000 so\'m yillik 10 % murakkab foiz bilan qo\'yildi. 2 yildan keyin omonatda necha so\'m bo\'ladi?',
        'answer' => 1210000, 'compute' => function () {
            $sum = 1000000;
            for ($year = 0; $year < 2; $year++) {
                $sum += $sum * 10 / 100;
            }

            return $sum;
        }],
    ['topic' => 'math.percent.ratio', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => 'Sinfdagi o\'g\'il va qiz bolalar soni $3 : 5$ nisbatda. Qizlar o\'g\'il bolalardan 8 ta ko\'p. Sinfda jami nechta o\'quvchi bor?',
        'answer' => 32, 'compute' => function () {
            for ($boys = 1; $boys < 100; $boys++) {
                $girls = $boys + 8;
                if ($boys * 5 === $girls * 3) {
                    return $boys + $girls;
                }
            }
        }],

    // ---------------- Algebraik ifodalar ----------------
    ['topic' => 'math.algebra.powers', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$2^3 \cdot 2^4 : 2^5$ ni hisoblang.',
        'answer' => 4, 'compute' => fn () => 2 ** 3 * 2 ** 4 / 2 ** 5,
        'options' => [4, 2, 8, 16]],
    ['topic' => 'math.algebra.formulas', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '$a + b = 7$ va $ab = 10$ bo\'lsa, $a^2 + b^2$ ni toping.',
        'answer' => 29, 'compute' => function () {
            [$a, $b] = M::quadRoots(1, -7, 10); // a, b are the roots of t^2 - 7t + 10

            return $a ** 2 + $b ** 2;
        },
        'options' => [29, 49, 39, 69]],
    ['topic' => 'math.algebra.factoring', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '$\dfrac{x^2 - 9}{x^2 + 3x}$ kasrni qisqartiring va uning $x = 6$ dagi qiymatini toping.',
        'answer' => 0.5, 'compute' => fn () => (6 ** 2 - 9) / (6 ** 2 + 3 * 6),
        'options' => [['\frac{1}{2}', 0.5], ['\frac{3}{2}', 1.5], 2, ['\frac{1}{3}', 1 / 3]]],
    ['topic' => 'math.algebra.formulas', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$101^2 - 99^2$ ni hisoblang.',
        'answer' => 400, 'compute' => fn () => 101 * 101 - 99 * 99],
    ['topic' => 'math.algebra.rational', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => '$x - \frac{1}{x} = 3$ bo\'lsa, $x^2 + \frac{1}{x^2}$ ni toping.',
        'answer' => 11, 'compute' => function () {
            $x = M::quadRoots(1, -3, -1)[1]; // x^2 - 3x - 1 = 0

            return $x ** 2 + 1 / $x ** 2;
        }],

    // ---------------- Tenglamalar va tengsizliklar ----------------
    ['topic' => 'math.equations.linear', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$3x - 7 = 11$ tenglamani yeching.',
        'answer' => 6, 'compute' => fn () => (11 + 7) / 3,
        'options' => [6, ['\frac{4}{3}', 4 / 3], -6, 18]],
    ['topic' => 'math.equations.quadratic', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '$x^2 - 5x + 6 = 0$ tenglama ildizlarining ko\'paytmasini toping.',
        'answer' => 6, 'compute' => fn () => array_product(M::quadRoots(1, -5, 6)),
        'options' => [6, 5, -6, -5]],
    ['topic' => 'math.equations.modulus', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '$|2x - 3| = 5$ tenglama ildizlarining yig\'indisini toping.',
        'answer' => 3, 'compute' => fn () => (3 + 5) / 2 + (3 - 5) / 2,
        'options' => [3, 4, 5, -1]],
    ['topic' => 'math.equations.irrational', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$\sqrt{x + 7} = x - 5$ tenglamani yeching.',
        'answer' => 9, 'compute' => function () {
            // squaring gives x^2 - 11x + 18 = 0; keep roots that satisfy the original
            $roots = array_filter(M::quadRoots(1, -11, 18), fn ($x) => abs(sqrt($x + 7) - ($x - 5)) < 1e-9);

            return array_sum($roots);
        }],
    ['topic' => 'math.equations.inequalities', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => '$x^2 - 2x - 15 < 0$ tengsizlikning butun yechimlari sonini toping.',
        'answer' => 7, 'compute' => fn () => M::countIntegers(fn ($x) => $x * $x - 2 * $x - 15 < 0, -100, 100)],

    // ---------------- Tenglamalar sistemalari ----------------
    ['topic' => 'math.systems.linear', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$\begin{cases} x + y = 10 \\\\ x - y = 4 \end{cases}$ sistemadan $x$ ni toping.',
        'answer' => 7, 'compute' => fn () => M::cramer(1, 1, 10, 1, -1, 4)[0],
        'options' => [7, 3, 14, 6]],
    ['topic' => 'math.systems.linear', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '$\begin{cases} 2x + 3y = 13 \\\\ x - y = -1 \end{cases}$ bo\'lsa, $x + y$ ni toping.',
        'answer' => 5, 'compute' => fn () => array_sum(M::cramer(2, 3, 13, 1, -1, -1)),
        'options' => [5, 6, 1, 13]],
    ['topic' => 'math.systems.nonlinear', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '$\begin{cases} x + y = 5 \\\\ xy = 6 \end{cases}$ sistemaning yechimlari uchun $x^2 + y^2$ ni toping.',
        'answer' => 13, 'compute' => function () {
            [$x, $y] = M::quadRoots(1, -5, 6);

            return $x ** 2 + $y ** 2;
        },
        'options' => [13, 25, 19, 37]],
    ['topic' => 'math.systems.linear', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$\begin{cases} 3x - 2y = 4 \\\\ 5x + 4y = 14 \end{cases}$ bo\'lsa, $xy$ ni toping.',
        'answer' => 2, 'compute' => fn () => array_product(M::cramer(3, -2, 4, 5, 4, 14))],
    ['topic' => 'math.systems.nonlinear', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => '$\begin{cases} x^2 + y^2 = 25 \\\\ x + y = 7 \end{cases}$ sistemaning yechimlari uchun $|x - y|$ ni toping.',
        'answer' => 1, 'compute' => function () {
            // y = 7 - x  =>  2x^2 - 14x + 24 = 0
            $x = M::quadRoots(2, -14, 24)[0];

            return abs($x - (7 - $x));
        }],

    // ---------------- Funksiyalar va grafiklar ----------------
    ['topic' => 'math.functions.linear', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$f(x) = 3x - 2$ bo\'lsa, $f(4)$ ni toping.',
        'answer' => 10, 'compute' => fn () => (fn ($x) => 3 * $x - 2)(4),
        'options' => [10, 12, 14, 2]],
    ['topic' => 'math.functions.quadratic', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '$y = x^2 - 6x + 5$ parabola uchining ordinatasini toping.',
        'answer' => -4, 'compute' => fn () => M::minOn(fn ($x) => $x * $x - 6 * $x + 5, -10, 10),
        'options' => [-4, 3, 5, -9]],
    ['topic' => 'math.functions.domain', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '$y = \sqrt{6 - x} + \sqrt{x + 2}$ funksiyaning aniqlanish sohasiga nechta butun son tegishli?',
        'answer' => 9, 'compute' => fn () => M::countIntegers(fn ($x) => 6 - $x >= 0 && $x + 2 >= 0, -100, 100),
        'options' => [9, 8, 7, 4]],
    ['topic' => 'math.functions.linear', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$y = kx + 3$ funksiya grafigi $A(2;\,11)$ nuqtadan o\'tadi. $k$ ni toping.',
        'answer' => 4, 'compute' => fn () => (11 - 3) / 2],
    ['topic' => 'math.functions.composition', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => '$f(x) = 2x + 1$ va $g(x) = x^2 - 3$ bo\'lsa, $f(g(3))$ ni toping.',
        'answer' => 13, 'compute' => function () {
            $f = fn ($x) => 2 * $x + 1;
            $g = fn ($x) => $x * $x - 3;

            return $f($g(3));
        }],

    // ---------------- Progressiyalar ----------------
    ['topic' => 'math.progressions.arithmetic', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Arifmetik progressiyada $a_1 = 3$, $d = 4$. $a_{10}$ ni toping.',
        'answer' => 39, 'compute' => function () {
            $a = 3;
            for ($n = 2; $n <= 10; $n++) {
                $a += 4;
            }

            return $a;
        },
        'options' => [39, 43, 40, 35]],
    ['topic' => 'math.progressions.geometric', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Geometrik progressiyada $b_1 = 2$, $q = 3$. $b_5$ ni toping.',
        'answer' => 162, 'compute' => fn () => 2 * 3 ** 4,
        'options' => [162, 486, 54, 30]],
    ['topic' => 'math.progressions.arithmetic', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Arifmetik progressiyada $a_1 = 5$, $d = 2$. Uning dastlabki 20 ta hadi yig\'indisini toping.',
        'answer' => 480, 'compute' => fn () => array_sum(array_map(fn ($n) => 5 + ($n - 1) * 2, range(1, 20))),
        'options' => [480, 440, 500, 960]],
    ['topic' => 'math.progressions.geometric', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Cheksiz kamayuvchi geometrik progressiyada $b_1 = 12$, $q = \frac{1}{3}$. Uning yig\'indisini toping.',
        'answer' => 18, 'compute' => fn () => array_sum(array_map(fn ($n) => 12 * (1 / 3) ** $n, range(0, 200)))],
    ['topic' => 'math.progressions.arithmetic', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => 'Arifmetik progressiyada $a_3 = 7$ va $a_7 = 19$. $a_{15}$ ni toping.',
        'answer' => 43, 'compute' => function () {
            // a1 + 2d = 7, a1 + 6d = 19
            [$a1, $d] = M::cramer(1, 2, 7, 1, 6, 19);

            return $a1 + 14 * $d;
        }],

    // ---------------- Ko'rsatkichli va logarifmik funksiyalar ----------------
    ['topic' => 'math.explog.log_properties', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$\log_2 32$ ni hisoblang.',
        'answer' => 5, 'compute' => fn () => log(32, 2),
        'options' => [5, 16, 4, 6]],
    ['topic' => 'math.explog.exponential', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '$3^{x+1} = 81$ tenglamani yeching.',
        'answer' => 3, 'compute' => fn () => log(81, 3) - 1,
        'options' => [3, 4, 2, 27]],
    ['topic' => 'math.explog.log_properties', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '$\log_6 4 + \log_6 9$ ni hisoblang.',
        'answer' => 2, 'compute' => fn () => log(4, 6) + log(9, 6),
        'options' => [2, 3, 6, 1]],
    ['topic' => 'math.explog.log_equations', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$\log_3 (2x - 1) = 2$ tenglamani yeching.',
        'answer' => 5, 'compute' => fn () => (3 ** 2 + 1) / 2],
    ['topic' => 'math.explog.exponential', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => '$4^x - 3 \cdot 2^x - 4 = 0$ tenglamani yeching.',
        'answer' => 2, 'compute' => function () {
            // t = 2^x > 0:  t^2 - 3t - 4 = 0
            $t = array_values(array_filter(M::quadRoots(1, -3, -4), fn ($t) => $t > 0));

            return log($t[0], 2);
        }],

    // ---------------- Trigonometriya ----------------
    ['topic' => 'math.trig.values', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$\sin 30^\circ + \cos 60^\circ$ ni hisoblang.',
        'answer' => 1, 'compute' => fn () => M::sinDeg(30) + M::cosDeg(60),
        'options' => [1, ['\frac{1}{2}', 0.5], ['\sqrt{3}', sqrt(3)], 0]],
    ['topic' => 'math.trig.identities', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '$\sin\alpha = 0{,}6$ va $\alpha$ o\'tkir burchak bo\'lsa, $\cos\alpha$ ni toping.',
        'answer' => 0.8, 'compute' => fn () => cos(asin(0.6)),
        'options' => [0.8, 0.4, 0.6, -0.8]],
    ['topic' => 'math.trig.identities', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '$\sin^2 15^\circ + \cos^2 15^\circ + \operatorname{tg} 45^\circ$ ni hisoblang.',
        'answer' => 2, 'compute' => fn () => M::sinDeg(15) ** 2 + M::cosDeg(15) ** 2 + M::tanDeg(45),
        'options' => [2, 1, 3, 0]],
    ['topic' => 'math.trig.equations', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$2\sin x = 1$ tenglamaning $[0^\circ;\,180^\circ]$ oraliqdagi ildizlari yig\'indisini gradusda toping.',
        'answer' => 180, 'compute' => fn () => array_sum(array_filter(range(0, 180), fn ($d) => abs(2 * M::sinDeg($d) - 1) < 1e-9))],
    ['topic' => 'math.trig.identities', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => '$\operatorname{tg}\alpha = 2$ bo\'lsa, $\dfrac{\sin\alpha + \cos\alpha}{\sin\alpha - \cos\alpha}$ ni toping.',
        'answer' => 3, 'compute' => function () {
            $a = atan(2);

            return (sin($a) + cos($a)) / (sin($a) - cos($a));
        }],

    // ---------------- Hosila va boshlang'ich funksiya ----------------
    ['topic' => 'math.calculus.derivative', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$f(x) = x^3$ funksiyaning $x = 2$ nuqtadagi hosilasini toping.',
        'answer' => 12, 'compute' => fn () => M::derivative(fn ($x) => $x ** 3, 2),
        'options' => [12, 8, 6, 4]],
    ['topic' => 'math.calculus.derivative', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '$f(x) = x^2 - 4x + 1$ bo\'lsa, $f\'(5)$ ni toping.',
        'answer' => 6, 'compute' => fn () => M::derivative(fn ($x) => $x * $x - 4 * $x + 1, 5),
        'options' => [6, 10, 2, 1]],
    ['topic' => 'math.calculus.applications', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '$f(x) = x^3 - 3x$ funksiyaning $[0;\,2]$ kesmadagi eng kichik qiymatini toping.',
        'answer' => -2, 'compute' => fn () => M::minOn(fn ($x) => $x ** 3 - 3 * $x, 0, 2),
        'options' => [-2, 0, 2, -3]],
    ['topic' => 'math.calculus.integral', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$\displaystyle\int_0^3 x^2\,dx$ ni hisoblang.',
        'answer' => 9, 'compute' => fn () => M::integrate(fn ($x) => $x * $x, 0, 3)],
    ['topic' => 'math.calculus.applications', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => '$y = x^2$ parabolaga abssissasi $x_0 = 3$ bo\'lgan nuqtada urinma o\'tkazildi. Urinmaning $Oy$ o\'qi bilan kesishish nuqtasi ordinatasini toping.',
        'answer' => -9, 'compute' => function () {
            $f = fn ($x) => $x * $x;
            $k = M::derivative($f, 3);

            return $f(3) - $k * 3; // y = f(x0) + k(x - x0) at x = 0
        }],

    // ---------------- Planimetriya ----------------
    ['topic' => 'math.planimetry.triangles', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'To\'g\'ri burchakli uchburchakning katetlari 6 va 8 ga teng. Gipotenuzani toping.',
        'answer' => 10, 'compute' => fn () => hypot(6, 8),
        'options' => [10, 14, 12, ['2\sqrt{7}', 2 * sqrt(7)]]],
    ['topic' => 'math.planimetry.areas', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Asosi 10 ga, shu asosga tushirilgan balandligi 6 ga teng uchburchakning yuzini toping.',
        'answer' => 30, 'compute' => fn () => 10 * 6 / 2,
        'options' => [30, 60, 16, 15]],
    ['topic' => 'math.planimetry.circle', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Radiusi 5 ga teng aylanada markazdan 3 masofada joylashgan vatarning uzunligini toping.',
        'answer' => 8, 'compute' => fn () => 2 * sqrt(5 ** 2 - 3 ** 2),
        'options' => [8, 4, 6, 10]],
    ['topic' => 'math.planimetry.quadrilaterals', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Rombning diagonallari 10 va 24 ga teng. Rombning tomonini toping.',
        'answer' => 13, 'compute' => fn () => hypot(10 / 2, 24 / 2)],
    ['topic' => 'math.planimetry.areas', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => 'Tomonlari 13, 14 va 15 ga teng uchburchakning yuzini toping.',
        'answer' => 84, 'compute' => function () {
            $p = (13 + 14 + 15) / 2;

            return sqrt($p * ($p - 13) * ($p - 14) * ($p - 15));
        }],

    // ---------------- Stereometriya ----------------
    ['topic' => 'math.stereometry.polyhedra', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Qirrasi 3 ga teng kubning hajmini toping.',
        'answer' => 27, 'compute' => fn () => 3 ** 3,
        'options' => [27, 9, 54, 18]],
    ['topic' => 'math.stereometry.polyhedra', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'To\'g\'ri burchakli parallelepipedning o\'lchamlari 2, 3 va 6 ga teng. Uning diagonalini toping.',
        'answer' => 7, 'compute' => fn () => sqrt(2 ** 2 + 3 ** 2 + 6 ** 2),
        'options' => [7, 11, ['\sqrt{11}', sqrt(11)], 49]],
    ['topic' => 'math.stereometry.revolution', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Silindrning asos radiusi 3 ga, balandligi 4 ga teng. Silindr hajmi $V = k\pi$ ko\'rinishida yozilsa, $k$ ni toping.',
        'answer' => 36, 'compute' => fn () => (M_PI * 3 ** 2 * 4) / M_PI,
        'options' => [36, 12, 24, 48]],
    ['topic' => 'math.stereometry.polyhedra', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Muntazam to\'rtburchakli piramidaning asos tomoni 6 ga, balandligi 4 ga teng. Piramida hajmini toping.',
        'answer' => 48, 'compute' => fn () => 6 * 6 * 4 / 3],
    ['topic' => 'math.stereometry.revolution', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => 'Konusning asos radiusi 6 ga, yasovchisi 10 ga teng. Konus hajmi $V = k\pi$ ko\'rinishida yozilsa, $k$ ni toping.',
        'answer' => 96, 'compute' => function () {
            $h = sqrt(10 ** 2 - 6 ** 2);

            return (M_PI * 6 ** 2 * $h / 3) / M_PI;
        }],

    // ---------------- Vektorlar va koordinatalar ----------------
    ['topic' => 'math.vectors.coordinates', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '$A(1;\,2)$ va $B(4;\,6)$ nuqtalar orasidagi masofani toping.',
        'answer' => 5, 'compute' => fn () => hypot(4 - 1, 6 - 2),
        'options' => [5, 7, 25, ['\sqrt{7}', sqrt(7)]]],
    ['topic' => 'math.vectors.vectors', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '$\vec a(2;\,3)$ va $\vec b(4;\,-2)$ vektorlarning skalyar ko\'paytmasini toping.',
        'answer' => 2, 'compute' => fn () => 2 * 4 + 3 * (-2),
        'options' => [2, 14, 10, -2]],
    ['topic' => 'math.vectors.coordinates', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '$A(-2;\,3)$ va $B(6;\,-1)$ nuqtalar berilgan. $AB$ kesma o\'rtasi koordinatalarining yig\'indisini toping.',
        'answer' => 3, 'compute' => fn () => (-2 + 6) / 2 + (3 + (-1)) / 2,
        'options' => [3, 2, 1, 4]],
    ['topic' => 'math.vectors.vectors', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '$\vec a(1;\,2;\,2)$ vektorning uzunligini toping.',
        'answer' => 3, 'compute' => fn () => sqrt(1 + 4 + 4)],
    ['topic' => 'math.vectors.vectors', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => '$\vec a(2;\,-1)$ va $\vec b(k;\,4)$ vektorlar perpendikulyar bo\'lsa, $k$ ni toping.',
        'answer' => 2, 'compute' => function () {
            for ($k = -50; $k <= 50; $k++) {
                if (2 * $k + (-1) * 4 === 0) {
                    return $k;
                }
            }
        }],

    // ---------------- Kombinatorika va ehtimollik ----------------
    ['topic' => 'math.combinatorics.counting', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => '5 ta turli kitobni javonga necha xil usulda terib qo\'yish mumkin?',
        'answer' => 120, 'compute' => fn () => M::fact(5),
        'options' => [120, 25, 60, 24]],
    ['topic' => 'math.combinatorics.counting', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => '8 kishidan 2 kishilik navbatchilar guruhini necha xil usulda tanlash mumkin?',
        'answer' => 28, 'compute' => fn () => M::comb(8, 2),
        'options' => [28, 56, 16, 64]],
    ['topic' => 'math.combinatorics.probability', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => 'Ikkita o\'yin soqqasi tashlandi. Tushgan ochkolar yig\'indisi 7 ga teng bo\'lish ehtimolini toping.',
        'answer' => 1 / 6, 'compute' => function () {
            $hits = 0;
            foreach (range(1, 6) as $a) {
                foreach (range(1, 6) as $b) {
                    $hits += (int) ($a + $b === 7);
                }
            }

            return $hits / 36;
        },
        'options' => [['\frac{1}{6}', 1 / 6], ['\frac{7}{36}', 7 / 36], ['\frac{1}{12}', 1 / 12], ['\frac{5}{36}', 5 / 36]]],
    ['topic' => 'math.combinatorics.counting', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => '0, 1, 2, 3, 4 raqamlaridan raqamlari takrorlanmaydigan nechta uch xonali son tuzish mumkin?',
        'answer' => 48, 'compute' => function () {
            $count = 0;
            for ($n = 100; $n <= 999; $n++) {
                $d = str_split((string) $n);
                $count += (int) (count(array_unique($d)) === 3 && max($d) <= 4);
            }

            return $count;
        }],
    ['topic' => 'math.combinatorics.probability', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => 'Qutida 5 ta oq va 3 ta qora shar bor. Tavakkaliga 2 ta shar olindi. Ikkala shar ham oq bo\'lish ehtimolini toping. Javobni 0,01 aniqlikda o\'nli kasr ko\'rinishida yozing.',
        'answer' => 10 / 28, 'tolerance' => 0.005, 'note' => '0,01 aniqlikda (0,36)',
        'compute' => fn () => M::comb(5, 2) / M::comb(8, 2)],

    // ---------------- Matnli masalalar ----------------
    ['topic' => 'math.word.motion', 'type' => 'single_choice', 'difficulty' => 1,
        'stem' => 'Avtomobil 3 soatda 180 km yo\'l bosdi. Uning tezligi necha km/soat?',
        'answer' => 60, 'compute' => fn () => 180 / 3,
        'options' => [60, 540, 90, 45]],
    ['topic' => 'math.word.work', 'type' => 'single_choice', 'difficulty' => 2,
        'stem' => 'Birinchi ishchi ishni 6 soatda, ikkinchisi 3 soatda bajaradi. Ikkalasi birgalikda shu ishni necha soatda bajaradi?',
        'answer' => 2, 'compute' => fn () => 1 / (1 / 6 + 1 / 3),
        'options' => [2, 4.5, 9, 3]],
    ['topic' => 'math.word.mixtures', 'type' => 'single_choice', 'difficulty' => 3,
        'stem' => '300 g 20 % li tuz eritmasiga 100 g suv qo\'shildi. Hosil bo\'lgan eritmada tuz necha foizni tashkil qiladi?',
        'answer' => 15, 'compute' => fn () => (300 * 0.2) / (300 + 100) * 100,
        'options' => [15, 20, 10, 25]],
    ['topic' => 'math.word.motion', 'type' => 'numeric', 'difficulty' => 3,
        'stem' => 'Ikki shahar orasidagi masofa 360 km. Ikki avtomobil bir vaqtda bir-biriga qarab 50 km/soat va 70 km/soat tezlik bilan yo\'lga chiqdi. Necha soatdan keyin ular uchrashadi?',
        'answer' => 3, 'compute' => fn () => 360 / (50 + 70)],
    ['topic' => 'math.word.motion', 'type' => 'numeric', 'difficulty' => 4,
        'stem' => 'Qayiqning turg\'un suvdagi tezligi 15 km/soat, daryo oqimining tezligi 3 km/soat. Qayiq oqim bo\'ylab 36 km va oqimga qarshi 36 km suzdi. U butun yo\'lga necha soat sarfladi?',
        'answer' => 5, 'compute' => fn () => 36 / (15 + 3) + 36 / (15 - 3)],
];
