import { describe, expect, it } from 'vitest';
import { compileExpr, exprError, parseExpr } from '../expr';

const at = (src, x) => compileExpr(src)(x);

describe('graph expression parser', () => {
    it('follows precedence: powers before signs, right-associative ^', () => {
        expect(at('x^2-2', 3)).toBe(7);
        expect(at('-x^2', 3)).toBe(-9);
        expect(at('2^3^2', 0)).toBe(512);
        expect(at('2^-1', 0)).toBe(0.5);
        expect(at('1-2-3', 0)).toBe(-4);
        expect(at('8/2/2', 0)).toBe(2);
    });

    it('supports implicit multiplication', () => {
        expect(at('2x', 4)).toBe(8);
        expect(at('3(x+1)', 1)).toBe(6);
        expect(at('(x-1)(x+1)', 3)).toBe(8);
        expect(at('2sin(x)', Math.PI / 2)).toBeCloseTo(2);
        expect(at('2pix', 1)).toBeCloseTo(2 * Math.PI);
        expect(at('xsin(x)', Math.PI / 2)).toBeCloseTo(Math.PI / 2);
    });

    it('knows functions, constants, abs bars and two-argument min / max', () => {
        expect(at('sqrt(x)', 9)).toBe(3);
        expect(at('abs(x)', -2)).toBe(2);
        expect(at('|x-3|', 1)).toBe(2);
        expect(at('2|x|', -3)).toBe(6);
        expect(at('ln(e)', 0)).toBeCloseTo(1);
        expect(at('log(100)', 0)).toBeCloseTo(2);
        expect(at('exp(0)', 0)).toBe(1);
        expect(at('cos(pi)', 0)).toBeCloseTo(-1);
        expect(at('max(x, 2)', 1)).toBe(2);
        expect(at('min(x, 2)', 1)).toBe(1);
        expect(at('5t', 2)).toBe(10); // t is the same variable (physics: v(t))
        expect(at('x^(1/3)', -8)).toBeCloseTo(-2);
    });

    it('returns NaN outside the domain instead of throwing', () => {
        expect(at('sqrt(x)', -1)).toBeNaN();
        expect(at('ln(x)', -1)).toBeNaN();
        expect(at('1/x', 0)).toBe(Infinity);
    });

    it('rejects anything that is not math (no code execution)', () => {
        for (const bad of ['alert(1)', 'constructor', 'x;1', 'window.x', '__proto__', 'x=1', '[1]', '"a"', 'sin x', '(x', 'x)', '', 'max(1)', 'sqrt(1,2)']) {
            expect(exprError(bad), bad).not.toBeNull();
        }
        expect(() => parseExpr('x'.repeat(300))).toThrow();
        expect(() => parseExpr('('.repeat(60) + 'x' + ')'.repeat(60))).toThrow(/chuqur/);
    });

    it('accepts typical teacher input', () => {
        for (const ok of ['x^2-2', '2*x+1', '-0.5x^2+3', 'sin(x)', 'tan(x)', '1/x', '3e2*x', '10 - 9.8t', '(x+1)/(x-1)', 'x·2', '−x']) {
            expect(exprError(ok), ok).toBeNull();
        }
    });
});
