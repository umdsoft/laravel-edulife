/*
 * Mock of window.Telegram.WebApp for the local emulator (/telegram/dev, APP_ENV=local only).
 *
 * Follows the public Mini Apps API (https://core.telegram.org/bots/webapps) and the same
 * postMessage protocol Telegram Web uses between the Mini App iframe and its parent:
 *   app -> parent   {"eventType": "web_app_*", "eventData": {...}}  (ready, expand, setup_back_button,
 *                   setup_main_button, setup_closing_behavior, setup_swipe_behavior,
 *                   trigger_haptic_feedback, open_link, open_tg_link, share_to_story, ...)
 *   parent -> app   {"eventType": "theme_changed" | "viewport_changed" | "safe_area_changed" |
 *                   "content_safe_area_changed" | "back_button_pressed" | "main_button_pressed" |
 *                   "secondary_button_pressed" | "visibility_changed" | "popup_closed", "eventData": {...}}
 * TW: requestContact (web_app_request_phone -> phone_requested -> web_app_invoke_custom_method
 * "getRequestedContact" -> custom_method_invoked, as the real SDK does) and requestWriteAccess
 * (web_app_request_write_access -> write_access_requested).
 * Launch parameters come from the URL hash (#tgWebAppData=...&tgWebAppVersion=...&tgWebAppThemeParams=...)
 * and are kept in sessionStorage for the next pages, like the real SDK does.
 */
(function () {
    'use strict';
    if (window.Telegram && window.Telegram.WebApp) return;

    var STORE = '__telegram__initParams';
    var params = {};
    try {
        var hash = new URLSearchParams(String(location.hash || '').replace(/^#/, ''));
        if (hash.get('tgWebAppData')) {
            hash.forEach(function (v, k) { params[k] = v; });
            sessionStorage.setItem(STORE, JSON.stringify(params));
        } else {
            params = JSON.parse(sessionStorage.getItem(STORE) || '{}') || {};
        }
    } catch (e) { params = {}; }

    var initData = params.tgWebAppData || '';
    var initDataUnsafe = {};
    try {
        new URLSearchParams(initData).forEach(function (v, k) {
            initDataUnsafe[k] = (k === 'user' || k === 'receiver' || k === 'chat') ? JSON.parse(v) : (k === 'auth_date' ? Number(v) : v);
        });
    } catch (e) { /* keep partial */ }

    var themeParams = {};
    try { themeParams = JSON.parse(params.tgWebAppThemeParams || '{}') || {}; } catch (e) { themeParams = {}; }

    var handlers = {};
    var pending = { contact: null, write: null, custom: {} };
    var parseQuery = function (q) {
        var out = {};
        new URLSearchParams(q).forEach(function (v, k) {
            try { out[k] = (v.charAt(0) === '{' || v.charAt(0) === '[') ? JSON.parse(v) : v; } catch (e) { out[k] = v; }
        });
        return out;
    };
    var post = function (eventType, eventData) {
        try {
            if (window.parent && window.parent !== window) {
                window.parent.postMessage(JSON.stringify({ eventType: eventType, eventData: eventData || {} }), '*');
            }
        } catch (e) { /* ignore */ }
    };
    var emit = function (name, arg) {
        (handlers[name] || []).slice().forEach(function (fn) {
            try { fn.call(WebApp, arg); } catch (e) { console.error(e); }
        });
    };
    var versionAtLeast = function (v) {
        var a = String(WebApp.version).split('.').map(Number);
        var b = String(v).split('.').map(Number);
        for (var i = 0; i < Math.max(a.length, b.length); i++) {
            var x = a[i] || 0, y = b[i] || 0;
            if (x !== y) return x > y;
        }
        return true;
    };
    var luminance = function (hex) {
        var m = /^#?([0-9a-f]{6})$/i.exec(hex || '');
        if (!m) return 1;
        var n = parseInt(m[1], 16);
        return (0.2126 * ((n >> 16) & 255) + 0.7152 * ((n >> 8) & 255) + 0.0722 * (n & 255)) / 255;
    };
    var setCss = function () {
        var root = document.documentElement.style;
        Object.keys(themeParams).forEach(function (k) { root.setProperty('--tg-theme-' + k.replace(/_/g, '-'), themeParams[k]); });
        root.setProperty('--tg-color-scheme', WebApp.colorScheme);
        root.setProperty('--tg-viewport-height', WebApp.viewportHeight + 'px');
        root.setProperty('--tg-viewport-stable-height', WebApp.viewportStableHeight + 'px');
        ['top', 'bottom', 'left', 'right'].forEach(function (side) {
            root.setProperty('--tg-safe-area-inset-' + side, (WebApp.safeAreaInset[side] || 0) + 'px');
            root.setProperty('--tg-content-safe-area-inset-' + side, (WebApp.contentSafeAreaInset[side] || 0) + 'px');
        });
    };

    var button = function (type) {
        var b = {
            type: type,
            text: type === 'main' ? 'Continue' : 'Cancel',
            color: themeParams.button_color || '#2481cc',
            textColor: themeParams.button_text_color || '#ffffff',
            isVisible: false,
            isActive: true,
            isProgressVisible: false,
            hasShineEffect: false,
            position: 'left',
        };
        var sync = function () {
            post(type === 'main' ? 'web_app_setup_main_button' : 'web_app_setup_secondary_button', {
                is_visible: b.isVisible, is_active: b.isActive, is_progress_visible: b.isProgressVisible,
                text: b.text, color: b.color, text_color: b.textColor, has_shine_effect: b.hasShineEffect, position: b.position,
            });
            return b;
        };
        var event = type === 'main' ? 'mainButtonClicked' : 'secondaryButtonClicked';
        b.setText = function (t) { b.text = String(t); return sync(); };
        b.onClick = function (fn) { WebApp.onEvent(event, fn); return b; };
        b.offClick = function (fn) { WebApp.offEvent(event, fn); return b; };
        b.show = function () { b.isVisible = true; return sync(); };
        b.hide = function () { b.isVisible = false; return sync(); };
        b.enable = function () { b.isActive = true; return sync(); };
        b.disable = function () { b.isActive = false; return sync(); };
        b.showProgress = function (leaveActive) { b.isProgressVisible = true; if (!leaveActive) b.isActive = false; return sync(); };
        b.hideProgress = function () { b.isProgressVisible = false; b.isActive = true; return sync(); };
        b.setParams = function (p) {
            p = p || {};
            if ('text' in p) b.text = String(p.text);
            if ('color' in p) b.color = p.color;
            if ('text_color' in p) b.textColor = p.text_color;
            if ('is_visible' in p) b.isVisible = !!p.is_visible;
            if ('is_active' in p) b.isActive = !!p.is_active;
            if ('has_shine_effect' in p) b.hasShineEffect = !!p.has_shine_effect;
            if ('position' in p) b.position = p.position;
            return sync();
        };
        return b;
    };

    var WebApp = {
        initData: initData,
        initDataUnsafe: initDataUnsafe,
        version: params.tgWebAppVersion || '8.0',
        platform: params.tgWebAppPlatform || 'unknown',
        colorScheme: luminance(themeParams.bg_color) < 0.5 ? 'dark' : 'light',
        themeParams: themeParams,
        isActive: true,
        isExpanded: false,
        viewportHeight: window.innerHeight,
        viewportStableHeight: window.innerHeight,
        headerColor: themeParams.header_bg_color || themeParams.bg_color || '#ffffff',
        backgroundColor: themeParams.bg_color || '#ffffff',
        bottomBarColor: themeParams.bottom_bar_bg_color || themeParams.secondary_bg_color || '#ffffff',
        isClosingConfirmationEnabled: false,
        isVerticalSwipesEnabled: true,
        isFullscreen: false,
        safeAreaInset: { top: 0, bottom: 0, left: 0, right: 0 },
        contentSafeAreaInset: { top: 0, bottom: 0, left: 0, right: 0 },
        isMock: true,

        isVersionAtLeast: versionAtLeast,
        ready: function () { post('web_app_ready'); },
        expand: function () { WebApp.isExpanded = true; post('web_app_expand'); },
        close: function () { post('web_app_close'); },
        setHeaderColor: function (c) { WebApp.headerColor = c; post('web_app_set_header_color', { color_key: c }); },
        setBackgroundColor: function (c) { WebApp.backgroundColor = c; post('web_app_set_background_color', { color: c }); },
        setBottomBarColor: function (c) { WebApp.bottomBarColor = c; post('web_app_set_bottom_bar_color', { color: c }); },
        enableClosingConfirmation: function () { WebApp.isClosingConfirmationEnabled = true; post('web_app_setup_closing_behavior', { need_confirmation: true }); },
        disableClosingConfirmation: function () { WebApp.isClosingConfirmationEnabled = false; post('web_app_setup_closing_behavior', { need_confirmation: false }); },
        enableVerticalSwipes: function () { WebApp.isVerticalSwipesEnabled = true; post('web_app_setup_swipe_behavior', { allow_vertical_swipe: true }); },
        disableVerticalSwipes: function () { WebApp.isVerticalSwipesEnabled = false; post('web_app_setup_swipe_behavior', { allow_vertical_swipe: false }); },
        openLink: function (url, options) { post('web_app_open_link', { url: String(url), try_instant_view: !!(options && options.try_instant_view) }); },
        openTelegramLink: function (url) {
            var u = String(url).replace(/^https?:\/\/t\.me/, '');
            post('web_app_open_tg_link', { path_full: u });
        },
        switchInlineQuery: function (query, chatTypes) { post('web_app_switch_inline_query', { query: String(query || ''), chat_types: chatTypes || [] }); },
        shareToStory: function (mediaUrl, p) { post('web_app_share_to_story', Object.assign({ media_url: String(mediaUrl) }, p || {})); },
        showPopup: function (p, cb) {
            post('web_app_open_popup', p || {});
            var ok = window.confirm(((p && p.title) ? p.title + '\n' : '') + ((p && p.message) || ''));
            setTimeout(function () { if (cb) cb(ok ? ((p.buttons && p.buttons[0] && p.buttons[0].id) || 'ok') : null); }, 0);
        },
        showAlert: function (m, cb) { post('web_app_open_popup', { message: m }); window.alert(m); if (cb) setTimeout(cb, 0); },
        showConfirm: function (m, cb) { post('web_app_open_popup', { message: m }); var ok = window.confirm(m); if (cb) setTimeout(function () { cb(ok); }, 0); },
        sendData: function () { /* keyboard-button Mini Apps only */ },
        // --- TW: phone and write access (Bot API 6.9) ---
        requestContact: function (cb) {
            if (!versionAtLeast('6.9')) throw Error('WebAppMethodUnsupported');
            if (pending.contact) throw Error('WebAppContactRequested');
            pending.contact = { callback: cb };
            post('web_app_request_phone');
        },
        requestWriteAccess: function (cb) {
            if (!versionAtLeast('6.9')) throw Error('WebAppMethodUnsupported');
            pending.write = { callback: cb };
            post('web_app_request_write_access');
        },
        onEvent: function (name, fn) { (handlers[name] = handlers[name] || []).push(fn); },
        offEvent: function (name, fn) { handlers[name] = (handlers[name] || []).filter(function (h) { return h !== fn; }); },
    };

    WebApp.BackButton = {
        isVisible: false,
        onClick: function (fn) { WebApp.onEvent('backButtonClicked', fn); return WebApp.BackButton; },
        offClick: function (fn) { WebApp.offEvent('backButtonClicked', fn); return WebApp.BackButton; },
        show: function () { WebApp.BackButton.isVisible = true; post('web_app_setup_back_button', { is_visible: true }); return WebApp.BackButton; },
        hide: function () { WebApp.BackButton.isVisible = false; post('web_app_setup_back_button', { is_visible: false }); return WebApp.BackButton; },
    };
    WebApp.MainButton = button('main');
    WebApp.SecondaryButton = button('secondary');
    var haptic = function (data) { post('web_app_trigger_haptic_feedback', data); return WebApp.HapticFeedback; };
    WebApp.HapticFeedback = {
        impactOccurred: function (style) { return haptic({ type: 'impact', impact_style: style }); },
        notificationOccurred: function (type) { return haptic({ type: 'notification', notification_type: type }); },
        selectionChanged: function () { return haptic({ type: 'selection_change' }); },
    };

    window.addEventListener('message', function (e) {
        if (e.source !== window.parent) return;
        var msg;
        try { msg = typeof e.data === 'string' ? JSON.parse(e.data) : e.data; } catch (err) { return; }
        if (!msg || !msg.eventType) return;
        var d = msg.eventData || {};
        switch (msg.eventType) {
            case 'theme_changed':
                themeParams = d.theme_params || {};
                WebApp.themeParams = themeParams;
                WebApp.colorScheme = luminance(themeParams.bg_color) < 0.5 ? 'dark' : 'light';
                setCss();
                emit('themeChanged');
                break;
            case 'viewport_changed':
                WebApp.viewportHeight = Number(d.height) || WebApp.viewportHeight;
                if (d.is_state_stable) WebApp.viewportStableHeight = WebApp.viewportHeight;
                WebApp.isExpanded = !!d.is_expanded;
                setCss();
                emit('viewportChanged', { isStateStable: !!d.is_state_stable });
                break;
            case 'safe_area_changed':
                WebApp.safeAreaInset = Object.assign({ top: 0, bottom: 0, left: 0, right: 0 }, d);
                setCss();
                emit('safeAreaChanged');
                break;
            case 'content_safe_area_changed':
                WebApp.contentSafeAreaInset = Object.assign({ top: 0, bottom: 0, left: 0, right: 0 }, d);
                setCss();
                emit('contentSafeAreaChanged');
                break;
            case 'back_button_pressed': emit('backButtonClicked'); break;
            case 'main_button_pressed': emit('mainButtonClicked'); break;
            case 'secondary_button_pressed': emit('secondaryButtonClicked'); break;
            case 'phone_requested':
                if (!pending.contact) break;
                (function (req) {
                    pending.contact = null;
                    var ev = { status: d.status };
                    if (d.status !== 'sent') {
                        if (req.callback) req.callback(false, ev);
                        emit('contactRequested', ev);
                        return;
                    }
                    var id = 'c' + Math.random().toString(36).slice(2);
                    pending.custom[id] = function (result) {
                        if (result) { ev.response = result; ev.responseUnsafe = parseQuery(result); }
                        if (req.callback) req.callback(true, ev);
                        emit('contactRequested', ev);
                    };
                    post('web_app_invoke_custom_method', { req_id: id, method: 'getRequestedContact', params: {} });
                })(pending.contact);
                break;
            case 'custom_method_invoked':
                if (pending.custom[d.req_id]) { var fn = pending.custom[d.req_id]; delete pending.custom[d.req_id]; fn(d.result || ''); }
                break;
            case 'write_access_requested':
                if (pending.write) { var w = pending.write; pending.write = null; if (w.callback) w.callback(d.status === 'allowed'); }
                emit('writeAccessRequested', { status: d.status });
                break;
            case 'visibility_changed':
                WebApp.isActive = !!d.is_visible;
                emit(WebApp.isActive ? 'activated' : 'deactivated');
                break;
            default:
                break;
        }
    });

    setCss();
    window.Telegram = window.Telegram || {};
    window.Telegram.WebApp = WebApp;
    window.Telegram.WebView = { initParams: params, isIframe: window.parent !== window };
    post('web_app_request_viewport');
    post('web_app_request_theme');
})();
