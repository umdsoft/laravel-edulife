import { describe, expect, it } from 'vitest';
import { cleanFilters } from '../useServerFilters';

describe('cleanFilters', () => {
    it('keeps only active filters', () => {
        expect(cleanFilters({ search: '  ali ', status: '', course: null, type: undefined, rating: 5 }))
            .toEqual({ search: 'ali', rating: 5 });
    });

    it('drops the page so a new filter starts from page 1', () => {
        expect(cleanFilters({ page: 4, status: 'active' })).toEqual({ status: 'active' });
    });

    it('drops false flags and handles empty input', () => {
        expect(cleanFilters({ unanswered: false, pinned: true })).toEqual({ pinned: true });
        expect(cleanFilters(undefined)).toEqual({});
    });
});
