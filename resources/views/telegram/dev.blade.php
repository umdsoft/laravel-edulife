<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>Telegram Mini App emulyatori</title>
    {{-- Telegram WebView (wave 2): local emulator, APP_ENV=local only. Plays Telegram's part around the
         Mini App iframe using the Mini Apps postMessage protocol; nothing is sent to Telegram. --}}
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font: 14px/1.45 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background: #e9ecf2; color: #111827; }
        .wrap { display: flex; flex-wrap: wrap; gap: 24px; padding: 20px; align-items: flex-start; }
        .panel { width: 320px; background: #fff; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .panel h1 { font-size: 16px; margin: 0 0 4px; }
        .panel p.note { margin: 0 0 12px; color: #6b7280; font-size: 12px; }
        label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin: 10px 0 4px; }
        select, input[type=text] { width: 100%; height: 34px; border: 1px solid #d0d5dd; border-radius: 8px; padding: 0 8px; font: inherit; background: #fff; }
        .chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
        .chips button, .row button { border: 1px solid #d0d5dd; background: #f8fafc; border-radius: 999px; padding: 3px 9px; font-size: 12px; cursor: pointer; }
        .row { display: flex; gap: 8px; margin-top: 12px; flex-wrap: wrap; }
        .row button { border-radius: 8px; padding: 7px 12px; font-size: 13px; }
        .row button.primary { background: #2481cc; border-color: #2481cc; color: #fff; }
        .log { margin-top: 12px; max-height: 260px; overflow: auto; font: 11px/1.4 ui-monospace, Menlo, Consolas, monospace; background: #0f172a; color: #e2e8f0; border-radius: 8px; padding: 8px; }
        .log div { white-space: pre-wrap; word-break: break-all; }
        .phone { border-radius: 28px; background: #111; padding: 10px; box-shadow: 0 10px 30px rgba(0,0,0,.25); }
        .screen { position: relative; overflow: hidden; border-radius: 20px; display: flex; flex-direction: column; background: var(--tg-bg); }
        .screen.shake { animation: shake .18s linear; }
        @keyframes shake { 25% { transform: translateX(-2px); } 75% { transform: translateX(2px); } }
        .tg-header { height: 56px; flex: none; display: flex; align-items: center; gap: 8px; padding: 0 10px; background: var(--tg-header); color: var(--tg-text); border-bottom: 1px solid rgba(127,127,127,.2); }
        .tg-header button { border: 0; background: transparent; color: var(--tg-link); font-size: 15px; cursor: pointer; padding: 6px 8px; border-radius: 8px; }
        .tg-header .title { flex: 1; font-weight: 600; font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .tg-header .flag { font-size: 11px; color: var(--tg-hint); }
        iframe { border: 0; width: 100%; flex: 1; background: var(--tg-bg); display: block; }
        .tg-bottom { flex: none; display: none; gap: 8px; padding: 8px 10px 10px; background: var(--tg-bottom); }
        .tg-bottom.visible { display: flex; }
        .tg-bottom button { flex: 1; height: 46px; border: 0; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .tg-bottom button[hidden] { display: none; }
        .tg-bottom button:disabled { opacity: .55; cursor: default; }
        .minimised { position: absolute; inset: 56px 0 0; background: rgba(15,23,42,.75); color: #fff; display: none; align-items: center; justify-content: center; font-weight: 600; }
        .minimised.on { display: flex; }
        /* auth (agent L): local bot chat */
        .chat { width: 360px; }
        .chat .msgs { margin-top: 10px; height: 520px; overflow: auto; background: #dfe7ef; border-radius: 10px; padding: 8px; display: flex; flex-direction: column; gap: 8px; }
        .chat .msg { background: #fff; border-radius: 10px; padding: 8px 10px; box-shadow: 0 1px 1px rgba(0,0,0,.06); }
        .chat .msg .meta { font-size: 11px; color: #6b7280; margin-bottom: 4px; }
        .chat .msg .text { white-space: pre-wrap; word-break: break-word; }
        .chat .msg code { background: #eef2ff; padding: 0 4px; border-radius: 4px; font-size: 15px; letter-spacing: 1px; }
        .chat .kb { display: flex; flex-direction: column; gap: 4px; margin-top: 6px; }
        .chat .kb .kbrow { display: flex; gap: 4px; }
        .chat .kb button { flex: 1; border: 0; border-radius: 8px; background: #e8f1fb; color: #2481cc; font: 600 12px/1.2 system-ui, sans-serif; padding: 7px 6px; cursor: pointer; }
        .chat .kb.reply button { background: #f1f5f9; color: #111827; }
        .chat .send { display: flex; gap: 6px; margin-top: 8px; }
        .chat .send input { flex: 1; height: 34px; border: 1px solid #d0d5dd; border-radius: 8px; padding: 0 8px; font: inherit; }
        .chat .send button { border-radius: 8px; padding: 7px 12px; border: 1px solid #2481cc; background: #2481cc; color: #fff; cursor: pointer; }
        .chat .empty { color: #6b7280; font-size: 12px; text-align: center; margin: auto; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="panel">
        <h1>Mini App emulyatori</h1>
        <p class="note">Faqat APP_ENV=local. initData server tomonda imzolanadi; Telegram'ga hech narsa yuborilmaydi.
            Emulyator shu brauzer sessiyasini ishlatadi — boshqa akkauntdan chiqib ketmaslik uchun yashirin oynada oching.</p>

        <label for="user">Demo foydalanuvchi</label>
        <select id="user">
            <option value="">— tanlang —</option>
            {{-- TW: a new Telegram user without an account -> the WebView registration --}}
            <option value="__fresh" @selected($initial['user'] === '__fresh')>★ Yangi Telegram foydalanuvchi (ro'yxatdan o'tmagan)</option>
            @foreach ($students as $s)
                <option value="{{ $s['id'] }}" @selected($initial['user'] === (string) $s['id'])>{{ $s['label'] }}</option>
            @endforeach
        </select>

        <label for="start">start_param (deep link)</label>
        <input id="start" type="text" value="{{ $initial['start'] }}" maxlength="64" autocomplete="off">
        <div class="chips" id="chips">
            @foreach (['exam', 'placement_math', 'placement_physics', 'battle', 'progress', 'leaderboard'] as $p)
                <button type="button" data-start="{{ $p }}">{{ $p }}</button>
            @endforeach
        </div>

        <label for="theme">Mavzu</label>
        <select id="theme">
            <option value="light" @selected($initial['theme'] === 'light')>Yorug' (Telegram light)</option>
            <option value="dark" @selected($initial['theme'] === 'dark')>Tungi (Telegram dark)</option>
        </select>

        <label for="size">Ekran</label>
        <select id="size">
            @foreach ([[320, 640], [360, 740], [375, 720], [390, 780], [430, 880]] as [$w, $h])
                <option value="{{ $w }}x{{ $h }}" @selected($initial['w'] === $w)>{{ $w }} × {{ $h }}</option>
            @endforeach
            @if (! in_array($initial['w'], [320, 360, 375, 390, 430], true))
                <option value="{{ $initial['w'] }}x{{ $initial['h'] }}" selected>{{ $initial['w'] }} × {{ $initial['h'] }}</option>
            @endif
        </select>

        <label for="platform">Platforma</label>
        <select id="platform">
            <option value="android">android</option>
            <option value="ios">ios</option>
            <option value="weba">weba (Telegram Web)</option>
            <option value="tdesktop">tdesktop</option>
        </select>

        <label><input id="safe" type="checkbox"> Pastki safe area (34 px, iPhone)</label>

        {{-- TW: what requestContact() answers --}}
        <label for="contact-mode">requestContact (telefonni ulashish)</label>
        <select id="contact-mode">
            <option value="share">ulashadi</option>
            <option value="cancel">rad etadi</option>
            <option value="unsupported">versiya 6.9 dan past (qo'lda yozadi)</option>
        </select>
        <input id="contact-phone" type="text" value="{{ $initial['phone'] }}" maxlength="20" autocomplete="off" aria-label="Ulashiladigan telefon">

        <div class="row">
            <button type="button" class="primary" id="open">Ochish</button>
            <button type="button" id="minimise">Yig'ish</button>
            <button type="button" id="reload">Qayta yuklash</button>
            <button type="button" id="new-fresh" title="Keyingi ochishda yangi Telegram id">Yangi id</button>
        </div>
        <div class="log" id="tg-log" aria-live="polite"></div>
    </div>

    <div class="phone">
        <div class="screen" id="screen">
            <div class="tg-header">
                <button type="button" id="tg-back" hidden aria-label="Orqaga">‹ Orqaga</button>
                <button type="button" id="tg-close" aria-label="Yopish">✕</button>
                <span class="title">Shanba imtihoni</span>
                <span class="flag" id="tg-flags"></span>
            </div>
            <iframe id="app" title="Mini App" src="about:blank"></iframe>
            <div class="tg-bottom" id="tg-bottom">
                <button type="button" id="tg-secondary" hidden></button>
                <button type="button" id="tg-main" hidden></button>
            </div>
            <div class="minimised" id="tg-minimised">Mini App yig'ilgan</div>
        </div>
    </div>

    {{-- auth (agent L, docs/auth/TELEGRAM_LOGIN.md §7): what the bot would send (local DevOutbox) + playing the Telegram user --}}
    <div class="panel chat" id="bot-chat">
        <h1>Bot chat</h1>
        <p class="note">Bot yuboradigan xabarlar (faqat local, TELEGRAM_BOT_TOKEN bo'sh). Tugmalar ishlaydi; yozish va kontakt —
            chapda tanlangan Telegram foydalanuvchi nomidan.</p>
        <label><input id="chat-mine" type="checkbox"> Faqat tanlangan foydalanuvchi chati</label>
        <div class="msgs" id="chat-msgs"><div class="empty">Hali xabar yo'q</div></div>
        <div class="send">
            <input id="chat-text" type="text" value="{{ $botStart ? '/start '.$botStart : '/start' }}" maxlength="256" autocomplete="off" aria-label="Botga xabar">
            <button type="button" class="primary" id="chat-send">Botga yuborish</button>
        </div>
        <div class="row">
            <button type="button" id="chat-contact" title="Chapdagi telefon raqam bilan">📱 Kontaktni ulashish</button>
            <button type="button" id="chat-clear">Tozalash</button>
        </div>
        @if ($botStart)
            <p class="note" id="chat-start-note" style="margin-top:10px">Saytdagi «Telegram orqali kirish»: chapda Telegram foydalanuvchini tanlang
                (yangi raqam uchun «★ Yangi Telegram foydalanuvchi»), so'ng «Botga yuborish».</p>
        @endif
    </div>
</div>

<script>
(function () {
    var THEMES = {
        light: { bg_color: '#ffffff', text_color: '#000000', hint_color: '#999999', link_color: '#2481cc', button_color: '#2481cc', button_text_color: '#ffffff', secondary_bg_color: '#efeff3', header_bg_color: '#ffffff', accent_text_color: '#2481cc', section_bg_color: '#ffffff', section_header_text_color: '#6d6d72', subtitle_text_color: '#999999', destructive_text_color: '#ff3b30', section_separator_color: '#c8c7cc', bottom_bar_bg_color: '#f2f2f2' },
        dark: { bg_color: '#212121', text_color: '#ffffff', hint_color: '#aaaaaa', link_color: '#8774e1', button_color: '#8774e1', button_text_color: '#ffffff', secondary_bg_color: '#181818', header_bg_color: '#212121', accent_text_color: '#8774e1', section_bg_color: '#212121', section_header_text_color: '#aaaaaa', subtitle_text_color: '#aaaaaa', destructive_text_color: '#e53935', section_separator_color: '#2f2f2f', bottom_bar_bg_color: '#212121' }
    };
    var initial = @json($initial);
    var $ = function (id) { return document.getElementById(id); };
    var frame = $('app'), screen = $('screen');
    var state = { back: false, main: null, secondary: null, closing: false, swipes: true, minimised: false };

    var log = function (type, data) {
        var row = document.createElement('div');
        row.dataset.event = type;
        row.textContent = new Date().toLocaleTimeString() + ' ' + type + (data && Object.keys(data).length ? ' ' + JSON.stringify(data) : '');
        $('tg-log').prepend(row);
    };
    var theme = function () { return THEMES[$('theme').value] || THEMES.light; };
    var size = function () { var p = $('size').value.split('x'); return { w: +p[0], h: +p[1] }; };
    var send = function (eventType, eventData) {
        if (frame.contentWindow) frame.contentWindow.postMessage(JSON.stringify({ eventType: eventType, eventData: eventData || {} }), '*');
    };
    var paintChrome = function () {
        var t = theme(), s = screen.style;
        s.setProperty('--tg-bg', t.bg_color); s.setProperty('--tg-header', t.header_bg_color); s.setProperty('--tg-text', t.text_color);
        s.setProperty('--tg-link', t.link_color); s.setProperty('--tg-hint', t.hint_color); s.setProperty('--tg-bottom', t.bottom_bar_bg_color);
        var sz = size();
        screen.style.width = sz.w + 'px';
        screen.style.height = sz.h + 'px';
    };
    var viewport = function () {
        var h = frame.clientHeight;
        send('viewport_changed', { height: h, width: frame.clientWidth, is_state_stable: true, is_expanded: true });
    };
    var safeArea = function () {
        send('safe_area_changed', { top: 0, bottom: $('safe').checked ? 34 : 0, left: 0, right: 0 });
        send('content_safe_area_changed', { top: 0, bottom: 0, left: 0, right: 0 });
    };
    var renderButtons = function () {
        var any = false;
        [['tg-main', state.main], ['tg-secondary', state.secondary]].forEach(function (pair) {
            var el = $(pair[0]), b = pair[1];
            if (b && b.is_visible) {
                any = true;
                el.hidden = false;
                el.textContent = (b.is_progress_visible ? '… ' : '') + (b.text || '');
                el.disabled = b.is_active === false || !!b.is_progress_visible;
                el.style.background = b.color || theme().button_color;
                el.style.color = b.text_color || theme().button_text_color;
            } else {
                el.hidden = true;
            }
        });
        $('tg-bottom').classList.toggle('visible', any);
        $('tg-back').hidden = !state.back;
        $('tg-flags').textContent = (state.closing ? 'yopishni so\'raydi ' : '') + (state.swipes ? '' : '· swipe o\'chiq');
        setTimeout(viewport, 0);
    };

    window.addEventListener('message', function (e) {
        if (e.source !== frame.contentWindow) return;
        var msg;
        try { msg = typeof e.data === 'string' ? JSON.parse(e.data) : e.data; } catch (err) { return; }
        if (!msg || !msg.eventType) return;
        var d = msg.eventData || {};
        switch (msg.eventType) {
            case 'web_app_request_viewport': viewport(); return;
            case 'web_app_request_theme': send('theme_changed', { theme_params: theme() }); return;
            case 'web_app_request_safe_area': safeArea(); return;
            case 'web_app_setup_back_button': state.back = !!d.is_visible; renderButtons(); break;
            case 'web_app_setup_main_button': state.main = d; renderButtons(); break;
            case 'web_app_setup_secondary_button': state.secondary = d; renderButtons(); break;
            case 'web_app_setup_closing_behavior': state.closing = !!d.need_confirmation; renderButtons(); break;
            case 'web_app_setup_swipe_behavior': state.swipes = !!d.allow_vertical_swipe; renderButtons(); break;
            case 'web_app_trigger_haptic_feedback':
                screen.classList.remove('shake'); void screen.offsetWidth; screen.classList.add('shake');
                break;
            case 'web_app_open_link': window.open(d.url, '_blank', 'noopener'); break;
            // TW: requestContact -> phone_requested + getRequestedContact (like the real SDK)
            case 'web_app_request_phone':
                if ($('contact-mode').value !== 'share') { send('phone_requested', { status: 'cancelled' }); break; }
                fetch(@json(route('telegram.dev.contact', [], false)), {
                    method: 'POST', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ telegram_id: state.telegramId, phone: $('contact-phone').value }),
                }).then(function (r) { return r.json(); }).then(function (res) {
                    state.contact = res.response || '';
                    send('phone_requested', { status: 'sent' });
                });
                break;
            case 'web_app_invoke_custom_method':
                if (d.method === 'getRequestedContact') send('custom_method_invoked', { req_id: d.req_id, result: state.contact || '' });
                break;
            case 'web_app_request_write_access': send('write_access_requested', { status: 'allowed' }); break;
            case 'web_app_close': frame.src = 'about:blank'; break;
            default: break;
        }
        log(msg.eventType, d);
    });

    $('tg-back').addEventListener('click', function () { send('back_button_pressed'); log('back_button_pressed'); });
    $('tg-main').addEventListener('click', function () { send('main_button_pressed'); log('main_button_pressed'); });
    $('tg-secondary').addEventListener('click', function () { send('secondary_button_pressed'); log('secondary_button_pressed'); });
    $('tg-close').addEventListener('click', function () {
        if (state.closing && !window.confirm("O'zgarishlar saqlanmasligi mumkin. Yopilsinmi?")) { log('close_cancelled'); return; }
        frame.src = 'about:blank';
        state = { back: false, main: null, secondary: null, closing: false, swipes: true, minimised: false };
        renderButtons();
        log('closed');
    });
    $('minimise').addEventListener('click', function () {
        state.minimised = !state.minimised;
        $('tg-minimised').classList.toggle('on', state.minimised);
        $('minimise').textContent = state.minimised ? 'Ochish (restore)' : "Yig'ish";
        send('visibility_changed', { is_visible: !state.minimised });
        log('visibility_changed', { is_visible: !state.minimised });
    });
    $('theme').addEventListener('change', function () { paintChrome(); send('theme_changed', { theme_params: theme() }); renderButtons(); });
    $('size').addEventListener('change', function () { paintChrome(); setTimeout(viewport, 50); });
    $('safe').addEventListener('change', safeArea);
    $('reload').addEventListener('click', function () { try { frame.contentWindow.location.reload(); } catch (e) { open(); } });
    $('chips').addEventListener('click', function (e) { if (e.target.dataset.start) $('start').value = e.target.dataset.start; });

    var open = function () {
        var user = $('user').value;
        if (!user) { log('error', { message: "Demo foydalanuvchini tanlang" }); return; }
        var start = $('start').value.trim();
        var fresh = user === '__fresh';
        var body = fresh ? { fresh: true, telegram_id: window.tgFreshId || null, start_param: start || null } : { user_id: user, start_param: start || null };
        fetch(@json(route('telegram.dev.sign', [], false)), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
            body: JSON.stringify(body),
        }).then(function (r) { return r.json(); }).then(function (res) {
            if (!res.init_data) { log('error', res); return; }
            state.telegramId = res.telegram_id;
            if (fresh) window.tgFreshId = res.telegram_id;
            state = { back: false, main: null, secondary: null, closing: false, swipes: true, minimised: false, telegramId: res.telegram_id };
            renderButtons();
            var version = $('contact-mode').value === 'unsupported' ? '6.7' : '8.0';
            var hash = 'tgWebAppData=' + encodeURIComponent(res.init_data)
                + '&tgWebAppVersion=' + version + '&tgWebAppPlatform=' + encodeURIComponent($('platform').value)
                + '&tgWebAppThemeParams=' + encodeURIComponent(JSON.stringify(theme()));
            frame.src = res.app_url + (start ? '&tgWebAppStartParam=' + encodeURIComponent(start) : '') + '#' + hash;
            log('open', { telegram_id: res.telegram_id, start_param: start });
        }).catch(function (err) { log('error', { message: String(err) }); });
    };
    $('open').addEventListener('click', open);
    $('new-fresh').addEventListener('click', function () { window.tgFreshId = null; log('fresh_id_reset'); });
    frame.addEventListener('load', function () { setTimeout(function () { viewport(); safeArea(); }, 50); });

    paintChrome();
    if (initial.autostart && $('user').value) open();
    window.tgEmulator = { send: send, state: function () { return state; }, open: open };
})();
</script>
{{-- auth (agent L): local bot chat --}}
<script>
(function () {
    var $ = function (id) { return document.getElementById(id); };
    var csrf = function () { return document.querySelector('meta[name=csrf-token]').content; };
    var post = function (url, body, method) {
        return fetch(url, { method: method || 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
            body: body ? JSON.stringify(body) : undefined }).then(function (r) { return r.json(); });
    };
    var urls = { outbox: @json(route('telegram.dev.outbox', [], false)), update: @json(route('telegram.dev.update', [], false)), sign: @json(route('telegram.dev.sign', [], false)) };
    var last = '';
    var picked = { user: null, id: null };

    // Telegram HTML (b, i, code) on top of escaped text
    var html = function (text) {
        var esc = String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return esc.replace(/&lt;(\/?)(b|i|code|u|s)&gt;/g, '<$1$2>').replace(/&amp;(amp|lt|gt|quot|#039|apos);/g, '&$1;');
    };

    // the Telegram id of the user chosen on the left (signing creates the demo user's fake account)
    var currentTg = function () {
        var user = $('user').value;
        if (!user) return Promise.reject(new Error('Chapda Telegram foydalanuvchini tanlang'));
        if (picked.id && picked.user === user && (user !== '__fresh' || window.tgFreshId === picked.id)) return Promise.resolve(picked.id);
        var fresh = user === '__fresh';
        return post(urls.sign, fresh ? { fresh: true, telegram_id: window.tgFreshId || null } : { user_id: user }).then(function (res) {
            if (!res.telegram_id) throw new Error('sign failed');
            if (fresh) window.tgFreshId = res.telegram_id;
            picked = { user: user, id: res.telegram_id };
            return res.telegram_id;
        });
    };
    var asUser = function (body) {
        return currentTg().then(function (id) { return post(urls.update, Object.assign({ telegram_id: id }, body)); })
            .then(refresh).catch(function (e) { alert(e.message); });
    };
    var openApp = function (m, url) {
        var to = '';
        try { to = new URL(url, location.origin).searchParams.get('to') || ''; } catch (e) { /* ignore */ }
        var select = $('user');
        var option = m.user_id ? select.querySelector('option[value="' + m.user_id + '"]') : null;
        if (option) { select.value = m.user_id; } else { select.value = '__fresh'; window.tgFreshId = m.telegram_id; }
        picked = { user: select.value, id: m.telegram_id };
        $('start').value = to;
        window.tgEmulator.open();
    };
    var press = function (m, b, btn) {
        if (b.callback_data) { post(urls.update, { telegram_id: m.telegram_id, callback: b.callback_data }).then(refresh); return; }
        if (b.request_contact) { post(urls.update, { telegram_id: m.telegram_id, phone: $('contact-phone').value }).then(refresh); return; }
        if (b.copy_text) {
            try { navigator.clipboard.writeText(b.copy_text.text); } catch (e) { /* ignore */ }
            btn.textContent = '✓ ' + b.copy_text.text;
            return;
        }
        var url = (b.web_app && b.web_app.url) || b.url || '';
        if (url.indexOf('/telegram/app') !== -1) { openApp(m, url); return; }
        if (url) window.open(url, '_blank', 'noopener');
    };
    var render = function (messages) {
        var box = $('chat-msgs');
        var mine = $('chat-mine').checked ? (picked.id || -1) : null;
        var list = messages.filter(function (m) { return mine === null || m.telegram_id === mine; });
        var key = JSON.stringify(list.map(function (m) { return m.id; })) + mine;
        if (key === last) return;
        last = key;
        box.innerHTML = '';
        if (!list.length) { box.innerHTML = '<div class="empty">Hali xabar yo\'q</div>'; return; }
        list.forEach(function (m) {
            var el = document.createElement('div');
            el.className = 'msg';
            el.dataset.msg = m.id;
            el.dataset.chat = m.telegram_id;
            el.innerHTML = '<div class="meta"></div><div class="text"></div>';
            el.querySelector('.meta').textContent = '→ ' + m.to + ' · ' + m.at;
            el.querySelector('.text').innerHTML = html(m.text);
            var mk = m.markup || {};
            var rows = mk.inline_keyboard || mk.keyboard || [];
            if (rows.length) {
                var kb = document.createElement('div');
                kb.className = 'kb' + (mk.keyboard ? ' reply' : '');
                rows.forEach(function (row) {
                    var r = document.createElement('div'); r.className = 'kbrow';
                    row.forEach(function (b) {
                        var btn = document.createElement('button');
                        btn.type = 'button';
                        btn.textContent = b.text;
                        btn.addEventListener('click', function () { press(m, b, btn); });
                        r.appendChild(btn);
                    });
                    kb.appendChild(r);
                });
                el.appendChild(kb);
            }
            box.appendChild(el);
        });
        box.scrollTop = box.scrollHeight;
    };
    var refresh = function () {
        return fetch(urls.outbox, { credentials: 'same-origin', headers: { Accept: 'application/json' } })
            .then(function (r) { return r.json(); }).then(function (res) { render(res.messages || []); }).catch(function () {});
    };

    $('chat-send').addEventListener('click', function () { asUser({ text: $('chat-text').value.trim() || '/start' }); });
    $('chat-text').addEventListener('keydown', function (e) { if (e.key === 'Enter') $('chat-send').click(); });
    $('chat-contact').addEventListener('click', function () { asUser({ phone: $('contact-phone').value }); });
    $('chat-clear').addEventListener('click', function () { post(urls.outbox, null, 'DELETE').then(function () { last = ''; refresh(); }); });
    $('chat-mine').addEventListener('change', function () { last = ''; refresh(); });
    window.tgBotChat = { refresh: refresh, asUser: asUser };
    refresh();
    setInterval(refresh, 2000);
})();
</script>
</body>
</html>
