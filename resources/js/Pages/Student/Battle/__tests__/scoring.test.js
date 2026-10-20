import { describe, expect, it } from 'vitest';
import {
    autoLabel, canPlayAnyLevel, difficultyOptions, levelLabel, levelSummary, sharedPercent, solverLabel, solverShare, valueFormula,
} from '../scoring';

describe('battle lobby levels', () => {
    it('disables levels without enough questions', () => {
        const options = difficultyOptions({ easy: 4, medium: 0, hard: 3 }, 3);
        expect(options.map((o) => o.value)).toEqual(['easy', 'medium', 'hard']);
        expect(options.map((o) => o.disabled)).toEqual([false, true, false]);
        expect(options[1].label).toBe("O'rta — 0 ta savol (yetarli emas)");
        expect(options[2].label).toBe('Qiyin — 3 ta savol');
        expect(autoLabel('easy')).toBe("Avtomatik (reyting bo'yicha: Oson)");
    });

    it('summarises and checks the counts', () => {
        expect(levelSummary({ easy: 4, hard: 3 })).toBe("Oson: 4 · O'rta: 0 · Qiyin: 3");
        expect(canPlayAnyLevel({ easy: 2, medium: 2, hard: 2 }, 3)).toBe(false);
        expect(canPlayAnyLevel({ medium: 3 }, 3)).toBe(true);
        expect(levelLabel('hard')).toBe('Qiyin');
        expect(levelLabel(null)).toBe('Aralash');
    });
});

describe('battle points', () => {
    it('names who solved a round', () => {
        expect(solverLabel(0, false)).toBe('Hech kim yechmadi');
        expect(solverLabel(1, true)).toBe('Faqat siz yechdingiz');
        expect(solverLabel(1, false)).toBe('Faqat raqib yechdi');
        expect(solverLabel(2, true)).toBe('Ikkalangiz yechdingiz');
    });

    it('shares the value between solvers', () => {
        expect(solverShare(1)).toBe(1);
        expect(solverShare(2)).toBe(0.5);
        expect(solverShare(0)).toBe(0);
        expect(sharedPercent({ solver_exponent: 1 })).toBe(50);
        expect(sharedPercent({ solver_exponent: 0.5 })).toBe(71);
    });

    it('writes the value formula of the active model', () => {
        expect(valueFormula({ base: 100, model: 'linear', k: 2 })).toBe('100 × (1 + 2 × (1 − p))');
        expect(valueFormula({ base: 100, model: 'logit', beta: 0.5 })).toBe('100 × e^(0.5 × b),  b = ln((1 − p) / p)');
    });
});
