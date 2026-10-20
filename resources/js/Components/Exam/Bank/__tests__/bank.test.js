import { describe, expect, it } from 'vitest';
import { hasCorrectAnswer, reviewKeyAction, topicOptions, unitPresetRows } from '../bank';

const key = (k, extra = {}) => ({ key: k, target: { tagName: 'BODY' }, ...extra });

describe('reviewKeyAction', () => {
    it('maps 1–5 to options A–E and the action keys', () => {
        expect(reviewKeyAction(key('1'))).toEqual({ action: 'mark', index: 0 });
        expect(reviewKeyAction(key('5'))).toEqual({ action: 'mark', index: 4 });
        expect(reviewKeyAction(key('6'))).toBeNull();
        expect(reviewKeyAction(key('Enter'))).toEqual({ action: 'approve' });
        expect(reviewKeyAction(key('R'))).toEqual({ action: 'reject' });
        expect(reviewKeyAction(key('n'))).toEqual({ action: 'next' });
        expect(reviewKeyAction(key('ArrowRight'))).toEqual({ action: 'next' });
        expect(reviewKeyAction(key('e'))).toEqual({ action: 'edit' });
    });

    it('ignores typing, modifiers and Enter on a focused button', () => {
        expect(reviewKeyAction(key('1', { target: { tagName: 'TEXTAREA' } }))).toBeNull();
        expect(reviewKeyAction(key('r', { target: { tagName: 'INPUT' } }))).toBeNull();
        expect(reviewKeyAction(key('2', { target: { tagName: 'DIV', isContentEditable: true } }))).toBeNull();
        expect(reviewKeyAction(key('1', { ctrlKey: true }))).toBeNull();
        expect(reviewKeyAction(key('Enter', { target: { tagName: 'BUTTON' } }))).toBeNull();
        expect(reviewKeyAction(key('3', { target: { tagName: 'BUTTON' } }))).toEqual({ action: 'mark', index: 2 });
    });
});

describe('hasCorrectAnswer', () => {
    const opts = (...marks) => marks.map((is_correct) => ({ is_correct }));
    it('mirrors the approval guard', () => {
        expect(hasCorrectAnswer({ question_type: 'single_choice', options: opts(null, null, null, null) })).toBe(false);
        expect(hasCorrectAnswer({ question_type: 'single_choice', options: opts(false, true, false, false) })).toBe(true);
        expect(hasCorrectAnswer({ question_type: 'single_choice', options: opts(true, true, false, false) })).toBe(false);
        expect(hasCorrectAnswer({ question_type: 'multiple_choice', options: opts(true, true, null, null) })).toBe(true);
        expect(hasCorrectAnswer({ question_type: 'numeric', answer: { value: '' } })).toBe(false);
        expect(hasCorrectAnswer({ question_type: 'numeric', answer: { value: '0' } })).toBe(true);
        expect(hasCorrectAnswer({ question_type: 'numeric_with_unit', answer: { value: '20', unit: '' } })).toBe(false);
        expect(hasCorrectAnswer({ question_type: 'numeric_with_unit', answer: { value: '20', unit: 'm/s' } })).toBe(true);
    });
});

describe('unitPresetRows / topicOptions', () => {
    it('suggests factors to the canonical unit, skipping listed ones', () => {
        const rows = unitPresetRows('m/s', [{ unit: 'km/h' }]);
        expect(rows.map((r) => r.unit)).toEqual(['km/soat', 'sm/s']);
        expect(rows[0].factor * 72).toBeCloseTo(20, 8);
        expect(unitPresetRows('furlong')).toEqual([]);
    });

    it('filters by subject, indents by level, keeps the current inactive topic', () => {
        const topics = [
            { id: 'a', subject_id: 'm', name_uz: 'Tenglamalar', level: 1, is_active: true },
            { id: 'b', subject_id: 'm', name_uz: 'Kvadrat', level: 2, is_active: false },
            { id: 'c', subject_id: 'p', name_uz: 'Kinematika', level: 1, is_active: true },
        ];
        expect(topicOptions(topics, 'm').map((o) => o.value)).toEqual(['a']);
        const kept = topicOptions(topics, 'm', { keep: 'b' });
        expect(kept.map((o) => o.value)).toEqual(['a', 'b']);
        expect(kept[1].label).toMatch(/^ +Kvadrat \(nofaol\)$/);
    });
});
