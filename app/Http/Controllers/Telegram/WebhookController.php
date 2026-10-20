<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\Telegram\BotUpdateHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST /telegram/webhook — Telegram Bot API updates.
 *
 * Authenticated by the secret_token given to setWebhook, which Telegram
 * echoes in X-Telegram-Bot-Api-Secret-Token. Without a configured token and
 * secret the endpoint does not exist (404). Rate limited per IP (routes).
 * Always answers 200 for an authenticated call so Telegram does not retry a
 * failing update forever.
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request, BotUpdateHandler $handler): JsonResponse
    {
        $secret = (string) config('exam.telegram.webhook_secret');
        if (trim((string) config('exam.telegram.bot_token')) === '' || $secret === '') {
            abort(404);
        }

        $given = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
        if (! hash_equals($secret, $given)) {
            Log::warning('telegram: webhook call with a wrong secret');
            abort(403);
        }

        $update = $request->json()->all();
        if (! is_array($update) || ! isset($update['update_id'])) {
            return response()->json(['ok' => true]);
        }

        try {
            $handler->handle($update);
        } catch (\Throwable $e) {
            // No report($e): messages of e.g. QueryException carry the bound
            // values (phone numbers); log the class and place only.
            Log::error('telegram: update failed', [
                'update_id' => (int) $update['update_id'],
                'error' => class_basename($e),
                'at' => basename($e->getFile()).':'.$e->getLine(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
