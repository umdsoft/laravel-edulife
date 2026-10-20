/**
 * Telegram WebView — everything the Mini App needs from Telegram, in one place.
 *
 * API used (https://core.telegram.org/bots/webapps): WebApp.ready / expand, themeParams + colorScheme +
 * themeChanged, viewportHeight / viewportStableHeight + viewportChanged, safeAreaInset / contentSafeAreaInset
 * (+ *Changed, Bot API 8.0), BackButton (6.1), MainButton (BottomButton, 7.10 setParams), enable/disable
 * ClosingConfirmation (6.2), disableVerticalSwipes (7.7), HapticFeedback (6.1), openLink / openTelegramLink,
 * shareToStory (7.8), switchInlineQuery (6.7), activated / deactivated (8.0), setHeaderColor /
 * setBackgroundColor (6.1) / setBottomBarColor (7.10).
 *
 * Outside Telegram every function is a no-op (getWebApp() is null), so the website behaves as before.
 *
 *   installTelegram({ router, axios })  once, in app.js
 *   const tg = useTelegram()            in a page: tg.active, tg.haptic.notify('success'), tg.openLink(url) …
 *   useBackButton(() => handler|null)   page-specific Back (null hides it; undefined = default)
 *   useMainButton(() => ({ text, onClick, active, progress }) | null)
 *   useClosingGuard(() => boolean)      closing confirmation + no vertical swipes while true
 *   onAppVisibility((visible, hiddenMs) => …)  Telegram minimised / restored
 */
import { computed, getCurrentInstance, onBeforeUnmount, reactive, readonly, shallowReactive, watchEffect } from 'vue';

const state = reactive({
    active: false,
    colorScheme: 'light',
    platform: '',
    version: '',
    isActive: true,
    viewportHeight: 0,
    stableHeight: 0,
    component: null,
    url: '',
});

let installed = false;
let deps = { router: null, axios: null };

/** The real (or emulator's) WebApp object, only when launched by Telegram (initData present). */
export function getWebApp() {
    if (typeof window === 'undefined') return null;
    const tg = window.Telegram?.WebApp;
    return tg && typeof tg.initData === 'string' && tg.initData !== '' ? tg : null;
}

export const isTelegram = () => getWebApp() !== null;

const atLeast = (tg, version) => {
    try {
        return typeof tg.isVersionAtLeast === 'function' ? tg.isVersionAtLeast(version) : false;
    } catch {
        return false;
    }
};
const safe = (fn) => {
    try {
        return fn();
    } catch {
        return undefined;
    }
};

// ---------------------------------------------------------------- theme ------------------------------

/** "#2481cc" -> "36 129 204" (our tokens are RGB triplets, see resources/css/app.css). */
export function hexToTriplet(hex) {
    const m = /^#?([0-9a-f]{3}|[0-9a-f]{6})$/i.exec(String(hex || '').trim());
    if (!m) return null;
    let h = m[1];
    if (h.length === 3) h = h.split('').map((c) => c + c).join('');
    const n = parseInt(h, 16);
    return `${(n >> 16) & 255} ${(n >> 8) & 255} ${n & 255}`;
}

const parse = (t) => (t ? t.split(' ').map(Number) : null);
const mix = (a, b, weight) => {
    const x = parse(a);
    const y = parse(b);
    if (!x || !y) return a || b || null;
    return x.map((v, i) => Math.round(v * (1 - weight) + y[i] * weight)).join(' ');
};

/**
 * Telegram themeParams -> our CSS variables. Only the neutral surfaces, text and the brand
 * (button) colour follow Telegram; success / warning / danger / subject colours keep our tokens
 * (light or dark set by colorScheme).
 */
export function themeTokens(themeParams = {}, colorScheme = 'light') {
    const t = (key) => hexToTriplet(themeParams[key]);
    const bg = t('bg_color');
    const text = t('text_color');
    if (!bg || !text) return {};
    const dark = colorScheme === 'dark';
    const canvas = t('secondary_bg_color') || mix(bg, text, 0.04);
    const surface = t('section_bg_color') || bg;
    const hint = t('hint_color') || mix(text, bg, 0.45);
    const button = t('button_color');
    const tokens = {
        '--bg': canvas,
        '--surface': surface,
        '--surface-2': mix(surface, text, dark ? 0.08 : 0.05),
        '--surface-3': mix(surface, text, dark ? 0.14 : 0.1),
        '--border': t('section_separator_color') || mix(surface, text, dark ? 0.14 : 0.1),
        '--border-strong': mix(surface, text, dark ? 0.24 : 0.18),
        '--fg': text,
        '--fg-muted': t('subtitle_text_color') || mix(text, bg, 0.3),
        '--fg-subtle': hint,
    };
    if (button) {
        Object.assign(tokens, {
            '--brand-500': button,
            '--brand-600': button,
            '--brand-700': mix(button, dark ? '255 255 255' : '0 0 0', 0.15),
            '--brand-50': mix(surface, button, dark ? 0.18 : 0.08),
            '--brand-100': mix(surface, button, dark ? 0.26 : 0.16),
            '--brand-fg': t('accent_text_color') || t('link_color') || button,
        });
    }
    const destructive = t('destructive_text_color');
    if (destructive) tokens['--danger-600'] = destructive;
    return Object.fromEntries(Object.entries(tokens).filter(([, v]) => !!v));
}

function applyTheme(tg) {
    const root = document.documentElement;
    const scheme = tg.colorScheme === 'dark' ? 'dark' : 'light';
    state.colorScheme = scheme;
    // follows Telegram without touching the website's saved preference (useTheme)
    root.classList.toggle('dark', scheme === 'dark');
    root.style.colorScheme = scheme;
    const tokens = themeTokens(tg.themeParams || {}, scheme);
    Object.entries(tokens).forEach(([name, value]) => root.style.setProperty(name, value));
    safe(() => atLeast(tg, '6.1') && tg.setHeaderColor(tg.themeParams?.section_bg_color ? tg.themeParams.section_bg_color : 'bg_color'));
    safe(() => atLeast(tg, '6.1') && tg.setBackgroundColor('secondary_bg_color'));
    safe(() => atLeast(tg, '7.10') && tg.setBottomBarColor('bottom_bar_bg_color'));
}

// ---------------------------------------------------------------- viewport / safe area ---------------

function applyViewport(tg) {
    const root = document.documentElement.style;
    const h = Number(tg.viewportHeight) || window.innerHeight;
    const stable = Number(tg.viewportStableHeight) || h;
    state.viewportHeight = h;
    state.stableHeight = stable;
    root.setProperty('--tg-app-height', `${stable}px`);
    root.setProperty('--tg-app-live-height', `${h}px`);
}

function applySafeArea(tg) {
    const root = document.documentElement.style;
    const s = tg.safeAreaInset || {};
    const c = tg.contentSafeAreaInset || {};
    ['top', 'bottom', 'left', 'right'].forEach((side) => {
        root.setProperty(`--tg-safe-${side}`, `${(Number(s[side]) || 0) + (Number(c[side]) || 0)}px`);
    });
}

// ---------------------------------------------------------------- back button -------------------------

const ROOT_PAGES = ['Student/Exam/Index', 'Student/Battle/Index', 'Student/Exam/Progress', 'Student/Profile/Show', 'Guardian/Dashboard'];
const backStack = shallowReactive([]);
let backHandler = null;

function defaultBack() {
    if (!state.component || ROOT_PAGES.includes(state.component)) return null;
    return () => {
        if (window.history.length > 1) window.history.back();
        else deps.router?.visit?.(examHome());
    };
}

function examHome() {
    try {
        return window.route('student.exam.index');
    } catch {
        return '/student/exam';
    }
}

// ---------------------------------------------------------------- main button / closing guard ---------

const mainStack = shallowReactive([]);
let mainHandler = null;
const guards = shallowReactive([]);

// ---------------------------------------------------------------- re-login ---------------------------

let reauthPromise = null;

function csrfMeta() {
    return typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]') : null;
}

function tooManyReauths() {
    try {
        const now = Date.now();
        const tries = JSON.parse(sessionStorage.getItem('edulife_tg_reauth') || '[]').filter((t) => now - t < 60000);
        tries.push(now);
        sessionStorage.setItem('edulife_tg_reauth', JSON.stringify(tries));
        return tries.length > 3;
    } catch {
        return false;
    }
}

/**
 * Log in again with initData (POST /telegram/app/reauth). Resolves to the new CSRF token,
 * or null. Concurrent callers share one request; at most 3 a minute (cookies blocked).
 */
export function reauthenticate() {
    const tg = getWebApp();
    if (!tg) return Promise.resolve(null);
    if (reauthPromise) return reauthPromise;
    if (tooManyReauths()) return Promise.resolve(null);
    reauthPromise = fetch('/telegram/app/reauth', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ init_data: tg.initData }),
    })
        .then((r) => (r.ok ? r.json() : null))
        .then((body) => {
            const token = body?.csrf_token || null;
            if (token) {
                csrfMeta()?.setAttribute('content', token);
                if (deps.axios?.defaults?.headers?.common) deps.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
            }
            return token;
        })
        .catch(() => null)
        .finally(() => {
            setTimeout(() => {
                reauthPromise = null;
            }, 0);
        });
    return reauthPromise;
}

/** axios: a 401/419 inside Telegram -> re-login once and retry the same request. */
export function installReauthInterceptor(axios) {
    if (!axios?.interceptors?.response) return;
    deps.axios = deps.axios || axios;
    axios.interceptors.response.use(undefined, async (error) => {
        const status = error?.response?.status;
        const config = error?.config;
        if (!getWebApp() || !config || config.__tgRetried || ![401, 419].includes(status) || String(config.url || '').includes('/telegram/app/')) {
            throw error;
        }
        const token = await reauthenticate();
        if (!token) throw error;
        config.__tgRetried = true;
        const headers = config.headers || {};
        if (typeof headers.set === 'function') {
            headers.set('X-CSRF-TOKEN', token);
            headers.delete?.('X-XSRF-TOKEN');
        } else {
            headers['X-CSRF-TOKEN'] = token;
            delete headers['X-XSRF-TOKEN'];
        }
        config.headers = headers;
        return axios.request(config);
    });
}

// ---------------------------------------------------------------- install ----------------------------

let lastVisitUrl = null;

function onNavigate(page) {
    if (!page) return;
    state.component = page.component || null;
    state.url = page.url || '';
    // The session cookie was lost: an Inertia visit landed on the login form. Never show it inside
    // Telegram: log in again with initData and repeat the visit (or go through /telegram/app).
    if (page.component === 'Auth/Login') {
        if (document.body) document.body.style.visibility = 'hidden';
        const target = lastVisitUrl && !/\/login(\?|$)/.test(lastVisitUrl) ? lastVisitUrl : null;
        reauthenticate().then((token) => {
            if (token && target) {
                deps.router?.visit?.(target, { replace: true, preserveState: false });
                if (document.body) document.body.style.visibility = '';
                return;
            }
            let path = null;
            try {
                path = target ? new URL(target, window.location.origin).pathname : null;
            } catch {
                path = null;
            }
            window.location.replace('/telegram/app' + (path ? `?return=${encodeURIComponent(path)}` : ''));
        });
    }
}

/** Open external links with Telegram's browser (the Mini App stays open), t.me links inside Telegram. */
function onLinkClick(e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    const a = e.target?.closest?.('a[href]');
    if (!a || a.hasAttribute('download')) return;
    let url;
    try {
        url = new URL(a.getAttribute('href'), window.location.href);
    } catch {
        return;
    }
    if (!/^https?:$/.test(url.protocol)) return;
    const tg = getWebApp();
    if (!tg) return;
    if (url.hostname === 't.me') {
        e.preventDefault();
        safe(() => tg.openTelegramLink(url.href));
    } else if (url.origin !== window.location.origin) {
        e.preventDefault();
        safe(() => tg.openLink(url.href));
    }
}

/** On-screen keyboard: keep the focused field in view (the viewport shrinks under it). */
function onFocusIn(e) {
    const el = e.target;
    if (!el || !/^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName)) return;
    setTimeout(() => safe(() => el.scrollIntoView({ block: 'center', behavior: 'smooth' })), 300);
}

export function installTelegram({ router = null, axios = null, initialPage = null } = {}) {
    const tg = getWebApp();
    if (!tg || installed) return false;
    installed = true;
    deps = { router, axios };

    state.active = true;
    state.platform = tg.platform || '';
    state.version = tg.version || '';
    state.isActive = tg.isActive !== false;
    document.documentElement.classList.add('tg-webview');

    safe(() => tg.ready());
    safe(() => tg.expand());

    applyTheme(tg);
    applyViewport(tg);
    applySafeArea(tg);
    safe(() => tg.onEvent('themeChanged', () => applyTheme(tg)));
    safe(() => tg.onEvent('viewportChanged', () => applyViewport(tg)));
    safe(() => tg.onEvent('safeAreaChanged', () => applySafeArea(tg)));
    safe(() => tg.onEvent('contentSafeAreaChanged', () => applySafeArea(tg)));
    safe(() => tg.onEvent('activated', () => setVisible(true)));
    safe(() => tg.onEvent('deactivated', () => setVisible(false)));

    // Back button: the top page handler, else "history back" except on the tab roots
    safe(() => tg.BackButton.onClick(() => backHandler && backHandler()));
    watchEffect(() => {
        const top = backStack[backStack.length - 1];
        let handler = top ? top.get() : undefined;
        if (handler === undefined) handler = defaultBack();
        backHandler = typeof handler === 'function' ? handler : null;
        safe(() => (backHandler ? tg.BackButton.show() : tg.BackButton.hide()));
    });

    // Main button: the top page's config, hidden otherwise
    safe(() => tg.MainButton.onClick(() => mainHandler && mainHandler()));
    watchEffect(() => {
        const top = mainStack[mainStack.length - 1];
        const cfg = top ? top.get() : null;
        mainHandler = cfg && typeof cfg.onClick === 'function' ? cfg.onClick : null;
        safe(() => {
            if (!cfg || !cfg.text) {
                tg.MainButton.hideProgress?.();
                tg.MainButton.hide();
                return;
            }
            const params = { text: String(cfg.text), is_visible: true, is_active: cfg.active !== false };
            if (atLeast(tg, '7.10')) params.has_shine_effect = !!cfg.shine;
            tg.MainButton.setParams(params);
            if (cfg.progress) tg.MainButton.showProgress(false);
            else tg.MainButton.hideProgress();
        });
    });

    // Closing confirmation + vertical swipes while an exam attempt or a battle is running
    watchEffect(() => {
        const on = guards.some((g) => !!g.get());
        safe(() => {
            if (atLeast(tg, '6.2')) (on ? tg.enableClosingConfirmation() : tg.disableClosingConfirmation());
            if (atLeast(tg, '7.7')) (on ? tg.disableVerticalSwipes() : tg.enableVerticalSwipes());
        });
    });

    if (axios) installReauthInterceptor(axios);
    if (router?.on) {
        router.on('before', (event) => {
            const visit = event?.detail?.visit;
            if (visit && String(visit.method || 'get').toLowerCase() === 'get') lastVisitUrl = String(visit.url?.href || visit.url || '');
        });
        router.on('navigate', (event) => onNavigate(event?.detail?.page));
    }
    if (initialPage) onNavigate(initialPage);
    document.addEventListener('click', onLinkClick, true);
    document.addEventListener('focusin', onFocusIn);
    document.addEventListener('visibilitychange', () => setVisible(!document.hidden, 'document'));

    try {
        sessionStorage.setItem('edulife_tg_sdk', document.querySelector('script[src*="telegram-web-app"], script[src*="/telegram/dev/sdk.js"]')?.getAttribute('src') || sessionStorage.getItem('edulife_tg_sdk') || '');
    } catch {
        /* storage unavailable */
    }
    return true;
}

// ---------------------------------------------------------------- visibility -------------------------

const visibilityListeners = new Set();
let hiddenSince = null;

/**
 * Telegram minimised (deactivated, Bot API 8.0) and/or the document hidden: one "hidden" period,
 * whichever signal comes first; the listeners get (false) when it starts and (true, hiddenMs) when it ends.
 */
function setVisible(visible) {
    if (!visible) {
        if (hiddenSince !== null) return;
        hiddenSince = Date.now();
        state.isActive = false;
        visibilityListeners.forEach((fn) => safe(() => fn(false, 0)));
        return;
    }
    if (hiddenSince === null) return;
    const ms = Date.now() - hiddenSince;
    hiddenSince = null;
    state.isActive = true;
    visibilityListeners.forEach((fn) => safe(() => fn(true, ms)));
}

/** Subscribe to app visibility (Telegram only); unsubscribes with the component. */
export function onAppVisibility(fn) {
    if (!getWebApp() || typeof fn !== 'function') return () => {};
    visibilityListeners.add(fn);
    const off = () => visibilityListeners.delete(fn);
    if (getCurrentInstance()) onBeforeUnmount(off);
    return off;
}

// ---------------------------------------------------------------- page helpers -----------------------

function pushWhileMounted(stack, getter) {
    if (!getWebApp()) return () => {};
    const entry = { get: typeof getter === 'function' ? getter : () => getter };
    stack.push(entry);
    const remove = () => {
        const i = stack.indexOf(entry);
        if (i >= 0) stack.splice(i, 1);
    };
    if (getCurrentInstance()) onBeforeUnmount(remove);
    return remove;
}

/** Page-specific BackButton: getter returns a handler, null (hide) or undefined (default). */
export const useBackButton = (getter) => pushWhileMounted(backStack, getter);

/** Page-specific MainButton: getter returns { text, onClick, active?, progress?, shine? } or null. */
export const useMainButton = (getter) => pushWhileMounted(mainStack, getter);

/** Ask before closing and turn off swipe-to-close while getter() is true. */
export const useClosingGuard = (getter) => pushWhileMounted(guards, getter);

export const haptic = {
    impact(style = 'light') {
        const tg = getWebApp();
        if (tg && atLeast(tg, '6.1')) safe(() => tg.HapticFeedback.impactOccurred(style));
    },
    notify(type = 'success') {
        const tg = getWebApp();
        if (tg && atLeast(tg, '6.1')) safe(() => tg.HapticFeedback.notificationOccurred(type));
    },
    selection() {
        const tg = getWebApp();
        if (tg && atLeast(tg, '6.1')) safe(() => tg.HapticFeedback.selectionChanged());
    },
};

/** External link via Telegram (true) — or false outside Telegram (the caller opens it itself). */
export function openLink(url) {
    const tg = getWebApp();
    if (!tg || !url) return false;
    safe(() => tg.openLink(String(url)));
    return true;
}

/** Telegram's "send to a chat" sheet with a link (t.me/share/url). */
export function shareLink(url, text = '') {
    const tg = getWebApp();
    if (!tg || !url) return false;
    const share = `https://t.me/share/url?url=${encodeURIComponent(url)}&text=${encodeURIComponent(text)}`;
    safe(() => tg.openTelegramLink(share));
    return true;
}

/**
 * Result card: a story (shareToStory, Bot API 7.8, mobile clients) or, elsewhere, the share sheet.
 * Returns 'story' | 'link' | false.
 */
export function shareResult(mediaUrl, text = '') {
    const tg = getWebApp();
    if (!tg || !mediaUrl) return false;
    const mobile = ['ios', 'android', 'android_x'].includes(String(tg.platform || '').toLowerCase());
    if (mobile && typeof tg.shareToStory === 'function' && atLeast(tg, '7.8') && /^https:\/\//.test(mediaUrl)) {
        safe(() => tg.shareToStory(mediaUrl, { text: String(text).slice(0, 200) }));
        return 'story';
    }
    return shareLink(mediaUrl, text) ? 'link' : false;
}

/** Inline mode share (needs inline mode on in BotFather); false when unavailable. */
export function switchInline(query, chatTypes = ['users', 'groups']) {
    const tg = getWebApp();
    if (!tg || !atLeast(tg, '6.7') || typeof tg.switchInlineQuery !== 'function') return false;
    safe(() => tg.switchInlineQuery(String(query || ''), chatTypes));
    return true;
}

/** Composable: reactive state + the helpers above. */
export function useTelegram() {
    const active = computed(() => state.active && isTelegram());
    return {
        active,
        state: readonly(state),
        haptic,
        openLink,
        shareLink,
        shareResult,
        switchInline,
        reauthenticate,
    };
}

/** Tests only. */
export function __resetTelegramForTests() {
    installed = false;
    deps = { router: null, axios: null };
    backStack.splice(0);
    mainStack.splice(0);
    guards.splice(0);
    visibilityListeners.clear();
    hiddenSince = null;
    reauthPromise = null;
    backHandler = null;
    mainHandler = null;
    Object.assign(state, { active: false, colorScheme: 'light', platform: '', version: '', isActive: true, viewportHeight: 0, stableHeight: 0, component: null, url: '' });
}
