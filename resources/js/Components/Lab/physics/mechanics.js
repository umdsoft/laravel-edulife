/**
 * Pure mechanics physics for the virtual lab simulations.
 *
 * Conventions
 *  - Every function takes and returns SI units (m, s, kg, N, J, rad) unless the
 *    name explicitly says otherwise (e.g. `toRad(deg)`).
 *  - Angles are in radians.
 *  - No function touches the DOM, Vue or the clock: everything is deterministic
 *    and unit-testable (see ./__tests__/mechanics.test.js).
 *  - Wherever a closed-form solution exists it is used; RK4 is used only for the
 *    non-integrable nonlinear pendulum.
 */

/** Standard gravitational acceleration used by the lab, m/s². */
export const G = 9.81;

/** Degrees → radians factor. */
export const DEG = Math.PI / 180;

/** @param {number} deg angle in degrees @returns {number} radians */
export const toRad = (deg) => deg * DEG;

/** @param {number} rad angle in radians @returns {number} degrees */
export const toDeg = (rad) => rad / DEG;

/** True if `x` is a finite number. */
const isNum = (x) => typeof x === 'number' && Number.isFinite(x);

/**
 * Return `value` when it is a finite number, otherwise `fallback`.
 * Unlike `value || fallback` this keeps legitimate zeros.
 * @param {*} value
 * @param {number} fallback
 * @returns {number}
 */
export function numberOr(value, fallback) {
    const n = typeof value === 'string' && value.trim() !== '' ? Number(value) : value;
    return isNum(n) ? n : fallback;
}

/**
 * Gravitational acceleration to use: the configured value if it is a finite
 * positive number, otherwise the standard G = 9.81 m/s².
 * @param {*} value configured g (m/s²)
 * @returns {number} g in m/s²
 */
export function resolveGravity(value) {
    const g = numberOr(value, NaN);
    return g > 0 ? g : G;
}

/**
 * Seconds elapsed between two requestAnimationFrame timestamps (milliseconds),
 * clamped to [0, maxDt] so a background tab / breakpoint does not make the
 * simulation jump. The first frame (prevMs == null) returns 0.
 * @param {number|null} prevMs previous timestamp, ms
 * @param {number} nowMs current timestamp, ms
 * @param {number} [maxDt=0.1] maximum step, s (real-time down to 10 fps)
 * @returns {number} dt in seconds
 */
export function frameDt(prevMs, nowMs, maxDt = 0.1) {
    if (!isNum(prevMs) || !isNum(nowMs)) return 0;
    const dt = (nowMs - prevMs) / 1000;
    if (!(dt > 0)) return 0;
    return Math.min(dt, maxDt);
}

/**
 * One classical 4th-order Runge–Kutta step for y' = f(t, y).
 *   k1 = f(t, y); k2 = f(t+h/2, y+h/2·k1); k3 = f(t+h/2, y+h/2·k2); k4 = f(t+h, y+h·k3)
 *   y(t+h) = y + h/6 · (k1 + 2k2 + 2k3 + k4)
 * @param {(t:number, y:number[])=>number[]} f derivative
 * @param {number} t
 * @param {number[]} y state vector
 * @param {number} h step
 * @returns {number[]} new state
 */
export function rk4Step(f, t, y, h) {
    const n = y.length;
    const add = (a, b, s) => {
        const r = new Array(n);
        for (let i = 0; i < n; i++) r[i] = a[i] + s * b[i];
        return r;
    };
    const k1 = f(t, y);
    const k2 = f(t + h / 2, add(y, k1, h / 2));
    const k3 = f(t + h / 2, add(y, k2, h / 2));
    const k4 = f(t + h, add(y, k3, h));
    const out = new Array(n);
    for (let i = 0; i < n; i++) out[i] = y[i] + (h / 6) * (k1[i] + 2 * k2[i] + 2 * k3[i] + k4[i]);
    return out;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Common kinematics / dynamics
 * ──────────────────────────────────────────────────────────────────────────── */

/** Linear momentum p = m·v (kg·m/s). */
export const momentum = (m, v) => m * v;

/** Translational kinetic energy KE = ½·m·v² (J). */
export const kineticEnergy = (m, v) => 0.5 * m * v * v;

/** Gravitational potential energy PE = m·g·h (J). */
export const potentialEnergy = (m, g, h) => m * g * h;

/**
 * Uniformly accelerated motion from (x0, v0):
 *   x(t) = x0 + v0·t + ½·a·t²,  v(t) = v0 + a·t
 * @returns {{x:number, v:number}}
 */
export function uniformAccelState(t, a, v0 = 0, x0 = 0) {
    return { x: x0 + v0 * t + 0.5 * a * t * t, v: v0 + a * t };
}

/**
 * Time needed to cover distance d (> 0) starting with v0 ≥ 0 at constant a:
 * smallest t ≥ 0 with v0·t + ½·a·t² = d.
 *   a = 0: t = d / v0;   a ≠ 0: t = (−v0 + √(v0² + 2ad)) / a
 * @returns {number} seconds, or Infinity if the distance is never reached
 */
export function timeToTravel(d, a, v0 = 0) {
    if (!(d > 0)) return 0;
    if (Math.abs(a) < 1e-15) return v0 > 0 ? d / v0 : Infinity;
    const disc = v0 * v0 + 2 * a * d;
    if (disc < 0) return Infinity; // decelerates to rest before reaching d
    return (-v0 + Math.sqrt(disc)) / a;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Simple (mathematical) pendulum
 * ──────────────────────────────────────────────────────────────────────────── */

/**
 * Arithmetic–geometric mean AGM(a, b), a, b ≥ 0 (quadratic convergence).
 * @returns {number}
 */
export function agm(a, b) {
    if (!(a >= 0) || !(b >= 0)) return NaN;
    for (let i = 0; i < 64; i++) {
        const an = 0.5 * (a + b);
        const bn = Math.sqrt(a * b);
        if (Math.abs(an - bn) <= 1e-16 * an) return an;
        a = an;
        b = bn;
    }
    return 0.5 * (a + b);
}

/**
 * Complete elliptic integral of the first kind K(k) with modulus k (0 ≤ k < 1):
 *   K(k) = ∫₀^{π/2} dφ / √(1 − k²·sin²φ) = π / (2·AGM(1, √(1 − k²)))
 * K(0) = π/2; K(k) → ∞ as k → 1.
 * @param {number} k modulus
 * @returns {number}
 */
export function ellipticK(k) {
    const kk = Math.abs(k);
    if (kk >= 1) return Infinity;
    return Math.PI / (2 * agm(1, Math.sqrt(1 - kk * kk)));
}

/**
 * Small-angle (harmonic) period of a simple pendulum: T₀ = 2π·√(L/g).
 * @param {number} L length, m
 * @param {number} g gravity, m/s²
 * @returns {number} s
 */
export function pendulumSmallAnglePeriod(L, g) {
    if (!(L > 0) || !(g > 0)) return 0;
    return 2 * Math.PI * Math.sqrt(L / g);
}

/**
 * Exact period of an undamped simple pendulum released from rest at θ₀:
 *   T = 4·√(L/g)·K(sin(θ₀/2))
 * (reduces to T₀ = 2π√(L/g) for θ₀ → 0; diverges for θ₀ → π).
 * @param {number} L length, m
 * @param {number} g gravity, m/s²
 * @param {number} theta0 amplitude, rad
 * @returns {number} s
 */
export function pendulumExactPeriod(L, g, theta0) {
    if (!(L > 0) || !(g > 0)) return 0;
    return 4 * Math.sqrt(L / g) * ellipticK(Math.sin(Math.abs(theta0) / 2));
}

/**
 * Angular acceleration of the (optionally linearly damped) nonlinear pendulum:
 *   θ'' = −(g/L)·sin θ − b·θ'
 * @param {number} theta rad
 * @param {number} omega rad/s
 * @param {number} L m
 * @param {number} g m/s²
 * @param {number} [damping=0] b, 1/s
 * @returns {number} rad/s²
 */
export function pendulumAngularAcceleration(theta, omega, L, g, damping = 0) {
    return -(g / L) * Math.sin(theta) - damping * omega;
}

/**
 * Mechanical energy of a simple pendulum (bob as point mass, zero PE at the
 * lowest point):
 *   KE = ½·m·(L·ω)²,  PE = m·g·L·(1 − cos θ)
 * @returns {{ke:number, pe:number, total:number, speed:number, height:number}}
 */
export function pendulumEnergy(m, L, g, theta, omega) {
    const speed = Math.abs(omega) * L;
    const height = L * (1 - Math.cos(theta));
    const ke = 0.5 * m * speed * speed;
    const pe = m * g * height;
    return { ke, pe, total: ke + pe, speed, height };
}

/**
 * Advance the nonlinear pendulum by `dt` seconds with RK4 sub-steps of at
 * most `hMax`, detecting upward zero crossings (θ: − → +, ω > 0). The crossing
 * instant is linearly interpolated inside the sub-step; since θ'' = 0 at θ = 0
 * the interpolation error is O(h³).
 *
 * Successive upward crossings are exactly one period apart.
 *
 * @param {{theta:number, omega:number, t:number}} state
 * @param {number} dt seconds to advance (≥ 0)
 * @param {{L:number, g:number, damping?:number, hMax?:number}} p
 * @returns {{theta:number, omega:number, t:number, crossings:number[]}}
 */
export function pendulumAdvance(state, dt, { L, g, damping = 0, hMax = 0.002 }) {
    let { theta, omega, t } = state;
    const crossings = [];
    if (!(dt > 0) || !(L > 0) || !(g > 0)) return { theta, omega, t, crossings };
    const steps = Math.max(1, Math.ceil(dt / hMax));
    const h = dt / steps;
    const f = (_t, y) => [y[1], pendulumAngularAcceleration(y[0], y[1], L, g, damping)];
    for (let i = 0; i < steps; i++) {
        const [nt, nw] = rk4Step(f, t, [theta, omega], h);
        if (theta < 0 && nt >= 0 && nw > 0) {
            const frac = -theta / (nt - theta);
            crossings.push(t + frac * h);
        }
        theta = nt;
        omega = nw;
        t += h;
    }
    return { theta, omega, t, crossings };
}

/* ────────────────────────────────────────────────────────────────────────────
 * Free fall (no air resistance, from rest)
 * ──────────────────────────────────────────────────────────────────────────── */

/** Distance fallen from rest: s = ½·g·t² (m). */
export const freeFallDistance = (t, g) => 0.5 * g * t * t;

/** Speed after falling for t from rest: v = g·t (m/s). */
export const freeFallVelocity = (t, g) => g * t;

/**
 * Time to fall height h from rest: t = √(2h/g).
 * @returns {number} s (0 for h ≤ 0)
 */
export function freeFallTime(h, g) {
    if (!(h > 0) || !(g > 0)) return 0;
    return Math.sqrt((2 * h) / g);
}

/** Impact speed after falling h from rest: v = √(2·g·h) (m/s). */
export function freeFallImpactSpeed(h, g) {
    if (!(h > 0) || !(g > 0)) return 0;
    return Math.sqrt(2 * g * h);
}

/**
 * Gravity from a measured fall: g = 2h / t².
 * @returns {number} m/s² (0 when t ≤ 0)
 */
export function gravityFromFall(h, t) {
    if (!(t > 0)) return 0;
    return (2 * h) / (t * t);
}

/**
 * Closed-form free-fall state at time t for a drop from height h; the motion
 * stops on the ground (t is clamped to the fall time).
 * @returns {{t:number, y:number, v:number, landed:boolean}} y = distance fallen
 */
export function freeFallState(t, h, g) {
    const tf = freeFallTime(h, g);
    const tc = Math.min(Math.max(t, 0), tf);
    const landed = t >= tf;
    return {
        t: tc,
        y: landed ? Math.max(h, 0) : freeFallDistance(tc, g),
        v: landed ? freeFallImpactSpeed(h, g) : freeFallVelocity(tc, g),
        landed,
    };
}

/* ────────────────────────────────────────────────────────────────────────────
 * Newton's second law (block on a horizontal surface, given friction force)
 * ──────────────────────────────────────────────────────────────────────────── */

/**
 * Acceleration of a block initially at rest pushed by a horizontal force F
 * against a friction force of magnitude f (static max = kinetic = f):
 *   |F| ≤ f  → the block stays at rest, a = 0
 *   |F| > f  → a = (F − sign(F)·f) / m
 * @param {number} F applied force, N
 * @param {number} m mass, kg (> 0)
 * @param {number} [f=0] friction force magnitude, N (≥ 0)
 * @returns {number} m/s²
 */
export function newtonAcceleration(F, m, f = 0) {
    if (!(m > 0)) return 0;
    const fr = Math.abs(f || 0);
    if (Math.abs(F) <= fr) return 0;
    return (F - Math.sign(F) * fr) / m;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Inclined plane
 * ──────────────────────────────────────────────────────────────────────────── */

/**
 * Forces on a block of mass m on an incline of angle θ with friction
 * coefficient μ (μ_static = μ_kinetic = μ). Directions: along the slope,
 * positive = down the slope.
 *   W = m·g,  F∥ = m·g·sin θ,  N = m·g·cos θ
 *   slides (tan θ > μ):   f = μ·N,  F_net = F∥ − f
 *   at rest (tan θ ≤ μ):  f = F∥ (static), F_net = 0
 * @returns {{weight:number, parallel:number, normal:number, friction:number, net:number, slides:boolean}}
 */
export function inclineForces(m, theta, mu, g) {
    const weight = m * g;
    const parallel = weight * Math.sin(theta);
    const normal = weight * Math.cos(theta);
    const maxFriction = Math.max(0, mu) * normal;
    const slides = parallel > maxFriction + 1e-12;
    const friction = slides ? maxFriction : parallel;
    return { weight, parallel, normal, friction, net: slides ? parallel - friction : 0, slides };
}

/**
 * Acceleration down an incline (block released from rest):
 *   a = g·(sin θ − μ·cos θ)  if tan θ > μ, else 0.
 * Independent of mass.
 * @param {number} theta rad
 * @param {number} mu friction coefficient
 * @param {number} g m/s²
 * @returns {number} m/s²
 */
export function inclineAcceleration(theta, mu, g) {
    const a = g * (Math.sin(theta) - Math.max(0, mu) * Math.cos(theta));
    return a > 1e-12 ? a : 0;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Spring–mass oscillator (undamped)
 * ──────────────────────────────────────────────────────────────────────────── */

/** Angular frequency ω = √(k/m) (rad/s). */
export function springOmega(k, m) {
    if (!(k > 0) || !(m > 0)) return 0;
    return Math.sqrt(k / m);
}

/** Period T = 2π·√(m/k) (s). */
export function springPeriod(k, m) {
    if (!(k > 0) || !(m > 0)) return 0;
    return 2 * Math.PI * Math.sqrt(m / k);
}

/**
 * Exact SHM solution:
 *   x(t) = A·cos(ωt + φ),  v(t) = −A·ω·sin(ωt + φ),  a(t) = −ω²·x(t)
 * @returns {{x:number, v:number, a:number}}
 */
export function springState(t, A, k, m, phi = 0) {
    const w = springOmega(k, m);
    const ph = w * t + phi;
    const x = A * Math.cos(ph);
    return { x, v: -A * w * Math.sin(ph), a: -w * w * x };
}

/**
 * Energy of the oscillator (elastic PE about equilibrium):
 *   KE = ½·m·v², PE = ½·k·x², E = ½·k·A²
 * @returns {{ke:number, pe:number, total:number}}
 */
export function springEnergy(k, m, x, v) {
    const ke = 0.5 * m * v * v;
    const pe = 0.5 * k * x * x;
    return { ke, pe, total: ke + pe };
}

/**
 * Upward zero crossings (x: − → +, v > 0) of x(t) = A·cos(ωt + φ) inside the
 * half-open interval (t0, t1]. They occur at ωt + φ = 3π/2 + 2πn.
 * @returns {number[]} crossing times, ascending
 */
export function springUpwardCrossings(t0, t1, k, m, phi = 0) {
    const w = springOmega(k, m);
    if (!(w > 0) || !(t1 > t0)) return [];
    const base = (3 * Math.PI) / 2 - phi;
    const out = [];
    let n = Math.floor((w * t0 - base) / (2 * Math.PI));
    for (let i = 0; i < 1e6; i++, n++) {
        const tc = (base + 2 * Math.PI * n) / w;
        if (tc > t1) break;
        if (tc > t0) out.push(tc);
    }
    return out;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Projectile (no air resistance)
 * ──────────────────────────────────────────────────────────────────────────── */

/**
 * Projectile position/velocity at time t, launched from (0, h0) with speed v0
 * at angle θ above the horizontal (y up):
 *   x = v0·cos θ·t,  y = h0 + v0·sin θ·t − ½·g·t²
 *   vx = v0·cos θ,   vy = v0·sin θ − g·t
 * @returns {{x:number, y:number, vx:number, vy:number}}
 */
export function projectileState(t, v0, theta, h0, g) {
    const vx = v0 * Math.cos(theta);
    const vy0 = v0 * Math.sin(theta);
    return { x: vx * t, y: h0 + vy0 * t - 0.5 * g * t * t, vx, vy: vy0 - g * t };
}

/**
 * Time of flight until y = 0 (h0 ≥ 0):
 *   T = (v0·sin θ + √(v0²·sin²θ + 2·g·h0)) / g
 * (= 2·v0·sin θ / g when h0 = 0).
 * @returns {number} s
 */
export function projectileFlightTime(v0, theta, h0, g) {
    if (!(g > 0)) return Infinity;
    const vy = v0 * Math.sin(theta);
    const disc = vy * vy + 2 * g * Math.max(0, h0);
    const T = (vy + Math.sqrt(disc)) / g;
    return T > 0 ? T : 0;
}

/**
 * Horizontal range R = v0·cos θ·T (T from projectileFlightTime).
 * For h0 = 0 this is the textbook R = v0²·sin 2θ / g.
 * @returns {number} m
 */
export function projectileRange(v0, theta, h0, g) {
    return v0 * Math.cos(theta) * projectileFlightTime(v0, theta, h0, g);
}

/**
 * Maximum height above the ground: H = h0 + (v0·sin θ)² / (2g) (for θ > 0,
 * otherwise h0).
 * @returns {number} m
 */
export function projectileMaxHeight(v0, theta, h0, g) {
    const vy = v0 * Math.sin(theta);
    if (!(vy > 0) || !(g > 0)) return Math.max(0, h0);
    return h0 + (vy * vy) / (2 * g);
}

/* ────────────────────────────────────────────────────────────────────────────
 * 1-D collisions
 * ──────────────────────────────────────────────────────────────────────────── */

/**
 * Final velocities of a 1-D collision with coefficient of restitution e
 * (e = 1 elastic, e = 0 perfectly inelastic):
 *   v1 = ((m1 − e·m2)·u1 + (1 + e)·m2·u2) / (m1 + m2)
 *   v2 = ((m2 − e·m1)·u2 + (1 + e)·m1·u1) / (m1 + m2)
 * Conserves momentum for any e; conserves KE for e = 1.
 * @returns {{v1:number, v2:number}}
 */
export function collisionVelocities(m1, m2, u1, u2, e = 1) {
    const M = m1 + m2;
    if (!(M > 0)) return { v1: u1, v2: u2 };
    return {
        v1: ((m1 - e * m2) * u1 + (1 + e) * m2 * u2) / M,
        v2: ((m2 - e * m1) * u2 + (1 + e) * m1 * u1) / M,
    };
}

/**
 * Kinetic energy lost in a 1-D collision:
 *   ΔKE = ½ · m1·m2/(m1+m2) · (1 − e²) · (u1 − u2)²
 * @returns {number} J (≥ 0)
 */
export function collisionEnergyLoss(m1, m2, u1, u2, e = 1) {
    const M = m1 + m2;
    if (!(M > 0)) return 0;
    const du = u1 - u2;
    return 0.5 * ((m1 * m2) / M) * (1 - e * e) * du * du;
}

/**
 * Time until two bodies on a line touch. Body 1 is on the left at x1 moving
 * with u1, body 2 on the right at x2 moving with u2; they touch when the
 * centre distance equals `contact` (sum of radii).
 *   t = ((x2 − x1) − contact) / (u1 − u2)   if u1 > u2
 * @returns {number} s (0 if already touching and approaching, Infinity if never)
 */
export function collisionTime(x1, x2, u1, u2, contact) {
    const closing = u1 - u2;
    if (!(closing > 0)) return Infinity;
    const gap = x2 - x1 - contact;
    return gap <= 0 ? 0 : gap / closing;
}

/* ────────────────────────────────────────────────────────────────────────────
 * Uniform circular motion
 * ──────────────────────────────────────────────────────────────────────────── */

/**
 * Uniform circular motion quantities:
 *   v = ω·r,  a_c = ω²·r = v²/r,  F_c = m·ω²·r,  T = 2π/ω,  f = ω/(2π)
 * @param {number} r radius, m
 * @param {number} omega angular velocity, rad/s
 * @param {number} m mass, kg
 * @returns {{v:number, a:number, F:number, T:number, f:number}}
 */
export function circularMotion(r, omega, m) {
    const w = Math.abs(omega);
    const a = w * w * r;
    return {
        v: w * r,
        a,
        F: m * a,
        T: w > 0 ? (2 * Math.PI) / w : Infinity,
        f: w / (2 * Math.PI),
    };
}

/* ────────────────────────────────────────────────────────────────────────────
 * Torque and rotation
 * ──────────────────────────────────────────────────────────────────────────── */

/**
 * Torque magnitude of a force F applied at distance r from the axis, at angle
 * θ between the position vector and the force: τ = F·r·sin θ (N·m).
 */
export const torque = (F, r, theta) => F * r * Math.sin(theta);

/** Lever arm (perpendicular distance to the line of action): d = r·|sin θ| (m). */
export const leverArm = (r, theta) => r * Math.abs(Math.sin(theta));

/**
 * Moment of inertia of a thin uniform rod (mass M, length L) about a
 * perpendicular axis at distance d from its centre (parallel-axis theorem):
 *   I = M·L²/12 + M·d²
 * @returns {number} kg·m²
 */
export function rodInertia(M, L, d = 0) {
    return (M * L * L) / 12 + M * d * d;
}

/**
 * Rotation from rest under a constant torque (α = τ/I):
 *   φ(t) = ½·α·t²,  ω(t) = α·t
 * @returns {{alpha:number, angle:number, omega:number}}
 */
export function rotationFromRest(t, tau, I) {
    const alpha = I > 0 ? tau / I : 0;
    return { alpha, angle: 0.5 * alpha * t * t, omega: alpha * t };
}

/* ────────────────────────────────────────────────────────────────────────────
 * Energy conservation: vertical drop with a constant resistive force
 * ──────────────────────────────────────────────────────────────────────────── */

/**
 * Drop from height h (from rest) with a constant resistive force equal to the
 * fraction k of the weight (F_r = k·m·g, 0 ≤ k < 1):
 *   a = g·(1 − k),  s = ½·a·t²,  v = a·t,  t_end = √(2h/a)
 *   PE = m·g·(h − s),  KE = ½·m·v²,  Q = k·m·g·s (heat)
 * Energy balance: PE + KE + Q = m·g·h at every instant.
 * @param {number} t s
 * @param {number} h m
 * @param {number} m kg
 * @param {number} g m/s²
 * @param {number} [k=0] resistance fraction
 * @returns {{t:number, s:number, v:number, a:number, pe:number, ke:number, heat:number, mechanical:number, total:number, landed:boolean, tEnd:number}}
 */
export function dropWithResistance(t, h, m, g, k = 0) {
    const kk = Math.min(Math.max(k || 0, 0), 1);
    const a = g * (1 - kk);
    const H = Math.max(0, h);
    const tEnd = a > 0 ? Math.sqrt((2 * H) / a) : Infinity;
    const tc = Math.min(Math.max(t, 0), tEnd);
    const landed = t >= tEnd;
    const s = landed ? H : 0.5 * a * tc * tc;
    const v = landed ? Math.sqrt(2 * a * H) : a * tc;
    const pe = m * g * (H - s);
    const ke = 0.5 * m * v * v;
    const heat = kk * m * g * s;
    return { t: tc, s, v, a, pe, ke, heat, mechanical: pe + ke, total: pe + ke + heat, landed, tEnd };
}
