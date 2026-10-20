import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { formatNumber } from '../format';

const read = (path) => readFileSync(fileURLToPath(new URL(`../../../${path}`, import.meta.url)), 'utf8');

// QA3-S-06: the header printed "1,772 XP" (uz-UZ), other pages "1 772"
describe('formatNumber', () => {
    it('groups thousands with a space, Uzbek style', () => {
        expect(formatNumber(1772)).toMatch(/^1\s772$/u);
        expect(formatNumber(1234567)).toMatch(/^1\s234\s567$/u);
        expect(formatNumber(35)).toBe('35');
        expect(formatNumber('abc')).toBe('0');
    });

    it('is what the student header uses', () => {
        const layout = read('Layouts/StudentLayout.vue');
        expect(layout).toContain("import { formatNumber } from '@/Components/Student/format'");
        expect(layout).not.toContain("'uz-UZ'");
    });
});
