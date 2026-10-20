import { describe, expect, it } from 'vitest';
import {
    BAND_OPTIONS, LEVEL_OPTIONS, bandCounts, bandLabel, bandOf, calibrationText, difficultyVariant, distributionSummary,
    isLevel, levelCountsSummary, levelLabel, levelsOf,
} from '../difficulty';

describe('difficulty scale', () => {
    it('labels the five bank levels the same way everywhere', () => {
        expect(LEVEL_OPTIONS.map((o) => o.label)).toEqual(['1 — juda oson', '2 — oson', "3 — o'rta", '4 — qiyin', '5 — juda qiyin']);
        expect(levelLabel(4)).toBe('4 — qiyin');
        expect(levelLabel('2')).toBe('2 — oson');
        expect(levelLabel(null)).toBeNull();
        expect(levelLabel(7)).toBeNull();
    });

    it('maps levels to bands: 1–2 easy, 3 medium, 4–5 hard', () => {
        expect([1, 2, 3, 4, 5].map(bandOf)).toEqual(['easy', 'easy', 'medium', 'hard', 'hard']);
        expect(bandOf(null)).toBeNull();
        expect(bandOf(true)).toBeNull();
        expect(bandOf('')).toBeNull();
    });

    it('maps bands back to levels', () => {
        expect(levelsOf('easy')).toEqual([1, 2]);
        expect(levelsOf('medium')).toEqual([3]);
        expect(levelsOf('hard')).toEqual([4, 5]);
        expect(levelsOf('x')).toEqual([]);
        // round trip
        for (const band of ['easy', 'medium', 'hard']) levelsOf(band).forEach((l) => expect(bandOf(l)).toBe(band));
    });

    it('has band labels and options for course tests and battles', () => {
        expect(BAND_OPTIONS).toEqual([
            { value: 'easy', label: 'Oson' }, { value: 'medium', label: "O'rta" }, { value: 'hard', label: 'Qiyin' },
        ]);
        expect(bandLabel('hard')).toBe('Qiyin');
        expect(bandLabel('mixed')).toBeNull();
    });

    it('validates levels strictly', () => {
        expect(isLevel(3)).toBe(true);
        expect(isLevel(' 5 ')).toBe(true);
        expect(isLevel(0)).toBe(false);
        expect(isLevel('3.5')).toBe(false);
        expect(isLevel(null)).toBe(false);
    });

    it('colours levels and bands alike', () => {
        expect(difficultyVariant(1)).toBe('success');
        expect(difficultyVariant('easy')).toBe('success');
        expect(difficultyVariant(3)).toBe('warning');
        expect(difficultyVariant(5)).toBe('danger');
        expect(difficultyVariant(null)).toBe('neutral');
    });

    it('sums level counts into bands and summarises', () => {
        expect(bandCounts({ 1: 2, 2: 3, 3: 5, 5: 1 })).toEqual({ easy: 5, medium: 5, hard: 1 });
        expect(levelCountsSummary({ 3: 12 })).toBe('1: 0 · 2: 0 · 3: 12 · 4: 0 · 5: 0');
        expect(distributionSummary({ easy: 5, medium: 10, hard: 0 })).toBe("oson 5 · o'rta 10");
    });

    it('writes the calibration hint only with data', () => {
        expect(calibrationText(null)).toBeNull();
        expect(calibrationText({ rate: 0.823, attempts: 34 })).toBe('amalda: 82% yechgan (34 ta javob)');
    });
});
