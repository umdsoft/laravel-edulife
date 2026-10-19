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

import { axisLabelIndexes } from '../format';

describe('axisLabelIndexes', () => {
    it('labels every bar of a short chart', () => {
        expect([...axisLabelIndexes(6)]).toEqual([0, 1, 2, 3, 4, 5]);
    });

    it('spaces labels evenly from the newest bar on a 30-day chart', () => {
        const shown = [...axisLabelIndexes(30)].sort((a, b) => a - b);
        expect(shown).toContain(29);
        expect(shown.length).toBeLessThanOrEqual(8);
        for (let i = 1; i < shown.length; i++) expect(shown[i] - shown[i - 1]).toBe(4);
    });

    it('handles empty input', () => {
        expect(axisLabelIndexes(0).size).toBe(0);
    });
});
