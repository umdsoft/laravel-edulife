/**
 * End-to-end tests of the thermodynamics simulations (real SFCs in the headless harness).
 *
 * For every simulation the real procedure is driven (settings → start → advance time →
 * measure) and the EMITTED readings / displayed values are compared with an independent
 * recomputation written here from textbook formulas and literal constants (not with the
 * physics module). For every seeded measurement task: name prefix and unit as the server
 * (app/Services/Lab/LabTaskChecker.php) checks them, ≥ 30 repeated readings within the
 * tolerance, worst-case reading error ≤ ½·tolerance, and expected.value = physics.
 */
import { describe, it, expect, beforeAll, afterAll } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { mountSimulation } from './mountSimulation.js';
import { maxReadingError, INSTRUMENTS } from '../../physics/instruments.js';
import { calculate } from '../../physics/calculator.js';

const EXPERIMENTS = JSON.parse(readFileSync(fileURLToPath(new URL('../../../../../../database/seeders/data/lab/experiments/thermodynamics.json', import.meta.url)), 'utf8'));
const EXP = Object.fromEntries(EXPERIMENTS.map((e) => [e.slug, e]));

// ── independent constants (literals from the sources, not from physics/*.js) ──
const R = 8.314462618;           // J/(mol·K), CODATA 2022 (exact N_A·k)
const ATM = 101325;              // Pa
const SIGMA = 5.670374419e-8;    // W/(m²·K⁴), CODATA 2022 (exact)
const ALPHA = { metal: 12e-6, aluminum: 23e-6, copper: 17e-6, glass: 9e-6 };       // 1/K
const C = { water: 4186, oil: 2000, iron: 450, copper: 385, aluminum: 897 };       // J/(kg·K)
const K = { copper: 385, aluminum: 205, iron: 80, glass: 1.05, wood: 0.15 };      // W/(m·K)
const ICE = { c: 2100, Lf: 334000, cw: 4186, Lv: 2260000, cs: 2000 };

const DIAL = INSTRUMENTS.dialIndicator;
const THERMO = INSTRUMENTS.digitalThermometer;
const WATCH = INSTRUMENTS.handStopwatch;
const VSCALE = { resolution: 0.1, sigmaAbs: 0.02 };
const MANO = { resolution: 0.01, sigmaAbs: 0.002 };
const N_REPEAT = 30;

// ── server rules (LabTaskChecker) re-implemented for the assertions ──
const normName = (s) => String(s).replace(/[ʻʼ’‘`´]/g, "'").replace(/\s+/g, ' ').trim().toLowerCase();
const PREFIXES = { T: 1e12, G: 1e9, M: 1e6, k: 1e3, '': 1, c: 1e-2, m: 1e-3, 'µ': 1e-6, n: 1e-9, p: 1e-12 };
const BASES = ['V/m', 'N·m', 'Hz', 'eV', 'Bq', 'Pa', 'Ω', 'W', 'J', 'N', 'V', 'A', 'C', 'T', 's', 'm', 'g', 'L'];
const normUnit = (u) => String(u).replace(/\s+/g, '').replace(/μ/g, 'µ').replace(/\*/g, '·').replace(/\^2/g, '²').replace(/\^3/g, '³');
const parseUnit = (u) => {
    for (const b of BASES) {
        if (!u.endsWith(b)) continue;
        const p = u.slice(0, u.length - b.length);
        if (!(p in PREFIXES)) continue;
        if (p === 'c' && !['m', 'L'].includes(b)) continue;
        return [b, PREFIXES[p]];
    }
    return null;
};
const unitFactor = (from, to) => {
    const a = normUnit(from);
    const b = normUnit(to);
    if (a === b) return 1;
    const pa = parseUnit(a);
    const pb = parseUnit(b);
    return pa && pb && pa[0] === pb[0] ? pa[1] / pb[1] : null;
};
/** The last emitted measurement the server would pick for this task. */
const pickForTask = (sim, expected) => [...sim.measurements].reverse()
    .find((m) => normName(m.name).startsWith(normName(expected.measurement)));

function assertTaskReading(m, expected) {
    expect(m, `a measurement named «${expected.measurement}…»`).toBeTruthy();
    const f = unitFactor(m.unit, expected.unit);
    expect(f, `unit ${m.unit} vs ${expected.unit}`).not.toBeNull();
    const v = Number(m.value) * f;
    expect(Number.isFinite(v)).toBe(true);
    expect(Math.abs(v - expected.value)).toBeLessThanOrEqual((expected.tolerance_percent / 100) * Math.abs(expected.value));
    return v;
}

/** expected.value is reproduced by the calculator/physics at the task's check inputs. */
function assertExpectedIsPhysics(expected, independent) {
    const r = calculate(expected.check.formula_id, expected.check.inputs);
    expect(r.ok, r.error).toBe(true);
    const tol = (expected.check.tolerance_percent ?? 0.1) / 100;
    expect(Math.abs(r.value * (expected.check.factor ?? 1) - expected.value)).toBeLessThanOrEqual(tol * Math.abs(expected.value));
    // and by the independent formula of this test (5 significant digits)
    expect(Math.abs(independent - expected.value)).toBeLessThanOrEqual(5e-5 * Math.abs(expected.value) + 1e-9);
}

/** Captures every canvas fillText of the next redraw. */
function canvasText(sim) {
    const texts = [];
    const c = sim.get('ctx');
    c.fillText = (s) => texts.push(String(s));
    sim.call('draw');
    return texts.join(' | ');
}

async function withSim(name, exp, fn) {
    const sim = await mountSimulation(name, { config: exp.simulation_config, currentTask: exp.tasks[0] });
    try {
        await fn(sim);
        expect(sim.errors.map((e) => e.message)).toEqual([]);
        expect(sim.warnings).toEqual([]);
    } finally {
        sim.unmount();
    }
}

// The harness's fake DOM has no global Document/ShadowRoot, which Vue's v-model
// (runtime-dom vModelText.beforeUpdate) references with `instanceof` when a bound value
// changes. Provide inert classes for this file only (never matched → normal v-model path).
const hadDocument = 'Document' in globalThis;
const hadShadowRoot = 'ShadowRoot' in globalThis;
beforeAll(() => {
    if (!hadDocument) globalThis.Document = class Document {};
    if (!hadShadowRoot) globalThis.ShadowRoot = class ShadowRoot {};
});
afterAll(() => {
    if (!hadDocument) delete globalThis.Document;
    if (!hadShadowRoot) delete globalThis.ShadowRoot;
});

const measureTasks = (exp) => exp.tasks.map((t, i) => [i, t]).filter(([, t]) => t.type === 'measurement');

// ═════════════════════════════ 1. Thermal expansion ═════════════════════════════
describe('ThermalExpansionSimulation (dial indicator 0.01 mm)', () => {
    const exp = EXP['thermal-expansion'];
    const SETUP = {
        1: { material: 'metal', initialLength: 1, heat: true },
        2: { material: 'aluminum', initialLength: 1, heat: true },
        3: { material: 'copper', initialLength: 2, heat: true },
        4: { material: 'metal', initialLength: 1, heat: false },
    };
    const independent = ({ material, initialLength, heat }) => ALPHA[material] * initialLength * (heat ? 80 : -40) * 1000; // mm

    it.each(measureTasks(exp))('task #%i: %s', async (i, task) => {
        const s = SETUP[i];
        const e = task.expected;
        assertExpectedIsPhysics(e, independent(s));
        // HARD RULE: worst-case reading error ≤ ½ · tolerance
        expect(maxReadingError(e.value, DIAL)).toBeLessThanOrEqual(0.5 * (e.tolerance_percent / 100) * Math.abs(e.value));
        await withSim('ThermalExpansionSimulation', exp, async (sim) => {
            await sim.set({ material: s.material, initialLength: s.initialLength });
            sim.call(s.heat ? 'startHeating' : 'startCooling');
            await sim.advance(6);
            expect(sim.get('isRunning')).toBe(false);
            expect(sim.get('temperature')).toBe(s.heat ? 100 : -20);
            for (let k = 0; k < N_REPEAT; k++) {
                sim.call('measure');
                const m = pickForTask(sim, e);
                expect(m.unit).toBe('mm');
                expect(m.value).toMatch(/^-?\d+\.\d{2}$/); // 0.01 mm resolution
                assertTaskReading(m, e);
                expect(Math.abs(Number(m.value) - independent(s))).toBeLessThanOrEqual(maxReadingError(1, DIAL) + 1e-9);
            }
            // σ = 0.002 mm ≪ 0.01 mm division: a good dial indicator repeats its reading
            expect(new Set(sim.measurements.map((m) => m.value)).size).toBeLessThanOrEqual(2);
        });
    });

    it('sweep: every material, L₀ = 0.5 … 2 m, heating and cooling', async () => {
        await withSim('ThermalExpansionSimulation', exp, async (sim) => {
            for (const material of Object.keys(ALPHA)) {
                for (const L of [0.5, 1, 1.5, 2]) {
                    for (const heat of [true, false]) {
                        sim.call('reset');
                        await sim.set({ material, initialLength: L });
                        sim.call(heat ? 'startHeating' : 'startCooling');
                        await sim.advance(6);
                        sim.call('measure');
                        const m = sim.measurements.at(-1);
                        const want = ALPHA[material] * L * (heat ? 80 : -40) * 1000;
                        expect(m.name).toMatch(/^ΔL \(/);
                        expect(Math.abs(Number(m.value) - want)).toBeLessThanOrEqual(0.0101);
                        const txt = canvasText(sim);
                        expect(txt).toContain(`ΔL = ${(Math.round(want * 100 + 1e-9 * Math.sign(want)) / 100).toFixed(2)} mm`);
                        expect(txt).toContain(`T = ${heat ? '100.0' : '-20.0'} °C`);
                    }
                }
            }
        });
    });

    it('reading is reproducible: same set-up, same sequence of readings', async () => {
        const run = async () => {
            const out = [];
            await withSim('ThermalExpansionSimulation', exp, async (sim) => {
                sim.call('startHeating');
                await sim.advance(6);
                for (let k = 0; k < 5; k++) sim.call('measure');
                out.push(...sim.measurements.map((m) => m.value));
            });
            return out;
        };
        expect(await run()).toEqual(await run());
    });
});

// ═════════════════════════════ 2. Specific heat ═════════════════════════════
describe('SpecificHeatSimulation (thermometer 0.1 °C, stopwatch 0.01 s)', () => {
    const exp = EXP['specific-heat'];
    const SETUP = { 1: { material: 'water', mass: 0.2, heatPower: 100 }, 4: { material: 'iron', mass: 0.2, heatPower: 100 } };
    /** worst case of c = P·t/(m·(T − T₀)) from the readings, rounded to 4 significant figures */
    const worstRelError = (c, dT, t) => (2 * maxReadingError(50, THERMO)) / dT + maxReadingError(t, WATCH) / t
        + (0.5 * 10 ** (Math.floor(Math.log10(c)) - 3)) / c;

    it.each(measureTasks(exp))('task #%i: %s', async (i, task) => {
        const s = SETUP[i];
        const e = task.expected;
        assertExpectedIsPhysics(e, C[s.material]);
        const t = (s.mass * C[s.material] * 80) / s.heatPower;
        expect(worstRelError(e.value, 80, t)).toBeLessThanOrEqual(0.5 * e.tolerance_percent / 100);
        await withSim('SpecificHeatSimulation', exp, async (sim) => {
            await sim.set(s);
            for (let run = 0; run < 3; run++) {
                sim.call('reset');
                sim.call('start');
                await sim.advance(25);
                expect(sim.get('isRunning')).toBe(false);
                expect(sim.get('temperature')).toBe(100);
                expect(sim.get('time')).toBeCloseTo(t, 9); // physical heating time t = mcΔT/P
                for (let k = 0; k < N_REPEAT; k++) {
                    sim.call('measure');
                    const m = pickForTask(sim, e);
                    expect(m.unit).toBe(e.unit);
                    assertTaskReading(m, e);
                    expect(Math.abs(Number(m.value) - C[s.material]) / C[s.material]).toBeLessThanOrEqual(worstRelError(e.value, 80, t) + 1e-12);
                    const T = sim.measurements.at(-2);
                    expect(T.name).toMatch(/^Harorat/);
                    expect(Math.abs(Number(T.value) - 100)).toBeLessThanOrEqual(maxReadingError(100, THERMO) + 1e-9);
                }
            }
        });
    });

    it('sweep: all substances, m = 0.1 … 1 kg, P = 50 … 500 W (full runs and mid-run readings)', async () => {
        await withSim('SpecificHeatSimulation', exp, async (sim) => {
            for (const material of Object.keys(C)) {
                for (const mass of [0.1, 0.5, 1]) {
                    for (const heatPower of [50, 500]) {
                        await sim.set({ material, mass, heatPower });
                        sim.call('reset');
                        sim.call('start');
                        await sim.advance(5);
                        // mid-run: the displayed state follows T = T₀ + P·t/(m·c)
                        const tMid = sim.get('time');
                        const Tmid = Math.min(100, 20 + (heatPower * tMid) / (mass * C[material]));
                        expect(sim.get('temperature')).toBeCloseTo(Tmid, 9);
                        await sim.advance(20);
                        const t = (mass * C[material] * 80) / heatPower;
                        expect(sim.get('time')).toBeCloseTo(t, 9);
                        sim.call('measure');
                        const c = sim.measurements.at(-1);
                        expect(c.name).toMatch(/^Issiqlik sig'imi/);
                        expect(Math.abs(Number(c.value) - C[material]) / C[material]).toBeLessThanOrEqual(worstRelError(C[material], 80, t) + 1e-12);
                    }
                }
            }
        });
    });

    it('before heating only the temperature is recorded (c is not "measured" from the table)', async () => {
        await withSim('SpecificHeatSimulation', exp, async (sim) => {
            sim.call('measure');
            expect(sim.measurements).toHaveLength(1);
            expect(sim.measurements[0].name).toMatch(/^Harorat/);
            expect(Math.abs(Number(sim.measurements[0].value) - 20)).toBeLessThanOrEqual(0.125 + 1e-9);
        });
    });
});

// ═════════════════════════════ 3. Heat transfer ═════════════════════════════
describe('HeatTransferSimulation (two thermocouples 0.1 °C, flux with 3 significant figures)', () => {
    const exp = EXP['heat-transfer'];
    const SETUP = {
        1: { material: 'copper', hotTemp: 100, coldTemp: 20 },
        2: { material: 'iron', hotTemp: 100, coldTemp: 20 },
        3: { material: 'glass', hotTemp: 100, coldTemp: 20 },
        4: { material: 'copper', hotTemp: 200, coldTemp: 0 },
    };
    const flux = ({ material, hotTemp, coldTemp }) => (K[material] * (hotTemp - coldTemp)) / 0.1;
    const halfUnit3 = (q) => 0.5 * 10 ** (Math.floor(Math.log10(Math.abs(q))) - 2);
    /** worst |q_read − q|: two thermocouple errors through k/d, then rounding to 3 significant figures */
    const worstAbs = (k, dT) => {
        const e = (k * 2 * maxReadingError(100, THERMO)) / 0.1;
        return e * 1.0001 + halfUnit3(Math.abs((k * dT) / 0.1) + e);
    };

    it.each(measureTasks(exp))('task #%i: %s', async (i, task) => {
        const s = SETUP[i];
        const e = task.expected;
        assertExpectedIsPhysics(e, flux(s));
        expect(worstAbs(K[s.material], s.hotTemp - s.coldTemp)).toBeLessThanOrEqual(0.5 * (e.tolerance_percent / 100) * e.value);
        await withSim('HeatTransferSimulation', exp, async (sim) => {
            await sim.set(s);
            await sim.advance(1);
            for (let k = 0; k < N_REPEAT; k++) {
                sim.call('measure');
                const m = pickForTask(sim, e);
                expect(m.unit).toBe('W/m²');
                expect(m.value).not.toMatch(/\.\d/); // no "308000.0"
                assertTaskReading(m, e);
                expect(Math.abs(Number(m.value) - flux(s))).toBeLessThanOrEqual(worstAbs(K[s.material], s.hotTemp - s.coldTemp));
            }
        });
    });

    it('sweep: materials × hot 50…200 °C × cold 0…50 °C; radiation readout in kelvin', async () => {
        await withSim('HeatTransferSimulation', exp, async (sim) => {
            for (const material of Object.keys(K)) {
                for (const hotTemp of [50, 100, 200]) {
                    for (const coldTemp of [0, 25, 50]) {
                        await sim.set({ material, hotTemp, coldTemp });
                        sim.call('measure');
                        const m = sim.measurements.at(-1);
                        const want = flux({ material, hotTemp, coldTemp });
                        expect(m.name).toMatch(/^Issiqlik oqimi/);
                        expect(Math.abs(Number(m.value) - want)).toBeLessThanOrEqual(worstAbs(K[material], hotTemp - coldTemp));
                        const rad = SIGMA * ((hotTemp + 273.15) ** 4 - (coldTemp + 273.15) ** 4);
                        expect(canvasText(sim)).toContain(`= ${rad.toFixed(0)} W/m²`);
                    }
                }
            }
        });
    });

    it('radiation task: σ(373.15⁴ − 293.15⁴) = 680.61 W/m²', () => {
        const e = exp.tasks[5].expected;
        assertExpectedIsPhysics(e, SIGMA * (373.15 ** 4 - 293.15 ** 4));
    });
});

// ═════════════════════════════ 4. Phase changes ═════════════════════════════
describe('PhaseChangeSimulation (heating curve of 0.5 kg ice, thermometer + stopwatch)', () => {
    const exp = EXP['phase-changes'];
    const m = 0.5;
    /** independent heating curve T(Q) from −20 °C */
    const Tof = (Q) => {
        const q1 = m * ICE.c * 20;
        const q2 = q1 + m * ICE.Lf;
        const q3 = q2 + m * ICE.cw * 100;
        const q4 = q3 + m * ICE.Lv;
        if (Q < q1) return -20 + Q / (m * ICE.c);
        if (Q < q2) return 0;
        if (Q < q3) return (Q - q2) / (m * ICE.cw);
        if (Q < q4) return 100;
        return 100 + (Q - q4) / (m * ICE.cs);
    };

    it('calculation tasks: expected values = physics', () => {
        assertExpectedIsPhysics(exp.tasks[1].expected, m * ICE.c * 20);
        assertExpectedIsPhysics(exp.tasks[2].expected, m * ICE.Lf);
        assertExpectedIsPhysics(exp.tasks[3].expected, (m * ICE.Lf) / 200);
        assertExpectedIsPhysics(exp.tasks[4].expected, m * ICE.Lv);
    });

    it.each([100, 200, 500])('P = %i W: readings follow the independent heating curve, Q = P·t', async (P) => {
        await withSim('PhaseChangeSimulation', exp, async (sim) => {
            await sim.set({ heatPower: P });
            sim.call('start');
            const Qend = m * ICE.c * 20 + m * ICE.Lf + m * ICE.cw * 100 + m * ICE.Lv + m * ICE.cs * 50;
            for (let step = 0; step < 14; step++) {
                await sim.advance(5);
                const t = sim.get('time');
                const Q = sim.get('heatAdded');
                expect(Q).toBeCloseTo(Math.min(Qend, P * t), 6);
                expect(sim.get('temperature')).toBeCloseTo(Tof(Q), 9);
                sim.call('measure');
                const [T, tw] = sim.measurements.slice(-2);
                expect(T).toMatchObject({ name: 'Harorat', unit: '°C' });
                expect(tw).toMatchObject({ name: 'Isitish vaqti', unit: 's' });
                expect(Math.abs(Number(T.value) - Tof(Q))).toBeLessThanOrEqual(maxReadingError(0, THERMO) + 1e-9);
                expect(Math.abs(Number(tw.value) - t)).toBeLessThanOrEqual(maxReadingError(t, WATCH) + 1e-9);
            }
            expect(sim.get('isRunning')).toBe(false);
            expect(sim.get('heatAdded')).toBeCloseTo(Qend, 6);
            expect(sim.get('temperature')).toBeCloseTo(150, 9);
        });
    });

    it('melting plateau lasts m·λ/P = 835 s at 200 W (as task #3 says)', async () => {
        await withSim('PhaseChangeSimulation', exp, async (sim) => {
            const bp = sim.get('breakpoints');
            expect(bp.solidEnd).toBeCloseTo(21000, 6);
            expect((bp.meltEnd - bp.solidEnd) / 200).toBeCloseTo(835, 9);
            expect(bp.liquidEnd).toBeCloseTo(21000 + 167000 + 209300, 6);
            expect(bp.boilEnd).toBeCloseTo(21000 + 167000 + 209300 + 1130000, 6);
        });
    });
});

// ═════════════════════════════ 5. Ideal gas ═════════════════════════════
describe('IdealGasSimulation (cylinder scale 0.1 L, gauge 0.01 atm)', () => {
    const exp = EXP['ideal-gas-law'];
    const Vof = (n, T, P) => (n * R * T) / (P * ATM) * 1000; // L
    const SETUP = {
        1: { n: 1, T: 300, P: 1 },
        2: { n: 1, T: 400, P: 1 },
        3: { n: 1, T: 300, P: 2 },
    };

    it.each(measureTasks(exp))('task #%i: %s', async (i, task) => {
        const s = SETUP[i];
        const e = task.expected;
        assertExpectedIsPhysics(e, Vof(s.n, s.T, s.P));
        expect(maxReadingError(e.value, VSCALE)).toBeLessThanOrEqual(0.5 * (e.tolerance_percent / 100) * e.value);
        await withSim('IdealGasSimulation', exp, async (sim) => {
            await sim.set({ holdConstant: 'pressure' });
            await sim.set({ pressureModel: s.P });
            await sim.set({ temperatureModel: s.T });
            await sim.set({ molesModel: s.n });
            expect(sim.get('volume')).toBeCloseTo(Vof(s.n, s.T, s.P), 6);
            for (let k = 0; k < N_REPEAT; k++) {
                sim.call('measure');
                const m = pickForTask(sim, e);
                expect(m.unit).toBe('L');
                expect(m.value).toMatch(/^\d+\.\d$/); // 0.1 L divisions
                assertTaskReading(m, e);
                expect(Math.abs(Number(m.value) - Vof(s.n, s.T, s.P))).toBeLessThanOrEqual(maxReadingError(1, VSCALE) + 1e-9);
                const p = sim.measurements.at(-2);
                expect(p).toMatchObject({ unit: 'atm' });
                expect(p.name).toMatch(/^Bosim P/);
                expect(Math.abs(Number(p.value) - s.P)).toBeLessThanOrEqual(maxReadingError(1, MANO) + 1e-9);
            }
        });
    });

    it('calculation task: P(1 mol, 300 K, 10 L) = 2.4617 atm, and the simulation shows it', async () => {
        const e = exp.tasks[4].expected;
        assertExpectedIsPhysics(e, (R * 300) / 0.01 / ATM);
        await withSim('IdealGasSimulation', exp, async (sim) => {
            await sim.set({ volumeModel: 10 });
            expect(sim.get('pressure')).toBeCloseTo((R * 300) / 0.01 / ATM, 7);
            expect(sim.text()).toContain('Bosim: 2.46 atm');
        });
    });

    it('sweep: T 200…500 K, P 0.5…10 atm, n 0.5…2 mol in both modes; PV = nRT on screen', async () => {
        await withSim('IdealGasSimulation', exp, async (sim) => {
            for (const hold of ['pressure', 'volume']) {
                for (const n of [0.5, 1, 2]) {
                    for (const T of [200, 300, 500]) {
                        for (const P of [0.5, 1, 10]) {
                            sim.call('reset');
                            await sim.set({ holdConstant: hold });
                            await sim.set({ pressureModel: P });
                            await sim.set({ molesModel: n });
                            await sim.set({ temperatureModel: T });
                            const p = sim.get('pressure');
                            const V = sim.get('volume');
                            expect(p * ATM * V / 1000).toBeCloseTo(n * R * T, 6);
                            if (hold === 'pressure') expect(V).toBeCloseTo(Vof(n, T, P), 6);
                            sim.call('measure');
                            const [pm, vm] = sim.measurements.slice(-2);
                            expect(Math.abs(Number(vm.value) - V)).toBeLessThanOrEqual(0.1 + 1e-9);
                            expect(Math.abs(Number(pm.value) - p)).toBeLessThanOrEqual(0.01 + 1e-9);
                            expect(canvasText(sim)).toContain(`nRT = ${(n * R * T).toFixed(1)} J`);
                        }
                    }
                }
            }
        });
    });
});

// ═════════════════════════════ 6. Calorimetry ═════════════════════════════
describe('CalorimetrySimulation (thermometer 0.1 °C)', () => {
    const exp = EXP.calorimetry;
    const Tf = (m1, t1, m2, t2) => (m1 * t1 + m2 * t2) / (m1 + m2);
    const SETUP = {
        1: { hotMass: 200, hotTemp: 80, coldMass: 300, coldTemp: 20 },
        2: { hotMass: 200, hotTemp: 100, coldMass: 300, coldTemp: 20 },
        3: { hotMass: 500, hotTemp: 80, coldMass: 100, coldTemp: 20 },
    };

    it.each(measureTasks(exp))('task #%i: %s', async (i, task) => {
        const s = SETUP[i];
        const e = task.expected;
        assertExpectedIsPhysics(e, Tf(s.hotMass, s.hotTemp, s.coldMass, s.coldTemp));
        expect(maxReadingError(e.value, THERMO)).toBeLessThanOrEqual(0.5 * (e.tolerance_percent / 100) * e.value);
        await withSim('CalorimetrySimulation', exp, async (sim) => {
            await sim.set(s);
            // before mixing: the thermometer reads the two portions, never the "final" answer
            sim.call('measure');
            expect(pickForTask(sim, e)).toBeUndefined();
            expect(canvasText(sim)).not.toContain(`${e.value.toFixed(1)}°C`);
            sim.call('mix');
            for (let k = 0; k < N_REPEAT; k++) {
                sim.call('measure');
                const m = pickForTask(sim, e);
                expect(m.unit).toBe('°C');
                expect(m.value).toMatch(/^\d+\.\d$/);
                assertTaskReading(m, e);
                expect(Math.abs(Number(m.value) - e.value)).toBeLessThanOrEqual(maxReadingError(e.value, THERMO) + 1e-9);
            }
        });
    });

    it('heat released task: 0.2·4186·(80 − 44) = 30 139 J', () => {
        assertExpectedIsPhysics(exp.tasks[4].expected, 0.2 * 4186 * 36);
    });

    it('sweep: masses 100…500 g, hot 50…100 °C, cold 5…30 °C', async () => {
        await withSim('CalorimetrySimulation', exp, async (sim) => {
            for (const hotMass of [100, 300, 500]) {
                for (const coldMass of [100, 500]) {
                    for (const hotTemp of [50, 100]) {
                        for (const coldTemp of [5, 30]) {
                            await sim.set({ hotMass, hotTemp, coldMass, coldTemp });
                            sim.call('measure');
                            const [h, c] = sim.measurements.slice(-2);
                            expect(h.name).toBe('Issiq suv harorati');
                            expect(c.name).toBe('Sovuq suv harorati');
                            expect(Math.abs(Number(h.value) - hotTemp)).toBeLessThanOrEqual(0.125 + 1e-9);
                            expect(Math.abs(Number(c.value) - coldTemp)).toBeLessThanOrEqual(0.125 + 1e-9);
                            sim.call('mix');
                            sim.call('measure');
                            const want = Tf(hotMass, hotTemp, coldMass, coldTemp);
                            expect(Math.abs(Number(sim.measurements.at(-1).value) - want)).toBeLessThanOrEqual(0.125 + 1e-9);
                            const q = (hotMass / 1000) * 4186 * (hotTemp - want) / 1000;
                            expect(canvasText(sim)).toContain(`Q = ${q.toFixed(2)} kJ`);
                        }
                    }
                }
            }
        });
    });
});

// ═════════════════════════════ 7. Carnot cycle ═════════════════════════════
describe('CarnotCycleSimulation (theory readouts, no instrument noise)', () => {
    const exp = EXP['carnot-cycle'];

    it('calculation tasks: η, Q_H, W = physics (exact R)', () => {
        assertExpectedIsPhysics(exp.tasks[1].expected, (1 - 300 / 600) * 100);
        assertExpectedIsPhysics(exp.tasks[2].expected, R * 600 * Math.LN2);
        assertExpectedIsPhysics(exp.tasks[3].expected, R * (600 - 300) * Math.LN2);
        assertExpectedIsPhysics(exp.tasks[4].expected, (1 - 200 / 800) * 100);
    });

    it.each([[600, 300], [800, 200], [400, 400], [400, 200], [700, 350]])('T_H = %i K, T_C = %i K: η, Q_H, Q_C, W on screen', async (Th, Tc) => {
        await withSim('CarnotCycleSimulation', exp, async (sim) => {
            await sim.set({ hotTemp: Th, coldTemp: Tc });
            await sim.advance(3);
            const eta = (1 - Tc / Th) * 100;
            expect(sim.text()).toContain(`${eta.toFixed(1)}%`);
            const txt = canvasText(sim);
            expect(txt).toContain(`QH = ${(R * Th * Math.LN2).toFixed(0)} J`);
            expect(txt).toContain(`QC = ${(R * Tc * Math.LN2).toFixed(0)} J`);
            expect(txt).toContain(`W = ${(R * (Th - Tc) * Math.LN2).toFixed(0)} J`);
        });
    });
});

// ═════════════════════════════ 8. Entropy ═════════════════════════════
describe('EntropySimulation (theory readouts, no instrument noise)', () => {
    const exp = EXP.entropy;
    const Cp = 2.5 * R;

    it('calculation tasks = physics (Cₚ = 5R/2 = 20.786 J/(mol·K))', () => {
        assertExpectedIsPhysics(exp.tasks[1].expected, 500 / 300);
        const T2 = 300 + 500 / 20.786;
        assertExpectedIsPhysics(exp.tasks[2].expected, 20.786 * Math.log(T2 / 300));
        assertExpectedIsPhysics(exp.tasks[3].expected, T2);
        assertExpectedIsPhysics(exp.tasks[4].expected, 300 * 2 ** (2 / 3));
        expect(exp.tasks[2].expected.check.inputs['Cₚ']).toBeCloseTo(Cp, 3);
    });

    it.each([
        ['isothermal', 300, 500],
        ['isobaric', 300, 500],
        ['adiabatic', 300, 500],
        ['isothermal', 200, 1000],
        ['isobaric', 500, 1000],
    ])('%s, T₁ = %i K, Q = %i J: ΔS and T₂ on screen', async (processType, initialTemp, heatAdded) => {
        await withSim('EntropySimulation', exp, async (sim) => {
            await sim.set({ processType, initialTemp, heatAdded });
            await sim.advance(1);
            let dS;
            let T2;
            if (processType === 'isothermal') { dS = heatAdded / initialTemp; T2 = initialTemp; }
            else if (processType === 'isobaric') { T2 = initialTemp + heatAdded / Cp; dS = Cp * Math.log(T2 / initialTemp); }
            else { dS = 0; T2 = initialTemp * 2 ** (2 / 3); }
            const txt = canvasText(sim);
            expect(txt).toContain(`ΔS = ${dS.toFixed(3)} J/K`);
            if (processType !== 'isothermal') expect(txt).toContain(`T₂ = ${T2.toFixed(1)} K`);
        });
    });
});
