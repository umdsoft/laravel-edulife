/**
 * Magnetism physics for the virtual lab (pure functions, SI units in/out).
 *
 * Conventions
 *  - B in tesla (T), I in ampere (A), lengths in metres (m), time in seconds (s),
 *    flux in weber (Wb), EMF in volt (V), angles in radians.
 *  - Invalid inputs (non-finite numbers, non-positive lengths) return NaN rather
 *    than throwing, so a UI can show "—" without crashing.
 */

import { MU_0 } from './constants.js';
import { INSTRUMENTS, quantize, truncatedNormal, dmmReading, dmmResolution } from './instruments.js';

export { MU_0 };

/**
 * Vacuum permeability µ0 [T·m/A = N/A²], CODATA 2022: 1.256 637 061 27·10⁻⁶
 * (https://physics.nist.gov/cgi-bin/cuu/Value?mu0). Since the 2019 SI redefinition µ0
 * is measured, not defined; CODATA 2022 differs from the classical 4π·10⁻⁷ by −1.3·10⁻¹⁰
 * (relative; CODATA 2018 gave +5.4·10⁻¹⁰) — invisible at the 5 significant digits of the
 * lab tasks (u_r = 1.6·10⁻¹⁰).
 */
export const MU0 = MU_0;

const isNum = (x) => typeof x === 'number' && Number.isFinite(x);

/**
 * Field of a long straight wire: B = µ0·I / (2π·r).
 * @param {number} current I [A]
 * @param {number} r distance from the wire [m], must be > 0
 * @returns {number} B [T]
 */
export function wireField(current, r) {
    if (!isNum(current) || !isNum(r) || r <= 0) return NaN;
    return (MU0 * current) / (2 * Math.PI * r);
}

/**
 * Field at the centre of a circular loop of N turns: B = µ0·N·I / (2R).
 * @param {number} current I [A]
 * @param {number} radius R [m], must be > 0
 * @param {number} [turns=1] N
 * @returns {number} B [T]
 */
export function loopCenterField(current, radius, turns = 1) {
    if (!isNum(current) || !isNum(radius) || radius <= 0 || !isNum(turns)) return NaN;
    return (MU0 * turns * current) / (2 * radius);
}

/**
 * Turns per metre of a solenoid: n = N / L.
 * @param {number} turns N
 * @param {number} length L [m], must be > 0
 * @returns {number} n [1/m]
 */
export function turnsPerMetre(turns, length) {
    if (!isNum(turns) || !isNum(length) || length <= 0) return NaN;
    return turns / length;
}

/**
 * Field inside a long solenoid filled with a linear core: B = µ0·µr·n·I, n = N/L.
 * (Textbook formula — it ignores core saturation, see {@link CORE_MATERIALS}.)
 * @param {{turns:number, length:number, current:number, mur?:number}} p
 *   turns N, length L [m], current I [A], relative permeability µr (default 1, air)
 * @returns {number} B [T]
 */
export function solenoidField({ turns, length, current, mur = 1 }) {
    const n = turnsPerMetre(turns, length);
    if (!isNum(n) || !isNum(current) || !isNum(mur) || mur <= 0) return NaN;
    return MU0 * mur * n * current;
}

/**
 * Core materials for the electromagnet: relative permeability µr (linear part of the
 * B–H curve), saturation flux density Bsat [T] and the knee [T] where the B–H curve
 * starts to bend (the linear formula B = µ0µr·nI holds below it).
 *  - iron: µr = 5000 is the maximum permeability of 99.8 % iron
 *    (https://en.wikipedia.org/wiki/Permeability_(electromagnetism)); high-permeability
 *    iron alloys saturate at 1.6–2.2 T (https://en.wikipedia.org/wiki/Saturation_(magnetic)),
 *    pure iron ≈ 2.15 T — 2.0 T is used for "soft iron"; knee ≈ 1.2 T (0.6·Bsat).
 *  - ferrite (Mn-Zn power ferrite, e.g. TDK N87): µi = 2200 ±25 %, Bsat = 490 mT at 25 °C,
 *    390 mT at 100 °C (TDK/EPCOS N87 data sheet,
 *    https://web.pa.msu.edu/people/edmunds/Disco_Kraken/Components/Transformers/tdk_ferrite_n87_siferrit.pdf) —
 *    µr = 2000 and Bsat = 0.45 T; knee 0.27 T.
 */
export const CORE_MATERIALS = Object.freeze({
    air: Object.freeze({ permeability: 1, saturation: Infinity, knee: Infinity }),
    iron: Object.freeze({ permeability: 5000, saturation: 2.0, knee: 1.2 }),
    ferrite: Object.freeze({ permeability: 2000, saturation: 0.45, knee: 0.27 }),
});

/**
 * Starting setup of the electromagnet lab. It used to start on an iron core
 * (N = 100, I = 2 A), where the linear formula gives 12 566 mT — far above the
 * ~2 T an iron core can reach (QA-S-21). Air core matches the first task.
 */
export const ELECTROMAGNET_DEFAULTS = Object.freeze({ turns: 100, current: 2, core: 'air' });

/**
 * True when the linear-model field exceeds the saturation flux density of the core
 * (the real field then stays at about Bsat).
 * @param {number} B linear-model field [T]
 * @param {number} saturation Bsat [T]
 */
export function isCoreSaturated(B, saturation) {
    return isNum(B) && Math.abs(B) > saturation;
}

/**
 * Magnetic polarisation of a soft core with a simple saturating B–H curve:
 *   J = Jlin                                   for |Jlin| ≤ Jk  (linear, J = µ0(µr−1)H)
 *   J = ±[Jk + (Js − Jk)·tanh((|Jlin| − Jk)/(Js − Jk))]   above the knee
 * Continuous with a continuous slope at the knee, monotonic, |J| < Js.
 * @param {number} Jlin linear-model polarisation [T]
 * @param {number} Js saturation polarisation [T] (Infinity → linear)
 * @param {number} Jk knee [T] (< Js)
 * @returns {number} J [T]
 */
export function saturatingPolarization(Jlin, Js, Jk) {
    if (!isNum(Jlin)) return NaN;
    if (!(Js < Infinity)) return Jlin;
    const k = Math.min(Jk, Js);
    const a = Math.abs(Jlin);
    if (a <= k) return Jlin;
    const span = Js - k;
    const J = span > 0 ? k + span * Math.tanh((a - k) / span) : Js;
    return Math.sign(Jlin) * J;
}

/**
 * Field inside a long solenoid with a ferromagnetic core, including core saturation:
 *   H = n·I,  B = µ0·H + J(µ0(µr−1)H)   (J from {@link saturatingPolarization}).
 * Below the knee this is exactly the textbook B = µ0µr·n·I; above it B bends over and
 * approaches Bsat (+ the small vacuum term µ0H) instead of growing without limit.
 * @param {{turns:number, length:number, current:number, core?:string, mur?:number,
 *          saturation?:number, knee?:number}} p core = key of CORE_MATERIALS (or explicit mur/saturation/knee)
 * @returns {{H:number, linear:number, B:number, nonlinear:boolean, saturated:boolean, effectiveMur:number}}
 *   H [A/m], linear = µ0µr·nI [T], B = model field [T], nonlinear = above the knee,
 *   saturated = linear field beyond Bsat, effectiveMur = B/(µ0H)
 */
export function coreSolenoidField({ turns, length, current, core, mur, saturation, knee }) {
    const mat = core ? CORE_MATERIALS[core] : null;
    const m = mur ?? mat?.permeability ?? 1;
    const Bs = saturation ?? mat?.saturation ?? Infinity;
    const Bk = knee ?? mat?.knee ?? Infinity;
    const n = turnsPerMetre(turns, length);
    if (!isNum(n) || !isNum(current) || !isNum(m) || m <= 0) {
        return { H: NaN, linear: NaN, B: NaN, nonlinear: false, saturated: false, effectiveMur: NaN };
    }
    const H = n * current;
    const linear = MU0 * m * H;
    const J = saturatingPolarization(MU0 * (m - 1) * H, Bs, Bk);
    const B = MU0 * H + J;
    return {
        H,
        linear,
        B,
        nonlinear: Math.abs(MU0 * (m - 1) * H) > Bk,
        saturated: isCoreSaturated(linear, Bs),
        effectiveMur: H === 0 ? m : B / (MU0 * H),
    };
}

// ─────────────────────────────────────────────────────────────────────────────
// Instruments of the magnetism labs (local specs built on instruments.js)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Digital teslameter with a Hall probe, 3½ digits, ranges 20 / 200 / 2000 mT with
 * resolution 0.01 / 0.1 / 1 mT (PHYWE Teslameter digital 13610-93,
 * https://phywe.com/equipment-accessories/measurement-devices/oscilloscopes/phywe-teslameter-digital_2108).
 * Scatter σ = 0.1 % of the reading (probe positioning / noise); the probe is zeroed
 * before the measurement, so the Earth's field (≈ 54 µT in Tashkent) is compensated.
 */
export const TESLAMETER = Object.freeze({
    ...INSTRUMENTS.teslameter,
    ranges: Object.freeze([20, 200, 2000]), // mT
    counts: 2000,
    limit: 2.5,
});

/** Last-digit resolution [mT] of the auto-ranged teslameter for a reading in mT. */
export function teslameterResolution(milliTesla) {
    return dmmResolution(milliTesla, TESLAMETER.counts, TESLAMETER.ranges);
}

/**
 * One teslameter reading [mT] of a true field [mT]: relative scatter, then rounded to
 * the last digit of the auto-selected range (no rng → only the rounding).
 */
export function teslameterReading(milliTesla, rng = null) {
    if (!isNum(milliTesla)) return milliTesla;
    const noisy = rng ? milliTesla * (1 + TESLAMETER.sigmaRel * truncatedNormal(rng, TESLAMETER.limit)) : milliTesla;
    return quantize(noisy, teslameterResolution(noisy));
}

/** Worst-case |teslameter reading − true value| [mT]: 2.5σ + ½ last digit. */
export function teslameterMaxError(milliTesla) {
    const v = Math.abs(milliTesla) * (1 + TESLAMETER.limit * TESLAMETER.sigmaRel);
    return TESLAMETER.limit * TESLAMETER.sigmaRel * Math.abs(milliTesla) + teslameterResolution(v) / 2;
}

/**
 * 3½-digit DMM used on the AC-voltage, mV and mA ranges (instruments.dmmReading) with a
 * per-use relative scatter: sigmaRel 0.05 % for a steady AC/DC reading, 0.5 % for a
 * PEAK-HOLD reading of a short induction pulse.
 */
export const LAB_DMM = Object.freeze({ counts: 2000, steadySigmaRel: 0.0005, peakSigmaRel: 0.005, limit: 2.5 });

/** DMM reading of a value in base units (V, A) — see instruments.dmmReading. */
export function labDmmReading(value, rng = null, sigmaRel = LAB_DMM.steadySigmaRel, ranges = undefined) {
    return dmmReading(value, rng, { counts: LAB_DMM.counts, sigmaRel, limit: LAB_DMM.limit, ...(ranges ? { ranges } : {}) });
}

/** Worst-case |DMM reading − true| in base units: 2.5σ + ½ last digit of the range. */
export function labDmmMaxError(value, sigmaRel = LAB_DMM.steadySigmaRel, ranges = undefined) {
    const v = Math.abs(value) * (1 + LAB_DMM.limit * sigmaRel);
    return LAB_DMM.limit * sigmaRel * Math.abs(value) + dmmResolution(v, LAB_DMM.counts, ranges) / 2;
}

/**
 * Digital storage oscilloscope automatic measurements (generator lab): Vamp / Vrms to
 * 0.01 V, frequency to 0.001 Hz; scatter 0.5 % (vertical) and 0.05 % (frequency, the
 * crank speed fluctuates slightly). Typical school DSO: 8-bit ADC, ±3 % DC gain accuracy.
 */
export const OSCILLOSCOPE = Object.freeze({
    voltage: Object.freeze({ resolution: 0.01, sigmaRel: 0.005, unit: 'V', accuracy: '±3 %' }),
    frequency: Object.freeze({ resolution: 0.001, sigmaRel: 0.0005, unit: 'Hz', accuracy: '±0.01 %' }),
});

/**
 * Magnitude of the Lorentz force on a charge: F = |q|·v·B·sin θ.
 * @param {number} q charge [C]
 * @param {number} v speed [m/s]
 * @param {number} B field [T]
 * @param {number} [theta=π/2] angle between v and B [rad]
 * @returns {number} F [N] (≥ 0)
 */
export function lorentzForce(q, v, B, theta = Math.PI / 2) {
    if (![q, v, B, theta].every(isNum)) return NaN;
    return Math.abs(q * v * B * Math.sin(theta));
}

/**
 * Magnitude of the Ampère force on a straight conductor: F = B·I·L·sin θ.
 * @param {number} B [T]
 * @param {number} current I [A]
 * @param {number} length L [m]
 * @param {number} [theta=π/2] angle between the wire and B [rad]
 * @returns {number} F [N] (≥ 0)
 */
export function wireForce(B, current, length, theta = Math.PI / 2) {
    if (![B, current, length, theta].every(isNum) || length < 0) return NaN;
    return Math.abs(B * current * length * Math.sin(theta));
}

// ─────────────────────────────────────────────────────────────────────────────
// Bar magnet (Gilbert "magnetic charge" model) and point dipole
// ─────────────────────────────────────────────────────────────────────────────

/**
 * On-axis field of a point dipole: B = µ0·2m / (4π·r³).
 * @param {number} moment m [A·m²]
 * @param {number} r [m] > 0
 */
export function dipoleAxialField(moment, r) {
    if (!isNum(moment) || !isNum(r) || r <= 0) return NaN;
    return (MU0 * 2 * moment) / (4 * Math.PI * r ** 3);
}

/**
 * Equatorial field of a point dipole: B = µ0·m / (4π·r³) (antiparallel to m).
 * @param {number} moment m [A·m²]
 * @param {number} r [m] > 0
 */
export function dipoleEquatorialField(moment, r) {
    if (!isNum(moment) || !isNum(r) || r <= 0) return NaN;
    return (MU0 * moment) / (4 * Math.PI * r ** 3);
}

/**
 * Pole positions/strengths of a bar magnet lying along the x axis.
 * Pole strength q = m / ℓ (ℓ = distance between the effective poles).
 * @param {{moment:number, poleSeparation:number, northOnLeft?:boolean, cx?:number, cy?:number}} p
 * @returns {Array<{x:number,y:number,q:number}>} [north(+q), south(−q)]
 */
export function barMagnetPoles({ moment, poleSeparation, northOnLeft = true, cx = 0, cy = 0 }) {
    const q = moment / poleSeparation;
    const half = poleSeparation / 2;
    const xN = northOnLeft ? cx - half : cx + half;
    const xS = northOnLeft ? cx + half : cx - half;
    return [
        { x: xN, y: cy, q },
        { x: xS, y: cy, q: -q },
    ];
}

/**
 * Field in the plane z = 0 of a set of magnetic point poles (outside the magnet):
 * B = Σ µ0·q/(4π) · r̂ / r², r measured from each pole.
 * @param {number} x [m]
 * @param {number} y [m]
 * @param {Array<{x:number,y:number,q:number}>} poles
 * @returns {{Bx:number, By:number, B:number}} [T]
 */
export function poleField(x, y, poles) {
    let Bx = 0;
    let By = 0;
    for (const p of poles) {
        const dx = x - p.x;
        const dy = y - p.y;
        const r2 = dx * dx + dy * dy;
        if (r2 === 0) return { Bx: NaN, By: NaN, B: NaN };
        const k = (MU0 * p.q) / (4 * Math.PI * r2 * Math.sqrt(r2));
        Bx += k * dx;
        By += k * dy;
    }
    return { Bx, By, B: Math.hypot(Bx, By) };
}

/**
 * Field magnitude on the axis of a bar magnet (Gilbert two-pole model), at distance r
 * from the magnet centre, outside the magnet (r > ℓ/2):
 *   B = µ0·q/(4π)·[1/(r − ℓ/2)² − 1/(r + ℓ/2)²],  q = m/ℓ.
 * For r ≫ ℓ it tends to the dipole value µ0·2m/(4πr³) (from above).
 * @param {number} moment m [A·m²]
 * @param {number} poleSeparation ℓ [m] > 0
 * @param {number} r [m] > ℓ/2
 * @returns {number} B [T]
 */
export function barMagnetAxialField(moment, poleSeparation, r) {
    if (![moment, poleSeparation, r].every(isNum) || poleSeparation <= 0 || r <= poleSeparation / 2) return NaN;
    const h = poleSeparation / 2;
    const q = moment / poleSeparation;
    return ((MU0 * q) / (4 * Math.PI)) * (1 / (r - h) ** 2 - 1 / (r + h) ** 2);
}

/**
 * Traces a field line by following the unit direction of a vector field (spatial RK2,
 * independent of time/frame rate).
 * @param {(x:number,y:number)=>{Bx:number,By:number}} fieldFn
 * @param {number} x0 start x
 * @param {number} y0 start y
 * @param {{step:number, maxSteps?:number, stop?:(x:number,y:number)=>boolean}} opts
 *   step length (sign selects along/against B), optional stop predicate
 * @returns {Array<[number, number]>} polyline points (including the start)
 */
export function traceFieldLine(fieldFn, x0, y0, { step, maxSteps = 800, stop } = {}) {
    const pts = [[x0, y0]];
    let x = x0;
    let y = y0;
    const dir = (px, py) => {
        const f = fieldFn(px, py);
        const m = Math.hypot(f.Bx, f.By);
        if (!(m > 0)) return null;
        return [f.Bx / m, f.By / m];
    };
    for (let i = 0; i < maxSteps; i++) {
        const d1 = dir(x, y);
        if (!d1) break;
        const d2 = dir(x + 0.5 * step * d1[0], y + 0.5 * step * d1[1]);
        if (!d2) break;
        x += step * d2[0];
        y += step * d2[1];
        pts.push([x, y]);
        if (stop && stop(x, y)) break;
    }
    return pts;
}

// ─────────────────────────────────────────────────────────────────────────────
// Faraday induction: magnet moving along the axis of a coil
// ─────────────────────────────────────────────────────────────────────────────

/**
 * B-flux through one circular turn (radius a, in the plane x = 0, normal +x) of a bar
 * magnet on the coil axis, Gilbert model with poles at axial positions xN (+q) and xS (−q):
 *   Φ = (µ0·q/2)·[u(xN) − u(xS)],   u(x) = x / √(x² + a²).
 * This is continuous when the magnet passes through the coil (the magnetisation flux
 * inside the magnet is included). Far away it reduces to the dipole result
 * Φ = µ0·m·a² / (2(x²+a²)^{3/2}) with m along the S→N direction.
 * @param {{poleStrength:number, xNorth:number, xSouth:number, radius:number}} p
 *   q [A·m], pole positions [m] relative to the coil plane, coil radius a [m]
 * @returns {number} Φ [Wb] (positive along +x)
 */
export function magnetFluxThroughLoop({ poleStrength, xNorth, xSouth, radius }) {
    if (![poleStrength, xNorth, xSouth, radius].every(isNum) || radius <= 0) return NaN;
    const u = (x) => x / Math.sqrt(x * x + radius * radius);
    return 0.5 * MU0 * poleStrength * (u(xNorth) - u(xSouth));
}

/**
 * Pole positions (relative to the coil plane) of a bar magnet whose centre is at X.
 * @param {number} X centre position [m]
 * @param {number} poleSeparation ℓ [m]
 * @param {boolean} [northOnLeft=true]
 * @returns {{xNorth:number, xSouth:number}}
 */
export function magnetPolePositions(X, poleSeparation, northOnLeft = true) {
    const h = poleSeparation / 2;
    return northOnLeft ? { xNorth: X - h, xSouth: X + h } : { xNorth: X + h, xSouth: X - h };
}

/**
 * Exact rate of change of the single-turn flux for a magnet moving with axial velocity v:
 *   dΦ/dt = (µ0·q/2)·[u'(xN) − u'(xS)]·v,   u'(x) = a² / (x² + a²)^{3/2}.
 * @param {{poleStrength:number, xNorth:number, xSouth:number, radius:number, velocity:number}} p
 * @returns {number} dΦ/dt [Wb/s]
 */
export function magnetFluxRate({ poleStrength, xNorth, xSouth, radius, velocity }) {
    if (![poleStrength, xNorth, xSouth, radius, velocity].every(isNum) || radius <= 0) return NaN;
    const a2 = radius * radius;
    const du = (x) => a2 / Math.pow(x * x + a2, 1.5);
    return 0.5 * MU0 * poleStrength * (du(xNorth) - du(xSouth)) * velocity;
}

/**
 * Faraday's law for an N-turn coil: ε = −N·dΦ/dt.
 * @param {number} turns N
 * @param {number} dPhiDt [Wb/s]
 * @returns {number} ε [V]
 */
export function faradayEMF(turns, dPhiDt) {
    if (!isNum(turns) || !isNum(dPhiDt)) return NaN;
    return -turns * dPhiDt;
}

/**
 * Full state of a coil + moving bar magnet at one instant (analytic, no time-stepping).
 * @param {{turns:number, moment:number, poleSeparation:number, radius:number,
 *          position:number, velocity:number, northOnLeft?:boolean}} p
 *   position = magnet centre relative to coil plane [m], velocity [m/s]
 * @returns {{flux:number, fluxRate:number, emf:number}} Φ per turn [Wb], dΦ/dt [Wb/s], ε [V]
 */
export function movingMagnetInduction({ turns, moment, poleSeparation, radius, position, velocity, northOnLeft = true }) {
    const q = moment / poleSeparation;
    const { xNorth, xSouth } = magnetPolePositions(position, poleSeparation, northOnLeft);
    const flux = magnetFluxThroughLoop({ poleStrength: q, xNorth, xSouth, radius });
    const fluxRate = magnetFluxRate({ poleStrength: q, xNorth, xSouth, radius, velocity });
    return { flux, fluxRate, emf: faradayEMF(turns, fluxRate) };
}

/**
 * Largest |ε| while the magnet centre moves at constant speed over [xFrom, xTo]
 * (what a PEAK-HOLD voltmeter shows after that stretch of motion). ε = N·v·g(X), so the
 * maximum of |g| is located by a dense scan refined with golden-section search —
 * independent of the animation frame rate.
 * @param {{turns:number, moment:number, poleSeparation:number, radius:number, speed:number,
 *          xFrom:number, xTo:number, northOnLeft?:boolean}} p
 * @returns {{emf:number, position:number}} peak |ε| [V] and the magnet position [m] where it occurs
 */
export function inductionPeakEMF({ turns, moment, poleSeparation, radius, speed, xFrom, xTo, northOnLeft = true }) {
    const lo = Math.min(xFrom, xTo);
    const hi = Math.max(xFrom, xTo);
    if (![turns, moment, poleSeparation, radius, speed, lo, hi].every(isNum)) return { emf: NaN, position: NaN };
    const f = (X) => Math.abs(movingMagnetInduction({ turns, moment, poleSeparation, radius, position: X, velocity: speed, northOnLeft }).emf);
    if (hi === lo) return { emf: f(lo), position: lo };
    const STEPS = 400;
    let best = lo;
    let bestV = -1;
    for (let i = 0; i <= STEPS; i++) {
        const X = lo + ((hi - lo) * i) / STEPS;
        const v = f(X);
        if (v > bestV) { bestV = v; best = X; }
    }
    let a = Math.max(lo, best - (hi - lo) / STEPS);
    let b = Math.min(hi, best + (hi - lo) / STEPS);
    const g = (Math.sqrt(5) - 1) / 2;
    for (let k = 0; k < 60; k++) {
        const c = b - g * (b - a);
        const d = a + g * (b - a);
        if (f(c) > f(d)) b = d; else a = c;
    }
    const X = (a + b) / 2;
    const v = f(X);
    return v >= bestV ? { emf: v, position: X } : { emf: bestV, position: best };
}

/**
 * Constant-speed back-and-forth motion between xMin and xMax (triangle wave), starting at
 * xMin moving in +x. Closed-form in t (no frame accumulation).
 * @param {number} t time [s]
 * @param {number} xMin [m]
 * @param {number} xMax [m]
 * @param {number} speed |v| [m/s]
 * @returns {{x:number, v:number}}
 */
export function shuttleMotion(t, xMin, xMax, speed) {
    const span = xMax - xMin;
    if (!(speed > 0) || !(span > 0) || !isNum(t) || t < 0) return { x: xMin, v: 0 };
    const period = (2 * span) / speed;
    const tau = t % period;
    if (tau < period / 2) return { x: xMin + speed * tau, v: speed };
    return { x: xMax - speed * (tau - period / 2), v: -speed };
}

/**
 * Lenz's law: direction of the induced field inside the coil, opposing the flux change.
 * @param {number} dPhiDt [Wb/s] (flux measured along +x)
 * @returns {-1|0|1} +1 → induced B' along +x, −1 → along −x, 0 → no change
 */
export function lenzInducedFieldDirection(dPhiDt) {
    if (!isNum(dPhiDt) || dPhiDt === 0) return 0;
    return dPhiDt > 0 ? -1 : 1;
}

/**
 * Magnetic poles formed on the coil ends by the induced current (B' exits at the N face).
 * @param {-1|0|1} inducedDir from {@link lenzInducedFieldDirection}
 * @returns {{left:string|null, right:string|null}}
 */
export function coilFacePoles(inducedDir) {
    if (inducedDir > 0) return { left: 'S', right: 'N' };
    if (inducedDir < 0) return { left: 'N', right: 'S' };
    return { left: null, right: null };
}

/**
 * Ohm's law for the induced current: I = ε / R.
 * @param {number} emf [V]
 * @param {number} resistance [Ω] > 0
 * @returns {number} I [A]
 */
export function inducedCurrent(emf, resistance) {
    if (!isNum(emf) || !isNum(resistance) || resistance <= 0) return NaN;
    return emf / resistance;
}

// ─────────────────────────────────────────────────────────────────────────────
// Transformer
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Mains supply in Uzbekistan: 220 V, 50 Hz single phase (CIA World Factbook,
 * https://www.cia.gov/the-world-factbook/about/archives/2023/countries/uzbekistan/travel-facts);
 * the three-phase line voltage is √3·220 ≈ 380 V.
 */
export const UZ_MAINS = Object.freeze({ voltage: 220, frequency: 50, lineVoltage: 380 });

/**
 * Geomagnetic field in Tashkent (41.2995° N, 69.2401° E, 455 m) on 2026-10-20, World
 * Magnetic Model WMM2025 (BGS calculator web service,
 * https://geomag.bgs.ac.uk/web_service/GMModels/wmm/2025/?latitude=41.2995&longitude=69.2401&altitude=0.455&date=2026-10-20&format=json):
 * F = 53 966 nT, H = 25 536 nT, Z = 47 542 nT, I = 61.76°, D = 5.69° E (IGRF-14 agrees to 10 nT).
 */
export const EARTH_FIELD_TASHKENT = Object.freeze({
    total: 53.966e-6, horizontal: 25.536e-6, vertical: 47.542e-6, inclinationDeg: 61.758, declinationDeg: 5.689,
});

/**
 * Transformer with a resistive load. Voltage follows the turns ratio,
 * Vs = Vp·Ns/Np; Is = Vs/R; Pout = Vs·Is; Pin = Pout/η; Ip = Pin/Vp.
 * For an ideal transformer (η = 1) this gives Ip/Is = Ns/Np and Pin = Pout.
 * @param {{primaryTurns:number, secondaryTurns:number, primaryVoltage:number,
 *          loadResistance?:number, efficiency?:number}} p
 *   Vp is RMS [V]; load R [Ω] (default ∞ = no load); η in (0, 1]
 * @returns {{ratio:number, secondaryVoltage:number, secondaryCurrent:number,
 *            primaryCurrent:number, inputPower:number, outputPower:number,
 *            type:'step-up'|'step-down'|'isolation'}|null}
 */
export function transformer({ primaryTurns, secondaryTurns, primaryVoltage, loadResistance = Infinity, efficiency = 1 }) {
    if (![primaryTurns, secondaryTurns, primaryVoltage].every(isNum) || primaryTurns <= 0 || secondaryTurns < 0) return null;
    if (!(efficiency > 0 && efficiency <= 1)) return null;
    if (!(loadResistance > 0)) return null;
    const ratio = secondaryTurns / primaryTurns;
    const secondaryVoltage = primaryVoltage * ratio;
    const secondaryCurrent = loadResistance === Infinity ? 0 : secondaryVoltage / loadResistance;
    const outputPower = secondaryVoltage * secondaryCurrent;
    const inputPower = outputPower / efficiency;
    const primaryCurrent = primaryVoltage === 0 ? 0 : inputPower / primaryVoltage;
    const type = ratio > 1 ? 'step-up' : ratio < 1 ? 'step-down' : 'isolation';
    return { ratio, secondaryVoltage, secondaryCurrent, primaryCurrent, inputPower, outputPower, type };
}

/**
 * Transformer efficiency η = Pout / Pin.
 * @param {number} outputPower [W]
 * @param {number} inputPower [W] > 0
 */
export function transformerEfficiency(outputPower, inputPower) {
    if (!isNum(outputPower) || !isNum(inputPower) || inputPower <= 0) return NaN;
    return outputPower / inputPower;
}

// ─────────────────────────────────────────────────────────────────────────────
// AC generator (rotating coil in a uniform field)
// ─────────────────────────────────────────────────────────────────────────────

/** Rotation frequency from revolutions per minute: f = rpm / 60 [Hz]. */
export function rpmToFrequency(rpm) {
    if (!isNum(rpm)) return NaN;
    return rpm / 60;
}

/** Angular frequency ω = 2π·f [rad/s]. */
export function angularFrequency(frequency) {
    if (!isNum(frequency)) return NaN;
    return 2 * Math.PI * frequency;
}

/**
 * Peak EMF of a coil rotating in a uniform field: ε0 = N·B·A·ω.
 * @param {{turns:number, field:number, area:number, omega:number}} p N, B [T], A [m²], ω [rad/s]
 * @returns {number} ε0 [V]
 */
export function generatorPeakEMF({ turns, field, area, omega }) {
    if (![turns, field, area, omega].every(isNum)) return NaN;
    return Math.abs(turns * field * area * omega);
}

/**
 * Flux linkage of the rotating coil, θ = ωt measured between the coil normal and B:
 *   NΦ(t) = N·B·A·cos(ωt).
 * @returns {number} [Wb]
 */
export function generatorFluxLinkage(t, { turns, field, area, omega }) {
    return turns * field * area * Math.cos(omega * t);
}

/**
 * Instantaneous EMF ε(t) = −d(NΦ)/dt = N·B·A·ω·sin(ωt).
 * @param {number} t [s]
 * @param {{turns:number, field:number, area:number, omega:number}} p
 * @returns {number} ε [V]
 */
export function generatorEMF(t, { turns, field, area, omega }) {
    if (!isNum(t)) return NaN;
    return turns * field * area * omega * Math.sin(omega * t);
}

/** RMS value of a sinusoid: Vrms = Vpeak / √2. */
export function rmsFromPeak(peak) {
    if (!isNum(peak)) return NaN;
    return peak / Math.SQRT2;
}
