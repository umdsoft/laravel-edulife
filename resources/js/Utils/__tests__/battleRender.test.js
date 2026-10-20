import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { richSegments } from '../richText';
import { optionsHaveMedia } from '@/Components/Media/optionMedia';

const match = readFileSync(fileURLToPath(new URL('../../Pages/Student/Battle/Match.vue', import.meta.url)), 'utf8');

describe('battle Match renders questions with the shared renderer', () => {
    it('uses MathText for the question and every option (no plain-text interpolation)', () => {
        expect(match).toMatch(/<MathText :text="currentRound\.question\.question_text"/);
        expect(match).toMatch(/<MathText :text="o\.content"/);
        expect(match).not.toContain('{{ currentRound.question.question_text }}');
        expect(match).not.toContain('{{ o.content }}');
    });

    it('turns a battle payload into KaTeX, images and graph segments', () => {
        const question = {
            question_text: 'Tezlik $v = \\frac{s}{t}$\n```graph\n{"x":[0,5],"y":[0,10],"functions":[{"expr":"2x"}]}\n```',
            options: [{ id: 1, content: '$2\\ \\mathrm{m/s}$' }, { id: 2, content: '![a](/storage/questions/2026/10/abc.webp)' }],
        };
        const segs = richSegments(question.question_text, { mode: 'plain' });
        expect(segs.map((s) => s.type)).toEqual(['html', 'graph']);
        expect(segs[0].html).toContain('class="katex"');
        expect(richSegments(question.options[0].content, { mode: 'plain' })[0].html).toContain('class="katex"');
        expect(richSegments(question.options[1].content, { mode: 'plain', zoom: 'badge' })[0].html).toContain('<img');
        expect(optionsHaveMedia(question.options)).toBe(true);
        expect(optionsHaveMedia([{ content: '$x$' }, { content: '2' }])).toBe(false);
    });
});
