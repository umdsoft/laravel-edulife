import { describe, it, expect } from 'vitest';
import {
    decimalsOf, quantize, formatReading, roundSig, seedFrom, createRng, normal, truncatedNormal,
    readingSigma, maxReadingError, reading, createInstrument, INSTRUMENTS, dmmResolution, dmmReading,
} from '../instruments.js';

describe('quantize / formatting', () => {
    it('decimalsOf', () => {
        expect(decimalsOf(0.01)).toBe(2);
        expect(decimalsOf(0.001)).toBe(3);
        expect(decimalsOf(0.5)).toBe(1);
        expect(decimalsOf(1)).toBe(0);
        expect(decimalsOf(5)).toBe(0);
        expect(decimalsOf(0.0001)).toBe(4);
        expect(decimalsOf(0)).toBe(0);
    });

    it('rounds to the scale division without float dust', () => {
        expect(quantize(0.1 + 0.2, 0.01)).toBe(0.3);
        expect(quantize(2.0147, 0.001)).toBe(2.015);
        expect(quantize(2.0147, 0.01)).toBe(2.01);
        expect(quantize(28.126, 0.5)).toBe(28);
        expect(quantize(28.26, 0.5)).toBe(28.5);
        expect(quantize(-0.4804, 0.01)).toBe(-0.48);
        expect(quantize(-0.001, 0.01)).toBe(0);
        expect(Object.is(quantize(-0.001, 0.01), -0)).toBe(false);
        expect(quantize(1.005, 0.01)).toBe(1.01); // half away from zero even for 1.005
        expect(quantize(7.3, 0)).toBe(7.3);
        expect(quantize(NaN, 0.1)).toBeNaN();
    });

    it('formatReading keeps the instrument decimals', () => {
        expect(formatReading(2, 0.01)).toBe('2.00');
        expect(formatReading(450.2, 1)).toBe('450');
        expect(formatReading(28.26, 0.5)).toBe('28.5');
        expect(formatReading(Infinity, 0.1)).toBe('—');
    });

    it('roundSig', () => {
        expect(roundSig(2.01469, 3)).toBe(2.01);
        expect(roundSig(308000.4, 3)).toBe(308000);
        expect(roundSig(0.000123456, 2)).toBe(0.00012);
        expect(roundSig(0, 3)).toBe(0);
    });
});

describe('seeded PRNG', () => {
    it('is deterministic and uniform-ish', () => {
        const a = createRng(42);
        const b = createRng(42);
        const xs = Array.from({ length: 1000 }, () => a());
        expect(xs).toEqual(Array.from({ length: 1000 }, () => b()));
        expect(Math.min(...xs)).toBeGreaterThanOrEqual(0);
        expect(Math.max(...xs)).toBeLessThan(1);
        const mean = xs.reduce((s, x) => s + x, 0) / xs.length;
        expect(Math.abs(mean - 0.5)).toBeLessThan(0.03);
    });

    it('seedFrom is stable and insensitive to float dust', () => {
        expect(seedFrom('pendulum', 0.1 + 0.2)).toBe(seedFrom('pendulum', 0.3));
        expect(seedFrom('a', 1)).not.toBe(seedFrom('a', 2));
        expect(seedFrom('x')).toBe(seedFrom('x'));
    });

    it('normal has mean 0 and σ 1; truncated never exceeds the limit', () => {
        const rng = createRng(7);
        const zs = Array.from({ length: 20000 }, () => normal(rng));
        const mean = zs.reduce((s, z) => s + z, 0) / zs.length;
        const sd = Math.sqrt(zs.reduce((s, z) => s + (z - mean) ** 2, 0) / zs.length);
        expect(Math.abs(mean)).toBeLessThan(0.03);
        expect(Math.abs(sd - 1)).toBeLessThan(0.03);
        const rng2 = createRng(8);
        for (let i = 0; i < 20000; i++) expect(Math.abs(truncatedNormal(rng2, 2))).toBeLessThanOrEqual(2);
    });
});

describe('readings', () => {
    it('σ combines absolute and relative parts', () => {
        expect(readingSigma(100, { sigmaAbs: 3, sigmaRel: 0.04 })).toBeCloseTo(5, 12);
        expect(readingSigma(5, {})).toBe(0);
    });

    it('a reading never leaves the documented band and is on the scale', () => {
        const spec = INSTRUMENTS.photogateTimer;
        const rng = createRng(1);
        for (let i = 0; i < 5000; i++) {
            const r = reading(2.0147, spec, rng);
            expect(Math.abs(r - 2.0147)).toBeLessThanOrEqual(maxReadingError(2.0147, spec) + 1e-12);
            expect(Math.abs(r * 1000 - Math.round(r * 1000))).toBeLessThan(1e-9);
        }
    });

    it('without rng only rounds', () => {
        expect(reading(2.0147, INSTRUMENTS.handStopwatch)).toBe(2.01);
    });

    it('createInstrument: the n-th reading of a set-up is reproducible, readings scatter', () => {
        const a = createInstrument(INSTRUMENTS.handStopwatch, 'pendulum');
        const b = createInstrument(INSTRUMENTS.handStopwatch, 'pendulum');
        const ra = [a.read(20.147, [1, 15]), a.read(20.147, [1, 15]), a.read(20.147, [1, 15])];
        const rb = [b.read(20.147, [1, 15]), b.read(20.147, [1, 15]), b.read(20.147, [1, 15])];
        expect(ra).toEqual(rb);
        expect(new Set(ra).size).toBeGreaterThan(1);
        a.reset();
        expect(a.read(20.147, [1, 15])).toBe(ra[0]);
        expect(a.format(2)).toBe('2.00');
    });

    it('every catalog entry has a unit, an accuracy text and a non-negative σ', () => {
        for (const [name, spec] of Object.entries(INSTRUMENTS)) {
            expect(typeof spec.accuracy, name).toBe('string');
            expect(spec.resolution ?? 0, name).toBeGreaterThanOrEqual(0);
            expect(readingSigma(1, spec), name).toBeGreaterThanOrEqual(0);
        }
    });
});

describe('digital multimeter', () => {
    it('auto-ranges a 3½-digit display', () => {
        expect(dmmResolution(0.0425)).toBe(0.0001);
        expect(dmmResolution(0.06)).toBe(0.0001);
        expect(dmmResolution(5.9)).toBe(0.01);
        expect(dmmResolution(12)).toBe(0.01);
        expect(dmmResolution(440)).toBe(1);
        expect(dmmResolution(150)).toBe(0.1);
    });

    it('reads within ±(2.5σ + ½ digit)', () => {
        const rng = createRng(3);
        for (let i = 0; i < 2000; i++) {
            const r = dmmReading(0.06, rng);
            expect(Math.abs(r - 0.06)).toBeLessThanOrEqual(0.06 * 0.0005 * 2.5 + 0.00005 + 1e-12);
        }
        expect(dmmReading(0.042483)).toBe(0.0425);
    });
});
