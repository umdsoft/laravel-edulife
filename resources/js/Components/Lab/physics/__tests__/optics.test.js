import { describe, it, expect } from 'vitest';
import {
    DEG, toRad, toDeg, NM, MM, CM,
    normalize,
    reflectionAngle, snellRefractionAngle, criticalAngle, isTotalInternalReflection,
    fresnelReflectance, normalIncidenceReflectance, brewsterAngle,
    refractDirection, reflectDirection,
    BK7_CAUCHY, cauchyIndex, prismDeviation, prismMinimumDeviation, prismMinimumDeviationIncidence,
    gaussianImage, thinLensImage, mirrorImage, opticalPower, mirrorFocalLength,
    thinLensPrincipalRays, mirrorPrincipalRays,
    sinc, screenSinTheta, fringeSpacing, brightFringePosition, doubleSlitIntensity,
    singleSlitIntensity, singleSlitMinimumAngle, gratingMaximumAngle, gratingMaxOrder,
    malusLaw, unpolarizedThroughPolarizer, polarizerChainIntensity, analyzerIntensity,
    degreeOfPolarization, wavelengthToRgb,
} from '../optics.js';

/** distance from point p to the infinite line through `a` with direction `dir` */
const distToLine = (p, a, dir) => {
    const [ux, uy] = normalize(dir[0], dir[1]);
    return Math.abs((p[0] - a[0]) * uy - (p[1] - a[1]) * ux);
};

describe('unit helpers', () => {
    it('converts degrees/radians and length prefixes', () => {
        expect(toRad(180)).toBeCloseTo(Math.PI, 15);
        expect(toDeg(Math.PI / 2)).toBeCloseTo(90, 12);
        expect(DEG).toBeCloseTo(Math.PI / 180, 15);
        expect(600 * NM).toBeCloseTo(6e-7, 20);
        expect(0.5 * MM).toBeCloseTo(5e-4, 18);
        expect(25 * CM).toBeCloseTo(0.25, 15);
    });
    it('normalize rejects zero vectors', () => {
        expect(normalize(3, 4)).toEqual([0.6, 0.8]);
        expect(() => normalize(0, 0)).toThrow(RangeError);
    });
});

describe('reflection', () => {
    it('θr = θi', () => {
        expect(reflectionAngle(toRad(35))).toBeCloseTo(toRad(35), 15);
        expect(reflectionAngle(0)).toBe(0);
        expect(() => reflectionAngle(-0.1)).toThrow(RangeError);
        expect(() => reflectionAngle(NaN)).toThrow(TypeError);
    });
    it('vector reflection mirrors the normal component', () => {
        const d = [Math.sin(toRad(30)), -Math.cos(toRad(30))];
        const r = reflectDirection(d, [0, 1]);
        expect(r[0]).toBeCloseTo(Math.sin(toRad(30)), 15);
        expect(r[1]).toBeCloseTo(Math.cos(toRad(30)), 15);
    });
});

describe("Snell's law (angles from the normal)", () => {
    it('air → glass, 30°: sinθ2 = sin30°/1.5 = 1/3 → 19.47°', () => {
        const t2 = snellRefractionAngle(1, 1.5, toRad(30));
        expect(t2).toBeCloseTo(Math.asin(1 / 3), 14);
        expect(toDeg(t2)).toBeCloseTo(19.4712, 4);
    });
    it('n1 sinθ1 = n2 sinθ2 holds for random inputs', () => {
        for (const [n1, n2, deg] of [[1, 1.33, 45], [1.33, 1.5, 60], [1.2, 2.4, 10], [1, 1, 37]]) {
            const t2 = snellRefractionAngle(n1, n2, toRad(deg));
            expect(n1 * Math.sin(toRad(deg))).toBeCloseTo(n2 * Math.sin(t2), 14);
        }
    });
    it('normal incidence is undeviated', () => {
        expect(snellRefractionAngle(1, 1.5, 0)).toBe(0);
    });
    it('critical angle glass→air = arcsin(1/1.5) = 41.81°, water→air = 48.75°', () => {
        expect(toDeg(criticalAngle(1.5, 1))).toBeCloseTo(41.8103, 4);
        expect(toDeg(criticalAngle(1.33, 1))).toBeCloseTo(48.7535, 4);
        expect(criticalAngle(1, 1.5)).toBeNull();
        expect(criticalAngle(1.5, 1.5)).toBeNull();
    });
    it('total internal reflection above θc, grazing exactly at θc', () => {
        const tc = criticalAngle(1.5, 1);
        expect(snellRefractionAngle(1.5, 1, tc)).toBeCloseTo(Math.PI / 2, 6);
        expect(isTotalInternalReflection(1.5, 1, tc + 1e-6)).toBe(true);
        expect(snellRefractionAngle(1.5, 1, toRad(60))).toBeNull();
        expect(isTotalInternalReflection(1.5, 1, toRad(40))).toBe(false);
        expect(isTotalInternalReflection(1, 1.5, toRad(85))).toBe(false);
    });
    it('rejects invalid indices', () => {
        expect(() => snellRefractionAngle(0, 1.5, 0.3)).toThrow(RangeError);
        expect(() => snellRefractionAngle(1, -1, 0.3)).toThrow(RangeError);
        expect(() => snellRefractionAngle(1, 1.5, NaN)).toThrow(TypeError);
    });
    it('vector Snell matches scalar Snell and reports TIR', () => {
        const th = toRad(50);
        const t = refractDirection([Math.sin(th), -Math.cos(th)], [0, 1], 1, 1.5);
        const t2 = snellRefractionAngle(1, 1.5, th);
        expect(t[0]).toBeCloseTo(Math.sin(t2), 14);
        expect(t[1]).toBeCloseTo(-Math.cos(t2), 14);
        expect(refractDirection([Math.sin(toRad(60)), -Math.cos(toRad(60))], [0, 1], 1.5, 1)).toBeNull();
        expect(() => refractDirection([0, -1], [0, -1], 1, 1.5)).toThrow(RangeError);
    });
});

describe('Fresnel equations', () => {
    it('normal incidence air/glass: R = (0.5/2.5)² = 0.04, Rs = Rp', () => {
        expect(normalIncidenceReflectance(1, 1.5)).toBeCloseTo(0.04, 15);
        const f = fresnelReflectance(1, 1.5, 0);
        expect(f.Rs).toBeCloseTo(0.04, 14);
        expect(f.Rp).toBeCloseTo(0.04, 14);
        expect(f.R + f.T).toBeCloseTo(1, 15);
    });
    it('water at normal incidence R = (0.333/2.333)² ≈ 0.0204', () => {
        expect(normalIncidenceReflectance(1, 1.333)).toBeCloseTo(0.020376, 5);
    });
    it('air/glass 45°: Rs = 0.09201, Rp = 0.00847 and Rp = Rs² (identity at 45°)', () => {
        const f = fresnelReflectance(1, 1.5, toRad(45));
        expect(f.Rs).toBeCloseTo(0.0920134, 6);
        expect(f.Rp).toBeCloseTo(0.0084665, 6);
        expect(f.Rp).toBeCloseTo(f.Rs ** 2, 12);
    });
    it('Rp vanishes at Brewster angle tanθB = 1.5 (56.31°) and θB + θt = 90°', () => {
        const tb = brewsterAngle(1, 1.5);
        expect(toDeg(tb)).toBeCloseTo(56.3099, 4);
        expect(fresnelReflectance(1, 1.5, tb).Rp).toBeLessThan(1e-30);
        expect(tb + snellRefractionAngle(1, 1.5, tb)).toBeCloseTo(Math.PI / 2, 14);
    });
    it('grazing incidence → R = 1; TIR → R = 1', () => {
        expect(fresnelReflectance(1, 1.5, Math.PI / 2).R).toBeCloseTo(1, 12);
        const tir = fresnelReflectance(1.5, 1, toRad(60));
        expect(tir).toMatchObject({ Rs: 1, Rp: 1, R: 1, T: 0, totalInternalReflection: true });
    });
});

describe('dispersion (Cauchy) and prism', () => {
    it('BK7 Cauchy: n(587.6 nm) ≈ 1.5168 (catalogue n_d), n(400) = 1.53085, n(700) = 1.51317', () => {
        expect(cauchyIndex(587.6 * NM)).toBeCloseTo(1.5168, 4);
        expect(cauchyIndex(400 * NM)).toBeCloseTo(1.5046 + 0.0042 / 0.16, 12);
        expect(cauchyIndex(700 * NM)).toBeCloseTo(1.5046 + 0.0042 / 0.49, 12);
        expect(BK7_CAUCHY.B).toBe(4.2e-15);
    });
    it('normal dispersion: n decreases monotonically with λ', () => {
        let prev = Infinity;
        for (let wl = 380; wl <= 780; wl += 20) {
            const n = cauchyIndex(wl * NM);
            expect(n).toBeLessThan(prev);
            prev = n;
        }
        expect(() => cauchyIndex(0)).toThrow(RangeError);
    });
    it('minimum deviation n=1.5, A=60°: δmin = 2·arcsin(0.75) − 60° = 37.18°', () => {
        const dmin = prismMinimumDeviation(1.5, toRad(60));
        expect(toDeg(dmin)).toBeCloseTo(37.1808, 4);
        // n = sin((A+δ)/2)/sin(A/2)
        expect(Math.sin((toRad(60) + dmin) / 2) / Math.sin(toRad(30))).toBeCloseTo(1.5, 14);
        const t1 = prismMinimumDeviationIncidence(1.5, toRad(60));
        expect(toDeg(t1)).toBeCloseTo(48.5904, 4);
        const p = prismDeviation(1.5, toRad(60), t1);
        expect(p.theta1r).toBeCloseTo(toRad(30), 14); // symmetric passage
        expect(p.deviation).toBeCloseTo(dmin, 14);
    });
    it('exact deviation at θ1 = 40°: θ1′=25.374°, θ2=34.626°, θ2′=58.466°, δ=38.466°', () => {
        const p = prismDeviation(1.5, toRad(60), toRad(40));
        expect(toDeg(p.theta1r)).toBeCloseTo(25.3740, 4);
        expect(toDeg(p.theta2)).toBeCloseTo(34.6260, 4);
        expect(toDeg(p.theta2r)).toBeCloseTo(58.4656, 4);
        expect(toDeg(p.deviation)).toBeCloseTo(38.4656, 4);
    });
    it('δ(θ1) ≥ δmin for every incidence that emerges', () => {
        const A = toRad(60);
        const dmin = prismMinimumDeviation(1.5, A);
        for (let deg = 30; deg <= 89; deg += 1) {
            const p = prismDeviation(1.5, A, toRad(deg));
            if (p) expect(p.deviation).toBeGreaterThanOrEqual(dmin - 1e-12);
        }
    });
    it('violet deviates more than red', () => {
        const A = toRad(60);
        expect(prismMinimumDeviation(cauchyIndex(400 * NM), A)).toBeGreaterThan(prismMinimumDeviation(cauchyIndex(700 * NM), A));
    });
    it('no emergent ray when A > 2θc (A = 90°, n = 1.5): TIR at the second face', () => {
        expect(prismMinimumDeviation(1.5, toRad(90))).toBeNull();
        expect(prismDeviation(1.5, toRad(90), toRad(89))).toBeNull();
        expect(prismMinimumDeviationIncidence(1.5, toRad(90))).toBeNull();
    });
    it('vector ray trace through the prism gives the same δ as the angle formula', () => {
        const A = toRad(60);
        const n = 1.52;
        const th1 = toRad(50);
        const half = A / 2;
        // canvas-like frame (y down), apex up, left-face inward normal at +A/2 below horizontal
        const inL = [Math.cos(half), Math.sin(half)];
        const phiIn = half - th1;
        const d0 = [Math.cos(phiIn), Math.sin(phiIn)];
        const d1 = refractDirection(d0, [-inL[0], -inL[1]], 1, n);
        const d2 = refractDirection(d1, [-Math.cos(half), Math.sin(half)], n, 1);
        const delta = Math.acos(d0[0] * d2[0] + d0[1] * d2[1]);
        expect(delta).toBeCloseTo(prismDeviation(n, A, th1).deviation, 13);
        // deviation is towards the base (downwards in a y-down frame)
        expect(d0[0] * d2[1] - d0[1] * d2[0]).toBeGreaterThan(0);
    });
});

describe('thin lens 1/F = 1/d + 1/d′ (real-is-positive), m = −d′/d', () => {
    it('convex F=10 cm, d=25 cm → d′=16.67 cm, m=−0.667: real, inverted, reduced', () => {
        const r = thinLensImage(0.10, 0.25, 0.03);
        expect(r.imageDistance).toBeCloseTo(0.25 * 0.1 / 0.15, 14);
        expect(r.magnification).toBeCloseTo(-2 / 3, 14);
        expect(r.imageHeight).toBeCloseTo(-0.02, 14);
        expect(r).toMatchObject({ isReal: true, isVirtual: false, isInverted: true, isUpright: false, isEnlarged: false, atInfinity: false });
        expect(1 / 0.25 + 1 / r.imageDistance).toBeCloseTo(1 / 0.10, 10);
    });
    it('object at 2F → image at 2F, m = −1', () => {
        const r = thinLensImage(0.1, 0.2);
        expect(r.imageDistance).toBeCloseTo(0.2, 14);
        expect(r.magnification).toBeCloseTo(-1, 14);
    });
    it('object inside F (d=5, F=10 cm) → virtual, upright, ×2 at d′=−10 cm', () => {
        const r = thinLensImage(0.1, 0.05);
        expect(r.imageDistance).toBeCloseTo(-0.1, 14);
        expect(r.magnification).toBeCloseTo(2, 14);
        expect(r).toMatchObject({ isReal: false, isVirtual: true, isUpright: true, isEnlarged: true });
    });
    it('object at the focus → image at infinity, no classification', () => {
        const r = thinLensImage(0.1, 0.1);
        expect(r.atInfinity).toBe(true);
        expect(Math.abs(r.imageDistance)).toBe(Infinity);
        expect(r.isReal).toBeNull();
        expect(r.isUpright).toBeNull();
        // also detected for values that differ only by round-off
        expect(thinLensImage(0.1, 0.30000000000000004 / 3).atInfinity).toBe(true);
    });
    it('concave F=−15 cm, d=30 cm → d′ = −10 cm, m = +1/3 (virtual, upright, reduced)', () => {
        const r = thinLensImage(-0.15, 0.30, 0.03);
        expect(r.imageDistance).toBeCloseTo(-0.10, 14);
        expect(r.magnification).toBeCloseTo(1 / 3, 14);
        expect(r.imageHeight).toBeCloseTo(0.01, 14);
        expect(r).toMatchObject({ isReal: false, isUpright: true, isEnlarged: false });
    });
    it('a diverging lens always gives a virtual reduced upright image of a real object', () => {
        for (const d of [0.01, 0.1, 0.3, 1, 10]) {
            const r = thinLensImage(-0.2, d);
            expect(r.isVirtual).toBe(true);
            expect(r.magnification).toBeGreaterThan(0);
            expect(r.magnification).toBeLessThan(1);
            expect(-r.imageDistance).toBeLessThan(0.2); // between lens and F′
        }
    });
    it('object at the lens (d=0) → image at the lens, m = 1', () => {
        const r = gaussianImage(0.1, 0);
        expect(r.imageDistance).toBeCloseTo(0, 15);
        expect(r.magnification).toBe(1);
    });
    it('rejects F = 0 and non-finite inputs', () => {
        expect(() => thinLensImage(0, 0.2)).toThrow(RangeError);
        expect(() => thinLensImage(0.1, NaN)).toThrow(TypeError);
        expect(() => thinLensImage(Infinity, 0.2)).toThrow(TypeError);
    });
    it('optical power D = 1/F', () => {
        expect(opticalPower(0.25)).toBeCloseTo(4, 14);
        expect(opticalPower(-0.5)).toBeCloseTo(-2, 14);
        expect(() => opticalPower(0)).toThrow(RangeError);
    });
});

describe('thin lens principal rays are consistent with the computed image', () => {
    const cases = [[0.1, 0.25], [0.1, 0.05], [0.2, 0.5], [-0.15, 0.3], [-0.05, 0.6], [0.1, 0.11]];
    for (const [f, d] of cases) {
        it(`F=${f} m, d=${d} m`, () => {
            const h = 0.03;
            const img = thinLensImage(f, d, h);
            const P = [img.imageDistance, img.imageHeight];
            const rays = thinLensPrincipalRays(f, d, h);
            expect(rays.map((r) => r.id)).toEqual(['parallel', 'central', 'focal']);
            for (const r of rays) {
                expect(r.dir[0]).toBeGreaterThan(0);
                // incident segment passes from object top to the hit point on the lens plane
                expect(r.hit[0]).toBe(0);
                // outgoing ray (or its backward extension) passes through the image
                expect(distToLine(P, r.hit, r.dir)).toBeLessThan(1e-12);
            }
        });
    }
    it('focal ray of a convex lens really passes through F (x = −F)', () => {
        const [, , focal] = thinLensPrincipalRays(0.1, 0.25, 0.03);
        expect(distToLine([-0.1, 0], focal.start, [focal.hit[0] - focal.start[0], focal.hit[1] - focal.start[1]])).toBeLessThan(1e-14);
    });
    it('parallel ray of a concave lens diverges away from the axis', () => {
        const [par] = thinLensPrincipalRays(-0.15, 0.3, 0.03);
        expect(par.dir[1]).toBeGreaterThan(0);
        expect(distToLine([-0.15, 0], par.hit, par.dir)).toBeLessThan(1e-14); // as if from F′
    });
    it('object at F: focal ray omitted, outgoing rays parallel', () => {
        const rays = thinLensPrincipalRays(0.1, 0.1, 0.03);
        expect(rays.map((r) => r.id)).toEqual(['parallel', 'central']);
        expect(rays[0].dir[0] * rays[1].dir[1] - rays[0].dir[1] * rays[1].dir[0]).toBeCloseTo(0, 14);
    });
    it('rejects d ≤ 0', () => {
        expect(() => thinLensPrincipalRays(0.1, 0, 0.03)).toThrow(RangeError);
    });
});

describe('spherical mirrors F = R/2 (concave +, convex −)', () => {
    it('focal length signs', () => {
        expect(mirrorFocalLength(0.4, 'concave')).toBeCloseTo(0.2, 15);
        expect(mirrorFocalLength(0.4, 'convex')).toBeCloseTo(-0.2, 15);
        expect(() => mirrorFocalLength(0.4, 'flat')).toThrow(RangeError);
        expect(() => mirrorFocalLength(0, 'concave')).toThrow(RangeError);
    });
    it('concave F=20 cm: d=40 (at C) → d′=40, m=−1; d=30 → d′=60, m=−2; d=10 → d′=−20, m=+2', () => {
        let r = mirrorImage(0.2, 0.4);
        expect(r.imageDistance).toBeCloseTo(0.4, 14);
        expect(r.magnification).toBeCloseTo(-1, 14);
        r = mirrorImage(0.2, 0.3);
        expect(r.imageDistance).toBeCloseTo(0.6, 14);
        expect(r.magnification).toBeCloseTo(-2, 14);
        expect(r).toMatchObject({ isReal: true, isInverted: true, isEnlarged: true });
        r = mirrorImage(0.2, 0.1);
        expect(r.imageDistance).toBeCloseTo(-0.2, 14);
        expect(r.magnification).toBeCloseTo(2, 14);
        expect(r).toMatchObject({ isVirtual: true, isUpright: true });
    });
    it('concave, object at F → image at infinity', () => {
        expect(mirrorImage(0.2, 0.2).atInfinity).toBe(true);
    });
    it('convex F=−20 cm, d=40 cm → d′=−13.33 cm, m=+1/3', () => {
        const r = mirrorImage(-0.2, 0.4);
        expect(r.imageDistance).toBeCloseTo(-0.4 / 3, 14);
        expect(r.magnification).toBeCloseTo(1 / 3, 14);
        expect(r).toMatchObject({ isVirtual: true, isUpright: true, isEnlarged: false });
    });
    const cases = [[0.2, 0.4], [0.2, 0.3], [0.2, 0.1], [0.2, 0.75], [-0.2, 0.4], [-0.1, 0.15]];
    for (const [f, d] of cases) {
        it(`mirror rays pass through the image (F=${f}, d=${d})`, () => {
            const h = 0.08;
            const img = mirrorImage(f, d, h);
            const P = [-img.imageDistance, img.imageHeight]; // real image in front (x < 0)
            const rays = mirrorPrincipalRays(f, d, h);
            for (const r of rays) {
                expect(r.dir[0]).toBeLessThan(0);
                expect(distToLine(P, r.hit, r.dir)).toBeLessThan(1e-12);
            }
        });
    }
    it('degenerate rays are omitted at d = 2F and d = F', () => {
        expect(mirrorPrincipalRays(0.2, 0.4, 0.08).map((r) => r.id)).toEqual(['parallel', 'focal', 'vertex']);
        expect(mirrorPrincipalRays(0.2, 0.2, 0.08).map((r) => r.id)).toEqual(['parallel', 'centre', 'vertex']);
    });
    it('convex mirror: parallel ray reflects as if from the virtual focus behind the mirror', () => {
        const [par] = mirrorPrincipalRays(-0.2, 0.4, 0.08);
        expect(par.dir[1]).toBeGreaterThan(0); // diverges from the axis
        expect(distToLine([0.2, 0], par.hit, par.dir)).toBeLessThan(1e-14);
    });
    it('vertex ray obeys the law of reflection about the axis', () => {
        const rays = mirrorPrincipalRays(0.2, 0.3, 0.08);
        const v = rays.find((r) => r.id === 'vertex');
        const inc = normalize(0.3, -0.08);
        expect(v.dir[0]).toBeCloseTo(-inc[0], 14);
        expect(v.dir[1]).toBeCloseTo(inc[1], 14);
    });
});

describe('Young double slit', () => {
    it('Δy = λL/d: 600 nm, 1 m, 0.5 mm → 1.2 mm; 500 nm, 2 m, 0.25 mm → 4 mm', () => {
        expect(fringeSpacing(600 * NM, 1, 0.5 * MM)).toBeCloseTo(1.2e-3, 15);
        expect(fringeSpacing(500 * NM, 2, 0.25 * MM)).toBeCloseTo(4e-3, 15);
        expect(() => fringeSpacing(600 * NM, 1, 0)).toThrow(RangeError);
    });
    it('exact bright-fringe position agrees with Δy for small angles; impossible orders → null', () => {
        const y1 = brightFringePosition(1, 600 * NM, 0.5 * MM, 1);
        // y = L·tanθ = L·s/√(1−s²), s = λ/d = 0.0012 → 1.2000008640 mm (small-angle: 1.2 mm)
        expect(y1).toBeCloseTo(0.0012 / Math.sqrt(1 - 0.0012 ** 2), 15);
        expect(Math.abs(y1 - 1.2e-3) / 1.2e-3).toBeLessThan(1e-6);
        expect(brightFringePosition(0, 600 * NM, 0.5 * MM, 1)).toBe(0);
        expect(brightFringePosition(2, 600 * NM, 1e-6, 1)).toBeNull(); // 2λ/d = 1.2 > 1
    });
    it('I = cos²(πd sinθ/λ): maxima at d sinθ = mλ, zeros at half orders', () => {
        const wl = 600 * NM, d = 0.5 * MM;
        expect(doubleSlitIntensity(0, wl, d)).toBe(1);
        expect(doubleSlitIntensity(wl / d, wl, d)).toBeCloseTo(1, 12);
        expect(doubleSlitIntensity(0.5 * wl / d, wl, d)).toBeCloseTo(0, 12);
        expect(doubleSlitIntensity(0.25 * wl / d, wl, d)).toBeCloseTo(0.5, 12);
    });
    it('single-slit envelope multiplies the fringes and suppresses missing orders (d = 3a)', () => {
        const wl = 600 * NM, d = 0.3 * MM, a = 0.1 * MM;
        // at the 3rd order the envelope is zero: a sinθ = λ
        expect(doubleSlitIntensity(3 * wl / d, wl, d, a)).toBeCloseTo(0, 12);
        // 1st order: envelope (sin(π/3)/(π/3))² = 0.6839
        expect(doubleSlitIntensity(wl / d, wl, d, a)).toBeCloseTo((Math.sin(Math.PI / 3) / (Math.PI / 3)) ** 2, 12);
        expect(() => doubleSlitIntensity(0, wl, d, -1)).toThrow(RangeError);
    });
    it('screen geometry: sinθ = y/√(y²+L²)', () => {
        expect(screenSinTheta(3, 4)).toBeCloseTo(0.6, 15);
        expect(screenSinTheta(-3, 4)).toBeCloseTo(-0.6, 15);
        expect(screenSinTheta(0, 1)).toBe(0);
    });
});

describe('single slit and grating', () => {
    it('first minimum a sinθ = λ: 600 nm, 0.1 mm → 0.3438°', () => {
        const t = singleSlitMinimumAngle(1, 0.1 * MM, 600 * NM);
        expect(t).toBeCloseTo(Math.asin(0.006), 15);
        expect(toDeg(t)).toBeCloseTo(0.343777, 6);
        expect(singleSlitMinimumAngle(-1, 0.1 * MM, 600 * NM)).toBeCloseTo(-t, 15);
        expect(singleSlitMinimumAngle(2, 1 * 1e-6, 600 * NM)).toBeNull(); // 1.2 > 1
        expect(() => singleSlitMinimumAngle(0, 1e-4, 6e-7)).toThrow(RangeError);
    });
    it('I = (sinβ/β)²: 1 at centre, 0 at minima, first side lobe 4.72 %', () => {
        const a = 0.1 * MM, wl = 600 * NM;
        expect(singleSlitIntensity(0, a, wl)).toBe(1);
        expect(singleSlitIntensity(wl / a, a, wl)).toBeLessThan(1e-25);
        expect(singleSlitIntensity(2 * wl / a, a, wl)).toBeLessThan(1e-25);
        // first secondary maximum at β ≈ 1.4303π (tanβ = β)
        expect(singleSlitIntensity(1.4303 * wl / a, a, wl)).toBeCloseTo(0.04719, 5);
    });
    it('sinc handles β → 0 smoothly', () => {
        expect(sinc(0)).toBe(1);
        expect(sinc(1e-10)).toBeCloseTo(1, 15);
        expect(sinc(Math.PI)).toBeCloseTo(0, 15);
    });
    it('grating d sinθ = mλ: 500 lines/mm, 500 nm → 14.4775° (m=1)', () => {
        const d = 1e-3 / 500;
        expect(toDeg(gratingMaximumAngle(1, d, 500 * NM))).toBeCloseTo(14.4775, 4);
        expect(gratingMaximumAngle(0, d, 500 * NM)).toBe(0);
        expect(gratingMaximumAngle(5, d, 500 * NM)).toBeNull();
        expect(gratingMaxOrder(d, 500 * NM)).toBe(3); // d/λ = 4 exactly → 90° not observable
        expect(gratingMaxOrder(d, 600 * NM)).toBe(3); // d/λ = 3.33
        expect(() => gratingMaximumAngle(1.5, d, 500 * NM)).toThrow(RangeError);
    });
});

describe('polarisation', () => {
    it("Malus's law I = I0 cos²θ", () => {
        expect(malusLaw(100, toRad(60))).toBeCloseTo(25, 12);
        expect(malusLaw(100, 0)).toBe(100);
        expect(malusLaw(100, Math.PI / 2)).toBeCloseTo(0, 12);
    });
    it('unpolarised → I0/2 after the first polariser', () => {
        expect(unpolarizedThroughPolarizer(80)).toBe(40);
        expect(polarizerChainIntensity(80, [toRad(17)])).toBe(40);
    });
    it('chained polarisers: crossed → 0, 0°/45°/90° → I0/8, polarised input', () => {
        expect(polarizerChainIntensity(100, [0, Math.PI / 2])).toBeCloseTo(0, 12);
        expect(polarizerChainIntensity(100, [0, toRad(45)])).toBeCloseTo(25, 12);
        expect(polarizerChainIntensity(100, [0, toRad(45), toRad(90)])).toBeCloseTo(12.5, 12);
        expect(polarizerChainIntensity(100, [toRad(30)], 0)).toBeCloseTo(75, 12);
        expect(polarizerChainIntensity(100, [])).toBe(100);
        expect(() => polarizerChainIntensity(100, [NaN])).toThrow(TypeError);
    });
    it('analyser on s+p light: Is cos²φ + Ip sin²φ; reduces to Malus when Ip = 0', () => {
        expect(analyzerIntensity(0.3, 0.1, 0)).toBeCloseTo(0.3, 15);
        expect(analyzerIntensity(0.3, 0.1, Math.PI / 2)).toBeCloseTo(0.1, 15);
        expect(analyzerIntensity(0.3, 0.1, toRad(45))).toBeCloseTo(0.2, 15);
        expect(analyzerIntensity(0.3, 0, toRad(60))).toBeCloseTo(malusLaw(0.3, toRad(60)), 15);
    });
    it('degree of polarisation', () => {
        expect(degreeOfPolarization(1, 1)).toBe(0);
        expect(degreeOfPolarization(1, 0)).toBe(1);
        expect(degreeOfPolarization(0, 0)).toBe(0);
        const f = fresnelReflectance(1, 1.5, brewsterAngle(1, 1.5));
        expect(degreeOfPolarization(f.Rs, f.Rp)).toBeCloseTo(1, 12);
    });
});

describe('wavelengthToRgb', () => {
    it('maps the visible ramp and falls back to white', () => {
        expect(wavelengthToRgb(700 * NM)).toEqual([255, 0, 0]);
        expect(wavelengthToRgb(440 * NM)).toEqual([0, 0, 255]);
        expect(wavelengthToRgb(510 * NM)).toEqual([0, 255, 0]);
        expect(wavelengthToRgb(1000 * NM)).toEqual([255, 255, 255]);
    });
});
