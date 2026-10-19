import { describe, it, expect } from 'vitest';
import { cleanPath, isActivePath, isItemActive, resolveNavItems, resolveNavSections } from '../nav.js';

const routes = {
    'student.dashboard': '/student/dashboard',
    'student.courses.index': '/student/courses',
    'student.my-courses.index': '/student/my-courses',
};
const has = (name) => Object.prototype.hasOwnProperty.call(routes, name);
const path = (name) => routes[name] ?? null;

describe('cleanPath', () => {
    it('strips query, hash, trailing slash and origin', () => {
        expect(cleanPath('/student/courses/?page=2#top')).toBe('/student/courses');
        expect(cleanPath('http://edulife.test/student/lab')).toBe('/student/lab');
        expect(cleanPath('')).toBe('/');
        expect(cleanPath('/')).toBe('/');
    });
});

describe('isActivePath', () => {
    it('matches the same path and children', () => {
        expect(isActivePath('/student/courses', '/student/courses')).toBe(true);
        expect(isActivePath('/student/courses/algebra?x=1', '/student/courses')).toBe(true);
    });
    it('does not match siblings that share a prefix', () => {
        expect(isActivePath('/student/courses-old', '/student/courses')).toBe(false);
        expect(isActivePath('/student/my-courses', '/student/courses')).toBe(false);
    });
    it('respects exact', () => {
        expect(isActivePath('/student/dashboard/x', '/student/dashboard', true)).toBe(false);
        expect(isActivePath('/student/dashboard', '/student/dashboard', true)).toBe(true);
    });
    it('never treats "/" as a prefix', () => {
        expect(isActivePath('/student/lab', '/')).toBe(false);
    });
});

describe('resolveNavItems', () => {
    it('drops items whose route is missing and resolves href', () => {
        const items = resolveNavItems(
            [
                { label: 'A', route: 'student.dashboard' },
                { label: 'Missing', route: 'student.exams.index' },
                { label: 'Static', href: '/x' },
            ],
            has,
            path,
        );
        expect(items.map((i) => i.label)).toEqual(['A', 'Static']);
        expect(items[0].href).toBe('/student/dashboard');
    });

    it('picks the first existing candidate route', () => {
        const [item] = resolveNavItems([{ label: 'C', route: ['nope.index', 'student.courses.index'] }], has, path);
        expect(item.route).toBe('student.courses.index');
        expect(item.href).toBe('/student/courses');
    });

    it('drops empty sections', () => {
        const sections = resolveNavSections(
            [
                { title: 'Empty', items: [{ label: 'x', route: 'nope' }] },
                { title: 'Full', items: [{ label: 'y', route: 'student.dashboard' }] },
            ],
            has,
            path,
        );
        expect(sections.map((s) => s.title)).toEqual(['Full']);
    });
});

describe('isItemActive', () => {
    it('uses extra match prefixes', () => {
        const item = { href: '/student/my-courses', match: ['/student/learn'] };
        expect(isItemActive('/student/learn/12/lesson/3', item)).toBe(true);
        expect(isItemActive('/student/lab', item)).toBe(false);
    });
});
