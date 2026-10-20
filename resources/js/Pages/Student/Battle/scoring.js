// Presentation helpers for battle levels and olympiad-style points
// (docs/battle/SCORING.md). Pure functions, so they are unit-tested.

import { BANDS, BAND_LABELS, difficultyVariant } from '@/Utils/difficulty';

// battle levels are the course-test bands of the one difficulty scale (Utils/difficulty.js)
export const LEVELS = BANDS;

export const LEVEL_LABELS = BAND_LABELS;

export const levelLabel = (level) => LEVEL_LABELS[level] || 'Aralash';

export const levelVariant = (level) => difficultyVariant(level);

/** The "automatic" choice (the Select placeholder, value null). */
export const autoLabel = (autoLevel = null) => (autoLevel ? `Avtomatik (reyting bo'yicha: ${levelLabel(autoLevel)})` : "Avtomatik (reyting bo'yicha)");

/** Lobby level options; a level without enough questions is disabled. */
export const difficultyOptions = (counts = {}, minRounds = 3) => LEVELS.map((level) => {
    const n = Number(counts?.[level] ?? 0);
    const enough = n >= minRounds;
    return {
        value: level,
        label: `${LEVEL_LABELS[level]} — ${n} ta savol${enough ? '' : ' (yetarli emas)'}`,
        disabled: !enough,
    };
});

/** "Oson: 4 · O'rta: 0 · Qiyin: 3" */
export const levelSummary = (counts = {}) => LEVELS.map((l) => `${LEVEL_LABELS[l]}: ${Number(counts?.[l] ?? 0)}`).join(' · ');

export const canPlayAnyLevel = (counts = {}, minRounds = 3) => LEVELS.some((l) => Number(counts?.[l] ?? 0) >= minRounds);

/** Who solved a round, from one player's point of view. */
export const solverLabel = (count, mineCorrect) => {
    if (!count) return 'Hech kim yechmadi';
    if (count >= 2) return 'Ikkalangiz yechdingiz';
    return mineCorrect ? 'Faqat siz yechdingiz' : 'Faqat raqib yechdi';
};

/** The share of the value one solver gets: 1, ½, … (solvers^−γ). */
export const solverShare = (count, exponent = 1) => (count > 0 ? count ** -exponent : 0);

/** "100 × (1 + 2 × (1 − p))" for the explanation note. */
export const valueFormula = (rules = {}) => {
    const base = rules.base ?? 100;
    if (rules.model === 'logit') {
        return `${base} × e^(${rules.beta ?? 0.5} × b),  b = ln((1 − p) / p)`;
    }
    return `${base} × (1 + ${rules.k ?? 2} × (1 − p))`;
};

/** "1 / 2" style label for the share at 2 solvers. */
export const sharedPercent = (rules = {}) => Math.round(solverShare(2, rules.solver_exponent ?? 1) * 100);
