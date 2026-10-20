import { describe, expect, it } from 'vitest';
import { escapeHtml, hasMath, mathTextHtml } from '../mathText';

describe('mathText', () => {
    it('renders inline and display formulas with KaTeX', () => {
        const out = mathTextHtml('$\sin 60^\circ$ qiymati?\n$$\frac{\sqrt3}{2}$$');
        expect(out).toContain('class="katex"');
        expect(out).toContain('katex-display');
        expect(out).not.toContain('@@MATH');
        expect(out).toContain(' qiymati?\n');
    });

    it('escapes plain text so a question cannot inject HTML', () => {
        const out = mathTextHtml('<img src=x onerror=alert(1)> 2 < 3 & $x$');
        expect(out).not.toContain('<img');
        expect(out).toContain('&lt;img');
        expect(out).toContain('2 &lt; 3 &amp; ');
        expect(out).toContain('class="katex"');
    });

    it('leaves text without formulas as typed (money-like dollars too)', () => {
        expect(hasMath('Narxi 5$ va 10$')).toBe(false);
        expect(mathTextHtml('Narxi 5$ va 10$')).toBe('Narxi 5$ va 10$');
        expect(hasMath('Tezlik $v=s/t$')).toBe(true);
    });

    it('passes HTML content through the given sanitizer', () => {
        const out = mathTextHtml('<p>Kuch $F=ma$</p><script>x</script>', (h) => h.replace(/<script>.*?<\/script>/g, ''));
        expect(out).toContain('<p>Kuch ');
        expect(out).not.toContain('<script>');
        expect(escapeHtml('"\'')).toBe('&quot;&#39;');
    });
});
