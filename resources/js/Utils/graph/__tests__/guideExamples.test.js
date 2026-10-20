import { describe, expect, it } from 'vitest';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { splitGraphBlocks } from '../../richText';
import { functionPaths } from '../spec';

const guide = readFileSync(fileURLToPath(new URL('../../../../../docs/exam/RASM_VA_GRAFIK.md', import.meta.url)), 'utf8');

describe('teacher guide graph examples (docs/exam/RASM_VA_GRAFIK.md)', () => {
    const graphs = splitGraphBlocks(guide).filter((p) => p.type === 'graph');

    it('all parse and validate on the client too', () => {
        expect(graphs.length).toBeGreaterThanOrEqual(6);
        for (const g of graphs) expect(g.errors, g.source).toEqual([]);
    });

    it('the hyperbola is drawn in two branches', () => {
        const hyper = graphs.find((g) => g.source.includes('1/(x-1)+2'));
        const [{ pieces }] = functionPaths(hyper.spec);
        expect(pieces).toHaveLength(2);
    });
});
