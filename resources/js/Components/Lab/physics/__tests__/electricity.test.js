import { describe, it, expect } from 'vitest';
import {
    EPSILON_0, K_COULOMB, formatSI, toFixedSafe,
    ohmCurrent, ohmVoltage, ohmResistance,
    seriesResistance, parallelResistance, voltageDivider, currentDivider,
    seriesCircuit, parallelCircuit, solveLinearSystem, solveKirchhoffCircuit,
    powerUI, powerI2R, powerU2R, energyKWh, energyJoules, energyCost,
    parallelPlateCapacitance, capacitorCharge, capacitorEnergy, seriesCapacitance, parallelCapacitance,
    rcTimeConstant, rcCharging, rcDischarging, rcTimeToFraction,
    coulombForceMagnitude, coulombForce, netCoulombForce, electricField, electricPotential,
    fieldLineSeeds, traceFieldLine, contourSegments, fieldLinePaths, defaultPotentialLevels, equipotentialSegments,
    motorSideForce, motorTorque, motorAverageTorque, motorBackEmf, motorSteadyOmega, omegaToRpm,
    motorKinematics, dcMotorState, DC_MOTOR_DEFAULTS,
    thermalVoltage, shockleyCurrent, diodeVoltageFromCurrent, lambertW0, lambertW0FromLog,
    diodeCircuit, piecewiseDiodeCurrent, halfWaveRectifierOutput, DIODE_MODELS,
} from '../electricity.js';

const rel = (a, b) => Math.abs(a - b) / Math.max(Math.abs(b), 1e-300);

describe('constants', () => {
    it('k = 1/(4πε0) ≈ 8.9875e9 N·m²/C², ε0 = 8.854e-12 F/m', () => {
        expect(rel(K_COULOMB, 8.9875517923e9)).toBeLessThan(1e-9);
        expect(rel(EPSILON_0, 8.854e-12)).toBeLessThan(1e-4);
    });
    it('thermal voltage kT/e at 300 K ≈ 25.85 mV', () => {
        expect(thermalVoltage(300)).toBeCloseTo(0.025852, 6);
        expect(thermalVoltage(600)).toBeCloseTo(2 * thermalVoltage(300), 12);
    });
});

describe('formatting helpers', () => {
    it('formatSI picks SI prefixes and significant digits', () => {
        expect(formatSI(0.014983, 'N', 4).text).toBe('14.98 mN');
        expect(formatSI(0.0266667, 'A', 3).text).toBe('26.7 mA');
        expect(formatSI(1500, 'Ω', 3).text).toBe('1.50 kΩ');
        expect(formatSI(2.5e-6, 'C', 2).text).toBe('2.5 µC');
        expect(formatSI(0.99996, 'V', 4).text).toBe('1.000 V'); // rounding carries a decade
        expect(formatSI(0, 'A').text).toBe('0 A');
        expect(formatSI(Infinity, 'A').value).toBe('∞');
        expect(formatSI(NaN, 'A').value).toBe('—');
    });
    it('toFixedSafe never returns NaN/Infinity/-0 strings', () => {
        expect(toFixedSafe(1.23456, 2)).toBe('1.23');
        expect(toFixedSafe(-0.0001, 2)).toBe('0.00');
        expect(toFixedSafe(NaN, 2)).toBe('0');
        expect(toFixedSafe(Infinity, 2)).toBe('0');
    });
});

describe("Ohm's law  I = U/R", () => {
    it('6 V across 100 Ω → 60 mA; 12 V across 10 Ω → 1.2 A', () => {
        expect(ohmCurrent(6, 100)).toBeCloseTo(0.06, 12);
        expect(ohmCurrent(12, 10)).toBeCloseTo(1.2, 12);
        expect(ohmVoltage(0.06, 100)).toBeCloseTo(6, 12);
        expect(ohmResistance(6, 0.06)).toBeCloseTo(100, 10);
    });
    it('short circuit, open circuit and invalid input', () => {
        expect(ohmCurrent(12, 0)).toBe(Infinity);
        expect(ohmCurrent(-12, 0)).toBe(-Infinity);
        expect(ohmCurrent(0, 0)).toBe(0);
        expect(ohmCurrent(5, -10)).toBeNaN();
        expect(ohmResistance(5, 0)).toBe(Infinity);
        expect(ohmResistance(0, 0)).toBeNaN();
    });
});

describe('series circuit  R = ΣR,  I = U/R,  Uk = I·Rk', () => {
    const s = seriesCircuit(12, [100, 200, 150]);
    it('R = 450 Ω, I = 26.667 mA', () => {
        expect(s.totalResistance).toBe(450);
        expect(s.current).toBeCloseTo(12 / 450, 12);
    });
    it('voltage drops 2.667 / 5.333 / 4.000 V and KVL ΣUk = U', () => {
        expect(s.voltageDrops[0]).toBeCloseTo(2.6666667, 6);
        expect(s.voltageDrops[1]).toBeCloseTo(5.3333333, 6);
        expect(s.voltageDrops[2]).toBeCloseTo(4, 10);
        expect(s.voltageDrops.reduce((a, b) => a + b, 0)).toBeCloseTo(12, 10);
    });
    it('power P = U·I = 0.32 W equals ΣI²Rk', () => {
        expect(s.totalPower).toBeCloseTo(0.32, 10);
        expect(s.powers.reduce((a, b) => a + b, 0)).toBeCloseTo(0.32, 10);
    });
    it('voltage divider U2 = U·R2/(R1+R2)', () => {
        expect(voltageDivider(12, 100, 200)).toBeCloseTo(8, 12);
        expect(voltageDivider(12, 0, 0)).toBe(0);
    });
    it('empty / zero-resistance series → short circuit', () => {
        expect(seriesResistance([])).toBe(0);
        expect(seriesCircuit(5, [0, 0]).current).toBe(Infinity);
    });
});

describe('parallel circuit  1/R = Σ1/R,  Ik = U/Rk', () => {
    const p = parallelCircuit(12, [100, 200, 150]);
    it('R = 46.154 Ω (1/R = 0.01 + 0.005 + 0.006667)', () => {
        expect(p.totalResistance).toBeCloseTo(600 / 13, 10);
    });
    it('branch currents 120 / 60 / 80 mA and KCL I = ΣIk = U/R = 260 mA', () => {
        expect(p.branchCurrents[0]).toBeCloseTo(0.12, 12);
        expect(p.branchCurrents[1]).toBeCloseTo(0.06, 12);
        expect(p.branchCurrents[2]).toBeCloseTo(0.08, 12);
        expect(p.totalCurrent).toBeCloseTo(0.26, 12);
        expect(p.totalCurrent).toBeCloseTo(12 / p.totalResistance, 12);
        expect(p.totalPower).toBeCloseTo(3.12, 10);
    });
    it('two equal resistors halve; R_parallel < min(Rk)', () => {
        expect(parallelResistance([100, 100])).toBeCloseTo(50, 12);
        expect(parallelResistance([100, 200, 150])).toBeLessThan(100);
    });
    it('edge cases: zero-ohm branch shorts, open branch ignored, empty is open', () => {
        expect(parallelResistance([100, 0])).toBe(0);
        expect(parallelResistance([Infinity, 100])).toBeCloseTo(100, 12);
        expect(parallelResistance([])).toBe(Infinity);
    });
    it('current divider I1 = I·R2/(R1+R2)', () => {
        expect(currentDivider(3, 6, 3)).toBeCloseTo(1, 12);
    });
});

describe('linear solver (Gaussian elimination with partial pivoting)', () => {
    it('classic 3×3: 2x+y−z=8, −3x−y+2z=−11, −2x+y+2z=−3 → (2, 3, −1)', () => {
        const x = solveLinearSystem([[2, 1, -1], [-3, -1, 2], [-2, 1, 2]], [8, -11, -3]);
        expect(x[0]).toBeCloseTo(2, 12);
        expect(x[1]).toBeCloseTo(3, 12);
        expect(x[2]).toBeCloseTo(-1, 12);
    });
    it('needs pivoting (zero on diagonal) and does not mutate inputs', () => {
        const A = [[0, 1], [1, 0]];
        const b = [2, 3];
        expect(solveLinearSystem(A, b)).toEqual([3, 2]);
        expect(A).toEqual([[0, 1], [1, 0]]);
    });
    it('singular matrix → null', () => {
        expect(solveLinearSystem([[1, 2], [2, 4]], [1, 2])).toBeNull();
    });
});

describe("Kirchhoff's laws: E – R1 – (R2 ‖ R3)", () => {
    it('E=12, R1=4, R2=6, R3=3 → I1=2 A, I2=2/3 A, I3=4/3 A (Rt = 4 + 2 = 6 Ω)', () => {
        const k = solveKirchhoffCircuit(12, 4, 6, 3);
        expect(k.i1).toBeCloseTo(2, 12);
        expect(k.i2).toBeCloseTo(2 / 3, 12);
        expect(k.i3).toBeCloseTo(4 / 3, 12);
        expect(k.v1).toBeCloseTo(8, 12);
        expect(k.v2).toBeCloseTo(4, 12);
        expect(k.v3).toBeCloseTo(4, 12);
        expect(k.totalResistance).toBeCloseTo(6, 12);
    });
    it('KCL and both KVL loops are satisfied for every slider combination', () => {
        for (const E of [6, 12, 24]) {
            for (let R1 = 1; R1 <= 10; R1 += 3) {
                for (let R2 = 2; R2 <= 12; R2 += 5) {
                    for (let R3 = 2; R3 <= 12; R3 += 5) {
                        const k = solveKirchhoffCircuit(E, R1, R2, R3);
                        expect(Math.abs(k.kclResidual)).toBeLessThan(1e-12);
                        expect(Math.abs(k.kvlResiduals[0])).toBeLessThan(1e-12);
                        expect(Math.abs(k.kvlResiduals[1])).toBeLessThan(1e-12);
                        // agrees with series-parallel reduction
                        const Rt = R1 + (R2 * R3) / (R2 + R3);
                        expect(k.i1).toBeCloseTo(E / Rt, 12);
                    }
                }
            }
        }
    });
    it('singular network (all resistors zero) → null', () => {
        expect(solveKirchhoffCircuit(12, 0, 0, 0)).toBeNull();
    });
});

describe('electric power and energy', () => {
    it('P = UI = I²R = U²/R: 12 V, 2 A, 6 Ω → 24 W', () => {
        expect(powerUI(12, 2)).toBe(24);
        expect(powerI2R(2, 6)).toBe(24);
        expect(powerU2R(12, 6)).toBe(24);
        expect(powerU2R(12, 0)).toBe(Infinity);
        expect(powerU2R(0, 0)).toBe(0);
    });
    it('kWh conversion E = P·t/1000 and cost', () => {
        expect(energyKWh(24, 1)).toBeCloseTo(0.024, 12);
        expect(energyKWh(1000, 1)).toBe(1);
        expect(energyJoules(1000, 3600)).toBe(3.6e6); // 1 kWh = 3.6 MJ
        expect(energyCost(24, 1, 500)).toBeCloseTo(12, 10);
        expect(energyCost(100, 10, 600)).toBeCloseTo(600, 10);
    });
});

describe('capacitors', () => {
    it('parallel plate C = ε0εrA/d: A = 1e-2 m², d = 1 mm → 88.54 pF; εr scales linearly', () => {
        expect(parallelPlateCapacitance(1e-2, 1e-3)).toBeCloseTo(8.8541878128e-11, 20);
        expect(parallelPlateCapacitance(1e-2, 1e-3, 2.2)).toBeCloseTo(2.2 * 8.8541878128e-11, 20);
        expect(parallelPlateCapacitance(1e-2, 0)).toBe(Infinity);
    });
    it('Q = CV = 1.2 mC and W = ½CV² = 7.2 mJ for 100 µF at 12 V', () => {
        expect(capacitorCharge(100e-6, 12)).toBeCloseTo(1.2e-3, 15);
        expect(capacitorEnergy(100e-6, 12)).toBeCloseTo(7.2e-3, 15);
    });
    it('series 1/C = Σ1/C (2 µF + 3 µF → 1.2 µF), parallel C = ΣC (→ 5 µF)', () => {
        expect(seriesCapacitance([2e-6, 3e-6])).toBeCloseTo(1.2e-6, 18);
        expect(parallelCapacitance([2e-6, 3e-6])).toBeCloseTo(5e-6, 18);
        expect(seriesCapacitance([2e-6, 0])).toBe(0);
        expect(seriesCapacitance([])).toBe(0);
    });
});

describe('RC transients (closed form)', () => {
    const R = 1000;
    const C = 100e-6; // τ = 0.1 s
    it('τ = RC: 1 kΩ · 100 µF = 0.1 s (µF prefix 1e-6)', () => {
        expect(rcTimeConstant(R, C)).toBeCloseTo(0.1, 15);
        expect(rcTimeConstant(100, 10e-6)).toBeCloseTo(1e-3, 15);
    });
    it('charging at t = τ: Vc = V0(1 − e⁻¹) = 6.3212 V, I = (V0/R)e⁻¹ = 3.6788 mA', () => {
        const s = rcCharging(10, R, C, 0.1);
        expect(s.vc).toBeCloseTo(6.321205588285577, 12);
        expect(s.i).toBeCloseTo(3.6787944117144233e-3, 15);
        expect(s.vc + s.vr).toBeCloseTo(10, 12); // KVL: V0 = Vc + I·R
        expect(s.i * R).toBeCloseTo(s.vr, 12);
        expect(s.q).toBeCloseTo(C * s.vc, 15);
        expect(s.energy).toBeCloseTo(0.5 * C * s.vc ** 2, 15);
    });
    it('charging boundary values: t = 0 → Vc = 0, I = V0/R; t = 5τ → 99.326 %', () => {
        const s0 = rcCharging(10, R, C, 0);
        expect(s0.vc).toBe(0);
        expect(s0.i).toBeCloseTo(0.01, 15);
        expect(rcCharging(10, R, C, 0.5).vc / 10).toBeCloseTo(1 - Math.exp(-5), 12);
    });
    it('discharging at t = τ: Vc = V0e⁻¹ = 3.6788 V, I = −3.6788 mA', () => {
        const s = rcDischarging(10, R, C, 0.1);
        expect(s.vc).toBeCloseTo(3.6787944117144233, 12);
        expect(s.i).toBeCloseTo(-3.6787944117144233e-3, 15);
        expect(rcDischarging(10, R, C, 0).vc).toBe(10);
    });
    it('result depends only on elapsed time, not on how it was stepped', () => {
        const direct = rcCharging(10, R, C, 0.237).vc;
        let t = 0;
        for (let k = 0; k < 237; k++) t += 0.001; // e.g. many tiny frames
        expect(rcCharging(10, R, C, t).vc).toBeCloseTo(direct, 10);
    });
    it('half-life t½ = τ·ln2 and t(63.2 %) ≈ τ', () => {
        expect(rcTimeToFraction(0.5, 0.1)).toBeCloseTo(0.1 * Math.LN2, 15);
        expect(rcTimeToFraction(1 - Math.exp(-1), 0.1)).toBeCloseTo(0.1, 12);
        expect(rcTimeToFraction(1, 0.1)).toBe(Infinity);
        expect(rcTimeToFraction(0, 0.1)).toBe(0);
    });
    it('edge cases: τ = 0 is instantaneous, negative t is "before the switch"', () => {
        expect(rcCharging(10, 0, C, 0.01).vc).toBe(10);
        expect(rcCharging(10, 0, C, 0.01).i).toBe(0);
        expect(rcCharging(10, R, C, -1).vc).toBe(0);
        expect(rcDischarging(10, R, C, -1).vc).toBe(10);
        expect(rcDischarging(10, R, 0, 0.01).vc).toBe(0);
    });
});

describe("Coulomb's law", () => {
    it('F = k|q1q2|/r²: 5 µC and −3 µC at 3 m → 14.979 mN', () => {
        expect(coulombForceMagnitude(5e-6, -3e-6, 3)).toBeCloseTo((K_COULOMB * 15e-12) / 9, 15);
        expect(coulombForceMagnitude(5e-6, -3e-6, 3)).toBeCloseTo(0.0149792529, 9);
        expect(coulombForceMagnitude(1e-6, 1e-6, 0)).toBe(Infinity);
        expect(coulombForceMagnitude(0, 1e-6, 0)).toBe(0);
    });
    it('inverse-square: doubling r quarters F', () => {
        expect(coulombForceMagnitude(1e-6, 1e-6, 2) / coulombForceMagnitude(1e-6, 1e-6, 1)).toBeCloseTo(0.25, 14);
    });
    it('vector direction: unlike charges attract, like charges repel', () => {
        const att = coulombForce(5e-6, { x: 0, y: 0 }, -3e-6, { x: 3, y: 0 });
        expect(att.fx).toBeGreaterThan(0); // charge 1 pulled toward charge 2 (+x)
        expect(att.attractive).toBe(true);
        expect(att.magnitude).toBeCloseTo(0.0149792529, 9);
        const rep = coulombForce(5e-6, { x: 0, y: 0 }, 3e-6, { x: 3, y: 0 });
        expect(rep.fx).toBeLessThan(0);
        expect(rep.repulsive).toBe(true);
        const none = coulombForce(0, { x: 0, y: 0 }, 3e-6, { x: 3, y: 0 });
        expect(none.magnitude).toBe(0);
        expect(none.attractive || none.repulsive).toBe(false);
    });
    it("Newton's third law and superposition", () => {
        const charges = [{ q: 2e-6, x: 0, y: 0 }, { q: -1e-6, x: 1, y: 0 }, { q: 1e-6, x: 0, y: 1 }];
        const f0 = netCoulombForce(0, charges);
        const f1 = netCoulombForce(1, charges);
        const f2 = netCoulombForce(2, charges);
        expect(f0.fx + f1.fx + f2.fx).toBeCloseTo(0, 15);
        expect(f0.fy + f1.fy + f2.fy).toBeCloseTo(0, 15);
        // force on charge 0: +k·2e-12 x̂ (attraction to q at x=1) −k·2e-12 ŷ (repulsion from q at y=1)
        expect(f0.fx).toBeCloseTo(K_COULOMB * 2e-12, 12);
        expect(f0.fy).toBeCloseTo(-K_COULOMB * 2e-12, 12);
    });
});

describe('electric field & potential (superposition)', () => {
    it('point charge 1 nC at 1 m: |E| = 8.988 V/m, V = 8.988 V; E points away from +q, toward −q', () => {
        const e = electricField([{ q: 1e-9, x: 0, y: 0 }], 1, 0);
        expect(e.ex).toBeCloseTo(8.987551792, 8);
        expect(e.ey).toBeCloseTo(0, 15);
        expect(electricPotential([{ q: 1e-9, x: 0, y: 0 }], 1, 0)).toBeCloseTo(8.987551792, 8);
        expect(electricField([{ q: -1e-9, x: 0, y: 0 }], 1, 0).ex).toBeCloseTo(-8.987551792, 8);
    });
    it('dipole ±10 µC at x = ∓1 m: E(0) = 2kq/1² = 179.75 kV/m along +x, V(0) = 0', () => {
        const dip = [{ q: 1e-5, x: -1, y: 0 }, { q: -1e-5, x: 1, y: 0 }];
        const e = electricField(dip, 0, 0);
        expect(e.ex).toBeCloseTo(2 * K_COULOMB * 1e-5, 6);
        expect(e.ey).toBeCloseTo(0, 12);
        expect(electricPotential(dip, 0, 0)).toBeCloseTo(0, 9);
        expect(electricPotential(dip, 0, 3)).toBeCloseTo(0, 9); // bisector is equipotential
    });
    it('field at a charge location is singular; zero charges are ignored', () => {
        expect(electricField([{ q: 1e-9, x: 0, y: 0 }], 0, 0).magnitude).toBe(Infinity);
        expect(electricPotential([{ q: -1e-9, x: 0, y: 0 }], 0, 0)).toBe(-Infinity);
        expect(electricField([{ q: 0, x: 0, y: 0 }], 0, 0).magnitude).toBe(0);
    });
    it('field lines: seeded from the dominant sign, count ∝ |q|', () => {
        const seedsA = fieldLineSeeds([{ q: 10, x: 0, y: 0 }, { q: -2, x: 1, y: 0 }]);
        expect(seedsA).toHaveLength(16);
        expect(seedsA.every((s) => s.source === 0 && s.direction === 1)).toBe(true);
        const seedsB = fieldLineSeeds([{ q: 2, x: 0, y: 0 }, { q: -10, x: 1, y: 0 }]);
        expect(seedsB.every((s) => s.source === 1 && s.direction === -1)).toBe(true);
        const seedsC = fieldLineSeeds([{ q: 10, x: 0, y: 0 }, { q: 5, x: 1, y: 0 }]);
        expect(seedsC).toHaveLength(16 + 8);
        expect(fieldLineSeeds([{ q: 0, x: 0, y: 0 }])).toEqual([]);
    });
    it('field line from + ends on −, and is tangent to E everywhere', () => {
        const dip = [{ q: 1e-5, x: -1, y: 0 }, { q: -1e-5, x: 1, y: 0 }];
        const line = traceFieldLine(dip, -1 + 0.25 * Math.cos(1), 0.25 * Math.sin(1), { stopRadius: 0.2 });
        expect(line.endCharge).toBe(1);
        const pts = line.points;
        for (let k = 1; k < pts.length - 2; k += 5) {
            const tx = pts[k + 1].x - pts[k].x;
            const ty = pts[k + 1].y - pts[k].y;
            const e = electricField(dip, (pts[k].x + pts[k + 1].x) / 2, (pts[k].y + pts[k + 1].y) / 2);
            const cos = (tx * e.ex + ty * e.ey) / (Math.hypot(tx, ty) * e.magnitude);
            expect(cos).toBeGreaterThan(0.999); // parallel and pointing along E
        }
        // traced backwards from the negative charge, it ends on the positive one
        const back = traceFieldLine(dip, 1 - 0.25, 0.01, { direction: -1, stopRadius: 0.2 });
        expect(back.endCharge).toBe(0);
    });
    it('a lone positive charge field line runs radially out of bounds', () => {
        const line = traceFieldLine([{ q: 1e-6, x: 0, y: 0 }], 0.25, 0, {
            bounds: { xMin: -2, xMax: 2, yMin: -2, yMax: 2 },
        });
        expect(line.endCharge).toBe(-1);
        const last = line.points[line.points.length - 1];
        expect(last.x).toBeGreaterThan(2);
        expect(Math.abs(last.y)).toBeLessThan(1e-12);
    });
    it('fieldLinePaths: dipole lines from + all leave the + charge, most end on −', () => {
        const dip = [{ q: 1e-5, x: 3, y: 2.5 }, { q: -1e-5, x: 5, y: 2.5 }];
        const paths = fieldLinePaths(dip, { xMin: 0, xMax: 8, yMin: 0, yMax: 5 });
        expect(paths).toHaveLength(16);
        expect(paths.every((p) => p.source === 0 && p.direction === 1)).toBe(true);
        expect(paths.filter((p) => p.endCharge === 1).length).toBeGreaterThanOrEqual(10);
    });
    it('defaultPotentialLevels: ±k·qmax·{¼,½,1,2} and 0 for mixed signs', () => {
        const lv = defaultPotentialLevels([{ q: 1e-6 }, { q: -2e-6 }]);
        expect(lv).toHaveLength(9);
        expect(lv).toContain(0);
        expect(Math.max(...lv)).toBeCloseTo(2 * K_COULOMB * 2e-6, 6);
        expect(defaultPotentialLevels([{ q: 1e-6 }]).every((v) => v > 0)).toBe(true);
        expect(defaultPotentialLevels([{ q: 0 }])).toEqual([]);
    });
    it('equipotentialSegments: point charge contour V = kq/r0 is a circle of radius r0', () => {
        const q = [{ q: 1e-9, x: 0, y: 0 }];
        const V0 = K_COULOMB * 1e-9 / 1.5; // r0 = 1.5 m
        const [{ segments }] = equipotentialSegments(q, { xMin: -3, xMax: 3, yMin: -3, yMax: 3 }, [V0], 0.05);
        expect(segments.length).toBeGreaterThan(50);
        segments.forEach(([x1, y1]) => expect(Math.hypot(x1, y1)).toBeCloseTo(1.5, 1));
        // dipole: V = 0 contour is the perpendicular bisector x = 0
        const dip = [{ q: 1e-6, x: -1, y: 0 }, { q: -1e-6, x: 1, y: 0 }];
        const [{ segments: zero }] = equipotentialSegments(dip, { xMin: -2.05, xMax: 2.05, yMin: -2, yMax: 2 }, [0], 0.1);
        expect(zero.length).toBeGreaterThan(10);
        zero.forEach(([x1, , x2]) => {
            expect(Math.abs(x1)).toBeLessThan(1e-9);
            expect(Math.abs(x2)).toBeLessThan(1e-9);
        });
    });
    it('marching squares: iso-line of f(x,y) = x at level 1.5 lies on x = 1.5', () => {
        const nx = 5;
        const ny = 4;
        const vals = [];
        for (let j = 0; j < ny; j++) for (let i = 0; i < nx; i++) vals.push(i);
        const segs = contourSegments(vals, nx, ny, 0, 0, 1, 1, 1.5);
        expect(segs).toHaveLength(ny - 1);
        segs.forEach((s) => {
            expect(s[0]).toBeCloseTo(1.5, 12);
            expect(s[2]).toBeCloseTo(1.5, 12);
        });
    });
});

describe('DC motor', () => {
    it('F = NBIL: 50 turns, 0.25 T, 2 A, 5 cm → 1.25 N', () => {
        expect(motorSideForce(50, 0.25, 2, 0.05)).toBeCloseTo(1.25, 12);
    });
    it('τ = NBIA|sinθ| (max with coil plane ∥ B), average (2/π)NBIA', () => {
        expect(motorTorque(50, 0.25, 2, 0.002, Math.PI / 2)).toBeCloseTo(0.05, 12);
        expect(motorTorque(50, 0.25, 2, 0.002, 0)).toBeCloseTo(0, 12);
        expect(motorTorque(50, 0.25, 2, 0.002, -Math.PI / 2)).toBeCloseTo(0.05, 12); // commutator keeps sign
        // numeric average of |sin| over one turn = 2/π
        let s = 0;
        const N = 100000;
        for (let k = 0; k < N; k++) s += motorTorque(50, 0.25, 2, 0.002, (2 * Math.PI * (k + 0.5)) / N);
        expect(s / N).toBeCloseTo(motorAverageTorque(50, 0.25, 2, 0.002), 8);
    });
    it('rpm = 60ω/(2π); steady ω = τ̄/b; power balance ε̄·I = τ̄·ω', () => {
        expect(omegaToRpm(2 * Math.PI)).toBeCloseTo(60, 12);
        expect(motorSteadyOmega(0.1, 0.01)).toBeCloseTo(10, 12);
        expect(motorSteadyOmega(0.1, 0)).toBe(Infinity);
        const m = dcMotorState(2, 0.5);
        expect(m.B).toBeCloseTo(0.25, 12);
        expect(m.avgTorque).toBeCloseTo((2 / Math.PI) * 0.05, 12);
        expect(m.omega).toBeCloseTo(m.avgTorque / DC_MOTOR_DEFAULTS.damping, 12);
        expect(m.rpm).toBeCloseTo(60.7927, 3);
        expect(m.backEmf * 2).toBeCloseTo(m.avgTorque * m.omega, 12);
        expect(motorBackEmf(50, 0.25, 0.002, m.omega)).toBeCloseTo(m.backEmf, 12);
        // speed ∝ I·B
        expect(dcMotorState(4, 0.5).omega).toBeCloseTo(2 * m.omega, 10);
        expect(dcMotorState(2, 1).omega).toBeCloseTo(2 * m.omega, 10);
    });
    it('kinematics: ω(τm) = ωss(1 − e⁻¹) from rest, θ composes exactly (frame-rate independent)', () => {
        const a = motorKinematics(0, 0, 10, 0.5, 0.5);
        expect(a.omega).toBeCloseTo(10 * (1 - Math.exp(-1)), 12);
        expect(a.theta).toBeCloseTo(10 * (0.5 - 0.5 * (1 - Math.exp(-1))), 12);
        const whole = motorKinematics(0.3, 2, 10, 0.5, 1.7);
        let st = { theta: 0.3, omega: 2 };
        for (let k = 0; k < 17; k++) st = motorKinematics(st.theta, st.omega, 10, 0.5, 0.1);
        expect(st.theta).toBeCloseTo(whole.theta, 10);
        expect(st.omega).toBeCloseTo(whole.omega, 10);
        expect(motorKinematics(1, 0, 3, 0, 2)).toEqual({ theta: 7, omega: 3 });
    });
});

describe('diodes', () => {
    const VT = thermalVoltage(300);
    const si = DIODE_MODELS.silicon;
    it('Shockley I = Is(e^(V/nVT) − 1): zero at 0 V, −Is in reverse', () => {
        expect(shockleyCurrent(0, 1e-14)).toBe(0);
        expect(shockleyCurrent(-5, 1e-14)).toBeCloseTo(-1e-14, 25);
        expect(shockleyCurrent(0.7, 1e-14, 1, VT)).toBeCloseTo(1e-14 * Math.expm1(0.7 / VT), 15);
        expect(diodeVoltageFromCurrent(shockleyCurrent(0.65, 1e-14), 1e-14)).toBeCloseTo(0.65, 12);
    });
    it('model forward drops at 10 mA: Si ≈ 0.714 V, Ge ≈ 0.298 V, LED ≈ 1.786 V', () => {
        expect(diodeVoltageFromCurrent(0.01, si.Is, si.n)).toBeCloseTo(VT * Math.log1p(1e12), 12);
        expect(diodeVoltageFromCurrent(0.01, si.Is, si.n)).toBeCloseTo(0.7143, 3);
        expect(diodeVoltageFromCurrent(0.01, 1e-7, 1)).toBeCloseTo(0.2976, 3);
        expect(diodeVoltageFromCurrent(0.01, 1e-17, 2)).toBeCloseTo(1.7858, 3);
    });
    it('Lambert W: W(1) = Ω = 0.567143…, W(e) = 1, W(0) = 0, huge arguments via log', () => {
        expect(lambertW0(1)).toBeCloseTo(0.5671432904097838, 14);
        expect(lambertW0(Math.E)).toBeCloseTo(1, 14);
        expect(lambertW0(0)).toBe(0);
        expect(lambertW0(1e-12)).toBeCloseTo(1e-12, 22);
        expect(lambertW0(-1)).toBeNaN();
        const w = lambertW0FromLog(1000); // W(e^1000)
        expect(w + Math.log(w)).toBeCloseTo(1000, 10);
    });
    it('exact operating point satisfies Vs = I·R + nVT·ln(1 + I/Is) (Si, 5 V, 100 Ω → 42.48 mA)', () => {
        const op = diodeCircuit(5, 100, si);
        expect(op.current).toBeCloseTo(0.0424829, 6);
        expect(op.diodeVoltage).toBeCloseTo(0.75171, 4);
        expect(op.resistorVoltage + op.diodeVoltage).toBeCloseTo(5, 12);
        expect(shockleyCurrent(op.diodeVoltage, si.Is, si.n)).toBeCloseTo(op.current, 12);
        // within 1.5 % of the textbook constant-drop model (5 − 0.7)/100 = 43 mA
        expect(rel(op.current, piecewiseDiodeCurrent(5, 100, 0.7))).toBeLessThan(0.015);
    });
    it('operating point residual is tiny for every model and slider value', () => {
        for (const model of Object.values(DIODE_MODELS)) {
            for (let Vs = -5; Vs <= 10; Vs += 0.5) {
                for (const R of [50, 100, 250, 500]) {
                    const op = diodeCircuit(Vs, R, model);
                    const vd = Vs - op.current * R;
                    expect(Math.abs(shockleyCurrent(vd, model.Is, model.n) - op.current))
                        .toBeLessThan(1e-9 * Math.max(1e-3, Math.abs(op.current)));
                }
            }
        }
    });
    it('reverse bias: I ≈ −Is, V_D ≈ Vs; LED below threshold barely conducts', () => {
        const rev = diodeCircuit(-5, 100, si);
        expect(rev.current).toBeCloseTo(-1e-14, 20);
        expect(rev.diodeVoltage).toBeCloseTo(-5, 9);
        expect(diodeCircuit(1.0, 100, DIODE_MODELS.led).current).toBeLessThan(1e-7);
        expect(diodeCircuit(0, 100, si).current).toBeCloseTo(0, 20);
    });
    it('R = 0 uses Shockley directly; piecewise model edge cases', () => {
        expect(diodeCircuit(0.6, 0, si).current).toBeCloseTo(shockleyCurrent(0.6, si.Is), 15);
        expect(piecewiseDiodeCurrent(0.5, 100, 0.7)).toBe(0);
        expect(piecewiseDiodeCurrent(0.7, 100, 0.7)).toBe(0);
        expect(piecewiseDiodeCurrent(5, 100, 0.7)).toBeCloseTo(0.043, 12);
        expect(piecewiseDiodeCurrent(5, 0, 0.7)).toBe(Infinity);
    });
    it('half-wave rectifier: passes positive half (minus ≈0.6–0.8 V), blocks negative', () => {
        const vp = halfWaveRectifierOutput(10, 1000, si);
        expect(vp).toBeGreaterThan(9.2);
        expect(vp).toBeLessThan(9.45);
        expect(Math.abs(halfWaveRectifierOutput(-10, 1000, si))).toBeLessThan(1e-9);
    });
});
