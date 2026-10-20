<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\StudentWeeklyResult;
use App\Services\Exam\ResultCardRenderer;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

/**
 * Telegram WebView (wave 2): the result card PNG behind a temporary signed URL.
 *
 * WebApp.shareToStory(media_url) needs an HTTPS URL Telegram's servers can fetch
 * without our session, so the Mini App's "Ulashish" gets a link signed for
 * 30 minutes (route middleware "signed"). It shows only what the student is
 * sharing (the card they could download anyway).
 */
class ShareCardController extends Controller
{
    public const TTL_MINUTES = 30;

    public static function urlFor(ExamAttempt $attempt): string
    {
        return URL::temporarySignedRoute('telegram.card', now()->addMinutes(self::TTL_MINUTES), ['attempt' => $attempt->id]);
    }

    public function __invoke(ExamAttempt $attempt, ResultCardRenderer $renderer): Response
    {
        $result = StudentWeeklyResult::query()->where('attempt_id', $attempt->id)->where('user_id', $attempt->user_id)->first();
        abort_if($result === null || $attempt->user === null, 404);

        $png = $renderer->render($result, $attempt->user);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Length' => (string) strlen($png),
            'Content-Disposition' => 'inline; filename="natija-kartasi.png"',
            'Cache-Control' => 'private, max-age=600',
        ]);
    }
}
