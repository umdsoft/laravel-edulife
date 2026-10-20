/**
 * Atomic & nuclear physics for the virtual lab (pure functions).
 *
 * Units: SI unless the name says otherwise (…EV = electron-volt, …Nm = nanometre,
 * …MeV, masses in unified atomic mass units u where stated).
 * Invalid inputs return NaN / null instead of throwing.
 */

import {
    PLANCK, SPEED_OF_LIGHT, ELEMENTARY_CHARGE, ELECTRON_MASS, PLANCK_EV, HC_EV_NM, BOHR_RADIUS, FINE_STRUCTURE,
    AMU_MEV, AVOGADRO, NEUTRON_MASS, AMU_KG, RYDBERG_INFINITY, RYDBERG_HYDROGEN, RYDBERG_ENERGY_INFINITY_EV,
    RYDBERG_ENERGY_HYDROGEN_EV,
} from './constants.js';

// Constants come from ./constants.js (CODATA 2022, https://physics.nist.gov/cuu/Constants/);
// the historical export names of this module are kept as re-exports.
export {
    PLANCK, SPEED_OF_LIGHT, ELEMENTARY_CHARGE, ELECTRON_MASS, PLANCK_EV, HC_EV_NM, BOHR_RADIUS, FINE_STRUCTURE,
    AMU_MEV, AVOGADRO, RYDBERG_INFINITY, RYDBERG_HYDROGEN,
};

/**
 * Rydberg constant used for hydrogen [1/m]: R_H = R∞/(1 + mₑ/mₚ) = 1.096 775 8·10⁷ m⁻¹
 * (reduced-mass correction). It reproduces the NIST ASD Balmer lines to 0.01 nm
 * (the residual is the fine structure the Bohr model ignores). The school value
 * R = 1.097·10⁷ m⁻¹ (= R∞ rounded) is {@link RYDBERG_SCHOOL}; it differs by 0.02 %.
 */
export const RYDBERG = RYDBERG_HYDROGEN;
/** Rydberg constant as printed in the school textbooks [1/m]. */
export const RYDBERG_SCHOOL = 1.097e7;
/**
 * Hydrogen ground-state binding energy of the Bohr model with reduced mass [eV]:
 * R_H·hc = 13.598 3 eV (NIST ionisation energy 13.598 434 eV includes relativistic and QED
 * terms). It is exactly consistent with {@link RYDBERG}: hc/ΔE = Rydberg λ.
 * The school value 13.6 eV is {@link RYDBERG_ENERGY_SCHOOL_EV} (0.01 % higher).
 */
export const RYDBERG_ENERGY_EV = RYDBERG_ENERGY_HYDROGEN_EV;
/** Hydrogen ground-state energy as printed in the school textbooks [eV]. */
export const RYDBERG_ENERGY_SCHOOL_EV = 13.6;
/** 1 MeV in joules. */
export const MEV_J = 1e6 * ELEMENTARY_CHARGE;

/** Visible band used for classification/colours [nm]. */
export const VISIBLE_MIN_NM = 380;
export const VISIBLE_MAX_NM = 750;

const isNum = (x) => typeof x === 'number' && Number.isFinite(x);
const isPosInt = (n) => Number.isInteger(n) && n >= 1;

// ─────────────────────────────────────────────────────────────────────────────
// Photons & colour
// ─────────────────────────────────────────────────────────────────────────────

/** λ = c / f. @param {number} frequency [Hz] @returns {number} [m] */
export function frequencyToWavelength(frequency) {
    if (!isNum(frequency) || frequency <= 0) return NaN;
    return SPEED_OF_LIGHT / frequency;
}

/** f = c / λ. @param {number} wavelength [m] @returns {number} [Hz] */
export function wavelengthToFrequency(wavelength) {
    if (!isNum(wavelength) || wavelength <= 0) return NaN;
    return SPEED_OF_LIGHT / wavelength;
}

/** Photon energy E = h·f in eV. @param {number} frequency [Hz] */
export function photonEnergyEV(frequency) {
    if (!isNum(frequency) || frequency < 0) return NaN;
    return PLANCK_EV * frequency;
}

/** Photon wavelength from its energy: λ[nm] = hc / E. @param {number} energyEV [eV] > 0 */
export function energyToWavelengthNm(energyEV) {
    if (!isNum(energyEV) || energyEV <= 0) return NaN;
    return HC_EV_NM / energyEV;
}

/**
 * Spectral region of a wavelength.
 * @param {number} nm wavelength [nm]
 * @returns {'UV'|'visible'|'IR'|null}
 */
export function spectralRegion(nm) {
    if (!isNum(nm) || nm <= 0) return null;
    if (nm < VISIBLE_MIN_NM) return 'UV';
    if (nm > VISIBLE_MAX_NM) return 'IR';
    return 'visible';
}

/**
 * Refractive index of standard dry air (15 °C, 101 325 Pa, 450 ppm CO₂) at a vacuum
 * wavelength — IAU standard (Morton 2000, ApJS 130, 403, after Birch & Downs 1994):
 *   n = 1 + 8.34254·10⁻⁵ + 2.406147·10⁻² / (130 − s²) + 1.5998·10⁻⁴ / (38.9 − s²),  s = 1 µm / λ_vac
 * Source: https://www.astro.uu.se/valdwiki/Air-to-vacuum%20conversion
 * (n − 1 ≈ 2.77·10⁻⁴ at 656 nm). Defined for λ ≥ 200 nm.
 * @param {number} vacuumNm vacuum wavelength [nm]
 */
export function airRefractiveIndex(vacuumNm) {
    if (!isNum(vacuumNm) || vacuumNm < 200) return NaN;
    const s2 = (1000 / vacuumNm) ** 2;
    return 1 + 0.0000834254 + 0.02406147 / (130 - s2) + 0.00015998 / (38.9 - s2);
}

/**
 * Vacuum → air wavelength, λ_air = λ_vac / n (IAU convention: spectral lines above
 * 200 nm are tabulated in air, below 200 nm in vacuum — below 200 nm the value is
 * returned unchanged).
 * @param {number} vacuumNm [nm] @returns {number} [nm]
 */
export function vacuumToAirNm(vacuumNm) {
    if (!isNum(vacuumNm) || vacuumNm <= 0) return NaN;
    if (vacuumNm < 200) return vacuumNm;
    return vacuumNm / airRefractiveIndex(vacuumNm);
}

/**
 * Air → vacuum wavelength (N. Piskunov's inversion of the Morton formula, VALD3; same source).
 * @param {number} airNm [nm] @returns {number} [nm]
 */
export function airToVacuumNm(airNm) {
    if (!isNum(airNm) || airNm <= 0) return NaN;
    if (airNm < 200) return airNm;
    const s2 = (1000 / airNm) ** 2;
    const n = 1 + 0.00008336624212083 + 0.02408926869968 / (130.1065924522 - s2)
        + 0.0001599740894897 / (38.92568793293 - s2);
    return airNm * n;
}

/**
 * Approximate sRGB colour of monochromatic light (Bruton's piece-wise model with
 * intensity fall-off at the edges of vision).
 * @param {number} nm wavelength [nm]
 * @returns {string|null} '#rrggbb', or null outside the visible band
 */
export function wavelengthToRGB(nm) {
    if (!isNum(nm) || nm < VISIBLE_MIN_NM || nm > VISIBLE_MAX_NM) return null;
    let r = 0;
    let g = 0;
    let b = 0;
    if (nm < 440) { r = -(nm - 440) / 60; b = 1; }
    else if (nm < 490) { g = (nm - 440) / 50; b = 1; }
    else if (nm < 510) { g = 1; b = -(nm - 510) / 20; }
    else if (nm < 580) { r = (nm - 510) / 70; g = 1; }
    else if (nm < 645) { r = 1; g = -(nm - 645) / 65; }
    else { r = 1; }
    let f = 1;
    if (nm < 420) f = 0.3 + (0.7 * (nm - 380)) / 40;
    else if (nm > 700) f = 0.3 + (0.7 * (780 - nm)) / 80;
    const ch = (v) => {
        const x = v <= 0 ? 0 : Math.round(255 * Math.pow(v * f, 0.8));
        return Math.min(255, x).toString(16).padStart(2, '0');
    };
    return `#${ch(r)}${ch(g)}${ch(b)}`;
}

// ─────────────────────────────────────────────────────────────────────────────
// Photoelectric effect
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Work functions φ [eV] of clean polycrystalline metal surfaces — the recommended values
 * of H. B. Michaelson, J. Appl. Phys. 48, 4729 (1977), https://doi.org/10.1063/1.323539
 * (reproduced in the CRC Handbook "Electron work function of the elements"). Measured
 * values depend on the crystal face and surface state by ±0.1…0.5 eV (CRC ranges, e.g.
 * Cu 4.53–5.10, Pt 5.12–5.93 eV); the older value Na 2.36 eV (oxidised/photoelectric
 * surfaces) is replaced by Michaelson's 2.75 eV.
 */
export const WORK_FUNCTIONS_EV = Object.freeze({
    Cs: 2.14,
    K: 2.30,
    Na: 2.75,
    Ca: 2.87,
    Mg: 3.66,
    Al: 4.28,
    Zn: 4.33,
    Ag: 4.26,
    Cu: 4.65,
    Pt: 5.65,
});

/**
 * Metal whose work function is closest to φ (for labelling the slider).
 * @param {number} workFunctionEV
 * @param {number} [tolerance=0.15] max |Δφ| in eV
 * @returns {string|null}
 */
export function nearestMetal(workFunctionEV, tolerance = 0.15) {
    if (!isNum(workFunctionEV)) return null;
    let best = null;
    let bestD = Infinity;
    for (const [sym, wf] of Object.entries(WORK_FUNCTIONS_EV)) {
        const d = Math.abs(wf - workFunctionEV);
        if (d < bestD) { bestD = d; best = sym; }
    }
    return bestD <= tolerance ? best : null;
}

/**
 * Non-relativistic speed from kinetic energy: v = √(2·KE / m).
 * @param {number} kineticEnergyJ [J]
 * @param {number} [mass=mₑ] [kg]
 */
export function speedFromKineticEnergy(kineticEnergyJ, mass = ELECTRON_MASS) {
    if (!isNum(kineticEnergyJ) || kineticEnergyJ < 0 || !(mass > 0)) return NaN;
    return Math.sqrt((2 * kineticEnergyJ) / mass);
}

/**
 * Einstein photoelectric equation. Emission occurs only if h·f > φ, independently of
 * intensity; then KEmax = h·f − φ and the stopping voltage V0 = KEmax / e.
 * @param {{frequency:number, workFunctionEV:number}} p f [Hz], φ [eV]
 * @returns {{photonEnergyEV:number, emits:boolean, kineticEnergyMaxEV:number,
 *            stoppingVoltage:number, maxSpeed:number, thresholdFrequency:number,
 *            thresholdWavelength:number}}
 */
export function photoelectric({ frequency, workFunctionEV }) {
    const E = photonEnergyEV(frequency);
    const ok = isNum(E) && isNum(workFunctionEV) && workFunctionEV > 0;
    const emits = ok && E > workFunctionEV;
    const kineticEnergyMaxEV = emits ? E - workFunctionEV : 0;
    return {
        photonEnergyEV: E,
        emits,
        kineticEnergyMaxEV,
        stoppingVoltage: kineticEnergyMaxEV, // V0 = KEmax/e → numerically equal in volts
        maxSpeed: speedFromKineticEnergy(kineticEnergyMaxEV * ELEMENTARY_CHARGE),
        thresholdFrequency: ok ? workFunctionEV / PLANCK_EV : NaN,
        thresholdWavelength: ok ? (HC_EV_NM / workFunctionEV) * 1e-9 : NaN,
    };
}

/**
 * Photon arrival rate of monochromatic light: Ṅ = P / (h·f).
 * @param {number} power [W]
 * @param {number} frequency [Hz]
 * @returns {number} photons per second
 */
export function photonRate(power, frequency) {
    if (!isNum(power) || power < 0 || !isNum(frequency) || frequency <= 0) return NaN;
    return power / (PLANCK * frequency);
}

/**
 * Saturation photocurrent I = η·e·P/(h·f) above threshold, 0 below threshold
 * (no emission below threshold, however intense the light).
 * @param {{power:number, frequency:number, workFunctionEV:number, quantumEfficiency?:number}} p
 * @returns {number} I [A]
 */
export function photocurrent({ power, frequency, workFunctionEV, quantumEfficiency = 1e-3 }) {
    const pe = photoelectric({ frequency, workFunctionEV });
    if (!pe.emits) return 0;
    const rate = photonRate(power, frequency);
    if (!isNum(rate)) return NaN;
    return quantumEfficiency * ELEMENTARY_CHARGE * rate;
}

// ─────────────────────────────────────────────────────────────────────────────
// Bohr model (hydrogen-like atoms)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Rydberg constant of a one-electron atom with nuclear charge Z [1/m]: hydrogen (Z = 1)
 * uses the reduced-mass value R_H; heavier nuclei the infinite-mass R∞ (He⁺ error 1.4·10⁻⁴).
 */
export function rydbergConstant(Z = 1) {
    return Z === 1 ? RYDBERG_HYDROGEN : RYDBERG_INFINITY;
}

/** Rydberg energy R·hc for nuclear charge Z [eV] (13.598 3 eV for hydrogen). */
export function rydbergEnergyEV(Z = 1) {
    return Z === 1 ? RYDBERG_ENERGY_HYDROGEN_EV : RYDBERG_ENERGY_INFINITY_EV;
}

/**
 * Energy of level n: Eₙ = −Ry·Z²/n², Ry = 13.598 eV for hydrogen (school: 13.6 eV).
 * @param {number} n principal quantum number (integer ≥ 1)
 * @param {number} [Z=1] nuclear charge
 */
export function bohrEnergyEV(n, Z = 1) {
    if (!isPosInt(n) || !(Z > 0)) return NaN;
    return (-rydbergEnergyEV(Z) * Z * Z) / (n * n);
}

/**
 * Orbit radius rₙ = a0·n²/Z [m]. With the reduced mass the electron–proton distance is
 * a0·n²·(1 + mₑ/mₚ); the electron's own orbit about the centre of mass is exactly a0·n².
 */
export function bohrRadius(n, Z = 1) {
    if (!isPosInt(n) || !(Z > 0)) return NaN;
    return (BOHR_RADIUS * n * n) / Z;
}

/** Orbital speed vₙ = α·c·Z/n [m/s] (≈ 2.19·10⁶ m/s for n = 1, Z = 1). */
export function bohrSpeed(n, Z = 1) {
    if (!isPosInt(n) || !(Z > 0)) return NaN;
    return (FINE_STRUCTURE * SPEED_OF_LIGHT * Z) / n;
}

/**
 * Rydberg formula 1/λ = R·Z²·(1/n1² − 1/n2²) for n2 > n1 ≥ 1 (R = R_H for hydrogen).
 * The result is the VACUUM wavelength; spectroscopes in air read λ/n_air
 * ({@link vacuumToAirNm}: 0.028 % shorter in the visible).
 * @param {number} n1 lower level
 * @param {number} n2 upper level
 * @param {number} [Z=1]
 * @returns {number} λ [m], NaN if n1 ≥ n2 or invalid
 */
export function rydbergWavelength(n1, n2, Z = 1) {
    if (!isPosInt(n1) || !isPosInt(n2) || n2 <= n1 || !(Z > 0)) return NaN;
    const inv = rydbergConstant(Z) * Z * Z * (1 / (n1 * n1) - 1 / (n2 * n2));
    return 1 / inv;
}

/** Name of the hydrogen series ending on level nLower. */
export function seriesName(nLower) {
    return ({ 1: 'Lyman', 2: 'Balmer', 3: 'Paschen', 4: 'Brackett', 5: 'Pfund', 6: 'Humphreys' })[nLower] ?? null;
}

/**
 * Photon emitted/absorbed in a transition nFrom → nTo.
 * |ΔE| = 13.6·Z²·|1/n_low² − 1/n_high²| eV, λ from the Rydberg formula.
 * @returns {{type:'emission'|'absorption', energyEV:number, wavelengthNm:number,
 *            lower:number, upper:number, series:string|null, region:string|null}|null}
 *   null when nFrom === nTo or levels invalid
 */
export function transitionPhoton(nFrom, nTo, Z = 1) {
    if (!isPosInt(nFrom) || !isPosInt(nTo) || nFrom === nTo) return null;
    const lower = Math.min(nFrom, nTo);
    const upper = Math.max(nFrom, nTo);
    const energyEV = bohrEnergyEV(upper, Z) - bohrEnergyEV(lower, Z);
    const wavelengthNm = rydbergWavelength(lower, upper, Z) * 1e9;
    return {
        type: nTo < nFrom ? 'emission' : 'absorption',
        energyEV,
        wavelengthNm,
        lower,
        upper,
        series: Z === 1 ? seriesName(lower) : null,
        region: spectralRegion(wavelengthNm),
    };
}

/**
 * Balmer lines Hα, Hβ, … (n → 2) from the Rydberg formula with R_H.
 * wavelengthNm is the vacuum wavelength (what the formula gives), wavelengthAirNm the
 * value a spectroscope in air reads and NIST ASD tabulates (Hα 656.28, Hβ 486.13,
 * Hγ 434.05, Hδ 410.17 nm — https://physics.nist.gov/PhysRefData/ASD/lines_form.html).
 * @param {number} [nMax=6]
 * @returns {Array<{name:string, n:number, wavelengthNm:number, wavelengthAirNm:number}>}
 */
export function balmerLines(nMax = 6) {
    const greek = ['α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η'];
    const out = [];
    for (let n = 3; n <= nMax; n++) {
        const wavelengthNm = rydbergWavelength(2, n) * 1e9;
        out.push({ name: `H${greek[n - 3] ?? n}`, n, wavelengthNm, wavelengthAirNm: vacuumToAirNm(wavelengthNm) });
    }
    return out;
}

/**
 * Reference visible emission lines [nm, IN AIR] — the strongest lines of the neutral
 * atoms in the 400–700 nm window, observed wavelengths of the NIST Atomic Spectra
 * Database (ASD v5, https://physics.nist.gov/PhysRefData/ASD/lines_form.html; He I, Na I,
 * Hg I, wavelengths in air). For hydrogen compare {@link balmerLines} (Bohr model).
 */
export const EMISSION_LINES_NM = Object.freeze({
    // H I Balmer lines (observed, air). The Bohr model (balmerLines → vacuumToAirNm) agrees
    // to ≤ 0.01 nm; the residual is the fine structure.
    hydrogen: [
        { wavelength: 656.279, name: 'Hα' },
        { wavelength: 486.135, name: 'Hβ' },
        { wavelength: 434.0472, name: 'Hγ' },
        { wavelength: 410.1734, name: 'Hδ' },
    ],
    helium: [
        { wavelength: 667.8151, name: '' },
        { wavelength: 587.5621, name: 'D3' },
        { wavelength: 501.5678, name: '' },
        { wavelength: 492.1931, name: '' },
        { wavelength: 471.3146, name: '' },
        { wavelength: 447.1480, name: '' },
    ],
    sodium: [
        { wavelength: 589.5924, name: 'D1' },
        { wavelength: 588.9951, name: 'D2' },
    ],
    mercury: [
        { wavelength: 579.0670, name: '' },
        { wavelength: 576.9610, name: '' },
        { wavelength: 546.0750, name: '' },
        { wavelength: 435.8335, name: '' },
        { wavelength: 404.6565, name: '' },
    ],
});

/**
 * Spectroscope used to read a line position (prism/grating spectroscope with a
 * wavelength scale read through the eyepiece to 0.1 nm; 1σ setting scatter 0.03 nm).
 */
export const SPECTROSCOPE = Object.freeze({ resolution: 0.1, sigmaAbs: 0.03, unit: 'nm', accuracy: '±0.1 nm' });

// ─────────────────────────────────────────────────────────────────────────────
// Radioactive decay
// ─────────────────────────────────────────────────────────────────────────────

/** λ = ln 2 / T½ [1/s]. @param {number} halfLife T½ [s] > 0 */
export function decayConstant(halfLife) {
    if (!isNum(halfLife) || halfLife <= 0) return NaN;
    return Math.LN2 / halfLife;
}

/** Mean lifetime τ = 1/λ = T½ / ln 2 [s]. */
export function meanLifetime(halfLife) {
    return 1 / decayConstant(halfLife);
}

/**
 * Undecayed nuclei N(t) = N0·e^(−λt) = N0·(1/2)^(t/T½).
 * @param {number} N0 initial number
 * @param {number} t [s] ≥ 0
 * @param {number} halfLife [s] > 0
 */
export function remainingNuclei(N0, t, halfLife) {
    if (!isNum(N0) || !isNum(t) || t < 0) return NaN;
    const lam = decayConstant(halfLife);
    if (!isNum(lam)) return NaN;
    return N0 * Math.exp(-lam * t);
}

/** Decayed nuclei N0 − N(t). */
export function decayedNuclei(N0, t, halfLife) {
    return N0 - remainingNuclei(N0, t, halfLife);
}

/** Activity A(t) = λ·N(t) [Bq]. */
export function activity(N0, t, halfLife) {
    return decayConstant(halfLife) * remainingNuclei(N0, t, halfLife);
}

/**
 * Inverse-transform sample of an exponential decay time t = −ln(u)/λ.
 * Pre-sampling one time per nucleus makes the per-nucleus display exact for any
 * frame rate and its statistics converge to N0·e^(−λt).
 * @param {number} u uniform random number in (0, 1]
 * @param {number} lambda decay constant [1/s]
 */
export function sampleDecayTime(u, lambda) {
    if (!isNum(u) || u <= 0 || u > 1 || !(lambda > 0)) return NaN;
    return -Math.log(u) / lambda;
}

/**
 * Half-lives T½ [s] of familiar radionuclides — ENSDF evaluations as served by the IAEA
 * LiveChart of Nuclides API (https://nds.iaea.org/relnsd/v1/data?fields=ground_states&nuclides=131i),
 * Ba-137m from NUBASE2020 (https://www-nds.iaea.org/amdc/ame2020/nubase_4.mas20.txt).
 * Rn-220 (55.6 s), Ba-137m (2.552 min) and Pa-234m (1.159 min) are the classic school
 * half-life experiments; the simulation's 5–30 s half-lives are of the same order.
 */
export const HALF_LIVES_S = Object.freeze({
    Rn220: 55.6,
    Ba137m: 153.12, // 2.552 min
    Pa234m: 69.54, // 1.159 min
    I131: 693377.28, // 8.0252 d
    Rn222: 330238.08, // 3.8222 d
    Co60: 166344192, // 5.2712 a
    Cs137: 949232333, // 30.08 a
    Ra226: 50491081559, // 1600 a
    C14: 179874478055, // 5700 a
    U238: 1.4099634525e17, // 4.468·10⁹ a
});

/**
 * Number of decays registered in the counting window (t − Δt, t] from the pre-sampled
 * decay times of the nuclei (a Geiger counter with 100 % efficiency and no background).
 * The count is binomial(N(t−Δt), 1 − e^(−λΔt)) ≈ Poisson — the physical scatter.
 * @param {number[]} decayTimes τ of every nucleus [s]
 * @param {number} t end of the window [s]
 * @param {number} dt window length [s] > 0
 */
export function countsInWindow(decayTimes, t, dt) {
    if (!Array.isArray(decayTimes) || !isNum(t) || !(dt > 0)) return NaN;
    const t0 = Math.max(0, t - dt);
    let n = 0;
    for (const tau of decayTimes) if (tau > t0 && tau <= t) n++;
    return n;
}

// ─────────────────────────────────────────────────────────────────────────────
// Nuclear fission
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Atomic masses [u]: nuclides from the Atomic Mass Evaluation AME2020 (Wang et al.,
 * Chin. Phys. C 45, 030003 (2021), https://www-nds.iaea.org/amdc/ame2020/mass_1.mas20.txt);
 * the neutron from CODATA 2022 (mₙ/mᵤ = 1.008 664 916 06; AME2020 1.008 664 915 90).
 */
export const ATOMIC_MASS_U = Object.freeze({
    n: NEUTRON_MASS / AMU_KG,
    U235: 235.043928117,
    Ba141: 140.914403653,
    Kr92: 91.926173092,
});

/**
 * Energy per U-235 fission used by the school tasks: ≈ 200 MeV. The evaluated energy
 * release per thermal fission (incl. delayed β/γ, without the antineutrinos) is
 * 202.36 ± 0.26 MeV (Ma et al., Phys. Rev. C 88, 014605 (2013), arXiv:1212.6625, Table 9) —
 * see {@link FISSION_ENERGY_U235_MEV}.
 */
export const TYPICAL_FISSION_ENERGY_MEV = 200;
/** Evaluated energy release per thermal U-235 fission, MeV (Ma et al. 2013). */
export const FISSION_ENERGY_U235_MEV = 202.36;

/**
 * Mean total number of neutrons per thermal U-235 fission ν̄ = 2.4355 ± 0.0023
 * (prompt ≈ 2.42 + delayed 0.016; IAEA neutron-standards thermal constants, GMA2004,
 * https://www-nds.iaea.org/standards/Reports/ThermalConstants.pdf).
 */
export const NEUTRONS_PER_FISSION_U235 = 2.4355;

/** Mass defect Δm = Σm(reactants) − Σm(products) [u]. */
export function massDefect(reactantMasses, productMasses) {
    const sum = (a) => a.reduce((s, m) => s + m, 0);
    return sum(reactantMasses) - sum(productMasses);
}

/** ΔE = Δm·c² with 1 u = 931.494 MeV. @param {number} deltaMassU [u] @returns {number} [MeV] */
export function massDefectEnergyMeV(deltaMassU) {
    if (!isNum(deltaMassU)) return NaN;
    return deltaMassU * AMU_MEV;
}

/**
 * Q-value of ²³⁵U + n → ¹⁴¹Ba + ⁹²Kr + 3n from the mass defect (≈ 173 MeV prompt;
 * the ≈ 200 MeV total includes the later β-decays of the fragments).
 * @returns {{deltaMassU:number, energyMeV:number}}
 */
export function u235FissionQ() {
    const m = ATOMIC_MASS_U;
    const deltaMassU = massDefect([m.U235, m.n], [m.Ba141, m.Kr92, m.n, m.n, m.n]);
    return { deltaMassU, energyMeV: massDefectEnergyMeV(deltaMassU) };
}

/**
 * Speed of a particle from kinetic energy, non-relativistic: β = v/c = √(2·KE / (m·c²)).
 * @param {number} kineticMeV
 * @param {number} massU [u]
 * @returns {number} β
 */
export function betaFromKineticMeV(kineticMeV, massU) {
    if (!isNum(kineticMeV) || kineticMeV < 0 || !(massU > 0)) return NaN;
    return Math.sqrt((2 * kineticMeV) / (massU * AMU_MEV));
}

/**
 * Two-fragment kinematics: equal and opposite momenta (fissioning nucleus at rest,
 * neutron momenta neglected), so KE_A/KE_B = m_B/m_A and v_A/v_B = m_B/m_A.
 * @param {{qMeV:number, massA:number, massB:number, neutrons?:number, neutronKineticMeV?:number}} p
 * @returns {{kineticA:number, kineticB:number, betaA:number, betaB:number, betaNeutron:number}}
 */
export function fissionFragmentKinetics({ qMeV, massA, massB, neutrons = 3, neutronKineticMeV = 2 }) {
    const fragQ = qMeV - neutrons * neutronKineticMeV;
    const kineticA = (fragQ * massB) / (massA + massB);
    const kineticB = (fragQ * massA) / (massA + massB);
    return {
        kineticA,
        kineticB,
        betaA: betaFromKineticMeV(kineticA, massA),
        betaB: betaFromKineticMeV(kineticB, massB),
        betaNeutron: betaFromKineticMeV(neutronKineticMeV, ATOMIC_MASS_U.n),
    };
}

/**
 * Neutron population after g generations of a chain reaction: N_g = N0·k^g.
 * @param {number} N0
 * @param {number} k multiplication factor
 * @param {number} g generations (≥ 0)
 */
export function chainReactionPopulation(N0, k, g) {
    if (![N0, k, g].every(isNum) || k < 0 || g < 0) return NaN;
    return N0 * Math.pow(k, g);
}

/** Criticality of a reactor/assembly from k. */
export function criticality(k) {
    if (!isNum(k) || k < 0) return null;
    if (Math.abs(k - 1) < 1e-12) return 'critical';
    return k < 1 ? 'subcritical' : 'supercritical';
}

/**
 * Energy released by fissioning a mass of U-235: E = (m/M)·N_A·E_f.
 * @param {number} massKg [kg]
 * @param {number} [energyPerFissionMeV=200]
 * @param {number} [molarMass=0.235] [kg/mol]
 * @returns {number} [J]  (≈ 8.2·10¹³ J per kg)
 */
export function fissionEnergyForMass(massKg, energyPerFissionMeV = TYPICAL_FISSION_ENERGY_MEV, molarMass = 0.235) {
    if (!isNum(massKg) || massKg < 0 || !(molarMass > 0)) return NaN;
    return (massKg / molarMass) * AVOGADRO * energyPerFissionMeV * MEV_J;
}

// ─────────────────────────────────────────────────────────────────────────────
// X-ray (Bragg) diffraction
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Lattice constants a [m] of the reference crystals (room temperature):
 *  NaCl 564.02 pm, LiF 402.7 pm — literature values of the Leybold school X-ray apparatus
 *    leaflet P7.1.2.1 (https://www.physics.wisc.edu/courses/home/spring2020/407/experiments/xray/P7121_E.PDF);
 *  KCl 629.31 pm — Handbook of Mineralogy, sylvite (https://www.handbookofmineralogy.org/pdfs/sylvite.pdf);
 *  diamond 356.679 pm (APS/ANL lattice-constant table, 3.567 Å);
 *  Si 543.102 0511 pm — CODATA 2022 "lattice parameter of silicon" (22.5 °C, NIST).
 */
export const LATTICE_CONSTANTS = Object.freeze({
    nacl: 0.56402e-9,
    lif: 0.4027e-9,
    kcl: 0.62931e-9,
    diamond: 0.356679e-9,
    silicon: 0.5431020511e-9,
});

/**
 * Interplanar spacings d [m] used for Bragg reflection:
 * rock-salt crystals (NaCl, LiF, KCl) cleave along {100}, the first allowed reflection is
 * (200): d = a/2 (NaCl 0.28201 nm, LiF 0.20135 nm, KCl 0.31466 nm); diamond-cubic crystals
 * (C, Si) reflect first on (111): d = a/√3 (diamond 0.20593 nm, Si 0.31356 nm).
 */
export const CRYSTAL_SPACINGS = Object.freeze({
    nacl: { plane: '(200)', d: LATTICE_CONSTANTS.nacl / 2 },
    lif: { plane: '(200)', d: LATTICE_CONSTANTS.lif / 2 },
    kcl: { plane: '(200)', d: LATTICE_CONSTANTS.kcl / 2 },
    diamond: { plane: '(111)', d: LATTICE_CONSTANTS.diamond / Math.sqrt(3) },
    silicon: { plane: '(111)', d: LATTICE_CONSTANTS.silicon / Math.sqrt(3) },
});

/**
 * Characteristic X-ray lines [m] from the NIST X-ray Transition Energies database
 * (https://physics.nist.gov/PhysRefData/XrayTrans/Html/search.html, direct experimental
 * energies; λ = hc/E): Cu Kα1 8047.8227 eV, Kα2 8027.8416 eV, Kβ1 8905.413 eV;
 * Mo Kα1 17479.372 eV, Kα2 17374.29 eV, Kβ1 19608.34 eV. Kα is the intensity-weighted
 * mean (2·Kα1 + Kα2)/3 used when the doublet is not resolved (Cu 0.154 18 nm, Mo 0.071 07 nm;
 * school value Cu Kα = 0.154 nm).
 */
const xray = (eV) => (HC_EV_NM / eV) * 1e-9;
export const XRAY_LINES = Object.freeze({
    CuKa1: xray(8047.8227),
    CuKa2: xray(8027.8416),
    CuKa: (2 * xray(8047.8227) + xray(8027.8416)) / 3,
    CuKb: xray(8905.413),
    MoKa1: xray(17479.372),
    MoKa2: xray(17374.29),
    MoKa: (2 * xray(17479.372) + xray(17374.29)) / 3,
    MoKb: xray(19608.34),
});

/**
 * X-ray goniometer of a school X-ray apparatus (crystal/counter tube in θ–2θ coupling,
 * stepper motor, 0.1° angular steps; a peak is located to its step).
 */
export const XRAY_GONIOMETER = Object.freeze({ resolution: 0.1, sigmaAbs: 0.02, unit: '°', accuracy: '±0.1°' });

/**
 * Bragg glancing angle θ (between the beam and the lattice planes) from nλ = 2d·sin θ.
 * @param {number} n order (integer ≥ 1)
 * @param {number} wavelength λ [m]
 * @param {number} d spacing [m]
 * @returns {number|null} θ [rad], or null if nλ/(2d) > 1 (order does not exist) / invalid
 */
export function braggAngle(n, wavelength, d) {
    if (!isPosInt(n) || !(wavelength > 0) || !(d > 0)) return null;
    const s = (n * wavelength) / (2 * d);
    if (s > 1) return null;
    return Math.asin(s);
}

/** Highest existing order n_max = ⌊2d/λ⌋. */
export function maxBraggOrder(wavelength, d) {
    if (!(wavelength > 0) || !(d > 0)) return 0;
    return Math.floor((2 * d) / wavelength + 1e-12);
}

/**
 * All existing Bragg orders with glancing angle θ and deviation 2θ.
 * @returns {Array<{n:number, theta:number, twoTheta:number}>} angles in rad
 */
export function braggOrders(wavelength, d, limit = Infinity) {
    const out = [];
    const nMax = Math.min(maxBraggOrder(wavelength, d), limit);
    for (let n = 1; n <= nMax; n++) {
        const theta = braggAngle(n, wavelength, d);
        if (theta !== null) out.push({ n, theta, twoTheta: 2 * theta });
    }
    return out;
}

/** λ = 2d·sin θ / n. */
export function braggWavelength(n, d, theta) {
    if (!isPosInt(n) || !(d > 0) || !isNum(theta)) return NaN;
    return (2 * d * Math.sin(theta)) / n;
}

/** d = nλ / (2 sin θ). */
export function braggSpacing(n, wavelength, theta) {
    if (!isPosInt(n) || !(wavelength > 0) || !isNum(theta) || Math.sin(theta) <= 0) return NaN;
    return (n * wavelength) / (2 * Math.sin(theta));
}
