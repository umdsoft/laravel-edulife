import { describe, it, expect } from 'vitest';
import * as W from '../waves.js';

const PI = Math.PI;
/** relative closeness */
const rel = (a, b, tol = 1e-9) => expect(Math.abs(a - b)).toBeLessThanOrEqual(tol * Math.max(1, Math.abs(b)));

describe('advanceClock (frame-rate independent time)', () => {
    it('first frame only records the timestamp', () => {
        expect(W.advanceClock({ t: 0, lastMs: null }, 1000, true)).toEqual({ t: 0, lastMs: 1000 });
    });
    it('advances by real elapsed time when running, holds when paused', () => {
        rel(W.advanceClock({ t: 1, lastMs: 1000 }, 1016, true).t, 1.016);
        expect(W.advanceClock({ t: 1, lastMs: 1000 }, 1016, false).t).toBe(1);
    });
    it('caps a single step (backgrounded tab)', () => {
        rel(W.advanceClock({ t: 0, lastMs: 0 }, 5000, true).t, 0.1);
    });
    it('30 fps and 144 fps give the same elapsed time after 1 s', () => {
        const run = (fps) => {
            let c = { t: 0, lastMs: null };
            for (let i = 0; i <= fps; i++) c = W.advanceClock(c, (i * 1000) / fps, true);
            return c.t;
        };
        rel(run(30), 1, 1e-12);
        rel(run(144), 1, 1e-12);
    });
});

describe('basic wave kinematics', () => {
    it('v = fλ, λ = v/f, f = v/λ', () => {
        expect(W.waveSpeed(2, 0.5)).toBe(1);
        rel(W.wavelengthFrom(343, 440), 0.7795454545); // 343/440
        rel(W.frequencyFrom(343, 0.78), 439.7435897); // 343/0.78
    });
    it('T = 1/f, ω = 2πf, k = 2π/λ', () => {
        expect(W.period(4)).toBe(0.25);
        rel(W.angularFrequency(1), 2 * PI);
        rel(W.waveNumber(0.5), 4 * PI);
    });
    it('edge cases', () => {
        expect(W.period(0)).toBe(Infinity);
        expect(W.period(-1)).toBeNaN();
        expect(W.wavelengthFrom(343, 0)).toBeNaN();
        expect(W.waveNumber(0)).toBeNaN();
        expect(W.waveSpeed(NaN, 1)).toBeNaN();
    });
    it('y = A sin(kx − ωt) travels toward +x', () => {
        const p = { amplitude: 2, frequency: 1, wavelength: 4 }; // v = 4 m/s
        rel(W.travelingWave(1, 0, p), 2); // crest at x = 1
        rel(W.travelingWave(2, 0.25, p), 2); // crest moved v·t = 1 m to +x
        expect(Math.abs(W.travelingWave(1, 0.25, p))).toBeLessThan(1e-12);
    });
    it('direction −1 travels toward −x, phase φ shifts', () => {
        const p = { amplitude: 2, frequency: 1, wavelength: 4, direction: -1 };
        rel(W.travelingWave(0, 0.25, p), 2); // crest from x=1 moved to x=0
        rel(W.travelingWave(0, 0, { amplitude: 1, frequency: 1, wavelength: 1, phase: PI / 2 }), 1);
    });
});

describe('sound', () => {
    it('v ≈ 331 + 0.6·T(°C)', () => {
        expect(W.speedOfSoundAirApprox(20)).toBe(343);
        expect(W.speedOfSoundAirApprox(0)).toBe(331);
        expect(W.speedOfSoundAirApprox(-300)).toBeNaN();
    });
    it('v = √(γRT/M): 331.3 m/s at 0 °C, 343.2 m/s at 20 °C', () => {
        expect(W.speedOfSoundIdealGas(273.15)).toBeCloseTo(331.3, 1);
        expect(W.speedOfSoundIdealGas(293.15)).toBeCloseTo(343.2, 1);
        expect(W.speedOfSoundIdealGas(0)).toBeNaN();
    });
    it('β = 10 log10(I/I0), I0 = 1e-12 W/m²', () => {
        expect(W.soundIntensityLevel(1e-12)).toBe(0);
        rel(W.soundIntensityLevel(1e-6), 60);
        rel(W.soundIntensityLevel(1), 120);
        expect(W.soundIntensityLevel(0)).toBe(-Infinity);
        expect(W.soundIntensityLevel(-1)).toBeNaN();
        rel(W.intensityFromLevel(60), 1e-6);
        // doubling intensity adds 10·log10(2) ≈ 3.01 dB
        rel(W.soundIntensityLevel(2e-6) - W.soundIntensityLevel(1e-6), 3.0103, 1e-4);
    });
    it('inverse-square law I = P/(4πr²)', () => {
        rel(W.intensityAtDistance(4 * PI, 1), 1);
        rel(W.intensityAtDistance(100, 10), 0.0795774715); // 100/(400π)
        rel(W.intensityAtDistance(100, 1) / W.intensityAtDistance(100, 2), 4);
        expect(W.intensityAtDistance(100, 0)).toBeNaN();
        // −6.02 dB per doubling of distance
        rel(W.soundIntensityLevel(W.intensityAtDistance(1, 2)) - W.soundIntensityLevel(W.intensityAtDistance(1, 1)), -6.0206, 1e-4);
    });
    it('pressure is 90° out of phase with displacement (Δp = −B ∂s/∂x)', () => {
        const p = { amplitude: 1, frequency: 1, wavelength: 1 };
        const a = W.soundWaveState(0, 0, p); // ∂s/∂x > 0 → rarefaction
        expect(a.displacement).toBeCloseTo(0, 12);
        expect(a.pressure).toBeCloseTo(-1, 12);
        const b = W.soundWaveState(0.5, 0, p); // ∂s/∂x < 0 → compression
        expect(b.pressure).toBeCloseTo(1, 12);
        const c = W.soundWaveState(0.25, 0, p); // max displacement, Δp = 0
        expect(c.displacement).toBeCloseTo(1, 12);
        expect(c.pressure).toBeCloseTo(0, 12);
    });
});

describe('Doppler f\' = f (v + vo)/(v − vs)', () => {
    const f = 440, v = 343;
    it('no motion → no shift', () => expect(W.dopplerFrequency(f, v, 0, 0)).toBe(440));
    it('source approaching / receding', () => {
        rel(W.dopplerFrequency(f, v, 0, 30), 482.1725240); // 440·343/313
        rel(W.dopplerFrequency(f, v, 0, -30), 404.6112601); // 440·343/373
    });
    it('observer approaching / receding', () => {
        rel(W.dopplerFrequency(f, v, 30, 0), 478.4839650); // 440·373/343
        rel(W.dopplerFrequency(f, v, -30, 0), 401.5160350); // 440·313/343
    });
    it('both approaching', () => rel(W.dopplerFrequency(f, v, 20, 20), 494.4891641)); // 440·363/323
    it('textbook: 700 Hz siren at 30 m/s toward observer, v = 340 → 767.7 Hz', () => {
        expect(W.dopplerFrequency(700, 340, 0, 30)).toBeCloseTo(767.742, 3);
    });
    it('moving source shifts more than moving observer at the same speed', () => {
        expect(W.dopplerFrequency(f, v, 0, 50)).toBeGreaterThan(W.dopplerFrequency(f, v, 50, 0));
    });
    it('supersonic / sonic source and runaway observer → NaN; observer at −v → 0', () => {
        expect(W.dopplerFrequency(f, v, 0, 343)).toBeNaN();
        expect(W.dopplerFrequency(f, v, 0, 400)).toBeNaN();
        expect(W.dopplerFrequency(f, v, -400, 0)).toBeNaN();
        expect(W.dopplerFrequency(f, v, -343, 0)).toBe(0);
        expect(W.dopplerFrequency(f, 0, 0, 0)).toBeNaN();
    });
    it('Mach number and cone angle sin α = v/vs', () => {
        expect(W.machNumber(686, 343)).toBe(2);
        rel(W.machConeHalfAngle(686, 343), PI / 6);
        expect(W.machConeHalfAngle(100, 343)).toBeNaN();
    });
    it('λ_front = (v − vs)/f, λ_behind = (v + vs)/f', () => {
        const l = W.dopplerWavelengths(440, 343, 30);
        rel(l.front, 313 / 440);
        rel(l.behind, 373 / 440);
        // consistency: v / λ_front equals the observed frequency for a stationary observer
        rel(343 / l.front, W.dopplerFrequency(440, 343, 0, 30));
        expect(W.dopplerWavelengths(440, 343, 400).front).toBeNaN();
    });
    it('wavefront geometry: spacing (v − vs)T ahead and (v + vs)T behind', () => {
        const fr = W.dopplerWavefronts(2.5, { period: 1, waveSpeed: 10, sourceSpeed: 4, maxAge: 3 });
        expect(fr.map((w) => w.age)).toEqual([0.5, 1.5, 2.5]);
        expect(fr.map((w) => w.radius)).toEqual([5, 15, 25]);
        expect(fr.map((w) => w.centerOffset)).toEqual([-2, -6, -10]);
        const front = fr.map((w) => w.centerOffset + w.radius);
        const back = fr.map((w) => w.centerOffset - w.radius);
        rel(front[1] - front[0], 6); // (10 − 4)·1
        rel(back[0] - back[1], 14); // (10 + 4)·1
        expect(W.dopplerWavefronts(1, { period: 0, waveSpeed: 1, sourceSpeed: 0, maxAge: 1 })).toEqual([]);
    });
});

describe('standing waves', () => {
    it('v = √(T/μ)', () => {
        expect(W.stringWaveSpeed(100, 0.01)).toBe(100);
        expect(W.stringWaveSpeed(100, 0)).toBeNaN();
    });
    it('string / open pipe f_n = n v/(2L), λ_n = 2L/n', () => {
        expect(W.stringHarmonicFrequency(1, 0.5, 100)).toBe(100);
        expect(W.stringHarmonicFrequency(3, 0.5, 100)).toBe(300);
        rel(W.standingWaveWavelength(3, 0.5), 1 / 3);
        expect(W.openPipeFrequency(1, 0.5, 340)).toBe(340);
        // v = λ_n f_n for every mode
        for (let n = 1; n <= 6; n++) rel(W.standingWaveWavelength(n, 0.7) * W.stringHarmonicFrequency(n, 0.7, 50), 50);
    });
    it('closed pipe: odd harmonics only (2n − 1) v/(4L)', () => {
        expect(W.closedPipeFrequency(1, 0.25, 340)).toBe(340);
        expect(W.closedPipeFrequency(2, 0.25, 340)).toBe(1020);
        expect(W.closedPipeFrequency(3, 0.25, 340)).toBe(1700);
        // closed pipe fundamental is half that of an open pipe of equal length
        rel(W.closedPipeFrequency(1, 1, 340), W.openPipeFrequency(1, 1, 340) / 2);
    });
    it('invalid modes', () => {
        expect(W.stringHarmonicFrequency(0, 1, 1)).toBeNaN();
        expect(W.stringHarmonicFrequency(1.5, 1, 1)).toBeNaN();
        expect(W.closedPipeFrequency(-1, 1, 1)).toBeNaN();
        expect(W.stringNodePositions(0, 1)).toEqual([]);
    });
    it('y = A sin(nπx/L) cos(2πft): nodes, antinodes, time dependence', () => {
        const p = { n: 2, length: 1, amplitude: 1, frequency: 1 };
        rel(W.standingWaveDisplacement(0.25, 0, p), 1);
        expect(Math.abs(W.standingWaveDisplacement(0.5, 0.1, p))).toBeLessThan(1e-12);
        expect(Math.abs(W.standingWaveDisplacement(1, 0.3, p))).toBeLessThan(1e-12);
        expect(Math.abs(W.standingWaveDisplacement(0.25, 0.25, p))).toBeLessThan(1e-12);
        rel(W.standingWaveDisplacement(0.25, 0.5, p), -1);
    });
    it('node/antinode positions (n + 1 nodes, n antinodes)', () => {
        expect(W.stringNodePositions(3, 1.5)).toEqual([0, 0.5, 1, 1.5]);
        expect(W.stringAntinodePositions(3, 1.5)).toEqual([0.25, 0.75, 1.25]);
        const p = { n: 3, length: 1.5, amplitude: 1, frequency: 1 };
        for (const x of W.stringNodePositions(3, 1.5)) expect(Math.abs(W.standingWaveDisplacement(x, 0, p))).toBeLessThan(1e-12);
        for (const x of W.stringAntinodePositions(3, 1.5)) rel(Math.abs(W.standingWaveDisplacement(x, 0, p)), 1);
    });
});

describe('driven damped oscillator', () => {
    it('A(ω) = (F0/m)/√((ω0² − ω²)² + (γω)²)', () => {
        expect(W.drivenAmplitude(1, 2, 2, 0.5)).toBe(1); // 1/(γω) = 1/1
        expect(W.drivenAmplitude(1, 2, 0, 0.5)).toBe(0.25); // static F0/k = (F0/m)/ω0²
        rel(W.drivenAmplitude(1, 2, 1, 0), 1 / 3);
        expect(W.drivenAmplitude(1, 2, 2, 0)).toBe(Infinity); // undamped resonance
        expect(W.drivenAmplitude(1, 0, 1, 0.1)).toBeNaN();
    });
    it('phase lag δ: 0 → π/2 at ω0 → π', () => {
        expect(W.drivenPhaseLag(2, 0, 0.5)).toBe(0);
        rel(W.drivenPhaseLag(2, 2, 0.5), PI / 2);
        expect(W.drivenPhaseLag(2, 200, 0.5)).toBeGreaterThan(PI - 0.01);
        rel(W.normalizedPhaseLag(1, 0.2), PI / 2);
    });
    it('normalised response R = 1/√((1 − r²)² + (2ζr)²) equals A·k/F0 with γ = 2ζω0', () => {
        expect(W.normalizedResponse(1, 0.1)).toBeCloseTo(5, 12);
        expect(W.normalizedResponse(0, 0.3)).toBe(1);
        const w0 = 2 * PI * 2, zeta = 0.15;
        for (const r of [0.3, 0.9, 1, 1.4, 3]) {
            rel(W.drivenAmplitude(1, w0, r * w0, 2 * zeta * w0) * w0 * w0, W.normalizedResponse(r, zeta), 1e-12);
            rel(W.drivenPhaseLag(w0, r * w0, 2 * zeta * w0), W.normalizedPhaseLag(r, zeta), 1e-12);
        }
    });
    it('peak at f0√(1 − 2ζ²) with height 1/(2ζ√(1 − ζ²))', () => {
        rel(W.resonancePeakFrequency(2, 0.1), 1.9798989873); // 2·√0.98
        rel(W.peakNormalizedResponse(0.1), 5.0251890763); // 1/(0.2·√0.99)
        // numerical maximum agrees
        let best = 0, bestR = 0;
        for (let r = 0.9; r <= 1.1; r += 1e-5) {
            const a = W.normalizedResponse(r, 0.1);
            if (a > best) { best = a; bestR = r; }
        }
        expect(bestR).toBeCloseTo(Math.sqrt(0.98), 4);
        expect(best).toBeCloseTo(W.peakNormalizedResponse(0.1), 6);
        expect(W.resonancePeakFrequency(2, 0.8)).toBe(0);
        expect(W.peakNormalizedResponse(0.8)).toBe(1);
    });
    it('Q = 1/(2ζ), bandwidth = 2ζ f0', () => {
        expect(W.qualityFactor(0.05)).toBeCloseTo(10, 12);
        expect(W.qualityFactor(0)).toBe(Infinity);
        expect(W.halfPowerBandwidth(2, 0.1)).toBeCloseTo(0.4, 12);
    });
    it('half-power resonance band', () => {
        expect(W.isInResonanceBand(1, 0.05)).toBe(true);
        expect(W.isInResonanceBand(0.95, 0.05)).toBe(true); // R = 7.35 ≥ 10.01/√2 = 7.08
        expect(W.isInResonanceBand(1.1, 0.05)).toBe(false); // R = 4.22
        expect(W.isInResonanceBand(1, 0.8)).toBe(false); // overdamped-like: no peak
        // heavy damping ζ = 0.5: peak is at r = 0.707, not 1
        expect(W.isInResonanceBand(Math.SQRT1_2, 0.5)).toBe(true);
    });
    it('steady-state x(t) = A_st R sin(ωt − δ)', () => {
        // at r = 1, δ = π/2 → x(0) = −R
        expect(W.drivenDisplacement(0, { f0: 2, f: 2, zeta: 0.1 })).toBeCloseTo(-5, 10);
        // far below resonance x follows the force in phase: x(T/4) ≈ +A_st
        expect(W.drivenDisplacement(0.25 / 0.01, { f0: 2, f: 0.01, zeta: 0.1 })).toBeCloseTo(1, 3);
    });
});

describe('beats', () => {
    it('f_beat = |f1 − f2|, T_beat = 1/f_beat', () => {
        expect(W.beatFrequency(440, 444)).toBe(4);
        expect(W.beatFrequency(444, 440)).toBe(4);
        expect(W.beatPeriod(440, 444)).toBe(0.25);
        expect(W.beatPeriod(440, 440)).toBe(Infinity);
    });
    it('sum ≡ 2A cos(πΔf t) sin(2π f̄ t) (sum-to-product identity)', () => {
        for (const t of [0, 0.0013, 0.017, 0.1234, 0.5, 1.789]) {
            rel(W.beatSum(t, 440, 444, 0.7), W.beatProduct(t, 440, 444, 0.7), 1e-9);
        }
    });
    it('envelope bounds the sum and vanishes at t = 1/(2Δf)', () => {
        for (let t = 0; t < 0.5; t += 0.0007) {
            expect(Math.abs(W.beatSum(t, 440, 444))).toBeLessThanOrEqual(W.beatEnvelope(t, 440, 444) + 1e-12);
        }
        expect(W.beatEnvelope(0.125, 440, 444)).toBeLessThan(1e-12);
        expect(W.beatEnvelope(0, 440, 444, 0.5)).toBe(1);
        expect(W.beatEnvelope(0.25, 440, 444, 0.5)).toBeCloseTo(1, 12);
    });
});

describe('two-source interference', () => {
    const s1 = { x: -3, y: 0 }, s2 = { x: 3, y: 0 };
    it('path difference', () => {
        expect(W.pathDifference({ x: 0, y: 4 }, s1, s2)).toBe(0);
        rel(W.pathDifference({ x: 3, y: 4 }, s1, s2), Math.sqrt(52) - 4);
    });
    it('A_res = 2A|cos(πΔr/λ)|', () => {
        expect(W.twoSourceAmplitude(0, 1)).toBe(2);
        expect(W.twoSourceAmplitude(0.5, 1)).toBeLessThan(1e-12);
        rel(W.twoSourceAmplitude(1, 1), 2);
        rel(W.twoSourceAmplitude(0.25, 1), Math.SQRT2);
        expect(W.twoSourceAmplitude(1, 0)).toBeNaN();
    });
    it('conditions Δr = mλ (max), (m + ½)λ (min)', () => {
        expect(W.interferenceType(2, 1)).toBe('constructive');
        expect(W.interferenceType(-0.98, 1)).toBe('constructive');
        expect(W.interferenceType(1.5, 1)).toBe('destructive');
        expect(W.interferenceType(1.25, 1)).toBe('partial');
    });
    it('field on the perpendicular bisector is 2A sin(kr − ωt)', () => {
        const p = { s1, s2, amplitude: 1, frequency: 2, wavelength: 1.5 };
        const r = 5; // point (0,4)
        rel(W.twoSourceField(0, 4, 0.3, p), 2 * Math.sin((2 * PI / 1.5) * r - 2 * PI * 2 * 0.3), 1e-12);
        // on a nodal point Δr = λ/2 the field is zero at all times
        const q = { ...p, wavelength: 2 * (Math.sqrt(52) - 4) };
        for (const t of [0, 0.1, 0.37]) expect(Math.abs(W.twoSourceField(3, 4, t, q))).toBeLessThan(1e-12);
    });
    it('number of nodal / antinodal lines', () => {
        expect(W.nodalLineCount(1, 1)).toBe(2);
        expect(W.nodalLineCount(2.2, 1)).toBe(4);
        expect(W.nodalLineCount(0.4, 1)).toBe(0);
        expect(W.antinodalLineCount(2.2, 1)).toBe(5);
        expect(W.antinodalLineCount(1, 1)).toBe(1);
        expect(W.nodalLineCount(1, 0)).toBeNaN();
    });
});

describe('double slit', () => {
    it('Δy = λL/d', () => {
        rel(W.fringeSpacing(550e-9, 1, 1e-4), 5.5e-3);
        rel(W.fringeSpacing(700e-9, 1, 0.05e-3), 0.014);
        expect(W.fringeSpacing(550e-9, 1, 0)).toBeNaN();
    });
    it('bright/dark fringe positions (exact geometry ≈ small-angle)', () => {
        rel(W.brightFringePosition(1, 550e-9, 1, 1e-4), 5.5e-3, 1e-4);
        rel(W.darkFringePosition(0, 550e-9, 1, 1e-4), 2.75e-3, 1e-4);
        expect(W.brightFringePosition(0, 550e-9, 1, 1e-4)).toBe(0);
        // sinθ = 0.5 → θ = 30°, y = L tan30°
        rel(W.brightFringePosition(1, 500e-9, 1, 1e-6), Math.tan(PI / 6));
        expect(W.brightFringePosition(3, 500e-9, 1, 1e-6)).toBeNaN();
    });
    it('max order ⌊d/λ⌋ (θ = 90° excluded)', () => {
        expect(W.maxFringeOrder(550e-9, 1e-6)).toBe(1);
        expect(W.maxFringeOrder(500e-9, 1e-6)).toBe(1);
        expect(W.maxFringeOrder(400e-9, 1e-6)).toBe(2);
    });
    it('I = I0 cos²(πd sinθ/λ) sinc²(πa sinθ/λ)', () => {
        const p = { wavelength: 550e-9, slitSeparation: 1e-4, screenDistance: 1 };
        expect(W.doubleSlitIntensity(0, p)).toBe(1);
        expect(W.doubleSlitIntensity(W.darkFringePosition(0, 550e-9, 1, 1e-4), p)).toBeLessThan(1e-12);
        expect(W.doubleSlitIntensity(W.brightFringePosition(2, 550e-9, 1, 1e-4), p)).toBeCloseTo(1, 12);
        // with slit width a = d/5 the 1st order is reduced by sinc²(π/5) = 0.87514
        const q = { ...p, slitWidth: 2e-5 };
        expect(W.doubleSlitIntensity(W.brightFringePosition(1, 550e-9, 1, 1e-4), q)).toBeCloseTo(0.875140, 5);
        // 5th order is missing (d/a = 5)
        expect(W.doubleSlitIntensity(W.brightFringePosition(5, 550e-9, 1, 1e-4), q)).toBeLessThan(1e-12);
        expect(W.doubleSlitIntensity(0, { ...p, i0: 3 })).toBe(3);
        expect(W.doubleSlitIntensity(0, { ...p, wavelength: 0 })).toBeNaN();
    });
});

describe('EM spectrum and photons', () => {
    it('c = fλ', () => {
        rel(W.emFrequency(500e-9), 5.99584916e14);
        rel(W.emWavelength(1e9), 0.299792458);
        expect(W.emFrequency(0)).toBeNaN();
    });
    it('E = hf = hc/λ; 500 nm → 2.48 eV; 1 nm → 1240 eV', () => {
        rel(W.photonEnergyFromFrequency(1e15), 6.62607015e-19);
        rel(W.photonEnergyEV(500e-9), 2.479684, 1e-6);
        rel(W.photonEnergyEV(1e-9), 1239.842, 1e-6);
        // hand value with h = 6.626e-34, c = 2.998e8, e = 1.602e-19 agrees within 0.02 %
        rel(W.photonEnergyEV(550e-9), (6.626e-34 * 2.998e8) / (550e-9 * 1.602e-19), 2e-4);
        rel(W.photonEnergy(550e-9), W.photonEnergyFromFrequency(W.emFrequency(550e-9)), 1e-12);
    });
    it('bands are contiguous and ordered long → short λ', () => {
        for (let i = 1; i < W.EM_BANDS.length; i++) {
            expect(W.EM_BANDS[i].lambdaMax).toBe(W.EM_BANDS[i - 1].lambdaMin);
            expect(W.EM_BANDS[i].lambdaMin).toBeLessThan(W.EM_BANDS[i].lambdaMax);
        }
    });
    it('classification', () => {
        const id = (l) => W.classifyWavelength(l)?.id;
        expect(id(3)).toBe('radio');
        expect(id(0.5)).toBe('microwave');
        expect(id(1e-6)).toBe('infrared');
        expect(id(550e-9)).toBe('visible');
        expect(id(380e-9)).toBe('visible');
        expect(id(760e-9)).toBe('infrared');
        expect(id(100e-9)).toBe('ultraviolet');
        expect(id(5e-9)).toBe('xray');
        expect(id(1e-12)).toBe('gamma');
        expect(W.classifyWavelength(0)).toBeNull();
    });
    it('derived band ranges (visible 394–789 THz, 1.63–3.26 eV; X-ray 30 PHz–30 EHz)', () => {
        const vis = W.bandRanges(W.EM_BANDS.find((b) => b.id === 'visible'));
        rel(vis.fMin, 3.94464e14, 1e-5);
        rel(vis.fMax, 7.88927e14, 1e-5);
        rel(vis.eMinEV, 1.63137, 1e-5);
        rel(vis.eMaxEV, 3.26274, 1e-5);
        rel(vis.lambdaCenter, Math.sqrt(380e-9 * 760e-9));
        const x = W.bandRanges(W.EM_BANDS.find((b) => b.id === 'xray'));
        rel(x.fMin, 2.99792458e16);
        rel(x.fMax, 2.99792458e19);
        const radio = W.bandRanges(W.EM_BANDS[0]);
        expect(W.formatSI(radio.fMin, 'Hz')).toBe('3 kHz');
        expect(W.formatSI(radio.fMax, 'Hz')).toBe('300 MHz');
    });
    it('wavelength → colour', () => {
        expect(W.wavelengthToRgb(700)).toEqual([255, 0, 0]);
        expect(W.wavelengthToRgb(450)).toEqual([0, 51, 255]);
        expect(W.wavelengthToRgb(550)).toEqual([146, 255, 0]);
        expect(W.wavelengthToRgb(300)).toEqual([0, 0, 0]);
        expect(W.wavelengthToRgb(NaN)).toEqual([0, 0, 0]);
    });
});

describe('formatting', () => {
    it('formatSI', () => {
        expect(W.formatSI(4.6e14, 'Hz')).toBe('460 THz');
        expect(W.formatSI(299792458, 'Hz')).toBe('300 MHz');
        expect(W.formatSI(5.5e-3, 'm', 2)).toBe('5.5 mm');
        expect(W.formatSI(550e-9, 'm')).toBe('550 nm');
        expect(W.formatSI(999.9, 'Hz')).toBe('1 kHz');
        expect(W.formatSI(0, 'Hz')).toBe('0 Hz');
        expect(W.formatSI(NaN, 'Hz')).toBe('—');
        expect(W.formatSI(-2500, 'm')).toBe('-2.5 km');
    });
    it('roundTo', () => {
        expect(W.roundTo(482.17252, 1)).toBe(482.2);
        expect(W.roundTo(NaN)).toBeNaN();
    });
});

describe('second audit: constants come from constants.js (CODATA 2022 / SI exact)', () => {
    it('re-exported constants are the SI defining values', async () => {
        const C = await import('../constants.js');
        expect(W.SPEED_OF_LIGHT).toBe(C.SPEED_OF_LIGHT);
        expect(W.PLANCK).toBe(C.PLANCK);
        expect(W.ELEMENTARY_CHARGE).toBe(C.ELEMENTARY_CHARGE);
        expect(W.GAS_CONSTANT).toBe(C.GAS_CONSTANT);
        expect(W.SPEED_OF_LIGHT).toBe(299792458);
        // hc/e = 1239.841 98… eV·nm
        rel(W.photonEnergyEV(1e-9), C.HC_EV_NM, 1e-12);
    });
});

describe('second audit: speed of sound in air v(t) = 343·√(T/293.15 K)', () => {
    it('343 m/s exactly at the 20 °C reference', () => {
        expect(W.speedOfSoundAir()).toBe(343);
        expect(W.speedOfSoundAir(20)).toBe(343);
    });
    it('0 °C → 331.09 m/s (0.1 % below the literature 331.3–331.5), 30 °C → 348.80 m/s', () => {
        expect(W.speedOfSoundAir(0)).toBeCloseTo(343 * Math.sqrt(273.15 / 293.15), 10);
        expect(W.speedOfSoundAir(0)).toBeCloseTo(331.09, 2);
        expect(Math.abs(W.speedOfSoundAir(0) - 331.46) / 331.46).toBeLessThan(1.5e-3);
        expect(W.speedOfSoundAir(30)).toBeCloseTo(348.80, 2);
        expect(W.speedOfSoundAir(-273.15)).toBeNaN();
    });
    it('agrees with the ideal gas √(γRT/M) and the 331 + 0.6 t rule within 0.1 %', () => {
        for (const t of [-20, 0, 10, 20, 30, 40]) {
            const v = W.speedOfSoundAir(t);
            rel(v, W.speedOfSoundIdealGas(273.15 + t), 1e-3);
            rel(v, W.speedOfSoundAirApprox(t), 2e-3);
        }
    });
    it('within 0.11 % of Cramer (1993): dry air, 101.325 kPa, 400 ppm CO2', () => {
        // Cramer, J. Acoust. Soc. Am. 93, 2510 (1993), eq. (A3) — coefficients a0…a15
        const cramer = (t, p, xw, xc) => 331.5024 + 0.603055 * t - 0.000528 * t * t
            + (51.471935 + 0.1495874 * t - 0.000782 * t * t) * xw
            + (-1.82e-7 + 3.73e-8 * t - 2.93e-10 * t * t) * p
            + (-85.20931 - 0.228525 * t + 5.91e-5 * t * t) * xc
            - 2.835149 * xw * xw - 2.15e-13 * p * p + 29.179762 * xc * xc + 4.86e-4 * xw * p * xc;
        const c20 = cramer(20, 101325, 0, 0.0004);
        expect(c20).toBeCloseTo(343.36, 2);
        expect(Math.abs(W.speedOfSoundAir(20) - c20) / c20).toBeLessThan(1.1e-3);
        for (const t of [0, 10, 30]) expect(Math.abs(W.speedOfSoundAir(t) - cramer(t, 101325, 0, 0.0004)) / cramer(t, 101325, 0, 0.0004)).toBeLessThan(1.5e-3);
    });
});

describe('second audit: Melde string, ripple tank, apparatus', () => {
    it('T = μv²; consistent with v = √(T/μ)', () => {
        rel(W.stringTension(40, 1e-3), 1.6);
        rel(W.stringWaveSpeed(W.stringTension(31.6, 1e-3), 1e-3), 31.6);
        expect(W.stringTension(10, 0)).toBeNaN();
    });
    it('Melde task: L = 1 m, f₁ = 20 Hz → v = 40 m/s, f₃ = 60 Hz; f₁ = 15 Hz → f₄ = 60 Hz', () => {
        const L = W.MELDE_STRING.length;
        expect(W.stringHarmonicFrequency(3, L, 2 * L * 20)).toBe(60);
        expect(W.stringHarmonicFrequency(4, L, 2 * L * 15)).toBe(60);
        rel(W.standingWaveWavelength(3, L) * 60, 40);
    });
    it('travel time t = s/v', () => {
        expect(W.travelTime(5, 2)).toBe(2.5);
        expect(W.travelTime(5, 0)).toBeNaN();
    });
    it('ripple tank: λ = 0.20/f, N wavelengths on a 30 cm ruler (N ≤ 10)', () => {
        rel(W.wavelengthFrom(W.RIPPLE_TANK.waveSpeed, 5), 0.04);
        expect(W.rippleSpanCount(0.04)).toBe(7);
        expect(W.rippleSpanCount(0.02)).toBe(10);
        expect(W.rippleSpanCount(0.2)).toBe(1);
        expect(W.rippleSpanCount(0.1)).toBe(3);
        expect(W.rippleSpanCount(0)).toBeNaN();
    });
    it('ripple-tank depth ≈ 4 mm keeps v within 7 % of 20 cm/s for 1–10 Hz (capillary–gravity dispersion)', () => {
        const g = 9.81, sigma = 0.0728, rho = 998, h = 0.004;
        for (const f of [1, 2, 5, 10]) {
            const w = 2 * PI * f;
            let lo = 1, hi = 5000;
            for (let i = 0; i < 200; i++) {
                const k = (lo + hi) / 2;
                if ((g * k + (sigma * k ** 3) / rho) * Math.tanh(k * h) > w * w) hi = k; else lo = k;
            }
            const v = w / lo;
            expect(Math.abs(v - W.RIPPLE_TANK.waveSpeed) / W.RIPPLE_TANK.waveSpeed).toBeLessThan(0.07);
        }
    });
    it('instrument specs', () => {
        expect(W.WAVE_INSTRUMENTS.lightGateTimer.resolution).toBe(0.001);
        expect(W.WAVE_INSTRUMENTS.tape.resolution).toBe(0.001);
        expect(W.WAVE_INSTRUMENTS.displacementSensor.resolution).toBe(0.0001);
        expect(W.WAVE_INSTRUMENTS.frequencyCounter.resolution).toBe(0.1);
        expect(W.CORD.gateB - W.CORD.gateA).toBe(5);
        expect(W.RESONANCE_STATIC_AMPLITUDE).toBe(0.01);
    });
});

describe('second audit: EM bands vs ISO 21348', () => {
    const band = (id) => W.EM_BANDS.find((b) => b.id === id);
    it('visible 380–760 nm, IR 760 nm – 1 mm (ISO 21348), microwaves 1 mm – 1 m', () => {
        expect(band('visible').lambdaMin).toBe(380e-9);
        expect(band('visible').lambdaMax).toBe(760e-9);
        expect(band('infrared').lambdaMax).toBe(1e-3);
        expect(band('microwave').lambdaMax).toBe(1);
        // microwaves 300 MHz – 300 GHz
        rel(W.bandRanges(band('microwave')).fMin, 299792458);
        rel(W.bandRanges(band('microwave')).fMax, 299792458e3);
    });
    it('UV 10–380 nm, X 10 pm – 10 nm, γ < 10 pm (≈ 124 keV)', () => {
        expect(band('ultraviolet').lambdaMin).toBe(10e-9);
        expect(band('xray').lambdaMin).toBe(1e-11);
        rel(W.photonEnergyEV(1e-11), 123984.198, 1e-8);
    });
    it('seeded representative photons: 537.4 nm → 2.3071 eV, 0.31623 nm → 3920.7 eV', () => {
        rel(W.bandRanges(band('visible')).lambdaCenter, 537.401e-9, 1e-5);
        rel(W.photonEnergyEV(W.bandRanges(band('visible')).lambdaCenter), 2.3071, 5e-5);
        rel(W.photonEnergyEV(W.bandRanges(band('xray')).lambdaCenter), 3920.7, 5e-5);
    });
});
