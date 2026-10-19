import { describe, expect, it } from 'vitest';
import { extractMath, injectMath, looksLikeHtml, renderRichText } from '../richText';

describe('richText', () => {
    it('renders Markdown headings, lists, bold and line breaks', () => {
        const { html } = renderRichText('# Tezlik\n\nTezlik muhim\n- birlik: m/s\n- **muhim**: vektor');
        expect(html).toContain('<h1>Tezlik</h1>');
        expect(html).toContain('<li>birlik: m/s</li>');
        expect(html).toContain('<strong>muhim</strong>');
    });

    it('turns $…$ and $$…$$ into KaTeX', () => {
        const { html, formulas } = renderRichText('Tezlik $v = s/t$ bilan topiladi.\n\n$$F = ma$$');
        expect(formulas).toHaveLength(2);
        expect(formulas.filter((f) => f.display).map((f) => f.tex)).toEqual(['F = ma']);
        const out = injectMath(html, formulas);
        expect(out).toContain('class="katex"');
        expect(out).toContain('katex-display');
        expect(out).not.toContain('@@MATH');
    });

    it('keeps math out of Markdown (underscores are not emphasis)', () => {
        const { text, formulas } = extractMath('$a_1 + a_2$ va $x_i$');
        expect(text).not.toContain('a_1');
        expect(formulas.map((f) => f.tex)).toEqual(['a_1 + a_2', 'x_i']);
    });

    it('escapes raw HTML in Markdown and leaves real HTML content as HTML', () => {
        expect(renderRichText('<script>alert(1)</script> salom').html).not.toContain('<script>');
        expect(looksLikeHtml('<p>Matn</p>')).toBe(true);
        expect(renderRichText('<p>Matn $x$</p>').html).toBe('<p>Matn @@MATH0@@</p>');
    });

    it('does not treat a lone dollar sign as math', () => {
        expect(extractMath('Narxi 5$ emas').formulas).toHaveLength(0);
    });
});
