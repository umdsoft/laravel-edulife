<?php

/*
 * "Shanba imtihoni" exam module settings. Each stage keeps its keys in its
 * own commented block; add new blocks below, do not reorder others.
 */

return [

    // --- exam: question bank, review queue, import (stage 3) ---
    'bank' => [
        // "bu hafta tasdiqlandi: N / target" on the review queue
        'weekly_review_target' => (int) (env('EXAM_WEEKLY_REVIEW_TARGET') ?: 100),
        'per_page' => 20,
        'import' => [
            'max_rows' => 500,
            'max_file_kb' => 4096,
            // how long a parsed file waits for the teacher's confirmation
            'preview_ttl_minutes' => 120,
        ],
    ],
    // --- end exam: question bank (stage 3) ---

    // --- exam: reports, leaderboards, result card, referral (stage 7) ---
    'leaderboard' => [
        // schools appear only with at least this many participants in the week
        'school_min_participants' => (int) (env('EXAM_SCHOOL_MIN_PARTICIPANTS') ?: 5),
        'top' => 50,
        'cache_minutes' => 10,
    ],
    'report' => [
        // Sunday report (routes/console.php), Asia/Tashkent
        'weekly_day' => 0,          // 0 = Sunday
        'weekly_time' => '09:00',
        'recommendations' => 3,
    ],
    'result_card' => [
        'width' => 1080,
        'height' => 1920,
        // TTF fonts with Uzbek Latin glyphs (o', g', sh); DejaVu ships with dompdf
        'font_regular' => env('EXAM_CARD_FONT_REGULAR') ?: base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans.ttf'),
        'font_bold' => env('EXAM_CARD_FONT_BOLD') ?: base_path('vendor/dompdf/dompdf/lib/fonts/DejaVuSans-Bold.ttf'),
    ],
    'referral' => [
        // funnel stage that earns the referrer the bonus (once per invited user)
        'reward_stage' => env('EXAM_REFERRAL_REWARD_STAGE') ?: 'placement_done',
        'cookie_days' => 30,
    ],
    // --- end exam: stage 7 ---

    // --- exam: AI draft generator (stage 4) ---
    // Every code path no-ops (with a log line) while ANTHROPIC_API_KEY is empty.
    'ai' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'base_url' => env('ANTHROPIC_BASE_URL') ?: 'https://api.anthropic.com',
        'api_version' => '2023-06-01',
        'model' => env('EXAM_AI_MODEL') ?: 'claude-opus-5-5',
        // low | medium | high | xhigh | max (claude-opus-5-5 defaults to medium)
        'effort' => env('EXAM_AI_EFFORT') ?: 'high',
        'max_tokens' => (int) (env('EXAM_AI_MAX_TOKENS') ?: 16000),
        'timeout_seconds' => (int) (env('EXAM_AI_TIMEOUT') ?: 300),
        // Server-side refusal fallback ("default" = Anthropic routes by refusal
        // category). Empty string turns it off.
        'fallbacks' => env('EXAM_AI_FALLBACKS', 'default'),
        'fallbacks_beta' => 'server-side-fallback-2026-07-01',
        'prompt' => 'question_draft', // resources/prompts/exam/<prompt>.md
        // Store the AI's own guess of the correct option as a hidden reviewer
        // hint (question_bank_options.ai_suggested_correct). is_correct is
        // never written by the AI either way.
        'store_suggested_answer' => (bool) env('EXAM_AI_STORE_HINT', false),
        'max_count_per_run' => 20,
        'max_source_chars' => 40000,
        'source_disk' => 'local', // storage/app/exam-sources/
        'source_dir' => 'exam-sources',
        'source_max_kb' => 2048,
        // USD per 1M tokens (https://www.anthropic.com/pricing); the cost log
        // is an estimate from response.usage.
        'pricing' => [
            'claude-opus-5-5' => ['input' => 4.00, 'output' => 20.00, 'cache_write' => 5.00, 'cache_read' => 0.20],
            'claude-sonnet-5-5' => ['input' => 2.00, 'output' => 10.00, 'cache_write' => 2.50, 'cache_read' => 0.20],
            'claude-haiku-5-5' => ['input' => 0.10, 'output' => 0.50, 'cache_write' => 0.125, 'cache_read' => 0.01],
        ],
        'weekly' => [
            // php artisan exam:ai-drafts-weekly (routes/console.php, Monday 03:00)
            'enabled' => (bool) env('EXAM_AI_WEEKLY_ENABLED', true),
            'target' => (int) (env('EXAM_AI_WEEKLY_TARGET') ?: 100),
            'batch_size' => 10,
            // no new run is started once this week's estimated cost reaches it
            'budget_usd' => (float) (env('EXAM_AI_WEEKLY_BUDGET_USD') ?: 15),
            'difficulties' => [2, 3, 4],
        ],
    ],
    // --- end exam: stage 4 ---

    // --- exam: Telegram bot + Mini App (stage 6) ---
    // Every code path no-ops (with a log line) while TELEGRAM_BOT_TOKEN is empty.
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'bot_username' => env('TELEGRAM_BOT_USERNAME'),
        // sent by Telegram in X-Telegram-Bot-Api-Secret-Token (setWebhook secret_token)
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'api_base' => 'https://api.telegram.org',
        // HTTPS URL of the Mini App page; empty = route('telegram.app')
        'mini_app_url' => env('TELEGRAM_MINI_APP_URL'),
        // initData older than this is rejected (seconds)
        'init_data_ttl' => (int) (env('TELEGRAM_INIT_DATA_TTL') ?: 86400),
        'timeout_seconds' => 10,
        // pause between bulk messages (Telegram allows ~30 messages/second)
        'send_delay_ms' => 50,
        'reminders' => [
            'eve_time' => '19:00',     // evening before the exam (Friday for Saturday)
            'before_minutes' => 60,    // "1 soat qoldi"
            'start_grace_minutes' => 15,
        ],
        // --- Telegram WebView (wave 2, agent T) ---
        // The Mini App SDK (https://core.telegram.org/bots/webapps#initializing-mini-apps)
        'sdk_url' => 'https://telegram.org/js/telegram-web-app.js',
        // Telegram Web runs the Mini App in a cross-site iframe: on HTTPS the session and
        // XSRF cookies of Mini App sessions are sent SameSite=None; Secure (+ Partitioned, CHIPS).
        // The website's own cookies keep config/session.php (lax).
        'webview_cookie' => [
            'same_site' => env('TELEGRAM_WEBVIEW_SAME_SITE') ?: 'none',   // none | lax
            'partitioned' => in_array(env('TELEGRAM_WEBVIEW_PARTITIONED'), [null, ''], true) ? true : (bool) env('TELEGRAM_WEBVIEW_PARTITIONED'),
        ],
        // a friend's battle challenge (t.me/<bot>?startapp=battle_<code>) stays open this long
        'challenge_minutes' => 15,
        // /telegram/dev emulator (APP_ENV=local only): signs initData with this token when
        // TELEGRAM_BOT_TOKEN is empty, so the Mini App can be tried without a bot
        'dev_bot_token' => '000000000:DEV-EMULATOR-ONLY',
        // bot commands set by telegram:set-webhook / telegram:setup-menu (setMyCommands)
        'commands' => [
            'imtihon' => 'Imtihonlar va qabul testi',
            'battle' => 'Bilimlar jangi (battle)',
            'natijalar' => 'Oxirgi natijalar',
            'taklif' => "Do'stni taklif qilish",
            'yordam' => 'Yordam',
            'bekor' => "Ro'yxatdan o'tishni qaytadan boshlash",
        ],
        // --- end Telegram WebView ---
    ],
    // --- end exam: stage 6 ---

    // --- exam: variant generator, exam engine, grading, mastery (stage 5) ---
    'engine' => [
        // Default for new blueprints: avoid questions the student saw in the
        // last N weeks (each blueprint stores its own avoid_repeat_weeks).
        'avoid_repeat_weeks' => (int) (env('EXAM_AVOID_REPEAT_WEEKS') ?: 4),
        // exam:tick pre-generates variants for sessions starting within this window
        'pregenerate_hours' => (int) (env('EXAM_PREGENERATE_HOURS') ?: 24),
        // answers that arrive this many seconds after the deadline still count (network lag)
        'answer_grace_seconds' => 15,
        'autosave_seconds' => 15,
        'max_batch_answers' => 100,
        'violation_log_limit' => 100,
        // EMA weight of a new observation (docs/exam/02_DATA_MODEL.md §6)
        'mastery_alpha' => [
            'placement' => 1.0,
            'weekly' => 0.3,
            'open_regional' => 0.3,
        ],
        // Default Saturday window offered by the teacher's session form
        'default_session' => ['start' => '10:00', 'end' => '13:00'],
        // Units offered by the numeric_with_unit picker (merged with the
        // question's own canonical and accepted units, shown sorted, so the
        // list does not give the expected unit away).
        'unit_catalog' => [
            'mm', 'sm', 'm', 'km', 'g', 'kg', 't', 's', 'min', 'soat', 'm/s', 'km/soat', 'm/s²',
            'N', 'kN', 'J', 'kJ', 'MJ', 'W', 'kW', 'Pa', 'kPa', 'atm', 'K', '°C', 'mol', 'Hz',
            'A', 'mA', 'V', 'kV', 'Ω', 'kΩ', 'C', 'μC', 'F', 'μF', 'T', 'Wb', 'H', 'eV', 'nm', 'm³', 'l', 'kg/m³',
        ],
    ],
    // --- end exam: stage 5 ---

];
