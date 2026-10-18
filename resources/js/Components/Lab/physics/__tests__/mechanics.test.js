import { describe, it, expect } from 'vitest';
import {
    G, DEG, toRad, toDeg, numberOr, resolveGravity, frameDt, rk4Step,
    momentum, kineticEnergy, potentialEnergy, uniformAccelState, timeToTravel,
    agm, ellipticK, pendulumSmallAnglePeriod, pendulumExactPeriod,
    pendulumAngularAcceleration, pendulumEnergy, pendulumAdvance,
    freeFallDistance, freeFallVelocity, freeFallTime, freeFallImpactSpeed, gravityFromFall, freeFallState,
    newtonAcceleration,
    inclineForces, inclineAcceleration,
    springOmega, springPeriod, springState, springEnergy, springUpwardCrossings,
    projectileState, projectileFlightTime, projectileRange, projectileMaxHeight,
    collisionVelocities, collisionEnergyLoss, collisionTime,
    circularMotion,
    torque, leverArm, rodInertia, rotationFromRest,
    dropWithResistance,
} from '../mechanics.js';

const close = (actual, expected, digits = 6) => expect(actual).toBeCloseTo(expected, digits);

describe('helpers', () => {
    it('G is 9.81 m/s² and DEG = π/180', () => {
        expect(G).toBe(9.81);
        close(DEG, Math.PI / 180, 15);
    });

    it('degree/radian conversion: 180° = π rad, π/6 rad = 30°', () => {
        close(toRad(180), Math.PI, 12);
        close(toDeg(Math.PI / 6), 30, 12);
    });

    it('numberOr keeps legitimate zeros (unlike ||) and parses numeric strings', () => {
        expect(numberOr(0, 5)).toBe(0);
        expect(numberOr(undefined, 5)).toBe(5);
        expect(numberOr(NaN, 5)).toBe(5);
        expect(numberOr('2.5', 5)).toBe(2.5);
        expect(numberOr('', 5)).toBe(5);
        expect(numberOr(null, 5)).toBe(5);
    });

    it('resolveGravity falls back to 9.81 for missing / non-positive / NaN', () => {
        expect(resolveGravity(9.8)).toBe(9.8);
        expect(resolveGravity('1.62')).toBe(1.62);
        expect(resolveGravity(undefined)).toBe(9.81);
        expect(resolveGravity(0)).toBe(9.81);
        expect(resolveGravity(-9.8)).toBe(9.81);
        expect(resolveGravity(NaN)).toBe(9.81);
    });

    it('frameDt converts ms → s, clamps to maxDt, first frame and backwards = 0', () => {
        expect(frameDt(null, 1000)).toBe(0);
        close(frameDt(1000, 1016), 0.016, 12);
        expect(frameDt(0, 5000)).toBe(0.1);
        expect(frameDt(0, 5000, 0.05)).toBe(0.05);
        close(frameDt(0, 58.8), 0.0588, 12); // 17 fps still real-time
        expect(frameDt(2000, 1000)).toBe(0);
    });

    it('rk4Step on y\' = y, h = 0.1: y1 = 1 + h + h²/2 + h³/6 + h⁴/24 = 1.105170833…', () => {
        const [y1] = rk4Step((_t, y) => [y[0]], 0, [1], 0.1);
        close(y1, 1 + 0.1 + 0.005 + 0.1 ** 3 / 6 + 0.1 ** 4 / 24, 12);
    });

    it('p = mv, KE = ½mv², PE = mgh', () => {
        expect(momentum(2, -3)).toBe(-6);
        expect(kineticEnergy(2, 3)).toBe(9);
        close(potentialEnergy(2, 9.81, 10), 196.2, 10);
    });

    it('uniformAccelState: x = x0 + v0 t + ½at², v = v0 + at', () => {
        const s = uniformAccelState(2, 3, 1, 5); // 5 + 2 + 6 = 13, v = 7
        close(s.x, 13, 12);
        close(s.v, 7, 12);
    });

    it('timeToTravel solves v0 t + ½ a t² = d', () => {
        close(timeToTravel(8, 4, 0), 2, 12); // ½·4·t² = 8 → t = 2
        close(timeToTravel(10, 0, 5), 2, 12); // t = d / v0
        close(timeToTravel(12, 2, 2), 2.6055512754639896, 10); // t = (−2 + √(4 + 48))/2
        expect(timeToTravel(10, 0, 0)).toBe(Infinity);
        expect(timeToTravel(10, -2, 4)).toBe(Infinity); // stops after 4 m
        expect(timeToTravel(0, 3)).toBe(0);
    });
});

describe('pendulum', () => {
    it('AGM(24, 6) = 13.458171481725615 (classic reference value)', () => {
        close(agm(24, 6), 13.458171481725615, 12);
        close(agm(1, 1), 1, 15);
        expect(agm(-1, 1)).toBeNaN();
    });

    it('K(k) = π / (2·AGM(1, √(1−k²))): K(0) = π/2, K(0.5) = 1.685750354812596, K(1/√2) = 1.854074677301372', () => {
        close(ellipticK(0), Math.PI / 2, 14);
        close(ellipticK(0.5), 1.685750354812596, 12);
        close(ellipticK(Math.SQRT1_2), 1.854074677301372, 12);
        expect(ellipticK(1)).toBe(Infinity);
    });

    it('T₀ = 2π√(L/g): L = 1 m, g = 9.81 → 2.006066680 s', () => {
        close(pendulumSmallAnglePeriod(1, 9.81), 2 * Math.PI * Math.sqrt(1 / 9.81), 12);
        close(pendulumSmallAnglePeriod(1, 9.81), 2.00606668, 6);
        // T ∝ √L: quadrupling L doubles T
        close(pendulumSmallAnglePeriod(4, 9.81) / pendulumSmallAnglePeriod(1, 9.81), 2, 12);
    });

    it('T₀ edge cases: L ≤ 0 or g ≤ 0 gives 0 (no NaN)', () => {
        expect(pendulumSmallAnglePeriod(0, 9.81)).toBe(0);
        expect(pendulumSmallAnglePeriod(1, 0)).toBe(0);
        expect(pendulumExactPeriod(-1, 9.81, 0.3)).toBe(0);
    });

    it('exact T = 4√(L/g)·K(sin θ₀/2): θ₀ → 0 reduces to T₀', () => {
        close(pendulumExactPeriod(1, 9.81, 0), pendulumSmallAnglePeriod(1, 9.81), 12);
    });

    it('exact T/T₀ at θ₀ = 90° equals (2/π)·K(1/√2) = 1.180340599', () => {
        close(pendulumExactPeriod(1, 9.81, Math.PI / 2) / pendulumSmallAnglePeriod(1, 9.81), 1.180340599, 8);
    });

    it('exact T/T₀ at θ₀ = 10° matches series 1 + θ²/16 + 11θ⁴/3072 = 1.0019072', () => {
        const th = toRad(10);
        const series = 1 + th ** 2 / 16 + (11 * th ** 4) / 3072;
        close(pendulumExactPeriod(2, 9.81, th) / pendulumSmallAnglePeriod(2, 9.81), series, 7);
    });

    it('exact T is symmetric in ±θ₀ and diverges at θ₀ = 180°', () => {
        close(pendulumExactPeriod(1, 9.81, -0.4), pendulumExactPeriod(1, 9.81, 0.4), 14);
        expect(pendulumExactPeriod(1, 9.81, Math.PI)).toBe(Infinity);
    });

    it('θ\'\' = −(g/L) sin θ − bω', () => {
        close(pendulumAngularAcceleration(Math.PI / 2, 0, 1, 9.81), -9.81, 12);
        close(pendulumAngularAcceleration(0, 2, 1, 9.81, 0.5), -1, 12);
    });

    it('energy: KE = ½m(Lω)², PE = mgL(1 − cos θ)', () => {
        const e = pendulumEnergy(0.5, 1, 9.81, Math.PI / 3, 2); // PE = 0.5·9.81·0.5, KE = ½·0.5·4
        close(e.pe, 2.4525, 12);
        close(e.ke, 1, 12);
        close(e.total, 3.4525, 12);
        close(e.speed, 2, 12);
        close(e.height, 0.5, 12);
    });

    it('RK4 nonlinear motion: upward zero crossings are one exact period apart (θ₀ = 25°)', () => {
        const L = 1, g = 9.81, th0 = toRad(25);
        let s = { theta: th0, omega: 0, t: 0 };
        const crossings = [];
        // Advance in irregular "frames" to show the result is frame-rate independent
        const frames = [1 / 60, 1 / 144, 1 / 30, 0.05, 1 / 75];
        for (let i = 0; s.t < 12; i++) {
            const r = pendulumAdvance(s, frames[i % frames.length], { L, g });
            crossings.push(...r.crossings);
            s = r;
        }
        expect(crossings.length).toBeGreaterThanOrEqual(5);
        const exact = pendulumExactPeriod(L, g, th0);
        for (let i = 1; i < crossings.length; i++) close(crossings[i] - crossings[i - 1], exact, 6);
        // First upward crossing from rest at +θ₀ is at ¾T
        close(crossings[0], 0.75 * exact, 6);
        // The exact period differs from the small-angle one by ~1.2 % at 25°
        expect(exact / pendulumSmallAnglePeriod(L, g)).toBeGreaterThan(1.011);
    });

    it('RK4 conserves mechanical energy E = mgL(1 − cos θ₀) (relative drift < 1e-8 over 20 s)', () => {
        const m = 0.5, L = 1.2, g = 9.81, th0 = toRad(60);
        const E0 = pendulumEnergy(m, L, g, th0, 0).total;
        let s = { theta: th0, omega: 0, t: 0 };
        let maxDrift = 0;
        while (s.t < 20) {
            s = pendulumAdvance(s, 1 / 60, { L, g });
            const E = pendulumEnergy(m, L, g, s.theta, s.omega).total;
            maxDrift = Math.max(maxDrift, Math.abs(E - E0) / E0);
        }
        expect(maxDrift).toBeLessThan(1e-8);
    });

    it('max speed at bottom v = √(2gL(1 − cos θ₀))', () => {
        const L = 1, g = 9.81, th0 = toRad(20);
        let s = { theta: th0, omega: 0, t: 0 };
        let vmax = 0;
        while (s.t < 3) {
            s = pendulumAdvance(s, 1 / 240, { L, g });
            vmax = Math.max(vmax, Math.abs(s.omega) * L);
        }
        close(vmax, Math.sqrt(2 * g * L * (1 - Math.cos(th0))), 4);
    });

    it('pendulumAdvance edge cases: dt ≤ 0 or invalid L/g leaves the state unchanged', () => {
        const s = { theta: 0.2, omega: 0, t: 1 };
        expect(pendulumAdvance(s, 0, { L: 1, g: 9.81 })).toMatchObject({ theta: 0.2, omega: 0, t: 1, crossings: [] });
        expect(pendulumAdvance(s, 0.1, { L: 0, g: 9.81 })).toMatchObject({ theta: 0.2, omega: 0, t: 1 });
    });
});

describe('free fall', () => {
    it('s = ½gt², v = gt: t = 2 s → 19.62 m, 19.62 m/s', () => {
        close(freeFallDistance(2, 9.81), 19.62, 12);
        close(freeFallVelocity(2, 9.81), 19.62, 12);
    });

    it('t = √(2h/g): h = 5 m → 1.009637 s; v = √(2gh) = 9.904544 m/s', () => {
        close(freeFallTime(5, 9.81), Math.sqrt(10 / 9.81), 12);
        close(freeFallTime(5, 9.81), 1.0096375, 6);
        close(freeFallImpactSpeed(5, 9.81), 9.9045444, 6);
    });

    it('g = 2h/t² recovers g from the analytic fall time', () => {
        close(gravityFromFall(5, freeFallTime(5, 9.81)), 9.81, 12);
        close(gravityFromFall(10, 1.4278), 2 * 10 / 1.4278 ** 2, 12);
        expect(gravityFromFall(5, 0)).toBe(0);
    });

    it('freeFallState is closed-form and clamps on landing', () => {
        const mid = freeFallState(0.5, 5, 9.81);
        close(mid.y, 0.5 * 9.81 * 0.25, 12);
        close(mid.v, 4.905, 12);
        expect(mid.landed).toBe(false);
        const end = freeFallState(10, 5, 9.81);
        expect(end.landed).toBe(true);
        expect(end.y).toBe(5);
        close(end.t, freeFallTime(5, 9.81), 12);
        close(end.v, freeFallImpactSpeed(5, 9.81), 12);
    });

    it('edge cases: h ≤ 0 → zero time and speed, negative t clamps to 0', () => {
        expect(freeFallTime(0, 9.81)).toBe(0);
        expect(freeFallImpactSpeed(-1, 9.81)).toBe(0);
        expect(freeFallState(-1, 5, 9.81)).toMatchObject({ t: 0, y: 0, v: 0, landed: false });
    });
});

describe("Newton's second law", () => {
    it('a = F/m without friction: 10 N / 2 kg = 5 m/s²', () => {
        expect(newtonAcceleration(10, 2)).toBe(5);
    });

    it('a = (F − f)/m with kinetic friction: (10 − 4)/2 = 3 m/s²', () => {
        expect(newtonAcceleration(10, 2, 4)).toBe(3);
    });

    it('static friction: F ≤ f → block stays at rest (a = 0, never negative)', () => {
        expect(newtonAcceleration(10, 2, 10)).toBe(0);
        expect(newtonAcceleration(5, 2, 8)).toBe(0);
    });

    it('negative force: a = (F + f)/m = (−10 + 4)/2 = −3 m/s²', () => {
        expect(newtonAcceleration(-10, 2, 4)).toBe(-3);
    });

    it('m ≤ 0 is guarded (no Infinity/NaN)', () => {
        expect(newtonAcceleration(10, 0)).toBe(0);
    });
});

describe('inclined plane', () => {
    it('frictionless: a = g sin θ = 9.81·sin 30° = 4.905 m/s²', () => {
        close(inclineAcceleration(toRad(30), 0, 9.81), 4.905, 12);
    });

    it('with friction: a = g(sin θ − μ cos θ) = 9.81(0.5 − 0.1·0.8660254) = 4.0554291 m/s²', () => {
        close(inclineAcceleration(toRad(30), 0.1, 9.81), 9.81 * (0.5 - 0.1 * Math.cos(toRad(30))), 12);
        close(inclineAcceleration(toRad(30), 0.1, 9.81), 4.0554291, 6);
    });

    it('tan θ ≤ μ: block does not slide (tan 10° = 0.176 < 0.5; tan 45° = μ = 1)', () => {
        expect(inclineAcceleration(toRad(10), 0.5, 9.81)).toBe(0);
        expect(inclineAcceleration(toRad(45), 1, 9.81)).toBe(0);
    });

    it('force decomposition: W = mg, F∥ = mg sin θ, N = mg cos θ, f = μN, F_net = m·a', () => {
        const f = inclineForces(2, toRad(30), 0.1, 9.81);
        close(f.weight, 19.62, 12);
        close(f.parallel, 9.81, 12);
        close(f.normal, 19.62 * Math.cos(toRad(30)), 12);
        close(f.friction, 0.1 * f.normal, 12);
        close(f.net, 2 * inclineAcceleration(toRad(30), 0.1, 9.81), 12);
        expect(f.slides).toBe(true);
    });

    it('at rest: static friction balances F∥ exactly, F_net = 0', () => {
        const f = inclineForces(1, toRad(10), 0.5, 9.81);
        expect(f.slides).toBe(false);
        close(f.friction, 9.81 * Math.sin(toRad(10)), 12);
        expect(f.net).toBe(0);
    });

    it('acceleration does not depend on mass (only via forces)', () => {
        const a1 = inclineForces(1, toRad(40), 0.2, 9.81).net / 1;
        const a5 = inclineForces(5, toRad(40), 0.2, 9.81).net / 5;
        close(a1, a5, 12);
    });
});

describe('spring oscillator', () => {
    it('ω = √(k/m) = √(50/0.5) = 10 rad/s, T = 2π√(m/k) = 0.6283185 s', () => {
        close(springOmega(50, 0.5), 10, 12);
        close(springPeriod(50, 0.5), Math.PI / 5, 12);
    });

    it('T quadruples mass → doubles period', () => {
        close(springPeriod(50, 2) / springPeriod(50, 0.5), 2, 12);
    });

    it('x = A cos ωt, v = −Aω sin ωt, a = −ω²x at t = 0.1 s (A = 0.1 m, ω = 10)', () => {
        const s = springState(0.1, 0.1, 50, 0.5);
        close(s.x, 0.1 * Math.cos(1), 12);
        close(s.v, -1 * Math.sin(1), 12);
        close(s.a, -100 * 0.1 * Math.cos(1), 12);
    });

    it('energy ½kx² + ½mv² = ½kA² = 0.25 J at every instant', () => {
        for (const t of [0, 0.013, 0.2, 1.7, 33.3]) {
            const s = springState(t, 0.1, 50, 0.5);
            close(springEnergy(50, 0.5, s.x, s.v).total, 0.25, 12);
        }
    });

    it('upward zero crossings at ωt = 3π/2 + 2πn → 0.4712389 s, 1.0995574 s', () => {
        const c = springUpwardCrossings(0, 1.2, 50, 0.5);
        expect(c).toHaveLength(2);
        close(c[0], (3 * Math.PI) / 20, 12);
        close(c[1] - c[0], springPeriod(50, 0.5), 12);
        expect(springUpwardCrossings(0.5, 0.6, 50, 0.5)).toEqual([]);
        expect(springUpwardCrossings(0, 1, 0, 0.5)).toEqual([]);
    });

    it('edge cases: k ≤ 0 or m ≤ 0 → ω = T = 0 (no NaN)', () => {
        expect(springOmega(0, 1)).toBe(0);
        expect(springPeriod(50, 0)).toBe(0);
    });
});

describe('projectile', () => {
    const g = 9.81;

    it('h0 = 0, v0 = 20 m/s, θ = 45°: T = 2v0 sin θ/g = 2.883208 s, R = v0² sin 2θ/g = 40.77472 m, H = 10.19368 m', () => {
        const th = toRad(45);
        close(projectileFlightTime(20, th, 0, g), (2 * 20 * Math.sin(th)) / g, 10);
        close(projectileRange(20, th, 0, g), 400 / g, 10);
        close(projectileRange(20, th, 0, g), 40.774719, 5);
        close(projectileMaxHeight(20, th, 0, g), 200 / (2 * g), 10);
    });

    it('h0 > 0: T = (v0 sin θ + √(v0² sin²θ + 2gh0))/g; h0 = 10 → T = 3.470633 s, R = 49.08283 m', () => {
        const th = toRad(45);
        const vy = 20 * Math.sin(th);
        const T = (vy + Math.sqrt(vy * vy + 2 * g * 10)) / g;
        close(projectileFlightTime(20, th, 10, g), T, 10);
        close(projectileRange(20, th, 10, g), 20 * Math.cos(th) * T, 10);
        // must be larger than the h0 = 0 textbook formula (old bug)
        expect(projectileRange(20, th, 10, g)).toBeGreaterThan(400 / g);
        close(projectileMaxHeight(20, th, 10, g), 10 + 200 / (2 * g), 10);
    });

    it('horizontal launch θ = 0: T = √(2h/g), R = v0·T, H = h0', () => {
        close(projectileFlightTime(10, 0, 5, g), Math.sqrt(10 / g), 12);
        close(projectileRange(10, 0, 5, g), 10 * Math.sqrt(10 / g), 12);
        expect(projectileMaxHeight(10, 0, 5, g)).toBe(5);
    });

    it('complementary angles give equal range on level ground (30° vs 60°)', () => {
        close(projectileRange(25, toRad(30), 0, g), projectileRange(25, toRad(60), 0, g), 10);
    });

    it('closed-form state lands exactly at (R, 0) with |v| = √(v0² + 2gh0)', () => {
        const v0 = 15, th = toRad(35), h0 = 4;
        const T = projectileFlightTime(v0, th, h0, g);
        const s = projectileState(T, v0, th, h0, g);
        close(s.y, 0, 9);
        close(s.x, projectileRange(v0, th, h0, g), 10);
        close(Math.hypot(s.vx, s.vy), Math.sqrt(v0 * v0 + 2 * g * h0), 9);
    });

    it('apex: vy = 0 at t = v0 sin θ/g and y equals H', () => {
        const v0 = 20, th = toRad(60), h0 = 2;
        const tA = (v0 * Math.sin(th)) / g;
        const s = projectileState(tA, v0, th, h0, g);
        close(s.vy, 0, 12);
        close(s.y, projectileMaxHeight(v0, th, h0, g), 10);
    });

    it('edge cases: v0 = 0 and h0 = 0 → T = R = 0', () => {
        expect(projectileFlightTime(0, toRad(45), 0, g)).toBe(0);
        expect(projectileRange(0, toRad(45), 0, g)).toBe(0);
    });
});

describe('1-D collisions', () => {
    it('elastic, m1 = 2, m2 = 1, u1 = 5, u2 = −3: v1 = −1/3, v2 = 23/3 m/s', () => {
        const { v1, v2 } = collisionVelocities(2, 1, 5, -3, 1);
        close(v1, -1 / 3, 12);
        close(v2, 23 / 3, 12);
    });

    it('momentum is conserved for any e: p = 2·5 + 1·(−3) = 7 kg·m/s', () => {
        for (const e of [0, 0.3, 0.7, 1]) {
            const { v1, v2 } = collisionVelocities(2, 1, 5, -3, e);
            close(2 * v1 + 1 * v2, 7, 12);
        }
    });

    it('elastic: KE conserved (29.5 J); perfectly inelastic: common v = p/(m1+m2) = 7/3', () => {
        const el = collisionVelocities(2, 1, 5, -3, 1);
        close(kineticEnergy(2, el.v1) + kineticEnergy(1, el.v2), 29.5, 12);
        const inel = collisionVelocities(2, 1, 5, -3, 0);
        close(inel.v1, 7 / 3, 12);
        close(inel.v2, 7 / 3, 12);
    });

    it('ΔKE = ½·μ·(1 − e²)·(u1 − u2)² = ½·(2/3)·64 = 21.333 J for e = 0', () => {
        close(collisionEnergyLoss(2, 1, 5, -3, 0), 64 / 3, 12);
        const inel = collisionVelocities(2, 1, 5, -3, 0);
        close(29.5 - (kineticEnergy(2, inel.v1) + kineticEnergy(1, inel.v2)), 64 / 3, 12);
        close(collisionEnergyLoss(2, 1, 5, -3, 1), 0, 12);
    });

    it('equal masses, elastic: velocities are exchanged', () => {
        const { v1, v2 } = collisionVelocities(1.5, 1.5, 4, -2, 1);
        close(v1, -2, 12);
        close(v2, 4, 12);
    });

    it('collisionTime: gap/closing speed = (600 − 150 − 85)/(5 + 3) = 45.625; never if not approaching', () => {
        close(collisionTime(150, 600, 5, -3, 85), 45.625, 12);
        expect(collisionTime(150, 600, 0, 0, 85)).toBe(Infinity);
        expect(collisionTime(150, 600, 1, 2, 85)).toBe(Infinity);
        expect(collisionTime(150, 200, 1, 0, 85)).toBe(0);
    });

    it('zero total mass is guarded', () => {
        expect(collisionVelocities(0, 0, 1, 2)).toEqual({ v1: 1, v2: 2 });
        expect(collisionEnergyLoss(0, 0, 1, 2)).toBe(0);
    });
});

describe('circular motion', () => {
    it('r = 1.5 m, ω = 2 rad/s, m = 1 kg: v = 3 m/s, a = 6 m/s², F = 6 N, T = π s', () => {
        const c = circularMotion(1.5, 2, 1);
        close(c.v, 3, 12);
        close(c.a, 6, 12);
        close(c.F, 6, 12);
        close(c.T, Math.PI, 12);
        close(c.f, 1 / Math.PI, 12);
    });

    it('a = v²/r identity holds', () => {
        const c = circularMotion(2.3, 3.7, 2);
        close(c.a, (c.v * c.v) / 2.3, 10);
    });

    it('ω = 0: T = ∞, no NaN', () => {
        const c = circularMotion(1, 0, 1);
        expect(c.T).toBe(Infinity);
        expect(c.a).toBe(0);
    });
});

describe('torque and rotation', () => {
    it('τ = F r sin θ: 20 N · 1 m · sin 90° = 20 N·m; sin 30° = sin 150° → 10 N·m', () => {
        close(torque(20, 1, toRad(90)), 20, 12);
        close(torque(20, 1, toRad(30)), 10, 12);
        close(torque(20, 1, toRad(150)), 10, 12);
    });

    it('lever arm d = r |sin θ| = 1·sin 30° = 0.5 m, τ = F·d', () => {
        close(leverArm(1, toRad(30)), 0.5, 12);
        close(20 * leverArm(1, toRad(30)), torque(20, 1, toRad(30)), 12);
    });

    it('rod inertia I = ML²/12 + Md² (parallel-axis): M = 1, L = 2 → 1/3; d = 0.4 → 0.49333', () => {
        close(rodInertia(1, 2, 0), 1 / 3, 12);
        close(rodInertia(1, 2, 0.4), 1 / 3 + 0.16, 12);
        close(rodInertia(1, 2, 1), 4 / 3, 12); // end axis: ML²/3
    });

    it('rotation from rest: α = τ/I = 60 rad/s², φ(0.5 s) = ½αt² = 7.5 rad, ω = αt = 30 rad/s', () => {
        const r = rotationFromRest(0.5, 20, 1 / 3);
        close(r.alpha, 60, 12);
        close(r.angle, 7.5, 12);
        close(r.omega, 30, 12);
        expect(rotationFromRest(1, 5, 0).alpha).toBe(0);
    });
});

describe('energy conservation drop', () => {
    it('no resistance: t_end = √(2h/g) = 0.7820622 s, v_end = √(2gh) = 7.672027 m/s, E = mgh = 29.43 J', () => {
        const end = dropWithResistance(5, 3, 1, 9.81, 0);
        expect(end.landed).toBe(true);
        close(end.tEnd, Math.sqrt(6 / 9.81), 12);
        close(end.v, Math.sqrt(2 * 9.81 * 3), 12);
        close(end.ke, 29.43, 10);
        close(end.pe, 0, 12);
    });

    it('PE + KE = mgh at every instant when k = 0', () => {
        for (const t of [0, 0.1, 0.3, 0.5, 0.78]) {
            const s = dropWithResistance(t, 3, 2, 9.81, 0);
            close(s.mechanical, 2 * 9.81 * 3, 10);
            close(s.heat, 0, 12);
        }
    });

    it('k = 0.2: a = 0.8g = 7.848, KE_end = (1 − k)mgh = 23.544 J, Q = k·mgh = 5.886 J, PE + KE + Q = mgh', () => {
        const end = dropWithResistance(10, 3, 1, 9.81, 0.2);
        close(end.a, 7.848, 12);
        close(end.ke, 23.544, 10);
        close(end.heat, 5.886, 10);
        close(end.v, Math.sqrt(2 * 7.848 * 3), 12);
        for (const t of [0, 0.2, 0.5, 0.8]) {
            close(dropWithResistance(t, 3, 1, 9.81, 0.2).total, 29.43, 10);
        }
    });

    it('edge case: k = 1 → a = 0, never lands, no NaN', () => {
        const s = dropWithResistance(2, 3, 1, 9.81, 1);
        expect(s.tEnd).toBe(Infinity);
        expect(s.s).toBe(0);
        expect(s.v).toBe(0);
        expect(s.landed).toBe(false);
    });
});
