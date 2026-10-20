import { describe, expect, it } from 'vitest';
import { extractImages, hasMedia, isAllowedImageUrl, mediaPlaceholderText, richSegments, splitGraphBlocks } from '../richText';

const IMG = '/storage/questions/2026/10/9b2f3c4e-1a2b-4c5d-8e9f-0a1b2c3d4e5f.webp';
const html = (text, opts) => richSegments(text, opts).filter((s) => s.type === 'html').map((s) => s.html).join('');

describe('rich text images', () => {
    it('allows only our own uploads', () => {
        expect(isAllowedImageUrl(IMG)).toBe(true);
        expect(isAllowedImageUrl('/storage/questions/2026/10/a.png')).toBe(true);
        for (const bad of [
            'https://evil.example/x.webp', '//evil.example/storage/questions/2026/10/a.webp', 'javascript:alert(1)',
            'data:image/png;base64,AAAA', '/storage/questions/2026/10/../../../.env', '/storage/avatars/2026/10/a.webp',
            '/storage/questions/2026/10/a.svg', '/storage/questions/2026/10/a.webp?x=1', 'http://localhost/storage/questions/2026/10/a.webp',
        ]) {
            expect(isAllowedImageUrl(bad), bad).toBe(false);
        }
    });

    it('renders an allowed image lazy, with alt text, width and zoom', () => {
        const out = html(`Chizmaga qarang:\n\n![Uchburchak ABC](${IMG}){width=60%}`);
        expect(out).toContain(`src="${IMG}"`);
        expect(out).toContain('alt="Uchburchak ABC"');
        expect(out).toContain('loading="lazy"');
        expect(out).toContain('style="width:60%"');
        expect(out).toContain('data-zoom="1"');
        expect(out).not.toContain('{width');
    });

    it('drops images from other hosts and gives a default alt', () => {
        expect(html('![x](https://evil.example/a.webp) matn')).not.toContain('<img');
        expect(html(`![](${IMG})`)).toContain('alt="Rasm"');
    });

    it('escapes alt text and ignores width out of range', () => {
        const out = html(`![" onerror="alert(1)<script>](${IMG}){width=500%}`);
        expect(out).not.toContain('onerror="alert');
        expect(out).toContain('&quot; onerror=&quot;alert(1)&lt;script&gt;');
        expect(out).not.toContain('style=');
    });

    it('cannot smuggle HTML through the URL', () => {
        const out = html('![a](/storage/questions/2026/10/a.webp"onload="alert(1))');
        expect(out).not.toContain('onload="alert');
    });

    it('works in plain mode (test options) with a zoom badge', () => {
        const out = html(`A) ![grafik](${IMG}){width=200px}`, { mode: 'plain', zoom: 'badge' });
        expect(out).toContain('style="width:200px"');
        expect(out).toContain('class="rt-zoom"');
        expect(out).not.toMatch(/<img[^>]*data-zoom/);
    });

    it('keeps formulas next to images', () => {
        const out = html(`$x^2$ ![a](${IMG})`);
        expect(out).toContain('class="katex"');
        expect(out).toContain('<img');
    });

    it('extracts image facts for the editor', () => {
        const { images } = extractImages(`![a](${IMG}){width=50%} ![b](https://x.y/z.png)`);
        expect(images.map((i) => [i.alt, i.allowed, i.width])).toEqual([['a', true, '50%'], ['b', false, null]]);
    });
});

describe('rich text graph blocks', () => {
    const block = '```graph\n{"x":[-5,5],"y":[-3,9],"functions":[{"expr":"x^2-2","label":"f(x)"}]}\n```';

    it('splits text around a graph block', () => {
        const parts = splitGraphBlocks(`Grafikni ko'ring:\n${block}\nQaysi nuqta?`);
        expect(parts.map((p) => p.type)).toEqual(['text', 'graph', 'text']);
        expect(parts[1].spec.functions[0].expr).toBe('x^2-2');
        expect(parts[1].errors).toEqual([]);
    });

    it('turns a bad block into errors, not into code', () => {
        const [g] = splitGraphBlocks('```graph\n{"x":[-5,5],"y":[0,1],"functions":[{"expr":"constructor.constructor(\'alert(1)\')()"}]}\n```');
        expect(g.type).toBe('graph');
        expect(g.spec).toBeNull();
        expect(g.errors.length).toBeGreaterThan(0);
    });

    it('keeps label text as data (rendered by Vue as text, never as HTML)', () => {
        const [g] = splitGraphBlocks('```graph\n{"x":[0,1],"y":[0,1],"labels":[{"x":0.5,"y":0.5,"text":"<img src=x onerror=alert(1)>"}]}\n```');
        expect(g.spec.labels[0].text).toBe('<img src=x onerror=alert(1)>');
        const segs = richSegments(`a\n${'```graph\n{"x":[0,1],"y":[0,1],"labels":[{"x":0.5,"y":0.5,"text":"<b>x</b>"}]}\n```'}`);
        expect(segs.filter((s) => s.type === 'html').map((s) => s.html).join('')).not.toContain('<b>x</b>');
    });

    it('detects media and replaces it with placeholders', () => {
        expect(hasMedia(`![a](${IMG})`)).toBe(true);
        expect(hasMedia(block)).toBe(true);
        expect(hasMedia('![a](https://evil.example/a.png)')).toBe(false);
        expect(hasMedia('$x^2$')).toBe(false);
        expect(mediaPlaceholderText(`Qarang: ![a](${IMG})\n${block}\nJavob?`)).toBe('Qarang: [rasm]\n [grafik] \nJavob?');
    });

    it('leaves a normal code block alone', () => {
        expect(splitGraphBlocks('```\n{"x":1}\n```').map((p) => p.type)).toEqual(['text']);
    });
});
