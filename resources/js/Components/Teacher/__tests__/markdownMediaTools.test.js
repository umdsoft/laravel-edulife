import { describe, expect, it } from 'vitest';
import { graphBlock, graphBlockAt, insertBlock, insertInline } from '../markdownTools';
import { altFromFileName, imageFileError } from '@/Components/Media/useImageUpload';

describe('markdownTools media helpers', () => {
    it('inserts a block on its own lines', () => {
        const r = insertBlock('Shart: rasmga qarang', 6, 6, '![a](/x.webp)');
        expect(r.text).toBe('Shart:\n![a](/x.webp)\n rasmga qarang');
        expect(insertBlock('', 0, 0, 'B').text).toBe('B');
        expect(insertBlock('a\n', 2, 2, 'B').text).toBe('a\nB');
    });

    it('inserts inline at the cursor, replacing the selection', () => {
        const r = insertInline('A) eski', 3, 7, '![g](/y.webp)');
        expect(r.text).toBe('A) ![g](/y.webp)');
        expect(r.start).toBe(r.text.length);
    });

    it('finds the graph block around the cursor', () => {
        const json = '{"x":[0,1],"y":[0,1]}';
        const text = `Savol\n${graphBlock(json)}\nJavob?`;
        const inside = text.indexOf('"x"');
        const found = graphBlockAt(text, inside);
        expect(found.source).toBe(json);
        expect(text.slice(found.start, found.end)).toBe(graphBlock(json));
        expect(graphBlockAt(text, 2)).toBeNull();
        expect(graphBlockAt(text, text.length)).toBeNull();
    });
});

describe('image upload helpers', () => {
    it('checks type and size before uploading', () => {
        expect(imageFileError({ type: 'image/svg+xml', size: 10 })).toMatch(/SVG/);
        expect(imageFileError({ type: 'image/png', size: 6 * 1024 * 1024 })).toMatch(/5 MB/);
        expect(imageFileError({ type: 'image/webp', size: 1000 })).toBeNull();
    });

    it('derives a readable alt text from the file name', () => {
        expect(altFromFileName('uchburchak_ABC.png')).toBe('uchburchak ABC');
        expect(altFromFileName('image.png')).toBe('chizma');
        expect(altFromFileName('Screenshot 2026.png')).toBe('chizma');
    });
});
