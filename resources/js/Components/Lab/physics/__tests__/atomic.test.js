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
    RYDBERG,
    RYDBERG_SCHOOL,
    RYDBERG_ENERGY_EV,
    RYDBERG_ENERGY_SCHOOL_EV,
    rydbergConstant,
    rydbergEnergyEV,
    airRefractiveIndex,
    vacuumToAirNm,
    airToVacuumNm,
    SPECTROSCOPE,
    HALF_LIVES_S,
    countsInWindow,
    TYPICAL_FISSION_ENERGY_MEV,
    FISSION_ENERGY_U235_MEV,
    NEUTRONS_PER_FISSION_U235,
    LATTICE_CONSTANTS,
    XRAY_LINES,
    XRAY_GONIOMETER,
    AMU_MEV,
    ELECTRON_MASS,
    BOHR_RADIUS,
    FINE_STRUCTURE,
} from '../atomic.js';
import * as C from '../constants.js';
import { maxReadingError, createRng } from '../instruments.js';

describe('constants come from constants.js (CODATA 2022)', () => {
    it('re-exported values', () => {
        expect(ELECTRON_MASS).toBe(C.ELECTRON_MASS);
        expect(ELECTRON_MASS).toBe(9.1093837139e-31);
        expect(BOHR_RADIUS).toBe(5.29177210544e-11);
        expect(FINE_STRUCTURE).toBe(7.2973525643e-3);
        expect(AMU_MEV).toBe(931.49410372);
        expect(RYDBERG).toBe(C.RYDBERG_HYDROGEN);
    });
});

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
    it('work functions = Michaelson 1977 recommended polycrystalline values (eV)', () => {
        expect(WORK_FUNCTIONS_EV).toEqual({
            Cs: 2.14, K: 2.30, Na: 2.75, Ca: 2.87, Mg: 3.66, Al: 4.28, Zn: 4.33, Ag: 4.26, Cu: 4.65, Pt: 5.65,
        });
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

// Independent CODATA 2022 values (NIST, https://physics.nist.gov/cuu/Constants/Table/allascii.txt)
const R_INF = 10973731.568157; // m⁻¹
const RY_EV = 13.60569312299; // eV
const MP_ME = 1836.152673426;
const R_H = R_INF / (1 + 1 / MP_ME); // 1.096 775 834·10⁷ m⁻¹
const E_H = RY_EV / (1 + 1 / MP_ME); // 13.598 287 eV

describe('Bohr model', () => {
    it('constants: R = R_H = 1.0967758·10⁷ m⁻¹, Ry = 13.5983 eV (school 1.097·10⁷, 13.6)', () => {
        expect(RYDBERG).toBeCloseTo(10967758.34, 1);
        expect(rel(RYDBERG, R_H)).toBeLessThan(1e-12);
        expect(RYDBERG_ENERGY_EV).toBeCloseTo(13.598287, 6);
        expect(RYDBERG_SCHOOL).toBe(1.097e7);
        expect(RYDBERG_ENERGY_SCHOOL_EV).toBe(13.6);
        // the school values differ by < 0.03 %, far below the 1 % task tolerance
        expect(rel(RYDBERG_SCHOOL, RYDBERG)).toBeLessThan(3e-4);
        expect(rel(RYDBERG_ENERGY_SCHOOL_EV, RYDBERG_ENERGY_EV)).toBeLessThan(2e-4);
        expect(rydbergConstant(2)).toBe(R_INF);
        expect(rydbergEnergyEV(2)).toBeCloseTo(RY_EV, 10);
    });
    it('Eₙ = −Ry·Z²/n²: −13.598, −3.3996, −1.5109 eV; He⁺ uses R∞ (−54.42 eV)', () => {
        expect(bohrEnergyEV(1)).toBeCloseTo(-E_H, 12);
        expect(bohrEnergyEV(2)).toBeCloseTo(-E_H / 4, 12);
        expect(bohrEnergyEV(3)).toBeCloseTo(-1.510921, 6);
        expect(bohrEnergyEV(1).toFixed(2)).toBe('-13.60'); // the display still reads the textbook value
        expect(bohrEnergyEV(2).toFixed(2)).toBe('-3.40');
        expect(bohrEnergyEV(1, 2)).toBeCloseTo(-4 * RY_EV, 10);
    });
    it('rₙ = a0 n²/Z (a0 = 0.529 177 210 544 Å, CODATA 2022)', () => {
        expect(bohrRadius(1) * 1e10).toBeCloseTo(0.529177210544, 11);
        expect(bohrRadius(3) / bohrRadius(1)).toBeCloseTo(9, 12);
        expect(bohrRadius(1, 2)).toBeCloseTo(bohrRadius(1) / 2, 20);
    });
    it('vₙ = αc/n = 2.188·10⁶ m/s for n = 1', () => {
        expect(bohrSpeed(1)).toBeCloseTo(7.2973525643e-3 * 299792458, 3);
        expect(bohrSpeed(2) / bohrSpeed(1)).toBeCloseTo(0.5, 12);
    });
    it('invalid n (0, non-integer, negative) → NaN', () => {
        expect(bohrEnergyEV(0)).toBeNaN();
        expect(bohrEnergyEV(1.5)).toBeNaN();
        expect(bohrRadius(-1)).toBeNaN();
    });
    it('Rydberg (vacuum): Hα = 36/(5R_H) = 656.470 nm, Lyman α = 4/(3R_H) = 121.568 nm, Paschen α = 1875.63 nm', () => {
        expect(rydbergWavelength(2, 3) * 1e9).toBeCloseTo(36e9 / (5 * R_H), 9);
        expect(rydbergWavelength(2, 3) * 1e9).toBeCloseTo(656.4696, 3);
        expect(rydbergWavelength(1, 2) * 1e9).toBeCloseTo(121.5684, 3);
        expect(rydbergWavelength(3, 4) * 1e9).toBeCloseTo(1875.627, 2);
        // NIST ASD Lyman α (vacuum) 121.567 nm
        expect(Math.abs(rydbergWavelength(1, 2) * 1e9 - 121.567)).toBeLessThan(0.005);
        // Balmer series limit 4/R_H = 364.70 nm
        expect(rydbergWavelength(2, 100000) * 1e9).toBeCloseTo(4e9 / R_H, 3);
        // He⁺ (Z = 2, R∞): λ = 36/(5·4·R∞)
        expect(rydbergWavelength(2, 3, 2) * 1e9).toBeCloseTo(36e9 / (20 * R_INF), 9);
        // school constant gives 656.34 nm: within 0.03 %
        expect(rel(36e9 / (5 * RYDBERG_SCHOOL), rydbergWavelength(2, 3) * 1e9)).toBeLessThan(3e-4);
    });
    it('Rydberg edge cases: n1 ≥ n2 → NaN', () => {
        expect(rydbergWavelength(3, 3)).toBeNaN();
        expect(rydbergWavelength(3, 2)).toBeNaN();
        expect(rydbergWavelength(0, 2)).toBeNaN();
    });
    it('energy and wavelength routes agree exactly: hc/ΔE = Rydberg λ', () => {
        const dE = bohrEnergyEV(3) - bohrEnergyEV(2);
        expect(dE).toBeCloseTo(E_H * (1 / 4 - 1 / 9), 12); // 1.888651 eV
        expect(dE).toBeCloseTo(1.888651, 6);
        for (const [lo, hi] of [[1, 2], [2, 3], [2, 6], [3, 7]]) {
            const e = bohrEnergyEV(hi) - bohrEnergyEV(lo);
            expect(rel(energyToWavelengthNm(e), rydbergWavelength(lo, hi) * 1e9)).toBeLessThan(1e-12);
        }
    });
    it('transitionPhoton: emission/absorption, series and region', () => {
        const em = transitionPhoton(3, 2);
        expect(em.type).toBe('emission');
        expect(em.energyEV).toBeCloseTo(1.888651, 6);
        expect(em.wavelengthNm).toBeCloseTo(656.4696, 3);
        expect(em.series).toBe('Balmer');
        expect(em.region).toBe('visible');
        const ab = transitionPhoton(2, 3);
        expect(ab.type).toBe('absorption');
        expect(ab.energyEV).toBeCloseTo(em.energyEV, 12);
        expect(transitionPhoton(2, 1).energyEV).toBeCloseTo(0.75 * E_H, 12); // 10.1987 eV
        expect(transitionPhoton(5, 1).series).toBe('Lyman');
        expect(transitionPhoton(5, 1).region).toBe('UV');
        expect(transitionPhoton(4, 3).series).toBe('Paschen');
        expect(transitionPhoton(4, 3).region).toBe('IR');
        expect(transitionPhoton(2, 2)).toBeNull();
        expect(transitionPhoton(0, 2)).toBeNull();
        expect(seriesName(4)).toBe('Brackett');
        expect(seriesName(5)).toBe('Pfund');
    });
    it('Balmer lines vs NIST ASD (H I Ritz, https://physics.nist.gov/PhysRefData/ASD/lines_form.html): vacuum and air within 0.012 nm', () => {
        const lines = balmerLines(6);
        expect(lines.map(l => l.name)).toEqual(['Hα', 'Hβ', 'Hγ', 'Hδ']);
        const nistVac = [656.4632, 486.2691, 434.1691, 410.28991];
        const nistAir = [656.2819, 486.1333, 434.0471, 410.17415];
        lines.forEach((l, i) => {
            // the residual (≤ 0.007 nm) is the fine structure that the Bohr model ignores
            expect(Math.abs(l.wavelengthNm - nistVac[i])).toBeLessThan(0.012);
            expect(Math.abs(l.wavelengthAirNm - nistAir[i])).toBeLessThan(0.012);
        });
        expect(lines[1].wavelengthNm).toBeCloseTo(16e9 / (3 * R_H), 9);
    });
});

describe('air ↔ vacuum wavelengths (IAU / Morton 2000)', () => {
    it('n_air − 1 = 2.76·10⁻⁴ at 656 nm; Hα 656.470 vac → 656.288 air', () => {
        expect((airRefractiveIndex(656.47) - 1) * 1e4).toBeCloseTo(2.762, 2);
        expect(vacuumToAirNm(656.4696)).toBeCloseTo(656.2883, 3);
        // NIST Na D2: 588.995 nm air ↔ 589.158 nm vacuum
        expect(airToVacuumNm(588.9951)).toBeCloseTo(589.1583, 3);
    });
    it('round trip and the < 200 nm vacuum convention', () => {
        for (const nm of [250, 404.6565, 546.075, 700, 1500]) {
            expect(airToVacuumNm(vacuumToAirNm(nm))).toBeCloseTo(nm, 6);
        }
        expect(vacuumToAirNm(121.567)).toBe(121.567);
        expect(airRefractiveIndex(100)).toBeNaN();
        expect(vacuumToAirNm(0)).toBeNaN();
    });
});

describe('reference emission lines (NIST ASD, air)', () => {
    it('sodium D lines: D1 = 589.5924 nm, D2 = 588.9951 nm (D2 is the shorter wavelength)', () => {
        const d1 = EMISSION_LINES_NM.sodium.find(l => l.name === 'D1');
        const d2 = EMISSION_LINES_NM.sodium.find(l => l.name === 'D2');
        expect(d1.wavelength).toBe(589.5924);
        expect(d2.wavelength).toBe(588.9951);
        expect(d2.wavelength).toBeLessThan(d1.wavelength);
    });
    it('helium (667.8151, D3 587.5621, 501.5678, 492.1931, 471.3146, 447.1480) and mercury (579.067, 576.961, 546.075, 435.8335, 404.6565)', () => {
        expect(EMISSION_LINES_NM.helium.map(l => l.wavelength)).toEqual([667.8151, 587.5621, 501.5678, 492.1931, 471.3146, 447.148]);
        expect(EMISSION_LINES_NM.helium.find(l => l.name === 'D3').wavelength).toBe(587.5621);
        expect(EMISSION_LINES_NM.mercury.map(l => l.wavelength)).toEqual([579.067, 576.961, 546.075, 435.8335, 404.6565]);
        Object.values(EMISSION_LINES_NM).flat().forEach(l => expect(spectralRegion(l.wavelength)).toBe('visible'));
    });
    it('spectroscope: 0.1 nm scale, worst-case error 2.5σ + ½ division = 0.125 nm', () => {
        expect(maxReadingError(500, SPECTROSCOPE)).toBeCloseTo(2.5 * 0.03 + 0.05, 12);
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

describe('radioactive decay: reference half-lives and counting', () => {
    it('ENSDF/NUBASE half-lives (IAEA LiveChart)', () => {
        const day = 86400;
        const year = 365.2422 * day;
        expect(HALF_LIVES_S.Rn220).toBe(55.6);
        expect(HALF_LIVES_S.Ba137m / 60).toBeCloseTo(2.552, 3);
        expect(HALF_LIVES_S.I131 / day).toBeCloseTo(8.0252, 4);
        expect(HALF_LIVES_S.Rn222 / day).toBeCloseTo(3.8222, 4);
        expect(HALF_LIVES_S.Co60 / year).toBeCloseTo(5.2712, 3);
        expect(HALF_LIVES_S.Cs137 / year).toBeCloseTo(30.08, 2);
        expect(HALF_LIVES_S.C14 / year).toBeCloseTo(5700, -1);
        expect(HALF_LIVES_S.U238 / year / 1e9).toBeCloseTo(4.468, 3);
    });
    it('countsInWindow counts the τ in (t − Δt, t]', () => {
        const taus = [0.5, 1, 1.2, 2, 2.0001, 5];
        expect(countsInWindow(taus, 2, 1)).toBe(2); // 1.2 and 2; τ = 1 is excluded (open left end)
        expect(countsInWindow(taus, 2, 1.0000001)).toBe(3);
        expect(countsInWindow(taus, 0.5, 1)).toBe(1); // window clipped at t = 0
        expect(countsInWindow(taus, 10, 0)).toBeNaN();
        expect(countsInWindow(null, 1, 1)).toBeNaN();
    });
    it('window counts scatter like Poisson: mean ≈ N0(1−e^(−λΔt))e^(−λt), variance ≈ mean', () => {
        const rnd = createRng(99);
        const T = 10;
        const lam = decayConstant(T);
        const counts = [];
        for (let k = 0; k < 400; k++) {
            const taus = Array.from({ length: 1000 }, () => sampleDecayTime(1 - rnd(), lam));
            counts.push(countsInWindow(taus, 10, 1));
        }
        const mean = counts.reduce((a, b) => a + b, 0) / counts.length;
        const variance = counts.reduce((a, b) => a + (b - mean) ** 2, 0) / (counts.length - 1);
        const expected = 1000 * Math.exp(-lam * 9) * (1 - Math.exp(-lam)); // 35.9
        expect(rel(mean, expected)).toBeLessThan(0.03);
        expect(variance / mean).toBeGreaterThan(0.8);
        expect(variance / mean).toBeLessThan(1.2);
    });
});

describe('nuclear fission', () => {
    it('reference data: E_f = 202.36 MeV (Ma 2013), school 200 MeV; ν̄ = 2.4355', () => {
        expect(TYPICAL_FISSION_ENERGY_MEV).toBe(200);
        expect(FISSION_ENERGY_U235_MEV).toBe(202.36);
        expect(NEUTRONS_PER_FISSION_U235).toBe(2.4355);
        expect(rel(TYPICAL_FISSION_ENERGY_MEV, FISSION_ENERGY_U235_MEV)).toBeLessThan(0.012);
    });
    it('AME2020 masses; neutron mass CODATA 2022 1.008 664 916 06 u', () => {
        expect(ATOMIC_MASS_U.U235).toBe(235.043928117);
        expect(ATOMIC_MASS_U.Ba141).toBe(140.914403653);
        expect(ATOMIC_MASS_U.Kr92).toBe(91.926173092);
        expect(ATOMIC_MASS_U.n).toBeCloseTo(1.00866491606, 10);
    });
    it('1 u = 931.494 MeV', () => {
        expect(massDefectEnergyMeV(1)).toBeCloseTo(931.49410372, 8);
        expect(massDefect([2, 1], [2.5])).toBeCloseTo(0.5, 12);
    });
    it('²³⁵U + n → ¹⁴¹Ba + ⁹²Kr + 3n: Δm = 0.18602 u → 173.3 MeV', () => {
        // 235.043928117 − 140.914403653 − 91.926173092 − 2·1.00866491606 = 0.18602154 u
        const q = u235FissionQ();
        expect(q.deltaMassU).toBeCloseTo(235.043928117 - 140.914403653 - 91.926173092 - 2 * 1.00866491606, 9);
        expect(q.energyMeV).toBeCloseTo(0.18602154 * 931.49410372, 4); // 173.278 MeV
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
    it('reference spacings: NaCl(200) 0.28201, LiF(200) 0.20135, KCl(200) 0.31466, diamond(111) 0.20593, Si(111) 0.31356 nm', () => {
        expect(LATTICE_CONSTANTS.silicon).toBe(5.431020511e-10); // CODATA 2022 lattice parameter of Si
        expect(CRYSTAL_SPACINGS.nacl.d * 1e9).toBeCloseTo(0.56402 / 2, 9);
        expect(CRYSTAL_SPACINGS.lif.d * 1e9).toBeCloseTo(0.4027 / 2, 9);
        expect(CRYSTAL_SPACINGS.kcl.d * 1e9).toBeCloseTo(0.62931 / 2, 9);
        expect(CRYSTAL_SPACINGS.diamond.d * 1e9).toBeCloseTo(0.356679 / Math.sqrt(3), 9);
        expect(CRYSTAL_SPACINGS.silicon.d * 1e9).toBeCloseTo(0.31356012, 7);
        // the task values d = 0.282 / 0.314 nm are these rounded to 3 decimals
        expect(+(CRYSTAL_SPACINGS.nacl.d * 1e9).toFixed(3)).toBe(0.282);
        expect(+(CRYSTAL_SPACINGS.silicon.d * 1e9).toFixed(3)).toBe(0.314);
    });
    it('X-ray lines from NIST transition energies: Cu Kα1 0.154059, Kα 0.154187, Mo Kα 0.071075 nm', () => {
        const hc = 1239.8419843320026; // eV·nm (exact)
        expect(XRAY_LINES.CuKa1 * 1e9).toBeCloseTo(hc / 8047.8227, 9);
        expect(XRAY_LINES.CuKa1 * 1e9).toBeCloseTo(0.154059, 6);
        expect(XRAY_LINES.CuKa2 * 1e9).toBeCloseTo(0.154443, 6);
        expect(XRAY_LINES.CuKa * 1e9).toBeCloseTo(0.154187, 6);
        expect(XRAY_LINES.CuKb * 1e9).toBeCloseTo(0.139223, 6);
        expect(XRAY_LINES.MoKa * 1e9).toBeCloseTo(0.071075, 6); // Leybold leaflet: 71.08 pm
        expect(XRAY_LINES.MoKb * 1e9).toBeCloseTo(0.063230, 6); // Leybold leaflet: 63.09 pm
        // school value 0.154 nm is Cu Kα to 0.12 %
        expect(rel(0.154e-9, XRAY_LINES.CuKa)).toBeLessThan(1.3e-3);
    });
    it('goniometer: 0.1° steps, worst-case error 0.1°', () => {
        expect(maxReadingError(15, XRAY_GONIOMETER)).toBeCloseTo(0.1, 12);
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
