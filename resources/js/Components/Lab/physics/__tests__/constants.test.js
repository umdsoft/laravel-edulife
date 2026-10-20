import { describe, it, expect } from 'vitest';
import * as C from '../constants.js';

// Reference values typed from the CODATA 2022 table
// https://physics.nist.gov/cuu/Constants/Table/allascii.txt
const rel = (a, b) => Math.abs(a - b) / Math.abs(b);

describe('CODATA 2022 constants', () => {
    it('exact SI defining constants', () => {
        expect(C.SPEED_OF_LIGHT).toBe(299792458);
        expect(C.PLANCK).toBe(6.62607015e-34);
        expect(C.ELEMENTARY_CHARGE).toBe(1.602176634e-19);
        expect(C.BOLTZMANN).toBe(1.380649e-23);
        expect(C.AVOGADRO).toBe(6.02214076e23);
    });

    it('exact derived constants agree with the NIST table', () => {
        expect(rel(C.GAS_CONSTANT, 8.314462618)).toBeLessThan(1e-10);
        expect(rel(C.STEFAN_BOLTZMANN, 5.670374419e-8)).toBeLessThan(1e-9);
        expect(rel(C.HC_EV_NM, 1239.84198)).toBeLessThan(1e-8);
        expect(rel(C.FARADAY, 96485.33212)).toBeLessThan(1e-10);
    });

    it('measured constants', () => {
        expect(rel(C.K_COULOMB, 8.9875517862e9)).toBeLessThan(1e-10);
        expect(rel(C.MU_0, 4 * Math.PI * 1e-7)).toBeLessThan(1e-9);
        expect(rel(C.RYDBERG_HYDROGEN, 1.09677583e7)).toBeLessThan(1e-8);
        expect(rel(C.RYDBERG_ENERGY_HYDROGEN_EV, 13.5983)).toBeLessThan(1e-5);
        expect(rel(C.RYDBERG_ENERGY_INFINITY_EV, C.RYDBERG_INFINITY * C.PLANCK * C.SPEED_OF_LIGHT / C.ELEMENTARY_CHARGE)).toBeLessThan(1e-11);
        // a₀ = ħ/(mₑcα)
        const hbar = C.PLANCK / (2 * Math.PI);
        expect(rel(C.BOHR_RADIUS, hbar / (C.ELECTRON_MASS * C.SPEED_OF_LIGHT * C.FINE_STRUCTURE))).toBeLessThan(1e-9);
        // 1 u·c² in MeV
        expect(rel(C.AMU_MEV, (C.AMU_KG * C.SPEED_OF_LIGHT ** 2) / (C.ELEMENTARY_CHARGE * 1e6))).toBeLessThan(1e-9);
    });
});

describe('gravity', () => {
    it('WGS 84 normal gravity: equator, pole, 45°', () => {
        expect(C.gravityAt(0)).toBeCloseTo(9.7803253359, 9);
        expect(C.gravityAt(90)).toBeCloseTo(9.8321849378, 8);
        expect(C.gravityAt(45)).toBeCloseTo(9.80620, 4);
    });

    it('Tashkent (41.30° N, 455 m) ≈ 9.8015 m/s²; the lab value 9.81 is within 0.1 %', () => {
        expect(C.G_TASHKENT).toBeCloseTo(9.8015, 3);
        expect(rel(C.LAB_GRAVITY, C.G_TASHKENT)).toBeLessThan(0.001);
    });
});
