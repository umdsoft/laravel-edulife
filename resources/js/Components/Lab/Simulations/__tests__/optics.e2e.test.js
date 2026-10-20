/**
 * End-to-end verification of the optics simulations (second physics audit).
 *
 * Every simulation is mounted with the real SFC (headless harness), driven like a
 * student would (sliders → "O'lchash"), and the EMITTED measurements are compared
 * with an independent recomputation written here (plain Math, no optics.js), and
 * with the seeded task answers exactly as the server (LabTaskChecker) checks them.
 */
import { describe, it, expect } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { mountSimulation } from './mountSimulation.js';
import { maxReadingError } from '../../physics/instruments.js';
import { OPTICS_INSTRUMENTS, photometerMaxError } from '../../physics/optics.js';

// Vue's v-model directive checks `el.getRootNode() instanceof Document/ShadowRoot` when a
// bound ref changes; the headless harness has no DOM classes, so provide inert ones.
globalThis.Document ??= class Document {};
globalThis.ShadowRoot ??= class ShadowRoot {};

const EXPERIMENTS = JSON.parse(readFileSync(fileURLToPath(new URL('../../../../../../database/seeders/data/lab/experiments/optics.json', import.meta.url)), 'utf8'));
const EXP = Object.fromEntries(EXPERIMENTS.map((e) => [e.slug, e]));
const REPEATS = 30;

// ───────────────────── independent physics (no optics.js) ─────────────────────
const D2R = Math.PI / 180;
const R2D = 180 / Math.PI;
const snell = (n1, n2, aDeg) => {
    const s = (n1 * Math.sin(aDeg * D2R)) / n2;
    return s > 1 ? null : Math.asin(s) * R2D;
};
const imageDistance = (F, d) => 1 / (1 / F - 1 / d); // 1/F = 1/d + 1/f
const schottBk7 = (nm) => { // SCHOTT N-BK7 data-sheet Sellmeier constants, λ in µm
    const l2 = (nm / 1000) ** 2;
    return Math.sqrt(1 + (1.03961212 * l2) / (l2 - 0.00600069867) + (0.231792344 * l2) / (l2 - 0.0200179144) + (1.01046945 * l2) / (l2 - 103.560653));
};
const prismDev = (n, Adeg, t1) => { // exact deviation, angles in rad
    const A = Adeg * D2R;
    const r1 = Math.asin(Math.sin(t1) / n);
    const s = n * Math.sin(A - r1);
    return Math.abs(s) > 1 ? null : (t1 + Math.asin(s) - A) * R2D;
};
const prismNaIncidence = (Adeg) => Math.asin(schottBk7(589.3) * Math.sin((Adeg * D2R) / 2));
const fresnel = (n1, n2, aDeg) => {
    const ci = Math.cos(aDeg * D2R);
    const st = (n1 * Math.sin(aDeg * D2R)) / n2;
    const ct = Math.sqrt(1 - st * st);
    const rs = (n1 * ci - n2 * ct) / (n1 * ci + n2 * ct);
    const rp = (n2 * ci - n1 * ct) / (n2 * ci + n1 * ct);
    return { Rs: rs * rs, Rp: rp * rp };
};

// ───────────────────── server rules (port of LabTaskChecker) ─────────────────────
const normName = (s) => String(s).replace(/[ʻʼ’‘`´]/g, "'").replace(/\s+/g, ' ').trim().toLowerCase();
const PREFIX = { T: 1e12, G: 1e9, M: 1e6, k: 1e3, '': 1, c: 1e-2, m: 1e-3, 'µ': 1e-6, n: 1e-9, p: 1e-12 };
const BASES = ['V/m', 'N·m', 'Hz', 'eV', 'Bq', 'Pa', 'Ω', 'W', 'J', 'N', 'V', 'A', 'C', 'T', 's', 'm', 'g', 'L'];
const parseUnit = (u) => {
    for (const b of BASES) {
        if (!u.endsWith(b)) continue;
        const p = u.slice(0, u.length - b.length);
        if (!(p in PREFIX) || (p === 'c' && b !== 'm' && b !== 'L')) continue;
        return [b, PREFIX[p]];
    }
    return null;
};
const unitFactor = (from, to) => {
    const f = from.replace(/\s+/g, '');
    const t = to.replace(/\s+/g, '');
    if (f === t) return 1;
    const a = parseUnit(f);
    const b = parseUnit(t);
    return a && b && a[0] === b[0] ? a[1] / b[1] : null;
};
/** The measurement the server would take: the LAST one whose name starts with expected.measurement. */
const serverPick = (measurements, expected) => {
    const needle = normName(expected.measurement);
    return [...measurements].reverse().find((m) => normName(m.name).startsWith(needle) && Number.isFinite(Number(m.value)));
};
const serverAccepts = (m, expected) => {
    const f = unitFactor(m.unit, expected.unit);
    if (f === null) return false;
    return Math.abs(Number(m.value) * f - expected.value) <= (expected.tolerance_percent / 100) * Math.abs(expected.value) * (1 + 1e-9) + 1e-12;
};

const measurementTasks = (slug) => EXP[slug].tasks
    .map((t, i) => [`${slug} #${i + 1} ${t.title}`, t])
    .filter(([, t]) => t.expected?.source === 'measurement');

async function withSim(name, slug, fn) {
    const sim = await mountSimulation(name, { config: EXP[slug]?.simulation_config ?? {}, currentTask: EXP[slug]?.tasks[1] });
    try {
        await fn(sim);
        expect(sim.errors.map((e) => e.message)).toEqual([]);
        expect(sim.warnings).toEqual([]);
        expect(sim.text()).not.toMatch(/\bNaN\b|undefined|Infinity/);
    } finally {
        sim.unmount();
    }
}

/**
 * Drives one seeded measurement task: settings → REPEATS × measure → every
 * server-picked reading is accepted; reading errors stay inside maxErr, and
 * maxErr ≤ ½·tolerance (hard rule); expected.value = independent physics.
 */
async function checkTask(sim, task, { settings, truth, maxErr, measure = () => sim.call('measure') }) {
    const ex = task.expected;
    expect(Math.abs(truth - ex.value), 'expected.value = physics (5 s.f.)').toBeLessThanOrEqual(6e-5 * Math.abs(ex.value) + 1e-12);
    const tolAbs = (ex.tolerance_percent / 100) * Math.abs(ex.value);
    expect(maxErr, 'max reading error ≤ ½ tolerance').toBeLessThanOrEqual(0.5 * tolAbs);
    await sim.set(settings);
    await sim.advance(0.5);
    const values = [];
    for (let k = 0; k < REPEATS; k++) {
        const before = sim.measurements.length;
        measure();
        // eslint-disable-next-line no-await-in-loop
        await sim.flush();
        const m = serverPick(sim.measurements.slice(before), ex);
        expect(m, `a measurement named «${ex.measurement}…»`).toBeTruthy();
        expect(unitFactor(m.unit, ex.unit), `unit ${m.unit} vs ${ex.unit}`).not.toBeNull();
        const v = Number(m.value) * unitFactor(m.unit, ex.unit);
        expect(Math.abs(v - truth)).toBeLessThanOrEqual(maxErr + 1e-9);
        expect(serverAccepts(m, ex)).toBe(true);
        values.push(v);
    }
    return values;
}

// ════════════════════════════ 1. Reflection ════════════════════════════
describe('ReflectionSimulation (light-reflection)', () => {
    const P = OPTICS_INSTRUMENTS.protractor;
    it.each(measurementTasks('light-reflection'))('%s', async (_l, task) => {
        await withSim('ReflectionSimulation', 'light-reflection', async (sim) => {
            const a = task.expected.check.inputs['α'];
            await checkTask(sim, task, { settings: { incidentAngle: a, surfaceType: 'mirror' }, truth: a, maxErr: maxReadingError(a, P) });
            expect(sim.text()).toMatch(/Transportir: \d+\.\d°/);
        });
    });
    it('sweep: θr = θi on every surface, readings on a 0.5° scale, reproducible', async () => {
        await withSim('ReflectionSimulation', 'light-reflection', async (sim) => {
            for (const surfaceType of ['mirror', 'glass', 'water']) {
                for (const a of [10, 25, 45, 80]) {
                    await sim.set({ incidentAngle: a, surfaceType });
                    sim.call('measure');
                    const m = sim.last('Qaytish burchagi');
                    expect(m.unit).toBe('°');
                    expect(Math.abs(Number(m.value) - a)).toBeLessThanOrEqual(maxReadingError(a, P));
                    expect((Number(m.value) * 2) % 1).toBe(0);
                }
            }
        });
        // the k-th reading of a set-up is the same in a fresh mount
        const first = [];
        for (let run = 0; run < 2; run++) {
            await withSim('ReflectionSimulation', 'light-reflection', async (sim) => {
                await sim.set({ incidentAngle: 35 });
                for (let k = 0; k < 5; k++) sim.call('measure');
                first[run] = sim.measurements.map((m) => m.value);
            });
        }
        expect(first[1]).toEqual(first[0]);
    });
});

// ════════════════════════════ 2. Refraction ════════════════════════════
describe('RefractionSimulation (light-refraction)', () => {
    const P = OPTICS_INSTRUMENTS.protractor;
    it.each(measurementTasks('light-refraction'))('%s', async (_l, task) => {
        await withSim('RefractionSimulation', 'light-refraction', async (sim) => {
            const { 'n₁': n1, 'n₂': n2, 'α': a } = task.expected.check.inputs;
            const truth = snell(n1, n2, a);
            await checkTask(sim, task, { settings: { n1, n2, angle1: a }, truth, maxErr: maxReadingError(truth, P) });
        });
    });
    it('sweep incl. slider limits and total internal reflection', async () => {
        await withSim('RefractionSimulation', 'light-refraction', async (sim) => {
            for (const [n1, n2] of [[1, 1], [1, 2.5], [2, 1], [1.5, 1], [1.3, 1.5]]) {
                for (const a of [0, 20, 41, 42, 60, 85]) {
                    await sim.set({ n1, n2, angle1: a });
                    sim.call('measure');
                    const m = sim.measurements.at(-1);
                    const g = snell(n1, n2, a);
                    if (g === null) {
                        expect(m.name).toMatch(/^To'liq ichki qaytish/);
                        expect(Math.abs(Number(m.value) - a)).toBeLessThanOrEqual(0.5);
                        expect(sim.get('isTotalReflection')).toBe(true);
                    } else {
                        expect(m.name.startsWith('Sinish burchagi')).toBe(true);
                        expect(Math.abs(Number(m.value) - g)).toBeLessThanOrEqual(maxReadingError(g, P));
                        expect(sim.get('angle2')).toBeCloseTo(g, 9);
                    }
                }
            }
            await sim.set({ n1: 1.5, n2: 1, angle1: 30 });
            expect(sim.get('criticalAngleDeg')).toBeCloseTo(Math.asin(1 / 1.5) * R2D, 9);
        });
    });
});

// ════════════════════════════ 3. Prism dispersion ════════════════════════════
describe('DispersionSimulation (prism-dispersion)', () => {
    const S = OPTICS_INSTRUMENTS.spectrometer;
    const angular = (A) => {
        const t = prismNaIncidence(A);
        return [prismDev(schottBk7(400), A, t), prismDev(schottBk7(700), A, t)];
    };
    it.each(measurementTasks('prism-dispersion'))('%s', async (_l, task) => {
        await withSim('DispersionSimulation', 'prism-dispersion', async (sim) => {
            const inp = task.expected.check.inputs;
            if (task.expected.unit === 'Δn') {
                const truth = schottBk7(inp['λ₁']) - schottBk7(inp['λ₂']);
                await checkTask(sim, task, { settings: { prismAngle: 60 }, truth, maxErr: 0.5e-6 });
            } else {
                const [v, r] = angular(inp.A);
                const maxErr = maxReadingError(v, S) + maxReadingError(r, S);
                const values = await checkTask(sim, task, { settings: { prismAngle: inp.A }, truth: v - r, maxErr });
                expect(new Set(values).size, 'repeated readings scatter').toBeGreaterThan(1);
                // the individual deviations are emitted too
                expect(Math.abs(Number(sim.last("Og'ish burchagi δ_qiz").value) - r)).toBeLessThanOrEqual(maxReadingError(r, S));
                expect(Math.abs(Number(sim.last("Og'ish burchagi δ_bin").value) - v)).toBeLessThanOrEqual(maxReadingError(v, S));
            }
        });
    });
    it('sweep of the prism angle 30°…90° (90°: no emergent ray, A > 2θc)', async () => {
        await withSim('DispersionSimulation', 'prism-dispersion', async (sim) => {
            for (const A of [30, 50, 70, 80, 90]) {
                await sim.set({ prismAngle: A });
                const before = sim.measurements.length;
                sim.call('measure');
                const ms = sim.measurements.slice(before);
                expect(Number(ms[0].value)).toBeCloseTo(schottBk7(400) - schottBk7(700), 5);
                const n = schottBk7(589.3);
                if (n * Math.sin((A * D2R) / 2) > 1) {
                    expect(ms).toHaveLength(1);
                    continue;
                }
                const [v, r] = angular(A);
                expect(Math.abs(Number(ms.at(-1).value) - (v - r))).toBeLessThanOrEqual(2 * maxReadingError(v, S));
                // displayed refractive indices of the real glass
                expect(sim.get('colors')[0].n).toBeCloseTo(schottBk7(700), 12);
                expect(sim.get('colors')[5].n).toBeCloseTo(schottBk7(400), 12);
            }
        });
    });
});

// ════════════════════════════ 4–6. Lenses and mirrors ════════════════════════════
const B = OPTICS_INSTRUMENTS.benchImage;
const benchCases = [
    ['ConvexLensSimulation', 'convex-lens', (inp) => ({ focalLength: inp.F, objectDistance: inp.d })],
    ['ConcaveLensSimulation', 'concave-lens', (inp) => ({ focalLength: inp.F, objectDistance: inp.d })],
    ['CurvedMirrorSimulation', 'curved-mirrors', (inp) => ({ mirrorType: inp.F > 0 ? 'concave' : 'convex', focalLength: Math.abs(inp.F), objectDistance: inp.d })],
];
describe.each(benchCases)('%s (%s): image distance on the optical bench', (name, slug, settingsOf) => {
    it.each(measurementTasks(slug))('%s', async (_l, task) => {
        await withSim(name, slug, async (sim) => {
            const inp = task.expected.check.inputs;
            const truth = imageDistance(inp.F, inp.d);
            const values = await checkTask(sim, task, { settings: settingsOf(inp), truth, maxErr: maxReadingError(truth, B) });
            for (const v of values) expect(Math.abs(v * 10 - Math.round(v * 10))).toBeLessThan(1e-9); // 1 mm scale
            expect(sim.text()).toMatch(/Optik skamya: d' = -?\d+\.\d cm/);
        });
    });
});

describe('lens / mirror sweeps over the slider ranges', () => {
    it('convex lens F 5…20 cm, d 5…50 cm (d = F: image at infinity)', async () => {
        await withSim('ConvexLensSimulation', 'convex-lens', async (sim) => {
            for (const F of [5, 12, 20]) {
                for (const d of [5, 9, 12, 20, 33, 50]) {
                    await sim.set({ focalLength: F, objectDistance: d });
                    sim.call('measure');
                    const m = sim.measurements.at(-1);
                    if (d === F) {
                        expect(m).toMatchObject({ value: '0', unit: '1/cm' });
                        continue;
                    }
                    const f = imageDistance(F, d);
                    expect(m.name).toBe('Tasvir masofasi');
                    expect(Math.abs(Number(m.value) - f)).toBeLessThanOrEqual(maxReadingError(f, B) + 1e-9);
                    expect(sim.get('magnification')).toBeCloseTo(-f / d, 9);
                }
            }
        });
    });
    it('concave lens F −30…−5 cm, d 10…60 cm: always virtual, upright, reduced', async () => {
        await withSim('ConcaveLensSimulation', 'concave-lens', async (sim) => {
            for (const F of [-30, -17, -5]) {
                for (const d of [10, 26, 60]) {
                    await sim.set({ focalLength: F, objectDistance: d });
                    sim.call('measure');
                    const f = imageDistance(F, d);
                    expect(f).toBeLessThan(0);
                    expect(Math.abs(Number(sim.measurements.at(-1).value) - f)).toBeLessThanOrEqual(maxReadingError(f, B) + 1e-9);
                    const m = sim.get('magnification');
                    expect(m).toBeGreaterThan(0);
                    expect(m).toBeLessThan(1);
                }
            }
        });
    });
    it('mirrors |F| 10…40 cm, d 15…80 cm, concave and convex', async () => {
        await withSim('CurvedMirrorSimulation', 'curved-mirrors', async (sim) => {
            for (const mirrorType of ['concave', 'convex']) {
                for (const F of [10, 25, 40]) {
                    for (const d of [15, 40, 80]) {
                        await sim.set({ mirrorType, focalLength: F, objectDistance: d });
                        sim.call('measure');
                        const m = sim.measurements.at(-1);
                        const Fs = mirrorType === 'concave' ? F : -F;
                        if (d === Fs) {
                            expect(m.unit).toBe('1/cm');
                            continue;
                        }
                        const f = imageDistance(Fs, d);
                        expect(m.name.startsWith('Tasvir masofasi')).toBe(true);
                        expect(Math.abs(Number(m.value) - f)).toBeLessThanOrEqual(maxReadingError(f, B) + 1e-9);
                    }
                }
            }
        });
    });
});

// ════════════════════════════ 7. Young interference ════════════════════════════
describe('InterferenceSimulation (light-interference)', () => {
    const M = OPTICS_INSTRUMENTS.fringeMicrometer;
    // fringes ±N/2 must lie inside the central envelope of the a = 0.02 mm slits
    const N = (dMm) => 2 * Math.min(5, Math.ceil(dMm / 0.02 - 1e-9) - 1);
    const maxErr = (dy, dMm) => (2 * maxReadingError((N(dMm) / 2) * dy, M)) / N(dMm) + 0.0005;
    it.each(measurementTasks('light-interference'))('%s', async (_l, task) => {
        await withSim('InterferenceSimulation', 'light-interference', async (sim) => {
            const { 'λ': l, L, d } = task.expected.check.inputs;
            const truth = (l * 1e-9 * L) / (d * 1e-3) / 1e-3; // mm
            const values = await checkTask(sim, task, { settings: { wavelength: l, slitDistance: d, screenDistance: L }, truth, maxErr: maxErr(truth, d) });
            expect(new Set(values).size).toBeGreaterThan(1);
            expect(sim.last(`${N(d)} ta oraliq kengligi`)).toBeTruthy();
        });
    });
    it('sweep: λ 400…700 nm, d 0.1…1 mm, L 0.5…2 m', async () => {
        await withSim('InterferenceSimulation', 'light-interference', async (sim) => {
            for (const [l, d, L] of [[400, 0.1, 0.5], [700, 0.1, 2], [550, 1, 1.3], [632.8, 0.3, 1]]) {
                await sim.set({ wavelength: l, slitDistance: d, screenDistance: L });
                await sim.advance(0.2);
                sim.call('measure');
                const dy = (l * 1e-9 * L) / (d * 1e-3) / 1e-3;
                const m = sim.last("Interferensiya oralig'i");
                expect(m.unit).toBe('mm');
                expect(Math.abs(Number(m.value) - dy)).toBeLessThanOrEqual(maxErr(dy, d) + 1e-9);
                expect(sim.get('fringeSpacing')).toBeCloseTo(dy, 9);
            }
        });
    });
});

// ════════════════════════════ 8. Single-slit diffraction ════════════════════════════
describe('DiffractionSimulation (light-diffraction)', () => {
    const M = OPTICS_INSTRUMENTS.fringeMicrometer;
    // θ = arctg(y₁/L), y₁ = (x₊ − x₋)/2 → |Δθ| ≤ (Δy₁/L) rad, plus the 4-decimal display
    const maxErrDeg = (y1mm) => (maxReadingError(y1mm, M) / 1000) * R2D + 0.00005;
    const truthOf = (l, a) => Math.asin((l * 1e-9) / (a * 1e-3)) * R2D;
    it.each(measurementTasks('light-diffraction'))('%s', async (_l, task) => {
        await withSim('DiffractionSimulation', 'light-diffraction', async (sim) => {
            const { 'λ': l, a } = task.expected.check.inputs;
            const truth = truthOf(l, a);
            const y1 = Math.tan(truth * D2R) * 1000; // mm on the 1 m screen
            await checkTask(sim, task, { settings: { wavelength: l, slitWidth: a }, truth, maxErr: maxErrDeg(y1) });
            const w = sim.last('Minimumlar orasidagi masofa 2y₁');
            expect(w.unit).toBe('mm');
            expect(Math.abs(Number(w.value) - 2 * y1)).toBeLessThanOrEqual(2 * maxReadingError(y1, M) + 1e-9);
        });
    });
    it('sweep: λ 400…700 nm, a 0.05…0.30 mm', async () => {
        await withSim('DiffractionSimulation', 'light-diffraction', async (sim) => {
            for (const [l, a] of [[400, 0.05], [700, 0.05], [500, 0.3], [700, 0.3]]) {
                await sim.set({ wavelength: l, slitWidth: a });
                await sim.advance(0.2);
                sim.call('measure');
                const t = truthOf(l, a);
                const y1 = Math.tan(t * D2R) * 1000;
                expect(Math.abs(Number(sim.last('Birinchi minimum burchagi').value) - t)).toBeLessThanOrEqual(maxErrDeg(y1) + 1e-9);
                expect(sim.get('firstMinimumY')).toBeCloseTo(y1, 9);
            }
        });
    });
});

// ════════════════════════════ 9. Polarisation ════════════════════════════
describe('PolarizationSimulation (polarization)', () => {
    /** worst-case P error from the two photometer readings (corner search) */
    const degreeMaxErr = (Is, Ip) => {
        const P = (Is - Ip) / (Is + Ip);
        let worst = 0;
        for (const s of [-1, 1]) {
            for (const p of [-1, 1]) {
                const a = Is + s * photometerMaxError(Is);
                const b = Ip + p * photometerMaxError(Ip);
                worst = Math.max(worst, Math.abs((a - b) / (a + b) - P));
            }
        }
        return worst * 100 + 0.005; // %, + 2-decimal display
    };
    it.each(measurementTasks('polarization'))('%s', async (_l, task) => {
        await withSim('PolarizationSimulation', 'polarization', async (sim) => {
            const inp = task.expected.check.inputs;
            const { Rs, Rp } = fresnel(inp['n₁'], inp['n₂'], inp['α']);
            const Is = (Rs / 2) * 100;
            const Ip = (Rp / 2) * 100;
            if (task.expected.check.formula_id === 'reflection_polarization_degree') {
                const truth = ((Is - Ip) / (Is + Ip)) * 100;
                await checkTask(sim, task, { settings: { angle: inp['α'], analyzerAngle: 0 }, truth, maxErr: degreeMaxErr(Is, Ip) });
            } else {
                const phi = inp['φ'] * D2R;
                const truth = Is * Math.cos(phi) ** 2 + Ip * Math.sin(phi) ** 2;
                const values = await checkTask(sim, task, { settings: { angle: inp['α'], analyzerAngle: inp['φ'] }, truth, maxErr: photometerMaxError(truth) });
                expect(new Set(values).size).toBeGreaterThan(1);
            }
        });
    });
    it('sweep: Brewster angle, crossed analyser, normal incidence', async () => {
        await withSim('PolarizationSimulation', 'polarization', async (sim) => {
            expect(sim.get('brewsterAngle')).toBeCloseTo(Math.atan(1.5) * R2D, 9);
            for (const [a, phi] of [[0, 0], [0, 90], [56, 90], [30, 45], [85, 180]]) {
                await sim.set({ angle: a, analyzerAngle: phi });
                sim.call('measure');
                const { Rs, Rp } = fresnel(1, 1.5, a);
                const Is = (Rs / 2) * 100;
                const Ip = (Rp / 2) * 100;
                const f = phi * D2R;
                const I = Is * Math.cos(f) ** 2 + Ip * Math.sin(f) ** 2;
                expect(Math.abs(Number(sim.last("Analizatordan o'tgan I/I₀").value) - I)).toBeLessThanOrEqual(photometerMaxError(I) + 1e-12);
                const P = ((Is - Ip) / (Is + Ip)) * 100;
                expect(Math.abs(Number(sim.last('Qaytgan nur qutblanish darajasi').value) - P)).toBeLessThanOrEqual(degreeMaxErr(Is, Ip) + 1e-9);
            }
            // normal incidence: R = 4 %, no polarisation
            await sim.set({ angle: 0, analyzerAngle: 0 });
            expect(sim.get('reflectedS') + sim.get('reflectedP')).toBeCloseTo(0.04, 12);
            expect(sim.get('polarizationDegree')).toBeCloseTo(0, 12);
        });
    });
});
