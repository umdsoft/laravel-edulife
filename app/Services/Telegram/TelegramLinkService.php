<?php

namespace App\Services\Telegram;

use App\Models\Exam\TelegramAccount;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Links an existing web account (guardian, student registered on the site)
 * to Telegram: the logged-in user opens t.me/<bot>?start=link_<token>; the
 * one-time token lives 15 minutes in the cache.
 */
class TelegramLinkService
{
    private const TTL_SECONDS = 900;

    public function createToken(User $user): string
    {
        $token = Str::random(32);
        Cache::put('telegram-link:'.$token, $user->id, self::TTL_SECONDS);

        return $token;
    }

    /** @return bool linked */
    public function consume(TelegramAccount $account, string $token): bool
    {
        if (! preg_match('/^[A-Za-z0-9]{32}$/', $token)) {
            return false;
        }
        $userId = Cache::pull('telegram-link:'.$token);
        $user = $userId ? User::query()->find($userId) : null;
        if ($user === null) {
            return false;
        }

        // One Telegram account per user: the explicit link from the site moves it.
        TelegramAccount::query()->where('user_id', $user->id)->where('id', '!=', $account->id)
            ->update(['user_id' => null, 'linked_at' => null]);
        $account->forceFill(['user_id' => $user->id, 'linked_at' => now(), 'is_blocked' => false])->save();
        Log::info('telegram: account linked from the site', ['account' => $account->id, 'user' => $user->id]);

        return true;
    }
}
