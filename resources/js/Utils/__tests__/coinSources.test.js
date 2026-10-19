import { describe, expect, it } from 'vitest';
import { coinSourceLabel } from '../coinSources';

// QA2-S-10: the coin history showed "lab_experiment", "lab_badge" as the source
describe('coinSourceLabel', () => {
    it('names every source the server writes in Uzbek', () => {
        expect(coinSourceLabel('lab_experiment')).toBe('Laboratoriya tajribasi');
        expect(coinSourceLabel('lab_badge')).toBe('Laboratoriya nishoni');
        expect(coinSourceLabel('battle_win')).toBe("Battle g'alabasi");
        expect(coinSourceLabel('legacy_transfer')).toBe('Avvalgi balans');
    });

    it('never shows a raw code', () => {
        expect(coinSourceLabel('something_new')).toBe('Boshqa');
        expect(coinSourceLabel(null)).toBe('—');
    });
});
