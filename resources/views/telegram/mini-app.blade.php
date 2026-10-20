<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex">
    <title>{{ $texts['title'] ?? 'Shanba imtihoni' }}</title>
    {{-- Stage 6: Telegram Mini App entry. Verifies initData on the server, then opens the exam pages. --}}
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
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
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div class="box">
        <div id="spinner" class="spinner" aria-hidden="true"></div>
        <p id="message" role="status">{{ $texts['loading'] ?? '' }}</p>
    </div>
    <script>
        (function () {
            var texts = @json($texts);
            var to = @json($to);
            var message = document.getElementById('message');
            var spinner = document.getElementById('spinner');
            var show = function (text) { spinner.classList.add('hidden'); message.textContent = text; };

            var tg = window.Telegram && window.Telegram.WebApp;
            if (!tg || !tg.initData) { show(texts.open_in_telegram); return; }
            tg.ready();
            tg.expand();

            fetch(@json(route('telegram.app.auth')), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ init_data: tg.initData, to: to || (tg.initDataUnsafe && tg.initDataUnsafe.start_param) || null }),
            })
                .then(function (r) { return r.json().then(function (body) { return { ok: r.ok, body: body }; }); })
                .then(function (res) {
                    if (res.ok && res.body.redirect) { window.location.replace(res.body.redirect); return; }
                    show((res.body && res.body.message) || texts.error);
                })
                .catch(function () { show(texts.error); });
        })();
    </script>
</body>
</html>
