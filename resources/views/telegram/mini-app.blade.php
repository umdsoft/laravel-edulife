<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $texts['title'] ?? 'Shanba imtihoni' }}</title>
    {{-- Stage 6 + Telegram WebView: Mini App entry. Verifies initData on the server, then opens the exam / battle pages. --}}
    <script src="{{ $sdkUrl }}"></script>
    <style>
        :root { color-scheme: light dark; }
        html, body { margin: 0; height: 100%; }
        body {
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            background: var(--tg-theme-bg-color, #ffffff);
            color: var(--tg-theme-text-color, #111827);
            display: flex; align-items: center; justify-content: center; text-align: center;
            padding: 0 16px; box-sizing: border-box;
        }
        .box { max-width: 22rem; }
        .spinner {
            width: 2rem; height: 2rem; margin: 0 auto 1rem; border-radius: 50%;
            border: 3px solid color-mix(in srgb, var(--tg-theme-button-color, #4f46e5) 25%, transparent);
            border-top-color: var(--tg-theme-button-color, #4f46e5);
            animation: spin .8s linear infinite;
        }
        .hidden { display: none; }
        p { line-height: 1.5; margin: 0; }
        a.site { display: inline-block; margin-top: 1rem; padding: .6rem 1rem; border-radius: .6rem; text-decoration: none;
            background: var(--tg-theme-button-color, #4f46e5); color: var(--tg-theme-button-text-color, #fff); font-weight: 600; }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="box">
        <div id="spinner" class="spinner" aria-hidden="true"></div>
        <p id="message" role="status">{{ $texts['loading'] ?? '' }}</p>
    </div>
    <form id="start-form" method="POST" class="hidden"><input type="hidden" name="_token" value=""></form>
    <script>
        (function () {
            var texts = @json($texts);
            var to = @json($to);
            var returnTo = @json($returnTo);
            var sdkUrl = @json($sdkUrl);
            var message = document.getElementById('message');
            var spinner = document.getElementById('spinner');
            var show = function (text) { spinner.classList.add('hidden'); message.textContent = text; };

            var tg = window.Telegram && window.Telegram.WebApp;
            if (!tg || !tg.initData) { show(texts.open_in_telegram); return; }
            tg.ready();
            tg.expand();

            // The app pages load the same SDK even if the session cookie gets lost (app.blade.php),
            // and a re-login loop (cookies blocked in Telegram Web's iframe) stops after 3 tries a minute.
            try {
                sessionStorage.setItem('edulife_tg_sdk', sdkUrl);
                var now = Date.now();
                var tries = JSON.parse(sessionStorage.getItem('edulife_tg_auth') || '[]').filter(function (t) { return now - t < 60000; });
                tries.push(now);
                sessionStorage.setItem('edulife_tg_auth', JSON.stringify(tries));
                if (tries.length > 3) { show(texts.cookies_blocked || texts.error); return; }
            } catch (e) { /* storage unavailable */ }

            fetch(@json(route('telegram.app.auth', [], false)), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    init_data: tg.initData,
                    to: to || (tg.initDataUnsafe && tg.initDataUnsafe.start_param) || null,
                    'return': returnTo || null,
                }),
            })
                .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
                .then(function (res) {
                    // TW (WebView-first): not registered yet -> the registration screen inside the Mini App
                    if (!res.ok && res.body && res.body.register) {
                        message.textContent = texts.registering || texts.loading;
                        window.location.replace(res.body.register);
                        return;
                    }
                    if (!res.ok || !res.body.redirect) {
                        show((res.body && res.body.message) || texts.error);
                        if (res.body && res.body.site) {
                            var a = document.createElement('a');
                            a.href = res.body.site; a.textContent = 'Saytda ochish'; a.className = 'site';
                            a.addEventListener('click', function (e) { if (tg.openLink) { e.preventDefault(); tg.openLink(res.body.site); } });
                            message.parentNode.appendChild(a);
                        }
                        return;
                    }
                    if (res.body.post && res.body.csrf_token) {
                        // starting a test is a POST (placement_<subject> deep link)
                        message.textContent = texts.starting || texts.loading;
                        var form = document.getElementById('start-form');
                        form.action = res.body.post;
                        form.querySelector('input[name=_token]').value = res.body.csrf_token;
                        form.submit();
                        return;
                    }
                    window.location.replace(res.body.redirect);
                })
                .catch(function () { show(texts.error); });
        })();
    </script>
</body>
</html>
