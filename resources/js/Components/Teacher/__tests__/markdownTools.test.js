import { describe, expect, it } from 'vitest';
import { insertBlockFormula, prefixLines, wrapSelection } from '../markdownTools';

describe('markdownTools', () => {
    it('wraps the selection and keeps it selected', () => {
        const r = wrapSelection('Tezlik v', 7, 8, '$');
        expect(r.text).toBe('Tezlik $v$');
        expect(r.text.slice(r.start, r.end)).toBe('v');
    });

    it('inserts a selected placeholder when nothing is selected', () => {
        const r = wrapSelection('Kuch ', 5, 5, '**', '**', 'qalin matn');
        expect(r.text).toBe('Kuch **qalin matn**');
        expect(r.text.slice(r.start, r.end)).toBe('qalin matn');
    });

    it('prefixes every selected line (bullets and numbers)', () => {
        expect(prefixLines('a\nb\nc', 0, 3, '- ').text).toBe('- a\n- b\nc');
        expect(prefixLines('x\na\nb', 2, 5, '1. ').text).toBe('x\n1. a\n2. b');
    });

    it('puts a display formula on its own line', () => {
        const r = insertBlockFormula('Nyuton:', 7, 7);
        expect(r.text).toBe('Nyuton:\n$$F = ma$$\n');
        expect(r.text.slice(r.start, r.end)).toBe('F = ma');
    });
});
