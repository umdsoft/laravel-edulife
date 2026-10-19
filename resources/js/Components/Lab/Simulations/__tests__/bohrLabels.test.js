import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

// QA2-S-19: the Bohr model canvas showed English "Jump" / "→ Go" buttons
describe('Bohr model simulation labels', () => {
    const source = readFileSync(fileURLToPath(new URL('../BohrModelSimulation.vue', import.meta.url)), 'utf8');
    const canvasTexts = [...source.matchAll(/fillText\(([^;]+)\);/g)].map((m) => m[1]).join('\n');

    it('draws Uzbek transition labels', () => {
        expect(canvasTexts).toContain('Tushish');
        expect(canvasTexts).toContain('Sakrash');
    });

    it('draws no English words', () => {
        expect(canvasTexts).not.toMatch(/\b(Jump|Go|Atom|Model|Energy levels)\b/);
    });
});
