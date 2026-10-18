/**
 * Formula calculator for the virtual-lab MeasurementPanel.
 *
 * Every formula id that LabExperimentSeeder can seed (see
 * database/seeders/data/lab/formulas.json) has an entry here. An entry declares
 *  - inputs: the variables the student types, in the units shown in the UI
 *            (the calculator converts them to SI before calling the physics),
 *  - result: the symbol and unit of the answer,
 *  - compute: a thin adapter around the tested functions in ./mechanics.js,
 *             ./thermodynamics.js, ./electricity.js, ./optics.js, ./waves.js,
 *             ./magnetism.js and ./atomic.js (no physics is re-implemented here).
 *
 * `calculate(id, inputs)` never returns a silent 0: unknown ids, missing or
 * invalid inputs and physically impossible cases come back as
 * `{ ok: false, error }` with a message in Uzbek.
 */
import * as mech from './mechanics.js';
import * as thermo from './thermodynamics.js';
import * as elec from './electricity.js';
import * as optics from './optics.js';
import * as waves from './waves.js';
import * as mag from './magnetism.js';
import * as atomic from './atomic.js';

const { toRad, toDeg } = mech;
const UC = 1e-6; // µC → C, µF → F
const NM = 1e-9;
const MM = 1e-3;
const CM = 1e-2;
const L_PER_M3 = 1000;

/** Input descriptor. Options: positive (> 0), nonNegative (≥ 0), integer, nonZero. */
const inp = (symbol, unit, opts = {}) => ({ symbol, unit, ...opts });
const pos = (symbol, unit, opts = {}) => inp(symbol, unit, { positive: true, ...opts });
const int = (symbol, opts = {}) => inp(symbol, '', { integer: true, positive: true, ...opts });
const res = (symbol, unit) => ({ symbol, unit });

/** Returns the value only when it is a finite number (null otherwise). */
const finite = (x) => (typeof x === 'number' && Number.isFinite(x) ? x : null);

const NO_REFRACTION = "To'liq ichki qaytish: bu burchakda singan nur yo'q.";
const AT_FOCUS = "Predmet fokusda turibdi: tasvir cheksizlikda (d' = ∞).";

/* eslint-disable quote-props */
export const CALCULATORS = Object.freeze({
    // ───────────────────────────── Mexanika ─────────────────────────────
    period: {
        inputs: [pos('L', 'm'), pos('g', 'm/s²')],
        result: res('T₀', 's'),
        compute: (v) => mech.pendulumSmallAnglePeriod(v.L, v.g),
    },
    pendulum_exact: {
        inputs: [pos('L', 'm'), pos('g', 'm/s²'), inp('θ₀', '°')],
        result: res('T', 's'),
        compute: (v) => (Math.abs(v['θ₀']) < 180 ? mech.pendulumExactPeriod(v.L, v.g, toRad(v['θ₀'])) : null),
        invalid: "Amplituda |θ₀| 180° dan kichik bo'lishi kerak.",
    },
    gravity: {
        inputs: [pos('L', 'm'), pos('T', 's')],
        result: res('g', 'm/s²'),
        compute: (v) => mech.gravityFromPendulum(v.L, v.T),
    },
    frequency: {
        inputs: [pos('T', 's')],
        result: res('ν', 'Hz'),
        // ν = 1/T — the same reciprocal as waves.period(f) = 1/f
        compute: (v) => waves.period(v.T),
    },
    height: {
        inputs: [pos('g', 'm/s²'), inp('t', 's', { nonNegative: true })],
        result: res('h', 'm'),
        compute: (v) => mech.freeFallDistance(v.t, v.g),
    },
    fall_time: {
        inputs: [pos('h', 'm'), pos('g', 'm/s²')],
        result: res('t', 's'),
        compute: (v) => mech.freeFallTime(v.h, v.g),
    },
    velocity: {
        inputs: [pos('g', 'm/s²'), inp('t', 's', { nonNegative: true })],
        result: res('v', 'm/s'),
        compute: (v) => mech.freeFallVelocity(v.t, v.g),
    },
    impact_speed: {
        inputs: [pos('h', 'm'), pos('g', 'm/s²')],
        result: res('v', 'm/s'),
        compute: (v) => mech.freeFallImpactSpeed(v.h, v.g),
    },
    gravity_from_h: {
        inputs: [pos('h', 'm'), pos('t', 's')],
        result: res('g', 'm/s²'),
        compute: (v) => mech.gravityFromFall(v.h, v.t),
    },
    newton_acceleration: {
        inputs: [inp('F', 'N'), pos('m', 'kg'), inp('f', 'N', { nonNegative: true })],
        result: res('a', 'm/s²'),
        compute: (v) => mech.newtonAcceleration(v.F, v.m, v.f),
    },
    incline_acceleration: {
        inputs: [inp('θ', '°'), inp('μ', '', { nonNegative: true }), pos('g', 'm/s²')],
        result: res('a', 'm/s²'),
        compute: (v) => mech.inclineAcceleration(toRad(v['θ']), v['μ'], v.g),
    },
    incline_normal_force: {
        inputs: [pos('m', 'kg'), pos('g', 'm/s²'), inp('θ', '°')],
        result: res('N', 'N'),
        compute: (v) => mech.inclineForces(v.m, toRad(v['θ']), 0, v.g).normal,
    },
    spring_period: {
        inputs: [pos('m', 'kg'), pos('k', 'N/m')],
        result: res('T', 's'),
        compute: (v) => mech.springPeriod(v.k, v.m),
    },
    spring_constant: {
        inputs: [pos('m', 'kg'), pos('T', 's')],
        result: res('k', 'N/m'),
        compute: (v) => mech.springConstantFromPeriod(v.m, v.T),
    },
    spring_energy: {
        inputs: [pos('k', 'N/m'), inp('A', 'm')],
        result: res('E', 'J'),
        // E = ½kA² — potential energy at the turning point (v = 0), independent of m
        compute: (v) => mech.springEnergy(v.k, 1, v.A, 0).pe,
    },
    projectile_range: {
        inputs: [inp('v₀', 'm/s', { nonNegative: true }), inp('θ', '°'), inp('h₀', 'm', { nonNegative: true }), pos('g', 'm/s²')],
        result: res('L', 'm'),
        compute: (v) => mech.projectileRange(v['v₀'], toRad(v['θ']), v['h₀'], v.g),
    },
    projectile_max_height: {
        inputs: [inp('v₀', 'm/s', { nonNegative: true }), inp('θ', '°'), inp('h₀', 'm', { nonNegative: true }), pos('g', 'm/s²')],
        result: res('H', 'm'),
        compute: (v) => mech.projectileMaxHeight(v['v₀'], toRad(v['θ']), v['h₀'], v.g),
    },
    projectile_flight_time: {
        inputs: [inp('v₀', 'm/s', { nonNegative: true }), inp('θ', '°'), inp('h₀', 'm', { nonNegative: true }), pos('g', 'm/s²')],
        result: res('t', 's'),
        compute: (v) => mech.projectileFlightTime(v['v₀'], toRad(v['θ']), v['h₀'], v.g),
    },
    momentum_total: {
        inputs: [pos('m₁', 'kg'), inp('v₁', 'm/s'), pos('m₂', 'kg'), inp('v₂', 'm/s')],
        result: res('p', 'kg·m/s'),
        compute: (v) => mech.momentum(v['m₁'], v['v₁']) + mech.momentum(v['m₂'], v['v₂']),
    },
    collision_v1: {
        inputs: [pos('m₁', 'kg'), pos('m₂', 'kg'), inp('u₁', 'm/s'), inp('u₂', 'm/s'), inp('e', '', { nonNegative: true })],
        result: res("v₁'", 'm/s'),
        compute: (v) => (v.e <= 1 ? mech.collisionVelocities(v['m₁'], v['m₂'], v['u₁'], v['u₂'], v.e).v1 : null),
        invalid: "Tiklanish koeffitsienti 0 ≤ e ≤ 1 bo'lishi kerak.",
    },
    collision_v2: {
        inputs: [pos('m₁', 'kg'), pos('m₂', 'kg'), inp('u₁', 'm/s'), inp('u₂', 'm/s'), inp('e', '', { nonNegative: true })],
        result: res("v₂'", 'm/s'),
        compute: (v) => (v.e <= 1 ? mech.collisionVelocities(v['m₁'], v['m₂'], v['u₁'], v['u₂'], v.e).v2 : null),
        invalid: "Tiklanish koeffitsienti 0 ≤ e ≤ 1 bo'lishi kerak.",
    },
    collision_energy_loss: {
        inputs: [pos('m₁', 'kg'), pos('m₂', 'kg'), inp('u₁', 'm/s'), inp('u₂', 'm/s'), inp('e', '', { nonNegative: true })],
        result: res('ΔE', 'J'),
        compute: (v) => (v.e <= 1 ? mech.collisionEnergyLoss(v['m₁'], v['m₂'], v['u₁'], v['u₂'], v.e) : null),
        invalid: "Tiklanish koeffitsienti 0 ≤ e ≤ 1 bo'lishi kerak.",
    },
    centripetal_acceleration: {
        inputs: [pos('r', 'm'), inp('ω', 'rad/s')],
        result: res('a', 'm/s²'),
        compute: (v) => mech.circularMotion(v.r, v['ω'], 1).a,
    },
    linear_speed: {
        inputs: [pos('r', 'm'), inp('ω', 'rad/s')],
        result: res('v', 'm/s'),
        compute: (v) => mech.circularMotion(v.r, v['ω'], 1).v,
    },
    centripetal_force: {
        inputs: [pos('m', 'kg'), pos('r', 'm'), inp('ω', 'rad/s')],
        result: res('F', 'N'),
        compute: (v) => mech.circularMotion(v.r, v['ω'], v.m).F,
    },
    circular_period: {
        inputs: [pos('ω', 'rad/s')],
        result: res('T', 's'),
        compute: (v) => mech.circularMotion(1, v['ω'], 1).T,
    },
    torque: {
        inputs: [inp('F', 'N'), inp('r', 'm', { nonNegative: true }), inp('θ', '°')],
        result: res('M', 'N·m'),
        compute: (v) => mech.torque(v.F, v.r, toRad(v['θ'])),
    },
    rod_inertia: {
        inputs: [pos('m', 'kg'), pos('l', 'm'), inp('d', 'm')],
        result: res('I', 'kg·m²'),
        compute: (v) => mech.rodInertia(v.m, v.l, v.d),
    },
    angular_acceleration: {
        inputs: [inp('M', 'N·m'), pos('I', 'kg·m²')],
        result: res('ε', 'rad/s²'),
        compute: (v) => mech.rotationFromRest(0, v.M, v.I).alpha,
    },
    potential_energy: {
        inputs: [pos('m', 'kg'), pos('g', 'm/s²'), inp('h', 'm')],
        result: res('Eₚ', 'J'),
        compute: (v) => mech.potentialEnergy(v.m, v.g, v.h),
    },
    kinetic_energy: {
        inputs: [pos('m', 'kg'), inp('v', 'm/s')],
        result: res('Eₖ', 'J'),
        compute: (v) => mech.kineticEnergy(v.m, v.v),
    },
    mechanical_energy_landing: {
        inputs: [pos('m', 'kg'), pos('g', 'm/s²'), inp('h', 'm', { nonNegative: true }), inp('k', '', { nonNegative: true })],
        result: res('E', 'J'),
        compute: (v) => (v.k < 1 ? mech.dropWithResistance(Infinity, v.h, v.m, v.g, v.k).mechanical : null),
        invalid: "Qarshilik ulushi 0 ≤ k < 1 bo'lishi kerak.",
    },

    // ─────────────────────────── Termodinamika ───────────────────────────
    linear_expansion: {
        inputs: [inp('α', '1/°C'), pos('L₀', 'm'), inp('ΔT', '°C')],
        result: res('ΔL', 'mm'),
        compute: (v) => thermo.linearExpansion(v['α'], v['L₀'], v['ΔT']) / MM,
    },
    expanded_length: {
        inputs: [inp('α', '1/°C'), pos('L₀', 'm'), inp('ΔT', '°C')],
        result: res('L', 'm'),
        compute: (v) => thermo.expandedLength(v['α'], v['L₀'], v['ΔT']),
    },
    sensible_heat: {
        inputs: [pos('m', 'kg'), pos('c', 'J/(kg·°C)'), inp('ΔT', '°C')],
        result: res('Q', 'J'),
        compute: (v) => thermo.sensibleHeat(v.m, v.c, v['ΔT']),
    },
    specific_heat_calc: {
        inputs: [inp('Q', 'J'), pos('m', 'kg'), inp('ΔT', '°C', { nonZero: true })],
        result: res('c', 'J/(kg·°C)'),
        compute: (v) => thermo.specificHeatFromMeasurement(v.Q, v.m, v['ΔT']),
    },
    heating_time: {
        inputs: [inp('Q', 'J', { nonNegative: true }), pos('P', 'W')],
        result: res('t', 's'),
        compute: (v) => thermo.heatingTime(v.Q, v.P),
    },
    latent_heat: {
        inputs: [pos('m', 'kg'), pos('λ', 'J/kg')],
        result: res('Q', 'J'),
        compute: (v) => thermo.latentHeat(v.m, v['λ']),
    },
    conduction_flux: {
        inputs: [pos('k', 'W/(m·K)'), inp('ΔT', 'K'), pos('d', 'm')],
        result: res('q', 'W/m²'),
        compute: (v) => thermo.conductionHeatFlux(v.k, v['ΔT'], v.d),
    },
    radiation_flux: {
        inputs: [inp('ε', '', { nonNegative: true }), pos('T', 'K'), pos('T₀', 'K')],
        result: res('q', 'W/m²'),
        compute: (v) => (v['ε'] <= 1 ? thermo.radiationNetFlux(v['ε'], v.T, v['T₀']) : null),
        invalid: "Qoralik darajasi 0 ≤ ε ≤ 1 bo'lishi kerak.",
    },
    ideal_gas_volume: {
        inputs: [pos('n', 'mol'), pos('T', 'K'), pos('P', 'atm')],
        result: res('V', 'L'),
        compute: (v) => thermo.idealGasVolume(v.n, v.T, v.P * thermo.ATM) * L_PER_M3,
    },
    ideal_gas_pressure: {
        inputs: [pos('n', 'mol'), pos('T', 'K'), pos('V', 'L')],
        result: res('P', 'atm'),
        compute: (v) => thermo.idealGasPressure(v.n, v.T, v.V / L_PER_M3) / thermo.ATM,
    },
    ideal_gas_temperature: {
        inputs: [pos('P', 'atm'), pos('V', 'L'), pos('n', 'mol')],
        result: res('T', 'K'),
        compute: (v) => thermo.idealGasTemperature(v.P * thermo.ATM, v.V / L_PER_M3, v.n),
    },
    equilibrium_temperature: {
        inputs: [pos('m₁', 'kg'), pos('c₁', 'J/(kg·°C)'), inp('t₁', '°C'), pos('m₂', 'kg'), pos('c₂', 'J/(kg·°C)'), inp('t₂', '°C')],
        result: res('t', '°C'),
        compute: (v) => thermo.equilibriumTemperature([
            { m: v['m₁'], c: v['c₁'], T: v['t₁'] },
            { m: v['m₂'], c: v['c₂'], T: v['t₂'] },
        ]),
    },
    heat_released: {
        inputs: [pos('m', 'kg'), pos('c', 'J/(kg·°C)'), inp('t₁', '°C'), inp('t', '°C')],
        result: res('Q', 'J'),
        compute: (v) => thermo.heatReleased(v.m, v.c, v['t₁'], v.t),
    },
    carnot_efficiency: {
        inputs: [pos('T_H', 'K'), pos('T_C', 'K')],
        result: res('η', '%'),
        compute: (v) => (v.T_C < v.T_H ? thermo.carnotEfficiency(v.T_H, v.T_C) * 100 : null),
        invalid: "Isitgich harorati sovutgichnikidan katta bo'lishi kerak (T_H > T_C).",
    },
    isothermal_heat: {
        inputs: [pos('n', 'mol'), pos('T', 'K'), pos('V₁', 'L'), pos('V₂', 'L')],
        result: res('Q', 'J'),
        compute: (v) => thermo.isothermalWork(v.n, v.T, v['V₁'] / L_PER_M3, v['V₂'] / L_PER_M3),
    },
    carnot_work: {
        inputs: [pos('n', 'mol'), pos('T_H', 'K'), pos('T_C', 'K'), pos('V₁', 'L'), pos('V₂', 'L')],
        result: res('W', 'J'),
        compute: (v) => {
            if (!(v.T_C < v.T_H) || !(v['V₂'] > v['V₁'])) return null;
            const cyc = thermo.carnotCycle({ n: v.n, Th: v.T_H, Tc: v.T_C, V1: v['V₁'] / L_PER_M3, V2: v['V₂'] / L_PER_M3 });
            return cyc ? cyc.W : null;
        },
        invalid: "T_H > T_C va V₂ > V₁ bo'lishi kerak.",
    },
    entropy_isothermal: {
        inputs: [inp('Q', 'J'), pos('T', 'K')],
        result: res('ΔS', 'J/K'),
        compute: (v) => thermo.entropyIsothermal(v.Q, v.T),
    },
    entropy_isobaric: {
        inputs: [pos('n', 'mol'), pos('Cₚ', 'J/(mol·K)'), pos('T₁', 'K'), inp('Q', 'J')],
        result: res('ΔS', 'J/K'),
        compute: (v) => thermo.entropyIsobaricHeating(v.n, v['Cₚ'], v['T₁'], v.Q).dS,
    },
    isobaric_final_temperature: {
        inputs: [pos('n', 'mol'), pos('Cₚ', 'J/(mol·K)'), pos('T₁', 'K'), inp('Q', 'J')],
        result: res('T₂', 'K'),
        compute: (v) => thermo.entropyIsobaricHeating(v.n, v['Cₚ'], v['T₁'], v.Q).T2,
    },
    adiabatic_temperature: {
        inputs: [pos('T₁', 'K'), pos('V₁', 'L'), pos('V₂', 'L'), pos('γ', '')],
        result: res('T₂', 'K'),
        compute: (v) => thermo.adiabaticTemperature(v['T₁'], v['V₁'] / L_PER_M3, v['V₂'] / L_PER_M3, v['γ']),
    },

    // ─────────────────────────────── Elektr ───────────────────────────────
    ohm: {
        inputs: [inp('U', 'V'), pos('R', 'Ω')],
        result: res('I', 'A'),
        compute: (v) => elec.ohmCurrent(v.U, v.R),
    },
    resistance: {
        inputs: [inp('U', 'V'), inp('I', 'A', { nonZero: true })],
        result: res('R', 'Ω'),
        compute: (v) => elec.ohmResistance(v.U, v.I),
    },
    ohm_voltage: {
        inputs: [inp('I', 'A'), inp('R', 'Ω', { nonNegative: true })],
        result: res('U', 'V'),
        compute: (v) => elec.ohmVoltage(v.I, v.R),
    },
    series_resistance: {
        inputs: [inp('R₁', 'Ω', { nonNegative: true }), inp('R₂', 'Ω', { nonNegative: true }), inp('R₃', 'Ω', { nonNegative: true })],
        result: res('R', 'Ω'),
        compute: (v) => elec.seriesResistance([v['R₁'], v['R₂'], v['R₃']]),
    },
    parallel_resistance: {
        inputs: [pos('R₁', 'Ω'), pos('R₂', 'Ω'), pos('R₃', 'Ω')],
        result: res('R', 'Ω'),
        compute: (v) => elec.parallelResistance([v['R₁'], v['R₂'], v['R₃']]),
    },
    parallel_resistance_2: {
        inputs: [pos('R₂', 'Ω'), pos('R₃', 'Ω')],
        result: res('R₂₃', 'Ω'),
        compute: (v) => elec.parallelResistance([v['R₂'], v['R₃']]),
    },
    kirchhoff_i1: {
        inputs: [inp('ℰ', 'V'), pos('R₁', 'Ω'), pos('R₂', 'Ω'), pos('R₃', 'Ω')],
        result: res('I₁', 'A'),
        compute: (v) => elec.solveKirchhoffCircuit(v['ℰ'], v['R₁'], v['R₂'], v['R₃'])?.i1,
    },
    kirchhoff_i2: {
        inputs: [inp('ℰ', 'V'), pos('R₁', 'Ω'), pos('R₂', 'Ω'), pos('R₃', 'Ω')],
        result: res('I₂', 'A'),
        compute: (v) => elec.solveKirchhoffCircuit(v['ℰ'], v['R₁'], v['R₂'], v['R₃'])?.i2,
    },
    kirchhoff_i3: {
        inputs: [inp('ℰ', 'V'), pos('R₁', 'Ω'), pos('R₂', 'Ω'), pos('R₃', 'Ω')],
        result: res('I₃', 'A'),
        compute: (v) => elec.solveKirchhoffCircuit(v['ℰ'], v['R₁'], v['R₂'], v['R₃'])?.i3,
    },
    rc_time_constant: {
        inputs: [pos('R', 'Ω'), pos('C', 'µF')],
        result: res('τ', 'ms'),
        compute: (v) => elec.rcTimeConstant(v.R, v.C * UC) / MM,
    },
    rc_charging_voltage: {
        inputs: [inp('U₀', 'V'), pos('R', 'Ω'), pos('C', 'µF'), inp('t', 'ms', { nonNegative: true })],
        result: res('U_C', 'V'),
        compute: (v) => elec.rcCharging(v['U₀'], v.R, v.C * UC, v.t * MM).vc,
    },
    rc_discharging_voltage: {
        inputs: [inp('U₀', 'V'), pos('R', 'Ω'), pos('C', 'µF'), inp('t', 'ms', { nonNegative: true })],
        result: res('U_C', 'V'),
        compute: (v) => elec.rcDischarging(v['U₀'], v.R, v.C * UC, v.t * MM).vc,
    },
    rc_time_to_fraction: {
        inputs: [pos('τ', 'ms'), pos('p', '%')],
        result: res('t', 'ms'),
        compute: (v) => (v.p < 100 ? elec.rcTimeToFraction(v.p / 100, v['τ']) : null),
        invalid: "Ulush 0 < p < 100 % bo'lishi kerak (100 % ga faqat t → ∞ da erishiladi).",
    },
    capacitor_charge: {
        inputs: [pos('C', 'µF'), inp('U', 'V')],
        result: res('q', 'mC'),
        compute: (v) => elec.capacitorCharge(v.C * UC, v.U) / MM,
    },
    capacitor_energy: {
        inputs: [pos('C', 'µF'), inp('U', 'V')],
        result: res('W', 'mJ'),
        compute: (v) => elec.capacitorEnergy(v.C * UC, v.U) / MM,
    },
    power_ui: {
        inputs: [inp('U', 'V'), inp('I', 'A')],
        result: res('P', 'W'),
        compute: (v) => elec.powerUI(v.U, v.I),
    },
    power_i2r: {
        inputs: [inp('I', 'A'), inp('R', 'Ω', { nonNegative: true })],
        result: res('P', 'W'),
        compute: (v) => elec.powerI2R(v.I, v.R),
    },
    power_u2r: {
        inputs: [inp('U', 'V'), pos('R', 'Ω')],
        result: res('P', 'W'),
        compute: (v) => elec.powerU2R(v.U, v.R),
    },
    energy_kwh: {
        inputs: [inp('P', 'W', { nonNegative: true }), inp('t', 'soat', { nonNegative: true })],
        result: res('W', 'kW·soat'),
        compute: (v) => elec.energyKWh(v.P, v.t),
    },
    energy_cost: {
        inputs: [inp('P', 'W', { nonNegative: true }), inp('t', 'soat', { nonNegative: true }), inp('b', "so'm/(kW·soat)", { nonNegative: true })],
        result: res('S', "so'm"),
        compute: (v) => elec.energyCost(v.P, v.t, v.b),
    },
    coulomb_force: {
        inputs: [inp('q₁', 'µC'), inp('q₂', 'µC'), pos('r', 'm')],
        result: res('F', 'N'),
        compute: (v) => elec.coulombForceMagnitude(v['q₁'] * UC, v['q₂'] * UC, v.r),
    },
    point_charge_field: {
        inputs: [inp('q', 'µC'), pos('r', 'm')],
        result: res('E', 'V/m'),
        compute: (v) => elec.electricField([{ q: v.q * UC, x: 0, y: 0 }], v.r, 0).magnitude,
    },
    two_charge_field: {
        inputs: [inp('q₁', 'µC'), inp('x₁', 'm'), inp('y₁', 'm'), inp('q₂', 'µC'), inp('x₂', 'm'), inp('y₂', 'm'), inp('x', 'm'), inp('y', 'm')],
        result: res('E', 'V/m'),
        compute: (v) => elec.electricField([
            { q: v['q₁'] * UC, x: v['x₁'], y: v['y₁'] },
            { q: v['q₂'] * UC, x: v['x₂'], y: v['y₂'] },
        ], v.x, v.y).magnitude,
        invalid: "Nuqta zaryadning ustida: maydon kuchlanganligi cheksiz.",
    },
    diode_current: {
        inputs: [inp('U', 'V'), pos('R', 'Ω'), inp('U_f', 'V', { nonNegative: true })],
        result: res('I', 'mA'),
        compute: (v) => elec.piecewiseDiodeCurrent(v.U, v.R, v.U_f) / MM,
    },

    // ───────────────────────────── Magnetizm ─────────────────────────────
    wire_field: {
        inputs: [inp('I', 'A'), pos('r', 'm')],
        result: res('B', 'µT'),
        compute: (v) => mag.wireField(v.I, v.r) / UC,
    },
    dipole_axial_field: {
        inputs: [inp('p_m', 'A·m²'), pos('r', 'm')],
        result: res('B', 'mT'),
        compute: (v) => mag.dipoleAxialField(v.p_m, v.r) / MM,
    },
    solenoid_field: {
        inputs: [pos('N', '', { integer: true }), inp('I', 'A'), pos('l', 'm'), pos('μr', '')],
        result: res('B', 'mT'),
        compute: (v) => mag.solenoidField({ turns: v.N, length: v.l, current: v.I, mur: v['μr'] }) / MM,
    },
    faraday_emf: {
        inputs: [pos('N', '', { integer: true }), inp('ΔΦ', 'mWb'), pos('Δt', 's')],
        result: res('ℰ', 'V'),
        compute: (v) => mag.faradayEMF(v.N, (v['ΔΦ'] * MM) / v['Δt']),
    },
    induced_current: {
        inputs: [inp('ℰ', 'V'), pos('R', 'Ω')],
        result: res('I', 'A'),
        compute: (v) => mag.inducedCurrent(v['ℰ'], v.R),
    },
    transformer_voltage: {
        inputs: [inp('U₁', 'V'), pos('N₁', '', { integer: true }), pos('N₂', '', { integer: true })],
        result: res('U₂', 'V'),
        compute: (v) => mag.transformer({ primaryTurns: v['N₁'], secondaryTurns: v['N₂'], primaryVoltage: v['U₁'] })?.secondaryVoltage,
    },
    transformer_secondary_current: {
        inputs: [inp('U₁', 'V'), pos('N₁', '', { integer: true }), pos('N₂', '', { integer: true }), pos('R', 'Ω')],
        result: res('I₂', 'A'),
        compute: (v) => mag.transformer({ primaryTurns: v['N₁'], secondaryTurns: v['N₂'], primaryVoltage: v['U₁'], loadResistance: v.R })?.secondaryCurrent,
    },
    transformer_primary_current: {
        inputs: [inp('U₁', 'V', { nonZero: true }), pos('N₁', '', { integer: true }), pos('N₂', '', { integer: true }), pos('R', 'Ω')],
        result: res('I₁', 'A'),
        compute: (v) => mag.transformer({ primaryTurns: v['N₁'], secondaryTurns: v['N₂'], primaryVoltage: v['U₁'], loadResistance: v.R })?.primaryCurrent,
    },
    generator_peak_emf: {
        inputs: [pos('N', '', { integer: true }), inp('B', 'T'), pos('S', 'm²'), inp('n', 'ayl/min')],
        result: res('ℰ₀', 'V'),
        compute: (v) => mag.generatorPeakEMF({ turns: v.N, field: v.B, area: v.S, omega: mag.angularFrequency(mag.rpmToFrequency(v.n)) }),
    },
    rms_from_peak: {
        inputs: [inp('ℰ₀', 'V')],
        result: res('U_ef', 'V'),
        compute: (v) => mag.rmsFromPeak(v['ℰ₀']),
    },
    rpm_to_frequency: {
        inputs: [inp('n', 'ayl/min', { nonNegative: true })],
        result: res('f', 'Hz'),
        compute: (v) => mag.rpmToFrequency(v.n),
    },
    ampere_force: {
        inputs: [pos('N', '', { integer: true }), inp('B', 'T'), inp('I', 'A'), pos('l', 'm')],
        result: res('F', 'N'),
        compute: (v) => elec.motorSideForce(v.N, v.B, v.I, v.l),
    },
    motor_max_torque: {
        inputs: [pos('N', '', { integer: true }), inp('B', 'T'), inp('I', 'A'), pos('S', 'cm²')],
        result: res('M', 'N·m'),
        compute: (v) => elec.motorTorque(v.N, v.B, v.I, v.S * 1e-4, Math.PI / 2),
    },
    motor_rpm: {
        inputs: [inp('I', 'A', { nonNegative: true }), inp('B', 'T', { nonNegative: true })],
        result: res('n', 'ayl/min'),
        compute: (v) => elec.dcMotorState(v.I, v.B / elec.DC_MOTOR_DEFAULTS.bMax).rpm,
    },

    // ─────────────────────────────── Optika ───────────────────────────────
    reflection_angle: {
        inputs: [inp('α', '°')],
        result: res('γ', '°'),
        compute: (v) => (v['α'] >= 0 && v['α'] <= 90 ? toDeg(optics.reflectionAngle(toRad(v['α']))) : null),
        invalid: "Tushish burchagi 0° … 90° oralig'ida bo'lishi kerak.",
    },
    normal_reflectance: {
        inputs: [pos('n₁', ''), pos('n₂', '')],
        result: res('R', '%'),
        compute: (v) => optics.normalIncidenceReflectance(v['n₁'], v['n₂']) * 100,
    },
    snell_angle: {
        inputs: [pos('n₁', ''), pos('n₂', ''), inp('α', '°')],
        result: res('γ', '°'),
        compute: (v) => {
            const r = optics.snellRefractionAngle(v['n₁'], v['n₂'], toRad(v['α']));
            return r === null ? null : toDeg(r);
        },
        invalid: NO_REFRACTION,
    },
    critical_angle: {
        inputs: [pos('n₁', ''), pos('n₂', '')],
        result: res('α₀', '°'),
        compute: (v) => {
            const r = optics.criticalAngle(v['n₁'], v['n₂']);
            return r === null ? null : toDeg(r);
        },
        invalid: "To'liq ichki qaytish faqat n₁ > n₂ bo'lganda (zichroq muhitdan siyrakroq muhitga o'tishda) mavjud.",
    },
    refractive_index_from_angles: {
        inputs: [pos('n₁', ''), inp('α', '°'), pos('γ', '°')],
        result: res('n₂', ''),
        compute: (v) => optics.refractiveIndexFromAngles(v['n₁'], toRad(v['α']), toRad(v['γ'])),
    },
    cauchy_index: {
        inputs: [pos('λ', 'nm')],
        result: res('n', ''),
        compute: (v) => optics.cauchyIndex(v['λ'] * NM),
    },
    cauchy_dispersion: {
        inputs: [pos('λ₁', 'nm'), pos('λ₂', 'nm')],
        result: res('Δn', ''),
        compute: (v) => optics.cauchyIndex(v['λ₁'] * NM) - optics.cauchyIndex(v['λ₂'] * NM),
    },
    prism_min_deviation: {
        inputs: [pos('n', ''), pos('A', '°')],
        result: res('δ_min', '°'),
        compute: (v) => {
            const r = optics.prismMinimumDeviation(v.n, toRad(v.A));
            return r === null ? null : toDeg(r);
        },
        invalid: "Bu prizmadan nur chiqmaydi: n·sin(A/2) > 1 (ikkinchi yoqda to'liq ichki qaytish).",
    },
    prism_angular_dispersion: {
        inputs: [pos('A', '°'), pos('λ₁', 'nm'), pos('λ₂', 'nm')],
        result: res('Δδ', '°'),
        // Same set-up as the Dispersion simulation: BK7 glass, the beam falls at the
        // minimum-deviation incidence of the sodium line λ = 589.3 nm.
        compute: (v) => {
            const A = toRad(v.A);
            const theta1 = optics.prismMinimumDeviationIncidence(optics.cauchyIndex(589.3 * NM), A);
            if (theta1 === null) return null;
            const d1 = optics.prismDeviation(optics.cauchyIndex(v['λ₁'] * NM), A, theta1);
            const d2 = optics.prismDeviation(optics.cauchyIndex(v['λ₂'] * NM), A, theta1);
            return d1 && d2 ? toDeg(d1.deviation - d2.deviation) : null;
        },
        invalid: "Bu sindirish burchagida nurlardan biri prizmadan chiqmaydi (to'liq ichki qaytish).",
    },
    thin_lens_image: {
        inputs: [inp('F', 'cm', { nonZero: true }), pos('d', 'cm')],
        result: res("f", 'cm'),
        compute: (v) => {
            const im = optics.thinLensImage(v.F * CM, v.d * CM);
            return im.atInfinity ? null : im.imageDistance / CM;
        },
        invalid: AT_FOCUS,
    },
    lens_magnification: {
        inputs: [inp('F', 'cm', { nonZero: true }), pos('d', 'cm')],
        result: res('Γ', ''),
        compute: (v) => {
            const im = optics.thinLensImage(v.F * CM, v.d * CM);
            return im.atInfinity ? null : im.magnification;
        },
        invalid: AT_FOCUS,
    },
    optical_power: {
        inputs: [inp('F', 'cm', { nonZero: true })],
        result: res('D', 'dptr'),
        compute: (v) => optics.opticalPower(v.F * CM),
    },
    mirror_image: {
        inputs: [inp('F', 'cm', { nonZero: true }), pos('d', 'cm')],
        result: res('f', 'cm'),
        compute: (v) => {
            const im = optics.mirrorImage(v.F * CM, v.d * CM);
            return im.atInfinity ? null : im.imageDistance / CM;
        },
        invalid: AT_FOCUS,
    },
    mirror_magnification: {
        inputs: [inp('F', 'cm', { nonZero: true }), pos('d', 'cm')],
        result: res('Γ', ''),
        compute: (v) => {
            const im = optics.mirrorImage(v.F * CM, v.d * CM);
            return im.atInfinity ? null : im.magnification;
        },
        invalid: AT_FOCUS,
    },
    fringe_spacing: {
        inputs: [pos('λ', 'nm'), pos('L', 'm'), pos('d', 'mm')],
        result: res('Δy', 'mm'),
        compute: (v) => optics.fringeSpacing(v['λ'] * NM, v.L, v.d * MM) / MM,
    },
    wavelength_from_fringes: {
        inputs: [pos('Δy', 'mm'), pos('L', 'm'), pos('d', 'mm')],
        result: res('λ', 'nm'),
        compute: (v) => optics.wavelengthFromFringeSpacing(v['Δy'] * MM, v.L, v.d * MM) / NM,
    },
    single_slit_minimum: {
        inputs: [inp('m', '', { integer: true, nonZero: true }), pos('λ', 'nm'), pos('a', 'mm')],
        result: res('φ', '°'),
        compute: (v) => {
            const r = optics.singleSlitMinimumAngle(v.m, v.a * MM, v['λ'] * NM);
            return r === null ? null : toDeg(r);
        },
        invalid: "Bu tartibli minimum mavjud emas: |mλ/a| > 1.",
    },
    central_maximum_width: {
        inputs: [pos('λ', 'nm'), pos('a', 'mm'), pos('L', 'm')],
        result: res('b', 'mm'),
        compute: (v) => {
            const r = optics.singleSlitMinimumAngle(1, v.a * MM, v['λ'] * NM);
            return r === null || r >= Math.PI / 2 ? null : (2 * v.L * Math.tan(r)) / MM;
        },
        invalid: "Tirqish to'lqin uzunligidan tor: birinchi minimum mavjud emas.",
    },
    brewster_angle: {
        inputs: [pos('n₁', ''), pos('n₂', '')],
        result: res('α_B', '°'),
        compute: (v) => toDeg(optics.brewsterAngle(v['n₁'], v['n₂'])),
    },
    malus_law: {
        inputs: [inp('I₀', '', { nonNegative: true }), inp('φ', '°')],
        result: res('I', ''),
        compute: (v) => optics.malusLaw(v['I₀'], toRad(v['φ'])),
    },
    reflection_polarization_degree: {
        inputs: [pos('n₁', ''), pos('n₂', ''), inp('α', '°')],
        result: res('P', '%'),
        compute: (v) => {
            const f = optics.fresnelReflectance(v['n₁'], v['n₂'], toRad(v['α']));
            return optics.degreeOfPolarization(f.Rs, f.Rp) * 100;
        },
    },
    analyzer_transmission: {
        inputs: [pos('n₁', ''), pos('n₂', ''), inp('α', '°'), inp('φ', '°')],
        result: res('I/I₀', '%'),
        // unpolarised light: each polarisation carries I₀/2 before reflection
        compute: (v) => {
            const f = optics.fresnelReflectance(v['n₁'], v['n₂'], toRad(v['α']));
            return optics.analyzerIntensity(f.Rs / 2, f.Rp / 2, toRad(v['φ'])) * 100;
        },
    },

    // ───────────────────────────── To'lqinlar ─────────────────────────────
    wave_speed: {
        inputs: [pos('λ', 'm'), pos('ν', 'Hz')],
        result: res('v', 'm/s'),
        compute: (v) => waves.waveSpeed(v['ν'], v['λ']),
    },
    wavelength_from_speed: {
        inputs: [pos('v', 'm/s'), pos('ν', 'Hz')],
        result: res('λ', 'm'),
        compute: (v) => waves.wavelengthFrom(v.v, v['ν']),
    },
    wave_period: {
        inputs: [pos('ν', 'Hz')],
        result: res('T', 's'),
        compute: (v) => waves.period(v['ν']),
    },
    harmonic_frequency: {
        inputs: [int('n'), pos('v', 'm/s'), pos('L', 'm')],
        result: res('νₙ', 'Hz'),
        compute: (v) => waves.stringHarmonicFrequency(v.n, v.L, v.v),
    },
    standing_wavelength: {
        inputs: [int('n'), pos('L', 'm')],
        result: res('λₙ', 'm'),
        compute: (v) => waves.standingWaveWavelength(v.n, v.L),
    },
    natural_frequency: {
        inputs: [pos('k', 'N/m'), pos('m', 'kg')],
        result: res('ν₀', 'Hz'),
        compute: (v) => mech.springOmega(v.k, v.m) / (2 * Math.PI),
    },
    resonance_amplitude: {
        inputs: [inp('ν', 'Hz', { nonNegative: true }), pos('ν₀', 'Hz'), pos('ζ', '')],
        result: res('A/Aₛₜ', ''),
        compute: (v) => waves.normalizedResponse(v['ν'] / v['ν₀'], v['ζ']),
    },
    quality_factor: {
        inputs: [pos('ζ', '')],
        result: res('Q', ''),
        compute: (v) => waves.qualityFactor(v['ζ']),
    },
    resonance_peak_frequency: {
        inputs: [pos('ν₀', 'Hz'), pos('ζ', '')],
        result: res('ν_rez', 'Hz'),
        compute: (v) => waves.resonancePeakFrequency(v['ν₀'], v['ζ']),
        invalid: "ζ ≥ 1/√2 da amplituda-chastota egri chizig'ida cho'qqi yo'q.",
    },
    doppler: {
        inputs: [pos('ν', 'Hz'), pos('v', 'm/s'), inp('v_k', 'm/s'), inp('v_m', 'm/s')],
        result: res("ν'", 'Hz'),
        compute: (v) => waves.dopplerFrequency(v['ν'], v.v, v.v_k, v.v_m),
        invalid: "Manba tovush tezligidan sekin harakatlanishi (v_m < v) va kuzatuvchi to'lqindan qochib ketmasligi kerak.",
    },
    nodal_lines: {
        inputs: [inp('d', 'px', { nonNegative: true }), pos('λ', 'px')],
        result: res('N', ''),
        compute: (v) => waves.nodalLineCount(v.d, v['λ']),
    },
    em_frequency: {
        inputs: [pos('λ', 'nm')],
        result: res('ν', 'Hz'),
        compute: (v) => waves.emFrequency(v['λ'] * NM),
    },
    photon_energy_ev: {
        inputs: [pos('λ', 'nm')],
        result: res('E', 'eV'),
        compute: (v) => waves.photonEnergyEV(v['λ'] * NM),
    },
    beat_frequency: {
        inputs: [pos('ν₁', 'Hz'), pos('ν₂', 'Hz')],
        result: res('ν_u', 'Hz'),
        compute: (v) => waves.beatFrequency(v['ν₁'], v['ν₂']),
    },
    beat_period: {
        inputs: [pos('ν₁', 'Hz'), pos('ν₂', 'Hz')],
        result: res('T_u', 's'),
        compute: (v) => waves.beatPeriod(v['ν₁'], v['ν₂']),
        invalid: "Chastotalar teng: urilish yo'q (T_u → ∞).",
    },

    // ──────────────────────────── Atom fizikasi ────────────────────────────
    photon_energy_freq: {
        inputs: [pos('ν', 'THz')],
        result: res('E', 'eV'),
        compute: (v) => atomic.photonEnergyEV(v['ν'] * 1e12),
    },
    photoelectric_ke: {
        inputs: [pos('ν', 'THz'), pos('W', 'eV')],
        result: res('Eₖ_max', 'eV'),
        compute: (v) => {
            const pe = atomic.photoelectric({ frequency: v['ν'] * 1e12, workFunctionEV: v.W });
            return pe.emits
                ? pe.kineticEnergyMaxEV
                : { value: 0, note: "hν ≤ W: fotoeffekt yuz bermaydi, elektronlar chiqmaydi." };
        },
    },
    threshold_frequency: {
        inputs: [pos('W', 'eV')],
        result: res('ν₀', 'THz'),
        // ν₀ = W/h (the photon frequency passed in does not enter the threshold)
        compute: (v) => atomic.photoelectric({ frequency: 1, workFunctionEV: v.W }).thresholdFrequency / 1e12,
    },
    stopping_voltage: {
        inputs: [pos('ν', 'THz'), pos('W', 'eV')],
        result: res('U₀', 'V'),
        compute: (v) => {
            const pe = atomic.photoelectric({ frequency: v['ν'] * 1e12, workFunctionEV: v.W });
            return pe.emits
                ? pe.stoppingVoltage
                : { value: 0, note: "hν ≤ W: fotoeffekt yuz bermaydi, tormozlovchi kuchlanish kerak emas." };
        },
    },
    bohr_energy: {
        inputs: [int('n')],
        result: res('Eₙ', 'eV'),
        compute: (v) => atomic.bohrEnergyEV(v.n),
    },
    bohr_radius: {
        inputs: [int('n')],
        result: res('rₙ', 'Å'),
        compute: (v) => atomic.bohrRadius(v.n) * 1e10,
    },
    rydberg_wavelength: {
        inputs: [int('n₁'), int('n₂')],
        result: res('λ', 'nm'),
        compute: (v) => atomic.rydbergWavelength(v['n₁'], v['n₂']) / NM,
        invalid: "Yuqori sath quyi sathdan katta bo'lishi kerak (n₂ > n₁).",
    },
    transition_energy: {
        inputs: [int('n₁'), int('n₂')],
        result: res('ΔE', 'eV'),
        compute: (v) => (v['n₂'] > v['n₁'] ? atomic.transitionPhoton(v['n₂'], v['n₁'])?.energyEV : null),
        invalid: "Yuqori sath quyi sathdan katta bo'lishi kerak (n₂ > n₁).",
    },
    remaining_nuclei: {
        inputs: [inp('N₀', '', { nonNegative: true }), inp('t', 's', { nonNegative: true }), pos('T½', 's')],
        result: res('N', ''),
        compute: (v) => atomic.remainingNuclei(v['N₀'], v.t, v['T½']),
    },
    decay_constant: {
        inputs: [pos('T½', 's')],
        result: res('λ', '1/s'),
        compute: (v) => atomic.decayConstant(v['T½']),
    },
    activity: {
        inputs: [inp('N₀', '', { nonNegative: true }), inp('t', 's', { nonNegative: true }), pos('T½', 's')],
        result: res('A', 'Bq'),
        compute: (v) => atomic.activity(v['N₀'], v.t, v['T½']),
    },
    mass_defect_energy: {
        inputs: [inp('Δm', 'u')],
        result: res('E', 'MeV'),
        compute: (v) => atomic.massDefectEnergyMeV(v['Δm']),
    },
    fission_energy_mass: {
        inputs: [inp('m', 'g', { nonNegative: true }), pos('E₁', 'MeV')],
        result: res('E', 'J'),
        compute: (v) => atomic.fissionEnergyForMass(v.m / 1000, v['E₁']),
    },
    bragg_angle: {
        inputs: [int('n'), pos('λ', 'nm'), pos('d', 'nm')],
        result: res('θ', '°'),
        compute: (v) => {
            const r = atomic.braggAngle(v.n, v['λ'] * NM, v.d * NM);
            return r === null ? null : toDeg(r);
        },
        invalid: "Bu tartibli maksimum mavjud emas: nλ > 2d.",
    },
    bragg_spacing: {
        inputs: [int('n'), pos('λ', 'nm'), pos('θ', '°')],
        result: res('d', 'nm'),
        compute: (v) => atomic.braggSpacing(v.n, v['λ'] * NM, toRad(v['θ'])) / NM,
    },
});
/* eslint-enable quote-props */

/** Ids the calculator can evaluate. */
export const CALCULATOR_IDS = Object.freeze(Object.keys(CALCULATORS));

/** True when `id` has a calculator. */
export function hasCalculator(id) {
    return Object.prototype.hasOwnProperty.call(CALCULATORS, id);
}

/** Input descriptors (symbol, unit, constraints) of a formula, or [] for an unknown id. */
export function calculatorInputs(id) {
    return hasCalculator(id) ? CALCULATORS[id].inputs : [];
}

/** Result descriptor {symbol, unit} of a formula, or null. */
export function calculatorResult(id) {
    return hasCalculator(id) ? CALCULATORS[id].result : null;
}

/**
 * Parse one user input (number, or a string with "." or "," as decimal separator).
 * @returns {number|null} null when empty / not a number
 */
export function parseInput(raw) {
    if (typeof raw === 'number') return Number.isFinite(raw) ? raw : null;
    if (typeof raw !== 'string') return null;
    const s = raw.trim().replace(',', '.');
    if (s === '') return null;
    const n = Number(s);
    return Number.isFinite(n) ? n : null;
}

const fail = (error) => ({ ok: false, error });

/**
 * Evaluate formula `id` with the student's inputs (keyed by variable symbol,
 * in the units declared in CALCULATORS[id].inputs).
 * @param {string} id formula id
 * @param {Record<string, number|string>} rawInputs
 * @returns {{ok:true, value:number, symbol:string, unit:string, note?:string} | {ok:false, error:string}}
 */
export function calculate(id, rawInputs = {}) {
    if (!hasCalculator(id)) {
        return fail(`"${id}" formulasi uchun kalkulyator mavjud emas.`);
    }
    const def = CALCULATORS[id];
    const values = {};
    for (const spec of def.inputs) {
        const x = parseInput(rawInputs?.[spec.symbol]);
        const label = spec.unit ? `${spec.symbol} (${spec.unit})` : spec.symbol;
        if (x === null) return fail(`${label} qiymatini son ko'rinishida kiriting.`);
        if (spec.positive && !(x > 0)) return fail(`${label} musbat son bo'lishi kerak.`);
        if (spec.nonNegative && x < 0) return fail(`${label} manfiy bo'lishi mumkin emas.`);
        if (spec.nonZero && x === 0) return fail(`${label} noldan farqli bo'lishi kerak.`);
        if (spec.integer && !Number.isInteger(x)) return fail(`${label} butun son bo'lishi kerak.`);
        values[spec.symbol] = x;
    }

    let out;
    try {
        out = def.compute(values);
    } catch (e) {
        out = null;
    }

    const note = out && typeof out === 'object' ? out.note : undefined;
    const value = finite(out && typeof out === 'object' ? out.value : out);
    if (value === null) {
        return fail(def.invalid ?? "Bu qiymatlar uchun natija mavjud emas — kiritilgan ma'lumotlarni tekshiring.");
    }
    const result = { ok: true, value: value === 0 ? 0 : value, symbol: def.result.symbol, unit: def.result.unit };
    if (note) result.note = note;
    return result;
}

/**
 * Human-readable number: up to 5 significant digits, scientific notation for very
 * large / small magnitudes (e.g. 5.4508e+14).
 * @param {number} value
 * @returns {string}
 */
export function formatResult(value) {
    if (typeof value !== 'number' || !Number.isFinite(value)) return '—';
    if (value === 0) return '0';
    const abs = Math.abs(value);
    if (abs >= 1e6 || abs < 1e-3) return value.toExponential(4);
    return String(Number(value.toPrecision(5)));
}
