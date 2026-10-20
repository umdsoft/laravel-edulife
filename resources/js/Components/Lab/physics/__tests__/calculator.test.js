import { describe, it, expect } from 'vitest';
import { readFileSync, readdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import {
    CALCULATORS, CALCULATOR_IDS, calculate, calculatorInputs, calculatorResult, hasCalculator, parseInput, formatResult,
} from '../calculator.js';

// Seed data shared with database/seeders/LabExperimentSeeder.php
const LAB_DATA = new URL('../../../../../../database/seeders/data/lab/', import.meta.url);
const readJson = (rel) => JSON.parse(readFileSync(fileURLToPath(new URL(rel, LAB_DATA)), 'utf8'));
const CATALOG = readJson('formulas.json');
const EXPERIMENT_FILES = readdirSync(fileURLToPath(new URL('experiments/', LAB_DATA))).filter((f) => f.endsWith('.json'));
const EXPERIMENTS = EXPERIMENT_FILES.flatMap((f) => readJson(`experiments/${f}`));
const SEEDED_IDS = [...new Set(EXPERIMENTS.flatMap((e) => e.formulas))].sort();
const SIM_REGISTRY = readFileSync(fileURLToPath(new URL('../../simulationRegistry.js', import.meta.url)), 'utf8');

const PI = Math.PI;
const G = 9.81;
const K = 8.9875517862e9; // 1/(4πε0), CODATA 2022 (NIST)
const value = (id, inputs) => {
    const r = calculate(id, inputs);
    if (!r.ok) throw new Error(`${id}: ${r.error}`);
    return r.value;
};
const close = (actual, expected, rel = 1e-6) =>
    expect(Math.abs(actual - expected)).toBeLessThanOrEqual(rel * Math.max(1e-9, Math.abs(expected)) + 1e-12);

/**
 * One independent textbook evaluation per formula id: inputs in the UI units and the
 * expected result computed here by hand (not with the physics modules).
 */
const SAMPLES = {
    // Mexanika
    period: [{ L: 1, g: G }, 2 * PI * Math.sqrt(1 / G)],
    pendulum_exact: [{ L: 1, g: G, 'θ₀': 15 }, 2 * PI * Math.sqrt(1 / G) * (1 + (1 / 16) * (15 * PI / 180) ** 2 + (11 / 3072) * (15 * PI / 180) ** 4), 1e-5],
    gravity: [{ L: 1, T: 2 }, PI * PI],
    frequency: [{ T: 0.25 }, 4],
    height: [{ g: G, t: 2 }, 19.62],
    fall_time: [{ h: 5, g: G }, Math.sqrt(10 / G)],
    velocity: [{ g: G, t: 3 }, 29.43],
    impact_speed: [{ h: 5, g: G }, Math.sqrt(2 * G * 5)],
    gravity_from_h: [{ h: 5, t: 1 }, 10],
    newton_acceleration: [{ F: 10, m: 2, f: 4 }, 3],
    incline_acceleration: [{ 'θ': 30, 'μ': 0.1, g: G }, G * (0.5 - 0.1 * Math.sqrt(3) / 2)],
    incline_normal_force: [{ m: 2, g: G, 'θ': 60 }, 2 * G * 0.5],
    spring_period: [{ m: 0.5, k: 50 }, 2 * PI * 0.1],
    spring_constant: [{ m: 1, T: 2 * PI }, 1],
    spring_energy: [{ k: 50, A: 0.1 }, 0.25],
    projectile_range: [{ 'v₀': 20, 'θ': 45, 'h₀': 0, g: G }, 400 / G],
    projectile_max_height: [{ 'v₀': 20, 'θ': 90, 'h₀': 5, g: G }, 5 + 400 / (2 * G)],
    projectile_flight_time: [{ 'v₀': 20, 'θ': 30, 'h₀': 0, g: G }, 20 / G],
    momentum_total: [{ 'm₁': 2, 'v₁': 5, 'm₂': 1, 'v₂': -3 }, 7],
    collision_v1: [{ 'm₁': 1, 'm₂': 1, 'u₁': 4, 'u₂': 0, e: 1 }, 0],
    collision_v2: [{ 'm₁': 1, 'm₂': 1, 'u₁': 4, 'u₂': 0, e: 1 }, 4],
    collision_energy_loss: [{ 'm₁': 1, 'm₂': 1, 'u₁': 4, 'u₂': 0, e: 0 }, 4],
    centripetal_acceleration: [{ r: 2, 'ω': 3 }, 18],
    linear_speed: [{ r: 2, 'ω': 3 }, 6],
    centripetal_force: [{ m: 2, r: 2, 'ω': 3 }, 36],
    circular_period: [{ 'ω': PI }, 2],
    torque: [{ F: 10, r: 2, 'θ': 30 }, 10],
    rod_inertia: [{ m: 3, l: 2, d: 1 }, 1 + 3],
    angular_acceleration: [{ M: 6, I: 2 }, 3],
    potential_energy: [{ m: 2, g: G, h: 10 }, 196.2],
    kinetic_energy: [{ m: 2, v: 3 }, 9],
    mechanical_energy_landing: [{ m: 1, g: 10, h: 2, k: 0.25 }, 15],
    // Termodinamika
    linear_expansion: [{ 'α': 1e-5, 'L₀': 2, 'ΔT': 50 }, 1], // mm
    expanded_length: [{ 'α': 1e-5, 'L₀': 2, 'ΔT': 50 }, 2.001],
    sensible_heat: [{ m: 2, c: 4200, 'ΔT': 10 }, 84000],
    specific_heat_calc: [{ Q: 84000, m: 2, 'ΔT': 10 }, 4200],
    heating_time: [{ Q: 1000, P: 50 }, 20],
    latent_heat: [{ m: 2, 'λ': 334000 }, 668000],
    conduction_flux: [{ k: 200, 'ΔT': 50, d: 0.5 }, 20000],
    radiation_flux: [{ 'ε': 0.5, T: 400, 'T₀': 300 }, 0.5 * 5.670374419e-8 * (400 ** 4 - 300 ** 4)],
    // R = 8.314 462 618 J/(mol·K) (CODATA 2022, exact); V_m(273.15 K, 101.325 kPa) = 22.413 969 54 L/mol (CODATA)
    ideal_gas_volume: [{ n: 1, T: 273.15, P: 1 }, 22.41396954],
    ideal_gas_pressure: [{ n: 2, T: 300, V: 24.616 }, 2 * 8.314462618 * 300 / (0.024616 * 101325)],
    ideal_gas_temperature: [{ P: 1, V: 22.4, n: 1 }, 101325 * 0.0224 / 8.314462618],
    equilibrium_temperature: [{ 'm₁': 1, 'c₁': 4186, 't₁': 80, 'm₂': 1, 'c₂': 4186, 't₂': 20 }, 50],
    heat_released: [{ m: 0.5, c: 4000, 't₁': 80, t: 30 }, 100000],
    carnot_efficiency: [{ T_H: 500, T_C: 300 }, 40],
    isothermal_heat: [{ n: 1, T: 300, 'V₁': 1, 'V₂': Math.E }, 8.314462618 * 300],
    carnot_work: [{ n: 1, T_H: 500, T_C: 300, 'V₁': 1, 'V₂': Math.E }, 8.314462618 * 200],
    entropy_isothermal: [{ Q: 600, T: 300 }, 2],
    entropy_isobaric: [{ n: 1, 'Cₚ': 20, 'T₁': 300, Q: 6000 }, 20 * Math.log(600 / 300)],
    isobaric_final_temperature: [{ n: 2, 'Cₚ': 25, 'T₁': 300, Q: 5000 }, 400],
    adiabatic_temperature: [{ 'T₁': 300, 'V₁': 8, 'V₂': 1, 'γ': 5 / 3 }, 1200],
    // Elektr
    ohm: [{ U: 6, R: 100 }, 0.06],
    resistance: [{ U: 6, I: 0.06 }, 100],
    ohm_voltage: [{ I: 0.5, R: 20 }, 10],
    series_resistance: [{ 'R₁': 100, 'R₂': 200, 'R₃': 150 }, 450],
    parallel_resistance: [{ 'R₁': 300, 'R₂': 300, 'R₃': 300 }, 100],
    parallel_resistance_2: [{ 'R₂': 6, 'R₃': 3 }, 2],
    kirchhoff_i1: [{ 'ℰ': 12, 'R₁': 4, 'R₂': 6, 'R₃': 3 }, 2],
    kirchhoff_i2: [{ 'ℰ': 12, 'R₁': 4, 'R₂': 6, 'R₃': 3 }, 2 / 3],
    kirchhoff_i3: [{ 'ℰ': 12, 'R₁': 4, 'R₂': 6, 'R₃': 3 }, 4 / 3],
    rc_time_constant: [{ R: 1000, C: 100 }, 100], // ms
    rc_charging_voltage: [{ 'U₀': 10, R: 1000, C: 100, t: 100 }, 10 * (1 - Math.exp(-1))],
    rc_discharging_voltage: [{ 'U₀': 10, R: 1000, C: 100, t: 200 }, 10 * Math.exp(-2)],
    rc_time_to_fraction: [{ 'τ': 100, p: 50 }, 100 * Math.LN2],
    capacitor_charge: [{ C: 100, U: 12 }, 1.2], // mC
    capacitor_energy: [{ C: 100, U: 12 }, 7.2], // mJ
    power_ui: [{ U: 12, I: 2 }, 24],
    power_i2r: [{ I: 2, R: 6 }, 24],
    power_u2r: [{ U: 12, R: 6 }, 24],
    energy_kwh: [{ P: 2000, t: 3 }, 6],
    energy_cost: [{ P: 2000, t: 3, b: 650 }, 3900], // 650 so'm/kWh: UZ household tariff from 1 June 2026
    coulomb_force: [{ 'q₁': 1, 'q₂': -1, r: 1 }, K * 1e-12],
    point_charge_field: [{ q: 1, r: 1 }, K * 1e-6],
    two_charge_field: [{ 'q₁': 1, 'x₁': -1, 'y₁': 0, 'q₂': -1, 'x₂': 1, 'y₂': 0, x: 0, y: 0 }, 2 * K * 1e-6],
    diode_current: [{ U: 5, R: 100, U_f: 0.7 }, 43], // mA
    // Magnetizm
    wire_field: [{ I: 10, r: 0.05 }, 40], // µT
    dipole_axial_field: [{ p_m: 1, r: 0.1 }, 0.2], // mT
    solenoid_field: [{ N: 100, I: 2, l: 0.1, 'μr': 1 }, 1.25663706127e-6 * 1000 * 2 * 1000, 1e-9], // µ0 CODATA 2022 (NIST)
    faraday_emf: [{ N: 50, 'ΔΦ': 2, 'Δt': 0.1 }, -1],
    induced_current: [{ 'ℰ': 0.5, R: 10 }, 0.05],
    transformer_voltage: [{ 'U₁': 220, 'N₁': 100, 'N₂': 200 }, 440],
    transformer_secondary_current: [{ 'U₁': 220, 'N₁': 100, 'N₂': 200, R: 100 }, 4.4],
    transformer_primary_current: [{ 'U₁': 220, 'N₁': 100, 'N₂': 200, R: 100 }, 8.8],
    generator_peak_emf: [{ N: 100, B: 0.5, S: 0.008, n: 60 }, 100 * 0.5 * 0.008 * 2 * PI],
    rms_from_peak: [{ 'ℰ₀': Math.SQRT2 }, 1],
    rpm_to_frequency: [{ n: 3000 }, 50],
    ampere_force: [{ N: 50, B: 0.25, I: 2, l: 0.05 }, 1.25],
    motor_max_torque: [{ N: 50, B: 0.25, I: 2, S: 20 }, 0.05],
    motor_rpm: [{ I: 2, B: 0.25 }, ((2 / PI) * 50 * 0.25 * 2 * 0.002 / 5e-4) * 60 / (2 * PI)], // 607.93 (b = 5e-4 N·m·s)
    // Optika
    reflection_angle: [{ 'α': 35 }, 35],
    normal_reflectance: [{ 'n₁': 1, 'n₂': 1.5 }, 4],
    snell_angle: [{ 'n₁': 1, 'n₂': Math.SQRT2, 'α': 45 }, 30],
    critical_angle: [{ 'n₁': 2, 'n₂': 1 }, 30],
    refractive_index_from_angles: [{ 'n₁': 1, 'α': 45, 'γ': 30 }, Math.SQRT2],
    cauchy_index: [{ 'λ': 500 }, 1.5046 + 4.2e-15 / (500e-9) ** 2],
    cauchy_dispersion: [{ 'λ₁': 400, 'λ₂': 700 }, 4.2e-15 * (1 / (400e-9) ** 2 - 1 / (700e-9) ** 2)],
    // SCHOTT N-BK7 data sheet: n(486.1 nm) = nF = 1.52238, n(656.3 nm) = nC = 1.51432, nF − nC = 0.008054
    bk7_dispersion: [{ 'λ₁': 486.1, 'λ₂': 656.3 }, 0.008054, 2e-3],
    prism_min_deviation: [{ n: Math.SQRT2, A: 60 }, 30],
    prism_angular_dispersion: [{ A: 60, 'λ₁': 589.3, 'λ₂': 589.3 }, 0],
    thin_lens_image: [{ F: 10, d: 20 }, 20],
    lens_magnification: [{ F: 10, d: 15 }, -2],
    optical_power: [{ F: 25 }, 4],
    mirror_image: [{ F: 20, d: 30 }, 60],
    mirror_magnification: [{ F: -20, d: 20 }, 0.5],
    fringe_spacing: [{ 'λ': 500, L: 2, d: 1 }, 1], // mm
    wavelength_from_fringes: [{ 'Δy': 1, L: 2, d: 1 }, 500], // nm
    single_slit_minimum: [{ m: 1, 'λ': 500, a: 0.001 }, 30],
    central_maximum_width: [{ 'λ': 500, a: 0.001, L: 1 }, 2 * Math.tan(PI / 6) * 1000],
    brewster_angle: [{ 'n₁': 1, 'n₂': 1 }, 45],
    malus_law: [{ 'I₀': 100, 'φ': 60 }, 25],
    reflection_polarization_degree: [{ 'n₁': 1, 'n₂': 1.5, 'α': Math.atan(1.5) * 180 / PI }, 100],
    analyzer_transmission: [{ 'n₁': 1, 'n₂': 1.5, 'α': 0, 'φ': 0 }, 2],
    // To'lqinlar
    wave_speed: [{ 'λ': 2, 'ν': 5 }, 10],
    wavelength_from_speed: [{ v: 340, 'ν': 170 }, 2],
    wave_period: [{ 'ν': 50 }, 0.02],
    harmonic_frequency: [{ n: 3, v: 4, L: 1 }, 6],
    standing_wavelength: [{ n: 4, L: 2 }, 1],
    natural_frequency: [{ k: 4 * PI * PI, m: 1 }, 1],
    resonance_amplitude: [{ 'ν': 2, 'ν₀': 2, 'ζ': 0.1 }, 5],
    quality_factor: [{ 'ζ': 0.05 }, 10],
    resonance_peak_frequency: [{ 'ν₀': 2, 'ζ': 0.5 }, 2 * Math.sqrt(0.5)],
    doppler: [{ 'ν': 440, v: 340, v_k: 0, v_m: 40 }, 440 * 340 / 300],
    nodal_lines: [{ d: 10, 'λ': 4 }, 4],
    em_frequency: [{ 'λ': 299.792458 }, 1e15],
    photon_energy_ev: [{ 'λ': 1000 }, 6.62607015e-34 * 299792458 / (1e-6 * 1.602176634e-19)],
    beat_frequency: [{ 'ν₁': 440, 'ν₂': 444 }, 4],
    beat_period: [{ 'ν₁': 440, 'ν₂': 444 }, 0.25],
    // Atom fizikasi
    photon_energy_freq: [{ 'ν': 1000 }, 6.62607015e-34 * 1e15 / 1.602176634e-19],
    photoelectric_ke: [{ 'ν': 1000, W: 2 }, 6.62607015e-34 * 1e15 / 1.602176634e-19 - 2],
    threshold_frequency: [{ W: 2 }, 2 * 1.602176634e-19 / 6.62607015e-34 / 1e12],
    stopping_voltage: [{ 'ν': 1000, W: 2 }, 6.62607015e-34 * 1e15 / 1.602176634e-19 - 2],
    // hydrogen with reduced mass (CODATA 2022): Ry_H = 13.605693123/(1 + 1/1836.152673) eV,
    // R_H = 10973731.568157/(1 + 1/1836.152673) m⁻¹ (school 13.6 eV / 1.097e7 agree to 0.03 %)
    bohr_energy: [{ n: 2 }, -13.60569312299 / (1 + 1 / 1836.152673426) / 4],
    bohr_radius: [{ n: 2 }, 4 * 0.529177210544],
    rydberg_wavelength: [{ 'n₁': 2, 'n₂': 3 }, 1e9 / (10973731.568157 / (1 + 1 / 1836.152673426) * (1 / 4 - 1 / 9))],
    transition_energy: [{ 'n₁': 1, 'n₂': 2 }, 0.75 * 13.60569312299 / (1 + 1 / 1836.152673426)],
    remaining_nuclei: [{ 'N₀': 1000, t: 30, 'T½': 10 }, 125],
    decay_constant: [{ 'T½': 1 }, Math.LN2],
    activity: [{ 'N₀': 1000, t: 10, 'T½': 10 }, 500 * Math.LN2 / 10],
    mass_defect_energy: [{ 'Δm': 1 }, 931.49410372], // CODATA 2022
    fission_energy_mass: [{ m: 235, 'E₁': 200 }, 6.02214076e23 * 200e6 * 1.602176634e-19],
    bragg_angle: [{ n: 1, 'λ': 0.1, d: 0.1 }, 30],
    bragg_spacing: [{ n: 2, 'λ': 0.1, 'θ': 30 }, 0.2],
};

describe('calculator registry ↔ seeded formula catalog', () => {
    it('every seeded experiment formula id is in the catalog and has a calculator', () => {
        expect(SEEDED_IDS.length).toBeGreaterThan(100);
        for (const id of SEEDED_IDS) {
            expect(CATALOG[id], `catalog entry for ${id}`).toBeTruthy();
            expect(hasCalculator(id), `calculator for ${id}`).toBe(true);
        }
    });

    it('catalog and calculator contain exactly the same ids', () => {
        expect(Object.keys(CATALOG).sort()).toEqual([...CALCULATOR_IDS].sort());
    });

    it('every catalog formula is used by at least one experiment', () => {
        expect(Object.keys(CATALOG).sort()).toEqual(SEEDED_IDS);
    });

    it.each(Object.keys(CATALOG))('%s: variables list = result + inputs with matching units', (id) => {
        const f = CATALOG[id];
        expect(typeof f.name).toBe('string');
        expect(f.name.length).toBeGreaterThan(0);
        expect(f.latex.length).toBeGreaterThan(0);
        expect(f.description.length).toBeGreaterThan(0);
        const vars = Object.fromEntries(f.variables.map((v) => [v.symbol, v]));
        expect(Object.keys(vars).length, 'unique symbols').toBe(f.variables.length);
        const res = calculatorResult(id);
        expect(vars[res.symbol], `result ${res.symbol}`).toBeTruthy();
        expect(vars[res.symbol].unit).toBe(res.unit);
        for (const spec of calculatorInputs(id)) {
            expect(vars[spec.symbol], `input ${spec.symbol}`).toBeTruthy();
            expect(vars[spec.symbol].unit, `unit of ${spec.symbol}`).toBe(spec.unit);
            expect(vars[spec.symbol].name.length).toBeGreaterThan(0);
        }
        expect(f.variables.length).toBe(calculatorInputs(id).length + 1);
    });
});

describe('calculator dispatch: every formula id computes the textbook value', () => {
    it('has an independent sample for every calculator id', () => {
        expect(Object.keys(SAMPLES).sort()).toEqual([...CALCULATOR_IDS].sort());
    });

    it.each(Object.keys(SAMPLES))('%s', (id) => {
        const [inputs, expected, rel = 1e-6] = SAMPLES[id];
        const r = calculate(id, inputs);
        expect(r.ok, r.error).toBe(true);
        expect(r.unit).toBe(CALCULATORS[id].result.unit);
        close(r.value, expected, rel);
    });

    it('accepts numeric strings (comma or dot decimal separator)', () => {
        close(value('ohm', { U: '6', R: '100' }), 0.06);
        close(value('spring_period', { m: '0,5', k: ' 50 ' }), 2 * PI * 0.1);
        expect(parseInput('')).toBeNull();
        expect(parseInput('abc')).toBeNull();
        expect(parseInput(Infinity)).toBeNull();
    });
});

describe('calculator never returns a silent 0', () => {
    it('unknown formula id → clear error', () => {
        const r = calculate('no_such_formula', { x: 1 });
        expect(r.ok).toBe(false);
        expect(r.error).toMatch(/kalkulyator mavjud emas/);
        expect(r.value).toBeUndefined();
    });

    it('missing / empty / non-numeric input → error naming the variable', () => {
        expect(calculate('ohm', { U: 6 })).toMatchObject({ ok: false, error: expect.stringContaining('R') });
        expect(calculate('ohm', { U: '', R: 100 })).toMatchObject({ ok: false, error: expect.stringContaining('U') });
        expect(calculate('ohm', { U: 'abc', R: 100 }).ok).toBe(false);
        expect(calculate('ohm', {}).ok).toBe(false);
        expect(calculate('ohm').ok).toBe(false);
    });

    it('domain constraints are reported instead of computing nonsense', () => {
        expect(calculate('period', { L: -1, g: 9.81 })).toMatchObject({ ok: false, error: expect.stringContaining('musbat') });
        expect(calculate('ohm', { U: 6, R: 0 }).ok).toBe(false);
        expect(calculate('resistance', { U: 6, I: 0 }).ok).toBe(false);
        expect(calculate('bohr_energy', { n: 1.5 })).toMatchObject({ ok: false, error: expect.stringContaining('butun') });
        expect(calculate('collision_v1', { 'm₁': 1, 'm₂': 1, 'u₁': 1, 'u₂': 0, e: 2 }).ok).toBe(false);
        expect(calculate('carnot_efficiency', { T_H: 300, T_C: 600 }).ok).toBe(false);
    });

    it('physically impossible cases → explanatory error', () => {
        expect(calculate('snell_angle', { 'n₁': 1.5, 'n₂': 1, 'α': 60 })).toMatchObject({ ok: false, error: expect.stringContaining("To'liq ichki qaytish") });
        expect(calculate('critical_angle', { 'n₁': 1, 'n₂': 1.5 }).ok).toBe(false);
        expect(calculate('thin_lens_image', { F: 10, d: 10 })).toMatchObject({ ok: false, error: expect.stringContaining('fokus') });
        expect(calculate('mirror_image', { F: 20, d: 20 }).ok).toBe(false);
        expect(calculate('bragg_angle', { n: 4, 'λ': 0.154, d: 0.282 }).ok).toBe(false);
        expect(calculate('rydberg_wavelength', { 'n₁': 3, 'n₂': 2 }).ok).toBe(false);
        expect(calculate('transition_energy', { 'n₁': 3, 'n₂': 3 }).ok).toBe(false);
        expect(calculate('doppler', { 'ν': 440, v: 343, v_k: 0, v_m: 400 }).ok).toBe(false);
        expect(calculate('beat_period', { 'ν₁': 440, 'ν₂': 440 }).ok).toBe(false);
        expect(calculate('two_charge_field', { 'q₁': 1, 'x₁': 0, 'y₁': 0, 'q₂': 1, 'x₂': 1, 'y₂': 0, x: 0, y: 0 }).ok).toBe(false);
        expect(calculate('prism_min_deviation', { n: 2.5, A: 90 }).ok).toBe(false);
        expect(calculate('single_slit_minimum', { m: 3, 'λ': 600, a: 0.001 }).ok).toBe(false);
    });

    it('a physical zero is returned with an explanatory note (no photoemission)', () => {
        const r = calculate('photoelectric_ke', { 'ν': 400, W: 3 });
        expect(r.ok).toBe(true);
        expect(r.value).toBe(0);
        expect(r.note).toMatch(/fotoeffekt yuz bermaydi/);
    });

    it('formatResult prints 5 significant digits / scientific notation, never toFixed(4) zeros', () => {
        expect(formatResult(2.0146938)).toBe('2.0147');
        expect(formatResult(5.4508e14)).toBe('5.4508e+14');
        expect(formatResult(2.5133e-4)).toBe('2.5133e-4');
        expect(formatResult(0)).toBe('0');
        expect(formatResult(NaN)).toBe('—');
    });
});

describe('seeded experiments: expected task answers are reproduced by the physics', () => {
    const TYPES = ['instruction', 'measurement', 'calculation', 'conclusion', 'graph_analysis', 'circuit_build'];
    const registryTypes = [...SIM_REGISTRY.matchAll(/^\s*'([a-z_]+)':\s*[A-Za-z]+Simulation,/gm)].map((m) => m[1]);

    it('every simulation in the registry is reachable from an experiment', () => {
        expect(registryTypes.length).toBe(59);
        const seededTypes = new Set(EXPERIMENTS.map((e) => e.simulation_type));
        for (const t of registryTypes) expect(seededTypes.has(t), t).toBe(true);
        for (const t of seededTypes) expect(registryTypes, t).toContain(t);
        expect(new Set(EXPERIMENTS.map((e) => e.slug)).size).toBe(EXPERIMENTS.length);
    });

    const tasks = EXPERIMENTS.flatMap((e) => e.tasks.map((t, i) => [`${e.slug} #${i + 1} ${t.title}`, e, t]));

    it.each(tasks)('%s', (_label, exp, task) => {
        expect(TYPES).toContain(task.type);
        if (!task.expected) return;
        const ex = task.expected;
        expect(['measurement', 'calculation']).toContain(ex.source);
        expect(ex.tolerance_percent).toBeGreaterThan(0);
        expect(exp.formulas, 'check formula must be offered in this experiment').toContain(ex.check.formula_id);
        if (ex.source === 'measurement') {
            expect(task.type).toBe('measurement');
            expect(typeof ex.measurement).toBe('string');
        } else {
            expect(task.type).toBe('calculation');
            expect(ex.unit).toBe(calculatorResult(ex.check.formula_id).unit);
        }
        const computed = value(ex.check.formula_id, ex.check.inputs) * (ex.check.factor ?? 1);
        const tol = (ex.check.tolerance_percent ?? 0.1) / 100;
        expect(Math.abs(computed - ex.value)).toBeLessThanOrEqual(tol * Math.abs(ex.value) + 1e-9);
    });
});
