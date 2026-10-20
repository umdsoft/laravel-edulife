import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const read = (path) => readFileSync(fileURLToPath(new URL(`../../../${path}`, import.meta.url)), 'utf8');

// QA3-S-06: the catalog's "Saralash" select was blank without a query string
describe('course filters', () => {
    it('does not read Array.prototype.sort from an empty filters array', () => {
        const src = read('Components/Student/CourseFilters.vue');
        expect(src).not.toMatch(/props\.filters\.sort/);
        expect(src).toContain("sort: initial('sort', 'popular')");
        expect(typeof [].sort).toBe('function'); // why `filters.sort || 'popular'` picked a function
    });
});
