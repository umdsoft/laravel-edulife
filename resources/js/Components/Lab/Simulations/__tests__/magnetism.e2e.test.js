/**
 * End-to-end verification of the magnetism simulations (headless harness, real SFCs).
 *
 * Every emitted measurement is compared with an INDEPENDENT recomputation written here
 * (textbook formulas with µ0 from CODATA 2022 — not the physics module functions), and
 * every seeded measurement task of database/seeders/data/lab/experiments/magnetism.json
 * is reproduced the way the server checks it (LabTaskChecker: name prefix, unit with SI
 * prefixes, relative tolerance), for ≥ 30 repeated readings.
 */
import { describe, it, expect } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { mountSimulation } from './mountSimulation.js';
import { teslameterMaxError, labDmmMaxError, LAB_DMM, OSCILLOSCOPE } from '../../physics/magnetism.js';
import { tachometerMaxError } from '../../physics/electricity.js';
import { maxReadingError } from '../../physics/instruments.js';

// runtime-dom's v-model directive checks `rootNode instanceof Document || … ShadowRoot` when a
// bound ref changes; the headless harness has no DOM classes, so provide inert ones here.
globalThis.Document ??= class Document {};
globalThis.ShadowRoot ??= class ShadowRoot {};

const EXPERIMENTS = JSON.parse(readFileSync(fileURLToPath(new URL('../../../../../../database/seeders/data/lab/experiments/magnetism.json', import.meta.url)), 'utf8'));
const exp = (slug) => EXPERIMENTS.find((e) => e.slug === slug);

// ── independent physics (hand formulas) ──
const MU0 = 1.25663706127e-6; // NIST CODATA 2022
const PI = Math.PI;

// ── server rules (app/Services/Lab/LabTaskChecker.php) ──
const normName = (s) => String(s).replace(/[ʻʼ’‘`´]/g, "'").replace(/\s+/g, ' ').trim().toLowerCase();
const BASES = ['V/m', 'N·m', 'Hz', 'eV', 'Bq', 'Pa', 'Ω', 'W', 'J', 'N', 'V', 'A', 'C', 'T', 's', 'm', 'g', 'L'];
const PREFIXES = { '': 1, T: 1e12, G: 1e9, M: 1e6, k: 1e3, c: 1e-2, m: 1e-3, 'µ': 1e-6, n: 1e-9, p: 1e-12 };
function parseUnit(u) {
    for (const base of BASES) {
        if (!u.endsWith(base)) continue;
        const prefix = u.slice(0, u.length - base.length);
        if (!(prefix in PREFIXES)) continue;
        if (prefix === 'c' && !['m', 'L'].includes(base)) continue;
        return [base, PREFIXES[prefix]];
    }
    return null;
}
function unitFactor(from, to) {
    const f = from.replace(/\s+/g, '').replace('μ', 'µ');
    const t = to.replace(/\s+/g, '').replace('μ', 'µ');
    if (f === t) return 1;
    const a = parseUnit(f);
    const b = parseUnit(t);
    if (!a || !b || a[0] !== b[0]) return null;
    return a[1] / b[1];
}
const withinTolerance = (value, unit, expected) => {
    const f = unitFactor(unit, expected.unit);
    expect(f).not.toBeNull();
    return Math.abs(value * f - expected.value) <= (Math.abs(expected.value) * expected.tolerance_percent) / 100 + 1e-12;
};

/** Patches the canvas context so the test can read every drawn text. */
function captureCanvasText(sim) {
    const texts = [];
    const ctx = sim.get('ctx');
    ctx.fillText = (s) => texts.push(String(s));
    return { texts, clear: () => texts.splice(0), find: (re) => texts.find((t) => re.test(t)) };
}

async function withSim(name, opts, fn) {
    const sim = await mountSimulation(name, opts);
    try {
        await fn(sim);
        expect(sim.errors.map((e) => e.message)).toEqual([]);
        expect(sim.warnings).toEqual([]);
        expect(sim.text()).not.toMatch(/\bNaN\b|undefined|Infinity/);
    } finally {
        sim.unmount();
    }
}

const REPEATS = 30;

// ─────────────────────────────────────────────────────────────────────────────
// Electromagnet
// ─────────────────────────────────────────────────────────────────────────────

/** Saturating core model written out independently (piecewise linear + tanh knee). */
function electromagnetB(N, I, core) {
    const mat = { air: [1, Infinity, Infinity], iron: [5000, 2.0, 1.2], ferrite: [2000, 0.45, 0.27] }[core];
    const [mur, Js, Jk] = mat;
    const H = (N / 0.1) * I;
    const Jlin = MU0 * (mur - 1) * H;
    let J = Jlin;
    if (Jlin > Jk) J = Jk + (Js - Jk) * Math.tanh((Jlin - Jk) / (Js - Jk));
    return { B: MU0 * H + J, linear: MU0 * mur * H };
}
const coreFor = (mur) => ({ 1: 'air', 5000: 'iron', 2000: 'ferrite' }[mur]);

describe('ElectromagnetSimulation (electromagnet)', () => {
    const e = exp('electromagnet');
    const tasks = e.tasks.filter((t) => t.type === 'measurement');

    it('has the 4 seeded measurement tasks', () => {
        expect(tasks).toHaveLength(4);
    });

    it.each(tasks.map((t) => [t.title, t]))('task "%s": ≥ 30 teslameter readings pass the server check', async (_title, task) => {
        const { N, I, l, 'μr': mur } = task.expected.check.inputs;
        expect(l).toBe(0.1);
        const exact = MU0 * mur * (N / l) * I * 1000; // mT, textbook formula
        expect(Math.abs(task.expected.value - exact) / exact).toBeLessThan(5e-5); // 5 significant digits
        // the task setting is in the linear part of the core curve (model = formula exactly)
        expect(electromagnetB(N, I, coreFor(mur)).B * 1000).toBeCloseTo(exact, 9);
        // HARD RULE: worst reading error ≤ ½ tolerance
        expect(teslameterMaxError(task.expected.value)).toBeLessThanOrEqual(0.5 * (task.expected.tolerance_percent / 100) * task.expected.value);
        await withSim('ElectromagnetSimulation', { config: e.simulation_config, currentTask: task }, async (sim) => {
            await sim.set({ coilTurns: N, current: I, coreType: coreFor(mur) });
            for (let k = 0; k < REPEATS; k++) sim.call('measure');
            const own = sim.measurements.filter((m) => normName(m.name).startsWith(normName(task.expected.measurement)));
            expect(own).toHaveLength(REPEATS);
            for (const m of own) {
                expect(typeof m.value).toBe('number');
                expect(withinTolerance(m.value, m.unit, task.expected)).toBe(true);
                expect(Math.abs(m.value - exact)).toBeLessThanOrEqual(teslameterMaxError(exact) + 1e-9);
            }
            expect(new Set(own.map((m) => m.value)).size).toBeGreaterThan(1); // realistic scatter
        });
    });

    it('sweep (all cores, N 50…500, I 0.1…5 A): readings follow the saturating core model; displayed formula value', async () => {
        await withSim('ElectromagnetSimulation', { config: e.simulation_config, currentTask: e.tasks[0] }, async (sim) => {
            const cap = captureCanvasText(sim);
            for (const core of ['air', 'ferrite', 'iron']) {
                for (const N of [50, 250, 500]) {
                    for (const I of [0.1, 2.5, 5]) {
                        // eslint-disable-next-line no-await-in-loop
                        await sim.set({ coilTurns: N, current: I, coreType: core });
                        sim.call('measure');
                        const m = sim.measurements[sim.measurements.length - 1];
                        const { B, linear } = electromagnetB(N, I, core);
                        expect(m.unit).toBe('mT');
                        expect(Math.abs(m.value - B * 1000)).toBeLessThanOrEqual(teslameterMaxError(B * 1000) + 1e-9);
                        if (core !== 'air') {
                            const ceiling = ((core === 'iron' ? 2.0 : 0.45) + MU0 * (N / 0.1) * I) * 1000; // Bsat + µ0H
                            expect(m.value).toBeLessThanOrEqual(ceiling + teslameterMaxError(ceiling));
                        }
                        cap.clear();
                        // eslint-disable-next-line no-await-in-loop
                        await sim.advance(1 / 30);
                        expect(cap.find(/^µ₀µᵣnI = /)).toBe(`µ₀µᵣnI = ${(linear * 1000).toFixed(3)} mT`);
                    }
                }
            }
            // deep saturation: 157 T linear → the teslameter reads ≈ 2.03 T, never 157 T
            await sim.set({ coilTurns: 500, current: 5, coreType: 'iron' });
            sim.call('measure');
            const deep = sim.measurements[sim.measurements.length - 1].value;
            expect(deep).toBeGreaterThan(2000);
            expect(deep).toBeLessThan(2040);
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Electric motor
// ─────────────────────────────────────────────────────────────────────────────

const motorRpm = (I, B) => {
    const avgTorque = (2 / PI) * 50 * B * I * (0.05 * 0.04); // N·m
    return ((avgTorque / 5e-4) * 60) / (2 * PI);
};

describe('ElectricMotorSimulation (electric-motor)', () => {
    const e = exp('electric-motor');
    const tasks = e.tasks.filter((t) => t.type === 'measurement');

    it('has the 2 seeded measurement tasks with realistic speeds (hundreds of RPM)', () => {
        expect(tasks).toHaveLength(2);
        for (const t of tasks) expect(t.expected.value).toBeGreaterThan(300);
    });

    it.each(tasks.map((t) => [t.title, t]))('task "%s": ≥ 30 tachometer readings after spin-up pass the server check', async (_title, task) => {
        const { I, B } = task.expected.check.inputs;
        const exact = motorRpm(I, B);
        expect(Math.abs(task.expected.value - exact) / exact).toBeLessThan(5e-5);
        expect(tachometerMaxError(task.expected.value)).toBeLessThanOrEqual(0.5 * (task.expected.tolerance_percent / 100) * task.expected.value);
        await withSim('ElectricMotorSimulation', { config: e.simulation_config, currentTask: task }, async (sim) => {
            await sim.set({ current: I, magnetStrength: (B / 0.5) * 100 });
            await sim.advance(3.2); // "kamida 3 s" after the change
            for (let k = 0; k < REPEATS; k++) sim.call('measure');
            const own = sim.measurements.filter((m) => normName(m.name).startsWith(normName(task.expected.measurement)));
            expect(own).toHaveLength(REPEATS);
            for (const m of own) {
                expect(m.unit).toBe(task.expected.unit); // RPM: no prefixes, exact match
                expect(withinTolerance(m.value, m.unit, task.expected)).toBe(true);
                // spin-up residue e^(−3.2/0.5) = 0.17 % + tachometer error ≤ ½ tolerance
                expect(Math.abs(m.value - exact) / exact).toBeLessThan(0.01);
            }
            expect(new Set(own.map((m) => m.value)).size).toBeGreaterThan(1);
        });
    });

    it('spin-up follows ω(t) = ωss(1 − e^(−t/τm)); sweep of I and B; displayed values', async () => {
        await withSim('ElectricMotorSimulation', { config: e.simulation_config, currentTask: e.tasks[0] }, async (sim) => {
            await sim.advance(0.5);
            const t = sim.get('time');
            sim.call('measure');
            const ss = motorRpm(2, 0.25);
            const expectNow = ss * (1 - Math.exp(-t / 0.5));
            expect(Math.abs(sim.measurements[0].value - expectNow)).toBeLessThanOrEqual(tachometerMaxError(expectNow) + 1e-9);
            for (const [I, pct] of [[0.5, 20], [5, 100], [3, 60]]) {
                // eslint-disable-next-line no-await-in-loop
                await sim.set({ current: I, magnetStrength: pct });
                // eslint-disable-next-line no-await-in-loop
                await sim.advance(5);
                sim.call('measure');
                const m = sim.measurements[sim.measurements.length - 1];
                const want = motorRpm(I, (pct / 100) * 0.5);
                expect(m.name).toBe(`Aylanish tezligi (I=${I} A, B=${((pct / 100) * 0.5).toFixed(2)} T)`);
                expect(Math.abs(m.value - want) / want).toBeLessThan(0.004);
            }
            // full range: 61 … 3040 RPM, supply voltage ≤ 15 V
            expect(motorRpm(0.5, 0.1)).toBeCloseTo(60.79, 2);
            expect(motorRpm(5, 0.5)).toBeCloseTo(3039.6, 1);
            const cap = captureCanvasText(sim);
            await sim.set({ current: 2, magnetStrength: 50 });
            await sim.advance(4);
            cap.clear();
            await sim.advance(1 / 30);
            expect(cap.find(/^\(barqaror:/)).toBe('(barqaror: 608 RPM)');
            const k = (2 / PI) * 50 * 0.25 * 0.002;
            const U = 2 * 0.5 + k * ((k * 2) / 5e-4);
            expect(cap.find(/^Manba: /)).toBe(`Manba: U = IR + ε̄ = ${U.toFixed(2)} V (R = 0.5 Ω)`);
            sim.call('measure');
            cap.clear();
            await sim.advance(1 / 30);
            expect(cap.find(/^Taxometr: /)).toBe(`Taxometr: ${sim.measurements[sim.measurements.length - 1].value} RPM`);
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Transformer
// ─────────────────────────────────────────────────────────────────────────────

describe('TransformerSimulation (transformer)', () => {
    const e = exp('transformer');
    const tasks = e.tasks.filter((t) => t.type === 'measurement');

    it('has the 3 seeded measurement tasks; mains 220 V and 380 V line voltage', () => {
        expect(tasks).toHaveLength(3);
        expect(tasks.map((t) => t.expected.check.inputs['U₁'])).toEqual([220, 220, 380]);
    });

    it.each(tasks.map((t) => [t.title, t]))('task "%s": ≥ 30 AC-voltmeter readings pass the server check', async (_title, task) => {
        const { 'U₁': U1, 'N₁': N1, 'N₂': N2 } = task.expected.check.inputs;
        const exact = (U1 * N2) / N1;
        expect(task.expected.value).toBeCloseTo(exact, 9);
        expect(labDmmMaxError(task.expected.value)).toBeLessThanOrEqual(0.5 * (task.expected.tolerance_percent / 100) * task.expected.value);
        await withSim('TransformerSimulation', { config: e.simulation_config, currentTask: task }, async (sim) => {
            await sim.set({ primaryTurns: N1, secondaryTurns: N2, primaryVoltage: U1 });
            for (let k = 0; k < REPEATS; k++) sim.call('measure');
            const own = sim.measurements.filter((m) => normName(m.name).startsWith(normName(task.expected.measurement)));
            expect(own).toHaveLength(REPEATS);
            for (const m of own) {
                expect(withinTolerance(m.value, m.unit, task.expected)).toBe(true);
                expect(Math.abs(m.value - exact)).toBeLessThanOrEqual(labDmmMaxError(exact) + 1e-9);
            }
        });
    });

    it('sweep: U₂ = U₁N₂/N₁, I₂ = U₂/R, I₁ = I₂N₂/N₁ (ideal) read by the meters', async () => {
        await withSim('TransformerSimulation', { config: e.simulation_config, currentTask: e.tasks[0] }, async (sim) => {
            for (const [N1, N2, U1, R] of [[50, 500, 110, 1000], [300, 50, 380, 10], [100, 200, 220, 100], [75, 50, 110, 470]]) {
                // eslint-disable-next-line no-await-in-loop
                await sim.set({ primaryTurns: N1, secondaryTurns: N2, primaryVoltage: U1, loadResistance: R });
                sim.call('measure');
                const [u2, i2, i1] = sim.measurements.slice(-3);
                const U2 = (U1 * N2) / N1;
                expect(u2.name).toBe('Ikkilamchi kuchlanish');
                expect(Math.abs(u2.value - U2)).toBeLessThanOrEqual(labDmmMaxError(U2) + 1e-9);
                expect(i2.unit).toBe('A');
                expect(Math.abs(i2.value - U2 / R)).toBeLessThanOrEqual(labDmmMaxError(U2 / R) + 1e-9);
                expect(i1.name).toBe('Birlamchi tok');
                expect(Math.abs(i1.value - ((U2 / R) * N2) / N1)).toBeLessThanOrEqual(labDmmMaxError(((U2 / R) * N2) / N1) + 1e-9);
            }
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Bar magnet field (teslameter on the axis)
// ─────────────────────────────────────────────────────────────────────────────

describe('MagneticFieldSimulation (magnetic-field-lines)', () => {
    const e = exp('magnetic-field-lines');
    it('O\'lchash: axial teslameter reading = two-pole field µ0q/4π·[1/(r−ℓ/2)² − 1/(r+ℓ/2)²]', async () => {
        await withSim('MagneticFieldSimulation', { config: e.simulation_config, currentTask: e.tasks[0] }, async (sim) => {
            for (const pct of [10, 50, 100]) {
                for (const r of [6, 10, 18]) {
                    // eslint-disable-next-line no-await-in-loop
                    await sim.set({ magnetStrength: pct, probeDistance: r });
                    sim.call('measure');
                    const m = sim.measurements[sim.measurements.length - 1];
                    const moment = (pct / 100) * 2;
                    const ell = 0.085;
                    const rr = r / 100;
                    const B = ((MU0 * (moment / ell)) / (4 * PI)) * (1 / (rr - ell / 2) ** 2 - 1 / (rr + ell / 2) ** 2) * 1000; // mT
                    expect(m.name).toBe(`Magnit induksiya (o'qda, r = ${r} sm)`);
                    expect(m.unit).toBe('mT');
                    expect(Math.abs(m.value - B)).toBeLessThanOrEqual(teslameterMaxError(B) + 1e-9);
                }
            }
            // task 3 set-up (p_m = 1 A·m², r = 10 cm): the near-field reading (0.30 mT) exceeds the
            // far-field dipole formula (0.2 mT) — the documented limit of the formula
            await sim.set({ magnetStrength: 50, probeDistance: 10 });
            sim.call('measure');
            expect(sim.measurements[sim.measurements.length - 1].value).toBeCloseTo(0.3, 9);
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// EM induction / Lenz: peak-hold readings
// ─────────────────────────────────────────────────────────────────────────────

/** Independent peak |ε| = N·v·(µ0q/2)·max|a²/(xN²+a²)^{3/2} − a²/(xS²+a²)^{3/2}| (brute force). */
function peakEmf({ N, v, m, ell, a, xMin, xMax }) {
    const q = m / ell;
    let best = 0;
    for (let i = 0; i <= 40000; i++) {
        const X = xMin + ((xMax - xMin) * i) / 40000;
        const xN = X - ell / 2;
        const xS = X + ell / 2;
        const g = a * a / (xN * xN + a * a) ** 1.5 - a * a / (xS * xS + a * a) ** 1.5;
        best = Math.max(best, Math.abs(N * v * 0.5 * MU0 * q * g));
    }
    return best;
}

describe('EMInductionSimulation (em-induction)', () => {
    const e = exp('em-induction');
    it('O\'lchash after a full stroke: peak EMF (mV) = N·v·max|dΦ/dX|, ∝ N and v', async () => {
        await withSim('EMInductionSimulation', { config: e.simulation_config, currentTask: e.tasks[0] }, async (sim) => {
            const cap = captureCanvasText(sim);
            for (const [N, v] of [[50, 2], [100, 5], [20, 1], [50, 4]]) {
                sim.call('reset');
                // eslint-disable-next-line no-await-in-loop
                await sim.set({ coilTurns: N, magnetSpeed: v });
                sim.call('start');
                // one stroke of 0.6 m at v, shown 20× slower
                // eslint-disable-next-line no-await-in-loop
                await sim.advance((0.6 / v) * 20 + 0.5);
                sim.call('measure');
                const m = sim.measurements[sim.measurements.length - 1];
                const want = peakEmf({ N, v, m: 3, ell: 0.1, a: 0.06, xMin: -0.3, xMax: 0.3 });
                expect(m.name).toBe(`EYuK amplitudasi (N=${N}, v=${v} m/s)`);
                expect(m.unit).toBe('mV');
                expect(Math.abs(m.value / 1000 - want)).toBeLessThanOrEqual(labDmmMaxError(want, LAB_DMM.peakSigmaRel) + 1e-9);
                cap.clear();
                // eslint-disable-next-line no-await-in-loop
                await sim.advance(1 / 30);
                expect(cap.find(/^mV-metr/)).toBe(`mV-metr (PEAK): ${m.value.toFixed(1)} mV`);
            }
            expect(peakEmf({ N: 50, v: 2, m: 3, ell: 0.1, a: 0.06, xMin: -0.3, xMax: 0.3 }) * 1000).toBeCloseTo(27.29, 2);
            // before the magnet reaches the coil the peak-hold shows less than the full peak
            sim.call('reset');
            await sim.set({ coilTurns: 50, magnetSpeed: 2 });
            sim.call('start');
            await sim.advance(1.5); // 0.075 s physical → 15 cm of the 30 cm approach
            sim.call('measure');
            expect(sim.measurements[sim.measurements.length - 1].value).toBeLessThan(10);
        });
    });
});

describe('LenzLawSimulation (lenz-law)', () => {
    const e = exp('lenz-law');
    it('O\'lchash: peak induced current = peak EMF / (10 Ω + 0.02 Ω·N)', async () => {
        await withSim('LenzLawSimulation', { config: e.simulation_config, currentTask: e.tasks[0] }, async (sim) => {
            for (const [N, v] of [[50, 3], [100, 6], [20, 1]]) {
                sim.call('reset');
                // eslint-disable-next-line no-await-in-loop
                await sim.set({ coilTurns: N, magnetSpeed: v });
                sim.call('start');
                // eslint-disable-next-line no-await-in-loop
                await sim.advance((0.4 / v) * 20 + 0.5);
                sim.call('measure');
                const [mi, me] = sim.measurements.slice(-2);
                const emf = peakEmf({ N, v, m: 3, ell: 0.085, a: 0.05, xMin: -0.2, xMax: 0.2 });
                const R = 10 + 0.02 * N;
                const ranges = [0.002, 0.02, 0.2, 2, 20];
                expect(mi.name).toBe(`Induksiya toki amplitudasi (N=${N}, v=${v} m/s)`);
                expect(mi.unit).toBe('mA');
                expect(Math.abs(mi.value / 1000 - emf / R)).toBeLessThanOrEqual(labDmmMaxError(emf / R, LAB_DMM.peakSigmaRel, ranges) + 1e-9);
                expect(me.unit).toBe('mV');
                expect(Math.abs(me.value / 1000 - emf)).toBeLessThanOrEqual(labDmmMaxError(emf, LAB_DMM.peakSigmaRel) + 1e-9);
            }
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────────
// Generator
// ─────────────────────────────────────────────────────────────────────────────

describe('GeneratorSimulation (electric-generator)', () => {
    const e = exp('electric-generator');
    it('O\'lchash (oscilloscope): ε0 = N·B·S·2πn/60, U_ef = ε0/√2, f = n/60', async () => {
        await withSim('GeneratorSimulation', { config: e.simulation_config, currentTask: e.tasks[0] }, async (sim) => {
            const cap = captureCanvasText(sim);
            for (const [n, N] of [[60, 100], [120, 200], [30, 50], [90, 125]]) {
                // eslint-disable-next-line no-await-in-loop
                await sim.set({ rotationSpeed: n, coilTurns: N });
                // eslint-disable-next-line no-await-in-loop
                await sim.advance(1);
                sim.call('measure');
                const [amp, rms, f] = sim.measurements.slice(-3);
                const e0 = N * 0.5 * 0.008 * 2 * PI * (n / 60);
                expect(amp.unit).toBe('V');
                expect(Math.abs(amp.value - e0)).toBeLessThanOrEqual(maxReadingError(e0, OSCILLOSCOPE.voltage) + 1e-9);
                expect(Math.abs(rms.value - e0 / Math.SQRT2)).toBeLessThanOrEqual(maxReadingError(e0, OSCILLOSCOPE.voltage) / Math.SQRT2 + 0.005 + 1e-9);
                expect(f.unit).toBe('Hz');
                expect(Math.abs(f.value - n / 60)).toBeLessThanOrEqual(maxReadingError(n / 60, OSCILLOSCOPE.frequency) + 1e-9);
                cap.clear();
                // eslint-disable-next-line no-await-in-loop
                await sim.advance(1 / 30);
                expect(cap.find(/^ε₀ = NBAω/)).toBe(`ε₀ = NBAω = ${e0.toFixed(2)} V`);
            }
            // calculation tasks of the experiment are what the screen shows
            expect(100 * 0.5 * 0.008 * 2 * PI).toBeCloseTo(e.tasks[1].expected.value, 4);
            expect(200 * 0.5 * 0.008 * 2 * PI * 2).toBeCloseTo(e.tasks[3].expected.value, 3);
        });
    });
});
