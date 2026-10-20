/**
 * End-to-end check of the waves simulations (second physics audit).
 *
 * Every simulation is mounted headless with the real SFC, driven like a student
 * would (set the sliders → let the animation run → press 📏) and the EMITTED
 * measurements are compared with an INDEPENDENT recomputation written here
 * (closed-form textbook formulas, not the physics module). For every seeded
 * measurement task the server rule is replayed: name prefix, unit (exact or SI
 * prefix), value within tolerance — for ≥ 30 repeated readings where the
 * instrument has scatter — and the worst-case reading error is proven to be at
 * most half of the task tolerance.
 */
import { describe, it, expect } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { mountSimulation } from './mountSimulation.js';
import { maxReadingError } from '../../physics/instruments.js';
import { WAVE_INSTRUMENTS } from '../../physics/waves.js';
import { CALCULATORS } from '../../physics/calculator.js';

// Vue's v-model directive checks `rootNode instanceof Document | ShadowRoot` when a
// bound ref is changed from code; the headless harness has no DOM classes.
globalThis.Document ??= class Document {};
globalThis.ShadowRoot ??= class ShadowRoot {};

const EXPERIMENTS = JSON.parse(readFileSync(fileURLToPath(new URL(
    '../../../../../../database/seeders/data/lab/experiments/waves.json', import.meta.url)), 'utf8'));
const EXP = Object.fromEntries(EXPERIMENTS.map((e) => [e.slug, e]));

// ─────────────── independent reference physics (textbook formulas) ───────────────
const C = 299792458;
const H = 6.62607015e-34;
const E = 1.602176634e-19;
const soundSpeed = (tC) => 343 * Math.sqrt((273.15 + tC) / 293.15); // 343 m/s at 20 °C
const response = (r, z) => 1 / Math.sqrt((1 - r * r) ** 2 + (2 * z * r) ** 2);
const doppler = (f, vo, vs, v = 343) => (f * (v + vo)) / (v - vs);
const photonEV = (lambdaM) => (H * C) / (lambdaM * E);

// ─────────────── replay of the server checker (LabTaskChecker.php) ───────────────
const PREFIXES = { T: 1e12, G: 1e9, M: 1e6, k: 1e3, '': 1, c: 1e-2, m: 1e-3, 'µ': 1e-6, n: 1e-9, p: 1e-12 };
const BASES = ['V/m', 'N·m', 'Hz', 'eV', 'Bq', 'Pa', 'Ω', 'W', 'J', 'N', 'V', 'A', 'C', 'T', 's', 'm', 'g', 'L'];
const normUnit = (u) => String(u ?? '').replace(/\s+/g, '').replace('μ', 'µ');
function parseUnit(u) {
    for (const base of BASES) {
        if (!u.endsWith(base)) continue;
        const p = u.slice(0, u.length - base.length);
        if (!(p in PREFIXES)) continue;
        if (p === 'c' && !['m', 'L'].includes(base)) continue;
        return [base, PREFIXES[p]];
    }
    return null;
}
function unitFactor(from, to) {
    const a = normUnit(from), b = normUnit(to);
    if (a === b) return 1;
    const pa = parseUnit(a), pb = parseUnit(b);
    if (!pa || !pb || pa[0] !== pb[0]) return null;
    return pa[1] / pb[1];
}
const normName = (s) => String(s).replace(/[ʻʼ’‘`´]/g, "'").replace(/\s+/g, ' ').trim().toLowerCase();
/** The measurement the server would pick for this task (last whose name starts with the prefix). */
function pick(expected, measurements) {
    const needle = normName(expected.measurement);
    return [...measurements].reverse().find((m) => normName(m.name).startsWith(needle));
}
function serverAccepts(expected, m) {
    const f = unitFactor(m.unit, expected.unit);
    if (f === null) return false;
    return Math.abs(m.value * f - expected.value) <= (Math.abs(expected.value) * expected.tolerance_percent) / 100 * (1 + 1e-9) + 1e-12;
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

/** Captures every canvas fillText of the next frames. */
function captureCanvasText(sim) {
    const texts = [];
    const ctx = sim.get('ctx');
    ctx.fillText = (s) => texts.push(String(s));
    return texts;
}

/** The student's procedure: set the task's settings, let it run, press 📏 `n` times. */
async function measureTask(sim, settings, n = 1) {
    await sim.set(settings);
    await sim.advance(1);
    for (let i = 0; i < n; i++) sim.call('measure');
    await sim.flush();
}

const REPEATS = 32;

/**
 * Generic seeded-task check: for each [taskIndex, settings, trueValueInExpectedUnit,
 * worstCaseRelativeError] the procedure is run REPEATS times.
 */
async function checkTasks(simName, slug, cases) {
    const exp = EXP[slug];
    await withSim(simName, exp, async (sim) => {
        for (const [idx, settings, trueValue, worstRel, scatter = true] of cases) {
            const task = exp.tasks[idx];
            const ex = task.expected;
            expect(task.type, `${slug} #${idx + 1}`).toBe('measurement');
            // expected.value is the exact model value (independent recomputation)
            expect(Math.abs(ex.value - trueValue)).toBeLessThanOrEqual(5e-5 * Math.abs(trueValue) + 1e-12);
            // HARD RULE: worst-case reading error ≤ ½ tolerance
            expect(worstRel, `${slug} #${idx + 1} worst-case error`).toBeLessThanOrEqual(0.5 * ex.tolerance_percent / 100);
            const before = sim.measurements.length;
            await measureTask(sim, settings, REPEATS);
            const mine = sim.measurements.slice(before);
            expect(mine.length).toBe(REPEATS);
            for (let k = 0; k < REPEATS; k++) {
                const m = pick(ex, mine.slice(0, k + 1));
                expect(m, `${slug} #${idx + 1}: a measurement named «${ex.measurement}…»`).toBeTruthy();
                expect(m).toBe(mine[k]);
                expect(unitFactor(m.unit, ex.unit), `${slug} #${idx + 1} unit ${m.unit}`).not.toBeNull();
                const f = unitFactor(m.unit, ex.unit);
                // within the instrument's worst-case band of the true value …
                expect(Math.abs(m.value * f - trueValue)).toBeLessThanOrEqual(worstRel * Math.abs(trueValue) + 1e-9);
                // … and accepted by the server rule
                expect(serverAccepts(ex, m), `${slug} #${idx + 1}: ${m.value} ${m.unit}`).toBe(true);
            }
            if (scatter) {
                // a real instrument scatters: the 32 readings are not all identical
                expect(new Set(mine.map((m) => m.value)).size).toBeGreaterThan(1);
            }
        }
    });
}

// worst-case relative error of a reading s/t with t from the light-gate timer, reported with 4 significant digits
const timerRel = (t) => {
    const e = maxReadingError(t, WAVE_INSTRUMENTS.lightGateTimer);
    return e / (t - e) + 5e-4;
};

// ═══════════════════════════════ 1. To'lqin xususiyatlari ═══════════════════════════════
describe('WaveBasicsSimulation (rubber cord, light-gate timer, m/s)', () => {
    it('seeded tasks: v = s/t in m/s within tolerance for 32 readings', async () => {
        await checkTasks('WaveBasicsSimulation', 'wave-properties', [
            [1, { frequency: 1, wavelength: 1 }, 1 * 1, timerRel(5 / 1)],
            [2, { frequency: 2, wavelength: 1.5 }, 2 * 1.5, timerRel(5 / 3)],
            [3, { frequency: 0.5, wavelength: 2 }, 0.5 * 2, timerRel(5 / 1)],
        ]);
    });

    it('sweep incl. slider min/max: emitted v ≈ fλ, name carries s and t, never px', async () => {
        await withSim('WaveBasicsSimulation', EXP['wave-properties'], async (sim) => {
            for (const f of [0.5, 1.3, 3]) {
                for (const lam of [0.5, 1.2, 2]) {
                    await measureTask(sim, { frequency: f, wavelength: lam });
                    const m = sim.measurements.at(-1);
                    expect(m.unit).toBe('m/s');
                    const v = f * lam;
                    expect(Math.abs(m.value - v) / v).toBeLessThanOrEqual(timerRel(5 / v));
                    const t = Number(/t = ([\d.]+) s/.exec(m.name)[1]);
                    expect(Math.abs(t - 5 / v)).toBeLessThanOrEqual(maxReadingError(5 / v, WAVE_INSTRUMENTS.lightGateTimer) + 1e-12);
                    expect(m.name).toMatch(/^To'lqin tezligi \(s = 5\.00 m, t = \d+\.\d{3} s\)$/);
                }
            }
            const texts = captureCanvasText(sim);
            await sim.set({ frequency: 2, wavelength: 1.5 });
            await sim.advance(0.1);
            expect(texts).toContain("To'lqin uzunligi (λ): 1.50 m");
            expect(texts).toContain('Tezlik (v = λf): 3.00 m/s');
            // the only "px" left is the stated scale "1 px = 1 cm"
            expect(texts.filter((s) => !s.startsWith('Masshtab: 1 px = 1 cm')).join(' ')).not.toMatch(/\bpx\b/);
            expect(sim.text()).toContain('1.5 m');
            expect(sim.text()).not.toMatch(/\bpx\b/);
        });
    });

    it('readings are reproducible for the same set-up (seeded instrument)', async () => {
        const run = async () => {
            let vals = [];
            await withSim('WaveBasicsSimulation', EXP['wave-properties'], async (sim) => {
                await measureTask(sim, { frequency: 2, wavelength: 1.5 }, 5);
                vals = sim.measurements.map((m) => m.value);
            });
            return vals;
        };
        expect(await run()).toEqual(await run());
    });
});

// ═══════════════════════════════ 2. Turg'un to'lqinlar (Melde) ═══════════════════════════════
describe('StandingWavesSimulation (Melde string, frequency counter)', () => {
    it('seeded tasks: f_n = n·f₁ read on the 0.1 Hz counter', async () => {
        await checkTasks('StandingWavesSimulation', 'standing-waves', [
            [1, { frequency: 20, harmonicMode: 3 }, 3 * 20, 0.05 / 60, false],
            [3, { frequency: 15, harmonicMode: 4 }, 4 * 15, 0.05 / 60, false],
        ]);
    });

    it('the measurement name carries the mode number n (the student sees which harmonic was measured)', async () => {
        const exp = EXP['standing-waves'];
        await withSim('StandingWavesSimulation', exp, async (sim) => {
            await measureTask(sim, { frequency: 15, harmonicMode: 4 });
            const m = pick(exp.tasks[3].expected, sim.measurements);
            expect(m.name).toBe('Garmonika chastotasi (n = 4)');
            expect(serverAccepts(exp.tasks[3].expected, m)).toBe(true);
        });
    });

    it('sweep: every mode n = 1…6 and f₁ = 5…50 Hz; v = 2L·f₁ and T = μv² on screen', async () => {
        await withSim('StandingWavesSimulation', EXP['standing-waves'], async (sim) => {
            for (const f1 of [5, 20, 37.5, 50]) {
                for (let n = 1; n <= 6; n++) {
                    await measureTask(sim, { frequency: f1, harmonicMode: n });
                    const m = sim.measurements.at(-1);
                    expect(m).toEqual({ name: `Garmonika chastotasi (n = ${n})`, value: n * f1, unit: 'Hz' });
                }
            }
            const texts = captureCanvasText(sim);
            await sim.set({ frequency: 20, harmonicMode: 3 });
            await sim.advance(0.1);
            expect(texts).toContain('λₙ = 2L/n = 0.667 m');
            expect(texts).toContain('v = 2L·f₁ = 40.0 m/s,  T = μv² = 1.60 N');
            expect(texts).toContain('Tugunlar (N): 4');
            expect(texts).toContain("Do'ngliklar (D): 3");
        });
    });

    it('calculation tasks: λ₃ and v = λ₃ν₃ match the Melde set-up', () => {
        const exp = EXP['standing-waves'];
        expect(exp.tasks[2].expected.value).toBeCloseTo(2 / 3, 4);
        expect(exp.tasks[4].expected.value).toBe(2 * 1 * 20);
        // tension of the default task 1.6 N = 0.163 kg hanging mass: a realistic Melde load
        expect(1e-3 * 40 ** 2).toBeCloseTo(1.6, 12);
    });
});

// ═══════════════════════════════ 3. Tovush to'lqinlari ═══════════════════════════════
describe('SoundWavesSimulation (microphone + tape, temperature)', () => {
    const tapeRel = (lam) => maxReadingError(lam, WAVE_INSTRUMENTS.tape) / lam;
    it('seeded tasks (20 °C): λ = v/f read on the tape', async () => {
        await checkTasks('SoundWavesSimulation', 'sound-waves', [
            [1, { frequency: 440, temperature: 20 }, 343 / 440, tapeRel(343 / 440)],
            [2, { frequency: 1000, temperature: 20 }, 343 / 1000, tapeRel(343 / 1000)],
            [3, { frequency: 100, temperature: 20 }, 343 / 100, tapeRel(343 / 100)],
        ]);
    });

    it('440 Hz is reachable on the frequency slider (step 10 Hz)', async () => {
        await withSim('SoundWavesSimulation', EXP['sound-waves'], async (sim) => {
            expect(sim.get('frequency')).toBe(440);
            expect(sim.get('temperature')).toBe(20);
            expect((440 - 100) % 10).toBe(0);
        });
    });

    it('sweep f = 100…1000 Hz, t = −20…40 °C: λ = v(t)/f within the tape error', async () => {
        await withSim('SoundWavesSimulation', EXP['sound-waves'], async (sim) => {
            for (const tC of [-20, 0, 20, 40]) {
                for (const f of [100, 440, 730, 1000]) {
                    await measureTask(sim, { frequency: f, temperature: tC });
                    const m = sim.measurements.at(-1);
                    const lam = soundSpeed(tC) / f;
                    expect(m.unit).toBe('m');
                    expect(m.name).toBe(`To'lqin uzunligi (f = ${f} Hz, t = ${tC} °C)`);
                    expect(Math.abs(m.value - lam)).toBeLessThanOrEqual(maxReadingError(lam, WAVE_INSTRUMENTS.tape) + 1e-12);
                }
            }
            const texts = captureCanvasText(sim);
            await sim.set({ frequency: 440, temperature: 0 });
            await sim.advance(0.1);
            expect(texts).toContain('v = 331.1 m/s (0 °C)');
            expect(texts).toContain(`λ = v/f = ${((soundSpeed(0) / 440) * 100).toFixed(1)} cm`);
        });
    });
});

// ═══════════════════════════════ 4. Rezonans ═══════════════════════════════
describe('ResonanceSimulation (A_st = 10 mm, displacement sensor 0.1 mm)', () => {
    const sensorRel = (R) => maxReadingError(R * 0.01, WAVE_INSTRUMENTS.displacementSensor) / (R * 0.01);
    it('seeded tasks: A/Aₛₜ within tolerance for 32 readings', async () => {
        await checkTasks('ResonanceSimulation', 'resonance', [
            [1, { naturalFreq: 2, drivingFreq: 2, damping: 0.1 }, response(1, 0.1), sensorRel(response(1, 0.1))],
            [2, { naturalFreq: 2, drivingFreq: 1, damping: 0.1 }, response(0.5, 0.1), sensorRel(response(0.5, 0.1))],
            [3, { naturalFreq: 2, drivingFreq: 2, damping: 0.05 }, response(1, 0.05), sensorRel(response(1, 0.05))],
        ]);
    });

    it('sweep f₀, f, ζ over the slider ranges: reading within the sensor band', async () => {
        await withSim('ResonanceSimulation', EXP.resonance, async (sim) => {
            for (const f0 of [1, 2.4, 4]) {
                for (const f of [0.5, 1.9, 2.4, 4]) {
                    for (const z of [0.05, 0.25, 0.5]) {
                        await measureTask(sim, { naturalFreq: f0, drivingFreq: f, damping: z });
                        const m = sim.measurements.at(-1);
                        const R = response(f / f0, z);
                        expect(m.unit).toBe('A/Aₛₜ');
                        expect(m.name.startsWith(`Amplituda (f = ${f.toFixed(1)} Hz, A = `)).toBe(true);
                        expect(Math.abs(m.value - R) * 0.01).toBeLessThanOrEqual(maxReadingError(R * 0.01, WAVE_INSTRUMENTS.displacementSensor) + 1e-12);
                        const aMm = Number(/A = ([\d.]+) mm/.exec(m.name)[1]);
                        expect(m.value).toBeCloseTo(aMm / 10, 10);
                    }
                }
            }
        });
    });

    it('calculation task values: peak f₀√(1 − 2ζ²) and Q', () => {
        expect(EXP.resonance.tasks[4].expected.value).toBeCloseTo(2 * Math.sqrt(1 - 2 * 0.01), 4);
    });
});

// ═══════════════════════════════ 5. Dopler effekti ═══════════════════════════════
describe('DopplerSimulation (frequency counter 0.1 Hz)', () => {
    it('seeded tasks', async () => {
        await checkTasks('DopplerSimulation', 'doppler-effect', [
            [1, { sourceFrequency: 440, sourceVelocity: 30, observerVelocity: 0 }, doppler(440, 0, 30), 0.05 / 400, false],
            [2, { sourceFrequency: 440, sourceVelocity: -30, observerVelocity: 0 }, doppler(440, 0, -30), 0.05 / 400, false],
            [3, { sourceFrequency: 440, sourceVelocity: 0, observerVelocity: 30 }, doppler(440, 30, 0), 0.05 / 400, false],
        ]);
    });

    it('sweep incl. min/max speeds and frequencies', async () => {
        await withSim('DopplerSimulation', EXP['doppler-effect'], async (sim) => {
            for (const f of [200, 440, 800]) {
                for (const vs of [-50, 0, 30, 100]) {
                    for (const vo of [-50, 0, 50]) {
                        await measureTask(sim, { sourceFrequency: f, sourceVelocity: vs, observerVelocity: vo });
                        const m = sim.measurements.at(-1);
                        expect(m.name).toBe('Kuzatilgan chastota');
                        expect(m.unit).toBe('Hz');
                        expect(Math.abs(m.value - doppler(f, vo, vs))).toBeLessThanOrEqual(0.05 + 1e-9);
                    }
                }
            }
        });
    });

    it('calculation task: both approaching at 30 m/s', () => {
        expect(EXP['doppler-effect'].tasks[4].expected.value).toBeCloseTo(doppler(440, 30, 30), 2);
    });
});

// ═══════════════════════════════ 6. To'lqin interferensiyasi ═══════════════════════════════
describe('WaveInterferenceSimulation (ripple tank, v = 20 cm/s, cm)', () => {
    // λ = span/N with the span read on a 1 mm ruler, reported with 4 significant digits
    const spanRel = (lamCm) => {
        const n = Math.max(1, Math.min(10, Math.floor(30 / lamCm + 1e-9)));
        const span = (n * lamCm) / 100;
        return maxReadingError(span, WAVE_INSTRUMENTS.tape) / span + 5e-4;
    };
    it('seeded tasks: λ in cm', async () => {
        await checkTasks('WaveInterferenceSimulation', 'wave-interference', [
            [1, { frequency: 5, separation: 10 }, 20 / 5, spanRel(20 / 5)],
            [2, { frequency: 10, separation: 10 }, 20 / 10, spanRel(20 / 10)],
        ]);
    });

    it('sweep f = 1…10 Hz: λ = 20 cm/s ÷ f, N·λ ≤ 30 cm on the ruler; nodal-line count on screen', async () => {
        await withSim('WaveInterferenceSimulation', EXP['wave-interference'], async (sim) => {
            for (const f of [1, 2.5, 5, 7.5, 10]) {
                for (const d of [2.5, 10, 20]) {
                    await measureTask(sim, { frequency: f, separation: d });
                    const m = sim.measurements.at(-1);
                    const lam = 20 / f;
                    expect(m.unit).toBe('cm');
                    expect(Math.abs(m.value - lam) / lam).toBeLessThanOrEqual(spanRel(lam));
                    const [, n, span] = /\((\d+)λ = ([\d.]+) cm\)/.exec(m.name).map(Number);
                    expect(n).toBeGreaterThanOrEqual(1);
                    expect(n).toBeLessThanOrEqual(10);
                    expect(n * lam).toBeLessThanOrEqual(30 + 1e-9);
                    expect(span).toBeLessThanOrEqual(30.2);
                }
            }
            const texts = captureCanvasText(sim);
            await sim.set({ frequency: 5, separation: 10 });
            await sim.advance(0.1);
            expect(texts).toContain('λ = v/f = 4.00 cm   (v = 20 cm/s)');
            expect(texts).toContain('d = 10.0 cm,  d/λ = 2.50');
            // |(m + ½)·4| < 10 → m + ½ ∈ {±0.5, ±1.5}: 4 nodal lines (seeded calculation task)
            expect(texts).toContain('Tugun chiziqlari: 4');
            expect(sim.text()).not.toMatch(/\bpx\b/);
        });
    });

    it('calculation tasks: nodal lines in cm (same counts as before)', () => {
        const t = EXP['wave-interference'].tasks;
        expect(t[3].expected.check.inputs).toEqual({ d: 10, 'λ': 4 });
        expect(t[3].expected.value).toBe(4);
        expect(t[4].expected.value).toBe(10);
        expect(CALCULATORS.nodal_lines.inputs.map((i) => i.unit)).toEqual(['cm', 'cm']);
    });
});

// ═══════════════════════════════ 7. Elektromagnit spektr ═══════════════════════════════
describe('EMSpectrumSimulation (reference readout, no instrument)', () => {
    it('seeded tasks: band-centre photon energies', async () => {
        await checkTasks('EMSpectrumSimulation', 'em-spectrum', [
            [1, { selectedWave: 'visible' }, photonEV(Math.sqrt(380e-9 * 760e-9)), 5e-4, false],
            [2, { selectedWave: 'xray' }, photonEV(Math.sqrt(1e-11 * 10e-9)), 5e-4, false],
        ]);
    });

    it('every band: E = hc/λ at the band centre (open bands: edge value)', async () => {
        const centre = {
            radio: 1, microwave: Math.sqrt(1e-3 * 1), infrared: Math.sqrt(760e-9 * 1e-3), visible: Math.sqrt(380e-9 * 760e-9),
            ultraviolet: Math.sqrt(10e-9 * 380e-9), xray: Math.sqrt(1e-11 * 10e-9), gamma: 1e-11,
        };
        await withSim('EMSpectrumSimulation', EXP['em-spectrum'], async (sim) => {
            for (const [id, lam] of Object.entries(centre)) {
                await measureTask(sim, { selectedWave: id });
                const m = sim.measurements.at(-1);
                expect(m.unit).toBe('eV');
                expect(Math.abs(m.value - photonEV(lam)) / photonEV(lam)).toBeLessThanOrEqual(5e-4);
            }
        });
    });
});

// ═══════════════════════════════ 8. Urishlar ═══════════════════════════════
describe('BeatsSimulation (10 beats timed, 1 ms)', () => {
    it('seeded tasks', async () => {
        await checkTasks('BeatsSimulation', 'beats-phenomenon', [
            [1, { freq1: 440, freq2: 444 }, 4, timerRel(10 / 4)],
            [2, { freq1: 440, freq2: 450 }, 10, timerRel(10 / 10)],
        ]);
    });

    it('sweep incl. equal frequencies (no beats → 0 Hz) and the largest difference', async () => {
        await withSim('BeatsSimulation', EXP['beats-phenomenon'], async (sim) => {
            for (const [f1, f2] of [[440, 440], [400, 402], [444, 440], [430, 470], [400, 500]]) {
                await measureTask(sim, { freq1: f1, freq2: f2 });
                const m = sim.measurements.at(-1);
                const fb = Math.abs(f1 - f2);
                expect(m.unit).toBe('Hz');
                expect(m.name.startsWith('Urishlar chastotasi')).toBe(true);
                if (fb === 0) expect(m.value).toBe(0);
                else expect(Math.abs(m.value - fb) / fb).toBeLessThanOrEqual(timerRel(10 / fb));
            }
        });
    });
});

// ═══════════════════════════════ all tasks covered ═══════════════════════════════
describe('every seeded measurement task of the waves domain is covered above', () => {
    it('task list', () => {
        const ids = EXPERIMENTS.flatMap((e) => e.tasks.map((t, i) => [e.slug, i, t]))
            .filter(([, , t]) => t.type === 'measurement').map(([s, i]) => `${s}#${i}`);
        expect(ids).toEqual([
            'wave-properties#1', 'wave-properties#2', 'wave-properties#3',
            'standing-waves#1', 'standing-waves#3',
            'sound-waves#1', 'sound-waves#2', 'sound-waves#3',
            'resonance#1', 'resonance#2', 'resonance#3',
            'doppler-effect#1', 'doppler-effect#2', 'doppler-effect#3',
            'wave-interference#1', 'wave-interference#2',
            'em-spectrum#1', 'em-spectrum#2',
            'beats-phenomenon#1', 'beats-phenomenon#2',
        ]);
    });
});
