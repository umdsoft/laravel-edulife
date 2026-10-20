import { describe, expect, it } from 'vitest';
import { compileExpr } from '../expr';
import { formatTick, functionPaths, niceStep, parseGraphBlock, sampleFunction, ticks, validateGraphSpec } from '../spec';

const base = { x: [-5, 5], y: [-5, 5] };

describe('graph spec validation', () => {
    it('accepts the design example and a full geometry / physics spec', () => {
        expect(validateGraphSpec({ x: [-5, 5], y: [-3, 9], grid: true, functions: [{ expr: 'x^2-2', label: 'f(x)' }], points: [{ x: 2, y: 2, label: 'A' }], axes: { x: 't, s', y: 'v, m/s' } })).toEqual([]);
        expect(validateGraphSpec({
            ...base, equal: true, ticks: [1, 2], width: 60,
            polylines: [{ points: [[0, 0], [2, 4], [5, 4]], label: 'v(t)', color: 'red' }],
            segments: [{ from: [0, 0], to: [3, 0], dashed: true }],
            vectors: [{ from: [0, 0], to: [2, 3], label: 'F' }],
            polygons: [{ points: [[0, 0], [4, 0], [0, 3]], fill: true }],
            circles: [{ center: [0, 0], r: 2 }],
            labels: [{ x: 1, y: 1, text: 'O' }],
            angles: [{ vertex: [0, 0], from: [4, 0], to: [0, 3], right: true }],
        })).toEqual([]);
    });

    it('reports bad ranges, unknown keys, bad expressions and colours', () => {
        expect(validateGraphSpec({ x: [5, -5], y: [0, 1] }).join()).toMatch(/"x" oralig'i/);
        expect(validateGraphSpec({ ...base, script: 'x' }).join()).toMatch(/Noma'lum kalit: "script"/);
        expect(validateGraphSpec({ ...base, functions: [{ expr: 'alert(1)' }] }).join()).toMatch(/Funksiya 1/);
        expect(validateGraphSpec({ ...base, points: [{ x: 1, y: 1, color: 'javascript:red' }] }).join()).toMatch(/rang/);
        expect(validateGraphSpec({ ...base, ticks: 0.001 }).join()).toMatch(/juda kichik/);
        expect(validateGraphSpec({ ...base, labels: [{ x: 0, y: 0, text: 'a'.repeat(41) }] }).join()).toMatch(/40 belgi/);
        expect(validateGraphSpec({ ...base, points: Array.from({ length: 51 }, () => ({ x: 0, y: 0 })) }).join()).toMatch(/50 ta/);
        expect(validateGraphSpec([1, 2])).toHaveLength(1);
    });

    it('parses the JSON of a block', () => {
        expect(parseGraphBlock('{"x":[-1,1],"y":[-1,1]}').errors).toEqual([]);
        expect(parseGraphBlock('{x:[-1,1]}').errors[0]).toMatch(/JSON/);
        expect(parseGraphBlock('{"x":[-1,1],"y":[-1,1]}').spec).not.toBeNull();
    });
});

describe('sampling', () => {
    const maxStep = (pieces) => Math.max(...pieces.flatMap((p) => p.slice(1).map(([, y], i) => Math.abs(y - p[i][1]))));

    it('draws a parabola as one piece', () => {
        const pieces = sampleFunction(compileExpr('x^2-2'), [-5, 5], [-3, 9]);
        expect(pieces).toHaveLength(1);
        expect(pieces[0][0]).toEqual([-5, 23]);
    });

    it('breaks 1/x at 0 (no vertical spike)', () => {
        const pieces = sampleFunction(compileExpr('1/x'), [-5, 5], [-5, 5], 401);
        expect(pieces.length).toBe(2);
        expect(pieces[0].every(([x]) => x < 0)).toBe(true);
        expect(pieces[1].every(([x]) => x > 0)).toBe(true);
    });

    it('breaks tan x at each asymptote', () => {
        const pieces = sampleFunction(compileExpr('tan(x)'), [-5, 5], [-4, 4]);
        // asymptotes at ±π/2, ±3π/2 → 5 branches, each one increasing (no +∞ → −∞ jump inside a piece)
        expect(pieces.length).toBe(5);
        for (const p of pieces) {
            for (let i = 1; i < p.length; i++) expect(p[i][1]).toBeGreaterThanOrEqual(p[i - 1][1]);
        }
    });

    it('breaks step functions and skips undefined parts', () => {
        expect(sampleFunction(compileExpr('floor(x)'), [0, 3.5], [-1, 4]).length).toBe(4);
        const sq = sampleFunction(compileExpr('sqrt(x)'), [-5, 5], [-1, 3]);
        expect(sq).toHaveLength(1);
        expect(sq[0][0][0]).toBeGreaterThanOrEqual(0);
    });

    it('keeps steep but continuous curves joined', () => {
        const pieces = sampleFunction(compileExpr('exp(x)'), [-5, 5], [-1, 10]);
        expect(pieces).toHaveLength(1);
        expect(maxStep(pieces)).toBeLessThan(200);
    });

    it('applies a function domain', () => {
        const [{ pieces }] = functionPaths({ x: [-5, 5], y: [-5, 5], functions: [{ expr: 'x', domain: [0, 2] }] });
        expect(pieces[0][0][0]).toBe(0);
        expect(pieces[0][pieces[0].length - 1][0]).toBe(2);
    });
});

describe('ticks', () => {
    it('picks nice steps and formats labels', () => {
        expect(niceStep(10, 10)).toBe(1);
        expect(niceStep(100, 8)).toBe(10);
        expect(niceStep(1, 8)).toBe(0.1);
        expect(ticks(-1, 1, 0.5)).toEqual([-1, -0.5, 0, 0.5, 1]);
        expect(ticks(0.1, 0.35, 0.1)).toEqual([0.1, 0.2, 0.3]);
        expect(formatTick(-2.5)).toBe('−2,5');
        expect(formatTick(0.30000000000000004)).toBe('0,3');
    });
});
