import { describe, it, expect } from 'vitest';
import {
    MU0,
    wireField,
    loopCenterField,
    turnsPerMetre,
    solenoidField,
    CORE_MATERIALS,
    ELECTROMAGNET_DEFAULTS,
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
    MU_0,
    saturatingPolarization,
    coreSolenoidField,
    TESLAMETER,
    teslameterResolution,
    teslameterReading,
    teslameterMaxError,
    labDmmReading,
    labDmmMaxError,
    LAB_DMM,
    barMagnetAxialField,
    inductionPeakEMF,
    UZ_MAINS,
    EARTH_FIELD_TASHKENT,
} from '../magnetism.js';
import { createRng } from '../instruments.js';

const rel = (a, b) => Math.abs(a - b) / Math.abs(b);

describe('constants', () => {
    it('µ0 is the CODATA 2022 value 1.256 637 061 27·10⁻⁶ N/A² (NIST), within 1.4·10⁻¹⁰ of 4π·10⁻⁷', () => {
        expect(MU0).toBe(1.25663706127e-6);
        expect(MU0).toBe(MU_0);
        expect(Math.abs(MU0 / (4 * Math.PI * 1e-7) - 1)).toBeLessThan(1.4e-10);
        expect(MU0 / (4 * Math.PI * 1e-7) - 1).toBeLessThan(0); // CODATA 2022: −1.3e−10
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
        // CODATA 2022 µ0 = 1.25663706127e-6 → µ0/0.2 = 6.28318530635e-6 (4π·10⁻⁷ gives …0718)
        expect(loopCenterField(1, 0.1)).toBeCloseTo(6.28318530635e-6, 16);
        expect(rel(loopCenterField(1, 0.1), 2 * Math.PI * 1e-6)).toBeLessThan(2e-10);
        expect(loopCenterField(1, 0.1, 10)).toBeCloseTo(6.28318530635e-5, 15);
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

describe('core saturation (electromagnet)', () => {
    it('material table: iron µr 5000 / Bsat 2.0 T / knee 1.2 T, ferrite µr 2000 / 0.45 T / 0.27 T', () => {
        expect(CORE_MATERIALS.iron).toEqual({ permeability: 5000, saturation: 2.0, knee: 1.2 });
        expect(CORE_MATERIALS.ferrite).toEqual({ permeability: 2000, saturation: 0.45, knee: 0.27 });
        expect(CORE_MATERIALS.air.saturation).toBe(Infinity);
    });
    it('saturatingPolarization: linear below the knee, C¹ at the knee, monotonic, < Js', () => {
        expect(saturatingPolarization(0.5, 2, 1.2)).toBe(0.5);
        expect(saturatingPolarization(-0.5, 2, 1.2)).toBe(-0.5);
        expect(saturatingPolarization(1.2, 2, 1.2)).toBe(1.2);
        const h = 1e-6;
        expect((saturatingPolarization(1.2 + h, 2, 1.2) - 1.2) / h).toBeCloseTo(1, 5); // slope 1 at the knee
        let prev = 0;
        for (let x = 0; x < 100; x += 0.37) {
            const J = saturatingPolarization(x, 2, 1.2);
            expect(J).toBeGreaterThanOrEqual(prev);
            expect(J).toBeLessThan(2 + 1e-12);
            prev = J;
        }
        expect(saturatingPolarization(100, 2, 1.2)).toBeCloseTo(2, 9);
        // 2.0 T linear → 1.2 + 0.8·tanh(1) = 1.80927 T
        expect(saturatingPolarization(2, 2, 1.2)).toBeCloseTo(1.2 + 0.8 * Math.tanh(1), 12);
        expect(saturatingPolarization(5, Infinity, Infinity)).toBe(5);
        expect(saturatingPolarization(NaN, 2, 1.2)).toBeNaN();
    });
    it('below the knee the model is exactly the textbook B = µ0µr·N·I/l (iron task: 50 turns, 0.2 A → 628.32 mT)', () => {
        const r = coreSolenoidField({ turns: 50, length: 0.1, current: 0.2, core: 'iron' });
        const hand = 1.25663706127e-6 * 5000 * (50 / 0.1) * 0.2; // 0.628318530635 T
        expect(r.H).toBeCloseTo(100, 12);
        expect(r.B).toBeCloseTo(hand, 14);
        expect(r.linear).toBeCloseTo(hand, 14);
        expect(r.nonlinear).toBe(false);
        expect(r.saturated).toBe(false);
        expect(r.B * 1000).toBeCloseTo(628.32, 2);
        // air: always linear, equals solenoidField
        const air = coreSolenoidField({ turns: 100, length: 0.1, current: 2, core: 'air' });
        expect(air.B).toBe(solenoidField({ turns: 100, length: 0.1, current: 2 }));
    });
    it('above the knee B bends over and never exceeds Bsat + µ0H (iron 500 turns, 5 A: linear 157 T → ≈ 2.03 T)', () => {
        const r = coreSolenoidField({ turns: 500, length: 0.1, current: 5, core: 'iron' });
        expect(r.linear).toBeCloseTo(157.08, 1);
        expect(r.saturated).toBe(true);
        expect(r.nonlinear).toBe(true);
        expect(r.B).toBeGreaterThan(2.0);
        expect(r.B).toBeLessThanOrEqual(2.0 + MU0 * r.H + 1e-12); // µ0H = 0.0314 T
        expect(r.effectiveMur).toBeLessThan(100);
        // old iron task (50 turns, 0.5 A, linear 1.5708 T) lies above the knee → 1.5464 T, 1.6 % below linear
        const old = coreSolenoidField({ turns: 50, length: 0.1, current: 0.5, core: 'iron' });
        expect(old.nonlinear).toBe(true);
        expect(old.B).toBeCloseTo(1.5464, 4);
        expect(old.B).toBeLessThan(old.linear * 0.99);
        // ferrite saturates near 0.45 T
        const f = coreSolenoidField({ turns: 500, length: 0.1, current: 5, core: 'ferrite' });
        expect(f.B).toBeGreaterThan(0.45);
        expect(f.B).toBeLessThan(0.45 + MU0 * f.H + 1e-9);
    });
    it('B is continuous and increasing in I for every core', () => {
        for (const core of ['air', 'iron', 'ferrite']) {
            let prev = 0;
            for (let I = 0.1; I <= 5.0001; I += 0.1) {
                const B = coreSolenoidField({ turns: 200, length: 0.1, current: I, core }).B;
                expect(B).toBeGreaterThan(prev);
                prev = B;
            }
        }
        expect(coreSolenoidField({ turns: 100, length: 0, current: 1, core: 'iron' }).B).toBeNaN();
    });
});

describe('teslameter (Hall probe, 20/200/2000 mT ranges)', () => {
    it('auto-range resolution 0.01 / 0.1 / 1 mT', () => {
        expect(TESLAMETER.ranges).toEqual([20, 200, 2000]);
        expect(teslameterResolution(2.5133)).toBe(0.01);
        expect(teslameterResolution(19.99)).toBe(0.01);
        expect(teslameterResolution(25)).toBe(0.1);
        expect(teslameterResolution(628.32)).toBe(1);
    });
    it('readings: rounded to the range digit, scatter within 2.5σ + ½ digit', () => {
        expect(teslameterReading(2.51327)).toBe(2.51);
        expect(teslameterReading(628.3185)).toBe(628);
        for (const v of [2.5133, 5.0265, 6.2832, 628.32, 0.298]) {
            const rng = createRng(12345);
            for (let k = 0; k < 200; k++) {
                expect(Math.abs(teslameterReading(v, rng) - v)).toBeLessThanOrEqual(teslameterMaxError(v) + 1e-12);
            }
        }
    });
});

describe('lab DMM (AC voltage / mV / mA, 3½ digits)', () => {
    it('220 V → 0.1 V digit, 440 V → 1 V digit; errors bounded', () => {
        expect(labDmmReading(110)).toBe(110);
        expect(labDmmReading(440.3)).toBe(440);
        expect(labDmmReading(0.0272935)).toBeCloseTo(0.0273, 12); // 200 mV range, 0.1 mV digit
        const rng = createRng(7);
        for (let k = 0; k < 200; k++) {
            expect(Math.abs(labDmmReading(440, rng) - 440)).toBeLessThanOrEqual(labDmmMaxError(440) + 1e-9);
            expect(Math.abs(labDmmReading(0.0273, rng, LAB_DMM.peakSigmaRel) - 0.0273)).toBeLessThanOrEqual(labDmmMaxError(0.0273, LAB_DMM.peakSigmaRel) + 1e-12);
        }
    });
});

describe('bar magnet on-axis field (two-pole model)', () => {
    it('hand value: m = 1 A·m², ℓ = 8.5 cm, r = 10 cm → 0.2979 mT (dipole formula: 0.2 mT)', () => {
        // q = 1/0.085, µ0q/4π = 1.17647e-6; 1/0.0575² − 1/0.1425² = 302.457 − 49.246 = 253.211
        expect(barMagnetAxialField(1, 0.085, 0.1)).toBeCloseTo((1e-7 / 0.085) * (1 / 0.0575 ** 2 - 1 / 0.1425 ** 2), 9);
        expect(barMagnetAxialField(1, 0.085, 0.1) * 1000).toBeCloseTo(0.2979, 4);
        expect(barMagnetAxialField(1, 0.085, 0.04)).toBeNaN(); // inside the magnet
    });
    it('matches poleField on the axis and tends to the dipole law far away', () => {
        const poles = barMagnetPoles({ moment: 2, poleSeparation: 0.085, northOnLeft: true });
        expect(barMagnetAxialField(2, 0.085, 0.15)).toBeCloseTo(poleField(0.15, 0, poles).B, 15);
        expect(rel(barMagnetAxialField(2, 0.085, 2), dipoleAxialField(2, 2))).toBeLessThan(1e-3);
        expect(barMagnetAxialField(2, 0.085, 0.3)).toBeGreaterThan(dipoleAxialField(2, 0.3));
    });
});

describe('induction peak EMF (peak-hold reading)', () => {
    const lab = { turns: 50, moment: 3, poleSeparation: 0.1, radius: 0.06, speed: 2 };
    it('equals a fine brute-force scan of |ε| (EM-induction defaults ≈ 27.29 mV at X ≈ −5.33 cm)', () => {
        let peak = 0;
        for (let i = 0; i <= 60000; i++) {
            const X = -0.3 + (0.6 * i) / 60000;
            peak = Math.max(peak, Math.abs(movingMagnetInduction({ ...lab, position: X, velocity: 2 }).emf));
        }
        const p = inductionPeakEMF({ ...lab, xFrom: -0.3, xTo: 0.3 });
        expect(rel(p.emf, peak)).toBeLessThan(1e-6);
        expect(p.emf * 1000).toBeCloseTo(27.29, 2);
        expect(Math.abs(p.position)).toBeCloseTo(0.0533, 3);
    });
    it('∝ N·v; over a stretch before the peak it is the value at the end of the stretch', () => {
        const p1 = inductionPeakEMF({ ...lab, xFrom: -0.3, xTo: 0 }).emf;
        expect(inductionPeakEMF({ ...lab, turns: 100, speed: 4, xFrom: -0.3, xTo: 0 }).emf / p1).toBeCloseTo(4, 9);
        const part = inductionPeakEMF({ ...lab, xFrom: -0.3, xTo: -0.15 }).emf;
        expect(part).toBeCloseTo(Math.abs(movingMagnetInduction({ ...lab, position: -0.15, velocity: 2 }).emf), 12);
    });
});

describe('reference data', () => {
    it('Uzbekistan mains 220 V / 50 Hz, line voltage √3·220 ≈ 380 V', () => {
        expect(UZ_MAINS).toEqual({ voltage: 220, frequency: 50, lineVoltage: 380 });
        expect(Math.abs(Math.sqrt(3) * 220 - 380) / 380).toBeLessThan(0.003);
    });
    it('Tashkent geomagnetic field (WMM2025): F ≈ 54 µT, H ≈ 25.5 µT, consistent with I', () => {
        const { total, horizontal, vertical, inclinationDeg } = EARTH_FIELD_TASHKENT;
        expect(Math.hypot(horizontal, vertical)).toBeCloseTo(total, 8);
        expect((Math.atan2(vertical, horizontal) * 180) / Math.PI).toBeCloseTo(inclinationDeg, 2);
        // 10 A wire at 5 cm (40 µT) is comparable to the Earth's field
        expect(wireField(10, 0.05) / total).toBeCloseTo(0.741, 3);
    });
});

describe('ELECTROMAGNET_DEFAULTS (QA-S-21)', () => {
    it('starts below core saturation, on the first task setup (2.513 mT)', () => {
        const { turns, current, core } = ELECTROMAGNET_DEFAULTS;
        const B = solenoidField({ turns, length: 0.1, current, mur: CORE_MATERIALS[core].permeability });
        expect(isCoreSaturated(B, CORE_MATERIALS[core].saturation)).toBe(false);
        expect(B * 1000).toBeCloseTo(2.5133, 3);
    });
});
