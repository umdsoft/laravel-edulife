import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import AnnouncementCard from '@/Components/Student/AnnouncementCard.vue';

const announcement = (overrides = {}) => ({
    title: 'Muhim xabar',
    content: '<img src=x onerror="alert(1)"><b>bold</b>\nSecond line',
    published_at: '2025-01-01T00:00:00Z',
    is_pinned: false,
    teacher: { first_name: 'Ali', last_name: 'Valiyev', avatar_url: null },
    ...overrides,
});

describe('Student AnnouncementCard', () => {
    it('renders teacher-authored content as text, not HTML', () => {
        const wrapper = mount(AnnouncementCard, { props: { announcement: announcement() } });
        const body = wrapper.find('.whitespace-pre-line');

        expect(body.text()).toContain('<img src=x onerror="alert(1)"><b>bold</b>');
        expect(body.find('img').exists()).toBe(false);
        expect(body.find('b').exists()).toBe(false);
        expect(body.html()).toContain('&lt;img');
    });

    it('does not crash when the teacher relation is missing', () => {
        const wrapper = mount(AnnouncementCard, { props: { announcement: announcement({ teacher: null }) } });
        expect(wrapper.text()).toContain('Muhim xabar');
    });
});
