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
    ],
    // --- end exam: stage 6 ---

];
