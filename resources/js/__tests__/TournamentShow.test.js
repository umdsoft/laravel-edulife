import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';

const post = vi.fn();
vi.mock('axios', () => ({ default: { post: (...args) => post(...args) } }));

const reload = vi.fn();
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, Head: { render: () => null }, router: { ...actual.router, reload: (...a) => reload(...a) } };
});

const { default: TournamentShow } = await import('@/Pages/Student/Tournaments/Show.vue');

const mountPage = () => mount(TournamentShow, {
    props: {
        tournament: { id: 't1', title: 'Kubok', status: 'upcoming', start_date: '2025-01-01', prize_pool: 100 },
        isJoined: false,
    },
    global: {
        stubs: {
            StudentLayout: { template: '<div><slot /></div>' },
            TournamentBracket: true,
        },
    },
});

describe('Student Tournaments/Show join', () => {
    beforeEach(() => {
        post.mockReset();
        reload.mockReset();
    });

    it('registers via axios (JSON endpoint) and reloads props', async () => {
        post.mockResolvedValue({ data: { success: true } });
        const wrapper = mountPage();

        await wrapper.find('button').trigger('click');
        await flushPromises();

        expect(post).toHaveBeenCalledWith('http://localhost/student/tournaments/t1/register');
        expect(reload).toHaveBeenCalled();
    });

    it('shows the server error message when registration is rejected', async () => {
        post.mockRejectedValue({ response: { status: 400, data: { message: 'Registration closed' } } });
        const wrapper = mountPage();

        await wrapper.find('button').trigger('click');
        await flushPromises();

        expect(reload).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain('Registration closed');
    });
});
