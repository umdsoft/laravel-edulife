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

];
