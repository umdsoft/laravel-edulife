import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { nextTick } from 'vue';
import {
    __resetTelegramForTests, getWebApp, haptic, hexToTriplet, installReauthInterceptor, installTelegram, onAppVisibility,
    openLink, shareResult, themeTokens, useClosingGuard, useMainButton,
} from '../useTelegram';

function fakeDom() {
    const props = {};
    const classes = new Set();
    const listeners = {};
    globalThis.document = {
        hidden: false,
        documentElement: {
            classList: {
                add: (c) => classes.add(c),
                remove: (c) => classes.delete(c),
                toggle: (c, on) => (on ? classes.add(c) : classes.delete(c)),
                contains: (c) => classes.has(c),
            },
            style: { setProperty: (k, v) => { props[k] = v; }, colorScheme: '' },
        },
        body: { style: {} },
        querySelector: () => null,
        addEventListener: (t, fn) => { (listeners[t] = listeners[t] || []).push(fn); },
    };
    const store = {};
    globalThis.sessionStorage = { getItem: (k) => store[k] ?? null, setItem: (k, v) => { store[k] = String(v); } };
    globalThis.window = { innerHeight: 700, history: { length: 1, back: vi.fn() }, location: { origin: 'https://exam.example.uz', href: 'https://exam.example.uz/student/exam' } };
    return { props, classes, listeners };
}

function fakeWebApp(overrides = {}) {
    const events = {};
    const button = () => ({ setParams: vi.fn(), show: vi.fn(), hide: vi.fn(), showProgress: vi.fn(), hideProgress: vi.fn(), onClick: vi.fn() });
    const tg = {
        initData: 'query_id=1&user=%7B%22id%22%3A1%7D&auth_date=1&hash=x',
        version: '8.0',
        platform: 'android',
        colorScheme: 'dark',
        themeParams: { bg_color: '#212121', text_color: '#ffffff', button_color: '#8774e1', secondary_bg_color: '#181818' },
        viewportHeight: 640,
        viewportStableHeight: 600,
        safeAreaInset: { top: 0, bottom: 34, left: 0, right: 0 },
        contentSafeAreaInset: { top: 0, bottom: 0, left: 0, right: 0 },
        isVersionAtLeast: (v) => Number(v) <= 8.0 || v === '7.10',
        ready: vi.fn(),
        expand: vi.fn(),
        setHeaderColor: vi.fn(),
        setBackgroundColor: vi.fn(),
        setBottomBarColor: vi.fn(),
        enableClosingConfirmation: vi.fn(),
        disableClosingConfirmation: vi.fn(),
        enableVerticalSwipes: vi.fn(),
        disableVerticalSwipes: vi.fn(),
        openLink: vi.fn(),
        openTelegramLink: vi.fn(),
        shareToStory: vi.fn(),
        onEvent: (name, fn) => { (events[name] = events[name] || []).push(fn); },
        emit: (name) => (events[name] || []).forEach((fn) => fn()),
        BackButton: { show: vi.fn(), hide: vi.fn(), onClick: vi.fn() },
        MainButton: button(),
        HapticFeedback: { impactOccurred: vi.fn(), notificationOccurred: vi.fn(), selectionChanged: vi.fn() },
        ...overrides,
    };
    globalThis.window.Telegram = { WebApp: tg };
    return tg;
}

beforeEach(() => {
    __resetTelegramForTests();
});
afterEach(() => {
    delete globalThis.window;
    delete globalThis.document;
    delete globalThis.sessionStorage;
});

describe('outside Telegram', () => {
    it('does nothing and reports that it is not in Telegram', () => {
        fakeDom();
        expect(getWebApp()).toBeNull();
        expect(installTelegram({})).toBe(false);
        expect(openLink('https://youtube.com')).toBe(false);
        expect(shareResult('https://x/card.png')).toBe(false);
        expect(() => haptic.notify('success')).not.toThrow();
        expect(typeof useMainButton(() => ({ text: 'X' }))).toBe('function');
        expect(document.documentElement.classList.contains('tg-webview')).toBe(false);
    });

    it('a Telegram object without initData (website opened elsewhere) is not the Mini App', () => {
        fakeDom();
        fakeWebApp({ initData: '' });
        expect(getWebApp()).toBeNull();
        expect(installTelegram({})).toBe(false);
    });
});

describe('theme mapping', () => {
    it('converts hex colours to our RGB triplets', () => {
        expect(hexToTriplet('#2481cc')).toBe('36 129 204');
        expect(hexToTriplet('#fff')).toBe('255 255 255');
        expect(hexToTriplet('red')).toBeNull();
    });

    it('maps Telegram themeParams to surfaces, text and brand', () => {
        const t = themeTokens({ bg_color: '#ffffff', text_color: '#000000', button_color: '#2481cc', secondary_bg_color: '#efeff3', hint_color: '#999999' }, 'light');
        expect(t['--bg']).toBe('239 239 243');
        expect(t['--surface']).toBe('255 255 255');
        expect(t['--fg']).toBe('0 0 0');
        expect(t['--fg-subtle']).toBe('153 153 153');
        expect(t['--brand-600']).toBe('36 129 204');
        expect(themeTokens({}, 'light')).toEqual({});
    });
});

describe('inside Telegram', () => {
    it('initialises the WebApp, theme, viewport and safe area', () => {
        const dom = fakeDom();
        const tg = fakeWebApp();
        expect(installTelegram({})).toBe(true);
        expect(tg.ready).toHaveBeenCalled();
        expect(tg.expand).toHaveBeenCalled();
        expect(dom.classes.has('tg-webview')).toBe(true);
        expect(dom.classes.has('dark')).toBe(true);
        expect(dom.props['--surface']).toBe('33 33 33');
        expect(dom.props['--brand-600']).toBe('135 116 225');
        expect(dom.props['--tg-app-height']).toBe('600px');
        expect(dom.props['--tg-safe-bottom']).toBe('34px');
    });

    it('drives the MainButton and the closing guard from page state', async () => {
        fakeDom();
        const tg = fakeWebApp();
        installTelegram({});
        const onClick = vi.fn();
        const removeMain = useMainButton(() => ({ text: 'Topshirish', onClick }));
        const removeGuard = useClosingGuard(() => true);
        await nextTick();
        expect(tg.MainButton.setParams).toHaveBeenLastCalledWith(expect.objectContaining({ text: 'Topshirish', is_visible: true }));
        expect(tg.enableClosingConfirmation).toHaveBeenCalled();
        expect(tg.disableVerticalSwipes).toHaveBeenCalled();
        tg.MainButton.onClick.mock.calls[0][0]();
        expect(onClick).toHaveBeenCalled();

        removeMain();
        removeGuard();
        await nextTick();
        expect(tg.MainButton.hide).toHaveBeenCalled();
        expect(tg.disableClosingConfirmation).toHaveBeenCalled();
        expect(tg.enableVerticalSwipes).toHaveBeenCalled();
    });

    it('haptics, external links and story sharing go through Telegram', () => {
        fakeDom();
        const tg = fakeWebApp();
        installTelegram({});
        haptic.notify('error');
        expect(tg.HapticFeedback.notificationOccurred).toHaveBeenCalledWith('error');
        expect(openLink('https://youtu.be/x')).toBe(true);
        expect(tg.openLink).toHaveBeenCalledWith('https://youtu.be/x');
        expect(shareResult('https://exam.example.uz/telegram/card/1?signature=x', 'Natija')).toBe('story');
        expect(tg.shareToStory).toHaveBeenCalled();
        tg.platform = 'tdesktop';
        expect(shareResult('https://exam.example.uz/telegram/card/1?signature=x', 'Natija')).toBe('link');
        expect(tg.openTelegramLink.mock.calls.at(-1)[0]).toContain('https://t.me/share/url?url=');
    });

    it('reports minimise / restore once, with the hidden time', () => {
        fakeDom();
        const tg = fakeWebApp();
        installTelegram({});
        const seen = [];
        onAppVisibility((visible, ms) => seen.push([visible, ms >= 0]));
        tg.emit('deactivated');
        document.hidden = true;
        document.hidden = false;
        tg.emit('activated');
        expect(seen).toEqual([[false, true], [true, true]]);
    });

    it('re-logs in once on 419 and retries the request with the new token', async () => {
        fakeDom();
        fakeWebApp();
        let handler;
        const axios = {
            defaults: { headers: { common: {} } },
            interceptors: { response: { use: (ok, err) => { handler = err; } } },
            request: vi.fn().mockResolvedValue({ data: 'retried' }),
        };
        globalThis.fetch = vi.fn().mockResolvedValue({ ok: true, json: () => Promise.resolve({ ok: true, csrf_token: 'NEW' }) });
        installReauthInterceptor(axios);

        const result = await handler({ response: { status: 419 }, config: { url: '/student/exam/attempts/1/answers', headers: { 'X-XSRF-TOKEN': 'old' } } });
        expect(result).toEqual({ data: 'retried' });
        expect(globalThis.fetch).toHaveBeenCalledWith('/telegram/app/reauth', expect.objectContaining({ method: 'POST' }));
        const retried = axios.request.mock.calls[0][0];
        expect(retried.headers['X-CSRF-TOKEN']).toBe('NEW');
        expect(retried.headers['X-XSRF-TOKEN']).toBeUndefined();
        expect(axios.defaults.headers.common['X-CSRF-TOKEN']).toBe('NEW');

        // a second failure of the retried request is not retried again
        await expect(handler({ response: { status: 419 }, config: { ...retried } })).rejects.toBeTruthy();
        // other errors pass through
        await expect(handler({ response: { status: 500 }, config: { url: '/x', headers: {} } })).rejects.toBeTruthy();
        delete globalThis.fetch;
    });
});
