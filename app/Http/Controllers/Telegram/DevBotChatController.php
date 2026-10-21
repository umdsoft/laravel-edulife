<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Models\Exam\TelegramAccount;
use App\Services\Telegram\BotUpdateHandler;
use App\Services\Telegram\DevOutbox;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Local "bot chat" of the /telegram/dev emulator (agent L, docs/auth/TELEGRAM_LOGIN.md §7).
 *
 * GET    /telegram/dev/outbox   the messages the bot would have sent (DevOutbox), with recipients
 * DELETE /telegram/dev/outbox   clear them
 * POST   /telegram/dev/update   play the Telegram user: a text (/start login_…), a shared contact or a
 *                               button press (callback_data) goes straight to BotUpdateHandler
 *
 * APP_ENV=local only (404 otherwise). Nothing is sent to Telegram.
 */
class DevBotChatController extends Controller
{
    public function outbox(): JsonResponse
    {
        $this->guard();
        $messages = DevOutbox::all();
        $ids = array_values(array_unique(array_column($messages, 'chat_id')));
        $accounts = [];
        foreach (TelegramAccount::query()->with('user:id,first_name,last_name,role')
            ->whereIn('chat_id', $ids)->orWhereIn('telegram_id', $ids)->get() as $a) {
            $accounts[(int) $a->telegram_id] = $a;
            $accounts[(int) $a->chat_id] = $a;
        }

        return response()->json(['messages' => array_map(function (array $m) use ($accounts) {
            $account = $accounts[$m['chat_id']] ?? null;
            $name = $account?->user ? trim($account->user->first_name.' '.$account->user->last_name) : trim(($account?->first_name ?? '').' (ro\'yxatsiz)');

            return $m + [
                'to' => $name !== '' ? $name : 'Telegram '.$m['chat_id'],
                'telegram_id' => (int) ($account?->telegram_id ?? $m['chat_id']),
                'user_id' => $account?->user_id,
            ];
        }, $messages)]);
    }

    public function clear(): JsonResponse
    {
        $this->guard();
        DevOutbox::clear();

        return response()->json(['ok' => true]);
    }

    public function update(Request $request, BotUpdateHandler $handler): JsonResponse
    {
        $this->guard();
        $data = $request->validate([
            'telegram_id' => ['required', 'integer', 'min:1'],
            'first_name' => ['nullable', 'string', 'max:64'],
            'text' => ['nullable', 'string', 'max:256'],
            'callback' => ['nullable', 'string', 'max:64'],
            'phone' => ['nullable', 'string', 'max:20'],
            'contact_of' => ['nullable', 'integer', 'min:1'], // a contact of someone else (the bot must refuse it)
        ]);

        $id = (int) $data['telegram_id'];
        $from = ['id' => $id, 'is_bot' => false, 'first_name' => $data['first_name'] ?? 'Sinov', 'language_code' => 'uz'];
        $chat = ['id' => $id, 'type' => 'private'];
        $update = ['update_id' => random_int(1, PHP_INT_MAX)];

        if (! empty($data['callback'])) {
            $update['callback_query'] = ['id' => 'DEV'.Str::random(12), 'from' => $from, 'data' => $data['callback'], 'message' => ['message_id' => 1, 'chat' => $chat]];
        } elseif (! empty($data['phone'])) {
            $update['message'] = ['message_id' => random_int(1, 1_000_000), 'date' => time(), 'from' => $from, 'chat' => $chat, 'contact' => [
                'phone_number' => preg_replace('/[^0-9+]/', '', $data['phone']),
                'first_name' => $from['first_name'],
                'user_id' => (int) ($data['contact_of'] ?? $id),
            ]];
        } else {
            $update['message'] = ['message_id' => random_int(1, 1_000_000), 'date' => time(), 'from' => $from, 'chat' => $chat, 'text' => (string) ($data['text'] ?? '/start')];
        }

        $handler->handle($update);

        return response()->json(['ok' => true]);
    }

    private function guard(): void
    {
        abort_unless(app()->environment('local'), 404);
    }
}
