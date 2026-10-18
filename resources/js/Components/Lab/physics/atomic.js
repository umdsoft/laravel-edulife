/**
 * Atomic & nuclear physics for the virtual lab (pure functions).
 *
 * Units: SI unless the name says otherwise (…EV = electron-volt, …Nm = nanometre,
 * …MeV, masses in unified atomic mass units u where stated).
 * Invalid inputs return NaN / null instead of throwing.
 */

/** Planck constant h [J·s] (exact, SI 2019). */
export const PLANCK = 6.62607015e-34;
/** Speed of light c [m/s] (exact). */
export const SPEED_OF_LIGHT = 299792458;
/** Elementary charge e [C] (exact). */
export const ELEMENTARY_CHARGE = 1.602176634e-19;
/** Electron mass mₑ [kg] (CODATA 2018). */
export const ELECTRON_MASS = 9.1093837015e-31;
/** Planck constant in eV·s: h/e ≈ 4.1357·10⁻¹⁵. */
export const PLANCK_EV = PLANCK / ELEMENTARY_CHARGE;
/** hc in eV·nm ≈ 1239.84. */
export const HC_EV_NM = (PLANCK * SPEED_OF_LIGHT) / ELEMENTARY_CHARGE * 1e9;
/** Rydberg constant used in school textbooks, R = 1.097·10⁷ m⁻¹ (R∞ = 1.0973731568·10⁷). */
export const RYDBERG = 1.097e7;
/** Hydrogen ground-state binding energy used with the Bohr model [eV]. */
export const RYDBERG_ENERGY_EV = 13.6;
/** Bohr radius a0 [m] (0.529 Å). */
export const BOHR_RADIUS = 5.29177210903e-11;
/** Fine-structure constant α. */
export const FINE_STRUCTURE = 7.2973525693e-3;
/** Energy equivalent of 1 u: 931.494 MeV. */
export const AMU_MEV = 931.49410242;
/** 1 MeV in joules. */
export const MEV_J = 1e6 * ELEMENTARY_CHARGE;
/** Avogadro constant [1/mol] (exact). */
export const AVOGADRO = 6.02214076e23;

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

/** Typical work functions φ [eV] of clean metal surfaces (CRC Handbook, polycrystalline). */
export const WORK_FUNCTIONS_EV = Object.freeze({
    Cs: 2.14,
    K: 2.29,
    Na: 2.36,
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
 * Energy of level n: Eₙ = −13.6·Z²/n² eV.
 * @param {number} n principal quantum number (integer ≥ 1)
 * @param {number} [Z=1] nuclear charge
 */
export function bohrEnergyEV(n, Z = 1) {
    if (!isPosInt(n) || !(Z > 0)) return NaN;
    return (-RYDBERG_ENERGY_EV * Z * Z) / (n * n);
}

/** Orbit radius rₙ = a0·n²/Z [m]. */
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
 * Rydberg formula 1/λ = R·Z²·(1/n1² − 1/n2²) for n2 > n1 ≥ 1.
 * @param {number} n1 lower level
 * @param {number} n2 upper level
 * @param {number} [Z=1]
 * @returns {number} λ [m], NaN if n1 ≥ n2 or invalid
 */
export function rydbergWavelength(n1, n2, Z = 1) {
    if (!isPosInt(n1) || !isPosInt(n2) || n2 <= n1 || !(Z > 0)) return NaN;
    const inv = RYDBERG * Z * Z * (1 / (n1 * n1) - 1 / (n2 * n2));
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
 * Balmer lines Hα, Hβ, … (n → 2) from the Rydberg formula.
 * @param {number} [nMax=6]
 * @returns {Array<{name:string, n:number, wavelengthNm:number}>}
 */
export function balmerLines(nMax = 6) {
    const greek = ['α', 'β', 'γ', 'δ', 'ε', 'ζ', 'η'];
    const out = [];
    for (let n = 3; n <= nMax; n++) {
        out.push({ name: `H${greek[n - 3] ?? n}`, n, wavelengthNm: rydbergWavelength(2, n) * 1e9 });
    }
    return out;
}

/**
 * Reference visible emission lines [nm, in air] (NIST Atomic Spectra Database, strong lines).
 * Hydrogen is computed with {@link balmerLines}.
 */
export const EMISSION_LINES_NM = Object.freeze({
    helium: [
        { wavelength: 667.8, name: '' },
        { wavelength: 587.6, name: 'D3' },
        { wavelength: 501.6, name: '' },
        { wavelength: 492.2, name: '' },
        { wavelength: 471.3, name: '' },
        { wavelength: 447.1, name: '' },
    ],
    sodium: [
        { wavelength: 589.6, name: 'D1' },
        { wavelength: 589.0, name: 'D2' },
    ],
    mercury: [
        { wavelength: 579.1, name: '' },
        { wavelength: 577.0, name: '' },
        { wavelength: 546.1, name: '' },
        { wavelength: 435.8, name: '' },
        { wavelength: 404.7, name: '' },
    ],
});

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

// ─────────────────────────────────────────────────────────────────────────────
// Nuclear fission
// ─────────────────────────────────────────────────────────────────────────────

/** Atomic masses [u] (AME2020). */
export const ATOMIC_MASS_U = Object.freeze({
    n: 1.00866491595,
    U235: 235.0439281,
    Ba141: 140.9144033,
    Kr92: 91.9261731,
});

/** Typical total energy released per U-235 fission (incl. delayed β/γ) ≈ 200 MeV. */
export const TYPICAL_FISSION_ENERGY_MEV = 200;

/** Mean number of prompt neutrons per thermal U-235 fission, ν ≈ 2.43. */
export const NEUTRONS_PER_FISSION_U235 = 2.43;

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
 * Interplanar spacings d [m] of common reference crystals:
 * NaCl (200) = a/2, a = 0.5640 nm; diamond (111) = a/√3, a = 0.35668 nm;
 * Si (111) = a/√3, a = 0.543102 nm.
 */
export const CRYSTAL_SPACINGS = Object.freeze({
    nacl: { plane: '(200)', d: 0.2820e-9 },
    diamond: { plane: '(111)', d: 0.35668e-9 / Math.sqrt(3) },
    silicon: { plane: '(111)', d: 0.543102e-9 / Math.sqrt(3) },
});

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
