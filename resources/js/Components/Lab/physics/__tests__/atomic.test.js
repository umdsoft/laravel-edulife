import { describe, it, expect } from 'vitest';
import {
    PLANCK_EV,
    HC_EV_NM,
    frequencyToWavelength,
    wavelengthToFrequency,
    photonEnergyEV,
    energyToWavelengthNm,
    spectralRegion,
    wavelengthToRGB,
    WORK_FUNCTIONS_EV,
    nearestMetal,
    speedFromKineticEnergy,
    photoelectric,
    photonRate,
    photocurrent,
    bohrEnergyEV,
    bohrRadius,
    bohrSpeed,
    rydbergWavelength,
    seriesName,
    transitionPhoton,
    balmerLines,
    EMISSION_LINES_NM,
    decayConstant,
    meanLifetime,
    remainingNuclei,
    decayedNuclei,
    activity,
    sampleDecayTime,
    massDefect,
    massDefectEnergyMeV,
    u235FissionQ,
    fissionFragmentKinetics,
    ATOMIC_MASS_U,
    chainReactionPopulation,
    criticality,
    fissionEnergyForMass,
    CRYSTAL_SPACINGS,
    braggAngle,
    maxBraggOrder,
    braggOrders,
    braggWavelength,
    braggSpacing,
} from '../atomic.js';

const deg = (r) => (r * 180) / Math.PI;
const rel = (a, b) => Math.abs(a - b) / Math.abs(b);

describe('photons', () => {
    it('constants: h = 4.1357·10⁻¹⁵ eV·s, hc = 1239.84 eV·nm', () => {
        expect(PLANCK_EV).toBeCloseTo(4.135667696e-15, 23);
        expect(HC_EV_NM).toBeCloseTo(1239.84198, 4);
    });
    it('600 THz: λ = c/f = 499.65 nm, E = hf = 2.4814 eV', () => {
        expect(frequencyToWavelength(600e12) * 1e9).toBeCloseTo(499.654, 3);
        expect(rel(wavelengthToFrequency(frequencyToWavelength(600e12)), 600e12)).toBeLessThan(1e-12);
        expect(photonEnergyEV(600e12)).toBeCloseTo(2.481401, 5);
        expect(energyToWavelengthNm(2.481401)).toBeCloseTo(499.654, 2);
    });
    it('invalid inputs', () => {
        expect(frequencyToWavelength(0)).toBeNaN();
        expect(energyToWavelengthNm(0)).toBeNaN();
        expect(photonEnergyEV(-1)).toBeNaN();
    });
    it('spectral regions', () => {
        expect(spectralRegion(121.5)).toBe('UV');
        expect(spectralRegion(656.3)).toBe('visible');
        expect(spectralRegion(1875)).toBe('IR');
        expect(spectralRegion(-1)).toBeNull();
    });
    it('wavelength → colour', () => {
        expect(wavelengthToRGB(700)).toBe('#ff0000');
        expect(wavelengthToRGB(510)).toBe('#00ff00');
        expect(wavelengthToRGB(440)).toBe('#0000ff');
        expect(wavelengthToRGB(300)).toBeNull();
        expect(wavelengthToRGB(800)).toBeNull();
        expect(wavelengthToRGB(589)).toMatch(/^#[0-9a-f]{6}$/);
    });
});

describe('photoelectric effect', () => {
    it('600 THz on φ = 2 eV: KEmax = hf − φ = 0.4814 eV, V0 = 0.4814 V, v = 4.115·10⁵ m/s', () => {
        const pe = photoelectric({ frequency: 600e12, workFunctionEV: 2 });
        expect(pe.emits).toBe(true);
        expect(pe.kineticEnergyMaxEV).toBeCloseTo(0.481401, 5);
        expect(pe.stoppingVoltage).toBeCloseTo(0.481401, 5); // V0 = KE/e
        // v = √(2·0.4814·1.602·10⁻¹⁹ / 9.109·10⁻³¹)
        expect(rel(pe.maxSpeed, 4.1151e5)).toBeLessThan(1e-3);
        expect(speedFromKineticEnergy(pe.kineticEnergyMaxEV * 1.602176634e-19)).toBeCloseTo(pe.maxSpeed, 6);
    });
    it('threshold: ν0 = φ/h = 483.6 THz, λ0 = hc/φ = 619.9 nm for φ = 2 eV', () => {
        const pe = photoelectric({ frequency: 600e12, workFunctionEV: 2 });
        expect(pe.thresholdFrequency / 1e12).toBeCloseTo(483.597, 2);
        expect(pe.thresholdWavelength * 1e9).toBeCloseTo(619.92, 2);
    });
    it('no emission below threshold regardless of intensity', () => {
        const pe = photoelectric({ frequency: 400e12, workFunctionEV: 2 }); // 1.654 eV < 2 eV
        expect(pe.emits).toBe(false);
        expect(pe.kineticEnergyMaxEV).toBe(0);
        expect(pe.stoppingVoltage).toBe(0);
        expect(photocurrent({ power: 1, frequency: 400e12, workFunctionEV: 2 })).toBe(0);
        expect(photocurrent({ power: 1e3, frequency: 400e12, workFunctionEV: 2 })).toBe(0);
    });
    it('exactly at threshold (hf = φ) there is no emission', () => {
        const f = 500e12;
        const pe = photoelectric({ frequency: f, workFunctionEV: photonEnergyEV(f) });
        expect(pe.emits).toBe(false);
        expect(pe.kineticEnergyMaxEV).toBe(0);
    });
    it('KEmax rises linearly with f with slope h', () => {
        const a = photoelectric({ frequency: 700e12, workFunctionEV: 2 }).kineticEnergyMaxEV;
        const b = photoelectric({ frequency: 800e12, workFunctionEV: 2 }).kineticEnergyMaxEV;
        expect((b - a) / 100e12).toBeCloseTo(PLANCK_EV, 22);
    });
    it('photocurrent ∝ intensity: I = η e P/(hf) = 2.015·10⁻⁷ A (P = 0.5 mW, 600 THz, η = 10⁻³)', () => {
        const i1 = photocurrent({ power: 0.5e-3, frequency: 600e12, workFunctionEV: 2, quantumEfficiency: 1e-3 });
        const i2 = photocurrent({ power: 1e-3, frequency: 600e12, workFunctionEV: 2, quantumEfficiency: 1e-3 });
        expect(i1).toBeCloseTo(2.01498e-7, 11);
        expect(i2 / i1).toBeCloseTo(2, 12);
        expect(photonRate(0.5e-3, 600e12)).toBeCloseTo(1.25766e15, -11);
        expect(photonRate(1, 0)).toBeNaN();
    });
    it('realistic work functions (eV) and nearest-metal labels', () => {
        expect(WORK_FUNCTIONS_EV.Cs).toBeLessThan(WORK_FUNCTIONS_EV.Na);
        expect(WORK_FUNCTIONS_EV.Na).toBeLessThan(WORK_FUNCTIONS_EV.Zn);
        expect(WORK_FUNCTIONS_EV.Zn).toBeLessThan(WORK_FUNCTIONS_EV.Pt);
        expect(nearestMetal(4.65)).toBe('Cu');
        expect(nearestMetal(2.3)).toBe('K');
        expect(nearestMetal(1.0)).toBeNull();
    });
    it('invalid work function', () => {
        const pe = photoelectric({ frequency: 600e12, workFunctionEV: 0 });
        expect(pe.emits).toBe(false);
        expect(pe.thresholdFrequency).toBeNaN();
    });
});

describe('Bohr model', () => {
    it('Eₙ = −13.6 Z²/n² eV', () => {
        expect(bohrEnergyEV(1)).toBeCloseTo(-13.6, 12);
        expect(bohrEnergyEV(2)).toBeCloseTo(-3.4, 12);
        expect(bohrEnergyEV(3)).toBeCloseTo(-1.51111, 5);
        expect(bohrEnergyEV(1, 2)).toBeCloseTo(-54.4, 12); // He⁺
    });
    it('rₙ = a0 n²/Z (a0 = 0.529 Å)', () => {
        expect(bohrRadius(1) * 1e10).toBeCloseTo(0.529177, 6);
        expect(bohrRadius(3) / bohrRadius(1)).toBeCloseTo(9, 12);
        expect(bohrRadius(1, 2)).toBeCloseTo(bohrRadius(1) / 2, 20);
    });
    it('vₙ = αc/n = 2.188·10⁶ m/s for n = 1', () => {
        expect(bohrSpeed(1)).toBeCloseTo(2.18769e6, -1);
        expect(bohrSpeed(2) / bohrSpeed(1)).toBeCloseTo(0.5, 12);
    });
    it('invalid n (0, non-integer, negative) → NaN', () => {
        expect(bohrEnergyEV(0)).toBeNaN();
        expect(bohrEnergyEV(1.5)).toBeNaN();
        expect(bohrRadius(-1)).toBeNaN();
    });
    it('Rydberg: Hα (3→2) = 36/(5R) = 656.33 nm, Lyman α = 4/(3R) = 121.54 nm, Paschen α = 144/(7R) = 1875.2 nm', () => {
        expect(rydbergWavelength(2, 3) * 1e9).toBeCloseTo(656.335, 2);
        expect(rydbergWavelength(1, 2) * 1e9).toBeCloseTo(121.543, 2);
        expect(rydbergWavelength(3, 4) * 1e9).toBeCloseTo(1875.24, 1);
        // Balmer series limit 4/R = 364.6 nm
        expect(rydbergWavelength(2, 10000) * 1e9).toBeCloseTo(364.63, 1);
        // He⁺: λ/Z²
        expect(rydbergWavelength(2, 3, 2) * 1e9).toBeCloseTo(656.335 / 4, 2);
    });
    it('Rydberg edge cases: n1 ≥ n2 → NaN', () => {
        expect(rydbergWavelength(3, 3)).toBeNaN();
        expect(rydbergWavelength(3, 2)).toBeNaN();
        expect(rydbergWavelength(0, 2)).toBeNaN();
    });
    it('energy and wavelength routes agree: hc/ΔE ≈ Rydberg λ (within 0.05 %)', () => {
        const dE = bohrEnergyEV(3) - bohrEnergyEV(2); // 1.8889 eV
        expect(dE).toBeCloseTo(1.888889, 5);
        expect(rel(energyToWavelengthNm(dE), rydbergWavelength(2, 3) * 1e9)).toBeLessThan(5e-4);
    });
    it('transitionPhoton: emission/absorption, series and region', () => {
        const em = transitionPhoton(3, 2);
        expect(em.type).toBe('emission');
        expect(em.energyEV).toBeCloseTo(1.888889, 5);
        expect(em.wavelengthNm).toBeCloseTo(656.335, 2);
        expect(em.series).toBe('Balmer');
        expect(em.region).toBe('visible');
        const ab = transitionPhoton(2, 3);
        expect(ab.type).toBe('absorption');
        expect(ab.energyEV).toBeCloseTo(em.energyEV, 12);
        expect(transitionPhoton(5, 1).series).toBe('Lyman');
        expect(transitionPhoton(5, 1).region).toBe('UV');
        expect(transitionPhoton(4, 3).series).toBe('Paschen');
        expect(transitionPhoton(4, 3).region).toBe('IR');
        expect(transitionPhoton(2, 2)).toBeNull();
        expect(transitionPhoton(0, 2)).toBeNull();
        expect(seriesName(4)).toBe('Brackett');
        expect(seriesName(5)).toBe('Pfund');
    });
    it('Balmer lines from Rydberg match NIST (air) within 0.2 nm: 656.28, 486.13, 434.05, 410.17', () => {
        const lines = balmerLines(6);
        expect(lines.map(l => l.name)).toEqual(['Hα', 'Hβ', 'Hγ', 'Hδ']);
        const nist = [656.28, 486.13, 434.05, 410.17];
        lines.forEach((l, i) => expect(Math.abs(l.wavelengthNm - nist[i])).toBeLessThan(0.2));
        expect(lines[1].wavelengthNm).toBeCloseTo(486.17, 2); // 16/(3R)
    });
});

describe('reference emission lines', () => {
    it('sodium D lines: D1 = 589.6 nm, D2 = 589.0 nm (D2 is the shorter wavelength)', () => {
        const d1 = EMISSION_LINES_NM.sodium.find(l => l.name === 'D1');
        const d2 = EMISSION_LINES_NM.sodium.find(l => l.name === 'D2');
        expect(d1.wavelength).toBeCloseTo(589.6, 1);
        expect(d2.wavelength).toBeCloseTo(589.0, 1);
        expect(d2.wavelength).toBeLessThan(d1.wavelength);
    });
    it('helium D3 = 587.6 nm, mercury green = 546.1 nm; all lines visible', () => {
        expect(EMISSION_LINES_NM.helium.find(l => l.name === 'D3').wavelength).toBe(587.6);
        expect(EMISSION_LINES_NM.mercury.some(l => l.wavelength === 546.1)).toBe(true);
        Object.values(EMISSION_LINES_NM).flat().forEach(l => expect(spectralRegion(l.wavelength)).toBe('visible'));
    });
});

describe('radioactive decay', () => {
    it('λ = ln2/T½: T½ = 10 s → 0.0693 s⁻¹; τ = 14.43 s', () => {
        expect(decayConstant(10)).toBeCloseTo(0.0693147, 7);
        expect(meanLifetime(10)).toBeCloseTo(14.4270, 4);
    });
    it('N(t) = N0 (1/2)^(t/T½): 1000 → 500 → 250', () => {
        expect(remainingNuclei(1000, 0, 10)).toBe(1000);
        expect(remainingNuclei(1000, 10, 10)).toBeCloseTo(500, 9);
        expect(remainingNuclei(1000, 20, 10)).toBeCloseTo(250, 9);
        expect(decayedNuclei(1000, 10, 10)).toBeCloseTo(500, 9);
    });
    it('e^(−λt) and (1/2)^(t/T½) forms agree', () => {
        for (const t of [0.3, 7.7, 33]) {
            expect(remainingNuclei(2000, t, 12)).toBeCloseTo(2000 * Math.pow(0.5, t / 12), 9);
        }
    });
    it('activity A = λN: 69.31 Bq at t = 0, half after one half-life', () => {
        expect(activity(1000, 0, 10)).toBeCloseTo(69.3147, 4);
        expect(activity(1000, 10, 10)).toBeCloseTo(34.6574, 4);
    });
    it('edge cases', () => {
        expect(decayConstant(0)).toBeNaN();
        expect(remainingNuclei(1000, -1, 10)).toBeNaN();
        expect(remainingNuclei(1000, 5, 0)).toBeNaN();
    });
    it('sampleDecayTime: median (u = 0.5) is exactly T½; u = 1 → 0; invalid → NaN', () => {
        expect(sampleDecayTime(0.5, decayConstant(10))).toBeCloseTo(10, 12);
        expect(sampleDecayTime(1, 0.1)).toBeCloseTo(0, 15);
        expect(sampleDecayTime(0, 0.1)).toBeNaN();
        expect(sampleDecayTime(0.5, 0)).toBeNaN();
    });
    it('per-nucleus sampling converges to N0·e^(−λt) (200 000 nuclei, seeded PRNG)', () => {
        let s = 12345;
        const rnd = () => { // mulberry32
            s |= 0; s = (s + 0x6D2B79F5) | 0;
            let t = Math.imul(s ^ (s >>> 15), 1 | s);
            t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
            return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
        };
        const N0 = 200000;
        const T = 10;
        const lam = decayConstant(T);
        const taus = Array.from({ length: N0 }, () => sampleDecayTime(1 - rnd(), lam));
        for (const t of [5, 10, 20, 30]) {
            const left = taus.filter(x => x > t).length;
            expect(rel(left, remainingNuclei(N0, t, T))).toBeLessThan(0.02);
        }
    });
});

describe('nuclear fission', () => {
    it('1 u = 931.494 MeV', () => {
        expect(massDefectEnergyMeV(1)).toBeCloseTo(931.494, 3);
        expect(massDefect([2, 1], [2.5])).toBeCloseTo(0.5, 12);
    });
    it('²³⁵U + n → ¹⁴¹Ba + ⁹²Kr + 3n: Δm = 0.18602 u → 173.3 MeV', () => {
        // 235.0439281 + 1.0086649 − 140.9144033 − 91.9261731 − 3·1.0086649 = 0.1860219 u
        const q = u235FissionQ();
        expect(q.deltaMassU).toBeCloseTo(0.1860219, 6);
        expect(q.energyMeV).toBeCloseTo(173.28, 1);
        // total ≈ 200 MeV with the subsequent β-decays: prompt Q is 80–90 % of it
        expect(q.energyMeV / 200).toBeGreaterThan(0.8);
        expect(q.energyMeV / 200).toBeLessThan(0.9);
    });
    it('fragments: equal and opposite momenta ⇒ KE_Ba/KE_Kr = m_Kr/m_Ba, light fragment faster', () => {
        const q = u235FissionQ().energyMeV;
        const k = fissionFragmentKinetics({ qMeV: q, massA: ATOMIC_MASS_U.Ba141, massB: ATOMIC_MASS_U.Kr92 });
        expect(k.kineticA + k.kineticB).toBeCloseTo(q - 6, 10);
        expect(k.kineticA / k.kineticB).toBeCloseTo(ATOMIC_MASS_U.Kr92 / ATOMIC_MASS_U.Ba141, 10);
        expect(ATOMIC_MASS_U.Ba141 * k.betaA).toBeCloseTo(ATOMIC_MASS_U.Kr92 * k.betaB, 10);
        expect(k.betaB).toBeGreaterThan(k.betaA);
        expect(k.kineticB).toBeCloseTo(101.2, 0); // ≈ 100 MeV light fragment
        expect(k.betaNeutron).toBeCloseTo(0.0652, 3); // 2 MeV neutron ≈ 0.065 c
    });
    it('chain reaction N_g = N0·kᵍ and criticality', () => {
        expect(chainReactionPopulation(1, 2, 10)).toBe(1024);
        expect(chainReactionPopulation(5, 1, 50)).toBe(5);
        expect(chainReactionPopulation(1, 0.9, 10)).toBeCloseTo(0.348678, 6);
        expect(chainReactionPopulation(1, -1, 2)).toBeNaN();
        expect(criticality(1)).toBe('critical');
        expect(criticality(0.95)).toBe('subcritical');
        expect(criticality(1.05)).toBe('supercritical');
        expect(criticality(-1)).toBeNull();
    });
    it('1 kg of U-235 at 200 MeV/fission ≈ 8.21·10¹³ J', () => {
        expect(rel(fissionEnergyForMass(1), 8.2115e13)).toBeLessThan(1e-3);
        expect(fissionEnergyForMass(-1)).toBeNaN();
    });
});

describe('Bragg diffraction', () => {
    const lambda = 0.154e-9; // Cu Kα
    it('reference spacings: NaCl(200) 0.282 nm, diamond(111) 0.2059 nm, Si(111) 0.3136 nm', () => {
        expect(CRYSTAL_SPACINGS.nacl.d * 1e9).toBeCloseTo(0.282, 4);
        expect(CRYSTAL_SPACINGS.diamond.d * 1e9).toBeCloseTo(0.20593, 4);
        expect(CRYSTAL_SPACINGS.silicon.d * 1e9).toBeCloseTo(0.31356, 4);
    });
    it('Cu Kα glancing angles: NaCl 15.85° (2θ 31.7°), Si 14.21° (2θ 28.4°), diamond 21.96° (2θ 43.9°)', () => {
        expect(deg(braggAngle(1, lambda, CRYSTAL_SPACINGS.nacl.d))).toBeCloseTo(15.846, 2);
        expect(deg(braggAngle(1, lambda, CRYSTAL_SPACINGS.silicon.d))).toBeCloseTo(14.216, 2);
        expect(deg(braggAngle(1, lambda, CRYSTAL_SPACINGS.diamond.d))).toBeCloseTo(21.958, 2);
    });
    it('second order sin θ₂ = 2 sin θ₁', () => {
        const t1 = braggAngle(1, lambda, 0.282e-9);
        const t2 = braggAngle(2, lambda, 0.282e-9);
        expect(Math.sin(t2)).toBeCloseTo(2 * Math.sin(t1), 12);
    });
    it('orders with nλ > 2d do not exist (sin θ > 1 → null)', () => {
        expect(braggAngle(3, 0.2e-9, 0.282e-9)).toBeNull();
        expect(braggAngle(1, 0.6e-9, 0.282e-9)).toBeNull();
        expect(braggAngle(0, lambda, 0.282e-9)).toBeNull();
        expect(braggAngle(1, 0, 0.282e-9)).toBeNull();
    });
    it('n_max = ⌊2d/λ⌋ and θ = 90° at the limit', () => {
        expect(maxBraggOrder(lambda, 0.282e-9)).toBe(3);
        const list = braggOrders(lambda, 0.282e-9);
        expect(list.map(o => o.n)).toEqual([1, 2, 3]);
        list.forEach(o => expect(o.twoTheta).toBeCloseTo(2 * o.theta, 12));
        expect(deg(braggAngle(1, 0.282e-9, 0.141e-9))).toBeCloseTo(90, 9);
        expect(maxBraggOrder(0, 1)).toBe(0);
    });
    it('inverse relations λ = 2d sinθ/n and d = nλ/(2 sinθ)', () => {
        const th = braggAngle(2, lambda, 0.3e-9);
        expect(braggWavelength(2, 0.3e-9, th)).toBeCloseTo(lambda, 20);
        expect(braggSpacing(2, lambda, th)).toBeCloseTo(0.3e-9, 20);
        expect(braggSpacing(1, lambda, 0)).toBeNaN();
    });
});
