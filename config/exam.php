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

];
