/**
 * Thermodynamics physics core for the virtual lab.
 *
 * Every function is pure and works in SI units unless the name/JSDoc says
 * otherwise (temperatures in kelvin for all absolute-temperature laws; a
 * temperature *difference* ΔT is the same in °C and K).
 *
 * Invalid physical input (non-finite numbers, non-positive absolute
 * temperatures, zero mass/volume/pressure, …) yields NaN instead of a
 * silently wrong number, so the UI can detect and guard it.
 *
 * Constants come from ./constants.js (CODATA 2022 / SI 2019, exact values);
 * the material tables below carry their sources (see the JSDoc of each table
 * and the "Termodinamika" section of the lab documentation).
 */
import { GAS_CONSTANT, STEFAN_BOLTZMANN, STANDARD_ATMOSPHERE, ZERO_CELSIUS } from './constants.js';
import { INSTRUMENTS } from './instruments.js';

/**
 * Molar gas constant R = N_A·k = 8.314 462 618… J/(mol·K) (exact since the 2019 SI;
 * CODATA 2022, https://physics.nist.gov/cgi-bin/cuu/Value?r). Was the rounded 8.314.
 */
export const R = GAS_CONSTANT;
/** Standard atmosphere [Pa] (exact). */
export const ATM = STANDARD_ATMOSPHERE;
/** Stefan–Boltzmann constant σ = 5.670 374 419…e−8 W/(m²·K⁴) (exact, https://physics.nist.gov/cgi-bin/cuu/Value?sigma). */
export const SIGMA = STEFAN_BOLTZMANN;
/** 0 °C expressed in kelvin (exact). */
export const ZERO_CELSIUS_IN_K = ZERO_CELSIUS;

// ─────────────────────────────────────────────────────────────────────────────
// Material tables (school-lab values; authoritative value and source in the comment)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Mean linear expansion coefficients α [1/K] for 20…100 °C.
 * Engineering ToolBox (https://www.engineeringtoolbox.com/linear-expansion-coefficients-d_95.html, 25 °C):
 * carbon steel 10.8–12.5, pure iron 12.0, aluminium 23, copper 16–16.7 (mean 20–100 °C ≈ 17,
 * CRC Handbook), plate glass 9.0 (all ×10⁻⁶ 1/K).
 */
export const LINEAR_EXPANSION = Object.freeze({
    steel: 12e-6,
    aluminium: 23e-6,
    copper: 17e-6,
    glass: 9e-6,
});

/**
 * Specific heat capacities c [J/(kg·K)] near room temperature.
 * Water: IAPWS-95 c_p = 4184 (20 °C) … 4216 (100 °C); mean over 20–100 °C ≈ 4188, the
 *   textbook 4186 (= 1 cal₁₅/(g·K)) is within 0.05 %
 *   (https://www.engineeringtoolbox.com/specific-heat-capacity-water-d_660.html).
 * Metals: CRC / Engineering ToolBox (https://www.engineeringtoolbox.com/specific-heat-metals-d_152.html):
 *   Al 897 (ToolBox 910), Fe 449–450, Cu 385 (ToolBox 390). Oil (vegetable/machine) ≈ 1.9–2.0 kJ/(kg·K).
 */
export const SPECIFIC_HEAT = Object.freeze({
    water: 4186,
    oil: 2000,
    iron: 450,
    copper: 385,
    aluminium: 897,
});

/**
 * Thermal conductivities k [W/(m·K)] near 20 °C.
 * Copper 385: commercial copper (ETP C11000 ≈ 390, pure 401;
 *   https://www.engineeringtoolbox.com/thermal-conductivity-metals-d_858.html);
 * aluminium 205: technical alloy (pure Al 237, alloys 6063 ≈ 200–210);
 * iron 80 (pure iron 80.2, CRC); glass 1.05 and wood 0.15 (pine) from
 *   https://www.engineeringtoolbox.com/thermal-conductivity-d_429.html.
 */
export const THERMAL_CONDUCTIVITY = Object.freeze({
    copper: 385,
    aluminium: 205,
    iron: 80,
    glass: 1.05,
    wood: 0.15,
});

/**
 * Instruments of the thermodynamics experiments (resolution, 1σ scatter of one reading;
 * readings are truncated at ±2.5σ, so |error| ≤ 2.5σ + resolution/2 — see instruments.js).
 */
export const THERMO_INSTRUMENTS = Object.freeze({
    /** Dial indicator on the expansion apparatus, 0.01 mm (value in mm). */
    dialIndicator: INSTRUMENTS.dialIndicator,
    /** Digital thermometer / thermocouple, 0.1 °C. */
    thermometer: INSTRUMENTS.digitalThermometer,
    /** Hand stopwatch, 0.01 s (+ reaction scatter). */
    stopwatch: INSTRUMENTS.handStopwatch,
    /** Graduated gas cylinder (piston position scale), 0.1 L divisions (value in L). */
    gasVolumeScale: Object.freeze({ resolution: 0.1, sigmaAbs: 0.02, unit: 'L', accuracy: '±0.1 L' }),
    /** Digital pressure gauge, 0.01 atm (value in atm). */
    manometer: Object.freeze({ resolution: 0.01, sigmaAbs: 0.002, unit: 'atm', accuracy: '±0.01 atm' }),
});

const isNum = (x) => typeof x === 'number' && Number.isFinite(x);
const allNum = (...xs) => xs.every(isNum);

/** Clamp x to [min, max]. */
export const clamp = (x, min, max) => Math.min(max, Math.max(min, x));

// ─────────────────────────────────────────────────────────────────────────────
// Temperature scales
// ─────────────────────────────────────────────────────────────────────────────

/**
 * T[K] = t[°C] + 273.15. Returns NaN below absolute zero.
 * @param {number} tC temperature in °C
 * @returns {number} temperature in K
 */
export function celsiusToKelvin(tC) {
    if (!isNum(tC)) return NaN;
    const T = tC + ZERO_CELSIUS_IN_K;
    return T < 0 ? NaN : T;
}

/**
 * t[°C] = T[K] − 273.15. Returns NaN for T < 0 K.
 * @param {number} TK temperature in K
 * @returns {number} temperature in °C
 */
export function kelvinToCelsius(TK) {
    if (!isNum(TK) || TK < 0) return NaN;
    return TK - ZERO_CELSIUS_IN_K;
}

// ─────────────────────────────────────────────────────────────────────────────
// Thermal expansion
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Linear expansion ΔL = α·L₀·ΔT.
 * @param {number} alpha linear expansion coefficient α [1/K]
 * @param {number} L0 initial length [m]
 * @param {number} dT temperature change [K] (= [°C])
 * @returns {number} ΔL [m]
 */
export function linearExpansion(alpha, L0, dT) {
    if (!allNum(alpha, L0, dT) || L0 < 0) return NaN;
    return alpha * L0 * dT;
}

/**
 * Length after heating L = L₀(1 + αΔT).
 * @param {number} alpha [1/K]
 * @param {number} L0 [m]
 * @param {number} dT [K]
 * @returns {number} L [m]
 */
export function expandedLength(alpha, L0, dT) {
    const dL = linearExpansion(alpha, L0, dT);
    return Number.isNaN(dL) ? NaN : L0 + dL;
}

/**
 * Volumetric expansion ΔV = β·V₀·ΔT.
 * @param {number} beta volumetric coefficient β [1/K]
 * @param {number} V0 initial volume [m³]
 * @param {number} dT [K]
 * @returns {number} ΔV [m³]
 */
export function volumetricExpansion(beta, V0, dT) {
    if (!allNum(beta, V0, dT) || V0 < 0) return NaN;
    return beta * V0 * dT;
}

/**
 * Isotropic solid: β ≈ 3α (first order in αΔT).
 * @param {number} alpha [1/K]
 * @returns {number} β [1/K]
 */
export function volumetricCoefficientFromLinear(alpha) {
    return isNum(alpha) ? 3 * alpha : NaN;
}

/**
 * Temperature under a constant-rate ramp, clamped to [min, max]:
 * T(t) = clamp(T_start + rate·t). Analytic in elapsed time, so it is
 * frame-rate independent.
 * @param {number} start temperature at t = 0
 * @param {number} rate signed rate [unit/s]
 * @param {number} elapsed elapsed time t [s] (negative treated as 0)
 * @param {number} min lower clamp
 * @param {number} max upper clamp
 * @returns {number}
 */
export function rampTemperature(start, rate, elapsed, min, max) {
    if (!allNum(start, rate, elapsed, min, max)) return NaN;
    return clamp(start + rate * Math.max(0, elapsed), min, max);
}

// ─────────────────────────────────────────────────────────────────────────────
// Sensible heat / specific heat
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Sensible heat Q = m·c·ΔT.
 * @param {number} m mass [kg]
 * @param {number} c specific heat [J/(kg·K)]
 * @param {number} dT [K]
 * @returns {number} Q [J]
 */
export function sensibleHeat(m, c, dT) {
    if (!allNum(m, c, dT) || m < 0 || c < 0) return NaN;
    return m * c * dT;
}

/**
 * Temperature change from absorbed heat ΔT = Q / (m·c).
 * @param {number} Q [J]
 * @param {number} m [kg] (> 0)
 * @param {number} c [J/(kg·K)] (> 0)
 * @returns {number} ΔT [K]
 */
export function temperatureChange(Q, m, c) {
    if (!allNum(Q, m, c) || m <= 0 || c <= 0) return NaN;
    return Q / (m * c);
}

/**
 * Specific heat from a measurement c = Q / (m·ΔT).
 * @param {number} Q [J]
 * @param {number} m [kg] (> 0)
 * @param {number} dT [K] (≠ 0)
 * @returns {number} c [J/(kg·K)]
 */
export function specificHeatFromMeasurement(Q, m, dT) {
    if (!allNum(Q, m, dT) || m <= 0 || dT === 0) return NaN;
    return Q / (m * dT);
}

/**
 * Time a heater of constant power needs to deliver heat Q (no losses): t = Q / P.
 * @param {number} Q heat [J] (≥ 0)
 * @param {number} power heater power [W] (> 0)
 * @returns {number} t [s]
 */
export function heatingTime(Q, power) {
    if (!allNum(Q, power) || Q < 0 || power <= 0) return NaN;
    return Q / power;
}

/**
 * Body heated by a constant power P with no losses, stopped at Tmax.
 * Exact solution: Q(t) = P·t, T(t) = T₀ + P·t/(m·c), valid until
 * t_max = m·c·(Tmax − T₀)/P, after which the heater is off.
 * @param {{T0:number, power:number, mass:number, c:number, elapsed:number, Tmax?:number}} p
 * @returns {{T:number, Q:number, t:number, tMax:number, done:boolean}}
 *   t is the effective heating time (≤ tMax).
 */
export function constantPowerHeating({ T0, power, mass, c, elapsed, Tmax = Infinity }) {
    const bad = { T: NaN, Q: NaN, t: NaN, tMax: NaN, done: false };
    if (!allNum(T0, power, mass, c, elapsed) || mass <= 0 || c <= 0 || power < 0) return bad;
    if (!(Tmax >= T0)) return bad;
    const tMax = power > 0 ? (mass * c * (Tmax - T0)) / power : Infinity;
    const t = Math.min(Math.max(0, elapsed), tMax);
    const Q = power * t;
    const done = Number.isFinite(tMax) && elapsed >= tMax;
    // On completion return Tmax exactly (avoid 99.99999 from round-off).
    const T = done ? Tmax : T0 + Q / (mass * c);
    return { T, Q, t, tMax, done };
}

// ─────────────────────────────────────────────────────────────────────────────
// Latent heat & heating curve
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Latent heat Q = m·L.
 * @param {number} m [kg]
 * @param {number} L specific latent heat [J/kg]
 * @returns {number} Q [J]
 */
export function latentHeat(m, L) {
    if (!allNum(m, L) || m < 0 || L < 0) return NaN;
    return m * L;
}

/**
 * Water properties used by the phase-change experiment (SI) — the standard school
 * textbook values; authoritative values for comparison:
 *  - ice c_p: 2.050 (0 °C), 2.000 (−10 °C), 1.943 (−20 °C) kJ/(kg·K), mean −20…0 °C ≈ 2.00
 *    (https://www.engineeringtoolbox.com/ice-thermal-properties-d_576.html) — textbook 2100;
 *  - fusion: 333.55 kJ/kg (IAPWS), Engineering ToolBox 334 kJ/kg
 *    (https://www.engineeringtoolbox.com/latent-heat-melting-solids-d_96.html);
 *  - vaporisation at 100 °C: 2256.4 kJ/kg (IAPWS-95,
 *    https://www.engineeringtoolbox.com/water-properties-d_1573.html) — textbook 2260 (+0.16 %);
 *  - steam c_p at 1 atm, 100–150 °C ≈ 2.0 kJ/(kg·K).
 */
export const WATER = Object.freeze({
    Tmelt: 0,          // °C
    Tboil: 100,        // °C (1 atm; 99.97 °C on ITS-90)
    cSolid: 2100,      // ice  J/(kg·K)
    cLiquid: SPECIFIC_HEAT.water, // water J/(kg·K)
    cGas: 2000,        // steam J/(kg·K)
    Lf: 334000,        // J/kg
    Lv: 2260000,       // J/kg
});

/**
 * Cumulative heat at which each segment of the heating curve ends,
 * starting from T₀ (°C or K, consistent with Tmelt/Tboil).
 *   solidEnd = m·c_s·(T_m − T₀)        (0 if not solid at start)
 *   meltEnd  = solidEnd + m·L_f
 *   liquidEnd= meltEnd  + m·c_l·(T_b − max(T₀,T_m))
 *   boilEnd  = liquidEnd + m·L_v
 * Phases already passed at T₀ contribute 0.
 * @param {{mass:number, T0:number, Tmelt:number, Tboil:number, cSolid:number, cLiquid:number, cGas:number, Lf:number, Lv:number}} p
 * @returns {{solidEnd:number, meltEnd:number, liquidEnd:number, boilEnd:number}}
 */
export function heatingCurveBreakpoints(p) {
    const { mass: m, T0, Tmelt, Tboil, cSolid, cLiquid, Lf, Lv } = p;
    if (!allNum(m, T0, Tmelt, Tboil, cSolid, cLiquid, Lf, Lv) || m <= 0 || Tboil < Tmelt) {
        return { solidEnd: NaN, meltEnd: NaN, liquidEnd: NaN, boilEnd: NaN };
    }
    const startsSolid = T0 <= Tmelt;
    const startsLiquid = !startsSolid && T0 <= Tboil;
    const solidEnd = startsSolid ? m * cSolid * (Tmelt - T0) : 0;
    const meltEnd = solidEnd + (startsSolid ? m * Lf : 0);
    const liquidEnd = meltEnd + (startsSolid || startsLiquid ? m * cLiquid * (Tboil - Math.max(T0, Tmelt)) : 0);
    const boilEnd = liquidEnd + (startsSolid || startsLiquid ? m * Lv : 0);
    return { solidEnd, meltEnd, liquidEnd, boilEnd };
}

/**
 * Piecewise-analytic heating curve T(Q) for a pure substance at constant
 * pressure: linear segments ΔT = Q/(m·c) in each single phase, and constant-T
 * plateaus while the latent heat m·L is absorbed.
 * @param {number} Q heat delivered since T₀ [J] (negative treated as 0)
 * @param {object} p see heatingCurveBreakpoints (+ cGas)
 * @returns {{T:number, phase:'solid'|'melting'|'liquid'|'boiling'|'gas', meltedFraction:number, vaporizedFraction:number}}
 */
export function heatingCurve(Q, p) {
    const bp = heatingCurveBreakpoints(p);
    if (!isNum(Q) || Number.isNaN(bp.boilEnd) || !isNum(p.cGas) || p.cGas <= 0) {
        return { T: NaN, phase: 'solid', meltedFraction: NaN, vaporizedFraction: NaN };
    }
    const { mass: m, T0, Tmelt, Tboil, cSolid, cLiquid, cGas, Lf, Lv } = p;
    const q = Math.max(0, Q);
    const startsGas = T0 > Tboil;
    if (startsGas) {
        return { T: T0 + q / (m * cGas), phase: 'gas', meltedFraction: 1, vaporizedFraction: 1 };
    }
    if (q < bp.solidEnd) {
        return { T: T0 + q / (m * cSolid), phase: 'solid', meltedFraction: 0, vaporizedFraction: 0 };
    }
    if (q < bp.meltEnd) {
        return { T: Tmelt, phase: 'melting', meltedFraction: (q - bp.solidEnd) / (m * Lf), vaporizedFraction: 0 };
    }
    if (q < bp.liquidEnd) {
        const Tl0 = Math.max(T0, Tmelt);
        return { T: Tl0 + (q - bp.meltEnd) / (m * cLiquid), phase: 'liquid', meltedFraction: 1, vaporizedFraction: 0 };
    }
    if (q < bp.boilEnd) {
        return { T: Tboil, phase: 'boiling', meltedFraction: 1, vaporizedFraction: (q - bp.liquidEnd) / (m * Lv) };
    }
    return { T: Tboil + (q - bp.boilEnd) / (m * cGas), phase: 'gas', meltedFraction: 1, vaporizedFraction: 1 };
}

/**
 * Inverse of the heating curve: the minimum heat needed to bring the sample
 * from T₀ to temperature T (at a transition temperature this is the heat at
 * the *start* of the plateau). Returns 0 for T ≤ T₀.
 * @param {number} T target temperature
 * @param {object} p see heatingCurve
 * @returns {number} Q [J]
 */
export function heatingCurveHeatFor(T, p) {
    const bp = heatingCurveBreakpoints(p);
    if (!isNum(T) || Number.isNaN(bp.boilEnd) || !isNum(p.cGas)) return NaN;
    const { mass: m, T0, Tmelt, Tboil, cSolid, cLiquid, cGas } = p;
    if (T <= T0) return 0;
    if (T0 > Tboil) return m * cGas * (T - T0);
    if (T <= Tmelt) return m * cSolid * (T - T0);
    if (T <= Tboil) return bp.meltEnd + m * cLiquid * (T - Math.max(T0, Tmelt));
    return bp.boilEnd + m * cGas * (T - Tboil);
}

// ─────────────────────────────────────────────────────────────────────────────
// Calorimetry
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Thermal equilibrium of bodies exchanging heat only with each other
 * (no phase change): Σ mᵢcᵢ(T_f − Tᵢ) = 0  ⇒  T_f = Σ mᵢcᵢTᵢ / Σ mᵢcᵢ.
 * Any consistent mass unit / temperature scale works (it cancels).
 * @param {{m:number, c:number, T:number}[]} bodies
 * @returns {number} T_f
 */
export function equilibriumTemperature(bodies) {
    if (!Array.isArray(bodies) || bodies.length === 0) return NaN;
    let num = 0;
    let den = 0;
    for (const b of bodies) {
        if (!b || !allNum(b.m, b.c, b.T) || b.m < 0 || b.c < 0) return NaN;
        num += b.m * b.c * b.T;
        den += b.m * b.c;
    }
    return den > 0 ? num / den : NaN;
}

/**
 * Heat given off by a body cooling from T to T_f: Q = m·c·(T − T_f)
 * (negative if the body warms up).
 * @param {number} m [kg]
 * @param {number} c [J/(kg·K)]
 * @param {number} T initial temperature
 * @param {number} Tf final temperature
 * @returns {number} Q [J]
 */
export function heatReleased(m, c, T, Tf) {
    return sensibleHeat(m, c, T - Tf);
}

// ─────────────────────────────────────────────────────────────────────────────
// Ideal gas
// ─────────────────────────────────────────────────────────────────────────────

/**
 * P = nRT / V.
 * @param {number} n [mol] (≥ 0)
 * @param {number} T [K] (> 0)
 * @param {number} V [m³] (> 0)
 * @returns {number} P [Pa]
 */
export function idealGasPressure(n, T, V) {
    if (!allNum(n, T, V) || n < 0 || T <= 0 || V <= 0) return NaN;
    return (n * R * T) / V;
}

/**
 * V = nRT / P.
 * @param {number} n [mol]
 * @param {number} T [K] (> 0)
 * @param {number} P [Pa] (> 0)
 * @returns {number} V [m³]
 */
export function idealGasVolume(n, T, P) {
    if (!allNum(n, T, P) || n < 0 || T <= 0 || P <= 0) return NaN;
    return (n * R * T) / P;
}

/**
 * T = PV / (nR).
 * @param {number} P [Pa]
 * @param {number} V [m³]
 * @param {number} n [mol] (> 0)
 * @returns {number} T [K]
 */
export function idealGasTemperature(P, V, n) {
    if (!allNum(P, V, n) || P <= 0 || V <= 0 || n <= 0) return NaN;
    return (P * V) / (n * R);
}

/**
 * Root-mean-square molecular speed v_rms = √(3RT/M).
 * @param {number} T [K]
 * @param {number} M molar mass [kg/mol]
 * @returns {number} [m/s]
 */
export function rmsSpeed(T, M) {
    if (!allNum(T, M) || T < 0 || M <= 0) return NaN;
    return Math.sqrt((3 * R * T) / M);
}

/**
 * Reversible isothermal work done BY the gas: W = nRT·ln(V₂/V₁).
 * @param {number} n [mol]
 * @param {number} T [K]
 * @param {number} V1 [m³]
 * @param {number} V2 [m³]
 * @returns {number} W [J]
 */
export function isothermalWork(n, T, V1, V2) {
    if (!allNum(n, T, V1, V2) || n < 0 || T <= 0 || V1 <= 0 || V2 <= 0) return NaN;
    return n * R * T * Math.log(V2 / V1);
}

/**
 * Reversible adiabatic work done BY the gas: W = (P₁V₁ − P₂V₂)/(γ − 1)
 * (= n·C_v·(T₁ − T₂)).
 * @param {number} P1 [Pa]
 * @param {number} V1 [m³]
 * @param {number} P2 [Pa]
 * @param {number} V2 [m³]
 * @param {number} gamma Cp/Cv (> 1)
 * @returns {number} W [J]
 */
export function adiabaticWork(P1, V1, P2, V2, gamma) {
    if (!allNum(P1, V1, P2, V2, gamma) || gamma <= 1) return NaN;
    return (P1 * V1 - P2 * V2) / (gamma - 1);
}

/**
 * Adiabat T·V^(γ−1) = const ⇒ V₂ = V₁·(T₁/T₂)^(1/(γ−1)).
 * @param {number} V1 [m³]
 * @param {number} T1 [K]
 * @param {number} T2 [K]
 * @param {number} gamma
 * @returns {number} V₂ [m³]
 */
export function adiabaticVolume(V1, T1, T2, gamma) {
    if (!allNum(V1, T1, T2, gamma) || V1 <= 0 || T1 <= 0 || T2 <= 0 || gamma <= 1) return NaN;
    return V1 * Math.pow(T1 / T2, 1 / (gamma - 1));
}

/**
 * Adiabat T·V^(γ−1) = const ⇒ T₂ = T₁·(V₁/V₂)^(γ−1).
 * @param {number} T1 [K]
 * @param {number} V1 [m³]
 * @param {number} V2 [m³]
 * @param {number} gamma
 * @returns {number} T₂ [K]
 */
export function adiabaticTemperature(T1, V1, V2, gamma) {
    if (!allNum(T1, V1, V2, gamma) || T1 <= 0 || V1 <= 0 || V2 <= 0 || gamma <= 1) return NaN;
    return T1 * Math.pow(V1 / V2, gamma - 1);
}

// ─────────────────────────────────────────────────────────────────────────────
// Heat transfer
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Fourier conduction heat flux through a slab: q = k·ΔT / d.
 * @param {number} k thermal conductivity [W/(m·K)]
 * @param {number} dT temperature difference T_hot − T_cold [K]
 * @param {number} d thickness / length [m] (> 0)
 * @returns {number} q [W/m²]
 */
export function conductionHeatFlux(k, dT, d) {
    if (!allNum(k, dT, d) || k < 0 || d <= 0) return NaN;
    return (k * dT) / d;
}

/**
 * Fourier conduction power P = k·A·ΔT / d.
 * @param {number} k [W/(m·K)]
 * @param {number} A cross-section [m²]
 * @param {number} dT [K]
 * @param {number} d [m]
 * @returns {number} P [W]
 */
export function conductionPower(k, A, dT, d) {
    const q = conductionHeatFlux(k, dT, d);
    if (Number.isNaN(q) || !isNum(A) || A < 0) return NaN;
    return q * A;
}

/**
 * Steady-state temperature profile in a uniform slab (linear):
 * T(x) = T_hot − (T_hot − T_cold)·x/d, x clamped to [0, d].
 * @param {number} x position from the hot face [m]
 * @param {number} Thot
 * @param {number} Tcold
 * @param {number} d [m]
 * @returns {number}
 */
export function conductionTemperatureAt(x, Thot, Tcold, d) {
    if (!allNum(x, Thot, Tcold, d) || d <= 0) return NaN;
    return Thot - ((Thot - Tcold) * clamp(x, 0, d)) / d;
}

/**
 * Net radiative flux of a grey surface at T exchanging with surroundings at
 * T₀ (both in KELVIN): q = ε·σ·(T⁴ − T₀⁴).
 * @param {number} emissivity ε ∈ [0, 1]
 * @param {number} TK [K]
 * @param {number} T0K [K]
 * @returns {number} q [W/m²]
 */
export function radiationNetFlux(emissivity, TK, T0K) {
    if (!allNum(emissivity, TK, T0K) || emissivity < 0 || emissivity > 1 || TK < 0 || T0K < 0) return NaN;
    return emissivity * SIGMA * (TK ** 4 - T0K ** 4);
}

/**
 * Net radiated power P = ε·σ·A·(T⁴ − T₀⁴), temperatures in kelvin.
 * @param {number} emissivity
 * @param {number} A [m²]
 * @param {number} TK [K]
 * @param {number} T0K [K]
 * @returns {number} P [W]
 */
export function radiationNetPower(emissivity, A, TK, T0K) {
    const q = radiationNetFlux(emissivity, TK, T0K);
    if (Number.isNaN(q) || !isNum(A) || A < 0) return NaN;
    return q * A;
}

// ─────────────────────────────────────────────────────────────────────────────
// Carnot cycle
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Carnot efficiency η = 1 − T_c/T_h (temperatures in KELVIN).
 * NaN unless 0 < T_c ≤ T_h.
 * @param {number} Th [K]
 * @param {number} Tc [K]
 * @returns {number} η ∈ [0, 1)
 */
export function carnotEfficiency(Th, Tc) {
    if (!allNum(Th, Tc) || Th <= 0 || Tc <= 0 || Tc > Th) return NaN;
    return 1 - Tc / Th;
}

/**
 * Full reversible Carnot cycle of n mol of ideal gas.
 *   1→2 isothermal expansion at T_h (V₁→V₂): Q_h = nRT_h·ln(V₂/V₁)
 *   2→3 adiabatic expansion: V₃ = V₂·(T_h/T_c)^(1/(γ−1)), W₂₃ = nC_v(T_h−T_c)
 *   3→4 isothermal compression at T_c: V₄ = V₁·(T_h/T_c)^(1/(γ−1)),
 *       Q_c = nRT_c·ln(V₃/V₄) = nRT_c·ln(V₂/V₁)
 *   4→1 adiabatic compression: W₄₁ = −W₂₃
 *   W = Q_h − Q_c,  η = W/Q_h = 1 − T_c/T_h.
 * @param {{n?:number, Th:number, Tc:number, V1:number, V2:number, gamma?:number}} p volumes in m³
 * @returns {null|{states:{P:number,V:number,T:number}[], Qh:number, Qc:number, W:number, efficiency:number,
 *   works:{w12:number,w23:number,w34:number,w41:number}, n:number, gamma:number}}
 */
export function carnotCycle({ n = 1, Th, Tc, V1, V2, gamma = 5 / 3 }) {
    if (!allNum(n, Th, Tc, V1, V2, gamma) || n <= 0 || V1 <= 0 || V2 <= V1 || gamma <= 1) return null;
    const eta = carnotEfficiency(Th, Tc);
    if (Number.isNaN(eta)) return null;
    const V3 = adiabaticVolume(V2, Th, Tc, gamma);
    const V4 = adiabaticVolume(V1, Th, Tc, gamma);
    const states = [
        { V: V1, T: Th, P: idealGasPressure(n, Th, V1) },
        { V: V2, T: Th, P: idealGasPressure(n, Th, V2) },
        { V: V3, T: Tc, P: idealGasPressure(n, Tc, V3) },
        { V: V4, T: Tc, P: idealGasPressure(n, Tc, V4) },
    ];
    const w12 = isothermalWork(n, Th, V1, V2);
    const w23 = adiabaticWork(states[1].P, V2, states[2].P, V3, gamma);
    const w34 = isothermalWork(n, Tc, V3, V4);
    const w41 = adiabaticWork(states[3].P, V4, states[0].P, V1, gamma);
    const Qh = w12;
    const Qc = -w34;
    return {
        states,
        Qh,
        Qc,
        W: w12 + w23 + w34 + w41,
        efficiency: eta,
        works: { w12, w23, w34, w41 },
        n,
        gamma,
    };
}

/**
 * State on the Carnot cycle during stage `stage` (0: 1→2, 1: 2→3, 2: 3→4,
 * 3: 4→1) at progress s ∈ [0, 1]. V is interpolated linearly; T follows the
 * isotherm (T const) or the adiabat T = T_a·(V_a/V)^(γ−1); P = nRT/V.
 * @param {ReturnType<typeof carnotCycle>} cycle
 * @param {number} stage 0..3
 * @param {number} s progress 0..1
 * @returns {{P:number, V:number, T:number}}
 */
export function carnotStateAt(cycle, stage, s) {
    if (!cycle || !isNum(stage) || !isNum(s)) return { P: NaN, V: NaN, T: NaN };
    const i = ((Math.floor(stage) % 4) + 4) % 4;
    const a = cycle.states[i];
    const b = cycle.states[(i + 1) % 4];
    const u = clamp(s, 0, 1);
    const V = a.V + (b.V - a.V) * u;
    const T = i % 2 === 0 ? a.T : adiabaticTemperature(a.T, a.V, V, cycle.gamma);
    return { V, T, P: idealGasPressure(cycle.n, T, V) };
}

// ─────────────────────────────────────────────────────────────────────────────
// Entropy
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Reversible isothermal heat exchange: ΔS = Q / T (T in KELVIN).
 * @param {number} Q [J]
 * @param {number} T [K] (> 0)
 * @returns {number} ΔS [J/K]
 */
export function entropyIsothermal(Q, T) {
    if (!allNum(Q, T) || T <= 0) return NaN;
    return Q / T;
}

/**
 * Entropy change when heating at constant heat capacity C from T₁ to T₂
 * (kelvin): ΔS = C·ln(T₂/T₁)  (C = m·c or n·C_p, …).
 * @param {number} C heat capacity [J/K] (≥ 0)
 * @param {number} T1 [K]
 * @param {number} T2 [K]
 * @returns {number} ΔS [J/K]
 */
export function entropyHeating(C, T1, T2) {
    if (!allNum(C, T1, T2) || C < 0 || T1 <= 0 || T2 <= 0) return NaN;
    return C * Math.log(T2 / T1);
}

/**
 * Isobaric heating of n mol of ideal gas by heat Q:
 *   T₂ = T₁ + Q/(n·C_p),  ΔS = n·C_p·ln(T₂/T₁)  (note ΔS < Q/T₁ for Q > 0).
 * @param {number} n [mol]
 * @param {number} Cp molar heat capacity at constant pressure [J/(mol·K)]
 * @param {number} T1 [K]
 * @param {number} Q [J]
 * @returns {{T2:number, dS:number}}
 */
export function entropyIsobaricHeating(n, Cp, T1, Q) {
    if (!allNum(n, Cp, T1, Q) || n <= 0 || Cp <= 0 || T1 <= 0) return { T2: NaN, dS: NaN };
    const T2 = T1 + Q / (n * Cp);
    if (T2 <= 0) return { T2: NaN, dS: NaN };
    return { T2, dS: entropyHeating(n * Cp, T1, T2) };
}

/**
 * Temperature along an isobar in the T–S plane: T(ΔS) = T₁·exp(ΔS/(n·C_p)).
 * @param {number} T1 [K]
 * @param {number} dS entropy gained since state 1 [J/K]
 * @param {number} n [mol]
 * @param {number} Cp [J/(mol·K)]
 * @returns {number} T [K]
 */
export function isobarTemperatureAtEntropy(T1, dS, n, Cp) {
    if (!allNum(T1, dS, n, Cp) || T1 <= 0 || n <= 0 || Cp <= 0) return NaN;
    return T1 * Math.exp(dS / (n * Cp));
}
