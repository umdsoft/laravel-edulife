/**
 * Waves physics — pure, framework-free functions used by the Waves lab
 * simulations (WaveBasics, Doppler, SoundWaves, StandingWaves, Resonance,
 * Beats, DoubleSlit, WaveInterference, EMSpectrum).
 *
 * Conventions
 *  - SI units in and out (m, s, Hz, N, kg, W, J) unless a name says otherwise
 *    (e.g. `photonEnergyEV`, `wavelengthToRgb(nm)`).
 *  - Angles are in radians.
 *  - Invalid physical input (non-finite numbers, zero/negative where the
 *    quantity must be positive) yields NaN instead of throwing, so the UI can
 *    show "—" rather than a wrong number. Limits that are physically meaningful
 *    (e.g. period of a 0 Hz wave) return Infinity.
 *  - Everything is a closed-form function of time `t`; nothing accumulates
 *    per animation frame, so results do not depend on the frame rate.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Physical constants — from ./constants.js (CODATA 2022, NIST; c, h, e, R exact
// since the 2019 SI). Re-exported under the old names for existing importers.
// ─────────────────────────────────────────────────────────────────────────────

import {
    SPEED_OF_LIGHT,
    PLANCK,
    ELEMENTARY_CHARGE,
    GAS_CONSTANT,
    ZERO_CELSIUS,
} from './constants.js';
import { INSTRUMENTS } from './instruments.js';

export { SPEED_OF_LIGHT, PLANCK, ELEMENTARY_CHARGE, GAS_CONSTANT };

/** Molar mass of dry air M, kg/mol (28.9647 g/mol, U.S. Standard Atmosphere 1976). */
export const MOLAR_MASS_AIR = 0.0289647;
/** Adiabatic index of air γ (diatomic ideal gas; 1.401 for real dry air at 20 °C). */
export const GAMMA_AIR = 1.4;
/** Threshold of hearing I₀, W/m² (reference for sound intensity level). */
export const HEARING_THRESHOLD = 1e-12;
/**
 * Speed of sound in air at 20 °C used by the lab, m/s. Reference values:
 * √(γRT/M) = 343.2 m/s (ideal gas, 293.15 K); Cramer (JASA 93, 2510, 1993):
 * 343.36 m/s dry air, 101.325 kPa, 400 ppm CO₂ (344.0 m/s at 50 % RH).
 * 343 m/s is the textbook value; it is 0.1 % below Cramer — far inside the
 * 1 % task tolerance.
 */
export const SOUND_SPEED_AIR = 343;
/** Temperature (°C) at which SOUND_SPEED_AIR holds. */
export const SOUND_SPEED_REF_TEMP_C = 20;

const TWO_PI = 2 * Math.PI;
const isNum = (x) => typeof x === 'number' && Number.isFinite(x);
const isPos = (x) => isNum(x) && x > 0;

// ─────────────────────────────────────────────────────────────────────────────
// Simulation clock (frame-rate independent elapsed time)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Advance a simulation clock from a requestAnimationFrame timestamp.
 * The returned `t` is real elapsed (unpaused) time in seconds, so physics
 * evaluated as f(t) runs at the same speed at 30, 60 or 144 fps.
 * A single step is capped at `maxStepS` so a backgrounded tab does not jump.
 *
 * @param {{t:number,lastMs:number|null}} clock previous clock state
 * @param {number} nowMs  current timestamp (ms), e.g. rAF argument
 * @param {boolean} running whether time should advance
 * @param {number} [maxStepS=0.1] largest single step, s
 * @returns {{t:number,lastMs:number}} new clock state
 */
export function advanceClock(clock, nowMs, running, maxStepS = 0.1) {
    const t = isNum(clock?.t) ? clock.t : 0;
    const last = clock?.lastMs;
    let next = t;
    if (running && isNum(last) && isNum(nowMs)) {
        const dt = (nowMs - last) / 1000;
        if (dt > 0) next = t + Math.min(dt, maxStepS);
    }
    return { t: next, lastMs: isNum(nowMs) ? nowMs : last ?? null };
}

// ─────────────────────────────────────────────────────────────────────────────
// Basic wave kinematics
// ─────────────────────────────────────────────────────────────────────────────

/** Wave speed v = f·λ. @param {number} f Hz @param {number} lambda m @returns {number} m/s */
export function waveSpeed(f, lambda) {
    if (!isNum(f) || !isNum(lambda) || f < 0 || lambda < 0) return NaN;
    return f * lambda;
}

/** Wavelength λ = v / f. @param {number} v m/s @param {number} f Hz @returns {number} m */
export function wavelengthFrom(v, f) {
    if (!isPos(v) || !isPos(f)) return NaN;
    return v / f;
}

/** Frequency f = v / λ. @param {number} v m/s @param {number} lambda m @returns {number} Hz */
export function frequencyFrom(v, lambda) {
    if (!isPos(v) || !isPos(lambda)) return NaN;
    return v / lambda;
}

/** Period T = 1 / f (Infinity for f = 0). @param {number} f Hz @returns {number} s */
export function period(f) {
    if (!isNum(f) || f < 0) return NaN;
    return f === 0 ? Infinity : 1 / f;
}

/** Angular frequency ω = 2πf. @param {number} f Hz @returns {number} rad/s */
export function angularFrequency(f) {
    return isNum(f) ? TWO_PI * f : NaN;
}

/** Angular wave number k = 2π/λ. @param {number} lambda m @returns {number} rad/m */
export function waveNumber(lambda) {
    return isPos(lambda) ? TWO_PI / lambda : NaN;
}

/**
 * Harmonic travelling wave y(x,t) = A·sin(kx − s·ωt + φ), where s = +1 for a
 * wave moving toward +x and s = −1 toward −x (crest position x = x₀ + s·v·t).
 *
 * @param {number} x position, m
 * @param {number} t time, s
 * @param {{amplitude:number,frequency:number,wavelength:number,phase?:number,direction?:1|-1}} p
 * @returns {number} displacement (same unit as amplitude)
 */
export function travelingWave(x, t, { amplitude, frequency, wavelength, phase = 0, direction = 1 }) {
    const k = waveNumber(wavelength);
    const w = angularFrequency(frequency);
    const s = direction < 0 ? -1 : 1;
    return amplitude * Math.sin(k * x - s * w * t + phase);
}

// ─────────────────────────────────────────────────────────────────────────────
// Sound
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Speed of sound in air, linear school approximation v ≈ 331 + 0.6·T(°C).
 * @param {number} tempC temperature, °C @returns {number} m/s
 */
export function speedOfSoundAirApprox(tempC) {
    if (!isNum(tempC) || tempC <= -273.15) return NaN;
    return 331 + 0.6 * tempC;
}

/**
 * Speed of sound in an ideal gas v = √(γRT/M).
 * @param {number} tempK absolute temperature, K
 * @param {number} [gamma=1.4] adiabatic index
 * @param {number} [molarMass=0.0289647] kg/mol
 * @returns {number} m/s
 */
export function speedOfSoundIdealGas(tempK, gamma = GAMMA_AIR, molarMass = MOLAR_MASS_AIR) {
    if (!isPos(tempK) || !isPos(gamma) || !isPos(molarMass)) return NaN;
    return Math.sqrt((gamma * GAS_CONSTANT * tempK) / molarMass);
}

/**
 * Speed of sound in air used by the lab simulations: the ideal-gas law
 * v ∝ √T anchored at the lab value 343 m/s at 20 °C,
 *   v(t) = 343·√((273.15 + t) / 293.15)  m/s
 * (331.1 m/s at 0 °C — 0.1 % below the literature 331.3–331.5, like 343 is
 * 0.1 % below Cramer's 343.36 at 20 °C; slope ≈ 0.59 m/s per °C, cf. the
 * school rule 331 + 0.6·t).
 * @param {number} [tempC=20] air temperature, °C
 * @returns {number} m/s (NaN at or below absolute zero)
 */
export function speedOfSoundAir(tempC = SOUND_SPEED_REF_TEMP_C) {
    if (!isNum(tempC) || tempC <= -ZERO_CELSIUS) return NaN;
    if (tempC === SOUND_SPEED_REF_TEMP_C) return SOUND_SPEED_AIR;
    return SOUND_SPEED_AIR * Math.sqrt((ZERO_CELSIUS + tempC) / (ZERO_CELSIUS + SOUND_SPEED_REF_TEMP_C));
}

/**
 * Sound intensity level β = 10·log₁₀(I / I₀), I₀ = 10⁻¹² W/m².
 * @param {number} intensity W/m² @returns {number} dB (−Infinity for I = 0)
 */
export function soundIntensityLevel(intensity, i0 = HEARING_THRESHOLD) {
    if (!isNum(intensity) || intensity < 0 || !isPos(i0)) return NaN;
    return 10 * Math.log10(intensity / i0);
}

/** Inverse of β: I = I₀·10^(β/10). @param {number} levelDb dB @returns {number} W/m² */
export function intensityFromLevel(levelDb, i0 = HEARING_THRESHOLD) {
    if (!isNum(levelDb) || !isPos(i0)) return NaN;
    return i0 * Math.pow(10, levelDb / 10);
}

/**
 * Point source in free space (inverse-square law) I = P / (4πr²).
 * @param {number} power W @param {number} r m @returns {number} W/m²
 */
export function intensityAtDistance(power, r) {
    if (!isNum(power) || power < 0 || !isPos(r)) return NaN;
    return power / (4 * Math.PI * r * r);
}

/**
 * Longitudinal sound wave state at (x, t) for displacement
 * s(x,t) = s₀·sin(kx − ωt). The pressure variation is
 * Δp = −B·∂s/∂x = −B·k·s₀·cos(kx − ωt), i.e. 90° out of phase with the
 * displacement; `pressure` is Δp / (B·k·s₀) ∈ [−1, 1] (+1 = compression).
 *
 * @param {number} x m @param {number} t s
 * @param {{amplitude:number,frequency:number,wavelength:number}} p
 * @returns {{displacement:number, pressure:number}}
 */
export function soundWaveState(x, t, { amplitude, frequency, wavelength }) {
    const phi = waveNumber(wavelength) * x - angularFrequency(frequency) * t;
    return { displacement: amplitude * Math.sin(phi), pressure: -Math.cos(phi) };
}

// ─────────────────────────────────────────────────────────────────────────────
// Doppler effect (sound, medium at rest)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Observed frequency f' = f·(v + v_o)/(v − v_s).
 * Sign convention: v_o > 0 observer moves TOWARD the source; v_s > 0 source
 * moves TOWARD the observer (negative = receding).
 * Returns NaN when no steady sound reaches the observer: a source approaching
 * at v_s ≥ v (supersonic — the formula breaks down, shock cone) or an observer
 * receding faster than sound (v_o < −v). Observer receding exactly at v → 0 Hz.
 *
 * @param {number} f source frequency, Hz
 * @param {number} v speed of sound, m/s
 * @param {number} vo observer speed, m/s (toward source positive)
 * @param {number} vs source speed, m/s (toward observer positive)
 * @returns {number} Hz
 */
export function dopplerFrequency(f, v, vo, vs) {
    if (!isNum(f) || f < 0 || !isPos(v) || !isNum(vo) || !isNum(vs)) return NaN;
    if (vs >= v) return NaN;
    if (v + vo < 0) return NaN;
    return (f * (v + vo)) / (v - vs);
}

/** Mach number M = |v_s| / v. @returns {number} */
export function machNumber(vs, v) {
    if (!isNum(vs) || !isPos(v)) return NaN;
    return Math.abs(vs) / v;
}

/** Mach-cone half-angle sin α = v / v_s (only for |v_s| > v). @returns {number} rad or NaN */
export function machConeHalfAngle(vs, v) {
    if (!isNum(vs) || !isPos(v) || Math.abs(vs) <= v) return NaN;
    return Math.asin(v / Math.abs(vs));
}

/**
 * Wavelengths in front of and behind a moving source:
 * λ_front = (v − v_s)/f, λ_behind = (v + v_s)/f (v_s > 0 = moving forward).
 * @returns {{front:number, behind:number}} m (front NaN when v_s ≥ v)
 */
export function dopplerWavelengths(f, v, vs) {
    if (!isPos(f) || !isPos(v) || !isNum(vs)) return { front: NaN, behind: NaN };
    return {
        front: vs < v ? (v - vs) / f : NaN,
        behind: vs > -v ? (v + vs) / f : NaN,
    };
}

/**
 * Wavefronts of a source moving with constant velocity v_s along +x, emitting
 * one crest each period T at t_n = n·T. At time t a crest of age τ = t − t_n
 * is a circle of radius v·τ centred where the source was at t_n, i.e. at
 * offset −v_s·τ from the current source position.
 *
 * @param {number} t current time, s
 * @param {{period:number, waveSpeed:number, sourceSpeed:number, maxAge:number}} p
 * @returns {Array<{age:number, radius:number, centerOffset:number}>} youngest first
 */
export function dopplerWavefronts(t, { period: T, waveSpeed: v, sourceSpeed: vs, maxAge }) {
    if (!isPos(T) || !isPos(v) || !isNum(vs) || !isPos(maxAge) || !isNum(t)) return [];
    const out = [];
    const nLast = Math.floor(t / T);
    for (let n = nLast; ; n--) {
        const age = t - n * T;
        if (age > maxAge) break;
        out.push({ age, radius: v * age, centerOffset: -vs * age });
    }
    return out;
}

// ─────────────────────────────────────────────────────────────────────────────
// Standing waves
// ─────────────────────────────────────────────────────────────────────────────

/** Wave speed on a string v = √(T/μ). @param {number} tension N @param {number} mu kg/m @returns {number} m/s */
export function stringWaveSpeed(tension, mu) {
    if (!isNum(tension) || tension < 0 || !isPos(mu)) return NaN;
    return Math.sqrt(tension / mu);
}

/** String tension needed for wave speed v: T = μ·v² (Melde / sonometer). @returns {number} N */
export function stringTension(v, mu) {
    if (!isNum(v) || v < 0 || !isPos(mu)) return NaN;
    return mu * v * v;
}

const validMode = (n) => Number.isInteger(n) && n >= 1;

/** String fixed at both ends (and open–open pipe): f_n = n·v/(2L). @returns {number} Hz */
export function stringHarmonicFrequency(n, length, v) {
    if (!validMode(n) || !isPos(length) || !isPos(v)) return NaN;
    return (n * v) / (2 * length);
}

/** Standing-wave wavelength for both-ends-fixed (or open–open): λ_n = 2L/n. @returns {number} m */
export function standingWaveWavelength(n, length) {
    if (!validMode(n) || !isPos(length)) return NaN;
    return (2 * length) / n;
}

/** Open–open pipe: f_n = n·v/(2L), n = 1, 2, 3… @returns {number} Hz */
export function openPipeFrequency(n, length, v) {
    return stringHarmonicFrequency(n, length, v);
}

/**
 * Closed (one end) pipe: only odd harmonics, f = (2n − 1)·v/(4L) for the n-th
 * allowed mode n = 1, 2, 3… (i.e. 1st, 3rd, 5th… harmonic).
 * @returns {number} Hz
 */
export function closedPipeFrequency(n, length, v) {
    if (!validMode(n) || !isPos(length) || !isPos(v)) return NaN;
    return ((2 * n - 1) * v) / (4 * length);
}

/**
 * Standing wave on a string fixed at x = 0 and x = L, n-th mode:
 * y(x,t) = A·sin(nπx/L)·cos(2πf_n·t). A is the antinode amplitude
 * (= 2× the amplitude of each counter-propagating travelling wave).
 * @returns {number}
 */
export function standingWaveDisplacement(x, t, { n, length, amplitude, frequency }) {
    if (!validMode(n) || !isPos(length)) return NaN;
    return amplitude * Math.sin((n * Math.PI * x) / length) * Math.cos(TWO_PI * frequency * t);
}

/** Node positions x_k = k·L/n, k = 0…n (n + 1 nodes, both ends included). @returns {number[]} m */
export function stringNodePositions(n, length) {
    if (!validMode(n) || !isPos(length)) return [];
    return Array.from({ length: n + 1 }, (_, k) => (k * length) / n);
}

/** Antinode positions x_k = (k + ½)·L/n, k = 0…n−1 (n antinodes). @returns {number[]} m */
export function stringAntinodePositions(n, length) {
    if (!validMode(n) || !isPos(length)) return [];
    return Array.from({ length: n }, (_, k) => ((k + 0.5) * length) / n);
}

// ─────────────────────────────────────────────────────────────────────────────
// Driven damped oscillator / resonance
//   m·x'' + b·x' + k·x = F₀·cos(ωt),  ω₀ = √(k/m),  γ = b/m = 2ζω₀
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Steady-state amplitude A(ω) = (F₀/m) / √((ω₀² − ω²)² + (γω)²).
 * @param {number} f0OverM F₀/m, m/s² @param {number} w0 rad/s @param {number} w rad/s
 * @param {number} gamma b/m, 1/s @returns {number} m
 */
export function drivenAmplitude(f0OverM, w0, w, gamma) {
    if (!isNum(f0OverM) || !isPos(w0) || !isNum(w) || w < 0 || !isNum(gamma) || gamma < 0) return NaN;
    const den = Math.sqrt((w0 * w0 - w * w) ** 2 + (gamma * w) ** 2);
    return den === 0 ? Infinity : f0OverM / den;
}

/**
 * Phase lag of displacement behind the force, δ = atan2(γω, ω₀² − ω²) ∈ [0, π]
 * (0 far below resonance, π/2 at ω = ω₀, → π far above).
 * @returns {number} rad
 */
export function drivenPhaseLag(w0, w, gamma) {
    if (!isPos(w0) || !isNum(w) || w < 0 || !isNum(gamma) || gamma < 0) return NaN;
    return Math.atan2(gamma * w, w0 * w0 - w * w);
}

/**
 * Dimensionless response (dynamic amplification) A/A_static with r = ω/ω₀ and
 * damping ratio ζ: 1 / √((1 − r²)² + (2ζr)²). Equals drivenAmplitude·k/F₀.
 * @returns {number}
 */
export function normalizedResponse(r, zeta) {
    if (!isNum(r) || r < 0 || !isNum(zeta) || zeta < 0) return NaN;
    const den = Math.sqrt((1 - r * r) ** 2 + (2 * zeta * r) ** 2);
    return den === 0 ? Infinity : 1 / den;
}

/** Phase lag with r = ω/ω₀: δ = atan2(2ζr, 1 − r²). @returns {number} rad */
export function normalizedPhaseLag(r, zeta) {
    if (!isNum(r) || r < 0 || !isNum(zeta) || zeta < 0) return NaN;
    return Math.atan2(2 * zeta * r, 1 - r * r);
}

/**
 * Amplitude-resonance frequency f_peak = f₀·√(1 − 2ζ²) (ζ < 1/√2);
 * for ζ ≥ 1/√2 the response decreases monotonically and the peak is at 0 Hz.
 * @returns {number} Hz
 */
export function resonancePeakFrequency(f0, zeta) {
    if (!isPos(f0) || !isNum(zeta) || zeta < 0) return NaN;
    const s = 1 - 2 * zeta * zeta;
    return s > 0 ? f0 * Math.sqrt(s) : 0;
}

/** Peak of normalizedResponse: 1/(2ζ√(1 − ζ²)) for ζ < 1/√2, else 1. */
export function peakNormalizedResponse(zeta) {
    if (!isNum(zeta) || zeta < 0) return NaN;
    if (zeta === 0) return Infinity;
    return 1 - 2 * zeta * zeta > 0 ? 1 / (2 * zeta * Math.sqrt(1 - zeta * zeta)) : 1;
}

/** Quality factor Q = 1/(2ζ) = ω₀/γ. */
export function qualityFactor(zeta) {
    if (!isNum(zeta) || zeta < 0) return NaN;
    return zeta === 0 ? Infinity : 1 / (2 * zeta);
}

/** Half-power (−3 dB) bandwidth Δf ≈ f₀/Q = 2ζf₀ (light damping). @returns {number} Hz */
export function halfPowerBandwidth(f0, zeta) {
    if (!isPos(f0) || !isNum(zeta) || zeta < 0) return NaN;
    return 2 * zeta * f0;
}

/**
 * True when the drive is inside the resonance band, i.e. the amplitude is at
 * least 1/√2 of the peak amplitude (half-power criterion). Never true for
 * ζ ≥ 1/√2 (no resonance peak).
 */
export function isInResonanceBand(r, zeta) {
    if (!isNum(r) || r < 0 || !isNum(zeta) || zeta < 0) return false;
    if (1 - 2 * zeta * zeta <= 0) return false;
    if (zeta === 0) return r === 1;
    return normalizedResponse(r, zeta) >= peakNormalizedResponse(zeta) / Math.SQRT2;
}

/**
 * Steady-state displacement under a sinusoidal drive F₀·sin(ωt):
 * x(t) = A_static·R(r,ζ)·sin(ωt − δ).
 * @param {number} t s
 * @param {{f0:number, f:number, zeta:number, staticAmplitude?:number}} p
 * @returns {number}
 */
export function drivenDisplacement(t, { f0, f, zeta, staticAmplitude = 1 }) {
    if (!isPos(f0)) return NaN;
    const r = f / f0;
    return staticAmplitude * normalizedResponse(r, zeta) * Math.sin(TWO_PI * f * t - normalizedPhaseLag(r, zeta));
}

// ─────────────────────────────────────────────────────────────────────────────
// Beats
// ─────────────────────────────────────────────────────────────────────────────

/** Beat frequency f_b = |f₁ − f₂|. @returns {number} Hz */
export function beatFrequency(f1, f2) {
    if (!isNum(f1) || !isNum(f2)) return NaN;
    return Math.abs(f1 - f2);
}

/** Beat period T_b = 1/|f₁ − f₂| (Infinity when equal). @returns {number} s */
export function beatPeriod(f1, f2) {
    return period(beatFrequency(f1, f2));
}

/** Direct sum y = A·sin(2πf₁t) + A·sin(2πf₂t). */
export function beatSum(t, f1, f2, amplitude = 1) {
    return amplitude * (Math.sin(TWO_PI * f1 * t) + Math.sin(TWO_PI * f2 * t));
}

/**
 * Product form (identical to beatSum): y = 2A·cos(π(f₁ − f₂)t)·sin(2π·f̄·t),
 * f̄ = (f₁ + f₂)/2.
 */
export function beatProduct(t, f1, f2, amplitude = 1) {
    return 2 * amplitude * Math.cos(Math.PI * (f1 - f2) * t) * Math.sin(Math.PI * (f1 + f2) * t);
}

/** Envelope (loudness) of the beat ±2A·|cos(π(f₁ − f₂)t)|. */
export function beatEnvelope(t, f1, f2, amplitude = 1) {
    return 2 * amplitude * Math.abs(Math.cos(Math.PI * (f1 - f2) * t));
}

// ─────────────────────────────────────────────────────────────────────────────
// Two-source interference (coherent, in phase, equal amplitude)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Path difference Δr = r₁ − r₂ from point P to sources S₁, S₂.
 * @param {{x:number,y:number}} p @param {{x:number,y:number}} s1 @param {{x:number,y:number}} s2
 * @returns {number}
 */
export function pathDifference(p, s1, s2) {
    return Math.hypot(p.x - s1.x, p.y - s1.y) - Math.hypot(p.x - s2.x, p.y - s2.y);
}

/**
 * Resultant amplitude of two equal in-phase waves: A_res = 2A·|cos(πΔr/λ)|
 * (maximum 2A for Δr = mλ, zero for Δr = (m + ½)λ).
 */
export function twoSourceAmplitude(dr, lambda, amplitude = 1) {
    if (!isNum(dr) || !isPos(lambda)) return NaN;
    return 2 * amplitude * Math.abs(Math.cos((Math.PI * dr) / lambda));
}

/**
 * Classify interference by the order m = Δr/λ.
 * @param {number} [tol=0.05] tolerance in wavelengths
 * @returns {'constructive'|'destructive'|'partial'}
 */
export function interferenceType(dr, lambda, tol = 0.05) {
    if (!isNum(dr) || !isPos(lambda)) return 'partial';
    const m = Math.abs(dr) / lambda;
    const frac = m - Math.floor(m);
    if (frac <= tol || frac >= 1 - tol) return 'constructive';
    if (Math.abs(frac - 0.5) <= tol) return 'destructive';
    return 'partial';
}

/**
 * Instantaneous field of two point sources (2-D ripple tank, no 1/√r decay):
 * y = A·sin(k·r₁ − ωt) + A·sin(k·r₂ − ωt).
 */
export function twoSourceField(x, y, t, { s1, s2, amplitude, frequency, wavelength }) {
    const k = waveNumber(wavelength);
    const w = angularFrequency(frequency);
    const r1 = Math.hypot(x - s1.x, y - s1.y);
    const r2 = Math.hypot(x - s2.x, y - s2.y);
    return amplitude * (Math.sin(k * r1 - w * t) + Math.sin(k * r2 - w * t));
}

/**
 * Number of nodal (destructive) hyperbolas between/around two sources a
 * distance d apart: count of m ∈ ℤ with |(m + ½)λ| < d → 2·⌈d/λ − ½⌉.
 */
export function nodalLineCount(d, lambda) {
    if (!isNum(d) || d < 0 || !isPos(lambda)) return NaN;
    return 2 * Math.max(0, Math.ceil(d / lambda - 0.5));
}

/** Number of antinodal (constructive) lines: count of m with |mλ| < d → 2·⌈d/λ⌉ − 1. */
export function antinodalLineCount(d, lambda) {
    if (!isNum(d) || d < 0 || !isPos(lambda)) return NaN;
    return Math.max(1, 2 * Math.ceil(d / lambda) - 1);
}

// ─────────────────────────────────────────────────────────────────────────────
// Double slit (Young)
// ─────────────────────────────────────────────────────────────────────────────

/** Fringe spacing (small angle) Δy = λL/d. @returns {number} m */
export function fringeSpacing(lambda, L, d) {
    if (!isPos(lambda) || !isPos(L) || !isPos(d)) return NaN;
    return (lambda * L) / d;
}

/**
 * Position of the m-th bright fringe: d·sinθ = mλ, y = L·tanθ (exact).
 * NaN if |m|λ > d (order does not exist).
 */
export function brightFringePosition(m, lambda, L, d) {
    if (!isNum(m) || !isPos(lambda) || !isPos(L) || !isPos(d)) return NaN;
    const s = (m * lambda) / d;
    if (Math.abs(s) >= 1) return NaN;
    return L * Math.tan(Math.asin(s));
}

/** Position of the m-th dark fringe: d·sinθ = (m + ½)λ, y = L·tanθ (exact). */
export function darkFringePosition(m, lambda, L, d) {
    return brightFringePosition(m + 0.5, lambda, L, d);
}

/** Highest bright order visible: m_max = ⌊d/λ⌋ (sinθ < 1). */
export function maxFringeOrder(lambda, d) {
    if (!isPos(lambda) || !isPos(d)) return NaN;
    const r = d / lambda;
    return Number.isInteger(r) ? r - 1 : Math.floor(r);
}

/**
 * Double-slit intensity on a screen at distance L, at height y:
 * sinθ = y/√(y² + L²), I = I₀·cos²(πd·sinθ/λ)·sinc²(πa·sinθ/λ),
 * with sinc(u) = sin(u)/u. Use slitWidth a = 0 for ideal (point) slits.
 *
 * @param {number} y m
 * @param {{wavelength:number, slitSeparation:number, screenDistance:number, slitWidth?:number, i0?:number}} p
 * @returns {number} intensity in units of I₀
 */
export function doubleSlitIntensity(y, { wavelength, slitSeparation, screenDistance, slitWidth = 0, i0 = 1 }) {
    if (!isNum(y) || !isPos(wavelength) || !isPos(slitSeparation) || !isPos(screenDistance)) return NaN;
    const sin = y / Math.hypot(y, screenDistance);
    const interference = Math.cos((Math.PI * slitSeparation * sin) / wavelength) ** 2;
    let diffraction = 1;
    if (isPos(slitWidth)) {
        const u = (Math.PI * slitWidth * sin) / wavelength;
        diffraction = u === 0 ? 1 : (Math.sin(u) / u) ** 2;
    }
    return i0 * interference * diffraction;
}

// ─────────────────────────────────────────────────────────────────────────────
// Electromagnetic spectrum & photons
// ─────────────────────────────────────────────────────────────────────────────

/** EM frequency f = c/λ. @param {number} lambda m @returns {number} Hz */
export function emFrequency(lambda) {
    return isPos(lambda) ? SPEED_OF_LIGHT / lambda : NaN;
}

/** EM wavelength λ = c/f. @param {number} f Hz @returns {number} m */
export function emWavelength(f) {
    return isPos(f) ? SPEED_OF_LIGHT / f : NaN;
}

/** Photon energy E = h·f. @param {number} f Hz @returns {number} J */
export function photonEnergyFromFrequency(f) {
    return isNum(f) && f >= 0 ? PLANCK * f : NaN;
}

/** Photon energy E = hc/λ. @param {number} lambda m @returns {number} J */
export function photonEnergy(lambda) {
    return isPos(lambda) ? (PLANCK * SPEED_OF_LIGHT) / lambda : NaN;
}

/** Photon energy in electron-volts E = hc/(λe) (≈ 1239.84 eV·nm / λ). @param {number} lambda m */
export function photonEnergyEV(lambda) {
    return photonEnergy(lambda) / ELEMENTARY_CHARGE;
}

/**
 * EM bands, longest wavelength first. Wavelength limits (m):
 *  - visible 380–760 nm and infrared 760 nm – 1 mm: ISO 21348:2007 (the CIE
 *    colorimetric tables run to 780 nm; the eye's sensitivity there is < 10⁻⁴);
 *  - ultraviolet 10–380 nm: ISO 21348 UV is 100–400 nm and EUV 10–121 nm; the
 *    380–400 nm overlap with visible is assigned to visible here;
 *  - X-rays 10 pm – 10 nm, gamma < 10 pm: the textbook convention (X/γ are
 *    really distinguished by origin; ISO 21348 puts the edge at 1 pm);
 *  - microwaves 1 mm – 1 m (300 MHz – 300 GHz), radio > 1 m.
 * Frequency/energy limits are derived via f = c/λ, E = hc/λ so the table is
 * self-consistent. `lambdaMax` of radio and `lambdaMin` of gamma are display
 * limits only.
 */
export const EM_BANDS = Object.freeze([
    { id: 'radio', name: 'Radio to\'lqinlari', lambdaMin: 1, lambdaMax: 1e5, openMax: true },
    { id: 'microwave', name: 'Mikro to\'lqinlar', lambdaMin: 1e-3, lambdaMax: 1 },
    { id: 'infrared', name: 'Infraqizil', lambdaMin: 760e-9, lambdaMax: 1e-3 },
    { id: 'visible', name: 'Ko\'rinadigan yorug\'lik', lambdaMin: 380e-9, lambdaMax: 760e-9 },
    { id: 'ultraviolet', name: 'Ultrabinafsha', lambdaMin: 10e-9, lambdaMax: 380e-9 },
    { id: 'xray', name: 'Rentgen nurlari', lambdaMin: 1e-11, lambdaMax: 10e-9 },
    { id: 'gamma', name: 'Gamma nurlari', lambdaMin: 1e-14, lambdaMax: 1e-11, openMin: true },
].map(Object.freeze));

/**
 * Band containing wavelength λ (boundaries belong to the shorter-λ band's upper
 * edge, i.e. ranges are [λmin, λmax)). @returns {object|null}
 */
export function classifyWavelength(lambda) {
    if (!isPos(lambda)) return null;
    if (lambda >= 1) return EM_BANDS[0];
    if (lambda < 1e-11) return EM_BANDS[EM_BANDS.length - 1];
    return EM_BANDS.find((b) => lambda >= b.lambdaMin && lambda < b.lambdaMax) ?? null;
}

/**
 * Derived frequency (Hz) and photon-energy (eV) range of a band.
 * @returns {{fMin:number,fMax:number,eMinEV:number,eMaxEV:number,lambdaCenter:number}}
 *   lambdaCenter is the geometric mean √(λmin·λmax).
 */
export function bandRanges(band) {
    return {
        fMin: emFrequency(band.lambdaMax),
        fMax: emFrequency(band.lambdaMin),
        eMinEV: photonEnergyEV(band.lambdaMax),
        eMaxEV: photonEnergyEV(band.lambdaMin),
        lambdaCenter: Math.sqrt(band.lambdaMin * band.lambdaMax),
    };
}

/**
 * Approximate sRGB colour of monochromatic light (piecewise-linear
 * approximation, D. Bruton), with intensity fall-off near the eye's limits.
 * @param {number} nm wavelength in nanometres
 * @returns {[number,number,number]} 0–255 each; black outside 380–780 nm
 */
export function wavelengthToRgb(nm) {
    if (!isNum(nm) || nm < 380 || nm > 780) return [0, 0, 0];
    let r = 0, g = 0, b = 0;
    if (nm < 440) { r = (440 - nm) / 60; b = 1; }
    else if (nm < 490) { g = (nm - 440) / 50; b = 1; }
    else if (nm < 510) { g = 1; b = (510 - nm) / 20; }
    else if (nm < 580) { r = (nm - 510) / 70; g = 1; }
    else if (nm < 645) { r = 1; g = (645 - nm) / 65; }
    else { r = 1; }
    let f = 1;
    if (nm < 420) f = 0.3 + (0.7 * (nm - 380)) / 40;
    else if (nm > 700) f = 0.3 + (0.7 * (780 - nm)) / 80;
    return [r, g, b].map((c) => Math.round(255 * c * f));
}

// ─────────────────────────────────────────────────────────────────────────────
// Lab apparatus of the waves simulations: scales and measuring instruments
// (documented in the "To'lqinlar" docs section). A real lab never reads
// pixels: every on-screen length has a stated physical scale.
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Wave basics: a long rubber cord on the floor, drawn 1 px = 1 cm (900 px =
 * 9 m). Two light gates at x = 2 m and 7 m (5.00 m apart) start/stop an
 * electronic timer when a crest passes: v = s/t.
 */
export const CORD = Object.freeze({ pxPerM: 100, gateA: 2, gateB: 7 });

/** Melde experiment: string L = 1.000 m, linear density μ = 1.0 g/m, driven by a vibrator. */
export const MELDE_STRING = Object.freeze({ length: 1, mu: 1e-3 });

/**
 * Ripple tank (top view): water ≈ 4 mm deep, v ≈ 0.20 m/s nearly constant for
 * 1–10 Hz (capillary–gravity dispersion ω² = (gk + σk³/ρ)·tanh(kh) gives
 * 19.8–21.3 cm/s there, i.e. within 7 %). Drawn 1 cm = 20 px (800 px = 40 cm).
 * λ is measured with a ruler over N whole wavelengths (N ≤ 10, N·λ ≤ 30 cm).
 */
export const RIPPLE_TANK = Object.freeze({ waveSpeed: 0.2, pxPerM: 2000, maxSpan: 0.3, maxCount: 10 });

/** Driven spring oscillator: static deflection A_st = F₀/k = 10 mm (1 px = 1 mm on screen). */
export const RESONANCE_STATIC_AMPLITUDE = 0.01;

/** Beats: the timer measures this many whole beat periods. */
export const BEATS_TIMED = 10;

/**
 * Instruments of the waves lab (specs from instruments.js; values in SI).
 *  - lightGateTimer: electronic timer 1 ms, σ = 0.5 ms (crest between gates, 10 beats);
 *  - tape: steel tape / metre rule 1 mm, σ = 0.3 mm (sound λ, ripple-tank span);
 *  - displacementSensor: 0.1 mm, σ = 0.03 mm (oscillator amplitude);
 *  - frequencyCounter: 0.1 Hz (generator / counter display, no scatter).
 */
export const WAVE_INSTRUMENTS = Object.freeze({
    lightGateTimer: INSTRUMENTS.photogateTimer,
    tape: Object.freeze({ ...INSTRUMENTS.metreRule, accuracy: '±0.5 mm (po\'lat ruletka, 1 mm)' }),
    displacementSensor: Object.freeze({ resolution: 0.0001, sigmaAbs: 0.00003, unit: 'm', accuracy: '±0.1 mm' }),
    frequencyCounter: INSTRUMENTS.frequencyCounter,
});

/** Travel time of a crest over a distance s at speed v: t = s/v. @returns {number} s */
export function travelTime(distance, v) {
    if (!isPos(distance) || !isPos(v)) return NaN;
    return distance / v;
}

/**
 * Number of whole wavelengths measured on the ripple-tank ruler: the largest
 * N ≤ maxCount with N·λ ≤ maxSpan (at least 1).
 */
export function rippleSpanCount(lambda, maxSpan = RIPPLE_TANK.maxSpan, maxCount = RIPPLE_TANK.maxCount) {
    if (!isPos(lambda)) return NaN;
    return Math.max(1, Math.min(maxCount, Math.floor(maxSpan / lambda + 1e-9)));
}

// ─────────────────────────────────────────────────────────────────────────────
// Formatting
// ─────────────────────────────────────────────────────────────────────────────

const SI_PREFIXES = [
    [1e18, 'E'], [1e15, 'P'], [1e12, 'T'], [1e9, 'G'], [1e6, 'M'], [1e3, 'k'],
    [1, ''], [1e-3, 'm'], [1e-6, 'µ'], [1e-9, 'n'], [1e-12, 'p'], [1e-15, 'f'],
];

/**
 * Format a value with an SI prefix, e.g. formatSI(4.6e14, 'Hz') → "460 THz".
 * @param {number} value @param {string} unit @param {number} [sig=3] significant digits
 * @returns {string} "—" for non-finite input
 */
export function formatSI(value, unit = '', sig = 3) {
    if (!isNum(value)) return '—';
    if (value === 0) return `0 ${unit}`.trim();
    const abs = Math.abs(value);
    let [scale, prefix] = SI_PREFIXES[SI_PREFIXES.length - 1];
    for (const [s, p] of SI_PREFIXES) {
        if (abs >= s * (1 - 5e-13)) { scale = s; prefix = p; break; }
    }
    let n = Number((value / scale).toPrecision(sig));
    // rounding may carry to 1000 → move to the next prefix
    if (Math.abs(n) >= 1000) {
        const idx = SI_PREFIXES.findIndex(([s]) => s === scale);
        if (idx > 0) { [scale, prefix] = SI_PREFIXES[idx - 1]; n = Number((value / scale).toPrecision(sig)); }
    }
    return `${n} ${prefix}${unit}`.trim();
}

/** Round to `digits` decimals and return a Number (NaN preserved). */
export function roundTo(value, digits = 2) {
    if (!isNum(value)) return NaN;
    const p = 10 ** digits;
    return Math.round(value * p) / p;
}
