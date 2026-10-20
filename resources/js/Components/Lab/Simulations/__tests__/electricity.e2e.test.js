/**
 * End-to-end check of the 10 electricity simulations: the real SFCs are mounted with
 * the headless harness, the student's procedure is driven (set sliders → start →
 * wait → "O'lchash") and the EMITTED readings are compared with an independent
 * recomputation written here (not with ../../physics/electricity.js), with the
 * server's matching rules (LabTaskChecker: name prefix, SI-prefix unit conversion,
 * relative tolerance).
 */
import { describe, it, expect } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { mountSimulation } from './mountSimulation.js';
import { dmmMaxError, ELECTRIC_METERS, createSigFigMeter } from '../../physics/electricity.js';
import { maxReadingError } from '../../physics/instruments.js';

const EXPERIMENTS = JSON.parse(readFileSync(fileURLToPath(new URL(
    '../../../../../../database/seeders/data/lab/experiments/electricity.json', import.meta.url,
)), 'utf8'));
const bySlug = (slug) => EXPERIMENTS.find((e) => e.slug === slug);

// ── independent physics (CODATA 2022, hand-written) ──
const EPS0 = 8.8541878188e-12;
const K = 1 / (4 * Math.PI * EPS0);
const VT300 = (1.380649e-23 * 300) / 1.602176634e-19;
const DIODES = { // Is [A], n, Rs [Ω]
    silicon: [14.11e-9, 1.984, 0.03389], // 1N4001 (Motorola SPICE model)
    germanium: [1e-7, 1, 0],
    led: [1e-17, 2, 0],
};
/** Bisection on f(I) = I·(R + Rs) + n·VT·ln(1 + I/Is) − Vs (monotonic in I). */
function diodeCurrentIndependent(Vs, R, [Is, n, Rs]) {
    const f = (I) => I * (R + Rs) + n * VT300 * Math.log1p(I / Is) - Vs;
    let lo = -Is * (1 - 1e-15);
    let hi = Math.max(Vs / R, 0) + 1e-3;
    for (let k = 0; k < 400; k++) {
        const mid = (lo + hi) / 2;
        if (f(mid) > 0) hi = mid; else lo = mid;
    }
    return (lo + hi) / 2;
}
const fieldAt = (charges, x, y) => {
    let ex = 0;
    let ey = 0;
    for (const c of charges) {
        const dx = x - c.x;
        const dy = y - c.y;
        const r = Math.hypot(dx, dy);
        ex += (K * c.q * dx) / r ** 3;
        ey += (K * c.q * dy) / r ** 3;
    }
    return Math.hypot(ex, ey);
};

// ── server rules (app/Services/Lab/LabTaskChecker.php) ──
const PREFIX = { T: 1e12, G: 1e9, M: 1e6, k: 1e3, '': 1, c: 1e-2, m: 1e-3, 'µ': 1e-6, n: 1e-9, p: 1e-12 };
const BASES = ['V/m', 'N·m', 'Hz', 'eV', 'Bq', 'Pa', 'Ω', 'W', 'J', 'N', 'V', 'A', 'C', 'T', 's', 'm', 'g', 'L'];
const parseUnit = (u) => {
    for (const b of BASES) {
        if (!u.endsWith(b)) continue;
        const p = u.slice(0, u.length - b.length);
        if (!(p in PREFIX)) continue;
        if (p === 'c' && !['m', 'L'].includes(b)) continue;
        return [b, PREFIX[p]];
    }
    return null;
};
const unitFactor = (from, to) => {
    if (from === to) return 1;
    const a = parseUnit(from);
    const b = parseUnit(to);
    if (!a || !b || a[0] !== b[0]) return null;
    return a[1] / b[1];
};
const norm = (s) => String(s).toLowerCase().replace(/[ʻʼ’‘`´]/g, "'").replace(/\s+/g, ' ').trim();
const SI_FACTOR = { mA: 1e-3, A: 1, mV: 1e-3, V: 1, 'Ω': 1, 'kΩ': 1e3, mN: 1e-3, W: 1, 'kV/m': 1e3, 'V/m': 1 };
const toSI = (m) => Number(m.value) * SI_FACTOR[m.unit];

/** Asserts that the readings pass the server check of `task` and returns them. */
function expectPasses(task, readings) {
    const e = task.expected;
    expect(readings.length).toBeGreaterThanOrEqual(30);
    for (const m of readings) {
        expect(norm(m.name).startsWith(norm(e.measurement))).toBe(true);
        expect(String(m.value)).toMatch(/^-?\d+(\.\d+)?$/);
        const f = unitFactor(m.unit, e.unit);
        expect(f, `${m.unit} → ${e.unit}`).not.toBeNull();
        const v = Number(m.value) * f;
        expect(Math.abs(v - e.value)).toBeLessThanOrEqual((e.tolerance_percent / 100) * Math.abs(e.value) * (1 + 1e-9));
    }
    return readings;
}

/** The hard rule of the lab: worst-case reading error ≤ ½ × task tolerance. */
const expectHalfTolerance = (task, maxErrSI, expectedSI) => {
    expect(maxErrSI).toBeLessThanOrEqual(0.5 * (task.expected.tolerance_percent / 100) * Math.abs(expectedSI));
};

const measureN = (sim, n = 30) => {
    const start = sim.measurements.length;
    for (let k = 0; k < n; k++) sim.call('measure');
    return sim.measurements.slice(start);
};
const relErr = (a, b) => Math.abs(a - b) / Math.abs(b);
const measurementTasks = (exp) => exp.tasks.filter((t) => t.type === 'measurement');

async function withSim(name, exp, fn) {
    const sim = await mountSimulation(name, { config: exp.simulation_config ?? {}, currentTask: exp.tasks[0] });
    try {
        await fn(sim);
        // "Document is not defined" comes from Vue's v-model directive in the headless
        // harness (no DOM `Document` class), not from the simulation code.
        expect(sim.errors.filter((e) => !/Document is not defined/.test(String(e)))).toEqual([]);
    } finally {
        sim.unmount();
    }
}

describe("1. Om qonuni (OhmLawSimulation): DMM ammeter in series", () => {
    const exp = bySlug('ohm-law');
    it('every measurement task passes for 30 readings; expected = U/R', async () => {
        await withSim('OhmLawSimulation', exp, async (sim) => {
            for (const task of measurementTasks(exp)) {
                const { U, R } = task.expected.check.inputs;
                const I = U / R;
                expect(relErr(task.expected.value, I * 1000)).toBeLessThan(1e-4);
                expectHalfTolerance(task, dmmMaxError(I, 'current'), I);
                await sim.set({ voltage: U, resistance: R, isSwitchOn: true });
                const r = expectPasses(task, measureN(sim));
                expect(r[0].unit).toBe('mA');
                expect(r[0].value).toMatch(/^\d+\.\d$/); // 200 mA range: 0.1 mA digit
            }
        });
    });
    it('switch open: the ammeter reads 0.000 mA and no measurement is recorded', async () => {
        await withSim('OhmLawSimulation', exp, async (sim) => {
            await sim.set({ voltage: 6, resistance: 100, isSwitchOn: false });
            expect(sim.get('ammeterDisplay')).toBe('0.000 mA');
            sim.call('measure');
            expect(sim.measurements).toHaveLength(0);
            expect(sim.text()).toContain('I = 0 mA');
        });
    });
    it('slider sweep (min/max): readings within the DMM error of U/R, correct range', async () => {
        await withSim('OhmLawSimulation', exp, async (sim) => {
            for (const U of [1, 6, 12]) {
                for (const R of [10, 100, 500]) {
                    await sim.set({ voltage: U, resistance: R, isSwitchOn: true });
                    const [m] = measureN(sim, 1);
                    const I = U / R;
                    expect(Math.abs(toSI(m) - I)).toBeLessThanOrEqual(dmmMaxError(I, 'current') + 1e-12);
                    expect(m.unit).toBe(I >= 0.19995 ? 'A' : 'mA');
                }
            }
        });
    });
});

describe('2–3. Series / parallel resistance (DMM ohmmeter)', () => {
    for (const [slug, name, combine] of [
        ['series-circuits', 'SeriesCircuitSimulation', (rs) => rs.reduce((a, b) => a + b, 0)],
        ['parallel-circuits', 'ParallelCircuitSimulation', (rs) => 1 / rs.reduce((a, b) => a + 1 / b, 0)],
    ]) {
        const exp = bySlug(slug);
        it(`${name}: every measurement task passes for 30 readings`, async () => {
            await withSim(name, exp, async (sim) => {
                for (const task of measurementTasks(exp)) {
                    const inp = task.expected.check.inputs;
                    const R = combine([inp['R₁'], inp['R₂'], inp['R₃']]);
                    expect(relErr(task.expected.value, R)).toBeLessThan(1e-4);
                    expectHalfTolerance(task, dmmMaxError(R, 'resistance'), R);
                    await sim.set({ r1: inp['R₁'], r2: inp['R₂'], r3: inp['R₃'] });
                    expectPasses(task, measureN(sim));
                }
            });
        });
        it(`${name}: sweep min/max resistors`, async () => {
            await withSim(name, exp, async (sim) => {
                for (const rs of [[50, 50, 50], [500, 500, 500], [50, 500, 250], [100, 200, 150]]) {
                    await sim.set({ r1: rs[0], r2: rs[1], r3: rs[2] });
                    const [m] = measureN(sim, 1);
                    const R = combine(rs);
                    expect(Math.abs(toSI(m) - R)).toBeLessThanOrEqual(dmmMaxError(R, 'resistance') + 1e-9);
                }
            });
        });
    }
});

describe('4. Kirxgof qonunlari (KirchhoffSimulation): main current on the 10 A input', () => {
    const exp = bySlug('kirchhoff-laws');
    const I1 = (E, R1, R2, R3) => E / (R1 + (R2 * R3) / (R2 + R3));
    it('every measurement task passes for 30 readings', async () => {
        await withSim('KirchhoffSimulation', exp, async (sim) => {
            for (const task of measurementTasks(exp)) {
                const inp = task.expected.check.inputs;
                const I = I1(inp['ℰ'], inp['R₁'], inp['R₂'], inp['R₃']);
                expect(relErr(task.expected.value, I)).toBeLessThan(1e-4);
                expectHalfTolerance(task, dmmMaxError(I, 'current'), I);
                await sim.set({ emf: inp['ℰ'], r1: inp['R₁'], r2: inp['R₂'], r3: inp['R₃'] });
                const r = expectPasses(task, measureN(sim));
                expect(r[0].unit).toBe('A');
                expect(r[0].value).toMatch(/^\d+\.\d\d$/);
            }
        });
    });
    it('sweep: E 6…24 V, R 1…12 Ω; KCL shown on the canvas holds', async () => {
        await withSim('KirchhoffSimulation', exp, async (sim) => {
            for (const [E, a, b, c] of [[6, 10, 12, 12], [24, 1, 2, 2], [12, 4, 6, 3], [18, 5, 2, 12]]) {
                await sim.set({ emf: E, r1: a, r2: b, r3: c });
                const [m] = measureN(sim, 1);
                const I = I1(E, a, b, c);
                expect(Math.abs(toSI(m) - I)).toBeLessThanOrEqual(dmmMaxError(I, 'current') + 1e-12);
                const i2 = (I * c) / (b + c);
                expect(sim.get('i2')).toBeCloseTo(i2, 10);
                expect(sim.get('i3')).toBeCloseTo(I - i2, 10);
            }
        });
    });
});

describe('5–6. Capacitor and RC circuit: DMM voltmeter at t = 5τ', () => {
    for (const [slug, name, U0, charge, discharge, startFn] of [
        ['capacitors', 'CapacitorSimulation', 12, 'startCharge', 'startDischarge', null],
        ['rc-circuits', 'RCCircuitSimulation', 10, 'charge', 'discharge', 'start'],
    ]) {
        const exp = bySlug(slug);
        it(`${name}: charging and discharging tasks pass for 30 readings`, async () => {
            await withSim(name, exp, async (sim) => {
                for (const task of measurementTasks(exp)) {
                    const inp = task.expected.check.inputs;
                    expect(inp['U₀']).toBe(U0);
                    const tau = inp.R * inp.C * 1e-6;
                    const t = inp.t * 1e-3;
                    const isCharge = task.expected.check.formula_id === 'rc_charging_voltage';
                    const V = isCharge ? U0 * (1 - Math.exp(-t / tau)) : U0 * Math.exp(-t / tau);
                    expect(relErr(task.expected.value, V)).toBeLessThan(1e-4);
                    expectHalfTolerance(task, dmmMaxError(V, 'voltage'), V);
                    if (startFn) {
                        await sim.set({ resistance: inp.R, capacitance: inp.C, circuitType: isCharge ? charge : discharge });
                        sim.call(startFn);
                    } else {
                        await sim.set({ resistance: inp.R, capacitance: inp.C });
                        sim.call(isCharge ? charge : discharge);
                    }
                    await sim.advance(5); // 5τ = 0.5 s is played back over 4 s
                    expect(sim.get('time')).toBeCloseTo(t, 12); // the timer stops exactly at 5τ
                    const r = expectPasses(task, measureN(sim));
                    expect(r[0].name).toContain('t=500 ms');
                    expect(r[0].unit).toBe(isCharge ? 'V' : 'mV');
                }
            });
        });
        it(`${name}: sweep R, C (min/max) — reading at the end equals U₀(1 − e⁻⁵) / U₀e⁻⁵`, async () => {
            await withSim(name, exp, async (sim) => {
                for (const [R, C] of [[100, 10], [5000, 500], [2000, 250]]) {
                    for (const isCharge of [true, false]) {
                        if (startFn) {
                            await sim.set({ resistance: R, capacitance: C, circuitType: isCharge ? charge : discharge });
                            sim.call(startFn);
                        } else {
                            await sim.set({ resistance: R, capacitance: C });
                            sim.call(isCharge ? charge : discharge);
                        }
                        await sim.advance(Math.max(5, 5 * R * C * 1e-6 + 1));
                        const [m] = measureN(sim, 1);
                        const V = isCharge ? U0 * (1 - Math.exp(-5)) : U0 * Math.exp(-5);
                        expect(Math.abs(toSI(m) - V)).toBeLessThanOrEqual(dmmMaxError(V, 'voltage') + 1e-12);
                        expect(m.name).toContain(`t=${(5 * R * C * 1e-3).toFixed(0)} ms`);
                    }
                }
            });
        });
    }
});

describe('7. Elektr quvvati (ElectricPowerSimulation): wattmeter, tariff 650 so\'m/kWh', () => {
    const exp = bySlug('electric-power');
    it('every measurement task passes for 30 readings; expected = U·I', async () => {
        await withSim('ElectricPowerSimulation', exp, async (sim) => {
            for (const task of measurementTasks(exp)) {
                const { U, I } = task.expected.check.inputs;
                expect(relErr(task.expected.value, U * I)).toBeLessThan(1e-4);
                expectHalfTolerance(task, maxReadingError(U * I, ELECTRIC_METERS.wattmeter), U * I);
                await sim.set({ voltage: U, current: I });
                const r = expectPasses(task, measureN(sim));
                expect(r[0].value).toMatch(/^\d+\.\d$/);
            }
            expect(sim.get('tariff')).toBe(650);
            // cost task: 120 W · 5 h = 0.6 kWh · 650 = 390 so'm
            const cost = exp.tasks.find((t) => t.expected?.check?.formula_id === 'energy_cost');
            expect(cost.expected.value).toBeCloseTo((120 * 5 / 1000) * 650, 9);
            expect(exp.simulation_config.tariff).toBe(650);
        });
    });
    it('sweep U 1…24 V, I 0.5…5 A', async () => {
        await withSim('ElectricPowerSimulation', exp, async (sim) => {
            for (const [U, I] of [[1, 0.5], [24, 5], [12, 2], [7, 3.5]]) {
                await sim.set({ voltage: U, current: I });
                const [m] = measureN(sim, 1);
                expect(Math.abs(Number(m.value) - U * I)).toBeLessThanOrEqual(maxReadingError(U * I, ELECTRIC_METERS.wattmeter) + 1e-9);
            }
        });
    });
});

describe('8. Elektrostatika (ElectrostaticsSimulation): Coulomb force sensor', () => {
    const exp = bySlug('electrostatics');
    const coulomb = (q1, q2, r) => (K * Math.abs(q1 * q2) * 1e-12) / (r * r);
    it('every measurement task passes for 30 readings; expected = k|q₁q₂|/r² (k = 8.9875517862e9)', async () => {
        expect(K).toBeCloseTo(8.9875517862e9, 0);
        await withSim('ElectrostaticsSimulation', exp, async (sim) => {
            for (const task of measurementTasks(exp)) {
                const inp = task.expected.check.inputs;
                const F = coulomb(inp['q₁'], inp['q₂'], inp.r);
                expect(relErr(task.expected.value, F * 1000)).toBeLessThan(1e-4);
                expectHalfTolerance(task, maxReadingError(F, ELECTRIC_METERS.forceSensor), F);
                await sim.set({ charge1: inp['q₁'], charge2: inp['q₂'], distanceCm: Math.round(inp.r * 100) });
                const r = expectPasses(task, measureN(sim));
                expect(r[0].unit).toBe('mN');
            }
        });
    });
    it('sweep charges ±10 µC, r = 1…6 m; attraction / repulsion', async () => {
        await withSim('ElectrostaticsSimulation', exp, async (sim) => {
            for (const [a, b, d] of [[10, 10, 100], [-10, 10, 600], [1, -1, 600], [5, -3, 300]]) {
                await sim.set({ charge1: a, charge2: b, distanceCm: d });
                const [m] = measureN(sim, 1);
                const F = coulomb(a, b, d / 100);
                expect(Math.abs(toSI(m) - F)).toBeLessThanOrEqual(maxReadingError(F, ELECTRIC_METERS.forceSensor) + 1e-12);
                expect(sim.get('isAttractive')).toBe(a * b < 0);
            }
        });
    });
});

describe('9. Elektr maydoni (ElectricFieldSimulation): field meter on a 10 cm probe grid', () => {
    const exp = bySlug('electric-field');
    it('every measurement task passes for 30 readings; expected = |E₁ + E₂| by hand', async () => {
        await withSim('ElectricFieldSimulation', exp, async (sim) => {
            const meter = createSigFigMeter(ELECTRIC_METERS.fieldMeter);
            for (const task of measurementTasks(exp)) {
                const inp = task.expected.check.inputs;
                expect([inp['x₁'], inp['y₁'], inp['x₂'], inp['y₂']]).toEqual([3, 2.5, 5, 2.5]);
                const E = fieldAt([{ q: inp['q₁'] * 1e-6, x: 3, y: 2.5 }, { q: inp['q₂'] * 1e-6, x: 5, y: 2.5 }], inp.x, inp.y);
                expect(relErr(task.expected.value, E / 1000)).toBeLessThan(1e-4);
                expectHalfTolerance(task, meter.maxError(E), E);
                await sim.set({ charge1: inp['q₁'], charge2: inp['q₂'] });
                // the student clicks near the point: the probe snaps to the 10 cm grid node
                sim.call('onCanvasClick', { clientX: inp.x * 100 + 3, clientY: inp.y * 100 - 4 });
                await sim.flush();
                expect(sim.get('probe')).toEqual({ x: inp.x * 100, y: inp.y * 100 });
                const r = expectPasses(task, measureN(sim));
                expect(r[0].unit).toBe('kV/m');
            }
        });
    });
    it('default probe (4; 1.5) m and a sweep of grid points', async () => {
        await withSim('ElectricFieldSimulation', exp, async (sim) => {
            const meter = createSigFigMeter(ELECTRIC_METERS.fieldMeter);
            expect(sim.get('probe')).toEqual({ x: 400, y: 150 });
            for (const [q1, q2, x, y] of [[10, -10, 4, 1.5], [20, 20, 1, 1], [-20, 6, 7.5, 4.5], [10, 10, 4, 0.5]]) {
                await sim.set({ charge1: q1, charge2: q2, probe: { x: x * 100, y: y * 100 } });
                const [m] = measureN(sim, 1);
                const E = fieldAt([{ q: q1 * 1e-6, x: 3, y: 2.5 }, { q: q2 * 1e-6, x: 5, y: 2.5 }], x, y);
                expect(Math.abs(toSI(m) - E)).toBeLessThanOrEqual(meter.maxError(E) * 1.0001);
            }
        });
    });
});

describe('10. Diodlar va LEDlar (DiodesSimulation): DMM ammeter, 1N4001 / Ge / red LED', () => {
    const exp = bySlug('diodes-leds');
    const TYPE = { 0.7: 'silicon', 0.3: 'germanium', 1.8: 'led' };
    it('every measurement task passes for 30 readings; expected = exact Shockley + Rs operating point', async () => {
        await withSim('DiodesSimulation', exp, async (sim) => {
            for (const task of measurementTasks(exp)) {
                const { U, R, U_f: Uf } = task.expected.check.inputs;
                const type = TYPE[Uf];
                const I = diodeCurrentIndependent(U, R, DIODES[type]);
                expect(relErr(task.expected.value, I * 1000)).toBeLessThan(1e-4);
                // the simplified model (U − U_f)/R stays inside the check tolerance
                expect(relErr((U - Uf) / R, I) * 100).toBeLessThan(task.expected.check.tolerance_percent);
                expectHalfTolerance(task, dmmMaxError(I, 'current'), I);
                await sim.set({ diodeType: type, voltage: U, resistance: R });
                const r = expectPasses(task, measureN(sim));
                expect(r[0].unit).toBe('mA');
            }
        });
    });
    it('LED task current is below the 30 mA rating of a 5 mm LED', () => {
        const led = exp.tasks.find((t) => t.expected?.check?.inputs?.U_f === 1.8);
        expect(led.expected.value).toBeLessThan(30);
        expect(led.expected.value).toBeGreaterThan(15);
    });
    it('sweep: U −5…10 V, R 50…500 Ω, all three diodes', async () => {
        await withSim('DiodesSimulation', exp, async (sim) => {
            for (const type of Object.keys(DIODES)) {
                for (const [U, R] of [[-5, 50], [0.5, 500], [10, 50], [5, 100], [3, 250]]) {
                    await sim.set({ diodeType: type, voltage: U, resistance: R });
                    const [m] = measureN(sim, 1);
                    const I = diodeCurrentIndependent(U, R, DIODES[type]);
                    expect(Math.abs(toSI(m) - I)).toBeLessThanOrEqual(dmmMaxError(I, 'current') + 1e-12);
                }
            }
        });
    });
});
