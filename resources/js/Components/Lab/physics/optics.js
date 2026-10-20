/**
 * Pure geometrical- and wave-optics physics for the virtual lab simulations.
 *
 * Conventions
 *  - SI units in and out: lengths and wavelengths in metres, angles in radians,
 *    intensities in the caller's unit (only ratios matter).
 *  - Angles of incidence / reflection / refraction are measured from the
 *    surface NORMAL (0 = normal incidence, π/2 = grazing).
 *  - Thin lenses and spherical mirrors use ONE sign convention, the
 *    "real-is-positive" (Gaussian) convention of the school textbooks
 *    (1/F = 1/d + 1/f):
 *        d  > 0  real object (on the side the light comes from)
 *        d' > 0  real image  (lens: on the outgoing side; mirror: in front of it)
 *        d' < 0  virtual image
 *        F  > 0  converging element (convex lens, concave mirror)
 *        F  < 0  diverging element  (concave lens, convex mirror)
 *        m = −d'/d   (m < 0 inverted, m > 0 upright), h' = m·h
 *    Object at the focus (d = F) gives d' = ±∞ (parallel outgoing rays).
 *  - No function touches the DOM, Vue or the clock: everything is
 *    deterministic and unit-tested (see ./__tests__/optics.test.js).
 */

import { INSTRUMENTS, dmmResolution, dmmReading, readingSigma, formatReading } from './instruments.js';

/** Degrees → radians factor. */
export const DEG = Math.PI / 180;

/** @param {number} deg angle in degrees @returns {number} radians */
export const toRad = (deg) => deg * DEG;

/** @param {number} rad angle in radians @returns {number} degrees */
export const toDeg = (rad) => rad / DEG;

/** nm → m */
export const NM = 1e-9;
/** mm → m */
export const MM = 1e-3;
/** cm → m */
export const CM = 1e-2;

/** Relative tolerance used to detect exact geometric degeneracies (d = F, sin = 1 …). */
const EPS = 1e-12;

const isNum = (x) => typeof x === 'number' && Number.isFinite(x);

function requireFinite(name, v) {
    if (!isNum(v)) throw new TypeError(`${name} must be a finite number, got ${v}`);
}

function requirePositive(name, v) {
    requireFinite(name, v);
    if (v <= 0) throw new RangeError(`${name} must be > 0, got ${v}`);
}

/**
 * asin that tolerates round-off just outside [−1, 1] and returns null when the
 * argument is genuinely out of range (no real angle exists).
 * @param {number} s
 * @returns {number|null}
 */
function asinOrNull(s) {
    if (s > 1) return s <= 1 + EPS ? Math.PI / 2 : null;
    if (s < -1) return s >= -1 - EPS ? -Math.PI / 2 : null;
    return Math.asin(s);
}

/**
 * Normalise a 2-D vector.
 * @param {number} x @param {number} y
 * @returns {[number, number]}
 */
export function normalize(x, y) {
    const len = Math.hypot(x, y);
    if (len === 0) throw new RangeError('cannot normalise a zero vector');
    return [x / len, y / len];
}

// ─────────────────────────────────────────────────────────────────────────────
// Reflection & refraction at a plane boundary
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Law of reflection: θr = θi (both from the normal, in the plane of incidence).
 * @param {number} thetaI incidence angle, rad, 0 ≤ θi ≤ π/2
 * @returns {number} reflection angle, rad
 */
export function reflectionAngle(thetaI) {
    requireFinite('thetaI', thetaI);
    if (thetaI < 0 || thetaI > Math.PI / 2) throw new RangeError('thetaI must be in [0, π/2]');
    return thetaI;
}

/**
 * Snell's law n1·sinθ1 = n2·sinθ2.
 * @param {number} n1 refractive index of the incident medium (> 0)
 * @param {number} n2 refractive index of the second medium (> 0)
 * @param {number} theta1 incidence angle from the normal, rad
 * @returns {number|null} refraction angle θ2 in rad, or null on total internal
 *   reflection (n1·sinθ1/n2 > 1). At exactly the critical angle θ2 = π/2.
 */
export function snellRefractionAngle(n1, n2, theta1) {
    requirePositive('n1', n1);
    requirePositive('n2', n2);
    requireFinite('theta1', theta1);
    return asinOrNull((n1 * Math.sin(theta1)) / n2);
}

/**
 * Refractive index of the second medium from measured angles (Snell's law solved
 * for n2): n2 = n1·sin θ1 / sin θ2.
 * @param {number} n1 index of the incident medium (> 0)
 * @param {number} theta1 incidence angle, rad
 * @param {number} theta2 refraction angle, rad (0 < θ2 ≤ π/2)
 * @returns {number} n2
 */
export function refractiveIndexFromAngles(n1, theta1, theta2) {
    requirePositive('n1', n1);
    requireFinite('theta1', theta1);
    requireFinite('theta2', theta2);
    const s2 = Math.sin(theta2);
    if (!(s2 > 0)) throw new RangeError('theta2 must be in (0, π/2]');
    return (n1 * Math.sin(theta1)) / s2;
}

/**
 * Critical angle for total internal reflection: θc = arcsin(n2/n1), only when n1 > n2.
 * @param {number} n1 index of the medium the light is in
 * @param {number} n2 index of the medium beyond the boundary
 * @returns {number|null} θc in rad, or null if n1 ≤ n2 (TIR impossible)
 */
export function criticalAngle(n1, n2) {
    requirePositive('n1', n1);
    requirePositive('n2', n2);
    if (n1 <= n2) return null;
    return Math.asin(n2 / n1);
}

/**
 * @param {number} n1 @param {number} n2 @param {number} theta1 rad
 * @returns {boolean} true when the light is totally internally reflected
 */
export function isTotalInternalReflection(n1, n2, theta1) {
    return snellRefractionAngle(n1, n2, theta1) === null;
}

/**
 * Fresnel power reflectances for a dielectric boundary (non-magnetic media).
 *   rs = (n1 cosθi − n2 cosθt)/(n1 cosθi + n2 cosθt)
 *   rp = (n2 cosθi − n1 cosθt)/(n2 cosθi + n1 cosθt)
 *   Rs = rs², Rp = rp², R(unpolarised) = (Rs + Rp)/2, T = 1 − R.
 * Under total internal reflection Rs = Rp = 1.
 * @param {number} n1 @param {number} n2 @param {number} thetaI incidence angle, rad
 * @returns {{Rs:number, Rp:number, R:number, T:number, totalInternalReflection:boolean}}
 */
export function fresnelReflectance(n1, n2, thetaI) {
    requirePositive('n1', n1);
    requirePositive('n2', n2);
    requireFinite('thetaI', thetaI);
    const st = (n1 * Math.sin(thetaI)) / n2;
    if (Math.abs(st) >= 1) {
        return { Rs: 1, Rp: 1, R: 1, T: 0, totalInternalReflection: Math.abs(st) > 1 + EPS };
    }
    const ci = Math.cos(thetaI);
    const ct = Math.sqrt(1 - st * st);
    const rs = (n1 * ci - n2 * ct) / (n1 * ci + n2 * ct);
    const rp = (n2 * ci - n1 * ct) / (n2 * ci + n1 * ct);
    const Rs = rs * rs;
    const Rp = rp * rp;
    const R = (Rs + Rp) / 2;
    return { Rs, Rp, R, T: 1 - R, totalInternalReflection: false };
}

/**
 * Normal-incidence reflectance R = ((n1 − n2)/(n1 + n2))².
 * @param {number} n1 @param {number} n2
 * @returns {number}
 */
export function normalIncidenceReflectance(n1, n2) {
    requirePositive('n1', n1);
    requirePositive('n2', n2);
    return ((n1 - n2) / (n1 + n2)) ** 2;
}

/**
 * Brewster angle tanθB = n2/n1 (reflected light is purely s-polarised, Rp = 0).
 * At θB the reflected and refracted rays are perpendicular: θB + θt = π/2.
 * @param {number} n1 @param {number} n2
 * @returns {number} θB in rad
 */
export function brewsterAngle(n1, n2) {
    requirePositive('n1', n1);
    requirePositive('n2', n2);
    return Math.atan(n2 / n1);
}

/**
 * Vector form of Snell's law.
 *   η = n1/n2, cosθi = −N·D, k = 1 − η²(1 − cos²θi)
 *   T = η·D + (η·cosθi − √k)·N
 * @param {[number, number]} d unit direction of the incident ray
 * @param {[number, number]} n unit surface normal pointing back into the
 *   incident medium (so that n·d < 0)
 * @param {number} n1 index of the incident medium
 * @param {number} n2 index of the transmitting medium
 * @returns {[number, number]|null} unit refracted direction, or null on TIR
 */
export function refractDirection(d, n, n1, n2) {
    requirePositive('n1', n1);
    requirePositive('n2', n2);
    const eta = n1 / n2;
    const cosI = -(n[0] * d[0] + n[1] * d[1]);
    if (cosI < 0) throw new RangeError('normal must point towards the incident side (n·d < 0)');
    const k = 1 - eta * eta * (1 - cosI * cosI);
    if (k < -EPS) return null;
    const root = Math.sqrt(Math.max(0, k));
    return normalize(eta * d[0] + (eta * cosI - root) * n[0], eta * d[1] + (eta * cosI - root) * n[1]);
}

/**
 * Vector form of the law of reflection R = D − 2(D·N)N.
 * @param {[number, number]} d unit incident direction
 * @param {[number, number]} n unit surface normal (either orientation)
 * @returns {[number, number]}
 */
export function reflectDirection(d, n) {
    const dot = d[0] * n[0] + d[1] * n[1];
    return [d[0] - 2 * dot * n[0], d[1] - 2 * dot * n[1]];
}

// ─────────────────────────────────────────────────────────────────────────────
// Dispersion & prisms
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Two-term Cauchy fit of BK7 crown glass (textbook values, e.g.
 * https://en.wikipedia.org/wiki/Cauchy%27s_equation: A = 1.5046, B = 0.00420 µm²).
 * B is given in m² (0.00420 µm² = 4.20·10⁻¹⁵ m²) so that λ can stay in metres.
 * Over 400–700 nm it reproduces the Schott Sellmeier data within 1.2·10⁻⁴
 * (it is 1.1·10⁻⁴ too high at 700 nm), see {@link bk7Index}.
 */
export const BK7_CAUCHY = Object.freeze({ A: 1.5046, B: 4.2e-15 });

/**
 * Sellmeier constants of SCHOTT N-BK7 (data sheet, λ in µm, C in µm²):
 *   n²(λ) − 1 = Σ Bᵢλ²/(λ² − Cᵢ)
 * Source: SCHOTT Optical Glass data sheets, N-BK7 (nd = 1.51680, νd = 64.17),
 * https://media.schott.com/api/public/content/38cbbe876d324e03b1881d33f3d26635
 * Gives n(404.7) = 1.53024, n(486.1) = 1.52238, n(587.6) = 1.51680,
 * n(589.3) = 1.51673, n(656.3) = 1.51432, n(706.5) = 1.51289 — the catalogue
 * table values to all printed digits.
 */
export const BK7_SELLMEIER = Object.freeze({
    B: Object.freeze([1.03961212, 0.231792344, 1.01046945]),
    C: Object.freeze([0.00600069867, 0.0200179144, 103.560653]), // µm²
});

/**
 * Sellmeier dispersion formula n(λ) = √(1 + Σ Bᵢλ²/(λ² − Cᵢ)), λ converted to µm.
 * @param {number} wavelength vacuum wavelength, m
 * @param {{B:number[], C:number[]}} [coeffs=BK7_SELLMEIER] C in µm²
 * @returns {number} refractive index
 */
export function sellmeierIndex(wavelength, coeffs = BK7_SELLMEIER) {
    requirePositive('wavelength', wavelength);
    const l2 = (wavelength * 1e6) ** 2; // µm²
    let s = 1;
    for (let i = 0; i < coeffs.B.length; i++) s += (coeffs.B[i] * l2) / (l2 - coeffs.C[i]);
    if (!(s > 0)) throw new RangeError('wavelength outside the validity range of the Sellmeier fit');
    return Math.sqrt(s);
}

/**
 * Refractive index of SCHOTT N-BK7 crown glass (Sellmeier, data sheet constants).
 * This is the "real glass" of the Dispersion simulation.
 * @param {number} wavelength vacuum wavelength, m
 * @returns {number}
 */
export function bk7Index(wavelength) {
    return sellmeierIndex(wavelength, BK7_SELLMEIER);
}

/**
 * Refractive indices (sodium D line, ≈ 589 nm, 20 °C) of the media used by the
 * simulations and the theory texts. Sources (see the optics docs section):
 *  - air 1.000 293: https://en.wikipedia.org/wiki/List_of_refractive_indices
 *  - water 1.333 (IAPWS R9-97: 1.333 0 at 589.3 nm, 20 °C, 0.1 MPa),
 *    https://iapws.org/relguide/Rindex.html
 *  - acrylic (PMMA) 1.49, diamond 2.417: List of refractive indices (above)
 *  - N-BK7 1.516 73 (589.3 nm) and F2 flint 1.620 04 (nd): SCHOTT data sheets
 *  - "glass" 1.5: the rounded school-textbook value used by the tasks
 */
export const REFRACTIVE_INDEX = Object.freeze({
    air: 1.000293,
    water: 1.333,
    acrylic: 1.49,
    glass: 1.5,
    bk7: 1.51673,
    flintF2: 1.62004,
    diamond: 2.417,
});

/** Laboratory light sources, vacuum/air wavelength in nm (sources in the docs section). */
export const LIGHT_SOURCES_NM = Object.freeze({
    heNe: 632.8, // He–Ne laser, 632.816 nm in air (632.991 nm vacuum)
    redDiode: 650, // red laser diode (nominal)
    greenDpss: 532, // frequency-doubled Nd:YAG / DPSS
    violetDiode: 405, // violet laser diode (nominal)
    sodiumD: 589.3, // mean of Na D2 588.995 nm and D1 589.592 nm (air)
});

/**
 * Cauchy dispersion formula n(λ) = A + B/λ².
 * (Equivalent to A + B'/λ² with λ in µm and B' = B·10¹² µm².)
 * @param {number} wavelength vacuum wavelength, m
 * @param {number} [A=BK7_CAUCHY.A]
 * @param {number} [B=BK7_CAUCHY.B] m²
 * @returns {number} refractive index
 */
export function cauchyIndex(wavelength, A = BK7_CAUCHY.A, B = BK7_CAUCHY.B) {
    requirePositive('wavelength', wavelength);
    return A + B / (wavelength * wavelength);
}

/**
 * Exact ray deviation through a prism of apex angle A (index n, surrounded by n = 1).
 *   face 1: sinθ1 = n·sinθ1'
 *   inside: θ2 = A − θ1'
 *   face 2: n·sinθ2 = sinθ2'
 *   deviation δ = θ1 + θ2' − A
 * Angles are signed and measured from each face normal.
 * @param {number} n refractive index of the prism
 * @param {number} apexAngle A, rad (0 < A < π)
 * @param {number} theta1 angle of incidence on the first face, rad
 * @returns {{theta1:number, theta1r:number, theta2:number, theta2r:number, deviation:number}|null}
 *   null when the ray is totally internally reflected at the second face
 *   (n·sinθ2 > 1) or cannot enter (only possible for n < 1).
 */
export function prismDeviation(n, apexAngle, theta1) {
    requirePositive('n', n);
    requirePositive('apexAngle', apexAngle);
    requireFinite('theta1', theta1);
    const theta1r = asinOrNull(Math.sin(theta1) / n); // always defined for n ≥ 1
    if (theta1r === null) return null;
    const theta2 = apexAngle - theta1r;
    const theta2r = asinOrNull(n * Math.sin(theta2));
    if (theta2r === null) return null;
    return { theta1, theta1r, theta2, theta2r, deviation: theta1 + theta2r - apexAngle };
}

/**
 * Minimum deviation of a prism: n = sin((A + δmin)/2) / sin(A/2)
 *   ⇒ δmin = 2·arcsin(n·sin(A/2)) − A.
 * @param {number} n @param {number} apexAngle rad
 * @returns {number|null} δmin in rad, or null when n·sin(A/2) > 1 (no ray emerges)
 */
export function prismMinimumDeviation(n, apexAngle) {
    const t = prismMinimumDeviationIncidence(n, apexAngle);
    return t === null ? null : 2 * t - apexAngle;
}

/**
 * Incidence angle giving minimum deviation (symmetric passage, θ1' = A/2):
 *   θ1 = arcsin(n·sin(A/2)).
 * @param {number} n @param {number} apexAngle rad
 * @returns {number|null}
 */
export function prismMinimumDeviationIncidence(n, apexAngle) {
    requirePositive('n', n);
    requirePositive('apexAngle', apexAngle);
    return asinOrNull(n * Math.sin(apexAngle / 2));
}

// ─────────────────────────────────────────────────────────────────────────────
// Thin lenses & spherical mirrors (real-is-positive convention, see header)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Gaussian imaging equation shared by thin lenses and spherical mirrors:
 *   1/F = 1/d + 1/d'  ⇒  d' = d·F/(d − F),   m = −d'/d,   h' = m·h.
 * @param {number} focalLength F, m (≠ 0; > 0 converging, < 0 diverging)
 * @param {number} objectDistance d, m (> 0 real object)
 * @param {number} [objectHeight=1] h, m
 * @returns {{
 *   imageDistance:number, magnification:number, imageHeight:number,
 *   atInfinity:boolean, isReal:boolean|null, isVirtual:boolean|null,
 *   isUpright:boolean|null, isInverted:boolean|null, isEnlarged:boolean|null
 * }} At d = F: imageDistance = ±Infinity, magnification = ±Infinity and every
 *   classification flag is null (no image is formed).
 */
export function gaussianImage(focalLength, objectDistance, objectHeight = 1) {
    requireFinite('focalLength', focalLength);
    requireFinite('objectDistance', objectDistance);
    requireFinite('objectHeight', objectHeight);
    if (focalLength === 0) throw new RangeError('focalLength must be non-zero');
    const f = focalLength;
    const d = objectDistance;
    const gap = d - f;
    if (Math.abs(gap) <= EPS * Math.max(Math.abs(d), Math.abs(f))) {
        // Object at the focal point: outgoing rays are parallel.
        const sign = Math.sign(f);
        return {
            imageDistance: sign * Infinity,
            magnification: -sign * Infinity,
            imageHeight: objectHeight === 0 ? 0 : -sign * Math.sign(objectHeight) * Infinity,
            atInfinity: true,
            isReal: null, isVirtual: null, isUpright: null, isInverted: null, isEnlarged: null,
        };
    }
    const di = (d * f) / gap;
    // m = −d'/d = −F/(d − F); this form is also valid at d = 0 (m = 1).
    const m = -f / gap;
    return {
        imageDistance: di,
        magnification: m,
        imageHeight: m * objectHeight,
        atInfinity: false,
        isReal: di > 0,
        isVirtual: !(di > 0),
        isUpright: m > 0,
        isInverted: m < 0,
        isEnlarged: Math.abs(m) > 1,
    };
}

/**
 * Thin lens imaging (see {@link gaussianImage}); convex lens F > 0, concave lens F < 0.
 * @param {number} focalLength m @param {number} objectDistance m @param {number} [objectHeight=1] m
 */
export function thinLensImage(focalLength, objectDistance, objectHeight = 1) {
    return gaussianImage(focalLength, objectDistance, objectHeight);
}

/**
 * Spherical-mirror imaging (paraxial), same equation as the thin lens with
 * d' > 0 meaning a real image IN FRONT of the mirror.
 * @param {number} focalLength m (concave > 0, convex < 0)
 * @param {number} objectDistance m @param {number} [objectHeight=1] m
 */
export function mirrorImage(focalLength, objectDistance, objectHeight = 1) {
    return gaussianImage(focalLength, objectDistance, objectHeight);
}

/**
 * Optical power D = 1/F (dioptres when F is in metres).
 * @param {number} focalLength m
 * @returns {number} dioptres
 */
export function opticalPower(focalLength) {
    requireFinite('focalLength', focalLength);
    if (focalLength === 0) throw new RangeError('focalLength must be non-zero');
    return 1 / focalLength;
}

/**
 * Paraxial focal length of a spherical mirror F = R/2, signed by the
 * real-is-positive convention: concave +|R|/2, convex −|R|/2.
 * @param {number} radius radius of curvature (magnitude used), m
 * @param {'concave'|'convex'} type
 * @returns {number} F, m
 */
export function mirrorFocalLength(radius, type) {
    requirePositive('|radius|', Math.abs(radius));
    if (type !== 'concave' && type !== 'convex') throw new RangeError(`unknown mirror type ${type}`);
    return (type === 'concave' ? 1 : -1) * Math.abs(radius) / 2;
}

/**
 * Principal rays of a thin lens for a ray diagram. Lens in the plane x = 0,
 * optical axis y = 0, light travels towards +x, object at x = −d with its top at
 * y = h. Every ray is { id, start, hit, dir } where start = object top, hit = point on
 * the lens plane and dir = unit direction after the lens (dir[0] > 0).
 *  - parallel: parallel to the axis, then through (or away from) F' at x = F
 *  - central:  through the optical centre, undeviated
 *  - focal:    along the line through the object-side focus x = −F, then parallel
 *              (omitted when d = F, where it is undefined)
 * The outgoing rays (real image) or their backward extensions (virtual image)
 * all pass through the image point (d', h') given by {@link thinLensImage}.
 * @param {number} focalLength m (≠ 0) @param {number} objectDistance m (> 0) @param {number} objectHeight m
 * @returns {Array<{id:string, start:[number,number], hit:[number,number], dir:[number,number]}>}
 */
export function thinLensPrincipalRays(focalLength, objectDistance, objectHeight) {
    requireFinite('focalLength', focalLength);
    requirePositive('objectDistance', objectDistance);
    requireFinite('objectHeight', objectHeight);
    if (focalLength === 0) throw new RangeError('focalLength must be non-zero');
    const f = focalLength;
    const d = objectDistance;
    const h = objectHeight;
    const s = Math.sign(f);
    const start = [-d, h];
    const rays = [
        { id: 'parallel', start, hit: [0, h], dir: normalize(s * f, -s * h) },
        { id: 'central', start, hit: [0, 0], dir: normalize(d, -h) },
    ];
    if (Math.abs(d - f) > EPS * Math.max(d, Math.abs(f))) {
        rays.push({ id: 'focal', start, hit: [0, (h * f) / (f - d)], dir: [1, 0] });
    }
    return rays;
}

/**
 * Principal rays of a spherical mirror (paraxial, reflection drawn at the vertex
 * plane x = 0). Light travels towards +x, object at x = −d in front of the mirror;
 * reflected rays travel towards −x (dir[0] < 0). F is at x = −F and C at x = −2F
 * (behind the mirror for a convex mirror, F < 0).
 *  - parallel: parallel to the axis, reflects through (or as if from) F
 *  - centre:   through (or towards) C, reflects back on itself (omitted at d = 2F)
 *  - focal:    through (or towards) F, reflects parallel to the axis (omitted at d = F)
 *  - vertex:   hits the vertex, reflects symmetrically about the axis
 * Real image at x = −d' (d' > 0); a virtual image (d' < 0) lies behind the mirror on
 * the backward extensions.
 * @param {number} focalLength m (≠ 0) @param {number} objectDistance m (> 0) @param {number} objectHeight m
 * @returns {Array<{id:string, start:[number,number], hit:[number,number], dir:[number,number]}>}
 */
export function mirrorPrincipalRays(focalLength, objectDistance, objectHeight) {
    requireFinite('focalLength', focalLength);
    requirePositive('objectDistance', objectDistance);
    requireFinite('objectHeight', objectHeight);
    if (focalLength === 0) throw new RangeError('focalLength must be non-zero');
    const f = focalLength;
    const d = objectDistance;
    const h = objectHeight;
    const s = Math.sign(f);
    const start = [-d, h];
    const tol = EPS * Math.max(d, Math.abs(f));
    const rays = [{ id: 'parallel', start, hit: [0, h], dir: normalize(-s * f, -s * h) }];
    if (Math.abs(2 * f - d) > tol) {
        const y = (2 * f * h) / (2 * f - d);
        // reversed incident direction (normal incidence on the sphere)
        rays.push({ id: 'centre', start, hit: [0, y], dir: normalize(-d, h - y) });
    }
    if (Math.abs(f - d) > tol) {
        rays.push({ id: 'focal', start, hit: [0, (h * f) / (f - d)], dir: [-1, 0] });
    }
    rays.push({ id: 'vertex', start, hit: [0, 0], dir: normalize(-d, -h) });
    return rays;
}

// ─────────────────────────────────────────────────────────────────────────────
// Interference & diffraction (Fraunhofer, flat screen at distance L)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * sinc used by diffraction: sin(β)/β with the removable singularity at 0.
 * @param {number} beta
 * @returns {number}
 */
export function sinc(beta) {
    if (Math.abs(beta) < 1e-8) return 1 - (beta * beta) / 6;
    return Math.sin(beta) / beta;
}

/**
 * sinθ of the direction from the slits to a point at height y on a flat screen
 * at distance L: sinθ = y/√(y² + L²) (exact, no small-angle approximation).
 * @param {number} y m @param {number} L m (> 0)
 * @returns {number}
 */
export function screenSinTheta(y, L) {
    requireFinite('y', y);
    requirePositive('L', L);
    return y / Math.hypot(y, L);
}

/**
 * Young double slit fringe spacing (small-angle) Δy = λL/d.
 * @param {number} wavelength λ, m @param {number} screenDistance L, m @param {number} slitSeparation d, m
 * @returns {number} Δy, m
 */
export function fringeSpacing(wavelength, screenDistance, slitSeparation) {
    requirePositive('wavelength', wavelength);
    requirePositive('screenDistance', screenDistance);
    requirePositive('slitSeparation', slitSeparation);
    return (wavelength * screenDistance) / slitSeparation;
}

/**
 * Wavelength from a measured Young fringe spacing (inverse of {@link fringeSpacing}):
 * λ = Δy·d / L.
 * @param {number} spacing Δy, m @param {number} screenDistance L, m @param {number} slitSeparation d, m
 * @returns {number} λ, m
 */
export function wavelengthFromFringeSpacing(spacing, screenDistance, slitSeparation) {
    requirePositive('spacing', spacing);
    requirePositive('screenDistance', screenDistance);
    requirePositive('slitSeparation', slitSeparation);
    return (spacing * slitSeparation) / screenDistance;
}

/**
 * Exact position of the m-th bright fringe: d·sinθ = mλ, y = L·tanθ.
 * @param {number} m integer order @param {number} wavelength m @param {number} slitSeparation m @param {number} screenDistance m
 * @returns {number|null} y in m, or null if |mλ/d| ≥ 1 (order does not exist)
 */
export function brightFringePosition(m, wavelength, slitSeparation, screenDistance) {
    requirePositive('wavelength', wavelength);
    requirePositive('slitSeparation', slitSeparation);
    requirePositive('screenDistance', screenDistance);
    const s = (m * wavelength) / slitSeparation;
    if (Math.abs(s) >= 1) return null;
    return screenDistance * Math.tan(Math.asin(s));
}

/**
 * Double-slit Fraunhofer intensity with the single-slit envelope:
 *   I/I0 = cos²(π d sinθ/λ) · (sin β/β)²,   β = π a sinθ/λ.
 * a = 0 gives ideal (infinitely narrow) slits.
 * @param {number} sinTheta @param {number} wavelength m @param {number} slitSeparation d, m
 * @param {number} [slitWidth=0] a, m (0 ≤ a < d)
 * @returns {number} relative intensity in [0, 1]
 */
export function doubleSlitIntensity(sinTheta, wavelength, slitSeparation, slitWidth = 0) {
    requireFinite('sinTheta', sinTheta);
    requirePositive('wavelength', wavelength);
    requirePositive('slitSeparation', slitSeparation);
    requireFinite('slitWidth', slitWidth);
    if (slitWidth < 0) throw new RangeError('slitWidth must be ≥ 0');
    const twoSlit = Math.cos((Math.PI * slitSeparation * sinTheta) / wavelength) ** 2;
    const env = sinc((Math.PI * slitWidth * sinTheta) / wavelength) ** 2;
    return twoSlit * env;
}

/**
 * Single-slit Fraunhofer intensity I/I0 = (sin β/β)², β = π a sinθ/λ.
 * @param {number} sinTheta @param {number} slitWidth a, m @param {number} wavelength λ, m
 * @returns {number} relative intensity in [0, 1]
 */
export function singleSlitIntensity(sinTheta, slitWidth, wavelength) {
    requireFinite('sinTheta', sinTheta);
    requirePositive('slitWidth', slitWidth);
    requirePositive('wavelength', wavelength);
    return sinc((Math.PI * slitWidth * sinTheta) / wavelength) ** 2;
}

/**
 * Single-slit minima a·sinθ = mλ (m = ±1, ±2, …).
 * @param {number} m non-zero integer @param {number} slitWidth a, m @param {number} wavelength λ, m
 * @returns {number|null} θm in rad, or null if |mλ/a| > 1
 */
export function singleSlitMinimumAngle(m, slitWidth, wavelength) {
    if (!Number.isInteger(m) || m === 0) throw new RangeError('m must be a non-zero integer');
    requirePositive('slitWidth', slitWidth);
    requirePositive('wavelength', wavelength);
    return asinOrNull((m * wavelength) / slitWidth);
}

/**
 * Diffraction grating maxima d·sinθ = mλ.
 * @param {number} m integer order @param {number} gratingPeriod d, m @param {number} wavelength λ, m
 * @returns {number|null} θm in rad, or null if |mλ/d| > 1
 */
export function gratingMaximumAngle(m, gratingPeriod, wavelength) {
    if (!Number.isInteger(m)) throw new RangeError('m must be an integer');
    requirePositive('gratingPeriod', gratingPeriod);
    requirePositive('wavelength', wavelength);
    return asinOrNull((m * wavelength) / gratingPeriod);
}

/**
 * Highest observable grating order: largest m with mλ/d < 1 (θ = 90° is not observable).
 * @param {number} gratingPeriod d, m @param {number} wavelength λ, m
 * @returns {number}
 */
export function gratingMaxOrder(gratingPeriod, wavelength) {
    requirePositive('gratingPeriod', gratingPeriod);
    requirePositive('wavelength', wavelength);
    const r = gratingPeriod / wavelength;
    const k = Math.round(r);
    return Math.abs(r - k) <= EPS * r ? k - 1 : Math.floor(r);
}

// ─────────────────────────────────────────────────────────────────────────────
// Polarisation
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Malus's law I = I0·cos²θ for linearly polarised light through a polariser
 * whose axis makes angle θ with the polarisation direction.
 * @param {number} I0 @param {number} theta rad
 * @returns {number}
 */
export function malusLaw(I0, theta) {
    requireFinite('I0', I0);
    requireFinite('theta', theta);
    return I0 * Math.cos(theta) ** 2;
}

/**
 * Ideal polariser on unpolarised light transmits I0/2 (average of cos² over all angles).
 * @param {number} I0
 * @returns {number}
 */
export function unpolarizedThroughPolarizer(I0) {
    requireFinite('I0', I0);
    return I0 / 2;
}

/**
 * Intensity after a chain of ideal polarisers.
 *   unpolarised input:  I = (I0/2) · Π_{k≥2} cos²(α_k − α_{k−1})
 *   polarised input at angle ψ: I = I0 · cos²(α_1 − ψ) · Π_{k≥2} cos²(α_k − α_{k−1})
 * @param {number} I0 input intensity
 * @param {number[]} axisAngles transmission-axis angles (rad), in the order the light meets them
 * @param {number|null} [inputPolarization=null] polarisation angle (rad) of the input, null = unpolarised
 * @returns {number}
 */
export function polarizerChainIntensity(I0, axisAngles, inputPolarization = null) {
    requireFinite('I0', I0);
    if (!Array.isArray(axisAngles)) throw new TypeError('axisAngles must be an array');
    if (axisAngles.length === 0) return I0;
    axisAngles.forEach((a, i) => requireFinite(`axisAngles[${i}]`, a));
    let I = inputPolarization === null ? unpolarizedThroughPolarizer(I0) : malusLaw(I0, axisAngles[0] - inputPolarization);
    for (let k = 1; k < axisAngles.length; k++) I = malusLaw(I, axisAngles[k] - axisAngles[k - 1]);
    return I;
}

/**
 * Light made of two incoherent orthogonal linear components (s with intensity Is,
 * p with intensity Ip) through an analyser whose axis makes angle φ with the s
 * direction: I = Is·cos²φ + Ip·sin²φ (Malus applied to each component).
 * @param {number} Is @param {number} Ip @param {number} phi rad
 * @returns {number}
 */
export function analyzerIntensity(Is, Ip, phi) {
    requireFinite('Is', Is);
    requireFinite('Ip', Ip);
    requireFinite('phi', phi);
    return malusLaw(Is, phi) + Ip * Math.sin(phi) ** 2;
}

/**
 * Degree of linear polarisation P = |Is − Ip| / (Is + Ip) (0 when both are 0).
 * @param {number} Is @param {number} Ip
 * @returns {number} in [0, 1]
 */
export function degreeOfPolarization(Is, Ip) {
    requireFinite('Is', Is);
    requireFinite('Ip', Ip);
    const sum = Is + Ip;
    return sum === 0 ? 0 : Math.abs(Is - Ip) / sum;
}

// ─────────────────────────────────────────────────────────────────────────────
// Measuring instruments of the optics bench (readings the student takes)
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Instrument specs of the optics experiments (spread from the shared catalog of
 * ./instruments.js and overridden where the optics procedure differs). Each
 * reading is trueValue + truncated-normal scatter (|z| ≤ 2.5σ), then quantized,
 * so |error| ≤ 2.5σ + resolution/2 (see maxReadingError).
 *  - protractor: optical disc / protractor, 0.5° divisions, σ = 0.1° → ±0.5°.
 *  - benchImage: optical bench scale 1 mm (value in cm); σ = 1 mm is the scatter
 *    of finding the sharpest image on the screen by eye (depth of focus) or of the
 *    parallax method for a virtual image → ±3 mm.
 *  - spectrometer: goniometer-spectrometer with a 0.01° (≈ 0.6′) vernier,
 *    σ = 0.002° for setting the cross-hair on a sharp spectral line → ±0.01°.
 *  - fringeMicrometer: measuring microscope / screen micrometer 0.01 mm,
 *    σ = 0.01 mm for setting the cross-hair on the centre of a fringe or of a
 *    dark minimum → ±0.03 mm per setting.
 *  - photometer: photodiode + 4½-digit meter (20 000 counts, auto-range), value
 *    in % of the incident intensity, σ = 0.05 % of the reading (stabilised source).
 */
export const OPTICS_INSTRUMENTS = Object.freeze({
    protractor: INSTRUMENTS.protractor,
    benchImage: Object.freeze({ ...INSTRUMENTS.opticalBench, sigmaAbs: 0.1, accuracy: '±3 mm (fokuslash)' }),
    spectrometer: Object.freeze({ ...INSTRUMENTS.goniometer, sigmaAbs: 0.002, accuracy: '±0.01°' }),
    fringeMicrometer: Object.freeze({ ...INSTRUMENTS.screenMicrometer, sigmaAbs: 0.01, accuracy: '±0.03 mm' }),
    photometer: Object.freeze({
        resolution: 0, sigmaRel: 0.0005, limit: 2.5, counts: 20000, ranges: Object.freeze([0.2, 2, 20, 200]),
        unit: '%', accuracy: '±(0.05 % + 1 raqam)',
    }),
});

/**
 * One photometer reading of a relative intensity given in % (auto-ranged
 * 20 000-count display: 7.2908 % → 7.291 on the 20 % range).
 * @param {number} percent true value, %
 * @param {() => number} [rng] seeded PRNG (omit → only display rounding)
 * @returns {number}
 */
export function photometerReading(percent, rng = null) {
    const p = OPTICS_INSTRUMENTS.photometer;
    return dmmReading(percent, rng, { counts: p.counts, ranges: p.ranges, sigmaRel: p.sigmaRel, limit: p.limit });
}

/**
 * Display text of a photometer reading with the digits of its range ("7.291", "0.00052").
 * @param {number} percent reading, % @returns {string}
 */
export function formatPhotometer(percent) {
    const p = OPTICS_INSTRUMENTS.photometer;
    return formatReading(percent, dmmResolution(percent, p.counts, p.ranges));
}

/**
 * Worst-case |photometer reading − true value| (scatter bound + half a digit of
 * the range the noisy value can fall into).
 * @param {number} percent @returns {number}
 */
export function photometerMaxError(percent) {
    const p = OPTICS_INSTRUMENTS.photometer;
    const noise = p.limit * readingSigma(percent, p);
    return noise + dmmResolution(Math.abs(percent) + noise, p.counts, p.ranges) / 2;
}

/**
 * Number of fringe spacings N measured across in the Young experiment: up to 10,
 * but only bright fringes inside the central single-slit envelope (|m| < d/a)
 * are bright enough to set on, so N = 2·min(5, ⌈d/a⌉ − 1).
 * @param {number} slitSeparation d, m @param {number} slitWidth a, m (> 0)
 * @returns {number} even N ≥ 2
 */
export function fringeCountForSpan(slitSeparation, slitWidth) {
    requirePositive('slitSeparation', slitSeparation);
    requirePositive('slitWidth', slitWidth);
    const inside = Math.ceil(slitSeparation / slitWidth - 1e-9) - 1; // largest |m| with m < d/a
    return 2 * Math.max(1, Math.min(5, inside));
}

/**
 * Position y of the m-th single-slit minimum on a flat screen at distance L:
 * y = L·tan θm, a·sin θm = mλ.
 * @param {number} m non-zero integer @param {number} slitWidth a, m @param {number} wavelength λ, m @param {number} screenDistance L, m
 * @returns {number|null} y in m, or null if the minimum does not exist
 */
export function singleSlitMinimumPosition(m, slitWidth, wavelength, screenDistance) {
    requirePositive('screenDistance', screenDistance);
    const t = singleSlitMinimumAngle(m, slitWidth, wavelength);
    if (t === null || Math.abs(t) >= Math.PI / 2) return null;
    return screenDistance * Math.tan(t);
}

// ─────────────────────────────────────────────────────────────────────────────
// Display helper
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Approximate sRGB colour of a visible wavelength (piece-wise linear spectrum
 * ramp, 380–780 nm); outside that range returns white.
 * @param {number} wavelength m
 * @returns {[number, number, number]} 0–255 components
 */
export function wavelengthToRgb(wavelength) {
    const wl = wavelength / NM;
    if (wl >= 380 && wl < 440) return [Math.round(((440 - wl) / 60) * 255), 0, 255];
    if (wl >= 440 && wl < 490) return [0, Math.round(((wl - 440) / 50) * 255), 255];
    if (wl >= 490 && wl < 510) return [0, 255, Math.round(((510 - wl) / 20) * 255)];
    if (wl >= 510 && wl < 580) return [Math.round(((wl - 510) / 70) * 255), 255, 0];
    if (wl >= 580 && wl < 645) return [255, Math.round(((645 - wl) / 65) * 255), 0];
    if (wl >= 645 && wl <= 780) return [255, 0, 0];
    return [255, 255, 255];
}
