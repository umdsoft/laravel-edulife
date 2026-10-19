import { describe, expect, it } from 'vitest';
import { toAmount } from '../format';

describe('toAmount', () => {
    it('turns decimal strings into plain numbers for number inputs', () => {
        expect(toAmount('100000.00')).toBe(100000);
        expect(toAmount('80000.50')).toBe(80000.5);
        expect(toAmount(0)).toBe(0);
    });

    it('keeps empty values empty', () => {
        expect(toAmount(null)).toBe('');
        expect(toAmount(undefined)).toBe('');
        expect(toAmount('')).toBe('');
        expect(toAmount('abc')).toBe('');
    });
});
