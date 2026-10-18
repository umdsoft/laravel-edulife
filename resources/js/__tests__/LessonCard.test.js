import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';

const destroy = vi.fn();
vi.mock('@inertiajs/vue3', async (importOriginal) => {
    const actual = await importOriginal();
    return { ...actual, useForm: () => ({ delete: destroy }) };
});

const { default: LessonCard } = await import('@/Components/Teacher/LessonCard.vue');

const props = {
    course: { id: 'c1' },
    module: { id: 'm1' },
    lesson: { id: 'l1', title: 'Kirish', type: 'quiz', is_free: false, is_preview: false },
};

describe('Teacher LessonCard (shallow lesson routes via Ziggy)', () => {
    beforeEach(() => destroy.mockClear());

    it('links to the shallow teacher.lessons.edit route with only the lesson id', () => {
        const wrapper = mount(LessonCard, { props });
        const hrefs = wrapper.findAll('a').map((a) => a.attributes('href'));

        expect(hrefs).toContain('http://localhost/teacher/lessons/l1/edit');
        expect(hrefs).toContain('http://localhost/teacher/courses/c1/tests/create?lesson_id=l1&type=lesson_test');
    });

    it('deletes via the shallow teacher.lessons.destroy route', async () => {
        vi.spyOn(window, 'confirm').mockReturnValue(true);
        const wrapper = mount(LessonCard, { props });

        await wrapper.find('button').trigger('click');

        expect(destroy).toHaveBeenCalledWith('http://localhost/teacher/lessons/l1');
    });
});
