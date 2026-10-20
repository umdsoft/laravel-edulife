/**
 * Physical constants of the virtual lab — one source of truth.
 *
 * Source: CODATA 2022 recommended values, NIST
 *   https://physics.nist.gov/cuu/Constants/Table/allascii.txt
 * The seven SI defining constants (Δν_Cs, c, h, e, k, N_A, K_cd) are exact since
 * the 2019 SI redefinition; constants derived only from them (R, σ, b, eV …) are
 * exact too. Measured constants carry the CODATA 2022 value (relative standard
 * uncertainty in the comment).
 *
 * Every module of ./physics imports from here; docs/lab/PHYSICS_REFERENCES.md
 * lists the same table with the source URLs.
 */

// ── SI defining constants (exact) ──
/** Speed of light in vacuum, m/s (exact). */
export const SPEED_OF_LIGHT = 299792458;
/** Planck constant, J·s (exact). */
export const PLANCK = 6.62607015e-34;
/** Elementary charge, C (exact). */
export const ELEMENTARY_CHARGE = 1.602176634e-19;
/** Boltzmann constant, J/K (exact). */
export const BOLTZMANN = 1.380649e-23;
/** Avogadro constant, 1/mol (exact). */
export const AVOGADRO = 6.02214076e23;

// ── exact derived constants ──
/** Molar gas constant R = N_A·k, J/(mol·K) (exact: 8.314 462 618…). */
export const GAS_CONSTANT = AVOGADRO * BOLTZMANN;
/** Stefan–Boltzmann constant σ = 2π⁵k⁴/(15h³c²), W/(m²·K⁴) (exact: 5.670 374 419…e−8). */
export const STEFAN_BOLTZMANN = (2 * Math.PI ** 5 * BOLTZMANN ** 4) / (15 * PLANCK ** 3 * SPEED_OF_LIGHT ** 2);
/** Planck constant in eV·s. */
export const PLANCK_EV = PLANCK / ELEMENTARY_CHARGE;
/** hc in eV·nm (1239.841 98…). */
export const HC_EV_NM = ((PLANCK * SPEED_OF_LIGHT) / ELEMENTARY_CHARGE) * 1e9;
/** Faraday constant F = N_A·e, C/mol (exact: 96 485.332 12…). */
export const FARADAY = AVOGADRO * ELEMENTARY_CHARGE;

// ── measured constants (CODATA 2022) ──
/** Vacuum electric permittivity ε₀, F/m (u_r = 1.6e−10). */
export const EPSILON_0 = 8.8541878188e-12;
/** Vacuum magnetic permeability μ₀, N/A² (u_r = 1.6e−10; ≈ 4π·10⁻⁷ to 1e−9). */
export const MU_0 = 1.25663706127e-6;
/** Coulomb constant k = 1/(4πε₀), N·m²/C² (8.987 551 7862e9). */
export const K_COULOMB = 1 / (4 * Math.PI * EPSILON_0);
/** Electron mass, kg (u_r = 3.1e−10). */
export const ELECTRON_MASS = 9.1093837139e-31;
/** Proton mass, kg (u_r = 3.1e−10). */
export const PROTON_MASS = 1.67262192595e-27;
/** Neutron mass, kg (u_r = 5.1e−10). */
export const NEUTRON_MASS = 1.67492750056e-27;
/** Proton–electron mass ratio. */
export const PROTON_ELECTRON_MASS_RATIO = 1836.152673426;
/** Bohr radius a₀, m (u_r = 1.6e−10). */
export const BOHR_RADIUS = 5.29177210544e-11;
/** Fine-structure constant α (u_r = 1.6e−10). */
export const FINE_STRUCTURE = 7.2973525643e-3;
/** Rydberg constant R∞ (infinite nuclear mass), 1/m (u_r = 1.1e−12). */
export const RYDBERG_INFINITY = 10973731.568157;
/** Rydberg energy R∞hc, eV (13.605 693 122 990). */
export const RYDBERG_ENERGY_INFINITY_EV = 13.60569312299;
/** Hydrogen Rydberg constant R_H = R∞/(1 + mₑ/mₚ), 1/m (reduced-mass correction) ≈ 1.096 775 8e7. */
export const RYDBERG_HYDROGEN = RYDBERG_INFINITY / (1 + 1 / PROTON_ELECTRON_MASS_RATIO);
/** Hydrogen ionisation energy in the Bohr model with reduced mass, eV ≈ 13.598 4. */
export const RYDBERG_ENERGY_HYDROGEN_EV = RYDBERG_ENERGY_INFINITY_EV / (1 + 1 / PROTON_ELECTRON_MASS_RATIO);
/** Atomic mass constant energy equivalent, MeV per u (u_r = 3.1e−10). */
export const AMU_MEV = 931.49410372;
/** Atomic mass constant, kg. */
export const AMU_KG = 1.66053906892e-27;
/** Newtonian constant of gravitation, m³/(kg·s²) (u_r = 2.2e−5). */
export const GRAVITATIONAL_CONSTANT = 6.6743e-11;

// ── conventional values ──
/** Standard acceleration of gravity g_n, m/s² (exact by convention, CGPM 1901). */
export const STANDARD_GRAVITY = 9.80665;
/** Standard atmosphere, Pa (exact). */
export const STANDARD_ATMOSPHERE = 101325;
/** 0 °C in kelvin (exact). */
export const ZERO_CELSIUS = 273.15;

/**
 * g used by the lab tasks: 9.81 m/s² — the value of the Uzbek school physics
 * textbooks and problem books. It differs from the local value in Tashkent
 * (gravityAt(41.30°, 455 m) ≈ 9.8015 m/s²) by 0.09 %, far below every task
 * tolerance and below the resolution of the school instruments.
 */
export const LAB_GRAVITY = 9.81;

/**
 * Normal gravity on the WGS 84 ellipsoid (Somigliana closed formula) with the
 * GRS-67 free-air height correction:
 *   g(φ) = 9.780 325 3359·(1 + 0.001 931 852 652 41 sin²φ)/√(1 − 0.006 694 379 990 13 sin²φ)
 *   g(φ, h) = g(φ) − (3.0877e−6 − 4.3e−9 sin²φ)·h + 7.2e−13·h²
 * Source: NIMA TR8350.2 (WGS 84), https://en.wikipedia.org/wiki/Theoretical_gravity
 * @param {number} latitudeDeg geodetic latitude, degrees
 * @param {number} [heightM=0] height above the ellipsoid, m
 * @returns {number} m/s²
 */
export function gravityAt(latitudeDeg, heightM = 0) {
    const s2 = Math.sin((latitudeDeg * Math.PI) / 180) ** 2;
    const g0 = (9.7803253359 * (1 + 0.00193185265241 * s2)) / Math.sqrt(1 - 0.00669437999013 * s2);
    return g0 - (3.0877e-6 - 4.3e-9 * s2) * heightM + 7.2e-13 * heightM * heightM;
}

/** Tashkent: 41.30° N, 455 m above sea level → 9.8015 m/s². */
export const TASHKENT = Object.freeze({ latitude: 41.2995, longitude: 69.2401, altitude: 455 });
export const G_TASHKENT = gravityAt(TASHKENT.latitude, TASHKENT.altitude);
