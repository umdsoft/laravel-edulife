/**
 * End-to-end verification of the 10 mechanics simulations (real SFC, real template,
 * real animation through the headless harness).
 *
 * For every simulation: default config + a sweep of slider values (incl. min / max)
 * → the real procedure (start → advance → "O'lchash") → the EMITTED measurement is
 * compared with an INDEPENDENT recomputation written here (series / Simpson /
 * textbook formulas, not the physics module). For every seeded measurement task:
 * the server's name prefix rule, the unit, the value within tolerance for ≥ 30
 * repeated readings, and the hard rule "worst-case reading error ≤ ½ tolerance".
 */
import { describe, it, expect } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { mountSimulation } from './mountSimulation.js';
import { maxReadingError } from '../../physics/instruments.js';
import { MECH_INSTRUMENTS, TIMED_PERIODS } from '../../physics/mechanics.js';
import { calculate } from '../../physics/calculator.js';

// Vue's vModelText.beforeUpdate tests `rootNode instanceof Document || … ShadowRoot`;
// the headless harness defines `document` but not these classes, so changing a
// slider value through sim.set() would throw "Document is not defined".
globalThis.Document ??= class Document {};
globalThis.ShadowRoot ??= class ShadowRoot {};

const EXPERIMENTS = JSON.parse(
    readFileSync(fileURLToPath(new URL('../../../../../../database/seeders/data/lab/experiments/mechanics.json', import.meta.url)), 'utf8'),
);
const bySlug = Object.fromEntries(EXPERIMENTS.map((e) => [e.slug, e]));

const g = 9.81;
const PI = Math.PI;
const deg = (d) => (d * PI) / 180;
const REPEATS = 30;
const TIMER = MECH_INSTRUMENTS.timer;

/** The server's name normalisation (LabTaskChecker::normalizeName). */
const normName = (s) => String(s).replace(/[ʻʼ’‘`´]/g, "'").replace(/\s+/gu, ' ').toLowerCase().trim();
/** Units the server converts with SI prefixes; every other unit must match exactly. */
const PREFIXABLE = ['V/m', 'N·m', 'Hz', 'eV', 'Bq', 'Pa', 'Ω', 'W', 'J', 'N', 'V', 'A', 'C', 'T', 's', 'm', 'g', 'L'];
const unitAccepted = (unit, expected) => unit === expected || (PREFIXABLE.includes(expected) && PREFIXABLE.some((b) => b === expected && unit.endsWith(b)));

/* ───────────── independent reference physics (written here, not imported) ───────────── */

/** Exact pendulum period by composite Simpson integration of K(k). */
function refPendulumPeriod(L, theta0Deg) {
    const k = Math.sin(deg(theta0Deg) / 2);
    const n = 2000;
    const h = PI / 2 / n;
    let s = 0;
    for (let i = 0; i <= n; i++) {
        const f = 1 / Math.sqrt(1 - k * k * Math.sin(i * h) ** 2);
        s += f * (i === 0 || i === n ? 1 : i % 2 ? 4 : 2);
    }
    return 4 * Math.sqrt(L / g) * ((h / 3) * s);
}
const refFallTime = (h) => Math.sqrt((2 * h) / g);
const refNewton = (F, m, f) => (F > f ? (F - f) / m : 0);
const refIncline = (thDeg, mu) => {
    const a = g * (Math.sin(deg(thDeg)) - mu * Math.cos(deg(thDeg)));
    return Math.tan(deg(thDeg)) > mu && a > 0 ? a : 0;
};
const refSpringPeriod = (k, m) => 2 * PI * Math.sqrt(m / k);
function refRange(v, thDeg, h0) {
    const vx = v * Math.cos(deg(thDeg));
    const vy = v * Math.sin(deg(thDeg));
    const t = (vy + Math.sqrt(vy * vy + 2 * g * h0)) / g;
    return vx * t;
}
function refCollision(m1, m2, u1, u2, e) {
    // momentum conservation + Newton's restitution law v2 − v1 = −e(u2 − u1)
    const p = m1 * u1 + m2 * u2;
    const v1 = (p - m2 * e * (u1 - u2)) / (m1 + m2);
    return { v1, v2: v1 + e * (u1 - u2) };
}

/* ───────────── worst-case error bounds of the emitted values ───────────── */

const E_T = (t) => maxReadingError(t, TIMER); // timer worst case for a reading of t
/** a = 2s/t² from a timer reading of t: worst |Δa| (+ display rounding 0.0005). */
const boundAccel = (s, a) => {
    const t = Math.sqrt((2 * s) / a);
    const e = E_T(t);
    return Math.max((2 * s) / (t - e) ** 2 - a, a - (2 * s) / (t + e) ** 2) + 0.0005;
};
/** T = t_N/N: worst |ΔT| (+ rounding of 5 decimals). */
const boundPeriod = (T, n = TIMED_PERIODS) => E_T(n * T) / n + 0.000005;
/** a = 4π²r/T² with T from N timed turns. */
const boundCentripetal = (r, omega) => {
    const T = (2 * PI) / omega;
    const dT = E_T(TIMED_PERIODS * T) / TIMED_PERIODS + 0.000005;
    const a = omega * omega * r;
    return Math.max((4 * PI * PI * r) / (T - dT) ** 2 - a, a - (4 * PI * PI * r) / (T + dT) ** 2) + 0.0005;
};
const FLAG = 0.1;
const boundGateSpeed = (v) => {
    if (v === 0) return 0;
    const dt = FLAG / Math.abs(v);
    const e = E_T(dt);
    return Math.max(FLAG / (dt - e) - Math.abs(v), Math.abs(v) - FLAG / (dt + e));
};
const boundMomentum = (m1, m2, v1, v2, e) => (e === 0 ? (m1 + m2) * boundGateSpeed(v1) : m1 * boundGateSpeed(v1) + m2 * boundGateSpeed(v2)) + 0.00005;
const boundTorque = (F, r, thDeg) => {
    const eF = maxReadingError(F, MECH_INSTRUMENTS.forceSensor);
    const er = maxReadingError(r, MECH_INSTRUMENTS.metreRule);
    return ((F + eF) * (r + er) - F * r) * Math.abs(Math.sin(deg(thDeg))) + 0.0005;
};

/* ───────────── harness helpers ───────────── */

async function withSim(name, exp, fn, opts = {}) {
    const sim = await mountSimulation(name, { config: exp?.simulation_config ?? null, currentTask: exp?.tasks?.[1] ?? null, fps: opts.fps ?? 30 });
    try {
        await fn(sim);
        expect(sim.errors.map((e) => e.message)).toEqual([]);
        expect(sim.warnings).toEqual([]);
        expect(sim.text()).not.toMatch(/\bNaN\b|undefined|Infinity/);
    } finally {
        sim.unmount();
    }
}
const num = (m) => Number(m.value);

/** Procedures: set the apparatus, run it, press "O'lchash", return the emitted measurement named `prefix`. */
const PROCEDURES = {
    async pendulum(sim, { L, theta0, mass }) {
        await sim.set({ length: L, angle: theta0, ...(mass ? { mass } : {}) });
        sim.call('start');
        await sim.advance(refPendulumPeriod(L, theta0) * (TIMED_PERIODS + 1) + 0.5);
        sim.call('measure');
        const m = sim.last('Tebranish davri');
        sim.call('reset');
        return m;
    },
    async freeFall(sim, { h }) {
        await sim.set({ height: h });
        sim.call('start');
        await sim.advance(refFallTime(h) + 0.3);
        sim.call('measure');
        const m = sim.last('Tushish vaqti');
        sim.call('reset');
        return m;
    },
    async newton(sim, { F, m, f }) {
        await sim.set({ force: F, mass: m, friction: f });
        sim.call('start');
        const a = refNewton(F, m, f);
        await sim.advance(a > 0 ? Math.sqrt(2 / a) + 0.2 : 0.2);
        sim.call('measure');
        const r = sim.last('Tezlanish');
        sim.call('reset');
        return r;
    },
    async incline(sim, { theta, mu }) {
        await sim.set({ angle: theta, friction: mu });
        sim.call('start');
        const a = refIncline(theta, mu);
        await sim.advance(a > 0 ? Math.sqrt(2 / a) + 0.2 : 0.2);
        sim.call('measure');
        const r = sim.last('Tezlanish');
        sim.call('reset');
        return r;
    },
    async spring(sim, { k, m }) {
        await sim.set({ springK: k, mass: m });
        sim.call('start');
        await sim.advance(refSpringPeriod(k, m) * (TIMED_PERIODS + 1.25) + 0.3);
        sim.call('measure');
        const r = sim.last('Davr');
        sim.call('reset');
        return r;
    },
    async projectile(sim, { v, theta, h0 }) {
        await sim.set({ velocity: v, angle: theta, height: h0 });
        sim.call('start');
        const vy = v * Math.sin(deg(theta));
        await sim.advance((vy + Math.sqrt(vy * vy + 2 * g * h0)) / g + 0.3);
        sim.call('measure');
        const r = sim.last('Uzoqlik');
        sim.call('reset');
        return r;
    },
    async momentum(sim, { m1, m2, u1, u2, e }) {
        await sim.set({ mass1: m1, mass2: m2, v1: u1, v2: u2, elasticity: e });
        sim.call('start');
        // gap (px) / closing speed (px/s), track 400 px/m
        const gap = 600 - 150 - (18 + m1 * 15) - (18 + m2 * 15);
        await sim.advance(gap / ((u1 - u2) * 400) + 0.3);
        sim.call('measure');
        const r = { p: sim.last('Yakuniy impuls'), v1: sim.last('1-aravacha'), v2: sim.last('2-aravacha') };
        sim.call('reset');
        return r;
    },
    async circular(sim, { r, omega }) {
        await sim.set({ radius: r, angularVelocity: omega });
        sim.call('start');
        await sim.advance(((2 * PI) / omega) * (TIMED_PERIODS + 0.5) + 0.2);
        sim.call('measure');
        const m = sim.last('Markazga intilma tezlanish');
        sim.call('reset');
        return m;
    },
    async torque(sim, { F, r, theta }) {
        await sim.set({ force: F, forceAngle: theta, pivotPos: Math.round(100 * (1 - r / 2)) });
        sim.call('measure');
        return sim.last('Moment');
    },
    async energy(sim, { m, h, k }) {
        await sim.set({ mass: m, height: h, friction: k });
        sim.call('start');
        await sim.advance(Math.sqrt((2 * h) / (g * (1 - k))) + 0.3);
        sim.call('measure');
        const r = sim.last('Jami energiya');
        sim.call('reset');
        return r;
    },
};

/* ───────────── seeded measurement tasks ───────────── */

/** slug → [component, procedure, settings(task, exp), bound(expectedValue, settings)] */
const TASK_SETUP = {
    'simple-pendulum': ['PendulumSimulation', 'pendulum', (t) => ({ L: t.L, theta0: t['θ₀'] }), (e) => boundPeriod(e)],
    'free-fall': ['FreeFallSimulation', 'freeFall', (t) => ({ h: t.h }), (e) => E_T(e) + 0.00005],
    'newtons-second-law': ['NewtonSecondSimulation', 'newton', (t) => ({ F: t.F, m: t.m, f: t.f }), (e) => boundAccel(1, e)],
    'inclined-plane': ['InclinedPlaneSimulation', 'incline', (t) => ({ theta: t['θ'], mu: t['μ'] }), (e) => boundAccel(1, e)],
    'spring-oscillations': ['SpringOscillationSimulation', 'spring', (t) => ({ k: t.k, m: t.m }), (e) => boundPeriod(e)],
    'projectile-motion': ['ProjectileSimulation', 'projectile', (t) => ({ v: t['v₀'], theta: t['θ'], h0: t['h₀'] }), (e) => maxReadingError(e, MECH_INSTRUMENTS.tapeMeasure) + 0.0005],
    'momentum-conservation': [
        'MomentumSimulation',
        'momentum',
        (t, exp) => {
            const start = exp.tasks[0].expected.check.inputs; // the initial state given in task 1
            const e = Math.abs(t['v₁'] - t['v₂']) < 1e-9 ? 0 : 1;
            return { m1: t['m₁'], m2: t['m₂'], u1: start['v₁'], u2: start['v₂'], e };
        },
        (e, s) => {
            const c = refCollision(s.m1, s.m2, s.u1, s.u2, s.e);
            return boundMomentum(s.m1, s.m2, c.v1, c.v2, s.e);
        },
    ],
    'circular-motion': ['CircularMotionSimulation', 'circular', (t) => ({ r: t.r, omega: t['ω'] }), (e, s) => boundCentripetal(s.r, s.omega)],
    'torque-rotation': ['TorqueSimulation', 'torque', (t) => ({ F: t.F, r: t.r, theta: t['θ'] }), (e, s) => boundTorque(s.F, s.r, s.theta)],
    'energy-conservation': ['EnergyConservationSimulation', 'energy', (t) => ({ m: t.m, h: t.h, k: t.k ?? 0 }), () => 0.0005],
};

const MEASUREMENT_TASKS = EXPERIMENTS.flatMap((exp) =>
    exp.tasks.map((task, i) => [exp.slug, i + 1, task]).filter(([, , t]) => t.expected?.source === 'measurement'),
);

describe('mechanics: every seeded measurement task is reproduced by the real simulation', () => {
    it('covers every mechanics experiment with a measurement task', () => {
        expect(MEASUREMENT_TASKS.length).toBe(27);
        for (const [slug] of MEASUREMENT_TASKS) expect(TASK_SETUP[slug]).toBeDefined();
    });

    it.each(MEASUREMENT_TASKS.map(([slug, step, task]) => [`${slug} #${step}`, slug, task]))('%s', async (_label, slug, task) => {
        const exp = bySlug[slug];
        const { expected } = task;
        const [component, procedure, settingsOf, boundOf] = TASK_SETUP[slug];
        const settings = settingsOf(expected.check.inputs, exp);
        const tol = (expected.tolerance_percent / 100) * Math.abs(expected.value);

        // expected.value is the physics at those inputs (5 significant figures)
        const calc = calculate(expected.check.formula_id, expected.check.inputs);
        expect(calc.ok).toBe(true);
        expect(Math.abs(calc.value * (expected.check.factor ?? 1) - expected.value)).toBeLessThanOrEqual(1e-4 * Math.abs(expected.value));

        // HARD RULE: worst-case reading error ≤ ½ tolerance
        const bound = boundOf(expected.value, settings);
        expect(bound).toBeLessThanOrEqual(0.5 * tol);

        await withSim(component, exp, async (sim) => {
            const readings = [];
            for (let i = 0; i < REPEATS; i++) {
                // eslint-disable-next-line no-await-in-loop
                const out = await PROCEDURES[procedure](sim, settings);
                const m = out.p ?? out;
                expect(m, 'measurement emitted').toBeDefined();
                expect(normName(m.name).startsWith(normName(expected.measurement))).toBe(true);
                expect(unitAccepted(m.unit, expected.unit)).toBe(true);
                const v = num(m);
                expect(Math.abs(v - expected.value)).toBeLessThanOrEqual(tol);
                expect(Math.abs(v - expected.value)).toBeLessThanOrEqual(bound + 1e-4 * Math.abs(expected.value));
                readings.push(v);
            }
            // energies are computed (no instrument); a = 4π²r/T² from 10 timed turns is too precise to scatter in 3 decimals
            const scatters = !['energy-conservation', 'circular-motion'].includes(slug);
            if (scatters) expect(new Set(readings).size).toBeGreaterThan(1); // repeated readings scatter like a real lab
        });
    }, 120000);
});

describe('mechanics: readings are reproducible (seeded), not Math.random', () => {
    it('two fresh mounts give the same sequence of free-fall readings', async () => {
        const seq = async () => {
            const out = [];
            await withSim('FreeFallSimulation', bySlug['free-fall'], async (sim) => {
                for (let i = 0; i < 5; i++) out.push(num(await PROCEDURES.freeFall(sim, { h: 1 })));
            });
            return out;
        };
        expect(await seq()).toEqual(await seq());
    });
});

/* ───────────── slider sweeps (default, min, max) vs the independent reference ───────────── */

describe('mechanics sweeps: emitted values = independent recomputation within the instrument bound', () => {
    it('pendulum: T = t₁₀/10 for L, θ₀ at min / default / max', async () => {
        await withSim('PendulumSimulation', bySlug['simple-pendulum'], async (sim) => {
            for (const [L, th] of [[1.0, 15], [0.3, 5], [1.5, 25], [0.3, 25], [1.5, 5]]) {
                const T = refPendulumPeriod(L, th);
                const m = await PROCEDURES.pendulum(sim, { L, theta0: th });
                expect(m.unit).toBe('s');
                expect(Math.abs(num(m) - T)).toBeLessThanOrEqual(boundPeriod(T));
            }
            // mass does not change the period
            const T = refPendulumPeriod(1, 15);
            for (const mass of [0.1, 2.0]) expect(Math.abs(num(await PROCEDURES.pendulum(sim, { L: 1, theta0: 15, mass })) - T)).toBeLessThanOrEqual(boundPeriod(T));
        });
    }, 120000);

    it('pendulum: timer readout shows N = 10 and t₁₀, the emitted T is t₁₀/10', async () => {
        await withSim('PendulumSimulation', bySlug['simple-pendulum'], async (sim) => {
            sim.call('start');
            await sim.advance(refPendulumPeriod(1, 15) * 11 + 0.5);
            sim.call('measure');
            const m = sim.last('Tebranish davri');
            const t10 = sim.get('timerReading');
            expect(sim.get('countedPeriods')).toBe(10);
            expect(num(m)).toBeCloseTo(t10 / 10, 4);
            expect(sim.text()).toContain(`t = ${t10.toFixed(4)} s`);
            expect(sim.text()).toContain(`T = t/N = ${m.value} s`);
            // a parameter change clears the old timing
            sim.call('pause');
            await sim.set({ length: 0.5 });
            expect(sim.get('period')).toBe(0);
        });
    }, 60000);

    it('free fall: t for h at min / default / max; g = 2h/t² shown', async () => {
        await withSim('FreeFallSimulation', bySlug['free-fall'], async (sim) => {
            for (const h of [0.2, 1.0, 2.0, 1.5]) {
                const m = await PROCEDURES.freeFall(sim, { h });
                expect(m.name).toBe(`Tushish vaqti (h=${h}m)`);
                expect(Math.abs(num(m) - refFallTime(h))).toBeLessThanOrEqual(E_T(refFallTime(h)) + 0.00005);
            }
            sim.call('start');
            await sim.advance(1);
            const t = sim.get('fallTime');
            expect(sim.get('measuredG')).toBeCloseTo((2 * 1.5) / (t * t), 10);
            expect(sim.text()).toContain(`t = ${t.toFixed(4)} s`);
        });
    }, 60000);

    it('Newton II: a = 2s/t² at slider extremes; F ≤ f → block stays, a = 0', async () => {
        await withSim('NewtonSecondSimulation', bySlug['newtons-second-law'], async (sim) => {
            for (const [F, m, f] of [[10, 2, 0], [1, 0.5, 0], [20, 5, 10], [20, 0.5, 0], [10, 2, 4], [1, 5, 0]]) {
                const a = refNewton(F, m, f);
                const r = await PROCEDURES.newton(sim, { F, m, f });
                expect(r.unit).toBe('m/s²');
                expect(Math.abs(num(r) - a)).toBeLessThanOrEqual(boundAccel(1, a));
            }
            const stuck = await PROCEDURES.newton(sim, { F: 5, m: 2, f: 5 });
            expect(num(stuck)).toBe(0);
        });
    }, 60000);

    it('inclined plane: a at θ, μ extremes; tan θ ≤ μ → a = 0', async () => {
        await withSim('InclinedPlaneSimulation', bySlug['inclined-plane'], async (sim) => {
            for (const [theta, mu] of [[30, 0.1], [10, 0], [60, 0.5], [60, 0], [45, 0.1], [10, 0.1]]) {
                const a = refIncline(theta, mu);
                const r = await PROCEDURES.incline(sim, { theta, mu });
                expect(Math.abs(num(r) - a)).toBeLessThanOrEqual(boundAccel(1, a));
            }
            expect(num(await PROCEDURES.incline(sim, { theta: 10, mu: 0.2 }))).toBe(0);
            // the mass does not change the acceleration
            await sim.set({ mass: 5 });
            const a = refIncline(30, 0.1);
            expect(Math.abs(num(await PROCEDURES.incline(sim, { theta: 30, mu: 0.1 })) - a)).toBeLessThanOrEqual(boundAccel(1, a));
        });
    }, 60000);

    it('spring: T = t₁₀/10 at k, m extremes; amplitude does not matter', async () => {
        await withSim('SpringOscillationSimulation', bySlug['spring-oscillations'], async (sim) => {
            for (const [k, m] of [[50, 0.5], [30, 0.1], [200, 2.0], [30, 2.0], [200, 0.1]]) {
                const T = refSpringPeriod(k, m);
                const r = await PROCEDURES.spring(sim, { k, m });
                expect(Math.abs(num(r) - T)).toBeLessThanOrEqual(boundPeriod(T));
            }
            await sim.set({ amplitude: 0.15 });
            const T = refSpringPeriod(50, 0.5);
            expect(Math.abs(num(await PROCEDURES.spring(sim, { k: 50, m: 0.5 })) - T)).toBeLessThanOrEqual(boundPeriod(T));
        });
    }, 120000);

    it('projectile: tape-measured range at v₀, θ, h₀ extremes (table-top distances)', async () => {
        await withSim('ProjectileSimulation', bySlug['projectile-motion'], async (sim) => {
            for (const [v, theta, h0] of [[5, 45, 0], [1, 80, 0], [8, 45, 1.5], [5, 0, 1], [8, 60, 0], [1, 0, 1.5]]) {
                const R = refRange(v, theta, h0);
                const r = await PROCEDURES.projectile(sim, { v, theta, h0 });
                expect(r.unit).toBe('m');
                expect(Math.abs(num(r) - R)).toBeLessThanOrEqual(maxReadingError(R, MECH_INSTRUMENTS.tapeMeasure) + 0.0005);
                expect(R).toBeLessThan(8); // a table-top lab, not a field
            }
            // complementary angles give the same range
            const a = num(await PROCEDURES.projectile(sim, { v: 5, theta: 30, h0: 0 }));
            const b = num(await PROCEDURES.projectile(sim, { v: 5, theta: 60, h0: 0 }));
            expect(Math.abs(a - b)).toBeLessThanOrEqual(2 * (maxReadingError(2.207, MECH_INSTRUMENTS.tapeMeasure) + 0.0005));
        });
    }, 60000);

    it('momentum: photogate velocities and p after elastic / inelastic collisions', async () => {
        await withSim('MomentumSimulation', null, async (sim) => {
            for (const [m1, m2, u1, u2] of [[0.4, 0.2, 0.5, -0.3], [1, 0.1, 1, -1], [0.1, 1, 0.2, 0], [0.5, 0.5, 0.6, -0.2]]) {
                for (const e of [1, 0]) {
                    const c = refCollision(m1, m2, u1, u2, e);
                    const r = await PROCEDURES.momentum(sim, { m1, m2, u1, u2, e });
                    expect(Math.abs(num(r.v1) - c.v1)).toBeLessThanOrEqual(boundGateSpeed(c.v1) + 0.00005);
                    expect(Math.abs(num(r.v2) - c.v2)).toBeLessThanOrEqual(boundGateSpeed(c.v2) + 0.00005);
                    const p0 = m1 * u1 + m2 * u2;
                    expect(Math.abs(num(r.p) - p0)).toBeLessThanOrEqual(boundMomentum(m1, m2, c.v1, c.v2, e) + 1e-9);
                }
            }
            // nothing to measure before the collision
            sim.call('measure');
            const before = sim.measurements.length;
            sim.call('start');
            await sim.advance(0.1);
            sim.call('measure');
            expect(sim.measurements.length).toBe(before);
        });
    }, 60000);

    it('circular motion: a = 4π²r/T² from 10 timed turns at r, ω extremes', async () => {
        await withSim('CircularMotionSimulation', bySlug['circular-motion'], async (sim) => {
            for (const [r, omega] of [[0.4, 5], [0.1, 1], [0.5, 10], [0.1, 10], [0.5, 1]]) {
                const m = await PROCEDURES.circular(sim, { r, omega });
                expect(m.unit).toBe('m/s²');
                expect(Math.abs(num(m) - omega * omega * r)).toBeLessThanOrEqual(boundCentripetal(r, omega));
            }
            const T = sim.last('Aylanish davri');
            expect(Math.abs(num(T) - (2 * PI) / 1)).toBeLessThanOrEqual(boundPeriod((2 * PI) / 1));
        });
    }, 120000);

    it('torque: M = F·r·sin θ from force-sensor and rule readings, pivot / angle / force extremes', async () => {
        await withSim('TorqueSimulation', bySlug['torque-rotation'], async (sim) => {
            for (const [F, r, theta] of [[20, 1, 90], [5, 1.6, 30], [50, 0.4, 150], [50, 1.6, 90], [5, 0.4, 45]]) {
                const m = await PROCEDURES.torque(sim, { F, r, theta });
                expect(m.unit).toBe('N·m');
                expect(Math.abs(num(m) - F * r * Math.sin(deg(theta)))).toBeLessThanOrEqual(boundTorque(F, r, theta));
            }
            expect(sim.text()).toContain('Kuch datchigi: F =');
        });
    }, 60000);

    it('energy: Eₖ + Eₚ = mgh without resistance at every instant, = mgh(1 − k) on landing', async () => {
        await withSim('EnergyConservationSimulation', bySlug['energy-conservation'], async (sim) => {
            for (const [m, h, k] of [[1, 3, 0], [2, 5, 0], [1, 3, 0.2], [0.5, 1, 0.3], [5, 10, 0.05]]) {
                const r = await PROCEDURES.energy(sim, { m, h, k });
                expect(Math.abs(num(r) - m * g * h * (1 - k))).toBeLessThanOrEqual(0.0005 + 1e-9);
            }
            // mid-fall: E_mech = mgh − k·m·g·s, and Eₖ + Eₚ + Q = mgh
            await sim.set({ mass: 1, height: 3, friction: 0 });
            sim.call('start');
            await sim.advance(0.4);
            sim.call('measure');
            expect(Math.abs(num(sim.last('Jami energiya')) - 29.43)).toBeLessThanOrEqual(0.0005 + 1e-9);
            sim.call('reset');
            await sim.set({ friction: 0.2 });
            sim.call('start');
            await sim.advance(0.4);
            const s = sim.get('position');
            sim.call('measure');
            expect(Math.abs(num(sim.last('Jami energiya')) - (29.43 - 0.2 * 9.81 * s))).toBeLessThanOrEqual(0.0005 + 1e-9);
            expect(sim.get('totalE') + sim.get('heatQ')).toBeCloseTo(29.43, 9);
        });
    }, 60000);
});
