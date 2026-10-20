/**
 * End-to-end checks of the atomic & nuclear physics simulations: the real SFCs are
 * mounted headless (mountSimulation), driven like a student does (sliders → start →
 * time → "O'lchash") and every EMITTED measurement / key displayed value is compared
 * with an INDEPENDENT recomputation written here from CODATA 2022 / NIST data (not by
 * calling the physics module that the simulations use).
 */
import { describe, it, expect } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { mountSimulation } from './mountSimulation.js';
import { maxReadingError, INSTRUMENTS } from '../../physics/instruments.js';
import { SPECTROSCOPE, XRAY_GONIOMETER } from '../../physics/atomic.js';

const EXPERIMENTS = JSON.parse(readFileSync(fileURLToPath(new URL(
    '../../../../../../database/seeders/data/lab/experiments/atomic.json', import.meta.url)), 'utf8'));
const exp = (slug) => EXPERIMENTS.find((e) => e.slug === slug);

// ── independent constants (CODATA 2022, NIST) ──
const H = 6.62607015e-34;
const E = 1.602176634e-19;
const C = 299792458;
const ME = 9.1093837139e-31;
const R_INF = 10973731.568157;
const RY = 13.60569312299;
const MP_ME = 1836.152673426;
const RH = R_INF / (1 + 1 / MP_ME);
const EH = RY / (1 + 1 / MP_ME);
const HC_EVNM = (H * C / E) * 1e9;
const DEG = 180 / Math.PI;

// ── server rules (app/Services/Lab/LabTaskChecker.php) ──
const normName = (s) => String(s).replace(/[ʻʼ’‘`´]/g, "'").replace(/\s+/g, ' ').toLowerCase().trim();
const PREFIX = { T: 1e12, G: 1e9, M: 1e6, k: 1e3, '': 1, c: 1e-2, m: 1e-3, 'µ': 1e-6, n: 1e-9, p: 1e-12 };
const BASES = ['V/m', 'N·m', 'Hz', 'eV', 'Bq', 'Pa', 'Ω', 'W', 'J', 'N', 'V', 'A', 'C', 'T', 's', 'm', 'g', 'L'];
const parseUnit = (u) => {
    for (const b of BASES) {
        if (!u.endsWith(b)) continue;
        const p = u.slice(0, u.length - b.length);
        if (!(p in PREFIX) || (p === 'c' && !['m', 'L'].includes(b))) continue;
        return [b, PREFIX[p]];
    }
    return null;
};
const unitFactor = (from, to) => {
    if (from === to) return 1;
    const a = parseUnit(from);
    const b = parseUnit(to);
    return a && b && a[0] === b[0] ? a[1] / b[1] : null;
};
/** The server's verdict for a measurement task given the emitted list. */
const serverCheck = (task, measurements) => {
    const ex = task.expected;
    const needle = normName(ex.measurement);
    const m = [...measurements].reverse().find((x) => normName(x.name).startsWith(needle));
    if (!m) return { ok: false, reason: 'no measurement' };
    const f = unitFactor(m.unit, ex.unit);
    if (f === null) return { ok: false, reason: `unit ${m.unit}` };
    return { ok: Math.abs(m.value * f - ex.value) <= (ex.tolerance_percent / 100) * Math.abs(ex.value), m };
};

/** A 2-D context that records every fillText (the canvas is the main display). */
function recordingContext() {
    const texts = [];
    const gradient = { addColorStop() {} };
    const base = {
        texts,
        fillText: (t) => texts.push(String(t)),
        measureText: (s) => ({ width: String(s).length * 6 }),
        createLinearGradient: () => gradient,
        createRadialGradient: () => gradient,
        getLineDash: () => [],
    };
    return new Proxy(base, {
        get: (t, k) => (k in t ? t[k] : () => {}),
        set: (t, k, v) => { t[k] = v; return true; },
    });
}
async function canvasTexts(sim, draw = true) {
    const rec = recordingContext();
    await sim.set({ ctx: rec });
    if (draw) sim.call('draw');
    return rec.texts.join('\n');
}

async function withSim(name, slug, fn) {
    const e = exp(slug);
    const sim = await mountSimulation(name, { config: e.simulation_config, currentTask: e.tasks[1] });
    try {
        await fn(sim, e);
        expect(sim.errors.map((x) => x.message)).toEqual([]);
        expect(sim.warnings).toEqual([]);
    } finally {
        sim.unmount();
    }
}

// ─────────────────────────────────────────────────────────────────────────────
describe('photoelectric-effect (PhotoelectricSimulation)', () => {
    it('default display: hf = 2.481 eV, KE = 0.481 eV, ν₀ = 484 THz', async () => {
        await withSim('PhotoelectricSimulation', 'photoelectric-effect', async (sim) => {
            const text = await canvasTexts(sim);
            expect(text).toContain(`hf = ${(H * 600e12 / E).toFixed(3)} eV`);
            expect(text).toContain(`KE_max = hf − W = ${(H * 600e12 / E - 2).toFixed(3)} eV`);
            expect(text).toContain(`ν₀ = W/h = ${(2 * E / H / 1e12).toFixed(0)} THz`);
            const v = Math.sqrt(2 * (H * 600e12 / E - 2) * E / ME);
            expect(text).toContain(`v_max = ${(v / 1e3).toFixed(0)} km/s`);
        });
    });

    it('O\'lchash over the slider ranges: U₀ = hf/e − W (3½-digit voltmeter), I = ηeP/(hf); none below threshold', async () => {
        await withSim('PhotoelectricSimulation', 'photoelectric-effect', async (sim) => {
            for (const f of [400, 480, 600, 700, 800]) {
                for (const W of [1, 2, 3.5, 5]) {
                    for (const intensity of [10, 50, 100]) {
                        await sim.set({ frequency: f, metalWorkFunction: W, lightIntensity: intensity });
                        for (let k = 0; k < 3; k++) {
                            sim.measurements.length = 0;
                            sim.call('measure');
                            const u = sim.last('Tormozlovchi kuchlanish U₀');
                            const i = sim.last('Fototok I');
                            expect(u.unit).toBe('V');
                            expect(i.unit).toBe('µA');
                            const ke = (H * f * 1e12) / E - W;
                            if (ke <= 0) {
                                expect(u.value).toBe(0);
                                expect(i.value).toBe(0);
                                continue;
                            }
                            const res = ke < 2 ? 0.001 : 0.01; // 2 V / 20 V range
                            expect(Math.abs(u.value - ke)).toBeLessThanOrEqual(2.5 * 0.003 + res / 2 + 1e-9);
                            const iTrue = 1e-3 * E * ((intensity / 100) * 1e-3) / (H * f * 1e12) * 1e6; // µA
                            expect(Math.abs(i.value - iTrue)).toBeLessThanOrEqual(2.5 * 0.002 * iTrue + 0.0005 + 1e-9);
                        }
                    }
                }
            }
        });
    });

    it('readings are reproducible: the k-th reading of a set-up is the same in a new session', async () => {
        const run = async () => {
            const out = [];
            await withSim('PhotoelectricSimulation', 'photoelectric-effect', async (sim) => {
                await sim.set({ frequency: 700, metalWorkFunction: 2 });
                for (let k = 0; k < 5; k++) sim.call('measure');
                out.push(...sim.measurements.map((m) => m.value));
            });
            return out;
        };
        const a = await run();
        expect(await run()).toEqual(a);
        expect(new Set(a.filter((_, i) => i % 2 === 1)).size).toBeGreaterThan(1); // U₀ scatters
    });

    it('calculation tasks agree with the screen (hf, KE, ν₀) within their tolerance', async () => {
        const e = exp('photoelectric-effect');
        const [, t1, t2, t3, t4] = e.tasks;
        expect(t1.expected.value).toBeCloseTo(H * 600e12 / E, 4);
        expect(t2.expected.value).toBeCloseTo(H * 600e12 / E - 2, 4);
        expect(t3.expected.value).toBeCloseTo(2 * E / H / 1e12, 1);
        expect(t4.expected.value).toBeCloseTo(H * 800e12 / E - 2, 4);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
describe('bohr-atom (BohrModelSimulation)', () => {
    it('levels Eₙ = −13.598/n² eV (shown −13.60, −3.40, −1.51 …) and rₙ = a₀n²', async () => {
        await withSim('BohrModelSimulation', 'bohr-atom', async (sim) => {
            const text = await canvasTexts(sim);
            for (let n = 1; n <= 6; n++) expect(text).toContain(`E = ${(-EH / n / n).toFixed(2)} eV`);
            expect(sim.text()).toContain(`r = ${(0.529177210544 * 9).toFixed(3)} Å`);
        });
    });

    it('every transition: ΔE = Ry_H·|1/n₁² − 1/n₂²|, λ = 1/(R_H·…) (vacuum) shown and emitted', async () => {
        await withSim('BohrModelSimulation', 'bohr-atom', async (sim) => {
            for (let from = 1; from <= 6; from++) {
                for (let to = 1; to <= 6; to++) {
                    if (from === to) continue;
                    await sim.set({ currentLevel: from });
                    sim.call('transition', to);
                    await sim.flush();
                    sim.call('measure');
                    const lo = Math.min(from, to);
                    const hi = Math.max(from, to);
                    const dE = EH * (1 / lo ** 2 - 1 / hi ** 2);
                    const lam = 1e9 / (RH * (1 / lo ** 2 - 1 / hi ** 2));
                    expect(sim.last('Foton energiyasi ΔE').value).toBeCloseTo(dE, 4);
                    expect(sim.last("Foton to'lqin uzunligi λ").value).toBeCloseTo(lam, 2);
                    expect(sim.last(`Sath energiyasi E${to}`).value).toBeCloseTo(-EH / to / to, 4);
                    expect(sim.text()).toContain(`λ = ${lam.toFixed(2)} nm`);
                    expect(sim.text()).toContain(to < from ? 'Nurlangan' : 'Yutilgan');
                }
            }
        });
    });

    it('calculation tasks (E₃, Hα, ΔE₃₂, Lyman α) = the values the simulation shows, within 1 %', async () => {
        const e = exp('bohr-atom');
        const [, tE3, tHa, tE32, tLa] = e.tasks;
        expect(tE3.expected.value).toBeCloseTo(-EH / 9, 4);
        expect(tHa.expected.value).toBeCloseTo(36e9 / (5 * RH), 2);
        expect(tE32.expected.value).toBeCloseTo(EH * (1 / 4 - 1 / 9), 4);
        expect(tLa.expected.value).toBeCloseTo(4e9 / (3 * RH), 2);
        await withSim('BohrModelSimulation', 'bohr-atom', async (sim) => {
            await sim.set({ currentLevel: 3 });
            sim.call('transition', 2);
            await sim.flush();
            sim.call('measure');
            for (const [task, name] of [[tHa, "Foton to'lqin uzunligi λ"], [tE32, 'Foton energiyasi ΔE']]) {
                const v = sim.last(name).value;
                expect(Math.abs(v - task.expected.value)).toBeLessThanOrEqual(task.expected.tolerance_percent / 100 * Math.abs(task.expected.value));
            }
            // a student using the textbook R = 1.097·10⁷ m⁻¹ / 13.6 eV still passes (0.02 %)
            expect(Math.abs(36e9 / (5 * 1.097e7) - tHa.expected.value) / tHa.expected.value).toBeLessThan(0.001);
            expect(Math.abs(13.6 * 5 / 36 - tE32.expected.value) / tE32.expected.value).toBeLessThan(0.001);
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────────
describe('emission-spectra (EmissionSpectraSimulation)', () => {
    // NIST ASD observed wavelengths in air (https://physics.nist.gov/PhysRefData/ASD/lines_form.html)
    const NIST = {
        hydrogen: [410.1734, 434.0472, 486.135, 656.279],
        helium: [447.148, 471.3146, 492.1931, 501.5678, 587.5621, 667.8151],
        sodium: [588.9951, 589.5924],
        mercury: [404.6565, 435.8335, 546.075, 576.961, 579.067],
    };

    it('lines shown = NIST lines (air) for every element; spectroscope reading within 2.5σ + ½ division', async () => {
        await withSim('EmissionSpectraSimulation', 'emission-spectra', async (sim) => {
            for (const [el, lines] of Object.entries(NIST)) {
                await sim.set({ element: el });
                const text = await canvasTexts(sim);
                expect(text).toContain(`Chiziqlar: ${lines.length}`);
                lines.forEach((nm) => expect(text).toContain(`${nm.toFixed(1)}nm`));
                for (let i = 0; i < lines.length; i++) {
                    await sim.set({ lineIndex: i });
                    for (let k = 0; k < 30; k++) {
                        sim.call('measure');
                        const m = sim.last('Spektral chiziq λ');
                        expect(m.unit).toBe('nm');
                        expect(Math.abs(m.value - lines[i])).toBeLessThanOrEqual(maxReadingError(lines[i], SPECTROSCOPE) + 1e-9);
                        expect(Math.abs(m.value * 10 - Math.round(m.value * 10))).toBeLessThan(1e-9); // 0.1 nm scale
                    }
                }
            }
        });
    });

    it('Bohr/Rydberg hydrogen lines (vacuum → air, IAU formula) agree with the NIST lines to 0.012 nm', () => {
        const nAir = (nm) => { const s2 = (1000 / nm) ** 2; return 1 + 0.0000834254 + 0.02406147 / (130 - s2) + 0.00015998 / (38.9 - s2); };
        [6, 5, 4, 3].forEach((n, i) => {
            const vac = 1e9 / (RH * (1 / 4 - 1 / n / n));
            expect(Math.abs(vac / nAir(vac) - NIST.hydrogen[i])).toBeLessThan(0.012);
        });
    });

    it('calculation tasks: Hβ (vacuum) 486.27 nm is within 1 % of the spectroscope reading; Na D₂ and Hg green energies', async () => {
        const e = exp('emission-spectra');
        const [, tHb, tNa, tHg] = e.tasks;
        expect(tHb.expected.value).toBeCloseTo(16e9 / (3 * RH), 2);
        expect(tNa.expected.value).toBeCloseTo(HC_EVNM / 589.0, 3);
        expect(tHg.expected.value).toBeCloseTo(HC_EVNM / 546.1, 4);
        expect(Math.abs(tHb.expected.value - 486.135) / tHb.expected.value).toBeLessThan(0.0004);
        expect(Math.abs(HC_EVNM / 588.9951 - tNa.expected.value) / tNa.expected.value).toBeLessThan(0.01);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
describe('radioactive-decay (RadioactiveDecaySimulation)', () => {
    it('counted N(t) follows N₀·2^(−t/T½) within 4σ (binomial) for a sweep of T½ and N₀; reproducible seed', async () => {
        const results = [];
        for (const [T, N0] of [[10, 1000], [5, 500], [30, 2000], [15, 1250]]) {
            await withSim('RadioactiveDecaySimulation', 'radioactive-decay', async (sim) => {
                await sim.set({ halfLife: T, initialAtoms: N0 });
                sim.call('start');
                for (const tStop of [T, 2 * T]) {
                    await sim.advance(tStop - sim.get('time'));
                    sim.call('measure');
                    const t = sim.last('Vaqt t').value;
                    const N = sim.last('Qolgan yadrolar N').value;
                    const counts = sim.last('Impulslar soni (1 s)');
                    expect(sim.last('Vaqt t').unit).toBe('s');
                    expect(counts.unit).toBe('imp');
                    expect(Math.abs(t - tStop)).toBeLessThan(0.05);
                    const p = 2 ** (-t / T);
                    const sigma = Math.sqrt(N0 * p * (1 - p));
                    expect(Math.abs(N - N0 * p)).toBeLessThanOrEqual(4 * sigma);
                    const mu = N0 * 2 ** (-(t - 1) / T) * (1 - 2 ** (-1 / T));
                    expect(Math.abs(counts.value - mu)).toBeLessThanOrEqual(5 * Math.sqrt(mu) + 1);
                    expect(Number.isInteger(N) && Number.isInteger(counts.value)).toBe(true);
                    results.push(N, counts.value);
                }
                const text = await canvasTexts(sim);
                expect(text).toContain(`λ = ln2/T½ = ${(Math.LN2 / T).toFixed(4)} s⁻¹`);
            });
        }
        // same seed → same nuclei → same counts in a new session
        const again = [];
        await withSim('RadioactiveDecaySimulation', 'radioactive-decay', async (sim) => {
            sim.call('start');
            await sim.advance(10);
            sim.call('measure');
            again.push(sim.last('Qolgan yadrolar N').value);
        });
        expect(again[0]).toBe(results[0]); // run 1 of (10 s, 1000) is reproducible
    }, 60000);

    it('pause stops the clock; reset restores N₀', async () => {
        await withSim('RadioactiveDecaySimulation', 'radioactive-decay', async (sim) => {
            sim.call('start');
            await sim.advance(4);
            sim.call('togglePause');
            const t = sim.get('time');
            await sim.advance(3);
            expect(sim.get('time')).toBe(t);
            sim.call('togglePause');
            await sim.advance(1);
            expect(sim.get('time')).toBeGreaterThan(t + 0.9);
            sim.call('reset');
            await sim.flush();
            sim.call('measure');
            expect(sim.last('Qolgan yadrolar N').value).toBe(1000);
        });
    });

    it('calculation tasks: N(10 s) = 500, N(20 s) = 250, λ = 0.069315 s⁻¹, A₀ = 69.315 Bq', () => {
        const [, a, b, c, d] = exp('radioactive-decay').tasks;
        expect(a.expected.value).toBe(500);
        expect(b.expected.value).toBe(250);
        expect(c.expected.value).toBeCloseTo(Math.LN2 / 10, 6);
        expect(d.expected.value).toBeCloseTo(1000 * Math.LN2 / 10, 3);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
describe('nuclear-fission (NuclearFissionSimulation)', () => {
    it('one fission after the neutron arrives; Δm (AME2020) = 0.18602 u → 173.3 MeV shown', async () => {
        await withSim('NuclearFissionSimulation', 'nuclear-fission', async (sim) => {
            sim.call('startFission');
            await sim.advance(4);
            expect(sim.get('fissionCount')).toBe(1);
            const text = await canvasTexts(sim);
            const mn = 1.00866491606;
            const dm = 235.043928117 + mn - 140.914403653 - 91.926173092 - 3 * mn;
            expect(text).toContain(`Δm = ${dm.toFixed(4)} u → ΔE = Δm·c² = ${(dm * 931.49410372).toFixed(1)} MeV`);
            expect(text).toContain('Energiya: ≈ 200 MeV');
            // 1 g: (1/235)·N_A·200 MeV
            expect(text).toContain(`${((1 / 235) * 6.02214076e23 * 200e6 * E / 1e10).toFixed(1)}·10¹⁰ J`);
        });
    });

    it('fragment directions are seeded: the same run looks the same in a new session', async () => {
        const run = async () => {
            let ev;
            await withSim('NuclearFissionSimulation', 'nuclear-fission', async (sim) => {
                sim.call('startFission');
                await sim.advance(0.1);
                ev = JSON.stringify(sim.get('event'));
            });
            return ev;
        };
        expect(await run()).toBe(await run());
    });

    it('calculation tasks: 0.186 u → 173.26 MeV, 1 g → 8.2115·10¹⁰ J', () => {
        const [, a, b] = exp('nuclear-fission').tasks;
        expect(a.expected.value).toBeCloseTo(0.186 * 931.49410372, 2);
        expect(Math.abs(b.expected.value - (1 / 235) * 6.02214076e23 * 200e6 * E) / b.expected.value).toBeLessThan(1e-4);
    });
});

// ─────────────────────────────────────────────────────────────────────────────
describe('xray-diffraction (XRayDiffractionSimulation)', () => {
    // d = a/2 (rock salt, (200)) or a/√3 (diamond cubic, (111)), lattice constants in nm
    const D = { nacl: 0.56402 / 2, lif: 0.4027 / 2, kcl: 0.62931 / 2, diamond: 0.356679 / Math.sqrt(3), silicon: 0.5431020511 / Math.sqrt(3) };

    it('goniometer readings θₙ = asin(nλ/2d) within 0.1° (0.1° steps) for all crystals and a λ sweep', async () => {
        await withSim('XRayDiffractionSimulation', 'xray-diffraction', async (sim) => {
            for (const [cr, d] of Object.entries(D)) {
                for (const lam of [0.05, 0.0711, 0.1, 0.154, 0.2, 0.25, 0.3]) {
                    await sim.set({ crystalType: cr, wavelength: lam });
                    sim.measurements.length = 0;
                    sim.call('measure');
                    const nMax = Math.floor(2 * d / lam + 1e-12);
                    expect(sim.measurements.length).toBe(Math.min(3, nMax));
                    for (let n = 1; n <= Math.min(3, nMax); n++) {
                        const m = sim.last(`Bregg burchagi θ${'₁₂₃'[n - 1]}`);
                        const theta = Math.asin(n * lam / (2 * d)) * DEG;
                        expect(m.unit).toBe('°');
                        expect(Math.abs(m.value - theta)).toBeLessThanOrEqual(maxReadingError(theta, XRAY_GONIOMETER) + 1e-9);
                        expect(Math.abs(m.value * 10 - Math.round(m.value * 10))).toBeLessThan(1e-9);
                    }
                    const text = await canvasTexts(sim);
                    if (nMax >= 1) expect(text).toContain(`θ₁ = ${(Math.asin(lam / (2 * d)) * DEG).toFixed(2)}°`);
                    else expect(text).toContain('θ₁ = — (λ > 2d)');
                }
            }
        });
    });

    it('calculation tasks agree with the simulated NaCl / Si angles within their 1 % tolerance', async () => {
        const [, t1, t2, tSi, tD] = exp('xray-diffraction').tasks;
        expect(t1.expected.value).toBeCloseTo(Math.asin(0.154 / 0.564) * DEG, 3);
        expect(t2.expected.value).toBeCloseTo(Math.asin(0.154 / 0.282) * DEG, 1);
        expect(tSi.expected.value).toBeCloseTo(Math.asin(0.154 / 0.628) * DEG, 3);
        expect(tD.expected.value).toBeCloseTo(0.154 / (2 * Math.sin(15.85 / DEG)), 5);
        await withSim('XRayDiffractionSimulation', 'xray-diffraction', async (sim) => {
            for (const [cr, task] of [['nacl', t1], ['nacl', t2], ['silicon', tSi]]) {
                await sim.set({ crystalType: cr, wavelength: 0.154 });
                const n = task.expected.check.inputs.n;
                const theta = (n === 1 ? sim.get('firstOrderAngle') : sim.get('secondOrderAngle'));
                expect(Math.abs(theta - task.expected.value)).toBeLessThanOrEqual(task.expected.tolerance_percent / 100 * task.expected.value);
            }
        });
    });
});

// ─────────────────────────────────────────────────────────────────────────────
describe('wave-particle-duality (DoubleSlitSimulation)', () => {
    const L = 1;
    const a = 0.02e-3;
    const SETTING = { ...INSTRUMENTS.screenMicrometer, sigmaAbs: 0.04 };
    /** Independent: number of spacings between symmetric dark fringes used by the micrometer. */
    const spacingsFor = (nm, dmm) => {
        const lam = nm * 1e-9;
        const d = dmm * 1e-3;
        const limit = Math.min(19, 0.95 * lam * L / a * 1e3);
        const dark = (k) => L * Math.tan(Math.asin((k + 0.5) * lam / d)) * 1e3;
        let k = 0;
        while (dark(k + 1) <= limit) k++;
        return { N: 2 * k + 1, span: 2 * dark(k) };
    };
    const maxErrDy = (N) => maxReadingError(0, SETTING) / N + 0.0005; // + rounding to 0.001 mm

    it('every seeded measurement task: name/unit accepted by the server, 30 readings within tolerance, max error ≤ ½ tolerance', async () => {
        const e = exp('wave-particle-duality');
        const tasks = e.tasks.filter((t) => t.type === 'measurement');
        expect(tasks.length).toBe(3);
        await withSim('DoubleSlitSimulation', 'wave-particle-duality', async (sim) => {
            for (const task of tasks) {
                const { 'λ': nm, d: dmm, L: Lin } = task.expected.check.inputs;
                expect(Lin).toBe(1);
                const exact = (nm * 1e-9 * L) / (dmm * 1e-3) * 1e3; // mm
                expect(task.expected.value).toBeCloseTo(exact, 9);
                const { N } = spacingsFor(nm, dmm);
                expect(maxErrDy(N)).toBeLessThanOrEqual(0.5 * task.expected.tolerance_percent / 100 * task.expected.value);
                await sim.set({ wavelength: nm, slitSeparation: dmm });
                for (let k = 0; k < 30; k++) {
                    sim.call('measure');
                    const v = serverCheck(task, sim.measurements);
                    expect(v.ok, `${task.title}: ${v.reason ?? v.m?.value}`).toBe(true);
                    expect(v.m.unit).toBe('mm');
                    expect(Math.abs(v.m.value - exact)).toBeLessThanOrEqual(maxErrDy(N) + 2e-4 * exact);
                }
            }
        });
    });

    it('slider sweep: Δy = span/N between dark fringes, within the micrometer error of λL/d', async () => {
        await withSim('DoubleSlitSimulation', 'wave-particle-duality', async (sim) => {
            for (const nm of [400, 475, 550, 625, 700]) {
                for (const dmm of [0.05, 0.1, 0.15, 0.2, 0.3]) {
                    await sim.set({ wavelength: nm, slitSeparation: dmm });
                    sim.call('measure');
                    const { N, span } = spacingsFor(nm, dmm);
                    const spanM = sim.last("O'lchangan masofa");
                    expect(spanM.name).toBe(`O'lchangan masofa (${N} oraliq)`);
                    expect(Math.abs(spanM.value - span)).toBeLessThanOrEqual(maxReadingError(span, SETTING) + 1e-9);
                    const dy = sim.last("Polosalar oralig'i Δy").value;
                    const exact = (nm * 1e-9 * L) / (dmm * 1e-3) * 1e3;
                    expect(Math.abs(dy - exact)).toBeLessThanOrEqual(maxErrDy(N) + 2e-4 * exact);
                    const text = await canvasTexts(sim);
                    expect(text).toContain(`Δy = λL/d = ${exact.toFixed(2)} mm`);
                }
            }
        });
    });

    it('photon energy task: E = hc/λ = 2.2543 eV at 550 nm', () => {
        const t = exp('wave-particle-duality').tasks[4];
        expect(t.expected.value).toBeCloseTo(HC_EVNM / 550, 4);
    });
});
