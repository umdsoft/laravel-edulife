import { describe, it, expect } from 'vitest';
import {
    R, ATM, SIGMA, ZERO_CELSIUS_IN_K, WATER,
    clamp, celsiusToKelvin, kelvinToCelsius,
    linearExpansion, expandedLength, volumetricExpansion, volumetricCoefficientFromLinear, rampTemperature,
    sensibleHeat, temperatureChange, specificHeatFromMeasurement, constantPowerHeating,
    latentHeat, heatingCurveBreakpoints, heatingCurve, heatingCurveHeatFor,
    equilibriumTemperature, heatReleased,
    idealGasPressure, idealGasVolume, idealGasTemperature, rmsSpeed,
    isothermalWork, adiabaticWork, adiabaticVolume, adiabaticTemperature,
    conductionHeatFlux, conductionPower, conductionTemperatureAt, radiationNetFlux, radiationNetPower,
    carnotEfficiency, carnotCycle, carnotStateAt,
    entropyIsothermal, entropyHeating, entropyIsobaricHeating, isobarTemperatureAtEntropy,
    LINEAR_EXPANSION, SPECIFIC_HEAT, THERMAL_CONDUCTIVITY, THERMO_INSTRUMENTS,
} from '../thermodynamics.js';
import { GAS_CONSTANT, STEFAN_BOLTZMANN } from '../constants.js';
import { maxReadingError, INSTRUMENTS } from '../instruments.js';

const LN2 = 0.6931471805599453;
/** R = N_A·k (exact, SI 2019 / CODATA 2022): 8.314 462 618 153 24 J/(mol·K). */
const RX = 8.31446261815324;

describe('constants', () => {
    it('match CODATA 2022 / SI exact values', () => {
        expect(R).toBeCloseTo(8.314462618, 9);
        expect(R).toBe(6.02214076e23 * 1.380649e-23);
        expect(ATM).toBe(101325);
        expect(SIGMA).toBeCloseTo(5.670374419e-8, 17);
        expect(ZERO_CELSIUS_IN_K).toBe(273.15);
    });
    it('clamp', () => {
        expect(clamp(5, 0, 3)).toBe(3);
        expect(clamp(-1, 0, 3)).toBe(0);
        expect(clamp(2, 0, 3)).toBe(2);
    });
});

describe('temperature scales', () => {
    it('T[K] = t[°C] + 273.15', () => {
        expect(celsiusToKelvin(25)).toBeCloseTo(298.15, 10);
        expect(celsiusToKelvin(-273.15)).toBe(0);
        expect(kelvinToCelsius(373.15)).toBeCloseTo(100, 10);
    });
    it('rejects temperatures below absolute zero', () => {
        expect(celsiusToKelvin(-300)).toBeNaN();
        expect(kelvinToCelsius(-1)).toBeNaN();
        expect(celsiusToKelvin(NaN)).toBeNaN();
    });
});

describe('thermal expansion (ΔL = αL₀ΔT)', () => {
    it('steel rod 1 m, ΔT = 80 K → ΔL = 0.96 mm', () => {
        expect(linearExpansion(12e-6, 1, 80)).toBeCloseTo(9.6e-4, 12);
    });
    it('aluminium rod 2 m, ΔT = 80 K → ΔL = 3.68 mm', () => {
        expect(linearExpansion(23e-6, 2, 80)).toBeCloseTo(3.68e-3, 12);
    });
    it('cooling gives contraction (negative ΔL)', () => {
        expect(linearExpansion(12e-6, 1, -40)).toBeCloseTo(-4.8e-4, 12);
    });
    it('zero ΔT or zero length gives zero', () => {
        expect(linearExpansion(17e-6, 1, 0)).toBe(0);
        expect(linearExpansion(17e-6, 0, 50)).toBe(0);
    });
    it('L = L₀(1 + αΔT): copper 1.5 m, ΔT = 50 K → 1.501275 m', () => {
        expect(expandedLength(17e-6, 1.5, 50)).toBeCloseTo(1.501275, 12);
    });
    it('ΔV = βV₀ΔT with β = 3α (aluminium, 1 L, 50 K → 3.45 cm³)', () => {
        const beta = volumetricCoefficientFromLinear(23e-6);
        expect(beta).toBeCloseTo(69e-6, 15);
        expect(volumetricExpansion(beta, 1e-3, 50)).toBeCloseTo(3.45e-6, 15);
    });
    it('β ≈ 3α agrees with exact cube (1+αΔT)³ − 1 to first order', () => {
        const a = 23e-6; const dT = 100;
        const exact = (1 + a * dT) ** 3 - 1;
        expect(Math.abs(exact - 3 * a * dT) / exact).toBeLessThan(3e-3);
    });
    it('invalid input → NaN', () => {
        expect(linearExpansion(12e-6, -1, 10)).toBeNaN();
        expect(expandedLength(NaN, 1, 10)).toBeNaN();
        expect(volumetricExpansion(1e-5, -1, 10)).toBeNaN();
    });
});

describe('rampTemperature (analytic T(t), frame-rate independent)', () => {
    it('heating at 30 °C/s from 20 °C', () => {
        expect(rampTemperature(20, 30, 2, -20, 100)).toBe(80);
        expect(rampTemperature(20, 30, 10, -20, 100)).toBe(100);
    });
    it('cooling clamps at the lower limit', () => {
        expect(rampTemperature(20, -30, 1, -20, 100)).toBe(-10);
        expect(rampTemperature(20, -30, 5, -20, 100)).toBe(-20);
    });
    it('negative elapsed time treated as 0', () => {
        expect(rampTemperature(20, 30, -1, -20, 100)).toBe(20);
    });
});

describe('sensible heat (Q = mcΔT)', () => {
    it('0.2 kg water heated by 80 K needs 66 976 J', () => {
        expect(sensibleHeat(0.2, 4186, 80)).toBeCloseTo(66976, 8);
    });
    it('ΔT = Q/(mc) and c = Q/(mΔT) are inverses', () => {
        expect(temperatureChange(66976, 0.2, 4186)).toBeCloseTo(80, 10);
        expect(specificHeatFromMeasurement(66976, 0.2, 80)).toBeCloseTo(4186, 8);
    });
    it('division-by-zero guards', () => {
        expect(temperatureChange(100, 0, 4186)).toBeNaN();
        expect(temperatureChange(100, 1, 0)).toBeNaN();
        expect(specificHeatFromMeasurement(100, 1, 0)).toBeNaN();
        expect(sensibleHeat(-1, 4186, 10)).toBeNaN();
    });
});

describe('constantPowerHeating (T = T₀ + Pt/(mc))', () => {
    const base = { T0: 20, power: 100, mass: 0.2, c: 4186, Tmax: 100 };
    it('after 100 s at 100 W: Q = 10 kJ, T = 20 + 10000/837.2 = 31.9446 °C', () => {
        const r = constantPowerHeating({ ...base, elapsed: 100 });
        expect(r.Q).toBeCloseTo(10000, 8);
        expect(r.T).toBeCloseTo(31.94457716, 6);
        expect(r.done).toBe(false);
    });
    it('stops exactly at Tmax: t_max = mcΔT/P = 669.76 s, Q = 66 976 J', () => {
        const r = constantPowerHeating({ ...base, elapsed: 5000 });
        expect(r.tMax).toBeCloseTo(669.76, 8);
        expect(r.T).toBe(100);
        expect(r.Q).toBeCloseTo(66976, 6);
        expect(r.done).toBe(true);
    });
    it('result is independent of how elapsed time was accumulated (frame rate)', () => {
        let t30 = 0; for (let i = 0; i < 30 * 50; i++) t30 += 1 / 30;
        let t144 = 0; for (let i = 0; i < 144 * 50; i++) t144 += 1 / 144;
        const a = constantPowerHeating({ ...base, elapsed: t30 });
        const b = constantPowerHeating({ ...base, elapsed: t144 });
        expect(a.T).toBeCloseTo(b.T, 9);
        expect(a.Q).toBeCloseTo(5000, 6);
    });
    it('zero power never finishes and never heats', () => {
        const r = constantPowerHeating({ ...base, power: 0, elapsed: 100 });
        expect(r.T).toBe(20);
        expect(r.done).toBe(false);
    });
    it('invalid mass → NaN', () => {
        expect(constantPowerHeating({ ...base, mass: 0, elapsed: 1 }).T).toBeNaN();
    });
});

describe('latent heat and heating curve of 0.5 kg ice from −20 °C', () => {
    const p = { mass: 0.5, T0: -20, ...WATER };
    it('Q = mL', () => {
        expect(latentHeat(0.5, 334000)).toBe(167000);
        expect(latentHeat(0.5, 2260000)).toBe(1130000);
        expect(latentHeat(-1, 1)).toBeNaN();
    });
    it('breakpoints include ALL sensible heats (regression: old code ignored them)', () => {
        const bp = heatingCurveBreakpoints(p);
        expect(bp.solidEnd).toBeCloseTo(21000, 8);        // 0.5·2100·20
        expect(bp.meltEnd).toBeCloseTo(188000, 8);        // + 0.5·334000
        expect(bp.liquidEnd).toBeCloseTo(397300, 8);      // + 0.5·4186·100
        expect(bp.boilEnd).toBeCloseTo(1527300, 8);       // + 0.5·2260000
    });
    it('ice warming segment: Q = 10.5 kJ → −10 °C', () => {
        const s = heatingCurve(10500, p);
        expect(s.phase).toBe('solid');
        expect(s.T).toBeCloseTo(-10, 10);
    });
    it('melting plateau at 0 °C, fraction proportional to latent heat absorbed', () => {
        expect(heatingCurve(21000, p)).toMatchObject({ phase: 'melting', T: 0, meltedFraction: 0 });
        const s = heatingCurve(21000 + 83500, p);
        expect(s.T).toBe(0);
        expect(s.meltedFraction).toBeCloseTo(0.5, 12);
    });
    it('still melting at Q = 167 kJ + 1 J (old code wrongly switched to liquid)', () => {
        const s = heatingCurve(167001, p);
        expect(s.phase).toBe('melting');
        expect(s.meltedFraction).toBeCloseTo((167001 - 21000) / 167000, 12);
    });
    it('water segment: 104 650 J past melt end → 50 °C', () => {
        const s = heatingCurve(188000 + 104650, p);
        expect(s.phase).toBe('liquid');
        expect(s.T).toBeCloseTo(50, 10);
    });
    it('boiling plateau at 100 °C', () => {
        const s = heatingCurve(397300 + 565000, p);
        expect(s.phase).toBe('boiling');
        expect(s.T).toBe(100);
        expect(s.vaporizedFraction).toBeCloseTo(0.5, 12);
    });
    it('still boiling at (Lf+Lv)·m + 1 J (old code wrongly switched to gas)', () => {
        expect(heatingCurve(1297001, p).phase).toBe('boiling');
    });
    it('steam segment: 10 kJ past boil end → 110 °C', () => {
        const s = heatingCurve(1527300 + 10000, p);
        expect(s.phase).toBe('gas');
        expect(s.T).toBeCloseTo(110, 10);
    });
    it('continuous at every breakpoint', () => {
        const bp = heatingCurveBreakpoints(p);
        for (const q of [bp.solidEnd, bp.meltEnd, bp.liquidEnd, bp.boilEnd]) {
            expect(heatingCurve(q - 1e-6, p).T).toBeCloseTo(heatingCurve(q, p).T, 5);
        }
    });
    it('negative heat is clamped to the initial state', () => {
        expect(heatingCurve(-100, p).T).toBe(-20);
    });
    it('inverse: heat to reach 150 °C = 1 577 300 J, to reach 50 °C = 292 650 J', () => {
        expect(heatingCurveHeatFor(150, p)).toBeCloseTo(1577300, 6);
        expect(heatingCurveHeatFor(50, p)).toBeCloseTo(292650, 6);
        expect(heatingCurveHeatFor(-30, p)).toBe(0);
        expect(heatingCurve(heatingCurveHeatFor(150, p), p).T).toBeCloseTo(150, 9);
    });
    it('start in the liquid phase (20 °C water)', () => {
        const q = { ...p, T0: 20 };
        const bp = heatingCurveBreakpoints(q);
        expect(bp.solidEnd).toBe(0);
        expect(bp.liquidEnd).toBeCloseTo(167440, 8);
        expect(heatingCurve(83720, q).T).toBeCloseTo(60, 10);
    });
    it('start in the gas phase (120 °C steam)', () => {
        const s = heatingCurve(1000, { ...p, T0: 120 });
        expect(s.phase).toBe('gas');
        expect(s.T).toBeCloseTo(121, 10);
    });
    it('invalid mass → NaN', () => {
        expect(heatingCurve(10, { ...p, mass: 0 }).T).toBeNaN();
    });
});

describe('calorimetry (T_f = Σmc T / Σmc)', () => {
    it('200 g at 80 °C + 300 g at 20 °C water → 44 °C', () => {
        expect(equilibriumTemperature([
            { m: 0.2, c: 4186, T: 80 },
            { m: 0.3, c: 4186, T: 20 },
        ])).toBeCloseTo(44, 10);
    });
    it('0.5 kg iron at 100 °C into 1 kg water at 20 °C → 24.0807 °C', () => {
        expect(equilibriumTemperature([
            { m: 0.5, c: 450, T: 100 },
            { m: 1, c: 4186, T: 20 },
        ])).toBeCloseTo(106220 / 4411, 10);
    });
    it('heat released by the hot water: 0.2·4186·36 = 30 139.2 J (= 30.14 kJ, regression ×1000)', () => {
        expect(heatReleased(0.2, 4186, 80, 44)).toBeCloseTo(30139.2, 6);
    });
    it('energy balance: heat lost = heat gained', () => {
        const Tf = equilibriumTemperature([{ m: 0.25, c: 4186, T: 90 }, { m: 0.4, c: 4186, T: 10 }]);
        expect(heatReleased(0.25, 4186, 90, Tf)).toBeCloseTo(-heatReleased(0.4, 4186, 10, Tf), 6);
    });
    it('edge cases', () => {
        expect(equilibriumTemperature([])).toBeNaN();
        expect(equilibriumTemperature([{ m: 0, c: 4186, T: 50 }])).toBeNaN();
        expect(equilibriumTemperature([{ m: -1, c: 4186, T: 50 }])).toBeNaN();
        expect(equilibriumTemperature([{ m: 1, c: 4186, T: 50 }])).toBe(50);
    });
});

describe('ideal gas (PV = nRT)', () => {
    it('1 mol at 0 °C and 1 atm occupies 22.413 969 54 L (CODATA molar volume)', () => {
        expect(idealGasVolume(1, 273.15, ATM) * 1000).toBeCloseTo(22.41396954, 7);
    });
    it('1 mol at 300 K and 1 atm occupies 24.617 L', () => {
        expect(idealGasVolume(1, 300, ATM) * 1000).toBeCloseTo(24.61721, 4);
    });
    it('1 mol, 300 K in 1 L → P = 2 494 339 Pa (≈ 24.6 atm)', () => {
        expect(idealGasPressure(1, 300, 1e-3)).toBeCloseTo(RX * 300 * 1000, 4);
        expect(idealGasPressure(1, 300, 1e-3)).toBeCloseTo(2494338.785, 2);
    });
    it('T = PV/(nR) round-trips', () => {
        const V = idealGasVolume(2, 450, 2 * ATM);
        expect(idealGasTemperature(2 * ATM, V, 2)).toBeCloseTo(450, 9);
    });
    it('Boyle, Charles and Gay-Lussac proportionalities', () => {
        expect(idealGasPressure(1, 300, 2e-3)).toBeCloseTo(idealGasPressure(1, 300, 1e-3) / 2, 6);
        expect(idealGasVolume(1, 600, ATM)).toBeCloseTo(2 * idealGasVolume(1, 300, ATM), 12);
        expect(idealGasPressure(1, 600, 1e-3)).toBeCloseTo(2 * idealGasPressure(1, 300, 1e-3), 6);
    });
    it('rejects non-physical states (T ≤ 0 K, V = 0, P = 0)', () => {
        expect(idealGasPressure(1, 0, 1e-3)).toBeNaN();
        expect(idealGasPressure(1, -10, 1e-3)).toBeNaN();
        expect(idealGasPressure(1, 300, 0)).toBeNaN();
        expect(idealGasVolume(1, 300, 0)).toBeNaN();
        expect(idealGasTemperature(ATM, 1e-3, 0)).toBeNaN();
    });
    it('v_rms of N₂ at 300 K = √(3RT/M) ≈ 517 m/s', () => {
        expect(rmsSpeed(300, 0.028)).toBeCloseTo(Math.sqrt((3 * RX * 300) / 0.028), 6);
        expect(rmsSpeed(300, 0)).toBeNaN();
    });
});

describe('isothermal & adiabatic processes', () => {
    it('W = nRT ln(V₂/V₁): 1 mol, 300 K, 1→2 L → 1728.94 J', () => {
        expect(isothermalWork(1, 300, 1e-3, 2e-3)).toBeCloseTo(RX * 300 * LN2, 8);
        expect(isothermalWork(1, 300, 2e-3, 1e-3)).toBeCloseTo(-RX * 300 * LN2, 8);
        expect(isothermalWork(1, 300, 1e-3, 1e-3)).toBe(0);
        expect(isothermalWork(1, 300, 0, 1e-3)).toBeNaN();
    });
    it('W = (P₁V₁ − P₂V₂)/(γ − 1) = nC_vΔT: 1 mol monatomic 600→300 K → 3741.5 J', () => {
        const P1 = idealGasPressure(1, 600, 2e-3);
        const V2 = adiabaticVolume(2e-3, 600, 300, 5 / 3);
        const P2 = idealGasPressure(1, 300, V2);
        expect(adiabaticWork(P1, 2e-3, P2, V2, 5 / 3)).toBeCloseTo(1.5 * RX * 300, 6);
        expect(adiabaticWork(P1, 2e-3, P2, V2, 1)).toBeNaN();
    });
    it('adiabat: V₂ = V₁(T₁/T₂)^(1/(γ−1)), and PV^γ = const', () => {
        const V2 = adiabaticVolume(2e-3, 600, 300, 5 / 3);
        expect(V2).toBeCloseTo(2e-3 * 2 ** 1.5, 12);
        const P1 = idealGasPressure(1, 600, 2e-3);
        const P2 = idealGasPressure(1, 300, V2);
        expect(P1 * (2e-3) ** (5 / 3)).toBeCloseTo(P2 * V2 ** (5 / 3), 6);
    });
    it('adiabatic compression to half volume: T₂ = 300·2^(2/3) = 476.22 K', () => {
        expect(adiabaticTemperature(300, 1, 0.5, 5 / 3)).toBeCloseTo(300 * 2 ** (2 / 3), 10);
        expect(adiabaticTemperature(300, 1, 0, 5 / 3)).toBeNaN();
    });
});

describe('heat transfer', () => {
    it('Fourier q = kΔT/d: copper, ΔT = 80 K, d = 0.1 m → 308 000 W/m²', () => {
        expect(conductionHeatFlux(385, 80, 0.1)).toBeCloseTo(308000, 6);
        expect(conductionHeatFlux(0.15, 80, 0.1)).toBeCloseTo(120, 10);
        expect(conductionHeatFlux(385, 0, 0.1)).toBe(0);
    });
    it('P = kAΔT/d: aluminium 1 cm², ... → 205 W', () => {
        expect(conductionPower(205, 0.01, 50, 0.5)).toBeCloseTo(205, 10);
    });
    it('steady linear profile: mid-point of 100→20 °C is 60 °C', () => {
        expect(conductionTemperatureAt(0.05, 100, 20, 0.1)).toBeCloseTo(60, 10);
        expect(conductionTemperatureAt(1, 100, 20, 0.1)).toBe(20);
    });
    it('conduction guards d = 0 and negative k', () => {
        expect(conductionHeatFlux(385, 80, 0)).toBeNaN();
        expect(conductionHeatFlux(-1, 80, 0.1)).toBeNaN();
    });
    it('Stefan–Boltzmann: black body 1000 K into 0 K → σ·10¹² = 56 703.7 W/m²', () => {
        expect(radiationNetFlux(1, 1000, 0)).toBeCloseTo(56703.74419, 4);
    });
    it('ε = 0.5, 600 K vs 300 K → 0.5σ(1.296e11 − 8.1e9) = 3444.75 W/m²', () => {
        expect(radiationNetFlux(0.5, 600, 300)).toBeCloseTo(0.5 * 5.670374419e-8 * 1.215e11, 6);
        expect(radiationNetPower(0.5, 2, 600, 300)).toBeCloseTo(2 * 0.5 * 5.670374419e-8 * 1.215e11, 6);
    });
    it('100 °C vs 20 °C must use kelvin: ≈ 680.6 W/m² (°C would give ~5.7)', () => {
        const q = radiationNetFlux(1, celsiusToKelvin(100), celsiusToKelvin(20));
        expect(q).toBeCloseTo(680.6, 1);
    });
    it('radiation guards ε out of range and negative T', () => {
        expect(radiationNetFlux(1.5, 300, 200)).toBeNaN();
        expect(radiationNetFlux(1, -1, 200)).toBeNaN();
    });
});

describe('Carnot cycle', () => {
    it('η = 1 − Tc/Th in kelvin', () => {
        expect(carnotEfficiency(600, 300)).toBeCloseTo(0.5, 12);
        expect(carnotEfficiency(800, 200)).toBeCloseTo(0.75, 12);
        expect(carnotEfficiency(400, 400)).toBe(0);
    });
    it('°C trap: 100 °C / 0 °C → η = 1 − 273.15/373.15 = 26.8 % (not 100 %)', () => {
        expect(carnotEfficiency(celsiusToKelvin(100), celsiusToKelvin(0))).toBeCloseTo(0.267989, 6);
    });
    it('rejects Tc > Th and non-positive temperatures', () => {
        expect(carnotEfficiency(300, 600)).toBeNaN();
        expect(carnotEfficiency(600, 0)).toBeNaN();
        expect(carnotEfficiency(0, 0)).toBeNaN();
    });

    const cyc = carnotCycle({ n: 1, Th: 600, Tc: 300, V1: 1e-3, V2: 2e-3, gamma: 5 / 3 });
    it('Qh = nRTh ln(V₂/V₁) = 3457.89 J, Qc = nRTc ln(V₂/V₁) = 1728.94 J', () => {
        expect(cyc.Qh).toBeCloseTo(RX * 600 * LN2, 6);
        expect(cyc.Qc).toBeCloseTo(RX * 300 * LN2, 6);
    });
    it('W = Qh − Qc and W/Qh = η (first law consistency)', () => {
        expect(cyc.W).toBeCloseTo(cyc.Qh - cyc.Qc, 6);
        expect(cyc.W / cyc.Qh).toBeCloseTo(0.5, 10);
        expect(cyc.efficiency).toBeCloseTo(0.5, 12);
    });
    it('adiabatic works cancel: W₂₃ = nC_v(Th−Tc) = 3741.5 J = −W₄₁', () => {
        expect(cyc.works.w23).toBeCloseTo(1.5 * RX * 300, 6);
        expect(cyc.works.w41).toBeCloseTo(-1.5 * RX * 300, 6);
    });
    it('corner volumes from T V^(γ−1) = const', () => {
        expect(cyc.states[2].V).toBeCloseTo(2e-3 * 2 ** 1.5, 12);
        expect(cyc.states[3].V).toBeCloseTo(1e-3 * 2 ** 1.5, 12);
        expect(cyc.states[2].V / cyc.states[3].V).toBeCloseTo(2, 12);
        expect(cyc.states[2].P).toBeCloseTo((RX * 300) / (2e-3 * 2 ** 1.5), 4);
    });
    it('Clausius equality: Qh/Th = Qc/Tc (ΔS_cycle = 0)', () => {
        expect(cyc.Qh / 600).toBeCloseTo(cyc.Qc / 300, 10);
    });
    it('carnotStateAt follows isotherms/adiabats and closes the cycle', () => {
        const mid = carnotStateAt(cyc, 0, 0.5);
        expect(mid.T).toBe(600);
        expect(mid.P * mid.V).toBeCloseTo(RX * 600, 6);
        const ad = carnotStateAt(cyc, 1, 0.5);
        expect(ad.T * ad.V ** (2 / 3)).toBeCloseTo(600 * (2e-3) ** (2 / 3), 8);
        const end = carnotStateAt(cyc, 3, 1);
        expect(end.V).toBeCloseTo(cyc.states[0].V, 15);
        expect(end.T).toBeCloseTo(600, 9);
        expect(carnotStateAt(cyc, 2, 1).T).toBe(300);
    });
    it('invalid cycle parameters → null', () => {
        expect(carnotCycle({ Th: 300, Tc: 600, V1: 1e-3, V2: 2e-3 })).toBeNull();
        expect(carnotCycle({ Th: 600, Tc: 300, V1: 2e-3, V2: 1e-3 })).toBeNull();
        expect(carnotStateAt(null, 0, 0).P).toBeNaN();
    });
});

describe('entropy', () => {
    it('isothermal ΔS = Q/T: 500 J at 300 K → 1.6667 J/K', () => {
        expect(entropyIsothermal(500, 300)).toBeCloseTo(5 / 3, 12);
        expect(entropyIsothermal(500, 0)).toBeNaN();
    });
    it('ΔS = mc ln(T₂/T₁): 0.5 kg water 0→100 °C = 652.9 J/K', () => {
        expect(entropyHeating(0.5 * 4186, 273.15, 373.15)).toBeCloseTo(2093 * Math.log(373.15 / 273.15), 10);
        expect(entropyHeating(2093, 273.15, 373.15)).toBeCloseTo(652.9, 1);
        expect(entropyHeating(2093, 0, 300)).toBeNaN();
    });
    it('isobaric heating of 1 mol monatomic gas by 500 J at 300 K', () => {
        const Cp = 2.5 * R;
        const { T2, dS } = entropyIsobaricHeating(1, Cp, 300, 500);
        expect(T2).toBeCloseTo(300 + 500 / (2.5 * RX), 9);
        expect(dS).toBeCloseTo(2.5 * RX * Math.log(T2 / 300), 10);
        expect(dS).toBeCloseTo(1.6033, 3);
        // bounded by Q/T₂ < ΔS < Q/T₁ (old code returned 1.2·Q/T₁ = 2.0 J/K)
        expect(dS).toBeLessThan(500 / 300);
        expect(dS).toBeGreaterThan(500 / T2);
    });
    it('isobar in T–S plane: T(ΔS) = T₁ exp(ΔS/(nCp)) reaches T₂', () => {
        const Cp = 2.5 * R;
        const { T2, dS } = entropyIsobaricHeating(1, Cp, 300, 800);
        expect(isobarTemperatureAtEntropy(300, dS, 1, Cp)).toBeCloseTo(T2, 9);
        expect(isobarTemperatureAtEntropy(300, 0, 1, Cp)).toBe(300);
    });
    it('isobaric edge cases', () => {
        expect(entropyIsobaricHeating(0, 20.785, 300, 500).dS).toBeNaN();
        expect(entropyIsobaricHeating(1, 20.785, 300, -1e6).dS).toBeNaN(); // would cool below 0 K
        expect(entropyIsobaricHeating(1, 20.785, 300, 0).dS).toBe(0);
    });
});

describe('constants come from constants.js (CODATA 2022), no local copies', () => {
    it('R, σ are the exact SI-derived values', () => {
        expect(R).toBe(GAS_CONSTANT);
        expect(SIGMA).toBe(STEFAN_BOLTZMANN);
        // σ = 2π⁵k⁴/(15h³c²) = 5.670 374 419…e−8 (NIST)
        expect(SIGMA).toBeCloseTo(5.670374419e-8, 16);
        expect(5 * R / 2).toBeCloseTo(20.786157, 6); // Cₚ of a monatomic ideal gas
    });
});

describe('material tables (values + authoritative ranges)', () => {
    // [value, authoritative low, high] — see the JSDoc sources in thermodynamics.js
    const within = (v, lo, hi) => {
        expect(v).toBeGreaterThanOrEqual(lo);
        expect(v).toBeLessThanOrEqual(hi);
    };
    it('linear expansion α (Engineering ToolBox / CRC, 20–100 °C)', () => {
        within(LINEAR_EXPANSION.steel, 10.8e-6, 12.5e-6);
        within(LINEAR_EXPANSION.aluminium, 22.2e-6, 23.6e-6);
        within(LINEAR_EXPANSION.copper, 16.0e-6, 17.6e-6);
        within(LINEAR_EXPANSION.glass, 8.5e-6, 9.5e-6);
    });
    it('specific heats c (IAPWS-95 water, CRC metals)', () => {
        within(SPECIFIC_HEAT.water, 4181.6, 4216); // c_p of water 25…100 °C
        expect(Math.abs(SPECIFIC_HEAT.water - 4188) / 4188).toBeLessThan(0.001); // mean 20–100 °C
        within(SPECIFIC_HEAT.iron, 440, 460);
        within(SPECIFIC_HEAT.copper, 380, 390);
        within(SPECIFIC_HEAT.aluminium, 890, 910);
        expect(WATER.cLiquid).toBe(SPECIFIC_HEAT.water);
    });
    it('thermal conductivities k (commercial grades)', () => {
        within(THERMAL_CONDUCTIVITY.copper, 380, 401);
        within(THERMAL_CONDUCTIVITY.aluminium, 167, 237);
        within(THERMAL_CONDUCTIVITY.iron, 79.5, 80.4);
        within(THERMAL_CONDUCTIVITY.glass, 0.8, 1.1);
        within(THERMAL_CONDUCTIVITY.wood, 0.10, 0.17);
    });
    it('water latent heats within 0.2 % of IAPWS (333.55 kJ/kg, 2256.4 kJ/kg)', () => {
        expect(Math.abs(WATER.Lf - 333550) / 333550).toBeLessThan(0.002);
        expect(Math.abs(WATER.Lv - 2256400) / 2256400).toBeLessThan(0.002);
    });
});

describe('instruments of the thermodynamics experiments', () => {
    it('use the catalog specs (dial indicator 0.01 mm, thermometer 0.1 °C, stopwatch 0.01 s)', () => {
        expect(THERMO_INSTRUMENTS.dialIndicator).toBe(INSTRUMENTS.dialIndicator);
        expect(THERMO_INSTRUMENTS.thermometer).toBe(INSTRUMENTS.digitalThermometer);
        expect(THERMO_INSTRUMENTS.stopwatch).toBe(INSTRUMENTS.handStopwatch);
        expect(THERMO_INSTRUMENTS.gasVolumeScale.resolution).toBe(0.1);
        expect(THERMO_INSTRUMENTS.manometer.resolution).toBe(0.01);
    });
    it('worst-case reading errors', () => {
        expect(maxReadingError(1, THERMO_INSTRUMENTS.dialIndicator)).toBeCloseTo(0.01, 12); // mm
        expect(maxReadingError(50, THERMO_INSTRUMENTS.thermometer)).toBeCloseTo(0.125, 12); // °C
        expect(maxReadingError(25, THERMO_INSTRUMENTS.gasVolumeScale)).toBeCloseTo(0.1, 12); // L
    });
});
