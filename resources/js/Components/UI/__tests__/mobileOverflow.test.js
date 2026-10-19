import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

const read = (path) => readFileSync(fileURLToPath(new URL(`../../../${path}`, import.meta.url)), 'utf8');

// QA2-A-05: pages were wider than a 375px phone (measured with Playwright: now 375)
describe('mobile overflow guards', () => {
    it('the table scroller contains absolutely positioned cell content (sr-only labels)', () => {
        // without "relative" an sr-only span is positioned against the page and widens it
        expect(read('Components/UI/Table.vue')).toMatch(/class="relative overflow-x-auto/);
    });

    it.each([
        'Pages/Admin/Certificates/Show.vue',
        'Pages/Teacher/Dashboard.vue',
        'Pages/Teacher/Courses/Tests/Show.vue',
        'Pages/Student/Olympiad/Show.vue',
        'Pages/Admin/Subscriptions/Plans.vue',
    ])('%s lets its grid columns shrink on phones', (path) => {
        // grid-cols-1 = minmax(0, 1fr): long content cannot stretch the single column
        expect(read(path)).toMatch(/class="grid grid-cols-1 gap-/);
    });
});
