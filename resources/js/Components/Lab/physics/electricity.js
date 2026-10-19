/**
 * Electricity & electrostatics physics for the virtual lab.
 *
 * Every function is pure and works in SI units (V, A, Ω, F, C, m, s, W, J, T)
 * unless the JSDoc explicitly says otherwise. The .vue simulations convert
 * slider units (mA, µF, µC, cm, %) to SI before calling these functions.
 *
 * All time-dependent quantities are closed-form functions of elapsed time,
 * so results never depend on the animation frame rate.
 */

// ─────────────────────────────────────────────────────────────────────────────
// Constants (CODATA 2018 / SI 2019 exact where applicable)
// ─────────────────────────────────────────────────────────────────────────────

/** Vacuum permittivity ε0 [F/m]. */
export const EPSILON_0 = 8.8541878128e-12;
/** Coulomb constant k = 1/(4πε0) [N·m²/C²] ≈ 8.9875e9. */
export const K_COULOMB = 1 / (4 * Math.PI * EPSILON_0);
/** Elementary charge e [C] (exact). */
export const ELEMENTARY_CHARGE = 1.602176634e-19;
/** Boltzmann constant k_B [J/K] (exact). */
export const BOLTZMANN = 1.380649e-23;

/** SI prefix multipliers used by the lab sliders. */
export const PREFIX = Object.freeze({ p: 1e-12, n: 1e-9, u: 1e-6, m: 1e-3, k: 1e3, M: 1e6 });

// ─────────────────────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────────────────────

const isNum = (x) => typeof x === 'number' && !Number.isNaN(x);

/**
 * Format a value with an automatic SI prefix.
 * formatSI(0.014983, 'N', 4) → { value: '14.98', unit: 'mN', text: '14.98 mN' }
 * @param {number} value  value in base SI unit
 * @param {string} unit   base unit symbol
 * @param {number} [digits=3] significant digits
 * @returns {{value: string, unit: string, text: string}}
 */
export function formatSI(value, unit, digits = 3) {
    if (!isNum(value)) return { value: '—', unit, text: `— ${unit}` };
    if (!Number.isFinite(value)) {
        const v = value > 0 ? '∞' : '−∞';
        return { value: v, unit, text: `${v} ${unit}` };
    }
    if (value === 0) return { value: '0', unit, text: `0 ${unit}` };
    const prefixes = [
        [1e9, 'G'], [1e6, 'M'], [1e3, 'k'], [1, ''], [1e-3, 'm'], [1e-6, 'µ'], [1e-9, 'n'], [1e-12, 'p'],
    ];
    const abs = Math.abs(value);
    let [mult, sym] = prefixes[prefixes.length - 1];
    for (const [m, s] of prefixes) {
        if (abs >= m * 0.9995) { mult = m; sym = s; break; }
    }
    const scaled = value / mult;
    const mag = Math.floor(Math.log10(Math.abs(scaled)));
    let decimals = Math.max(0, digits - 1 - mag);
    // Rounding can carry into the next decade (0.99996 → "1.0000"): drop one decimal.
    if (decimals > 0 && Math.abs(Number(scaled.toFixed(decimals))) >= 10 ** (mag + 1)) decimals -= 1;
    const str = scaled.toFixed(decimals);
    return { value: str, unit: sym + unit, text: `${str} ${sym}${unit}` };
}

/**
 * Round to fixed decimals for display / measurement emission. Returns a string,
 * or '0' for non-finite inputs so the backend `numeric` validation never fails.
 * @param {number} value
 * @param {number} decimals
 * @returns {string}
 */
export function toFixedSafe(value, decimals) {
    if (!Number.isFinite(value)) return '0';
    const s = value.toFixed(decimals);
    return /^-0(\.0+)?$/.test(s) ? s.slice(1) : s;
}

// ─────────────────────────────────────────────────────────────────────────────
// DC resistive circuits
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Ohm's law current  I = U / R.
 * R = 0 is a short circuit: returns ±Infinity (0 if U = 0). Negative R → NaN.
 * @param {number} U voltage [V]
 * @param {number} R resistance [Ω]
 * @returns {number} current [A]
 */
export function ohmCurrent(U, R) {
    if (!isNum(U) || !isNum(R) || R < 0) return NaN;
    if (R === 0) return U === 0 ? 0 : Math.sign(U) * Infinity;
    return U / R;
}

/**
 * Text for the "I = U / R = ..." line under the Ohm's law circuit.
 * With the switch open no current flows, so "6 / 100 = 0.00 mA" would be
 * wrong arithmetic (QA-S-13); say the circuit is open instead.
 * @returns {string}
 */
export function ohmFormulaLine(U, R, switchOn) {
    if (!switchOn) return "Kalit ochiq — zanjirdan tok o'tmaydi: I = 0 mA";
    const I = ohmCurrent(U, R);
    const mA = Number.isFinite(I) ? `${(I * 1000).toFixed(2)} mA` : '∞ (qisqa tutashuv)';
    return `I = U / R = ${U} / ${R} = ${mA}`;
}

/** Ohm's law  U = I·R  [V]. */
export function ohmVoltage(I, R) {
    return I * R;
}

/**
 * Ohm's law  R = U / I  [Ω]. I = 0 → Infinity (open circuit), unless U = 0 (NaN, undetermined).
 */
export function ohmResistance(U, I) {
    if (I === 0) return U === 0 ? NaN : Infinity;
    return U / I;
}

/**
 * Series equivalent resistance  R = R1 + R2 + … + Rn.
 * @param {number[]} resistances [Ω]
 * @returns {number} [Ω]
 */
export function seriesResistance(resistances) {
    return resistances.reduce((s, r) => s + r, 0);
}

/**
 * Parallel equivalent resistance  1/R = 1/R1 + 1/R2 + … + 1/Rn.
 * Any zero-ohm branch shorts the network (R = 0). Empty list → Infinity (open).
 * Infinite (open) branches contribute nothing.
 * @param {number[]} resistances [Ω]
 * @returns {number} [Ω]
 */
export function parallelResistance(resistances) {
    if (resistances.length === 0) return Infinity;
    if (resistances.some((r) => r === 0)) return 0;
    const g = resistances.reduce((s, r) => s + 1 / r, 0);
    return g === 0 ? Infinity : 1 / g;
}

/**
 * Voltage divider: voltage across R2 of a series pair  U2 = U·R2/(R1+R2).
 */
export function voltageDivider(U, R1, R2) {
    const Rt = R1 + R2;
    return Rt === 0 ? 0 : (U * R2) / Rt;
}

/**
 * Two-branch current divider: current through R1 when I splits between R1 ‖ R2
 *  I1 = I·R2/(R1+R2).
 */
export function currentDivider(I, R1, R2) {
    const Rt = R1 + R2;
    return Rt === 0 ? I / 2 : (I * R2) / Rt;
}

/**
 * Full solution of an ideal source U driving resistors in series.
 *  R = ΣRk,  I = U/R,  Uk = I·Rk,  Pk = I²·Rk,  P = U·I.
 * @param {number} U source voltage [V]
 * @param {number[]} resistances [Ω]
 */
export function seriesCircuit(U, resistances) {
    const totalResistance = seriesResistance(resistances);
    const current = ohmCurrent(U, totalResistance);
    const voltageDrops = resistances.map((r) => (r === 0 ? 0 : current * r));
    const powers = resistances.map((r) => (r === 0 ? 0 : current * current * r));
    return {
        totalResistance,
        current,
        voltageDrops,
        powers,
        totalPower: U * current,
    };
}

/**
 * Full solution of an ideal source U driving resistors in parallel.
 *  1/R = Σ1/Rk,  Ik = U/Rk,  I = ΣIk = U/R,  Pk = U²/Rk.
 * @param {number} U source voltage [V]
 * @param {number[]} resistances [Ω]
 */
export function parallelCircuit(U, resistances) {
    const totalResistance = parallelResistance(resistances);
    const branchCurrents = resistances.map((r) => ohmCurrent(U, r));
    const totalCurrent = branchCurrents.reduce((s, i) => s + i, 0);
    return {
        totalResistance,
        totalCurrent,
        branchCurrents,
        branchPowers: branchCurrents.map((i) => U * i),
        totalPower: U * totalCurrent,
    };
}

/**
 * Solve the linear system A·x = b by Gaussian elimination with partial pivoting.
 * Inputs are not mutated. Returns null when the matrix is singular.
 * @param {number[][]} A n×n coefficient matrix
 * @param {number[]} b  right-hand side (length n)
 * @returns {number[]|null}
 */
export function solveLinearSystem(A, b) {
    const n = b.length;
    const M = A.map((row, i) => [...row, b[i]]);
    const scale = Math.max(1e-300, ...A.flat().map(Math.abs));
    for (let col = 0; col < n; col++) {
        let piv = col;
        for (let r = col + 1; r < n; r++) {
            if (Math.abs(M[r][col]) > Math.abs(M[piv][col])) piv = r;
        }
        if (Math.abs(M[piv][col]) <= 1e-12 * scale) return null;
        [M[col], M[piv]] = [M[piv], M[col]];
        for (let r = col + 1; r < n; r++) {
            const f = M[r][col] / M[col][col];
            if (f === 0) continue;
            for (let c = col; c <= n; c++) M[r][c] -= f * M[col][c];
        }
    }
    const x = new Array(n).fill(0);
    for (let r = n - 1; r >= 0; r--) {
        let s = M[r][n];
        for (let c = r + 1; c < n; c++) s -= M[r][c] * x[c];
        x[r] = s / M[r][r];
    }
    return x;
}

/**
 * Kirchhoff analysis of the lab circuit: EMF E in series with R1, followed by
 * R2 ‖ R3. Unknown branch currents I1 (through E, R1), I2 (R2), I3 (R3) are
 * found from the exact linear system
 *   KCL (junction):      I1 − I2 − I3 = 0
 *   KVL (E, R1, R2):     R1·I1 + R2·I2 = E
 *   KVL (R2, R3 loop):   R2·I2 − R3·I3 = 0
 * Residuals of every law are returned so the UI can show they hold.
 * @param {number} E  EMF [V]
 * @param {number} R1 [Ω]
 * @param {number} R2 [Ω]
 * @param {number} R3 [Ω]
 * @returns {{i1:number,i2:number,i3:number,v1:number,v2:number,v3:number,
 *   totalResistance:number,kclResidual:number,kvlResiduals:number[]}|null}
 *   null when the network is singular (e.g. R1 = 0 and a branch shorted).
 */
export function solveKirchhoffCircuit(E, R1, R2, R3) {
    const A = [
        [1, -1, -1],
        [R1, R2, 0],
        [0, R2, -R3],
    ];
    const x = solveLinearSystem(A, [0, E, 0]);
    if (!x) return null;
    const [i1, i2, i3] = x;
    const v1 = i1 * R1;
    const v2 = i2 * R2;
    const v3 = i3 * R3;
    return {
        i1, i2, i3, v1, v2, v3,
        totalResistance: i1 === 0 ? Infinity : E / i1,
        kclResidual: i1 - i2 - i3,
        kvlResiduals: [E - v1 - v2, E - v1 - v3],
    };
}

// ─────────────────────────────────────────────────────────────────────────────
// Electric power & energy
// ─────────────────────────────────────────────────────────────────────────────

/** Power  P = U·I  [W]. */
export function powerUI(U, I) {
    return U * I;
}
/** Power  P = I²·R  [W]. */
export function powerI2R(I, R) {
    return I * I * R;
}
/** Power  P = U²/R  [W]; R = 0 → Infinity (short circuit). */
export function powerU2R(U, R) {
    if (R === 0) return U === 0 ? 0 : Infinity;
    return (U * U) / R;
}

/**
 * Electrical energy in kilowatt-hours  E[kWh] = P[W]·t[h] / 1000.
 * @param {number} powerW power [W]
 * @param {number} hours  duration [h]
 */
export function energyKWh(powerW, hours) {
    return (powerW * hours) / 1000;
}

/** Energy in joules  W = P·t  (t in seconds). 1 kWh = 3.6e6 J. */
export function energyJoules(powerW, seconds) {
    return powerW * seconds;
}

/**
 * Electricity cost = E[kWh] · tariff.
 * @param {number} powerW [W]
 * @param {number} hours [h]
 * @param {number} tariffPerKWh price per kWh (e.g. so'm/kWh)
 */
export function energyCost(powerW, hours, tariffPerKWh) {
    return energyKWh(powerW, hours) * tariffPerKWh;
}

// ─────────────────────────────────────────────────────────────────────────────
// Capacitors & RC transients
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Parallel-plate capacitance  C = ε0·εr·A/d.
 * @param {number} area plate area A [m²]
 * @param {number} gap  plate separation d [m] (> 0)
 * @param {number} [epsR=1] relative permittivity εr
 * @returns {number} [F]
 */
export function parallelPlateCapacitance(area, gap, epsR = 1) {
    if (gap <= 0) return Infinity;
    return (EPSILON_0 * epsR * area) / gap;
}

/** Capacitor charge  Q = C·V  [C]. */
export function capacitorCharge(C, V) {
    return C * V;
}

/** Stored energy  W = ½·C·V²  [J]. */
export function capacitorEnergy(C, V) {
    return 0.5 * C * V * V;
}

/** Capacitors in series  1/C = Σ 1/Ck. A zero capacitor blocks: C = 0. */
export function seriesCapacitance(caps) {
    if (caps.length === 0) return 0;
    if (caps.some((c) => c === 0)) return 0;
    return 1 / caps.reduce((s, c) => s + 1 / c, 0);
}

/** Capacitors in parallel  C = Σ Ck. */
export function parallelCapacitance(caps) {
    return caps.reduce((s, c) => s + c, 0);
}

/** RC time constant  τ = R·C  [s]. */
export function rcTimeConstant(R, C) {
    return R * C;
}

/**
 * RC charging from 0 V through R by an ideal source V0 (switch closed at t = 0):
 *   Vc(t) = V0·(1 − e^(−t/τ)),  I(t) = (V0/R)·e^(−t/τ),  Q = C·Vc,  τ = RC.
 * Negative t is treated as "before the switch closed" (Vc = 0, I = 0).
 * @param {number} V0 source voltage [V]
 * @param {number} R [Ω]
 * @param {number} C [F]
 * @param {number} t elapsed time [s]
 * @returns {{vc:number, vr:number, i:number, q:number, energy:number, tau:number}}
 */
export function rcCharging(V0, R, C, t) {
    const tau = rcTimeConstant(R, C);
    if (t < 0) return { vc: 0, vr: 0, i: 0, q: 0, energy: 0, tau };
    let vc;
    let vr;
    if (tau === 0) {
        vc = t > 0 ? V0 : 0;
        vr = V0 - vc;
    } else {
        vc = -V0 * Math.expm1(-t / tau); // V0·(1 − e^(−t/τ)), precise for t ≪ τ
        vr = V0 * Math.exp(-t / tau);
    }
    const i = R === 0 ? (t === 0 ? Infinity * Math.sign(V0) : 0) : vr / R;
    return { vc, vr, i, q: C * vc, energy: capacitorEnergy(C, vc), tau };
}

/**
 * RC discharge of a capacitor initially at V0 through R (t = 0 at switch):
 *   Vc(t) = V0·e^(−t/τ),  I(t) = −(V0/R)·e^(−t/τ)  (negative = opposite to charging).
 * @param {number} V0 initial capacitor voltage [V]
 * @param {number} R [Ω]
 * @param {number} C [F]
 * @param {number} t elapsed time [s]
 */
export function rcDischarging(V0, R, C, t) {
    const tau = rcTimeConstant(R, C);
    if (t < 0) return { vc: V0, vr: 0, i: 0, q: C * V0, energy: capacitorEnergy(C, V0), tau };
    const decay = tau === 0 ? (t === 0 ? 1 : 0) : Math.exp(-t / tau);
    const vc = V0 * decay;
    const i = R === 0 ? (t === 0 ? -Infinity * Math.sign(V0) : 0) : -vc / R;
    return { vc, vr: -vc, i, q: C * vc, energy: capacitorEnergy(C, vc), tau };
}

/**
 * Time for a charging capacitor to reach the fraction f of V0:
 *   t = −τ·ln(1 − f)   (f = 0.632 → t ≈ τ; f = 0.5 → t = τ·ln2).
 */
export function rcTimeToFraction(fraction, tau) {
    if (fraction >= 1) return Infinity;
    if (fraction <= 0) return 0;
    return -tau * Math.log1p(-fraction);
}

// ─────────────────────────────────────────────────────────────────────────────
// Electrostatics (point charges in the plane)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Coulomb's law magnitude  F = k·|q1·q2| / r².
 * @param {number} q1 [C]
 * @param {number} q2 [C]
 * @param {number} r separation [m]
 * @returns {number} [N] (Infinity for r = 0 with non-zero charges)
 */
export function coulombForceMagnitude(q1, q2, r) {
    const num = K_COULOMB * Math.abs(q1 * q2);
    if (r === 0) return num === 0 ? 0 : Infinity;
    return num / (r * r);
}

/**
 * Coulomb force vector on charge 1 due to charge 2:
 *   F₁₂ = k·q1·q2·(r1 − r2)/|r1 − r2|³
 * Like charges → F points away from charge 2 (repulsion); unlike → toward it.
 * @param {number} q1 [C]
 * @param {{x:number,y:number}} p1 position of charge 1 [m]
 * @param {number} q2 [C]
 * @param {{x:number,y:number}} p2 position of charge 2 [m]
 * @returns {{fx:number, fy:number, magnitude:number, attractive:boolean, repulsive:boolean}}
 */
export function coulombForce(q1, p1, q2, p2) {
    const dx = p1.x - p2.x;
    const dy = p1.y - p2.y;
    const r2 = dx * dx + dy * dy;
    const r = Math.sqrt(r2);
    const prod = q1 * q2;
    if (r === 0 || prod === 0) {
        return { fx: 0, fy: 0, magnitude: r === 0 && prod !== 0 ? Infinity : 0, attractive: false, repulsive: false };
    }
    const f = (K_COULOMB * prod) / (r2 * r);
    return {
        fx: f * dx,
        fy: f * dy,
        magnitude: Math.abs(f) * r,
        attractive: prod < 0,
        repulsive: prod > 0,
    };
}

/**
 * Net electrostatic force on charges[index] by superposition of all others.
 * @param {number} index
 * @param {{q:number,x:number,y:number}[]} charges
 * @returns {{fx:number, fy:number, magnitude:number}}
 */
export function netCoulombForce(index, charges) {
    const me = charges[index];
    let fx = 0;
    let fy = 0;
    charges.forEach((other, j) => {
        if (j === index) return;
        const f = coulombForce(me.q, me, other.q, other);
        fx += f.fx;
        fy += f.fy;
    });
    return { fx, fy, magnitude: Math.hypot(fx, fy) };
}

/**
 * Electric field by superposition  E = Σ k·qi·(r − ri)/|r − ri|³.
 * Field points away from positive and toward negative charges.
 * @param {{q:number,x:number,y:number}[]} charges [C, m, m]
 * @param {number} x field point [m]
 * @param {number} y field point [m]
 * @returns {{ex:number, ey:number, magnitude:number}} [V/m = N/C]
 */
export function electricField(charges, x, y) {
    let ex = 0;
    let ey = 0;
    for (const c of charges) {
        if (c.q === 0) continue;
        const dx = x - c.x;
        const dy = y - c.y;
        const r2 = dx * dx + dy * dy;
        if (r2 === 0) return { ex: NaN, ey: NaN, magnitude: Infinity };
        const f = (K_COULOMB * c.q) / (r2 * Math.sqrt(r2));
        ex += f * dx;
        ey += f * dy;
    }
    return { ex, ey, magnitude: Math.hypot(ex, ey) };
}

/**
 * Electric potential by superposition  V = Σ k·qi/|r − ri|  (V → 0 at infinity).
 * @returns {number} [V] (±Infinity exactly on a charge)
 */
export function electricPotential(charges, x, y) {
    let v = 0;
    for (const c of charges) {
        if (c.q === 0) continue;
        const r = Math.hypot(x - c.x, y - c.y);
        if (r === 0) return Math.sign(c.q) * Infinity;
        v += (K_COULOMB * c.q) / r;
    }
    return v;
}

/**
 * Seed points for field lines. Lines are seeded around the charges whose sign
 * matches the sign of the net charge (positive when the net charge is 0), with
 * a count proportional to |q| (Gauss's law: line density ∝ flux ∝ q).
 * direction = +1 → trace along E (from + charges), −1 → against E (from − charges).
 * @param {{q:number,x:number,y:number}[]} charges
 * @param {{linesForMax?:number, radius?:number, minLines?:number}} [opts]
 * @returns {{x:number,y:number,direction:number,source:number}[]}
 */
export function fieldLineSeeds(charges, { linesForMax = 16, radius = 0.25, minLines = 4 } = {}) {
    const nonZero = charges.filter((c) => c.q !== 0);
    if (nonZero.length === 0) return [];
    const net = nonZero.reduce((s, c) => s + c.q, 0);
    const sign = net < 0 ? -1 : 1;
    const qMax = Math.max(...nonZero.map((c) => Math.abs(c.q)));
    const seeds = [];
    charges.forEach((c, idx) => {
        if (c.q === 0 || Math.sign(c.q) !== sign) return;
        const n = Math.max(minLines, Math.round((linesForMax * Math.abs(c.q)) / qMax));
        for (let k = 0; k < n; k++) {
            const a = (2 * Math.PI * (k + 0.5)) / n;
            seeds.push({ x: c.x + radius * Math.cos(a), y: c.y + radius * Math.sin(a), direction: sign, source: idx });
        }
    });
    return seeds;
}

/**
 * Trace one electric field line (a curve everywhere tangent to E) with a
 * classical RK4 integrator on the unit direction field  dr/ds = ±E/|E|.
 * This is a geometric construction (arc length s), independent of time.
 * @param {{q:number,x:number,y:number}[]} charges
 * @param {number} x0 start [m]
 * @param {number} y0 start [m]
 * @param {{direction?:number, step?:number, maxSteps?:number, stopRadius?:number,
 *   bounds?:{xMin:number,xMax:number,yMin:number,yMax:number}}} [opts]
 * @returns {{points:{x:number,y:number}[], endCharge:number}} endCharge = index of the
 *   charge the line terminated on, or −1.
 */
export function traceFieldLine(charges, x0, y0, opts = {}) {
    const { direction = 1, step = 0.04, maxSteps = 600, stopRadius = 0.2, bounds = null } = opts;
    const dir = (x, y) => {
        const e = electricField(charges, x, y);
        if (!Number.isFinite(e.magnitude) || e.magnitude === 0) return null;
        return [(direction * e.ex) / e.magnitude, (direction * e.ey) / e.magnitude];
    };
    const points = [{ x: x0, y: y0 }];
    let x = x0;
    let y = y0;
    for (let s = 0; s < maxSteps; s++) {
        const k1 = dir(x, y);
        if (!k1) break;
        const k2 = dir(x + (step / 2) * k1[0], y + (step / 2) * k1[1]);
        if (!k2) break;
        const k3 = dir(x + (step / 2) * k2[0], y + (step / 2) * k2[1]);
        if (!k3) break;
        const k4 = dir(x + step * k3[0], y + step * k3[1]);
        if (!k4) break;
        x += (step / 6) * (k1[0] + 2 * k2[0] + 2 * k3[0] + k4[0]);
        y += (step / 6) * (k1[1] + 2 * k2[1] + 2 * k3[1] + k4[1]);
        points.push({ x, y });
        if (bounds && (x < bounds.xMin || x > bounds.xMax || y < bounds.yMin || y > bounds.yMax)) break;
        for (let i = 0; i < charges.length; i++) {
            const c = charges[i];
            if (c.q === 0) continue;
            // A line traced along +E ends on a negative charge, along −E on a positive one.
            if (Math.sign(c.q) === direction) continue;
            if (Math.hypot(x - c.x, y - c.y) < stopRadius) {
                points.push({ x: c.x, y: c.y });
                return { points, endCharge: i };
            }
        }
    }
    return { points, endCharge: -1 };
}

/**
 * Marching-squares iso-line extraction for a scalar field sampled on a regular
 * grid (used for equipotential lines V = const).
 * @param {number[]} values row-major samples, values[j*nx + i] at (x0 + i·dx, y0 + j·dy)
 * @param {number} nx columns
 * @param {number} ny rows
 * @param {number} x0
 * @param {number} y0
 * @param {number} dx
 * @param {number} dy
 * @param {number} level iso-value
 * @returns {number[][]} segments [x1, y1, x2, y2]
 */
export function contourSegments(values, nx, ny, x0, y0, dx, dy, level) {
    const segs = [];
    const lerp = (a, b, va, vb) => a + ((level - va) / (vb - va)) * (b - a);
    for (let j = 0; j < ny - 1; j++) {
        for (let i = 0; i < nx - 1; i++) {
            const v00 = values[j * nx + i];
            const v10 = values[j * nx + i + 1];
            const v01 = values[(j + 1) * nx + i];
            const v11 = values[(j + 1) * nx + i + 1];
            if (![v00, v10, v01, v11].every(Number.isFinite)) continue;
            const xa = x0 + i * dx;
            const xb = xa + dx;
            const ya = y0 + j * dy;
            const yb = ya + dy;
            const pts = [];
            // top edge (v00 → v10), right (v10 → v11), bottom (v01 → v11), left (v00 → v01)
            if ((v00 < level) !== (v10 < level)) pts.push([lerp(xa, xb, v00, v10), ya]);
            if ((v10 < level) !== (v11 < level)) pts.push([xb, lerp(ya, yb, v10, v11)]);
            if ((v01 < level) !== (v11 < level)) pts.push([lerp(xa, xb, v01, v11), yb]);
            if ((v00 < level) !== (v01 < level)) pts.push([xa, lerp(ya, yb, v00, v01)]);
            if (pts.length === 2) {
                segs.push([pts[0][0], pts[0][1], pts[1][0], pts[1][1]]);
            } else if (pts.length === 4) {
                // Saddle: disambiguate with the cell-centre value.
                const vc = (v00 + v10 + v01 + v11) / 4;
                if ((vc < level) === (v00 < level)) {
                    segs.push([pts[0][0], pts[0][1], pts[1][0], pts[1][1]]);
                    segs.push([pts[2][0], pts[2][1], pts[3][0], pts[3][1]]);
                } else {
                    segs.push([pts[0][0], pts[0][1], pts[3][0], pts[3][1]]);
                    segs.push([pts[1][0], pts[1][1], pts[2][0], pts[2][1]]);
                }
            }
        }
    }
    return segs;
}

/**
 * All field lines of a set of point charges inside a rectangular region:
 * seeds from fieldLineSeeds() traced with traceFieldLine().
 * Each path's points run from its seed charge; `direction` tells whether they run
 * along E (+1, seeded on + charges) or against E (−1, seeded on − charges).
 * @param {{q:number,x:number,y:number}[]} charges [C, m]
 * @param {{xMin:number,xMax:number,yMin:number,yMax:number}} bounds [m]
 * @param {{linesForMax?:number, radius?:number, step?:number, maxSteps?:number, stopRadius?:number}} [opts]
 * @returns {{points:{x:number,y:number}[], direction:number, source:number, endCharge:number}[]}
 */
export function fieldLinePaths(charges, bounds, opts = {}) {
    const { linesForMax = 16, radius = 0.25, step = 0.04, maxSteps = 600, stopRadius = 0.2 } = opts;
    return fieldLineSeeds(charges, { linesForMax, radius }).map((s) => {
        const line = traceFieldLine(charges, s.x, s.y, { direction: s.direction, step, maxSteps, stopRadius, bounds });
        return { points: line.points, direction: s.direction, source: s.source, endCharge: line.endCharge };
    });
}

/**
 * Default equipotential levels: ±V_ref·{¼, ½, 1, 2} with V_ref = k·|q|max / r_ref,
 * plus V = 0 when charges of both signs are present.
 * @param {{q:number}[]} charges [C]
 * @param {number} [refDistance=1] r_ref [m]
 * @returns {number[]} [V]
 */
export function defaultPotentialLevels(charges, refDistance = 1) {
    const qs = charges.map((c) => c.q).filter((q) => q !== 0);
    if (qs.length === 0) return [];
    const vRef = (K_COULOMB * Math.max(...qs.map(Math.abs))) / refDistance;
    const levels = [];
    const hasPos = qs.some((q) => q > 0);
    const hasNeg = qs.some((q) => q < 0);
    for (const f of [0.25, 0.5, 1, 2]) {
        if (hasPos) levels.push(f * vRef);
        if (hasNeg) levels.push(-f * vRef);
    }
    if (hasPos && hasNeg) levels.push(0);
    return levels;
}

/**
 * Equipotential lines V(x, y) = level of the superposed potential, sampled on a
 * grid of the given cell size and extracted with marching squares.
 * @param {{q:number,x:number,y:number}[]} charges [C, m]
 * @param {{xMin:number,xMax:number,yMin:number,yMax:number}} bounds [m]
 * @param {number[]} levels [V]
 * @param {number} [cell=0.1] grid spacing [m]
 * @returns {{level:number, segments:number[][]}[]} segments in metres
 */
export function equipotentialSegments(charges, bounds, levels, cell = 0.1) {
    const nx = Math.floor((bounds.xMax - bounds.xMin) / cell) + 1;
    const ny = Math.floor((bounds.yMax - bounds.yMin) / cell) + 1;
    const values = new Array(nx * ny);
    for (let j = 0; j < ny; j++) {
        for (let i = 0; i < nx; i++) {
            values[j * nx + i] = electricPotential(charges, bounds.xMin + i * cell, bounds.yMin + j * cell);
        }
    }
    return levels.map((level) => ({
        level,
        segments: contourSegments(values, nx, ny, bounds.xMin, bounds.yMin, cell, cell, level),
    }));
}

// ─────────────────────────────────────────────────────────────────────────────
// DC motor (single rectangular coil + split-ring commutator in a uniform field B)
// ─────────────────────────────────────────────────────────────────────────────

/** Default lab motor: N turns, coil side length L [m], coil width w [m], Bmax [T], damping b [N·m·s], spin-up τm [s]. */
export const DC_MOTOR_DEFAULTS = Object.freeze({
    turns: 50,
    sideLength: 0.05,
    width: 0.04,
    bMax: 0.5,
    damping: 5e-3,
    spinUpTime: 0.5,
});

/** Force on one coil side  F = N·B·I·L  [N] (Ampère/Lorentz force, I ⟂ B). */
export function motorSideForce(turns, B, I, L) {
    return turns * B * I * L;
}

/**
 * Instantaneous torque of a commutated coil  τ(θ) = N·B·I·A·|sin θ|,
 * θ = angle between coil normal and B (max when coil plane ∥ B).
 */
export function motorTorque(turns, B, I, area, theta) {
    return turns * B * I * area * Math.abs(Math.sin(theta));
}

/** Average torque over a revolution with a commutator  τ̄ = (2/π)·N·B·I·A. */
export function motorAverageTorque(turns, B, I, area) {
    return (2 / Math.PI) * turns * B * I * area;
}

/** Average back-EMF  ε̄ = (2/π)·N·B·A·ω  [V] (same constant as torque: ε̄·I = τ̄·ω). */
export function motorBackEmf(turns, B, area, omega) {
    return (2 / Math.PI) * turns * B * area * omega;
}

/** Steady angular speed under viscous load b:  τ̄ = b·ω  ⇒  ω = τ̄/b  [rad/s]. */
export function motorSteadyOmega(avgTorque, damping) {
    if (damping <= 0) return Infinity;
    return avgTorque / damping;
}

/** Convert angular speed ω [rad/s] to revolutions per minute  n = 60·ω/(2π). */
export function omegaToRpm(omega) {
    return (omega * 60) / (2 * Math.PI);
}

/**
 * Exact rotor kinematics for J·dω/dt = τ̄ − b·ω (first-order spin-up, τm = J/b):
 *   ω(t) = ωss + (ω0 − ωss)·e^(−t/τm)
 *   θ(t) = θ0 + ωss·t + (ω0 − ωss)·τm·(1 − e^(−t/τm))
 * @param {number} theta0 [rad]
 * @param {number} omega0 [rad/s]
 * @param {number} omegaSS [rad/s]
 * @param {number} tauM mechanical time constant [s] (0 → instant)
 * @param {number} t elapsed [s]
 * @returns {{theta:number, omega:number}}
 */
export function motorKinematics(theta0, omega0, omegaSS, tauM, t) {
    if (tauM <= 0) return { theta: theta0 + omegaSS * t, omega: omegaSS };
    const e = Math.exp(-t / tauM);
    return {
        omega: omegaSS + (omega0 - omegaSS) * e,
        theta: theta0 + omegaSS * t - (omega0 - omegaSS) * tauM * Math.expm1(-t / tauM),
    };
}

/**
 * Complete steady-state description of the lab motor for current I and magnet
 * strength fraction (0…1 of Bmax).
 */
export function dcMotorState(I, strengthFraction, params = DC_MOTOR_DEFAULTS) {
    const p = { ...DC_MOTOR_DEFAULTS, ...params };
    const B = p.bMax * strengthFraction;
    const area = p.sideLength * p.width;
    const sideForce = motorSideForce(p.turns, B, I, p.sideLength);
    const peakTorque = p.turns * B * I * area;
    const avgTorque = motorAverageTorque(p.turns, B, I, area);
    const omega = motorSteadyOmega(avgTorque, p.damping);
    return {
        B,
        area,
        sideForce,
        peakTorque,
        avgTorque,
        omega,
        rpm: omegaToRpm(omega),
        backEmf: motorBackEmf(p.turns, B, area, omega),
        mechanicalPower: avgTorque * omega,
        spinUpTime: p.spinUpTime,
    };
}

// ─────────────────────────────────────────────────────────────────────────────
// Semiconductor diodes
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Thermal voltage  V_T = k_B·T/e  (≈ 25.85 mV at 300 K).
 * @param {number} [T=300] temperature [K]
 */
export function thermalVoltage(T = 300) {
    return (BOLTZMANN * T) / ELEMENTARY_CHARGE;
}

/**
 * Shockley-model parameters (300 K). Chosen so that at I = 10 mA the forward
 * drop is ≈ 0.71 V (Si), ≈ 0.30 V (Ge), ≈ 1.79 V (red LED) — the textbook
 * "threshold" values 0.7 / 0.3 / 1.8 V are kept as nominalVf.
 */
export const DIODE_MODELS = Object.freeze({
    silicon: Object.freeze({ Is: 1e-14, n: 1, nominalVf: 0.7 }),
    germanium: Object.freeze({ Is: 1e-7, n: 1, nominalVf: 0.3 }),
    led: Object.freeze({ Is: 1e-17, n: 2, nominalVf: 1.8 }),
});

/**
 * Shockley diode equation  I = Is·(e^(V/(n·V_T)) − 1).
 * Reverse bias saturates at −Is (breakdown is not modelled).
 * @param {number} V diode voltage [V]
 * @param {number} Is saturation current [A]
 * @param {number} [n=1] ideality factor
 * @param {number} [VT] thermal voltage [V]
 * @returns {number} [A]
 */
export function shockleyCurrent(V, Is, n = 1, VT = thermalVoltage()) {
    return Is * Math.expm1(V / (n * VT));
}

/** Inverse Shockley  V = n·V_T·ln(1 + I/Is)  [V] (I > −Is). */
export function diodeVoltageFromCurrent(I, Is, n = 1, VT = thermalVoltage()) {
    if (I <= -Is) return -Infinity;
    return n * VT * Math.log1p(I / Is);
}

/**
 * Principal branch of the Lambert W function, W(x)·e^W(x) = x, given ln(x).
 * Working in log-space allows arguments like e^300 that overflow a double.
 * Accurate to ~1e-15 relative.
 * @param {number} lnX natural log of the (positive) argument
 * @returns {number}
 */
export function lambertW0FromLog(lnX) {
    if (lnX === -Infinity) return 0;
    if (lnX > 2) {
        // Solve w + ln w = lnX by Newton.
        let w = lnX - Math.log(lnX);
        for (let k = 0; k < 50; k++) {
            const f = w + Math.log(w) - lnX;
            const dw = f / (1 + 1 / w);
            w -= dw;
            if (Math.abs(dw) <= 1e-15 * Math.abs(w)) break;
        }
        return w;
    }
    const x = Math.exp(lnX);
    if (x < 1e-8) return x - x * x + 1.5 * x * x * x; // series W(x) = x − x² + 3/2·x³ …
    // Halley iteration on w·e^w − x.
    let w = Math.log1p(x);
    for (let k = 0; k < 50; k++) {
        const ew = Math.exp(w);
        const f = w * ew - x;
        const dw = f / (ew * (w + 1) - ((w + 2) * f) / (2 * w + 2));
        w -= dw;
        if (Math.abs(dw) <= 1e-15 * Math.max(1e-300, Math.abs(w))) break;
    }
    return w;
}

/** Principal branch Lambert W0(x) for x ≥ 0. */
export function lambertW0(x) {
    if (x < 0) return NaN;
    if (x === 0) return 0;
    return lambertW0FromLog(Math.log(x));
}

/**
 * Exact operating point of source Vs → series resistor R → diode (Shockley).
 * Solves Vs = I·R + n·V_T·ln(1 + I/Is) in closed form with Lambert W:
 *   I = (n·V_T/R)·W( (Is·R/(n·V_T))·e^((Vs + Is·R)/(n·V_T)) ) − Is
 * @param {number} Vs source voltage [V] (negative = reverse bias)
 * @param {number} R series resistance [Ω] (0 → I = Shockley(Vs))
 * @param {{Is:number, n?:number}} model
 * @param {number} [VT] thermal voltage [V]
 * @returns {{current:number, diodeVoltage:number, resistorVoltage:number, diodePower:number}}
 */
export function diodeCircuit(Vs, R, model, VT = thermalVoltage()) {
    const { Is, n = 1 } = model;
    const nVT = n * VT;
    let current;
    if (R <= 0) {
        current = shockleyCurrent(Vs, Is, n, VT);
        return { current, diodeVoltage: Vs, resistorVoltage: 0, diodePower: current * Vs };
    }
    const lnArg = Math.log((Is * R) / nVT) + (Vs + Is * R) / nVT;
    const w = lambertW0FromLog(lnArg);
    current = (nVT / R) * w - Is;
    const resistorVoltage = current * R;
    // Use the Shockley inverse for V_D when forward biased (better precision than Vs − I·R).
    const diodeVoltage = current > 0 ? diodeVoltageFromCurrent(current, Is, n, VT) : Vs - resistorVoltage;
    return { current, diodeVoltage, resistorVoltage, diodePower: current * diodeVoltage };
}

/**
 * Textbook constant-voltage-drop ("piecewise") model:
 *   I = (Vs − Vf)/R  for Vs > Vf,  otherwise 0.
 */
export function piecewiseDiodeCurrent(Vs, R, Vf) {
    if (Vs <= Vf) return 0;
    if (R <= 0) return Infinity;
    return (Vs - Vf) / R;
}

/**
 * Half-wave rectifier output across the load R for an instantaneous input vin:
 *   v_out = I·R where I is the exact diode-circuit current (≈ vin − Vf when
 *   forward biased, ≈ −Is·R ≈ 0 when reverse biased).
 */
export function halfWaveRectifierOutput(vin, R, model, VT = thermalVoltage()) {
    return diodeCircuit(vin, R, model, VT).resistorVoltage;
}
