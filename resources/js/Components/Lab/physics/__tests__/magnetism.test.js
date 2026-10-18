import { describe, it, expect } from 'vitest';
import {
    MU0,
    wireField,
    loopCenterField,
    turnsPerMetre,
    solenoidField,
    CORE_MATERIALS,
    isCoreSaturated,
    lorentzForce,
    wireForce,
    dipoleAxialField,
    dipoleEquatorialField,
    barMagnetPoles,
    poleField,
    traceFieldLine,
    magnetFluxThroughLoop,
    magnetPolePositions,
    magnetFluxRate,
    faradayEMF,
    movingMagnetInduction,
    shuttleMotion,
    lenzInducedFieldDirection,
    coilFacePoles,
    inducedCurrent,
    transformer,
    transformerEfficiency,
    rpmToFrequency,
    angularFrequency,
    generatorPeakEMF,
    generatorFluxLinkage,
    generatorEMF,
    rmsFromPeak,
} from '../magnetism.js';

const rel = (a, b) => Math.abs(a - b) / Math.abs(b);

describe('constants', () => {
    it('µ0 = 4π·10⁻⁷ T·m/A', () => {
        expect(MU0).toBeCloseTo(1.2566370614e-6, 15);
    });
});

describe('straight wire, loop, solenoid', () => {
    it('wire: B = µ0I/(2πr) → 10 A at 5 cm = 4·10⁻⁵ T', () => {
        // 2·10⁻⁷ · 10 / 0.05 = 4·10⁻⁵
        expect(wireField(10, 0.05)).toBeCloseTo(4e-5, 12);
    });
    it('wire: B ∝ 1/r', () => {
        expect(wireField(3, 0.02) / wireField(3, 0.04)).toBeCloseTo(2, 12);
    });
    it('wire: r ≤ 0 or NaN inputs give NaN', () => {
        expect(wireField(1, 0)).toBeNaN();
        expect(wireField(1, -0.1)).toBeNaN();
        expect(wireField(NaN, 0.1)).toBeNaN();
    });
    it('loop centre: B = µ0NI/(2R) → 1 A, R = 10 cm: 6.2832·10⁻⁶ T; ×N', () => {
        expect(loopCenterField(1, 0.1)).toBeCloseTo(2 * Math.PI * 1e-6, 15);
        expect(loopCenterField(1, 0.1, 10)).toBeCloseTo(2 * Math.PI * 1e-5, 14);
        expect(loopCenterField(1, 0)).toBeNaN();
    });
    it('n = N/L is per metre: 100 turns over 10 cm = 1000 m⁻¹', () => {
        expect(turnsPerMetre(100, 0.1)).toBeCloseTo(1000, 9);
        expect(turnsPerMetre(100, 0)).toBeNaN();
    });
    it('solenoid air core: µ0·n·I = 4π·10⁻⁷·1000·2 = 2.5133 mT', () => {
        expect(solenoidField({ turns: 100, length: 0.1, current: 2 })).toBeCloseTo(2.513274e-3, 8);
    });
    it('solenoid uses N/L, not N: doubling L halves B', () => {
        const b1 = solenoidField({ turns: 100, length: 0.1, current: 2 });
        const b2 = solenoidField({ turns: 100, length: 0.2, current: 2 });
        expect(b1 / b2).toBeCloseTo(2, 12);
    });
    it('solenoid with iron core: µr = 5000 → 12.566 T (linear model) and is flagged saturated', () => {
        const B = solenoidField({ turns: 100, length: 0.1, current: 2, mur: CORE_MATERIALS.iron.permeability });
        expect(B).toBeCloseTo(12.566370614, 6);
        expect(isCoreSaturated(B, CORE_MATERIALS.iron.saturation)).toBe(true);
        expect(isCoreSaturated(B, CORE_MATERIALS.air.saturation)).toBe(false);
        expect(isCoreSaturated(0.5, CORE_MATERIALS.iron.saturation)).toBe(false);
    });
    it('solenoid edge cases', () => {
        expect(solenoidField({ turns: 100, length: 0, current: 1 })).toBeNaN();
        expect(solenoidField({ turns: 100, length: 0.1, current: 1, mur: 0 })).toBeNaN();
        expect(solenoidField({ turns: 100, length: 0.1, current: 0 })).toBe(0);
    });
});

describe('Lorentz / Ampère force', () => {
    it('F = |q|vB sinθ: electron, 10⁶ m/s, 0.5 T → 8.011·10⁻¹⁴ N', () => {
        expect(lorentzForce(-1.602176634e-19, 1e6, 0.5)).toBeCloseTo(8.01088317e-14, 20);
    });
    it('F = 0 for v ∥ B; F halves at 30°', () => {
        expect(lorentzForce(1e-6, 100, 1, 0)).toBe(0);
        expect(lorentzForce(1e-6, 100, 1, Math.PI / 6) / lorentzForce(1e-6, 100, 1)).toBeCloseTo(0.5, 12);
    });
    it('F = BIL sinθ: 0.2 T · 3 A · 0.5 m = 0.3 N', () => {
        expect(wireForce(0.2, 3, 0.5)).toBeCloseTo(0.3, 12);
        expect(wireForce(0.2, 3, 0.5, Math.PI / 6)).toBeCloseTo(0.15, 12);
        expect(wireForce(0.2, 3, -1)).toBeNaN();
    });
});

describe('bar magnet / dipole', () => {
    it('dipole: axial µ0·2m/(4πr³) = 2·10⁻⁴ T, equatorial 10⁻⁴ T (m = 1, r = 0.1)', () => {
        expect(dipoleAxialField(1, 0.1)).toBeCloseTo(2e-4, 12);
        expect(dipoleEquatorialField(1, 0.1)).toBeCloseTo(1e-4, 12);
        expect(dipoleAxialField(1, 0)).toBeNaN();
    });
    it('pole model → dipole far away; on axis B ∥ m (S→N), at equator B antiparallel to m', () => {
        const poles = barMagnetPoles({ moment: 1, poleSeparation: 0.01, northOnLeft: true });
        // N at −x ⇒ m points −x
        const axial = poleField(1, 0, poles);
        expect(axial.Bx).toBeLessThan(0);
        expect(rel(axial.B, dipoleAxialField(1, 1))).toBeLessThan(1e-3);
        const eq = poleField(0, 1, poles);
        expect(eq.Bx).toBeGreaterThan(0);
        expect(Math.abs(eq.By)).toBeLessThan(1e-15);
        expect(rel(eq.B, dipoleEquatorialField(1, 1))).toBeLessThan(1e-3);
    });
    it('field lines leave N: just outside the N end B points away from the magnet', () => {
        const poles = barMagnetPoles({ moment: 2, poleSeparation: 0.085, northOnLeft: true });
        const left = poleField(-0.1, 0, poles); // beyond the N end
        const right = poleField(0.1, 0, poles); // beyond the S end
        expect(left.Bx).toBeLessThan(0); // away from N (to the left)
        expect(right.Bx).toBeLessThan(0); // into S (to the left)
    });
    it('field at a pole is NaN (singular)', () => {
        const poles = barMagnetPoles({ moment: 1, poleSeparation: 0.1 });
        expect(poleField(-0.05, 0, poles).B).toBeNaN();
    });
    it('traceFieldLine follows a uniform field in a straight line of the given step', () => {
        const pts = traceFieldLine(() => ({ Bx: 3, By: 4 }), 0, 0, { step: 1, maxSteps: 10 });
        expect(pts).toHaveLength(11);
        expect(pts[10][0]).toBeCloseTo(6, 12);
        expect(pts[10][1]).toBeCloseTo(8, 12);
    });
    it('traceFieldLine honours the stop predicate and zero field', () => {
        const pts = traceFieldLine(() => ({ Bx: 1, By: 0 }), 0, 0, { step: 1, stop: (x) => x >= 3 });
        expect(pts[pts.length - 1][0]).toBeCloseTo(3, 12);
        expect(traceFieldLine(() => ({ Bx: 0, By: 0 }), 0, 0, { step: 1 })).toHaveLength(1);
    });
});

describe('Faraday induction — moving magnet', () => {
    const a = 0.05;
    it('two-pole flux reduces to the dipole result Φ = µ0ma²/(2(x²+a²)^{3/2})', () => {
        const m = 1;
        const l = 1e-4;
        const X = 0.1;
        const { xNorth, xSouth } = magnetPolePositions(X, l, true);
        const phi = magnetFluxThroughLoop({ poleStrength: m / l, xNorth, xSouth, radius: a });
        const dip = (MU0 * m * a * a) / (2 * Math.pow(X * X + a * a, 1.5)); // 1.1240e-6 Wb
        expect(dip).toBeCloseTo(1.12397e-6, 10);
        expect(phi).toBeLessThan(0); // m points −x (N on the left)
        expect(rel(-phi, dip)).toBeLessThan(1e-4);
    });
    it('magnet centred in the coil: Φ = −µ0q(ℓ/2)/√((ℓ/2)²+a²) = −8.886 µWb (q = 10, ℓ = 0.1, a = 0.05)', () => {
        const phi = magnetFluxThroughLoop({ poleStrength: 10, xNorth: -0.05, xSouth: 0.05, radius: a });
        expect(phi).toBeCloseTo(-MU0 * 10 * Math.SQRT1_2, 15);
        expect(phi).toBeCloseTo(-8.8858e-6, 9);
    });
    it('flux is continuous when the magnet passes through the coil', () => {
        const f = (X) => {
            const p = magnetPolePositions(X, 0.1, true);
            return magnetFluxThroughLoop({ poleStrength: 10, ...p, radius: a });
        };
        // A Gilbert H-flux model would jump by µ0·q = 1.26·10⁻⁵ Wb when a pole crosses the
        // coil plane; the B-flux changes only by slope·Δx (~10⁻¹¹ Wb for Δx = 2·10⁻⁷ m).
        for (const X0 of [-0.05, 0, 0.05]) {
            expect(Math.abs(f(X0 + 1e-7) - f(X0 - 1e-7))).toBeLessThan(1e-9);
        }
        expect(Math.abs(f(100))).toBeLessThan(1e-12);
    });
    it('analytic dΦ/dt equals the numerical derivative dΦ/dX · v', () => {
        const q = 30;
        const l = 0.1;
        const v = 2;
        for (const X of [-0.25, -0.08, -0.02, 0.03, 0.12]) {
            const p = magnetPolePositions(X, l, true);
            const analytic = magnetFluxRate({ poleStrength: q, ...p, radius: 0.06, velocity: v });
            const h = 1e-6;
            const pp = magnetPolePositions(X + h, l, true);
            const pm = magnetPolePositions(X - h, l, true);
            const num = ((magnetFluxThroughLoop({ poleStrength: q, ...pp, radius: 0.06 })
                - magnetFluxThroughLoop({ poleStrength: q, ...pm, radius: 0.06 })) / (2 * h)) * v;
            expect(rel(analytic, num)).toBeLessThan(1e-6);
        }
    });
    it('ε = −N dΦ/dt', () => {
        expect(faradayEMF(50, 0.02)).toBeCloseTo(-1, 12);
        expect(faradayEMF(50, NaN)).toBeNaN();
    });
    const base = { turns: 50, moment: 3, poleSeparation: 0.1, radius: 0.06, northOnLeft: true };
    it('EMF ∝ N and ∝ v; zero when the magnet is at rest or centred', () => {
        const e1 = movingMagnetInduction({ ...base, position: -0.1, velocity: 2 }).emf;
        expect(movingMagnetInduction({ ...base, turns: 100, position: -0.1, velocity: 2 }).emf / e1).toBeCloseTo(2, 10);
        expect(movingMagnetInduction({ ...base, position: -0.1, velocity: 4 }).emf / e1).toBeCloseTo(2, 10);
        expect(movingMagnetInduction({ ...base, position: -0.1, velocity: 0 }).emf).toBeCloseTo(0, 15);
        expect(movingMagnetInduction({ ...base, position: 0, velocity: 2 }).emf).toBeCloseTo(0, 15);
    });
    it('EMF is antisymmetric: ε(−X) = −ε(X) for the same velocity', () => {
        const e1 = movingMagnetInduction({ ...base, position: -0.07, velocity: 2 }).emf;
        const e2 = movingMagnetInduction({ ...base, position: 0.07, velocity: 2 }).emf;
        expect(e1).toBeCloseTo(-e2, 12);
    });
    it('Lenz: approaching S pole → coil face towards the magnet becomes S (repulsion)', () => {
        // N on the left ⇒ the S end leads when moving +x from the left
        const s = movingMagnetInduction({ ...base, position: -0.1, velocity: 2 });
        expect(s.emf).toBeGreaterThan(0);
        const dir = lenzInducedFieldDirection(s.fluxRate);
        expect(dir).toBe(1);
        expect(coilFacePoles(dir).left).toBe('S');
    });
    it('Lenz: receding magnet (N end facing the coil) → facing coil end is S (attraction)', () => {
        const s = movingMagnetInduction({ ...base, position: 0.1, velocity: 2 });
        const dir = lenzInducedFieldDirection(s.fluxRate);
        expect(dir).toBe(-1);
        expect(coilFacePoles(dir)).toEqual({ left: 'N', right: 'S' });
    });
    it('Lenz: no change → no induced field', () => {
        expect(lenzInducedFieldDirection(0)).toBe(0);
        expect(coilFacePoles(0)).toEqual({ left: null, right: null });
    });
    it('I = ε/R', () => {
        expect(inducedCurrent(0.05, 10)).toBeCloseTo(5e-3, 15);
        expect(inducedCurrent(0.05, 0)).toBeNaN();
    });
    it('peak EMF for the EM-induction lab defaults is tens of mV (realistic)', () => {
        let peak = 0;
        for (let i = 0; i <= 600; i++) {
            const X = -0.3 + (0.6 * i) / 600;
            peak = Math.max(peak, Math.abs(movingMagnetInduction({ ...base, position: X, velocity: 2 }).emf));
        }
        expect(peak).toBeGreaterThan(0.01);
        expect(peak).toBeLessThan(0.2);
    });
});

describe('shuttleMotion (closed-form triangle wave)', () => {
    it('starts at xMin moving +x and bounces with period 2·span/speed', () => {
        expect(shuttleMotion(0, -0.3, 0.3, 2)).toEqual({ x: -0.3, v: 2 });
        const m1 = shuttleMotion(0.15, -0.3, 0.3, 2);
        expect(m1.x).toBeCloseTo(0, 12);
        expect(m1.v).toBe(2);
        const m2 = shuttleMotion(0.45, -0.3, 0.3, 2);
        expect(m2.x).toBeCloseTo(0, 12);
        expect(m2.v).toBe(-2);
        expect(shuttleMotion(0.6 + 0.15, -0.3, 0.3, 2).x).toBeCloseTo(0, 12);
    });
    it('is independent of how time was stepped (pure function of t)', () => {
        let t = 0;
        for (let i = 0; i < 37; i++) t += 1 / 144;
        const a = shuttleMotion(t, -0.2, 0.2, 3);
        const b = shuttleMotion(37 / 144, -0.2, 0.2, 3);
        expect(a.x).toBeCloseTo(b.x, 12);
    });
    it('edge cases: zero speed / negative time / empty span', () => {
        expect(shuttleMotion(1, -0.3, 0.3, 0)).toEqual({ x: -0.3, v: 0 });
        expect(shuttleMotion(-1, -0.3, 0.3, 2)).toEqual({ x: -0.3, v: 0 });
        expect(shuttleMotion(1, 0.3, 0.3, 2)).toEqual({ x: 0.3, v: 0 });
    });
});

describe('transformer', () => {
    it('ideal step-up: Np 100, Ns 200, Vp 220 V, R 100 Ω', () => {
        const t = transformer({ primaryTurns: 100, secondaryTurns: 200, primaryVoltage: 220, loadResistance: 100 });
        expect(t.ratio).toBe(2);
        expect(t.secondaryVoltage).toBeCloseTo(440, 10); // Vs = Vp·Ns/Np
        expect(t.secondaryCurrent).toBeCloseTo(4.4, 10); // Is = Vs/R
        expect(t.primaryCurrent).toBeCloseTo(8.8, 10); // Ip = Is·Ns/Np
        expect(t.outputPower).toBeCloseTo(1936, 8);
        expect(t.inputPower).toBeCloseTo(t.outputPower, 10); // power conservation
        expect(t.primaryCurrent / t.secondaryCurrent).toBeCloseTo(200 / 100, 12);
        expect(t.type).toBe('step-up');
    });
    it('step-down and 1:1 types', () => {
        const d = transformer({ primaryTurns: 200, secondaryTurns: 50, primaryVoltage: 220, loadResistance: 10 });
        expect(d.secondaryVoltage).toBeCloseTo(55, 10);
        expect(d.type).toBe('step-down');
        expect(transformer({ primaryTurns: 100, secondaryTurns: 100, primaryVoltage: 220 }).type).toBe('isolation');
    });
    it('keeps decimals (75 → 50 turns at 110 V gives 73.33 V, not 73)', () => {
        expect(transformer({ primaryTurns: 75, secondaryTurns: 50, primaryVoltage: 110 }).secondaryVoltage).toBeCloseTo(73.3333, 4);
    });
    it('non-ideal: η = 0.9 → Pin = Pout/η', () => {
        const t = transformer({ primaryTurns: 100, secondaryTurns: 200, primaryVoltage: 220, loadResistance: 100, efficiency: 0.9 });
        expect(t.inputPower).toBeCloseTo(1936 / 0.9, 8);
        expect(t.primaryCurrent).toBeCloseTo(1936 / 0.9 / 220, 10);
        expect(transformerEfficiency(t.outputPower, t.inputPower)).toBeCloseTo(0.9, 12);
    });
    it('no load → no current; invalid inputs → null', () => {
        const t = transformer({ primaryTurns: 100, secondaryTurns: 200, primaryVoltage: 220 });
        expect(t.secondaryCurrent).toBe(0);
        expect(t.primaryCurrent).toBe(0);
        expect(transformer({ primaryTurns: 0, secondaryTurns: 200, primaryVoltage: 220 })).toBeNull();
        expect(transformer({ primaryTurns: 100, secondaryTurns: 200, primaryVoltage: 220, efficiency: 1.2 })).toBeNull();
        expect(transformer({ primaryTurns: 100, secondaryTurns: 200, primaryVoltage: 220, loadResistance: 0 })).toBeNull();
        expect(transformerEfficiency(1, 0)).toBeNaN();
    });
});

describe('AC generator', () => {
    it('f = rpm/60 and ω = 2πf', () => {
        expect(rpmToFrequency(60)).toBe(1);
        expect(rpmToFrequency(3000)).toBe(50);
        expect(angularFrequency(50)).toBeCloseTo(314.159265, 5);
    });
    const p = { turns: 100, field: 0.5, area: 0.008, omega: 2 * Math.PI };
    it('ε0 = NBAω: 100·0.5·0.008·2π = 2.5133 V; doubles with rpm', () => {
        expect(generatorPeakEMF(p)).toBeCloseTo(2.513274, 6);
        expect(generatorPeakEMF({ ...p, omega: 4 * Math.PI }) / generatorPeakEMF(p)).toBeCloseTo(2, 12);
    });
    it('ε(t) = ε0 sin ωt: 0 at t = 0, +ε0 at T/4, −ε0 at 3T/4', () => {
        expect(generatorEMF(0, p)).toBeCloseTo(0, 12);
        expect(generatorEMF(0.25, p)).toBeCloseTo(generatorPeakEMF(p), 10);
        expect(generatorEMF(0.75, p)).toBeCloseTo(-generatorPeakEMF(p), 10);
    });
    it('ε(t) = −d(NΦ)/dt (numerical derivative of NBA cos ωt)', () => {
        for (const t of [0.1, 0.37, 0.9]) {
            const h = 1e-6;
            const num = -(generatorFluxLinkage(t + h, p) - generatorFluxLinkage(t - h, p)) / (2 * h);
            expect(rel(generatorEMF(t, p), num)).toBeLessThan(1e-6);
        }
    });
    it('Vrms = Vpeak/√2: 325.27 V peak → 230 V rms', () => {
        expect(rmsFromPeak(230 * Math.SQRT2)).toBeCloseTo(230, 10);
        expect(rmsFromPeak(2.513274)).toBeCloseTo(1.777153, 5);
        expect(rmsFromPeak(NaN)).toBeNaN();
    });
});
