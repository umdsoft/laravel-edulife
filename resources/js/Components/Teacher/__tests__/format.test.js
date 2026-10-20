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

import { editorShortcut, questionDifficulty } from '../format';

describe('editorShortcut', () => {
    it('maps Ctrl/Cmd+S and Ctrl+Enter to save, Ctrl+Shift+Enter to save-and-new', () => {
        expect(editorShortcut({ ctrlKey: true, key: 's' })).toBe('save');
        expect(editorShortcut({ metaKey: true, key: 'S' })).toBe('save');
        expect(editorShortcut({ ctrlKey: true, key: 'Enter' })).toBe('save');
        expect(editorShortcut({ ctrlKey: true, shiftKey: true, key: 'Enter' })).toBe('save-and-new');
    });

    it('ignores plain keys and AltGr combinations', () => {
        expect(editorShortcut({ key: 's' })).toBeNull();
        expect(editorShortcut({ ctrlKey: true, altKey: true, key: 's' })).toBeNull();
        expect(editorShortcut({ ctrlKey: true, key: 'b' })).toBeNull();
    });
});

describe('questionDifficulty', () => {
    it('labels the three levels and falls back to medium', () => {
        expect(questionDifficulty('hard').label).toBe('Qiyin');
        expect(questionDifficulty(null).value).toBe('medium');
    });
});
