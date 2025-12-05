<?php

namespace Database\Seeders;

use App\Models\LabCategory;
use App\Models\LabExperiment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LabExperimentSeeder extends Seeder
{
    public function run(): void
    {
        $categories = LabCategory::all()->keyBy('slug');

        if ($categories->isEmpty()) {
            $this->command->error('Avval LabCategorySeeder ni ishga tushiring!');
            return;
        }

        $allExperiments = array_merge(
            $this->getMechanicsExperiments($categories['mechanics']->id ?? null),
            $this->getThermodynamicsExperiments($categories['thermodynamics']->id ?? null),
            $this->getElectricityExperiments($categories['electricity']->id ?? null),
            $this->getOpticsExperiments($categories['optics']->id ?? null),
            $this->getWavesExperiments($categories['waves']->id ?? null),
            $this->getMagnetismExperiments($categories['magnetism']->id ?? null),
            $this->getAtomicExperiments($categories['atomic']->id ?? null),
        );

        $count = 0;
        foreach ($allExperiments as $data) {
            if (!$data['category_id']) continue;
            
            LabExperiment::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
            $count++;
        }

        $this->command->info("✅ {$count} ta tajriba yaratildi!");
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MEXANIKA TAJRIBALARI (10 ta)
    // ═══════════════════════════════════════════════════════════════════════
    private function getMechanicsExperiments($categoryId): array
    {
        return [
            // 1. Simple Pendulum (FREE)
            $this->createExperiment($categoryId, 'simple-pendulum', 1, 'Oddiy mayatnik', 7, 'easy', true, 25, 50, [
                'short_description_uz' => 'Mayatnikning tebranish davrini ip uzunligiga bog\'liqligini o\'rganing.',
                'simulation_type' => 'pendulum_simple',
                'xp_reward' => 50, 'coin_reward' => 10,
            ]),
            
            // 2. Free Fall (FREE)
            $this->createExperiment($categoryId, 'free-fall', 2, 'Erkin tushish', 7, 'easy', true, 20, 40, [
                'short_description_uz' => 'Jismning erkin tushish qonuniyatlarini o\'rganing va g tezlanishini aniqlang.',
                'simulation_type' => 'free_fall',
                'xp_reward' => 40, 'coin_reward' => 8,
            ]),
            
            // 3. Newton's Second Law
            $this->createExperiment($categoryId, 'newtons-second-law', 3, 'Nyuton ikkinchi qonuni', 8, 'medium', false, 30, 70, [
                'short_description_uz' => 'Kuch, massa va tezlanish orasidagi bog\'liqlikni o\'rganing: F = ma',
                'simulation_type' => 'newton_second',
                'xp_reward' => 70, 'coin_reward' => 15,
            ]),
            
            // 4. Inclined Plane
            $this->createExperiment($categoryId, 'inclined-plane', 4, 'Qiya tekislik', 8, 'medium', false, 30, 65, [
                'short_description_uz' => 'Qiya tekislikda jism harakatini va ishqalanish kuchini o\'rganing.',
                'simulation_type' => 'inclined_plane',
                'xp_reward' => 65, 'coin_reward' => 14,
            ]),
            
            // 5. Spring Oscillations
            $this->createExperiment($categoryId, 'spring-oscillations', 5, 'Prujina tebranishlari', 9, 'medium', false, 35, 75, [
                'short_description_uz' => 'Prujinaning elastik xususiyatlari va tebranish qonuniyatlarini o\'rganing.',
                'simulation_type' => 'spring_oscillation',
                'xp_reward' => 75, 'coin_reward' => 16,
            ]),
            
            // 6. Projectile Motion
            $this->createExperiment($categoryId, 'projectile-motion', 6, 'Gorizontal otilgan jism', 9, 'medium', false, 35, 80, [
                'short_description_uz' => 'Gorizontal otilgan jism harakat traektoriyasini o\'rganing.',
                'simulation_type' => 'projectile',
                'xp_reward' => 80, 'coin_reward' => 18,
            ]),
            
            // 7. Momentum Conservation
            $this->createExperiment($categoryId, 'momentum-conservation', 7, 'Impuls saqlanish qonuni', 9, 'hard', false, 40, 90, [
                'short_description_uz' => 'Elastik va noelastik to\'qnashuvlarda impuls saqlanishini kuzating.',
                'simulation_type' => 'momentum',
                'xp_reward' => 90, 'coin_reward' => 20,
            ]),
            
            // 8. Circular Motion
            $this->createExperiment($categoryId, 'circular-motion', 8, 'Aylana harakat', 10, 'hard', false, 40, 95, [
                'short_description_uz' => 'Markazga intilma tezlanish va aylana harakat qonunlarini o\'rganing.',
                'simulation_type' => 'circular_motion',
                'xp_reward' => 95, 'coin_reward' => 22,
            ]),
            
            // 9. Torque and Rotation
            $this->createExperiment($categoryId, 'torque-rotation', 9, 'Aylanma moment', 10, 'hard', false, 45, 100, [
                'short_description_uz' => 'Kuch momenti va aylanish qonunlarini o\'rganing.',
                'simulation_type' => 'torque',
                'xp_reward' => 100, 'coin_reward' => 25,
            ]),
            
            // 10. Energy Conservation
            $this->createExperiment($categoryId, 'energy-conservation', 10, 'Energiya saqlanish qonuni', 10, 'hard', false, 45, 100, [
                'short_description_uz' => 'Mexanik energiya saqlanish qonunini turli holatlarda tekshiring.',
                'simulation_type' => 'energy_conservation',
                'xp_reward' => 100, 'coin_reward' => 25,
            ]),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TERMODINAMIKA TAJRIBALARI (8 ta)
    // ═══════════════════════════════════════════════════════════════════════
    private function getThermodynamicsExperiments($categoryId): array
    {
        return [
            // 1. Thermal Expansion (FREE)
            $this->createExperiment($categoryId, 'thermal-expansion', 1, 'Issiqlik kengayishi', 7, 'easy', true, 20, 45, [
                'short_description_uz' => 'Jismlarning isitilganda kengayishini o\'rganing.',
                'simulation_type' => 'thermal_expansion',
                'xp_reward' => 45, 'coin_reward' => 10,
            ]),
            
            // 2. Specific Heat
            $this->createExperiment($categoryId, 'specific-heat', 2, 'Solishtirma issiqlik sig\'imi', 8, 'medium', false, 30, 70, [
                'short_description_uz' => 'Turli moddalarning issiqlik sig\'imini aniqlang.',
                'simulation_type' => 'specific_heat',
                'xp_reward' => 70, 'coin_reward' => 15,
            ]),
            
            // 3. Heat Transfer
            $this->createExperiment($categoryId, 'heat-transfer', 3, 'Issiqlik uzatish', 8, 'medium', false, 30, 70, [
                'short_description_uz' => 'Issiqlik o\'tkazuvchanlik, konveksiya va nurlanishni o\'rganing.',
                'simulation_type' => 'heat_transfer',
                'xp_reward' => 70, 'coin_reward' => 15,
            ]),
            
            // 4. Phase Changes
            $this->createExperiment($categoryId, 'phase-changes', 4, 'Agregat holat o\'zgarishi', 8, 'medium', false, 35, 75, [
                'short_description_uz' => 'Suyuqlanish va qaynash jarayonlarini kuzating.',
                'simulation_type' => 'phase_change',
                'xp_reward' => 75, 'coin_reward' => 16,
            ]),
            
            // 5. Ideal Gas Law
            $this->createExperiment($categoryId, 'ideal-gas-law', 5, 'Ideal gaz qonuni', 9, 'medium', false, 35, 80, [
                'short_description_uz' => 'PV = nRT qonunini eksperimental tekshiring.',
                'simulation_type' => 'ideal_gas',
                'xp_reward' => 80, 'coin_reward' => 18,
            ]),
            
            // 6. Calorimetry
            $this->createExperiment($categoryId, 'calorimetry', 6, 'Kalorimetriya', 9, 'hard', false, 40, 85, [
                'short_description_uz' => 'Kalorimetr yordamida issiqlik miqdorini o\'lchash.',
                'simulation_type' => 'calorimetry',
                'xp_reward' => 85, 'coin_reward' => 20,
            ]),
            
            // 7. Carnot Cycle
            $this->createExperiment($categoryId, 'carnot-cycle', 7, 'Karno sikli', 10, 'hard', false, 45, 100, [
                'short_description_uz' => 'Ideal issiqlik mashinasining ishlash prinsipi.',
                'simulation_type' => 'carnot_cycle',
                'xp_reward' => 100, 'coin_reward' => 25,
            ]),
            
            // 8. Entropy
            $this->createExperiment($categoryId, 'entropy', 8, 'Entropiya', 11, 'hard', false, 45, 100, [
                'short_description_uz' => 'Termodinamikning ikkinchi qonuni va entropiya tushunchasini o\'rganing.',
                'simulation_type' => 'entropy',
                'xp_reward' => 100, 'coin_reward' => 25,
            ]),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ELEKTR TAJRIBALARI (10 ta)
    // ═══════════════════════════════════════════════════════════════════════
    private function getElectricityExperiments($categoryId): array
    {
        return [
            // 1. Ohm's Law (FREE)
            $this->createExperiment($categoryId, 'ohm-law', 1, 'Om qonuni', 8, 'medium', true, 30, 60, [
                'short_description_uz' => 'Elektr zanjirida tok kuchi, kuchlanish va qarshilik orasidagi bog\'liqlik.',
                'simulation_type' => 'ohm_law',
                'xp_reward' => 60, 'coin_reward' => 15,
            ]),
            
            // 2. Series Circuits
            $this->createExperiment($categoryId, 'series-circuits', 2, 'Ketma-ket ulanish', 8, 'medium', false, 30, 65, [
                'short_description_uz' => 'Ketma-ket ulangan rezistorlar zanjirini o\'rganing.',
                'simulation_type' => 'series_circuit',
                'xp_reward' => 65, 'coin_reward' => 14,
            ]),
            
            // 3. Parallel Circuits
            $this->createExperiment($categoryId, 'parallel-circuits', 3, 'Parallel ulanish', 8, 'medium', false, 30, 65, [
                'short_description_uz' => 'Parallel ulangan rezistorlar zanjirini o\'rganing.',
                'simulation_type' => 'parallel_circuit',
                'xp_reward' => 65, 'coin_reward' => 14,
            ]),
            
            // 4. Kirchhoff's Laws
            $this->createExperiment($categoryId, 'kirchhoff-laws', 4, 'Kirxgof qonunlari', 9, 'hard', false, 40, 85, [
                'short_description_uz' => 'Murakkab zanjirlarni Kirxgof qonunlari bilan tahlil qiling.',
                'simulation_type' => 'kirchhoff',
                'xp_reward' => 85, 'coin_reward' => 20,
            ]),
            
            // 5. Capacitors
            $this->createExperiment($categoryId, 'capacitors', 5, 'Kondensatorlar', 9, 'medium', false, 35, 75, [
                'short_description_uz' => 'Kondensatorning zaryadlanish va razryadlanish jarayoni.',
                'simulation_type' => 'capacitor',
                'xp_reward' => 75, 'coin_reward' => 16,
            ]),
            
            // 6. RC Circuits
            $this->createExperiment($categoryId, 'rc-circuits', 6, 'RC zanjirlar', 10, 'hard', false, 40, 90, [
                'short_description_uz' => 'Rezistor-kondensator zanjiridagi o\'tkinchi jarayonlar.',
                'simulation_type' => 'rc_circuit',
                'xp_reward' => 90, 'coin_reward' => 20,
            ]),
            
            // 7. Electric Power
            $this->createExperiment($categoryId, 'electric-power', 7, 'Elektr quvvati', 8, 'medium', false, 30, 70, [
                'short_description_uz' => 'Elektr quvvati va energiya sarfini hisoblash.',
                'simulation_type' => 'electric_power',
                'xp_reward' => 70, 'coin_reward' => 15,
            ]),
            
            // 8. Electrostatics
            $this->createExperiment($categoryId, 'electrostatics', 8, 'Elektrostatika', 9, 'medium', false, 35, 80, [
                'short_description_uz' => 'Elektr zaryadlar va Kulon qonunini o\'rganing.',
                'simulation_type' => 'electrostatics',
                'xp_reward' => 80, 'coin_reward' => 18,
            ]),
            
            // 9. Electric Field
            $this->createExperiment($categoryId, 'electric-field', 9, 'Elektr maydoni', 10, 'hard', false, 40, 90, [
                'short_description_uz' => 'Elektr maydoni kuch chiziqlari va potensialini o\'rganing.',
                'simulation_type' => 'electric_field',
                'xp_reward' => 90, 'coin_reward' => 22,
            ]),
            
            // 10. Diodes and LEDs
            $this->createExperiment($categoryId, 'diodes-leds', 10, 'Diodlar va LEDlar', 10, 'hard', false, 40, 95, [
                'short_description_uz' => 'Yarim o\'tkazgich diodlarning ishlash prinsipi.',
                'simulation_type' => 'diodes',
                'xp_reward' => 95, 'coin_reward' => 22,
            ]),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // OPTIKA TAJRIBALARI (9 ta)
    // ═══════════════════════════════════════════════════════════════════════
    private function getOpticsExperiments($categoryId): array
    {
        return [
            // 1. Light Reflection (FREE)
            $this->createExperiment($categoryId, 'light-reflection', 1, 'Yorug\'lik qaytishi', 7, 'easy', true, 20, 45, [
                'short_description_uz' => 'Yorug\'likning tekis sirtdan qaytish qonunini o\'rganing.',
                'simulation_type' => 'reflection',
                'xp_reward' => 45, 'coin_reward' => 10,
            ]),
            
            // 2. Light Refraction
            $this->createExperiment($categoryId, 'light-refraction', 2, 'Yorug\'lik sinishi', 8, 'medium', false, 30, 70, [
                'short_description_uz' => 'Snell qonuni va yorug\'lik sinishini o\'rganing.',
                'simulation_type' => 'refraction',
                'xp_reward' => 70, 'coin_reward' => 15,
            ]),
            
            // 3. Prism and Dispersion
            $this->createExperiment($categoryId, 'prism-dispersion', 3, 'Prizma va dispersiya', 8, 'medium', false, 30, 70, [
                'short_description_uz' => 'Prizmada yorug\'lik dispersiyasini kuzating.',
                'simulation_type' => 'dispersion',
                'xp_reward' => 70, 'coin_reward' => 15,
            ]),
            
            // 4. Convex Lens
            $this->createExperiment($categoryId, 'convex-lens', 4, 'Yig\'uvchi linza', 9, 'medium', false, 35, 80, [
                'short_description_uz' => 'Yig\'uvchi linzada tasvir hosil bo\'lishini kuzating.',
                'simulation_type' => 'convex_lens',
                'xp_reward' => 80, 'coin_reward' => 18,
            ]),
            
            // 5. Concave Lens
            $this->createExperiment($categoryId, 'concave-lens', 5, 'Sochuvchi linza', 9, 'medium', false, 35, 80, [
                'short_description_uz' => 'Sochuvchi linzada tasvir hosil bo\'lishini o\'rganing.',
                'simulation_type' => 'concave_lens',
                'xp_reward' => 80, 'coin_reward' => 18,
            ]),
            
            // 6. Mirrors
            $this->createExperiment($categoryId, 'curved-mirrors', 6, 'Sferik oynalar', 9, 'medium', false, 35, 80, [
                'short_description_uz' => 'Botiq va qavariq oynalarda tasvir hosil bo\'lishi.',
                'simulation_type' => 'curved_mirrors',
                'xp_reward' => 80, 'coin_reward' => 18,
            ]),
            
            // 7. Interference
            $this->createExperiment($categoryId, 'light-interference', 7, 'Yorug\'lik interferensiyasi', 10, 'hard', false, 40, 95, [
                'short_description_uz' => 'Yung tajribasini o\'tkazing va interferensiya rasmini kuzating.',
                'simulation_type' => 'interference',
                'xp_reward' => 95, 'coin_reward' => 22,
            ]),
            
            // 8. Diffraction
            $this->createExperiment($categoryId, 'light-diffraction', 8, 'Yorug\'lik difraksiyasi', 10, 'hard', false, 40, 95, [
                'short_description_uz' => 'Yorug\'likning tor teshikdan o\'tishida egilishini kuzating.',
                'simulation_type' => 'diffraction',
                'xp_reward' => 95, 'coin_reward' => 22,
            ]),
            
            // 9. Polarization
            $this->createExperiment($categoryId, 'polarization', 9, 'Yorug\'lik polyarizatsiyasi', 11, 'hard', false, 45, 100, [
                'short_description_uz' => 'Yorug\'likning polyarizatsiya hodisasini o\'rganing.',
                'simulation_type' => 'polarization',
                'xp_reward' => 100, 'coin_reward' => 25,
            ]),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // TO'LQINLAR TAJRIBALARI (8 ta)
    // ═══════════════════════════════════════════════════════════════════════
    private function getWavesExperiments($categoryId): array
    {
        return [
            // 1. Wave Properties (FREE)
            $this->createExperiment($categoryId, 'wave-properties', 1, 'To\'lqin xususiyatlari', 7, 'easy', true, 20, 45, [
                'short_description_uz' => 'To\'lqin uzunligi, chastota va tezlik orasidagi bog\'liqlik.',
                'simulation_type' => 'wave_basics',
                'xp_reward' => 45, 'coin_reward' => 10,
            ]),
            
            // 2. Standing Waves
            $this->createExperiment($categoryId, 'standing-waves', 2, 'Turg\'un to\'lqinlar', 8, 'medium', false, 30, 70, [
                'short_description_uz' => 'Simda turg\'un to\'lqinlarni hosil qiling.',
                'simulation_type' => 'standing_waves',
                'xp_reward' => 70, 'coin_reward' => 15,
            ]),
            
            // 3. Sound Waves
            $this->createExperiment($categoryId, 'sound-waves', 3, 'Tovush to\'lqinlari', 8, 'medium', false, 30, 70, [
                'short_description_uz' => 'Tovush to\'lqinlarining xususiyatlarini o\'rganing.',
                'simulation_type' => 'sound_waves',
                'xp_reward' => 70, 'coin_reward' => 15,
            ]),
            
            // 4. Resonance
            $this->createExperiment($categoryId, 'resonance', 4, 'Rezonans', 9, 'medium', false, 35, 80, [
                'short_description_uz' => 'Mexanik va akustik rezonans hodisasini kuzating.',
                'simulation_type' => 'resonance',
                'xp_reward' => 80, 'coin_reward' => 18,
            ]),
            
            // 5. Doppler Effect
            $this->createExperiment($categoryId, 'doppler-effect', 5, 'Dopler effekti', 9, 'medium', false, 35, 80, [
                'short_description_uz' => 'Harakatlanayotgan manbadan kelayotgan tovush chastotasi o\'zgarishini kuzating.',
                'simulation_type' => 'doppler',
                'xp_reward' => 80, 'coin_reward' => 18,
            ]),
            
            // 6. Wave Interference
            $this->createExperiment($categoryId, 'wave-interference', 6, 'To\'lqin interferensiyasi', 9, 'hard', false, 40, 85, [
                'short_description_uz' => 'Ikki to\'lqin manbai hosil qilgan interferensiya rasmini kuzating.',
                'simulation_type' => 'wave_interference',
                'xp_reward' => 85, 'coin_reward' => 20,
            ]),
            
            // 7. Electromagnetic Spectrum
            $this->createExperiment($categoryId, 'em-spectrum', 7, 'Elektromagnit spektr', 10, 'hard', false, 40, 90, [
                'short_description_uz' => 'Elektromagnit to\'lqinlarning turli diapazonlarini o\'rganing.',
                'simulation_type' => 'em_spectrum',
                'xp_reward' => 90, 'coin_reward' => 22,
            ]),
            
            // 8. Beats
            $this->createExperiment($categoryId, 'beats-phenomenon', 8, 'Urilishlar hodisasi', 10, 'hard', false, 40, 90, [
                'short_description_uz' => 'Yaqin chastotali tovushlar ustma-ustushishida urilishlarni eshiting.',
                'simulation_type' => 'beats',
                'xp_reward' => 90, 'coin_reward' => 20,
            ]),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // MAGNETIZM TAJRIBALARI (6 ta)
    // ═══════════════════════════════════════════════════════════════════════
    private function getMagnetismExperiments($categoryId): array
    {
        return [
            // 1. Magnetic Field Lines (FREE)
            $this->createExperiment($categoryId, 'magnetic-field-lines', 1, 'Magnit maydoni chiziqlari', 7, 'easy', true, 20, 45, [
                'short_description_uz' => 'Doimiy magnitning maydon chiziqlarini vizualizatsiya qiling.',
                'simulation_type' => 'magnetic_field',
                'xp_reward' => 45, 'coin_reward' => 10,
            ]),
            
            // 2. Electromagnet
            $this->createExperiment($categoryId, 'electromagnet', 2, 'Elektromagnit', 8, 'medium', false, 30, 70, [
                'short_description_uz' => 'Elektromagnit yarating va uning kuchini o\'rganing.',
                'simulation_type' => 'electromagnet',
                'xp_reward' => 70, 'coin_reward' => 15,
            ]),
            
            // 3. Electromagnetic Induction
            $this->createExperiment($categoryId, 'em-induction', 3, 'Elektromagnit induksiya', 9, 'hard', false, 40, 90, [
                'short_description_uz' => 'Faradey qonuni va induksiya E.Y.K.ni o\'rganing.',
                'simulation_type' => 'em_induction',
                'xp_reward' => 90, 'coin_reward' => 22,
            ]),
            
            // 4. Lenz's Law
            $this->createExperiment($categoryId, 'lenz-law', 4, 'Lens qonuni', 10, 'hard', false, 40, 90, [
                'short_description_uz' => 'Induksiya tokining yo\'nalishini aniqlash.',
                'simulation_type' => 'lenz_law',
                'xp_reward' => 90, 'coin_reward' => 22,
            ]),
            
            // 5. Electric Motor
            $this->createExperiment($categoryId, 'electric-motor', 5, 'Elektr dvigatel', 10, 'hard', false, 45, 95, [
                'short_description_uz' => 'Oddiy elektr dvigatelning ishlash prinsipini o\'rganing.',
                'simulation_type' => 'electric_motor',
                'xp_reward' => 95, 'coin_reward' => 22,
            ]),
            
            // 6. Transformer
            $this->createExperiment($categoryId, 'transformer', 6, 'Transformator', 10, 'hard', false, 45, 100, [
                'short_description_uz' => 'Transformatorning ishlash prinsipini o\'rganing.',
                'simulation_type' => 'transformer',
                'xp_reward' => 100, 'coin_reward' => 25,
            ]),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ATOM FIZIKASI TAJRIBALARI (6 ta)
    // ═══════════════════════════════════════════════════════════════════════
    private function getAtomicExperiments($categoryId): array
    {
        return [
            // 1. Photoelectric Effect
            $this->createExperiment($categoryId, 'photoelectric-effect', 1, 'Fotoelektrik effekt', 10, 'hard', false, 40, 90, [
                'short_description_uz' => 'Yorug\'lik ta\'sirida elektronlar chiqishini o\'rganing.',
                'simulation_type' => 'photoelectric',
                'xp_reward' => 90, 'coin_reward' => 22,
            ]),
            
            // 2. Bohr Atom Model
            $this->createExperiment($categoryId, 'bohr-atom', 2, 'Bor atom modeli', 10, 'hard', false, 40, 90, [
                'short_description_uz' => 'Vodorod atomining Bor modeli va energetik sathlar.',
                'simulation_type' => 'bohr_model',
                'xp_reward' => 90, 'coin_reward' => 22,
            ]),
            
            // 3. Emission Spectra
            $this->createExperiment($categoryId, 'emission-spectra', 3, 'Emissiya spektri', 10, 'hard', false, 40, 95, [
                'short_description_uz' => 'Turli elementlarning emissiya spektrlarini kuzating.',
                'simulation_type' => 'emission_spectra',
                'xp_reward' => 95, 'coin_reward' => 22,
            ]),
            
            // 4. Radioactive Decay
            $this->createExperiment($categoryId, 'radioactive-decay', 4, 'Radioaktiv yemirilish', 11, 'hard', false, 45, 100, [
                'short_description_uz' => 'Radioaktiv yemirilish va yarim yemirilish davrini o\'rganing.',
                'simulation_type' => 'radioactive_decay',
                'xp_reward' => 100, 'coin_reward' => 25,
            ]),
            
            // 5. Nuclear Fission
            $this->createExperiment($categoryId, 'nuclear-fission', 5, 'Yadro bo\'linishi', 11, 'hard', false, 45, 100, [
                'short_description_uz' => 'Yadro bo\'linish reaksiyasini simulyatsiya qiling.',
                'simulation_type' => 'nuclear_fission',
                'xp_reward' => 100, 'coin_reward' => 25,
            ]),
            
            // 6. Wave-Particle Duality
            $this->createExperiment($categoryId, 'wave-particle-duality', 6, 'To\'lqin-zarra dualligi', 11, 'hard', false, 45, 100, [
                'short_description_uz' => 'Elektronlarning to\'lqin xususiyatlarini ikki tirqish tajribasida kuzating.',
                'simulation_type' => 'double_slit',
                'xp_reward' => 100, 'coin_reward' => 25,
            ]),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // YORDAMCHI METODLAR
    // ═══════════════════════════════════════════════════════════════════════
    private function createExperiment(
        $categoryId, 
        string $slug, 
        int $number, 
        string $titleUz, 
        int $grade, 
        string $difficulty, 
        bool $isFree, 
        int $duration, 
        int $totalPoints, 
        array $extra = []
    ): array {
        $difficultyScores = ['easy' => 2, 'medium' => 5, 'hard' => 8];
        
        return array_merge([
            'category_id' => $categoryId,
            'slug' => $slug,
            'experiment_number' => $number,
            'title' => ucwords(str_replace('-', ' ', $slug)),
            'title_uz' => $titleUz,
            'title_ru' => $titleUz, // Placeholder
            'short_description_uz' => $extra['short_description_uz'] ?? "Bu tajribada {$titleUz} mavzusini o'rganasiz.",
            'description_uz' => $extra['description_uz'] ?? "Bu tajribada siz {$titleUz} mavzusini chuqur o'rganasiz va amaliy tajriba o'tkazasiz.",
            'grade_level' => $grade,
            'difficulty' => $difficulty,
            'difficulty_score' => $difficultyScores[$difficulty],
            'estimated_duration' => $duration,
            'is_free' => $isFree,
            'is_premium' => !$isFree,
            'required_subscription' => $isFree ? 'free' : 'premium',
            'objectives' => $extra['objectives'] ?? [
                'uz' => [
                    "{$titleUz} asosiy tushunchalarini o'rganish",
                    "Amaliy tajriba o'tkazish",
                    "Natijalarni tahlil qilish",
                ],
            ],
            'theory_introduction' => $extra['theory'] ?? [
                'uz' => "Bu tajribada {$titleUz} haqida nazariy va amaliy bilim olasiz.",
            ],
            'formulas' => $extra['formulas'] ?? [],
            'required_equipment' => $extra['equipment'] ?? [],
            'simulation_type' => $extra['simulation_type'] ?? 'generic',
            'simulation_config' => $extra['simulation_config'] ?? $this->getDefaultSimConfig(),
            'tasks' => $extra['tasks'] ?? $this->getDefaultTasks($titleUz, $totalPoints),
            'total_points' => $totalPoints,
            'passing_points' => (int)($totalPoints * 0.6),
            'xp_reward' => $extra['xp_reward'] ?? $totalPoints,
            'xp_reward_premium' => ($extra['xp_reward'] ?? $totalPoints) * 2,
            'coin_reward' => $extra['coin_reward'] ?? 10,
            'coin_reward_premium' => ($extra['coin_reward'] ?? 10) * 2,
            'status' => 'active',
            'is_featured' => $isFree,
        ], $extra);
    }

    private function getDefaultSimConfig(): array
    {
        return [
            'canvas' => ['width' => 800, 'height' => 600, 'background' => '#1a1a2e'],
            'physics' => ['gravity' => 9.8],
            'measurements' => ['showValues' => true],
        ];
    }

    private function getDefaultTasks(string $title, int $totalPoints): array
    {
        $taskPoints = (int)($totalPoints / 4);
        $remainderPoints = $totalPoints - ($taskPoints * 4);
        
        return [
            [
                'step' => 1,
                'title' => 'Asboblar bilan tanishing',
                'description' => "Tajribadagi asboblar va ularning funksiyalari bilan tanishing.",
                'type' => 'instruction',
                'max_score' => $taskPoints,
            ],
            [
                'step' => 2,
                'title' => 'Birinchi o\'lchov',
                'description' => "Birinchi o'lchovni bajaring va natijani yozing.",
                'type' => 'measurement',
                'max_score' => $taskPoints,
            ],
            [
                'step' => 3,
                'title' => 'Ikkinchi o\'lchov',
                'description' => "Parametrlarni o'zgartirib ikkinchi o'lchovni bajaring.",
                'type' => 'measurement',
                'max_score' => $taskPoints,
            ],
            [
                'step' => 4,
                'title' => 'Xulosa yozing',
                'description' => "Tajriba natijalarini tahlil qilib xulosa yozing.",
                'type' => 'conclusion',
                'max_score' => $taskPoints + $remainderPoints,
            ],
        ];
    }
}
