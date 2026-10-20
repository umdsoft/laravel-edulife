import { describe, it, expect } from 'vitest';
import {
    EPSILON_0, K_COULOMB, formatSI, toFixedSafe,
    ohmCurrent, ohmVoltage, ohmResistance, ohmFormulaLine,
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
import {
    BOLTZMANN, ELEMENTARY_CHARGE, DIODE_TEMPERATURE, diodeTerminalVoltage,
    DMM_RANGES, DMM_SCATTER, dmmDisplay, dmmMaxError, createDmm, ELECTRIC_METERS, createSigFigMeter, UZ_HOUSEHOLD_TARIFF,
} from '../electricity.js';
import * as C from '../constants.js';

const rel = (a, b) => Math.abs(a - b) / Math.max(Math.abs(b), 1e-300);

describe('constants', () => {
    it('CODATA 2022: ε0 = 8.8541878188e-12 F/m, k = 1/(4πε0) = 8.9875517862e9 N·m²/C² (from constants.js)', () => {
        expect(EPSILON_0).toBe(8.8541878188e-12);
        expect(EPSILON_0).toBe(C.EPSILON_0);
        expect(K_COULOMB).toBe(C.K_COULOMB);
        expect(rel(K_COULOMB, 8.9875517862e9)).toBeLessThan(2e-11);
        expect(BOLTZMANN).toBe(1.380649e-23);
        expect(ELEMENTARY_CHARGE).toBe(1.602176634e-19);
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
        expect(parallelPlateCapacitance(1e-2, 1e-3)).toBeCloseTo(8.8541878188e-11, 20);
        expect(parallelPlateCapacitance(1e-2, 1e-3, 2.2)).toBeCloseTo(2.2 * 8.8541878188e-11, 20);
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
        expect(e.ex).toBeCloseTo(8.987551786, 8);
        expect(e.ey).toBeCloseTo(0, 15);
        expect(electricPotential([{ q: 1e-9, x: 0, y: 0 }], 1, 0)).toBeCloseTo(8.987551786, 8);
        expect(electricField([{ q: -1e-9, x: 0, y: 0 }], 1, 0).ex).toBeCloseTo(-8.987551786, 8);
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

// (separate import so the DC-motor block stays self-contained)
// eslint-disable-next-line import/first
import { MOTOR_TACHOMETER, tachometerResolution, tachometerReading, tachometerMaxError } from '../electricity.js';

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
        // b = 5·10⁻⁴ N·m·s: ω = (2/π)·0.05/5e-4 = 63.662 rad/s → 607.93 RPM (was 60.79 RPM with b = 5e-3)
        expect(DC_MOTOR_DEFAULTS.damping).toBe(5e-4);
        expect(m.rpm).toBeCloseTo(607.927, 2);
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
    it('realistic apparatus: 61…3040 RPM over the slider range, CC supply voltage U = I·Ra + ε̄ ≤ 15 V', () => {
        // hand calculation: k = (2/π)·N·B·A, ω = k·I/b, ε̄ = k·ω
        const k = (b) => (2 / Math.PI) * 50 * b * 0.002;
        const lo = dcMotorState(0.5, 0.2);
        const hi = dcMotorState(5, 1);
        expect(lo.rpm).toBeCloseTo(((k(0.1) * 0.5) / 5e-4) * 60 / (2 * Math.PI), 6); // 60.79
        expect(hi.rpm).toBeCloseTo(((k(0.5) * 5) / 5e-4) * 60 / (2 * Math.PI), 6); // 3039.6
        expect(hi.rpm).toBeCloseTo(3039.6, 1);
        const w = (k(0.5) * 5) / 5e-4;
        expect(hi.supplyVoltage).toBeCloseTo(5 * 0.5 + k(0.5) * w, 9); // 12.63 V
        expect(hi.supplyVoltage).toBeLessThan(15);
        const task = dcMotorState(2, 0.5);
        expect(task.supplyVoltage).toBeCloseTo(2 * 0.5 + k(0.25) * ((k(0.25) * 2) / 5e-4), 9); // ≈ 2.01 V
        // electrical power = Joule heat + mechanical power
        expect(task.supplyVoltage * 2).toBeCloseTo(2 * 2 * 0.5 + task.mechanicalPower, 9);
    });
    it('tachometer: 0.1 RPM below 1000 RPM, 1 RPM above; worst error ≤ 0.3 % ≪ 2 % task tolerance', () => {
        expect(tachometerResolution(607.93)).toBe(0.1);
        expect(tachometerResolution(1215.9)).toBe(1);
        expect(tachometerReading(607.927)).toBe(607.9);
        expect(tachometerReading(1215.85)).toBe(1216);
        expect(tachometerReading(607.927, 2.5)).toBeCloseTo(609.4, 9); // 607.927·1.0025 = 609.447 → 609.4
        expect(tachometerReading(607.927, 99)).toBe(tachometerReading(607.927, 2.5)); // clamped to ±2.5σ
        for (const v of [607.927, 1215.85]) {
            expect(tachometerMaxError(v) / v).toBeLessThan(0.003);
            for (const z of [-2.5, -1, 0, 1.3, 2.5]) expect(Math.abs(tachometerReading(v, z) - v)).toBeLessThanOrEqual(tachometerMaxError(v) + 1e-9);
        }
        expect(MOTOR_TACHOMETER.unit).toBe('RPM');
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
    it('model forward drops vs datasheets: Si 1N4001 0.69 V @10 mA, ≤ 1.1 V @1 A; Ge 0.30 V @10 mA; red LED 1.82 V @20 mA', () => {
        // independent: V = n·kT/e·ln(1 + I/Is) + I·Rs with the Motorola 1N4001 SPICE parameters
        const vt = (1.380649e-23 * 300) / 1.602176634e-19;
        expect(diodeTerminalVoltage(0.01, si)).toBeCloseTo(1.984 * vt * Math.log1p(0.01 / 14.11e-9) + 0.01 * 0.03389, 12);
        expect(diodeTerminalVoltage(0.01, si)).toBeCloseTo(0.6913, 3);
        expect(diodeTerminalVoltage(1, si)).toBeLessThan(1.1); // datasheet V_F max at 1 A
        expect(diodeTerminalVoltage(1, si)).toBeGreaterThan(0.85);
        expect(diodeTerminalVoltage(0.01, DIODE_MODELS.germanium)).toBeCloseTo(0.2976, 3);
        expect(diodeTerminalVoltage(0.3, DIODE_MODELS.germanium)).toBeLessThan(0.5); // Ge rectifier at its 0.3 A rating
        const led = diodeTerminalVoltage(0.02, DIODE_MODELS.led);
        expect(led).toBeCloseTo(1.8216, 3);
        expect(led).toBeGreaterThan(1.7);
        expect(led).toBeLessThan(2.5); // L-7113SRD-E: typ 1.85 V, max 2.5 V at 20 mA
        expect(DIODE_TEMPERATURE).toBe(300); // SPICE TNOM = 27 °C
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
    it('exact operating point satisfies Vs = I·(R + Rs) + nVT·ln(1 + I/Is) (Si 1N4001, 5 V, 100 Ω → 42.336 mA)', () => {
        const op = diodeCircuit(5, 100, si);
        expect(op.current).toBeCloseTo(0.0423361, 6);
        expect(op.diodeVoltage).toBeCloseTo(0.76639, 4);
        expect(op.resistorVoltage + op.diodeVoltage).toBeCloseTo(5, 12);
        expect(shockleyCurrent(op.diodeVoltage - op.current * si.Rs, si.Is, si.n)).toBeCloseTo(op.current, 12);
        // within 2 % of the textbook constant-drop model (5 − 0.7)/100 = 43 mA (task check tolerance)
        expect(rel(op.current, piecewiseDiodeCurrent(5, 100, 0.7))).toBeLessThan(0.02);
        // Ge and red LED task points
        expect(diodeCircuit(5, 100, DIODE_MODELS.germanium).current * 1000).toBeCloseTo(46.626, 3);
        expect(diodeCircuit(5, 150, DIODE_MODELS.led).current * 1000).toBeCloseTo(21.170, 3);
        expect(diodeCircuit(5, 150, DIODE_MODELS.led).current).toBeLessThan(0.03); // under the LED's 30 mA rating
    });
    it('operating point residual is tiny for every model and slider value', () => {
        for (const model of Object.values(DIODE_MODELS)) {
            for (let Vs = -5; Vs <= 10; Vs += 0.5) {
                for (const R of [50, 100, 250, 500]) {
                    const op = diodeCircuit(Vs, R, model);
                    const vj = Vs - op.current * (R + model.Rs); // junction voltage
                    expect(Math.abs(shockleyCurrent(vj, model.Is, model.n) - op.current))
                        .toBeLessThan(1e-9 * Math.max(1e-3, Math.abs(op.current)));
                }
            }
        }
    });
    it('reverse bias: I ≈ −Is, V_D ≈ Vs; LED below threshold barely conducts', () => {
        const rev = diodeCircuit(-5, 100, si);
        expect(rev.current).toBeCloseTo(-14.11e-9, 15); // ≈ −14 nA (1N4001 datasheet I_R ≤ 5 µA)
        expect(rev.diodeVoltage).toBeCloseTo(-5, 5); // −Is·R = 1.4 µV across the resistor
        expect(diodeCircuit(1.0, 100, DIODE_MODELS.led).current).toBeLessThan(1e-7);
        expect(diodeCircuit(0, 100, si).current).toBeCloseTo(0, 20);
    });
    it('R = 0: ideal junction uses Shockley directly, Rs still limits a real diode; piecewise edge cases', () => {
        const ideal = { Is: 1e-14, n: 1 };
        expect(diodeCircuit(0.6, 0, ideal).current).toBeCloseTo(shockleyCurrent(0.6, 1e-14), 15);
        const op = diodeCircuit(0.6, 0, si);
        expect(diodeTerminalVoltage(op.current, si)).toBeCloseTo(0.6, 9);
        expect(piecewiseDiodeCurrent(0.5, 100, 0.7)).toBe(0);
        expect(piecewiseDiodeCurrent(0.7, 100, 0.7)).toBe(0);
        expect(piecewiseDiodeCurrent(5, 100, 0.7)).toBeCloseTo(0.043, 12);
        expect(piecewiseDiodeCurrent(5, 0, 0.7)).toBe(Infinity);
    });
    it('half-wave rectifier: passes positive half (minus ≈0.6–0.8 V), blocks negative', () => {
        const vp = halfWaveRectifierOutput(10, 1000, si);
        expect(vp).toBeGreaterThan(9.2);
        expect(vp).toBeLessThan(9.45);
        expect(Math.abs(halfWaveRectifierOutput(-10, 1000, si))).toBeLessThan(2e-5); // leakage 14 nA · 1 kΩ
    });
});

describe('ohmFormulaLine (QA-S-13)', () => {
    it('shows the real current when the switch is closed', () => {
        expect(ohmFormulaLine(6, 100, true)).toBe('I = U / R = 6 / 100 = 60.00 mA');
    });
    it('does not claim 6 / 100 = 0 when the switch is open', () => {
        const line = ohmFormulaLine(6, 100, false);
        expect(line).toContain('I = 0 mA');
        expect(line).not.toContain('6 / 100');
    });
    it('marks a short circuit', () => {
        expect(ohmFormulaLine(6, 0, true)).toContain('∞');
    });
});

describe('laboratory meters (3½-digit DMM, force sensor, field meter, wattmeter)', () => {
    it('DMM auto-range: 2000 counts, lowest range that holds the rounded value', () => {
        expect(dmmDisplay(0.06, 'current')).toMatchObject({ text: '60.0', unit: 'mA', resolution: 1e-4 });
        expect(dmmDisplay(0.0123, 'current')).toMatchObject({ text: '12.30', unit: 'mA' });
        expect(dmmDisplay(0.00123, 'current')).toMatchObject({ text: '1.230', unit: 'mA' });
        // 20.00 mA does not fit the 20 mA range (max 19.99) → 200 mA range
        expect(dmmDisplay(0.02, 'current')).toMatchObject({ text: '20.0', unit: 'mA' });
        expect(dmmDisplay(0.019994, 'current')).toMatchObject({ text: '19.99', unit: 'mA' });
        expect(dmmDisplay(0.199996, 'current')).toMatchObject({ text: '0.20', unit: 'A' });
        expect(dmmDisplay(1.2, 'current')).toMatchObject({ text: '1.20', unit: 'A', resolution: 0.01 });
        expect(dmmDisplay(0, 'current')).toMatchObject({ text: '0.000', unit: 'mA' });
        expect(dmmDisplay(25, 'current').overload).toBe(true);
        expect(dmmDisplay(Infinity, 'current').text).toBe('OL');
        expect(dmmDisplay(0.080855, 'voltage')).toMatchObject({ text: '80.9', unit: 'mV', value: 0.0809 });
        expect(dmmDisplay(11.919, 'voltage')).toMatchObject({ text: '11.92', unit: 'V' });
        expect(dmmDisplay(1.5, 'voltage')).toMatchObject({ text: '1.500', unit: 'V' });
        expect(dmmDisplay(-4.2, 'voltage')).toMatchObject({ text: '-4.20', unit: 'V' });
        expect(dmmDisplay(450, 'resistance')).toMatchObject({ text: '450', unit: 'Ω' });
        expect(dmmDisplay(600 / 13, 'resistance')).toMatchObject({ text: '46.2', unit: 'Ω' });
        expect(dmmDisplay(4700, 'resistance')).toMatchObject({ text: '4.70', unit: 'kΩ' });
        expect(() => dmmDisplay(1, 'capacitance')).toThrow();
        // every range shows ≤ 1999 counts
        for (const ranges of Object.values(DMM_RANGES)) {
            for (const r of ranges.slice(0, -1)) expect(Math.round(r.max / r.res)).toBe(2000);
        }
    });
    it('DMM worst-case error = 2.5σ + ½ digit; seeded readings are reproducible and stay inside it', () => {
        expect(DMM_SCATTER).toEqual({ sigmaRel: 0.0005, limit: 2.5 });
        expect(dmmMaxError(0.06, 'current')).toBeCloseTo(2.5 * 0.0005 * 0.06 + 0.00005, 12);
        expect(dmmMaxError(1.2, 'current')).toBeCloseTo(2.5 * 0.0005 * 1.2 + 0.005, 12);
        const a = createDmm('current', 'x');
        const b = createDmm('current', 'x');
        const ra = [0, 1, 2, 3].map(() => a.read(0.0424, [5, 100]).value);
        const rb = [0, 1, 2, 3].map(() => b.read(0.0424, [5, 100]).value);
        expect(ra).toEqual(rb);
        for (let k = 0; k < 200; k++) {
            const v = a.read(0.06, [6, 100]).value;
            expect(Math.abs(v - 0.06)).toBeLessThanOrEqual(dmmMaxError(0.06, 'current') + 1e-12);
        }
        expect(a.show(0.06).text).toBe('60.0'); // steady display, no scatter
        expect(a.read(Infinity).overload).toBe(true);
    });
    it('field meter shows 4 significant digits with an SI prefix and stays within its max error', () => {
        const m = createSigFigMeter(ELECTRIC_METERS.fieldMeter, 'f');
        const E = 63551.588;
        for (let k = 0; k < 100; k++) {
            const r = m.read(E, [1]);
            expect(r.unit).toBe('kV/m');
            expect(r.text).toMatch(/^\d{2}\.\d{2}$/);
            expect(Math.abs(r.value - E)).toBeLessThanOrEqual(m.maxError(E) + 1e-9);
        }
        expect(m.maxError(E)).toBeCloseTo(2.5 * 0.001 * E + 5, 6);
        expect(m.read(NaN).text).toBe('—');
    });
    it('force sensor 0.01 mN and wattmeter 0.1 W specs', () => {
        expect(ELECTRIC_METERS.forceSensor.resolution).toBe(1e-5);
        expect(ELECTRIC_METERS.wattmeter.resolution).toBe(0.1);
    });
    it('Uzbek household tariff (inside the 200 kWh social norm, from 1 June 2026) = 650 so\'m/kWh', () => {
        expect(UZ_HOUSEHOLD_TARIFF).toBe(650);
        expect(energyCost(120, 5, UZ_HOUSEHOLD_TARIFF)).toBeCloseTo(390, 10);
    });
});
