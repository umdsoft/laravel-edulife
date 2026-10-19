import { beforeEach, describe, expect, it } from 'vitest';
import { clearToasts, pushFlash, useToast } from '../useToast.js';

describe('useToast store', () => {
    beforeEach(() => clearToasts());

    it('pushes typed toasts and dismisses them', () => {
        const toast = useToast();
        const id = toast.success('Saqlandi');
        expect(toast.toasts).toHaveLength(1);
        expect(toast.toasts[0]).toMatchObject({ type: 'success', message: 'Saqlandi' });
        toast.dismiss(id);
        expect(toast.toasts).toHaveLength(0);
    });

    it('ignores empty messages and de-duplicates identical ones', () => {
        const toast = useToast();
        toast.info('');
        toast.error('Xato');
        toast.error('Xato');
        expect(toast.toasts).toHaveLength(1);
    });

    it('maps flash props to toasts and caps the stack', () => {
        const toast = useToast();
        pushFlash({ success: 'OK', error: 'Bad', warning: null });
        expect(toast.toasts.map((t) => t.type)).toEqual(['success', 'error']);
        for (let i = 0; i < 10; i++) toast.info(`m${i}`);
        expect(toast.toasts.length).toBeLessThanOrEqual(4);
    });

    it('falls back to info for unknown types', () => {
        const toast = useToast();
        toast.push({ type: 'weird', message: 'x' });
        expect(toast.toasts[0].type).toBe('info');
    });
});
